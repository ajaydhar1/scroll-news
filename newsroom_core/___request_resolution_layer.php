<?php
// newsroom_core/request_resolution_layer.php
require_once __DIR__ . '/../___modules.php'; // for getRandomArticle(), fix_image_if_broken(), etc.
require_once __DIR__ . '/../OpenGraph.php';
require_once __DIR__ . '/../Feed.php';

function newsroom_resolve_article(): array {
    // If no URL, pick a random and redirect (preserves your current behavior)
    if (empty($_GET['url'])) {

        try { 
            $random = getRecentWeightedArticle_fromDB(); // ['category'=>..., 'link'=>...]
        
        } catch (Throwable $e) {
            error_log("getRecentWeightedArticle_fromDB DB error: " . $e->getMessage());
        }

        header('Location: newsroom.php?url=' . urlencode($random['link']) . '&category=' . urlencode($random['category']) . '&pub_date=' . urlencode($random['pub_date']));
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

    return ['url' => $url, 'category' => $category];
}
