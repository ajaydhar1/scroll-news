<?php
// search.php — keyword search across rss_items (+ feeds, + articles)
//
// Assumes:
//   - tables: rss_items, feeds, articles
//   - rss_items.feed_id -> feeds.id
//   - articles has a URL column matching rss_items.link (adjust if needed)

require_once 'config_interest.php';
require_once "___modules.php"; // adjust if needed

$pdo        = _pdo_or_null();
$results    = [];
$errorMsg   = null;
$hasFilters = false;

// Read query params
$rawMode         = $_GET['mode']       ?? 'classic';
$mode            = ($rawMode === 'nlp') ? 'nlp' : 'classic';  // sanitize
$q               = trim($_GET['q']     ?? '');
$highSignalOnly  = !empty($_GET['high_signal']);             // <-- NEW
$emotion         = $_GET['emotion']    ?? null;              // e.g. 'Sad', 'Love', 'Wow'
$sentiment       = $_GET['sentiment']  ?? null;              // e.g. 'positive', 'negative'
$range           = $_GET['range']      ?? 'all';             // '24h', 'older', 'all'

// Decide if *any* filters are active (even without q)
$hasFilters =
    ($q !== '') ||
    !empty($emotion) ||
    !empty($sentiment) ||
    ($range !== 'all') ||
    $highSignalOnly;                                         // <-- NEW

if (!$pdo) {
    $errorMsg = "Database connection not available.";
} else {
    try {
        if ($hasFilters) {
            // shared options
            $options = [
                'emotion'      => $emotion,
                'sentiment'    => $sentiment,
                'range'        => $range,
                'high_signal'  => $highSignalOnly,           // <-- NEW
            ];

            if ($mode === 'nlp') {
                // NLP search on articles table
                $results = search_nlp($pdo, $q, $options);
            } else {
                // Classic search on rss_items + feeds + articles
                $results = search_classic($pdo, $q, $options);
            }
        } else {
            $results = [];
        }
    } catch (Throwable $e) {
        $errorMsg = 'There was a problem running your search.';
        $results  = [];
        // (Optional) error_log($e->getMessage());
    }
}

// From here down, render your HTML:
// - use $q to populate the search box
// - use $mode to highlight classic vs NLP toggle
// - optionally show $emotion/$sentiment/$range chips
// - loop over $results to show cards
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



            .sn-hashtag-chip {
              display: inline-block;
              font-size: 0.75rem;
              padding: 2px 6px;
              border-radius: 999px;
              background: #f3f4f6;
              color: #374151;
              margin-right: 4px;
              margin-bottom: 2px;
            }

            .sn-sentiment {
              font-size: 0.78rem;
              color: #4b5563;
            }
            .sn-sentiment-label {
              font-weight: 500;
            }

            .sn-emotions {
              margin-top: 0.25rem;
            }

            .sn-emotion-bar {
              display: flex;
              align-items: center;
              gap: 6px;
              font-size: 0.75rem;
              margin-top: 2px;
            }

            .sn-emotion-label {
              min-width: 48px;
              color: #4b5563;
            }

            .sn-emotion-bar-track {
              flex: 1;
              height: 6px;
              border-radius: 999px;
              background: #e5e7eb;
              overflow: hidden;
            }

            .sn-emotion-bar-fill {
              height: 100%;
              border-radius: 999px;
              background: #10b981; /* if you want, you can later vary this by label */
            }

            .sn-emotion-value {
              color: #6b7280;
              min-width: 32px;
              text-align: right;
            }



            .sn-loading-overlay {
                position: fixed;
                inset: 0;
                background: rgba(10, 10, 15, 0.35);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 2000; /* above navbar/content */
            }

            .sn-loading-overlay.active {
                display: flex;
            }

            .sn-loading-spinner .spinner-border {
                width: 3rem;
                height: 3rem;
            }

        </style>
    </head>
    <body id="page-top" class="bg-dark">

        <!-- Loading overlay -->
        <div id="loadingOverlay" class="loading-overlay" aria-live="polite" aria-busy="true" hidden>
          <div class="loading-spinner" role="status" aria-label="Loading"></div>
        </div>

        <div id="sn-search-loading" class="sn-loading-overlay" aria-hidden="true">
            <div class="sn-loading-spinner">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading…</span>
                </div>
            </div>
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
                    <div class="col-lg-4 d-flex text-lg-left text-bolder">
                        <h5 class="mb-2 mb-sm-0">
                            <a href="index.php" data-loading>
                                <img src="assets/img/play-green.png" alt="Logo play button" style="height: 24px; width: auto; vertical-align: middle; margin-right: 5px; margin-bottom: 5px;">
                                Scroll News
                            </a>
                        </h5>
                    </div>
                    <div class="col-lg-4 my-3 my-lg-0">
                        <a class="btn btn-black btn-social mx-2" title="History" href="scroll-history.php" data-loading><i class="fas fa-history"></i></a>
                        <a class="btn btn-green btn-social mx-2" title="Stumble through articles" href="newsroom.php" onclick="" data-loading><i class="fas fa-play"></i></a>
                        <a class="btn btn-black btn-social mx-2" title="Control Room" href="control-room.html"><i class="fas fa-dashboard"></i></a>
                    </div>
                    <div class="col-lg-4 d-flex text-lg-right" style="">
                        <div class="ml-auto">
                            <a href="about.html" class="mr-3">About</a>
                            <a class="search-button" href="search.php" title="Search" aria-label="Search">🔍</a>
                        </div>
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
                        <form id="sn-search-form" method="get" action="search.php" class="mb-4">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-6">
                                    <input
                                        type="text"
                                        name="q"
                                        class="form-control"
                                        placeholder="Search headlines…"
                                        value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                </div>

                                <div class="col-md-6 d-flex flex-wrap gap-2 justify-content-md-end mt-2 mt-md-0">

                                    <!-- Mode pills (do NOT have name="mode"; they update the hidden input) -->
                                    <div class="btn-group btn-group-sm me-2" role="group" aria-label="Search mode">
                                        <button
                                            type="button"
                                            class="btn <?php echo ($mode === 'classic') ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                                            onclick="
                                                document.getElementById('mode-input').value='classic';
                                                this.form.submit();
                                            "
                                        >
                                            Keyword
                                        </button>
                                        <button
                                            type="button"
                                            class="btn <?php echo ($mode === 'nlp') ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                                            onclick="
                                                document.getElementById('mode-input').value='nlp';
                                                this.form.submit();
                                            "
                                        >
                                            Smart (NLP)
                                        </button>
                                    </div>

                                    <!-- Range selector (both modes) -->
                                    <select
                                        name="range"
                                        class="form-select form-select-sm w-auto me-2"
                                        onchange="this.form.submit()"
                                    >
                                        <option value="all"   <?php if ($range === 'all')   echo 'selected'; ?>>All time</option>
                                        <option value="24h"   <?php if ($range === '24h')   echo 'selected'; ?>>Last 24 hours</option>
                                        <option value="older" <?php if ($range === 'older') echo 'selected'; ?>>Older than 24 hours</option>
                                    </select>

                                    <?php if ($mode === 'nlp'): ?>
                                        <!-- Sentiment filter (NLP mode only) -->
                                        <select
                                            name="sentiment"
                                            class="form-select form-select-sm w-auto me-2"
                                            onchange="this.form.submit()"
                                        >
                                            <option value="" <?php if (empty($sentiment)) echo 'selected'; ?>>Any sentiment</option>
                                            <option value="positive" <?php if ($sentiment === 'positive') echo 'selected'; ?>>Positive</option>
                                            <option value="neutral"  <?php if ($sentiment === 'neutral')  echo 'selected'; ?>>Neutral</option>
                                            <option value="negative" <?php if ($sentiment === 'negative') echo 'selected'; ?>>Negative</option>
                                        </select>

                                        <!-- Emotion filter (NLP mode only) -->
                                        <select
                                            name="emotion"
                                            class="form-select form-select-sm w-auto me-2"
                                            onchange="this.form.submit()"
                                        >
                                            <option value="" <?php if (empty($emotion)) echo 'selected'; ?>>Any emotion</option>
                                            <option value="Wow"  <?php if ($emotion === 'Wow')  echo 'selected'; ?>>Wow</option>
                                            <option value="Love" <?php if ($emotion === 'Love') echo 'selected'; ?>>Love</option>
                                            <option value="Sad"  <?php if ($emotion === 'Sad')  echo 'selected'; ?>>Sad</option>
                                            <!-- Add more if you have them -->
                                        </select>
                                    <?php endif; ?>

                                    <!-- Explicit Search button -->
                                    <button type="submit" class="btn btn-sm btn-success">
                                        Search
                                    </button>

                                    <!-- Hidden mode value (single source of truth) -->
                                    <input
                                        type="hidden"
                                        name="mode"
                                        id="mode-input"
                                        value="<?php echo htmlspecialchars($mode, ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                </div>
                            </div>
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

                <?php if (!$hasFilters): ?>
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

                            <?php
                                // Build a simple "Active filters" string
                                $filterChips = [];

                                if ($mode === 'nlp') {
                                    $filterChips[] = 'Smart (NLP)';
                                } else {
                                    $filterChips[] = 'Keyword';
                                }

                                if ($range === '24h') {
                                    $filterChips[] = 'Last 24 hours';
                                } elseif ($range === 'older') {
                                    $filterChips[] = 'Older than 24 hours';
                                } else {
                                    $filterChips[] = 'All time';
                                }

                                if (!empty($sentiment)) {
                                    $filterChips[] = 'Sentiment: ' . ucfirst($sentiment);
                                }

                                if (!empty($emotion)) {
                                    $filterChips[] = 'Emotion: ' . $emotion;
                                }
                            ?>

                            <?php if ($hasFilters && !empty($filterChips)): ?>
                                <p class="small text-muted mb-1">
                                    Active filters:
                                    <?php echo htmlspecialchars(implode(' · ', $filterChips), ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            <?php endif; ?>


                            <h2 class="h6 mb-3">
                                <?php if ($q !== ''): ?>
                                    Results for
                                    "<span class="fw-semibold">
                                        <?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>"
                                <?php else: ?>
                                    Filtered results
                                <?php endif; ?>

                                <?php if (!empty($results)): ?>
                                    <span class="text-muted">
                                        · <?php echo count($results); ?> found
                                    </span>
                                <?php endif; ?>
                            </h2>

                            <?php if (empty($results)): ?>
                                <p class="text-muted">
                                    No results matched your search or filters. Try another keyword or a more general phrase, or loosen your filters.
                                </p>
                            <?php else: ?>
                                <?php foreach ($results as $row): ?>
                                    <?php
                                    // Decide how to map fields based on search mode
                                    $isNlpMode = ($mode === 'nlp');

                                    if ($isNlpMode) {
                                        // Results from `articles` (NLP search)
                                        $title    = $row['title']       ?? '';
                                        $feedName = $row['source_slug'] ?? '';    // e.g. "foxnews"
                                        if ($feedName !== '') {
                                            $feedName = ucfirst($feedName);
                                        }
                                        $readUrl  = $row['url']         ?? '#';
                                        $pubHuman = sn_format_pub_date($row['pub_date'] ?? null);

                                        $pubRaw = $article['pub_date'] ?? null;

                                        $pubIso = '';
                                        $pub_ts = null;

                                        if (is_numeric($pubRaw)) {
                                            // DB stored as unix timestamp (e.g. INT)
                                            $pub_ts = (int) $pubRaw;
                                            $pubIso = gmdate(DATE_ATOM, $pub_ts);
                                        } elseif (is_string($pubRaw) && $pubRaw !== '') {
                                            // DB stored as a datetime string (e.g. "2025-12-07 15:45:00")
                                            $tmp = strtotime($pubRaw);
                                            if ($tmp !== false) {
                                                $pub_ts = $tmp;
                                                $pubIso = gmdate(DATE_ATOM, $pub_ts);
                                            }
                                        }

                                        // In NLP mode, every row IS an analyzed article
                                        $hasNlp = true;

                                        $analyzeUrl = null;
                                        if (!empty($readUrl)) {
                                            $ts = !empty($row['pub_date']) ? (strtotime($row['pub_date']) ?: null) : null;
                                            $analyzeUrl = 'newsroom.php?url=' . urlencode($readUrl)
                                                . '&category=' . urlencode($feedName)
                                                . '&pub_date=' . urlencode($ts)
                                                . '&db=1';
                                        }

                                    } else {
                                        // Classic search results from `rss_items` + `feeds` + `articles`
                                        $title    = $row['title']     ?? '';
                                        $feedName = $row['feed_name'] ?? '';
                                        $readUrl  = $row['link']      ?? '#';
                                        $pubHuman = sn_format_pub_date($row['pub_date'] ?? null);

                                        $hasNlp = !empty($row['article_id']);

                                        $analyzeUrl = null;
                                        if ($hasNlp && !empty($readUrl)) {
                                            $ts = !empty($row['pub_date']) ? (strtotime($row['pub_date']) ?: null) : null;
                                            $analyzeUrl = 'newsroom.php?url=' . urlencode($readUrl)
                                                . '&category=' . urlencode($feedName)
                                                . '&pub_date=' . urlencode($ts)
                                                . '&db=1';
                                        }
                                    }

                                    // Derive domain + favicon from the *read* URL (works for both modes)
                                    $domain     = '';
                                    $faviconUrl = null;
                                    if (!empty($readUrl)) {
                                        $host = parse_url($readUrl, PHP_URL_HOST);
                                        if ($host) {
                                            // Strip leading www.
                                            $domain = preg_replace('/^www\./i', '', $host);

                                            // Google favicon endpoint using the full URL
                                            $faviconUrl = 'https://t0.gstatic.com/faviconV2'
                                                . '?client=SOCIAL&type=FAVICON'
                                                . '&fallback_opts=TYPE,SIZE,URL'
                                                . '&url=' . rawurlencode($readUrl)
                                                . '&size=64';
                                        }
                                    }

                                    /*
                                     *
                                     *  Prepare NLP data for analyzed articles
                                     *
                                     */

                                    // NLP is jsonb in Postgres; PDO usually returns it as a string
                                    $nlpRaw = $row['nlp'] ?? null;

                                    if (is_string($nlpRaw)) {
                                        $nlp = json_decode($nlpRaw, true) ?: [];
                                    } elseif (is_array($nlpRaw)) {
                                        // In case it's already decoded for some reason
                                        $nlp = $nlpRaw;
                                    } else {
                                        $nlp = [];
                                    }

                                    /**
                                     * 1) HASHTAGS from `keywords`
                                     */
                                    $keywords = $nlp['keywords'] ?? [];
                                    $hashtags = [];

                                    foreach ($keywords as $kw) {
                                        $kw = trim((string)$kw);
                                        if ($kw === '') continue;
                                        // Make sure it starts with '#'
                                        if ($kw[0] !== '#') {
                                            $kw = '#' . $kw;
                                        }
                                        $hashtags[] = $kw;
                                    }

                                    // keep just the first 5
                                    $hashtags = array_slice($hashtags, 0, 5);

                                    /**
                                     * 2) SENTIMENT (label + score)
                                     */
                                    $sentimentLabel = $nlp['sentiment']['label'] ?? null;
                                    $sentimentScore = $nlp['sentiment']['score'] ?? null; // 0.1712 etc

                                    $sentimentEmoji = '';
                                    if ($sentimentLabel === 'positive') {
                                        $sentimentEmoji = '😊';
                                    } elseif ($sentimentLabel === 'negative') {
                                        $sentimentEmoji = '😔';
                                    } elseif ($sentimentLabel === 'neutral') {
                                        $sentimentEmoji = '😐';
                                    }

                                    // Turn 0.1712 into 17% (optional)
                                    $sentimentPercent = null;
                                    if (is_numeric($sentimentScore)) {
                                        $sentimentPercent = (int)round($sentimentScore * 100);
                                    }

                                    /**
                                     * 3) EMOTIONAL REACTION (Wow / Love / etc.)
                                     *    nlp['emotional_reaction'] is a map like: { "Wow": 35.71, "Love": 64.29 }
                                     */
                                    $emotionsRaw = $nlp['emotional_reaction'] ?? [];
                                    $emotions = [];

                                    // Normalize into a list of ['label' => 'Love', 'value' => 64.29]
                                    foreach ($emotionsRaw as $label => $value) {
                                        if (!is_numeric($value)) continue;
                                        $emotions[] = [
                                            'label' => $label,
                                            'value' => (float)$value
                                        ];
                                    }

                                    // Sort by value descending so the strongest reactions come first
                                    usort($emotions, function ($a, $b) {
                                        return $b['value'] <=> $a['value'];
                                    });

                                    // Take top 2–3 for display
                                    $topEmotions = array_slice($emotions, 0, 3);

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

                                            <?php if (!empty($hashtags)): ?>
                                                <div class="sn-hashtags mt-1">
                                                    <?php foreach ($hashtags as $tag): ?>
                                                        <span class="sn-hashtag-chip">
                                                            <?php echo htmlspecialchars($tag); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($sentimentLabel)): ?>
                                                <div class="sn-sentiment mt-1">
                                                    <span class="sn-sentiment-label">
                                                        <?php if ($sentimentEmoji): ?>
                                                            <span class="mr-1"><?php echo $sentimentEmoji; ?></span>
                                                        <?php endif; ?>
                                                        <?php echo ucfirst(htmlspecialchars($sentimentLabel)); ?>
                                                    </span>
                                                    <?php if ($sentimentPercent !== null): ?>
                                                        <span class="sn-sentiment-score text-muted small">
                                                            (<?php echo $sentimentPercent; ?>%)
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($topEmotions)): ?>
                                                <div class="sn-emotions mt-1">
                                                    <?php foreach ($topEmotions as $emo): ?>
                                                        <div class="sn-emotion-bar">
                                                            <span class="sn-emotion-label">
                                                                <?php echo htmlspecialchars($emo['label']); ?>
                                                            </span>
                                                            <div class="sn-emotion-bar-track">
                                                                <div class="sn-emotion-bar-fill"
                                                                     style="width: <?php echo max(5, min(100, $emo['value'])); ?>%;">
                                                                </div>
                                                            </div>
                                                            <span class="sn-emotion-value">
                                                                <?php echo (int)round($emo['value']); ?>%
                                                            </span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="btn-group btn-group-sm<?php
                                                if (!empty($hashtags) || !empty($sentimentLabel) || !empty($topEmotions)) {
                                                    echo ' mt-2';
                                                }
                                            ?>" role="group">
                                                <a
                                                    href="<?php echo htmlspecialchars($readUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="btn btn-outline-secondary"
                                                    target="_blank"
                                                    rel="noopener"
                                                    data-article-url="<?php echo htmlspecialchars($readUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-article-title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-article-source="<?= htmlspecialchars(strtolower($feedName ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-article-image="<?= htmlspecialchars($row['media_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-article-pub-date="<?= htmlspecialchars($pubIso ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    data-article-kind="external"
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
                            <a href="index.php" data-loading>scroll news</a>
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

        <script src="js/sn_history.js"></script>

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

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var form    = document.getElementById('sn-search-form');
                var overlay = document.getElementById('sn-search-loading');

                if (!form || !overlay) return;

                form.addEventListener('submit', function () {
                    overlay.classList.add('active');
                });
            });
        </script>

    </body>
</html>
