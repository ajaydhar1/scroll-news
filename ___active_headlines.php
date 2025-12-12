<?php
// ___active_headlines.php

const ACTIVE_HEADLINES_LIMIT = 6;
const ACTIVE_HEADLINES_FEEDS = [
    'https://feeds.nbcnews.com/nbcnews/public/news',
];
const ACTIVE_HEADLINES_CACHE_TTL    = 300; // seconds = 5 minutes
const ACTIVE_HEADLINES_CACHE_FILE   = __DIR__ . '/cache/active_headlines_nbc.json';

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

    // 1) Try to load any existing cache (even if it may be expired)
    if (is_readable($cacheFile)) {
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
    $httpContext = stream_context_create([
        'http' => ['timeout' => 4],
        'ssl'  => [
            'verify_peer'      => true,
            'verify_peer_name' => true,
        ],
    ]);

    foreach (ACTIVE_HEADLINES_FEEDS as $feedUrl) {
        if (count($items) >= ACTIVE_HEADLINES_LIMIT) {
            break;
        }

        $xmlString = @file_get_contents($feedUrl, false, $httpContext);
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
    font-weight: 500;
    font-size: 0.82rem;
    line-height: 1.4;
    display: inline-block;
}

.sn-headline-link:hover {
    text-decoration: underline;
}

.sn-headline-meta {
    font-size: 0.72rem;
    opacity: 0.7;
    margin-top: 0.14rem;
}

.sn-headline-source {
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 500;
}

</style>

<div class="sn-card-active-headlines mb-3">
    <div class="sn-card-header sn-card-header-inline">
        <div class="sn-card-header-left">
            <h2 class="sn-card-title">
                Active Headlines
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
                            <?php echo htmlspecialchars($headline['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>

                        <?php if (!empty($headline['pub_human'])): ?>
                            <div class="sn-headline-meta">
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
