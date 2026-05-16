<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <!-- Basics -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <meta
        name="description"
        content="Create a new password for your Scroll News account and regain access to your saved articles, news trails, and reading history." />

    <meta name="author" content="Scroll News" />

    <title>Reset Password — Scroll News</title>

    <!-- Canonical + favicon -->
    <link rel="canonical" href="https://scrollnews.ai/auth/reset-password" />
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <!-- Open Graph -->
    <meta property="og:type" content="website" />

    <meta
        property="og:url"
        content="https://scrollnews.ai/auth/reset-password" />

    <meta
        property="og:title"
        content="Reset Password — Scroll News" />

    <meta
        property="og:description"
        content="Create a new password for your Scroll News account and regain access to your saved articles, news trails, and reading history." />

    <meta
        property="og:image"
        content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />

    <meta
        name="twitter:url"
        content="https://scrollnews.ai/auth/reset-password" />

    <meta
        name="twitter:title"
        content="Reset Password — Scroll News" />

    <meta
        name="twitter:description"
        content="Create a new password for your Scroll News account and regain access to your saved articles, news trails, and reading history." />

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
            <div class="auth-card card border-0 shadow-lg rounded-3">
                <div class="card-body p-5">

                    <header class="text-center mb-4">
                        <img
                            src="/assets/img/play-green.png"
                            alt="Scroll News"
                            class="auth-logo mb-3"
                            style="height: 52px; width: auto;">

                        <h1 class="h3 mb-2">
                            Create a new password
                        </h1>

                        <p class="text-muted mb-0">
                            Enter a new password to regain access to your Scroll News account.
                        </p>
                    </header>

                    <form method="post" action="/auth/handlers/reset-password.handler.php">

                        <input
                            type="hidden"
                            name="token"
                            value="<?php echo htmlspecialchars($token ?? ''); ?>"
                        >

                        <div class="form-group">
                            <label for="password">New password</label>

                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                autocomplete="new-password"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">
                                Confirm new password
                            </label>

                            <input
                                type="password"
                                class="form-control"
                                id="confirm_password"
                                name="confirm_password"
                                autocomplete="new-password"
                                required>
                        </div>

                        <button type="submit" class="btn btn-green btn-block">
                            Reset Password
                        </button>

                    </form>

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