<?php

require_once __DIR__ . '/../core/search_database.php';

$failures = [];
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
};

$initializerCalls = 0;
$landing = sn_search_database_context(false, false, static function () use (&$initializerCalls): ?PDO {
    $initializerCalls++;
    return null;
});
$assert(!$landing['required'], 'Unfiltered Search landing should not require DB');
$assert($landing['pdo'] === null, 'Unfiltered Search landing should not open a PDO');
$assert($landing['error_message'] === null, 'Unfiltered Search landing should not show a DB error');
$assert($initializerCalls === 0, 'Unfiltered Search landing must not call its DB initializer');

$localPdo = new PDO('sqlite::memory:');
$filteredCalls = 0;
$filtered = sn_search_database_context(true, false, static function () use (&$filteredCalls, $localPdo): PDO {
    $filteredCalls++;
    return $localPdo;
});
$assert($filtered['required'] && $filtered['pdo'] === $localPdo, 'Filtered search should initialize and retain its connection');
$assert($filtered['error_message'] === null && $filteredCalls === 1, 'Filtered search should initialize exactly once');

$savedShuffleCalls = 0;
$savedShuffle = sn_search_database_context(false, true, static function () use (&$savedShuffleCalls, $localPdo): PDO {
    $savedShuffleCalls++;
    return $localPdo;
});
$assert($savedShuffle['required'] && $savedShuffle['pdo'] === $localPdo, 'Saved shuffle view should initialize its connection');
$assert($savedShuffleCalls === 1, 'Saved shuffle view should initialize exactly once');

$failureCalls = 0;
$unavailable = sn_search_database_context(true, false, static function () use (&$failureCalls): ?PDO {
    $failureCalls++;
    return null;
});
$assert($failureCalls === 1, 'DB-dependent unavailable path should invoke initializer once');
$assert($unavailable['error_message'] === 'Database connection not available.', 'DB-dependent failure message should remain unchanged');

$searchSource = file_get_contents(__DIR__ . '/../search.php');
$assert(str_contains($searchSource, 'sn_search_database_context($hasFilters, $isSavedShuffleView)'), 'Search route must use the DB requirement gate');
$assert(!preg_match('/\$pdo\s*=\s*_pdo_or_null\s*\(/', $searchSource), 'Search route must not initialize PDO unconditionally');

foreach ($failures as $failure) {
    fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
    exit(1);
}

echo 'PASS: ' . $checks . ' Search database initialization checks' . PHP_EOL;