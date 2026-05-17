<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_db.php';

$config = require __DIR__ . '/../config/auth_config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $config['change_password_path']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header('Location: ' . $config['login_path'] . '?error=login_required');
    exit;
}

$oldPassword = $_POST['old_password'] ?? '';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (strlen($password) < $config['password_min_length']) {
    header('Location: ' . $config['change_password_path'] . '?error=password_too_short');
    exit;
}

if ($password !== $confirmPassword) {
    header('Location: ' . $config['change_password_path'] . '?error=password_mismatch');
    exit;
}

try {
    $pdo = auth_db();

    $stmt = $pdo->prepare("
        SELECT id, password_hash
        FROM users
        WHERE id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        ':user_id' => $userId,
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_unset();
        session_destroy();

        header('Location: ' . $config['login_path'] . '?error=login_required');
        exit;
    }

    if (!password_verify($oldPassword, $user['password_hash'])) {
        header('Location: ' . $config['change_password_path'] . '?error=invalid_current_password');
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        UPDATE users
        SET 
            password_hash = :password_hash,
            updated_at = NOW()
        WHERE id = :user_id
    ");

    $stmt->execute([
        ':password_hash' => $passwordHash,
        ':user_id' => $user['id'],
    ]);

    header('Location: ' . $config['change_password_path'] . '?changed=1');
    exit;

} catch (Throwable $e) {
    error_log('[ChangePassword] ' . $e->getMessage());

    header('Location: ' . $config['change_password_path'] . '?error=server_error');
    exit;
}