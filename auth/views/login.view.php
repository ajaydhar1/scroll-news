<?php

$theme_experiment_enabled = true;

$config = require __DIR__ . '/../config/auth_config.php';

$successMessages = [
    'registered' => 'Your account has been created. Please check your email to verify your account before signing in.',
    'reset_success' => 'Your password has been reset. You can now log in.',
    'logged_out' => 'You have been signed out successfully.',
];

$errorMessages = [
    'invalid_login' => 'The email or password you entered is incorrect.',
    'email_not_verified' => 'Please verify your email address before signing in.',
    'login_failed' => 'Something went wrong while signing you in. Please try again.',
    'login_required' => 'Please sign in to continue.',
    'session_expired' => 'Your session has expired. Please sign in again.',
];

$successKey = null;

if (isset($_GET['registered'])) {
    $successKey = 'registered';
}

if (($_GET['reset'] ?? '') === 'success') {
    $successKey = 'reset_success';
}

if (isset($_GET['logged_out'])) {
    $successKey = 'logged_out';
}

$successMessage = $successMessages[$successKey] ?? null;

$errorKey = $_GET['error'] ?? null;
$errorMessage = $errorMessages[$errorKey] ?? null;

$email = $_SESSION['old']['email'] ?? '';
$rememberMe = $_SESSION['old']['remember_me'] ?? false;

unset($_SESSION['old']);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Apply the saved theme before stylesheets paint the page. -->
    <script src="/assets/js/dark-theme.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/dark-theme.js'); ?>"></script>

    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <!-- Basics -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description"
        content="Sign in to Scroll News to save articles, revisit news trails, and build your personal news archive." />
    <meta name="author" content="Scroll News" />
    <title>Sign In — Scroll News</title>

    <!-- Canonical + favicon -->
    <link rel="canonical" href="https://scrollnews.ai/auth/login" />
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <!-- Open Graph -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://scrollnews.ai/auth/login" />
    <meta property="og:title" content="Sign In — Scroll News" />
    <meta property="og:description"
        content="Access your Scroll News account to save articles, track stories, and build your personal news archive." />
    <meta property="og:image"
        content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" content="https://scrollnews.ai/auth/login" />
    <meta name="twitter:title" content="Sign In — Scroll News" />
    <meta name="twitter:description"
        content="Access your Scroll News account to save articles, track stories, and build your personal news archive." />
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
                    <p class="auth-eyebrow">Your account</p>
                    <h1 id="auth-title">Welcome back</h1>
                    <p class="auth-intro">Sign in to save stories and pick up where you left off.</p>
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

                <form class="auth-form" method="post" action="/auth/handlers/login_handler.php">
                    <div class="auth-field">
                        <label class="auth-label" for="email">Email address</label>
                        <input
                            class="auth-input"
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                            autocomplete="username"
                            inputmode="email"
                            required>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="password">Password</label>
                        <input
                            class="auth-input"
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            minlength="<?= $config['min_password_length'] ?>"
                            required>
                    </div>

                    <div class="auth-form-options">
                        <label class="auth-remember" for="remember">
                            <input
                                class="auth-checkbox"
                                type="checkbox"
                                id="remember"
                                name="remember_me"
                                value="1"
                                <?= $rememberMe ? 'checked' : '' ?>>
                            <span>Keep me signed in</span>
                        </label>
                        <a class="auth-link auth-forgot-link" href="/auth/forgot-password.php">Forgot password?</a>
                    </div>

                    <button class="auth-button auth-button--primary" type="submit">Sign In</button>
                </form>

                <div class="auth-secondary-action">
                    <span>New to Scroll News?</span>
                    <a class="auth-button auth-button--secondary" href="/auth/register.php">Create an account</a>
                </div>
            </section>
        </main>

        <?php require BASE_PATH . '/auth/views/partials/auth-footer.php'; ?>
    </div>
</body>

</html>