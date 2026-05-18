<?php

declare(strict_types=1);

require_once __DIR__ . '/auth_bootstrap.php';

if (!$currentUser) {
    header('Location: ' . $config['login_path'] . '?error=login_required');
    exit;
}