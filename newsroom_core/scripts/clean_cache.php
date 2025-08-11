<?php
// scripts/clean_cache.php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit; }
require_once dirname(__DIR__) . '/app/newsroom_cache_core.php';

// delete cache files not touched in 90 days
$maxAge = 90 * 24 * 3600;
$now = time();
$deleted = 0;

foreach (glob(CACHE_DIR . '/*.json') as $f) {
  $mtime = @filemtime($f) ?: 0;
  if ($mtime && ($now - $mtime) > $maxAge) {
    @unlink($f);
    $deleted++;
  }
}
echo "Deleted $deleted old cache files\n";
