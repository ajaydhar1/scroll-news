<?php
define('BASE_PATH', __DIR__);
$theme_experiment_enabled = true;

require_once BASE_PATH . '/auth/includes/auth_bootstrap.php';
require_once BASE_PATH . '/core/___modules.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Watch the latest news coverage from leading newsrooms on Scroll News." />
    <meta name="author" content="Scroll News" />
    <title>Watch Desk — Scroll News</title>

    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <link rel="preconnect" href="https://www.youtube.com" crossorigin />
    <link rel="preconnect" href="https://i.ytimg.com" crossorigin />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&amp;family=Open+Sans&amp;display=swap" rel="stylesheet" />

    <link href="/assets/css/styles.css?v=<?= filemtime(BASE_PATH . '/assets/css/styles.css') ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?= filemtime(BASE_PATH . '/assets/css/custom.css') ?>" rel="stylesheet" />
    <link id="mindpour-theme" href="/assets/css/mindpour.css?v=<?= filemtime(BASE_PATH . '/assets/css/mindpour.css') ?>" rel="stylesheet" />
    <link id="dark-theme" href="/assets/css/dark.css?v=<?= filemtime(BASE_PATH . '/assets/css/dark.css') ?>" rel="stylesheet" />
    <link id="dark-typography-theme" href="/assets/css/dark-typography.css?v=<?= filemtime(BASE_PATH . '/assets/css/dark-typography.css') ?>" rel="stylesheet" />
    <script src="/assets/js/dark-theme.js?v=<?= filemtime(BASE_PATH . '/assets/js/dark-theme.js') ?>"></script>
    <link href="/assets/css/light-saas.css?v=<?= filemtime(BASE_PATH . '/assets/css/light-saas.css') ?>" rel="stylesheet" />
    <link href="/assets/css/pages/watch-desk.css?v=<?= filemtime(BASE_PATH . '/assets/css/pages/watch-desk.css') ?>" rel="stylesheet" />
</head>

<body id="page-top" class="watch-page sn-light-saas">
    <div class="page">
        <?php require_once BASE_PATH . '/views/partials/___topnav_product.php'; ?>

        <main>
            <?php require BASE_PATH . '/views/home/partials/___home_playlist.php'; ?>
        </main>

        <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>
    </div>

    <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" defer></script>
</body>

</html>