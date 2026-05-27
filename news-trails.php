<?php

define('BASE_PATH', __DIR__);

require_once BASE_PATH . "/core/___modules.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = _pdo_or_null();
//$currentUser = current_user() ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;

$editorEmails = [
    'ajaytest2@sharklasers.com',
];

$activeBase = $_GET['base'] ?? 'all';

$allowedBases = ['all', 'personal', 'editors', 'community'];

if (!in_array($activeBase, $allowedBases, true)) {
    $activeBase = 'all';
}

$isFilteredView = $activeBase !== 'all';

$personalLimit = $isFilteredView ? 18 : 6;
$editorLimit = $isFilteredView ? 18 : 6;
$communityLimit = $isFilteredView ? 24 : 9;

function fetchTrails(PDO $pdo, string $base, ?int $currentUserId, array $editorEmails): array
{
    $params = [
        ':min_records' => 3,
    ];

    $where = '';

    if ($base === 'personal') {
        if (!$currentUserId) {
            return [];
        }

        $where = 'WHERE u.id = :current_user_id';
        $params[':current_user_id'] = $currentUserId;
    } elseif ($base === 'editors') {
        $placeholders = [];

        foreach ($editorEmails as $index => $email) {
            $key = ':editor_email_' . $index;
            $placeholders[] = $key;
            $params[$key] = strtolower($email);
        }

        $where = 'WHERE LOWER(u.email) IN (' . implode(', ', $placeholders) . ')';
    } else {
        $conditions = [];
        $placeholders = [];

        foreach ($editorEmails as $index => $email) {
            $key = ':community_editor_email_' . $index;
            $placeholders[] = $key;
            $params[$key] = strtolower($email);
        }

        if (!empty($placeholders)) {
            $conditions[] = 'LOWER(u.email) NOT IN (' . implode(', ', $placeholders) . ')';
        }

        if ($currentUserId) {
            $conditions[] = 'u.id <> :current_user_id';
            $params[':current_user_id'] = $currentUserId;
        }

        $where = !empty($conditions)
            ? 'WHERE ' . implode(' AND ', $conditions)
            : '';
    }

    $sql = "
        WITH activity AS (
            SELECT user_id, (created_at - INTERVAL '4 hours')::date AS trail_date, 'reading' AS activity_type
            FROM user_reading_history
            WHERE created_at >= NOW() - INTERVAL '2 months'

            UNION ALL

            SELECT user_id, (created_at - INTERVAL '4 hours')::date AS trail_date, 'saved' AS activity_type
            FROM user_saved_headlines
            WHERE created_at >= NOW() - INTERVAL '2 months'

            UNION ALL

            SELECT user_id, (created_at - INTERVAL '4 hours')::date AS trail_date, 'search' AS activity_type
            FROM user_search_history
            WHERE created_at >= NOW() - INTERVAL '2 months'

            UNION ALL

            SELECT user_id, (created_at - INTERVAL '4 hours')::date AS trail_date, 'shuffle' AS activity_type
            FROM shuffle_sessions
            WHERE created_at >= NOW() - INTERVAL '2 months'
        )
        SELECT
            u.id AS user_id,
            u.public_trail_key,
            COALESCE(NULLIF(u.display_name, ''), 'Scroll News Reader') AS display_name,
            a.trail_date,
            COUNT(*) AS total_records,
            COUNT(*) FILTER (WHERE a.activity_type = 'reading') AS reading_count,
            COUNT(*) FILTER (WHERE a.activity_type = 'saved') AS saved_count,
            COUNT(*) FILTER (WHERE a.activity_type = 'search') AS search_count,
            COUNT(*) FILTER (WHERE a.activity_type = 'shuffle') AS shuffle_count
        FROM activity a
        JOIN users u ON u.id = a.user_id
        {$where}
        GROUP BY u.id, u.public_trail_key, u.display_name, a.trail_date
        HAVING COUNT(*) >= :min_records
        ORDER BY a.trail_date DESC, total_records DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function selectTrailCards(array $trails, int $limit = 6): array
{
    if (empty($trails)) {
        return [];
    }

    $latestDate = max(array_column($trails, 'trail_date'));
    $latestWindowStart = date('Y-m-d', strtotime($latestDate . ' -14 days'));

    $latestWindow = array_values(array_filter($trails, function ($trail) use ($latestWindowStart) {
        return $trail['trail_date'] >= $latestWindowStart;
    }));

    shuffle($latestWindow);

    $selected = array_slice($latestWindow, 0, $limit);

    usort($selected, function ($a, $b) {
        return strcmp($b['trail_date'], $a['trail_date']);
    });

    return $selected;
}

$personalTrails = [];
$editorTrails = [];
$communityTrails = [];

if ($activeBase === 'all' || $activeBase === 'personal') {
    $personalTrails = selectTrailCards(
        fetchTrails($pdo, 'personal', $currentUserId, $editorEmails),
        $personalLimit
    );
}

if ($activeBase === 'all' || $activeBase === 'editors') {
    $editorTrails = selectTrailCards(
        fetchTrails($pdo, 'editors', $currentUserId, $editorEmails),
        $editorLimit
    );
}

if ($activeBase === 'all' || $activeBase === 'community') {
    $communityTrails = selectTrailCards(
        fetchTrails($pdo, 'community', $currentUserId, $editorEmails),
        $communityLimit
    );
}

function renderTrailCard(array $trail, string $base): void
{
    $url = '/news-trail-player.php?' . http_build_query([
        'base' => $base,
        'trail_user' => $trail['public_trail_key'],
        'trail_date' => $trail['trail_date'],
    ]);

    $displayName = trim($trail['display_name'] ?? '');

    $firstName = 'Reader';

    if ($displayName !== '') {
        $parts = preg_split('/\s+/', $displayName);
        $firstName = $parts[0] ?? 'Reader';
    }
?>

    <div class="col-md-6 col-xl-4 mb-3">
        <div class="trail-card h-100 p-3 border rounded bg-white shadow-sm">
            <div class="small text-muted mb-1">
                <?= htmlspecialchars(ucfirst($base), ENT_QUOTES, 'UTF-8') ?> Trail
            </div>

            <h3 class="h6 mb-2">
                <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>’s Trail
            </h3>

            <div class="text-muted small mb-3">
                <?= htmlspecialchars(date('F j, Y', strtotime($trail['trail_date'])), ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div class="trail-meta small mb-3">
                <?= (int) $trail['total_records'] ?> records ·
                <?= (int) $trail['reading_count'] ?> reads ·
                <?= (int) $trail['saved_count'] ?> saved ·
                <?= (int) $trail['search_count'] ?> searches ·
                <?= (int) $trail['shuffle_count'] ?> shuffles
            </div>

            <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-green btn-sm">
                <i class="fa-solid fa-play mr-1"></i> Open Trail
            </a>
        </div>
    </div>

<?php
}

function renderEmptyState(
    string $title,
    string $message,
    string $icon = 'fa-solid fa-route'
): void {
?>

    <div class="col-12">
        <div class="trail-empty-state p-4 border rounded bg-light text-center">

            <div class="trail-empty-icon mb-2">
                <i class="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i>
            </div>

            <h3 class="h6 mb-2">
                <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
            </h3>

            <p class="text-muted mb-0">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </p>

        </div>
    </div>

<?php
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <meta
        name="description"
        content="Explore News Trails on Scroll News — grouped reading sessions built from saved headlines, searches, reading history, and shuffles." />

    <meta name="author" content="Scroll News" />

    <title>News Trails – Scroll News</title>

    <meta name="robots" content="index,follow">

    <!-- Favicon-->
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <link
        rel="canonical"
        href="https://scrollnews.ai/news-trails.php" />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />

    <meta
        property="og:url"
        content="https://scrollnews.ai/news-trails.php" />

    <meta
        property="og:title"
        content="News Trails on Scroll News" />

    <meta
        property="og:description"
        content="Explore grouped news journeys built from reading history, saved headlines, searches, and shuffles across Scroll News." />

    <meta
        property="og:image"
        content="https://scrollnews.ai/assets/img/og/og-scrollnews-news-trails-1200x630.png" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />

    <meta
        name="twitter:url"
        content="https://scrollnews.ai/news-trails.php" />

    <meta
        name="twitter:title"
        content="News Trails on Scroll News" />

    <meta
        name="twitter:description"
        content="Explore reading sessions and grouped news journeys across Scroll News." />

    <meta
        name="twitter:image"
        content="https://scrollnews.ai/assets/img/og/og-scrollnews-news-trails-1200x630.png" />

    <!-- jQuery min-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <!-- Font Awesome icons (free version)-->
    <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous"></script>

    <!-- Google fonts-->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />

    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/auth.css?v=<?= filemtime(BASE_PATH . '/assets/css/auth.css') ?>" rel="stylesheet" />
    <link href="/assets/css/account.css?v=<?= filemtime(BASE_PATH . '/assets/css/account.css') ?>" rel="stylesheet" />

    <style>
        section {
            padding: 0 !important;
        }

        .trail-empty-icon {
            font-size: 2rem;
            color: #6c757d;
            opacity: 0.85;
        }
    </style>

</head>

<body id="page-top" class="auth-page account-page">

    <!-- Top nav-->
    <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

    <main class="container py-5">

        <header class="mb-5 text-center">
            <h1 class="h2 mb-2"><i class="fa-solid fa-route mr-1"></i> News Trails</h1>
            <p class="text-muted mb-0">
                Explore grouped news journeys built from reading, searches, saved headlines, and shuffles.
            </p>
        </header>

        <?php if ($activeBase === 'all' || $activeBase === 'personal'): ?>
            <section class="mb-5">

                <h2 class="h5 mb-3">Personal Trails</h2>

                <div class="row">

                    <?php if (!$currentUserId): ?>

                        <?php renderEmptyState(
                            'Sign in to create personal trails',
                            'Your saved headlines, searches, reading history, and shuffles can automatically form personal News Trails.',
                            'fa-solid fa-user-lock'
                        ); ?>

                    <?php elseif (!empty($personalTrails)): ?>

                        <?php foreach ($personalTrails as $trail): ?>
                            <?php renderTrailCard($trail, 'personal'); ?>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <?php renderEmptyState(
                            'No personal trails yet',
                            'Read, save, search, or shuffle a few items in one day to create your first trail.',
                            'fa-solid fa-user-clock'
                        ); ?>

                    <?php endif; ?>

                </div>

                <?php if ($currentUserId && count($personalTrails) >= $personalLimit): ?>
                    <a href="/news-trails.php?base=personal" class="small">
                        View more personal trails
                    </a>
                <?php endif; ?>

            </section>
        <?php endif; ?>

        <?php if ($activeBase === 'all' || $activeBase === 'editors'): ?>
            <section class="mb-5">
                <h2 class="h5 mb-3">Editor Trails</h2>
                <div class="row">
                    <?php if (!empty($editorTrails)): ?>
                        <?php foreach ($editorTrails as $trail): ?>
                            <?php renderTrailCard($trail, 'editors'); ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php renderEmptyState(
                            'No editor trails yet',
                            'Editor trails will appear here once selected editor accounts have enough activity.',
                            'fa-solid fa-newspaper'
                        ); ?>
                    <?php endif; ?>
                </div>
                <?php if (count($editorTrails) > 0): ?>
                    <a href="/news-trails.php?base=editors" class="small">View more editor trails</a>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($activeBase === 'all' || $activeBase === 'community'): ?>
            <section class="mb-5">
                <h2 class="h5 mb-3">Community Trails</h2>
                <div class="row">
                    <?php if (!empty($communityTrails)): ?>
                        <?php foreach ($communityTrails as $trail): ?>
                            <?php renderTrailCard($trail, 'community'); ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php renderEmptyState(
                            'No community trails yet',
                            'Community trails will appear once readers generate enough activity in a 24-hour window.',
                            'fa-solid fa-users'
                        ); ?>
                    <?php endif; ?>
                </div>
                <?php if (count($communityTrails) > 0): ?>
                    <a href="/news-trails.php?base=community" class="small">View more community trails</a>
                <?php endif; ?>
            </section>
        <?php endif; ?>

    </main>

    <!-- Footer-->
    <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>

    <!-- Modals-->
    <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

    <!-- Core JS (Bootstrap 4 requires jQuery first) -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" defer></script>

    <!-- Theme -->
    <script src="/assets/js/scripts.js" defer></script>
</body>

</html>