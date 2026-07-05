<?php

define('BASE_PATH', __DIR__);

require_once BASE_PATH . "/core/___modules.php";

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

function add_query_params(string $url, array $params): string
{
    $separator = str_contains($url, '?') ? '&' : '?';

    return $url . $separator . http_build_query($params);
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
    SELECT id
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
    $item['player_url'] = add_query_params($item['url'], [
        'context' => 'trail-player',
    ]);
}
unset($item);

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

    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/auth.css?v=<?= filemtime(BASE_PATH . '/assets/css/auth.css') ?>" rel="stylesheet" />
    <link href="/assets/css/account.css?v=<?= filemtime(BASE_PATH . '/assets/css/account.css') ?>" rel="stylesheet" />

    <style>
        .trail-player-preview img {
            border-radius: 1rem;
            overflow: hidden;
        }

        .text-muted-light {
            color: rgba(255, 255, 255, 0.68);
        }

        .trail-iframe-wrap {
            width: 100%;
            height: min(72vh, 760px);
            background: #111;
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .trail-iframe-wrap iframe {
            width: 100%;
            height: 100%;
            border: 0;
            background: #fff;
        }
    </style>

</head>

<body id="page-top" class="auth-page account-page">

    <!-- Top nav-->
    <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

    <main class="container py-5">

        <div class="row justify-content-center">
            <div class="col-xl-9">

                <div class="text-center mb-5">

                    <header class="mb-5 text-center">
                        <h1 class="h2 mb-2"><i class="fa-solid fa-route mr-1"></i> News Trail Player</h1>
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <p class="text-muted mb-0">
                                    Play back a personalized news journey built from reading history,
                                    saved headlines, searches, and shuffles.
                                </p>
                            </div>
                        </div>
                    </header>

                </div>

                <div class="trail-player-card bg-dark text-white rounded shadow-sm p-3">

                    <div class="trail-meta mb-3">
                        <div class="small text-uppercase text-muted-light">News Trail</div>
                        <h1 id="trailTitle" class="h4 mb-1">Loading trail...</h1>
                        <div id="trailItemMeta" class="small text-muted-light"></div>
                    </div>

                    <div class="trail-iframe-wrap mb-3">
                        <iframe
                            id="trailFrame"
                            src=""
                            title="News Trail article"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>
                    </div>

                    <div class="trail-controls d-flex justify-content-between align-items-center gap-2">
                        <button id="prevTrailItem" class="btn btn-outline-light btn-sm">
                            ← Previous
                        </button>

                        <a id="openOriginal" class="btn btn-success btn-sm" href="#" target="_blank" rel="noopener">
                            Open Original
                        </a>

                        <button id="nextTrailItem" class="btn btn-outline-light btn-sm">
                            Next →
                        </button>
                    </div>

                </div>

            </div>
        </div>

    </main>

    <!-- Footer-->
    <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>

    <!-- Modals-->
    <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

    <!-- Core JS (Bootstrap 4 requires jQuery first) -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" defer></script>

    <!-- Theme -->
    <script src="/assets/js/scripts.js" defer></script>

    <script>
        window.scrollNewsTrailItems = <?= json_encode($trailItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        window.scrollNewsTrailMeta = {
            base: <?= json_encode($base); ?>,
            trailUser: <?= json_encode($trailUser); ?>,
            trailDate: <?= json_encode($trailDate); ?>,
            itemCount: <?= count($trailItems); ?>
        };
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const trailItems = window.scrollNewsTrailItems || [];
            let currentIndex = 0;

            const frame = document.getElementById('trailFrame');
            const title = document.getElementById('trailTitle');
            const meta = document.getElementById('trailItemMeta');
            const openOriginal = document.getElementById('openOriginal');
            const prevBtn = document.getElementById('prevTrailItem');
            const nextBtn = document.getElementById('nextTrailItem');

            function renderTrailItem() {
                if (!trailItems.length) {
                    title.textContent = 'No trail items found';
                    meta.textContent = '';
                    frame.removeAttribute('src');
                    openOriginal.href = '#';
                    prevBtn.disabled = true;
                    nextBtn.disabled = true;
                    return;
                }

                const item = trailItems[currentIndex];

                title.textContent = item.title || 'Untitled article';
                meta.textContent = `${currentIndex + 1} of ${trailItems.length} · ${item.source || 'Unknown source'}${item.pub_date ? ' · ' + item.pub_date : ''}`;

                frame.src = item.player_url || item.url;
                openOriginal.href = item.url;

                prevBtn.disabled = currentIndex === 0;
                nextBtn.disabled = currentIndex === trailItems.length - 1;
            }

            prevBtn.addEventListener('click', function() {
                if (currentIndex > 0) {
                    currentIndex--;
                    renderTrailItem();
                }
            });

            nextBtn.addEventListener('click', function() {
                if (currentIndex < trailItems.length - 1) {
                    currentIndex++;
                    renderTrailItem();
                }
            });

            renderTrailItem();
        });
    </script>
</body>

</html>