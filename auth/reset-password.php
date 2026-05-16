<?php

define('BASE_PATH', dirname(__DIR__));

session_start();

$pageTitle = 'Reset Password — Scroll News';

require_once BASE_PATH . '/auth/views/reset-password.view.php';