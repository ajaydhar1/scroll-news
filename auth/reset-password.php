<?php

define('BASE_PATH', dirname(__DIR__));

require_once __DIR__ . '/includes/auth_db.php';

$config = require __DIR__ . '/config/auth_config.php';

session_start();

// If already logged in, redirect away from login page
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $config['dashboard_path']);
    exit;
}

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    header('Location: ' . $config['forgot_password_path'] . '?error=invalid_or_expired_token');
    exit;
}

try {
    $pdo = auth_db();

    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE password_reset_token = :token_hash
          AND password_reset_expires_at > NOW()
        LIMIT 1
    ");

    $stmt->execute([
        ':token_hash' => $tokenHash,
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: ' . $config['forgot_password_path'] . '?error=invalid_or_expired_token');
        exit;
    }
} catch (Throwable $e) {
    error_log('[ResetPasswordPage] ' . $e->getMessage());

    header('Location: ' . $config['forgot_password_path'] . '?error=server_error');
    exit;
}

$pageTitle = 'Reset Password — Scroll News';

require_once BASE_PATH . '/auth/views/reset-password.view.php';