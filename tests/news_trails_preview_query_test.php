<?php

require_once __DIR__ . '/../core/news_trails_pagination.php';

$failures = [];
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
};

$countCalls = 0;
$communityPopulated = sn_trail_preview_has_more(
    'community',
    [['total_trails' => 12]],
    9,
    static function () use (&$countCalls): int {
        $countCalls++;
        return 0;
    }
);
$assert($communityPopulated, 'Community preview should show View More when total exceeds preview');
$assert($countCalls === 0, 'Community preview should not execute a separate total query');

$communityEmpty = sn_trail_preview_has_more(
    'community',
    [],
    0,
    static function () use (&$countCalls): int {
        $countCalls++;
        return 10;
    }
);
$assert(!$communityEmpty, 'Empty Community collection should not show View More');
$assert($countCalls === 0, 'Empty Community collection should not issue an unnecessary count query');

$personalRecentMore = sn_trail_preview_has_more(
    'personal',
    [['total_trails' => 8]],
    6,
    static function () use (&$countCalls): int {
        $countCalls++;
        return 0;
    }
);
$assert($personalRecentMore, 'Personal preview-window total above visible cards proves View More');
$assert($countCalls === 0, 'Personal count query should be skipped when the preview count proves more trails exist');

$editorOlderHistory = sn_trail_preview_has_more(
    'editors',
    [['total_trails' => 4]],
    4,
    static function () use (&$countCalls): int {
        $countCalls++;
        return 11;
    }
);
$assert($editorOlderHistory, 'Editor full-history fallback should preserve View More for older trails');
$assert($countCalls === 1, 'Editor should count full history when the preview total cannot prove View More');

$personalNoMore = sn_trail_preview_has_more(
    'personal',
    [['total_trails' => 3]],
    3,
    static fn(): int => 3
);
$assert(!$personalNoMore, 'Personal full-history fallback should hide View More when no older trails exist');

$personalOnlyOlder = sn_trail_preview_has_more(
    'personal',
    [],
    0,
    static fn(): int => 5
);
$assert($personalOnlyOlder, 'Personal full-history fallback should show View More for trails older than preview window');

$route = file_get_contents(__DIR__ . '/../news-trails.php');
$assert(str_contains($route, '$_GET[\'base\'] ?? \'all\''), 'Parameterless and explicit base=all share the all-view default selection');
$assert(str_contains($route, '$currentUserId = $_SESSION[\'user_id\'] ?? null;'), 'Signed-in user identity remains session-based');
$assert(str_contains($route, 'if ($base === \'personal\' && !$currentUserId)'), 'Anonymous users remain excluded from Personal Trail queries');
$assert(str_contains($route, 'if ($activeBase === \'all\' || $activeBase === \'personal\')'), 'All view and Personal view share the Personal section gate');
$assert(str_contains($route, 'if ($activeBase === \'all\' || $activeBase === \'editors\')'), 'All view and Editor view share the Editor section gate');
$assert(str_contains($route, 'if ($activeBase === \'all\' || $activeBase === \'community\')'), 'All view and Community view share the Community section gate');
$assert(str_contains($route, 'u.community_trail_sharing IS TRUE'), 'Community opt-in visibility predicate remains in the query');
$assert(str_contains($route, 'LOWER(u.email) NOT IN'), 'Editor exclusion remains applied to Community Trails');
$assert(str_contains($route, "'WHERE LOWER(u.email) IN (' . implode"), 'Editor collection visibility still uses the editor allowlist');
$assert(str_contains($route, '$personalLimit = $isFilteredView ? 18 : 6'), 'Personal preview limit remains unchanged');
$assert(str_contains($route, '$editorLimit = $isFilteredView ? 18 : 6'), 'Editor preview limit remains unchanged');
$assert(str_contains($route, '$communityLimit = $isFilteredView ? 24 : 9'), 'Community preview limit remains unchanged');
$assert(str_contains($route, 'sn_resolve_filtered_trail_page('), 'Filtered pagination continues using its existing helper');
$assert(str_contains($route, 'loadTrailPreviews($pdo, $personalTrails, $editorTrails, $communityTrails)'), 'All displayed trail sections share one batched preview load');
$assert(str_contains($route, 'ROW_NUMBER() OVER ('), 'Preview steps are ranked within each user/date trail');
$assert(str_contains($route, 'WHERE step_number <= 4'), 'The preview query returns at most four steps per trail');
$assert(str_contains($route, "'reading' => 'fa-book-open'"), 'Article reads use a distinct preview icon');
$assert(str_contains($route, "'saved' => 'fa-bookmark'"), 'Saved articles use a distinct preview icon');
$assert(str_contains($route, "'search' => 'fa-magnifying-glass'"), 'Searches use a distinct preview icon');
$assert(str_contains($route, "'shuffle' => 'fa-shuffle'"), 'Shuffles use a distinct preview icon');
$assert(str_contains($route, 'htmlspecialchars($stepTitle'), 'Displayed preview titles and search queries are HTML-escaped');
$assert(str_contains($route, '$moreSteps = max(0, (int)'), 'The more-steps indicator uses the existing trail record count');
$assert(str_contains($route, 'h.url, h.title, h.image'), 'Reading previews use their stored article image');
$assert(str_contains($route, 'matched_item.media_url'), 'Saved previews use the matching RSS article image when available');
$assert(str_contains($route, 'class="trail-preview-thumb"'), 'Article preview images use a dedicated compact thumbnail style');
$assert(str_contains($route, 'loading="lazy"'), 'Article preview thumbnails load lazily');
$assert(str_contains($route, "this.onerror=null;this.src='/assets/img/news-placeholder.jpg';"), 'Broken preview images fall back to the standard news placeholder');

foreach ($failures as $failure) {
    fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
}

if ($failures) {
    exit(1);
}

echo 'PASS: ' . $checks . ' News Trails preview query checks' . PHP_EOL;