<?php
// Resolve chosen article (redirects if none)
$resolved = newsroom_resolve_article();
$url      = $resolved['url'];
$category = $resolved['category'] ?? '';
$db = $resolved['db'] ?? '';

$fromDb = $db == "1" || $category == "db";

// Get meta data for article
$meta           = [];
$title          = '';
$des            = '';
$img            = '';
$pub            = '';
$pub_link       = '';
$youtube_search = '';

// If we're in "db mode", try to pull the article from the DB instead of scraping
if (!empty($db)) {
    $article = getArticleFromDBByUrl($url);

    if (!empty($article) && is_array($article)) {
        $dbMeta = build_meta_from_db_article($url, $article);
        if (is_array($dbMeta)) {
            $meta = $dbMeta;
        }
    }
}

// If we don't have an image yet (or $meta is still empty), fall back to scraping
if (empty($meta['image'])) {
    $scraped = newsroom_extract_meta($url);

    if (is_array($scraped)) {
        // Merge scraped meta in without nuking DB values that already exist
        $meta = array_merge($meta, $scraped);
        // If you want DB to win over scraped, flip the order:
        // $meta = array_merge($meta, $scraped);

        if (empty($meta['image'])) {
            $meta['image'] = 'assets/img/news-placeholder.jpg';
        }
    }
}

// Finally, hydrate the individual vars safely
$title          = $meta['title']          ?? '';
$des            = $meta['description']    ?? '';
$img            = $meta['image']          ?? '';
$pub            = $meta['publisher']      ?? '';
$pub_link       = $meta['publisher_link'] ?? '';
$youtube_search = $meta['youtube_search'] ?? $title;

// Derive domain from the readUrl
$domain = '';
$favicon_url = null;
if (!empty($url)) {
    $host = parse_url($url, PHP_URL_HOST);
    if ($host) {
        // Strip leading www.
        $domain = preg_replace('/^www\./i', '', $host);

        // Google favicon endpoint using the full URL (your working pattern)
        $favicon_url = 'https://t0.gstatic.com/faviconV2'
            . '?client=SOCIAL&type=FAVICON'
            . '&fallback_opts=TYPE,SIZE,URL'
            . '&url=' . rawurlencode($url)
            . '&size=64';
    }
}

?>