<?php

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

        <!-- Content area -->
        <div class="auth-shell container my-5">
            <div class="auth-card card border-0">
                <div class="card-body p-5">

                    <header class="text-center mb-4">
                        <img
                            src="/assets/img/play-green.png"
                            alt="Scroll News"
                            class="auth-logo mb-3"
                            style="height: 52px; width: auto;">

                        <h1 class="h3 mb-2">
                            Create your Scroll News account
                        </h1>

                        <p class="text-muted mb-0">
                            Save articles, build news trails, and keep your reading history connected.
                        </p>
                    </header>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger auth-alert" role="alert">
                            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="/auth/handlers/register_handler.php">

                        <div class="form-group">
                            <label for="name">Name</label>

                            <input
                                type="text"
                                class="form-control"
                                id="name"
                                name="display_name"
                                value="<?= htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="name"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email address</label>

                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="email"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>

                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                autocomplete="new-password"
                                minlength="<?= $config['min_password_length'] ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">
                                Confirm password
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="confirm_password"
                                name="password_confirm"
                                autocomplete="new-password"
                                minlength="<?= $config['min_password_length'] ?>"
                                required>
                        </div>

                        <button type="submit" class="btn btn-green btn-block">
                            Create Account
                        </button>

                    </form>

                    <hr class="my-4">

                    <p class="text-center mb-0">
                        Already have an account?
                        <a href="/auth/login.php">Sign in</a>
                    </p>

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