<?php
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/auth/includes/require_auth.php';
require_once BASE_PATH . '/auth/includes/auth_db.php';

$pdo = auth_db();

if (!$currentUser) {
    header('Location: /auth/login.php');
    exit;
}

$userId = (int) $currentUser['id'];
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_one') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE user_search_history
                SET deleted_at = NOW()
                WHERE id = :id
                  AND user_id = :user_id
                  AND deleted_at IS NULL
            ");

            $stmt->execute([
                ':id' => $id,
                ':user_id' => $userId,
            ]);
        }

        header('Location: /account/search-history.php');
        exit;
    }

    if ($action === 'clear_all') {
        $stmt = $pdo->prepare("
            UPDATE user_search_history
            SET deleted_at = NOW()
            WHERE user_id = :user_id
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':user_id' => $userId,
        ]);

        header('Location: /account/search-history.php');
        exit;
    }
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$total = 0;
$searches = [];

if (!$pdo) {
    $errorMsg = 'Database connection not available.';
} else {
    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM user_search_history
        WHERE user_id = :user_id
          AND deleted_at IS NULL
    ");

    $countStmt->execute([
        ':user_id' => $userId,
    ]);

    $total = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT id, query, mode, \"range\", params_json, created_at, shuffle_session_uuid
        FROM user_search_history
        WHERE user_id = :user_id
          AND deleted_at IS NULL
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $searches = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalPages = max(1, (int) ceil($total / $perPage));

function build_search_url(?string $paramsJson, string $fallbackQuery): string
{
    $params = [];

    if ($paramsJson) {
        $decoded = json_decode($paramsJson, true);

        if (is_array($decoded)) {
            $params = $decoded;
        }
    }

    if (empty($params['q']) && $fallbackQuery !== '') {
        $params['q'] = $fallbackQuery;
    }

    return '/search.php?' . http_build_query($params) . '&context=search_history';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <title>Search History — Scroll News</title>

    <meta name="description" content="Review, rerun, and manage searches from your Scroll News account history." />
    <meta name="author" content="Scroll News" />

    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://scrollnews.ai/account/search-history.php" />
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
    </style>
</head>

<body id="page-top" class="auth-page account-page">

    <div class="page">

        <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

        <div class="auth-shell container my-5">
            <div class="auth-card card border-0 rounded-3">
                <div class="card-body">

                    <div class="reading-history-header mb-4 d-flex justify-content-between align-items-start flex-wrap">
                        <div>
                            <div class="reading-history-icon mb-2">
                                <i class="fas fa-search"></i>
                            </div>
                            <h1 class="mb-2">Search History</h1>
                            <p class="text-muted mb-0">
                                Review, rerun, or delete searches from your Scroll News account.
                            </p>
                        </div>

                        <?php if ($total > 0): ?>
                            <form method="post" class="mt-3 mt-md-0" onsubmit="return confirm('Clear all search history? This cannot be undone.');">
                                <input type="hidden" name="action" value="clear_all">
                                <button type="submit" class="btn btn-outline-light btn-sm">
                                    Clear all history
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if ($errorMsg): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="text-muted small mb-1">Total Searches</div>
                                    <div class="h4 mb-0"><?= number_format($total) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="text-muted small mb-1">Sort Order</div>
                                    <div class="h5 mb-0">Newest First</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="text-muted small mb-1">Privacy</div>
                                    <div class="h5 mb-0">Private to your account</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="/account/" class="text-muted">← Back to Account</a>
                        <a href="/search.php" class="btn btn-green btn-sm">
                            <i class="fa-solid fa-search mr-1"></i> Search
                        </a>
                    </div>

                    <?php if (empty($searches)): ?>
                        <div class="history-empty-state mt-4">
                            <div class="mb-3">
                                <div class="search-history-icon mx-auto">
                                    <i class="fas fa-search"></i>
                                </div>
                            </div>

                            <h4 class="mb-2">No search history yet</h4>

                            <p class="text-muted mb-4">
                                Searches you perform while signed in will appear here for quick access later.
                            </p>

                            <a href="/search.php" class="btn btn-green">
                                <i class="fas fa-search mr-1"></i>
                                Search Scroll News
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group mt-4">
                            <?php foreach ($searches as $item): ?>
                                <?php
                                $searchUrl = build_search_url($item['params_json'] ?? null, $item['query'] ?? '');
                                $mode = $item['mode'] ?: 'classic';
                                $range = $item['range'] ?: 'all';
                                ?>

                                <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="mb-2 mb-sm-0">
                                        <a class="history-title" href="<?= htmlspecialchars($searchUrl, ENT_QUOTES, 'UTF-8') ?>" data-loading>
                                            <strong><?= htmlspecialchars($item['query'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        </a>

                                        <div class="text-muted small mt-1">
                                            <?= htmlspecialchars(ucfirst($mode), ENT_QUOTES, 'UTF-8') ?>
                                            ·
                                            <?= htmlspecialchars($range === 'all' ? 'All time' : $range, ENT_QUOTES, 'UTF-8') ?>
                                            ·
                                            <?= htmlspecialchars($item['created_at'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center">

                                        <?php if (!empty($item['shuffle_session_uuid'])): ?>
                                            <a href="/search.php?shuffle_session=<?= urlencode($item['shuffle_session_uuid']) ?>" class="btn btn-sm btn-outline-success mr-2" data-loading>
                                                🔀 Open Shuffle
                                            </a>
                                        <?php endif; ?>

                                        <a href="<?= htmlspecialchars($searchUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-success mr-2" data-loading>
                                            Run search
                                        </a>

                                        <form method="post" onsubmit="return confirm('Delete this search from your history?');">
                                            <input type="hidden" name="action" value="delete_one">
                                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="/account/search-history.php?page=<?= $i ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
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