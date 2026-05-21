<?php
define('BASE_PATH', __DIR__);

require_once BASE_PATH . "/core/___modules.php";
require_once BASE_PATH . "/auth/includes/auth_bootstrap.php";

// Config
$DAYS_PER_PAGE = 5;
$errorMsg      = null;
$page          = max(1, (int)($_GET['page'] ?? 1));
$totalPages    = 1;
$days          = [];

$ARCHIVE_OLDEST_DATE = '2025-11-30'; // first day RSS ingestion started

$pdo = _pdo_or_null();

if (!$pdo) {
    $errorMsg = "Database connection not available.";
} else {
    try {
        $tzId = 'America/New_York';
        $tz   = new DateTimeZone($tzId);

        $todayLocal = new DateTimeImmutable('today', $tz);

        $daysBackStart = (($page - 1) * $DAYS_PER_PAGE) + ($DAYS_PER_PAGE - 1);
        $daysBackEnd   = ($page - 1) * $DAYS_PER_PAGE;

        $windowStart = $todayLocal->sub(new DateInterval('P' . $daysBackStart . 'D'));
        $windowEndExclusive = $todayLocal->sub(new DateInterval('P' . $daysBackEnd . 'D'))
            ->add(new DateInterval('P1D'));

        $windowStartDate = $windowStart->format('Y-m-d');
        $windowEndDate   = $windowEndExclusive->format('Y-m-d');

        $oldestDate = new DateTimeImmutable($ARCHIVE_OLDEST_DATE, $tz);
        $newestDate = new DateTimeImmutable('today', $tz);

        $totalDays  = (int)$oldestDate->diff($newestDate)->days + 1;
        $totalPages = max(1, (int)ceil($totalDays / $DAYS_PER_PAGE));

        if ($page > $totalPages) {
            $page = $totalPages;

            $windowStart = $todayLocal->sub(new DateInterval('P' . (($page - 1) * $DAYS_PER_PAGE + ($DAYS_PER_PAGE - 1)) . 'D'));
            $windowEnd   = $todayLocal->sub(new DateInterval('P' . (($page - 1) * $DAYS_PER_PAGE) . 'D'))
                ->add(new DateInterval('P1D'));

            $windowStartDate = $windowStart->format('Y-m-d');
            $windowEndDate   = $windowEnd->format('Y-m-d');
        }

        $sql = "
            SELECT 
                ri.id,
                ri.title,
                ri.link,
                ri.pub_date,
                ri.media_url,
                f.name AS feed_name,
                a.id AS article_id,
                a.nlp
            FROM rss_items ri
            JOIN feeds f ON f.id = ri.feed_id AND f.deleted_at IS NULL
            JOIN articles a ON a.url = ri.link AND a.deleted_at IS NULL
            WHERE 
                ri.pub_date IS NOT NULL
                AND ri.deleted_at IS NULL
                AND (ri.pub_date AT TIME ZONE 'America/New_York')::date >= :window_start
                AND (ri.pub_date AT TIME ZONE 'America/New_York')::date < :window_end
            ORDER BY ri.pub_date DESC, ri.id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':window_start' => $windowStartDate,
            ':window_end'   => $windowEndDate,
        ]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            if (empty($item['pub_date'])) {
                continue;
            }

            $raw = $item['pub_date'];
            $ts = null;

            if (is_numeric($raw)) {
                $digits = preg_replace('/\D/', '', (string)$raw);
                if ($digits !== '') {
                    $ts = (int)$digits;
                    if ($ts > 1000000000000) {
                        $ts = (int)round($ts / 1000);
                    }
                }
            } else {
                $tmp = strtotime($raw);
                if ($tmp !== false) {
                    $ts = $tmp;
                }
            }

            if ($ts === null) {
                continue;
            }

            $dt = (new DateTimeImmutable('@' . $ts))->setTimezone($tz);

            $item['_dt']       = $dt;
            $item['_ts']       = $ts;
            $item['_date_key'] = $dt->format('Y-m-d');

            $dateKey = $item['_date_key'];

            if (!isset($days[$dateKey])) {
                $days[$dateKey] = [];
            }
            $days[$dateKey][] = $item;
        }
    } catch (Throwable $e) {
        $errorMsg = 'There was a problem loading your scroll history.';
        $days     = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Browse every article Scroll News has captured in one continuous archive—day by day, row by row." />
        <meta name="author" content="Scroll News" />
        <title>Daily Scroll Archive – Browse Every Saved Article</title>

        <!-- Favicon-->
        <link rel="icon" type="image/png" href="/assets/img/play-green.png" />
        <link rel="canonical" href="https://scrollnews.ai/scroll-archive">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://scrollnews.ai/scroll-archive.php" />
        <meta property="og:title" content="Daily Scroll Archive — Every Article in One Place" />
        <meta property="og:description" content="Flip through the full Scroll News archive like a book, with one horizontal row of cards for each day." />
        <meta property="og:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-archive-1200x630.png" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://scrollnews.ai/scroll-archive.php" />
        <meta name="twitter:title" content="Daily Scroll Archive — Every Article in One Place" />
        <meta name="twitter:description" content="Flip through the full Scroll News archive like a book, with one horizontal row of cards for each day." />
        <meta name="twitter:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-archive-1200x630.png" />

        <!-- Performance: Preload background -->
        <link rel="preload" as="image" href="/assets/img/mind-pour_00.jpg">

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
        <link href="/assets/css/mindpour.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/mindpour.css'); ?>" rel="stylesheet" />
        <link href="/assets/css/pages/scroll-archive.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/scroll-archive.css'); ?>" rel="stylesheet" />

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
                                Flip through every article Scroll News has captured, with one horizontal row of cards for each day.
                            </p>
                            <p class="section-subheading">
                                Viewing page <?= (int)$page; ?> of <?= (int)$totalPages; ?> — a <?= $DAYS_PER_PAGE ?>-day slice of the Scroll News archive.
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
                                    <div class="col-md-4 col-lg-2 mb-2 filter-col">
                                        <label for="historyFilterKeyword">Filter by keyword</label>
                                        <input id="historyFilterKeyword" type="text" class="form-control form-control-sm" placeholder="headline, topic, etc.">
                                    </div>
                                    <div class="col-md-4 col-lg-2 mb-2 filter-col">
                                        <label for="historyFilterDomain">Filter by domain</label>
                                        <input id="historyFilterDomain" type="text" class="form-control form-control-sm" placeholder="e.g. nytimes.com">
                                    </div>
                                    <div class="col-md-4 col-lg-2 col-xl-1 mb-2 filter-col">
                                        <label for="historyFilterTime">Time window</label>
                                        <select id="historyFilterTime" class="form-control form-control-sm">
                                            <option value="all">All time</option>
                                            <option value="1d">Last 24 hours</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                        $rangeLabelStart = $windowStart->format('F j, Y');
                        $rangeLabelEnd   = $windowEndExclusive->sub(new DateInterval('P1D'))->format('F j, Y');
                        ?>
                        <p class="small text-muted text-center mt-3 mb-0">
                            Showing archive window: <strong><?php echo $rangeLabelStart; ?></strong>
                            through
                            <strong><?php echo $rangeLabelEnd; ?></strong>
                        </p>

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
                                                'force_analyze' => true,
                                                'force_db' => true,   // ✅ new
                                            ]);

                                            sn_render_article_card_archive($vm, []);

                                            ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </section>
                        <?php endforeach; ?>

                        <?php
                        $paginationRadius = 2;
                        $startPage = max(1, $page - $paginationRadius);
                        $endPage   = min($totalPages, $page + $paginationRadius);

                        $buildArchivePageUrl = function (int $targetPage): string {
                            $params = $_GET;
                            $params['page'] = $targetPage;
                            return 'scroll-archive.php?' . http_build_query($params);
                        };
                        ?>

                        <?php if ($totalPages > 1): ?>
                            <nav class="sn-archive-pagination mt-4 mt-md-5" aria-label="Scroll Archive pagination">
                                <ul class="pagination justify-content-center flex-wrap">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                        href="<?php echo $page > 1 ? htmlspecialchars($buildArchivePageUrl($page - 1), ENT_QUOTES, 'UTF-8') : '#'; ?>"
                                        aria-label="Previous"
                                        <?php echo $page > 1 ? 'data-loading' : 'tabindex="-1" aria-disabled="true"'; ?>>
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>

                                    <?php if ($startPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo htmlspecialchars($buildArchivePageUrl(1), ENT_QUOTES, 'UTF-8'); ?>" data-loading>1</a>
                                        </li>
                                        <?php if ($startPage > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                                        <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?php echo htmlspecialchars($buildArchivePageUrl($p), ENT_QUOTES, 'UTF-8'); ?>" data-loading>
                                                <?php echo $p; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($endPage < $totalPages): ?>
                                        <?php if ($endPage < $totalPages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo htmlspecialchars($buildArchivePageUrl($totalPages), ENT_QUOTES, 'UTF-8'); ?>" data-loading>
                                                <?php echo $totalPages; ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                        href="<?php echo $page < $totalPages ? htmlspecialchars($buildArchivePageUrl($page + 1), ENT_QUOTES, 'UTF-8') : '#'; ?>"
                                        aria-label="Next"
                                        <?php echo $page < $totalPages ? 'data-loading' : 'tabindex="-1" aria-disabled="true"'; ?>>
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>

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
        <script src="/assets/js/scripts.js"></script>
        <script src="/assets/js/sn_history.js"></script>
        <script src="/assets/js/pages/scroll-archive.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/pages/scroll-archive.js'); ?>"></script>
    </body>
</html>
