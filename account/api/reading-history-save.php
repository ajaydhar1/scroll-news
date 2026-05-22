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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON',
    ]);
    exit;
}

$url = trim((string)($data['url'] ?? ''));
$title = trim((string)($data['title'] ?? ''));

if ($url === '' || $title === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields',
    ]);
    exit;
}

$source = trim((string)($data['source'] ?? ''));
$image = trim((string)($data['image'] ?? ''));
$kind = trim((string)($data['kind'] ?? 'external'));
$pubDate = trim((string)($data['pub_date'] ?? ''));
$viewedAt = trim((string)($data['clicked_at'] ?? $data['viewed_at'] ?? ''));

$allowedKinds = ['external', 'analyze'];
if (!in_array($kind, $allowedKinds, true)) {
    $kind = 'external';
}

$articleId = null;
$rssItemId = null;

try {
    /*
     * Try to enrich the history row by matching the clicked URL
     * to rss_items.link and articles.rss_item_id.
     */
    $lookupSql = "
        SELECT
            ri.id AS rss_item_id,
            a.id AS article_id
        FROM rss_items ri
        LEFT JOIN articles a ON a.rss_item_id = ri.id
        WHERE ri.link = :url
        LIMIT 1
    ";

    $pdo = auth_db();

    $lookupUrl = $url;

    $parsed = parse_url($url);

    if (!empty($parsed['query'])) {
        parse_str($parsed['query'], $queryParams);

        if (!empty($queryParams['url'])) {
            $lookupUrl = $queryParams['url'];
        }
    }

    $lookupStmt = $pdo->prepare($lookupSql);
    $lookupStmt->execute(['url' => $lookupUrl]);
    $match = $lookupStmt->fetch(PDO::FETCH_ASSOC);

    if ($match) {
        $rssItemId = $match['rss_item_id'] ?? null;
        $articleId = $match['article_id'] ?? null;
    }

    $insertSql = "
        INSERT INTO user_reading_history (
            user_id,
            article_id,
            rss_item_id,
            url,
            title,
            source,
            image,
            pub_date,
            kind,
            viewed_at,
            created_at,
            updated_at
        ) VALUES (
            :user_id,
            :article_id,
            :rss_item_id,
            :url,
            :title,
            :source,
            :image,
            NULLIF(:pub_date, '')::timestamp,
            :kind,
            COALESCE(NULLIF(:viewed_at, '')::timestamp, CURRENT_TIMESTAMP),
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP
        )
    ";

    $stmt = $pdo->prepare($insertSql);
    $stmt->execute([
        'user_id' => $userId,
        'article_id' => $articleId,
        'rss_item_id' => $rssItemId,
        'url' => $url,
        'title' => $title,
        'source' => $source,
        'image' => $image,
        'pub_date' => $pubDate,
        'kind' => $kind,
        'viewed_at' => $viewedAt,
    ]);

    echo json_encode([
        'success' => true,
        'article_id' => $articleId,
        'rss_item_id' => $rssItemId,
    ]);
    exit;
} catch (Throwable $e) {
    error_log('Reading history save failed: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to save reading history',
    ]);
    exit;
}
