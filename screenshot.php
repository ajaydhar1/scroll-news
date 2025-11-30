<?php
// screenshot.php — stream article screenshot from DB by id

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/___modules.php';


$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('Bad request');
}

$pdo = _pdo_or_null();

$sql = "
    SELECT screenshot_bytes
    FROM articles
    WHERE id = :id AND screenshot_bytes IS NOT NULL
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    exit('Not found');
}

// If you have a screenshot_mime_type column, use that instead of hardcoding:
$mime = 'image/png'; // or 'image/jpeg' depending on how you store them

$bytes = $row['screenshot_bytes'];

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($bytes));
// cache for a day — optional but nice
header('Cache-Control: public, max-age=86400');

echo $bytes;
exit;
