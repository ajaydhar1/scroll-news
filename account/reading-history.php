<?php

define('BASE_PATH', dirname(__DIR__));

require_once __DIR__ . '/../auth/includes/require_auth.php';
require_once __DIR__ . '/../auth/includes/auth_db.php';

$userEmail = $_SESSION['user_email'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

$perPage = 25;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$totalItems = 0;
$totalPages = 1;
$historyItems = [];

$q = trim($_GET['q'] ?? '');
$hasSearch = $q !== '';

$searchSql = $hasSearch
    ? " AND (title ILIKE :search OR source ILIKE :search)"
    : "";

try {
    $countSql = "
        SELECT COUNT(*)
        FROM (
            SELECT DISTINCT ON (url) url
            FROM user_reading_history
            WHERE user_id = :user_id
            AND deleted_at IS NULL
            $searchSql
            ORDER BY url, viewed_at DESC
        ) latest
    ";

    $pdo = auth_db();

    $countStmt = $pdo->prepare($countSql);
    $countStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

    if ($hasSearch) {
        $countStmt->bindValue(':search', '%' . $q . '%', PDO::PARAM_STR);
    }

    $countStmt->execute();
    $totalItems = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalItems / $perPage));

    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    $sql = "
        SELECT *
        FROM (
            SELECT DISTINCT ON (url)
                id,
                url,
                title,
                source,
                image,
                pub_date,
                kind,
                viewed_at,
                article_id,
                rss_item_id
            FROM user_reading_history
            WHERE user_id = :user_id
            AND deleted_at IS NULL
            $searchSql
            ORDER BY url, viewed_at DESC
        ) latest
        ORDER BY viewed_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

    if ($hasSearch) {
        $stmt->bindValue(':search', '%' . $q . '%', PDO::PARAM_STR);
    }

    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $historyItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Reading history page error: ' . $e->getMessage());
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function niceDate($value): string
{
    if (!$value) {
        return '';
    }

    $timestamp = strtotime((string)$value);

    if (!$timestamp) {
        return '';
    }

    return date('D M d, Y g:i A', $timestamp);
}

function pageUrl(int $page): string
{
    $params = ['page' => $page];

    if (trim($_GET['q'] ?? '') !== '') {
        $params['q'] = trim($_GET['q']);
    }

    return '/account/reading-history.php?' . http_build_query($params);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Review your Scroll News reading history and return to articles you recently opened." />
    <meta name="author" content="Scroll News" />
    <title>Reading History — Scroll News</title>

    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://scrollnews.ai/account/reading-history.php" />
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />

    <link href="/assets/css/styles.css?v=<?= filemtime(BASE_PATH . '/assets/css/styles.css') ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?= filemtime(BASE_PATH . '/assets/css/custom.css') ?>" rel="stylesheet" />
    <link href="/assets/css/auth.css?v=<?= filemtime(BASE_PATH . '/assets/css/auth.css') ?>" rel="stylesheet" />
    <link href="/assets/css/account.css?v=<?= filemtime(BASE_PATH . '/assets/css/account.css') ?>" rel="stylesheet" />

    <style>
        .reading-history-header {
            background:
                radial-gradient(circle at top left, rgba(32, 170, 89, 0.18), transparent 32%),
                radial-gradient(circle at bottom right, rgba(255, 255, 255, 0.06), transparent 28%),
                linear-gradient(135deg, #1d2125 0%, #2a2f35 55%, #343a40 100%);

            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 1rem;
            padding: 1.5rem;

            color: #f8f9fa;
        }

        .reading-history-header .text-muted {
            color: rgba(255, 255, 255, 0.72) !important;
        }

        .reading-history-header h1 {
            color: #ffffff;
        }

        .reading-history-icon {
            width: 48px;
            height: 48px;
            border-radius: 999px;
            background: rgba(32, 170, 89, 0.12);
            color: #198754;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .history-card {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .history-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1.25rem rgba(0, 0, 0, 0.08) !important;
        }

        .history-thumb {
            width: 96px;
            height: 72px;
            border-radius: .75rem;
            object-fit: cover;
            background: #eef1f0;
            flex: 0 0 auto;
        }

        .history-thumb-placeholder {
            width: 96px;
            height: 72px;
            border-radius: .75rem;
            background: #eef1f0;
            color: #8a9490;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            font-size: 1.25rem;
        }

        .history-title {
            color: #212529;
            text-decoration: none;
        }

        .history-title:hover {
            color: #198754;
            text-decoration: none;
        }

        .badge-newsroom {
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
            border: 1px solid rgba(25, 135, 84, 0.18);
        }

        .badge-publisher {
            background: rgba(108, 117, 125, 0.12);
            color: #495057;
            border: 1px solid rgba(108, 117, 125, 0.18);
        }

        .history-empty-state {
            border: 1px dashed rgba(0, 0, 0, 0.15);
            border-radius: 1rem;
            background: #fbfcfc;
            padding: 2rem;
        }

        .history-pagination .page-link {
            color: #198754;
        }

        .history-pagination .page-item.active .page-link {
            background-color: #198754;
            border-color: #198754;
            color: #fff;
        }

        @media (max-width: 575.98px) {
            .history-item-layout {
                flex-direction: column;
            }

            .history-thumb,
            .history-thumb-placeholder {
                width: 100%;
                height: 150px;
            }
        }

        .reading-history-search .form-control {
            background: #fff;
            border: 1px solid #ced4da;
            color: #495057;
        }
    </style>
</head>

<body id="page-top" class="auth-page account-page">

    <div class="page">

        <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

        <div class="auth-shell container my-5">
            <div class="auth-card card border-0 rounded-3">
                <div class="card-body">

                    <div class="reading-history-header mb-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start">
                            <div class="d-flex align-items-start">
                                <div class="reading-history-icon mr-3">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-1">Your Activity</p>
                                    <h1 class="h3 mb-2">Reading History</h1>
                                    <p class="text-muted mb-0">
                                        Articles you’ve opened across Scroll News. Publisher and Newsroom views are saved here.
                                    </p>
                                    <?php if ($hasSearch): ?>
                                        <div class="small text-muted mt-2">
                                            Search results for <strong>"<?= h($q) ?>"</strong>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mt-3 mt-md-0 text-md-right">
                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                    <i class="fa-solid fa-trash-can mr-1"></i> Clear History
                                </button>
                                <div class="text-muted small mt-2">
                                    <?php if ($hasSearch): ?>
                                        Showing <?= number_format($totalItems) ?> result<?= $totalItems === 1 ? '' : 's' ?>
                                    <?php else: ?>
                                        <?= number_format($totalItems) ?> saved article<?= $totalItems === 1 ? '' : 's' ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="get" class="reading-history-search mb-4">
                        <div class="input-group">
                            <input
                                type="search"
                                name="q"
                                class="form-control"
                                placeholder="Search your reading history..."
                                value="<?= h($_GET['q'] ?? '') ?>">
                            <div class="input-group-append">
                                <button class="btn btn-dark" type="submit" data-loading>Search</button>
                            </div>
                        </div>
                    </form>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="/account/" class="text-muted">← Back to Account</a>
                        <a href="/newsroom.php" class="btn btn-green btn-sm">
                            <i class="fa-solid fa-play mr-1"></i> Stumble
                        </a>
                    </div>

                    <?php if (empty($historyItems)): ?>

                        <div class="history-empty-state text-center">
                            <div class="reading-history-icon mb-3">
                                <i class="fa-solid fa-book-open-reader"></i>
                            </div>

                            <?php if ($hasSearch): ?>

                                <h2 class="h5 mb-2">No matching articles found</h2>

                                <p class="text-muted mb-4">
                                    No reading history matched "<strong><?= h($q) ?></strong>". Try a different keyword or clear the search.
                                </p>

                                <a href="/account/reading-history.php" class="btn btn-outline-secondary btn-sm" data-loading>
                                    Clear Search
                                </a>

                            <?php else: ?>

                                <h2 class="h5 mb-2">No reading history yet</h2>

                                <p class="text-muted mb-4">
                                    Open a few articles from the Newsroom or Search page, and they’ll appear here automatically.
                                </p>

                                <a href="/newsroom.php" class="btn btn-green btn-sm">
                                    Start Reading
                                </a>

                            <?php endif; ?>

                        </div>

                    <?php else: ?>

                        <div class="history-list">
                            <?php foreach ($historyItems as $item): ?>
                                <?php
                                $kind = $item['kind'] ?? 'external';
                                $isAnalyze = $kind === 'analyze';
                                $badgeText = $isAnalyze ? 'Newsroom view' : 'Publisher view';
                                $badgeClass = $isAnalyze ? 'badge-newsroom' : 'badge-publisher';
                                $buttonText = $isAnalyze ? 'Open in Newsroom' : 'Open Publisher Site';
                                $targetAttrs = $isAnalyze ? '' : ' target="_blank" rel="noopener noreferrer"';
                                ?>

                                <article class="history-card card border-0 shadow-sm mb-3">
                                    <div class="card-body">
                                        <div class="history-item-layout d-flex">
                                            <?php if (!empty($item['image'])): ?>
                                                <img
                                                    src="<?= h($item['image']) ?>"
                                                    alt=""
                                                    class="history-thumb mr-3 mb-3 mb-sm-0"
                                                    loading="lazy">
                                            <?php else: ?>
                                                <div class="history-thumb-placeholder mr-3 mb-3 mb-sm-0">
                                                    <i class="fa-regular fa-newspaper"></i>
                                                </div>
                                            <?php endif; ?>

                                            <div class="flex-grow-1">
                                                <div class="mb-2">
                                                    <span class="badge <?= h($badgeClass) ?> mr-2">
                                                        <?= h($badgeText) ?>
                                                    </span>

                                                    <?php if (!empty($item['source'])): ?>
                                                        <span class="text-muted small">
                                                            <?= h($item['source']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <h2 class="h5 mb-2">
                                                    <a class="history-title" href="<?= h($item['url']) ?>" <?= $targetAttrs ?>>
                                                        <?= h($item['title'] ?: 'Untitled article') ?>
                                                    </a>
                                                </h2>

                                                <div class="text-muted small mb-3">
                                                    <?php if (!empty($item['pub_date'])): ?>
                                                        <span>Published: <?= h(niceDate($item['pub_date'])) ?></span>
                                                        <span class="mx-1">•</span>
                                                    <?php endif; ?>

                                                    <span>Viewed: <?= h(niceDate($item['viewed_at'])) ?></span>
                                                </div>

                                                <div class="d-flex flex-wrap align-items-center">
                                                    <a class="btn btn-green btn-sm mr-2 mb-2" href="<?= h($item['url']) ?>" <?= $targetAttrs ?>>
                                                        <?= h($buttonText) ?>
                                                    </a>

                                                    <?php if (!empty($item['rss_item_id']) || !empty($item['article_id'])): ?>
                                                        <span class="text-muted small mb-2">
                                                            Linked to Scroll News article data
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>

                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav class="history-pagination mt-4" aria-label="Reading history pagination">
                                <ul class="pagination justify-content-center flex-wrap">

                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= h(pageUrl(max(1, $page - 1))) ?>" aria-label="Previous">
                                            «
                                        </a>
                                    </li>

                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    ?>

                                    <?php if ($startPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= h(pageUrl(1)) ?>">1</a>
                                        </li>
                                        <?php if ($startPage > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= h(pageUrl($i)) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($endPage < $totalPages): ?>
                                        <?php if ($endPage < $totalPages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= h(pageUrl($totalPages)) ?>"><?= $totalPages ?></a>
                                        </li>
                                    <?php endif; ?>

                                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= h(pageUrl(min($totalPages, $page + 1))) ?>" aria-label="Next">
                                            »
                                        </a>
                                    </li>

                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php endif; ?>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="/" class="text-muted" data-loading>← Back to Scroll News</a>
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