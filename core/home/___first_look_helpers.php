<?php

function fl_cache_dir(): string {
    $dir = BASE_PATH . '/_cache_first_look';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function fl_cache_path(): string {
  return fl_cache_dir() . '/first_look.json';
}

function fl_lock_path(string $cachePath): string {
  return $cachePath . '.lock';
}

function fl_safe_get_json(string $path): ?array {
  if (!is_file($path)) return null;
  $raw = @file_get_contents($path);
  if ($raw === false) return null;
  $data = json_decode($raw, true);
  return is_array($data) ? $data : null;
}

function fl_safe_put_json(string $path, array $data): void {
  $tmp = $path . '.tmp';
  @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX);
  @rename($tmp, $path);
}

function fl_fetch_url(string $url): string|false {
  if (function_exists('curl_init')) {
    $ch = curl_init($url);

    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS => 5,
      CURLOPT_CONNECTTIMEOUT => 4,
      CURLOPT_TIMEOUT => 8,
      CURLOPT_USERAGENT => 'ScrollNewsFirstLook/1.0 (+https://scrollnews.io)',
      CURLOPT_HTTPHEADER => [
        'Accept: application/rss+xml, application/xml;q=0.9, text/xml;q=0.8, */*;q=0.5',
      ],
    ]);

    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);

    curl_close($ch);

    if ($body === false || $status >= 400) {
      error_log("[FirstLook] cURL failed for {$url}; status={$status}; error={$err}");
      return false;
    }

    return $body;
  }

  $ctx = stream_context_create([
    'http' => [
      'timeout' => 8,
      'follow_location' => 1,
      'max_redirects' => 5,
      'user_agent' => 'ScrollNewsFirstLook/1.0 (+https://scrollnews.io)',
      'header' => "Accept: application/rss+xml, application/xml;q=0.9, text/xml;q=0.8, */*;q=0.5\r\n",
    ],
    'ssl' => [
      'verify_peer' => true,
      'verify_peer_name' => true,
    ],
  ]);

  return @file_get_contents($url, false, $ctx);
}

function fl_fetch_rss_top_items(string $rssUrl, int $maxItems = 2): array {
  $ctx = stream_context_create([
    'http' => [
      'timeout' => 4,
      'user_agent' => 'ScrollNewsFirstLook/1.0 (+https://scroll.news)',
      'header' => "Accept: application/rss+xml, application/xml;q=0.9, */*;q=0.8\r\n",
    ],
    'ssl' => [
      'verify_peer' => true,
      'verify_peer_name' => true,
    ],
  ]);

  $xml = fl_fetch_url($rssUrl);

  if (!$xml) {
    error_log("[FirstLook] Empty fetch body for {$rssUrl}");
    return [];
  }

  // Some feeds contain invalid chars; try a light cleanup
  $xml = preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x80-\xFF]/', '', $xml);

  libxml_use_internal_errors(true);
  $feed = @simplexml_load_string($xml);
  if (!$feed) return [];

  $items = [];

  // RSS 2.0: channel->item; Atom: entry
  if (isset($feed->channel->item)) {
    foreach ($feed->channel->item as $it) {
      $title = trim((string)($it->title ?? ''));
      $link  = trim((string)($it->link ?? ''));
      $date  = trim((string)($it->pubDate ?? $it->date ?? ''));

      if ($title === '' || $link === '') continue;
      $ts = $date ? strtotime($date) : 0;

      $items[] = ['title'=>$title, 'url'=>$link, 'ts'=>$ts ?: 0, 'emo'=>fl_headline_emoji($title)];
      if (count($items) >= $maxItems) break;
    }
  } elseif (isset($feed->entry)) {
    foreach ($feed->entry as $it) {
      $title = trim((string)($it->title ?? ''));
      $link  = '';

      if (isset($it->link)) {
        foreach ($it->link as $ln) {
          $href = (string)($ln['href'] ?? '');
          if ($href) { $link = $href; break; }
        }
      }

      $date = trim((string)($it->updated ?? $it->published ?? ''));

      if ($title === '' || $link === '') continue;
      $ts = $date ? strtotime($date) : 0;

      $items[] = ['title'=>$title, 'url'=>$link, 'ts'=>$ts ?: 0, 'emo'=>fl_headline_emoji($title)];
      if (count($items) >= $maxItems) break;
    }
  }

  return $items;
}

function fl_build_payload(array $feeds, int $perFeed): array {
  error_log("[FirstLook] Starting feed build");

  $out = [];

  foreach ($feeds as $f) {
    if (empty($f['rss'])) continue;

    error_log("[FirstLook] Fetching {$f['name']} - {$f['rss']}");

    $items = fl_fetch_rss_top_items($f['rss'], $perFeed);

    error_log("[FirstLook] {$f['name']} returned " . count($items) . " items");

    if (!$items) continue;

    $out[] = [
      'name'  => $f['name'],
      'home'  => $f['url'],
      'items' => $items,
    ];
  }

  usort($out, function($a, $b){
    $at = $a['items'][0]['ts'] ?? 0;
    $bt = $b['items'][0]['ts'] ?? 0;
    return $bt <=> $at;
  });

  return $out;
}

/**
 * Attempt a refresh but never block page render.
 * If we can get the lock, we register a shutdown function to refresh cache.
 */
function fl_trigger_background_refresh(string $cachePath, array $feeds, int $perFeed): void {
  $lockFile = fl_lock_path($cachePath);
  $lock = @fopen($lockFile, 'c');
  if (!$lock) return;

  // Non-blocking lock: if another request is refreshing, do nothing.
  if (!@flock($lock, LOCK_EX|LOCK_NB)) {
    @fclose($lock);
    return;
  }

  // Refresh after the response is sent (best-effort).
  register_shutdown_function(function() use ($lock, $cachePath, $feeds, $perFeed) {
    $payload = fl_build_payload($feeds, $perFeed);
    fl_safe_put_json($cachePath, [
      'fetched_at' => time(),
      'feeds' => $payload,
    ]);
    @flock($lock, LOCK_UN);
    @fclose($lock);
  });

  // If PHP-FPM, send response early so user doesn’t wait on shutdown work.
  if (function_exists('fastcgi_finish_request')) {
    @fastcgi_finish_request();
  }
}