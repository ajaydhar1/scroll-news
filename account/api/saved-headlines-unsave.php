<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__, 2));

require_once BASE_PATH . '/auth/includes/auth_db.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Not signed in',
    ]);
    exit;
}

$userId = (int) $userId;

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput ?: '', true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON payload',
    ]);
    exit;
}

$headlineHash = trim((string)($payload['id'] ?? $payload['headline_hash'] ?? ''));

if ($headlineHash === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Missing headline hash',
    ]);
    exit;
}

try {
    $sql = "
        UPDATE user_saved_headlines
        SET deleted_at = CURRENT_TIMESTAMP
        WHERE user_id = :user_id
          AND headline_hash = :headline_hash
          AND deleted_at IS NULL
        RETURNING id
    ";

    $pdo = auth_db();

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $userId,
        ':headline_hash' => $headlineHash,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'status' => $row ? 'unsaved' : 'already_unsaved',
        'id' => $row ? (int) $row['id'] : null,
    ]);
    exit;

} catch (Throwable $e) {
    error_log('Saved headline unsave failed: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to unsave headline',
    ]);
    exit;
}