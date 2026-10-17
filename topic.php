<?php
// topic.php — editorial topic page, driven by core/topics/topics_config.php
//
// /topic.php?topic=iran[&page=2&w=720]
//   topic : slug from the topics config (unknown slugs return the 404 page)
//   page  : 1..SN_TOPIC_MAX_PAGES
//   w     : recency window in hours; only values on the topic's widening ladder are honoured

define('BASE_PATH', __DIR__);
$theme_experiment_enabled = true;
$product_nav_stylesheet_in_head = true;

require_once BASE_PATH . '/auth/includes/auth_bootstrap.php';
require_once BASE_PATH . '/core/___modules.php';
require_once BASE_PATH . '/core/topics/___topics.php';

$topicParam = $_GET['topic'] ?? '';
$topic = is_string($topicParam) ? sn_topic_get($topicParam) : null;

if (!$topic) {
    http_response_code(404);
    require BASE_PATH . '/404.php';
    exit;
}

$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => SN_TOPIC_MAX_PAGES]]);
$page = $page === false ? 1 : (int) $page;

$requestedWindow = filter_var($_GET['w'] ?? null, FILTER_VALIDATE_INT);
$windowHours = ($requestedWindow !== false && $requestedWindow !== null && in_array($requestedWindow, sn_topic_window_ladder($topic), true))
    ? (int) $requestedWindow
    : null;

$topicUrl = 'https://scrollnews.ai' . sn_topic_url($topic['slug']);
$pageTitle = $topic['name'] . ' news – Scroll News';
$pageDescription = 'Recent ' . $topic['name'] . ' coverage from across Scroll News feeds, with links to read each story or analyze it in depth.';
$accentStyle = $topic['accent'] ? ' style="--topic-accent: ' . htmlspecialchars($topic['accent'], ENT_QUOTES, 'UTF-8') . '"' : '';

// Rendered page body is cached briefly so warm requests need no database connection.
$cacheKey = 'topic_' . $topic['slug'] . '_p' . $page . '_w' . ($windowHours ?? 0) . '_' . $CACHE_VER;
$renderFailed = false;

// ?nocache=1 (same flag as the homepage) renders fresh data for this request only. Restricted to
// loopback so it can't be used to bypass the cache and hit the database from the public internet.
$bust = ($_GET['nocache'] ?? '') === '1'
    && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
if ($bust) {
    header('Cache-Control: no-store');
}

sn_perf_phase('render');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>" />
    <meta name="author" content="Scroll News" />
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <?php if ($page > 1 || $bust): ?>
        <meta name="robots" content="noindex,follow">
    <?php endif; ?>
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />
    <link rel="canonical" href="<?= htmlspecialchars($topicUrl, ENT_QUOTES, 'UTF-8') ?>">

    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?= htmlspecialchars($topicUrl, ENT_QUOTES, 'UTF-8') ?>" />
    <meta property="og:title" content="<?= htmlspecialchars($topic['name'], ENT_QUOTES, 'UTF-8') ?> news on Scroll News" />
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>" />

    <!-- jQuery min-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" defer></script>

    <!-- Font Awesome icons (free version)-->
    <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous" defer></script>

    <!-- Google fonts-->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Montserrat:wght@400;700&family=Open+Sans:wght@400&family=Poppins:wght@600&family=Roboto+Slab:wght@100;300;400;700&display=swap" rel="stylesheet" />

    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
    <link id="mindpour-theme" href="/assets/css/mindpour.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/mindpour.css'); ?>" rel="stylesheet" />
    <link id="dark-theme" href="/assets/css/dark.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/dark.css'); ?>" rel="stylesheet" />
    <link id="dark-typography-theme" href="/assets/css/dark-typography.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/dark-typography.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/light-saas.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/light-saas.css'); ?>" rel="stylesheet" />

    <script src="/assets/js/dark-theme.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/dark-theme.js'); ?>"></script>

    <link href="/assets/css/pages/topic.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/topic.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/product-nav.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/product-nav.css'); ?>" rel="stylesheet" />
</head>

<body id="page-top" class="sn-light-saas topic-page">

    <!-- Top nav-->
    <?php require_once BASE_PATH . '/views/partials/___topnav_product.php'; ?>

    <main class="sn-topic-page" data-density="<?= htmlspecialchars($topic['density'], ENT_QUOTES, 'UTF-8') ?>"<?= $accentStyle ?>>
        <div class="container sn-topic-container">
            <?php
            $renderTopic = function () use ($topic, $page, $windowHours, &$renderFailed) {
                $pdo = _pdo_or_null();
                $data = $pdo
                    ? sn_topic_fetch($pdo, $topic, $page, $windowHours)
                    : ['items' => [], 'total' => 0, 'window_hours' => $topic['range_hours'], 'requested_hours' => $topic['range_hours'],
                       'widened' => false, 'page' => $page, 'has_more' => false, 'error' => true];

                $renderFailed = $data['error'];
                $plan = sn_topic_plan_layout($data['items'], $topic, $page);
                require BASE_PATH . '/views/topics/___topic_layout_compact_editorial.php';
            };

            if ($bust) {
                // Fresh render only: the cached fragment is neither read, written nor removed.
                $renderTopic();
            } else {
                fragment_cache_swr($cacheKey, 300, 0, $renderTopic);

                // Never keep a failure page in the cache
                if ($renderFailed) {
                    @unlink(_fragment_cache_path($cacheKey));
                }
            }
            ?>
        </div>
    </main>

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

</body>

</html>
