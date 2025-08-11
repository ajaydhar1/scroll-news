<?php
// newsroom_core/public/api/newsroom_data.php

/**
 * newsroom_data.php
 *
 * API endpoint for fetching newsroom article data.
 *
 * Accepts:  GET parameter "url" (article URL to process)
 * Optional: GET parameter "prewarm" (1 to force background refresh of cached data)
 *
 * Workflow:
 *  - Normalizes the incoming article URL.
 *  - Reads any cached NLP analysis, Wikipedia summary, and screenshot info for that URL.
 *  - Returns available data immediately (partial results allowed).
 *  - If data is missing or stale, triggers a background refresh without blocking the response.
 *
 * Output: JSON object containing:
 *  - url: normalized article URL
 *  - nlp: NLP analysis data (if cached)
 *  - wiki: Wikipedia summary data (if cached)
 *  - shot_url: URL to cached screenshot (if available)
 *  - stale: flags indicating whether each part is stale
 *
 * This endpoint is designed for fast first-paint in the frontend,
 * with heavy API work done in the background and/or via a prewarm cron.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once dirname(__DIR__) . '/newsroom_core/app/newsroom_cache_core.php';

try {
  $articleUrl = $_GET['url'] ?? '';
  if (!$articleUrl) throw new InvalidArgumentException('Missing url');

  // normalize validates scheme
  $norm = normalize_url($articleUrl);

  // read current cache snapshot
  $payload = cache_get_payload($redis, $norm) ?? [];

  // response (partial OK)
  $resp = [
    'url'       => $norm,
    'nlp'       => $payload['nlp'] ?? null,
    'wiki'      => $payload['wiki'] ?? null,
    'shot_url'  => $payload['shot_url'] ?? null,
    'stale'     => [
      'nlp'  => is_stale($payload, 'nlp',  NLP_TTL),
      'wiki' => is_stale($payload, 'wiki', WIKI_TTL),
      'shot' => is_stale($payload, 'shot', SHOT_TTL),
    ],
  ];

  // If caller is prewarming, we *always* trigger refresh in background.
  $prewarm = isset($_GET['prewarm']) && $_GET['prewarm'] == '1';

  // Return immediately, then refresh stale in background.
  echo json_encode($resp, JSON_UNESCAPED_SLASHES);
  fastcgi_flush_if_possible();

  if ($prewarm || $resp['stale']['nlp'] || $resp['stale']['wiki'] || $resp['stale']['shot']) {
    refresh_parts_in_background($norm);
  }

} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['error' => $e->getMessage()]);
}
