<?php
// Production-ish settings
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('log_errors', '1');

define('BASE_PATH', __DIR__);

require_once BASE_PATH . "/auth/includes/auth_bootstrap.php";

require_once BASE_PATH . "/core/config/interest.php";
require_once BASE_PATH . "/core/___session_results.php";
require_once BASE_PATH . "/core/___modules.php";

require_once BASE_PATH . '/core/newsroom/___request_resolution_layer.php';
require_once BASE_PATH . '/core/newsroom/___newsroom_meta.php';
require_once BASE_PATH . '/core/newsroom/___newsroom_bootstrap.php';

$hasContext = isset($_GET['context']) && trim($_GET['context']) !== '';
$isTrailPlayer = ($_GET['context'] ?? '') === 'trail-player';

/*
echo '<pre>';
var_dump([
  'url' => $_GET['url'] ?? null,
  'category' => $_GET['category'] ?? null,
  'db' => $_GET['db'] ?? null,
  'fromDb' => $fromDb ?? null,
  'article_is_array' => isset($article) && is_array($article),
]);
echo '</pre>';
exit;
*/

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Browse the Scroll News Newsroom to see AI-analyzed U.S. headlines, scroll through article screenshots, and quickly understand what’s happening right now." />
    <meta name="author" content="Scroll News" />

    <title><?= htmlspecialchars($title) ?></title>

    <!-- Favicon-->
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <!-- Twitter card and Open Graph-->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Scroll News: [<?php echo $pub; ?> - <?= htmlspecialchars($title) ?>]" />
    <meta name="twitter:description" content="<?= htmlspecialchars($title) ?>" />
    <meta name="twitter:image" content="<?php echo $img; ?>" />

    <meta property="og:url" content="https://scrollnews.ai" />
    <meta property="og:title" content="Scroll News: [<?php echo $pub; ?> - <?= htmlspecialchars($title) ?>]" />
    <meta property="og:description" content="<?= htmlspecialchars($title) ?>" />
    <meta property="og:image" content="<?php echo $img; ?>" />
    <meta property="og:site_name" content="Scroll News" />

    <!-- Performance: Preload background -->
    <link rel="preload" as="image" href="/assets/img/mind-pour_00.jpg">

    <link rel="preload" as="image" href="<?= $img ?>">

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
    <link href="/assets/css/pages/newsroom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/newsroom.css'); ?>" rel="stylesheet" />
    <?php if (!$isTrailPlayer): ?>
        <link href="/assets/css/mindpour.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/mindpour.css'); ?>" rel="stylesheet" />
    <?php endif; ?>

    <!-- Add IntroJs styles -->
    <link href="/assets/css/introjs.css" rel="stylesheet">

    <link rel="stylesheet" href="/assets/css/jquery.fancybox.min.css" />

    <script>
        // Flip this to false to go back to your old 2-AJAX flow instantly.
        const USE_UNIFIED_NEWSROOM_API = true;
    </script>

    <style>
        <?php if ($isTrailPlayer): ?>body {
            background: linear-gradient(to bottom, #eef1f4, #e8edf1);
        }

        .card-header {
            background-color: var(--dark);
            color: white;
        }

        <?php endif; ?>
    </style>

</head>

<body id="page-top">

    <!-- Blurred overlay -->
    <div class="blur-layer"></div>

    <div class="page">

        <!-- Top nav-->
        <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

        <div class="text-center my-3">
            <h2>🧠 NLP Dashboard</h2>

            <?php if (!$isTrailPlayer): ?>
                <button class="btn btn-small btn-primary btn-rectangle" style="color: black; box-shadow: none !important;" onclick="introJs().setOptions({highlightClass: 'custom-highlight', overlayOpacity: 0.5}).start();"><i class="fa fa-play-circle" style=""></i><span>&nbsp;&nbsp;&nbsp;Guide</span></button>
            <?php endif; ?>

        </div>

        <div class="container-fluid text-center">
            <span class="link"><?= $url ?></span>
        </div>

        <?php if (isset($_GET['error']) && $_GET['error'] == '1'): ?>
            <div class="container-fluid mt-4">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <strong>Oops!</strong> We were unable to fully analyze this article. The NLP dashboard or image may be incomplete.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div id="side-by-side-panel"> <?php // class="mb-4" style="min-height:480px;" 
                                        ?>
            <div class="container-fluid" style="padding-top: 30px;">
                <div id="panel-inner-row newsroom-layout" class="row"> <?php //  style="height: 95vh;" 
                                                                        ?>
                    <!-- NLP Dashboard Panel -->
                    <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-12 panel newsroom-main" style="overflow-y: auto;">

                        <!-- Masthead-->
                        <header class="masthead" style="background-image: url(<?php echo $img; ?>)">
                            <div class="container cover-img py-5">
                                <?php if (array_key_exists($_GET['category'], $rss_feeds)): ?>
                                    <div class="mb-2" style="font-size: 1.25rem;"><strong><a href="" class="category-link" data-category="<?= $_GET['category'] ?>" data-category-url="<?= $rss_feeds[$_GET['category']] ?>">#<?= $_GET['category'] ?></a></strong></div>
                                <?php endif; ?>
                                <div class="masthead-subheading mb-1"><a href="<?= $pub_link ?>" target="_blank" class="bright-link-hover"><?php echo $pub; ?></a></div>
                                <div class="mb-2 text-muted" style="font-size: 1.25rem;"><strong><?php if (isset($_GET['pub_date'])) {
                                                                                                        echo format_news_date($_GET["pub_date"]);
                                                                                                    } ?></strong></div>
                                <div class="masthead-heading text-uppercase"><?php echo htmlspecialchars($title); ?></div>
                                <?php
                                $badges = [];
                                if ($fromDb && is_array($article)) {
                                    $badges = scroll_get_article_badges($article);
                                }

                                // TEMP DEBUG
                                // echo '<pre>';
                                // var_dump($fromDb, $article, $badges);
                                // echo '</pre>';
                                ///exit;
                                ?>
                                <div class="text-center">
                                    <?php
                                    // 1) Category from query string
                                    $category = isset($_GET['category']) ? trim($_GET['category']) : '';

                                    // 2) Normalize the source
                                    $historySource = '';

                                    // If category is present and not a tech flag like "db"
                                    if ($category !== '' && strtolower($category) !== 'db') {
                                        $historySource = strtolower($category);
                                    } elseif (!empty($source_slug)) {
                                        // fallback 2: DB/source slug if available
                                        $historySource = strtolower($source_slug);
                                    } elseif (!empty($url)) {
                                        // fallback 3: derive from article URL host
                                        $host = parse_url($url, PHP_URL_HOST) ?: '';
                                        if ($host) {
                                            $historySource = preg_replace('/^www\./i', '', strtolower($host));
                                        }
                                    }


                                    $pubDateParam = $_GET['pub_date'] ?? null;

                                    $pubIso   = '';
                                    $pub_ts   = null; // keep around if you want it for anything else later

                                    if (is_numeric($pubDateParam)) {
                                        // Expected case: unix timestamp from links
                                        $pub_ts = (int) $pubDateParam;
                                        if ($pub_ts > 0) {
                                            $pubIso = gmdate(DATE_ATOM, $pub_ts);
                                        }
                                    } elseif (is_string($pubDateParam) && $pubDateParam !== '') {
                                        // Just in case some link passes a date string instead
                                        $tmp = strtotime($pubDateParam);
                                        if ($tmp !== false) {
                                            $pub_ts = $tmp;
                                            $pubIso = gmdate(DATE_ATOM, $pub_ts);
                                        }
                                    }
                                    ?>
                                    <a class="btn btn-outline-secondary btn-lg btn-rectangle js-scroll-trigger d-block d-md-inline-block btn-width-mobile-75 w-md-auto mx-auto mb-3" target='_blank' href="<?php echo $url; ?>" style="color: white; border-color: transparent;"
                                        data-article-url="<?= htmlspecialchars($url) ?>"
                                        data-article-title="<?= htmlspecialchars($title) ?>"
                                        data-article-source="<?= htmlspecialchars($historySource) ?>"
                                        data-article-image="<?= htmlspecialchars($img ?? '') ?>"
                                        data-article-pub-date="<?= htmlspecialchars($pubIso) ?>"
                                        data-article-kind="external">Go to Story</a>
                                </div>
                                <?php if (!empty($badges)) : ?>
                                    <div class="scroll-article-badges justify-content-center mt-2">
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
                            </div>
                        </header>

                    </div>

                    <!-- Article Image Panel -->
                    <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-12 panel newsroom-sidebar" style="height: 100%; overflow-y: auto;"> <?php //background-color: #fcfcfc; 
                                                                                                                                        ?>
                        <div id="analytics" class="skeleton">

                            <?php

                            if ($fromDb) {
                                $arr = $article['nlp'];

                                if (!$arr || (!empty($arr['error']) && $arr['error'] === 'No features in text.') || empty($arr['entities'])) {
                                    $host = parse_url($url, PHP_URL_HOST) ?: 'this page';
                                    // Small, in-panel empty state card
                                    echo '
                                        <div class="card shadow-sm border-0 empty-analytics">
                                        <div class="card-body d-flex align-items-start gap-3">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <circle cx="12" cy="12" r="10" fill="#eef2ff"></circle>
                                            <path d="M12 7v6" stroke="#6366f1" stroke-width="2" stroke-linecap="round"/>
                                            <circle cx="12" cy="16" r="1.5" fill="#6366f1"/>
                                            </svg>
                                            <div>
                                            <h6 class="mb-1">Nothing to analyze</h6>
                                            <p class="mb-2 text-muted small">
                                                We couldn’t find enough readable text on <span class="fw-semibold">' . $host . '</span> to compute keywords, entities, narrative frames, or sentiment.
                                            </p>
                                            <div class="d-flex gap-2">
                                                <a class="btn btn-sm btn-outline-secondary mr-2" href="' . $url . '" target="_blank" rel="noopener">Open article</a>';

                                    //<button class="btn btn-sm btn-primary" onclick="reanalyzeAnalytics('{$url}')">Retry</button>

                                    echo '
                                            </div>
                                            <details class="mt-2 small text-muted">
                                                <summary class="pointer">Why?</summary>
                                                <ul class="mb-0 ps-3">
                                                <li>Video/live page or gallery</li>
                                                <li>Very short post or headline-only</li>
                                                <li>Paywall or script-rendered content</li>
                                                </ul>
                                            </details>
                                            </div>
                                        </div>
                                        </div>
                                        ';
                                } else {
                                    require_once BASE_PATH . "/views/newsroom/___nlp_body.php";
                                }
                            } else {
                                echo '
                                        <div id="analytics-loader" class="analytics-loader mb-4">

                                            <div class="loader-header">
                                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                                <span class="fw-semibold">Analyzing article...</span>
                                            </div>

                                            <div class="analytics-skeleton">
                                                <div class="sk-card"></div>
                                                <div class="sk-card"></div>
                                                <div class="sk-card"></div>
                                                <div class="sk-card"></div>
                                            </div>

                                        </div>

                                        <div id="analytics-results"></div>
                                    ';
                            }

                            ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Footer-->
        <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>

        <div id="sn-mini-player-mount"></div>

    </div>

    <span id="history-meta"
        class="d-none"
        data-article-url="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>"
        data-article-title="<?= htmlspecialchars($title) ?>"
        data-article-source="<?= htmlspecialchars($historySource) ?>"
        data-article-image="<?= htmlspecialchars($img ?? '') ?>"
        data-article-pub-date="<?= htmlspecialchars($pubIso) ?>"
        data-article-kind="analyze">
    </span>

    <!-- Modals-->
    <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

    <!-- Core JS (Bootstrap 4 requires jQuery first) -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" defer></script>

    <script type="text/javascript" src="/assets/js/intro.js"></script>
    <script type="text/javascript" src="/assets/js/jquery.fancybox.min.js"></script>

    <script src="/assets/js/newsroom/handlers.js" defer></script>
    <!--<script src="/assets/js/newsroom/api_legacy.js" defer></script>-->
    <!--<script src="/assets/js/newsroom/api_unified.js" defer></script>-->
    <script src="/assets/js/newsroom/init.js" defer></script>

    <?php if (!$hasContext): ?>
        <script src="/assets/js/sn_history.js?v=<?= filemtime(BASE_PATH . '/assets/js/sn_history.js') ?>" defer></script>
    <?php endif; ?>

    <script src="/assets/js/sn-mini-player-yt.js?v=<?= filemtime(BASE_PATH . '/assets/js/sn-mini-player-yt.js') ?>" defer></script>

    <?php
    $cfg_fromDb = (bool)($vm['fromDb'] ?? $fromDb ?? false);
    $cfg_url    = (string)($vm['url'] ?? $url ?? '');
    ?>
    <script>
        window.NEWSROOM = <?= json_encode([
                                'fromDb' => $cfg_fromDb,
                                'url' => $cfg_url,
                                'intro' => [
                                    'shouldRun' =>
                                    !$isTrailPlayer
                                        && (($_SESSION['resultViewed'] ?? 999) < 2)
                                        && (($_GET['siteSubmit'] ?? '') !== 'true')
                                ],
                            ], JSON_UNESCAPED_SLASHES) ?>;
    </script>

    <script src="/assets/js/pages/newsroom.js?v=<?= filemtime(BASE_PATH . '/assets/js/pages/newsroom.js') ?>" defer></script>

</body>

</html>