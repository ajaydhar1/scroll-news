<?php
// scripts/prewarm_newsroom.php
// CLI only
if (php_sapi_name() !== 'cli') { http_response_code(403); exit; }

require_once dirname(__DIR__) . '/app/newsroom_cache_core.php';

// TODO: replace this with your real "top N article URLs" provider:
// e.g., from DB, from your latest RSS harvest, etc.
function get_trending_article_urls(): array {
  // placeholder samples:
  return [
    'https://www.cbsnews.com/news/example-1/',
    'https://www.nytimes.com/2025/08/10/example-2.html',
    'https://www.theverge.com/2025/08/10/example-3',
  ];
}

// Avoid overlapping runs
$jobLock = 'cron:prewarm_newsroom:lock';
with_lock($redis, $jobLock, 300, function() {
  $urls = get_trending_article_urls();
  foreach ($urls as $u) {
    $norm = normalize_url($u);
    // hit the local endpoint with prewarm=1 (non-blocking server-side refresh happens there)
    $api = 'http://127.0.0.1/api/newsroom_data.php?prewarm=1&url=' . rawurlencode($norm);

    $ch = curl_init($api);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 8,
      CURLOPT_CONNECTTIMEOUT => 3,
      CURLOPT_USERAGENT => 'ScrollNewsPrewarmer/1.0',
    ]);
    curl_exec($ch);
    curl_close($ch);

    // polite pacing to avoid stampedes on your own APIs
    usleep(150000); // 150ms
  }
});
