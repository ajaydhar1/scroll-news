<?php

require_once __DIR__ . '/../core/timelines/___timelines.php';

$failures = [];
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
};

$art = static fn(int $id, string $title, string $desc, array $ents, string $pub, string $url = 'https://www.cnn.com/x', string $cat = 'news'): array => [
    'id' => $id, 'title' => $title, 'description' => $desc, 'url' => $url,
    'source_slug' => $cat, 'pub_date' => $pub, 'media_url' => '',
    'entities' => array_map(static fn($t, $c) => ['text' => $t, 'count' => $c], array_keys($ents), $ents),
];

// Story registry and alias lookup
$stories = sn_tl_all_stories();
$assert(isset($stories['greenland'], $stories['alex-pretti'], $stories['venezuela'], $stories['iran']), 'All curated stories are registered');
$assert(sn_tl_get_story('greenland') !== null, 'Known slug resolves');
$assert(sn_tl_get_story('../etc/passwd') === null, 'Path-like slug rejected');
$assert(sn_tl_get_story('nope') === null, 'Unknown slug rejected');
$assert(sn_tl_find_story_by_alias('  Greenland ')['slug'] === 'greenland', 'Alias lookup is case/space-insensitive');
$assert(sn_tl_find_story_by_alias('green') === null, 'Alias lookup is exact, not partial');

// Sanitising publisher markup
$assert(sn_tl_plain('&lt;div&gt;&lt;img src=x&gt;Hello &amp;amp; bye&lt;/div&gt;') === 'Hello & bye', 'Escaped HTML descriptions are reduced to plain text');

$g = sn_tl_get_story('greenland');
$p = sn_tl_get_story('alex-pretti');

// Classification
$assert(sn_tl_classify($art(1, 'Trump says US needs Greenland', '', [], '2026-01-10T12:00:00Z'), $g)['tier'] === 'core', 'Title hit is core');
$assert(sn_tl_classify($art(2, 'Tulsa business roundup', 'Local news', ['greenland' => 1], '2026-01-10T12:00:00Z'), $g)['tier'] === 'rejected', 'Passing entity mention is rejected');
$assert(sn_tl_classify($art(3, 'Allies weigh response', 'Talks', ['greenland' => 4], '2026-01-10T12:00:00Z'), $g)['tier'] === 'related', 'Mid entity count is related');
$assert(sn_tl_classify($art(4, 'Allies weigh response', 'Talks', ['greenland' => 12], '2026-01-10T12:00:00Z'), $g)['tier'] === 'core', 'High entity count is core');
$assert(sn_tl_classify($art(12948, 'Anything', '', [], '2026-01-10T12:00:00Z'), $g)['tier'] === 'related', 'Pins are honored');
$assert(sn_tl_classify($art(12737, 'Greenland tariffs', '', [], '2026-01-10T12:00:00Z'), $g)['tier'] === 'rejected', 'Excludes beat title hits');
$assert(sn_tl_classify($art(5, 'Minnesota CEOs urge deescalation after Pretti killing', '', [], '2026-01-26T12:00:00Z'), $p)['tier'] === 'core', 'Pretti title match with zero entity count is core');
$assert(sn_tl_classify($art(6, 'Philadelphia snowstorm', 'Snow', ['pretti' => 1], '2026-01-26T12:00:00Z'), $p)['tier'] === 'rejected', 'Pretti noise rejected');

// Assembly: dedupe, ET bucketing, categories, gaps
$rows = [
    $art(101, 'Greenland talks begin', 'd', [], '2026-01-10T15:00:00Z'),
    $art(102, 'Greenland talks begin!', 'dup', [], '2026-01-10T16:00:00Z', 'https://www.bbc.com/a'),
    $art(103, 'Greenland late story', 'd', [], '2026-01-11T03:30:00Z', 'https://www.nbcnews.com/a'),
    $art(104, 'Greenland match report', 'd', [], '2026-01-12T12:00:00Z', 'https://www.espn.com/a', 'sports'),
    $art(105, 'Greenland returns', 'd', [], '2026-01-20T12:00:00Z', 'https://www.cnn.com/b'),
    $art(106, '<b>Greenland</b> markup title', '&lt;div&gt;desc&lt;/div&gt;', [], '2026-01-20T13:00:00Z', 'https://www.npr.org/a'),
];
$model = sn_tl_assemble($g, $rows);
$s = $model['stats'];
$assert($s['duplicates_removed'] === 1, 'Duplicate titles removed');
$assert($s['core_articles'] === 4, 'Sports category excluded; four core remain');
$assert(isset($model['days']['2026-01-10']) && count($model['days']['2026-01-10']['articles']) === 2, 'Jan 10 holds the first duplicate plus the 03:30Z Jan 11 article (ET bucketing)');
$assert(!isset($model['days']['2026-01-11']), '03:30Z on Jan 11 buckets to Jan 10 in ET');
$assert($s['first_day'] === '2026-01-10' && $s['last_day'] === '2026-01-20', 'First/last core days computed');
$assert(isset($model['gaps']['2026-01-20']) && $model['gaps']['2026-01-20']['days'] === 9, 'Nine quiet days between core days flagged');
$assert($s['publishers'] === 3, 'Publisher count derived from hosts');
$titles = array_column($model['days']['2026-01-20']['articles'], 'title');
$assert(in_array('Greenland markup title', $titles, true), 'Markup stripped from titles');

// Chapter placement
$story = $g;
$story['chapters'] = [['from' => '2026-01-10', 'to' => '2026-01-12', 'title' => 'A', 'summary' => '', 'basis' => []],
                      ['from' => '2026-01-20', 'to' => '2026-01-20', 'title' => 'B', 'summary' => '', 'basis' => []]];
$m2 = sn_tl_assemble($story, $rows);
$assert($m2['chapters'][0]['core_articles'] === 2 && $m2['chapters'][0]['first_day'] === '2026-01-10', 'Chapter 1 counts and first day');
$assert($m2['chapters'][1]['core_articles'] === 2 && $m2['chapters'][1]['first_day'] === '2026-01-20', 'Chapter 2 counts and first day');

// Every curated chapter basis id and range are well-formed
foreach ($stories as $slug => $def) {
    $prev = null;
    foreach ($def['chapters'] ?? [] as $i => $ch) {
        $assert($ch['from'] <= $ch['to'], "$slug chapter $i has ordered dates");
        $assert($prev === null || $ch['from'] > $prev, "$slug chapter $i starts after previous ends");
        $assert(count($ch['basis'] ?? []) >= 2, "$slug chapter $i cites at least two articles");
        $prev = $ch['to'];
    }
    $assert(!array_intersect_key($def['pins'] ?? [], $def['excludes'] ?? []), "$slug pins and excludes do not overlap");
}

// ---------------------------------------------------------------------------------------------
// Active-timeline engine
// ---------------------------------------------------------------------------------------------
$tzET = new DateTimeZone(SN_TL_TZ);
$now = new DateTimeImmutable('2026-10-14 09:00:00', $tzET);
$activeStory = ['slug' => 'tltest', 'status' => 'active', 'window' => ['from' => '2026-02-16', 'to' => null]];

// Window resolution
$w = sn_tl_resolve_window($activeStory, $now);
$assert($w['open'] === true && $w['to'] === '2026-10-14' && $w['from'] === '2026-02-16', 'Open-ended window resolves to today (ET)');
$w2 = sn_tl_resolve_window($activeStory, $now->modify('+1 day'));
$assert($w2['to'] === '2026-10-15', 'Open window advances with the clock without any edit');
$assert(sn_tl_today(new DateTimeImmutable('2026-10-15 02:00:00', new DateTimeZone('UTC'))) === '2026-10-14', 'Today is computed in ET, not UTC');
$w3 = sn_tl_resolve_window(['status' => 'active', 'window' => ['from' => '2026-02-16', 'to' => '2027-01-01']], $now);
$assert($w3['to'] === '2026-10-14' && $w3['open'] === false, 'Active story future window.to is clamped to today');
$w4 = sn_tl_resolve_window(['status' => 'closed', 'window' => ['from' => '2026-01-04', 'to' => '2026-02-28']], $now);
$assert($w4['to'] === '2026-02-28', 'Closed story keeps its fixed window.to');
$w5 = sn_tl_resolve_window($activeStory + ['lookback_days' => 30], $now);
$assert($w5['from'] === '2026-09-14', 'lookback_days gives a rolling start');

// Fake PDO to test fetching without a database
class TlFakeStmt extends PDOStatement
{
    public array $rows = [];
    public array $bound = [];
    public ?Throwable $fail = null;
    public function execute(?array $params = null): bool
    {
        if ($this->fail) {
            throw $this->fail;
        }
        $this->bound = $params ?? [];
        return true;
    }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        return $this->rows;
    }
}
class TlFakePdo extends PDO
{
    public array $rows = [];
    public string $sql = '';
    public int $prepared = 0;
    public ?Throwable $fail = null;
    public function __construct() {}
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->sql = $query;
        $this->prepared++;
        $st = new TlFakeStmt();
        $st->rows = $this->rows;
        $st->fail = $this->fail;
        return $st;
    }
}
$dbRow = static fn(int $id, string $title, string $pub): array => [
    'id' => $id, 'url' => 'https://www.cnn.com/x' . $id, 'title' => $title, 'description' => '',
    'source_slug' => 'politics', 'pub_date' => $pub, 'media_url' => '', 'entities_json' => '[{"text":"iran","count":5}]',
];

$fake = new TlFakePdo();
$fake->rows = [
    $dbRow(5, 'Iran news E', '2026-10-14T12:00:00Z'),
    $dbRow(4, 'Iran news D', '2026-10-13T12:00:00Z'),
    $dbRow(3, 'Iran news C', '2026-10-12T12:00:00Z'),
];
$fetched = sn_tl_fetch_candidates($fake, $activeStory + ['terms' => ['iran'], 'max_candidates' => 100], $meta, $now);
$assert(stripos($fake->sql, 'ORDER BY a.pub_date DESC') !== false && stripos($fake->sql, 'LIMIT 100') !== false, 'Fetch is newest-first and capped');
$assert(stripos($fake->sql, 'a.nlp::text ILIKE') !== false, 'NLP prefilter is on by default');
$assert(array_column($fetched, 'id') === [3, 4, 5], 'Rows are returned oldest-first');
$assert($meta['truncated'] === false && $meta['window']['open'] === true, 'Untruncated fetch reports window metadata');
$assert($fetched[0]['entities'][0]['text'] === 'iran', 'Entities decoded from the narrow JSON column');
sn_tl_fetch_candidates($fake, $activeStory + ['terms' => ['iran'], 'prefilter_nlp' => false], $meta, $now);
$assert(stripos($fake->sql, 'a.nlp::text ILIKE') === false, 'prefilter_nlp=false skips the expensive NLP text scan');
$assert($meta['limit'] === 3000, 'Default candidate limit is 3000');
sn_tl_fetch_candidates($fake, $activeStory + ['terms' => ['iran'], 'max_candidates' => 5], $meta, $now);
$assert($meta['limit'] === 100, 'Candidate limit is clamped to a minimum of 100');

// Truncation drops only the OLDEST partial day, never the newest articles
$fake->rows = [];
for ($i = 0; $i < 100; $i++) {
    $ts = (new DateTimeImmutable('2026-10-14 18:00:00', $tzET))->modify('-' . ($i * 3) . ' hours')->format('c');
    $fake->rows[] = $dbRow(1000 - $i, 'Iran item ' . $i, $ts);
}
$trunc = sn_tl_fetch_candidates($fake, $activeStory + ['terms' => ['iran'], 'max_candidates' => 100], $meta, $now);
$assert($meta['truncated'] === true && $meta['coverage_from'] !== null, 'Hitting the cap is flagged as truncated');
$assert(in_array(1000, array_column($trunc, 'id'), true), 'Newest article is kept when truncated');
$assert(count($trunc) < 100, 'The partial oldest day is dropped when truncated');
$oldestKept = new DateTimeImmutable((string)$trunc[0]['pub_date']);
$assert($oldestKept->setTimezone($tzET)->format('Y-m-d') >= $meta['coverage_from'], 'Everything kept is on or after coverage_from');

// Classification extras
$ir = sn_tl_get_story('iran');
$assert($ir !== null && $ir['status'] === 'active' && $ir['window']['to'] === null, 'Iran story is active with an open window');
$assert(($ir['prefilter_nlp'] ?? true) === false, 'Iran story skips the NLP text prefilter');
$assert(sn_tl_classify($art(1, 'Trump says US strikes on Iran will continue', '', [], '2026-03-01T12:00:00Z'), $ir)['tier'] === 'core', 'Iran war headline is core');
$assert(sn_tl_classify($art(2, 'Iran v Iran: the fans in the stands at the World Cup', '', [], '2026-06-15T12:00:00Z'), $ir)['tier'] === 'rejected', 'World Cup headline is rejected');
$assert(sn_tl_classify($art(3, 'Iranian Students Shine Amid War, Winning 18 Gold Medals at International Science Olympiads', '', [], '2026-10-14T12:00:00Z'), $ir)['tier'] === 'rejected', 'Science Olympiad headline is rejected');
$assert(sn_tl_classify($art(4, 'Iranian soccer team lands in Mexico', '', [], '2026-06-15T12:00:00Z', 'https://www.cnn.com/x', 'sports'), $ir)['tier'] === 'rejected', 'Sports category is rejected');
$assert(sn_tl_classify($art(5, 'Iranian artisans revive carpet weaving', 'A craft profile', [], '2026-05-15T12:00:00Z'), $ir)['tier'] === 'related', 'Iran headline with no US–Iran/war context is related, not core');
$assert(sn_tl_classify($art(6, 'Oil jumps as Strait of Hormuz traffic halts', 'Tankers wait offshore', [], '2026-03-06T12:00:00Z'), $ir)['tier'] === 'related', 'Hormuz-only headline is related');
$assert(sn_tl_classify($art(7, 'Tehran confirms ceasefire talks', '', [], '2026-04-08T12:00:00Z'), $ir)['tier'] === 'core', 'Tehran ceasefire headline is core');
$assert(sn_tl_classify($art(8, 'Passing mention', 'Iran', ['iran' => 1], '2026-04-08T12:00:00Z'), $ir)['tier'] === 'rejected', 'Passing Iran mention is rejected');

// title_exclude is generic and does not beat pins
$teStory = ['slug' => 't', 'match' => ['primary' => '/\bfoo\b/i', 'entity' => '/^foo$/'], 'title_exclude' => ['/\bbar\b/i'], 'pins' => [9 => 'related']];
$assert(sn_tl_classify($art(1, 'Foo bar', '', [], '2026-01-01T00:00:00Z'), $teStory)['tier'] === 'rejected', 'title_exclude rejects matching headlines');
$assert(sn_tl_classify($art(9, 'Foo bar', '', [], '2026-01-01T00:00:00Z'), $teStory)['tier'] === 'related', 'Pins override title_exclude');

// Review states: reviewed / latest / uncovered
$tstory = [
    'slug' => 'tl-review', 'status' => 'active', 'window' => ['from' => '2026-03-01', 'to' => null],
    'terms' => ['foo'], 'match' => ['primary' => '/\bfoo\b/i', 'entity' => '/^foo$/'],
    'chapters' => [
        ['from' => '2026-03-01', 'to' => '2026-03-05', 'title' => 'A', 'summary' => '', 'basis' => []],
        ['from' => '2026-03-10', 'to' => '2026-03-12', 'title' => 'B', 'summary' => '', 'basis' => []],
    ],
];
$rr = [
    $art(1, 'Foo one', '', [], '2026-03-02T15:00:00Z', 'https://www.cnn.com/1'),
    $art(2, 'Foo two', '', [], '2026-03-07T15:00:00Z', 'https://www.bbc.com/2'),
    $art(3, 'Foo three', '', [], '2026-03-11T15:00:00Z', 'https://www.npr.org/3'),
    $art(4, 'Foo four', '', [], '2026-03-20T15:00:00Z', 'https://www.nbcnews.com/4'),
    $art(5, 'Foo five', '', [], '2026-03-21T15:00:00Z', 'https://www.foxnews.com/5'),
];
$rm = sn_tl_assemble($tstory, $rr, ['window' => ['from' => '2026-03-01', 'to' => '2026-03-25', 'open' => true]]);
$assert(($rm['days']['2026-03-02']['review'] ?? '') === 'reviewed', 'Day inside a chapter is reviewed');
$assert(($rm['days']['2026-03-07']['review'] ?? '') === 'uncovered', 'Day between chapters is flagged uncovered');
$assert(($rm['days']['2026-03-20']['review'] ?? '') === 'latest' && ($rm['days']['2026-03-21']['review'] ?? '') === 'latest', 'Days after the last chapter are latest');
$assert(($rm['stats']['latest_core'] ?? -1) === 2 && ($rm['stats']['uncovered_core'] ?? -1) === 1, 'Latest and uncovered core counts');
$assert(($rm['review']['latest_from'] ?? '') === '2026-03-20', 'latest_from is the first post-chapter day');
$noCh = sn_tl_assemble(['chapters' => []] + $tstory, $rr);
$assert(($noCh['days']['2026-03-02']['review'] ?? '') === 'latest', 'A story with no chapters treats everything as latest');

// Series bucketing: day / week / month
$mkDays = static function (string $start, int $n, int $step = 1): array {
    $days = [];
    $d = new DateTimeImmutable($start);
    for ($i = 0; $i < $n; $i++) {
        $k = $d->modify('+' . ($i * $step) . ' days')->format('Y-m-d');
        $days[$k] = ['date' => $k, 'core' => 1, 'related' => 0, 'articles' => []];
    }
    return $days;
};
$sd = sn_tl_volume_series($mkDays('2026-01-01', 30), null);
$assert($sd && $sd[0]['unit'] === 'day' && count($sd) === 30, 'Short spans bucket by day');
$sw = sn_tl_volume_series($mkDays('2026-01-01', 200), null);
$assert($sw[0]['unit'] === 'week' && count($sw) < 40 && count($sw) > 25, 'Medium spans bucket by week');
$assert(date('N', strtotime($sw[0]['date'])) === '1' && $sw[0]['to'] === date('Y-m-d', strtotime($sw[0]['date'] . ' +6 days')), 'Weekly buckets run Monday to Sunday');
$assert(array_sum(array_column($sw, 'core')) === 200, 'Weekly bucketing preserves the total');
$assert($sw[0]['target'] >= $sw[0]['date'] && $sw[0]['target'] <= $sw[0]['to'], 'Bucket target lies inside the bucket');
$sm = sn_tl_volume_series($mkDays('2024-01-01', 900), null);
$assert($sm[0]['unit'] === 'month' && count($sm) <= 800, 'Long spans bucket by month');
$assert(array_sum(array_column($sm, 'core')) === 900, 'Monthly bucketing preserves the total');
$sl = sn_tl_volume_series($mkDays('2026-03-01', 30), '2026-03-20');
$latestBuckets = array_filter($sl, static fn($b) => !empty($b['latest']));
$assert(count($latestBuckets) > 0 && count($latestBuckets) < count($sl), 'Only buckets after the review cutoff are marked latest');
$gaps = sn_tl_volume_series($mkDays('2026-01-01', 10, 5), null);
$assert(count($gaps) === 46 && $gaps[1]['core'] === 0 && $gaps[1]['target'] === null && $gaps[5]['core'] === 1, 'Empty buckets are filled in with no jump target');

// max_items trimming drops the oldest related items first
$many = [];
for ($i = 0; $i < 80; $i++) {
    $many[] = $art(2000 + $i, 'Foo core ' . $i, '', [], (new DateTimeImmutable('2026-03-01 12:00', $tzET))->modify('+' . $i . ' hours')->format('c'), 'https://www.site' . ($i % 7) . '.com/a' . $i);
}
for ($i = 0; $i < 40; $i++) {
    $many[] = $art(3000 + $i, 'Unrelated title ' . $i, 'd', ['foo' => 6], (new DateTimeImmutable('2026-03-01 12:30', $tzET))->modify('+' . $i . ' hours')->format('c'), 'https://www.rel' . ($i % 5) . '.com/a' . $i);
}
$trim = sn_tl_assemble(['max_items' => 100, 'related_entity_min' => 3, 'core_entity_min' => 20] + $tstory, $many);
$total = $trim['stats']['core_articles'] + $trim['stats']['related_articles'];
$assert($total <= 100 && ($trim['stats']['trimmed'] ?? 0) > 0, 'max_items caps the rendered item count');
$assert($trim['stats']['core_articles'] === 80, 'Related items are trimmed before core items');

// Cache: single-flight model, stale-on-error, and failure back-off
define('SN_TL_CACHE_DIR', sys_get_temp_dir() . '/sn_tl_test_' . getmypid());
@mkdir(SN_TL_CACHE_DIR);
$cStory = $tstory + ['cache_ttl' => 120];
$cStory['slug'] = 'tl-cache';
$okPdo = new TlFakePdo();
$okPdo->rows = [$dbRow(1, 'Foo cached', '2026-10-13T15:00:00Z')];
$built = sn_tl_build_model($cStory, $okPdo);
$assert($built !== null && $okPdo->prepared === 1, 'First build queries the database');
$again = sn_tl_build_model($cStory, $okPdo);
$assert($again !== null && $okPdo->prepared === 1, 'Second build within the TTL is served from cache');
foreach (glob(SN_TL_CACHE_DIR . '/sn_tl_tl-cache_*.ser') as $f) {
    touch($f, time() - 3600);
}
$badPdo = new TlFakePdo();
$badPdo->fail = new RuntimeException('db down');
$prevLog = ini_set('error_log', sys_get_temp_dir() . '/sn_tl_test.log');
$stale = sn_tl_build_model($cStory, $badPdo);
$assert($stale !== null && $stale['stats']['core_articles'] === $built['stats']['core_articles'], 'Expired cache is served stale when the database fails');
$assert($badPdo->prepared === 1, 'A failed rebuild tried the database once');
sn_tl_build_model($cStory, $badPdo);
$assert($badPdo->prepared === 1, 'Failure back-off prevents hammering the database');
ini_set('error_log', (string)$prevLog);
foreach (glob(SN_TL_CACHE_DIR . '/*') as $f) {
    @unlink($f);
}
@rmdir(SN_TL_CACHE_DIR);
@unlink(sys_get_temp_dir() . '/sn_tl_test.log');

foreach ($failures as $failure) {
    fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
}
if ($failures) {
    exit(1);
}
echo 'PASS: ' . $checks . ' Coverage Timelines matching checks' . PHP_EOL;
