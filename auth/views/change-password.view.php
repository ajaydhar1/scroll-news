<?php

$config = require __DIR__ . '/../config/auth_config.php';

$theme_experiment_enabled = true;

$successMessages = [
    'changed' => 'Your password has been updated.',
];

$successKey = null;

if (isset($_GET['changed'])) {
    $successKey = 'changed';
}

$successMessage = $successMessages[$successKey] ?? null;

$errorMessage = null;

if (isset($_GET['error'])) {
    switch ($_GET['error']) {

        case 'login_required':
            $errorMessage = 'Please sign in to access this page.';
            break;

        case 'invalid_current_password':
            $errorMessage = 'Your current password is incorrect.';
            break;

        case 'password_too_short':
            $errorMessage = 'Your password must be at least ' . $config['min_password_length'] . ' characters long.';
            break;

        case 'password_mismatch':
            $errorMessage = 'Passwords do not match.';
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
        content="Update the password for your Scroll News account to keep your news history, saved articles, and account secure." />

    <meta name="author" content="Scroll News" />

    <title>Change Password — Scroll News</title>

    <!-- Canonical + favicon -->
    <link rel="canonical" href="https://scrollnews.ai/auth/change-password" />
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <!-- Open Graph -->
    <meta property="og:type" content="website" />

    <meta
        property="og:url"
        content="https://scrollnews.ai/auth/change-password" />

    <meta
        property="og:title"
        content="Change Password — Scroll News" />

    <meta
        property="og:description"
        content="Update the password for your Scroll News account to keep your news history, saved articles, and account secure." />

    <meta
        property="og:image"
        content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />

    <meta
        name="twitter:url"
        content="https://scrollnews.ai/auth/change-password" />

    <meta
        name="twitter:title"
        content="Change Password — Scroll News" />

    <meta
        name="twitter:description"
        content="Update the password for your Scroll News account to keep your news history, saved articles, and account secure." />

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
                    <p class="auth-eyebrow">Account security</p>
                    <h1 id="auth-title">Change your password</h1>
                    <p class="auth-intro">Update the password for your Scroll News account.</p>
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

                <form class="auth-form" method="post" action="/auth/handlers/change_password_handler.php">
                    <div class="auth-field">
                        <label class="auth-label" for="old_password">Current password</label>
                        <input
                            class="auth-input"
                            type="password"
                            id="old_password"
                            name="old_password"
                            autocomplete="current-password"
                            minlength="<?= $config['min_password_length'] ?>"
                            required>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="password">New password</label>
                        <input
                            class="auth-input"
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            minlength="<?= $config['min_password_length'] ?>"
                            required>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="confirm_password">Confirm new password</label>
                        <input
                            class="auth-input"
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            autocomplete="new-password"
                            minlength="<?= $config['min_password_length'] ?>"
                            required>
                    </div>

                    <button class="auth-button auth-button--primary" type="submit">Change Password</button>
                </form>

                <div class="auth-secondary-action">
                    <a class="auth-button auth-button--secondary" href="/account/">Back to your account</a>
                </div>
            </section>
        </main>

        <?php require BASE_PATH . '/auth/views/partials/auth-footer.php'; ?>
    </div>
</body>

</html>