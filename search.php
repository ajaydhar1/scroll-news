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

if ($q !== '') {
    try {
        // Basic keyword search on title (and optionally summary/description if you have it)
        $sql = "
            SELECT
                ri.id,
                ri.title,
                ri.link,
                ri.pub_date,
                ri.media_url,
                f.name AS feed_name,
                -- Adjust this join target if your articles table uses a different URL column:
                a.id AS article_id
            FROM rss_items ri
            JOIN feeds f
              ON f.id = ri.feed_id
            LEFT JOIN articles a
              ON a.url = ri.link  -- <— CHANGE to a.link = ri.link or a.rss_item_id = ri.id if that’s your schema
            WHERE
                ri.title ILIKE :q
                -- If you have a summary/description column, uncomment one of these:
                -- OR ri.summary ILIKE :q
                OR ri.description ILIKE :q
            ORDER BY
                ri.pub_date DESC NULLS LAST,
                ri.id DESC
            LIMIT 100
        ";

        // Assumes $pdo (PDO) is available
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':q' => '%' . $q . '%']);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {
        // Fail quietly in production, or log $e->getMessage()
        $errorMsg = 'There was a problem running your search.';
    }
}

function sn_format_pub_date(?string $raw): string {
    if (empty($raw)) {
        return '';
    }

    $ts = strtotime($raw);
    if ($ts === false) {
        return '';
    }

    // Prefer the shared helper if it exists so we stay consistent with newsroom masthead
    if (function_exists('format_news_date')) {
        return format_news_date($ts, 'America/New_York');
    }

    // Fallback: simple local-ish formatting
    return date('M j, Y • g:i A', $ts);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php // require_once __DIR__ . '/___google-analytics.php'; ?>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <title>Search • Scroll News</title>

    <!-- You can reuse whatever CSS/JS includes you use on the newsroom page -->
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="css/styles.css" rel="stylesheet" />
    <link href="css/custom.css" rel="stylesheet" />
</head>
<body class="bg-light">

    <!-- Simple top bar; replace with your real navbar include if you have one -->
    <nav class="navbar navbar-expand-md navbar-light bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand" href="/index.php">
                Scroll News
            </a>
            <span class="navbar-text d-none d-md-inline">
                Search
            </span>
        </div>
    </nav>

    <main class="container py-4">

        <!-- Search form -->
        <div class="row mb-4">
            <div class="col-lg-8 mx-auto">
                <form method="get" action="search.php" class="input-group">
                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Search headlines..."
                        value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                        aria-label="Search headlines"
                    />
                    <button class="btn btn-primary" type="submit">
                        Search
                    </button>
                </form>
                <div class="small text-muted mt-2">
                    Search across recent items from all feeds.
                </div>
            </div>
        </div>

        <?php if ($errorMsg): ?>
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($q === ''): ?>
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <p class="text-muted">
                        Type a keyword above to search headlines from your feeds.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8 mx-auto">

                    <h2 class="h6 mb-3">
                        Results for "<span class="fw-semibold"><?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?></span>"
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
                            // IMPORTANT: tweak this to match whatever Scroll History uses.
                            $analyzeUrl = null;
                            if ($hasNlp) {
                                $analyzeUrl = 'newsroom.php'
                                    . '?feed=' . urlencode($feedName)
                                    . '&id=' . (int)$row['article_id'];  // or &article_id=...
                            }
                            ?>

                            <div class="card mb-3 shadow-sm border-0">
                                <div class="card-body">
                                    <h5 class="card-title mb-1">
                                        <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                                    </h5>

                                    <div class="small text-muted mb-2">
                                        <?php if ($pubHuman): ?>
                                            <?php echo htmlspecialchars($pubHuman, ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                        <?php if ($feedName): ?>
                                            <?php if ($pubHuman): ?> • <?php endif; ?>
                                            <?php echo htmlspecialchars($feedName, ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="btn-group btn-group-sm" role="group">
                                        <!-- Always show Read story (publisher) -->
                                        <a
                                            href="<?php echo htmlspecialchars($readUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                            class="btn btn-outline-secondary"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            Read story
                                        </a>

                                        <!-- Only show Analyze if NLP/article exists -->
                                        <?php if ($hasNlp && $analyzeUrl): ?>
                                            <a
                                                href="<?php echo htmlspecialchars($analyzeUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                class="btn btn-primary"
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

    </main>
</body>
</html>
