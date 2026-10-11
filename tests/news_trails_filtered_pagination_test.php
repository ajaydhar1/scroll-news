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

foreach (['personal', 'community', 'editors'] as $base) {
    $pageCalls = 0;
    $totalCalls = 0;
    $rows = [['trail_date' => '2026-10-01', 'total_trails' => 27]];
    $result = sn_resolve_filtered_trail_page(
        $base,
        2,
        10,
        static function (string $requestedBase, int $page, int $limit) use ($base, &$pageCalls, $rows): array {
            $pageCalls++;
            if ($requestedBase !== $base || $page !== 2 || $limit !== 10) {
                throw new RuntimeException('Unexpected filtered page request');
            }
            return $rows;
        },
        static function () use (&$totalCalls): int {
            $totalCalls++;
            return 0;
        }
    );
    $assert($result['rows'] === $rows && $result['total'] === 27, "$base populated page should reuse window count");
    $assert($result['page'] === 2 && $result['total_pages'] === 3, "$base populated page should preserve pagination");
    $assert($pageCalls === 1 && $totalCalls === 0, "$base populated page should skip separate count query");
}

$emptyPageCalls = 0;
$emptyTotalCalls = 0;
$empty = sn_resolve_filtered_trail_page(
    'community',
    1,
    10,
    static function () use (&$emptyPageCalls): array {
        $emptyPageCalls++;
        return [];
    },
    static function () use (&$emptyTotalCalls): int {
        $emptyTotalCalls++;
        return 0;
    }
);
$assert($empty['rows'] === [] && $empty['total'] === 0, 'Empty results should remain empty');
$assert($empty['page'] === 1 && $empty['total_pages'] === 1, 'Empty results should retain minimum page state');
$assert($emptyPageCalls === 1 && $emptyTotalCalls === 1, 'Empty results should use the count fallback');

$emptyOutOfRangeCalls = 0;
$emptyOutOfRange = sn_resolve_filtered_trail_page(
    'personal',
    4,
    10,
    static function () use (&$emptyOutOfRangeCalls): array {
        $emptyOutOfRangeCalls++;
        return [];
    },
    static fn(): int => 0
);
$assert($emptyOutOfRange['page'] === 1 && $emptyOutOfRange['total_pages'] === 1, 'Empty out-of-range request should clamp to page one');
$assert($emptyOutOfRangeCalls === 1, 'Empty out-of-range request should not refetch an empty page');

$outOfRangeCalls = [];
$outOfRangeTotalCalls = 0;
$lastPageRows = [['trail_date' => '2026-10-01', 'total_trails' => 25]];
$outOfRange = sn_resolve_filtered_trail_page(
    'editors',
    9,
    10,
    static function (string $base, int $page, int $limit) use (&$outOfRangeCalls, $lastPageRows): array {
        $outOfRangeCalls[] = [$base, $page, $limit];
        return $page === 9 ? [] : $lastPageRows;
    },
    static function () use (&$outOfRangeTotalCalls): int {
        $outOfRangeTotalCalls++;
        return 25;
    }
);
$assert($outOfRange['page'] === 3 && $outOfRange['total_pages'] === 3, 'Out-of-range page should clamp to last page');
$assert($outOfRange['rows'] === $lastPageRows, 'Clamped page rows should be fetched');
$assert($outOfRangeTotalCalls === 1 && count($outOfRangeCalls) === 2, 'Out-of-range page should count then fetch corrected page');

foreach ($failures as $failure) {
    fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
}

if ($failures) {
    exit(1);
}

echo 'PASS: ' . $checks . ' filtered News Trails pagination checks' . PHP_EOL;