<?php
$theme_experiment_enabled = true;
$successMessage = null;
$errorMessage = null;

if (isset($_GET['reset'])) {
    $successMessage = 'If an account exists for that email address, a password reset link has been sent.';
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_email':
            $errorMessage = 'Please enter a valid email address.';
            break;

        case 'invalid_token':
        case 'invalid_or_expired_token':
            $errorMessage = 'This password reset link is invalid or has expired. Please request a new one.';
            break;

        case 'server_error':
            $errorMessage = 'Something went wrong. Please try again.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script src="/assets/js/dark-theme.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/dark-theme.js'); ?>"></script>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <!-- Basics -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <meta
        name="description"
        content="Reset your Scroll News password and regain access to your saved articles, news trails, and reading history." />

    <meta name="author" content="Scroll News" />

    <title>Reset Password — Scroll News</title>

    <!-- Canonical + favicon -->
    <link rel="canonical" href="https://scrollnews.ai/auth/forgot-password" />
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <!-- Open Graph -->
    <meta property="og:type" content="website" />

    <meta
        property="og:url"
        content="https://scrollnews.ai/auth/forgot-password" />

    <meta
        property="og:title"
        content="Reset Password — Scroll News" />

    <meta
        property="og:description"
        content="Reset your Scroll News password and regain access to your saved articles, news trails, and reading history." />

    <meta
        property="og:image"
        content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />

    <meta
        name="twitter:url"
        content="https://scrollnews.ai/auth/forgot-password" />

    <meta
        name="twitter:title"
        content="Reset Password — Scroll News" />

    <meta
        name="twitter:description"
        content="Reset your Scroll News password and regain access to your saved articles, news trails, and reading history." />

    <meta
        name="twitter:image"
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
                    <p class="auth-eyebrow">Account recovery</p>
                    <h1 id="auth-title">Reset your password</h1>
                    <p class="auth-intro">Enter your email and we’ll send you a link to reset your password.</p>
                </header>

                <?php if ($successMessage): ?>
                    <div class="auth-message auth-message--success" role="status" aria-live="polite">
                        <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="auth-message auth-message--error" role="alert" aria-live="assertive">
                        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form class="auth-form" method="post" action="/auth/handlers/forgot_password_handler.php">
                    <div class="auth-field">
                        <label class="auth-label" for="email">Email address</label>
                        <input
                            class="auth-input"
                            type="email"
                            id="email"
                            name="email"
                            autocomplete="email"
                            inputmode="email"
                            required>
                    </div>

                    <button class="auth-button auth-button--primary" type="submit">Send Reset Link</button>
                </form>

                <div class="auth-secondary-action">
                    <span>Remembered your password?</span>
                    <a class="auth-button auth-button--secondary" href="/auth/login.php">Sign in</a>
                </div>
            </section>
        </main>

        <?php require BASE_PATH . '/auth/views/partials/auth-footer.php'; ?>
    </div>
</body>

</html>