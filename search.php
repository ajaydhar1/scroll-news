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
$q               = $_GET['q'] ?? '';
$q               = trim((string)$q);  // make sure it's a string
$highSignalOnly  = !empty($_GET['high_signal']);             // <-- NEW
$deepDive        = isset($_GET['deep_dive']) && $_GET['deep_dive'] === '1';
$emotion         = $_GET['emotion']    ?? null;              // e.g. 'Sad', 'Love', 'Wow'
$sentiment       = $_GET['sentiment']  ?? null;              // e.g. 'positive', 'negative'
$range           = $_GET['range']      ?? 'all';             // '24h', 'older', 'all'

$deepDiveActive    = !empty($_GET['deep_dive']);
$highSignalActive  = !empty($_GET['high_signal']);

// ignore deep_dive when not in NLP mode
$deepDive       = ($mode === 'nlp') ? $deepDive : false;
$deepDiveActive = $deepDive;

// Decide if *any* filters are active (even without q)
$hasFilters =
    ($q !== '') ||
    !empty($emotion) ||
    !empty($sentiment) ||
    ($range !== 'all') ||
    $highSignalOnly ||                                       // <-- NEW
    $deepDive;

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
                'deep_dive'    => $deepDive,
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
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />

        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>" rel="stylesheet" />
        <link href="css/custom.css?v=<?php echo filemtime(__DIR__ . '/css/custom.css'); ?>" rel="stylesheet" />

        <link href="css/sn-search.css?v=<?php echo filemtime(__DIR__ . '/css/sn-search.css'); ?>" rel="stylesheet" />

    </head>
    <body id="page-top" class="bg-dark">

        <div id="sn-search-loading" class="sn-loading-overlay" aria-hidden="true">
            <div class="sn-loading-spinner">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading…</span>
                </div>
            </div>
        </div>

        <!-- Top nav-->        
        <?php require_once __DIR__ . '/___topnav_full.php'; ?>

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
                                        <button type="button"
                                                class="btn <?= ($mode === 'classic') ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                                                data-sn-mode="classic">
                                        Keyword
                                        </button>

                                        <button type="button"
                                                class="btn <?= ($mode === 'nlp') ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                                                data-sn-mode="nlp">
                                        Smart (NLP)
                                        </button>
                                    </div>

                                    <!-- Range selector (both modes) -->
                                    <select
                                        name="range"
                                        class="form-select form-select-sm w-auto me-2"
                                        data-sn-autosubmit
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
                                            data-sn-autosubmit
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
                                            data-sn-autosubmit
                                        >
                                            <option value="" <?php if (empty($emotion)) echo 'selected'; ?>>Any emotion</option>
                                            <option value="Love" <?php if ($emotion === 'Love') echo 'selected'; ?>>Love</option>
                                            <option value="Angry" <?php if ($emotion === 'Angry') echo 'selected'; ?>>Angry</option>
                                            <option value="Ahah" <?php if ($emotion === 'Ahah') echo 'selected'; ?>>Ahah</option>
                                            <option value="Wow"  <?php if ($emotion === 'Wow')  echo 'selected'; ?>>Wow</option>
                                            <option value="Sad"  <?php if ($emotion === 'Sad')  echo 'selected'; ?>>Sad</option>
                                            <!-- Add more if you have them -->
                                        </select>
                                    <?php endif; ?>

                                    <!-- Explicit Search button -->
                                    <button type="submit" class="btn btn-sm btn-success">
                                        Search
                                    </button>

                                    <div class="scroll-article-badges">
                                        <?php if ($mode === 'nlp'): ?>
                                            <button type="button"
                                                    class="scroll-badge scroll-badge-deep-dive <?= $deepDiveActive ? 'scroll-badge-active' : ''; ?>"
                                                    data-sn-toggle="deep_dive"
                                                    data-sn-only-when-mode="nlp">
                                            DEEP DIVE
                                            </button>
                                        <?php endif; ?>

                                        <button type="button"
                                                class="scroll-badge scroll-badge-high-signal-publisher <?= $highSignalActive ? 'scroll-badge-active' : ''; ?>"
                                                data-sn-toggle="high_signal">
                                        HIGH-SIGNAL PUBLISHER
                                        </button>
                                    </div>

                                    <!-- Hidden mode value (single source of truth) -->
                                    <input
                                        type="hidden"
                                        name="mode"
                                        id="mode-input"
                                        value="<?php echo htmlspecialchars($mode, ENT_QUOTES, 'UTF-8'); ?>"
                                    >

                                    <input type="hidden" name="deep_dive"   id="deep-dive-input"   value="<?php echo $deepDiveActive ? '1' : ''; ?>">
                                    <input type="hidden" name="high_signal" id="high-signal-input" value="<?php echo $highSignalActive ? '1' : ''; ?>">

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

                            <?php
                                // Curated starter searches (ship-now)
                                $searchChips = [
                                    'AI regulation',
                                    'Apple',
                                    'Microsoft',
                                    'Elon Musk',
                                    'OpenAI',
                                    'NVIDIA',
                                    'Ukraine',
                                    'Gaza',
                                    'Supreme Court',
                                    'Interest rates',
                                    'Inflation',
                                    'Climate',
                                    'Taylor Swift',
                                    'NFL',
                                    'NBA',
                                ];

                                // build classic search urls
                                $buildSearchUrl = function(string $q): string {
                                    $params = [
                                        'q' => $q,
                                        'range' => 'all',
                                        'mode' => 'classic',
                                        'deep_dive' => '',
                                        'high_signal' => '',
                                    ];
                                    return '/search.php?' . http_build_query($params);
                                };
                            ?>

                            <div class="sn-search-chips mt-2">
                                <?php foreach ($searchChips as $chip): ?>
                                    <a class="sn-chip" href="<?= htmlspecialchars($buildSearchUrl($chip), ENT_QUOTES, 'UTF-8') ?>" data-loading>
                                        <?= htmlspecialchars($chip, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>

                            <div class="small text-muted mt-2">
                                Tip: try names, companies, locations, or big topics.
                            </div>
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

                                // NEW: High-signal publishers
                                if (!empty($highSignalOnly)) {
                                    $filterChips[] = 'High-signal publishers';
                                }

                                // NEW: Deep dive (entity-dense)
                                if (!empty($deepDive) && $mode === 'nlp') {
                                    $filterChips[] = 'Deep Dive (entity-dense)';
                                    // or shorter: 'Deep Dive'
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

                                        $pubRaw = $row['pub_date'] ?? null;

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

                                    // $article is your article row
                                    $badges = scroll_get_article_badges($row);

                                    $card_classes = ' scroll-history-card';

                                    if (scroll_is_high_signal_publisher($row)) {
                                        $card_classes .= ' scroll-card-high-signal';
                                    }

                                    if (scroll_is_deep_dive($row)) {
                                        $card_classes .= ' scroll-card-deep-dive';
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

                                    <div class="card mb-3 shadow-sm border-0 sn-search-card<?php echo $card_classes; ?>">
                                        <div class="card-body">
                                            <h5 class="card-title mb-1">
                                                <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                                            </h5>

                                            <div class="sn-search-meta small text-muted mb-3 d-flex align-items-center">
                                                <?php if ($faviconUrl): ?>
                                                    <a 
                                                        href="https://<?php echo htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>" 
                                                        target="_blank" 
                                                        rel="noopener noreferrer"
                                                    >
                                                        <img
                                                            src="<?php echo htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                            alt="<?php echo htmlspecialchars($domain ?: $feedName ?: 'Site', ENT_QUOTES, 'UTF-8'); ?> logo"
                                                            class="sn-favicon"
                                                        >
                                                    </a>
                                                <?php endif; ?>

                                                <div class="sn-meta-text">
                                                    <?php if ($pubHuman): ?>
                                                        <?php echo htmlspecialchars($pubHuman, ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php endif; ?>

                                                    <?php if ($feedName): ?>
                                                        <?php if ($pubHuman): ?> • <?php endif; ?>

                                                        <?php
                                                            $categoryValue = urlencode(strtolower($feedName));
                                                            $categoryUrl = "/analysis.php?context=category&value={$categoryValue}&w=7d";
                                                        ?>

                                                        <a 
                                                            href="<?php echo htmlspecialchars($categoryUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                            class="sn-category-link"
                                                            data-loading
                                                        >
                                                            <?php echo htmlspecialchars($feedName, ENT_QUOTES, 'UTF-8'); ?>
                                                        </a>        
                                                    <?php endif; ?>

                                                    <?php if ($domain): ?>
                                                        <?php if ($pubHuman || $feedName): ?> • <?php endif; ?>
                                                        <a 
                                                            href="https://<?php echo htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>" 
                                                            target="_blank" 
                                                            rel="noopener noreferrer"
                                                        >
                                                            <?php echo htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <?php if (!empty($badges)) : ?>
                                                <div class="scroll-article-badges">
                                                    <?php foreach ($badges as $badge): ?>
                                                        <?php
                                                            $slug = $badge['slug'] ?? '';

                                                            // Default links (you can define these earlier in the file too)
                                                            $highSignalSearchUrl = '/search.php?high_signal=1'; // maybe add &mode=nlp later
                                                            $deepDiveSearchUrl   = '/search.php?mode=nlp&deep_dive=1';

                                                            // Decide href per badge
                                                            $badgeHref = $highSignalSearchUrl; // sensible default

                                                            if ($slug === 'deep-dive') {
                                                                $badgeHref = $deepDiveSearchUrl;
                                                            } elseif ($slug === 'high-signal-publisher') {
                                                                $badgeHref = $highSignalSearchUrl;
                                                            }
                                                        ?>
                                                        <a class="scroll-badge scroll-badge-<?php echo htmlspecialchars($slug); ?>"
                                                           href="<?php echo htmlspecialchars($badgeHref); ?>" title="<?php echo htmlspecialchars($badge['tooltip']); ?>" data-loading>
                                                            <?php echo htmlspecialchars($badge['label']); ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($hashtags)): ?>
                                                <div class="sn-hashtags mt-1">
                                                    <?php foreach ($hashtags as $tag): ?>
                                                        <?php
                                                            // hashtags likely come in like "#TaylorSwift" or "#Taylor Swift"
                                                            $raw = (string)$tag;

                                                            // Remove ONE leading "#", then trim whitespace
                                                            $clean = ltrim($raw, "# \t\n\r\0\x0B");

                                                            // If you want to be extra safe:
                                                            $clean = trim($clean);

                                                            // Build analysis URL (7d for search page)
                                                            $href = sn_analysis_url($clean, '7d', 'entity');
                                                        ?>
                                                        <a class="sn-hashtag-chip"
                                                        href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" data-loading>
                                                            <?= htmlspecialchars($raw, ENT_QUOTES, 'UTF-8') ?>
                                                        </a>
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

        <!-- Footer-->        
        <?php require_once __DIR__ . '/___footer.php'; ?>
        
        <!-- Modals-->        
        <?php require_once __DIR__ . '/___modals.php'; ?>

        <!-- Core JS (Bootstrap 4 requires jQuery first) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" defer></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" defer></script>

        <!-- Theme -->
        <script src="js/scripts.js" defer></script>

        <!-- Scroll News Features -->
        <script src="js/sn_history.js" defer></script>
        <script src="js/sn-mini-player-yt.js?v=<?= filemtime(__DIR__.'/js/sn-mini-player-yt.js') ?>" defer></script>
        <script src="js/sn-search.js?v=<?= filemtime(__DIR__.'/js/sn-search.js') ?>" defer></script>

    </body>
</html>
