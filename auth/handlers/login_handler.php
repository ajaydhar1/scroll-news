<?php

require_once __DIR__ . '/../includes/auth_db.php';

$config = require __DIR__ . '/../config/auth_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $config['login_path']);
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$rememberMe = isset($_POST['remember_me']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $config['login_path'] . '?error=invalid_login');
    exit;
}

if ($password === '') {
    header('Location: ' . $config['login_path'] . '?error=invalid_login');
    exit;
}

try {
    $pdo = auth_db();

    $stmt = $pdo->prepare("
        SELECT id, email, password_hash, display_name, email_verified
        FROM users
        WHERE email = :email
        LIMIT 1
    ");

    $stmt->execute([
        ':email' => $email,
    ]);

    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        header('Location: ' . $config['login_path'] . '?error=invalid_login');
        exit;
    }

    if (
        !empty($config['require_email_verification']) &&
        !$user['email_verified']
    ) {
        header('Location: ' . $config['login_path'] . '?error=email_not_verified');
        exit;
    }

    $rawSessionToken = bin2hex(random_bytes(32));
    $sessionTokenHash = hash('sha256', $rawSessionToken);

    $sessionLifetime = $rememberMe
        ? $config['remember_me_lifetime']
        : $config['session_lifetime'];

    $expiresAt = date('Y-m-d H:i:s', time() + $sessionLifetime);

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO user_sessions (
            user_id,
            session_token_hash,
            user_agent,
            ip_address,
            expires_at
        ) VALUES (
            :user_id,
            :session_token_hash,
            :user_agent,
            :ip_address,
            :expires_at
        )
    ");

    $stmt->execute([
        ':user_id' => $user['id'],
        ':session_token_hash' => $sessionTokenHash,
        ':user_agent' => $userAgent,
        ':ip_address' => $ipAddress,
        ':expires_at' => $expiresAt,
    ]);

    setcookie(
        'scroll_news_session',
        $rawSessionToken,
        [
            'expires' => time() + $sessionLifetime,
            'path' => '/',
            'secure' => $config['secure_cookies'],
            'httponly' => $config['http_only_cookies'],
            'samesite' => $config['same_site_policy'],
        ]
    );

    header('Location: ' . $config['dashboard_path']);
    exit;

} catch (Throwable $e) {
    error_log('Login failed: ' . $e->getMessage());

    header('Location: ' . $config['login_path'] . '?error=login_failed');
    exit;
}