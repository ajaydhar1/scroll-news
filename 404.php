<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . "/auth/includes/auth_bootstrap.php";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>
        
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="The page you’re looking for couldn’t be found on Scroll News." />
        <meta name="author" content="Scroll News" />
        <title>Page Not Found | Scroll News</title>

        <!-- Favicon-->
        <link rel="icon" type="image/png" href="/assets/img/play-green.png" />
        <link rel="canonical" href="https://scrollnews.ai/404">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://scrollnews.ai/404.php" />
        <meta property="og:title" content="Page not found — Scroll News" />
        <meta property="og:description" content="The page you’re looking for could not be found. Return to the Scroll News homepage to analyze, browse, or search the news." />
        <meta property="og:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-404-1200x630.png" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://scrollnews.ai/404.php" />
        <meta name="twitter:title" content="Page not found — Scroll News" />
        <meta name="twitter:description" content="The page you’re looking for could not be found. Return to the Scroll News homepage to analyze, browse, or search the news." />
        <meta name="twitter:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-404-1200x630.png" />

        <!-- jQuery min-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

        <!-- Font Awesome icons (free version)-->
        <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous"></script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />

        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
        <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />

        <style>
            section#services {
                background: linear-gradient(to bottom, #eef1f4, #e8edf1);
            }

            p {
                font-weight: 300;
            }

            .page-section h3.section-subheading {
                font-weight: 700;
            }

        </style>

    </head>
    <body id="page-top" class="bg-dark">

        <!-- Top nav-->
        <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

        <!-- 404 Content -->
        <section class="page-section" id="services" style="padding: 4rem 0;">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8 text-center">
                        <h2 class="section-heading text-uppercase">Page Not Found</h2>
                        <h3 class="section-subheading" style="margin-bottom: 1.5rem;">
                            The link you followed doesn’t seem to go anywhere on Scroll News.
                        </h3>
                        <p class="text-muted mb-4">
                            The page may have moved, been renamed, or never existed. You can jump back into the latest stories
                            or return to the homepage.
                        </p>
                        <div class="mt-4">
                            <a href="/" class="btn btn-dark btn-lg mr-2" data-loading>
                                <i class="fas fa-home mr-1"></i> Go to Homepage
                            </a>
                            <a href="newsroom.php" class="btn btn-green btn-lg" data-loading>
                                <i class="fas fa-play mr-1"></i> Open Newsroom
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row text-center mt-5">
                    <div class="col-md-4 mb-4">
                        <span class="fa-stack fa-3x">
                            <i class="fas fa-circle fa-stack-2x text-dark"></i>
                            <i class="fas fa-search fa-stack-1x fa-inverse"></i>
                        </span>
                        <h4 class="my-3">Check the URL</h4>
                        <p class="text-muted">Make sure the address is spelled correctly or doesn’t include extra characters.</p>
                    </div>
                    <div class="col-md-4 mb-4">
                        <span class="fa-stack fa-3x">
                            <i class="fas fa-circle fa-stack-2x text-pink"></i>
                            <i class="fas fa-newspaper fa-stack-1x fa-inverse"></i>
                        </span>
                        <h4 class="my-3">Browse Stories</h4>
                        <p class="text-muted">The Newsroom will show you a fresh stream of current U.S. stories, ready to scroll.</p>
                    </div>
                    <div class="col-md-4 mb-4">
                        <span class="fa-stack fa-3x">
                            <i class="fas fa-circle fa-stack-2x" style="color: #00bfa6;"></i>
                            <i class="fas fa-sliders-h fa-stack-1x fa-inverse"></i>
                        </span>
                        <h4 class="my-3">Visit the Control Room</h4>
                        <p class="text-muted">Fine-tune how Scroll News behaves and explore more tools behind the feed.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer-->
        <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>

        <!-- Modals-->
        <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

        <!-- Bootstrap core JS-->
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>

    </body>
</html>
