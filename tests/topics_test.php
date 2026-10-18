<?php
/**
 * Topics: config validation, de-duplication, layout planning and fetch pagination.
 * No database needed. Run: php tests/topics_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/core/___modules.php';
require_once BASE_PATH . '/core/topics/___topics.php';

$failures = [];
$checks = 0;
$assert = static function (bool $ok, string $msg) use (&$failures, &$checks): void {
    $checks++;
    if (!$ok) {
        $failures[] = $msg;
    }
};

// --- Config ------------------------------------------------------------
$all = sn_topics_all();
$assert(count($all) >= 6, 'Shipped config defines the six launch topics');
$assert(isset($all['iran'], $all['artificial-intelligence']), 'Slugs key the registry');
$assert(array_keys($all) === array_column($all, 'slug'), 'Registry is keyed by slug');
$orders = array_column(array_values($all), 'order');
$sorted = $orders;
sort($sorted);
$assert($orders === $sorted, 'Registry is ordered by the order key');
$assert(sn_topic_get('IRAN')['slug'] === 'iran', 'Slug lookup is case-insensitive');
$assert(sn_topic_get('../etc') === null && sn_topic_get('') === null && sn_topic_get(null) === null, 'Bad slugs are rejected');
$assert(sn_topic_get('nope') === null, 'Unknown slug returns null');

$bad = sn_topics_all([
    ['slug' => 'Bad Slug', 'name' => 'x', 'query' => 'x'],
    ['slug' => 'ok', 'name' => '', 'query' => 'x'],
    ['slug' => 'good', 'name' => 'Good', 'query' => 'good', 'accent' => 'red', 'hero' => 'nonsense', 'density' => 'huge', 'layout' => 'x', 'range_hours' => 999999],
    ['slug' => 'good', 'name' => 'Dupe', 'query' => 'dupe'],
    ['slug' => 'hidden', 'name' => 'Hidden', 'query' => 'h', 'home' => false, 'order' => 1],
]);
$assert(array_keys($bad) === ['hidden', 'good'], 'Invalid and duplicate entries are dropped');
$g = $bad['good'];
$assert($g['accent'] === null && $g['hero'] === 'auto' && $g['density'] === 'standard' && $g['layout'] === 'compact-editorial', 'Invalid enum/accent values fall back to defaults');
$assert($g['terms'] === ['good'] && $g['range_hours'] === 2160, 'Terms default to the query; range is clamped');
$assert(array_column(array_filter($bad, static fn($t) => $t['home']), 'slug') === ['good'], 'home=false topics are excluded from chips');
$assert(sn_topic_window_ladder(['range_hours' => 168]) === [168, 720, 2160], 'Window ladder widens past the default');
$assert(sn_topic_window_ladder(['range_hours' => 2160]) === [2160], 'Ladder never narrows or repeats');
$assert(sn_topic_window_label(24) === 'past 24 hours' && sn_topic_window_label(168) === 'past 7 days', 'Window labels are human readable');

// --- Items -------------------------------------------------------------
$assert(sn_topic_image_url('javascript:alert(1)') === null && sn_topic_image_url('') === null, 'Non-http images are rejected');
$laTimesResize = 'https://ca-times.brightspotcdn.com/dims4/default/a46dc97/2147483647/strip/true/crop/4000x2100+0+566/resize/1200x630!/quality/75/?url=https%3A%2F%2Fcalifornia-times-brightspot.s3.amazonaws.com%2F10%2F0a%2Fx%2Fa.jpg';
$assert(sn_topic_image_url($laTimesResize) === null, 'LA Times Brightspot resize URLs are not image-eligible');
$assert(sn_topic_image_url('https://ca-times.brightspotcdn.com/10/0a/x/a.jpg') !== null, 'Other LA Times CDN paths stay eligible');
$assert(sn_topic_image_url('https://other.brightspotcdn.com/dims4/default/a/b/resize/1200x630!/?url=x') !== null, 'Other Brightspot publishers stay eligible');
$laTimesItems = [['title' => 'LAT', 'image' => sn_topic_image_url($laTimesResize)], ['title' => 'Other', 'image' => 'https://i.example.com/o.jpg']];
$laTimesPlan = sn_topic_plan_layout($laTimesItems, ['hero' => 'auto', 'density' => 'standard'], 1);
$assert($laTimesPlan['lead']['title'] === 'Other' && $laTimesPlan['lead_mode'] === 'image', 'Planner skips LA Times resize image for the featured lead');
$assert($laTimesPlan['support'][0]['title'] === 'LAT' && empty($laTimesPlan['support'][0]['image']), 'LA Times story stays visible as text-only');
$assert(sn_topic_image_url('https://img.example.com/a.jpg') === 'https://img.example.com/a.jpg', 'https images are accepted');
$now = 1_800_000_000;
$assert(sn_topic_relative_time($now - 30, $now) === 'Just now', 'Relative time: just now');
$assert(sn_topic_relative_time($now - 600, $now) === '10 min ago', 'Relative time: minutes');
$assert(sn_topic_relative_time($now - 7200, $now) === '2 hr ago', 'Relative time: hours');
$assert(sn_topic_relative_time($now - 100000, $now) === 'Yesterday', 'Relative time: yesterday');
$assert(sn_topic_relative_time($now - 4 * 86400, $now) === '4 days ago', 'Relative time: days');
$assert(sn_topic_truncate(str_repeat('word ', 80), 100) !== '' && mb_strlen(sn_topic_truncate(str_repeat('word ', 80), 100)) <= 101, 'Descriptions truncate at a word boundary');

$topic = $all['iran'];
$ents = sn_topic_entities(['nlp' => json_encode(['entities' => [
    ['text' => 'Iran', 'label' => 'GPE', 'count' => 9],
    ['text' => 'Tehran', 'label' => 'GPE', 'count' => 8],
    ['text' => 'Pentagon', 'label' => 'ORG', 'count' => 3],
    ['text' => 'Marco Rubio', 'label' => 'PERSON', 'count' => 5],
    ['text' => 'Tuesday', 'label' => 'DATE', 'count' => 7],
    ['text' => 'Pentagon', 'label' => 'ORG', 'count' => 1],
    ['text' => 'NATO', 'label' => 'ORG', 'count' => 2],
    ['text' => 'EU', 'label' => 'ORG', 'count' => 20],
]])], $topic);
$assert($ents === ['Marco Rubio', 'Pentagon', 'NATO'], 'Entities skip the topic itself, dates and duplicates, ranked by count');
$assert(sn_topic_entities(['nlp' => null], $topic) === [] && sn_topic_entities(['nlp' => 'not json'], $topic) === [], 'Missing or invalid NLP yields no entities');
$assert(sn_topic_entities(['nlp' => json_encode(['entities' => ['bad', ['text' => '12345', 'label' => 'ORG'], ['text' => 'Reuters', 'label' => 'ORG']]])], $topic) === ['Reuters'], 'Malformed and non-word entities are dropped');

// --- De-duplication ----------------------------------------------------
$rows = [
    ['link' => 'https://www.a.com/story/1/', 'title' => 'Big news today - A News'],
    ['link' => 'https://a.com/story/1', 'title' => 'Different title'],
    ['link' => 'https://b.com/x', 'title' => 'Big news today'],
    ['link' => 'https://c.com/y', 'title' => 'Another story'],
];
$assert(array_column(sn_topic_dedupe($rows), 'link') === ['https://www.a.com/story/1/', 'https://c.com/y'], 'Duplicate URLs and syndicated headlines collapse, keeping the newest');

// --- Layout planning ---------------------------------------------------
$mk = static function (int $n, array $imageIdx = []) {
    $items = [];
    for ($i = 0; $i < $n; $i++) {
        $items[] = ['title' => "Story $i", 'image' => in_array($i, $imageIdx, true) ? "https://i.example.com/$i.jpg" : null];
    }
    return $items;
};
$titles = static function (array $plan): array {
    $t = [];
    if ($plan['lead']) {
        $t[] = $plan['lead']['title'];
    }
    foreach (array_merge($plan['support'], $plan['rail']) as $it) {
        $t[] = $it['title'];
    }
    foreach ($plan['stream'] as $b) {
        foreach ($b['items'] as $it) {
            $t[] = $it['title'];
        }
    }
    return $t;
};

$empty = sn_topic_plan_layout([], $topic);
$assert($empty['lead'] === null && !$empty['support'] && !$empty['rail'] && !$empty['stream'], 'No items yields an empty plan');

foreach ([1, 2, 3, 4, 6, 9, 17, 30] as $n) {
    $plan = sn_topic_plan_layout($mk($n, [2, 5, 6, 11]), $topic);
    $out = $titles($plan);
    $assert(count($out) === $n && count(array_unique($out)) === $n, "Plan with $n items places each exactly once");
    $assert($plan['lead'] !== null, "Plan with $n items always has a lead");
    $assert(count($plan['support']) === min(2, $n - 1), "Plan with $n items fills support without gaps");
    foreach ($plan['stream'] as $b) {
        $assert(!empty($b['items']), "Plan with $n items has no empty stream block");
    }
}

$withImg = sn_topic_plan_layout($mk(10, [3, 4]), $topic);
$assert($withImg['lead']['title'] === 'Story 3' && $withImg['lead_mode'] === 'image', 'Lead is the newest story with an image');
$assert(array_column($withImg['support'], 'title') === ['Story 4', 'Story 0'] || count($withImg['support']) === 2, 'Support prefers imaged stories');
$noImg = sn_topic_plan_layout($mk(10), $topic);
$assert($noImg['lead']['title'] === 'Story 0' && $noImg['lead_mode'] === 'text', 'Without images the newest story leads as text');
$lateImg = sn_topic_plan_layout($mk(14, [11]), $topic);
$assert($lateImg['lead_mode'] === 'text' && $lateImg['lead']['title'] === 'Story 0', 'A distant imaged story does not displace the newest lead');
$textHero = sn_topic_plan_layout($mk(10, [3]), ['hero' => 'text'] + $topic);
$assert($textHero['lead_mode'] === 'text' && $textHero['lead']['title'] === 'Story 0', 'hero=text forces a text lead');
$assert(count(sn_topic_plan_layout($mk(20), ['density' => 'compact'] + $topic)['rail']) === 9, 'Compact density widens the rail');

$p2 = sn_topic_plan_layout($mk(12, [1]), $topic, 2);
$assert($p2['lead'] === null && !$p2['support'] && !$p2['rail'] && count($titles($p2)) === 12, 'Later pages contain only the stream');
$types = array_column(sn_topic_plan_layout($mk(30, range(0, 29)), $topic, 2)['stream'], 'type');
$assert(count(array_unique($types)) === 3, 'Stream varies block types');
$assert(!in_array('wide', array_column(sn_topic_plan_layout($mk(8), $topic, 2)['stream'], 'type'), true), 'Wide blocks need an image');

if ($failures) {
    fwrite(STDERR, 'FAIL: ' . implode("\nFAIL: ", $failures) . "\n");
    exit(1);
}
echo "OK, $checks checks\n";
