<?php

define('BASE_PATH', __DIR__);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <meta
        name="description"
        content="Preview the upcoming News Trail Player on Scroll News — a focused way to move through grouped news journeys built from reading, searches, saved headlines, and shuffles." />

    <meta name="author" content="Scroll News" />

    <title>News Trail Player – Scroll News</title>

    <meta name="robots" content="index,follow">

    <!-- Favicon-->
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <link
        rel="canonical"
        href="https://scrollnews.ai/news-trail-player.php" />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />

    <meta
        property="og:url"
        content="https://scrollnews.ai/news-trail-player.php" />

    <meta
        property="og:title"
        content="News Trail Player on Scroll News" />

    <meta
        property="og:description"
        content="A focused way to move through grouped news journeys built from reading, searches, saved headlines, and shuffles." />

    <meta
        property="og:image"
        content="https://scrollnews.ai/assets/img/og/og-scrollnews-news-trails-1200x630.png" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />

    <meta
        name="twitter:url"
        content="https://scrollnews.ai/news-trail-player.php" />

    <meta
        name="twitter:title"
        content="News Trail Player on Scroll News" />

    <meta
        name="twitter:description"
        content="Move through grouped news journeys built from reading history, saved headlines, searches, and shuffles." />

    <meta
        name="twitter:image"
        content="https://scrollnews.ai/assets/img/og/og-scrollnews-news-trails-1200x630.png" />

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
    <link href="/assets/css/auth.css?v=<?= filemtime(BASE_PATH . '/assets/css/auth.css') ?>" rel="stylesheet" />
    <link href="/assets/css/account.css?v=<?= filemtime(BASE_PATH . '/assets/css/account.css') ?>" rel="stylesheet" />

    <style>
        .trail-player-preview img {
            border-radius: 1rem;
            overflow: hidden;
        }
    </style>

</head>

<body id="page-top" class="auth-page account-page">

    <!-- Top nav-->
    <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

    <main class="container py-5">

        <div class="row justify-content-center">
            <div class="col-xl-9">

                <div class="text-center mb-5">

                    <header class="mb-5 text-center">
                        <h1 class="h2 mb-2"><i class="fa-solid fa-route mr-1"></i> News Trail Player</h1>
                        <div class="row justify-content-center">
                            <div class="col-lg-10">
                                <p class="text-trail-subtitle mb-0">
                                    A focused way to move through grouped news journeys built from reading,
                        saved headlines, searches, and shuffles.
                                </p>
                            </div>
                        </div>
                    </header>

                    <div class="mt-4">
                        <span class="badge badge-dark px-4 py-2" style="font-size: 1rem;">
                            Coming soon
                        </span>
                    </div>

                </div>

                <div class="trail-player-preview text-center">

                    <img
                        src="/assets/img/og/og-scrollnews-news-trails-1200x630.png"
                        alt="News Trails Preview"
                        class="img-fluid rounded shadow-lg border" />

                </div>

            </div>
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
</body>

</html>