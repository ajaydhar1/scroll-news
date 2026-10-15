<?php
// Curated story: Alex Pretti. Sensitive subject: no hero image, no sentiment/emotion badges.
return [
    'slug'   => 'alex-pretti',
    'order'  => 2,
    'title'  => 'Alex Pretti',
    'kicker' => 'Minneapolis',
    'dek'    => 'Coverage of the fatal shooting of Alex Pretti in Minneapolis, the shifting official account, and the political fallout.',
    'status' => 'closed',
    'window' => ['from' => '2026-01-24', 'to' => '2026-03-31'],
    'aliases' => ['alex pretti', 'pretti', 'minneapolis shooting'],
    'terms'  => ['pretti'],
    'exclude_categories' => ['sports', 'entertainment'],
    'core_entity_min'    => 15,
    'related_entity_min' => 5,
    'match' => [
        'primary' => '/\bpretti\b/iu',
        'entity'  => '/^(alex )?pretti$/u',
        'related' => [],
    ],
    'pins' => [
        13419 => 'core',
        13416 => 'core',
        13413 => 'core',
        16038 => 'related',
        13641 => 'related',
        13864 => 'related',
        15667 => 'related',
    ],
    'excludes' => [
        17061 => 'Athletes and politics feature; passing mention',
        16750 => 'Scripture and midterms; passing mention',
        22156 => 'Springsteen tour; passing mention',
    ],
    'gap_min_days' => 3,
    'hero_image' => false,
    'notes' => [
        'Sensitive subject: no hero image, and no sentiment or emotion labels are shown.',
        'Article thumbnails are intentionally hidden for this story.',
    ],
    'chapters' => [
        [
            'from' => '2026-01-25', 'to' => '2026-01-26',
            'title' => 'The shooting and first reactions',
            'summary' => 'Political and business leaders react while questions arise about the administration’s description of the killing.',
            'basis' => [13421, 13435, 13428, 13542, 13555],
        ],
        [
            'from' => '2026-01-27', 'to' => '2026-01-29',
            'title' => 'A shifting account',
            'summary' => 'Coverage reports White House walk-backs, open investigation questions, and newly surfaced video.',
            'basis' => [13611, 13702, 13868],
        ],
        [
            'from' => '2026-01-30', 'to' => '2026-03-31',
            'title' => 'Vigils, business response and aftermath',
            'summary' => 'Vigils and reflection by health-care workers and business leaders continue after the news cycle moves on.',
            'basis' => [14371, 14587],
        ],
    ],
];
