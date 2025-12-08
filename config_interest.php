<?php
// config_interest.php

// Minimum total entities to count as a "Deep dive" article
define('SCROLL_INTEREST_ENTITY_THRESHOLD', 12);

// Domains considered "high-signal publishers"
$SCROLL_HIGH_SIGNAL_PUBLISHERS = [
    'cnn.com'     => true,
    'nbcnews.com' => true,
];

/**
 * Normalize a URL to a bare domain (no scheme, no www).
 */
function scroll_normalize_domain(?string $url): ?string {
    if (!$url) return null;

    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return null;

    // strip leading www.
    $host = preg_replace('/^www\./i', '', $host);
    return strtolower($host);
}

/**
 * Is this article a "Deep dive" based on entity count?
 *
 * @param array $article  Article row with either 'entity_count'
 *                        or 'entities_json' you can derive from.
 */
function scroll_is_deep_dive(array $article): bool {
    // Prefer precomputed entity_count if you have it
    if (isset($article['entity_count'])) {
        $count = (int)$article['entity_count'];
    } elseif (!empty($article['entities_json'])) {
        // example fallback: decode and count all entities
        $entities = json_decode($article['entities_json'], true);
        $count = is_array($entities) ? count($entities) : 0;
    } else {
        $count = 0;
    }

    return $count >= SCROLL_INTEREST_ENTITY_THRESHOLD;
}

/**
 * Is this article from a configured high-signal publisher?
 */
function scroll_is_high_signal_publisher(array $article): bool {
    global $SCROLL_HIGH_SIGNAL_PUBLISHERS;

    $domain = null;

    if (!empty($article['domain'])) {
        $domain = strtolower(preg_replace('/^www\./i', '', $article['domain']));
    } elseif (!empty($article['source_slug'])) {
        $domain = strtolower(preg_replace('/^www\./i', '', $article['source_slug']));
    } elseif (!empty($article['url'])) {
        $domain = scroll_normalize_domain($article['url']);
    } elseif (!empty($article['link'])) { // <--- add this
        $domain = scroll_normalize_domain($article['link']);
    }

    if (!$domain) return false;

    return !empty($SCROLL_HIGH_SIGNAL_PUBLISHERS[$domain]);
}

/**
 * Return an array of badges for an article.
 * Each badge is an associative array with 'slug' and 'label'.
 */
function scroll_get_article_badges(array $article): array {
    $badges = [];

    if (scroll_is_deep_dive($article)) {
        $badges[] = [
            'slug'  => 'deep-dive',
            'label' => 'Deep dive',
        ];
    }

    if (scroll_is_high_signal_publisher($article)) {
        $badges[] = [
            'slug'  => 'high-signal-publisher',
            'label' => 'High-Signal Publisher',
        ];
    }

    return $badges;
}
