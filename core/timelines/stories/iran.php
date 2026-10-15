<?php
// Curated ACTIVE story: US–Iran confrontation and war. Self-updating: window.to is open-ended, so new
// matching articles appear automatically; articles after the last chapter show as "Latest coverage".
// Chapter wording is editorial and is based only on the titles of the cited articles.
return [
    'slug'   => 'iran',
    'order'  => 0,
    'title'  => 'Iran',
    'kicker' => 'US–Iran confrontation',
    'dek'    => 'How coverage of the US–Iran confrontation moved from nuclear talks and strike threats to open war, a fragile ceasefire, a blockade and a long stalemate.',
    'status' => 'active',
    'window' => ['from' => '2026-02-16', 'to' => null],
    'aliases' => ['iran', 'iran war', 'us iran war', 'trump iran'],
    'terms'  => ['iran', 'tehran', 'hormuz', 'khamenei', 'kharg'],
    'prefilter_nlp'      => false,
    'max_candidates'     => 3000,
    'max_items'          => 1200,
    'cache_ttl'          => 600,
    'exclude_categories' => ['sports', 'entertainment'],
    'core_entity_min'    => 12,
    'related_entity_min' => 5,
    'match' => [
        'primary' => '/\b(iran|iranian|iranians|tehran)\b/iu',
        'entity'  => '/^(iran|iranian|iranians|tehran)$/u',
        // A headline naming Iran is core only with US–Iran/war framing somewhere in headline or description
        'context' => '/\b(war|strikes?|struck|ceasefire|cease-fire|truce|talks?|negotiat\w*|deal|nuclear|uranium|enrich\w*|sanctions?|blockade|hormuz|military|pentagon|missiles?|drones?|attacks?|bomb\w*|troops|carrier|navy|trump|rubio|witkoff|vance|netanyahu|israel\w*|white house|u\.s\.|us|american|oil|crude|tankers?|regime|ayatollah|khamenei|irgc|revolutionary guard|gulf|diplomacy|conflict|deadline|leverage|tensions|escalat\w*)\b/iu',
        'related' => [
            ['all_of' => ['/\b(hormuz|kharg|khamenei|persian gulf)\b/iu']],
        ],
    ],
    'title_exclude' => [
        '/\b(world cup|soccer|fifa|highlights|olympiads?|gold medals?)\b/iu',
    ],
    'pins' => [
        19574 => 'related', // Halkbank prosecution; Iran business angle only
        23864 => 'related', // Weekly briefing: Israel and US politics
        19008 => 'related', // UK–US relationship piece; Iran war linkage unclear
        51894 => 'related', // Iraqi PM visit to Tehran; regional diplomacy
    ],
    'excludes' => [
        17961 => 'Texas politics newsletter; Iran only in description',
        18798 => 'Primary-day newsletter; Iran only in description',
        18935 => 'Texas Senate primary newsletter',
        20507 => 'Illinois primary newsletter',
        21652 => 'Maine Senate race newsletter',
        25441 => 'Senate candidate profile newsletter',
        29034 => 'US diplomats forced out; generic crises piece',
        64755 => 'USS Abraham Lincoln mental-health piece; weak Iran link',
        27973 => 'Doomsday Clock explainer',
        23330 => 'Pasteur Institute public-health piece',
        33317 => 'Global science rankings',
        28137 => 'Australian politics roundup; Iran passing mention',
        94199 => 'Diesel deal with Russia; Iran only in description',
    ],
    'gap_min_days' => 3,
    'hero_image' => true,
    'notes' => [
        'Active story: new matching articles are added automatically. Only articles up to the last chapter have been reviewed by editors; newer articles appear under “Latest coverage”.',
        'The timeline starts on Feb 16, 2026 (US–Iran nuclear talks). Earlier coverage, including January’s protests and tariff threats, is not included.',
        'Coverage includes Iranian state media (tasnimnews.ir); read it as that outlet’s own account.',
        'Wording of chapters is editorial interpretation, not article content.',
    ],
    'chapters' => [
        [
            'from' => '2026-02-16', 'to' => '2026-02-27',
            'title' => 'Talks, a deadline and strike options',
            'summary' => 'Nuclear talks open in Geneva, the president sets a 10–15 day window, and coverage turns to military options.',
            'basis' => [16720, 17164, 17750, 17736, 17997],
        ],
        [
            'from' => '2026-02-28', 'to' => '2026-03-09',
            'title' => 'The strikes begin',
            'summary' => 'The president announces an attack on Iran; Congress and allies react, and analysis questions the war’s stated goals.',
            'basis' => [18348, 18365, 18707, 19201, 19407],
        ],
        [
            'from' => '2026-03-10', 'to' => '2026-04-06',
            'title' => 'A widening war and the ground-troops question',
            'summary' => 'Weeks into the war, coverage examines endgames, the Strait of Hormuz and Kharg Island, and whether to put troops on the ground.',
            'basis' => [19655, 20932, 21042, 21568],
        ],
        [
            'from' => '2026-04-07', 'to' => '2026-04-12',
            'title' => 'Ceasefire and uncertain talks',
            'summary' => 'A ceasefire is announced around the president’s deadline, followed by questions about whether it will hold and what a deal would look like.',
            'basis' => [23072, 23253, 23318, 23495],
        ],
        [
            'from' => '2026-04-13', 'to' => '2026-05-31',
            'title' => 'Blockade and deadlock',
            'summary' => 'A blockade of the strait, an extended ceasefire and on-off deal talks dominate, with analysts describing the administration as stuck.',
            'basis' => [23940, 24621, 25297, 26548, 27725, 31255],
        ],
        [
            'from' => '2026-06-01', 'to' => '2026-07-31',
            'title' => 'Exchanges of fire, uranium and an off-ramp',
            'summary' => 'Israel and the US exchange fire with Iran, reports describe a paused mission to seize uranium, a 14-point peace plan appears, and later coverage questions the administration’s claims.',
            'basis' => [33480, 35095, 35238, 36187, 36680, 51630],
        ],
        [
            'from' => '2026-08-01', 'to' => '2026-09-30',
            'title' => 'Expiring understanding and renewed strikes',
            'summary' => 'An understanding is set to expire, the president threatens countries aiding Iran, and new US strikes target the Strait of Hormuz and Kharg Island area.',
            'basis' => [64760, 66779, 72394, 76617, 85621],
        ],
    ],
];
