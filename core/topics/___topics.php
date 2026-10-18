<?php
/**
 * Topics: registry, retrieval and editorial layout planning.
 *
 * Config, normalisation, de-duplication and layout planning are pure functions so they can be
 * tested without a database. Retrieval reuses search_classic() (core/___modules.php) and the
 * shared article view model (core/render/___render.php) so eligibility rules and the
 * Read Story / Analyze URL conventions stay identical to Search.
 */

const SN_TOPIC_PAGE_SIZE = 30;
const SN_TOPIC_MAX_PAGES = 3;          // 3 x 30 keeps the single-query window within search_classic's 100-row cap
const SN_TOPIC_WIDEN_STEPS = [720, 2160]; // 30 and 90 days
const SN_TOPIC_LAYOUTS = ['compact-editorial'];

function sn_topic_defaults(): array
{
    return [
        'terms' => [],
        'exclude' => [],
        'word_match' => true,
        'home' => true,
        'order' => 100,
        'range_hours' => 168,
        'min_items' => 8,
        'layout' => 'compact-editorial',
        'hero' => 'auto',
        'density' => 'standard',
        'accent' => null,
        'dek' => '',
        'timeline' => null,
    ];
}

/** Validate one raw config entry; returns null (and logs) when unusable. */
function sn_topic_normalize(array $raw): ?array
{
    $t = array_merge(sn_topic_defaults(), $raw);

    $slug = strtolower(trim((string) ($t['slug'] ?? '')));
    $name = trim((string) ($t['name'] ?? ''));
    $query = trim((string) ($t['query'] ?? ''));
    if (!preg_match('/^[a-z0-9-]{1,60}$/', $slug) || $name === '' || $query === '') {
        error_log('topics: skipped invalid topic config ' . json_encode($raw));
        return null;
    }

    $clean = static fn($list): array => array_values(array_unique(array_filter(
        array_map(static fn($v) => trim((string) $v), (array) $list),
        'strlen'
    )));

    $terms = $clean($t['terms']);
    if (!$terms) {
        $terms = [$query];
    }

    $accent = is_string($t['accent']) && preg_match('/^#[0-9a-fA-F]{6}$/', $t['accent']) ? $t['accent'] : null;

    return [
        'slug' => $slug,
        'name' => $name,
        'query' => $query,
        'terms' => $terms,
        'exclude' => $clean($t['exclude']),
        'word_match' => (bool) $t['word_match'],
        'home' => (bool) $t['home'],
        'order' => (int) $t['order'],
        'range_hours' => max(1, min(2160, (int) $t['range_hours'])),
        'min_items' => max(1, min(SN_TOPIC_PAGE_SIZE, (int) $t['min_items'])),
        'layout' => in_array($t['layout'], SN_TOPIC_LAYOUTS, true) ? $t['layout'] : 'compact-editorial',
        'hero' => in_array($t['hero'], ['image', 'text', 'auto'], true) ? $t['hero'] : 'auto',
        'density' => in_array($t['density'], ['compact', 'standard', 'comfortable'], true) ? $t['density'] : 'standard',
        'accent' => $accent,
        'dek' => trim((string) $t['dek']),
        'timeline' => is_string($t['timeline']) && preg_match('/^[a-z0-9-]{1,60}$/', $t['timeline']) ? $t['timeline'] : null,
    ];
}

/** @return array<string,array> normalised topics keyed by slug, ordered by 'order' */
function sn_topics_all(?array $rawOverride = null): array
{
    static $cache = null;
    if ($rawOverride === null && $cache !== null) {
        return $cache;
    }

    $raw = $rawOverride ?? (require __DIR__ . '/topics_config.php');
    $topics = [];
    foreach ((array) $raw as $entry) {
        $topic = is_array($entry) ? sn_topic_normalize($entry) : null;
        if ($topic && !isset($topics[$topic['slug']])) {
            $topics[$topic['slug']] = $topic;
        }
    }
    uasort($topics, static fn($a, $b) => $a['order'] <=> $b['order']);

    if ($rawOverride === null) {
        $cache = $topics;
    }
    return $topics;
}

function sn_topic_get(?string $slug): ?array
{
    $slug = strtolower(trim((string) $slug));
    if ($slug === '' || !preg_match('/^[a-z0-9-]{1,60}$/', $slug)) {
        return null;
    }
    return sn_topics_all()[$slug] ?? null;
}

function sn_topics_home(): array
{
    return array_values(array_filter(sn_topics_all(), static fn($t) => $t['home']));
}

function sn_topic_url(string $slug, array $extra = []): string
{
    return '/topic.php?' . http_build_query(['topic' => $slug] + $extra);
}

/** Recency windows to try, in order: the configured default, then progressively wider. */
function sn_topic_window_ladder(array $topic): array
{
    $ladder = [(int) $topic['range_hours']];
    foreach (SN_TOPIC_WIDEN_STEPS as $h) {
        if ($h > $ladder[0]) {
            $ladder[] = $h;
        }
    }
    return $ladder;
}

function sn_topic_window_label(int $hours): string
{
    if ($hours < 48) {
        return 'past ' . $hours . ' hours';
    }
    $days = (int) round($hours / 24);
    return 'past ' . $days . ' days';
}

/* ------------------------------------------------------------------ items */

function sn_topic_clean_text(?string $s): string
{
    $s = html_entity_decode(strip_tags((string) $s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
}

function sn_topic_truncate(string $s, int $max): string
{
    if (mb_strlen($s) <= $max) {
        return $s;
    }
    $cut = mb_substr($s, 0, $max);
    $space = mb_strrpos($cut, ' ');
    if ($space !== false && $space > $max * 0.6) {
        $cut = mb_substr($cut, 0, $space);
    }
    return rtrim($cut, " ,;:.-–—") . '…';
}

function sn_topic_title_key(string $title): string
{
    $t = mb_strtolower(sn_topic_clean_text($title));
    $t = preg_replace('/\s+[-–—|]\s+[^-–—|]{2,40}$/u', '', $t) ?? $t; // trailing " - Publisher"
    return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $t) ?? $t);
}

function sn_topic_url_key(string $url): string
{
    $parts = parse_url(trim($url));
    if (!$parts || empty($parts['host'])) {
        return strtolower(trim($url));
    }
    $host = preg_replace('/^www\./i', '', strtolower($parts['host']));
    return $host . rtrim($parts['path'] ?? '', '/');
}

/** Drop repeats of the same URL or headline, keeping the newest (first) occurrence. */
function sn_topic_dedupe(array $rows): array
{
    $seenUrls = [];
    $seenTitles = [];
    $out = [];
    foreach ($rows as $row) {
        $uKey = sn_topic_url_key((string) ($row['link'] ?? $row['url'] ?? ''));
        $tKey = sn_topic_title_key((string) ($row['title'] ?? ''));
        if ($tKey === '' && $uKey === '') {
            continue;
        }
        if (($uKey !== '' && isset($seenUrls[$uKey])) || ($tKey !== '' && isset($seenTitles[$tKey]))) {
            continue;
        }
        $seenUrls[$uKey] = true;
        $seenTitles[$tKey] = true;
        $out[] = $row;
    }
    return $out;
}

function sn_topic_image_url($raw): ?string
{
    $url = trim((string) $raw);
    if ($url === '' || !preg_match('#^https?://[^\s"\'<>]+$#i', $url)) {
        return null;
    }
    // Temporary workaround: LA Times' Brightspot on-demand resize URLs intermittently redirect to a 1x1
    // placeholder PNG, which renders as a gray box. Treat them as imageless (text-only) until fixed upstream.
    $parts = parse_url($url);
    if (strtolower($parts['host'] ?? '') === 'ca-times.brightspotcdn.com' && str_starts_with($parts['path'] ?? '', '/dims4/')) {
        return null;
    }
    return $url;
}

function sn_topic_relative_time(?int $ts, ?int $now = null): string
{
    if (!$ts) {
        return '';
    }
    $now = $now ?? time();
    $d = $now - $ts;
    if ($d < 0) {
        $d = 0;
    }
    if ($d < 90) {
        return 'Just now';
    }
    if ($d < 3600) {
        return (int) round($d / 60) . ' min ago';
    }
    if ($d < 86400) {
        return (int) floor($d / 3600) . ' hr ago';
    }
    if ($d < 172800) {
        return 'Yesterday';
    }
    if ($d < 7 * 86400) {
        return (int) floor($d / 86400) . ' days ago';
    }
    return date('M j', $ts);
}

/** Top entities from existing NLP metadata, skipping ones that just restate the topic. */
function sn_topic_entities(array $row, array $topic, int $limit = 3): array
{
    $nlp = $row['nlp'] ?? null;
    if (is_string($nlp) && $nlp !== '') {
        $nlp = json_decode($nlp, true);
    }
    if (!is_array($nlp)) {
        return [];
    }

    $skip = [];
    foreach (array_merge([$topic['name'], $topic['query']], $topic['terms']) as $s) {
        $skip[mb_strtolower($s)] = true;
    }

    $allowed = ['PERSON', 'ORG', 'GPE', 'EVENT', 'NORP', 'LOC', 'FAC', 'PRODUCT'];
    $cands = [];
    foreach ((array) ($nlp['entities'] ?? []) as $ent) {
        if (!is_array($ent)) {
            continue;
        }
        $text = trim((string) ($ent['text'] ?? ''));
        $len = mb_strlen($text);
        if ($len < 3 || $len > 28 || !in_array($ent['label'] ?? '', $allowed, true) || !preg_match('/\p{L}{2}/u', $text)) {
            continue;
        }
        $key = mb_strtolower($text);
        if (isset($skip[$key])) {
            continue;
        }
        $cands[$key] = ['text' => $text, 'count' => max($cands[$key]['count'] ?? 0, (int) ($ent['count'] ?? 1))];
    }
    uasort($cands, static fn($a, $b) => $b['count'] <=> $a['count']);

    $out = [];
    foreach ($cands as $c) {
        $out[] = $c['text'];
        if (count($out) >= $limit) {
            break;
        }
    }
    return $out;
}

/** Normalise one search row into the item shape the templates render. */
function sn_topic_build_item(array $row, array $topic, ?int $now = null): array
{
    $vm = sn_article_vm_from_row($row, ['mode' => 'classic', 'analysis_window' => '7d']);

    $title = sn_topic_clean_text($vm['title']);
    $desc = sn_topic_clean_text($row['description'] ?? '');
    $descKey = sn_topic_title_key($desc);
    $titleKey = sn_topic_title_key($title);
    if ($descKey === '' || $descKey === $titleKey || str_starts_with($descKey, $titleKey) || str_starts_with($titleKey, $descKey)) {
        $desc = '';
    }

    $publisher = $vm['feed_name_human'] !== '' ? $vm['feed_name_human'] : $vm['domain'];

    return [
        'title' => $title,
        'url' => $vm['read_url'],
        'analyze_url' => $vm['analyze_url'],
        'publisher' => $publisher,
        'source' => $vm['feed_name_lower'],
        'publisher_url' => $vm['domain'] !== '' ? sn_publisher_profile_url($vm['domain']) : '',
        'ts' => $vm['pub_ts'],
        'iso' => $vm['pub_iso'],
        'rel' => sn_topic_relative_time($vm['pub_ts'], $now),
        'desc' => sn_topic_truncate($desc, 230),
        'image' => sn_topic_image_url($row['media_url'] ?? null),
        'entities' => sn_topic_entities($row, $topic),
    ];
}

/* ----------------------------------------------------------------- layout */

/**
 * Arrange items into the Compact Editorial composition.
 *
 * Returns ['lead','lead_mode','support','rail','stream'] where stream is a list of
 * ['type' => wide|pair|brief, 'items' => [...]] blocks. Items are consumed from a single
 * ordered queue, so nothing appears twice and empty regions are never produced.
 * Pages after the first only receive stream blocks.
 */
function sn_topic_plan_layout(array $items, array $topic, int $page = 1): array
{
    $plan = ['lead' => null, 'lead_mode' => 'text', 'support' => [], 'rail' => [], 'stream' => []];
    $items = array_values($items);
    if (!$items) {
        return $plan;
    }

    if ($page > 1) {
        $plan['stream'] = sn_topic_plan_stream($items);
        return $plan;
    }

    // Lead: the newest story with an image among the first few (keeps ordering recency-led)
    $leadIdx = 0;
    $leadMode = 'text';
    if ($topic['hero'] !== 'text') {
        foreach (array_slice($items, 0, 8, true) as $i => $it) {
            if (!empty($it['image'])) {
                $leadIdx = $i;
                $leadMode = 'image';
                break;
            }
        }
    }
    $plan['lead'] = $items[$leadIdx];
    $plan['lead_mode'] = $leadMode;
    array_splice($items, $leadIdx, 1);

    // Support: two next stories, preferring ones with images from the near-term queue
    $supportIdx = [];
    foreach (array_slice($items, 0, 6, true) as $i => $it) {
        if (!empty($it['image']) && count($supportIdx) < 2) {
            $supportIdx[] = $i;
        }
    }
    foreach (array_keys($items) as $i) {
        if (count($supportIdx) >= 2) {
            break;
        }
        if (!in_array($i, $supportIdx, true)) {
            $supportIdx[] = $i;
        }
    }
    sort($supportIdx);
    foreach ($supportIdx as $i) {
        $plan['support'][] = $items[$i];
    }
    $items = array_values(array_diff_key($items, array_flip($supportIdx)));

    // Rail: compact text list; collapses naturally when little remains
    $railSize = ['compact' => 9, 'standard' => 7, 'comfortable' => 6][$topic['density']] ?? 7;
    $plan['rail'] = array_slice($items, 0, $railSize);
    $items = array_slice($items, $railSize);

    $plan['stream'] = sn_topic_plan_stream($items);
    return $plan;
}

/** Vary the stream: a wide image feature, a pair, then a text brief list, repeating. */
function sn_topic_plan_stream(array $items): array
{
    $blocks = [];
    $items = array_values($items);
    $cycle = ['wide', 'pair', 'brief'];
    $step = 0;

    while ($items) {
        $type = $cycle[$step % 3];
        $step++;

        if ($type === 'wide') {
            $pick = null;
            foreach (array_slice($items, 0, 4, true) as $i => $it) {
                if (!empty($it['image'])) {
                    $pick = $i;
                    break;
                }
            }
            if ($pick === null) {
                $type = 'pair';
            } else {
                $blocks[] = ['type' => 'wide', 'items' => [$items[$pick]]];
                array_splice($items, $pick, 1);
                continue;
            }
        }

        if ($type === 'pair' && count($items) >= 2) {
            $blocks[] = ['type' => 'pair', 'items' => array_splice($items, 0, 2)];
            continue;
        }

        $blocks[] = ['type' => 'brief', 'items' => array_splice($items, 0, 4)];
    }

    return $blocks;
}

/* -------------------------------------------------------------- retrieval */

/**
 * Fetch one page of topic stories.
 *
 * Page 1 starts at the configured window and widens (30 then 90 days) while fewer than
 * min_items stories are found; later pages must pass that chosen window so pagination stays
 * on the same result set. Rows are fetched from offset 0 and de-duplicated as one list, so a
 * story can never repeat across pages.
 *
 * @return array{items:array,total:int,window_hours:int,requested_hours:int,widened:bool,page:int,has_more:bool,error:bool}
 */
function sn_topic_fetch(PDO $pdo, array $topic, int $page = 1, ?int $windowHours = null): array
{
    $page = max(1, min(SN_TOPIC_MAX_PAGES, $page));
    $ladder = sn_topic_window_ladder($topic);
    $requested = $ladder[0];
    $now = time();

    $base = [
        'terms' => $topic['terms'],
        'word_match' => $topic['word_match'],
        'exclude' => $topic['exclude'],
        'distinct_link' => true,
        'offset' => 0,
    ];

    $query = static function (int $hours, int $limit) use ($pdo, $base): array {
        $total = 0;
        $rows = search_classic($pdo, '', $base + ['since_hours' => $hours, 'limit' => $limit], $total);
        return [$rows, $total];
    };

    $result = [
        'items' => [], 'total' => 0, 'window_hours' => $requested, 'requested_hours' => $requested,
        'widened' => false, 'page' => $page, 'has_more' => false, 'error' => false,
    ];

    try {
        $window = ($windowHours !== null && in_array($windowHours, $ladder, true)) ? $windowHours : null;
        $rows = null;
        $total = 0;

        if ($window === null) {
            foreach ($ladder as $i => $hours) {
                [$rows, $total] = $query($hours, SN_TOPIC_PAGE_SIZE);
                $window = $hours;
                if (count(sn_topic_dedupe($rows)) >= $topic['min_items'] || $i === count($ladder) - 1) {
                    break;
                }
            }
            if ($page > 1) {
                $rows = null; // re-query below with the full window
            }
        }

        if ($rows === null || $page > 1) {
            [$rows, $total] = $query($window, min(100, $page * SN_TOPIC_PAGE_SIZE));
        }

        $skip = $page > 1 ? count(sn_topic_dedupe(array_slice($rows, 0, ($page - 1) * SN_TOPIC_PAGE_SIZE))) : 0;
        $deduped = array_slice(sn_topic_dedupe($rows), $skip, SN_TOPIC_PAGE_SIZE);

        $result['items'] = array_map(static fn($r) => sn_topic_build_item($r, $topic, $now), $deduped);
        $result['total'] = $total;
        $result['window_hours'] = $window;
        $result['widened'] = $window > $requested;
        $result['has_more'] = $page < SN_TOPIC_MAX_PAGES && $total > $page * SN_TOPIC_PAGE_SIZE;
    } catch (Throwable $e) {
        error_log('sn_topic_fetch ERROR: ' . $e->getMessage());
        $result['error'] = true;
    }

    return $result;
}
