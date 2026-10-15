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
$assert(isset($stories['greenland'], $stories['alex-pretti'], $stories['venezuela']), 'Three curated stories are registered');
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

foreach ($failures as $failure) {
    fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
}
if ($failures) {
    exit(1);
}
echo 'PASS: ' . $checks . ' Coverage Timelines matching checks' . PHP_EOL;
