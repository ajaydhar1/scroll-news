<?php

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/auth/includes/require_auth.php';
require_once BASE_PATH . '/auth/includes/auth_bootstrap.php';
require_once BASE_PATH . '/auth/includes/auth_db.php';

$pdo = auth_db();

if (!$currentUser) {
    header('Location: /auth/login.php');
    exit;
}

$userId = (int) $currentUser['id'];

$search = trim($_GET['q'] ?? '');

$params = [
    ':user_id' => $userId,
];

$searchSql = '';

if ($search !== '') {
    $searchSql = "
      AND (
          ss.query ILIKE :search
          OR EXISTS (
              SELECT 1
              FROM (
                  SELECT title, article_description
                  FROM shuffle_session_items
                  WHERE shuffle_session_id = ss.id
                  ORDER BY position ASC
                  LIMIT 10
              ) top_items
              WHERE top_items.title ILIKE :search
          )
      )
    ";

    $params[':search'] = '%' . $search . '%';
}

$stmt = $pdo->prepare("
    SELECT
        ss.id,
        ss.source_context,
        ss.shuffle_type,
        ss.query,
        ss.results_count,
        ss.filters_json,
        ss.snapshot_json,
        ss.created_at,
        (
            SELECT string_agg(si.title, '|||ORDER|||'
                   ORDER BY si.position)
            FROM (
                SELECT title, position
                FROM shuffle_session_items
                WHERE shuffle_session_id = ss.id
                ORDER BY position ASC
                LIMIT 3
            ) si
        ) AS preview_titles
    FROM shuffle_sessions ss
    WHERE ss.user_id = :user_id
      AND ss.deleted_at IS NULL
      $searchSql
    ORDER BY ss.created_at DESC
    LIMIT 50
");

$stmt->execute($params);

$shuffleSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSessions = count($shuffleSessions);

function shuffle_type_label(string $sourceContext, string $shuffleType): string
{
    if ($sourceContext === 'search_results') {
        return 'Search Shuffle';
    }

    if ($sourceContext === 'browse_news_modal') {
        return 'Browse News Shuffle';
    }

    return 'Shuffle';
}

function shuffle_view_url(array $row): string
{
    if (($row['source_context'] ?? '') === 'search_results') {
        return '/search.php?shuffle_session=' . urlencode($row['id']) . '&context=shuffle_history';
    }

    if (($row['source_context'] ?? '') === 'browse_news_modal') {
        return '/newsroom.php?browse_shuffle=' . urlencode($row['id']) . '&context=shuffle_history';
    }

    return '#';
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Revisit your AI-powered shuffle sessions from Search and Browse News on Scroll News." />
    <meta name="author" content="Scroll News" />
    <title>Shuffle History — Scroll News</title>

    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://scrollnews.ai/account/shuffle-history.php" />
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />

    <link href="/assets/css/styles.css?v=<?= filemtime(BASE_PATH . '/assets/css/styles.css') ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?= filemtime(BASE_PATH . '/assets/css/custom.css') ?>" rel="stylesheet" />
    <link href="/assets/css/auth.css?v=<?= filemtime(BASE_PATH . '/assets/css/auth.css') ?>" rel="stylesheet" />
    <link href="/assets/css/account.css?v=<?= filemtime(BASE_PATH . '/assets/css/account.css') ?>" rel="stylesheet" />

    <style>
        .shuffle-history-header {
            background:
                radial-gradient(circle at top left, rgba(32, 170, 89, 0.18), transparent 32%),
                radial-gradient(circle at bottom right, rgba(255, 255, 255, 0.06), transparent 28%),
                linear-gradient(135deg, #1d2125 0%, #2a2f35 55%, #343a40 100%);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 1rem;
            padding: 1.5rem;
            color: #f8f9fa;
        }

        .shuffle-history-header .text-muted {
            color: rgba(255, 255, 255, 0.72) !important;
        }

        .shuffle-history-header h1 {
            color: #ffffff;
        }

        .shuffle-history-icon {
            width: 48px;
            height: 48px;
            border-radius: 999px;
            background: rgba(32, 170, 89, 0.12);
            color: #198754;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .shuffle-history-card {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .shuffle-history-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1.25rem rgba(0, 0, 0, 0.08) !important;
        }

        .shuffle-empty-state {
            border: 1px dashed rgba(0, 0, 0, 0.15);
            border-radius: 1rem;
            background: #fbfcfc;
            padding: 2rem;
        }
    </style>
</head>

<body id="page-top" class="auth-page account-page">

    <div class="page">

        <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

        <div class="container py-4">
            <div class="row">
                <div class="col-lg-9 mx-auto">

                    <div class="shuffle-history-header mb-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start">

                            <div class="d-flex align-items-start">
                                <div class="shuffle-history-icon mr-3">
                                    <i class="fas fa-random"></i>
                                </div>

                                <div>
                                    <p class="text-muted small mb-1">Your discovery history</p>

                                    <h1 class="h3 mb-2">Shuffle History</h1>

                                    <p class="text-muted mb-0">
                                        Revisit AI-powered shuffle sessions from Search and Browse News.
                                    </p>

                                    <?php if ($search !== ''): ?>
                                        <div class="small text-muted mt-2">
                                            Search results for
                                            <strong>
                                                "<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                                            </strong>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mt-3 mt-md-0 text-md-right">
                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                    <i class="fa-solid fa-trash-can mr-1"></i>
                                    Clear History
                                </button>

                                <div class="text-muted small mt-2">
                                    <?= number_format($totalSessions) ?>
                                    shuffle session<?= $totalSessions === 1 ? '' : 's' ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <form method="get" class="mb-4">
                        <div class="input-group">
                            <input
                                type="search"
                                name="q"
                                class="form-control"
                                placeholder="Search shuffle headlines or descriptions..."
                                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="input-group-append">
                                <button class="btn btn-dark" type="submit" data-loading>
                                    <i class="fa-solid fa-search mr-1"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="/account/" class="text-muted">← Back to Account</a>
                        <div>
                            <a href="/search.php" class="btn btn-green btn-sm">
                                <i class="fa-solid fa-search mr-1"></i> Search
                            </a>
                            <a href="" class="btn btn-green btn-sm" data-toggle="modal" data-target="#browseNewsModal">
                                <i class="fa-solid fa-newspaper mr-1"></i> Browse
                            </a>
                        </div>
                    </div>

                    <?php if (empty($shuffleSessions)): ?>

                        <div class="shuffle-empty-state text-center p-4 border rounded bg-light">
                            <div class="mb-3" style="font-size: 2rem;">🔀</div>
                            <h2 class="h5 mb-2">No shuffle history yet</h2>
                            <p class="text-muted mb-4">
                                Use AI-powered Shuffle on Search or Browse News, and your sessions will appear here.
                            </p>
                            <a href="/search.php" class="btn btn-green btn-sm">
                                Search News
                            </a>
                        </div>

                    <?php else: ?>

                        <div class="list-group shadow-sm">
                            <?php foreach ($shuffleSessions as $row): ?>
                                <?php
                                $label = shuffle_type_label($row['source_context'], $row['shuffle_type']);
                                $viewUrl = shuffle_view_url($row);

                                $previewTitles = [];
                                if (!empty($row['preview_titles'])) {
                                    $previewTitles = explode('|||ORDER|||', $row['preview_titles']);
                                }

                                $createdAt = new DateTime($row['created_at'], new DateTimeZone('UTC'));
                                $createdAt->setTimezone(new DateTimeZone('America/New_York'));

                                $createdAtLabel = $createdAt->format('M j, Y g:i A');
                                ?>

                                <div class="list-group-item p-3 shuffle-history-card">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <div class="mb-2">
                                                <?php

                                                $badgeClass = 'badge-info';

                                                if ($label === 'Search Shuffle') {
                                                    $badgeClass = 'badge-primary';
                                                } elseif ($label === 'Browse News Shuffle') {
                                                    $badgeClass = 'badge-success';
                                                }

                                                ?>

                                                <span class="badge <?= $badgeClass ?>">
                                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </div>

                                            <h2 class="h6 mb-1">
                                                <?php if (!empty($row['query'])): ?>
                                                    Query: “<?= htmlspecialchars($row['query'], ENT_QUOTES, 'UTF-8') ?>”
                                                <?php else: ?>
                                                    Recent articles shuffle
                                                <?php endif; ?>
                                            </h2>

                                            <p class="text-muted small mb-2">
                                                <?= (int) $row['results_count'] ?> articles ·
                                                <?= htmlspecialchars($createdAtLabel, ENT_QUOTES, 'UTF-8') ?>
                                            </p>

                                            <?php if (!empty($previewTitles)): ?>
                                                <ol class="text-muted small mb-0 ps-3">
                                                    <?php foreach ($previewTitles as $title): ?>
                                                        <li><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></li>
                                                    <?php endforeach; ?>
                                                </ol>
                                            <?php endif; ?>
                                        </div>

                                        <div class="text-nowrap">

                                            <?php if ($row['shuffle_type'] === 'browse_shuffle'): ?>

                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary view-shuffle-btn"
                                                    data-shuffle-session-id="<?= htmlspecialchars(urlencode($row['id']), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-shuffle-type="<?= htmlspecialchars($row['shuffle_type'], ENT_QUOTES, 'UTF-8') ?>">

                                                    View shuffle

                                                </button>

                                            <?php else: ?>

                                                <a href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>"
                                                    class="btn btn-sm btn-outline-primary"
                                                    data-loading>

                                                    View shuffle

                                                </a>

                                            <?php endif; ?>

                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="/" class="text-muted" data-loading>← Back to Scroll News</a>
                        <a href="/auth/logout.php" class="btn btn-outline-secondary btn-sm">Sign Out</a>
                    </div>

                </div>
            </div>
        </div>

        <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>

    </div>

    <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>

    <script>
        // View shuffle buttons
        $(document).on("click", ".view-shuffle-btn", function(e) {

            e.preventDefault();

            const shuffleSessionId = $(this).data("shuffleSessionId");
            const shuffleType = $(this).data("shuffleType");

            if (!shuffleSessionId || !shuffleType) {
                return;
            }

            // Browse News shuffle
            if (shuffleType === "browse_shuffle") {

                // Clear existing modal content
                $("#rssArticles").empty();

                // Optional loading state
                $("#rssArticles").html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-success" role="status"></div>
                </div>
                `);

                // Open existing Browse News modal
                $("#browseNewsModal").modal("show");

                // Fetch + render saved shuffle
                loadBrowseNewsShuffle(shuffleSessionId);

                return;
            }

            // Search shuffle
            if (shuffleType === "search_shuffle") {

                window.location.href =
                    "/search.php?shuffle_session_id=" +
                    encodeURIComponent(shuffleSessionId);

                return;
            }

        });

        function loadBrowseNewsShuffle(shuffleSessionId) {

            $.ajax({
                url: "/account/api/get-browse-shuffle-history.php",
                method: "GET",
                data: {
                    shuffle_session_id: shuffleSessionId
                },
                dataType: "json",

                success: function(response) {

                    const articles = response.items || [];

                    const container = $("#rssArticles");

                    container.empty();

                    container.append(`
                        <div class="col-12">
                            <div class="alert alert-light border small d-flex align-items-center justify-content-between mb-4">

                                <div>
                                    <strong>Viewing saved Browse News shuffle</strong>
                                    <span class="text-muted">
                                        • Replaying a previously shuffled article session
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    id="returnToLiveBrowseNewsBtn"
                                    onclick="fetchRSSArticles($('#categorySelect').val(), 'Politics');">

                                    Back to Live Feed

                                </button>

                            </div>
                        </div>
                    `);

                    if (articles.length === 0) {

                        container.append(`
                    <div class="col-12">
                        <p class="text-muted mb-0">
                            No articles found for this shuffle.
                        </p>
                    </div>
                `);

                        return;
                    }

                    articles.forEach((article) => {

                        const filterOutPublisher = pubsToFilterOut.some((substring) =>
                            (article.link || "").includes(substring)
                        );

                        if (!filterOutPublisher) {

                            const encodedPub = encodeURIComponent(article.publisher || "");

                            const faviconUrl =
                                "https://t0.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url=http://" +
                                encodedPub +
                                "&size=64";

                            const articleId = Number(article.articleId || 0);

                            const card = `
                        <div class="col-md-4 mb-4 browse-article-card"
                             data-article-id="${articleId}"
                             data-article-url="${article.link || ""}"
                             data-article-title="${article.title || ""}"
                             data-image-url="${article.image || ""}"
                             data-publisher="${article.publisher || ""}"
                             data-pub-date="${article.pubDate || ""}"
                             data-description="${(article.description || "").replace(/"/g, '&quot;')}">

                            <div class="card h-100">

                                <img src="${article.image || "/assets/img/news-placeholder.jpg"}"
                                     class="card-img-top news-modal"
                                     alt=""
                                     onerror="this.src='/assets/img/news-placeholder.jpg';">

                                <div class="card-body d-flex flex-column">

                                    <h4 class="card-title mb-2">
                                        ${article.title || ""}
                                    </h4>

                                    <p class="card-text text-muted mb-1">

                                        <img src="${faviconUrl}"
                                             alt="${encodedPub} logo"
                                             class="sn-favicon">

                                        <small>
                                            <a target="_blank"
                                               href="https://${article.publisher || ""}">

                                                ${article.publisher || ""}

                                            </a>

                                            ${article.pubDate
                                                ? " • " + timeElapsedString(article.pubDate)
                                                : ""}

                                        </small>

                                    </p>

                                    <p class="card-text">
                                        ${article.description || ""}
                                    </p>

                                    <div class="row g-2 mt-auto">

                                        <div class="col-6 d-grid browse-card-btn-col">

                                            <a href="${article.link}"
                                               class="btn btn-secondary mt-auto w-100"
                                               target="_blank">

                                                Read Story

                                            </a>

                                        </div>

                                        <div class="col-6 d-grid browse-card-btn-col">

                                            <a href="/newsroom.php?url=${encodeURIComponent(article.link)}&pub_date=${article.pubDateForLink || ""}&db=1"
                                               class="btn btn-green mt-auto w-100">

                                                Analyze

                                            </a>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>
                    `;

                            container.append(card);
                        }
                    });
                },

                error: function(xhr, textStatus, errorThrown) {

                    console.group("loadBrowseNewsShuffle ERROR");

                    console.log("textStatus:", textStatus);
                    console.log("errorThrown:", errorThrown);
                    console.log("HTTP status:", xhr.status);
                    console.log("Response text:", xhr.responseText);

                    console.groupEnd();

                    $("#rssArticles").html(`
                        <div class="col-12">
                            <p class="text-danger mb-0">
                                Failed to load shuffle history.
                            </p>
                        </div>
                    `);
                }
            });
        }
    </script>

</body>

</html>