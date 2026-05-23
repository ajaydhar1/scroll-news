<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__, 2));

require_once BASE_PATH . '/auth/includes/auth_db.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Adjust this depending on your auth helper/session shape
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
$headlineUrl = trim((string)($payload['url'] ?? $payload['headline_url'] ?? ''));
$headlineTitle = trim((string)($payload['title'] ?? $payload['headline_title'] ?? ''));
$sourceSlug = trim((string)($payload['source_slug'] ?? ''));
$pubDate = trim((string)($payload['pub_date'] ?? ''));
$savedAtMs = isset($payload['saved_at']) ? (int) $payload['saved_at'] : 0;
$savedAt = $savedAtMs > 0
    ? date('Y-m-d H:i:s', (int) floor($savedAtMs / 1000))
    : date('Y-m-d H:i:s');

if ($headlineHash === '' || $headlineUrl === '' || $headlineTitle === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required headline fields',
    ]);
    exit;
}

if (!filter_var($headlineUrl, FILTER_VALIDATE_URL)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid headline URL',
    ]);
    exit;
}

try {
    /*
     * Restore soft-deleted saved headlines before inserting.
     * Active duplicates are protected by the partial unique index:
     *   UNIQUE (user_id, headline_hash) WHERE deleted_at IS NULL
     */

    $restoreSql = "
        UPDATE user_saved_headlines
        SET
            headline_url = :headline_url,
            headline_title = :headline_title,
            source_slug = :source_slug,
            pub_date = NULLIF(:pub_date, '')::timestamp,
            saved_at = :saved_at,
            deleted_at = NULL
        WHERE user_id = :user_id
          AND headline_hash = :headline_hash
          AND deleted_at IS NOT NULL
        RETURNING id
    ";

    $pdo = auth_db();

    $restoreStmt = $pdo->prepare($restoreSql);
    $restoreStmt->execute([
        ':user_id' => $userId,
        ':headline_hash' => $headlineHash,
        ':headline_url' => $headlineUrl,
        ':headline_title' => $headlineTitle,
        ':source_slug' => $sourceSlug !== '' ? $sourceSlug : null,
        ':pub_date' => $pubDate,
        ':saved_at' => $savedAt,
    ]);

    $restored = $restoreStmt->fetch(PDO::FETCH_ASSOC);

    if ($restored) {
        echo json_encode([
            'success' => true,
            'status' => 'restored',
            'id' => (int) $restored['id'],
        ]);
        exit;
    }

    $insertSql = "
        INSERT INTO user_saved_headlines (
            user_id,
            headline_hash,
            headline_url,
            headline_title,
            source_slug,
            pub_date,
            saved_at
        )
        VALUES (
            :user_id,
            :headline_hash,
            :headline_url,
            :headline_title,
            :source_slug,
            NULLIF(:pub_date, '')::timestamp,
            :saved_at
        )
        ON CONFLICT (user_id, headline_hash)
        WHERE deleted_at IS NULL
        DO NOTHING
        RETURNING id
    ";

    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        ':user_id' => $userId,
        ':headline_hash' => $headlineHash,
        ':headline_url' => $headlineUrl,
        ':headline_title' => $headlineTitle,
        ':source_slug' => $sourceSlug !== '' ? $sourceSlug : null,
        ':pub_date' => $pubDate,
        ':saved_at' => $savedAt,
    ]);

    $inserted = $insertStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'status' => $inserted ? 'saved' : 'already_saved',
        'id' => $inserted ? (int) $inserted['id'] : null,
    ]);
    exit;
} catch (Throwable $e) {
    error_log('Saved headline save failed: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to save headline',
    ]);
    exit;
}
