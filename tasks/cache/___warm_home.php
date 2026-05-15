<?php

define('BASE_PATH', dirname(__DIR__, 2)); // /tasks/cache -> project root
require_once BASE_PATH . '/core/___modules.php';
require_once BASE_PATH . '/core/home/___first_look_helpers.php';

$bust = true;

// Optional: simple secret to prevent random people from hitting it
$secret = getenv('WARM_SECRET') ?: '';

if ($secret !== '' && (($_GET['key'] ?? '') !== $secret)) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

/**
 * Render panel HTML once,
 * then write to:
 * - local fragment cache
 * - persistent DB cache
 */
function warm_and_store_panel(
    string $cache_key,
    int $ttl,
    int $stale_ttl,
    string $include_path
): void {

    global $CACHE_VER;

    /**
     * Render HTML ONCE
     */
    ob_start();

    include $include_path;

    $html = ob_get_clean();

    // Safety
    if (trim($html) === '') {

        error_log(
            "warm_and_store_panel: empty HTML for {$cache_key}"
        );

        return;
    }

    /**
     * Warm local fragment cache
     */
    fragment_cache_swr(
        "{$cache_key}_{$CACHE_VER}",
        $ttl,
        $stale_ttl,
        function () use ($html) {
            echo $html;
        },
        true,  // bust
        true,  // warm_only
        false
    );

    /**
     * Persist HTML into DB cache
     */
    upsert_cache(
        cache_key: $cache_key,
        cache_group: 'homepage',
        html: $html,
        expires_in_seconds: $ttl,
        build_version: $CACHE_VER,
        meta: [
            'source' => basename($include_path),
            'generated_by' => '___warm_home.php'
        ]
    );
}

/**
 * Homepage panels
 */

warm_and_store_panel(
    'homepage_news_intel_panel',
    120,
    600,
    BASE_PATH . '/views/home/panels/___news_intel_panel.php'
);

warm_and_store_panel(
    'homepage_active_stories',
    60,
    300,
    BASE_PATH . '/views/home/panels/___active_stories.php'
);

// 1) Prime First Look JSON cache
ob_start();

$_GET['warm'] = '1';
include BASE_PATH . '/views/home/panels/___first_look.php';
unset($_GET['warm']);

ob_end_clean();


// 2) Now render actual First Look HTML and store it
ob_start();

include BASE_PATH . '/views/home/panels/___first_look.php';

$firstLookHtml = ob_get_clean();

if (trim($firstLookHtml) !== '') {
    upsert_cache(
        cache_key: 'homepage_first_look',
        cache_group: 'homepage',
        html: $firstLookHtml,
        expires_in_seconds: 300,
        build_version: $CACHE_VER,
        meta: [
            'source' => '___first_look.php',
            'generated_by' => '___warm_home.php'
        ]
    );
} else {
    error_log("___warm_home.php: First Look rendered empty; not storing DB cache");
}

warm_and_store_panel(
    'homepage_scroll_strip',
    120,
    600,
    BASE_PATH . '/views/home/panels/___scroll_strip.php'
);

header("Content-Type: text/plain");

echo "OK\n";