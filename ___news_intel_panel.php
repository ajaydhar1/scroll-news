<?php
// ___news_intel_panel.php
// News Intelligence Panel: trending entities / places / topics for last 24h

require_once 'config_interest.php';

if (!function_exists('_pdo_or_null')) {
    require_once __DIR__ . '/___modules.php';
}

$intel_panel = [
    'stats'    => [],
    'entities' => [],
    'places'   => [],
    'topics'   => [],
];

// Map sentiment label -> emoji
function nlp_sentiment_emoji(?string $label): string
{
    if (!$label) return '';
    switch (strtolower($label)) {
        case 'positive': return '🙂';
        case 'negative': return '☹️';
        case 'neutral':  return '😐';
        case 'mixed':    return '😶';
        default:         return '😐';
    }
}

// Get top N emotions with percentages
function nlp_top_emotions_detail(array $emotionalReaction, int $max = 2, float $minPercent = 10.0): array
{
    if (!$emotionalReaction) return [];
    arsort($emotionalReaction); // strongest first

    $out = [];
    foreach ($emotionalReaction as $name => $pct) {
        if (count($out) >= $max) break;
        if ($pct < $minPercent) continue;
        $out[] = ['name' => $name, 'pct' => (float)$pct];
    }
    return $out;
}



try {
    $db = _pdo_or_null();
    if (!$db) {
        throw new Exception("DB handle not available");
    }

    // Compute cutoff for last 24h in PHP so it works on both SQLite + Postgres
    $tz = new DateTimeZone('UTC'); // match DB storage timezone if you store UTC
    $cutoff = (new DateTimeImmutable('now', $tz))->modify('-24 hours')->format('Y-m-d H:i:s');

    $sql = "
        SELECT
            id,
            url,
            title,
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
        ORDER BY pub_date DESC
        LIMIT 2000
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([':cutoff' => $cutoff]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        throw new Exception("No recent articles found");
    }

    $articleCount = count($rows);

    $entityCounts  = [];
    $entityArticles = [];
    $uniqueEntityKeys = [];
    $entityLabelMap  = [];

    $placeCounts   = [];
    $placeArticles  = [];
    $uniquePlaceKeys  = [];
    $placeLabelMap = [];

    $topicCounts   = [];
    $topicArticles  = [];
    $uniqueTopicKeys  = [];

    // Normalize place names so variants like "U.s", "Us", "U.S." map together
    function normalize_place_name(string $name): array
    {
        $trimmed = trim($name);
        $lower   = mb_strtolower(preg_replace('/\./', '', $trimmed)); // remove dots for matching

        // You can expand this list as needed
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

        // Default: use the lowercased key + original label
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
                'patterns' => [
                    'trump',
                    'donald trump',
                    'donald j trump',
                    'president trump',
                    'mr trump',
                ],
            ],
            'joe-biden' => [
                'label'    => 'Joe Biden',
                'patterns' => [
                    'biden',
                    'joe biden',
                    'joseph r biden',
                    'president biden',
                    'mr biden',
                ],
            ],
            'kamala-harris' => [
                'label'    => 'Kamala Harris',
                'patterns' => [
                    'kamala',
                    'kamala harris',
                    'vice president harris',
                    'vp harris',
                ],
            ],
            'elon-musk' => [
                'label'    => 'Elon Musk',
                'patterns' => [
                    'musk',
                    'elon',
                    'elon musk',
                ],
            ],
            'vladimir-putin' => [
                'label'    => 'Vladimir Putin',
                'patterns' => [
                    'putin',
                    'vladimir putin',
                    'president putin',
                ],
            ],
            'xi-jinping' => [
                'label'    => 'Xi Jinping',
                'patterns' => [
                    'xi',
                    'xi jinping',
                    'president xi',
                ],
            ],
            'benjamin-netanyahu' => [
                'label'    => 'Benjamin Netanyahu',
                'patterns' => [
                    'netanyahu',
                    'benjamin netanyahu',
                    'pm netanyahu',
                ],
            ],
            'volodymyr-zelenskyy' => [
                'label'    => 'Volodymyr Zelenskyy',
                'patterns' => [
                    'zelensky',
                    'zelenskyy',
                    'volodymyr zelensky',
                    'volodymyr zelenskyy',
                    'president zelensky',
                ],
            ],
        ];

        // Try to match against the known people map
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

        // Default: use original text as label, lowered key
        return [
            'key'   => mb_strtolower($trimmed),
            'label' => $trimmed,
        ];
    }


    foreach ($rows as $row) {
        $pubDate = $row['pub_date'];

        // helper: decode JSON as array safely
        $nlp = json_decode($row['nlp'] ?? '{}', true) ?: [];
        $entities_object = $nlp['entities'] ?? [];
        $topics_array    = $nlp['topics']   ?? [];

        // Normalize entities and places (can be array of strings or array of objects)

        // Only include entities that appear at least >= 2 times
        $ENTITY_MIN_COUNT = 2;

        foreach ($entities_object as $ent) {
            // If it's not an array, it probably doesn't have a count field
            $count = isset($ent['count']) ? (int)$ent['count'] : 0;
            if ($count < $ENTITY_MIN_COUNT) {
                continue;
            }

            $name = is_array($ent) ? ($ent['text'] ?? null) : $ent;
            if (!$name) continue;

            $type = is_array($ent) ? ($ent['label'] ?? null) : null;

            // People → trending entities
            if ($type === "PERSON" || $type === "ORG") {
                // Normalize Trump variants etc.
                $norm  = normalize_entity_name($name);
                $key   = $norm['key'];   // e.g. 'donald-trump'
                $label = $norm['label']; // e.g. 'Donald Trump'

                $entityCounts[$key] = ($entityCounts[$key] ?? 0) + 1;
                $entityArticles[$key][] = $row;
                $uniqueEntityKeys[$key] = true;

                if (!isset($entityLabelMap[$key])) {
                    $entityLabelMap[$key] = $label;
                }
            }
            // GPE / LOC → trending places
            elseif ($type === "GPE" || $type === "LOC") {
                $norm  = normalize_place_name($name);
                $key   = $norm['key'];   // e.g. 'us'
                $label = $norm['label']; // e.g. 'U.S.'

                $placeCounts[$key] = ($placeCounts[$key] ?? 0) + 1;
                $placeArticles[$key][] = $row;
                $uniquePlaceKeys[$key] = true;

                if (!isset($placeLabelMap[$key])) {
                    $placeLabelMap[$key] = $label;
                }
            }
        }

        // Normalize topics: $topics is now [ 'Health' => '0.07', 'Government' => '0.63', ... ]
        // Only include topics where score >= 0.2
        $TOPIC_MIN_SCORE = 0.2;

        // Only include articles with 6 or less topics
        $TOPICS_MAX_COUNT = 6;

        // Only include articles with 6 or fewer topics
        if (is_array($topics_array) && count($topics_array) <= $TOPICS_MAX_COUNT) {
            foreach ($topics_array as $name => $score) {
                if (!$name) {
                    continue;
                }

                $scoreVal = (float)$score;
                if ($scoreVal < $TOPIC_MIN_SCORE) {
                    continue; // skip weak topics
                }

                $key = mb_strtolower(trim($name));

                // Each qualifying topic increments its count by 1…
                $topicCounts[$key] = ($topicCounts[$key] ?? 0) + 1;

                // …or if you want to weight by score, replace the line above with:
                // $topicCounts[$key] = ($topicCounts[$key] ?? 0) + $scoreVal;

                $topicArticles[$key][] = $row;
                $uniqueTopicKeys[$key] = true;
            }
        }
        // else: article has too many topics (7+), so we skip it entirely for trending topics
    }

    $usedArticleKeys = []; // global “already displayed in the panel” tracker
                    
    // Helper to build “trending” list from counts + article map
    $buildTrending = function (
        array $counts,
        array $articleMap,
        int $maxItems = 8,
        int $minCount = 2,
        int $maxArticlesPerItem = 4,
        array $labelMap = [],
        array &$usedArticleKeys = []   // <-- add this
    ) {
        if (!$counts) return [];

        arsort($counts);

        $trending = [];

        foreach ($counts as $key => $count) {
            if ($count < $minCount) continue;

            $articles = $articleMap[$key] ?? [];
            if (!$articles) continue;

            // Pick up to N articles that haven't been used anywhere else in the panel
            $picked = [];

            $nationalDomains = [
                'reuters.com',
                'apnews.com',
                'nytimes.com',
                'washingtonpost.com',
                'wsj.com',
                'bloomberg.com',
                'npr.org',
                'bbc.com',
                'theguardian.com',
                'cnn.com',
                'foxnews.com',
                'nbcnews.com',
                'cbsnews.com',
                'abcnews.go.com',
                'usatoday.com',
                'politico.com',
                'thehill.com',
            ];

            $domainOf = function($url) {
                $host = parse_url($url ?: '', PHP_URL_HOST) ?: '';
                $host = strtolower(preg_replace('/^www\./', '', $host));
                return $host;
            };

            $endsWith = function(string $haystack, string $needle): bool {
                $len = strlen($needle);
                if ($len === 0) return true;
                return substr($haystack, -$len) === $needle;
            };

            $endsWithAny = function($host, $domains) use ($endsWith) {
                foreach ($domains as $d) {
                    $d = strtolower($d);

                    if (
                        $host === $d ||
                        (strlen($host) > strlen($d) && $endsWith($host, '.' . $d))
                    ) {
                        return true;
                    }
                }
                return false;
            };

            // Rank articles: national first, then high-signal, then newest
            usort($articles, function ($a, $b) use ($nationalDomains, $domainOf, $endsWithAny) {

                $da = $domainOf($a['url'] ?? '');
                $db = $domainOf($b['url'] ?? '');

                $aNational = $endsWithAny($da, $nationalDomains) ? 1 : 0;
                $bNational = $endsWithAny($db, $nationalDomains) ? 1 : 0;
                if ($aNational !== $bNational) return $bNational <=> $aNational;

                // If you want to use your existing “high signal publisher” logic here:
                $aHigh = function_exists('scroll_is_high_signal_publisher') && scroll_is_high_signal_publisher($a) ? 1 : 0;
                $bHigh = function_exists('scroll_is_high_signal_publisher') && scroll_is_high_signal_publisher($b) ? 1 : 0;
                if ($aHigh !== $bHigh) return $bHigh <=> $aHigh;

                // Newest first (safe even if pub_date is string)
                return strcmp($b['pub_date'] ?? '', $a['pub_date'] ?? '');
            });

            foreach ($articles as $row) {
                if (count($picked) >= $maxArticlesPerItem) break;

                $dedupeKey = null;
                if (!empty($row['id'])) {
                    $dedupeKey = 'id:' . $row['id'];
                } elseif (!empty($row['url'])) {
                    $dedupeKey = 'url:' . $row['url'];
                }

                if (!$dedupeKey) continue;

                if (isset($usedArticleKeys[$dedupeKey])) {
                    continue; // already shown elsewhere
                }

                $usedArticleKeys[$dedupeKey] = true;
                $picked[] = $row;
            }

            // If we couldn't find anything unique, skip this trending item entirely
            if (!$picked) continue;

            $label = $labelMap[$key] ?? ucwords(str_replace('-', ' ', $key));

            $trending[] = [
                'label'    => $label,
                'count'    => $count,
                'articles' => $picked,
            ];

            if (count($trending) >= $maxItems) break;
        }

        return $trending;
    };

    $intel_panel['entities'] = $buildTrending($entityCounts, $entityArticles, 4, 2, 2, $entityLabelMap, $usedArticleKeys);
    $intel_panel['places']   = $buildTrending($placeCounts,  $placeArticles,  2, 2, 2, $placeLabelMap,  $usedArticleKeys);
    $intel_panel['topics']   = $buildTrending($topicCounts,  $topicArticles,  4, 2, 2, [],              $usedArticleKeys);


    $intel_panel['stats'] = [
        'article_count_24h' => $articleCount,
        'unique_entities'   => count($uniqueEntityKeys),
        'unique_places'     => count($uniquePlaceKeys),
        'unique_topics'     => count($uniqueTopicKeys),
    ];
} catch (Throwable $e) {
    error_log('[IntelPanel] ' . $e->getMessage());
    $intel_panel = null;

    if (!empty($_GET['debug_intel'])) {
        echo '<div class="alert alert-warning small" style="margin:10px 0;">';
        echo '<strong>Intel Panel error:</strong> ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}

if (empty($intel_panel) || $intel_panel['entities'] === [] && $intel_panel['places'] === [] && $intel_panel['topics'] === []) {
    // nothing to show
    return;
}
?>

<style>

.page-section.news-intel-panel {
    padding: 4rem 0;
}



.news-intel-panel .intel-card {
    border-radius: 1rem;
}

.news-intel-panel .intel-chip {
    transition: transform 0.12s ease, box-shadow 0.12s ease;
}

.news-intel-panel .intel-chip:hover {
    /*
    transform: translateY(-1px);
    box-shadow: 0 0.35rem 0.9rem rgba(0,0,0,0.12);
    */
}

.news-intel-panel .intel-article-list .intel-article-item a {
    /* font-weight: 500; */
}

.news-intel-panel .intel-article-list .intel-article-item a.headline-link,
.sn-card-active-headlines a.headline-link {
    color: black;
    font-weight: 700;
}

.news-intel-panel .intel-article-list .intel-article-item a.headline-link:hover,
.sn-card-active-headlines a.headline-link:hover {
    color: #2cae86;
}

.source-slug {
    font-weight: 500;
}


.news-intel-panel .sentiment-emoji {
    font-size: 0.9rem;
    vertical-align: -1px;
}

.news-intel-panel .intel-article-item {
    line-height: 1.35;
}

.news-intel-panel .nlp-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.news-intel-panel .nlp-chip {
    display: inline-block;
    padding: 4px 6px 0px 6px;
    border-radius: 999px;
    font-size: 11px;
    background: rgba(0, 0, 0, 0.04);
    color: #555;
}

.news-intel-panel .nlp-chip-entity {
    border: 1px solid rgba(0, 0, 0, 0.12);
    font-weight: 500;
}

.news-intel-panel .nlp-chip-topic {
    background: rgba(0, 200, 140, 0.10);
    color: #006d50;
}

.news-intel-panel .nlp-emotion-line {
    font-size: 11px;
}

/* Base: stacked on narrow screens */
.news-intel-panel .intel-article-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem 1rem; /* row / col spacing */
    padding-left: 0;
    margin-bottom: 0;
}

.news-intel-panel .intel-article-list .intel-article-item {
    list-style: none;
}

/* On wider screens (e.g. lg and up), go 2 columns */
@media (min-width: 992px) {
    .news-intel-panel .intel-article-list {
        grid-template-columns: 1fr 1fr;
    }
}



.btn-xs, .btn-group-xs > .btn {
    padding: 0.2rem 0.45rem;
    font-size: 0.87rem;
}
a.btn.btn-outline-primary.btn-analyze:hover {
    background: #00bfa6;
    border: none;
}
.btn-outline-primary {
    color: black;
    border: none;
}
footer .btn {
    box-shadow: 0 0 0 2px #00bfa6 !important;
}
.btn {
    box-shadow: none !important;
}
.btn-gray-border {
    border: 2px solid #6c757d;
}



/* Tablet landscape: 2-column layout with centered wide last card */
@media (min-width: 768px) and (max-width: 1199.98px) {

  /* Force 2-column behavior */
  .news-intel-panel .intel-sections > [class*="col-"] {
    flex: 0 0 50%;
    max-width: 50%;
  }

  /* If there's an odd number of items, widen + center the last one */
  .news-intel-panel .intel-sections
    > [class*="col-"]:nth-last-child(1):nth-child(odd) {
    flex: 0 0 70%;
    max-width: 70%;
    margin-left: auto;
    margin-right: auto;
  }
}


.intel-article-meta {
    line-height: 1.2;
}

.intel-favicon {
    border-radius: 3px;
}

.intel-publisher-link:hover {
    text-decoration: underline;
}

.intel-publisher-link img {
    transition: transform 0.15s ease;
}

.intel-publisher-link:hover img {
    transform: scale(1.1);
}
</style>

<section class="page-section news-intel-panel bg-light-2">
    <div class="container-fluid">
        <div class="align-items-center text-center mb-3">
            <div>
                <h2 class="h5 mb-1">
                    🧠 News Intelligence
                </h2>
                <p class="text-muted mb-0">
                    Trending entities, places, and topics from the last 24 hours.
                </p>
            </div>
            <?php if (!empty($intel_panel['stats'])): ?>
                <div class="text-muted mt-3">
                    <div><?= (int)$intel_panel['stats']['article_count_24h'] ?> articles</div>
                    <div><?= (int)$intel_panel['stats']['unique_entities'] ?> entities · <?= (int)$intel_panel['stats']['unique_places'] ?> places · <?= (int)$intel_panel['stats']['unique_topics'] ?> topics</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="intel-sections row g-3">
            <?php
            $sections = [
                'places'   => ['title' => 'Trending Places',   'icon' => '🗺️'],
                'entities' => ['title' => 'Trending Entities', 'icon' => '👤'],
                'topics'   => ['title' => 'Trending Topics',   'icon' => '🧵'],
            ];

            foreach ($sections as $key => $meta):
                $items = $intel_panel[$key] ?? [];

                // For non-entities, if there are no items, skip the column entirely
                if ($key !== 'entities' && !$items) {
                    continue;
                }
            ?>
                <div class="col-12 col-md-6 col-xl-4 mb-3">

                <?php if ($items): ?>
                    <div class="card h-100 intel-card mt-3 mt-lg-3">
                        <div class="card-body">
                            <?php if ($key === 'places'): ?>
                                <!-- Active Headlines card goes at the top of the first column -->
                                <?php include __DIR__ . '/___active_headlines.php'; ?>
                            <?php endif; ?>

                            <div class="mb-2">
                                <div class="sn-time-marker">RECENT</div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="h6 mb-0">
                                        <?= htmlspecialchars($meta['icon'] . ' ' . $meta['title']) ?>
                                    </h3>
                                    <!-- optional right-side thing later -->
                                </div>
                            </div>
                            <div class="intel-chip-list">
                                <?php foreach ($items as $item): ?>
                                    <div class="intel-chip mb-3 pb-2 border-bottom">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <?php
                                            $entityLabel = (string)($item['label'] ?? '');
                                            $entityValue = strtolower(trim($entityLabel)); // analysis expects lowercase canonical-ish
                                            $entityValue = preg_replace('/\s+/', ' ', $entityValue); // normalize spaces

                                            // Decide analysis context based on panel key
                                            $analysisContext = ($key === 'topics') ? 'topic' : 'entity';

                                            $analysisUrl = '/analysis.php?' . http_build_query([
                                                'context' => $analysisContext,
                                                'value'   => $entityValue,
                                                'w'       => '24h',
                                            ]);
                                            ?>
                                            <div class="fw-semibold">
                                                <a href="<?= htmlspecialchars($analysisUrl) ?>" class="text-muted" data-loading
                                                title="Analyze entity: <?= htmlspecialchars($entityLabel) ?>">
                                                    <?= htmlspecialchars($entityLabel) ?> 📊
                                                </a>
                                            </div>
                                            <span class="badge rounded-pill bg-secondary-subtle text-body-secondary small">
                                                <?= (int)$item['count'] ?> articles
                                            </span>
                                        </div>
                                        <ul class="list-unstyled mb-0 medium intel-article-list">
                                            <?php
                                                // Make a local copy so we don't mutate $item unexpectedly elsewhere
                                                $articles = $item['articles'] ?? [];

                                                // Your "national publishers" list:
                                                // Choose ONE strategy:
                                                // (A) by domain (recommended — stable, matches your favicon logic)
                                                // (B) by source_slug (works too if it's consistent)

                                                // (A) Domains:
                                                $nationalDomains = [
                                                    'nytimes.com',
                                                    'washingtonpost.com',
                                                    'wsj.com',
                                                    'bloomberg.com',
                                                    'npr.org',
                                                    'bbc.com',
                                                    'theguardian.com',
                                                    'cnn.com',
                                                    'foxnews.com',
                                                    'nbcnews.com',
                                                    'cbsnews.com',
                                                    'abcnews.go.com',
                                                    'usatoday.com',
                                                    'politico.com',
                                                    'thehill.com',
                                                ];

                                                // Helper: get domain from URL
                                                $domainOf = function($url) {
                                                    if (!$url) return '';
                                                    $host = parse_url($url, PHP_URL_HOST) ?: '';
                                                    $host = preg_replace('/^www\./', '', strtolower($host));
                                                    return $host;
                                                };

                                                usort($articles, function($a, $b) use ($nationalDomains, $domainOf) {
                                                    $da = $domainOf($a['url'] ?? '');
                                                    $db = $domainOf($b['url'] ?? '');

                                                    $aNational = in_array($da, $nationalDomains, true) ? 1 : 0;
                                                    $bNational = in_array($db, $nationalDomains, true) ? 1 : 0;

                                                    // National first
                                                    if ($aNational !== $bNational) return $bNational <=> $aNational;

                                                    // Tie-breaker: newest first (best-effort)
                                                    $ta = strtotime($a['pub_date'] ?? '') ?: 0;
                                                    $tb = strtotime($b['pub_date'] ?? '') ?: 0;
                                                    return $tb <=> $ta;
                                                });
                                            ?>
                                            <?php foreach ($articles as $article): ?>
                                                <?php
                                                // Decode NLP for this article
                                                $nlp = json_decode($article['nlp'] ?? '{}', true) ?: [];

                                                // Sentiment
                                                $sentLabel = $nlp['sentiment']['label'] ?? null;
                                                $sentEmoji = nlp_sentiment_emoji($sentLabel);

                                                // Emotional reactions (Wow, Love, etc.)
                                                $emotionsRaw   = $nlp['emotional_reaction'] ?? [];
                                                $emotionDetail = is_array($emotionsRaw) ? nlp_top_emotions_detail($emotionsRaw, 2, 15.0) : [];

                                                // Topics: top 2 above your score threshold
                                                $topicsRaw  = $nlp['topics'] ?? [];
                                                $topicChips = [];
                                                if (is_array($topicsRaw)) {
                                                    arsort($topicsRaw); // highest score first
                                                    foreach ($topicsRaw as $tName => $score) {
                                                        if (count($topicChips) >= 2) break;
                                                        if ((float)$score < 0.2) continue; // keep in sync with your TOPIC_MIN_SCORE
                                                        $topicChips[] = $tName;
                                                    }
                                                }

                                                // Entities: top 2 by count
                                                $entitiesRaw  = $nlp['entities'] ?? [];
                                                $entityChips  = [];
                                                if (is_array($entitiesRaw)) {
                                                    usort($entitiesRaw, function ($a, $b) {
                                                        $ca = (int)($a['count'] ?? 0);
                                                        $cb = (int)($b['count'] ?? 0);
                                                        return $cb <=> $ca;
                                                    });
                                                    foreach ($entitiesRaw as $ent) {
                                                        if (count($entityChips) >= 2) break;
                                                        $name = is_array($ent) ? ($ent['text'] ?? $ent['name'] ?? null) : $ent;
                                                        if (!$name) continue;
                                                        $entityChips[] = $name;
                                                    }
                                                }

                                                $pubRaw = $article['pub_date'] ?? null;

                                                $pubIso = '';
                                                $pub_ts = null;

                                                if (is_numeric($pubRaw)) {
                                                    // DB stored as unix timestamp (e.g. INT)
                                                    $pub_ts = (int) $pubRaw;
                                                    $pubIso = gmdate(DATE_ATOM, $pub_ts);
                                                } elseif (is_string($pubRaw) && $pubRaw !== '') {
                                                    // DB stored as a datetime string (e.g. "2025-12-07 15:45:00")
                                                    $tmp = strtotime($pubRaw);
                                                    if ($tmp !== false) {
                                                        $pub_ts = $tmp;
                                                        $pubIso = gmdate(DATE_ATOM, $pub_ts);
                                                    }
                                                }

                                                $qs = http_build_query([
                                                    'url'      => $article['url'],
                                                    'category' => ucfirst($article['source_slug'] ?? ''),
                                                    'pub_date' => $pub_ts,
                                                    'db'       => 1,
                                                ]);

                                                // $article is your article row
                                                $badges = scroll_get_article_badges($article);

                                                $card_classes = ' scroll-history-card';

                                                if (scroll_is_high_signal_publisher($article)) {
                                                    $card_classes .= ' scroll-card-high-signal';
                                                }

                                                if (scroll_is_deep_dive($article)) {
                                                    $card_classes .= ' scroll-card-deep-dive';
                                                }

                                                ?>
                                                <li class="intel-article-item mb-2">
                                                    <a href="newsroom.php?<?= htmlspecialchars($qs) ?>"
                                                       class="text-decoration-none d-block headline-link"
                                                       data-loading>
                                                        <?php if ($sentEmoji): ?>
                                                            <span class="sentiment-emoji me-1"><?= htmlspecialchars($sentEmoji) ?></span>
                                                        <?php endif; ?>
                                                        <span class="intel-article-title">
                                                            <?= htmlspecialchars($article['title']) ?> ▶️
                                                        </span>
                                                        <?php if (!empty($article['source_slug'])): ?>
                                                            <span class="source-slug text-muted"> · <?= htmlspecialchars($article['source_slug']) ?></span>
                                                        <?php endif; ?>
                                                    </a>

                                                    <?php
                                                        $publisherDomain = '';
                                                        $faviconUrl = '';
                                                        $formattedDate = '';

                                                        if (!empty($article['url'])) {
                                                            $parsed = parse_url($article['url']);
                                                            $publisherDomain = $parsed['host'] ?? '';
                                                            $publisherDomain = preg_replace('/^www\./', '', $publisherDomain);

                                                            if ($publisherDomain) {
                                                                // Google favicon service (simple + reliable)
                                                                $faviconUrl = "https://www.google.com/s2/favicons?sz=64&domain={$publisherDomain}";
                                                            }
                                                        }

                                                        if (!empty($article['pub_date'])) {
                                                            $formattedDate = date('M j, Y', strtotime($article['pub_date']));
                                                        }
                                                    ?>

                                                    <?php if ($publisherDomain || $formattedDate): ?>
                                                        <div class="intel-article-meta text-muted small mt-1 d-flex align-items-center gap-2 flex-wrap">
                                                            
                                                            <?php if ($publisherDomain): ?>
                                                                <?php $publisherSearchUrl = "/analysis.php?context=pub&value=" . urlencode($publisherDomain) . "&w=7d"; ?>

                                                                <a href="<?= htmlspecialchars($publisherSearchUrl); ?>"
                                                                class="intel-publisher-link d-inline-flex align-items-center gap-1 text-decoration-none text-muted"
                                                                data-loading>
                                                                
                                                                    <?php if ($faviconUrl): ?>
                                                                        <img src="<?= htmlspecialchars($faviconUrl) ?>"
                                                                            alt=""
                                                                            width="14"
                                                                            height="14"
                                                                            class="intel-favicon">
                                                                    <?php endif; ?>

                                                                    <span><?= htmlspecialchars($publisherDomain) ?></span>
                                                                </a>
                                                            <?php endif; ?>

                                                            <?php if ($formattedDate): ?>
                                                                <span>· <?= htmlspecialchars($formattedDate) ?></span>
                                                            <?php endif; ?>

                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($badges)) : ?>
                                                        <div class="scroll-article-badges mt-1">
                                                            <?php foreach ($badges as $badge): ?>
                                                                <?php
                                                                    $slug = $badge['slug'] ?? '';

                                                                    // Default links (you can define these earlier in the file too)
                                                                    $highSignalSearchUrl = '/search.php?high_signal=1'; // maybe add &mode=nlp later
                                                                    $deepDiveSearchUrl   = '/search.php?mode=nlp&deep_dive=1';

                                                                    // Decide href per badge
                                                                    $badgeHref = $highSignalSearchUrl; // sensible default

                                                                    if ($slug === 'deep-dive') {
                                                                        $badgeHref = $deepDiveSearchUrl;
                                                                    } elseif ($slug === 'high-signal-publisher') {
                                                                        $badgeHref = $highSignalSearchUrl;
                                                                    }
                                                                ?>
                                                                <a class="scroll-badge scroll-badge-<?php echo htmlspecialchars($slug); ?>"
                                                                   href="<?php echo htmlspecialchars($badgeHref); ?>" title="<?php echo htmlspecialchars($badge['tooltip']); ?>" data-loading>
                                                                    <?php echo htmlspecialchars($badge['label']); ?>
                                                                </a>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($entityChips || $topicChips): ?>
                                                        <div class="nlp-chip-row mt-1">

                                                            <?php foreach ($entityChips as $name): ?>
                                                                <?php
                                                                    $clean = trim(strtolower($name)); // entities do NOT include #
                                                                    $href  = sn_analysis_url($clean, '24h', 'entity');
                                                                ?>
                                                                <a class="nlp-chip nlp-chip-entity"
                                                                href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" data-loading>
                                                                    #<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                                                                </a>
                                                            <?php endforeach; ?>

                                                            <?php foreach ($topicChips as $topicName): ?>
                                                                <?php
                                                                    $clean = trim(strtolower($topicName));
                                                                    $href  = sn_analysis_url($clean, '24h', 'topic');
                                                                ?>
                                                                <a class="nlp-chip nlp-chip-topic"
                                                                href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" data-loading>
                                                                    <?= htmlspecialchars($topicName, ENT_QUOTES, 'UTF-8') ?>
                                                                </a>
                                                            <?php endforeach; ?>

                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($emotionDetail): ?>
                                                        <div class="nlp-emotion-line medium text-muted mt-1">
                                                            Emotions:
                                                            <?php foreach ($emotionDetail as $idx => $emo): ?>
                                                                <?= $idx > 0 ? ' · ' : '' ?>
                                                                <?= htmlspecialchars($emo['name']) ?> <?= round($emo['pct']) ?>%
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="btn-group btn-group-xs mt-2" role="group">
                                                        <a
                                                            href="<?php echo htmlspecialchars($article['url'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            class="btn btn-outline-secondary"
                                                            target="_blank"
                                                            rel="noopener"
                                                            data-article-url="<?php echo htmlspecialchars($article['url'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-article-title="<?= htmlspecialchars($article['title']) ?>"
                                                            data-article-source="<?= htmlspecialchars($article['source_slug']) ?>"
                                                            data-article-image="<?= htmlspecialchars($article['image_url']) ?>"
                                                            data-article-pub-date="<?= htmlspecialchars($pubIso) ?>"
                                                            data-article-kind="external"
                                                        >
                                                            Read story
                                                        </a>
                                                        <a
                                                            href="newsroom.php?<?= htmlspecialchars($qs) ?>"
                                                            class="btn btn-green btn-gray-border"
                                                            data-loading
                                                        >
                                                            Analyze
                                                        </a>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
