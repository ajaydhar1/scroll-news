<?php

$failures = [];
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
};

$endpoints = [
    'reading history save' => [
        'path' => __DIR__ . '/../account/api/reading-history-save.php',
        'identity' => '$userId = $currentUser[\'id\'] ?? null;',
        'unauthorized' => "http_response_code(401);",
        'http_status' => 401,
    ],
    'saved headline save' => [
        'path' => __DIR__ . '/../account/api/saved-headlines-save.php',
        'identity' => '$userId = $currentUser[\'id\'] ?? null;',
        'unauthorized' => "http_response_code(401);",
        'http_status' => 401,
    ],
    'saved headline unsave' => [
        'path' => __DIR__ . '/../account/api/saved-headlines-unsave.php',
        'identity' => '$userId = $currentUser[\'id\'] ?? null;',
        'unauthorized' => "http_response_code(401);",
        'http_status' => 401,
    ],
    'Browse shuffle replay' => [
        'path' => __DIR__ . '/../account/api/get-browse-shuffle-history.php',
        'identity' => '$userId = $currentUser[\'id\'] ?? null;',
        'unauthorized' => "http_response_code(401);",
        'http_status' => 401,
    ],
    'Search shuffle link' => [
        'path' => __DIR__ . '/../account/api/link-search-shuffle.php',
        'identity' => '$currentUserId = $currentUser[\'id\'] ?? null;',
        'unauthorized' => "['success' => false, 'error' => 'Not signed in']",
        'http_status' => 200,
    ],
];

foreach ($endpoints as $name => $expectation) {
    $source = file_get_contents($expectation['path']);
    $bootstrapPosition = strpos($source, "require_once BASE_PATH . '/auth/includes/auth_bootstrap.php';");
    $identityPosition = strpos($source, $expectation['identity']);

    $assert(
        $bootstrapPosition !== false
            && $identityPosition !== false
            && $bootstrapPosition < $identityPosition,
        $name . ' initializes shared auth before reading user identity'
    );
    $assert(
        !str_contains($source, '$_SESSION[\'user_id\']'),
        $name . ' does not bypass remember-me recovery with a raw session read'
    );
    $assert(
        str_contains($source, $expectation['unauthorized']),
        $name . ' preserves its existing unauthorized response'
    );
}

$runUnauthenticatedRequest = static function (string $endpoint): array {
    $runner = 'http_response_code(200);'
        . 'register_shutdown_function(static function (): void {'
        . 'fwrite(STDOUT, "\n__HTTP_STATUS__" . http_response_code());'
        . '});'
        . '$_SERVER["REQUEST_METHOD"] = "POST";'
        . '$_COOKIE = [];'
        . 'require ' . var_export($endpoint, true) . ';';
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $pipes = [];
    $process = proc_open([PHP_BINARY, '-r', $runner], $descriptors, $pipes);

    if (!is_resource($process)) {
        return ['status' => null, 'body' => null, 'raw' => null];
    }

    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    $marker = strrpos($output, "\n__HTTP_STATUS__");
    if ($marker === false) {
        return ['status' => null, 'body' => null, 'raw' => $output];
    }

    $body = trim(substr($output, 0, $marker));
    $status = (int) substr($output, $marker + strlen("\n__HTTP_STATUS__"));
    $jsonStart = strpos($body, '{');
    $jsonEnd = strrpos($body, '}');
    $jsonBody = $jsonStart !== false && $jsonEnd !== false && $jsonEnd >= $jsonStart
        ? substr($body, $jsonStart, $jsonEnd - $jsonStart + 1)
        : '';

    return [
        'status' => $status,
        'body' => json_decode($jsonBody, true),
        'raw' => $jsonBody,
    ];
};

foreach ($endpoints as $name => $expectation) {
    $response = $runUnauthenticatedRequest($expectation['path']);
    $assert(
        $response['status'] === $expectation['http_status'],
        $name . ' preserves its unauthenticated HTTP response status'
    );
    $assert(
        is_array($response['body'])
            && ($response['body']['success'] ?? null) === false
            && !empty($response['body']['error']),
        $name . ' returns its existing JSON failure response without credentials: ' . var_export($response['raw'], true)
    );
}

$bootstrap = file_get_contents(__DIR__ . '/../auth/includes/auth_bootstrap.php');
$assert(
    str_contains($bootstrap, 'if (!$authUser || strtotime($authUser[\'expires_at\']) < time())')
        && str_contains($bootstrap, 'if ((int) $authUser[\'email_verified\'] !== 1)'),
    'Invalid, expired, and unverified remember credentials are not restored'
);

$browseHandler = file_get_contents(__DIR__ . '/../assets/js/newsroom/modules.js');
$browseModal = file_get_contents(__DIR__ . '/../views/modals/___modal_browse.php');
$browseReplay = file_get_contents(__DIR__ . '/../browse-news.php');
$replayEndpoint = file_get_contents(__DIR__ . '/../account/api/get-browse-shuffle-history.php');
$assert(
    !str_contains($browseHandler, 'window.isLoggedIn')
        && !str_contains($browseModal, 'window.isLoggedIn')
        && !str_contains($browseReplay, 'window.isLoggedIn'),
    'Browse News recording is not gated by a stale render-time authentication flag'
);
$assert(
    str_contains($browseHandler, 'You need to be signed in to save shuffle history.')
        && str_contains($browseHandler, 'xhr.status === 401'),
    'Browse News shows authentication failure feedback from the authoritative endpoint'
);
$assert(
    str_contains($browseReplay, '/account/api/get-browse-shuffle-history.php')
        && str_contains($browseReplay, 'shuffle_session_id')
        && str_contains($replayEndpoint, 'FROM shuffle_session_items si')
        && str_contains($replayEndpoint, 'WHERE ss.id = :shuffle_session_id')
        && str_contains($replayEndpoint, 'ss.deleted_at IS NULL')
        && str_contains($replayEndpoint, '\'items\' => $items'),
    'Browse News replay still requests and renders the selected saved shuffle'
);

$readingHistoryClient = file_get_contents(__DIR__ . '/../assets/js/sn_history.js');
$firstLookClient = file_get_contents(__DIR__ . '/../views/home/panels/___first_look.php');
$assert(
    str_contains($readingHistoryClient, "'/account/api/reading-history-save.php'")
        && str_contains($readingHistoryClient, 'item.synced = false')
        && str_contains($readingHistoryClient, 'if (synced)'),
    'Reading history keeps its local unsynced queue and server sync behavior'
);
$assert(
    str_contains($firstLookClient, 'saveLocal(item);')
        && str_contains($firstLookClient, 'saveToAccount(item);')
        && str_contains($firstLookClient, 'unsaveLocal(id);')
        && str_contains($firstLookClient, 'unsaveFromAccount(id);'),
    'Bookmark changes retain their local-first and account-sync behavior'
);

foreach ($failures as $failure) {
    fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
}

if ($failures) {
    exit(1);
}

echo 'PASS: ' . $checks . ' account API authentication bootstrap checks' . PHP_EOL;