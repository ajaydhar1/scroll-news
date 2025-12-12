<?php
// ___active_headlines.php

const ACTIVE_HEADLINES_LIMIT = 6;
const ACTIVE_HEADLINES_FEEDS = [
    'https://feeds.nbcnews.com/nbcnews/public/news',
];

function scrollnews_fetch_active_headlines(): array
{
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
            continue;
        }

        $xml = @simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$xml || !isset($xml->channel->item)) {
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

    return $items;
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

<div class="sn-card sn-card-active-headlines">
    <div class="sn-card-header sn-card-header-inline">
        <div class="sn-card-header-left">
            <h2 class="sn-card-title">
                Active Headlines
            </h2>
            <span class="sn-card-subtitle">Live feed from NBC News</span>
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
