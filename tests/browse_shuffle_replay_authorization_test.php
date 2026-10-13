<?php

require_once __DIR__ . '/../core/community_trail_privacy.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT NOT NULL, community_trail_sharing BOOLEAN NOT NULL DEFAULT 0)');
$pdo->exec("INSERT INTO users (id, email, community_trail_sharing) VALUES
    (10, 'owner@example.com', TRUE),
    (11, 'private@example.com', FALSE),
    (12, 'ajaytest2@sharklasers.com', TRUE)");

$now = new DateTimeImmutable('2026-10-13 12:00:00', new DateTimeZone('UTC'));
$trailDate = '2026-10-12';
$failures = [];
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
};

$canReplay = static function (
    int $ownerId,
    string $ownerEmail,
    int $viewerId,
    bool $active = true,
    bool $ownerIsActive = true,
    bool $hasPublicTrailKey = true,
    string $date = '2026-10-12',
    int $activityCount = 3,
    bool $sessionBelongs = true
) use ($pdo, $now): bool {
    return sn_browse_shuffle_replay_authorized(
        $pdo,
        $ownerId,
        $ownerEmail,
        $viewerId,
        $active,
        $ownerIsActive,
        $hasPublicTrailKey,
        $date,
        $activityCount,
        $sessionBelongs,
        $now
    );
};

$assert($canReplay(11, 'private@example.com', 11, hasPublicTrailKey: false, activityCount: 0, sessionBelongs: false), 'Owner can replay their active private shuffle');
$assert($canReplay(10, 'owner@example.com', 20), 'Another signed-in user can replay a shuffle in an opted-in eligible Community Trail');
$assert(!$canReplay(11, 'private@example.com', 20), 'Private session cannot be replayed by another user');
$assert(!$canReplay(10, 'owner@example.com', 20, activityCount: 2), 'Community trail below the three-distinct-activity threshold is denied');
$assert(!$canReplay(10, 'owner@example.com', 20, sessionBelongs: false), 'Shuffle not included in the eligible trail is denied');
$assert(!$canReplay(10, 'owner@example.com', 20, date: '2026-06-12'), 'Community trail outside the existing visibility window is denied');
$assert(!$canReplay(10, 'owner@example.com', 20, hasPublicTrailKey: false), 'Community replay without an addressable public trail is denied');
$assert(!$canReplay(10, 'owner@example.com', 20, ownerIsActive: false), 'Deleted/inactive trail owner cannot grant shared access');
$assert(!$canReplay(12, 'ajaytest2@sharklasers.com', 20), 'Configured editor accounts remain excluded from Community Trail sharing');
$pdo->exec('UPDATE users SET community_trail_sharing = FALSE WHERE id = 10');
$assert(!$canReplay(10, 'owner@example.com', 20), 'Disabling sharing immediately revokes non-owner replay');
$assert($canReplay(10, 'owner@example.com', 10), 'Owner access remains available after Community Trail sharing is disabled');
$assert(!$canReplay(10, 'owner@example.com', 20, active: false), 'Deleted shuffle session is denied for non-owner access');
$assert(!$canReplay(10, 'owner@example.com', 10, active: false), 'Deleted shuffle session is denied to its owner');

$endpoint = file_get_contents(__DIR__ . '/../account/api/get-browse-shuffle-history.php');
$parentLoadPosition = strpos($endpoint, '$sessionStmt->execute([');
$authorizationPosition = strpos($endpoint, 'if (!sn_browse_shuffle_replay_authorized(');
$itemLoadPosition = strpos($endpoint, '$stmt->execute([');
$assert(
    $parentLoadPosition !== false
        && $authorizationPosition !== false
        && $itemLoadPosition !== false
        && $parentLoadPosition < $authorizationPosition
        && $authorizationPosition < $itemLoadPosition,
    'Parent session authorization occurs before item retrieval'
);
$assert(
    str_contains($endpoint, "AND ss.deleted_at IS NULL")
        && str_contains($endpoint, "AND ss.source_context = 'browse_news_modal'")
        && str_contains($endpoint, 'INNER JOIN shuffle_sessions ss')
        && str_contains($endpoint, 'ON ss.id = si.shuffle_session_id')
        && str_contains($endpoint, "http_response_code(404);")
        && str_contains($endpoint, "'error' => 'Shuffle not found'"),
    'Deleted, non-Browse, private, and nonexistent sessions share a non-disclosing not-found response'
);
$assert(
    str_contains($endpoint, 'COUNT(DISTINCT LOWER(TRIM(item_url)))')
        && str_contains($endpoint, 'BOOL_OR(shuffle_session_id = :shuffle_session_id)')
        && str_contains($endpoint, "NOW() - INTERVAL '2 months'")
        && str_contains($endpoint, "(created_at - INTERVAL '4 hours')::date"),
    'Shared replay verifies distinct activity threshold, exact session membership, and existing trail date window'
);

$runRequest = static function (bool $withInvalidRememberCookie): array {
    $endpointPath = __DIR__ . '/../account/api/get-browse-shuffle-history.php';
    $runner = 'http_response_code(200);'
        . 'register_shutdown_function(static function (): void {'
        . 'fwrite(STDOUT, "\n__HTTP_STATUS__" . http_response_code());'
        . '});'
        . '$_SERVER["REQUEST_METHOD"] = "GET";'
        . '$_GET["shuffle_session_id"] = "nonexistent-test-id";'
        . '$_COOKIE = ' . ($withInvalidRememberCookie
            ? '["scroll_news_session" => "invalid-test-credential"]'
            : '[]') . ';'
        . 'require ' . var_export($endpointPath, true) . ';';
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $pipes = [];
    $process = proc_open([PHP_BINARY, '-r', $runner], $descriptors, $pipes);

    if (!is_resource($process)) {
        return ['status' => null, 'body' => null];
    }

    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    $marker = strrpos($output, "\n__HTTP_STATUS__");
    if ($marker === false) {
        return ['status' => null, 'body' => null];
    }

    $status = (int) substr($output, $marker + strlen("\n__HTTP_STATUS__"));
    $responseText = substr($output, 0, $marker);
    $jsonStart = strpos($responseText, '{');
    $jsonEnd = strrpos($responseText, '}');
    $json = $jsonStart !== false && $jsonEnd !== false && $jsonEnd >= $jsonStart
        ? substr($responseText, $jsonStart, $jsonEnd - $jsonStart + 1)
        : '';

    return [
        'status' => $status,
        'body' => json_decode($json, true),
    ];
};

$unauthenticated = $runRequest(false);
$assert($unauthenticated['status'] === 401, 'Unauthenticated replay request remains unauthorized');
$assert(($unauthenticated['body']['error'] ?? null) === 'Unauthorized', 'Unauthenticated response retains the existing authorization contract');

$invalidCredential = $runRequest(true);
$assert($invalidCredential['status'] === 401, 'Invalid remember-me credential does not restore authentication');
$assert(($invalidCredential['body']['error'] ?? null) === 'Unauthorized', 'Invalid remember-me credential receives the unauthorized response');

foreach ($failures as $failure) {
    fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
}

if ($failures) {
    exit(1);
}

echo 'PASS: ' . $checks . ' Browse shuffle replay authorization checks' . PHP_EOL;
