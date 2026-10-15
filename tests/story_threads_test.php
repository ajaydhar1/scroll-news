<?php

require_once __DIR__ . '/../core/timelines/___timelines.php';
require_once __DIR__ . '/../core/story_threads/___story_threads.php';

$failures = [];
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$failures, &$checks): void {
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
};

$tz = new DateTimeZone(SN_ST_TZ);
$now = new DateTimeImmutable('2026-03-20 15:00:00', $tz);

// Normalisation and junk filtering
$assert(sn_st_norm_entity('Trump') === 'donald trump', 'Trump aliases to Donald Trump');
$assert(sn_st_norm_entity('U.S.') === sn_st_norm_entity('United States'), 'U.S. and United States share one entity');
$assert(sn_st_is_junk('email print copy'), 'Share-button text is junk');
$assert(sn_st_is_junk('whatsapp linkedin'), 'Share-widget names are junk');
$assert(!sn_st_is_junk('lindsey graham'), 'Real names are not junk');
$assert(sn_st_contains_word('russia and ukraine', 'us') === false, 'Whole-word match: "us" is not inside "russia"');
$assert(sn_st_contains_word('talks with iran today', 'iran'), 'Whole-word match finds a real word');

// Status labels: series are oldest first, last cell is today
$pad = static fn(array $tail): array => array_merge(array_fill(0, 21 - count($tail), 0), $tail);
$assert(sn_st_status([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 2, 2, 3, 3])['key'] !== '', 'Status always has a key');
$flat = array_fill(0, 21, 3);
$assert(sn_st_status($flat)['key'] === 'sustained', 'Flat coverage is Sustained');
$rise = array_merge(array_fill(0, 7, 1), array_fill(0, 7, 2), array_fill(0, 7, 6));
$assert(in_array(sn_st_status($rise)['key'], ['building', 'peaking'], true), 'Rising coverage is Building or Peaking');
$fall = array_merge(array_fill(0, 7, 6), array_fill(0, 7, 2), [1, 1, 0, 0, 0, 0, 0]);
$assert(sn_st_status($fall)['key'] === 'cooling', 'Fading coverage is Cooling');
$resurf = array_merge([3, 3, 3, 3], array_fill(0, 12, 0), [0, 0, 0, 3, 4]);
$assert(sn_st_status($resurf)['key'] === 'resurfacing', 'Coverage after a long quiet gap is Resurfacing');
$peak = array_merge(array_fill(0, 14, 1), [1, 1, 1, 1, 2, 3, 9]);
$assert(sn_st_status($peak)['key'] === 'peaking', 'A spike on the most recent day is Peaking');
foreach ([$flat, $rise, $fall, $resurf, $peak] as $series) {
    $assert(trim(sn_st_status($series)['why']) !== '', 'Every status carries a plain-English reason');
}

// Synthetic corpus: one persistent story, one single-day burst, share-button junk, one duplicate headline
$rows = [];
$id = 1;
$hosts = ['cnn.com', 'bbc.com', 'npr.org', 'apnews.com', 'reuters.com'];
$ent = static fn(string $t, int $c = 2, string $l = 'PERSON'): array => ['t' => $t, 'c' => $c, 'l' => $l];
for ($d = 0; $d < 14; $d++) {
    for ($k = 0; $k < 2; $k++) {
        $day = $now->modify('-' . $d . ' days')->setTime(9 + $k, 0);
        $rows[] = [
            'id' => $id++,
            'title' => "Senate vote on Zelenko bill, day $d report $k",
            'description' => 'Senator Maria Velasquez and Ministry of Finance discuss the Zelenko bill.',
            'url' => 'https://www.' . $hosts[($d + $k) % 5] . '/a' . $id,
            'source_slug' => 'news',
            'pub_date' => $day->format('c'),
            'image_url' => '',
            'ents' => [$ent('Zelenko'), $ent('Maria Velasquez'), $ent('Ministry of Finance', 2, 'ORG'), $ent('Email Print Copy', 5, 'ORG')],
        ];
    }
}
// One-day burst should never become a thread
for ($k = 0; $k < 12; $k++) {
    $rows[] = [
        'id' => $id++, 'title' => "Burst Rocket event update $k", 'description' => 'Burst Rocket launch with Flash Agency.',
        'url' => 'https://www.' . $hosts[$k % 5] . '/b' . $id, 'source_slug' => 'news',
        'pub_date' => $now->setTime(8, $k)->format('c'), 'image_url' => '',
        'ents' => [$ent('Burst Rocket'), $ent('Flash Agency', 2, 'ORG')],
    ];
}
// Syndicated duplicate: same headline from another host on the same day
$rows[] = $rows[0];
$rows[count($rows) - 1]['id'] = $id++;
$rows[count($rows) - 1]['url'] = 'https://www.example.com/dup';

$prepared = sn_st_prepare($rows, $now);
$assert(count($prepared['articles']) === count($rows) - 1, 'Syndicated duplicate headline is removed');
$assert(count($prepared['days']) === SN_ST_DAYS, 'Window is 21 days');
foreach ($prepared['articles'] as $a) {
    $assert(!isset($a['tags']['email print copy']), 'Junk entity never becomes a tag');
}

$model = sn_st_build_model($rows, $now, ['min_lead_articles' => 6]);
$assert(isset($model['threads'], $model['topics'], $model['days']), 'Model exposes threads, topics and days');
$leads = array_column($model['threads'], 'lead');
$assert(!in_array('burst rocket', $leads, true), 'One-day burst is not a thread');
$assert(in_array('zelenko', $leads, true), 'Persistent story becomes a thread');

foreach ($model['threads'] as $t) {
    $assert(count($t['series']) === SN_ST_DAYS, 'Pulse strip has 21 cells');
    $assert(array_sum($t['series']) === $t['n'], 'Pulse strip sums to the article count');
    $assert($t['active'] >= 5, 'Thread has at least 5 active days');
    $assert($t['publishers'] >= 4, 'Thread spans at least 4 publishers');
    $assert(isset($t['status']['key'], $t['status']['why']), 'Thread has a status and reason');
    $assert(count($t['cast']) <= 5, 'Cast is capped');
    $assert(count($t['previews']) > 0, 'Thread has previews');
    $titles = array_column($t['previews'], 'title');
    $assert(count($titles) === count(array_unique($titles)), 'Previews are distinct');

    // Co-occurrence counts equal the true co-tag counts in the prepared articles
    foreach ($t['cast'] as $m) {
        $true = 0;
        foreach ($prepared['articles'] as $a) {
            if (isset($a['tags'][$t['lead']], $a['tags'][$m['entity']])) {
                $true++;
            }
        }
        $assert($m['shared'] <= $true && $m['shared'] > 0, 'Cast shared count is a real co-tag count');
        $assert($m['pct'] <= 100, 'Cast share never exceeds 100% of the thread');
    }
    foreach ($t['pairs'] as $p) {
        $true = 0;
        foreach ($prepared['articles'] as $a) {
            if (isset($a['tags'][$p['a']], $a['tags'][$p['b']])) {
                $true++;
            }
        }
        $assert($p['shared'] === $true, 'Pair count equals true co-tag count');
    }
}

// Timeline linking
$threads = [
    ['lead' => 'iran', 'name' => 'Iran', 'timeline' => null],
    ['lead' => 'greenland', 'name' => 'Greenland', 'timeline' => null],
    ['lead' => 'zelenko', 'name' => 'Zelenko', 'timeline' => null],
];
$stories = sn_tl_all_stories();
$linked = sn_st_link_timelines($threads, $stories, 'iran');
$leadsLinked = array_column($linked, 'lead');
$assert(!in_array('iran', $leadsLinked, true), 'Featured timeline story is dropped from threads');
$assert(in_array('greenland', $leadsLinked, true), 'Other threads remain');
$g = array_values(array_filter($linked, static fn($t) => $t['lead'] === 'greenland'))[0] ?? null;
$assert($g !== null && is_array($g['timeline']) && $g['timeline']['slug'] === 'greenland', 'Curated story links to its timeline');
$z = array_values(array_filter($linked, static fn($t) => $t['lead'] === 'zelenko'))[0] ?? null;
$assert($z !== null && $z['timeline'] === null, 'Unmatched thread gets no timeline link');
$assert(count(sn_st_link_timelines($threads, $stories, null, 1)) === 1, 'Max limit is honoured');

if ($failures) {
    fwrite(STDERR, "FAILED ($checks checks)\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}
echo "story_threads_test: OK ($checks checks)\n";
