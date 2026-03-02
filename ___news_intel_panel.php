<?php
// ___news_intel_panel.php
// News Intelligence Panel: trending entities / places / topics for last 24h

require_once 'config_interest.php';

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
                                            <?php foreach ($item['articles'] as $article): ?>
                                                <?= scroll_render_article_intel_item($article, ['w' => '24h', 'db' => 1]); ?>
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
