<?php
define('BASE_PATH', __DIR__);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Basics -->
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Review the Terms & Conditions for using Scroll News, including guidelines, responsibilities, and important legal information." />
        <meta name="author" content="Scroll News" />
        <title>Terms & Conditions | Scroll News</title>

        <!-- Canonical + favicon -->
        <link rel="canonical" href="https://scrollnews.io/terms" />
        <link rel="icon" type="image/png" href="assets/img/play-green.png" />

        <!-- Open Graph -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://scrollnews.io/terms" />
        <meta property="og:title" content="Scroll News Terms of Service" />
        <meta property="og:description" content="Read the Terms of Service for using Scroll News, including acceptable use, limitations of liability, and other legal details." />
        <meta property="og:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-terms-1200x630.png" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://scrollnews.io/terms" />
        <meta name="twitter:title" content="Scroll News Terms of Service" />
        <meta name="twitter:description" content="Read the Terms of Service for using Scroll News, including acceptable use, limitations of liability, and other legal details." />
        <meta name="twitter:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-terms-1200x630.png" />

        <!-- jQuery min-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" defer></script>

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
                <div class="masthead-heading text-uppercase">Terms & Conditions</div>
            </div>
        </header>

        <!-- Content area-->
        <div class="container my-5">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-5">

                    <header class="mb-5">
                        <h1 class="mb-3">Terms & Conditions</h1>
                        <p class="text-muted mb-0">
                            <strong>Last Updated:</strong> August 1, 2025
                        </p>
                    </header>

                    <p class="lead">
                        Welcome to <strong>Scroll News</strong>. By accessing or using this website
                        (scrollnews.io), you agree to be bound by these Terms and Conditions.
                    </p>

                    <hr class="my-5">

                    <section class="mb-5">
                        <h4 class="fw-bold mb-3">1. Use of Site</h4>
                        <p>
                            Scroll News provides a streamlined way to view curated news content.
                            You agree not to use this site for unlawful purposes or interfere with its
                            normal operation.
                        </p>
                    </section>

                    <section class="mb-5">
                        <h4 class="fw-bold mb-3">2. Intellectual Property</h4>
                        <p>
                            All content on this site, unless otherwise stated, is owned or licensed by
                            Scroll News. Please don’t copy, reproduce, or redistribute content without
                            permission.
                        </p>
                    </section>

                    <section class="mb-5">
                        <h4 class="fw-bold mb-3">3. Disclaimer</h4>
                        <p>
                            Scroll News presents content for informational purposes only. We do not
                            guarantee the accuracy, timeliness, or completeness of any third-party content.
                        </p>
                    </section>

                    <section class="mb-5">
                        <h4 class="fw-bold mb-3">4. Changes</h4>
                        <p>
                            These terms may be updated occasionally. By continuing to use the site, you
                            accept any changes.
                        </p>
                    </section>

                    <section>
                        <h4 class="fw-bold mb-3">5. Contact</h4>
                        <p>
                            Questions? Reach out to us at
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

        <!-- Third party plugin JS-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>

    </body>
</html>