<?php

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/core/___modules.php';
require_once BASE_PATH . '/core/newsroom/___newsroom_meta.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$url = 'https://publisher.example/story';
$article = [
    'title' => 'Stored article title',
    'description' => 'Stored article description',
    'media_url' => 'https://publisher.example/image.jpg',
    'source_slug' => 'Example Publisher',
];

$lookupCalls = 0;
$fetchCalls = 0;
$complete = newsroom_resolve_article_data(
    $url,
    true,
    static function (string $requestedUrl) use (&$lookupCalls, $url, $article): array {
        $lookupCalls++;
        if ($requestedUrl !== $url) {
            throw new RuntimeException('Unexpected article URL');
        }
        return $article;
    },
    static function () use (&$fetchCalls) {
        $fetchCalls++;
        return false;
    }
);
$assert($complete['from_db'] && $complete['article'] === $article, 'Complete row should resolve as DB-backed');
$assert($complete['meta']['title'] === $article['title'], 'DB title should be used');
$assert($complete['meta']['description'] === $article['description'], 'DB description should be used');
$assert($complete['meta']['publisher'] === $article['source_slug'], 'DB source label should be used');
$assert($complete['meta']['image'] === $article['media_url'], 'DB media URL should be used');
$assert($lookupCalls === 1 && $fetchCalls === 0, 'Successful DB article must be looked up once and never fetched externally');

$missingImage = newsroom_resolve_article_data(
    $url,
    true,
    static fn(string $requestedUrl): array => array_merge($article, ['media_url' => null]),
    static function () use (&$fetchCalls) {
        $fetchCalls++;
        return false;
    }
);
$assert($missingImage['from_db'], 'DB row without image should remain DB-backed');
$assert($missingImage['meta']['image'] === '/assets/img/news-placeholder.jpg', 'Missing DB image should use placeholder');
$assert($fetchCalls === 0, 'Missing DB image must not trigger OpenGraph');

$missingFields = newsroom_resolve_article_data(
    $url,
    true,
    static fn(string $requestedUrl): array => ['title' => null, 'description' => null, 'media_url' => '', 'source_slug' => ''],
    static function () use (&$fetchCalls) {
        $fetchCalls++;
        return false;
    }
);
$assert($missingFields['from_db'], 'Sparse DB row should remain DB-backed');
$assert($missingFields['meta']['title'] === 'No Title', 'Missing DB title should have a safe fallback');
$assert($missingFields['meta']['description'] === '', 'Missing DB description should be empty');
$assert($missingFields['meta']['publisher'] === 'publisher.example', 'Missing DB source should fall back to URL host');
$assert($missingFields['meta']['image'] === '/assets/img/news-placeholder.jpg', 'Missing sparse-row image should use placeholder');
$assert($fetchCalls === 0, 'Missing optional DB fields must not trigger OpenGraph');

$externalFetch = static function (string $requestedUrl) use (&$fetchCalls, $url): ArrayIterator {
    $fetchCalls++;
    if ($requestedUrl !== $url) {
        throw new RuntimeException('Unexpected external URL');
    }
    return new ArrayIterator([
        'title' => 'External title',
        'description' => 'External description',
        'image' => 'https://publisher.example/og.jpg',
        'site_name' => 'External Publisher',
    ]);
};

$staleLookupCalls = 0;
$staleDb = newsroom_resolve_article_data(
    $url,
    true,
    static function (string $requestedUrl) use (&$staleLookupCalls): ?array {
        $staleLookupCalls++;
        return null;
    },
    $externalFetch
);
$assert(!$staleDb['from_db'] && $staleDb['article'] === null, 'Stale DB link should degrade to external URL mode');
$assert($staleDb['meta']['title'] === 'External title', 'Stale DB link should retain OG title');
$assert($staleLookupCalls === 1, 'Stale DB link should perform one lookup');
$assert($fetchCalls === 1, 'Stale DB link should make one injected OG fetch');

$external = newsroom_resolve_article_data($url, false, static function () use (&$lookupCalls) {
    $lookupCalls++;
    return null;
}, $externalFetch);
$assert(!$external['from_db'] && $external['meta']['publisher'] === 'External Publisher', 'Arbitrary external URLs should retain OG metadata');
$assert($lookupCalls === 1, 'External-only URL must not query DB');
$assert($fetchCalls === 2, 'External-only URL should perform one metadata fetch');

$fetchesBeforeFailure = $fetchCalls;
$failureLookupCalls = 0;
$lookupFailure = newsroom_resolve_article_data(
    $url,
    true,
    static function () use (&$failureLookupCalls): never {
        $failureLookupCalls++;
        throw new RuntimeException('Simulated offline database lookup');
    },
    $externalFetch
);
$assert(!$lookupFailure['from_db'] && $lookupFailure['meta']['title'] === 'External title', 'Lookup failure should degrade to external metadata');
$assert($failureLookupCalls === 1, 'Lookup failure should be attempted once');
$assert($fetchCalls === $fetchesBeforeFailure + 1, 'Lookup failure should make one injected OG fetch');

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
    }
    exit(1);
}

echo 'PASS: 26 Newsroom metadata resolution checks' . PHP_EOL;