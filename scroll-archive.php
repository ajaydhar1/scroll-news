<?php
// scroll-archive.php — Daily Scroll Archive inside Scroll News template

define('BASE_PATH', __DIR__);

require_once BASE_PATH . "/core/___modules.php";

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

        <!-- jQuery min-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" defer></script>

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
        <link href="css/scroll-archive.css?v=<?php echo filemtime(__DIR__ . '/css/scroll-archive.css'); ?>" rel="stylesheet" />

    </head>
    <body id="page-top" class="bg-light-3">

        <!-- Blurred overlay -->
        <div class="blur-layer"></div>

        <div class="page">

            <!-- Top nav-->        
            <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

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

                        <?php require_once BASE_PATH . "/core/config/interest.php"; ?>

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
            <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>

        </div>

        <!-- Modals-->        
        <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

        <!-- Core JS (Bootstrap 4 requires jQuery first) -->
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" defer></script>

        <!-- Core theme JS-->
        <script src="js/scripts.js"></script>
        <script src="js/sn_history.js"></script>
        <script src="js/scroll-archive.js?v=<?php echo filemtime(__DIR__ . '/js/scroll-archive.js'); ?>"></script>
    </body>
</html>
