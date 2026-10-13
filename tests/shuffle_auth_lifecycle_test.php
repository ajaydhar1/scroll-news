<?php

$failures = [];
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
};

$trailsPage = file_get_contents(__DIR__ . '/../news-trails.php');
$trailPlayer = file_get_contents(__DIR__ . '/../news-trail-player.php');
$saveEndpoint = file_get_contents(__DIR__ . '/../account/api/save-shuffle-history.php');
$authBootstrap = file_get_contents(__DIR__ . '/../auth/includes/auth_bootstrap.php');
$browseHandler = file_get_contents(__DIR__ . '/../assets/js/newsroom/modules.js');
$searchPage = file_get_contents(__DIR__ . '/../search.php');

$trailsBootstrapPosition = strpos($trailsPage, "require_once BASE_PATH . '/auth/includes/auth_bootstrap.php';");
$trailsIdentityPosition = strpos($trailsPage, '$currentUserId =');
$assert(
    $trailsBootstrapPosition !== false
        && $trailsIdentityPosition !== false
        && $trailsBootstrapPosition < $trailsIdentityPosition,
    'News Trails bootstraps authentication before deriving the trail user'
);
$assert(
    str_contains($trailsPage, '$currentUser[\'id\']')
        && !str_contains($trailsPage, '$_SESSION[\'user_id\'] ?? null'),
    'News Trails derives its identity from the shared authenticated user context'
);
$playerBootstrapPosition = strpos($trailPlayer, "require_once BASE_PATH . '/auth/includes/auth_bootstrap.php';");
$playerIdentityPosition = strpos($trailPlayer, '$sessionUserId =');
$assert(
    $playerBootstrapPosition !== false
        && $playerIdentityPosition !== false
        && $playerBootstrapPosition < $playerIdentityPosition,
    'Trail playback bootstraps authentication before evaluating owner access'
);
$assert(
    str_contains($trailPlayer, '$currentUser[\'id\']')
        && !str_contains($trailPlayer, '$_SESSION[\'user_id\']'),
    'Trail playback uses the recovered authenticated identity'
);
$assert(
    str_contains($saveEndpoint, "require_once BASE_PATH . '/auth/includes/auth_bootstrap.php';")
        && str_contains($saveEndpoint, 'if (empty($currentUser[\'id\']))')
        && str_contains($saveEndpoint, 'http_response_code(401)'),
    'Shuffle persistence uses bootstrap recovery and preserves unauthorized responses'
);
$assert(
    str_contains($authBootstrap, '$_COOKIE[$rememberCookieName]')
        && str_contains($authBootstrap, '$_SESSION[\'user_id\'] = $authUser[\'user_id\']')
        && str_contains($authBootstrap, 'if (!$authUser || strtotime($authUser[\'expires_at\']) < time())'),
    'Authentication bootstrap restores valid remember credentials and rejects expired credentials'
);
$assert(
    str_contains($browseHandler, 'url: "/account/api/save-shuffle-history.php"')
        && str_contains($browseHandler, 'response.success === true && response.shuffle_session_id')
        && str_contains($browseHandler, 'Shuffle saved.')
        && str_contains($browseHandler, 'xhr.status === 401')
        && str_contains($browseHandler, 'This shuffle could not be saved.'),
    'Browse News reports success only after a confirmed save and visibly reports failures'
);
$assert(
    str_contains($searchPage, "fetch('/account/api/save-shuffle-history.php'")
        && str_contains($searchPage, 'data.success !== true')
        && str_contains($searchPage, 'data.shuffle_session_id')
        && str_contains($searchPage, 'Shuffle saved.')
        && str_contains($searchPage, 'error.status === 401'),
    'Search requires a confirmed saved session and visibly reports authentication failures'
);

foreach ($failures as $failure) {
    fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
}

if ($failures) {
    exit(1);
}

echo 'PASS: ' . $checks . ' shuffle authentication lifecycle checks' . PHP_EOL;