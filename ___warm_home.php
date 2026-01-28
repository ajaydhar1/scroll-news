<?php
require_once __DIR__ . '/___modules.php';

$bust = true;

// Optional: simple secret to prevent random people from hitting it
$secret = getenv('WARM_SECRET') ?: '';
if ($secret !== '' && (($_GET['key'] ?? '') !== $secret)) {
  http_response_code(403);
  echo "Forbidden\n";
  exit;
}

fragment_cache_swr("news_intel_panel_$CACHE_VER", 120, 600, function () {
  include __DIR__ . '/___news_intel_panel.php';
}, $bust, true, false);

fragment_cache_swr("active_stories_$CACHE_VER", 60, 300, function () {
  include __DIR__ . '/___active_stories.php';
}, $bust, true, false);

fragment_cache_swr("first_look_$CACHE_VER", 60, 300, function () {
  include __DIR__ . '/___first_look.php';
}, $bust, true, false);

fragment_cache_swr("scroll_strip_$CACHE_VER", 120, 600, function () {
  include __DIR__ . '/___scroll_strip.php';
}, $bust, true, false);

header("Content-Type: text/plain");
echo "OK\n";
