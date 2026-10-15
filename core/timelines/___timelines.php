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

    $titleHit = (bool)preg_match($m['primary'], $title);
    $descHit  = (bool)preg_match($m['primary'], $desc);
    $coreMin  = (int)($story['core_entity_min'] ?? 10);
    $relMin   = (int)($story['related_entity_min'] ?? 3);

    if ($titleHit) {
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

/**
 * Read-only fetch of candidate rows for a story window.
 *
 * @return array[] raw article rows with decoded entities
 */
function sn_tl_fetch_candidates(PDO $pdo, array $story): array
{
    $terms = $story['terms'] ?? [];
    if (!$terms) {
        return [];
    }
    $w = $story['window'];
    $tz = new DateTimeZone(SN_TL_TZ);
    $from = (new DateTimeImmutable($w['from'] . ' 00:00:00', $tz))->format('c');
    $to   = (new DateTimeImmutable($w['to'] . ' 00:00:00', $tz))->modify('+1 day')->format('c');

    $conds = [];
    $params = [':from' => $from, ':to' => $to];
    foreach (array_values($terms) as $i => $t) {
        $p = ":t$i";
        $conds[] = "(a.title ILIKE $p OR a.description ILIKE $p OR a.nlp::text ILIKE $p)";
        $params[$p] = '%' . addcslashes($t, '%_\\') . '%';
    }
    $sql = "SELECT a.id, a.url, a.title, a.description, a.source_slug, a.pub_date, a.media_url, a.nlp
              FROM articles a
             WHERE a.deleted_at IS NULL
               AND a.pub_date IS NOT NULL
               AND a.pub_date >= :from AND a.pub_date < :to
               AND (" . implode(' OR ', $conds) . ")
             ORDER BY a.pub_date ASC, a.id ASC
             LIMIT 2000";
    $st = $pdo->prepare($sql);
    $st->execute($params);

    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $nlp = json_decode((string)($r['nlp'] ?? ''), true);
        $r['entities'] = is_array($nlp) && is_array($nlp['entities'] ?? null) ? $nlp['entities'] : [];
        unset($r['nlp']);
        $rows[] = $r;
    }
    return $rows;
}

/**
 * Turn classified rows into the timeline model. Pure function.
 *
 * @param array[] $rows candidate rows (see sn_tl_fetch_candidates)
 */
function sn_tl_assemble(array $story, array $rows): array
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
    ];

    return [
        'story'    => $story,
        'days'     => $days,
        'gaps'     => $gaps,
        'chapters' => $chapters,
        'stats'    => $stats,
        'series'   => sn_tl_volume_series($days),
    ];
}

/** Day-by-day core volume across the story's active span (for the scrubber). */
function sn_tl_volume_series(array $days): array
{
    $coreDays = array_keys(array_filter($days, static fn($d) => $d['core'] > 0));
    if (!$coreDays) {
        return [];
    }
    $tz = new DateTimeZone(SN_TL_TZ);
    $cur = new DateTimeImmutable(min($coreDays), $tz);
    $end = new DateTimeImmutable(max($coreDays), $tz);
    $series = [];
    while ($cur <= $end && count($series) < 400) {
        $k = $cur->format('Y-m-d');
        $series[] = ['date' => $k, 'core' => $days[$k]['core'] ?? 0, 'related' => $days[$k]['related'] ?? 0];
        $cur = $cur->modify('+1 day');
    }
    return $series;
}

/** Build the model for a story, cached in the system temp dir (not the git-tracked fragment cache). */
function sn_tl_build_model(array $story, ?PDO $pdo = null): ?array
{
    $mtime = max((int)@filemtime($story['_file'] ?? __FILE__), (int)@filemtime(__FILE__));
    $ttl = ($story['status'] ?? 'closed') === 'active' ? 600 : 86400;
    $cacheFile = sys_get_temp_dir() . '/sn_tl_' . $story['slug'] . '_' . $mtime . '.ser';

    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
        $m = @unserialize((string)file_get_contents($cacheFile), ['allowed_classes' => [DateTimeImmutable::class, DateTimeZone::class]]);
        if (is_array($m)) {
            $m['story'] = $story;
            return $m;
        }
    }

    $pdo = $pdo ?: (function_exists('_pdo_or_null') ? _pdo_or_null() : null);
    if (!$pdo) {
        return null;
    }
    try {
        $rows = sn_tl_fetch_candidates($pdo, $story);
    } catch (Throwable $e) {
        error_log('timeline fetch failed: ' . $e->getMessage());
        return null;
    }
    $model = sn_tl_assemble($story, $rows);
    $toCache = $model;
    $toCache['story'] = ['slug' => $story['slug']];
    @file_put_contents($cacheFile, serialize($toCache), LOCK_EX);
    return $model;
}

function sn_tl_fmt_day(string $ymd, string $fmt = 'D, M j'): string
{
    return (new DateTimeImmutable($ymd, new DateTimeZone(SN_TL_TZ)))->format($fmt);
}
