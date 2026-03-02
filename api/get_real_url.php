<?php
error_reporting(E_ERROR | E_PARSE);
define('BASE_PATH', dirname(__DIR__)); // /api -> project root
require_once BASE_PATH . '/core/___modules.php';

header('Content-Type: application/json');

if (!isset($_GET['link']) || empty($_GET['link'])) {
    echo json_encode(['error' => 'Missing link']);
    exit;
}

$google_news_url = $_GET['link'];

$url = 'https://news-nlp-api-08865bb82971.herokuapp.com/resolve_google_news_url';

// azeo_getData returns decoded JSON as array
$arr = azeo_getData($url . '?url=' . urlencode($google_news_url));

if (isset($arr['resolved_url'])) {
    echo json_encode(['resolved_url' => $arr['resolved_url']]);
} else {
    echo json_encode(['error' => 'Could not resolve URL']);
}
