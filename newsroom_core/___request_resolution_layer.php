<?php
// newsroom_core/request_resolution_layer.php
require_once BASE_PATH . '/vendor/opengraph/OpenGraph.php';
require_once BASE_PATH . '/vendor/feed/Feed.php';

function newsroom_resolve_article(): array {
    // If no URL, pick a random and redirect (preserves your current behavior)
    if (empty($_GET['url'])) {

        try { 
            $random = getRecentWeightedArticle_forStumble_fromDB(); // ['category'=>..., 'link'=>...]
        
        } catch (Throwable $e) {
            error_log("getRecentWeightedArticle_forStumble_fromDB DB error: " . $e->getMessage());
        }

        $redirect_url = 'Location: newsroom.php?url=' . urlencode($random['link']) . '&category=' . urlencode($random['category']) . '&pub_date=' . urlencode($random['pub_date']);

         if (!empty($random['source']) && $random['source'] === 'db') {
            $redirect_url .= '&db=1';
        }

        header($redirect_url);
        exit;
    }
    // Normalize / sanitize URL a bit
    $url = trim($_GET['url']);
    if (!preg_match('~^https?://~i', $url)) {
        http_response_code(400);
        die('Invalid URL');
    }

    // Optional: pass-through category if present
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';

    $db = isset($_GET['db']) ? trim($_GET['db']) : '';

    return ['url' => $url, 'category' => $category, 'db' => $db];
}
