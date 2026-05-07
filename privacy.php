<?php
define('BASE_PATH', __DIR__);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

        <!-- Basics -->
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Read the Scroll News Privacy Policy to learn how we collect, use, and protect information when you browse our site." />
        <meta name="author" content="Scroll News" />
        <title>Privacy Policy | Scroll News</title>

        <!-- Canonical + favicon -->
        <link rel="canonical" href="https://scrollnews.ai/privacy" />
        <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

        <!-- Open Graph -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://scrollnews.ai/privacy" />
        <meta property="og:title" content="Scroll News Privacy Policy" />
        <meta property="og:description" content="Learn how Scroll News collects, uses, and protects your data, including cookies, logs, and third-party services." />
        <meta property="og:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-privacy-1200x630.png" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://scrollnews.ai/privacy" />
        <meta name="twitter:title" content="Scroll News Privacy Policy" />
        <meta name="twitter:description" content="Learn how Scroll News collects, uses, and protects your data, including cookies, logs, and third-party services." />
        <meta name="twitter:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-privacy-1200x630.png" />

        <!-- jQuery min-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

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
        <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
        <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />

        <!-- Page-specific styles -->
        <style>
            body#page-top { background: #fafafa; }

            header.masthead {
            padding-top: 3.5rem;
            padding-bottom: 0.5rem;
            background-image: unset;
            }

            @media (min-width: 768px) {
            header.masthead .masthead-heading {
                font-size: 3.5rem;
                font-weight: 700;
                line-height: 1;
                margin-bottom: 2rem;
            }
            }

            @media (min-width: 768px) {
            section {
                padding: 0;
            }
            }

            a { color: var(--brand-color); }

            .lead {
            font-size: 1.2rem;
            font-weight: 300;
            }

        </style>
    </head>
    <body id="page-top">

        <!-- Top nav-->        
        <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

        <!-- Masthead-->
        <header class="masthead">
            <div class="container cover-img bg-dark py-5">
                <div class="mb-2 text-muted" style="font-size: 1.25rem;"><strong>#starter version</strong></div>
                <div class="masthead-heading text-uppercase">Privacy Policy</div>
            </div>
        </header>

        <!-- Content area--> 
        <div class="container my-5">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-5">

                <header class="mb-5">
                    <h1 class="mb-3">Privacy Policy</h1>
                    <p class="text-muted mb-0">
                    <strong>Last Updated:</strong> August 1, 2025
                    </p>
                </header>

                <p class="lead">
                    Scroll News respects your privacy. This policy explains what information we collect,
                    how it is used, and how it is protected when you browse our site.
                </p>

                <hr class="my-5">

                <section class="mb-5">
                    <h4 class="fw-bold mb-3">1. Data We Collect</h4>
                    <p>
                    We do <strong>not</strong> collect personal information such as names or email
                    addresses at this time.
                    </p>
                    <p>
                    We may use basic, non-personal analytics to understand general site usage,
                    including page views, device type, and browser information.
                    </p>
                </section>

                <section class="mb-5">
                    <h4 class="fw-bold mb-3">2. Cookies</h4>
                    <p>
                    Scroll News may use minimal cookies to improve performance and enhance
                    your experience.
                    </p>
                    <p>
                    You can disable cookies at any time through your browser settings.
                    </p>
                </section>

                <section class="mb-5">
                    <h4 class="fw-bold mb-3">3. Third-Party Links</h4>
                    <p>
                    Our site may link to external websites. We are not responsible for the
                    privacy practices or content of third-party sites.
                    </p>
                </section>

                <section class="mb-5">
                    <h4 class="fw-bold mb-3">4. Policy Updates</h4>
                    <p>
                    We may update this Privacy Policy periodically.
                    Continued use of Scroll News indicates acceptance of any changes.
                    </p>
                </section>

                <section>
                    <h4 class="fw-bold mb-3">5. Contact</h4>
                    <p>
                    If you have questions about this Privacy Policy, please contact us at
                    <a href="mailto:your@email.com">your@email.com</a>.
                    </p>
                </section>

                </div>
            </div>
        </div>

        <!-- Footer-->        
        <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>
        
        <!-- Modals-->        
        <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

        <!-- Bootstrap core JS-->
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>

    </body>
</html>
