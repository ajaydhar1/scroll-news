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

    foreach ($rows as $row) {
        $pubDate = $row['pub_date'];

        // helper: decode JSON as array safely
        $entities_object = json_decode($row['nlp']   ?? '[]', true)['entities'] ?: [];
        $topics_array = json_decode($row['topics'] ?? '{}', true) ?: [];

        // Normalize entities and places (can be array of strings or array of objects)
        foreach ($entities_object as $ent) {
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
                $placeCounts[$key] = ($placeCounts[$key] ?? 0) + 1;
                $placeArticles[$key][] = $row;
                $uniquePlaceKeys[$key] = true;
            }
        }

        // Normalize topics: $topics is now [ 'Health' => '0.07', 'Government' => '0.63', ... ]
        foreach ($topics_array as $name => $score) {
            if (!$name) continue;

            $key = mb_strtolower(trim($name));

            // If you want simple counts (each topic once per article):
            $topicCounts[$key] = ($topicCounts[$key] ?? 0) + 1;

            // If you prefer to weight by score instead, use this instead:
            // $topicCounts[$key] = ($topicCounts[$key] ?? 0) + (float)$score;

            $topicArticles[$key][] = $row;
            $uniqueTopicKeys[$key] = true;
        }
    }

    // Helper to build “trending” list from counts + article map
    $buildTrending = function (array $counts, array $articleMap, int $maxItems = 8, int $minCount = 2, int $maxArticlesPerItem = 4) {
        if (!$counts) return [];

        arsort($counts); // highest first

        $trending = [];

        foreach ($counts as $key => $count) {
            if ($count < $minCount) {
                // stop once we hit items that only show up once
                continue;
            }

            $articles = $articleMap[$key] ?? [];
            if (!$articles) continue;

            // Sort articles by pub_date desc
            usort($articles, function ($a, $b) {
                return strcmp($b['pub_date'], $a['pub_date']);
            });

            $trending[] = [
                'label'    => ucwords($key),
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
    $intel_panel['places']   = $buildTrending($placeCounts,  $placeArticles,  8, 2, 4);
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
                                                <a href="<?= htmlspecialchars($article['url']) ?>"
                                                   class="text-decoration-none">
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
