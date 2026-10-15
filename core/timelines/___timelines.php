<?php
/**
 * Coverage Timelines: curated story definitions, article classification and timeline assembly.
 *
 * Layers (never mixed in the UI):
 *  - confirmed : article rows exactly as stored (title, publisher, timestamp, link)
 *  - computed  : counts derived from confirmed rows (articles, publishers, peak day, gaps)
 *  - editorial : hand-written story dek, chapters and curation notes
 *
 * The classifier and assembler are pure functions so they can be unit tested without a database.
 */

if (!defined('SN_TL_TZ')) {
    define('SN_TL_TZ', 'America/New_York');
}

// Bump when the stored model format (not the engine logic, which is hashed) changes.
if (!defined('SN_TL_MODEL_FORMAT')) {
    define('SN_TL_MODEL_FORMAT', 'sntl1');
}
// The local file cache sits in front of the database cache and only needs to be short-lived.
if (!defined('SN_TL_LOCAL_TTL')) {
    define('SN_TL_LOCAL_TTL', 60);
}

function sn_tl_stories_dir(): string
{
    return __DIR__ . '/stories';
}

/** @return array<string,array> story definitions keyed by slug */
function sn_tl_all_stories(): array
{
    static $stories = null;
    if ($stories !== null) {
        return $stories;
    }
    $stories = [];
    foreach (glob(sn_tl_stories_dir() . '/*.php') ?: [] as $file) {
        $def = require $file;
        if (is_array($def) && !empty($def['slug'])) {
            $def['_file'] = $file;
            $stories[$def['slug']] = $def;
        }
    }
    uasort($stories, static fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
    return $stories;
}

function sn_tl_get_story(?string $slug): ?array
{
    $slug = strtolower(trim((string)$slug));
    if ($slug === '' || !preg_match('/^[a-z0-9-]{1,60}$/', $slug)) {
        return null;
    }
    return sn_tl_all_stories()[$slug] ?? null;
}

/** Find a curated story whose alias exactly matches a search query or entity label. */
function sn_tl_find_story_by_alias(string $text): ?array
{
    $needle = sn_tl_norm($text);
    if ($needle === '') {
        return null;
    }
    foreach (sn_tl_all_stories() as $story) {
        foreach ($story['aliases'] ?? [] as $alias) {
            if (sn_tl_norm($alias) === $needle) {
                return $story;
            }
        }
    }
    return null;
}

function sn_tl_norm(string $s): string
{
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = str_replace(["\u{2019}", "\u{2018}"], "'", $s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return (string)$s;
}

/** Normalised title used for exact-duplicate removal. */
function sn_tl_title_key(string $title): string
{
    $t = sn_tl_norm($title);
    $t = preg_replace('/[^\p{L}\p{N} ]+/u', '', $t);
    return trim((string)preg_replace('/\s+/', ' ', (string)$t));
}

/** Publisher text can contain markup/entities (e.g. RSS descriptions with <img>): reduce to plain text. */
function sn_tl_plain(string $s): string
{
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $s = preg_replace('#<(script|style)\b.*?</\1>#is', ' ', $s);
    $s = strip_tags((string)$s);
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string)preg_replace('/\s+/u', ' ', $s));
}

function sn_tl_host(string $url): string
{
    $h = strtolower((string)parse_url($url, PHP_URL_HOST));
    return preg_replace('/^www\./', '', $h) ?: '';
}

/** Sum of NLP entity counts whose text matches the story's entity pattern. */
function sn_tl_entity_count(array $entities, string $entityRegex): int
{
    $n = 0;
    foreach ($entities as $e) {
        if (!is_array($e)) {
            continue;
        }
        $text = sn_tl_norm((string)($e['text'] ?? ''));
        if ($text !== '' && preg_match($entityRegex, $text)) {
            $n += max(1, (int)($e['count'] ?? 1));
        }
    }
    return $n;
}

/**
 * Classify one article against a story definition.
 *
 * @param array $a  keys: id, title, description, entities (list of {text,count})
 * @return array{tier:string,reason:string,count:int}  tier: core|related|rejected
 */
function sn_tl_classify(array $a, array $story): array
{
    $id    = (int)($a['id'] ?? 0);
    $title = (string)($a['title'] ?? '');
    $desc  = (string)($a['description'] ?? '');
    $m     = $story['match'];
    $count = sn_tl_entity_count((array)($a['entities'] ?? []), $m['entity']);

    if (isset($story['excludes'][$id])) {
        return ['tier' => 'rejected', 'reason' => 'excluded', 'count' => $count];
    }
    if (isset($story['pins'][$id])) {
        return ['tier' => $story['pins'][$id], 'reason' => 'pinned', 'count' => $count];
    }

    foreach ($story['title_exclude'] ?? [] as $re) {
        if (preg_match($re, $title)) {
            return ['tier' => 'rejected', 'reason' => 'title-excluded', 'count' => $count];
        }
    }

    $titleHit = (bool)preg_match($m['primary'], $title);
    $descHit  = (bool)preg_match($m['primary'], $desc);
    $coreMin  = (int)($story['core_entity_min'] ?? 10);
    $relMin   = (int)($story['related_entity_min'] ?? 3);

    if ($titleHit) {
        // Optional framing check: a headline naming the subject without the story's framing is only "related"
        if (!empty($m['context']) && !preg_match($m['context'], $title . ' ' . $desc)) {
            return ['tier' => 'related', 'reason' => 'title-no-context', 'count' => $count];
        }
        return ['tier' => 'core', 'reason' => 'title', 'count' => $count];
    }
    if ($descHit && $count >= 3) {
        return ['tier' => 'core', 'reason' => 'description+entity', 'count' => $count];
    }
    if ($count >= $coreMin) {
        return ['tier' => 'core', 'reason' => 'entity-high', 'count' => $count];
    }
    if ($count >= $relMin) {
        return ['tier' => 'related', 'reason' => 'entity-mid', 'count' => $count];
    }
    foreach ($m['related'] ?? [] as $rule) {
        $all = true;
        foreach ($rule['all_of'] as $re) {
            if (!preg_match($re, $title . ' ' . $desc)) {
                $all = false;
                break;
            }
        }
        if ($all) {
            return ['tier' => 'related', 'reason' => 'co-terms', 'count' => $count];
        }
    }
    return ['tier' => 'rejected', 'reason' => $descHit ? 'description-only' : 'weak-entity', 'count' => $count];
}

/** Today's date (Y-m-d) in the timeline timezone. */
function sn_tl_today(?DateTimeImmutable $now = null): string
{
    $now = $now ?? new DateTimeImmutable('now', new DateTimeZone(SN_TL_TZ));
    return $now->setTimezone(new DateTimeZone(SN_TL_TZ))->format('Y-m-d');
}

/**
 * Resolve a story's date window. Active stories may leave window.to null (open-ended: it
 * resolves to today in Eastern Time on every call) and may set lookback_days for a rolling start.
 *
 * @return array{from:string,to:string,open:bool}
 */
function sn_tl_resolve_window(array $story, ?DateTimeImmutable $now = null): array
{
    $tz = new DateTimeZone(SN_TL_TZ);
    $today = sn_tl_today($now);
    $from = (string)($story['window']['from'] ?? $today);
    $to = $story['window']['to'] ?? null;
    $open = ($to === null || $to === '');
    if ($open) {
        $to = $today;
    } elseif (($story['status'] ?? 'closed') === 'active' && $to > $today) {
        $to = $today;
    }
    $look = (int)($story['lookback_days'] ?? 0);
    if ($look > 0) {
        $rolling = (new DateTimeImmutable($to, $tz))->modify('-' . $look . ' days')->format('Y-m-d');
        if ($rolling > $from) {
            $from = $rolling;
        }
    }
    return ['from' => $from, 'to' => (string)$to, 'open' => $open];
}

/**
 * Read-only fetch of candidate rows for a story window.
 *
 * Rows are selected newest-first so a cap on candidates can only ever drop the oldest coverage,
 * then returned oldest-first. If the cap is hit, the oldest (possibly partial) day is dropped
 * and $meta['truncated'] is set so the UI and audit can say so.
 *
 * Story options: max_candidates (default 3000), prefilter_nlp (default true; set false to match
 * on headline/description only, which is much cheaper on a large archive).
 *
 * @param array|null $meta out: window, truncated, limit, fetched
 * @return array[] raw article rows with decoded entities
 */
function sn_tl_fetch_candidates(PDO $pdo, array $story, ?array &$meta = null, ?DateTimeImmutable $now = null): array
{
    $meta = ['window' => null, 'truncated' => false, 'limit' => 0, 'fetched' => 0, 'coverage_from' => null];
    $terms = $story['terms'] ?? [];
    if (!$terms) {
        return [];
    }
    $win = sn_tl_resolve_window($story, $now);
    $meta['window'] = $win;
    $tz = new DateTimeZone(SN_TL_TZ);
    $from = (new DateTimeImmutable($win['from'] . ' 00:00:00', $tz))->format('c');
    $to   = (new DateTimeImmutable($win['to'] . ' 00:00:00', $tz))->modify('+1 day')->format('c');
    $limit = max(100, min(10000, (int)($story['max_candidates'] ?? 3000)));
    $meta['limit'] = $limit;
    $useNlp = ($story['prefilter_nlp'] ?? true) !== false;

    $conds = [];
    $params = [':from' => $from, ':to' => $to];
    foreach (array_values($terms) as $i => $t) {
        $p = ":t$i";
        $conds[] = "(a.title ILIKE $p OR a.description ILIKE $p" . ($useNlp ? " OR a.nlp::text ILIKE $p" : '') . ')';
        $params[$p] = '%' . addcslashes($t, '%_\\') . '%';
    }
    // Only the entity list is needed from the NLP blob; fetching all of it is wasteful
    $sql = "SELECT a.id, a.url, a.title, a.description, a.source_slug, a.pub_date, a.media_url,
                   (a.nlp::jsonb->'entities')::text AS entities_json
              FROM articles a
             WHERE a.deleted_at IS NULL
               AND a.pub_date IS NOT NULL
               AND a.pub_date >= :from AND a.pub_date < :to
               AND (" . implode(' OR ', $conds) . ")
             ORDER BY a.pub_date DESC, a.id DESC
             LIMIT $limit";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $all = $st->fetchAll(PDO::FETCH_ASSOC);
    $meta['fetched'] = count($all);

    if (count($all) >= $limit) {
        $meta['truncated'] = true;
        $oldest = new DateTimeImmutable((string)end($all)['pub_date']);
        $cut = $oldest->setTimezone($tz)->setTime(0, 0)->modify('+1 day');
        $all = array_values(array_filter($all, static fn($r) => new DateTimeImmutable((string)$r['pub_date']) >= $cut));
        $meta['coverage_from'] = $cut->format('Y-m-d');
    }

    $rows = [];
    foreach (array_reverse($all) as $r) {
        $ents = json_decode((string)($r['entities_json'] ?? ''), true);
        $r['entities'] = is_array($ents) ? $ents : [];
        unset($r['entities_json']);
        $rows[] = $r;
    }
    return $rows;
}

/**
 * Turn classified rows into the timeline model. Pure function.
 *
 * @param array[] $rows candidate rows (see sn_tl_fetch_candidates)
 */
function sn_tl_assemble(array $story, array $rows, array $meta = []): array
{
    $tz = new DateTimeZone(SN_TL_TZ);
    $excludeCats = array_map('strtolower', $story['exclude_categories'] ?? []);
    $excludeHosts = $story['exclude_hosts'] ?? [];

    $items = [];
    $seen = [];
    $counts = ['core' => 0, 'related' => 0, 'rejected' => 0, 'duplicates' => 0];

    foreach ($rows as $r) {
        $id = (int)$r['id'];
        $r['title'] = sn_tl_plain((string)$r['title']);
        $r['description'] = sn_tl_plain((string)($r['description'] ?? ''));
        $cat = strtolower((string)($r['source_slug'] ?? ''));
        $host = sn_tl_host((string)$r['url']);
        $pinned = isset($story['pins'][$id]);

        if (!$pinned && (in_array($cat, $excludeCats, true) || in_array($host, $excludeHosts, true))) {
            $counts['rejected']++;
            continue;
        }
        $c = sn_tl_classify($r, $story);
        if ($c['tier'] === 'rejected') {
            $counts['rejected']++;
            continue;
        }
        $key = sn_tl_title_key((string)$r['title']);
        if ($key !== '' && isset($seen[$key])) {
            $counts['duplicates']++;
            continue;
        }
        $seen[$key] = true;

        try {
            $dt = (new DateTimeImmutable((string)$r['pub_date']))->setTimezone($tz);
        } catch (Throwable $e) {
            continue;
        }
        $counts[$c['tier']]++;
        $items[] = [
            'id'          => $id,
            'url'         => (string)$r['url'],
            'title'       => sn_tl_plain((string)$r['title']),
            'description' => sn_tl_plain((string)($r['description'] ?? '')),
            'category'    => $cat,
            'host'        => $host,
            'image'       => (string)($r['media_url'] ?? ''),
            'dt'          => $dt,
            'iso'         => $dt->format('c'),
            'day'         => $dt->format('Y-m-d'),
            'tier'        => $c['tier'],
            'reason'      => $c['reason'],
            'entity_count' => $c['count'],
        ];
    }

    usort($items, static fn($a, $b) => [$a['dt'], $a['id']] <=> [$b['dt'], $b['id']]);

    // Render cap: drop the oldest related items first, then the oldest core items
    $maxItems = max(50, (int)($story['max_items'] ?? 1500));
    $trimmed = 0;
    if (count($items) > $maxItems) {
        $excess = count($items) - $maxItems;
        foreach (['related', 'core'] as $tier) {
            foreach ($items as $k => $it) {
                if ($excess <= 0) {
                    break;
                }
                if ($it['tier'] === $tier) {
                    unset($items[$k]);
                    $excess--;
                    $trimmed++;
                    $counts[$tier]--;
                }
            }
        }
        $items = array_values($items);
    }

    $days = [];
    foreach ($items as $it) {
        $d = $it['day'];
        $days[$d] ??= ['date' => $d, 'articles' => [], 'core' => 0, 'related' => 0];
        $days[$d]['articles'][] = $it;
        $days[$d][$it['tier']]++;
    }
    ksort($days);

    $coreDays = array_keys(array_filter($days, static fn($d) => $d['core'] > 0));

    // Quiet gaps are measured between consecutive core days only
    $gaps = [];
    $minGap = (int)($story['gap_min_days'] ?? 3);
    for ($i = 1; $i < count($coreDays); $i++) {
        $a = new DateTimeImmutable($coreDays[$i - 1], $tz);
        $b = new DateTimeImmutable($coreDays[$i], $tz);
        $quiet = (int)$a->diff($b)->days - 1;
        if ($quiet >= $minGap) {
            $gaps[$coreDays[$i]] = ['before' => $coreDays[$i], 'days' => $quiet, 'from' => $coreDays[$i - 1]];
        }
    }

    $chapters = [];
    foreach ($story['chapters'] ?? [] as $idx => $ch) {
        $inRange = array_values(array_filter($coreDays, static fn($d) => $d >= $ch['from'] && $d <= $ch['to']));
        $n = 0;
        foreach ($inRange as $d) {
            $n += $days[$d]['core'];
        }
        $chapters[$idx] = $ch + ['core_days' => $inRange, 'core_articles' => $n, 'first_day' => $inRange[0] ?? null];
    }

    // Editorial review state: days after the last chapter are "latest" (not yet reviewed);
    // days before it that no chapter covers are flagged individually.
    $cutoff = null;
    foreach ($story['chapters'] ?? [] as $ch) {
        $cutoff = ($cutoff === null || $ch['to'] > $cutoff) ? $ch['to'] : $cutoff;
    }
    $latestFrom = null;
    $latestCore = 0;
    $uncoveredMid = 0;
    foreach ($days as $d => &$info) {
        $inChapter = false;
        foreach ($story['chapters'] ?? [] as $ch) {
            if ($d >= $ch['from'] && $d <= $ch['to']) {
                $inChapter = true;
                break;
            }
        }
        $info['review'] = $inChapter ? 'reviewed' : (($cutoff === null || $d > $cutoff) ? 'latest' : 'uncovered');
        if ($info['review'] === 'latest') {
            $latestFrom ??= $d;
            $latestCore += $info['core'];
        } elseif ($info['review'] === 'uncovered' && $info['core'] > 0) {
            $uncoveredMid += $info['core'];
        }
    }
    unset($info);

    $core = array_values(array_filter($items, static fn($i) => $i['tier'] === 'core'));
    $pubs = [];
    foreach ($core as $it) {
        $pubs[$it['host']] = ($pubs[$it['host']] ?? 0) + 1;
    }
    arsort($pubs);
    $peakDay = null;
    $peakN = 0;
    foreach ($days as $d => $info) {
        if ($info['core'] > $peakN) {
            $peakN = $info['core'];
            $peakDay = $d;
        }
    }
    $topHost = $pubs ? array_key_first($pubs) : null;

    $stats = [
        'core_articles'    => count($core),
        'related_articles' => $counts['related'],
        'publishers'       => count($pubs),
        'days_active'      => count($coreDays),
        'first_day'        => $coreDays[0] ?? null,
        'last_day'         => $coreDays ? end($coreDays) : null,
        'peak_day'         => $peakDay,
        'peak_count'       => $peakN,
        'top_publisher'    => $topHost,
        'top_publisher_share' => ($topHost && $core) ? $pubs[$topHost] / count($core) : 0.0,
        'publisher_mix'    => array_slice($pubs, 0, 8, true),
        'duplicates_removed' => $counts['duplicates'],
        'rejected'         => $counts['rejected'],
        'trimmed'          => $trimmed,
        'latest_core'      => $latestCore,
        'uncovered_core'   => $uncoveredMid,
    ];

    return [
        'story'    => $story,
        'days'     => $days,
        'gaps'     => $gaps,
        'chapters' => $chapters,
        'stats'    => $stats,
        'review'   => ['cutoff' => $cutoff, 'latest_from' => $latestFrom],
        'fetch'    => [
            'window'        => $meta['window'] ?? null,
            'truncated'     => (bool)($meta['truncated'] ?? false),
            'coverage_from' => $meta['coverage_from'] ?? null,
            'fetched'       => (int)($meta['fetched'] ?? count($rows)),
        ],
        'generated_at' => time(),
        'series'   => sn_tl_volume_series($days, $cutoff),
    ];
}

/**
 * Volume series for the scrubber. Short spans are daily; long-running stories are bucketed by
 * ISO week (or by month beyond ~2 years) so bars stay legible and tappable.
 * Each bucket links to its first core day.
 */
function sn_tl_volume_series(array $days, ?string $reviewCutoff = null): array
{
    $coreDays = array_keys(array_filter($days, static fn($d) => $d['core'] > 0));
    if (!$coreDays) {
        return [];
    }
    $tz = new DateTimeZone(SN_TL_TZ);
    $first = new DateTimeImmutable(min($coreDays), $tz);
    $last = new DateTimeImmutable(max($coreDays), $tz);
    $span = (int)$first->diff($last)->days + 1;
    $unit = $span <= 90 ? 'day' : ($span <= 730 ? 'week' : 'month');

    $buckets = [];
    foreach ($days as $date => $info) {
        if ($date < $first->format('Y-m-d') || $date > $last->format('Y-m-d')) {
            continue;
        }
        $dt = new DateTimeImmutable($date, $tz);
        if ($unit === 'day') {
            $start = $end = $date;
        } elseif ($unit === 'week') {
            $start = $dt->modify('monday this week')->format('Y-m-d');
            $end = $dt->modify('sunday this week')->format('Y-m-d');
        } else {
            $start = $dt->modify('first day of this month')->format('Y-m-d');
            $end = $dt->modify('last day of this month')->format('Y-m-d');
        }
        $buckets[$start] ??= ['date' => $start, 'to' => $end, 'unit' => $unit, 'core' => 0, 'related' => 0, 'target' => null];
        $buckets[$start]['core'] += $info['core'];
        $buckets[$start]['related'] += $info['related'];
        if ($info['core'] > 0 && $buckets[$start]['target'] === null) {
            $buckets[$start]['target'] = $date;
        }
    }

    // Fill empty buckets so quiet stretches are visible
    $series = [];
    $cur = new DateTimeImmutable(array_key_first($buckets), $tz);
    $stop = new DateTimeImmutable(array_key_last($buckets), $tz);
    $step = $unit === 'day' ? '+1 day' : ($unit === 'week' ? '+1 week' : 'first day of next month');
    while ($cur <= $stop && count($series) < 800) {
        $k = $cur->format('Y-m-d');
        $b = $buckets[$k] ?? ['date' => $k, 'to' => $k, 'unit' => $unit, 'core' => 0, 'related' => 0, 'target' => null];
        $b['latest'] = $reviewCutoff === null || $b['to'] > $reviewCutoff;
        $series[] = $b;
        $cur = $cur->modify($step);
    }
    return $series;
}

/**
 * Cache directory. Models live in the OS temp dir (not the git-tracked fragment cache);
 * SN_TL_CACHE_DIR overrides it (used by tests).
 */
function sn_tl_cache_dir(): string
{
    return defined('SN_TL_CACHE_DIR') ? (string)SN_TL_CACHE_DIR : sys_get_temp_dir();
}

function sn_tl_cache_read(string $file): ?array
{
    if (!is_file($file)) {
        return null;
    }
    $m = @unserialize((string)@file_get_contents($file), ['allowed_classes' => [DateTimeImmutable::class, DateTimeZone::class]]);
    return is_array($m) ? $m : null;
}

/**
 * Content-based version of a model: changes only when the story definition or this engine file
 * changes, so ordinary deployments keep valid cached models while edits invalidate them.
 */
function sn_tl_model_version(array $story): string
{
    static $memo = [];
    $file = $story['_file'] ?? null;
    if ($file !== null && isset($memo[$file])) {
        return $memo[$file];
    }
    $read = static fn(string $f): string => str_replace("\r\n", "\n", (string)@file_get_contents($f));
    $storyPart = ($file !== null && is_file($file))
        ? $read($file)
        : (string)json_encode($story, JSON_PARTIAL_OUTPUT_ON_ERROR);
    $v = substr(sha1(SN_TL_MODEL_FORMAT . '|' . ($story['slug'] ?? '') . '|' . $storyPart . '|' . $read(__FILE__)), 0, 12);
    if ($file !== null) {
        $memo[$file] = $v;
    }
    return $v;
}

function sn_tl_model_ttl(array $story): int
{
    return ($story['status'] ?? 'closed') === 'active' ? max(120, (int)($story['cache_ttl'] ?? 600)) : 86400;
}

/** Local file prefix for a story, keyed by content version. */
function sn_tl_cache_base(array $story): string
{
    return sn_tl_cache_dir() . '/sn_tl_' . $story['slug'] . '_' . sn_tl_model_version($story);
}

/** A cached model is usable only if it has the expected shape and real DateTime article stamps. */
function sn_tl_model_valid(array $m, string $slug): bool
{
    foreach (['days', 'gaps', 'chapters', 'stats', 'review', 'series'] as $k) {
        if (!isset($m[$k]) || !is_array($m[$k])) {
            return false;
        }
    }
    if (!isset($m['generated_at']) || !is_int($m['generated_at']) || ($m['story']['slug'] ?? null) !== $slug) {
        return false;
    }
    foreach ($m['days'] as $day) {
        foreach ($day['articles'] ?? [] as $a) {
            if (!($a['dt'] ?? null) instanceof DateTimeInterface) {
                return false;
            }
        }
    }
    return true;
}

function sn_tl_json_prepare(mixed $v): mixed
{
    if ($v instanceof DateTimeInterface) {
        return ['__dt' => $v->format('Y-m-d\TH:i:s.uP')];
    }
    if (is_array($v)) {
        return array_map('sn_tl_json_prepare', $v);
    }
    return $v;
}

function sn_tl_json_restore(mixed $v): mixed
{
    if (is_array($v)) {
        if (count($v) === 1 && isset($v['__dt']) && is_string($v['__dt'])) {
            return (new DateTimeImmutable($v['__dt']))->setTimezone(new DateTimeZone(SN_TL_TZ));
        }
        return array_map('sn_tl_json_restore', $v);
    }
    return $v;
}

/** Model -> "sntl1:<sha1>:<base64(gzip(json))>", or null if it cannot be represented. */
function sn_tl_model_encode(array $model, string $slug): ?string
{
    $model['story'] = ['slug' => $slug];
    $json = json_encode(sn_tl_json_prepare($model), JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES);
    $z = $json === false ? false : gzencode($json, 6);
    if ($z === false) {
        return null;
    }
    $b64 = base64_encode($z);
    return SN_TL_MODEL_FORMAT . ':' . sha1($b64) . ':' . $b64;
}

/** Inverse of sn_tl_model_encode; returns null for anything truncated, tampered or malformed. */
function sn_tl_model_decode(string $payload, string $slug): ?array
{
    $parts = explode(':', $payload, 3);
    if (count($parts) !== 3 || $parts[0] !== SN_TL_MODEL_FORMAT || !hash_equals($parts[1], sha1($parts[2]))) {
        return null;
    }
    $z = base64_decode($parts[2], true);
    $json = $z === false ? false : @gzdecode($z, 64 * 1024 * 1024);
    $data = $json === false || $json === null ? null : json_decode($json, true, 64);
    if (!is_array($data)) {
        return null;
    }
    $model = sn_tl_json_restore($data);
    return sn_tl_model_valid($model, $slug) ? $model : null;
}

/** @return array{model:array,age:int}|null the local file copy for the current version */
function sn_tl_local_read(array $story): ?array
{
    $file = sn_tl_cache_base($story) . '.ser';
    if (!is_file($file)) {
        return null;
    }
    $m = sn_tl_cache_read($file);
    if ($m === null || !sn_tl_model_valid($m, $story['slug'])) {
        return null;
    }
    return ['model' => $m, 'age' => max(0, time() - (int)@filemtime($file))];
}

function sn_tl_local_write(array $story, array $model): void
{
    $file = sn_tl_cache_base($story) . '.ser';
    $model['story'] = ['slug' => $story['slug']];
    $tmp = $file . '.' . getmypid() . '.tmp';
    if (@file_put_contents($tmp, serialize($model)) !== false && !@rename($tmp, $file)) {
        @unlink($tmp);
    }
}

function sn_tl_db_pdo(?PDO $pdo): ?PDO
{
    return $pdo ?: (function_exists('_pdo_or_null') ? _pdo_or_null() : null);
}

/**
 * Database copy (scrollnews_cache, group timeline_model). Rejects version-mismatched or invalid
 * rows; expired but version-compatible rows are returned with fresh=false so callers can serve
 * them if a refresh fails.
 *
 * @return array{model:array,fresh:bool}|null
 */
function sn_tl_db_read(array $story, ?PDO $pdo): ?array
{
    if (!$pdo) {
        return null;
    }
    try {
        $st = $pdo->prepare(
            "SELECT html, build_version, (expires_at IS NULL OR expires_at > NOW()) AS is_fresh
               FROM scrollnews_cache
              WHERE cache_key = :k AND cache_group = 'timeline_model' AND deleted_at IS NULL
              LIMIT 1"
        );
        $st->execute([':k' => 'timeline_model:' . $story['slug']]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('timeline db cache read failed (' . $story['slug'] . '): ' . $e->getMessage());
        return null;
    }
    if (!$row || (string)($row['build_version'] ?? '') !== sn_tl_model_version($story)) {
        return null;
    }
    $model = sn_tl_model_decode((string)($row['html'] ?? ''), $story['slug']);
    if ($model === null) {
        error_log('timeline db cache row invalid (' . $story['slug'] . ')');
        return null;
    }
    return ['model' => $model, 'fresh' => in_array($row['is_fresh'] ?? false, [true, 't', 'true', '1', 1], true)];
}

function sn_tl_db_write(array $story, array $model, ?PDO $pdo): bool
{
    if (!$pdo) {
        return false;
    }
    $payload = sn_tl_model_encode($model, $story['slug']);
    if ($payload === null || sn_tl_model_decode($payload, $story['slug']) === null) {
        error_log('timeline model could not be encoded (' . $story['slug'] . ')');
        return false;
    }
    try {
        $st = $pdo->prepare(
            "INSERT INTO scrollnews_cache (cache_key, cache_group, html, generated_at, expires_at, build_version, meta, deleted_at)
             VALUES (:k, 'timeline_model', :html, NOW(), NOW() + (:ttl || ' seconds')::interval, :v, :meta::jsonb, NULL)
             ON CONFLICT (cache_key) DO UPDATE SET
                 html = EXCLUDED.html, generated_at = NOW(), expires_at = EXCLUDED.expires_at,
                 build_version = EXCLUDED.build_version, meta = EXCLUDED.meta, deleted_at = NULL"
        );
        return $st->execute([
            ':k'    => 'timeline_model:' . $story['slug'],
            ':html' => $payload,
            ':ttl'  => sn_tl_model_ttl($story),
            ':v'    => sn_tl_model_version($story),
            ':meta' => json_encode([
                'slug' => $story['slug'],
                'core_articles' => $model['stats']['core_articles'] ?? null,
                'last_day' => $model['stats']['last_day'] ?? null,
                'bytes' => strlen($payload),
                'generated_by' => 'sn_tl_build_model',
            ]),
        ]);
    } catch (Throwable $e) {
        error_log('timeline db cache write failed (' . $story['slug'] . '): ' . $e->getMessage());
        return false;
    }
}

/**
 * Cached-only model lookup for latency-sensitive callers (the homepage). Never rebuilds: reads the
 * short-lived local copy, then the database copy (one indexed row), and returns the newest valid
 * model however old, or null if none exists.
 */
function sn_tl_peek_model(array $story, ?PDO $pdo = null): ?array
{
    $local = sn_tl_local_read($story);
    if ($local && $local['age'] < min(SN_TL_LOCAL_TTL, sn_tl_model_ttl($story))) {
        return ['story' => $story] + $local['model'];
    }
    $db = sn_tl_db_read($story, sn_tl_db_pdo($pdo));
    if ($db) {
        if ($db['fresh']) {
            sn_tl_local_write($story, $db['model']);
        }
        return ['story' => $story] + $db['model'];
    }
    return $local ? ['story' => $story] + $local['model'] : null;
}

/**
 * Choose the story to feature on the homepage.
 *
 * 1. A story with 'featured' => true wins (manual override; first by order).
 * 2. Otherwise the most active qualifying ACTIVE story: newest core article within 7 days and at
 *    least 5 core articles in the last 14 days (ranked by core articles in the last 7 days).
 * 3. Otherwise a closed story, rotating by calendar day (ET).
 *
 * @param array<string,array|null> $models cached models by slug (null when not cached)
 * @return array{story:array,model:?array,reason:string}|null
 */
function sn_tl_pick_featured(array $stories, array $models, ?DateTimeImmutable $now = null): ?array
{
    if (!$stories) {
        return null;
    }
    foreach ($stories as $slug => $s) {
        if (!empty($s['featured'])) {
            return ['story' => $s, 'model' => $models[$slug] ?? null, 'reason' => 'manual'];
        }
    }
    $tz = new DateTimeZone(SN_TL_TZ);
    $today = sn_tl_today($now);
    $d7 = (new DateTimeImmutable($today, $tz))->modify('-7 days')->format('Y-m-d');
    $d14 = (new DateTimeImmutable($today, $tz))->modify('-13 days')->format('Y-m-d');
    $best = null;
    $bestScore = -1;
    foreach ($stories as $slug => $s) {
        $m = $models[$slug] ?? null;
        if (($s['status'] ?? 'closed') !== 'active' || !$m) {
            continue;
        }
        $last = $m['stats']['last_day'] ?? null;
        if (!$last || $last < $d7) {
            continue;
        }
        $n7 = 0;
        $n14 = 0;
        foreach ($m['days'] as $d => $info) {
            if ($d >= $d14) {
                $n14 += $info['core'];
            }
            if ($d >= $d7) {
                $n7 += $info['core'];
            }
        }
        if ($n14 >= 5 && $n7 > $bestScore) {
            $best = $s;
            $bestScore = $n7;
        }
    }
    if ($best) {
        return ['story' => $best, 'model' => $models[$best['slug']], 'reason' => 'active'];
    }
    $closed = array_values(array_filter($stories, static fn($s) => ($s['status'] ?? 'closed') !== 'active'));
    if (!$closed) {
        $closed = array_values($stories);
    }
    $dayNo = (int)floor((new DateTimeImmutable($today, new DateTimeZone('UTC')))->getTimestamp() / 86400);
    $pick = $closed[$dayNo % count($closed)];
    return ['story' => $pick, 'model' => $models[$pick['slug']] ?? null, 'reason' => 'rotation'];
}

/**
 * Get the model for a story, building it only when no fresh copy exists.
 *
 * Layers: short-lived local file (SN_TL_LOCAL_TTL) -> PostgreSQL copy (scrollnews_cache, group
 * timeline_model, TTL 10 minutes for active stories / 24h for closed) -> rebuild from articles.
 *
 * Protections:
 *  - Version: copies are keyed by a content hash of the story file and this engine, so ordinary
 *    deployments keep them and any definition/engine edit rejects them.
 *  - Single-flight: one process per container rebuilds while others serve the previous model.
 *  - Stale-on-error: if the rebuild fails, the newest valid version-compatible copy (even
 *    expired) is served and retries are suppressed for 60 seconds.
 *  - Writes are atomic (single-row upsert with checksum; renamed local file); reads validate.
 */
function sn_tl_build_model(array $story, ?PDO $pdo = null): ?array
{
    $ttl = sn_tl_model_ttl($story);
    $localTtl = min(SN_TL_LOCAL_TTL, $ttl);
    $base = sn_tl_cache_base($story);
    $failFile = $base . '.fail';
    $pdo = sn_tl_db_pdo($pdo);

    $serve = static fn(?array $m): ?array => $m === null ? null : ['story' => $story] + $m;

    $local = sn_tl_local_read($story);
    if ($local && $local['age'] < $localTtl) {
        return $serve($local['model']);
    }
    $db = sn_tl_db_read($story, $pdo);
    if ($db && $db['fresh']) {
        sn_tl_local_write($story, $db['model']);
        return $serve($db['model']);
    }
    $stale = static function () use (&$local, &$db, $serve): ?array {
        $c = array_values(array_filter([$local['model'] ?? null, $db['model'] ?? null]));
        usort($c, static fn($a, $b) => $b['generated_at'] <=> $a['generated_at']);
        return $serve($c[0] ?? null);
    };

    if (is_file($failFile) && (time() - (int)filemtime($failFile)) < 60) {
        return $stale();
    }

    $lock = @fopen($base . '.lock', 'c');
    $release = static function () use (&$lock): void {
        if ($lock) {
            @flock($lock, LOCK_UN);
            @fclose($lock);
            $lock = null;
        }
    };
    if ($lock) {
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            $existing = $stale();
            if ($existing !== null) {
                $release();
                return $existing;
            }
            flock($lock, LOCK_EX); // nothing to serve yet: wait for the refresher
        }
        $again = sn_tl_local_read($story);
        if ($again && $again['age'] < $localTtl) {
            $release();
            return $serve($again['model']);
        }
    }

    try {
        if (!$pdo) {
            throw new RuntimeException('database unavailable');
        }
        $meta = [];
        $rows = sn_tl_fetch_candidates($pdo, $story, $meta);
        $model = sn_tl_assemble($story, $rows, $meta);
    } catch (Throwable $e) {
        error_log('timeline build failed (' . $story['slug'] . '): ' . $e->getMessage());
        @touch($failFile);
        $release();
        return $stale();
    }

    sn_tl_local_write($story, $model);
    sn_tl_db_write($story, $model, $pdo);
    @unlink($failFile);
    $release();
    return $model;
}


function sn_tl_fmt_day(string $ymd, string $fmt = 'D, M j'): string
{
    return (new DateTimeImmutable($ymd, new DateTimeZone(SN_TL_TZ)))->format($fmt);
}
