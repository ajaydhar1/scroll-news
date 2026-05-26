<?php

define('BASE_PATH', dirname(__DIR__, 2));

require_once BASE_PATH . '/auth/includes/auth_db.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Adjust this depending on your auth helper/session shape
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$shuffleSessionId = $_GET['shuffle_session_id'] ?? $_POST['shuffle_session_id'] ?? '';

if ($shuffleSessionId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing shuffle_session_id']);
    exit;
}

$sql = "
    SELECT
        article_id,
        position,
        url AS link,
        title,
        article_description,
        source_name,
        pub_date,
        image_url
    FROM shuffle_session_items
    WHERE shuffle_session_id = :shuffle_session_id
    ORDER BY position ASC
";

try {

    $pdo = auth_db();

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':shuffle_session_id' => $shuffleSessionId
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];

    foreach ($rows as $r) {

        $link = (string)($r['link'] ?? '');

        if ($link === '') {
            continue;
        }

        $pubTs = !empty($r['pub_date'])
            ? strtotime((string)$r['pub_date'])
            : null;

        $items[] = [
            'articleId'      => $r['article_id'] ?? '',
            'title'          => (string)($r['title'] ?? ''),
            'link'           => $link,
            'publisher'      => (string)($r['source_name'] ?? ''),
            'pubDate'        => $pubTs ? gmdate('c', $pubTs) : null,
            'pubDateForLink' => $pubTs ? (int)$pubTs : '',
            'description'    => (string)($r['article_description'] ?? ''),
            'image'          => (string)($r['image_url'] ?? ''),
            'position'       => (int)($r['position'] ?? 0),
        ];
    }

    echo json_encode([
        'success' => true,
        'items' => $items
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Failed to load shuffle history'
    ]);
}
