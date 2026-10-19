<?php
// Production-ish settings
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('log_errors', '1');

define('BASE_PATH', __DIR__);

require_once BASE_PATH . "/auth/includes/auth_bootstrap.php";

require_once BASE_PATH . "/core/config/interest.php";
require_once BASE_PATH . "/core/___modules.php";

require_once BASE_PATH . '/core/newsroom/___request_resolution_layer.php';
require_once BASE_PATH . '/core/newsroom/___newsroom_meta.php';
require_once BASE_PATH . '/core/newsroom/___newsroom_bootstrap.php';

$hasContext = isset($_GET['context']) && trim($_GET['context']) !== '';
$isTrailPlayer = ($_GET['context'] ?? '') === 'trail-player';
$theme_experiment_enabled = !$isTrailPlayer;

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

// Shared header/history values (used by the masthead, Go to Story link and #history-meta)
$category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
$hasCategoryLink = $category !== '' && isset($rss_feeds) && is_array($rss_feeds) && array_key_exists($category, $rss_feeds);

$historySource = '';
if ($category !== '' && strtolower($category) !== 'db') {
    $historySource = strtolower($category);
} elseif (!empty($source_slug)) {
    $historySource = strtolower($source_slug);
} elseif (!empty($url)) {
    $host = parse_url($url, PHP_URL_HOST) ?: '';
    if ($host) {
        $historySource = preg_replace('/^www\./i', '', strtolower($host));
    }
}

$pubDateParam = $_GET['pub_date'] ?? null;
$pubIso = '';
$pub_ts = null;
if (is_numeric($pubDateParam)) {
    $pub_ts = (int) $pubDateParam;
    if ($pub_ts > 0) {
        $pubIso = gmdate(DATE_ATOM, $pub_ts);
    }
} elseif (is_string($pubDateParam) && $pubDateParam !== '') {
    $tmp = strtotime($pubDateParam);
    if ($tmp !== false) {
        $pub_ts = $tmp;
        $pubIso = gmdate(DATE_ATOM, $pub_ts);
    }
}

$newsroomDescription = trim(html_entity_decode(strip_tags((string) $des), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
$newsroomDescription = preg_replace('/\s+/u', ' ', $newsroomDescription) ?? $newsroomDescription;
if (strcasecmp($newsroomDescription, 'No description') === 0) {
    $newsroomDescription = '';
}

$badges = [];
if ($fromDb && is_array($article)) {
    $badges = scroll_get_article_badges($article);
}

sn_perf_phase('render');
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

    <link rel="preload" as="image" href="<?= $img ?>">

    <!-- jQuery min-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" defer></script>

    <!-- Font Awesome icons (free version)-->
    <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous" defer></script>

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
        <!-- Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

        <link id="mindpour-theme" href="/assets/css/mindpour.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/mindpour.css'); ?>" rel="stylesheet" />
        <link id="dark-theme" href="/assets/css/dark.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/dark.css'); ?>" rel="stylesheet" />
        <link id="dark-typography-theme" href="/assets/css/dark-typography.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/dark-typography.css'); ?>" rel="stylesheet" />
        <!-- LIGHT SaaS: remove these two links and the sn-light-saas body class to revert -->
        <link href="/assets/css/light-saas.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/light-saas.css'); ?>" rel="stylesheet" />
        <link href="/assets/css/pages/nlp-dashboard-light-saas.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/nlp-dashboard-light-saas.css'); ?>" rel="stylesheet" />

        <script src="/assets/js/dark-theme.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/dark-theme.js'); ?>"></script>
        <link href="/assets/css/pages/newsroom-reading-desk.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/newsroom-reading-desk.css'); ?>" rel="stylesheet" />
    <?php endif; ?>

    <?php if ($isTrailPlayer): ?>
        <link id="dark-theme" href="/assets/css/dark.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/dark.css'); ?>" rel="stylesheet" />
        <link id="dark-typography-theme" href="/assets/css/dark-typography.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/dark-typography.css'); ?>" rel="stylesheet" />
        <script src="/assets/js/dark-theme.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/dark-theme.js'); ?>"></script>
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

        html[data-ui-theme="dark"] body {
            background: var(--sn-canvas) !important;
        }

        .card-header {
            background-color: var(--dark);
            color: white;
        }

        html[data-ui-theme="dark"] #side-by-side-panel .hashtags-card #sm-tags {
            color: var(--sn-muted);
        }

        /* Embedded dark mode: dark.css leaves Bootstrap's #212529 body text on the charcoal cards */
        html[data-ui-theme="dark"] .page > .text-center h2,
        html[data-ui-theme="dark"] #side-by-side-panel .card-body {
            color: #e6e9ed;
        }

        html[data-ui-theme="dark"] #side-by-side-panel #narrative-frames ul ul li,
        html[data-ui-theme="dark"] #side-by-side-panel .sn-emo-val {
            color: var(--sn-muted);
        }

        /* Embedded light mode: light-saas.css isn't loaded here, so use its accessible link teal (--sn-l-link) */
        html:not([data-ui-theme="dark"]) #side-by-side-panel .hashtag a,
        html:not([data-ui-theme="dark"]) #side-by-side-panel a.js-wiki {
            color: #067466;
        }

        html:not([data-ui-theme="dark"]) #side-by-side-panel .hashtag a:hover,
        html:not([data-ui-theme="dark"]) #side-by-side-panel a.js-wiki:hover {
            color: #04554b;
        }

        <?php endif; ?>
    </style>

</head>

<body id="page-top"<?= $isTrailPlayer ? '' : ' class="sn-light-saas newsroom-page"' ?>>

    <div class="page">

        <!-- Top nav-->
        <?php require_once BASE_PATH . '/views/partials/___topnav_product.php'; ?>

        <?php if ($isTrailPlayer): ?>
            <div class="text-center my-3">
                <h2>Article analysis</h2>
            </div>
        <?php else: ?>
            <div class="newsroom-pagebar">
                <div class="container-fluid newsroom-shell">
                    <div class="newsroom-crumbs" aria-label="Newsroom">
                        <span class="newsroom-crumb-root">Newsroom</span>
                        <?php if ($hasCategoryLink): ?>
                            <span class="newsroom-crumb-sep" aria-hidden="true">›</span>
                            <a href="" class="category-link newsroom-category-tag" data-category="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>" data-category-url="<?= htmlspecialchars((string) $rss_feeds[$category], ENT_QUOTES, 'UTF-8') ?>">#<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="newsroom-header-actions" role="group" aria-label="Newsroom actions">
                        <a data-step="1" data-intro="Welcome to the Scroll News newsroom! Here we provide analytics for the latest news stories. Click this play button to stumble through trending articles." class="sn-product-link sn-product-stumble newsroom-stumble-action" href="/newsroom.php" onclick="trackStumbleClick('newsroom_header')" aria-label="Stumble to a random article" data-loading>
                            <i class="fas fa-play" aria-hidden="true"></i>
                            <span>Stumble</span>
                        </a>
                        <button class="sn-product-link newsroom-guide-action" type="button" onclick="ScrollNews.startIntro();"><i class="fa fa-play-circle" aria-hidden="true"></i><span>Guide</span></button>
                        <a class="sn-product-link newsroom-story-action" target="_blank" rel="noopener" href="<?= htmlspecialchars((string) $url, ENT_QUOTES, 'UTF-8') ?>"
                            data-article-url="<?= htmlspecialchars($url) ?>"
                            data-article-title="<?= htmlspecialchars($title) ?>"
                            data-article-source="<?= htmlspecialchars($historySource) ?>"
                            data-article-image="<?= htmlspecialchars($img ?? '') ?>"
                            data-article-pub-date="<?= htmlspecialchars($pubIso) ?>"
                            data-article-kind="external"><span>Go to Story</span><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

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

        <?php if (!$isTrailPlayer): ?>
            <section class="newsroom-story" aria-labelledby="newsroom-headline">
                <div class="container-fluid newsroom-shell">
                    <div class="newsroom-story-card<?= $img === '' ? ' newsroom-story-card--no-media' : '' ?>">
                        <div class="newsroom-story-copy">
                            <div class="newsroom-story-meta">
                                <a href="<?= htmlspecialchars(sn_publisher_profile_url((string) $domain) ?: '#', ENT_QUOTES, 'UTF-8') ?>" class="newsroom-story-publisher" data-loading><?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') ?></a>
                                <?php if (isset($_GET['pub_date'])): ?>
                                    <span class="newsroom-story-dot" aria-hidden="true"></span>
                                    <span class="newsroom-story-date"><?= format_news_date($_GET['pub_date']) ?></span>
                                <?php endif; ?>
                            </div>
                            <h1 class="newsroom-story-title" id="newsroom-headline"><?= htmlspecialchars($title) ?></h1>
                            <?php if ($newsroomDescription !== ''): ?>
                                <p class="newsroom-article-description"><?= htmlspecialchars($newsroomDescription, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <?php if (!empty($badges)) : ?>
                                <div class="scroll-article-badges newsroom-story-badges">
                                    <?php foreach ($badges as $badge): ?>
                                        <?php
                                        $slug = $badge['slug'] ?? '';
                                        $badgeHref = ($slug === 'deep-dive') ? '/search.php?mode=nlp&deep_dive=1' : '/search.php?high_signal=1';
                                        ?>
                                        <a class="scroll-badge scroll-badge-<?= htmlspecialchars($slug) ?>"
                                            href="<?= htmlspecialchars($badgeHref) ?>" title="<?= htmlspecialchars($badge['tooltip']) ?>" data-loading>
                                            <?= htmlspecialchars($badge['label']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <span class="link newsroom-story-url"><?= htmlspecialchars((string) $url) ?></span>
                        </div>
                        <?php if ($img !== ''): ?>
                            <div class="newsroom-story-media">
                                <img id="shot" src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" alt="" data-fallback-src="/assets/img/news-placeholder.jpg">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <div id="side-by-side-panel"> <?php // class="mb-4" style="min-height:480px;" 
                                        ?>
            <div class="container-fluid<?= $isTrailPlayer ? '' : ' newsroom-shell newsroom-analysis-shell' ?>"<?= $isTrailPlayer ? ' style="padding-top: 30px;"' : '' ?>>
                <?php // Trail player keeps the original (space-containing) id so its existing CSS behaviour is unchanged ?>
                <div id="<?= $isTrailPlayer ? 'panel-inner-row newsroom-layout' : 'panel-inner-row' ?>" class="row<?= $isTrailPlayer ? '' : ' newsroom-layout' ?>"> <?php //  style="height: 95vh;" 
                                                                        ?>
                    <!-- NLP Dashboard Panel -->
                    <?php if ($isTrailPlayer): ?>
                    <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-12 panel newsroom-main" style="overflow-y: auto;">

                        <!-- Masthead-->
                        <header class="masthead" style="background-image: url(<?php echo $img; ?>)">
                            <div class="container cover-img py-5">
                                <?php if (array_key_exists($_GET['category'], $rss_feeds)): ?>
                                    <div class="mb-2" style="font-size: 1.25rem;"><strong><a href="" class="category-link" data-category="<?= $_GET['category'] ?>" data-category-url="<?= $rss_feeds[$_GET['category']] ?>">#<?= $_GET['category'] ?></a></strong></div>
                                <?php endif; ?>
                                <div class="masthead-subheading mb-1"><a href="<?= htmlspecialchars(sn_publisher_profile_url((string) $domain) ?: '#', ENT_QUOTES, 'UTF-8') ?>" class="bright-link-hover" data-loading><?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') ?></a></div>
                                <div class="mb-2 text-muted" style="font-size: 1.25rem;"><strong><?php if (isset($_GET['pub_date'])) {
                                                                                                        echo format_news_date($_GET["pub_date"]);
                                                                                                    } ?></strong></div>
                                <div class="masthead-heading text-uppercase"><?php echo htmlspecialchars($title); ?></div>
                                <?php
                                $newsroomDescription = trim(html_entity_decode(strip_tags((string)$des), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                                $newsroomDescription = preg_replace('/\s+/u', ' ', $newsroomDescription) ?? $newsroomDescription;
                                if (strcasecmp($newsroomDescription, 'No description') === 0) {
                                    $newsroomDescription = '';
                                }
                                ?>
                                <?php if ($newsroomDescription !== ''): ?>
                                    <p class="newsroom-article-description"><?= htmlspecialchars($newsroomDescription, ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
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
                                    <?php if (!$isTrailPlayer): ?>
                                    <a class="btn btn-outline-secondary btn-lg btn-rectangle js-scroll-trigger d-block d-md-inline-block btn-width-mobile-75 w-md-auto mx-auto mb-3" target='_blank' href="<?php echo $url; ?>" style="color: white; border-color: transparent;"
                                        data-article-url="<?= htmlspecialchars($url) ?>"
                                        data-article-title="<?= htmlspecialchars($title) ?>"
                                        data-article-source="<?= htmlspecialchars($historySource) ?>"
                                        data-article-image="<?= htmlspecialchars($img ?? '') ?>"
                                        data-article-pub-date="<?= htmlspecialchars($pubIso) ?>"
                                        data-article-kind="external">Go to Story</a>
                                    <?php endif; ?>
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

                    <?php endif; ?>

                    <!-- Article Image Panel -->
                    <div class="<?= $isTrailPlayer ? 'col-xxl-6 col-xl-6 col-lg-6 col-md-12' : 'col-12' ?> panel newsroom-sidebar"<?= $isTrailPlayer ? ' style="height: 100%; overflow-y: auto;"' : '' ?>> <?php //background-color: #fcfcfc; 
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

    <script type="text/javascript" src="/assets/js/intro.js" defer></script>
    <script type="text/javascript" src="/assets/js/jquery.fancybox.min.js" defer></script>

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
                            ], JSON_UNESCAPED_SLASHES) ?>;
    </script>

    <script src="/assets/js/pages/newsroom.js?v=<?= filemtime(BASE_PATH . '/assets/js/pages/newsroom.js') ?>" defer></script>

</body>

</html>