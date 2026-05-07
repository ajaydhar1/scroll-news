<?php
// search.php — keyword search across rss_items (+ feeds, + articles)
//
// Assumes:
//   - tables: rss_items, feeds, articles
//   - rss_items.feed_id -> feeds.id
//   - articles has a URL column matching rss_items.link (adjust if needed)

define('BASE_PATH', __DIR__);

require_once BASE_PATH . "/core/config/interest.php";
require_once BASE_PATH . "/core/___modules.php";

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
        if ($hasFilters) {
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
        } else {
            $results = [];
        }
    } catch (Throwable $e) {
        $errorMsg = 'There was a problem running your search.';
        $results  = [];
        // (Optional) error_log($e->getMessage());
    }
}

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

        <!-- Favicon-->
        <link rel="icon" type="image/png" href="/assets/img/play-green.png" />
        <link rel="canonical" href="https://scrollnews.ai/search.php">

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
                        <h2 class="section-heading text-uppercase">Search headlines</h2>
                        <h3 class="section-subheading" style="margin-bottom: 1.5rem;">
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

                        <div class="small text-muted mt-2">
                            Search across recent items from all feeds.
                        </div>
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
                        <div class="col-md-8 mx-auto">
                            <p class="text-muted">
                                Type a keyword above to search headlines from your feeds.
                            </p>

                            <?php
                                // Curated starter searches (ship-now)
                                $searchChips = [
                                    'AI regulation', 'Apple', 'Microsoft', 'Elon Musk',
                                    'OpenAI', 'NVIDIA', 'Ukraine', 'Gaza', 'Supreme Court',
                                    'Interest rates', 'Inflation', 'Climate', 'Taylor Swift',
                                    'NFL', 'NBA',
                                ];

                                // build classic search urls
                                $buildSearchUrl = function(string $q): string {
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

                            <div class="sn-search-chips mt-2">
                                <?php foreach ($searchChips as $chip): ?>
                                    <a class="sn-chip" href="<?= htmlspecialchars($buildSearchUrl($chip), ENT_QUOTES, 'UTF-8') ?>" data-sn-loading>
                                        <?= htmlspecialchars($chip, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>

                            <div class="small text-muted mt-2">
                                Tip: try names, companies, locations, or big topics.
                            </div>
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

                            <?php if ($hasFilters && !empty($filterChips)): ?>
                                <p class="small text-muted mb-1">
                                    Active filters:
                                    <?php echo htmlspecialchars(implode(' · ', $filterChips), ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            <?php endif; ?>


                            <h2 class="h6 mb-3">
                                <?php if ($q !== ''): ?>
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
        <script src="/assets/js/sn-mini-player-yt.js?v=<?= filemtime(BASE_PATH . '/assets/js/sn-mini-player-yt.js') ?>" defer></script>
        <script src="/assets/js/pages/search.js?v=<?= filemtime(BASE_PATH . '/assets/js/pages/search.js') ?>" defer></script>

    </body>
</html>
