<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_db.php';

$config = require __DIR__ . '/../config/auth_config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $config['forgot_password_path']);
    exit;
}

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($token === '') {
    header('Location: ' . $config['forgot_password_path'] . '?error=invalid_token');
    exit;
}

if (strlen($password) < $config['min_password_length']) {
    header('Location: ' . $config['reset_password_path'] . '?token=' . urlencode($token) . '&error=password_too_short');
    exit;
}

if ($password !== $confirmPassword) {
    header('Location: ' . $config['reset_password_path'] . '?token=' . urlencode($token) . '&error=password_mismatch');
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

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        UPDATE users
        SET 
            password_hash = :password_hash,
            password_reset_token = NULL,
            password_reset_expires_at = NULL,
            updated_at = NOW()
        WHERE id = :user_id
    ");

    $stmt->execute([
        ':password_hash' => $passwordHash,
        ':user_id' => $user['id'],
    ]);

    header('Location: ' . $config['login_path'] . '?reset=success');
    exit;

} catch (Throwable $e) {
    error_log('[ResetPassword] ' . $e->getMessage());

    header('Location: ' . $config['reset_password_path'] . '?token=' . urlencode($token) . '&error=server_error');
    exit;
}