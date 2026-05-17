<?php

define('BASE_PATH', dirname(__DIR__));

session_start();

$pageTitle = 'Change Password — Scroll News';

require_once BASE_PATH . '/auth/views/change-password.view.php';