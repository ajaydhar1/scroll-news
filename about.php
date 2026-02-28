<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Basics -->
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Learn what Scroll News is, why it was created, and how it helps you analyze, browse, and search the news more intelligently." />
        <meta name="author" content="Scroll News" />
        <title>About Scroll News – Our Story & How It Works</title>

        <!-- Canonical + favicon -->
        <link rel="canonical" href="https://scrollnews.io/about" />
        <link rel="icon" type="image/png" href="assets/img/play-green.png" />

        <!-- Open Graph -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://scrollnews.io/about" />
        <meta property="og:title" content="About Scroll News — Why it was built" />
        <meta property="og:description" content="Learn what Scroll News is, why it was created, and how it helps you analyze, browse, and search the news more intelligently." />
        <meta property="og:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-about-1200x630.png" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://scrollnews.io/about" />
        <meta name="twitter:title" content="About Scroll News — Why it was built" />
        <meta name="twitter:description" content="Learn what Scroll News is, why it was created, and how it helps you analyze, browse, and search the news more intelligently." />
        <meta name="twitter:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-about-1200x630.png" />

        <!-- Icons -->
        <script
            src="https://use.fontawesome.com/releases/v6.7.2/js/all.js"
            crossorigin="anonymous"
        ></script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />

        <!-- Site CSS -->
        <link href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>" rel="stylesheet" />
        <link href="css/custom.css?v=<?php echo filemtime(__DIR__ . '/css/custom.css'); ?>" rel="stylesheet" />

        <!-- Page-specific styles -->
        <style>
            body#page-top { background: #fafafa; }

            a { color: var(--brand-color); }

            p { font-weight: 300; }

            .lead {
            font-size: 1.2rem;
            font-weight: 300;
            }

            /* This page used to feel "flat" mainly because it was: dark body + one gray section + no elevation.
               Card layout + subtle section spacing fixes that without changing your content. */
        </style>
    </head>
    <body id="page-top">

        <!-- Top nav-->
        <?php require_once __DIR__ . '/___topnav_full.php'; ?>

        <!-- Content area -->
        <div class="container my-5">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-5">

                    <header class="mb-5">
                        <h1 class="mb-3"><img src="assets/img/play-green.png" alt="Logo" style="height: 38px; width: auto; vertical-align: middle; margin-right: 10px; margin-bottom: 5px;">About Scroll News</h1>
                        <p class="text-muted mb-0">
                            <strong>Scroll News is a personal news intelligence layer.</strong>
                        </p>
                    </header>

                    <p class="lead">
                        Modern news is overwhelming. Headlines compete for attention, timelines refresh endlessly,
                        and important context gets buried under volume.
                    </p>

                    <p class="text-muted" style="font-size: 1.05rem; line-height: 1.7;">
                        Scroll News was built as a calm alternative — a way to see what matters, revisit what you’ve read,
                        and build understanding over time.
                    </p>

                    <p class="text-muted" style="font-size: 1.05rem; line-height: 1.7; margin-bottom: 0.75rem;">
                        It’s designed to be:
                    </p>

                    <ul class="text-muted" style="font-size: 1.05rem; line-height: 1.8; font-weight: 300;">
                        <li>A focused layer on top of the news you already read</li>
                        <li>A way to track themes, people, and places across stories</li>
                        <li>A personal archive of headlines you cared enough to save</li>
                        <li>A quiet space built for clarity, not urgency</li>
                    </ul>

                    <p class="text-muted" style="font-size: 1.05rem; line-height: 1.7; margin-top: 1.25rem;">
                        <span style="font-weight: 600;">No ads. No outrage.</span> Just information — organized.
                    </p>

                    <hr class="my-5">

                    <!-- Feature blocks -->
                    <div class="row g-4 justify-content-center text-center">
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm rounded-3">
                                <div class="card-body p-4">
                                    <span class="fa-stack fa-3x mb-3">
                                        <i class="fas fa-circle fa-stack-2x text-dark"></i>
                                        <i class="fas fa-flag-usa fa-stack-1x fa-inverse"></i>
                                    </span>
                                    <h4 class="mb-2">U.S. News</h4>
                                    <p class="text-muted mb-0">
                                        Scroll News focuses on U.S. coverage — built for people who live here, or anyone
                                        who wants a clear view of what’s happening.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm rounded-3">
                                <div class="card-body p-4">
                                    <span class="fa-stack fa-3x mb-3">
                                        <i class="fas fa-circle fa-stack-2x text-pink"></i>
                                        <i class="fas fa-newspaper fa-stack-1x fa-inverse"></i>
                                    </span>
                                    <h4 class="mb-2">Signal</h4>
                                    <p class="text-muted mb-0">
                                        A fast way to catch the day’s active headlines and the stories that are picking up
                                        momentum — without the chaos.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm rounded-3">
                                <div class="card-body p-4">
                                    <span class="fa-stack fa-3x mb-3">
                                        <i class="fas fa-circle fa-stack-2x" style="color: #00bfa6;"></i>
                                        <i class="fas fa-chart-bar fa-stack-1x fa-inverse"></i>
                                    </span>
                                    <h4 class="mb-2">Insight</h4>
                                    <p class="text-muted mb-0">
                                        Natural language processing highlights key people, places, and themes — so you can
                                        see patterns across stories at a glance.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Footer-->
        <?php require_once __DIR__ . '/___footer.php'; ?>

        <!-- Modals-->
        <?php require_once __DIR__ . '/___modals.php'; ?>

        <!-- Bootstrap core JS-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>

        <!-- Third party plugin JS-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>

    </body>
</html>