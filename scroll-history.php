<?php
// scroll-history.php — "Daily Scroll" archive view for Scroll News

// If you already have a shared config/DB helper, include that instead:
# require_once __DIR__ . '/config.php';

require_once('___modules.php');

$pdo = _pdo_or_null();

// ----- Fetch all RSS items ordered by pub_date DESC -----
// Adjust column names if your schema is different.
$sql = "
    SELECT id, title, link, pub_date, publisher, media_url
    FROM rss_items
    WHERE pub_date IS NOT NULL
    ORDER BY pub_date DESC, id DESC
";
$stmt = $pdo->query($sql);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----- Group items by date (Y-m-d) -----
$days = [];
foreach ($items as $item) {
    $dt = new DateTime($item['pub_date']);
    $dateKey = $dt->format('Y-m-d');
    if (!isset($days[$dateKey])) {
        $days[$dateKey] = [];
    }
    $days[$dateKey][] = $item;
}

// Optional: if you want newest days at top, keep as-is.
// If you ever want oldest → newest, you can ksort($days).

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Daily Scroll Archive — Scroll News</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- If you have shared CSS, include it here -->
    <!-- <link rel="stylesheet" href="/assets/css/app.css"> -->

    <style>
        :root {
            --scroll-bg: #f5f5f7;
            --card-bg: #ffffff;
            --card-border: rgba(0, 0, 0, 0.06);
            --card-border-hover: rgba(0, 0, 0, 0.14);
            --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            --card-shadow-hover: 0 10px 30px rgba(0, 0, 0, 0.18);
            --text-muted: #6b6b7a;
            --accent: #3800ff; /* adjust to Scroll News brand */
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--scroll-bg);
            color: #111;
        }

        .page-shell {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem 1.5rem 3rem;
        }

        .page-header {
            position: sticky;
            top: 0;
            z-index: 20;
            padding: 0.75rem 1rem;
            margin: -1.5rem -1.5rem 1rem;
            background: linear-gradient(to bottom, rgba(245,245,247,0.96), rgba(245,245,247,0.9), rgba(245,245,247,0));
            backdrop-filter: blur(10px);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .page-subtitle {
            margin: 0.25rem 0 0;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .day-row {
            padding: 1.5rem 0 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .day-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.75rem;
            margin: 0 0 0.5rem;
        }

        .day-title {
            font-weight: 600;
            font-size: 1rem;
        }

        .day-meta {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .articles-track {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding: 0.25rem 0 0.75rem;
            scroll-snap-type: x mandatory;
            /* Hide ugly scrollbars (optional, browser-dependent) */
            -webkit-overflow-scrolling: touch;
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
        .day-row:nth-of-type(odd) .articles-track {
            margin-left: 0;
            margin-right: 2rem;
        }

        .day-row:nth-of-type(even) .articles-track {
            margin-left: 2rem;
            margin-right: 0;
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
            position: relative;
            overflow: hidden;
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.7rem;
            border-radius: 999px;
            border: 1px solid var(--accent);
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--accent);
            text-decoration: none;
            background: rgba(56, 0, 255, 0.04);
        }

        .btn-analyze:hover {
            background: rgba(56, 0, 255, 0.08);
        }

        .link-read {
            font-size: 0.78rem;
            color: inherit;
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

        /* Keyboard focus visibility */
        .article-card a:focus-visible,
        .article-card button:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }

        @media (max-width: 768px) {
            .page-shell {
                padding-inline: 1rem;
            }

            .page-header {
                margin-inline: -1rem;
            }

            .day-row:nth-of-type(even) .articles-track {
                margin-left: 1rem;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
<div class="page-shell">

    <header class="page-header">
        <h1 class="page-title">Daily Scroll Archive</h1>
        <p class="page-subtitle">
            Flip through every article Scroll News has captured — day by day, row by row.
        </p>
    </header>

    <?php if (empty($days)): ?>
        <p>No articles found in the archive yet.</p>
    <?php else: ?>
        <?php foreach ($days as $dateKey => $articles): ?>
            <?php
                $dt = DateTime::createFromFormat('Y-m-d', $dateKey);
                $friendlyDate = $dt ? $dt->format('F j, Y') : htmlspecialchars($dateKey);
                $count = count($articles);
            ?>
            <section class="day-row">
                <div class="day-header">
                    <h2 class="day-title"><?php echo $friendlyDate; ?></h2>
                    <div class="day-meta">
                        <?php echo $count; ?> article<?php echo $count !== 1 ? 's' : ''; ?>
                    </div>
                </div>

                <div class="articles-track">
                    <?php foreach ($articles as $item): ?>
                        <?php
                            $title = $item['title'] ?? '';
                            $publisher = $item['publisher'] ?? '';
                            $url = $item['link'] ?? '#';
                            $mediaUrl = $item['media_url'] ?? '';
                            $pubDt = new DateTime($item['pub_date']);
                            $pubTime = $pubDt->format('g:i A');
                        ?>
                        <article class="article-card">
                            <div class="article-image-wrap">
                                <?php if (!empty($mediaUrl)): ?>
                                    <img src="<?php echo htmlspecialchars($mediaUrl); ?>" alt="">
                                <?php else: ?>
                                    <!-- Simple placeholder if no image -->
                                    <img src="https://via.placeholder.com/400x225?text=Scroll+News" alt="">
                                <?php endif; ?>
                            </div>
                            <div class="article-body">
                                <h3 class="article-title">
                                    <?php echo htmlspecialchars($title); ?>
                                </h3>
                                <div class="article-meta">
                                    <?php if (!empty($publisher)): ?>
                                        <?php echo htmlspecialchars($publisher); ?>
                                        &middot;
                                    <?php endif; ?>
                                    <?php echo $pubTime; ?>
                                </div>
                                <div class="article-actions">
                                    <!-- Adjust this to your actual analyze endpoint -->
                                    <a class="btn-analyze"
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
            </section>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</body>
</html>
