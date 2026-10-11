<?php
// Resolve chosen article (redirects if none)
$resolved = newsroom_resolve_article();
$url      = $resolved['url'];
$category = $resolved['category'] ?? '';
$db = $resolved['db'] ?? '';

// Normalize the optional database-backed article flag.
$dbFlag = ($db === '1' || $db === 'true' || $db === 'yes');

// Database-backed articles use stored metadata; unresolved URLs retain OG scraping.
$databaseRequested = $dbFlag || strtolower($category) === 'db';
$articleData = newsroom_resolve_article_data($url, $databaseRequested);
$article = $articleData['article'];
$fromDb = $articleData['from_db'];
$meta = $articleData['meta'];
$source_slug = $article['source_slug'] ?? '';

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