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
    | Uses the DATABASE_URL environment variable provided by
    | Render or your local environment.
    |
    | Example:
    | postgres://user:password@host:5432/database
    |
    */

    //$databaseUrl = getenv('DATABASE_URL');
    $databaseUrl = "postgres://u54p8tqv3cg377:p07d3f3181a94264cd3a103e335f8fa769dccd2ca4b9788a78cd5f660fcbfd1e1@c12662383iu6b3.cluster-czrs8kj4isg7.us-east-1.rds.amazonaws.com:5432/daa88slg44bj7f";

    if (!$databaseUrl) {

        error_log('DATABASE_URL environment variable is missing.');

        http_response_code(500);

        exit('Database configuration missing.');
    }

    $db = parse_url($databaseUrl);

    if ($db === false) {

        error_log('Failed to parse DATABASE_URL.');

        http_response_code(500);

        exit('Invalid database configuration.');
    }

    $host = $db['host'] ?? 'localhost';
    $port = $db['port'] ?? '5432';
    $dbname = ltrim($db['path'] ?? '', '/');
    $username = $db['user'] ?? '';
    $password = $db['pass'] ?? '';

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