<?php

define('BASE_PATH', dirname(__DIR__));

session_start();

// If already logged in, redirect away from login page
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

// Optional page variables
$pageTitle = 'Sign In — Scroll News';

// Load the view
require_once BASE_PATH . '/auth/views/login.view.php';