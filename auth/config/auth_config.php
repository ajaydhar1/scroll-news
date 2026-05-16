<?php

/*
|--------------------------------------------------------------------------
| Authentication Configuration
|--------------------------------------------------------------------------
|
| Central configuration for the Scroll News authentication framework.
| Shared across login, registration, sessions, password resets,
| email verification, and future reusable auth components.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Application
    |--------------------------------------------------------------------------
    */

    'app_name' => 'Scroll News',

    'base_url' => 'http://localhost:8000',


    /*
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */

    'login_path' => '/auth/login.php',

    'register_path' => '/auth/register.php',

    'logout_path' => '/auth/handlers/logout_handler.php',

    'dashboard_path' => '/auth/',

    'forgot_password_path' => '/auth/forgot-password.php',

    'reset_password_path' => '/auth/reset-password.php',


    /*
    |--------------------------------------------------------------------------
    | Session Settings
    |--------------------------------------------------------------------------
    */

    // Standard session lifetime (in seconds)
    'session_lifetime' => 60 * 60 * 24 * 7, // 7 days

    // Persistent "Keep Me Signed In" lifetime
    'remember_me_lifetime' => 60 * 60 * 24 * 30, // 30 days


    /*
    |--------------------------------------------------------------------------
    | Token Expiration
    |--------------------------------------------------------------------------
    */

    // Email verification token expiration
    'email_verification_expiry' => 60 * 60 * 24, // 24 hours

    // Password reset token expiration
    'password_reset_expiry' => 60 * 30, // 30 minutes


    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */

    // Minimum password length
    'min_password_length' => 8,

    // Secure cookie requirement
    'secure_cookies' => false,

    // HttpOnly cookies
    'http_only_cookies' => true,

    // SameSite policy
    'same_site_policy' => 'Lax',


    /*
    |--------------------------------------------------------------------------
    | Email Verification
    |--------------------------------------------------------------------------
    */

    'require_email_verification' => true,

];