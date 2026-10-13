<?php

define('BASE_PATH', dirname(__DIR__, 2));

require_once BASE_PATH . '/auth/includes/auth_bootstrap.php';
require_once BASE_PATH . '/core/community_trail_privacy.php';

header('Content-Type: application/json');

$userId = $currentUser['id'] ?? null;

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
        si.article_id,
        si.position,
        si.url AS link,
        si.title,
        si.article_description,
        si.source_name,
        si.pub_date,
        si.image_url
    FROM shuffle_session_items si
    INNER JOIN shuffle_sessions ss
        ON ss.id = si.shuffle_session_id
       AND ss.deleted_at IS NULL
       AND ss.source_context = 'browse_news_modal'
    WHERE ss.id = :shuffle_session_id
    ORDER BY si.position ASC
";

try {

    $pdo = auth_db();

    $sessionStmt = $pdo->prepare("
        SELECT
            ss.id,
            ss.user_id,
            ss.source_context,
            ss.created_at,
            u.email,
            u.public_trail_key,
            u.deleted_at
        FROM shuffle_sessions ss
        INNER JOIN users u ON u.id = ss.user_id
        WHERE ss.id = :shuffle_session_id
          AND ss.deleted_at IS NULL
          AND ss.source_context = 'browse_news_modal'
        LIMIT 1
    ");
    $sessionStmt->execute([
        ':shuffle_session_id' => $shuffleSessionId,
    ]);
    $shuffleSession = $sessionStmt->fetch(PDO::FETCH_ASSOC);

    if (!$shuffleSession) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Shuffle not found']);
        exit;
    }

    $ownerId = (int) $shuffleSession['user_id'];
    $viewerId = (int) $userId;
    $trailDate = (new DateTimeImmutable((string) $shuffleSession['created_at'], new DateTimeZone('UTC')))
        ->modify('-4 hours')
        ->format('Y-m-d');
    $eligibility = [];

    if ($ownerId !== $viewerId) {
        $eligibilityStmt = $pdo->prepare("
            WITH activity_raw AS (
                SELECT
                    user_id,
                    (viewed_at - INTERVAL '4 hours')::date AS trail_date,
                    url AS item_url,
                    NULL::text AS shuffle_session_id
                FROM user_reading_history
                WHERE deleted_at IS NULL
                  AND viewed_at >= NOW() - INTERVAL '2 months'
                  AND url IS NOT NULL
                  AND url <> ''

                UNION ALL

                SELECT
                    user_id,
                    (saved_at - INTERVAL '4 hours')::date AS trail_date,
                    headline_url AS item_url,
                    NULL::text AS shuffle_session_id
                FROM user_saved_headlines
                WHERE deleted_at IS NULL
                  AND saved_at >= NOW() - INTERVAL '2 months'
                  AND headline_url IS NOT NULL
                  AND headline_url <> ''

                UNION ALL

                SELECT
                    user_id,
                    (created_at - INTERVAL '4 hours')::date AS trail_date,
                    '/search.php?q=' || replace(query, ' ', '+') ||
                        '&range=' || COALESCE(range, 'all') ||
                        '&mode=' || COALESCE(mode, 'classic') ||
                        '&deep_dive=' || COALESCE(params_json->>'deep_dive', '') ||
                        '&high_signal=' || COALESCE(params_json->>'high_signal', '') AS item_url,
                    NULL::text AS shuffle_session_id
                FROM user_search_history
                WHERE deleted_at IS NULL
                  AND created_at >= NOW() - INTERVAL '2 months'
                  AND shuffle_session_uuid IS NULL
                  AND query IS NOT NULL
                  AND query <> ''

                UNION ALL

                SELECT
                    user_id,
                    (created_at - INTERVAL '4 hours')::date AS trail_date,
                    CASE
                        WHEN source_context = 'search_results'
                            THEN '/search.php?shuffle_session=' || id::text
                        WHEN source_context = 'browse_news_modal'
                            THEN '/browse-news.php?shuffle_session_id=' || id::text
                    END AS item_url,
                    id::text AS shuffle_session_id
                FROM shuffle_sessions
                WHERE deleted_at IS NULL
                  AND created_at >= NOW() - INTERVAL '2 months'
                  AND source_context IN ('search_results', 'browse_news_modal')
            )
            SELECT
                COUNT(DISTINCT LOWER(TRIM(item_url))) AS distinct_activity_count,
                BOOL_OR(shuffle_session_id = :shuffle_session_id) AS shuffle_belongs_to_trail
            FROM activity_raw
            WHERE user_id = :owner_id
              AND trail_date = CAST(:trail_date AS date)
              AND item_url IS NOT NULL
              AND item_url <> ''
        ");
        $eligibilityStmt->execute([
            ':shuffle_session_id' => (string) $shuffleSession['id'],
            ':owner_id' => $ownerId,
            ':trail_date' => $trailDate,
        ]);
        $eligibility = $eligibilityStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    if (!sn_browse_shuffle_replay_authorized(
        $pdo,
        $ownerId,
        (string) $shuffleSession['email'],
        $viewerId,
        $shuffleSession['source_context'] === 'browse_news_modal' && $shuffleSession['deleted_at'] === null,
        $shuffleSession['deleted_at'] === null,
        trim((string) ($shuffleSession['public_trail_key'] ?? '')) !== '',
        $trailDate,
        (int) ($eligibility['distinct_activity_count'] ?? 0),
        in_array(strtolower((string) ($eligibility['shuffle_belongs_to_trail'] ?? '')), ['1', 't', 'true'], true)
    )) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Shuffle not found']);
        exit;
    }

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
