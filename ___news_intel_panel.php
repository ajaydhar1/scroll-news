<?php
// ___news_intel_panel.php
// News Intelligence Panel: trending entities / places / topics for last 24h

if (!function_exists('_pdo_or_null')) {
    require_once __DIR__ . '/___modules.php';
}

$intel_panel = [
    'stats'    => [],
    'entities' => [],
    'places'   => [],
    'topics'   => [],
];

try {
    $db = _pdo_or_null();
    if (!$db) {
        throw new Exception("DB handle not available");
    }

    // Compute cutoff for last 24h in PHP so it works on both SQLite + Postgres
    $cutoff = (new DateTimeImmutable('-24 hours'))->format('Y-m-d H:i:s');

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

    $placeCounts   = [];
    $placeArticles  = [];

    $topicCounts   = [];
    $topicArticles  = [];

    $uniqueEntityKeys = [];
    $uniquePlaceKeys  = [];
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


    foreach ($rows as $row) {
        $pubDate = $row['pub_date'];

        // helper: decode JSON as array safely
        $nlp = json_decode($row['nlp'] ?? '{}', true) ?: [];
        $entities_object = $nlp['entities'] ?? [];
        $topics_array    = $nlp['topics']   ?? [];

        // Normalize entities and places (can be array of strings or array of objects)

        // Only include entities that appear at least 3 times
        $ENTITY_MIN_COUNT = 3;

        foreach ($entities_object as $ent) {
            // If it's not an array, it probably doesn't have a count field
            $count = isset($ent['count']) ? (int)$ent['count'] : 0;

            if ($count < $ENTITY_MIN_COUNT) {
                continue;
            }

            $name = is_array($ent) ? ($ent['text'] ?? null) : $ent;
            if (!$name) continue;

            $key = mb_strtolower(trim($name));

            $type = is_array($ent) ? ($ent['label'] ?? null) : $ent;
            if ($type == "PERSON") {
                $entityCounts[$key] = ($entityCounts[$key] ?? 0) + 1;
                $entityArticles[$key][] = $row;
                $uniqueEntityKeys[$key] = true;
            }
            else if ($type == "GPE" || $type == "LOC") {

                $norm = normalize_place_name($name);
                $key  = $norm['key'];
                $label = $norm['label'];

                $placeLabelMap = [];
                $placeCounts[$key] = ($placeCounts[$key] ?? 0) + 1;
                $placeArticles[$key][] = $row;
                $uniquePlaceKeys[$key] = true;

                // Remember a nice display label for this key
                // (later, 'us' -> 'U.S.')
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

    // Helper to build “trending” list from counts + article map
    $buildTrending = function (
        array $counts,
        array $articleMap,
        int $maxItems = 8,
        int $minCount = 2,
        int $maxArticlesPerItem = 4,
        array $labelMap = []
    ) {
        if (!$counts) return [];

        arsort($counts); // highest first

        $trending = [];

        foreach ($counts as $key => $count) {
            if ($count < $minCount) {
                continue;
            }

            $articles = $articleMap[$key] ?? [];
            if (!$articles) continue;

            usort($articles, function ($a, $b) {
                return strcmp($b['pub_date'], $a['pub_date']);
            });

            $label = $labelMap[$key] ?? ucwords($key);

            $trending[] = [
                'label'    => $label,
                'count'    => $count,
                'articles' => array_slice($articles, 0, $maxArticlesPerItem),
            ];

            if (count($trending) >= $maxItems) {
                break;
            }
        }

        return $trending;
    };

    $intel_panel['entities'] = $buildTrending($entityCounts, $entityArticles, 8, 2, 4);
    $intel_panel['places']   = $buildTrending($placeCounts,  $placeArticles,  8, 2, 4, $placeLabelMap);
    $intel_panel['topics']   = $buildTrending($topicCounts,  $topicArticles,  8, 2, 4);


    $intel_panel['stats'] = [
        'article_count_24h' => $articleCount,
        'unique_entities'   => count($uniqueEntityKeys),
        'unique_places'     => count($uniquePlaceKeys),
        'unique_topics'     => count($uniqueTopicKeys),
    ];
} catch (Throwable $e) {
    // If anything fails, just don’t render the panel
    $intel_panel = null;
}

if (empty($intel_panel) || $intel_panel['entities'] === [] && $intel_panel['places'] === [] && $intel_panel['topics'] === []) {
    // nothing to show
    return;
}
?>

<style>

.news-intel-panel .intel-card {
    border-radius: 1rem;
}

.news-intel-panel .intel-chip {
    transition: transform 0.12s ease, box-shadow 0.12s ease;
}

.news-intel-panel .intel-chip:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.35rem 0.9rem rgba(0,0,0,0.12);
}

.news-intel-panel .intel-article-list .intel-article-item a {
    font-weight: 500;
}

</style>

<section class="news-intel-panel my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h5 mb-1">
                🧠 News Intelligence
            </h2>
            <p class="text-muted small mb-0">
                Trending entities, places, and topics from the last 24 hours.
            </p>
        </div>
        <?php if (!empty($intel_panel['stats'])): ?>
            <div class="text-end small text-muted">
                <div><?= (int)$intel_panel['stats']['article_count_24h'] ?> articles</div>
                <div><?= (int)$intel_panel['stats']['unique_entities'] ?> entities · <?= (int)$intel_panel['stats']['unique_places'] ?> places · <?= (int)$intel_panel['stats']['unique_topics'] ?> topics</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="intel-sections row g-3">
        <?php
        $sections = [
            'entities' => ['title' => 'Trending Entities', 'icon' => '👤'],
            'places'   => ['title' => 'Trending Places',   'icon' => '🗺️'],
            'topics'   => ['title' => 'Trending Topics',   'icon' => '🧵'],
        ];

        foreach ($sections as $key => $meta):
            $items = $intel_panel[$key] ?? [];
            if (!$items) continue;
        ?>
            <div class="col-12 col-lg-4">
                <div class="card h-100 intel-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="h6 mb-0">
                                <?= htmlspecialchars($meta['icon'] . ' ' . $meta['title']) ?>
                            </h3>
                        </div>
                        <div class="intel-chip-list">
                            <?php foreach ($items as $item): ?>
                                <div class="intel-chip mb-3 pb-2 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="fw-semibold small">
                                            <?= htmlspecialchars($item['label']) ?>
                                        </div>
                                        <span class="badge rounded-pill bg-secondary-subtle text-body-secondary small">
                                            <?= (int)$item['count'] ?> articles
                                        </span>
                                    </div>
                                    <ul class="list-unstyled mb-0 small intel-article-list">
                                        <?php foreach ($item['articles'] as $article): ?>
                                            <li class="intel-article-item text-truncate">
                                                <?php
                                                    $pub_ts = $article['pub_date'] ? strtotime($article['pub_date']) : '';
                                                    $qs = http_build_query([
                                                        'url'      => $article['url'],
                                                        'category' => ucfirst($article['source_slug']),
                                                        'pub_date' => $pub_ts,
                                                        'db'       => 1,
                                                    ]);
                                                ?>
                                                <a href="newsroom.php?<?= htmlspecialchars($qs) ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($article['title']) ?>
                                                </a>
                                                <?php if (!empty($article['source_slug'])): ?>
                                                    <span class="text-muted"> · <?= htmlspecialchars($article['source_slug']) ?></span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
