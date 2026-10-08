<?php

define('BASE_PATH', __DIR__);
$theme_experiment_enabled = true;

require_once BASE_PATH . "/core/___modules.php";
require_once BASE_PATH . '/core/community_trail_privacy.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function normalize_trail_url(string $url): string
{
    $url = trim($url);

    if ($url === '') {
        return '';
    }

    $parts = parse_url($url);

    if (!$parts || empty($parts['host'])) {
        return strtolower($url);
    }

    $scheme = strtolower($parts['scheme'] ?? 'https');
    $host = strtolower($parts['host']);
    $path = rtrim($parts['path'] ?? '/', '/');

    // Keep query only if you need it. For news links, removing tracking is usually better.
    $query = '';

    return $scheme . '://' . $host . $path . $query;
}

$pdo = _pdo_or_null();

$base = $_GET['base'] ?? 'personal';
$trailUser = $_GET['trail_user'] ?? '';
$trailDate = $_GET['trail_date'] ?? date('Y-m-d');

$allowedBases = ['personal', 'editors', 'community'];

if (!in_array($base, $allowedBases, true)) {
    http_response_code(400);
    exit('Invalid trail base.');
}

if (!preg_match('/^u_[a-zA-Z0-9]+$/', $trailUser)) {
    http_response_code(400);
    exit('Invalid trail user.');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $trailDate)) {
    http_response_code(400);
    exit('Invalid trail date.');
}

$startDate = $trailDate . ' 00:00:00';
$endDate = date('Y-m-d H:i:s', strtotime($trailDate . ' +1 day'));

// Get user id from trail id
$userStmt = $pdo->prepare("
    SELECT id, email, display_name
    FROM users
    WHERE public_trail_key = :trail_user
      AND deleted_at IS NULL
    LIMIT 1
");

$userStmt->execute([
    ':trail_user' => $trailUser,
]);

$trailOwner = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$trailOwner) {
    http_response_code(404);
    exit('Trail user not found.');
}

$trailUserId = (int) $trailOwner['id'];
$sessionUserId = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])
    ? (int) $_SESSION['user_id']
    : null;

if (!sn_trail_playback_authorized($pdo, $base, $trailUserId, (string) $trailOwner['email'], $sessionUserId)) {
    http_response_code(404);
    exit('Trail not found.');
}

if ($base === 'community' && !sn_community_trail_date_is_recent($trailDate)) {
    http_response_code(404);
    exit('Trail not found.');
}

$communityReadDateFilter = $base === 'community' ? "AND viewed_at >= NOW() - INTERVAL '2 months'" : '';
$communitySavedDateFilter = $base === 'community' ? "AND saved_at >= NOW() - INTERVAL '2 months'" : '';
$communitySearchDateFilter = $base === 'community' ? "AND created_at >= NOW() - INTERVAL '2 months'" : '';
$communityShuffleDateFilter = $base === 'community' ? "AND ss.created_at >= NOW() - INTERVAL '2 months'" : '';

// get trail links
// get trail links
$stmt = $pdo->prepare("
    WITH trail_items_raw AS (
        SELECT
            url,
            title,
            source,
            image,
            pub_date,
            viewed_at AS activity_at,
            'reading' AS activity_type
        FROM user_reading_history
        WHERE user_id = :user_id
          AND deleted_at IS NULL
          AND url IS NOT NULL
          AND url <> ''
          $communityReadDateFilter
          AND (viewed_at - INTERVAL '4 hours')::date = :trail_date

        UNION ALL

        SELECT
            headline_url AS url,
            headline_title AS title,
            source_slug AS source,
            NULL AS image,
            pub_date,
            saved_at AS activity_at,
            'saved' AS activity_type
        FROM user_saved_headlines
        WHERE user_id = :user_id
          AND deleted_at IS NULL
          AND headline_url IS NOT NULL
          AND headline_url <> ''
          $communitySavedDateFilter
          AND (saved_at - INTERVAL '4 hours')::date = :trail_date

        UNION ALL

        SELECT
            '/search.php?q=' || replace(query, ' ', '+') ||
            '&range=' || COALESCE(range, 'all') ||
            '&mode=' || COALESCE(mode, 'classic') ||
            '&deep_dive=' || COALESCE(params_json->>'deep_dive', '') ||
            '&high_signal=' || COALESCE(params_json->>'high_signal', '') AS url,
            'Search: ' || query AS title,
            'Search' AS source,
            NULL AS image,
            NULL AS pub_date,
            created_at AS activity_at,
            'search' AS activity_type
        FROM user_search_history
            WHERE user_id = :user_id
            AND deleted_at IS NULL
            AND shuffle_session_uuid IS NULL
            AND query IS NOT NULL
            AND query <> ''
            $communitySearchDateFilter
            AND (created_at - INTERVAL '4 hours')::date = :trail_date

        UNION ALL

        SELECT
            CASE
                WHEN ss.source_context = 'search_results'
                    THEN '/search.php?shuffle_session=' || ss.id::text
                WHEN ss.source_context = 'browse_news_modal'
                    THEN '/browse-news.php?shuffle_session_id=' || ss.id::text
                ELSE NULL
            END AS url,
            CASE
                WHEN ss.source_context = 'search_results'
                    THEN 'Search Shuffle' || COALESCE(': ' || ush.query, '')
                WHEN ss.source_context = 'browse_news_modal'
                    THEN 'Browse News Shuffle'
                ELSE 'News Shuffle'
            END AS title,
            'Shuffle' AS source,
            NULL AS image,
            NULL AS pub_date,
            ss.created_at AS activity_at,
            'shuffle' AS activity_type
        FROM shuffle_sessions ss
        LEFT JOIN user_search_history ush
          ON ush.shuffle_session_uuid = ss.id
         AND ush.user_id = ss.user_id
         AND ush.deleted_at IS NULL
        WHERE ss.user_id = :user_id
          AND ss.deleted_at IS NULL
          AND ss.source_context IN ('search_results', 'browse_news_modal')
          $communityShuffleDateFilter
          AND (ss.created_at - INTERVAL '4 hours')::date = :trail_date
    ),

    trail_items AS (
        SELECT DISTINCT ON (LOWER(TRIM(url)))
            *
        FROM trail_items_raw
        WHERE url IS NOT NULL
          AND url <> ''
        ORDER BY LOWER(TRIM(url)), activity_at DESC
    )

    SELECT *
    FROM trail_items
    ORDER BY activity_at ASC;
");

$stmt->execute([
    ':user_id' => $trailUserId,
    ':trail_date' => $trailDate,
]);

$trailItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($base === 'community' && count($trailItems) < 3) {
    http_response_code(404);
    exit('Trail not found.');
}

$seenTrailUrls = [];
$dedupedTrailItems = [];

foreach ($trailItems as $item) {
    $url = trim($item['url'] ?? '');

    if ($url === '') {
        continue;
    }

    $key = normalize_trail_url($url);

    if ($key === '' || isset($seenTrailUrls[$key])) {
        continue;
    }

    $seenTrailUrls[$key] = true;
    $dedupedTrailItems[] = $item;
}

$trailItems = $dedupedTrailItems;

foreach ($trailItems as $index => &$item) {
    if (in_array($item['activity_type'] ?? '', ['reading', 'saved'], true)) {
        $publisherUrl = trim((string) $item['url']);
        $legacyParams = [];
        $storedUrlParts = parse_url($publisherUrl);

        if (is_array($storedUrlParts) && preg_match('~(?:^|/)newsroom\.php$~', $storedUrlParts['path'] ?? '')) {
            parse_str($storedUrlParts['query'] ?? '', $legacyParams);
            $legacyPublisherUrl = trim((string) ($legacyParams['url'] ?? ''));

            if (preg_match('~^https?://~i', $legacyPublisherUrl)) {
                $publisherUrl = $legacyPublisherUrl;
            }
        }

        if (preg_match('~^https?://~i', $publisherUrl)) {
            $playerParams = [
                'url' => $publisherUrl,
                'context' => 'trail-player',
            ];

            foreach (['category', 'pub_date', 'db'] as $parameter) {
                if (!empty($legacyParams[$parameter])) {
                    $playerParams[$parameter] = $legacyParams[$parameter];
                }
            }

            if (empty($playerParams['pub_date']) && !empty($item['pub_date'])) {
                $playerParams['pub_date'] = $item['pub_date'];
            }

            $item['publisher_url'] = $publisherUrl;
            $item['player_url'] = '/newsroom.php?' . http_build_query($playerParams);
        } else {
            $item['publisher_url'] = $item['url'];
        }
    }
}
unset($item);

$trailCategoryLabel = ['personal' => 'Personal Trail', 'editors' => 'Editor Trail', 'community' => 'Community Trail'][$base];
$trailOwnerLabel = sn_trail_first_name($trailOwner['display_name'] ?? '') . '’s Trail';
$trailDateLabel = date('F j, Y', strtotime($trailDate));

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <meta
        name="description"
        content="Preview the upcoming News Trail Player on Scroll News — a focused way to move through grouped news journeys built from reading, searches, saved headlines, and shuffles." />

    <meta name="author" content="Scroll News" />

    <title>News Trail Player – Scroll News</title>

    <meta name="robots" content="index,follow">

    <!-- Favicon-->
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <link
        rel="canonical"
        href="https://scrollnews.ai/news-trail-player.php" />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />

    <meta
        property="og:url"
        content="https://scrollnews.ai/news-trail-player.php" />

    <meta
        property="og:title"
        content="News Trail Player on Scroll News" />

    <meta
        property="og:description"
        content="A focused way to move through grouped news journeys built from reading, searches, saved headlines, and shuffles." />

    <meta
        property="og:image"
        content="https://scrollnews.ai/assets/img/og/og-scrollnews-news-trails-1200x630.png" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />

    <meta
        name="twitter:url"
        content="https://scrollnews.ai/news-trail-player.php" />

    <meta
        name="twitter:title"
        content="News Trail Player on Scroll News" />

    <meta
        name="twitter:description"
        content="Move through grouped news journeys built from reading history, saved headlines, searches, and shuffles." />

    <meta
        name="twitter:image"
        content="https://scrollnews.ai/assets/img/og/og-scrollnews-news-trails-1200x630.png" />

    <!-- jQuery min-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <!-- Font Awesome icons (free version)-->
    <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous"></script>

    <!-- Google fonts-->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />
    <!-- Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
    <link id="dark-typography-theme" href="/assets/css/dark-typography.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/dark-typography.css'); ?>" rel="stylesheet" />

    <script src="/assets/js/dark-theme.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/dark-theme.js'); ?>"></script>

    <link href="/assets/css/auth.css?v=<?= filemtime(BASE_PATH . '/assets/css/auth.css') ?>" rel="stylesheet" />
    <link href="/assets/css/account.css?v=<?= filemtime(BASE_PATH . '/assets/css/account.css') ?>" rel="stylesheet" />
    <link href="/assets/css/pages/news-trail-player.css?v=<?= filemtime(BASE_PATH . '/assets/css/pages/news-trail-player.css') ?>" rel="stylesheet" />

</head>

<body id="page-top" class="auth-page account-page trail-player-page">

    <!-- Top nav-->
    <?php require_once BASE_PATH . '/views/partials/___topnav_product.php'; ?>

    <main class="trail-player-main">
        <section class="trail-player" aria-label="News trail playback">
            <header class="trail-player-header">
                <div class="trail-context">
                    <div class="trail-context-primary">
                        <span class="trail-context-mark" aria-hidden="true">↗</span>
                        <div>
                            <div class="trail-context-kicker"><?= htmlspecialchars($trailCategoryLabel, ENT_QUOTES, 'UTF-8') ?></div>
                            <h1 class="trail-context-owner"><?= htmlspecialchars($trailOwnerLabel, ENT_QUOTES, 'UTF-8') ?></h1>
                        </div>
                    </div>
                    <time class="trail-context-date" datetime="<?= htmlspecialchars($trailDate, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($trailDateLabel, ENT_QUOTES, 'UTF-8') ?></time>
                </div>

                <div class="trail-item-heading">
                    <div class="trail-item-type" id="trailActivityLabel">Trail item</div>
                    <h2 id="trailTitle">Loading trail...</h2>
                    <div id="trailItemMeta" class="trail-item-meta"></div>
                    <a id="openOriginal" class="trail-primary-action" href="#" target="_blank" rel="noopener" hidden></a>
                </div>

                <div class="trail-progress" aria-label="Trail progress">
                    <div class="trail-progress-label" id="trailPosition">Item 0 of <?= count($trailItems) ?></div>
                    <div class="trail-progress-track" id="trailProgress" role="progressbar" aria-label="Current item in trail" aria-valuemin="0" aria-valuemax="<?= count($trailItems) ?>" aria-valuenow="0">
                        <span id="trailProgressFill"></span>
                    </div>
                </div>
            </header>

            <div class="trail-iframe-wrap" id="trailFrameWrap">
                <iframe
                    id="trailFrame"
                    src=""
                    title="Scroll News article analysis"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>
            </div>

            <section class="trail-activity-panel" id="trailActivityPanel" aria-live="polite" hidden>
                <div class="trail-activity-symbol" id="trailActivitySymbol" aria-hidden="true"></div>
                <div class="trail-activity-copy">
                    <div class="trail-activity-eyebrow" id="trailEventLabel"></div>
                    <h3 id="trailEventTitle"></h3>
                    <p id="trailEventSummary"></p>
                    <a id="trailEventAction" class="trail-primary-action" href="#" target="_blank" rel="noopener"></a>
                </div>
            </section>

            <nav class="trail-controls" aria-label="Trail item navigation">
                <button id="prevTrailItem" class="trail-nav-button" type="button" aria-label="Previous trail item">
                    <span aria-hidden="true">←</span><span>Previous</span>
                </button>
                <span class="trail-controls-position" id="trailControlsPosition">0 / <?= count($trailItems) ?></span>
                <button id="nextTrailItem" class="trail-nav-button trail-nav-next" type="button" aria-label="Next trail item">
                    <span>Next</span><span aria-hidden="true">→</span>
                </button>
            </nav>
        </section>
    </main>

    <!-- Footer-->
    <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>

    <!-- Modals-->
    <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

    <!-- Core JS (Bootstrap 4 requires jQuery first) -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" defer></script>

    <!-- Theme -->
    <script src="/assets/js/scripts.js" defer></script>

    <script type="application/json" id="trailItemsData"><?= json_encode($trailItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
    <script src="/assets/js/pages/news-trail-player.js?v=<?= filemtime(BASE_PATH . '/assets/js/pages/news-trail-player.js') ?>" defer></script>
</body>

</html>