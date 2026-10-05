<?php

define('BASE_PATH', dirname(__DIR__));
$theme_experiment_enabled = true;

require_once __DIR__ . '/../auth/includes/require_auth.php';
require_once __DIR__ . '/../auth/includes/auth_db.php';
require_once BASE_PATH . '/core/community_trail_privacy.php';

$userEmail = $_SESSION['user_email'] ?? '';
$displayName = $_SESSION['display_name'] ?? '';
$userId = $_SESSION['user_id'] ?? null;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_community_trail_sharing') {
    if (!hash_equals(sn_community_trail_csrf_token(), (string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    sn_set_community_trail_sharing(
        auth_db(),
        (int) $userId,
        ($_POST['community_trail_sharing'] ?? '') === '1'
    );

    header('Location: /account/?trail_sharing_updated=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $displayName = trim($_POST['display_name'] ?? '');

    if ($displayName !== '') {

        $pdo = auth_db();

        $stmt = $pdo->prepare("
            UPDATE users
            SET display_name = :display_name
            WHERE id = :user_id
        ");

        $stmt->execute([
            ':display_name' => $displayName,
            ':user_id' => $userId,
        ]);

        $_SESSION['display_name'] = $displayName;

        header('Location: /account/?updated=1');
        exit;
    }
}

$verifiedPublisher = null;
$verifiedPublisherStmt = auth_db()->prepare("\n    SELECT p.id, p.domain, p.name\n    FROM publishers p\n    INNER JOIN publisher_users pu ON pu.publisher_id = p.id\n    WHERE pu.user_id = :user_id\n      AND pu.status = 'verified'\n    ORDER BY p.id\n    LIMIT 1\n");
$verifiedPublisherStmt->execute([
    ':user_id' => $userId,
]);
$verifiedPublisher = $verifiedPublisherStmt->fetch(PDO::FETCH_ASSOC) ?: null;

$memberSinceStmt = auth_db()->prepare('SELECT created_at FROM users WHERE id = :user_id LIMIT 1');
$memberSinceStmt->execute([':user_id' => $userId]);
$memberSince = $memberSinceStmt->fetchColumn();
$communityTrailSharingEnabled = sn_community_trail_sharing_enabled(auth_db(), (int) $userId);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Manage your Scroll News account, saved headlines, reading history, and News Trails." />
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
    <!-- Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
    <link id="dark-typography-theme" href="/assets/css/dark-typography.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/dark-typography.css'); ?>" rel="stylesheet" />

    <script src="/assets/js/dark-theme.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/dark-theme.js'); ?>"></script>

    <link href="/assets/css/auth.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/auth.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/account.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/account.css'); ?>" rel="stylesheet" />

</head>

<body id="page-top" class="auth-page account-page">

    <div class="page">

        <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

        <div class="auth-shell container my-5">
            <div class="auth-card card border-0 rounded-3">
                <div class="card-body">

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
                        <div class="badges gap-2 mb-4 mt-1">
                            <span class="badge bg-success">
                                ✅ Verified Member
                            </span>

                            <span class="badge bg-dark">
                                📅 Member Since <?= $memberSince ? htmlspecialchars(date('F Y', strtotime($memberSince)), ENT_QUOTES, 'UTF-8') : 'Unknown' ?>
                            </span>

                            <span class="badge bg-primary">
                                📰 News Explorer
                            </span>
                        </div>
                    </header>

                    <div class="alert alert-success auth-alert" role="alert">
                        Your account settings and reader activity tools are ready to use. Publisher tools are available to verified publishers.
                    </div>

                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <h2 class="h5 mb-2">
                                <i class="fa-solid fa-id-card mr-2"></i>Profile Information
                            </h2>

                            <p class="text-muted mb-3">
                                Update the name associated with your Scroll News account.
                            </p>

                            <?php if (isset($_GET['updated'])): ?>
                                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                                    <strong>Success!</strong> Your display name has been updated.
                                    <button type="button" class="close" data-dismiss="alert">
                                        <span>&times;</span>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <form method="post" class="profile-information-form">
                                <div class="row align-items-end profile-information-row">
                                    <div class="col-md-8">
                                        <label class="small text-muted mb-1">
                                            Display Name
                                        </label>

                                        <input
                                            type="text"
                                            class="form-control"
                                            name="display_name"
                                            value="<?= htmlspecialchars($currentUser['display_name'] ?? '') ?>"
                                            maxlength="100">
                                    </div>

                                    <div class="col-md-4">
                                        <button type="submit"
                                            class="btn btn-primary btn-block" data-loading>
                                            Save Changes
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
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
                                        <li><strong>Email address:</strong> <?= trim($currentUser['email'] ?? '') ?></li>
                                        <li><strong>Email verification status:</strong> ✅</li>

                                        <?php
                                        $lastSession = null;

                                        $pdo = auth_db();

                                        $stmt = $pdo->prepare("
                                                SELECT created_at, user_agent
                                                FROM user_sessions
                                                WHERE user_id = :user_id
                                                ORDER BY created_at DESC
                                                LIMIT 1
                                            ");
                                        $stmt->execute([
                                            ':user_id' => $userId,
                                        ]);
                                        $lastSession = $stmt->fetch(PDO::FETCH_ASSOC);

                                        function h($value): string
                                        {
                                            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                                        }

                                        function summarize_user_agent(?string $userAgent): string
                                        {
                                            if (!$userAgent) {
                                                return 'Unknown device';
                                            }

                                            $device = 'Desktop';
                                            $browser = 'Browser';

                                            if (stripos($userAgent, 'iPhone') !== false) {
                                                $device = 'iPhone';
                                            } elseif (stripos($userAgent, 'iPad') !== false) {
                                                $device = 'iPad';
                                            } elseif (stripos($userAgent, 'Android') !== false) {
                                                $device = 'Android';
                                            } elseif (stripos($userAgent, 'Windows') !== false) {
                                                $device = 'Windows';
                                            } elseif (stripos($userAgent, 'Mac OS X') !== false) {
                                                $device = 'Mac';
                                            }

                                            if (stripos($userAgent, 'Edg/') !== false) {
                                                $browser = 'Edge';
                                            } elseif (stripos($userAgent, 'Chrome/') !== false && stripos($userAgent, 'Safari/') !== false) {
                                                $browser = 'Chrome';
                                            } elseif (stripos($userAgent, 'Firefox/') !== false) {
                                                $browser = 'Firefox';
                                            } elseif (stripos($userAgent, 'Safari/') !== false) {
                                                $browser = 'Safari';
                                            }

                                            return $browser . ' on ' . $device;
                                        }
                                        ?>
                                        <li>
                                            <strong>Last login:</strong>
                                            <?php if ($lastSession): ?>
                                                <?= h(date('M j, Y g:i A', strtotime($lastSession['created_at']))) ?>
                                                <span class="text-muted">
                                                    — <?= h(summarize_user_agent($lastSession['user_agent'] ?? null)) ?>
                                                </span>
                                            <?php else: ?>
                                                Unknown
                                            <?php endif; ?>
                                        </li>
                                        <li>
                                            <strong>Member since:</strong>
                                            <?= $memberSince
                                                ? h(date('M j, Y', strtotime($memberSince)))
                                                : 'Unknown' ?>
                                        </li>
                                        <li><a href="/auth/change-password.php" class="account-link">Change password</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h2 class="h5 mb-2">
                                        <i class="fa-solid fa-wave-square mr-2"></i>Your Activity
                                        <span class="badge badge-dark ml-1">New</span>
                                    </h2>
                                    <p class="text-muted mb-2">
                                        Saved articles, searches, and your reading history.
                                    </p>
                                    <ul class="text-muted mb-3">
                                        <li><a href="/account/saved-headlines.php" class="account-link" data-loading>Saved headlines</a></li>
                                        <li><a href="/account/reading-history.php" class="account-link" data-loading>Reading history</a></li>
                                        <li><a href="/account/search-history.php" class="account-link" data-loading>Search history</a></li>
                                        <li><a href="/account/shuffle-history.php" class="account-link" data-loading>Shuffle history</a></li>
                                        <li><a href="/control-room.php" class="account-link">Your news pattern</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h2 class="h5 mb-2">
                                        <i class="fa-solid fa-route mr-2"></i>News Trails
                                        <span class="badge badge-dark ml-1">New</span>
                                    </h2>
                                    <p class="text-muted mb-2">
                                        Trace AI-powered trails across related stories and narrative frames.
                                    </p>
                                    <ul class="text-muted mb-3">
                                        <li><a href="/news-trails.php?base=personal" class="account-link" data-loading>Personal Trails</a></li>
                                        <li><a href="/news-trails.php?base=editors" class="account-link" data-loading>Editor Trails</a></li>
                                        <li><a href="/news-trails.php?base=community" class="account-link" data-loading>Community Trails</a></li>
                                        <li><a href="/news-trails.php" class="account-link" data-loading>Signal paths through the news</a></li>
                                    </ul>
                                    <div class="border rounded p-3 bg-light">
                                        <h3 class="h6 mb-2">Community Trail sharing</h3>
                                        <p class="text-muted small mb-3">
                                            When enabled, other visitors can see your Community Trails, including activity dates and counts, opened or saved article titles and links, search queries, and shuffle activity. A portion of your display name may appear. Your account email is not shown as a profile field, but information you include in a search query or article link may be visible.
                                        </p>
                                        <?php if (isset($_GET['trail_sharing_updated'])): ?>
                                            <div class="alert alert-success py-2" role="status">Your Community Trail sharing preference was updated.</div>
                                        <?php endif; ?>
                                        <form method="post" action="/account/" class="mb-0">
                                            <input type="hidden" name="action" value="update_community_trail_sharing" />
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(sn_community_trail_csrf_token(), ENT_QUOTES, 'UTF-8') ?>" />
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="accountCommunityTrailSharing" name="community_trail_sharing" value="1" <?= $communityTrailSharingEnabled ? 'checked' : '' ?> />
                                                <label class="form-check-label" for="accountCommunityTrailSharing">Allow my Community Trails to be visible to other visitors</label>
                                            </div>
                                            <button type="submit" class="btn btn-outline-primary btn-sm">Save preference</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h2 class="h5 mb-2">
                                        <i class="fa-solid fa-tower-broadcast mr-2"></i>Publisher Tools
                                        <span class="badge badge-success ml-1">Core tools available</span>
                                    </h2>
                                    <p class="text-muted mb-2">
                                        Verify publisher access, edit your publisher profile, and preview ingested articles.
                                    </p>
                                    <ul class="text-muted mb-3">
                                        <li>
                                            <a href="/publisher-pro/" class="account-link">
                                                <strong>Publisher Pro</strong>
                                            </a>
                                            <span class="badge badge-success ml-1">New</span>
                                        </li>
                                        <li><a href="/account/publisher-verification.php" class="account-link">Publisher verification</a></li>
                                        <?php if ($verifiedPublisher): ?>
                                            <li>
                                                <a href="/account/publisher-dashboard.php?publisher_id=<?= (int) $verifiedPublisher['id']; ?>" class="account-link">
                                                    Publisher Dashboard
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <li>Feed onboarding and corrections/removal support through Publisher Pro</li>
                                        <li>Self-service feed submissions and article controls are not available yet</li>
                                        <li>Public publisher profile pages are not available yet</li>
                                    </ul>
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