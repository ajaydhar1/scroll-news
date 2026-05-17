<?php

define('BASE_PATH', dirname(__DIR__));

require_once __DIR__ . '/../auth/includes/require_auth.php';

$userEmail = $_SESSION['user_email'] ?? '';
$displayName = $_SESSION['display_name'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

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
                                    <p class="text-muted mb-2">
                                        Manage your email, password, and sign-in settings.
                                    </p>
                                    <ul class="text-muted mb-3">
                                        <li>Email address</li>
                                        <li>Password</li>
                                        <li>Email verification status</li>
                                        <li>Active sessions/devices</li>
                                        <li>Remembered devices</li>
                                    </ul>
                                    <a href="/account/profile.php" class="btn btn-green btn-sm">Manage Account</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h2 class="h5 mb-2">
                                        <i class="fa-solid fa-wave-square mr-2"></i>Your Activity
                                        <span class="badge badge-light ml-1">Coming soon</span>
                                    </h2>
                                    <p class="text-muted mb-2">
                                        Saved articles, searches, and your reading history.
                                    </p>
                                    <ul class="text-muted mb-3">
                                        <li>Saved headlines</li>
                                        <li>Reading history</li>
                                        <li>Saved searches</li>
                                        <li>Shuffle history</li>
                                        <li>Your news pattern</li>
                                    </ul>
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
                                    <p class="text-muted mb-2">
                                        Trace AI-powered trails across related stories and narrative frames.
                                    </p>
                                    <ul class="text-muted mb-3">
                                        <li>Personal Trails</li>
                                        <li>Editor Trails</li>
                                        <li>Community Trails</li>
                                        <li>Signal paths through the news</li>
                                    </ul>
                                    <button class="btn btn-outline-secondary btn-sm" disabled>Coming Soon</button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h2 class="h5 mb-2">
                                        <i class="fa-solid fa-tower-broadcast mr-2"></i>Publisher Tools
                                        <span class="badge badge-light ml-1">Coming soon</span>
                                    </h2>
                                    <p class="text-muted mb-2">
                                        Manage feeds, publisher verification, content rights, and distribution tools.
                                    </p>
                                    <ul class="text-muted mb-3">
                                        <li>Publisher verification</li>
                                        <li>RSS feed submissions</li>
                                        <li>Article removals</li>
                                        <li>Creator profile</li>
                                    </ul>
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