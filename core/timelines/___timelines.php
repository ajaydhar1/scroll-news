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
 * Build the model for a story.
 *
 * Cache protections:
 *  - TTL: 10 minutes for active stories (story option cache_ttl, min 120s), 24h for closed.
 *  - Single-flight: one request refreshes while others keep serving the previous model.
 *  - Stale-on-error: if the database fails, the last good model is served and retries are
 *    suppressed for 60 seconds so a database outage is not hammered.
 *  - Cache files are written atomically.
 */
function sn_tl_build_model(array $story, ?PDO $pdo = null): ?array
{
    $mtime = max((int)@filemtime($story['_file'] ?? __FILE__), (int)@filemtime(__FILE__));
    $isActive = ($story['status'] ?? 'closed') === 'active';
    $ttl = $isActive ? max(120, (int)($story['cache_ttl'] ?? 600)) : 86400;
    $base = sn_tl_cache_dir() . '/sn_tl_' . $story['slug'] . '_' . $mtime;
    $cacheFile = $base . '.ser';
    $failFile = $base . '.fail';

    $fresh = static fn() => is_file($cacheFile) && (time() - (int)filemtime($cacheFile)) < $ttl;
    $serve = static function (?array $m) use ($story): ?array {
        if ($m === null) {
            return null;
        }
        $m['story'] = $story;
        return $m;
    };

    if ($fresh()) {
        $m = sn_tl_cache_read($cacheFile);
        if ($m !== null) {
            return $serve($m);
        }
    }
    $stale = static fn() => $serve(sn_tl_cache_read($cacheFile));

    if (is_file($failFile) && (time() - (int)filemtime($failFile)) < 60) {
        return $stale();
    }

    $lock = @fopen($base . '.lock', 'c');
    if ($lock) {
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            $existing = $stale();
            if ($existing !== null) {
                fclose($lock);
                return $existing;
            }
            flock($lock, LOCK_EX); // nothing to serve yet: wait for the refresher
            if ($fresh()) {
                $m = sn_tl_cache_read($cacheFile);
                flock($lock, LOCK_UN);
                fclose($lock);
                if ($m !== null) {
                    return $serve($m);
                }
                $lock = null;
            }
        } elseif ($fresh()) {
            $m = sn_tl_cache_read($cacheFile);
            if ($m !== null) {
                flock($lock, LOCK_UN);
                fclose($lock);
                return $serve($m);
            }
        }
    }
    $release = static function () use (&$lock): void {
        if ($lock) {
            @flock($lock, LOCK_UN);
            @fclose($lock);
            $lock = null;
        }
    };

    $pdo = $pdo ?: (function_exists('_pdo_or_null') ? _pdo_or_null() : null);
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

    $toCache = $model;
    $toCache['story'] = ['slug' => $story['slug']];
    $tmp = $cacheFile . '.' . getmypid() . '.tmp';
    if (@file_put_contents($tmp, serialize($toCache)) !== false) {
        if (!@rename($tmp, $cacheFile)) {
            @unlink($tmp);
        }
    }
    @unlink($failFile);
    $release();
    return $model;
}

function sn_tl_fmt_day(string $ymd, string $fmt = 'D, M j'): string
{
    return (new DateTimeImmutable($ymd, new DateTimeZone(SN_TL_TZ)))->format($fmt);
}
