<?php
// screenshot.php — stream article screenshot from DB by id

http_response_code(410);
exit('Deprecated endpoint');

ini_set('display_errors', '1');              // you can turn these off later
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__)); // /api -> project root
require_once BASE_PATH . '/core/___modules.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('Bad request');
}

$pdo = _pdo_or_null();
if (!$pdo) {
    http_response_code(500);
    exit('No DB connection');
}

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

$bytes = $row['screenshot_bytes'];

// ❗ Convert stream resource → string
if (is_resource($bytes)) {
    $bytes = stream_get_contents($bytes);
}

if (!is_string($bytes) || $bytes === '') {
    http_response_code(500);
    exit('Empty screenshot data');
}

// If you know your screenshotter saves PNGs, keep this.
// Change to image/jpeg if they're JPEGs.
$mime = 'image/png';

header('Content-Type: ' . $mime);
// You can skip Content-Length while debugging; uncomment later if you want
// header('Content-Length: ' . strlen($bytes));
header('Cache-Control: public, max-age=86400');

echo $bytes;
exit;
