<?php

$config = require __DIR__ . '/../config/auth_config.php';

$successMessages = [
    'registered' => 'Your account has been created. Please check your email to verify your account before signing in.',
];

$errorMessages = [
    'invalid_login' => 'The email or password you entered is incorrect.',
    'email_not_verified' => 'Please verify your email address before signing in.',
    'login_failed' => 'Something went wrong while signing you in. Please try again.',
];

$successKey = isset($_GET['registered']) ? 'registered' : null;
$successMessage = $successMessages[$successKey] ?? null;

$errorKey = $_GET['error'] ?? null;
$errorMessage = $errorMessages[$errorKey] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
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
            <div class="auth-card card border-0 shadow-lg rounded-3">
                <div class="card-body p-5">

                    <header class="text-center mb-4">
                        <img
                            src="/assets/img/play-green.png"
                            alt="Scroll News"
                            class="auth-logo mb-3"
                            style="height: 52px; width: auto;">
                        <h1 class="h3 mb-2">Sign in to Scroll News</h1>
                        <p class="text-muted mb-0">
                            Save your history, revisit trails, and build your personal news archive.
                        </p>
                    </header>

                    <?php if ($successMessage): ?>
                        <div class="alert alert-success auth-alert" role="alert">
                            <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger auth-alert" role="alert">
                            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="/auth/handlers/login_handler.php">
                        <div class="form-group">
                            <label for="email">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="<?= $config['min_password_length'] ?>" required>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember_me">
                                <label class="form-check-label" for="remember">
                                    Keep me signed in
                                </label>
                            </div>

                            <a href="/auth/forgot-password.php">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn btn-green btn-block">
                            Sign In
                        </button>
                    </form>

                    <hr class="my-4">

                    <p class="text-center mb-0">
                        New to Scroll News?
                        <a href="/auth/register.php">Create an account</a>
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