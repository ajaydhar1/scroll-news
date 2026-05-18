<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Basics -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description"
        content="Verify your Scroll News email address and finish setting up your account." />
    <meta name="author" content="Scroll News" />
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | Scroll News</title>

    <meta name="robots" content="noindex, nofollow">
    <!-- Canonical + favicon -->
    <link rel="canonical" href="https://scrollnews.ai/auth/verify-email" />
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <!-- Open Graph -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://scrollnews.ai/auth/verify-email" />
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> — Scroll News" />
    <meta property="og:description"
        content="Verify your Scroll News email address and finish setting up your account." />
    <meta property="og:image"
        content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" content="https://scrollnews.ai/auth/verify-email" />
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> — Scroll News" />
    <meta name="twitter:description"
        content="Verify your Scroll News email address and finish setting up your account." />
    <meta name="twitter:image"
        content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />

    <!-- Performance: Preload background -->
    <link rel="preload" as="image" href="/assets/img/mind-pour_00.jpg">

    <!-- jQuery min-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <!-- Icons -->
    <script
        src="https://use.fontawesome.com/releases/v6.7.2/js/all.js"
        crossorigin="anonymous"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />

    <!-- Site CSS -->
    <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/auth.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/auth.css'); ?>" rel="stylesheet" />
</head>

<body id="page-top" class="auth-page">

    <div class="page">

        <!-- Top nav-->
        <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

        <div class="auth-shell container my-5">
            <div class="auth-card card border-0">
                <div class="card-body p-5">

                    <header class="text-center mb-4">
                        <div class="auth-status-icon <?= $isSuccess ? 'auth-status-success' : 'auth-status-error' ?>" aria-hidden="true">
                            <i class="fas <?= $isSuccess ? 'fa-check' : 'fa-exclamation' ?>"></i>
                        </div>
                        <h1 class="h3 mb-2"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                        <p class="text-muted mb-0">
                            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </header>

                    <?php if ($isSuccess): ?>
                        <a href="/auth/login.php" class="btn btn-green btn-block">
                            Sign In
                        </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Footer-->

        <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>

    </div>

    <!-- Modals-->
    <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

    <!-- Bootstrap core JS-->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>


</body>

</html>