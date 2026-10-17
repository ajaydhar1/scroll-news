<?php
/**
 * Regression guard for search_classic(): the SQL and bound params produced for
 * pre-existing option sets must never change. Topic-only options are additive.
 *
 * Run: php tests/search_classic_regression_test.php          (verify)
 *      php tests/search_classic_regression_test.php --print  (print current hashes)
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/core/___modules.php';

class SnFakeStmt extends PDOStatement
{
    public array $boundParams = [];
    public function execute(?array $params = null): bool
    {
        $this->boundParams = $params ?? [];
        return true;
    }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        return [];
    }
}

class SnFakePdo extends PDO
{
    public array $captured = [];
    public function __construct() {}
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $stmt = new SnFakeStmt();
        $this->captured[] = ['sql' => $query, 'stmt' => $stmt];
        return $stmt;
    }
}

function sn_capture_classic(string $q, array $opts): array
{
    $pdo = new SnFakePdo();
    $total = 0;
    search_classic($pdo, $q, $opts, $total);
    $c = $pdo->captured[0];
    return [
        'sql' => trim(preg_replace('/\s+/', ' ', $c['sql'])),
        'params' => $c['stmt']->boundParams,
    ];
}

/** Pre-existing behaviours that must stay byte-for-byte stable. */
$legacyCases = [
    'default'        => ['iran', []],
    'range24h_off'   => ['iran', ['range' => '24h', 'offset' => 200]],
    'range_older'    => ['x', ['range' => 'older']],
    'browse'         => ['', ['browse_everything' => true]],
    'shuffle_pool'   => ['x', ['shuffle_pool' => true]],
    'high_signal'    => ['', ['high_signal' => true]],
];

$golden = [
    'default' => '342a6353b0280d120a3bba6cb6f58785',
    'range24h_off' => '65107ceabb0e4eccc638a38b3e3e69ee',
    'range_older' => 'f8778a1ba2d7a9848886309e86a3cd47',
    'browse' => 'dcc17e33309bbc0604f6cd8e7d86a4d9',
    'shuffle_pool' => '7cac47434f31b1218d43b843168a8dba',
    'high_signal' => '3794e2d7b470cec020589f51ab7707d1',
];

if (in_array('--print', $argv ?? [], true)) {
    foreach ($legacyCases as $name => [$q, $opts]) {
        $cap = sn_capture_classic($q, $opts);
        echo "    '$name' => '" . md5($cap['sql'] . json_encode($cap['params'])) . "',\n";
    }
    exit(0);
}

$failures = [];
$checks = 0;
$assert = static function (bool $ok, string $msg) use (&$failures, &$checks): void {
    $checks++;
    if (!$ok) {
        $failures[] = $msg;
    }
};

foreach ($legacyCases as $name => [$q, $opts]) {
    $cap = sn_capture_classic($q, $opts);
    $hash = md5($cap['sql'] . json_encode($cap['params']));
    $assert(($golden[$name] ?? null) === $hash, "Legacy SQL changed for case '$name'");
}

// Additive topic options
$t = sn_capture_classic('', [
    'terms' => ['Iran', 'Tehran'],
    'word_match' => true,
    'exclude' => ['Miranda'],
    'since_hours' => 72,
    'limit' => 30,
    'distinct_link' => true,
    'offset' => 30,
]);
$assert(str_contains($t['sql'], '~*'), 'Word-match terms use a regex match');
$assert(str_contains($t['sql'], 'DISTINCT ON (ri.link)'), 'distinct_link collapses syndicated links');
$assert(str_contains($t['sql'], "INTERVAL '72 hours'"), 'since_hours adds a recency bound');
$assert(str_contains($t['sql'], 'LIMIT 30 OFFSET 30'), 'limit and offset are applied');
$assert(str_contains($t['sql'], 'ri.deleted_at IS NULL'), 'Soft-delete rule still applies');
$assert(str_contains($t['sql'], 'f.deleted_at IS NULL'), 'Feed eligibility rule still applies');
$assert(in_array('\\mIran\\M', $t['params'], true) && in_array('\\mTehran\\M', $t['params'], true), 'Terms are bound as word-boundary patterns');
$assert(!str_contains($t['sql'], 'Iran'), 'Terms are never interpolated into SQL');

$t2 = sn_capture_classic('', ['terms' => ['C++ (test)'], 'word_match' => true]);
$assert(!in_array('\\mC++ (test)\\M', $t2['params'], true), 'Regex metacharacters in terms are escaped');

$t3 = sn_capture_classic('', ['terms' => ['AI'], 'limit' => 5000]);
$assert(str_contains($t3['sql'], 'LIMIT 100 OFFSET 0'), 'limit is clamped to 100');
$assert(str_contains($t3['sql'], 'ILIKE'), 'Without word_match terms use ILIKE');

$noneDb = new SnFakePdo();
$noneTotal = 0;
search_classic($noneDb, '', [], $noneTotal);
$assert($noneDb->captured === [], 'Empty query with no filters still returns early without querying');

if ($failures) {
    fwrite(STDERR, "FAILED (" . count($failures) . "/$checks):\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}
echo "OK ($checks checks)\n";
