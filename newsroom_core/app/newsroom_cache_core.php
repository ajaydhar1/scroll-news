<?php
// app/newsroom_cache_core.php

// ============================
// The core logic for caching in the newsroom, contains:
//   - Constants for config (endpoints, TTLs, dirs)
//   - Utility functions (normalize_url, multiFetch, etc.)
//   - Cache backend logic (file + Redis versions)
//   - Refresh logic for the newsroom
// ============================


// ============================
// Config
// ============================
define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_DIR', APP_ROOT . '/newsroom_core/public');
define('SHOT_DIR', PUBLIC_DIR . '/newsroom_core/shots');      // where screenshots are stored
define('SHOT_URL_BASE', '/newsroom_core/shots');              // public URL prefix for shots

// External services (replace with your real endpoints)
define('NLP_ENDPOINT',  getenv('NLP_ENDPOINT')  ?: 'https://your-nlp.example.com/analyze?url=');
define('WIKI_ENDPOINT', getenv('WIKI_ENDPOINT') ?: 'https://your-wiki.example.com/summarize?url=');
define('SHOT_ENDPOINT', getenv('SHOT_ENDPOINT') ?: 'https://your-shot.example.com/screenshot?url=');

// TTLs
define('NLP_TTL',   2 * 24 * 3600);  // 2 days
define('WIKI_TTL', 14 * 24 * 3600);  // 14 days
define('SHOT_TTL', 60 * 24 * 3600);  // 60 days

// Cache backend flag (default: FILE)
// Set USE_REDIS=true in your env to enable Redis
define('USE_REDIS', filter_var(getenv('USE_REDIS') ?: 'false', FILTER_VALIDATE_BOOLEAN));

// File cache dirs
define('CACHE_DIR', APP_ROOT . '/storage/cache');
define('LOCK_DIR',  APP_ROOT . '/storage/locks');

// Redis config (used only if USE_REDIS=true)
define('REDIS_HOST', getenv('REDIS_HOST') ?: '127.0.0.1');
define('REDIS_PORT', intval(getenv('REDIS_PORT') ?: '6379'));
define('REDIS_AUTH', getenv('REDIS_AUTH') ?: ''); // optional

// ============================
// Bootstrap
// ============================
function ensure_dir($dir) { if (!is_dir($dir)) @mkdir($dir, 0775, true); }
ensure_dir(SHOT_DIR);
ensure_dir(CACHE_DIR);
ensure_dir(LOCK_DIR);

function fastcgi_flush_if_possible() {
  if (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); return; }
  @ignore_user_abort(true); @ob_end_flush(); @flush();
}

// ============================
// URL normalization & keys
// ============================
function normalize_url(string $url): string {
  $url = trim($url);
  if (!preg_match('~^https?://~i', $url)) throw new InvalidArgumentException('Unsupported URL scheme');
  $p = parse_url($url);
  $scheme = strtolower($p['scheme'] ?? 'https');
  $host   = strtolower($p['host'] ?? '');
  $host   = preg_replace('/^www\./', '', $host);
  $path   = isset($p['path']) ? rtrim($p['path'], '/') : '';
  parse_str($p['query'] ?? '', $q);
  foreach ($q as $k => $v) if (preg_match('/^(utm_|fbclid|gclid|mc_cid|mc_eid)/i', $k)) unset($q[$k]);
  ksort($q);
  $query = http_build_query($q);
  return $scheme.'://'.$host.$path.($query ? '?'.$query : '');
}
function cache_key(string $url): string { return 'newsroom:' . sha1(normalize_url($url)); }

// ============================
// Helpers
// ============================
function valid_json_string(?string $s): bool {
  if (!is_string($s) || $s === '') return false;
  json_decode($s, true);
  return json_last_error() === JSON_ERROR_NONE;
}

function makeNlpUrl(string $articleUrl): string  { return NLP_ENDPOINT  . rawurlencode($articleUrl); }
function makeWikiUrl(string $articleUrl): string { return WIKI_ENDPOINT . rawurlencode($articleUrl); }
function makeShotUrl(string $articleUrl): string { return SHOT_ENDPOINT . rawurlencode($articleUrl); }

function save_screenshot_and_get_url(string $binary, string $articleUrl): ?string {
  $name = sha1(normalize_url($articleUrl)) . '.png';
  $path = SHOT_DIR . '/' . $name;
  $ok = @file_put_contents($path, $binary);
  if ($ok === false) return null;
  return SHOT_URL_BASE . '/' . $name;
}

// ============================
// Parallel fetch (curl_multi)
// ============================
function multiFetch(array $requests, int $timeout = 12): array {
  $mh = curl_multi_init();
  $chs = [];
  foreach ($requests as $key => $req) {
    $ch = curl_init();
    $headers = $req['headers'] ?? [];
    curl_setopt_array($ch, [
      CURLOPT_URL => $req['url'],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_TIMEOUT => $timeout,
      CURLOPT_CONNECTTIMEOUT => 6,
      CURLOPT_MAXREDIRS => 5,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_USERAGENT => 'ScrollNewsBot/1.0',
    ]);
    $chs[$key] = $ch;
    curl_multi_add_handle($mh, $ch);
  }
  $running = null;
  do {
    $mrc = curl_multi_exec($mh, $running);
    if ($running) {
      $sr = curl_multi_select($mh, 1.0);
      if ($sr === -1) usleep(100000);
    }
  } while ($running && $mrc == CURLM_OK);

  $out = [];
  foreach ($chs as $key => $ch) {
    $out[$key] = [
      'body' => curl_multi_getcontent($ch),
      'code' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
    ];
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
  }
  curl_multi_close($mh);
  return $out;
}

// ============================
// CACHE BACKENDS (File & Redis)
//   We expose unified functions:
//   - cache_get_payload($ctx, $url)
//   - cache_set_part($ctx, $url, $part, $value, $ttl)
//   - is_stale($payload, $part, $maxAge)
//   - with_lock($ctx, $lockKey, $ttl, $fn)
// ============================

// ---------- File backend ----------
function _file_cache_path(string $url): string { return CACHE_DIR . '/' . cache_key($url) . '.json'; }
function _file_lock_path(string $key): string { return LOCK_DIR . '/' . preg_replace('/[^a-z0-9:_-]/i','_', $key) . '.lock'; }

function file_cache_get_payload($_unused, string $url): ?array {
  $path = _file_cache_path($url);
  if (!is_file($path)) return null;
  $raw = @file_get_contents($path);
  if ($raw === false) return null;
  $data = json_decode($raw, true);
  return is_array($data) ? $data : null;
}
function file_cache_set_part($_unused, string $url, string $part, $value, int $ttl): void {
  $path = _file_cache_path($url);
  $data = file_cache_get_payload(null, $url) ?? [];
  $data[$part] = is_string($value) ? $value : $value; // keep arrays
  $data['ts_'.$part] = time();
  $data['ttl_'.$part] = $ttl;
  $data['v'] = 1;
  $tmp = $path . '.tmp';
  @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_SLASHES));
  @rename($tmp, $path);
}
function file_with_lock($_unused, string $lockKey, int $_ttl, callable $fn): void {
  $lockPath = _file_lock_path($lockKey);
  $fh = fopen($lockPath, 'c'); if (!$fh) { $fn(); return; }
  $got = flock($fh, LOCK_EX | LOCK_NB);
  if (!$got) { fclose($fh); return; }
  try { $fn(); } finally { flock($fh, LOCK_UN); fclose($fh); }
}

// ---------- Redis backend ----------
$redis = null;
if (USE_REDIS) {
  $redis = new Redis();
  $redis->connect(REDIS_HOST, REDIS_PORT);
  if (REDIS_AUTH !== '') { $redis->auth(REDIS_AUTH); }
  $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
}

function redis_cache_get_payload(Redis $redis = null, string $url): ?array {
  if (!$redis) return null;
  $h = $redis->hGetAll(cache_key($url));
  if (!$h) return null;
  foreach (['nlp','wiki','meta'] as $f) if (isset($h[$f]) && $h[$f] !== '') $h[$f] = json_decode($h[$f], true);
  return $h;
}
function redis_cache_set_part(Redis $redis = null, string $url, string $part, $value, int $ttl): void {
  if (!$redis) return;
  $key = cache_key($url);
  $fields = [
    $part => is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_SLASHES),
    'ts_'.$part => (string) time(),
    'v' => '1',
  ];
  $redis->hMSet($key, $fields);
  $redis->expire($key, $ttl);
}
function redis_with_lock(Redis $redis = null, string $lockKey, int $ttl, callable $fn): void {
  if (!$redis) { $fn(); return; }
  $ok = $redis->set($lockKey, '1', ['nx', 'ex' => $ttl]);
  if ($ok) { try { $fn(); } finally { $redis->del($lockKey); } }
}

// ---------- Unified façade ----------
function cache_get_payload($ctx, string $url): ?array {
  return USE_REDIS ? redis_cache_get_payload($ctx, $url) : file_cache_get_payload(null, $url);
}
function cache_set_part($ctx, string $url, string $part, $value, int $ttl): void {
  USE_REDIS ? redis_cache_set_part($ctx, $url, $part, $value, $ttl)
            : file_cache_set_part(null, $url, $part, $value, $ttl);
}
function with_lock($ctx, string $lockKey, int $ttl, callable $fn): void {
  USE_REDIS ? redis_with_lock($ctx, $lockKey, $ttl, $fn)
            : file_with_lock(null, $lockKey, $ttl, $fn);
}
function is_stale(array $payload = null, string $part = '', int $maxAge = 0): bool {
  if (!$payload) return true;
  $ts = (int)($payload['ts_'.$part] ?? 0);
  return !$ts || (time() - $ts) > $maxAge;
}

// Provide a context handle for the chosen backend
$GLOBALS['CACHE_CTX'] = USE_REDIS ? $redis : null;

// ============================
// Background refresh (SWR)
// ============================
function refresh_parts_in_background(string $articleUrl): void {
  $ctx = $GLOBALS['CACHE_CTX'];
  $lockKey = cache_key($articleUrl) . ':lock';

  with_lock($ctx, $lockKey, 60, function() use ($articleUrl, $ctx) {
    $res = multiFetch([
      'nlp'  => ['url' => makeNlpUrl($articleUrl)],
      'wiki' => ['url' => makeWikiUrl($articleUrl)],
      'shot' => ['url' => makeShotUrl($articleUrl)],
    ], 12);

    if (($res['nlp']['code'] ?? 0) === 200 && valid_json_string($res['nlp']['body'])) {
      cache_set_part($ctx, $articleUrl, 'nlp', json_decode($res['nlp']['body'], true), NLP_TTL);
    }
    if (($res['wiki']['code'] ?? 0) === 200 && valid_json_string($res['wiki']['body'])) {
      cache_set_part($ctx, $articleUrl, 'wiki', json_decode($res['wiki']['body'], true), WIKI_TTL);
    }
    if (($res['shot']['code'] ?? 0) === 200 && !empty($res['shot']['body'])) {
      $shotUrl = save_screenshot_and_get_url($res['shot']['body'], $articleUrl);
      if ($shotUrl) cache_set_part($ctx, $articleUrl, 'shot_url', $shotUrl, SHOT_TTL);
    }
  });
}
