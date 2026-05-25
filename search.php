<?php
// search.php — keyword search across rss_items (+ feeds, + articles)
//
// Assumes:
//   - tables: rss_items, feeds, articles
//   - rss_items.feed_id -> feeds.id
//   - articles has a URL column matching rss_items.link (adjust if needed)

define('BASE_PATH', __DIR__);

require_once BASE_PATH . "/auth/includes/auth_bootstrap.php";

require_once BASE_PATH . "/core/config/interest.php";
require_once BASE_PATH . "/core/___modules.php";

function save_user_search_history(PDO $pdo, int $userId, array $data): void
{
    $query = trim((string) ($data['query'] ?? ''));

    if ($query === '') {
        return;
    }

    $mode = trim((string) ($data['mode'] ?? ''));
    $range = trim((string) ($data['range'] ?? ''));

    $params = $data['params'] ?? [];

    if (!is_array($params)) {
        $params = [];
    }

    // Remove empty values
    $params = array_filter($params, static function ($value) {
        return $value !== null && $value !== '';
    });

    $sql = "
        INSERT INTO user_search_history (
            user_id,
            query,
            mode,
            range,
            params_json
        )
        VALUES (
            :user_id,
            :query,
            :mode,
            :range,
            :params_json
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_id' => $userId,
        ':query' => $query,
        ':mode' => $mode !== '' ? $mode : null,
        ':range' => $range !== '' ? $range : null,
        ':params_json' => !empty($params)
            ? json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null,
    ]);
}

function save_search_shuffle_history(PDO $pdo, int $userId, array $results, array $context): void
{
    if (empty($results)) {
        return;
    }

    $pdo->beginTransaction();

    try {
        $sessionStmt = $pdo->prepare("
            INSERT INTO shuffle_sessions (
                user_id,
                source_context,
                shuffle_type,
                query,
                seed,
                algorithm_version,
                results_count,
                filters_json,
                snapshot_json
            ) VALUES (
                :user_id,
                'search_results',
                'search_shuffle',
                :query,
                :seed,
                'v1',
                :results_count,
                :filters_json,
                :snapshot_json
            )
            RETURNING id
        ");

        $filters = $context['filters'] ?? [];
        $snapshot = $context['snapshot'] ?? [];

        $sessionStmt->execute([
            ':user_id' => $userId,
            ':query' => $context['query'] ?? null,
            ':seed' => $context['seed'] ?? null,
            ':results_count' => count($results),
            ':filters_json' => !empty($filters)
                ? json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
            ':snapshot_json' => !empty($snapshot)
                ? json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
        ]);

        $shuffleSessionId = $sessionStmt->fetchColumn();

        $itemStmt = $pdo->prepare("
            INSERT INTO shuffle_session_items (
                shuffle_session_id,
                position,
                article_id,
                url,
                title,
                source_name,
                pub_date,
                image_url
            ) VALUES (
                :shuffle_session_id,
                :position,
                :article_id,
                :url,
                :title,
                :source_name,
                :pub_date,
                :image_url
            )
        ");

        foreach ($results as $index => $row) {
            $url = $row['link'] ?? $row['url'] ?? '';
            $title = $row['title'] ?? '';

            if ($url === '' || $title === '') {
                continue;
            }

            $itemStmt->execute([
                ':shuffle_session_id' => $shuffleSessionId,
                ':position' => $index + 1,
                ':article_id' => $row['article_id'] ?? $row['id'] ?? null,
                ':url' => $url,
                ':title' => $title,
                ':source_name' => $row['source_name'] ?? $row['feed_name'] ?? $row['feed_title'] ?? null,
                ':pub_date' => $row['pub_date'] ?? $row['published_at'] ?? $row['created_at'] ?? null,
                ':image_url' => $row['image_url'] ?? $row['image'] ?? null,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Search shuffle history save failed: ' . $e->getMessage());
    }
}

$pdo        = _pdo_or_null();
$results    = [];
$errorMsg   = null;
$hasFilters = false;

// Read query params
$rawMode         = $_GET['mode']       ?? 'classic';
$mode            = ($rawMode === 'nlp') ? 'nlp' : 'classic';  // sanitize
$q               = $_GET['q'] ?? '';
$q               = trim((string)$q);  // make sure it's a string
$highSignalOnly  = !empty($_GET['high_signal']);             // <-- NEW
$deepDive        = isset($_GET['deep_dive']) && $_GET['deep_dive'] === '1';
$emotion         = $_GET['emotion']    ?? null;              // e.g. 'Sad', 'Love', 'Wow'
$sentiment       = $_GET['sentiment']  ?? null;              // e.g. 'positive', 'negative'
$range           = $_GET['range']      ?? 'all';             // '24h', 'older', 'all'

$deepDiveActive    = !empty($_GET['deep_dive']);
$highSignalActive  = !empty($_GET['high_signal']);

// ignore deep_dive when not in NLP mode
$deepDive       = ($mode === 'nlp') ? $deepDive : false;
$deepDiveActive = $deepDive;

$shuffleSessionId = $_GET['shuffle_session'] ?? '';
$shuffleSessionId = is_string($shuffleSessionId) ? trim($shuffleSessionId) : '';

$isSavedShuffleView = $shuffleSessionId !== '';

// Decide if *any* filters are active (even without q)
$hasFilters =
    ($q !== '') ||
    !empty($emotion) ||
    !empty($sentiment) ||
    ($range !== 'all') ||
    $highSignalOnly ||                                       // <-- NEW
    $deepDive;

if (!$pdo) {
    $errorMsg = "Database connection not available.";
} else {
    try {
        if ($isSavedShuffleView && $currentUser) {
            $results = load_search_shuffle_results(
                $pdo,
                (int) $currentUser['id'],
                $shuffleSessionId
            );

            $hasFilters = true;

            if (!empty($results)) {
                $q = $results[0]['query'] ?? '';
            }
        } elseif ($hasFilters) {
            // shared options
            $options = [
                'emotion'      => $emotion,
                'sentiment'    => $sentiment,
                'range'        => $range,
                'high_signal'  => $highSignalOnly,           // <-- NEW
                'deep_dive'    => $deepDive,
            ];

            if ($mode === 'nlp') {
                // NLP search on articles table
                $results = search_nlp($pdo, $q, $options);
            } else {
                // Classic search on rss_items + feeds + articles
                $results = search_classic($pdo, $q, $options);
            }

            if (!empty($_GET['shuffle'])) {
                shuffle($results);
            }

            if ($currentUser && $q !== '') {
                save_user_search_history(
                    $pdo,
                    (int) $currentUser['id'],
                    [
                        'query' => $q,
                        'mode' => $mode ?? null,
                        'range' => $range ?? null,
                        'params' => $_GET,
                    ]
                );
            }
        } else {
            $results = [];
        }
    } catch (Throwable $e) {
        $errorMsg = 'There was a problem running your search.';
        $results  = [];
        // (Optional) error_log($e->getMessage());
    }
}

$shouldSaveSearchShuffle =
    !empty($_GET['shuffle']) &&
    $currentUser &&
    !empty($results);

// From here down, render your HTML:
// - use $q to populate the search box
// - use $mode to highlight classic vs NLP toggle
// - optionally show $emotion/$sentiment/$range chips
// - loop over $results to show cards
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Search recent headlines across Scroll News feeds and jump into articles or detailed analysis." />
    <meta name="author" content="Scroll News" />
    <title>Search – Scroll News</title>

    <meta name="robots" content="noindex,follow">
    <!-- Favicon-->
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />
    <link rel="canonical" href="https://scrollnews.ai/search">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://scrollnews.ai/search.php" />
    <meta property="og:title" content="Search headlines on Scroll News" />
    <meta property="og:description" content="Search recent U.S. news headlines across Scroll News feeds, then read or analyze stories in detail." />
    <meta property="og:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-search-1200x630.png" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" content="https://scrollnews.ai/search.php" />
    <meta name="twitter:title" content="Search headlines on Scroll News" />
    <meta name="twitter:description" content="Search recent headlines and jump into Scroll News analysis or publisher stories." />
    <meta name="twitter:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-search-1200x630.png" />

    <!-- jQuery min-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <!-- Font Awesome icons (free version)-->
    <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous"></script>

    <!-- Google fonts-->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />

    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/pages/search.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/search.css'); ?>" rel="stylesheet" />

</head>

<body id="page-top" class="bg-dark">

    <div id="sn-search-loading" class="sn-loading-overlay" aria-hidden="true">
        <div class="sn-loading-spinner">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading…</span>
            </div>
        </div>
    </div>

    <!-- Top nav-->
    <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

    <!-- Main content section, reusing services section styling -->
    <section class="page-section" id="services" style="padding: 4rem 0;">
        <div class="container">
            <div class="row justify-content-center mb-4">
                <div class="col-md-8 text-center">
                    <h2 class="section-heading">🔎 Search Headlines</h2>
                    <h3 class="section-subheading text-muted" style="margin-bottom: 1.5rem;">
                        Find recent stories from Scroll News feeds, then read or analyze them.
                    </h3>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-8 mx-auto">

                    <?php
                    $snSearch = [
                        'mode' => $mode,
                        'q' => $q,
                        'range' => $range,
                        'sentiment' => $sentiment,
                        'emotion' => $emotion,
                        'deep_dive_active' => $deepDiveActive,
                        'high_signal_active' => $highSignalActive,
                    ];
                    ?>

                    <?php require_once BASE_PATH . '/views/search/___search_form.php'; ?>

                    <?php if ($currentUser): ?>
                        <div class="mb-3 d-flex flex-wrap align-items-center gap-2">

                            <span class="text-muted small mr-2">
                                Your discovery history:
                            </span>

                            <a href="/account/search-history.php"
                                class="btn btn-sm btn-outline-secondary mr-2" data-loading>
                                <i class="fas fa-search mr-1"></i>
                                Search History
                            </a>

                            <a href="/account/shuffle-history.php"
                                class="btn btn-sm btn-outline-secondary" data-loading>
                                <i class="fas fa-random mr-1"></i>
                                Shuffle History
                            </a>

                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <?php if ($errorMsg): ?>
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$hasFilters): ?>
                <div class="row">
                    <div class="col-md-8 mx-auto text-center">

                        <img src="/assets/img/stickers/radar.gif" class="radar" />

                        <?php
                        // Curated starter searches (ship-now)
                        $searchChips = [
                            'AI',
                            'Trump',
                            'Markets',
                            'AI regulation',
                            'Apple',
                            'Microsoft',
                            'Elon Musk',
                            'Democrat',
                            'Iran',
                            'Congress',
                            'California',
                            'Washington',
                            'OpenAI',
                            'NVIDIA',
                            'Ukraine',
                            'Gaza',
                            'Supreme Court',
                            'Los Angeles',
                            'Republican',
                            'Politics',
                            'Business',
                            'Policy',
                            'Interest rates',
                            'Inflation',
                            'Climate',
                            'Taylor Swift',
                            'Stock',
                            'NFL',
                            'NBA',
                            'AI startups',
                            'Cybersecurity',
                            'Robotics',
                            'SpaceX',
                            'Quantum computing',
                            'EVs',
                            'Semiconductors',
                            'China',
                            'London',
                            'Africa',
                            'UN',
                            'Middle East',
                            'Gen Z',
                            'Dating',
                            'Mental health',
                            'Remote work',
                            'Productivity',
                            'Billionaires',
                            'Housing',
                            'Education',
                            'Longevity',
                            'Climate tech',
                            'Neuroscience',
                            'Future cities',
                            'Energy',
                            'Biotechnology',
                            'Movies',
                            'Streaming',
                            'YouTube',
                            'Gaming',
                            'Celebrities',
                            'Music industry',
                            'UFC',
                            'Soccer',
                            'Formula 1',
                            'MLB'
                        ];


                        // build classic search urls
                        $buildSearchUrl = function (string $q): string {
                            $params = [
                                'q' => $q,
                                'range' => 'all',
                                'mode' => 'classic',
                                'deep_dive' => '',
                                'high_signal' => '',
                            ];
                            return '/search.php?' . http_build_query($params);
                        };
                        ?>

                        <h5 class="text-center mb-3">Discovery topic chips</h5>

                        <div class="sn-search-chips">
                            <?php foreach ($searchChips as $chip): ?>
                                <a class="sn-chip" href="<?= htmlspecialchars($buildSearchUrl($chip), ENT_QUOTES, 'UTF-8') ?>" data-sn-loading>
                                    <?= htmlspecialchars($chip, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <blockquote class="small text-muted mt-5">
                            Tip: try names, companies, locations, or big topics.
                        </blockquote>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-8 mx-auto">

                        <?php
                        // Build a simple "Active filters" string
                        $filterChips = [];

                        if ($mode === 'nlp') {
                            $filterChips[] = 'Smart (NLP)';
                        } else {
                            $filterChips[] = 'Keyword';
                        }

                        if ($range === '24h') {
                            $filterChips[] = 'Last 24 hours';
                        } elseif ($range === 'older') {
                            $filterChips[] = 'Older than 24 hours';
                        } else {
                            $filterChips[] = 'All time';
                        }

                        if (!empty($sentiment)) {
                            $filterChips[] = 'Sentiment: ' . ucfirst($sentiment);
                        }

                        if (!empty($emotion)) {
                            $filterChips[] = 'Emotion: ' . $emotion;
                        }

                        // NEW: High-signal publishers
                        if (!empty($highSignalOnly)) {
                            $filterChips[] = 'High-signal publishers';
                        }

                        // NEW: Deep dive (entity-dense)
                        if (!empty($deepDive) && $mode === 'nlp') {
                            $filterChips[] = 'Deep Dive (entity-dense)';
                            // or shorter: 'Deep Dive'
                        }
                        ?>

                        <?php if ($isSavedShuffleView): ?>

                            <p class="text-muted mb-1">
                                Saved AI-powered shuffle session
                            </p>

                        <?php elseif ($hasFilters && !empty($filterChips)): ?>

                            <p class="text-muted mb-1">
                                Active filters:
                                <?php echo htmlspecialchars(implode(' · ', $filterChips), ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                        <?php endif; ?>


                        <h2 class="h6 mb-3">

                            <?php if ($isSavedShuffleView): ?>

                                AI-powered Shuffle

                                <?php if ($q !== ''): ?>
                                    for
                                    "<span class="fw-semibold">
                                        <?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>"
                                <?php endif; ?>

                            <?php elseif ($q !== ''): ?>

                                Results for
                                "<span class="fw-semibold">
                                    <?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>
                                </span>"

                            <?php else: ?>

                                Filtered results

                            <?php endif; ?>

                            <?php if (!empty($results)): ?>
                                <span class="text-muted">
                                    · <?php echo count($results); ?> found
                                </span>
                                <?php
                                $params = $_GET;
                                $params['shuffle'] = 1;

                                $explore_url = '?' . http_build_query($params);
                                ?>

                                <a href="<?= htmlspecialchars($explore_url) ?>" class="btn btn-sm btn-info" data-sn-loading>
                                    🔀 AI-powered Shuffle
                                </a>
                            <?php endif; ?>
                        </h2>

                        <?php if (empty($results)): ?>
                            <p class="text-muted">
                                No results matched your search or filters. Try another keyword or a more general phrase, or loosen your filters.
                            </p>
                        <?php else: ?>
                            <?php foreach ($results as $row): ?>
                                <?php
                                $vm = sn_article_vm_from_row($row, [
                                    'mode' => $mode,
                                    'analysis_window' => '7d',
                                ]);

                                // Search page should preserve current filters when clicking badges (recommended)
                                $baseParams = [
                                    'q' => $q,
                                    'range' => $range,
                                    'mode' => $mode,
                                    'sentiment' => $sentiment,
                                    'emotion' => $emotion,
                                    'deep_dive' => $deepDiveActive ? '1' : '',
                                    'high_signal' => $highSignalActive ? '1' : '',
                                ];

                                $badgeHrefBuilder = function (string $slug, array $vm) use ($baseParams) {
                                    // preserve current params, then force the badge param
                                    $p = $baseParams;

                                    if ($slug === 'deep-dive') {
                                        $p['mode'] = 'nlp';
                                        $p['deep_dive'] = '1';
                                    } elseif ($slug === 'high-signal-publisher') {
                                        $p['high_signal'] = '1';
                                    }

                                    // remove empties
                                    $p = array_filter($p, fn($v) => $v !== '' && $v !== null);

                                    return '/search.php?' . http_build_query($p);
                                };
                                ?>

                                <?php
                                sn_render_article_card($vm, [
                                    'card_class' => 'sn-search-card',
                                    'analysis_window' => '7d',
                                    'badge_href_builder' => $badgeHrefBuilder,
                                    // Search page: show NLP details if present
                                    'show_hashtags' => true,
                                    'show_sentiment' => true,
                                    'show_emotions' => true,
                                    'show_badges' => true,
                                    'show_analyze' => true,
                                ]);
                                ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($shouldSaveSearchShuffle): ?>
        <script>
            window.scrollNewsShufflePayload = {
                source_context: 'search_results',
                shuffle_type: 'search_shuffle',
                query: <?php echo json_encode($q !== '' ? $q : null); ?>,
                seed: null,
                algorithm_version: 'v1',
                filters: <?php echo json_encode([
                                'mode' => $mode,
                                'range' => $range,
                                'sentiment' => $sentiment,
                                'emotion' => $emotion,
                                'high_signal' => $highSignalOnly,
                                'deep_dive' => $deepDive,
                                'params' => $_GET,
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
                snapshot: <?php echo json_encode([
                                'page' => 'search.php',
                                'total_results' => count($results),
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
                results: <?php echo json_encode(array_values(array_map(function ($row, $index) {
                                return [
                                    'position' => $index + 1,
                                    'article_id' => $row['article_id'] ?? $row['id'] ?? null,
                                    'url' => $row['link'] ?? $row['url'] ?? '',
                                    'title' => $row['title'] ?? '',
                                    'source_name' => $row['source_name'] ?? $row['feed_name'] ?? $row['feed_title'] ?? null,
                                    'pub_date' => $row['pub_date'] ?? $row['published_at'] ?? $row['created_at'] ?? null,
                                    'image_url' => $row['image_url'] ?? $row['image'] ?? null,
                                ];
                            }, $results, array_keys($results))), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
            };
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (!window.scrollNewsShufflePayload) {
                    return;
                }

                fetch('/account/api/save-shuffle-history.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(window.scrollNewsShufflePayload)
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        if (!data.success) {
                            console.warn('Shuffle history was not saved:', data.error);
                        }
                    })
                    .catch(function(error) {
                        console.warn('Shuffle history save failed:', error);
                    });
            });
        </script>
    <?php endif; ?>

    <!-- Footer-->
    <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>

    <!-- Modals-->
    <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

    <!-- Core JS (Bootstrap 4 requires jQuery first) -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" defer></script>

    <!-- Theme -->
    <script src="/assets/js/scripts.js" defer></script>

    <!-- Scroll News Features -->
    <script src="/assets/js/sn_history.js" defer></script>

    <script src="/assets/js/pages/search.js?v=<?= filemtime(BASE_PATH . '/assets/js/pages/search.js') ?>" defer></script>

</body>

</html>