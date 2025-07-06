<?php
error_reporting(E_ERROR | E_PARSE);
require_once('___modules.php');

header('Content-Type: application/json');

if (!isset($_GET['link']) || empty($_GET['link'])) {
    echo json_encode(['error' => 'Missing link']);
    exit;
}

$google_news_url = $_GET['link'];

$url = 'https://news-nlp-api-08865bb82971.herokuapp.com/resolve_google_news_url';

// azeo_postData returns decoded JSON as array
$arr = azeo_postData($url, 'google_news_url=' . urlencode($google_news_url));

if (isset($arr['resolved_url'])) {
    echo json_encode(['resolved_url' => $arr['resolved_url']]);
} else {
    echo json_encode(['error' => 'Could not resolve URL']);
}
