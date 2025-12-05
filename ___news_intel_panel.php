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
          AND LOWER(source_slug) != 'sports'
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

    $intel_panel['entities'] = $buildTrending($entityCounts, $entityArticles, 8, 2, 2);
    $intel_panel['places']   = $buildTrending($placeCounts,  $placeArticles,  8, 2, 2, $placeLabelMap);
    $intel_panel['topics']   = $buildTrending($topicCounts,  $topicArticles,  4, 2, 2);


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
    padding: 2px 6px;
    border-radius: 999px;
    font-size: 11px;
    background: rgba(0, 0, 0, 0.04);
    color: #555;
    white-space: nowrap;
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


</style>

<section class="page-section news-intel-panel bg-light">
    <div class="container-fluid">
        <div class="align-items-center mb-3">
            <div>
                <h2 class="h5 mb-1">
                    🧠 News Intelligence
                </h2>
                <p class="text-muted small mb-0">
                    Trending entities, places, and topics from the last 24 hours.
                </p>
            </div>
            <?php if (!empty($intel_panel['stats'])): ?>
                <div class="small text-muted mt-3">
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

                                                // Build newsroom link with unix timestamp
                                                $pub_ts = !empty($article['pub_date']) ? strtotime($article['pub_date']) : null;
                                                $qs = http_build_query([
                                                    'url'      => $article['url'],
                                                    'category' => ucfirst($article['source_slug'] ?? ''),
                                                    'pub_date' => $pub_ts,
                                                    'db'       => 1,
                                                ]);
                                                ?>
                                                <li class="intel-article-item mb-2">
                                                    <a href="newsroom.php?<?= htmlspecialchars($qs) ?>"
                                                       class="text-decoration-none d-block">
                                                        <?php if ($sentEmoji): ?>
                                                            <span class="sentiment-emoji me-1"><?= htmlspecialchars($sentEmoji) ?></span>
                                                        <?php endif; ?>
                                                        <span class="intel-article-title">
                                                            <?= htmlspecialchars($article['title']) ?>
                                                        </span>
                                                        <?php if (!empty($article['source_slug'])): ?>
                                                            <span class="text-muted"> · <?= htmlspecialchars($article['source_slug']) ?></span>
                                                        <?php endif; ?>
                                                    </a>

                                                    <?php if ($entityChips || $topicChips): ?>
                                                        <div class="nlp-chip-row mt-1">
                                                            <?php foreach ($entityChips as $name): ?>
                                                                <span class="nlp-chip nlp-chip-entity">
                                                                    #<?= htmlspecialchars($name) ?>
                                                                </span>
                                                            <?php endforeach; ?>

                                                            <?php foreach ($topicChips as $topicName): ?>
                                                                <span class="nlp-chip nlp-chip-topic">
                                                                    <?= htmlspecialchars($topicName) ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($emotionDetail): ?>
                                                        <div class="nlp-emotion-line small text-muted mt-1">
                                                            Emotions:
                                                            <?php foreach ($emotionDetail as $idx => $emo): ?>
                                                                <?= $idx > 0 ? ' · ' : '' ?>
                                                                <?= htmlspecialchars($emo['name']) ?> <?= round($emo['pct']) ?>%
                                                            <?php endforeach; ?>
                                                        </div>
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
    </div>
</section>
