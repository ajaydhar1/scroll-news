<?php
define('BASE_PATH', dirname(__DIR__)); // /api -> project root
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Browse the Scroll News Newsroom to see AI-analyzed U.S. headlines...">
        <meta name="author" content="Scroll News" />

        <title>Scroll News NLP API Docs</title>

        <!-- Favicon-->
        <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

        <link rel="canonical" href="https://scrollnews.ai/api/docs" />

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
        <!-- <link href="/assets/css/mindpour.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/mindpour.css'); ?>" rel="stylesheet" /> -->
        <link href="/assets/css/pages/api-docs.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/api-docs.css'); ?>" rel="stylesheet" />

    </head>
    <body id="page-top">

        <!-- Blurred overlay -->
        <div class="blur-layer"></div>

        <div class="page">

            <!-- Top nav-->        
            <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

            <!-- ADD CONTENT HERE-->
            <main class="api-docs-page">
                <section class="api-docs-hero">
                    <div class="container">
                        <span class="api-docs-badge">Preview</span>
                        <h1>Scroll News NLP API Documentation</h1>
                        <p>
                            Explore the planned structure of the Scroll News NLP API, including
                            example requests, response fields, and output formats.
                        </p>
                    </div>
                </section>

                <section class="api-docs-content">
                    <div class="container">
                        <!-- Overview -->
                        <!-- Endpoint -->
                        <!-- Example Request -->
                        <!-- Example Response -->
                        <!-- Fields -->
                        <!-- Status -->
                    </div>
                </section>
            </main>

            <!-- Footer-->
            <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>

        </div>

        <!-- Modals-->        
        <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

        <!-- Core JS (Bootstrap 4 requires jQuery first) -->
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" defer></script>

    </body>
</html>
