<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . "/auth/includes/auth_bootstrap.php";
require_once BASE_PATH . "/core/___modules.php";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Scroll News uses AI to analyze news articles, uncover narrative frames, and highlight key entities and trends. Explore headlines with deeper insight and interactive analytics." />
        <meta name="author" content="Scroll News" />
        <title>Scroll News – AI News Analysis, Narrative Frames & Trending Stories</title>

        <!-- Favicon-->
        <link rel="icon" type="image/png" href="/assets/img/play-green.png" />
        <link rel="canonical" href="https://scrollnews.ai/">

        <!-- Performance: YouTube preconnects -->
        <link rel="preconnect" href="https://www.youtube.com" crossorigin>
        <link rel="preconnect" href="https://www.google.com" crossorigin>
        <link rel="preconnect" href="https://i.ytimg.com" crossorigin>
        <link rel="preconnect" href="https://yt3.ggpht.com" crossorigin>
        <link rel="dns-prefetch" href="https://s.ytimg.com">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://scrollnews.ai/" />
        <meta property="og:title" content="Scroll News — Analyze, Browse, Search the news" />
        <meta property="og:description" content="A smarter way to catch up on the news. Analyze articles by URL, browse top stories by topic, and search the latest headlines." />
        <meta property="og:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://scrollnews.ai/" />
        <meta name="twitter:title" content="Scroll News — Analyze, Browse, Search the news" />
        <meta name="twitter:description" content="A smarter way to catch up on the news. Analyze articles by URL, browse top stories by topic, and search the latest headlines." />
        <meta name="twitter:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />

        <!-- Performance: Preload background -->
        <link rel="preload" as="image" href="/assets/img/mind-pour_00.jpg">

        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@graph": [
            {
              "@type": "WebSite",
              "@id": "https://scrollnews.ai/#website",
              "url": "https://scrollnews.ai/",
              "name": "Scroll News",
              "description": "A smarter way to catch up on the news. Analyze articles by URL, browse stories by topic, and search the latest headlines.",
              "inLanguage": "en",
              "publisher": {
                "@id": "https://scrollnews.ai/#organization"
              },
              "potentialAction": {
                "@type": "SearchAction",
                "target": "https://scrollnews.ai/search.php?q={search_term_string}",
                "query-input": "required name=search_term_string"
              }
            },
            {
              "@type": "Organization",
              "@id": "https://scrollnews.ai/#organization",
              "name": "Scroll News",
              "url": "https://scrollnews.ai/",
              "logo": {
                "@type": "ImageObject",
                "url": "https://scrollnews.ai/assets/img/logos/scrollnews-icon-512.png"
              }
            },
            {
              "@type": "WebPage",
              "@id": "https://scrollnews.ai/#homepage",
              "url": "https://scrollnews.ai/",
              "name": "Scroll News — Analyze, Browse, Search the news",
              "description": "A smarter way to catch up on the news. Analyze articles by URL, browse stories by topic, and search the latest headlines.",
              "inLanguage": "en",
              "isPartOf": {
                "@id": "https://scrollnews.ai/#website"
              }
            }
          ]
        }
        </script>

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
        <link href="/assets/css/mindpour.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/mindpour.css'); ?>" rel="stylesheet" />
        <link href="/assets/css/pages/home.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/home.css'); ?>" rel="stylesheet" />
        <link href="/assets/css/panels/news-intel-panel.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/panels/news-intel-panel.css'); ?>" rel="stylesheet" />
        <link href="/assets/css/panels/active-stories.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/panels/active-stories.css'); ?>" rel="stylesheet" />
        <link href="/assets/css/panels/scroll-strip.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/panels/scroll-strip.css'); ?>" rel="stylesheet" />

        <link href="/assets/css/lightbox.css" rel="stylesheet" />

    </head>
    <body id="page-top">

        <!-- Blurred overlay -->
        <div class="blur-layer"></div>

        <div class="page">

            <!-- Top nav-->        
            <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

            <!-- Masthead-->
            <header class="masthead bg-light-2">
                <div class="container">
                    <div class="masthead-subheading">An intelligence layer between you and the news.</div>
                    <div class="masthead-heading text-uppercase">AI-Driven News<br><span>Insights</span></div>
                    <div class="cta-group text-center">
                        <a id="scroll" class="btn btn-green btn-lg btn-rectangle js-scroll-trigger text-black d-block d-md-inline-block btn-width-mobile-75 mx-auto mb-3 mb-sm-0 mr-md-2" href="newsroom.php" data-loading>Launch Newsroom</a>
                        <a class="btn btn-dark btn-dark-clean btn-lg btn-rectangle js-scroll-trigger d-block d-md-inline-block btn-width-mobile-75 w-md-auto mx-auto mb-3 mb-sm-0 mr-md-2" href="/auth/register.php">Sign Up</a>
                        <a id="" class="btn btn-light btn-lg btn-rectangle js-scroll-trigger text-black d-block d-md-inline-block btn-width-mobile-75 mx-auto" href="/auth/login.php">Login</a>
                    </div>
                </div>
            </header>

            <!-- Reading Modes-->
            <section class="reading-modes-section page-section bg-light-2 pt-5 pb-0">
            <div class="container">
                <div class="row mb-4">
                <div class="col-12 text-center">
                    <h2 class="mb-2">Reading Modes</h2>
                    <p class="text-muted mb-0">
                    Two ways to reach the reporting you love — choose how you get there.
                    </p>
                </div>
                </div>

                <div class="row g-4">
                <!-- Deep Dive -->
                <div class="col-md-6 mb-3">
                    <div class="card h-100 shadow-sm reading-mode-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                        <span class="reading-mode-icon me-2">🔎</span>
                        <h3 class="h5 mb-0">Deep Dive</h3>
                        </div>
                        <div class="scroll-article-badges">
                            <a class="scroll-badge scroll-badge-deep-dive" href="/search.php?mode=nlp&amp;deep_dive=1" title="Articles with rich entity density (detailed topics)." data-loading="">
                                Deep dive
                            </a>
                        </div>
                        <p class="text-muted mb-2">
                        Thoughtful, entity-aware reporting that goes beyond headlines and reactions.
                        </p>
                        <p class="text-muted italic mb-4">
                        These are the pieces you sit with — fewer articles, more understanding.
                        </p>
                        <a href="/search.php?mode=nlp&deep_dive=1" class="btn btn-sm btn-green" data-loading>
                        Browse Deep Dives
                        </a>
                    </div>
                    </div>
                </div>

                <!-- High-Signal Publishers -->
                <div class="col-md-6 mb-3">
                    <div class="card h-100 shadow-sm reading-mode-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                        <span class="reading-mode-icon me-2">📡</span>
                        <h3 class="h5 mb-0">High-Signal Publishers</h3>
                        </div>
                        <div class="scroll-article-badges mb-2">
                            <a class="scroll-badge scroll-badge-high-signal-publisher" href="/search.php?high_signal=1" title="Top-tier outlets with high editorial signal." data-loading="">
                                High-Signal Publisher
                            </a>
                        </div>
                        <p class="text-muted mb-2">
                        A curated stream of outlets that consistently publish smart, compelling reporting.
                        </p>
                        <p class="text-muted italic mb-4">
                        Ideal when you want to browse without worrying if an article will be worth your time.
                        </p>
                        <a href="/search.php?high_signal=1" class="btn btn-sm btn-outline-secondary" data-loading>
                        View High-Signal Feed
                        </a>
                    </div>
                    </div>
                </div>
                </div>
            </div>
            </section>

            <!-- Category pills for the analysis page -->
            <?php

            $categories = [
            'politics'      => 'Politics',
            'sports'        => 'Sports',
            'business'      => 'Business',
            'technology'    => 'Technology',
            'science'       => 'Science',
            'health'        => 'Health',
            'entertainment' => 'Entertainment',
            ];

            // optional: highlight the active category if you're already on analysis pages
            $currentCtx = isset($_GET['context']) ? strtolower(trim((string)$_GET['context'])) : '';
            $currentVal = isset($_GET['value'])   ? strtolower(trim((string)$_GET['value']))   : '';
            ?>
            <section class="container-fluid bg-light-2 py-3 text-center">
            <div class="sn-categories-strip">
            <div class="sn-categories-title">Analyze trends by category</div>

            <div class="sn-categories-pills">
                <?php foreach ($categories as $slug => $label):
                $params = $_GET; // preserve existing params (optional)
                $params['context'] = 'category';
                $params['value']   = $slug;
                $params['w']       = '7d';

                // If you don't want to preserve homepage params, replace the 4 lines above with:
                // $params = ['context' => 'category', 'value' => $slug, 'w' => '7d'];

                $href = '/analysis.php?' . http_build_query($params);

                $isActive = ($currentCtx === 'category' && $currentVal === $slug);
                ?>
                <a
                    class="btn btn-dark mx-1 mb-2"
                    href="<?= htmlspecialchars($href) ?>"
                    title="Analyze <?= htmlspecialchars($label) ?>"
                    data-loading
                >
                    <?= htmlspecialchars($label) ?>
                </a>
                <?php endforeach; ?>
            </div>
            </div>
            </section>


            <?php

                $bust = isset($_GET['nocache']) && $_GET['nocache'] == '1';

                // bump $CACHE_VER in ___modules.php to v2 when you change markup in cached includes

                $homepageCache = $bust ? [] : get_cache_group('homepage');

                function render_home_panel(
                    string $cache_key,
                    int $ttl,
                    int $stale_ttl,
                    string $include_path,
                    bool $bust,
                    array $homepageCache
                ): void {
                    global $CACHE_VER;

                    if (!$bust && !empty($homepageCache[$cache_key]['html'])) {
                        echo $homepageCache[$cache_key]['html'];
                        return;
                    }

                    fragment_cache_swr(
                        "{$cache_key}_{$CACHE_VER}",
                        $ttl,
                        $stale_ttl,
                        function () use ($include_path) {
                            include $include_path;
                        },
                        $bust,
                        false,
                        false
                    );
                }

            ?>

            <!-- News Intelligence Panel-->
            <?php
                fragment_cache_swr("news_intel_panel_$CACHE_VER", 60, 600, function () {
                    include BASE_PATH . '/views/home/panels/___news_intel_panel.php';
                }, $bust, false, false);
            ?>

            <!-- Active Stories Panel-->
            <?php
                render_home_panel(
                    'homepage_active_stories',
                    30,
                    300,
                    BASE_PATH . '/views/home/panels/___active_stories.php',
                    $bust,
                    $homepageCache
                );
            ?>

            <!-- Brief Me Bar-->
            <?php require_once BASE_PATH . '/views/home/partials/___brief_me.php'; ?>

            <!-- First Look-->
            <?php include BASE_PATH . '/views/home/panels/___first_look.php'; ?>

            <?php include BASE_PATH . '/views/home/partials/___home_features.php'; ?>
            <?php include BASE_PATH . '/views/home/partials/___home_modules.php'; ?>
            <?php include BASE_PATH . '/views/home/partials/___home_story.php'; ?>

            <?php
                render_home_panel(
                    'homepage_scroll_strip',
                    60,
                    600,
                    BASE_PATH . '/views/home/panels/___scroll_strip.php',
                    $bust,
                    $homepageCache
                );
            ?>

            
            <?php include BASE_PATH . '/views/home/partials/___home_playlist.php'; ?>

            <?php include BASE_PATH . '/views/home/partials/___home_team.php'; ?>


            <!-- Trusted by creators text section -->
            <section class="py-5">
                <div class="container text-center">
                    <h3 class="mb-0">Trusted by modern creators and teams</h3>
                </div>
            </section>
            <!-- Contact-->
            <section class="page-section" id="contact">
                <div class="container">
                    <div class="text-center">
                        <h2 class="section-heading text-uppercase">&nbsp;</h2>
                        <h3 class="section-subheading text-muted">&nbsp;</h3>
                    </div>
                    
                </div>
            </section>
            
            <!-- Footer-->
            <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>
        
        </div>
        
        <!-- Modals-->        
        <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

        <!-- Core JS (Bootstrap 4 requires jQuery first) -->
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" defer></script>

        <script type="text/javascript" src="/assets/js/lightbox.js"></script>

        <script src="/assets/js/sn_history.js?v=<?= filemtime(BASE_PATH . '/assets/js/sn_history.js') ?>" defer></script>
        

    </body>
</html>
