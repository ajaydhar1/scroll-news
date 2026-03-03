<?php
define('BASE_PATH', dirname(__DIR__, 2)); // /tasks/cache -> project root
require_once BASE_PATH . '/core/___modules.php';

$bust = true;

// Optional: simple secret to prevent random people from hitting it
$secret = getenv('WARM_SECRET') ?: '';
if ($secret !== '' && (($_GET['key'] ?? '') !== $secret)) {
  http_response_code(403);
  echo "Forbidden\n";
  exit;
}

fragment_cache_swr("news_intel_panel_$CACHE_VER", 120, 600, function () {
  include BASE_PATH . '/views/home/panels/___news_intel_panel.php';
}, $bust, true, false);

fragment_cache_swr("active_stories_$CACHE_VER", 60, 300, function () {
  include BASE_PATH . '/views/home/panels/___active_stories.php';
}, $bust, true, false);

// Prime First Look JSON cache (no fragment caching)
ob_start();
$_GET['warm'] = '1';
include BASE_PATH . '/views/home/panels/___first_look.php';
unset($_GET['warm']);
ob_end_clean();

fragment_cache_swr("scroll_strip_$CACHE_VER", 120, 600, function () {
  include BASE_PATH . '/views/home/panels/___scroll_strip.php';
}, $bust, true, false);

header("Content-Type: text/plain");
echo "OK\n";
