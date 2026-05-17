<?php

declare(strict_types=1);

$config = require_once __DIR__ . '/config/auth_config.php';

require_once __DIR__ . '/includes/auth_db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = auth_db();

    if (!empty($_COOKIE['scroll_news_session'])) {
        $tokenHash = hash('sha256', $_COOKIE['scroll_news_session']);

        $stmt = $pdo->prepare("
            DELETE FROM user_sessions
            WHERE session_token_hash = :token_hash
        ");

        $stmt->execute([
            ':token_hash' => $tokenHash,
        ]);
    }
} catch (Throwable $e) {
    error_log('Logout remember token cleanup failed: ' . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| Clear remember-me cookie
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Clear session
|--------------------------------------------------------------------------
*/

$_SESSION = [];

if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Lax',
        ]
    );
}

session_destroy();

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header('Location: /auth/login?logged_out=1');
exit;
