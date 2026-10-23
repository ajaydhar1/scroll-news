<?php
// ___news_intel_panel.php
// News Intelligence ("24-Hour Signal Map"): trending people/orgs, places and narrative frames
// for the last 24h, a focus stage per signal, and co-occurrence ("related") signals.
//
// Rendered through fragment_cache_swr() / the warm job (tasks/cache/___warm_home.php),
// so everything here must be deterministic markup with no per-request state.

require_once BASE_PATH . "/core/config/interest.php";
if (!defined('SN_SENT_POS') && is_readable(BASE_PATH . '/core/config/sentiment_thresholds.php')) {
    require_once BASE_PATH . '/core/config/sentiment_thresholds.php';
}

/* ------------------------------------------------------------------ *
 * Helpers (guarded: this file can be included more than once per run)
 * ------------------------------------------------------------------ */

if (!function_exists('sn_nip_e')) {

    function sn_nip_e($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }

    function sn_nip_sentiment_bucket($score): string
    {
        if ($score === null || $score === '' || !is_numeric($score)) return 'unknown';
        $s   = (float)$score;
        $pos = defined('SN_SENT_POS') ? SN_SENT_POS : 0.30;
        $neg = defined('SN_SENT_NEG') ? SN_SENT_NEG : -0.02;
        if ($s >= $pos) return 'positive';
        if ($s <= $neg) return 'negative';
        return 'neutral';
    }

    function sn_nip_plain($text): string
    {
        $t = trim(html_entity_decode(strip_tags((string)$text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return preg_replace('/\s+/u', ' ', $t) ?? $t;
    }

    function sn_nip_pub_ts($raw): ?int
    {
        if (is_numeric($raw)) return (int)$raw;
        if (is_string($raw) && $raw !== '') {
            $t = strtotime($raw);
            if ($t !== false) return $t;
        }
        return null;
    }

    function sn_nip_newsroom_url(array $a): string
    {
        return '/newsroom.php?' . http_build_query([
            'url'      => $a['url'] ?? '',
            'category' => ucfirst((string)($a['source_slug'] ?? '')),
            'pub_date' => sn_nip_pub_ts($a['pub_date'] ?? null),
            'db'       => 1,
        ]);
    }

    function sn_nip_domain(array $a): string
    {
        $host = parse_url((string)($a['url'] ?? ''), PHP_URL_HOST) ?: '';
        return preg_replace('/^www\./', '', (string)$host);
    }

    function sn_nip_tone_pill(string $bucket): string
    {
        $map = [
            'positive' => ['is-pos', 'Positive tone'],
            'negative' => ['is-neg', 'Negative tone'],
            'neutral'  => ['is-neu', 'Neutral tone'],
        ];
        if (!isset($map[$bucket])) return '';
        return '<span class="sn-nip-tp ' . $map[$bucket][0] . '"><i aria-hidden="true"></i>' . $map[$bucket][1] . '</span>';
    }

    /** Publisher link + date + tone, shared by lead and supporting stories. */
    function sn_nip_meta(array $a, string $tone): string
    {
        $domain = sn_nip_domain($a);
        $ts     = sn_nip_pub_ts($a['pub_date'] ?? null);
        $out    = '<div class="sn-nip-meta">';

        if ($domain !== '') {
            $profile = function_exists('sn_publisher_profile_url') ? (sn_publisher_profile_url($domain) ?: '#') : '#';
            $out .= '<a class="sn-nip-pub" href="' . sn_nip_e($profile) . '" data-loading>'
                . '<img src="https://www.google.com/s2/favicons?sz=64&amp;domain=' . sn_nip_e(rawurlencode($domain)) . '" alt="" width="16" height="16" loading="lazy" decoding="async">'
                . '<span>' . sn_nip_e($domain) . '</span></a>';
        }
        if ($ts) {
            $out .= '<span class="sn-nip-dotsep" aria-hidden="true">·</span><span>' . sn_nip_e(date('M j, Y', $ts)) . '</span>';
        }
        $pill = sn_nip_tone_pill($tone);
        if ($pill !== '') {
            $out .= '<span class="sn-nip-dotsep" aria-hidden="true">·</span>' . $pill;
        }
        return $out . '</div>';
    }

    function sn_nip_render_brief(array $a, string $tone): string
    {
        return '<li class="sn-nip-brief">'
            . '<a class="sn-nip-hl" href="' . sn_nip_e(sn_nip_newsroom_url($a)) . '" data-loading>' . sn_nip_e($a['title'] ?? '') . '</a>'
            . sn_nip_meta($a, $tone)
            . '</li>';
    }

    function sn_nip_render_lead(array $a, string $tone, string $w = '24h'): string
    {
        $nlp = json_decode((string)($a['nlp'] ?? '{}'), true);
        if (!is_array($nlp)) $nlp = [];

        $desc = sn_nip_plain($a['description'] ?? '');

        // Emotions (top 2 at >= 15%)
        $emotions = [];
        if (function_exists('sn_normalized_emotion_distribution') && is_array($nlp['emotional_reaction'] ?? null)) {
            foreach (sn_normalized_emotion_distribution($nlp['emotional_reaction'], null) as $emo) {
                if (count($emotions) >= 2) break;
                if ((float)($emo['value'] ?? 0) < 15.0) continue;
                $emotions[] = ucfirst((string)($emo['label'] ?? '')) . ' ' . (int)$emo['value'] . '%';
            }
        }

        // Entity chips: top 2 by in-article mentions; frame chips: top 2 topic scores >= 0.2
        $entityChips = [];
        $entitiesRaw = is_array($nlp['entities'] ?? null) ? $nlp['entities'] : [];
        usort($entitiesRaw, fn($x, $y) => (int)($y['count'] ?? 0) <=> (int)($x['count'] ?? 0));
        foreach ($entitiesRaw as $ent) {
            if (count($entityChips) >= 2) break;
            $name = is_array($ent) ? ($ent['text'] ?? $ent['name'] ?? null) : $ent;
            if ($name) $entityChips[] = (string)$name;
        }
        $topicChips = [];
        $topicsRaw  = is_array($nlp['topics'] ?? null) ? $nlp['topics'] : [];
        arsort($topicsRaw);
        foreach ($topicsRaw as $tName => $score) {
            if (count($topicChips) >= 2) break;
            if ($tName && (float)$score >= 0.2) $topicChips[] = (string)$tName;
        }

        $badges = function_exists('scroll_get_article_badges') ? (scroll_get_article_badges($a) ?: []) : [];

        $h  = '<article class="sn-nip-lead">';
        $h .= '<p class="sn-nip-lbl">Lead story</p>';
        $h .= '<p class="sn-nip-lead-t"><a class="sn-nip-hl" href="' . sn_nip_e(sn_nip_newsroom_url($a)) . '" data-loading>' . sn_nip_e($a['title'] ?? '') . '</a></p>';
        if ($desc !== '') {
            $h .= '<p class="sn-nip-desc">' . sn_nip_e($desc) . '</p>';
        }
        $h .= sn_nip_meta($a, $tone);

        if ($badges) {
            $h .= '<div class="sn-nip-badges scroll-article-badges">';
            foreach ($badges as $badge) {
                $slug = (string)($badge['slug'] ?? '');
                $href = $slug === 'deep-dive' ? '/search.php?mode=nlp&deep_dive=1' : '/search.php?high_signal=1';
                $h .= '<a class="scroll-badge scroll-badge-' . sn_nip_e($slug) . '" href="' . sn_nip_e($href) . '" title="' . sn_nip_e($badge['tooltip'] ?? '') . '" data-loading>' . sn_nip_e($badge['label'] ?? '') . '</a>';
            }
            $h .= '</div>';
        }

        if ($entityChips || $topicChips) {
            $h .= '<div class="sn-nip-chips">';
            foreach ($entityChips as $name) {
                $h .= '<a class="sn-nip-chip" href="' . sn_nip_e(sn_search_url($name)) . '" title="Search headlines: ' . sn_nip_e($name) . '" data-loading>#' . sn_nip_e($name) . '</a>';
            }
            foreach ($topicChips as $name) {
                $h .= '<span class="sn-nip-chip is-frame">' . sn_nip_e($name) . '</span>';
            }
            $h .= '</div>';
        }

        if ($emotions) {
            $h .= '<p class="sn-nip-emo">Emotions in this article: ' . sn_nip_e(implode(' · ', $emotions)) . '</p>';
        }
        return $h . '</article>';
    }

    /** 24 hourly bars, oldest on the left. $hours[0] is the current hour. */
    function sn_nip_sparkline(array $hours): string
    {
        $max = max(1, max($hours));
        $svg = '<svg class="sn-nip-spark" viewBox="0 0 240 44" preserveAspectRatio="none" aria-hidden="true" focusable="false">';
        for ($slot = 0; $slot < 24; $slot++) {
            $v = (int)($hours[23 - $slot] ?? 0);
            $barH = $v > 0 ? max(3, (int)round(($v / $max) * 40)) : 2;
            $cls  = 'sn-nip-sb' . ($v === 0 ? ' is-empty' : '') . ($slot === 23 ? ' is-latest' : '');
            $svg .= '<rect class="' . $cls . '" x="' . ($slot * 10 + 1) . '" y="' . (44 - $barH) . '" width="8" height="' . $barH . '"/>';
        }
        return $svg . '</svg>';
    }

    function sn_nip_render_stage(array $t, array $lensMeta): string
    {
        $lens  = $t['lens'];
        $meta  = $lensMeta[$lens];
        $total = (int)$t['count'];
        $peak  = max($t['hours']);
        $peakAgo = 0;
        foreach ($t['hours'] as $ago => $v) {            // newest wins on ties
            if ($v === $peak) { $peakAgo = $ago; break; }
        }
        $peakTxt = $peak > 0
            ? ($peakAgo === 0 ? 'this hour' : ($peakAgo === 1 ? 'about 1 hour ago' : 'about ' . $peakAgo . ' hours ago'))
            : '';

        $scored = $t['tone']['positive'] + $t['tone']['neutral'] + $t['tone']['negative'];

        $h  = '<div class="sn-nip-stage" id="snNipS-' . sn_nip_e($t['id']) . '" aria-labelledby="snNipH-' . sn_nip_e($t['id']) . '">';
        $h .= '<div class="sn-nip-stage-in">';

        $h .= '<header class="sn-nip-sh">';
        $h .= '<p class="sn-nip-kicker">' . sn_nip_e($meta['label']) . ' · No. ' . ((int)$t['n'] + 1) . '</p>';
        $heading = sn_nip_e($t['label']);
        if ($t['lens'] !== 'topics') {
            $heading = '<a href="' . sn_nip_e(sn_search_url($t['label'])) . '" title="Search headlines: ' . sn_nip_e($t['label']) . '" data-loading>' . $heading . ' <span aria-hidden="true">→</span></a>';
        }
        $h .= '<h4 class="sn-nip-st" id="snNipH-' . sn_nip_e($t['id']) . '">' . $heading . '</h4>';
        $h .= '<p class="sn-nip-sstat"><strong>' . $total . '</strong> ' . ($total === 1 ? 'article' : 'articles') . ' in the last 24 hours' . ($peakTxt !== '' ? ' · busiest ' . $peakTxt : '') . '</p>';
        $h .= '</header>';

        $h .= '<div class="sn-nip-viz">';
        $h .= '<figure class="sn-nip-pulse">';
        $h .= sn_nip_sparkline($t['hours']);
        $h .= '<figcaption><span>24h ago</span><span class="sn-nip-cap">Articles per hour</span><span>Now</span></figcaption>';
        $h .= '</figure>';

        if ($scored > 0) {
            $h .= '<div class="sn-nip-tone-w">';
            $h .= '<div class="sn-nip-tone" aria-hidden="true">';
            foreach (['negative' => 'is-neg', 'neutral' => 'is-neu', 'positive' => 'is-pos'] as $b => $cls) {
                if ($t['tone'][$b] > 0) {
                    $h .= '<span class="' . $cls . '" style="flex-grow:' . (int)$t['tone'][$b] . '"></span>';
                }
            }
            $h .= '</div>';
            $h .= '<p class="sn-nip-tone-l"><span class="sn-nip-lblinl">Tone, ' . $scored . ' scored:</span> '
                . '<span class="is-neg"><i aria-hidden="true"></i>' . (int)$t['tone']['negative'] . ' negative</span> '
                . '<span class="is-neu"><i aria-hidden="true"></i>' . (int)$t['tone']['neutral'] . ' neutral</span> '
                . '<span class="is-pos"><i aria-hidden="true"></i>' . (int)$t['tone']['positive'] . ' positive</span></p>';
            $h .= '</div>';
        }
        $h .= '</div>';

        // Coverage: lead + supporting
        $arts = $t['articles'];
        if ($arts) {
            $h .= sn_nip_render_lead($arts[0]['row'], $arts[0]['tone']);
            if (count($arts) > 1) {
                $h .= '<ul class="sn-nip-more" aria-label="More coverage of ' . sn_nip_e($t['label']) . '">';
                foreach (array_slice($arts, 1) as $a) {
                    $h .= sn_nip_render_brief($a['row'], $a['tone']);
                }
                $h .= '</ul>';
            }
        }

        // Related signals (co-occurrence only)
        if (!empty($t['related'])) {
            $h .= '<div class="sn-nip-rel">';
            $h .= '<p class="sn-nip-lbl">Tagged in the same articles <span class="sn-nip-cap">(co-occurrence, not cause)</span></p>';
            $h .= '<ul class="sn-nip-rel-l">';
            foreach ($t['related'] as $r) {
                $tip = $r['co'] . ' ' . ($r['co'] === 1 ? 'article is' : 'articles are') . ' tagged with both ' . $t['label'] . ' and ' . $r['label']
                    . '. This shows they appear together, not that one affects the other.';
                $h .= '<li><a class="sn-nip-rchip" data-l="' . sn_nip_e($r['lens']) . '" data-jump href="#snNipT-' . sn_nip_e($r['id']) . '" title="' . sn_nip_e($tip) . '">'
                    . '<span class="sn-nip-rdot" aria-hidden="true"></span>'
                    . '<span class="sn-nip-rname">' . sn_nip_e($r['label']) . '</span>'
                    . '<span class="sn-nip-rn">' . (int)$r['co'] . ' shared<span class="sn-nip-sr"> articles, ' . sn_nip_e($lensMeta[$r['lens']]['label']) . '</span></span>'
                    . '</a></li>';
            }
            $h .= '</ul></div>';
        }

        return $h . '</div></div>';
    }
}

/* ------------------------------------------------------------------ *
 * Name normalisation (unchanged from the previous panel)
 * ------------------------------------------------------------------ */

if (!function_exists('normalize_place_name')) {

    // Normalize place names so variants like "U.s", "Us", "U.S." map together
    function normalize_place_name(string $name): array
    {
        $trimmed = trim($name);
        $lower   = mb_strtolower(preg_replace('/\./', '', $trimmed)); // remove dots for matching

        $usVariants = [
            'us',
            'u s',
            'u.s',
            'u.s.',
            'united states',
            'united states of america',
            'usa',
            'u s a',
            'u.s.a',
        ];

        if (in_array($lower, $usVariants, true)) {
            return [
                'key'   => 'us',     // canonical key used internally
                'label' => 'U.S.',   // how we display it in the UI
            ];
        }

        return [
            'key'   => mb_strtolower($trimmed),
            'label' => $trimmed,
        ];
    }

    function normalize_entity_name(string $name): array
    {
        $trimmed = trim($name);

        // Lowercase + strip punctuation for matching
        $clean = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $trimmed));

        // Map of canonical people → label + matching patterns
        $peopleMap = [
            'donald-trump' => [
                'label'    => 'Donald Trump',
                'patterns' => ['trump', 'donald trump', 'donald j trump', 'president trump', 'mr trump'],
            ],
            'joe-biden' => [
                'label'    => 'Joe Biden',
                'patterns' => ['biden', 'joe biden', 'joseph r biden', 'president biden', 'mr biden'],
            ],
            'kamala-harris' => [
                'label'    => 'Kamala Harris',
                'patterns' => ['kamala', 'kamala harris', 'vice president harris', 'vp harris'],
            ],
            'elon-musk' => [
                'label'    => 'Elon Musk',
                'patterns' => ['musk', 'elon', 'elon musk'],
            ],
            'vladimir-putin' => [
                'label'    => 'Vladimir Putin',
                'patterns' => ['putin', 'vladimir putin', 'president putin'],
            ],
            'xi-jinping' => [
                'label'    => 'Xi Jinping',
                'patterns' => ['xi', 'xi jinping', 'president xi'],
            ],
            'benjamin-netanyahu' => [
                'label'    => 'Benjamin Netanyahu',
                'patterns' => ['netanyahu', 'benjamin netanyahu', 'pm netanyahu'],
            ],
            'volodymyr-zelenskyy' => [
                'label'    => 'Volodymyr Zelenskyy',
                'patterns' => ['zelensky', 'zelenskyy', 'volodymyr zelensky', 'volodymyr zelenskyy', 'president zelensky'],
            ],
        ];

        foreach ($peopleMap as $key => $cfg) {
            foreach ($cfg['patterns'] as $pat) {
                // simple contains check on the cleaned string
                if (strpos($clean, $pat) !== false) {
                    return [
                        'key'   => $key,           // canonical key for counts
                        'label' => $cfg['label'],  // pretty label for UI
                    ];
                }
            }
        }

        return [
            'key'   => mb_strtolower($trimmed),
            'label' => $trimmed,
        ];
    }
}

/* ------------------------------------------------------------------ *
 * Aggregation
 * ------------------------------------------------------------------ */

$LENS_META = [
    'entities' => ['label' => 'People & Orgs',    'blurb' => 'Named most often in the last 24 hours', 'context' => 'entity',          'max' => 6],
    'places'   => ['label' => 'Places',           'blurb' => 'Locations the coverage keeps returning to', 'context' => 'entity',      'max' => 4],
    'topics'   => ['label' => 'Narrative frames', 'blurb' => 'Recurring themes in how stories are framed', 'context' => 'narrative_frame', 'max' => 6],
];

$nip = null;

try {
    $db = _pdo_or_null();
    if (!$db) {
        throw new Exception("DB handle not available");
    }

    // Compute cutoff for last 24h in PHP so it works on both SQLite + Postgres
    $tz     = new DateTimeZone('UTC'); // match DB storage timezone if you store UTC
    $nowDt  = new DateTimeImmutable('now', $tz);
    $nowTs  = $nowDt->getTimestamp();
    $cutoff = $nowDt->modify('-24 hours')->format('Y-m-d H:i:s');

    $sql = "
        SELECT
            id,
            url,
            title,
            description,
            source_slug,
            media_url AS image_url,
            pub_date,
            nlp
        FROM articles
        WHERE pub_date IS NOT NULL
          AND pub_date >= :cutoff
          AND url IS NOT NULL
          AND title IS NOT NULL
          AND LOWER(source_slug) != 'sports'
          AND nlp IS NOT NULL
          AND nlp <> '{}'::jsonb
          AND deleted_at IS NULL
        ORDER BY pub_date DESC
        LIMIT 2000
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([':cutoff' => $cutoff]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        throw new Exception("No recent articles found");
    }
    $rows = array_values($rows);

    $ENTITY_MIN_COUNT = 2;   // mentions of an entity inside one article
    $TOPIC_MIN_SCORE  = 0.2; // ignore weak topic scores
    $TOPICS_MAX_COUNT = 6;   // skip articles that are tagged with too many topics
    // Noisy entities hidden from the Signal Map only (case-insensitive exact match, trimmed)
    $ENTITY_EXCLUDE = ['whatsapp', 'email print copy'];

    // lens => key => [article index => true]. Keyed by index so an article counts once per signal.
    $maps   = ['entities' => [], 'places' => [], 'topics' => []];
    $labels = ['entities' => [], 'places' => [], 'topics' => []];
    $artMeta = [];

    foreach ($rows as $i => $row) {
        $nlp = json_decode($row['nlp'] ?? '{}', true) ?: [];
        if (!is_array($nlp)) $nlp = [];

        $entities_object = is_array($nlp['entities'] ?? null) ? $nlp['entities'] : [];
        $topics_array    = $nlp['topics'] ?? [];

        $pubTs = null;
        try {
            $pubTs = (new DateTimeImmutable((string)$row['pub_date'], $tz))->getTimestamp();
        } catch (Throwable $e) {
            $pubTs = null;
        }
        $artMeta[$i] = [
            'ts'   => $pubTs,
            'tone' => sn_nip_sentiment_bucket($nlp['sentiment']['score'] ?? null),
        ];

        foreach ($entities_object as $ent) {
            if (!is_array($ent)) continue;

            $count = isset($ent['count']) ? (int)$ent['count'] : 0;
            if ($count < $ENTITY_MIN_COUNT) continue;

            $name = $ent['text'] ?? null;
            if (!$name) continue;
            if (in_array(mb_strtolower(trim((string)$name)), $ENTITY_EXCLUDE, true)) continue;

            $type = $ent['label'] ?? null;

            if ($type === "PERSON" || $type === "ORG") {
                $norm = normalize_entity_name((string)$name);
                $lens = 'entities';
            } elseif ($type === "GPE" || $type === "LOC") {
                $norm = normalize_place_name((string)$name);
                $lens = 'places';
            } else {
                continue;
            }

            $maps[$lens][$norm['key']][$i] = true;
            if (!isset($labels[$lens][$norm['key']])) {
                $labels[$lens][$norm['key']] = $norm['label'];
            }
        }

        if (is_array($topics_array) && count($topics_array) <= $TOPICS_MAX_COUNT) {
            foreach ($topics_array as $name => $score) {
                if (!$name) continue;
                if ((float)$score < $TOPIC_MIN_SCORE) continue;
                $maps['topics'][mb_strtolower(trim((string)$name))][$i] = true;
            }
        }
    }

    // Story ranking inside a signal: national outlets first, then high-signal publishers, then newest.
    $nationalDomains = [
        'reuters.com', 'apnews.com', 'nytimes.com', 'washingtonpost.com', 'wsj.com', 'bloomberg.com',
        'npr.org', 'bbc.com', 'theguardian.com', 'cnn.com', 'foxnews.com', 'nbcnews.com', 'cbsnews.com',
        'abcnews.go.com', 'usatoday.com', 'politico.com', 'thehill.com',
    ];
    $isNational = function (string $host) use ($nationalDomains): bool {
        foreach ($nationalDomains as $d) {
            if ($host === $d || (strlen($host) > strlen($d) && substr($host, -(strlen($d) + 1)) === '.' . $d)) {
                return true;
            }
        }
        return false;
    };
    $rankCache = [];
    $rankOf = function (int $i) use (&$rankCache, $rows, $isNational): array {
        if (!isset($rankCache[$i])) {
            $row = $rows[$i];
            $rankCache[$i] = [
                $isNational(strtolower(sn_nip_domain($row))) ? 1 : 0,
                (function_exists('scroll_is_high_signal_publisher') && scroll_is_high_signal_publisher($row)) ? 1 : 0,
                (string)($row['pub_date'] ?? ''),
            ];
        }
        return $rankCache[$i];
    };
    $cmp = function (int $a, int $b) use ($rankOf): int {
        $ra = $rankOf($a);
        $rb = $rankOf($b);
        return ($rb[0] <=> $ra[0]) ?: ($rb[1] <=> $ra[1]) ?: strcmp($rb[2], $ra[2]);
    };

    $terms = [];   // lens => list of terms
    foreach ($LENS_META as $lens => $cfg) {
        $counts = array_map('count', $maps[$lens]);
        arsort($counts);

        $terms[$lens] = [];
        $usedInLens   = [];
        $n = 0;

        foreach ($counts as $key => $count) {
            if ($count < 2) break; // sorted: nothing below this qualifies
            if ($n >= $cfg['max']) break;

            $idxs = array_keys($maps[$lens][$key]);
            usort($idxs, $cmp);

            // Prefer stories not already leading another signal in this lens, then fill up.
            $picked = [];
            foreach ($idxs as $i) {
                if (count($picked) >= 3) break;
                if (isset($usedInLens[$i])) continue;
                $picked[] = $i;
                $usedInLens[$i] = true;
            }
            foreach ($idxs as $i) {
                if (count($picked) >= 3) break;
                if (!in_array($i, $picked, true)) $picked[] = $i;
            }

            $hours = array_fill(0, 24, 0);
            $tone  = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
            foreach ($idxs as $i) {
                $ts = $artMeta[$i]['ts'];
                if ($ts !== null) {
                    $ago = (int)floor(($nowTs - $ts) / 3600);
                    $hours[max(0, min(23, $ago))]++;
                }
                if (isset($tone[$artMeta[$i]['tone']])) $tone[$artMeta[$i]['tone']]++;
            }

            $label = $labels[$lens][$key] ?? ucwords(str_replace('-', ' ', (string)$key));
            $terms[$lens][] = [
                'id'           => $lens . '-' . $n,
                'n'            => $n,
                'lens'         => $lens,
                'key'          => (string)$key,
                'label'        => $label,
                'count'        => $count,
                'set'          => $maps[$lens][$key],
                'hours'        => $hours,
                'tone'         => $tone,
                'articles'     => array_map(fn($i) => ['row' => $rows[$i], 'tone' => $artMeta[$i]['tone']], $picked),
                'related'      => [],
            ];
            $n++;
        }
    }

    // Related signals: other-lens signals that share articles with this one (co-occurrence only).
    // Ranked by Jaccard overlap (shared / union) so ubiquitous signals such as "U.S." don't win by size alone.
    $REL_MIN_SHARED  = 2;
    $REL_MIN_JACCARD = 0.08;
    $REL_PER_LENS    = 2;

    $relatedById = [];
    foreach ($terms as $lensA => $listA) {
        foreach ($listA as $a) {
            $cands = [];
            foreach ($terms as $lensB => $listB) {
                if ($lensB === $lensA) continue;
                foreach ($listB as $b) {
                    $shared = count(array_intersect_key($a['set'], $b['set']));
                    if ($shared < $REL_MIN_SHARED) continue;
                    $union = $a['count'] + $b['count'] - $shared;
                    $j = $union > 0 ? $shared / $union : 0;
                    if ($j < $REL_MIN_JACCARD) continue;
                    $cands[$lensB][] = ['id' => $b['id'], 'lens' => $lensB, 'label' => $b['label'], 'co' => $shared, 'j' => $j];
                }
            }
            $rel = [];
            foreach ($cands as $list) {
                usort($list, fn($x, $y) => ($y['j'] <=> $x['j']) ?: ($y['co'] <=> $x['co']));
                $rel = array_merge($rel, array_slice($list, 0, $REL_PER_LENS));
            }
            usort($rel, fn($x, $y) => ($y['j'] <=> $x['j']) ?: ($y['co'] <=> $x['co']));
            $relatedById[$a['id']] = $rel;
        }
    }
    foreach ($terms as $lensA => $listA) {
        foreach ($listA as $k => $a) {
            $terms[$lensA][$k]['related'] = $relatedById[$a['id']] ?? [];
        }
    }

    $nip = [
        'terms'   => $terms,
        'asof'    => $nowTs,
        'stats'   => [
            'article_count_24h' => count($rows),
            'entities'          => count($maps['entities']),
            'places'            => count($maps['places']),
            'topics'            => count($maps['topics']),
        ],
    ];
} catch (Throwable $e) {
    error_log('[IntelPanel] ' . $e->getMessage());
    $nip = null;

    if (!empty($_GET['debug_intel'])) {
        echo '<div class="alert alert-warning small" style="margin:10px 0;">';
        echo '<strong>Intel Panel error:</strong> ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}

$nipLenses = [];
if ($nip) {
    foreach ($LENS_META as $lens => $cfg) {
        if (!empty($nip['terms'][$lens])) $nipLenses[] = $lens;
    }
}

$nipIcons = [
    'entities' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="3.6"/><path d="M5 20c0-3.6 3.1-6 7-6s7 2.4 7 6"/></svg>',
    'places'   => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 21s7-6.2 7-11.5a7 7 0 0 0-14 0C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>',
    'topics'   => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 7h16M4 12h10M4 17h13"/></svg>',
];

if ($nipLenses) {
    $defaultLens = in_array('entities', $nipLenses, true) ? 'entities' : $nipLenses[0];
    $statKey     = ['entities' => 'entities', 'places' => 'places', 'topics' => 'topics'];
    ?>
<section class="sn-nip" aria-labelledby="snNipTitle" data-sn-nip>
    <script>(function(s){var r=s.parentNode;r.classList.add('sn-nip--js');if(!('IntersectionObserver' in window)){r.classList.add('is-in');return}var o=new IntersectionObserver(function(e){if(e[0].isIntersecting){r.classList.add('is-in');o.disconnect()}},{threshold:.12});o.observe(r)})(document.currentScript);</script>
    <div class="sn-nip-band">

        <div class="sn-nip-top">
            <span class="sn-nip-eyebrow">News Intelligence</span>
            <span class="sn-nip-pill"><span class="sn-nip-dot" aria-hidden="true"></span>Last 24 hours · updated <time datetime="<?= gmdate('c', $nip['asof']) ?>" data-sn-time><?= gmdate('g:i A', $nip['asof']) ?> UTC</time></span>
            <span class="sn-nip-tag">From the data</span>
        </div>

        <div class="sn-nip-head">
            <h2 class="sn-nip-title" id="snNipTitle">The 24-Hour Signal Map</h2>
            <p class="sn-nip-dek">What the news cycle is clustering around, ranked by how many of the <?= number_format((int)$nip['stats']['article_count_24h']) ?> analysed articles mention each signal.</p>
        </div>

        <div class="sn-nip-lenses" role="tablist" aria-label="Signal lens" data-sn-lenses>
            <?php foreach ($nipLenses as $lens):
                $top = $nip['terms'][$lens][0]; ?>
                <button type="button" role="tab" class="sn-nip-tab<?= $lens === $defaultLens ? ' is-active' : '' ?>" data-l="<?= sn_nip_e($lens) ?>"
                        id="snNipTab-<?= sn_nip_e($lens) ?>" aria-controls="snNipP-<?= sn_nip_e($lens) ?>"
                        aria-selected="<?= $lens === $defaultLens ? 'true' : 'false' ?>" tabindex="<?= $lens === $defaultLens ? '0' : '-1' ?>">
                    <span class="sn-nip-tab-ico"><?= $nipIcons[$lens] ?></span>
                    <span class="sn-nip-tab-txt">
                        <span class="sn-nip-tab-name"><?= sn_nip_e($LENS_META[$lens]['label']) ?></span>
                        <span class="sn-nip-tab-sub">Top: <?= sn_nip_e($top['label']) ?></span>
                    </span>
                    <span class="sn-nip-tab-num"><?= number_format((int)$nip['stats'][$statKey[$lens]]) ?><span class="sn-nip-sr"> tracked</span></span>
                </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($nipLenses as $lens):
            $list   = $nip['terms'][$lens];
            $maxCnt = max(1, (int)$list[0]['count']);
            $isDef  = $lens === $defaultLens; ?>
            <div class="sn-nip-panel<?= $isDef ? ' is-active' : '' ?>" data-l="<?= sn_nip_e($lens) ?>" id="snNipP-<?= sn_nip_e($lens) ?>"
                 aria-labelledby="snNipTab-<?= sn_nip_e($lens) ?>" style="--n: <?= count($list) ?>">
                <div class="sn-nip-lens-h">
                    <h3><?= $nipIcons[$lens] ?> <?= sn_nip_e($LENS_META[$lens]['label']) ?></h3>
                    <p><?= sn_nip_e($LENS_META[$lens]['blurb']) ?> · <?= number_format((int)$nip['stats'][$statKey[$lens]]) ?> tracked</p>
                </div>
                <ol class="sn-nip-terms" role="list" aria-label="<?= sn_nip_e($LENS_META[$lens]['label']) ?> ranked by number of articles">
                    <?php foreach ($list as $t):
                        $pct    = max(4, round($t['count'] / $maxCnt * 100, 1));
                        $active = $isDef && $t['n'] === 0;
                        $inner  = '<span class="sn-nip-rank" aria-hidden="true">' . ($t['n'] + 1) . '</span>'
                            . '<span class="sn-nip-name">' . sn_nip_e($t['label']) . '</span>'
                            . '<span class="sn-nip-count"><strong>' . (int)$t['count'] . '</strong><span class="sn-nip-unit"> ' . ($t['count'] === 1 ? 'article' : 'articles') . '</span></span>'
                            . '<span class="sn-nip-bar" aria-hidden="true"><i class="sn-nip-fill"></i></span>'; ?>
                        <li class="sn-nip-term<?= $active ? ' is-active' : '' ?>" role="listitem" id="snNipT-<?= sn_nip_e($t['id']) ?>"
                            data-l="<?= sn_nip_e($lens) ?>" style="--w: <?= $pct ?>%; --i: <?= (int)$t['n'] ?>">
                            <div class="sn-nip-sel sn-nip-sel--static"><?= $inner ?></div>
                            <button type="button" class="sn-nip-sel sn-nip-sel--btn" aria-expanded="<?= $active ? 'true' : 'false' ?>"
                                    aria-controls="snNipS-<?= sn_nip_e($t['id']) ?>"><?= $inner ?></button>
                            <?= sn_nip_render_stage($t, $LENS_META) ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        <?php endforeach; ?>

        <p class="sn-nip-sr" aria-live="polite" data-sn-live></p>

        <p class="sn-nip-note">
            Counts are articles from the last 24 hours whose analysis tags each signal; narrative frames are topic scores of 0.2 or higher.
            “Tagged in the same articles” is co-occurrence only: it doesn’t mean one signal causes, influences or is connected to another.
        </p>
    </div>

    <?php include __DIR__ . '/___active_headlines.php'; ?>
</section>
<?php
} else {
    // No intelligence data (DB hiccup / quiet window): still give readers the live wire.
    ?>
<section class="sn-nip sn-nip-solo" aria-label="Live wire">
    <?php include __DIR__ . '/___active_headlines.php'; ?>
</section>
<?php
}
