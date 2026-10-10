<?php

$theme_experiment_enabled = true;

$config = require __DIR__ . '/../config/auth_config.php';

$errorMessages = [
    'invalid_email' => 'Please enter a valid email address.',
    'password_too_short' => 'Your password must be at least ' . $config['min_password_length'] . ' characters long.',
    'passwords_do_not_match' => 'The passwords you entered do not match.',
    'email_exists' => 'An account with that email already exists.',
    'registration_failed' => 'Something went wrong while creating your account. Please try again.',
];

$errorKey = $_GET['error'] ?? null;
$errorMessage = $errorMessages[$errorKey] ?? null;

$name = $_SESSION['old']['display_name'] ?? '';
$email = $_SESSION['old']['email'] ?? '';

unset($_SESSION['old']);

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
        content="Create a Scroll News account to save articles, build news trails, and keep your reading history connected." />

    <meta name="author" content="Scroll News" />

    <title>Create Account — Scroll News</title>

    <!-- Canonical + favicon -->
    <link rel="canonical" href="https://scrollnews.ai/auth/register" />
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <!-- Open Graph -->
    <meta property="og:type" content="website" />

    <meta
        property="og:url"
        content="https://scrollnews.ai/auth/register" />

    <meta
        property="og:title"
        content="Create Account — Scroll News" />

    <meta
        property="og:description"
        content="Create your Scroll News account to save articles, track stories, and build your personal news archive." />

    <meta
        property="og:image"
        content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />

    <meta
        name="twitter:url"
        content="https://scrollnews.ai/auth/register" />

    <meta
        name="twitter:title"
        content="Create Account — Scroll News" />

    <meta
        name="twitter:description"
        content="Create your Scroll News account to save articles, track stories, and build your personal news archive." />

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
                    <p class="auth-eyebrow">Create your account</p>
                    <h1 id="auth-title">Join Scroll News</h1>
                    <p class="auth-intro">Save articles, build news trails, and keep your reading history connected.</p>
                </header>

                <?php if ($errorMessage): ?>
                    <div class="auth-message auth-message--error" role="alert" aria-live="assertive">
                        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form class="auth-form" method="post" action="/auth/handlers/register_handler.php">
                    <div class="auth-field">
                        <label class="auth-label" for="name">Name</label>
                        <input
                            class="auth-input"
                            type="text"
                            id="name"
                            name="display_name"
                            value="<?= htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            autocomplete="name"
                            required>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="email">Email address</label>
                        <input
                            class="auth-input"
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            autocomplete="email"
                            required>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="password">Password</label>
                        <input
                            class="auth-input"
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            minlength="<?= $config['min_password_length'] ?>"
                            aria-describedby="registration-password-hint"
                            required>
                        <p class="auth-field-hint" id="registration-password-hint">Use at least <?= (int) $config['min_password_length'] ?> characters.</p>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="confirm_password">Confirm password</label>
                        <input
                            class="auth-input"
                            type="password"
                            id="confirm_password"
                            name="password_confirm"
                            autocomplete="new-password"
                            minlength="<?= $config['min_password_length'] ?>"
                            required>
                    </div>

                    <button class="auth-button auth-button--primary" type="submit">Create Account</button>
                </form>

                <div class="auth-secondary-action">
                    <span>Already have an account?</span>
                    <a class="auth-button auth-button--secondary" href="/auth/login.php">Sign in</a>
                </div>
            </section>
        </main>

        <?php require BASE_PATH . '/auth/views/partials/auth-footer.php'; ?>
    </div>
</body>

</html>