<?php
// Curated story: Greenland. Chapter wording is editorial and is based only on titles of the cited articles.
return [
    'slug'   => 'greenland',
    'order'  => 1,
    'title'  => 'Greenland',
    'kicker' => 'US–Denmark–Greenland',
    'dek'    => 'How coverage of the Trump administration’s push to acquire Greenland escalated, peaked in Davos, and faded.',
    'status' => 'closed',
    'window' => ['from' => '2026-01-04', 'to' => '2026-02-28'],
    'aliases' => ['greenland', 'greenland acquisition', 'trump greenland'],
    'terms'  => ['greenland'],
    'exclude_categories' => ['sports', 'entertainment'],
    'core_entity_min'    => 10,
    'related_entity_min' => 3,
    'match' => [
        'primary' => '/\bgreenland(ers?)?\b/iu',
        'entity'  => '/^greenland(er|ers)?$/u',
        'related' => [],
    ],
    'pins' => [
        12948 => 'related',
        12990 => 'related',
        12615 => 'related',
        17912 => 'related',
    ],
    'excludes' => [
        12737 => 'Supreme Court tariffs piece; Greenland only in description',
        12754 => 'Alaska science events listing',
        13860 => 'Iran column; passing mention',
        16748 => 'Science history calendar',
        12807 => 'Podcast interview; passing mention',
        17181 => 'Tariff ruling business reaction',
    ],
    'gap_min_days' => 3,
    'hero_image' => true,
    'notes' => [
        'Coverage is CNN-heavy in the archive; see the publisher mix.',
        'Wording of chapters is editorial interpretation, not article content.',
    ],
    'chapters' => [
        [
            'from' => '2026-01-06', 'to' => '2026-01-09',
            'title' => 'The White House opens the door',
            'summary' => 'Administration officials say acquiring Greenland is under discussion and that military options are not ruled out.',
            'basis' => [10968, 10992, 11037, 11039],
        ],
        [
            'from' => '2026-01-10', 'to' => '2026-01-14',
            'title' => 'Pushback from Greenland and Denmark',
            'summary' => 'Greenlandic political parties respond, and talks involving Denmark, Greenland and the US end with disagreement.',
            'basis' => [11504, 11515, 11540, 12090],
        ],
        [
            'from' => '2026-01-15', 'to' => '2026-01-19',
            'title' => 'Polls, tariff threats and allied alarm',
            'summary' => 'Polling shows public opposition, tariff threats target the effort, and UK and US lawmakers react.',
            'basis' => [12159, 12181, 12403, 12431, 12665],
        ],
        [
            'from' => '2026-01-20', 'to' => '2026-01-23',
            'title' => 'Davos: from force to a framework',
            'summary' => 'Around the Davos speech, coverage reports the president stepping back from military force and a “framework” deal.',
            'basis' => [12925, 12931, 12965],
        ],
        [
            'from' => '2026-01-24', 'to' => '2026-02-28',
            'title' => 'Aftermath',
            'summary' => 'Coverage thins out; a later hospital-ship announcement draws fact-checks.',
            'basis' => [17423, 17785],
        ],
    ],
];
