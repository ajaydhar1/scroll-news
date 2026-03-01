<?php
// includes/analysis_helpers.php

if (!function_exists('_pdo_or_null')) {
    require_once __DIR__ . '/___modules.php';
}

function analysis_fail(string $msg, ?Throwable $e = null, bool $debug = false): void {
    error_log('[Analysis] ' . $msg . ($e ? (' | ' . $e->getMessage()) : ''));
    if ($debug) {
        echo '<div class="alert alert-warning small" style="margin:10px 0;">';
        echo '<strong>Analysis error:</strong> ' . htmlspecialchars($msg);
        if ($e) echo '<br><code>' . htmlspecialchars($e->getMessage()) . '</code>';
        echo '</div>';
    }
}

function favicon_for_domain(string $domain): ?string {
    $domain = trim(strtolower($domain));
    if ($domain === '') return null;

    $url = 'https://' . $domain;

    return 'https://t0.gstatic.com/faviconV2'
        . '?client=SOCIAL&type=FAVICON'
        . '&fallback_opts=TYPE,SIZE,URL'
        . '&url=' . rawurlencode($url)
        . '&size=32';
}

function get_param(string $k, $default = null) {
    return isset($_GET[$k]) ? trim((string)$_GET[$k]) : $default;
}