<?php
error_reporting(E_ERROR | E_PARSE);
require_once('___modules.php');

$term = trim($_GET['term'] ?? '');
$result = '';
$img_tag = '';

if ($term === '') {
  echo 'No definition found.';
  exit;
}

/* ---------- Definition (search) ---------- */
$wiki_api_endpoint = "https://en.wikipedia.org/w/api.php?action=query&format=json&list=search&srsearch=";
$wiki = azeo_getData($wiki_api_endpoint . urlencode($term));

if (is_array($wiki)
    && isset($wiki['query']['search'][0]['title'], $wiki['query']['search'][0]['snippet'])) {
  $title   = $wiki['query']['search'][0]['title'];
  // Snippet can contain HTML spans; strip & trim.
  $snippet = strip_tags($wiki['query']['search'][0]['snippet']);
  $result  = '<strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '. </strong> ' . $snippet . '...';
}

/* ---------- Image (pageimages) ---------- */
/*
  Note: use the main wikipedia.org API (not m.), follow redirects, and ask for a thumbnail.
*/
$wiki_img_url = "https://en.wikipedia.org/w/api.php"
  . "?action=query&format=json&redirects=1&prop=pageimages&piprop=thumbnail&pithumbsize=500&titles="
  . urlencode($term);

$asr = azeo_getData($wiki_img_url);

if (is_array($asr) && isset($asr['query']['pages']) && is_array($asr['query']['pages']) && count($asr['query']['pages'])) {
  // Get the first page object safely
  $pages = $asr['query']['pages'];
  $page  = reset($pages); // returns first element or false

  if (is_array($page) && isset($page['thumbnail']['source']) && $page['thumbnail']['source'] !== '') {
    $src = $page['thumbnail']['source'];
    $img_tag = '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" class="mb-2" loading="lazy" />';
  }
}

/* ---------- Post-process text ---------- */
if (strlen($result) > 300) {
  $result = substr($result, 0, 300) . '...';
}

if ($result === '' || $result === '<strong>. </strong> ...') {
  $result = 'No definition found.';
}

/* ---------- Output (image + text) ---------- */
echo $img_tag . $result;
