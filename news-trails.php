<?php

define('BASE_PATH', __DIR__);
$theme_experiment_enabled = true;

require_once BASE_PATH . "/core/___modules.php";
require_once BASE_PATH . '/core/community_trail_privacy.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = _pdo_or_null();
//$currentUser = current_user() ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;

$editorEmails = sn_community_trail_editor_emails();

$communitySharingEnabled = false;
if ($pdo && $currentUserId) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_community_trail_sharing') {
        if (!hash_equals(sn_community_trail_csrf_token(), (string) ($_POST['csrf_token'] ?? ''))) {
            http_response_code(403);
            exit('Invalid request token.');
        }

        sn_set_community_trail_sharing(
            $pdo,
            (int) $currentUserId,
            ($_POST['community_trail_sharing'] ?? '') === '1'
        );

        header('Location: /news-trails.php?base=community&sharing_updated=1');
        exit;
    }

    $communitySharingEnabled = sn_community_trail_sharing_enabled($pdo, (int) $currentUserId);
}

$activeBase = $_GET['base'] ?? 'all';

$allowedBases = ['all', 'personal', 'editors', 'community'];

if (!in_array($activeBase, $allowedBases, true)) {
    $activeBase = 'all';
}

$isFilteredView = $activeBase !== 'all';

$personalLimit = $isFilteredView ? 18 : 6;
$editorLimit = $isFilteredView ? 18 : 6;
$communityLimit = $isFilteredView ? 24 : 9;

// $fullHistory drops the 2-month SQL window for personal/editors; community always keeps it.
function fetchTrails(
    PDO $pdo,
    string $base,
    ?int $currentUserId,
    array $editorEmails,
    bool $fullHistory = false,
    ?int $limit = null,
    int $offset = 0
): array {
    $params = [
        ':min_records' => 3,
    ];

    $windowed = !($fullHistory && $base !== 'community');
    $win = static fn(string $col): string => $windowed ? "AND {$col} >= NOW() - INTERVAL '2 months'" : '';
    $paging = $limit !== null ? 'LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset) : '';

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

        $conditions[] = 'u.community_trail_sharing IS TRUE';

        $where = !empty($conditions)
            ? 'WHERE ' . implode(' AND ', $conditions)
            : '';
    }

    $sql = "
        WITH activity_raw AS (
            SELECT
                user_id,
                (viewed_at - INTERVAL '4 hours')::date AS trail_date,
                'reading' AS activity_type,
                url AS item_url
            FROM user_reading_history
            WHERE deleted_at IS NULL
            {$win('viewed_at')}
            AND url IS NOT NULL
            AND url <> ''

            UNION ALL

            SELECT
                user_id,
                (saved_at - INTERVAL '4 hours')::date AS trail_date,
                'saved' AS activity_type,
                headline_url AS item_url
            FROM user_saved_headlines
            WHERE deleted_at IS NULL
            {$win('saved_at')}
            AND headline_url IS NOT NULL
            AND headline_url <> ''

            UNION ALL

            SELECT
                user_id,
                (created_at - INTERVAL '4 hours')::date AS trail_date,
                'search' AS activity_type,
                '/search.php?q=' || replace(query, ' ', '+') ||
                '&range=' || COALESCE(range, 'all') ||
                '&mode=' || COALESCE(mode, 'classic') ||
                '&deep_dive=' || COALESCE(params_json->>'deep_dive', '') ||
                '&high_signal=' || COALESCE(params_json->>'high_signal', '') AS item_url
            FROM user_search_history
            WHERE deleted_at IS NULL
            {$win('created_at')}
            AND shuffle_session_uuid IS NULL
            AND query IS NOT NULL
            AND query <> ''

            UNION ALL

            SELECT
                ss.user_id,
                (ss.created_at - INTERVAL '4 hours')::date AS trail_date,
                'shuffle' AS activity_type,
                CASE
                    WHEN ss.source_context = 'search_results'
                        THEN '/search.php?shuffle_session=' || ss.id::text
                    WHEN ss.source_context = 'browse_news_modal'
                        THEN '/browse-news.php?shuffle_session_id=' || ss.id::text
                    ELSE NULL
                END AS item_url
            FROM shuffle_sessions ss
            WHERE ss.deleted_at IS NULL
            {$win('ss.created_at')}
            AND ss.source_context IN ('search_results', 'browse_news_modal')
        ),

        activity AS (
            SELECT DISTINCT ON (user_id, trail_date, LOWER(TRIM(item_url)))
                user_id,
                trail_date,
                activity_type,
                item_url
            FROM activity_raw
            WHERE item_url IS NOT NULL
            AND item_url <> ''
            ORDER BY user_id, trail_date, LOWER(TRIM(item_url)), activity_type
        )

        SELECT
            u.id AS user_id,
            u.public_trail_key,
            u.display_name,
            a.trail_date,
            COUNT(*) AS total_records,
            COUNT(*) FILTER (WHERE a.activity_type = 'reading') AS reading_count,
            COUNT(*) FILTER (WHERE a.activity_type = 'saved') AS saved_count,
            COUNT(*) FILTER (WHERE a.activity_type = 'search') AS search_count,
            COUNT(*) FILTER (WHERE a.activity_type = 'shuffle') AS shuffle_count,
            COUNT(*) OVER () AS total_trails
        FROM activity a
        JOIN users u ON u.id = a.user_id
        {$where}
        GROUP BY u.id, u.public_trail_key, u.display_name, a.trail_date
        HAVING COUNT(*) >= :min_records
        ORDER BY a.trail_date DESC, total_records DESC, u.id ASC
        {$paging}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchTrailTotal(PDO $pdo, string $base, ?int $currentUserId, array $editorEmails): int
{
    $rows = fetchTrails($pdo, $base, $currentUserId, $editorEmails, true, 1, 0);

    return (int) ($rows[0]['total_trails'] ?? 0);
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
$hasMore = ['personal' => false, 'editors' => false, 'community' => false];

$trailsPerPage = 18;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalPages = 1;

// Filtered views: full collection, newest-first, paginated. Main page: windowed shuffled preview.
function loadTrailSection(
    PDO $pdo,
    string $base,
    ?int $currentUserId,
    array $editorEmails,
    bool $isFilteredView,
    int $previewLimit,
    int $perPage,
    int &$page,
    int &$totalPages,
    array &$hasMore
): array {
    if ($base === 'personal' && !$currentUserId) {
        return [];
    }

    $total = fetchTrailTotal($pdo, $base, $currentUserId, $editorEmails);

    if ($isFilteredView) {
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);

        return fetchTrails($pdo, $base, $currentUserId, $editorEmails, true, $perPage, ($page - 1) * $perPage);
    }

    $preview = selectTrailCards(fetchTrails($pdo, $base, $currentUserId, $editorEmails), $previewLimit);
    $hasMore[$base] = $total > count($preview);

    return $preview;
}

if ($activeBase === 'all' || $activeBase === 'personal') {
    $personalTrails = loadTrailSection($pdo, 'personal', $currentUserId, $editorEmails, $isFilteredView, $personalLimit, $trailsPerPage, $page, $totalPages, $hasMore);
}

if ($activeBase === 'all' || $activeBase === 'editors') {
    $editorTrails = loadTrailSection($pdo, 'editors', $currentUserId, $editorEmails, $isFilteredView, $editorLimit, $trailsPerPage, $page, $totalPages, $hasMore);
}

if ($activeBase === 'all' || $activeBase === 'community') {
    $communityTrails = loadTrailSection($pdo, 'community', $currentUserId, $editorEmails, $isFilteredView, $communityLimit, $trailsPerPage, $page, $totalPages, $hasMore);
}

function trailPageUrl(string $base, int $page): string
{
    return '/news-trails.php?' . http_build_query(['base' => $base, 'page' => $page]);
}

function renderTrailPagination(string $base, int $page, int $totalPages): void
{
    if ($totalPages <= 1) {
        return;
    }

    $radius = 2;
    $startPage = max(1, $page - $radius);
    $endPage = min($totalPages, $page + $radius);
    $h = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
    <nav class="sn-archive-pagination mt-4" aria-label="News Trails pagination">
        <ul class="pagination justify-content-center flex-wrap">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link"
                    href="<?= $page > 1 ? $h(trailPageUrl($base, $page - 1)) : '#' ?>"
                    aria-label="Previous"
                    <?= $page > 1 ? 'data-loading' : 'tabindex="-1" aria-disabled="true"' ?>>
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <?php if ($startPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $h(trailPageUrl($base, 1)) ?>" data-loading>1</a>
                </li>
                <?php if ($startPage > 2): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $h(trailPageUrl($base, $p)) ?>" data-loading><?= $p ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $h(trailPageUrl($base, $totalPages)) ?>" data-loading><?= $totalPages ?></a>
                </li>
            <?php endif; ?>

            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link"
                    href="<?= $page < $totalPages ? $h(trailPageUrl($base, $page + 1)) : '#' ?>"
                    aria-label="Next"
                    <?= $page < $totalPages ? 'data-loading' : 'tabindex="-1" aria-disabled="true"' ?>>
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
<?php
}

function renderTrailCard(array $trail, string $base): void
{
    $url = '/news-trail-player.php?' . http_build_query([
        'base' => $base,
        'trail_user' => $trail['public_trail_key'],
        'trail_date' => $trail['trail_date'],
    ]);

    $firstName = sn_trail_first_name($trail['display_name'] ?? '');
?>

    <div class="col-md-6 col-xl-4 mb-3">
        <div class="trail-card h-100 p-3 border rounded bg-white shadow-sm">
            <div class="small text-muted mb-1">
                <?= htmlspecialchars(ucfirst($base), ENT_QUOTES, 'UTF-8') ?> Trail
            </div>

            <h3 class="trail-card-name mb-1">
                <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>’s Trail
            </h3>

            <div class="trail-card-date text-muted mb-3">
                <?= htmlspecialchars(date('F j, Y', strtotime($trail['trail_date'])), ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div class="trail-meta small mb-3">
                <div class="trail-meta-total"><?= (int) $trail['total_records'] ?> records</div>
                <div class="trail-meta-activities">
                    <strong><?= (int) $trail['reading_count'] ?></strong> reads ·
                    <strong><?= (int) $trail['saved_count'] ?></strong> saved ·
                    <strong><?= (int) $trail['search_count'] ?></strong> searches ·
                    <strong><?= (int) $trail['shuffle_count'] ?></strong> shuffles
                </div>
            </div>

            <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="trail-card-action btn btn-green btn-sm" data-loading>
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
    <!-- Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
    <link id="dark-theme" href="/assets/css/dark.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/dark.css'); ?>" rel="stylesheet" />
    <link id="dark-typography-theme" href="/assets/css/dark-typography.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/dark-typography.css'); ?>" rel="stylesheet" />

    <script src="/assets/js/dark-theme.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/dark-theme.js'); ?>"></script>

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

        .trail-card {
            display: flex;
            flex-direction: column;
        }

        .trail-card-name {
            color: #212529;
            font-size: 1.0625rem;
            font-weight: 600;
            line-height: 1.35;
        }

        .trail-card-date {
            font-size: 0.875rem;
        }

        .trail-meta-total {
            color: #343a40;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.125rem;
        }

        .trail-meta-activities {
            color: #6c757d;
            font-size: 0.8125rem;
            line-height: 1.5;
        }

        .trail-meta-activities strong {
            color: #495057;
            font-weight: 600;
        }

        .trail-card-action {
            align-self: flex-start;
            margin-top: auto;
        }

        .editor-trails-section {
            background-color: #f1f8f7;
        }

        .editor-trails-heading .fa-newspaper {
            color: #00bfa6;
        }

        .sn-archive-pagination .pagination {
            gap: 0.2rem;
        }

        .sn-archive-pagination .page-link {
            border-radius: 999px;
            min-width: 42px;
            text-align: center;
            font-weight: 600;
        }

        .sn-archive-pagination .page-item.active .page-link {
            box-shadow: 0 4px 14px rgba(0, 0, 0, .12);
        }
    </style>

</head>

<body id="page-top" class="auth-page account-page news-trails-page">

    <!-- Top nav-->
    <?php require_once BASE_PATH . '/views/partials/___topnav_product.php'; ?>

    <main class="container py-5">

        <header class="sn-page-header sn-page-header--no-toolbar">
            <h1>News Trails</h1>
            <p>Move through grouped news journeys built from reading history, saved headlines, searches, and shuffles.</p>
        </header>

        <?php if ($activeBase === 'all' || $activeBase === 'personal'): ?>
            <section class="mb-5">

                <h2 class="h5 mb-3"><i class="fa-solid fa-user mr-2"></i> Personal Trails</h2>

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

                <?php if ($isFilteredView): ?>
                    <?php renderTrailPagination('personal', $page, $totalPages); ?>
                <?php elseif ($currentUserId && $hasMore['personal']): ?>
                    <a href="/news-trails.php?base=personal" class="small">
                        View more personal trails
                    </a>
                <?php endif; ?>

            </section>
        <?php endif; ?>

        <?php if ($activeBase === 'all' || $activeBase === 'editors'): ?>
            <section class="editor-trails-section p-4 mb-5">
                <h2 class="editor-trails-heading h5 mb-3"><i class="fa-solid fa-newspaper mr-2"></i> Editor Trails</h2>
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
                <?php if ($isFilteredView): ?>
                    <?php renderTrailPagination('editors', $page, $totalPages); ?>
                <?php elseif ($hasMore['editors']): ?>
                    <a href="/news-trails.php?base=editors" class="small">View more editor trails</a>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($activeBase === 'all' || $activeBase === 'community'): ?>
            <section class="mb-5">
                <h2 class="h5 mb-3"><i class="fa-solid fa-users mr-2"></i> Community Trails</h2>
                <div class="border rounded p-3 mb-4 bg-light">
                    <h3 class="h6 mb-2">Community Trail sharing</h3>
                    <p class="text-muted mb-3">
                        When enabled, other visitors can see your Community Trails, including activity dates and counts, opened or saved article titles and links, search queries, and shuffle activity. A portion of your display name may appear. Your account email is not shown as a profile field, but information you include in a search query or article link may be visible.
                    </p>
                    <?php if ($currentUserId): ?>
                        <?php if (isset($_GET['sharing_updated'])): ?>
                            <div class="alert alert-success py-2" role="status">Your Community Trail sharing preference was updated.</div>
                        <?php endif; ?>
                        <form method="post" action="/news-trails.php?base=community" class="mb-0">
                            <input type="hidden" name="action" value="update_community_trail_sharing" />
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(sn_community_trail_csrf_token(), ENT_QUOTES, 'UTF-8') ?>" />
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="communityTrailSharing" name="community_trail_sharing" value="1" <?= $communitySharingEnabled ? 'checked' : '' ?> />
                                <label class="form-check-label" for="communityTrailSharing">Allow my Community Trails to be visible to other visitors</label>
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm">Save preference</button>
                        </form>
                    <?php else: ?>
                        <p class="mb-0"><a href="/auth/login.php">Sign in</a> to manage whether your activity can appear in Community Trails.</p>
                    <?php endif; ?>
                </div>
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
                <?php if ($isFilteredView): ?>
                    <?php renderTrailPagination('community', $page, $totalPages); ?>
                <?php elseif ($hasMore['community']): ?>
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