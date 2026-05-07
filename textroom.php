<?php
// Production-ish settings
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('log_errors', '1');

define('BASE_PATH', __DIR__);

require_once BASE_PATH . "/core/config/interest.php";
require_once BASE_PATH . "/core/___modules.php";

// Capture pasted article text
$text = trim($_POST['text'] ?? '');

// Basic guard
if ($text === '') {
    http_response_code(400);
    echo "No text provided.";
    exit;
}

// Optional: limit length to prevent huge pastes
if (mb_strlen($text) > 50000) {
    $text = mb_substr($text, 0, 50000);
}

// Optional: create a simple title from the text
$title = mb_substr(preg_replace('/\s+/', ' ', $text), 0, 80);

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="robots" content="noindex, nofollow">
        <meta name="description" content="Browse the Scroll News Newsroom to see AI-analyzed U.S. headlines, scroll through article screenshots, and quickly understand what’s happening right now." />
        <meta name="author" content="Scroll News" />

        <title>Text Analysis | Scroll News</title>

        <!-- Favicon-->
        <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

        <!-- SEO -->
        <meta name="description" content="Analyze pasted text using Scroll News NLP tools. Extract entities, sentiment, emotions, keywords, and narrative signals instantly." />
        <meta name="robots" content="noindex, nofollow" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Scroll News Text Analysis Tool" />
        <meta name="twitter:description" content="Paste any article or document and instantly analyze entities, sentiment, emotions, and keywords." />
        <meta name="twitter:image" content="https://scrollnews.ai/assets/img/scrollnews-og.png" />

        <!-- Open Graph -->
        <meta property="og:url" content="https://scrollnews.ai/textroom.php" />
        <meta property="og:title" content="Scroll News Text Analysis Tool" />
        <meta property="og:description" content="Paste any article or document and instantly analyze entities, sentiment, emotions, and keywords." />
        <meta property="og:image" content="https://scrollnews.ai/assets/img/scrollnews-og.png" />
        <meta property="og:site_name" content="Scroll News" />
        <meta property="og:type" content="website" />

        <!-- Performance: Preload background -->
        <link rel="preload" as="image" href="/assets/img/mind-pour_00.jpg">

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
        <link href="/assets/css/mindpour.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/mindpour.css'); ?>" rel="stylesheet" />

        <!-- Add IntroJs styles -->
        <link href="/assets/css/introjs.css" rel="stylesheet">

        <link rel="stylesheet" href="/assets/css/jquery.fancybox.min.css"/>

    </head>
    <body id="page-top">

        <!-- Blurred overlay -->
        <div class="blur-layer"></div>

        <div class="page">

            <!-- Top nav-->        
            <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

            <!-- Masthead-->
            <header class="masthead" style="background-image: url(<?php echo $img; ?>)">
                <div class="container cover-img py-5">
                    <div class="mb-2 text-muted" style="font-size: 1.25rem;"><strong>Analyze pasted text using Scroll News NLP</strong></div>
                    <div class="masthead-heading text-uppercase mb-1">Textroom</div>
                </div>
            </header>

            <a name="analytics"></a>

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

            <div id="side-by-side-panel"> <?php // class="mb-4" style="min-height:480px;" ?>
                <div class="container-fluid" style="padding-top: 30px;">
                    <div id="panel-inner-row" class="row flex-row"> <?php //  style="height: 95vh;" ?>
                        <!-- NLP Dashboard Panel -->
                        <div class="col-xxl-6 col-xl-12 col-lg-12 col-md-12 panel" style="overflow-y: auto; border-right: 2px solid #eee;">
                            <div class="text-center mb-3">
                                <h2>🧠 NLP Dashboard</h2>
                            </div>
                            <div id="analytics" class="skeleton">
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
                            </div>
                        </div>

                        <!-- Article Text Panel -->
                        <div class="col-xxl-6 col-xl-12 col-lg-12 col-md-12 panel" style="height: 100%; padding: 0; overflow-y: auto;"> <?php //background-color: #fcfcfc; ?>
                            <div class="text-center mb-3">
                                <h2>📰 Article</h2>
                            </div>
                            <div class="px-3">
                                <div class="row">
                                    <div class="col-12 col-md-12 col-lg-12 col-xl-12">
                                        <div data-step="10" data-intro="This is the text you submitted for analysis." class="card w-100 shadow mb-3">
                                            <!-- Card Header - Dropdown -->
                                            <div class="card-header d-flex flex-row align-items-center justify-content-between bg-gradient">
                                                <h5 class="m-0 font-weight-bold">Source Text</h5>
                                            </div>
                                            <!-- Card Body -->
                                            <div class="card-body">
                                                <p><?= $text ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>                     
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer-->
            <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>

            <div id="sn-mini-player-mount"></div>

        </div>

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

        <script src="/assets/js/sn-mini-player-yt.js?v=<?= filemtime(BASE_PATH.'/assets/js/sn-mini-player-yt.js') ?>" defer></script>

        <script>
            window.TEXTROOM = {
                fromDb: false,
                text: <?= json_encode($text ?? '') ?>
            };
        </script>

        <script src="/assets/js/pages/textroom.js?v=<?= filemtime(BASE_PATH.'/assets/js/pages/textroom.js') ?>" defer></script>

    </body>
</html>
