<?php
// includes/analysis_helpers.php

require_once BASE_PATH . '/core/config/sentiment_thresholds.php';
require_once BASE_PATH . '/core/utils/___sentiment.php';

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

/**
 * Postgres SQL CASE that buckets sentiment from score stored in nlp JSON.
 * $nlpCol should include table alias if needed (ex: "a.nlp")
 */
function sn_sentiment_bucket_sql(string $nlpCol = 'nlp'): string {
    $pos = SN_SENT_POS;
    $neg = SN_SENT_NEG;
    return "
      CASE
        WHEN ($nlpCol->'sentiment'->>'score') IS NULL THEN 'unknown'
        WHEN ($nlpCol->'sentiment'->>'score')::float >= {$pos} THEN 'positive'
        WHEN ($nlpCol->'sentiment'->>'score')::float <= {$neg} THEN 'negative'
        ELSE 'neutral'
      END
    ";
}