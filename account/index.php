<?php

define('BASE_PATH', dirname(__DIR__));

// UI-only placeholder for now.
// Later: require auth/session helper here.
// require_once __DIR__ . '/../auth/require_auth.php';

$userEmail = 'ajay@example.com'; // Temporary UI placeholder.

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Manage your Scroll News account, saved articles, reading history, and personalization settings." />
    <meta name="author" content="Scroll News" />
    <title>Account — Scroll News</title>

    <link rel="canonical" href="https://scrollnews.ai/account/" />
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://scrollnews.ai/account/" />
    <meta property="og:title" content="Account — Scroll News" />
    <meta property="og:description" content="Manage your Scroll News account and personal news experience." />
    <meta property="og:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Account — Scroll News" />
    <meta name="twitter:description" content="Manage your Scroll News account and personal news experience." />
    <meta name="twitter:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />

    <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/auth.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/auth.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/account.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/account.css'); ?>" rel="stylesheet" />

</head>

<body id="page-top" class="auth-page account-page">

    <div class="page">

        <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

        <div class="auth-shell container my-5">
            <div class="auth-card card border-0 rounded-3">
                <div class="card-body p-5">

                    <header class="text-center mb-4">
                        <img
                            src="/assets/img/play-green.png"
                            alt="Scroll News"
                            class="auth-logo mb-3"
                            style="height: 52px; width: auto;">

                        <h1 class="h3 mb-2">Your Scroll News Account</h1>
                        <p class="text-muted mb-0">
                            You’re signed in as
                            <strong><?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?></strong>
                        </p>
                    </header>

                    <div class="alert alert-success auth-alert" role="alert">
                        Signed in securely. Account features are being connected.
                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h2 class="h5 mb-2">
                                        <i class="fa-solid fa-user mr-2"></i>Account & Security
                                    </h2>
                                    <p class="text-muted mb-3">
                                        Manage your email, password, and sign-in settings.
                                    </p>
                                    <a href="/account/profile.php" class="btn btn-green btn-sm">Manage Account</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h2 class="h5 mb-2">
                                        <i class="fa-solid fa-bookmark mr-2"></i>Saved Articles
                                        <span class="badge badge-light ml-1">Coming soon</span>
                                    </h2>
                                    <p class="text-muted mb-3">
                                        Save articles and come back to them later.
                                    </p>
                                    <button class="btn btn-outline-secondary btn-sm" disabled>Coming Soon</button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h2 class="h5 mb-2">
                                        <i class="fa-solid fa-clock-rotate-left mr-2"></i>Reading History
                                        <span class="badge badge-light ml-1">Coming soon</span>
                                    </h2>
                                    <p class="text-muted mb-3">
                                        Revisit articles, searches, and analysis pages you viewed.
                                    </p>
                                    <button class="btn btn-outline-secondary btn-sm" disabled>Coming Soon</button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h2 class="h5 mb-2">
                                        <i class="fa-solid fa-route mr-2"></i>News Trails
                                        <span class="badge badge-light ml-1">Coming soon</span>
                                    </h2>
                                    <p class="text-muted mb-3">
                                        Build personal trails across related stories and narrative frames.
                                    </p>
                                    <button class="btn btn-outline-secondary btn-sm" disabled>Coming Soon</button>
                                </div>
                            </div>
                        </div>

                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="/" class="text-muted">← Back to Scroll News</a>
                        <a href="/auth/logout.php" class="btn btn-outline-secondary btn-sm">Sign Out</a>
                    </div>

                </div>
            </div>
        </div>

        <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>

    </div>

    <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>

</body>

</html>