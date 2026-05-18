<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . "/auth/includes/auth_bootstrap.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Use the Scroll News Control Room to fine-tune how you browse U.S. news, adjust filters, and explore tools for analyzing headlines, entities, and narrative trends." />
    <meta name="author" content="Scroll News" />
    <title>Scroll News Control Room – Tune Your Newsroom Tools</title>

    <!-- Favicon-->
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />
    <link rel="canonical" href="https://scrollnews.ai/control-room">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://scrollnews.ai/control-room.php" />
    <meta property="og:title" content="Scroll News Control Room — Tools & analysis" />
    <meta property="og:description" content="Access the Scroll News control room to experiment with article analysis, entity extraction, and other news intelligence tools." />
    <meta property="og:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-control-room-1200x630.png" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" content="https://scrollnews.ai/control-room.php" />
    <meta name="twitter:title" content="Scroll News Control Room — Tools & analysis" />
    <meta name="twitter:description" content="Access the Scroll News control room to experiment with article analysis, entity extraction, and other news intelligence tools." />
    <meta name="twitter:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-control-room-1200x630.png" />

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
    <link href="/assets/css/pages/control-room.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/control-room.css'); ?>" rel="stylesheet" />

</head>

<body id="page-top">

    <div class="page-container">

        <video autoplay muted loop playsinline id="myVideo">
            <source src="assets/videos/newsroom.mp4" type="video/mp4">
        </video>

        <!-- Top nav-->
        <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

        <div class="content">
            <div class="control-room-overlay">
                <div class="control-card reading-profile-card">
                    <div class="card-label">🧠 Reading Profile</div>
                    <h3>Your News Pattern</h3>
                    <p>You’ve analyzed <strong id="snTotalReads">0</strong> articles recently.</p>

                    <div class="mini-stat">
                        <span>Top Category</span>
                        <strong id="snTopCategory">—</strong>
                    </div>

                    <div class="mini-stat">
                        <span>Today</span>
                        <strong id="snReadsToday">0 articles</strong>
                    </div>

                    <div class="mini-stat">
                        <span>7-Day Activity</span>
                        <strong id="snReadsWeek">0 articles</strong>
                    </div>

                    <div class="mini-stat">
                        <span>Peak Activity</span>
                        <strong id="snPeakActivity">—</strong>
                    </div>

                    <div class="mini-stat">
                        <span>Reader Type</span>
                        <strong id="snReaderType">—</strong>
                    </div>

                    <div class="mini-stat">
                        <span>Coverage Diversity</span>
                        <strong id="snDiversity">—</strong>
                    </div>

                    <div class="mini-stat">
                        <span>Signal Strength</span>
                        <strong id="snSignalStrength">—</strong>
                    </div>

                    <div class="mini-stat">
                        <span>Last Signal</span>
                        <strong id="snLastArticle">—</strong>
                    </div>
                </div>
            </div>

            <h1 class="text-white">Stumble Through the News</h1>
            <h2 class="brand-text">Smart analytics. Fresh perspectives.</h2>

            <!-- Footer-->
            <?php require_once BASE_PATH . '/views/partials/___footer_control_room.php'; ?>

        </div>
    </div>

    <!-- Modals-->
    <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

    <!-- Core JS (Bootstrap 4 requires jQuery first) -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" defer></script>

    <script>
        (function() {
            const history = JSON.parse(localStorage.getItem('sn_article_history') || '[]');

            const $ = (id) => document.getElementById(id);

            const totalReads = history.length;
            const now = new Date();

            const todayKey = now.toISOString().slice(0, 10);
            const sevenDaysAgo = new Date(now);
            sevenDaysAgo.setDate(now.getDate() - 7);

            const readsToday = history.filter(item =>
                item.clicked_at && item.clicked_at.slice(0, 10) === todayKey
            ).length;

            const readsWeek = history.filter(item =>
                item.clicked_at && new Date(item.clicked_at) >= sevenDaysAgo
            ).length;

            const categoryCounts = {};
            const hourCounts = {};

            history.forEach(item => {
                const category = item.source || 'Unknown';
                categoryCounts[category] = (categoryCounts[category] || 0) + 1;

                if (item.clicked_at) {
                    const hour = new Date(item.clicked_at).getHours();
                    hourCounts[hour] = (hourCounts[hour] || 0) + 1;
                }
            });

            const topCategory = Object.entries(categoryCounts)
                .sort((a, b) => b[1] - a[1])[0];

            const uniqueCategories = Object.keys(categoryCounts).length;

            const peakHour = Object.entries(hourCounts)
                .sort((a, b) => b[1] - a[1])[0];

            function formatPeakActivity(hour) {
                if (hour === undefined) return '—';

                hour = Number(hour);

                if (hour >= 5 && hour < 12) return 'Morning';
                if (hour >= 12 && hour < 17) return 'Afternoon';
                if (hour >= 17 && hour < 21) return 'Evening';
                return 'Late Night';
            }

            function getReaderType() {
                if (totalReads >= 20 && uniqueCategories >= 5) return 'Narrative Explorer';
                if (totalReads >= 15 && readsWeek >= 10) return 'Signal Hunter';
                if (uniqueCategories <= 2 && totalReads >= 8) return 'Focused Specialist';
                if (readsToday >= 5) return 'Active Scanner';
                if (totalReads > 0) return 'Casual Observer';
                return 'New Reader';
            }

            function getDiversity() {
                if (uniqueCategories >= 6) return 'Wide';
                if (uniqueCategories >= 3) return 'Balanced';
                if (uniqueCategories >= 1) return 'Focused';
                return '—';
            }

            function getSignalStrength() {
                if (readsToday >= 8 || readsWeek >= 20) return 'High';
                if (readsToday >= 3 || readsWeek >= 8) return 'Medium';
                if (totalReads > 0) return 'Low';
                return '—';
            }

            const latest = history[0];

            $('snTotalReads').textContent = totalReads;
            $('snReadsToday').textContent = `${readsToday} article${readsToday === 1 ? '' : 's'}`;
            $('snReadsWeek').textContent = `${readsWeek} article${readsWeek === 1 ? '' : 's'}`;
            $('snTopCategory').textContent = topCategory ? `${topCategory[0]} (${topCategory[1]})` : '—';
            $('snPeakActivity').textContent = peakHour ? formatPeakActivity(peakHour[0]) : '—';
            $('snReaderType').textContent = getReaderType();
            $('snDiversity').textContent = getDiversity();
            $('snSignalStrength').textContent = getSignalStrength();
            $('snLastArticle').textContent = latest?.title || '—';
        })();
    </script>

</body>

</html>