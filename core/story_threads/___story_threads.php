<?php
/**
 * Story Threads: discovery model for the homepage "Story Threads" panel.
 *
 * A thread is a developing story discovered from NLP entity co-tagging over the last 21 days:
 * a lead entity (person / organisation / place), the entities most often tagged in the same articles
 * (its "cast"), a 21-day coverage pulse, an activity status, and a few representative articles.
 *
 * Everything below the fetch function is a pure function of article rows and a clock, so it can be unit
 * tested without a database (see tests/story_threads_test.php). Relationship numbers are plain
 * co-tagging counts: "A and B are tagged in the same N articles". They never imply cause or influence.
 */

if (!defined('SN_ST_DAYS')) {
    define('SN_ST_DAYS', 21);
}
if (!defined('SN_ST_TZ')) {
    define('SN_ST_TZ', 'America/New_York');
}

/** Entities that are scaffolding rather than stories (sports, media outlets, generic words). */
function sn_st_stop_entities(): array
{
    static $set = null;
    if ($set === null) {
        $set = array_fill_keys([
            // sports (the NLP labels teams and leagues as PERSON/ORG/GPE)
            'goal', 'nfl', 'nba', 'mlb', 'nhl', 'afc', 'nfc', 'mls', 'nascar', 'ncaa', 'playoffs', 'wild card',
            'super bowl', 'tom brady', 'colin cowherd', 'fox sports', 'cfp', 'college football playoff',
            'afc north', 'afc south', 'afc east', 'afc west', 'nfc north', 'nfc south', 'nfc east', 'nfc west',
            'steelers', 'lakers', 'warriors', 'yankees', 'dodgers', 'red sox', 'patriots', 'cowboys', 'packers',
            // media outlets and platforms
            'cnn', 'fox news', 'msnbc', 'cbs news', 'abc news', 'nbc news', 'nbc', 'abc', 'cbs', 'fox', 'reuters',
            'associated press', 'ap', 'ap news', 'the guardian', 'guardian', 'new york times', 'nyt',
            'washington post', 'usa today', 'npr', 'bbc', 'al jazeera', 'bloomberg', 'politico', 'axios',
            'the hill', 'wall street journal', 'espn', 'getty images', 'instagram', 'facebook', 'twitter',
            'youtube', 'tiktok', 'x', 'reddit', 'google news',
            // share-button and page-chrome text scraped into the NLP entities
            'gop', 'democrats', 'republicans', 'email', 'print', 'copy', 'share', 'whatsapp', 'linkedin', 'pinterest', 'javascript', 'opens',
            'threads', 'sms', 'advertisement', 'subscribe', 'sign up', 'newsletter',
            // generic words that show up as entities
            'one', 'american', 'americans', 'state', 'states', 'the state', 'city', 'county', 'government',
            'federal', 'national', 'officials', 'company', 'people', 'police', 'first', 'new',
        ], true);
    }
    return $set;
}

/** Canonical names for the always-in-the-news "standing topics" (kept as a compact secondary row). */
function sn_st_standing_defs(): array
{
    return [
        'donald trump' => ['label' => 'Donald Trump', 'type' => 'PERSON'],
        'u.s.'         => ['label' => 'U.S.',         'type' => 'GPE'],
        'washington'   => ['label' => 'Washington',   'type' => 'GPE'],
        'congress'     => ['label' => 'Congress',     'type' => 'ORG'],
        'house'        => ['label' => 'House',        'type' => 'ORG'],
        'senate'       => ['label' => 'Senate',       'type' => 'ORG'],
        'white house'  => ['label' => 'White House',  'type' => 'ORG'],
    ];
}

/** Spelling variants collapsed to one canonical entity. */
function sn_st_entity_aliases(): array
{
    return [
        'trump' => 'donald trump', 'president trump' => 'donald trump', 'president donald trump' => 'donald trump',
        'u.s' => 'u.s.', 'us' => 'u.s.', 'usa' => 'u.s.', 'u.s.a.' => 'u.s.', 'united states' => 'u.s.',
        'america' => 'u.s.', 'the united states' => 'u.s.', 'the us' => 'u.s.', 'the u.s.' => 'u.s.',
        'the white house' => 'white house', 'the senate' => 'senate', 'the house' => 'house',
    ];
}

function sn_st_norm_entity(string $text): string
{
    $t = mb_strtolower(trim($text), 'UTF-8');
    $t = str_replace(["\u{2019}", "\u{2018}"], "'", $t);
    $t = preg_replace("/'s$/u", '', $t) ?? $t;
    $t = preg_replace('/\s+/u', ' ', $t) ?? $t;
    $t = trim($t, " \t\n\r\0\x0B\"“”,;:");
    $aliases = sn_st_entity_aliases();
    return $aliases[$t] ?? $t;
}

function sn_st_display_name(string $entity, array $votes = []): string
{
    $standing = sn_st_standing_defs();
    if (isset($standing[$entity])) {
        return $standing[$entity]['label'];
    }
    if ($votes) {
        arsort($votes);
        $best = (string)array_key_first($votes);
        if ($best !== '') {
            return $best;
        }
    }
    return mb_convert_case($entity, MB_CASE_TITLE, 'UTF-8');
}

function sn_st_plain(string $s): string
{
    $t = trim(html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    return (string)preg_replace('/\s+/u', ' ', $t);
}

function sn_st_host(string $url): string
{
    $h = strtolower((string)parse_url($url, PHP_URL_HOST));
    return (string)(preg_replace('/^www\./', '', $h) ?: '');
}

function sn_st_title_key(string $title): string
{
    $t = mb_strtolower(trim($title), 'UTF-8');
    $t = preg_replace('/[^\p{L}\p{N} ]+/u', '', $t) ?? $t;
    return trim((string)preg_replace('/\s+/', ' ', $t));
}

/** The 21 local calendar days ending today (oldest first). */
function sn_st_day_keys(DateTimeImmutable $now): array
{
    $tz = new DateTimeZone(SN_ST_TZ);
    $today = $now->setTimezone($tz)->setTime(0, 0);
    $days = [];
    for ($i = SN_ST_DAYS - 1; $i >= 0; $i--) {
        $days[] = $today->modify("-{$i} days")->format('Y-m-d');
    }
    return $days;
}

/**
 * Read-only fetch: one compact query for the window. Sports articles are excluded outright because the
 * NLP labels teams and players as people/organisations and would otherwise dominate the clusters.
 * Entities arrive pre-filtered to PERSON/ORG/GPE inside a single JSON array per article.
 */
function sn_st_fetch_articles(PDO $pdo, ?DateTimeImmutable $now = null): array
{
    $now = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $since = $now->modify('-' . (SN_ST_DAYS + 1) . ' days')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:sP');

    $sql = <<<SQL
        SELECT
            a.id,
            a.title,
            a.description,
            a.url,
            a.source_slug,
            a.pub_date,
            a.created_at,
            COALESCE(a.media_url, '') AS image_url,
            (
                SELECT COALESCE(jsonb_agg(jsonb_build_object(
                    't', e->>'text',
                    'l', e->>'label',
                    'c', CASE WHEN (e->>'count') ~ '^[0-9]{1,4}$' THEN (e->>'count')::int ELSE 1 END
                )), '[]'::jsonb)
                FROM jsonb_array_elements(a.nlp->'entities') e
                WHERE e->>'label' IN ('PERSON', 'ORG', 'GPE')
                  AND LENGTH(TRIM(COALESCE(e->>'text', ''))) >= 3
            ) AS ents
        FROM articles a
        WHERE a.created_at >= :since
          AND a.deleted_at IS NULL
          AND COALESCE(a.source_slug, '') <> 'sports'
          AND a.nlp IS NOT NULL
          AND jsonb_typeof(a.nlp->'entities') = 'array'
          AND COALESCE(a.url, '') NOT ILIKE '%floridapolitics.com%'
        ORDER BY a.id DESC
        SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':since' => $since]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Normalise raw rows into articles with a day index and a map of tagged entities.
 * An article tags an entity when the entity is named at least $minCount times or appears in the headline,
 * which keeps passing mentions out of the counts.
 *
 * @return array{articles: array<int,array>, days: string[]}
 */
function sn_st_prepare(array $rows, DateTimeImmutable $now, int $minCount = 1): array
{
    $tz = new DateTimeZone(SN_ST_TZ);
    $days = sn_st_day_keys($now);
    $dayIndex = array_flip($days);
    $nowTs = $now->getTimestamp();
    $seen = [];
    $articles = [];

    foreach ($rows as $r) {
        $url = (string)($r['url'] ?? '');
        $title = sn_st_plain((string)($r['title'] ?? ''));
        if ($title === '' || !preg_match('#^https?://#i', $url)) {
            continue;
        }
        $raw = $r['pub_date'] ?? null;
        if (!$raw) {
            $raw = $r['created_at'] ?? null;
        }
        $ts = $raw ? strtotime((string)$raw) : false;
        if ($ts === false) {
            continue;
        }
        $ts = min($ts, $nowTs);
        $day = (new DateTimeImmutable('@' . $ts))->setTimezone($tz)->format('Y-m-d');
        if (!isset($dayIndex[$day])) {
            continue;
        }
        $key = sn_st_title_key($title);
        if (isset($seen[$key])) {
            continue; // syndicated duplicate headline
        }
        $seen[$key] = true;

        $ents = $r['ents'] ?? [];
        if (is_string($ents)) {
            $ents = json_decode($ents, true);
        }

        $articles[] = [
            'id'    => (int)($r['id'] ?? 0),
            'title' => $title,
            'desc'  => sn_st_plain((string)($r['description'] ?? '')),
            'url'   => $url,
            'host'  => sn_st_host($url),
            'cat'   => (string)($r['source_slug'] ?? ''),
            'ts'    => $ts,
            'day'   => $dayIndex[$day],
            'image' => (string)($r['image_url'] ?? ''),
            'raw'   => is_array($ents) ? $ents : [],
            'tags'  => [],
            'forms' => [],
        ];
    }

    // Surname merge: "Graham" -> "Lindsey Graham" when one fuller name clearly owns the surname.
    $merge = sn_st_surname_merges($articles);

    $stop = sn_st_stop_entities();
    foreach ($articles as $i => $a) {
        $titleLc = sn_st_fold($a['title']);
        $hayLc = $titleLc . ' ' . sn_st_fold($a['desc']);
        $counts = [];
        $labels = [];
        $forms = [];
        $visible = [];
        $inTitle = [];
        foreach ($a['raw'] as $e) {
            $text = (string)($e['t'] ?? '');
            $name = sn_st_norm_entity($text);
            $name = $merge[$name] ?? $name;
            if ($name === '' || mb_strlen($name) < 3 || isset($stop[$name]) || sn_st_is_junk($name)) {
                continue;
            }
            $counts[$name] = ($counts[$name] ?? 0) + max(1, (int)($e['c'] ?? 1));
            $labels[$name] = $labels[$name] ?? (string)($e['l'] ?? '');
            $f = trim($text);
            $forms[$name][$f] = ($forms[$name][$f] ?? 0) + 1;
            // Only mentions a reader can see count: the surface form must appear in the headline or description
            $surface = sn_st_fold(preg_replace("/'s$/u", '', sn_st_fold($text)) ?? $text);
            if (!isset($visible[$name]) && sn_st_contains_word($hayLc, $surface)) {
                $visible[$name] = true;
            }
            if (!isset($inTitle[$name]) && sn_st_contains_word($titleLc, $surface)) {
                $inTitle[$name] = true;
            }
        }
        $tags = [];
        foreach ($counts as $name => $c) {
            if (isset($visible[$name]) || $c >= 3) {
                $tags[$name] = ['label' => $labels[$name], 'cnt' => $c, 'title' => isset($inTitle[$name])];
            }
        }
        $articles[$i]['tags'] = $tags;
        $articles[$i]['forms'] = array_intersect_key($forms, $tags);
        unset($articles[$i]['raw']);
    }

    return ['articles' => $articles, 'days' => $days];
}

/** Share-widget and page-chrome strings that the NLP step sometimes returns as entities. */
function sn_st_is_junk(string $name): bool
{
    return preg_match('/\b(email|print|copy|share|whatsapp|linkedin|facebook|pinterest|javascript|opens|reddit|threads|subscribe|newsletter|advertisement|cookie|sign up)\b/u', $name) === 1;
}

/** Lowercase with typographic quotes flattened, for visibility checks. */
function sn_st_fold(string $s): string
{
    return str_replace(["\u{2019}", "\u{2018}"], "'", mb_strtolower($s, 'UTF-8'));
}

/** Whole-word containment (so "us" does not match "business"). */
function sn_st_contains_word(string $haystack, string $needle): bool
{
    $needle = trim($needle);
    if ($needle === '' || mb_stripos($haystack, $needle) === false) {
        return false;
    }
    return preg_match('/(?<![\p{L}\p{N}])' . preg_quote($needle, '/') . '(?![\p{L}\p{N}])/u', $haystack) === 1;
}

/** @return array<string,string> single-token surname => fuller person name */
function sn_st_surname_merges(array $articles): array
{
    $presence = [];
    foreach ($articles as $i => $a) {
        foreach ($a['raw'] as $e) {
            if (($e['l'] ?? '') !== 'PERSON') {
                continue;
            }
            $n = sn_st_norm_entity((string)($e['t'] ?? ''));
            if ($n !== '') {
                $presence[$n][$i] = true;
            }
        }
    }
    $byLast = [];
    foreach ($presence as $name => $set) {
        if (strpos($name, ' ') !== false && count($set) >= 3) {
            $parts = explode(' ', $name);
            $byLast[end($parts)][] = $name;
        }
    }
    $merge = [];
    foreach ($presence as $single => $set) {
        if (strpos($single, ' ') !== false || mb_strlen($single) < 4 || empty($byLast[$single])) {
            continue;
        }
        $cands = [];
        foreach ($byLast[$single] as $full) {
            $cands[$full] = ['n' => count($presence[$full]), 'overlap' => count(array_intersect_key($set, $presence[$full]))];
        }
        uasort($cands, static fn($x, $y) => $y['n'] <=> $x['n']);
        $names = array_keys($cands);
        $best = $names[0];
        $second = isset($names[1]) ? $cands[$names[1]]['n'] : 0;
        // The fuller name must clearly own the surname, and the two must actually co-occur
        if (($second === 0 || $cands[$best]['n'] >= 2 * $second) && $cands[$best]['overlap'] / max(1, count($set)) >= 0.4) {
            $merge[$single] = $best;
        }
    }
    return $merge;
}

/** Classify a 21-day series (oldest first; the last cell is today and still filling). */
function sn_st_status(array $s): array
{
    $s = array_values($s);
    $n = count($s);
    $sum = static fn(int $a, int $b): int => (int)array_sum(array_slice($s, $a, $b - $a + 1));

    $recent = $sum($n - 8, $n - 2);   // seven complete days before today
    $prior  = $sum($n - 15, $n - 9);  // the seven before that
    $last3  = $sum($n - 3, $n - 1);

    // Resurfacing: a quiet gap of 4+ days inside the story, followed by renewed coverage in the last 3 days
    $first = null;
    foreach ($s as $i => $c) {
        if ($c > 0) {
            $first = $i;
            break;
        }
    }
    $run = 0;
    $gapEnd = null;
    $gapLen = 0;
    if ($first !== null) {
        for ($i = $first; $i < $n; $i++) {
            if ($s[$i] === 0) {
                $run++;
            } else {
                if ($run >= 4) {
                    $gapLen = $run;
                    $gapEnd = $i;
                }
                $run = 0;
            }
        }
    }
    if ($gapEnd !== null && $gapEnd >= $n - 3 && $last3 >= 2) {
        return ['key' => 'resurfacing', 'label' => 'Resurfacing', 'why' => "Back in the news after {$gapLen} quiet days: {$last3} articles in the last 3 days."];
    }

    $prevMax = $n > 3 ? max(array_slice($s, 0, $n - 3)) : 0;
    $recentMax = max(array_slice($s, $n - 3));
    $nonZero = array_filter($s);
    $meanActive = $nonZero ? array_sum($nonZero) / count($nonZero) : 0;
    if ($recentMax >= 4 && $recentMax >= $prevMax && $recentMax >= 1.5 * $meanActive) {
        return ['key' => 'peaking', 'label' => 'Peaking', 'why' => "Heaviest coverage of the 21 days is happening now ({$recentMax} articles on a recent day)."];
    }

    if ($recent >= max(5, 1.4 * $prior)) {
        $vs = $prior > 0 ? ' (' . round($recent / $prior, 1) . '×)' : '';
        return ['key' => 'building', 'label' => 'Building', 'why' => "Coverage is rising: {$recent} articles in the last 7 full days vs {$prior} the week before{$vs}."];
    }

    if (($s[$n - 1] === 0 && $s[$n - 2] === 0) || ($prior >= 5 && $recent <= 0.6 * $prior)) {
        return ['key' => 'cooling', 'label' => 'Cooling', 'why' => "Coverage is easing: {$recent} articles in the last 7 full days vs {$prior} the week before."];
    }

    return ['key' => 'sustained', 'label' => 'Sustained', 'why' => "Coverage is holding steady: {$recent} articles in the last 7 full days vs {$prior} the week before."];
}

/**
 * Build the Story Threads model.
 *
 * Options: min_lead_articles (8), min_active_days (5), min_span_days (7), max_idle_days (3),
 * cast_size (5), min_shared (3), max_threads (6), dedupe_overlap (0.6), previews (5).
 */
function sn_st_build_model(array $rows, DateTimeImmutable $now, array $opts = []): array
{
    $o = $opts + [
        'min_lead_articles' => 6, 'min_active_days' => 5, 'min_span_days' => 7, 'max_idle_days' => 3,
        'cast_size' => 5, 'min_shared' => 3, 'max_threads' => 6, 'dedupe_overlap' => 0.6,
        'previews' => 5, 'min_count' => 1, 'max_leads' => 140, 'min_publishers' => 4,
    ];

    $prep = sn_st_prepare($rows, $now, (int)$o['min_count']);
    $articles = $prep['articles'];
    $days = $prep['days'];
    $nDays = count($days);
    $standing = sn_st_standing_defs();
    $stop = sn_st_stop_entities();

    // Inverted index: entity => [article index => true], plus display-form votes
    $index = [];
    $forms = [];
    foreach ($articles as $i => $a) {
        foreach ($a['tags'] as $name => $_) {
            $index[$name][$i] = true;
        }
        foreach ($a['forms'] as $name => $fs) {
            foreach ($fs as $f => $c) {
                $forms[$name][$f] = ($forms[$name][$f] ?? 0) + $c;
            }
        }
    }
    $count = array_map('count', $index);

    $seriesOf = static function (array $set) use ($articles, $nDays): array {
        $s = array_fill(0, $nDays, 0);
        foreach ($set as $i => $_) {
            $s[$articles[$i]['day']]++;
        }
        return $s;
    };

    // Candidate leads: frequent, non-standing, non-stop entities
    $cands = [];
    foreach ($count as $name => $n) {
        if ($n >= $o['min_lead_articles'] && !isset($standing[$name]) && !isset($stop[$name])) {
            $cands[$name] = $n;
        }
    }
    arsort($cands);
    $cands = array_slice($cands, 0, (int)$o['max_leads'], true);

    $threads = [];
    foreach ($cands as $lead => $nLead) {
        // Co-tagging counts with every other entity
        $shared = [];
        foreach ($index[$lead] as $i => $_) {
            foreach ($articles[$i]['tags'] as $other => $t) {
                if ($other !== $lead) {
                    $shared[$other] = ($shared[$other] ?? 0) + 1;
                }
            }
        }
        $scored = [];
        foreach ($shared as $other => $c) {
            if ($c < $o['min_shared'] || isset($stop[$other])) {
                continue;
            }
            // Skip name fragments of the lead ("iran" vs "iran international")
            if (preg_match('/(^| )' . preg_quote($lead, '/') . '( |$)/u', $other) || preg_match('/(^| )' . preg_quote($other, '/') . '( |$)/u', $lead)) {
                continue;
            }
            // Cosine association keeps ever-present entities (Trump, U.S.) from joining every cast
            $scored[$other] = $c / sqrt($nLead * $count[$other]);
        }
        arsort($scored);
        $castNames = array_slice(array_keys($scored), 0, (int)$o['cast_size']);
        if (count($castNames) < 2) {
            continue;
        }

        // Thread articles: the lead is prominent (in the headline or mentioned repeatedly) and a cast member is tagged
        $set = [];
        foreach ($index[$lead] as $i => $_) {
            $lt = $articles[$i]['tags'][$lead];
            if (!$lt['title'] && $lt['cnt'] < 2) {
                continue;
            }
            foreach ($castNames as $m) {
                if (isset($articles[$i]['tags'][$m])) {
                    $set[$i] = true;
                    break;
                }
            }
        }
        $n = count($set);
        // Co-tag counts restricted to the thread's own articles so percentages never exceed 100%
        $shared = [];
        foreach ($set as $i => $_) {
            foreach ($articles[$i]['tags'] as $other => $_t) {
                if ($other !== $lead) {
                    $shared[$other] = ($shared[$other] ?? 0) + 1;
                }
            }
        }
        $castNames = array_values(array_filter($castNames, static fn($m) => ($shared[$m] ?? 0) >= 2));
        if (count($castNames) < 2) {
            continue;
        }
        $hostSet = [];
        foreach ($set as $i => $_) {
            $hostSet[$articles[$i]['host']] = true;
        }
        if (count($hostSet) < (int)$o['min_publishers']) {
            continue;
        }
        $series = $seriesOf($set);
        $active = count(array_filter($series));
        $first = null;
        $last = null;
        foreach ($series as $d => $c) {
            if ($c > 0) {
                $first = $first ?? $d;
                $last = $d;
            }
        }
        if ($n < $o['min_lead_articles'] || $active < $o['min_active_days'] || $first === null
            || ($last - $first + 1) < $o['min_span_days'] || $last < $nDays - 1 - (int)$o['max_idle_days']) {
            continue;
        }

        $idle = ($nDays - 1) - $last;
        $recency = $idle <= 1 ? 1.0 : ($idle === 2 ? 0.85 : 0.7);

        $threads[$lead] = [
            'lead' => $lead, 'set' => $set, 'n' => $n, 'series' => $series, 'active' => $active,
            'first' => $first, 'last' => $last, 'cast' => $castNames, 'shared' => $shared,
            'score' => $n * sqrt($active / $nDays) * $recency,
        ];
    }

    // Deduplicate: a thread whose articles are mostly contained in a higher-ranked thread is the same story
    uasort($threads, static fn($a, $b) => $b['score'] <=> $a['score']);
    $kept = [];
    $absorbed = [];
    foreach ($threads as $lead => $t) {
        $dup = null;
        foreach ($kept as $k => $kt) {
            $inter = count(array_intersect_key($t['set'], $kt['set']));
            $overlap = $inter / max(1, min($t['n'], $kt['n']));
            $related = in_array($lead, $kt['cast'], true) || in_array($k, $t['cast'], true);
            if ($overlap >= $o['dedupe_overlap'] || ($related && $overlap >= 0.4)) {
                $dup = $k;
                break;
            }
        }
        if ($dup !== null) {
            $absorbed[$dup][] = $lead;
            continue;
        }
        $kept[$lead] = $t;
        if (count($kept) >= (int)$o['max_threads'] + 4) {
            break; // headroom so the featured-timeline exclusion can still fill the board
        }
    }

    $out = [];
    foreach ($kept as $lead => $t) {
        $out[] = sn_st_finish_thread($lead, $t, $articles, $days, $count, $index, $forms, $absorbed[$lead] ?? [], $o);
    }

    $topics = [];
    foreach ($standing as $name => $def) {
        if (!empty($index[$name])) {
            $topics[] = ['entity' => $name, 'label' => $def['label'], 'n' => count($index[$name]), 'series' => $seriesOf($index[$name])];
        }
    }
    usort($topics, static fn($a, $b) => $b['n'] <=> $a['n']);

    return [
        'version' => 1,
        'asof'    => $now->getTimestamp(),
        'days'    => $days,
        'stats'   => ['articles' => count($articles), 'entities' => count($index), 'candidates' => count($cands), 'passed' => count($threads)],
        'threads' => $out,
        'topics'  => $topics,
    ];
}

function sn_st_finish_thread(string $lead, array $t, array $articles, array $days, array $count, array $index, array $forms, array $absorbed, array $o): array
{
    $cast = [];
    foreach ($t['cast'] as $m) {
        $cast[] = [
            'entity' => $m,
            'name'   => sn_st_display_name($m, $forms[$m] ?? []),
            'shared' => $t['shared'][$m],
            'pct'    => (int)round($t['shared'][$m] / max(1, $t['n']) * 100),
        ];
    }

    // Member-to-member co-tagging counts over every article in the window
    $pairs = [];
    $cn = count($t['cast']);
    for ($a = 0; $a < $cn; $a++) {
        for ($b = $a + 1; $b < $cn; $b++) {
            $ea = $t['cast'][$a];
            $eb = $t['cast'][$b];
            $c = count(array_intersect_key($index[$ea], $index[$eb]));
            if ($c >= 2) {
                $pairs[] = ['a' => $ea, 'b' => $eb, 'shared' => $c];
            }
        }
    }

    // Previews: newest, most connected, then newest remaining, with distinct headlines
    $pool = [];
    foreach ($t['set'] as $i => $_) {
        $a = $articles[$i];
        $hits = array_values(array_intersect(array_keys($a['tags']), $t['cast']));
        $pool[] = [
            'i' => $i, 'ts' => $a['ts'], 'hits' => $hits,
            'conn' => count($hits) + ($a['tags'][$lead]['title'] ? 1 : 0) + min(3, $a['tags'][$lead]['cnt'] / 4),
        ];
    }
    usort($pool, static fn($x, $y) => $y['ts'] <=> $x['ts']);
    $picked = [];
    if ($pool) {
        $picked[$pool[0]['i']] = ['p' => $pool[0], 'badge' => 'Newest'];
        $byConn = $pool;
        usort($byConn, static fn($x, $y) => [$y['conn'], $y['ts']] <=> [$x['conn'], $x['ts']]);
        foreach ($byConn as $p) {
            if (!isset($picked[$p['i']])) {
                $picked[$p['i']] = ['p' => $p, 'badge' => 'Most connected'];
                break;
            }
        }
        foreach ($pool as $p) {
            if (count($picked) >= (int)$o['previews']) {
                break;
            }
            if (!isset($picked[$p['i']])) {
                $picked[$p['i']] = ['p' => $p, 'badge' => ''];
            }
        }
    }
    $previews = [];
    $seenTitles = [];
    foreach ($picked as $i => $pk) {
        $a = $articles[$i];
        $k = sn_st_title_key($a['title']);
        if (isset($seenTitles[$k])) {
            continue;
        }
        $seenTitles[$k] = true;
        $desc = $a['desc'];
        if (mb_strlen($desc) > 240) {
            $desc = rtrim(mb_substr($desc, 0, 237)) . '…';
        }
        $previews[] = [
            'id' => $a['id'], 'title' => $a['title'], 'desc' => $desc, 'url' => $a['url'], 'host' => $a['host'],
            'cat' => $a['cat'], 'ts' => $a['ts'], 'image' => $a['image'], 'badge' => $pk['badge'], 'ents' => $pk['p']['hits'],
        ];
    }
    $order = ['Newest' => 0, 'Most connected' => 1, '' => 2];
    usort($previews, static fn($x, $y) => [$order[$x['badge']], -$x['ts']] <=> [$order[$y['badge']], -$y['ts']]);

    $hosts = [];
    foreach ($t['set'] as $i => $_) {
        if ($articles[$i]['host'] !== '') {
            $hosts[$articles[$i]['host']] = true;
        }
    }

    $series = $t['series'];
    $peak = max($series);
    $peakDay = array_search($peak, array_reverse($series, true), true);

    return [
        'id'         => trim((string)preg_replace('/[^a-z0-9]+/', '-', $lead), '-') ?: 'thread',
        'lead'       => $lead,
        'name'       => sn_st_display_name($lead, $forms[$lead] ?? []),
        'n'          => $t['n'],
        'lead_n'     => $count[$lead],
        'active'     => $t['active'],
        'first'      => $days[$t['first']],
        'last'       => $days[$t['last']],
        'series'     => $series,
        'peak'       => $peak,
        'peak_day'   => $days[(int)$peakDay],
        'publishers' => count($hosts),
        'status'     => sn_st_status($series),
        'cast'       => $cast,
        'pairs'      => $pairs,
        'previews'   => $previews,
        'absorbed'   => $absorbed,
        'score'      => round($t['score'], 2),
        'timeline'   => null,
    ];
}

/**
 * Attach curated Coverage Timeline links and drop the thread the homepage already features.
 * Matching is exact on the lead entity (alias or search term) so unrelated stories are never linked.
 *
 * @param array $stories slug => story definition
 */
function sn_st_link_timelines(array $threads, array $stories, ?string $featuredSlug, int $max = 6): array
{
    $norm = static fn(string $s): string => mb_strtolower(trim($s), 'UTF-8');
    $out = [];
    foreach ($threads as $t) {
        $match = null;
        foreach ($stories as $story) {
            foreach (array_merge($story['aliases'] ?? [], $story['terms'] ?? []) as $nm) {
                if ($norm((string)$nm) === $t['lead']) {
                    $match = $story;
                    break 2;
                }
            }
        }
        if ($match) {
            if ($featuredSlug !== null && ($match['slug'] ?? '') === $featuredSlug) {
                continue; // already the Featured Coverage Timeline directly above
            }
            $chapter = null;
            if (!empty($match['chapters'])) {
                $c = end($match['chapters']);
                $chapter = ['title' => (string)($c['title'] ?? ''), 'to' => (string)($c['to'] ?? '')];
            }
            $t['timeline'] = [
                'slug'    => (string)$match['slug'],
                'title'   => (string)($match['title'] ?? $t['name']),
                'status'  => (string)($match['status'] ?? 'closed'),
                'chapter' => $chapter,
            ];
        }
        $out[] = $t;
        if (count($out) >= $max) {
            break;
        }
    }
    return $out;
}
