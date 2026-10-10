<?php
$theme_experiment_enabled = true;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script src="/assets/js/dark-theme.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/dark-theme.js'); ?>"></script>
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

    <!-- Icons -->
    <script
        src="https://use.fontawesome.com/releases/v6.7.2/js/all.js"
        crossorigin="anonymous"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />

    <!-- Site CSS -->
    <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
    <link id="dark-typography-theme" href="/assets/css/dark-typography.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/dark-typography.css'); ?>" rel="stylesheet" />
    <link id="dark-theme" href="/assets/css/dark.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/dark.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/auth.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/auth.css'); ?>" rel="stylesheet" />
</head>

<body id="page-top" class="auth-page auth-page--foundation">
    <div class="auth-layout">
        <?php require BASE_PATH . '/auth/views/partials/auth-header.php'; ?>

        <main class="auth-main">
            <section class="auth-panel" aria-labelledby="auth-title">
                <header class="auth-panel__heading">
                    <div class="auth-status-icon <?= $isSuccess ? 'auth-status-success' : 'auth-status-error' ?>" aria-hidden="true">
                        <i class="fas <?= $isSuccess ? 'fa-check' : 'fa-exclamation' ?>"></i>
                    </div>
                    <p class="auth-eyebrow">Email verification</p>
                    <h1 id="auth-title"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                </header>

                <div class="auth-message <?= $isSuccess ? 'auth-message--success' : 'auth-message--error' ?>" <?= $isSuccess ? 'role="status" aria-live="polite"' : 'role="alert" aria-live="assertive"' ?>>
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>

                <div class="auth-secondary-action">
                    <?php if ($isSuccess): ?>
                        <a class="auth-button auth-button--primary" href="/auth/login.php">Sign in</a>
                    <?php else: ?>
                        <a class="auth-button auth-button--secondary" href="/auth/login.php">Return to sign in</a>
                    <?php endif; ?>
                </div>
            </section>
        </main>

        <?php require BASE_PATH . '/auth/views/partials/auth-footer.php'; ?>
    </div>
</body>

</html>