<?php
error_reporting(E_ERROR | E_PARSE);
define('BASE_PATH', dirname(__DIR__)); // /api -> project root
require_once BASE_PATH . '/core/___modules.php';

$term = trim($_GET['term'] ?? '');
$label = ner_label_to_human($_GET['label']);
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
  $result  = $snippet . '...';
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
//echo $img_tag . $result;

$knowledge_card = '
  <div class="gkp__media">
    '.$img_tag.'
  </div>
  <div class="gkp__title">'.$term.'</div>
  <div class="gkp__meta">'.$label.'</div>
  <div class="gkp__desc">
    '.$result.'
  </div>
  <div class="gkp__source">Source: Wikipedia</div>';

echo $knowledge_card;






/**
 * Convert an NER entity type (e.g. PERSON, ORG, GPE) into
 * a user-friendly label for display in UI.
 *
 * Designed around common spaCy-style labels:
 * PERSON, NORP, FAC, ORG, GPE, LOC, PRODUCT, EVENT,
 * WORK_OF_ART, LAW, LANGUAGE, DATE, TIME, PERCENT,
 * MONEY, QUANTITY, ORDINAL, CARDINAL.
 *
 * Unknown labels fall back to a prettified version.
 */
function ner_label_to_human(string $label): string
{
    $code = strtoupper(trim($label));

    $map = [
        // People & groups
        'PERSON'     => 'Person',
        'NORP'       => 'Group (nationality / religion / politics)',

        // Places
        'GPE'        => 'Place (country / city / state)',
        'LOC'        => 'Location',
        'FAC'        => 'Facility or landmark',

        // Organizations & things
        'ORG'        => 'Organization',
        'PRODUCT'    => 'Product',
        'EVENT'      => 'Event',
        'WORK_OF_ART'=> 'Work of art (book, song, movie)',
        'LAW'        => 'Law or legal document',
        'LANGUAGE'   => 'Language',

        // Dates, times, quantities
        'DATE'       => 'Date',
        'TIME'       => 'Time',
        'PERCENT'    => 'Percentage',
        'MONEY'      => 'Money amount',
        'QUANTITY'   => 'Quantity or measurement',
        'ORDINAL'    => 'Position (first, second, etc.)',
        'CARDINAL'   => 'Number',

        // Some extra common tags from other NER schemes
        'MISC'       => 'Miscellaneous entity',
        'O'          => 'Not an entity',
    ];

    if (isset($map[$code])) {
        return $map[$code];
    }

    // Fallback: prettify the raw label, e.g. "WORK_OF_ART" -> "Work Of Art"
    $pretty = preg_replace('/[^A-Z0-9_]+/', '', $code); // keep alnum + underscore
    $pretty = str_replace('_', ' ', $pretty);
    $pretty = ucwords(strtolower($pretty));

    return $pretty ?: 'Entity';
}
