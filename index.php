<?php
define('BASE_PATH', __DIR__);
$theme_experiment_enabled = true;
$product_nav_stylesheet_in_head = true;
require_once BASE_PATH . "/auth/includes/auth_bootstrap.php";
require_once BASE_PATH . "/core/___modules.php";
sn_perf_phase('render');
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

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@graph": [{
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

    <script src="/assets/js/dark-theme.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/dark-theme.js'); ?>"></script>

    <link href="/assets/css/pages/home.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/home.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/pages/watch-desk.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/watch-desk.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/panels/news-intel-panel.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/panels/news-intel-panel.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/panels/news-intel-signal-map.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/panels/news-intel-signal-map.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/panels/active-stories.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/panels/active-stories.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/panels/scroll-strip.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/panels/scroll-strip.css'); ?>" rel="stylesheet" />
    <!-- HOMEPAGE LIGHT SaaS EXPERIMENT: remove these two lines and the sn-light-saas body class to revert -->
    <link id="light-saas" href="/assets/css/light-saas.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/light-saas.css'); ?>" rel="stylesheet" />
    <link id="home-light-saas" href="/assets/css/pages/home-light-saas.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/home-light-saas.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/pages/home-timeline.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/home-timeline.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/pages/home-reading-lens.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/home-reading-lens.css'); ?>" rel="stylesheet" />

    <link href="/assets/css/lightbox.css" rel="stylesheet" />
    <link href="/assets/css/product-nav.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/product-nav.css'); ?>" rel="stylesheet" />

</head>

<body id="page-top" class="home-page sn-light-saas">

    <div class="page">

        <!-- Top nav-->
        <?php require_once BASE_PATH . '/views/partials/___topnav_product.php'; ?>

        <!-- INTRO ATMOSPHERE EXPERIMENT: remove this wrapper div (and its closing tag) to revert -->
        <div class="sn-intro-atmosphere">

            <!-- Masthead -->
            <header class="masthead bg-light-2">
                <div class="container">
                    <div class="masthead-subheading">
                        An intelligence layer between you and the news.
                    </div>

                    <div class="masthead-heading text-uppercase">
                        Explore News<br><span>Differently.</span>
                    </div>

                    <div class="cta-group text-center">
                        <a id="scroll"
                            class="btn btn-green btn-lg btn-rectangle js-scroll-trigger text-black d-block d-md-inline-block btn-width-mobile-75 mx-auto mb-3 mb-sm-0 mr-md-2"
                            href="/newsroom.php"
                            data-loading>
                            Launch Newsroom
                        </a>

                        <?php if (!empty($_SESSION['user_id'])): ?>
                            <a class="btn btn-outline-secondary btn-lg btn-rectangle d-block d-md-inline-block btn-width-mobile-75 mx-auto mb-3 mb-md-0 mr-md-2"
                                href="/news-trails.php"
                                data-loading>
                                Explore News Trails
                            </a>
                        <?php endif; ?>

                        <?php if (empty($_SESSION['user_id'])): ?>
                            <a class="btn btn-dark btn-dark-clean btn-lg btn-rectangle js-scroll-trigger d-block d-md-inline-block btn-width-mobile-75 w-md-auto mx-auto mb-3 mb-sm-0 mr-md-2"
                                href="/auth/register.php">
                                Sign Up
                            </a>

                            <a class="btn btn-light btn-lg btn-rectangle js-scroll-trigger text-black d-block d-md-inline-block btn-width-mobile-75 mx-auto"
                                href="/auth/login.php">
                                Login
                            </a>
                        <?php endif; ?>
                    </div>

                    <p class="home-how-link-wrap">
                        <a class="home-how-link" href="how-it-works.php">
                            Discover how Scroll News works
                            <span aria-hidden="true">↗</span>
                        </a>
                    </p>
                </div>
            </header>


            <!-- Reading Lens -->
            <?php
            // Presets are the single source of truth for the lens: flags and URLs.
            // The "everything" preset has no filters, but a bare /search.php is only a
            // landing page, so it opens the last 24 hours instead.
            $rlPresets = [
                'worth' => [
                    'label'   => 'Worth your time',
                    'desc'    => 'Dense reporting from trusted outlets.',
                    'summary' => 'Deep-dive reporting from high-signal publishers.',
                    'deep'    => true,
                    'trusted' => true,
                    'href'    => '/search.php?mode=nlp&deep_dive=1&high_signal=1',
                ],
                'deep' => [
                    'label'   => 'Go deep',
                    'desc'    => 'Detailed, specific reporting from any source.',
                    'summary' => 'Deep-dive reporting from any publisher.',
                    'deep'    => true,
                    'trusted' => false,
                    'href'    => '/search.php?mode=nlp&deep_dive=1',
                ],
                'trusted' => [
                    'label'   => 'Trusted outlets',
                    'desc'    => 'Every story from our curated publishers.',
                    'summary' => 'Any article from high-signal publishers.',
                    'deep'    => false,
                    'trusted' => true,
                    'href'    => '/search.php?high_signal=1',
                ],
                'everything' => [
                    'label'   => 'Everything',
                    'desc'    => 'The full, unfiltered stream from the last 24 hours.',
                    'summary' => 'Every headline from the last 24 hours, unfiltered.',
                    'deep'    => false,
                    'trusted' => false,
                    'href'    => '/search.php?range=24h',
                ],
            ];
            $rlDefaultKey = 'worth';
            $rlDefault    = $rlPresets[$rlDefaultKey];

            $rlPublisherNames = [
                'nbcnews.com'    => 'NBC News',
                'cbsnews.com'    => 'CBS News',
                'abcnews.go.com' => 'ABC News',
                'npr.org'        => 'NPR',
                'axios.com'      => 'Axios',
                'apnews.com'     => 'Associated Press',
            ];
            $rlPublishers = [];
            foreach (array_keys($SCROLL_HIGH_SIGNAL_PUBLISHERS ?? []) as $rlDomain) {
                $rlPublishers[] = $rlPublisherNames[$rlDomain] ?? $rlDomain;
            }

            // Illustrative article field: d = deep dive, t = trusted outlet, b = both, . = neither
            $rlField = [
                'd.d.b.d..dt.',
                '.d..d.b.d.d.',
                'd.t.d.d.b..d',
                '.d.d..b.dt.d',
            ];
            ?>
            <section class="reading-modes-section sn-rl-section page-section bg-light-2 pt-5 pb-0" aria-labelledby="snRlTitle">
                <div class="container">
                    <div class="sn-rl" id="snRl" data-default="<?= htmlspecialchars($rlDefaultKey) ?>">
                        <header class="sn-rl-head">
                            <p class="sn-rl-eyebrow">Reading Lens</p>
                            <h2 class="sn-rl-title" id="snRlTitle">Find a read worth your time</h2>
                            <p class="sn-rl-sub">Narrow the feed by depth and by source. Pick a starting point, then fine-tune it.</p>
                        </header>

                        <div class="sn-rl-panel">
                            <div class="sn-rl-choose">
                                <ul class="sn-rl-presets" id="snRlPresets" aria-label="Reading lens presets">
                                    <?php foreach ($rlPresets as $rlKey => $rlPreset): ?>
                                        <li>
                                            <a class="sn-rl-preset"
                                                href="<?= htmlspecialchars($rlPreset['href']) ?>"
                                                data-preset="<?= htmlspecialchars($rlKey) ?>"
                                                data-deep="<?= $rlPreset['deep'] ? '1' : '0' ?>"
                                                data-trusted="<?= $rlPreset['trusted'] ? '1' : '0' ?>"
                                                data-summary="<?= htmlspecialchars($rlPreset['summary']) ?>"
                                                data-loading>
                                                <span class="sn-rl-mark" aria-hidden="true"></span>
                                                <span class="sn-rl-preset-text">
                                                    <span class="sn-rl-preset-name">
                                                        <?= htmlspecialchars($rlPreset['label']) ?>
                                                        <?php if ($rlKey === $rlDefaultKey): ?>
                                                            <span class="sn-rl-tag">Suggested</span>
                                                        <?php endif; ?>
                                                    </span>
                                                    <span class="sn-rl-preset-desc"><?= htmlspecialchars($rlPreset['desc']) ?></span>
                                                </span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <div class="sn-rl-stage">
                                <div class="sn-rl-field"
                                    id="snRlField"
                                    data-deep="<?= $rlDefault['deep'] ? '1' : '0' ?>"
                                    data-trusted="<?= $rlDefault['trusted'] ? '1' : '0' ?>"
                                    aria-hidden="true">
                                    <?php foreach ($rlField as $rlRow): ?>
                                        <?php foreach (str_split($rlRow) as $rlCell): ?>
                                            <i class="sn-rl-dot"
                                                data-deep="<?= ($rlCell === 'd' || $rlCell === 'b') ? '1' : '0' ?>"
                                                data-trusted="<?= ($rlCell === 't' || $rlCell === 'b') ? '1' : '0' ?>"></i>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </div>
                                <p class="sn-rl-caption">Each dot is an article. Lit dots match your lens (illustrative).</p>

                                <p class="sn-rl-summary" id="snRlSummary" aria-live="polite">
                                    <?= htmlspecialchars($rlDefault['summary']) ?>
                                </p>

                                <div class="sn-rl-tune" id="snRlTune" role="group" aria-label="Fine-tune your lens" hidden>
                                    <button type="button" class="sn-rl-switch" role="switch" aria-checked="<?= $rlDefault['deep'] ? 'true' : 'false' ?>" data-switch="deep">
                                        <span class="sn-rl-switch-track" aria-hidden="true"></span>
                                        <span class="sn-rl-switch-text">
                                            <span class="sn-rl-switch-axis">Depth</span>
                                            <span class="sn-rl-switch-label">Deep dives only</span>
                                        </span>
                                    </button>
                                    <button type="button" class="sn-rl-switch" role="switch" aria-checked="<?= $rlDefault['trusted'] ? 'true' : 'false' ?>" data-switch="trusted">
                                        <span class="sn-rl-switch-track" aria-hidden="true"></span>
                                        <span class="sn-rl-switch-text">
                                            <span class="sn-rl-switch-axis">Source</span>
                                            <span class="sn-rl-switch-label">High-signal publishers only</span>
                                        </span>
                                    </button>
                                </div>

                                <a class="sn-rl-cta" id="snRlCta" href="<?= htmlspecialchars($rlDefault['href']) ?>" data-loading>
                                    Open this feed <span aria-hidden="true">→</span>
                                </a>
                            </div>
                        </div>

                        <div class="sn-rl-notes">
                            <details class="sn-rl-note">
                                <summary>What counts as a deep dive?</summary>
                                <p>An article that mentions a lot of specific people, places and organizations (32 or more mentions). It's a signal of detailed reporting, not a guarantee of quality.</p>
                            </details>
                            <?php if (!empty($rlPublishers)): ?>
                                <details class="sn-rl-note">
                                    <summary>Which publishers are high-signal?</summary>
                                    <p><?= htmlspecialchars(implode(', ', $rlPublishers)) ?>. A curated list of outlets with consistently strong reporting.</p>
                                </details>
                            <?php endif; ?>
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
                    <p class="sn-rl-topic-intro" id="snTopicIntro">Or start with a topic</p>                    <div class="sn-categories-title">Analyze trends by category</div>

                    <div class="sn-categories-pills" role="group" aria-labelledby="snTopicIntro">
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
                                data-loading>
                                <?= htmlspecialchars($label) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div><!-- /.sn-intro-atmosphere -->


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

        <?php // Featured Coverage Timeline band: cached model only, same key as the warm job (___warm_home.php) ?>
        <?php render_home_panel('homepage_featured_timeline', 300, 900, BASE_PATH . '/views/home/panels/___featured_timeline.php', $bust, $homepageCache); ?>

        <!-- Intelligence workspace wrapper (light SaaS experiment grouping; harmless without the experiment CSS) -->
        <div class="sn-intel-workspace">

            <!-- News Intelligence Panel-->
            <?php
            render_home_panel(
                'homepage_news_intel_panel',
                60,
                600,
                BASE_PATH . '/views/home/panels/___news_intel_panel.php',
                $bust,
                $homepageCache
            );
            ?>

            <!-- Active Stories Panel-->
            <?php
            render_home_panel(
                'homepage_active_stories_descriptions_v1',
                30,
                300,
                BASE_PATH . '/views/home/panels/___active_stories.php',
                $bust,
                $homepageCache
            );
            ?>

            <!-- Brief Me Bar-->
            <?php // require_once BASE_PATH . '/views/home/partials/___brief_me.php'; 
            ?>

            <!-- First Look-->
            <?php include BASE_PATH . '/views/home/panels/___first_look.php'; ?>

        </div><!-- /.sn-intel-workspace -->

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


        <!-- Bigger than your feed text section -->
        <section class="py-5">
            <div class="container text-center">
                <h3 class="mb-0">The news is bigger than your feed.</h3>
            </div>
        </section>
        <!-- Contact-->
        <section class="page-section" id="contact">
            <img class="home-editorial-image" src="/assets/img/stories-connect-the-world.png" alt="Stories connect people and perspectives around the world" />
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
    <script src="/assets/js/pages/home-timeline.js?v=<?= filemtime(BASE_PATH . '/assets/js/pages/home-timeline.js') ?>" defer></script>
    <script src="/assets/js/pages/home-reading-lens.js?v=<?= filemtime(BASE_PATH . '/assets/js/pages/home-reading-lens.js') ?>" defer></script>
    <script src="/assets/js/pages/home-news-intel.js?v=<?= filemtime(BASE_PATH . '/assets/js/pages/home-news-intel.js') ?>" defer></script>


</body>

</html>