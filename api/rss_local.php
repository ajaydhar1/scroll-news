<?php
// /api/rss_local.php — return JSON items for Browse News modal from DB
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

define('BASE_PATH', dirname(__DIR__)); // /api -> project root
require_once BASE_PATH . '/core/___modules.php';

$feed = trim((string)($_POST['feed'] ?? ''));
if ($feed === '') {
  echo json_encode(['items' => []], JSON_UNESCAPED_SLASHES);
  exit;
}

// Expect feed like: /rss.php?category=Politics
$category = '';
$parts = parse_url($feed);
if (!empty($parts['query'])) {
  parse_str($parts['query'], $q);
  $category = trim((string)($q['category'] ?? ''));
}
if ($category === '') {
  // fallback: allow passing category directly
  $category = trim((string)($_POST['category'] ?? ''));
}

$pdo = _pdo_or_null();
$dbExplain = null;

if (!$pdo && function_exists('getPdo')) {
  try {
    $pdo = getPdo();
  } catch (Throwable $e) {
    $dbExplain = [
      'message' => $e->getMessage(),
      'code' => $e->getCode(),
    ];
  }
}

if (!$pdo) {
  http_response_code(500);

  $debug = [
    'error' => 'DB connection not available',
    'has_getPdo' => function_exists('getPdo'),
    'has_getPdoOrExplain' => function_exists('getPdoOrExplain'),
    'has_DATABASE_URL' => (bool)getenv('DATABASE_URL'),
    'has_DB_HOST' => (bool)getenv('DB_HOST'),
    'has_DB_NAME' => (bool)getenv('DB_NAME'),
    'has_DB_USER' => (bool)getenv('DB_USER'),
    'db_explain' => $dbExplain,
    'cwd' => getcwd(),
    'base_path' => defined('BASE_PATH') ? BASE_PATH : null,
  ];

  echo json_encode($debug, JSON_UNESCAPED_SLASHES);
  exit;
}

// V1: you currently don’t have category in articles, so ignore category for now.
// If later you add category or store it in nlp, we can filter here.
$LIMIT = 50;

$sql = "
  SELECT
    title,
    url AS link,
    source_slug,
    pub_date,
    media_url AS image_url,
    description
  FROM articles
  WHERE
    title IS NOT NULL
    AND url IS NOT NULL
    AND pub_date IS NOT NULL
    AND LOWER(source_slug) = LOWER(:category)
  ORDER BY pub_date DESC
  LIMIT {$LIMIT}
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':category' => $category]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$items = [];
foreach ($rows as $r) {
  $link = (string)($r['link'] ?? '');
  if ($link === '') continue;

  $host = (string)(parse_url($link, PHP_URL_HOST) ?: '');

  $pubTs = !empty($r['pub_date']) ? strtotime((string)$r['pub_date']) : null;

  $desc = (string)($r['description'] ?? '');
  $desc = html_entity_decode($desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $desc = preg_replace('/<img\b[^>]*>/i', '', $desc);   // remove any inline images
  $desc = strip_tags($desc);
  $desc = preg_replace('/\s+/', ' ', trim($desc));

  $items[] = [
    'title'          => (string)($r['title'] ?? ''),
    'link'           => $link,
    'publisher'      => $host,
    'pubDate'        => $pubTs ? gmdate('c', $pubTs) : null,
    'pubDateForLink' => $pubTs ? (int)$pubTs : '',          // ✅ timestamp for newsroom.php
    'description'    => $desc,
    'image'          => (string)($r['image_url'] ?? ''),
    // optional, in case you want it later:
    'category'       => $category,
  ];
}

echo json_encode(['items' => $items], JSON_UNESCAPED_SLASHES);