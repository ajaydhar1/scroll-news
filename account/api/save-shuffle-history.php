<?php

define('BASE_PATH', dirname(__DIR__, 2));

require_once BASE_PATH . '/auth/includes/auth_db.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'You must be signed in to save shuffle history.'
    ]);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON payload.'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

$sourceContext = $data['source_context'] ?? '';
$shuffleType = $data['shuffle_type'] ?? '';
$query = $data['query'] ?? null;
$seed = $data['seed'] ?? null;
$algorithmVersion = $data['algorithm_version'] ?? 'v1';
$filters = $data['filters'] ?? null;
$snapshot = $data['snapshot'] ?? null;
$results = $data['results'] ?? [];

$allowedSourceContexts = [
    'search_results',
    'browse_news_modal'
];

$allowedShuffleTypes = [
    'search_shuffle',
    'browse_shuffle'
];

if (!in_array($sourceContext, $allowedSourceContexts, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid source_context.'
    ]);
    exit;
}

if (!in_array($shuffleType, $allowedShuffleTypes, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid shuffle_type.'
    ]);
    exit;
}

if (!is_array($results) || count($results) === 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'No shuffle results were provided.'
    ]);
    exit;
}

$pdo = null;

try {

    $pdo = auth_db();

    $pdo->beginTransaction();

    $sessionStmt = $pdo->prepare("
        INSERT INTO shuffle_sessions (
            user_id,
            source_context,
            shuffle_type,
            query,
            seed,
            algorithm_version,
            results_count,
            filters_json,
            snapshot_json
        ) VALUES (
            :user_id,
            :source_context,
            :shuffle_type,
            :query,
            :seed,
            :algorithm_version,
            :results_count,
            :filters_json,
            :snapshot_json
        )
        RETURNING id
    ");

    $sessionStmt->execute([
        ':user_id' => $userId,
        ':source_context' => $sourceContext,
        ':shuffle_type' => $shuffleType,
        ':query' => $query,
        ':seed' => $seed,
        ':algorithm_version' => $algorithmVersion,
        ':results_count' => count($results),
        ':filters_json' => $filters ? json_encode($filters) : null,
        ':snapshot_json' => $snapshot ? json_encode($snapshot) : null
    ]);

    $shuffleSessionId = $sessionStmt->fetchColumn();

    $itemStmt = $pdo->prepare("
        INSERT INTO shuffle_session_items (
            shuffle_session_id,
            position,
            article_id,
            url,
            title,
            article_description,
            source_name,
            pub_date,
            image_url
        ) VALUES (
            :shuffle_session_id,
            :position,
            :article_id,
            :url,
            :title,
            :article_description,
            :source_name,
            :pub_date,
            :image_url
        )
    ");

    foreach ($results as $index => $item) {

        $position = $item['position'] ?? ($index + 1);

        if (empty($item['url']) || empty($item['title'])) {
            continue;
        }

        $itemStmt->execute([
            ':shuffle_session_id' => $shuffleSessionId,
            ':position' => $position,
            ':article_id' => $item['article_id'] ?? null,
            ':url' => $item['url'],
            ':title' => $item['title'],
            ':article_description' => $item['article_description'] ?? null,
            ':source_name' => $item['source_name'] ?? null,
            ':pub_date' => $item['pub_date'] ?? null,
            ':image_url' => $item['image_url'] ?? null
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'shuffle_session_id' => $shuffleSessionId
    ]);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Shuffle history save failed: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Shuffle history could not be saved.'
    ]);
    exit;
}
