<?php
// ___active_headlines.php
// "Live wire": slim headline strip rendered beneath the News Intelligence board.

require_once BASE_PATH . '/core/headlines/___emoji_headlines.php';

const ACTIVE_HEADLINES_LIMIT = 6;
const ACTIVE_HEADLINES_FEEDS = [
    'https://feeds.nbcnews.com/feeds/topstories',
];
const ACTIVE_HEADLINES_CACHE_TTL    = 300; // seconds = 5 minutes
const ACTIVE_HEADLINES_CACHE_FILE   = BASE_PATH . '/_cache_active_headlines/active_headlines_nbc.json';

function active_headlines_fetch_url(string $url): string|false
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_USERAGENT => 'ScrollNewsActiveHeadlines/1.0 (+https://scrollnews.ai)',
            CURLOPT_HTTPHEADER => [
                'Accept: application/rss+xml, application/xml;q=0.9, text/xml;q=0.8, */*;q=0.5',
            ],
        ]);

        if (($_SERVER['SERVER_NAME'] ?? '') === 'localhost') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);

        error_log("[ActiveHeadlines] cURL result for {$url}; status={$status}; bytes=" . strlen((string)$body) . "; error={$err}");

        curl_close($ch);

        if ($body === false || $status >= 400) {
            error_log("[ActiveHeadlines] cURL failed for {$url}; status={$status}; error={$err}");
            return false;
        }

        return $body;
    }

    return @file_get_contents($url);
}

/**
 * Fetch active headlines with a small file cache.
 *
 * Cache behavior:
 * - If cache exists and is younger than TTL -> use cache.
 * - If cache is expired -> try live fetch; on success, overwrite cache.
 * - If live fetch fails but we have *any* cached data -> return cached
 *   even if it's old, so the card doesn't go empty.
 */
function scrollnews_fetch_active_headlines(): array
{
    $cacheFile  = ACTIVE_HEADLINES_CACHE_FILE;
    $cacheTtl   = ACTIVE_HEADLINES_CACHE_TTL;
    $now        = time();
    $cachedData = null;

    $debug   = isset($_GET['debug_active_headlines']);
    $nocache = isset($_GET['nocache']) && $_GET['nocache'] === '1';

    if ($debug) {
        error_log("[ActiveHeadlines] DEBUG enabled");
        error_log("[ActiveHeadlines] cacheFile={$cacheFile}");
        error_log("[ActiveHeadlines] nocache=" . ($nocache ? 'yes' : 'no'));
    }

    // 1) Try to load any existing cache (even if it may be expired)
    if (!$nocache && is_readable($cacheFile)) {
        $json = @file_get_contents($cacheFile);
        if ($json !== false) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $cachedData = $decoded;
            }
        }

        $age = $now - @filemtime($cacheFile);
        if ($cachedData && $age >= 0 && $age < $cacheTtl) {
            error_log("[ActiveHeadlines] Using fresh cache ({$age}s old) from {$cacheFile}");
            return $cachedData;
        } else {
            error_log("[ActiveHeadlines] Cache present but expired ({$age}s old) at {$cacheFile}");
        }
    } else {
        error_log("[ActiveHeadlines] No cache file yet at {$cacheFile}");
    }

    // 2) Cache is missing or expired -> fetch live
    $items = [];

    foreach (ACTIVE_HEADLINES_FEEDS as $feedUrl) {
        if (count($items) >= ACTIVE_HEADLINES_LIMIT) {
            break;
        }

        $xmlString = active_headlines_fetch_url($feedUrl);
        if (!$xmlString) {
            error_log("[ActiveHeadlines] Failed to fetch feed: {$feedUrl}");
            continue;
        }

        $xml = @simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$xml || !isset($xml->channel->item)) {
            error_log("[ActiveHeadlines] Invalid XML or no <item> for feed: {$feedUrl}");
            continue;
        }

        $host = parse_url($feedUrl, PHP_URL_HOST) ?: 'Unknown source';

        foreach ($xml->channel->item as $item) {
            if (count($items) >= ACTIVE_HEADLINES_LIMIT) {
                break 2;
            }

            $title = trim((string) $item->title);
            $link  = trim((string) $item->link);

            if ($title === '' || $link === '') {
                continue;
            }

            $pubRaw =
                (string) ($item->pubDate ?? '') ?:
                (string) ($item->dateTimeWritten ?? '') ?:
                (string) ($item->updateDate ?? '');

            $ts = $pubRaw ? strtotime($pubRaw) : 0;
            if ($ts === false) {
                $ts = 0;
            }

            $pubHuman = $ts > 0 ? gmdate('M j, Y · H:i', $ts) . ' GMT' : '';

            $items[] = [
                'title'     => $title,
                'link'      => $link,
                'pub_ts'    => $ts,
                'pub_human' => $pubHuman,
                'source'    => $host,
            ];
        }
    }

    if ($debug) {
        error_log("[ActiveHeadlines] Parsed " . count($items) . " live items");

        if (!empty($items[0])) {
            error_log("[ActiveHeadlines] First live item: " . json_encode($items[0]));
        }
    }

    if (!empty($items)) {
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($cacheFile, json_encode($items), LOCK_EX);
        error_log("[ActiveHeadlines] Wrote cache with " . count($items) . " items to {$cacheFile}");

        return $items;
    }

    if ($cachedData) {
        error_log("[ActiveHeadlines] Live fetch failed, falling back to stale cache from {$cacheFile}");
        return $cachedData;
    }

    error_log("[ActiveHeadlines] No headlines available (no live, no cache)");
    return [];
}

$activeHeadlines = scrollnews_fetch_active_headlines();

$lwProfile = function_exists('sn_publisher_profile_url') ? (sn_publisher_profile_url('nbcnews.com') ?: '#') : '#';
?>
<aside class="sn-lw" aria-labelledby="snLwTitle">
    <div class="sn-lw-head">
        <span class="sn-lw-live"><span class="sn-lw-dot" aria-hidden="true"></span>Live</span>
        <h3 class="sn-lw-title" id="snLwTitle">Live wire</h3>
        <a class="sn-lw-src" href="<?= htmlspecialchars($lwProfile, ENT_QUOTES, 'UTF-8') ?>" data-loading>
            <img src="https://www.google.com/s2/favicons?sz=64&amp;domain=nbcnews.com" alt="" width="14" height="14" loading="lazy" decoding="async">
            <span>NBC News</span>
        </a>
    </div>

    <?php if (empty($activeHeadlines)): ?>
        <p class="sn-lw-empty">No live headlines available right now. Please try again in a moment.</p>
    <?php else: ?>
        <ul class="sn-lw-list">
            <?php foreach ($activeHeadlines as $headline): ?>
                <?php
                $titleUi = headline_with_emojis((string)($headline['title'] ?? ''), 1);
                $pubTs   = (int)($headline['pub_ts'] ?? 0);
                ?>
                <li class="sn-lw-item">
                    <a class="sn-lw-link"
                       href="<?= htmlspecialchars((string)$headline['link'], ENT_QUOTES, 'UTF-8') ?>"
                       target="_blank" rel="noopener">
                        <span class="sn-lw-t"><?= htmlspecialchars($titleUi, ENT_QUOTES, 'UTF-8') ?></span><span class="sn-nip-sr"> (opens in a new tab)</span>
                    </a>
                    <?php if (!empty($headline['pub_human'])): ?>
                        <time class="sn-lw-time"<?= $pubTs > 0 ? ' datetime="' . gmdate('c', $pubTs) . '" data-sn-rel' : '' ?>><?= htmlspecialchars((string)$headline['pub_human'], ENT_QUOTES, 'UTF-8') ?></time>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</aside>
