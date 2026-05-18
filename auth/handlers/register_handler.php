<?php

require_once __DIR__ . '/../includes/auth_db.php';

require_once __DIR__ . '/../includes/send_auth_email.php';

$config = require __DIR__ . '/../config/auth_config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $config['register_path']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';
$displayName = trim($_POST['display_name'] ?? '');

$_SESSION['old'] = [
    'display_name' => $displayName,
    'email' => $email,
];

$email = strtolower($email);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $config['register_path'] . '?error=invalid_email');
    exit;
}

if (strlen($password) < $config['min_password_length']) {
    header('Location: ' . $config['register_path'] . '?error=password_too_short');
    exit;
}

if ($password !== $passwordConfirm) {
    header('Location: ' . $config['register_path'] . '?error=passwords_do_not_match');
    exit;
}

try {
    $pdo = auth_db();

    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->execute([
        ':email' => $email,
    ]);

    if ($stmt->fetch()) {
        header('Location: ' . $config['register_path'] . '?error=email_exists');
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO users (
            email,
            password_hash,
            display_name,
            email_verified
        ) VALUES (
            :email,
            :password_hash,
            :display_name,
            :email_verified
        )
        RETURNING id
    ");

    $stmt->execute([
        ':email' => $email,
        ':password_hash' => $passwordHash,
        ':display_name' => $displayName !== '' ? $displayName : null,
        ':email_verified' => 0,
    ]);

    $userId = $stmt->fetchColumn();

    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);

    $expiresAt = date(
        'Y-m-d H:i:s',
        time() + $config['email_verification_expiry']
    );

    $stmt = $pdo->prepare("
        INSERT INTO email_verification_tokens (
            user_id,
            token_hash,
            expires_at
        ) VALUES (
            :user_id,
            :token_hash,
            :expires_at
        )
    ");

    $stmt->execute([
        ':user_id' => $userId,
        ':token_hash' => $tokenHash,
        ':expires_at' => $expiresAt,
    ]);

    $pdo->commit();

    // Later: send this link by email.
    // For now, you can log it locally for testing.
    $verificationLink = rtrim($config['base_url'], '/') .
        '/auth/verify-email.php?token=' .
        urlencode($rawToken);

    $emailSent = send_auth_email(
        $email,
        'Verify your Scroll News account',
        '
            <p>Welcome to Scroll News.</p>
            <p>Please verify your email address by clicking the link below:</p>
            <p><a href="' . htmlspecialchars($verificationLink, ENT_QUOTES, 'UTF-8') . '">Verify your email</a></p>
            <p>If the button does not work, copy and paste this link into your browser:</p>
            <p>' . htmlspecialchars($verificationLink, ENT_QUOTES, 'UTF-8') . '</p>
        '
    );

    if (!$emailSent) {
        error_log('[Register] Verification email failed to send to ' . $email);
    }

    error_log('Email verification link: ' . $verificationLink);

    header('Location: ' . $config['login_path'] . '?registered=1');
    exit;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Registration failed: ' . $e->getMessage());

    header('Location: ' . $config['register_path'] . '?error=registration_failed');
    exit;
}
