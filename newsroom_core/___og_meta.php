<?php
// app/newsroom_core/og_meta.php
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
      $t = str_replace(["â","","�"], ["'","",""], $v);
      $meta['title'] = $t;
      $meta['youtube_search'] = $t;
    } elseif ($k === 'site_name' && !empty($v)) {
      // Prefer OG site_name as publisher if present
      $meta['publisher'] = $v;
    } elseif ($k === 'description' && !empty($v)) {
      $meta['description'] = $v;
    }
  }

  return $meta;
}
