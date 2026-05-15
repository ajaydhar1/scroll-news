<?php
// ___active_headlines.php

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

        if ($_SERVER['SERVER_NAME'] === 'localhost') {
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
?>

<style>

/* Shell – inherit your existing card style but allow it to be a little denser */
.sn-card-active-headlines {
    /* if your base .sn-card already has padding/bg/rounded/shadow,
       you can leave this mostly empty, or just tweak spacing */
}

/* Header layout */
.sn-card-header-inline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.sn-card-header-left {
    min-width: 0; /* so the title can truncate nicely */
}

.sn-card-title {
    font-size: 0.95rem; /* smaller, dashboard-y */
    margin: 0;
}

.sn-card-subtitle {
    display: block;
    font-size: 0.75rem;
    opacity: 0.7;
    margin-top: 0.15rem;
}

/* LIVE pill */
.sn-live-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.15rem 0.55rem;
    border-radius: 999px;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: rgba(255, 0, 0, 0.15);
    border: 1px solid rgba(255, 0, 0, 0.45);
}

.sn-live-dot {
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background: #ff4a4a;
    box-shadow: 0 0 0 4px rgba(255, 74, 74, 0.35);
    animation: sn-live-pulse 1.4s ease-out infinite;
}

@keyframes sn-live-pulse {
    0% {
        transform: scale(0.8);
        opacity: 1;
    }
    100% {
        transform: scale(1.4);
        opacity: 0;
    }
}

/* Body / list */
.sn-card-body-tight {
    padding-top: 0.6rem; /* if you want slightly tighter body in this card */
}

.sn-headline-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.sn-headline-item + .sn-headline-item {
    margin-top: 0.45rem;
    padding-top: 0.45rem;
    border-top: 1px solid rgba(255, 255, 255, 0.06); /* tweak for theme */
}

.sn-headline-link {
    text-decoration: none;
    font-weight: 700;
    font-size: 16px;
    line-height: 1.4;
    display: inline-block;
}

.sn-headline-link:hover {
    text-decoration: underline;
}

.sn-headline-meta {
    font-size: 0.72rem;
    opacity: 0.7;
    margin-top: 0.1rem;
}

.sn-headline-source {
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 500;
}



.sn-time-marker {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #6c757d; /* muted gray */
    margin-bottom: 0.15rem;
}


.sn-headline-meta {
    line-height: 1.2;
}

.sn-headline-favicon {
    border-radius: 3px;
    margin-right: 1px;
}

</style>

<div class="sn-card-active-headlines mb-4">
    <div class="sn-card-header sn-card-header-inline">
        <div class="sn-card-header-left">
            <div class="sn-time-marker">NOW</div>
            <h2 class="sn-card-title">
                Breaking News
            </h2>
            <span class="sn-card-subtitle">Live from NBC News (cached ~5 min)</span>
        </div>
        <div class="sn-card-header-right">
            <span class="sn-live-pill">
                <span class="sn-live-dot"></span>
                LIVE
            </span>
        </div>
    </div>

    <?php if (empty($activeHeadlines)): ?>
        <div class="sn-card-body">
            <p class="sn-muted small mb-0">
                No active headlines available right now. Please try again in a moment.
            </p>
        </div>
    <?php else: ?>
        <div class="sn-card-body sn-card-body-tight">
            <ul class="sn-headline-list">
                <?php foreach ($activeHeadlines as $headline): ?>
                    <li class="sn-headline-item">
                        <a
                            href="<?php echo htmlspecialchars($headline['link'], ENT_QUOTES, 'UTF-8'); ?>"
                            class="sn-headline-link"
                            target="_blank"
                            rel="noopener"
                        >
                            <?php
                                $titleRaw = (string)($headline['title'] ?? '');
                                $titleUi  = headline_with_emojis($titleRaw, 1);
                                echo htmlspecialchars($titleUi, ENT_QUOTES, 'UTF-8');
                            ?>
                        </a>

                        <?php if (!empty($headline['pub_human'])): ?>
                            <div class="sn-headline-meta d-flex align-items-center gap-1 flex-wrap">

                                <!-- NBC Favicon -->
                                <img src="https://www.google.com/s2/favicons?sz=64&domain=nbcnews.com"
                                    alt="NBC News"
                                    width="14"
                                    height="14"
                                    class="sn-headline-favicon">

                                <span class="sn-headline-source">
                                    <?php echo htmlspecialchars($headline['source'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>

                                ·

                                <span class="sn-headline-time">
                                    <?php echo htmlspecialchars($headline['pub_human'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>

                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
