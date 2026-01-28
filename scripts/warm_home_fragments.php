<?php
// scripts/warm_home_fragments.php
require_once __DIR__ . '/../___modules.php';

$bust = true;

$warm = function(string $key, callable $fn) use ($bust) {
    // Force blocking render only if cache doesn't exist, otherwise refresh happens quickly
    // We can pass $bust=true if you want to forcibly regenerate every time.
    fragment_cache_swr($key, 0, 0, $fn, $bust, true);
};

// Warm the fragments (render functions match homepage)
$warm("news_intel_panel_$CACHE_VER", function () {
    include __DIR__ . '/../___news_intel_panel.php';
});

$warm("active_stories_$CACHE_VER", function () {
    include __DIR__ . '/../___active_stories.php';
});

$warm("first_look_$CACHE_VER", function () {
    include __DIR__ . '/../___first_look.php';
});

$warm("scroll_strip_$CACHE_VER", function () {
    include __DIR__ . '/../___scroll_strip.php';
});

echo "OK\n";
