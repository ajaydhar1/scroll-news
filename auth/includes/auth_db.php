<?php

/*
|--------------------------------------------------------------------------
| Authentication Database Connection
|--------------------------------------------------------------------------
|
| Shared PDO connection for the authentication framework.
| Used by login, registration, sessions, password resets,
| email verification, and future account features.
|
*/

function auth_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Replace these values with your actual database credentials.
    | Later, these can be moved into environment variables or a
    | centralized application config system.
    |
    */

    $host = 'YOUR_DB_HOST';
    $port = '5432';
    $dbname = 'YOUR_DB_NAME';
    $username = 'YOUR_DB_USER';
    $password = 'YOUR_DB_PASSWORD';

    /*
    |--------------------------------------------------------------------------
    | PDO Connection
    |--------------------------------------------------------------------------
    */

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {

        $pdo = new PDO(
            $dsn,
            $username,
            $password,
            $options
        );

    } catch (PDOException $e) {

        error_log('Auth DB Connection Failed: ' . $e->getMessage());

        http_response_code(500);

        exit('Database connection failed.');
    }

    return $pdo;
}