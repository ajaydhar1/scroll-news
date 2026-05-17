<?php

declare(strict_types=1);

require_once __DIR__ . '/auth_db.php';

$config = require __DIR__ . '/../config/auth_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_COOKIE['scroll_news_session'])) {
    header('Location: ' . $config['login_path']);
    exit;
}

$tokenHash = hash('sha256', $_COOKIE['scroll_news_session']);

try {
    $pdo = auth_db();

    $stmt = $pdo->prepare("
        SELECT
            us.id AS session_id,
            us.user_id,
            us.expires_at,
            u.email,
            u.display_name,
            u.email_verified
        FROM user_sessions us
        INNER JOIN users u ON u.id = us.user_id
        WHERE us.session_token_hash = :session_token_hash
        LIMIT 1
    ");

    $stmt->execute([
        ':session_token_hash' => $tokenHash,
    ]);

    $authUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$authUser || strtotime($authUser['expires_at']) < time()) {
        if ($authUser && !empty($authUser['session_id'])) {
            $deleteStmt = $pdo->prepare("
            DELETE FROM user_sessions
            WHERE id = :session_id
        ");

            $deleteStmt->execute([
                ':session_id' => $authUser['session_id'],
            ]);
        }

        setcookie(
            'scroll_news_session',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $config['secure_cookies'],
                'httponly' => $config['http_only_cookies'],
                'samesite' => $config['same_site_policy'],
            ]
        );

        header('Location: ' . $config['login_path'] . '?error=session_expired');
        exit;
    }

    $_SESSION['user_id'] = $authUser['user_id'];
    $_SESSION['user_email'] = $authUser['email'];
    $_SESSION['display_name'] = $authUser['display_name'];
} catch (Throwable $e) {
    error_log('Auth check failed: ' . $e->getMessage());

    header('Location: ' . $config['login_path']);
    exit;
}
