<?php

define('BASE_PATH', dirname(__DIR__));
require_once __DIR__ . '/publisher.php';

$status = 'error';
$message = 'This verification link is invalid or has already been used.';
$publisherId = null;
$token = (string) ($_GET['token'] ?? '');

if ($token !== '') {
    try {
        $pdo = auth_db();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT pu.id, pu.publisher_id, pu.status, pu.token_expires_at FROM publisher_users pu WHERE pu.verification_token_hash = :token_hash FOR UPDATE');
        $stmt->execute([':token_hash' => hash('sha256', $token)]);
        $relationship = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$relationship) {
            $pdo->rollBack();
        } elseif ($relationship['status'] === 'verified') {
            $pdo->rollBack();
            $status = 'success';
            $message = 'This publisher relationship is already verified.';
            $publisherId = (int) $relationship['publisher_id'];
        } elseif (empty($relationship['token_expires_at']) || strtotime($relationship['token_expires_at']) < time()) {
            $pdo->rollBack();
            $message = 'This verification link has expired. Please request a new one.';
        } else {
            $updateStmt = $pdo->prepare("UPDATE publisher_users SET status = 'verified', verified_at = CURRENT_TIMESTAMP, verification_token_hash = NULL, token_expires_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND status <> 'verified'");
            $updateStmt->execute([':id' => $relationship['id']]);
            $pdo->commit();
            $status = 'success';
            $message = 'Your publisher relationship is verified.';
            $publisherId = (int) $relationship['publisher_id'];
        }
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Publisher verification failed: ' . $e->getMessage());
        $message = 'Something went wrong while verifying this publisher. Please try again.';
    }
}

if ($status === 'success' && $publisherId) {
    header('Location: /account/publisher-dashboard.php?publisher_id=' . $publisherId . '&verified=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1" /><title>Publisher Verification — Scroll News</title><link href="/assets/css/styles.css" rel="stylesheet" /><link href="/assets/css/auth.css" rel="stylesheet" /></head>
<body class="auth-page"><main class="auth-shell container my-5"><div class="auth-card card border-0"><div class="card-body p-4"><h1 class="h3">Publisher Verification</h1><div class="alert <?= $status === 'success' ? 'alert-success' : 'alert-danger'; ?>" role="alert"><?= publisher_h($message); ?></div><a class="btn btn-primary" href="/account/">Return to Account</a></div></div></main></body></html>