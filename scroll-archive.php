<?php
// scroll-archive.php — Daily Scroll Archive inside Scroll News template

require_once('___modules.php');

// Fetch all RSS items ordered by pub_date DESC
$DAYS_TO_SHOW = 4;
$errorMsg   = null;

$pdo = _pdo_or_null();

if (!$pdo) {
    $errorMsg = "Database connection not available.";
} else {
    try {

        // Compute cutoff date: today minus (DAYS_TO_SHOW - 1) days
        $cutoffDate = (new DateTimeImmutable('today'))
            ->sub(new DateInterval('P' . ($DAYS_TO_SHOW - 1) . 'D'))
            ->format('Y-m-d');

        $sql = "
            SELECT 
                ri.id,
                ri.title,
                ri.link,
                ri.pub_date,
                ri.media_url,
                f.name AS feed_name
            FROM rss_items ri
            JOIN feeds f ON f.id = ri.feed_id
            WHERE 
                ri.pub_date IS NOT NULL
                AND ri.pub_date::date >= :cutoff_date
            ORDER BY ri.pub_date DESC, ri.id DESC
        ";

        $stmt  = $pdo->prepare($sql);
        $stmt->execute([':cutoff_date' => $cutoffDate]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group items by local date (Y-m-d) based on pub_date, using same offset logic as format_news_date()
        $days = [];

        $tzId = 'America/New_York';
        $tz   = new DateTimeZone($tzId);

        foreach ($items as $item) {
            if (empty($item['pub_date'])) {
                continue; // nothing to group
            }

            $raw = $item['pub_date'];

            // Allow either a Unix timestamp-ish value or a datetime string from the DB
            $ts = null;

            if (is_numeric($raw)) {
                // Normalize digits and guard against ms
                $digits = preg_replace('/\D/', '', (string)$raw);
                if ($digits !== '') {
                    $ts = (int)$digits;
                    if ($ts > 1000000000000) { // looks like ms
                        $ts = (int)round($ts / 1000);
                    }
                }
            } else {
                // Treat as TIMESTAMPTZ string like "2025-12-01 18:15:00+00"
                $tmp = strtotime($raw);
                if ($tmp !== false) {
                    $ts = $tmp;
                }
            }

            if ($ts === null) {
                continue; // can't parse, skip
            }

            // Apply same offset logic as the masthead: start from UTC and shift to America/New_York
            $dt = (new DateTimeImmutable('@' . $ts))->setTimezone($tz);

            // Store for later display (you can reuse this with format_news_date if you want)
            $item['_dt']       = $dt;
            $item['_ts']       = $ts;               // raw Unix seconds for filters
            $item['_date_key'] = $dt->format('Y-m-d');

            // Group by local calendar date
            $dateKey = $item['_date_key'];

            if (!isset($days[$dateKey])) {
                $days[$dateKey] = [];
            }
            $days[$dateKey][] = $item;
        }
    } catch (Throwable $e) {
        $errorMsg = 'There was a problem loading your scroll history.';
        $rows     = [];
        // Optional: error_log($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Browse every article Scroll News has captured in one continuous archive—day by day, row by row." />
        <meta name="author" content="Scroll News" />
        <title>Daily Scroll Archive – Browse Every Saved Article</title>

        <!-- Favicon-->
        <link rel="icon" type="image/png" href="assets/img/play-green.png" />
        <link rel="canonical" href="https://scrollnews.io/scroll-archive.php">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://scrollnews.io/scroll-archive.php" />
        <meta property="og:title" content="Daily Scroll Archive — Every Article in One Place" />
        <meta property="og:description" content="Flip through the full Scroll News archive like a book, with one horizontal row of cards for each day." />
        <meta property="og:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-history-1200x630.png" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://scrollnews.io/scroll-archive.php" />
        <meta name="twitter:title" content="Daily Scroll Archive — Every Article in One Place" />
        <meta name="twitter:description" content="Flip through the full Scroll News archive like a book, with one horizontal row of cards for each day." />
        <meta name="twitter:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-history-1200x630.png" />

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
        <link href="css/mindpour.css?v=<?php echo filemtime(__DIR__ . '/css/mindpour.css'); ?>" rel="stylesheet" />

        <style>
            .content {
                position: fixed;
                bottom: 0;
                left: 0;
                color: #0a0a0a;
                width: 100%;
                padding: 20px;
                height: 190px;
                margin-top: -95px;
                text-align: center;
            }

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

            /* Daily Scroll-specific styles */

            .sn-archive-header {
                /* margin-bottom: 2.5rem; */
            }

            .page-section h2.section-heading {
                margin-bottom: .5rem;
            }

            .sn-archive-header h2 {
                font-size: 1.75rem;
                font-weight: 700;
            }

            .sn-archive-header p {
                font-size: 0.95rem;
                color: #6b6b7a;
                margin-bottom: 0;
            }

            .day-row {
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                padding: 1.5rem 0 2rem;
            }

            .day-title {
                font-weight: 600;
                font-size: 1rem;
            }

            .day-meta {
                font-size: 0.8rem;
                color: #f1f1f1f1;
            }

            .articles-track-wrapper {
                position: relative;
            }

            .articles-track {
                display: flex;
                gap: 1rem;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 0.5rem;
            }

            .articles-track::-webkit-scrollbar {
                height: 6px;
            }
            .articles-track::-webkit-scrollbar-track {
                background: transparent;
            }
            .articles-track::-webkit-scrollbar-thumb {
                background: rgba(0, 0, 0, 0.18);
                border-radius: 999px;
            }

            /* Alternating horizontal offsets for rows */
            .day-row:nth-of-type(odd) .articles-track-wrapper {
                padding-left: 0;
                padding-right: 2rem;
            }
            .day-row:nth-of-type(even) .articles-track-wrapper {
                padding-left: 2rem;
                padding-right: 0;
            }

            .article-card {
                scroll-snap-align: start;
                min-width: 260px;
                max-width: 320px;
                background: #ffffff;
                border-radius: 0.75rem;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
                border: 1px solid rgba(0, 0, 0, 0.06);
                display: flex;
                flex-direction: column;
            }

            .article-image-wrap {
                background: #e1e1e8;
                position: relative;
            }

            .article-image-wrap img {
                display: block;
                width: 100%;
                height: 180px;
                object-fit: cover;
            }

            .article-body {
                padding: 0.75rem 0.9rem 0.9rem;
                display: flex;
                flex-direction: column;
                gap: 0.35rem;
            }

            .article-title {
                font-size: 0.95rem;
                font-weight: 600;
                line-height: 1.3;
                margin: 0 0 0.1rem;
            }

            .article-meta {
                font-size: 0.78rem;
                color: #6b6b7a;
            }

            .article-actions {
                margin-top: 0.5rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                flex-wrap: wrap;
            }

            .btn-analyze {
                border-radius: 999px;
                border-width: 1px;
                font-size: 0.78rem;
                padding: 0.25rem 0.7rem;
            }

            .link-read {
                font-size: 0.78rem;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                color: inherit;
            }

            .link-read span.icon {
                font-size: 0.8em;
            }

            .link-read:hover {
                text-decoration: underline;
            }

            /* Scroll buttons on each side of the track */
            .scroll-btn {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                z-index: 5;
                width: 34px;
                height: 34px;
                border-radius: 999px;
                border: none;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, 0.95);
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
                cursor: pointer;
            }

            .scroll-btn-left {
                left: 0.25rem;
            }

            .scroll-btn-right {
                right: 0.25rem;
            }

            .scroll-btn:disabled {
                opacity: 0.4;
                cursor: default;
                box-shadow: none;
            }

            @media (max-width: 768px) {
                .day-row:nth-of-type(even) .articles-track-wrapper {
                    padding-left: 1rem;
                    padding-right: 0;
                }

                .scroll-btn-left {
                    left: 0;
                }
                .scroll-btn-right {
                    right: 0;
                }
            }

            /* Loading overlay from template */
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

            a.btn.btn-outline-primary.btn-analyze:hover {
                background: #00bfa6;
                border: none;
            }

            .btn-outline-primary {
                color: black;
                border: none;
            }

            .article-image-wrap {
                position: relative;
            }

            .domain-chip {
                position: absolute;
                left: 0.5rem;
                bottom: 0.5rem;
                padding: 4px 8px;
                border-radius: 999px;
                background: rgba(0, 0, 0, 0.65);
                color: #fff;
                font-size: 0.75rem;
                display: inline-flex;
                letter-spacing: 0.02em;
                align-items: center;
                gap: 4px;
            }

            .domain-chip .pub-favicon {
                width: 16px;
                height: 16px;
                border-radius: 4px;
                object-fit: contain;
            }

            /* Filter bar */
            .sn-history-filters label {
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #6b6b7a;
                margin-bottom: 0.15rem;
            }
        </style>

    </head>
    <body id="page-top" class="bg-light-3">

        <!-- Blurred overlay -->
        <div class="blur-layer"></div>

        <div class="page">

            <!-- Top nav-->        
            <?php require_once __DIR__ . '/___topnav_full.php'; ?>

            <!-- Daily Scroll Archive -->
            <section class="page-section" id="" style="padding: 4rem 0;">
                <div class="container-fluid px-3 px-md-4">
                    <div class="row justify-content-center sn-archive-header mb-3">
                        <div class="col-md-8 text-center">
                            <h2 class="section-heading text-uppercase">Daily Scroll Archive</h2>
                            <p class="section-subheading">
                                Flip through every article Scroll News has captured over the last <?= $DAYS_TO_SHOW + 1 ?> days, with one horizontal row of cards for each day.
                            </p>
                        </div>
                    </div>

                    <p class="small text-muted text-center mb-3">
                        Want to see only what <em>you’ve</em> read?
                        <a href="history.php">View your reading history →</a>
                    </p>

                    <?php if ($errorMsg): ?>
                        <div class="row">
                            <div class="col-md-6 mx-auto">
                                <div class="alert alert-danger">
                                    <?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                        </div>
                    <?php elseif (empty($days)): ?>
                        <div class="row justify-content-center">
                            <div class="col-md-8 text-center">
                                <p class="text-muted">No articles found in the archive yet.</p>
                            </div>
                        </div>
                    <?php else: ?>

                        <!-- Filter bar -->
                        <div class="sn-history-filters mt-5 mb-0">
                            <div class="container-fluid px-0 px-md-1">
                                <div class="row justify-content-center">
                                    <div class="col-md-4 col-lg-2 mb-2">
                                        <label for="historyFilterKeyword">Filter by keyword</label>
                                        <input id="historyFilterKeyword" type="text" class="form-control form-control-sm" placeholder="headline, topic, etc.">
                                    </div>
                                    <div class="col-md-4 col-lg-2 mb-2">
                                        <label for="historyFilterDomain">Filter by domain</label>
                                        <input id="historyFilterDomain" type="text" class="form-control form-control-sm" placeholder="e.g. nytimes.com">
                                    </div>
                                    <div class="col-md-4 col-lg-2 mb-2">
                                        <label for="historyFilterTime">Time window</label>
                                        <select id="historyFilterTime" class="form-control form-control-sm">
                                            <option value="all">All time</option>
                                            <option value="1d">Last 24 hours</option>
                                            <option value="7d">Last 7 days</option>
                                            <option value="30d">Last 30 days</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php require_once 'config_interest.php'; ?>

                        <div id="history-no-results" class="row justify-content-center mt-3 d-none">
                            <div class="col-md-6">
                                <div class="alert alert-info" role="alert">
                                No articles found. Try adjusting your filters or clearing them.
                                </div>
                            </div>
                        </div>

                        <?php $rowIndex = 0; ?>
                        <?php foreach ($days as $dateKey => $articles): ?>
                            <?php
                                $rowIndex++;
                                $trackId = 'articles-track-' . $rowIndex;
                                $dt = DateTime::createFromFormat('Y-m-d', $dateKey);
                                $friendlyDate = $dt ? $dt->format('F j, Y') : htmlspecialchars($dateKey);
                                $count = count($articles);
                            ?>
                            <section class="day-row sn-history-day">
                                <div class="row gx-2 gx-sm-3 mb-2">
                                    <div class="col-12 d-flex justify-content-between align-items-baseline">
                                        <h3 class="day-title mb-0"><?php echo $friendlyDate; ?></h3>
                                        <div class="day-meta">
                                            <?php echo $count; ?> article<?php echo $count !== 1 ? 's' : ''; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="articles-track-wrapper">
                                    <!-- Scroll buttons -->
                                    <button class="scroll-btn scroll-btn-left"
                                            type="button"
                                            data-track="<?php echo $trackId; ?>"
                                            data-direction="left">
                                        ‹
                                    </button>
                                    <button class="scroll-btn scroll-btn-right"
                                            type="button"
                                            data-direction="right"
                                            data-track="<?php echo $trackId; ?>">
                                        ›
                                    </button>

                                    <!-- Horizontal track -->
                                    <div class="articles-track" id="<?php echo $trackId; ?>">
                                        <?php foreach ($articles as $row): ?>
                                            <?php
                                            $vm = sn_article_vm_from_row($row, [
                                                'mode' => 'classic',
                                                'analysis_window' => '30d',
                                                'force_analyze' => true,   // ✅ bring Analyze back for archive rows
                                            ]);

                                            sn_render_article_card_archive($vm, []);
                                            ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Footer-->        
            <?php require_once __DIR__ . '/___footer.php'; ?>

        </div>

        <!-- Modals-->        
        <?php require_once __DIR__ . '/___modals.php'; ?>

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
            // Scroll buttons, keyboard navigation, and filters for Scroll History
            document.addEventListener('DOMContentLoaded', function () {
                var SCROLL_AMOUNT = 600; // px per click; tweak as needed

                // Arrow button click scroll
                document.querySelectorAll('.scroll-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var trackId = btn.getAttribute('data-track');
                        var dir     = btn.getAttribute('data-direction');
                        var track   = document.getElementById(trackId);
                        if (!track) return;

                        var delta = (dir === 'right' ? 1 : -1) * SCROLL_AMOUNT;

                        track.scrollBy({
                            left: delta,
                            behavior: 'smooth'
                        });
                    });
                });

                // Keyboard left/right navigation for each horizontal track
                document.querySelectorAll('.articles-track').forEach(function (track) {
                    track.tabIndex = 0; // make focusable

                    track.addEventListener('keydown', function (e) {
                        if (e.key === 'ArrowRight') {
                            e.preventDefault();
                            track.scrollBy({
                                left: track.clientWidth * 0.9,
                                behavior: 'smooth'
                            });
                        } else if (e.key === 'ArrowLeft') {
                            e.preventDefault();
                            track.scrollBy({
                                left: -track.clientWidth * 0.9,
                                behavior: 'smooth'
                            });
                        }
                    });
                });

                // Filter bar logic
                var keywordInput = document.getElementById('historyFilterKeyword');
                var domainInput  = document.getElementById('historyFilterDomain');
                var timeSelect   = document.getElementById('historyFilterTime');

                if (keywordInput && domainInput && timeSelect) {
                    var items     = Array.prototype.slice.call(document.querySelectorAll('.sn-history-item'));
                    var dayGroups = Array.prototype.slice.call(document.querySelectorAll('.sn-history-day'));

                    function applyFilters() {
                        var keyword = keywordInput.value.trim().toLowerCase();
                        var domain  = domainInput.value.trim().toLowerCase();
                        var timeVal = timeSelect.value;

                        var nowSec = Math.floor(Date.now() / 1000);

                        items.forEach(function (item) {
                            var title      = (item.dataset.title || '').toLowerCase();
                            var itemDomain = (item.dataset.domain || '').toLowerCase();
                            var ts         = parseInt(item.dataset.timestamp || '0', 10);

                            var visible = true;

                            // Keyword filter
                            if (keyword && !title.includes(keyword)) {
                                visible = false;
                            }

                            // Domain filter
                            if (visible && domain && !itemDomain.includes(domain)) {
                                visible = false;
                            }

                            // Time window filter
                            if (visible && timeVal !== 'all' && ts > 0) {
                                var diffSec = nowSec - ts;
                                if (timeVal === '1d'  && diffSec > 86400)        visible = false;
                                if (timeVal === '7d'  && diffSec > 86400 * 7)    visible = false;
                                if (timeVal === '30d' && diffSec > 86400 * 30)   visible = false;
                            }

                            if (visible) {
                                item.classList.remove('d-none');
                            } else {
                                item.classList.add('d-none');
                            }
                        });

                        // Hide whole day rows that have no visible items
                        dayGroups.forEach(function (group) {
                            var anyVisible = group.querySelector('.sn-history-item:not(.d-none)') !== null;
                            if (anyVisible) {
                                group.classList.remove('d-none');
                            } else {
                                group.classList.add('d-none');
                            }
                        });

                        // Reset all tracks back to the start (left) after filtering
                        document.querySelectorAll('.articles-track').forEach(function (track) {
                            track.scrollLeft = 0;
                        });

                        // 🔹 NOW update the no-results alert based on current visibility
                        updateHistoryNoResultsMessage();
                    }

                    keywordInput.addEventListener('input', applyFilters);
                    domainInput.addEventListener('input', applyFilters);
                    timeSelect.addEventListener('change', applyFilters);

                }
            });

            function updateHistoryNoResultsMessage() {
                const items = Array.from(document.querySelectorAll('.sn-history-item'));
                if (!items.length) return; // nothing to do if there are no items at all

                const anyVisible = items.some(el => !el.classList.contains('d-none'));
                const alertBox = document.getElementById('history-no-results');
                if (!alertBox) return;

                if (!anyVisible) {
                    alertBox.classList.remove('d-none');
                } else {
                    alertBox.classList.add('d-none');
                }
            }
        </script>

    </body>
</html>
