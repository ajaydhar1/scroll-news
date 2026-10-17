<?php
/**
 * Topics: the single source of truth for homepage chips and /topic.php pages.
 *
 * Keys per topic (only slug, name and query are required; see sn_topic_defaults()):
 *   slug         URL slug, [a-z0-9-]{1,60}
 *   name         Chip label and page headline
 *   query        Primary search term (also the "search all" link on the page)
 *   terms        Optional OR-set of terms; defaults to [query]
 *   exclude      Optional terms that disqualify an article
 *   word_match   Match whole words (essential for short terms such as "AI")
 *   home         Show as a homepage chip
 *   order        Homepage ordering (ascending)
 *   range_hours  Default recency window; auto-widens when coverage is sparse
 *   min_items    Below this many stories the window widens once or twice
 *   layout       Layout preset id (MVP: compact-editorial)
 *   hero         image | text | auto
 *   density      compact | standard | comfortable
 *   accent       Optional #hex accent colour
 *   dek          Optional one-line editorial intro
 *   timeline     Optional Coverage Timeline slug to cross-link
 */
return [
    [
        'slug' => 'trump',
        'name' => 'Trump',
        'query' => 'Trump',
        'terms' => ['Trump'],
        'order' => 10,
        'accent' => '#b4452f',
    ],
    [
        'slug' => 'elections',
        'name' => 'Elections',
        'query' => 'Elections',
        'terms' => [
            'election',
            'elections',
            'voting',
            'ballot',
            'midterms',
        ],
        'order' => 15,
        'accent' => '#635b9e',
    ],
    [
        'slug' => 'iran',
        'name' => 'Iran',
        'query' => 'Iran',
        'terms' => ['Iran', 'Iranian', 'Tehran'],
        'order' => 20,
        'accent' => '#8a6d1f',
        'timeline' => 'iran',
    ],
    [
        'slug' => 'artificial-intelligence',
        'name' => 'Artificial Intelligence',
        'query' => 'Artificial Intelligence',
        'terms' => ['artificial intelligence', 'AI', 'OpenAI', 'ChatGPT', 'Anthropic'],
        'order' => 30,
        'accent' => '#2f5fb4',
    ],
    [
        'slug' => 'economy',
        'name' => 'Economy',
        'query' => 'Economy',
        'terms' => ['economy', 'economic', 'inflation', 'recession', 'Federal Reserve', 'GDP'],
        'order' => 40,
        'accent' => '#2b7a5a',
    ],
    [
        'slug' => 'ukraine',
        'name' => 'Ukraine',
        'query' => 'Ukraine',
        'terms' => ['Ukraine', 'Ukrainian', 'Zelensky', 'Kyiv'],
        'order' => 50,
        'accent' => '#2f6f9f',
    ],
    [
        'slug' => 'climate',
        'name' => 'Climate',
        'query' => 'Climate',
        'terms' => ['climate change', 'global warming', 'climate crisis', 'emissions'],
        'order' => 60,
        'accent' => '#3f8a3a',
    ],
];
