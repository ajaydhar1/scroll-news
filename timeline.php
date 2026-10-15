<?php
define('BASE_PATH', __DIR__);
$theme_experiment_enabled = true;

require_once BASE_PATH . '/core/___modules.php';
require_once BASE_PATH . '/auth/includes/auth_bootstrap.php';
require_once BASE_PATH . '/core/timelines/___timelines.php';
require_once BASE_PATH . '/views/timeline/___card.php';

$tl_slug = isset($_GET['story']) ? (string)$_GET['story'] : '';
$tl_story = $tl_slug !== '' ? sn_tl_get_story($tl_slug) : null;
$tl_not_found = $tl_slug !== '' && !$tl_story;
$tl_model = null;
$tl_models = [];

if ($tl_not_found) {
    http_response_code(404);
}

if ($tl_story) {
    $tl_model = sn_tl_build_model($tl_story);
    $pageTitle = $tl_story['title'] . ' Coverage Timeline | Scroll News';
    $pageDesc = $tl_story['dek'];
    $canonical = 'https://scrollnews.ai/timeline?story=' . $tl_story['slug'];
} else {
    foreach (sn_tl_all_stories() as $slug => $story) {
        $tl_models[$slug] = sn_tl_build_model($story);
    }
    $pageTitle = 'Coverage Timelines | Scroll News';
    $pageDesc = 'See how major developing stories unfolded, day by day, through real coverage from many publishers.';
    $canonical = 'https://scrollnews.ai/timeline';
}

$asset = static fn(string $p): string => $p . '?v=' . (int)@filemtime(BASE_PATH . $p);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="<?= sn_tl_e($pageDesc) ?>" />
        <meta name="author" content="Scroll News" />
        <title><?= sn_tl_e($pageTitle) ?></title>

        <link rel="icon" type="image/png" href="/assets/img/play-green.png" />
        <link rel="canonical" href="<?= sn_tl_e($canonical) ?>">

        <meta property="og:type" content="website" />
        <meta property="og:url" content="<?= sn_tl_e($canonical) ?>" />
        <meta property="og:title" content="<?= sn_tl_e($pageTitle) ?>" />
        <meta property="og:description" content="<?= sn_tl_e($pageDesc) ?>" />
        <meta name="twitter:card" content="summary" />
        <meta name="twitter:title" content="<?= sn_tl_e($pageTitle) ?>" />
        <meta name="twitter:description" content="<?= sn_tl_e($pageDesc) ?>" />

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" defer></script>
        <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous" defer></script>

        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

        <link href="<?= $asset('/assets/css/styles.css') ?>" rel="stylesheet" />
        <link href="<?= $asset('/assets/css/custom.css') ?>" rel="stylesheet" />
        <link id="mindpour-theme" href="<?= $asset('/assets/css/mindpour.css') ?>" rel="stylesheet" />
        <link id="dark-theme" href="<?= $asset('/assets/css/dark.css') ?>" rel="stylesheet" />
        <link id="dark-typography-theme" href="<?= $asset('/assets/css/dark-typography.css') ?>" rel="stylesheet" />

        <script src="<?= $asset('/assets/js/dark-theme.js') ?>"></script>

        <link href="<?= $asset('/assets/css/light-saas.css') ?>" rel="stylesheet" />
        <link href="<?= $asset('/assets/css/pages/timeline.css') ?>" rel="stylesheet" />
    </head>
    <body id="page-top" class="bg-light-3 sn-light-saas sn-tl-page">
        <div class="page">

            <?php require_once BASE_PATH . '/views/partials/___topnav_product.php'; ?>

            <main class="sn-tl-main">
                <div class="sn-tl-container">
                    <?php
                    if ($tl_story) {
                        require BASE_PATH . '/views/timeline/___story.php';
                    } else {
                        require BASE_PATH . '/views/timeline/___gallery.php';
                    }
                    ?>
                </div>
            </main>

            <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>
        </div>

        <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" defer></script>
        <script src="/assets/js/scripts.js" defer></script>
        <script src="<?= $asset('/assets/js/sn_history.js') ?>" defer></script>
        <script src="<?= $asset('/assets/js/pages/timeline.js') ?>" defer></script>
    </body>
</html>
