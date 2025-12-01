<?php
// scroll-history.php — "Daily Scroll" archive view for Scroll News

require_once('___modules.php');

$pdo = _pdo_or_null();

// ----- Fetch all RSS items ordered by pub_date DESC -----
$sql = "
    SELECT id, title, link, pub_date, media_url
    FROM rss_items
    WHERE pub_date IS NOT NULL
    ORDER BY pub_date DESC, id DESC
";
$stmt  = $pdo->query($sql);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----- Group items by date (Y-m-d) -----
// (Using pub_date as-is; you've already confirmed it looks correct.)
$days = [];
foreach ($items as $item) {
    if (empty($item['pub_date'])) {
        continue;
    }

    $dt = new DateTime($item['pub_date']);
    $item['_dt'] = $dt; // store for later display

    $dateKey = $dt->format('Y-m-d');
    if (!isset($days[$dateKey])) {
        $days[$dateKey] = [];
    }
    $days[$dateKey][] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Daily Scroll Archive — Scroll News</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap CSS (remove if you already include it globally) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <style>
        :root {
            --scroll-bg: #f5f5f7;
            --card-bg: #ffffff;
            --card-border: rgba(0, 0, 0, 0.06);
            --card-border-hover: rgba(0, 0, 0, 0.14);
            --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            --card-shadow-hover: 0 10px 30px rgba(0, 0, 0, 0.18);
            --text-muted: #6b6b7a;
            --accent: #3800ff; /* Scroll News brand color-ish */
        }

        body {
            background: var(--scroll-bg);
        }

        .page-header-blur {
            background: linear-gradient(
                to bottom,
                rgba(245,245,247,0.96),
                rgba(245,245,247,0.9),
                rgba(245,245,247,0)
            );
            backdrop-filter: blur(10px);
        }

        .day-row {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
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
            background: var(--card-bg);
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--card-border);
            display: flex;
            flex-direction: column;
            transition:
                transform 150ms ease-out,
                box-shadow 150ms ease-out,
                border-color 150ms ease-out;
        }

        .article-card:hover,
        .article-card:focus-within {
            transform: translateY(-4px) scale(1.02);
            box-shadow: var(--card-shadow-hover);
            border-color: var(--card-border-hover);
        }

        .article-image-wrap {
            background: #e1e1e8;
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
            color: var(--text-muted);
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

        .article-image-wrap {
            background: #e1e1e8;
            position: relative; /* add this */
        }

        .domain-chip {
            position: absolute;
            left: 0.5rem;
            bottom: 0.5rem;
            padding: 0.18rem 0.55rem;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.72);
            color: #f8f9fa;
            font-size: 0.68rem;
            font-weight: 500;
            letter-spacing: 0.02em;
            max-width: 70%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

    </style>
</head>
<body>

<div class="container-fluid py-3">

    <!-- Sticky header -->
    <header class="page-header-blur sticky-top pb-2 mb-3 border-bottom">
        <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between px-2 px-sm-3">
            <div>
                <h1 class="h4 mb-1 fw-bold">Daily Scroll Archive</h1>
                <p class="text-muted small mb-0">
                    Flip through every article Scroll News has captured — day by day, row by row.
                </p>
            </div>
        </div>
    </header>

    <?php if (empty($days)): ?>
        <p class="text-muted px-2">No articles found in the archive yet.</p>
    <?php else: ?>
        <?php $rowIndex = 0; ?>
        <?php foreach ($days as $dateKey => $articles): ?>
            <?php
                $rowIndex++;
                $trackId = 'articles-track-' . $rowIndex;
                $dt = DateTime::createFromFormat('Y-m-d', $dateKey);
                $friendlyDate = $dt ? $dt->format('F j, Y') : htmlspecialchars($dateKey);
                $count = count($articles);
            ?>
            <section class="day-row py-3">
                <div class="row gx-2 gx-sm-3 mb-2 px-2 px-sm-3">
                    <div class="col-12 d-flex justify-content-between align-items-baseline">
                        <h2 class="h6 mb-0 fw-semibold"><?php echo $friendlyDate; ?></h2>
                        <div class="text-muted small">
                            <?php echo $count; ?> article<?php echo $count !== 1 ? 's' : ''; ?>
                        </div>
                    </div>
                </div>

                <div class="position-relative articles-track-wrapper px-4">
                    <!-- Scroll buttons -->
                    <button class="scroll-btn scroll-btn-left"
                            type="button"
                            data-track="<?php echo $trackId; ?>"
                            data-direction="left">
                        ‹
                    </button>
                    <button class="scroll-btn scroll-btn-right"
                            type="button"
                            data-track="<?php echo $trackId; ?>"
                            data-direction="right">
                        ›
                    </button>

                    <!-- Horizontal track -->
                    <div class="articles-track py-2" id="<?php echo $trackId; ?>">
                        <?php foreach ($articles as $item): ?>
                            <?php
                                $title    = $item['title'] ?? '';
                                $url      = $item['link'] ?? '#';
                                $mediaUrl = $item['media_url'] ?? '';
                                $pubDt    = $item['_dt'] ?? null;
                                $pubTime  = $pubDt ? $pubDt->format('g:i A') : '';

                                // Extract domain for chip (like the scroll strip)
                                $domain = '';
                                if (!empty($url) && $url !== '#') {
                                    $host = parse_url($url, PHP_URL_HOST);
                                    if ($host) {
                                        // Strip leading www.
                                        if (strpos($host, 'www.') === 0) {
                                            $host = substr($host, 4);
                                        }
                                        $domain = $host;
                                    }
                                }
                            ?>
                            <article class="article-card">
                                <div class="article-image-wrap">
                                    <?php if (!empty($mediaUrl)): ?>
                                        <img src="<?php echo htmlspecialchars($mediaUrl); ?>" alt="" onerror="this.onerror=null;this.src='https://via.placeholder.com/400x225?text=Scroll+News';">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/400x225?text=Scroll+News" alt="">
                                    <?php endif; ?>

                                    <?php if (!empty($domain)): ?>
                                        <div class="domain-chip">
                                            <?php echo htmlspecialchars($domain); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="article-body">
                                    <h3 class="article-title mb-0">
                                        <?php echo htmlspecialchars($title); ?>
                                    </h3>
                                    <div class="article-meta">
                                        <?php echo $pubTime; ?>
                                    </div>
                                    <div class="article-actions">
                                        <a class="btn btn-outline-primary btn-analyze"
                                           href="<?php echo '/analyze.php?id=' . urlencode($item['id']); ?>">
                                            Analyze
                                        </a>
                                        <a class="link-read"
                                           href="<?php echo htmlspecialchars($url); ?>"
                                           target="_blank"
                                           rel="noopener noreferrer">
                                            <span>Read story</span>
                                            <span class="icon">↗</span>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<!-- Optional: Bootstrap JS (only needed if you use other components) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

<script>
// Simple left/right scroll buttons for each articles-track
document.addEventListener('DOMContentLoaded', function () {
    const SCROLL_AMOUNT = 600; // px per click; tweak if you want

    document.querySelectorAll('.scroll-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const trackId = btn.getAttribute('data-track');
            const dir     = btn.getAttribute('data-direction');
            const track   = document.getElementById(trackId);
            if (!track) return;

            const delta = (dir === 'right' ? 1 : -1) * SCROLL_AMOUNT;

            track.scrollBy({
                left: delta,
                behavior: 'smooth'
            });
        });
    });
});
</script>

</body>
</html>
