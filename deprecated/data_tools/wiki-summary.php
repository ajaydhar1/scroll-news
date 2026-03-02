<?php
http_response_code(410);
exit('Deprecated');

header('Content-Type: application/json; charset=UTF-8');

$title = $_GET['title'] ?? 'Vladimir_Putin';

try {
  $summary   = wiki_summary($title);          // short text
  $lead_html = wiki_lead_html($title);        // first section HTML

  echo json_encode([
    'title'      => $summary['title'] ?? $title,
    'summary'    => $summary['extract'] ?? null,          // plain text
    'summary_html'=> $summary['extract_html'] ?? null,    // formatted summary
    'lead_html'  => $lead_html,                           // section 0 HTML
    'source_url' => $summary['content_urls']['desktop']['page'] 
                     ?? ('https://en.wikipedia.org/wiki/' . rawurlencode($title)),
  ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}

/* ---------- helpers ---------- */

function http_get(string $url): string {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_ENCODING       => '', // auto-handle gzip
    CURLOPT_USERAGENT      => 'ScrollNewsBot/1.0 (+https://scrollnews.io)',
    CURLOPT_HTTPHEADER     => ['Accept: application/json, text/html;q=0.8'],
  ]);
  $body = curl_exec($ch);
  if ($body === false) {
    $err = curl_error($ch); curl_close($ch);
    throw new Exception("cURL error: $err");
  }
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($code >= 400) throw new Exception("HTTP $code fetching $url");
  return $body;
}

// Tiny JSON with title, summary, links, thumbnail, etc.
function wiki_summary(string $title): array {
  $url = 'https://en.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode($title);
  $json = http_get($url);
  return json_decode($json, true) ?? [];
}

// HTML for just the lead section (section=0), much smaller than full page
function wiki_lead_html(string $title): ?string {
  $url = 'https://en.wikipedia.org/w/api.php'
       . '?action=parse&format=json&formatversion=2'
       . '&prop=text&section=0&redirects=1&disableeditsection=1'
       . '&page=' . rawurlencode($title);
  $data = json_decode(http_get($url), true);
  return $data['parse']['text'] ?? null;
}
