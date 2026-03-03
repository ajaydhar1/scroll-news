<?php
// app/core/newsroom/og_meta.php
require_once __DIR__ . '/___request_resolution_layer.php'; // ensures modules + OpenGraph loaded

function newsroom_extract_meta(string $url): array {
  $og = OpenGraph::fetch($url);

  // Defaults
  $meta = [
    'title'          => 'No Title',
    'description'    => 'No description',
    'image'          => '',
    'publisher'      => 'Unknown Publisher',
    'publisher_link' => '',
    'youtube_search' => '',
  ];

  // Publisher from domain (fallback)
  $host   = parse_url($url, PHP_URL_HOST);
  $domain = preg_replace('/^www\./', '', (string)$host);
  $meta['publisher']      = $domain ?: $meta['publisher'];
  $meta['publisher_link'] = $domain ? ('https://' . $domain) : '';

  // Pull OG values
  foreach ($og as $k => $v) {
    if ($k === 'image' && !empty($v)) {
      $img = fix_image_if_broken($v);
      if ($img) $meta['image'] = $img;
    } elseif ($k === 'title' && !empty($v)) {
      // Clean odd bytes you were stripping
      //$t = str_replace(["â","","�"], ["'","",""], $v);
      //$meta['title'] = $t;
      $meta['title'] = $v;
      $meta['youtube_search'] = $v;
    } elseif ($k === 'site_name' && !empty($v)) {
      // Prefer OG site_name as publisher if present
      $meta['publisher'] = $v;
    } elseif ($k === 'description' && !empty($v)) {
      $meta['description'] = $v;
    }
  }

  return $meta;
}


function build_meta_from_db_article(string $url, array $article): array
{
    if (empty($article)) {
        return [];
    }

    // Parse the URL into its components
    $parsedUrl = parse_url($url);

    // Extract the scheme and host
    $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : 'https'; // default to https
    $host   = $parsedUrl['host'] ?? '';
    $domain = preg_replace('/^www\./', '', (string)$host);

    // Construct the main homepage URL (empty if we somehow have no host)
    $homepageUrl = $host !== '' ? $scheme . '://' . $host : '';

    // Clean up the title a bit
    $rawTitle   = $article['title'] ?? '';
    /*
    $cleanTitle = str_replace(
        ["â", "", "�"],
        ["'",   "",   ""],
        $rawTitle
    );
    */

    // Build $meta from DB row
    return [
        'title'           => $article['title'] ?? '',
        'description'     => $article['description']   ?? '',
        'image'           => $article['media_url']     ?? '',
        'publisher'       => $domain,
        'publisher_link'  => $homepageUrl,
        'youtube_search'  => $cleanTitle,
    ];
}
