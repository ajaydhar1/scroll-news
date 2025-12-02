<?php
// search.php — keyword search across rss_items (+ feeds, + articles)
//
// Assumes:
//   - tables: rss_items, feeds, articles
//   - rss_items.feed_id -> feeds.id
//   - articles has a URL column matching rss_items.link (adjust if needed)

require_once('___modules.php');

$pdo = _pdo_or_null();

$q        = trim($_GET['q'] ?? '');
$results  = [];
$errorMsg = null;

if ($q !== '' && $pdo) {
    try {
        $sql = "
            SELECT
                ri.id,
                ri.title,
                ri.link,
                ri.pub_date,
                ri.media_url,
                f.name AS feed_name,
                a.id AS article_id
            FROM rss_items ri
            JOIN feeds f
              ON f.id = ri.feed_id
            LEFT JOIN articles a
              ON a.url = ri.link  -- adjust if your schema differs
            WHERE
                ri.title ILIKE :q
                OR ri.description ILIKE :q
            ORDER BY
                ri.pub_date DESC NULLS LAST,
                ri.id DESC
            LIMIT 100
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':q' => '%' . $q . '%']);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {
        $errorMsg = 'There was a problem running your search.';
    }
}

function sn_format_pub_date(?string $raw): string {
    if (empty($raw)) return '';

    $ts = strtotime($raw);
    if ($ts === false) return '';

    if (function_exists('format_news_date')) {
        return format_news_date($ts, 'America/New_York');
    }

    return date('M j, Y • g:i A', $ts);
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Search recent headlines across Scroll News feeds and jump into articles or detailed analysis." />
        <meta name="author" content="Scroll News" />
        <title>Search – Scroll News</title>

        <!-- Favicon-->
        <link rel="icon" type="image/png" href="assets/img/play-green.png" />
        <link rel="canonical" href="https://scrollnews.io/search.php">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://scrollnews.io/search.php" />
        <meta property="og:title" content="Search headlines on Scroll News" />
        <meta property="og:description" content="Search recent U.S. news headlines across Scroll News feeds, then read or analyze stories in detail." />
        <meta property="og:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-search-1200x630.png" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://scrollnews.io/search.php" />
        <meta name="twitter:title" content="Search headlines on Scroll News" />
        <meta name="twitter:description" content="Search recent headlines and jump into Scroll News analysis or publisher stories." />
        <meta name="twitter:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-search-1200x630.png" />

        <!-- Font Awesome icons (free version)-->
        <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous"></script>
        <!-- Google fonts-->
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css" />
        <link href="https://fonts.googleapis.com/css?family=Droid+Serif:400,700,400italic,700italic" rel="stylesheet" type="text/css" />
        <link href="https://fonts.googleapis.com/css?family=Roboto+Serif:400,700,400italic,700italic" rel="stylesheet" type="text/css" />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700&family=Lato&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600&family=Inter&display=swap" rel="stylesheet">

        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/styles.css" rel="stylesheet" />
        <link href="css/custom.css" rel="stylesheet" />

        <style>
            section#services {
                background: #eee;
            }
            p {
                font-weight: 300;
            }
            .page-section h3.section-subheading {
                font-weight: 700;
            }
            a {
                color: var(--brand-color);
            }
            footer.footer {
                background: white;
            }
            footer .text-lg-right a {
                color: #00bfa6;
            }
            footer .text-lg-right a:hover {
                color: black;
            }


            a.btn.btn-outline-primary.btn-analyze:hover {
                background: #00bfa6;
                border: none;
            }
            .btn-outline-primary {
                color: black;
                border: none;
            }
            footer .btn {
                box-shadow: 0 0 0 2px #00bfa6 !important;
            }
            .btn {
                box-shadow: none !important;
            }
            .btn-gray-border {
                border: 2px solid #6c757d;
            }
        </style>
    </head>
    <body id="page-top" class="bg-dark">

        <!-- Loading overlay -->
        <div id="loadingOverlay" class="loading-overlay" aria-live="polite" aria-busy="true" hidden>
          <div class="loading-spinner" role="status" aria-label="Loading"></div>
        </div>

        <style>
          .loading-overlay{
            position:fixed; inset:0; display:flex; align-items:center; justify-content:center;
            background:rgba(255,255,255,0.82); z-index:2000; backdrop-filter:saturate(120%) blur(2px);
          }
          .loading-spinner{
            width:48px; height:48px; border:4px solid #e5e7eb; border-top-color:#0d6efd;
            border-radius:50%; animation:spin 1s linear infinite;
          }
          @keyframes spin{to{transform:rotate(360deg)}}
          @media (prefers-reduced-motion: reduce){ .loading-spinner{animation:none} }
        </style>

        <!-- topnav (same chrome as About) -->
        <footer class="footer py-4 bg-white sticky-top sn-top-nav">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-4 text-lg-left text-bolder">
                        <h5 class="mb-0">
                            <a href="index.php">
                                <img src="assets/img/play-green.png" alt="Logo" style="height: 24px; width: auto; vertical-align: middle; margin-right: 5px; margin-bottom: 5px;">
                                Scroll News
                            </a>
                        </h5>
                    </div>
                    <div class="col-lg-4 my-3 my-lg-0">
                        <a class="btn btn-black btn-social mx-2" title="History" href="scroll-history.php" data-loading><i class="fas fa-history"></i></a>
                        <a class="btn btn-green btn-social mx-2" title="Stumble through articles" href="newsroom.php" data-loading><i class="fas fa-play"></i></a>
                        <a class="btn btn-black btn-social mx-2" title="Control Room" href="control-room.html"><i class="fas fa-dashboard"></i></a>
                    </div>
                    <div class="col-lg-4 text-lg-right">
                        <a href="about.html" class="mr-3">About</a>
                        <a class="search-button" href="search.php" title="Search" aria-label="Search">🔍</a>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Main content section, reusing services section styling -->
        <section class="page-section" id="services" style="padding: 4rem 0;">
            <div class="container">
                <div class="row justify-content-center mb-4">
                    <div class="col-md-8 text-center">
                        <h2 class="section-heading text-uppercase">Search headlines</h2>
                        <h3 class="section-subheading" style="margin-bottom: 1.5rem;">
                            Find recent stories from Scroll News feeds, then read or analyze them.
                        </h3>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-8 mx-auto">
                        <form method="get" action="search.php" class="input-group">
                            <input
                                type="text"
                                name="q"
                                class="form-control"
                                placeholder="Search headlines..."
                                value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                                aria-label="Search headlines"
                            />
                            <button class="btn btn-green" type="submit" style="border-radius: 0 0.25rem 0.25rem 0;" data-loading>
                                <i class="fas fa-search"></i>&nbsp;Search
                            </button>
                        </form>
                        <div class="small text-muted mt-2">
                            Search across recent items from all feeds.
                        </div>
                    </div>
                </div>

                <?php if ($errorMsg): ?>
                    <div class="row">
                        <div class="col-md-8 mx-auto">
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($q === ''): ?>
                    <div class="row">
                        <div class="col-md-8 mx-auto">
                            <p class="text-muted">
                                Type a keyword above to search headlines from your feeds.
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-md-8 mx-auto">
                            <h2 class="h6 mb-3">
                                Results for
                                "<span class="fw-semibold"><?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?></span>"
                                <?php if (!empty($results)): ?>
                                    <span class="text-muted"> · <?php echo count($results); ?> found</span>
                                <?php endif; ?>
                            </h2>

                            <?php if (empty($results)): ?>
                                <p class="text-muted">
                                    No results matched your search. Try another keyword or a more general phrase.
                                </p>
                            <?php else: ?>
                                <?php foreach ($results as $row): ?>
                                    <?php
                                    $title    = $row['title'] ?? '';
                                    $feedName = $row['feed_name'] ?? '';
                                    $readUrl  = $row['link'] ?? '#';
                                    $pubHuman = sn_format_pub_date($row['pub_date'] ?? null);
                                    $hasNlp   = !empty($row['article_id']);

                                    // Build Analyze (newsroom) URL if NLP/article exists.
                                    $analyzeUrl = null;
                                    if ($hasNlp) {
                                        $ts = !empty($row['pub_date']) ? (strtotime($row['pub_date']) ?: null) : null;
                                        $analyzeUrl = 'newsroom.php?url=' . urlencode($readUrl) . '&category=' . urlencode($feedName) . '&pub_date=' . urlencode($ts);
                                    }

                                    // Derive domain from the readUrl
                                    $domain = '';
                                    $faviconUrl = null;
                                    if (!empty($readUrl)) {
                                        $host = parse_url($readUrl, PHP_URL_HOST);
                                        if ($host) {
                                            // Strip leading www.
                                            $domain = preg_replace('/^www\./i', '', $host);

                                            // Google favicon endpoint using the full URL (your working pattern)
                                            $faviconUrl = 'https://t0.gstatic.com/faviconV2'
                                                . '?client=SOCIAL&type=FAVICON'
                                                . '&fallback_opts=TYPE,SIZE,URL'
                                                . '&url=' . rawurlencode($readUrl)
                                                . '&size=64';
                                        }
                                    }
                                    ?>
                                    <div class="card mb-3 shadow-sm border-0 sn-search-card">
                                        <div class="card-body">
                                            <h5 class="card-title mb-1">
                                                <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                                            </h5>

                                            <div class="sn-search-meta small text-muted mb-3 d-flex align-items-center">
                                                <?php if ($faviconUrl): ?>
                                                    <img
                                                        src="<?php echo htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                        alt="<?php echo htmlspecialchars($domain ?: $feedName ?: 'Site', ENT_QUOTES, 'UTF-8'); ?> logo"
                                                        class="sn-favicon"
                                                    >
                                                <?php endif; ?>

                                                <div class="sn-meta-text">
                                                    <?php if ($pubHuman): ?>
                                                        <?php echo htmlspecialchars($pubHuman, ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php endif; ?>

                                                    <?php if ($feedName): ?>
                                                        <?php if ($pubHuman): ?> • <?php endif; ?>
                                                        <?php echo htmlspecialchars($feedName, ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php endif; ?>

                                                    <?php if ($domain): ?>
                                                        <?php if ($pubHuman || $feedName): ?> • <?php endif; ?>
                                                        <?php echo htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="btn-group btn-group-sm" role="group">
                                                <a
                                                    href="<?php echo htmlspecialchars($readUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="btn btn-outline-secondary"
                                                    target="_blank"
                                                    rel="noopener"
                                                >
                                                    Read story
                                                </a>

                                                <?php if ($hasNlp && $analyzeUrl): ?>
                                                    <a
                                                        href="<?php echo htmlspecialchars($analyzeUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                        class="btn btn-green btn-gray-border"
                                                        data-loading
                                                    >
                                                        Analyze
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Footer -->
        <div class="bg-dark" style="height: 338px;">
            <footer class="footer footer-bottom bg-white py-4">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-4 text-lg-left">Copyright © Scroll News 2025</div>
                        <div class="col-lg-4 my-3 my-lg-0">
                            <a class="btn btn-black btn-social mx-2" title="X profile" href="https://x.com/scrollnewsio" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
                            <a class="btn btn-black btn-social mx-2" title="History" href="scroll-history.php" data-loading><i class="fas fa-history"></i></a>
                            <a class="btn btn-green btn-social mx-2" title="Stumble through articles" href="newsroom.php" data-loading><i class="fas fa-play"></i></a>
                            <a class="btn btn-black btn-social mx-2" title="Control Room" href="control-room.html"><i class="fas fa-dashboard"></i></a>
                            <a class="btn btn-black btn-social mx-2" title="IG profile" href="https://www.instagram.com/scrollnewsio/" target="_blank"><i class="fa-brands fa-instagram"></i></a>
                        </div>
                        <div class="col-lg-4 text-lg-right font-weight-bold">
                            <a href="index.php">scroll news</a>
                            <br>
                            <a href="about.html" class="text-muted small mr-3">About</a>
                            <a href="terms.html" class="text-muted small mr-3">Terms</a>
                            <a href="privacy.html" class="text-muted small">Privacy</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

        <!-- Bootstrap core JS-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
        <!-- Third party plugin JS-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
        <!-- Contact form JS-->
        <script src="assets/mail/jqBootstrapValidation.js"></script>
        <script src="assets/mail/contact_me.js"></script>
        <!-- Core theme JS-->
        <script src="js/scripts.js"></script>

        <script>
            (function(){
              const overlay = document.getElementById('loadingOverlay');
              const show = () => overlay && (overlay.hidden = false);
              const hide = () => overlay && (overlay.hidden = true);

              window.addEventListener('pageshow', hide);

              document.addEventListener('click', function(e){
                const t = e.target.closest('[data-loading]');
                if (t) show();
              });

              document.addEventListener('click', function(e){
                const btn = e.target.closest('[data-loading-btn]');
                if (!btn) return;
                btn.dataset.originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span>&nbsp;Loading…';
                btn.classList.add('disabled'); btn.setAttribute('aria-busy','true');
              });

              const style = document.createElement('style');
              style.textContent = '.btn-spinner{display:inline-block;width:1em;height:1em;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:spin .6s linear infinite;vertical-align:-0.125em}';
              document.head.appendChild(style);
            })();
        </script>
    </body>
</html>
