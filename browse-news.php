<?php
define('BASE_PATH', __DIR__);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <!-- Basics -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="description" content="Replay saved Browse News sessions and review previously shuffled article collections." />
    <meta name="author" content="Scroll News" />

    <title>Browse News Replay | Scroll News</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <!-- Open Graph -->
    <meta property="og:title" content="Browse News Replay | Scroll News" />
    <meta property="og:description" content="Replay saved Browse News sessions and review previously shuffled article collections." />
    <meta property="og:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Browse News Replay | Scroll News" />
    <meta name="twitter:description" content="Replay saved Browse News sessions and review previously shuffled article collections." />
    <meta name="twitter:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-home-1200x630.png" />
    
    <!-- jQuery min-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <!-- Icons -->
    <script
        src="https://use.fontawesome.com/releases/v6.7.2/js/all.js"
        crossorigin="anonymous"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />

    <!-- Site CSS -->
    <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />

    <!-- Page-specific styles -->
    <style>
        body#page-top {
            background: #fff;
        }
    </style>
</head>

<body id="page-top">

    <!-- Content area-->
    <div class="container my-5">
        <div id="rssArticlesGrid" class="row"></div>
    </div>

    <!-- Bootstrap core JS-->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>

    <script>
        window.isLoggedIn = <?php echo !empty($currentUser) ? 'true' : 'false'; ?>;
    </script>

    <script src="/assets/js/newsroom/utils.js"></script>
    <script src="/assets/js/newsroom/modules.js"></script>

    <script>
        pubsToFilterOut = <?php echo json_encode($filter_out ?? []); ?>;
    </script>

    <script>
        $.ajax({
            url: "/account/api/get-browse-shuffle-history.php",
            method: "GET",
            data: {
                shuffle_session_id: "<?= $_GET['shuffle_session_id'] ?>"
            },
            dataType: "json",

            success: function(response) {

                const articles = response.items || [];

                const container = $("#rssArticlesGrid");

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

                $("#rssArticlesGrid").html(`
                    <div class="col-12">
                        <p class="text-danger mb-0">
                            Failed to load shuffle history.
                        </p>
                    </div>
                `);
            }
        });
    </script>

</body>

</html>