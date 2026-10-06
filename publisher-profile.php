<?php
// Public publisher profile: basic verified-publisher info + recent articles.
define('BASE_PATH', __DIR__);
$theme_experiment_enabled = true;

require_once BASE_PATH . '/auth/includes/auth_bootstrap.php';
require_once BASE_PATH . '/account/publisher.php';
require_once BASE_PATH . '/core/___modules.php';

$domain = is_string($_GET['domain'] ?? null) ? sn_publisher_domain($_GET['domain']) : null;

if ($domain === null) {
    http_response_code(404);
    require BASE_PATH . '/404.php';
    exit;
}

// Redirect non-canonical forms (uppercase, www.) to the normalized domain URL.
if ($_GET['domain'] !== $domain) {
    header('Location: /publisher-profile.php?domain=' . rawurlencode($domain), true, 301);
    exit;
}

$publisher = null;
$profile = publisher_empty_profile();
$isVerified = false;

try {
    $pdo = auth_db();
    $hostExpr = publisher_article_host_sql_expr();

    // Exact normalized-host match against Scroll News article data.
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE deleted_at IS NULL AND url IS NOT NULL AND $hostExpr = :domain");
    $countStmt->execute([':domain' => $domain]);
    $totalArticles = (int) $countStmt->fetchColumn();

    if ($totalArticles === 0) {
        http_response_code(404);
        require BASE_PATH . '/404.php';
        exit;
    }

    // Optional Publisher Tools enrichment for the same domain.
    $stmt = $pdo->prepare("
        SELECT p.id, p.name,
               EXISTS (SELECT 1 FROM publisher_users pu WHERE pu.publisher_id = p.id AND pu.status = 'verified') AS is_verified
        FROM publishers p
        WHERE regexp_replace(lower(p.domain), '^www\\.', '') = :domain
        LIMIT 1
    ");
    $stmt->execute([':domain' => $domain]);
    $publisher = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($publisher) {
        $isVerified = (bool) $publisher['is_verified'];
        $profile = publisher_load_profile($pdo, (int) $publisher['id']);
    }
} catch (Throwable $e) {
    error_log('Publisher profile load failed: ' . $e->getMessage());
    http_response_code(500);
    readfile(BASE_PATH . '/500.html');
    exit;
}

$perPage = 20;
$page = max(1, (int) filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['default' => 1]]));
$totalPages = max(1, (int) ceil($totalArticles / $perPage));
$page = min($page, $totalPages);

$listStmt = $pdo->prepare("
    SELECT id, url, title, pub_date, source_slug, nlp
    FROM articles
    WHERE deleted_at IS NULL AND url IS NOT NULL AND $hostExpr = :domain
    ORDER BY pub_date DESC NULLS LAST
    LIMIT :limit OFFSET :offset
");
$listStmt->bindValue(':domain', $domain);
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$listStmt->execute();
$articles = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$publisherName = !empty($publisher['name']) ? $publisher['name'] : $domain;
$pageUrl = '/publisher-profile.php?domain=' . rawurlencode($domain);
$websiteUrl = !empty($profile['website_url']) ? publisher_normalize_url((string) $profile['website_url']) : null;
$websiteUrl = $websiteUrl ?: 'https://' . $domain;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="<?= publisher_h('Recent articles from ' . $publisherName . ' on Scroll News.') ?>" />
    <title><?= publisher_h($publisherName) ?> — Scroll News</title>
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <link href="/assets/css/styles.css?v=<?= filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?= filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
    <link id="dark-typography-theme" href="/assets/css/dark-typography.css?v=<?= filemtime(BASE_PATH . '/assets/css/dark-typography.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/mindpour.css?v=<?= filemtime(BASE_PATH . '/assets/css/mindpour.css'); ?>" rel="stylesheet" />

    <script src="/assets/js/dark-theme.js?v=<?= filemtime(BASE_PATH . '/assets/js/dark-theme.js'); ?>"></script>

    <style>
        .publisher-profile-logo { width: 48px; height: 48px; border-radius: 6px; object-fit: contain; background: #fff; }
        .publisher-profile-panel { background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.12); color: #f5f7fa; }
        .publisher-profile-panel .text-muted { color: rgba(255, 255, 255, 0.7) !important; }
        .publisher-profile-panel p { font-weight: 300; overflow-wrap: anywhere; }
        .publisher-articles { background: rgba(0, 0, 0, 0.55); border-radius: 0.5rem; padding: 0.5rem 1rem; }
    </style>
</head>
<body id="page-top" class="bg-dark">
    <div class="page">
        <?php require_once BASE_PATH . '/views/partials/___topnav_product.php'; ?>

        <main class="container my-5 text-light">
            <div class="card publisher-profile-panel mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-start flex-wrap">
                        <img class="publisher-profile-logo mr-3 mb-2" src="<?= publisher_h(publisher_favicon_url($domain)) ?>" alt="" />
                        <div class="flex-grow-1">
                            <h1 class="h4 mb-1"><?= publisher_h($publisherName) ?></h1>
                            <p class="mb-2">
                                <?php if ($isVerified): ?>
                                    <span class="badge badge-success">Verified Publisher</span>
                                <?php endif; ?>
                                <span class="text-muted"><?= publisher_h($domain) ?></span>
                            </p>

                            <?php if (!empty($profile['description'])): ?>
                                <p class="mb-2"><?= nl2br(publisher_h($profile['description'])) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($profile['location'])): ?>
                                <p class="mb-2 text-muted"><i class="fa-solid fa-location-dot mr-1"></i><?= publisher_h($profile['location']) ?></p>
                            <?php endif; ?>

                            <p class="mb-2"><a href="<?= publisher_h($websiteUrl) ?>" target="_blank" rel="noopener noreferrer">Visit <?= publisher_h($publisherName) ?> website <i class="fa-solid fa-arrow-up-right-from-square ml-1 small"></i></a></p>

                            <p class="mb-0 text-muted small"><?= number_format($totalArticles) ?> article<?= $totalArticles === 1 ? '' : 's' ?> on Scroll News</p>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="h5 mb-3">Recent articles</h2>

            <?php if ($articles): ?>
                <div class="news-intel-panel publisher-articles">
                    <ul class="list-unstyled mb-0 intel-article-list">
                        <?php foreach ($articles as $article): ?>
                            <?= scroll_render_article_intel_item($article, ['w' => '24h', 'db' => 1]) ?>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="d-flex justify-content-between align-items-center mt-4" aria-label="Publisher articles pagination">
                        <?php if ($page > 1): ?>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= publisher_h($pageUrl . '&page=' . ($page - 1)) ?>">&larr; Newer</a>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>
                        <span class="text-muted small">Page <?= $page ?> of <?= $totalPages ?></span>
                        <?php if ($page < $totalPages): ?>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= publisher_h($pageUrl . '&page=' . ($page + 1)) ?>">Older &rarr;</a>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </main>

        <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>
    </div>

    <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
