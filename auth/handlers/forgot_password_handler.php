<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_db.php';

require_once __DIR__ . '/../includes/send_auth_email.php';

$config = require __DIR__ . '/../config/auth_config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $config['forgot_password_path']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $config['forgot_password_path'] . '?error=invalid_email');
    exit;
}

// Always show the same message for security.
$redirectSuccess = $config['forgot_password_path'] . '?reset=1';

try {
    $pdo = auth_db();

    $stmt = $pdo->prepare("
        SELECT id, email
        FROM users
        WHERE email = :email
        LIMIT 1
    ");

    $stmt->execute([
        ':email' => $email,
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + $config['password_reset_expiry']);

        $stmt = $pdo->prepare("
            UPDATE users
            SET 
                password_reset_token = :token_hash,
                password_reset_expires_at = :expires_at
            WHERE id = :user_id
        ");

        $stmt->execute([
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
            ':user_id' => $user['id'],
        ]);

        $resetUrl = rtrim($config['base_url'], '/') . '/' . ltrim($config['reset_password_path'], '/') . '?token=' . urlencode($token);

        error_log('[ForgotPassword] Reset URL: ' . $resetUrl);

        $emailSent = send_auth_email(
            $email,
            'Reset your Scroll News password',
            '
                <p>We received a request to reset your Scroll News password.</p>
                <p>You can reset your password by clicking the link below:</p>
                <p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">Reset your password</a></p>
                <p>If the button does not work, copy and paste this link into your browser:</p>
                <p>' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '</p>
                <p>If you did not request this, you can ignore this email.</p>
            '
        );

        if (!$emailSent) {
            error_log('[ForgotPassword] Reset email failed to send to ' . $email);
        }
    }

    header('Location: ' . $redirectSuccess);
    exit;
} catch (Throwable $e) {
    error_log('[ForgotPassword] ' . $e->getMessage());

    header('Location: ' . $config['forgot_password_path'] . '?error=server_error');
    exit;
}
