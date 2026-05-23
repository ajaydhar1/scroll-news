<?php

define('BASE_PATH', dirname(__DIR__));

require_once __DIR__ . '/../auth/includes/require_auth.php';
require_once __DIR__ . '/../auth/includes/auth_db.php';

$userId = $_SESSION['user_id'] ?? null;

$perPage = 25;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$totalItems = 0;
$totalPages = 1;
$savedItems = [];

try {
    $pdo = auth_db();

    $countSql = "
        SELECT COUNT(*)
        FROM user_saved_headlines
        WHERE user_id = :user_id
          AND deleted_at IS NULL
    ";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute(['user_id' => $userId]);
    $totalItems = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalItems / $perPage));

    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    $sql = "
        SELECT
            id,
            headline_hash,
            headline_url,
            headline_title,
            source_slug,
            pub_date,
            saved_at
        FROM user_saved_headlines
        WHERE user_id = :user_id
          AND deleted_at IS NULL
        ORDER BY saved_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $savedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Saved headlines page error: ' . $e->getMessage());
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
    return '/account/saved-headlines.php?page=' . $page;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Review headlines you saved from Scroll News First Look." />
    <meta name="author" content="Scroll News" />
    <title>Saved Headlines — Scroll News</title>

    <link rel="canonical" href="https://scrollnews.ai/account/saved-headlines.php" />
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://scrollnews.ai/account/saved-headlines.php" />
    <meta property="og:title" content="Saved Headlines — Scroll News" />
    <meta property="og:description" content="Review headlines you saved from Scroll News First Look." />
    <meta property="og:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Saved Headlines — Scroll News" />
    <meta name="twitter:description" content="Review headlines you saved from Scroll News First Look." />
    <meta name="twitter:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />

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
        .saved-headlines-header {
            background:
                radial-gradient(circle at top left, rgba(32, 170, 89, 0.18), transparent 32%),
                radial-gradient(circle at bottom right, rgba(255, 255, 255, 0.06), transparent 28%),
                linear-gradient(135deg, #1d2125 0%, #2a2f35 55%, #343a40 100%);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 1rem;
            padding: 1.5rem;
            color: #f8f9fa;
        }

        .saved-headlines-header .text-muted {
            color: rgba(255, 255, 255, 0.72) !important;
        }

        .saved-headlines-header h1 {
            color: #ffffff;
        }

        .saved-headlines-icon {
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

        .saved-headline-card {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .saved-headline-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1.25rem rgba(0, 0, 0, 0.08) !important;
        }

        .saved-headline-title {
            color: #212529;
            text-decoration: none;
        }

        .saved-headline-title:hover {
            color: #198754;
            text-decoration: none;
        }

        .badge-first-look {
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
            border: 1px solid rgba(25, 135, 84, 0.18);
        }

        .saved-headlines-empty-state,
        .saved-headlines-import-banner {
            border: 1px dashed rgba(0, 0, 0, 0.15);
            border-radius: 1rem;
            background: #fbfcfc;
            padding: 2rem;
        }

        .saved-headlines-pagination .page-link {
            color: #198754;
        }

        .saved-headlines-pagination .page-item.active .page-link {
            background-color: #198754;
            border-color: #198754;
            color: #fff;
        }
    </style>
</head>

<body id="page-top" class="auth-page account-page">

    <div class="page">

        <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

        <div class="auth-shell container my-5">
            <div class="auth-card card border-0 rounded-3">
                <div class="card-body">

                    <div class="saved-headlines-header mb-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start">
                            <div class="d-flex align-items-start">
                                <div class="saved-headlines-icon mr-3">
                                    <i class="fa-solid fa-bookmark"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-1">Your Saves</p>
                                    <h1 class="h3 mb-2">Saved Headlines</h1>
                                    <p class="text-muted mb-0">
                                        Headlines you saved from the First Look panel.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 mt-md-0 text-md-right">
                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                    <i class="fa-solid fa-trash-can mr-1"></i> Clear Saved
                                </button>
                                <div class="text-muted small mt-2">
                                    <?= number_format($totalItems) ?> saved headline<?= $totalItems === 1 ? '' : 's' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="savedHeadlinesImportBanner" class="saved-headlines-import-banner mb-4 d-none">
                        <div class="d-flex flex-wrap justify-content-between align-items-center">
                            <div class="mb-3 mb-md-0">
                                <h2 class="h5 mb-2">Saved headlines found on this device</h2>
                                <p class="text-muted mb-0">
                                    You have <span id="savedHeadlinesLocalCount">0</span> local saved headline<span id="savedHeadlinesLocalPlural">s</span>.
                                    Add them to your account so they stay with your saved headlines.
                                </p>
                            </div>
                            <button id="savedHeadlinesImportBtn" class="btn btn-green btn-sm">
                                <i class="fa-solid fa-cloud-arrow-up mr-1"></i> Add to Account
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="/account/" class="text-muted">← Back to Account</a>
                        <a href="/#first-look" class="btn btn-green btn-sm">
                            <i class="fa-solid fa-newspaper mr-1"></i> First Look
                        </a>
                    </div>

                    <?php if (empty($savedItems)): ?>

                        <div class="saved-headlines-empty-state text-center">
                            <div class="saved-headlines-icon mb-3">
                                <i class="fa-regular fa-bookmark"></i>
                            </div>
                            <h2 class="h5 mb-2">No saved headlines yet</h2>
                            <p class="text-muted mb-4">
                                Save headlines from the First Look panel, and they’ll appear here.
                            </p>
                            <a href="/#first-look" class="btn btn-green btn-sm">
                                Go to First Look
                            </a>
                        </div>

                    <?php else: ?>

                        <div class="saved-headlines-list">
                            <?php foreach ($savedItems as $item): ?>
                                <article class="saved-headline-card card border-0 shadow-sm mb-3">
                                    <div class="card-body">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start">
                                            <div class="flex-grow-1 pr-md-3">
                                                <div class="mb-2">
                                                    <span class="badge badge-first-look mr-2">First Look</span>

                                                    <?php if (!empty($item['source_slug'])): ?>
                                                        <span class="text-muted small">
                                                            <?= h($item['source_slug']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <h2 class="h5 mb-2">
                                                    <a
                                                        class="saved-headline-title"
                                                        href="<?= h($item['headline_url']) ?>"
                                                        target="_blank"
                                                        rel="noopener noreferrer">
                                                        <?= h($item['headline_title'] ?: 'Untitled headline') ?>
                                                    </a>
                                                </h2>

                                                <div class="text-muted small mb-3">
                                                    <?php if (!empty($item['pub_date'])): ?>
                                                        <span>Published: <?= h(niceDate($item['pub_date'])) ?></span>
                                                        <span class="mx-1">•</span>
                                                    <?php endif; ?>

                                                    <span>Saved: <?= h(niceDate($item['saved_at'])) ?></span>
                                                </div>

                                                <div class="d-flex flex-wrap align-items-center">
                                                    <a
                                                        class="btn btn-green btn-sm mr-2 mb-2"
                                                        href="<?= h($item['headline_url']) ?>"
                                                        target="_blank"
                                                        rel="noopener noreferrer">
                                                        Open Publisher Site
                                                    </a>

                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-secondary btn-sm mb-2 saved-headline-unsave"
                                                        data-id="<?= h($item['headline_hash']) ?>">
                                                        <i class="fa-regular fa-bookmark mr-1"></i> Unsave
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav class="saved-headlines-pagination mt-4" aria-label="Saved headlines pagination">
                                <ul class="pagination justify-content-center flex-wrap">

                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= h(pageUrl(max(1, $page - 1))) ?>" aria-label="Previous">«</a>
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
                                            <a class="page-link" href="<?= h(pageUrl($i)) ?>"><?= $i ?></a>
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
                                        <a class="page-link" href="<?= h(pageUrl(min($totalPages, $page + 1))) ?>" aria-label="Next">»</a>
                                    </li>

                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php endif; ?>

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

    <script>
    (function () {
        const LOCAL_KEY = 'scrollnews:saved_firstlook:v1';
        const SAVE_API_URL = '/account/api/saved-headlines-save.php';
        const UNSAVE_API_URL = '/account/api/saved-headlines-unsave.php';
        const dbSavedIds = new Set(<?= json_encode(array_values(array_map(static fn($item) => $item['headline_hash'], $savedItems))) ?>);

        const importBanner = document.getElementById('savedHeadlinesImportBanner');
        const importButton = document.getElementById('savedHeadlinesImportBtn');
        const localCount = document.getElementById('savedHeadlinesLocalCount');
        const localPlural = document.getElementById('savedHeadlinesLocalPlural');

        function safeParse(json, fallback) {
            try { return JSON.parse(json); } catch { return fallback; }
        }

        function getLocalSaved() {
            return safeParse(localStorage.getItem(LOCAL_KEY) || '[]', []);
        }

        function setLocalSaved(list) {
            localStorage.setItem(LOCAL_KEY, JSON.stringify(list));
        }

        function getLocalOnlyItems() {
            return getLocalSaved().filter(item => {
                return item &&
                    item.id &&
                    item.url &&
                    item.title &&
                    !dbSavedIds.has(item.id);
            });
        }

        function updateImportBanner() {
            if (!importBanner || !localCount || !localPlural) return;

            const localOnly = getLocalOnlyItems();

            if (localOnly.length === 0) {
                importBanner.classList.add('d-none');
                return;
            }

            localCount.textContent = String(localOnly.length);
            localPlural.textContent = localOnly.length === 1 ? '' : 's';
            importBanner.classList.remove('d-none');
        }

        async function saveToAccount(item) {
            const response = await fetch(SAVE_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(item)
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Unable to save headline');
            }

            return data;
        }

        async function unsaveFromAccount(id) {
            const response = await fetch(UNSAVE_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Unable to unsave headline');
            }

            return data;
        }

        function removeFromLocalStorage(id) {
            const next = getLocalSaved().filter(item => item && item.id !== id);
            setLocalSaved(next);
        }

        if (importButton) {
            importButton.addEventListener('click', async () => {
                const localOnly = getLocalOnlyItems();

                if (localOnly.length === 0) {
                    updateImportBanner();
                    return;
                }

                importButton.disabled = true;
                importButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Adding…';

                try {
                    for (const item of localOnly) {
                        await saveToAccount(item);
                    }

                    window.location.reload();
                } catch (err) {
                    console.warn('Saved headlines import failed:', err);
                    importButton.disabled = false;
                    importButton.innerHTML = '<i class="fa-solid fa-cloud-arrow-up mr-1"></i> Try Again';
                }
            });
        }

        document.querySelectorAll('.saved-headline-unsave').forEach(button => {
            button.addEventListener('click', async () => {
                const id = button.getAttribute('data-id');
                const card = button.closest('.saved-headline-card');

                if (!id || !card) return;

                button.disabled = true;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Unsaving…';

                try {
                    await unsaveFromAccount(id);
                    removeFromLocalStorage(id);
                    card.remove();
                    window.dispatchEvent(new StorageEvent('storage', { key: LOCAL_KEY }));
                } catch (err) {
                    console.warn('Saved headline unsave failed:', err);
                    button.disabled = false;
                    button.innerHTML = '<i class="fa-regular fa-bookmark mr-1"></i> Unsave';
                }
            });
        });

        window.addEventListener('storage', (event) => {
            if (event.key === LOCAL_KEY) {
                updateImportBanner();
            }
        });

        updateImportBanner();
    })();
    </script>

</body>

</html>