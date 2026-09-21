<?php

define('BASE_PATH', dirname(__DIR__));
$theme_experiment_enabled = true;
require_once BASE_PATH . '/auth/includes/require_auth.php';
require_once __DIR__ . '/publisher.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$publisherId = (int) ($_GET['publisher_id'] ?? 0);
$pdo = auth_db();
$stmt = $pdo->prepare("SELECT p.id, p.domain, p.name FROM publishers p INNER JOIN publisher_users pu ON pu.publisher_id = p.id WHERE p.id = :publisher_id AND pu.user_id = :user_id AND pu.status = 'verified' LIMIT 1");
$stmt->execute([':publisher_id' => $publisherId, ':user_id' => $userId]);
$publisher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$publisher) {
    http_response_code(403);
    $publisher = null;
}
?>
<!DOCTYPE html>
<html lang="en"><head><?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?><meta charset="utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" /><title>Publisher Dashboard — Scroll News</title><link rel="icon" type="image/png" href="/assets/img/play-green.png" /><link href="/assets/css/styles.css?v=<?= filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" /><link href="/assets/css/custom.css?v=<?= filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" /><link href="/assets/css/auth.css?v=<?= filemtime(BASE_PATH . '/assets/css/auth.css'); ?>" rel="stylesheet" /><link href="/assets/css/account.css?v=<?= filemtime(BASE_PATH . '/assets/css/account.css'); ?>" rel="stylesheet" /></head>
<body class="auth-page account-page"><div class="page"><?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?><main class="auth-shell container my-5"><div class="auth-card card border-0 rounded-3"><div class="card-body p-4 p-md-5"><?php if (!$publisher): ?><div class="alert alert-danger" role="alert">You do not have access to this publisher dashboard.</div><a href="/account/" class="btn btn-outline-secondary">Return to Account</a><?php else: ?><p class="text-muted mb-2"><a href="/account/">Account</a> / Publisher Tools</p><h1 class="h3 mb-2"><?= publisher_h($publisher['name'] ?: $publisher['domain']); ?></h1><p class="mb-4"><span class="badge badge-success">Verified Publisher</span> <span class="text-muted"><?= publisher_h($publisher['domain']); ?></span></p><h2 class="h5">Publisher Tools</h2><ul class="text-muted"><li>Publisher Profile — Coming soon</li><li>Content Statistics — Coming soon</li><li>Content Controls — Coming soon</li><li>Article Visibility — Coming soon</li></ul><?php endif; ?></div></div></main><?php require_once BASE_PATH . '/views/partials/___footer.php'; ?></div></body></html>