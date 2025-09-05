<?php
// public/api/wiki-by-url.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/newsroom_cache_core.php';

// 1) get & validate the article URL
$articleUrl = trim($_POST['url'] ?? $_GET['url'] ?? '');
$norm = normalize_url($articleUrl);

// 2) try cache first (did we already have NLP?)
$payload = cache_get_payload($GLOBALS['CACHE_CTX'], $norm);
$entities = $payload['nlp']['entities'] ?? null;

// 3) if missing, call analyze.php to get NLP
if (!$entities) {
  $analyzeUrl = BASE_URL . '/analyze.php?format=json&url=' . rawurlencode($norm);
  $resp = multiFetch(['nlp'=>['url'=>$analyzeUrl]], 12);
  if (($resp['nlp']['code'] ?? 0) === 200 && valid_json_string($resp['nlp']['body'])) {
    $nlp = json_decode($resp['nlp']['body'], true);
    // save to cache for next time
    cache_set_part($GLOBALS['CACHE_CTX'], $norm, 'nlp', $nlp, NLP_TTL);
    $entities = $nlp['entities'] ?? null;
  }
}

// 4) build the '---' entity string expected by wiki-fragments.php
function build_entity_string($entities): string {
  if (!is_array($entities)) return '';
  // tweak as needed to match your analyzer output shape

  $names = [];
  foreach ($entities as $e) {
    // e.g., $e = ['name'=>'Donald Trump','type'=>'PERSON', ...]
    //if (!empty($e['name'])) $names[] = $e['name'];
    if ($e !== "") $names[] = $e;
  }
  // unique + top N if you want
  $names = array_values(array_unique($names));
  $top   = array_slice($names, 0, 25); // cap it if helpful
  return implode('---', $top);
}

$entityString = build_entity_string($entities);

//echo json_encode(['entity_string_test' => $entityString]);

// 5) if we still have nothing, return a soft error
if ($entityString === '') {
  echo json_encode(['summary'=>null,'fragmentsHtml'=>'','note'=>'No entities available']);
  exit;
}

// 6) POST to your existing wiki-fragments.php which expects $_POST["data"]
$ch = curl_init(BASE_URL . '/wiki-fragments.php');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => ['data' => $entityString],
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 10,
  CURLOPT_CONNECTTIMEOUT => 4,
  CURLOPT_HTTPHEADER => ['Accept: application/json'],
  CURLOPT_USERAGENT => 'ScrollNewsBot/1.0',
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

echo json_encode(['wiki-fragments_response_test1' => $body]);
exit;

// 7) ensure JSON output (adapt if wiki-fragments returns HTML)
if ($code === 200 && valid_json_string($body)) {
  echo $body;
} else {
  echo json_encode(['summary'=>null,'fragmentsHtml'=>'','note'=>'Wiki request failed']);
}
