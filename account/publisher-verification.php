<?php

define('BASE_PATH', dirname(__DIR__));
$theme_experiment_enabled = true;

require_once BASE_PATH . '/auth/includes/require_auth.php';
require_once __DIR__ . '/publisher.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$errors = [];
$success = $_GET['sent'] ?? null;
$form = ['domain' => '', 'email' => ''];

if (empty($_SESSION['publisher_verification_csrf'])) {
    $_SESSION['publisher_verification_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['domain'] = trim((string) ($_POST['domain'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));

    if (!hash_equals((string) $_SESSION['publisher_verification_csrf'], (string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    }

    $domain = publisher_normalize_domain($form['domain']);
    $email = publisher_normalize_email($form['email']);
    $testEmail = trim((string) getenv('PUBLISHER_VERIFICATION_TEST_EMAIL'));

    if ($domain === null) {
        $errors[] = 'Please enter a valid publisher domain such as publisher.com.';
    }

    if ($email === null) {
        $errors[] = 'Please enter a valid publisher email address.';
    } elseif ($email !== strtolower($domain ?? '') && !str_ends_with($email, '@' . ($domain ?? ''))) {
        if ($testEmail === '' || $form['email'] !== $testEmail) {
            $errors[] = 'The publisher email address must use the publisher domain.';
        }
    }

    if (!$errors) {
        try {
            $pdo = auth_db();
            $pdo->beginTransaction();

            $publisherStmt = $pdo->prepare('INSERT INTO publishers (domain) VALUES (:domain) ON CONFLICT (domain) DO UPDATE SET updated_at = CURRENT_TIMESTAMP RETURNING id');
            $publisherStmt->execute([':domain' => $domain]);
            $publisherId = (int) $publisherStmt->fetchColumn();

            $relationshipStmt = $pdo->prepare('SELECT id, status FROM publisher_users WHERE user_id = :user_id AND publisher_id = :publisher_id FOR UPDATE');
            $relationshipStmt->execute([':user_id' => $userId, ':publisher_id' => $publisherId]);
            $relationship = $relationshipStmt->fetch(PDO::FETCH_ASSOC);

            if ($relationship && $relationship['status'] === 'verified') {
                $pdo->commit();
                header('Location: /account/publisher-dashboard.php?publisher_id=' . $publisherId . '&already_verified=1');
                exit;
            }

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + 86400);

            if ($relationship) {
                $updateStmt = $pdo->prepare('UPDATE publisher_users SET email = :email, status = \'pending\', verification_token_hash = :token_hash, token_expires_at = :expires_at, requested_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
                $updateStmt->execute([':email' => $email, ':token_hash' => $tokenHash, ':expires_at' => $expiresAt, ':id' => $relationship['id']]);
            } else {
                $insertStmt = $pdo->prepare('INSERT INTO publisher_users (publisher_id, user_id, email, status, verification_token_hash, token_expires_at, requested_at) VALUES (:publisher_id, :user_id, :email, \'pending\', :token_hash, :expires_at, CURRENT_TIMESTAMP)');
                $insertStmt->execute([':publisher_id' => $publisherId, ':user_id' => $userId, ':email' => $email, ':token_hash' => $tokenHash, ':expires_at' => $expiresAt]);
            }

            $pdo->commit();

            $config = require BASE_PATH . '/auth/config/auth_config.php';
            $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://scrollnews.ai'), '/');
            $verificationUrl = $baseUrl . '/account/verify-publisher.php?token=' . rawurlencode($token);
            $sent = send_auth_email($email, 'Verify publisher access on Scroll News', publisher_verification_email($domain, $verificationUrl));

            if (!$sent) {
                $invalidateStmt = $pdo->prepare('UPDATE publisher_users SET verification_token_hash = NULL, token_expires_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id AND publisher_id = :publisher_id AND verification_token_hash = :token_hash');
                $invalidateStmt->execute([':user_id' => $userId, ':publisher_id' => $publisherId, ':token_hash' => $tokenHash]);
                $errors[] = 'We could not send the verification email. Please try again later.';
            } else {
                $_SESSION['publisher_verification_csrf'] = bin2hex(random_bytes(32));
                header('Location: /account/publisher-verification.php?sent=1');
                exit;
            }
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Publisher verification request failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong while creating the verification request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Publisher Verification — Scroll News</title>
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <link href="/assets/css/styles.css?v=<?= filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?= filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
    <link id="dark-typography-theme" href="/assets/css/dark-typography.css?v=<?= filemtime(BASE_PATH . '/assets/css/dark-typography.css'); ?>" rel="stylesheet" />

    <script src="/assets/js/dark-theme.js?v=<?= filemtime(BASE_PATH . '/assets/js/dark-theme.js'); ?>"></script>

    <link href="/assets/css/auth.css?v=<?= filemtime(BASE_PATH . '/assets/css/auth.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/account.css?v=<?= filemtime(BASE_PATH . '/assets/css/account.css'); ?>" rel="stylesheet" />
</head>
<body class="auth-page account-page">
    <div class="page">
        <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>
        <main class="auth-shell container my-5">
            <div class="auth-card card border-0 rounded-3"><div class="card-body p-4 p-md-5">
                <p class="text-muted mb-2"><a href="/account/">Account</a> / Publisher Tools</p>
                <h1 class="h3 mb-2">Publisher Verification</h1>
                <p class="text-muted mb-4">Verify your relationship with a publisher to access its Scroll News tools.</p>
                <?php if ($success): ?><div class="alert alert-success" role="alert">Check your email for the verification link.</div><?php endif; ?>
                <?php if ($errors): ?><div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= publisher_h($error); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= publisher_h($_SESSION['publisher_verification_csrf']); ?>" />
                    <div class="form-group"><label for="domain">Publisher domain</label><input class="form-control" id="domain" name="domain" type="text" placeholder="publisher.com" maxlength="253" value="<?= publisher_h($form['domain']); ?>" required /></div>
                    <div class="form-group"><label for="email">Publisher email address</label><input class="form-control" id="email" name="email" type="email" placeholder="you@publisher.com" maxlength="254" value="<?= publisher_h($form['email']); ?>" required /></div>
                    <button class="btn btn-primary" type="submit">Send Verification Email</button>
                </form>
            </div></div>
        </main>
        <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>
    </div>

    <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>