<?php

define('BASE_PATH', dirname(__DIR__));

$config = require __DIR__ . '/config/auth_config.php';

session_start();

// If already logged in, redirect away from login page
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $config['dashboard_path']);
    exit;
}

// Optional page variables
$pageTitle = 'Sign In — Scroll News';

// Load the view
require_once BASE_PATH . '/auth/views/login.view.php';