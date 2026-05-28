<?php

define('BASE_PATH', dirname(__DIR__, 2));

require_once BASE_PATH . "/core/___modules.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$currentUserId = $_SESSION['user_id'] ?? null;

if (!$currentUserId) {
    echo json_encode(['success' => false, 'error' => 'Not signed in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$searchHistoryId = (int) ($input['search_history_id'] ?? 0);
$shuffleSessionUuid = trim((string) ($input['shuffle_session_uuid'] ?? ''));

if (!$searchHistoryId || $shuffleSessionUuid === '') {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$pdo = _pdo_or_null();

$sql = "
    UPDATE user_search_history
    SET shuffle_session_uuid = :shuffle_session_uuid
    WHERE id = :id
      AND user_id = :user_id
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':shuffle_session_uuid' => $shuffleSessionUuid,
    ':id' => $searchHistoryId,
    ':user_id' => (int) $currentUserId,
]);

echo json_encode([
    'success' => $stmt->rowCount() > 0,
]);