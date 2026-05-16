<?php

define('BASE_PATH', dirname(__DIR__));

require_once __DIR__ . '/config/auth_config.php';
require_once __DIR__ . '/handlers/verify_email_handler.php';

$result = verifyEmailToken($_GET['token'] ?? '');

$status = $result['status'] ?? 'error';
$message = $result['message'] ?? 'Something went wrong while verifying your email.';

$isSuccess = $status === 'success';
$pageTitle = $isSuccess ? 'Email Verified' : 'Verification Failed';

require __DIR__ . '/views/verify-email.view.php';