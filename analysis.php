<?php
// analysis.php
// Category Analysis page (Pass 1): KPIs, Timeseries, Topics, Entities, Sentiment, Sources, Articles

if (!function_exists('_pdo_or_null')) {
    require_once __DIR__ . '/___modules.php';
}

$ANALYSIS_DEBUG = true; // toggle while building

$analysisFail = function(string $msg, ?Throwable $e = null) use ($ANALYSIS_DEBUG) {
    error_log('[Analysis] ' . $msg . ($e ? (' | ' . $e->getMessage()) : ''));
    if ($ANALYSIS_DEBUG) {
        echo '<div class="alert alert-warning small" style="margin:10px 0;">';
        echo '<strong>Analysis error:</strong> ' . htmlspecialchars($msg);
        if ($e) echo '<br><code>' . htmlspecialchars($e->getMessage()) . '</code>';
        echo '</div>';
    }
};

$allowed_categories = ['politics','sports','business','tech','science','health','entertainment']; // adjust to your slugs
$allowed_windows = ['24h','7d','30d','custom'];

$get = function(string $k, $default = null) {
    return isset($_GET[$k]) ? trim((string)$_GET[$k]) : $default;
};

$category = $get('category', 'politics');
if (!in_array($category, $allowed_categories, true)) $category = 'politics';

$time_window = $get('w', '7d');
if (!in_array($time_window, $allowed_windows, true)) $time_window = '7d';

// Only used if w=custom
$custom_from = $get('from', null); // expected like '2026-02-01'
$custom_to   = $get('to', null);   // expected like '2026-02-07'

// Hygiene toggles
$require_nlp_ok    = 1;
$require_status_ok = 1;

try {
    $db = _pdo_or_null();
    if (!$db) throw new Exception("DB handle not available");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ---- Shared scaffold SQL (category-based) ----
    // IMPORTANT: this ends with "-- FINAL SELECT" marker so we can append per-module SELECTs.
    $SCAFFOLD = <<<SQL
WITH
params (category_slug, time_window, custom_from, custom_to, require_nlp_ok, require_status_ok) AS (
  VALUES (
    :category_slug,
    :time_window,
    CAST(:custom_from AS timestamptz),
    CAST(:custom_to   AS timestamptz),
    CAST(:require_nlp_ok AS int),
    CAST(:require_status_ok AS int)
  )
),
bounds AS (
  SELECT
    p.*,
    CASE p.time_window
      WHEN '24h' THEN now() - interval '24 hours'
      WHEN '7d'  THEN now() - interval '7 days'
      WHEN '30d' THEN now() - interval '30 days'
      WHEN 'custom' THEN p.custom_from
      ELSE now() - interval '7 days'
    END AS time_min,
    CASE p.time_window
      WHEN 'custom' THEN p.custom_to
      ELSE now()
    END AS time_max
  FROM params p
),
base_articles AS (
  SELECT
    a.id,
    a.pub_date,
    a.source_slug,
    a.title,
    a.url,
    a.description,
    a.author,
    a.nlp
  FROM articles a
  CROSS JOIN bounds b
  WHERE a.pub_date >= b.time_min
    AND a.pub_date <  b.time_max
    AND a.source_slug = b.category_slug
    AND (b.require_status_ok = 0 OR a.status = 'ok')
    AND (
      b.require_nlp_ok = 0
      OR (a.nlp IS NOT NULL AND jsonb_exists(a.nlp::jsonb, 'entities'))
    )
),
domainized AS (
  SELECT
    b.*,
    lower(
      regexp_replace(
        split_part(split_part(b.url, '://', 2), '/', 1),
        '^www\.',
        ''
      )
    ) AS domain,
    COALESCE(b.nlp::jsonb #>> '{sentiment,label}', 'unknown') AS sentiment_label,
    NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric AS sentiment_score
  FROM base_articles b
),
topics AS (
  SELECT
    d.id AS article_id,
    d.pub_date,
    d.domain,
    t.key AS topic,
    NULLIF(t.value #>> '{}','')::numeric AS weight
  FROM domainized d
  CROSS JOIN LATERAL jsonb_each(
    CASE
      WHEN jsonb_typeof(d.nlp::jsonb->'topics') = 'object'
        THEN d.nlp::jsonb->'topics'
      ELSE '{}'::jsonb
    END
  ) t
),
entities AS (
  SELECT
    d.id AS article_id,
    d.pub_date,
    d.domain,
    lower(trim(e->>'text')) AS entity_text,
    e->>'label' AS entity_label
  FROM domainized d
  CROSS JOIN LATERAL jsonb_array_elements(
    CASE
      WHEN jsonb_typeof(d.nlp::jsonb->'entities') = 'array'
        THEN d.nlp::jsonb->'entities'
      ELSE '[]'::jsonb
    END
  ) e
  WHERE COALESCE(e->>'text','') <> ''
)
-- FINAL SELECT
SQL;

    // Common bound params
    // If custom dates not provided, still bind something parseable.
    $bind = [
        ':category_slug'      => $category,
        ':time_window'        => $time_window,
        ':custom_from'        => ($custom_from ? ($custom_from . ' 00:00:00-05') : '2000-01-01 00:00:00-05'),
        ':custom_to'          => ($custom_to   ? ($custom_to   . ' 23:59:59-05') : '2099-01-01 00:00:00-05'),
        ':require_nlp_ok'     => $require_nlp_ok,
        ':require_status_ok'  => $require_status_ok,
    ];

    $run = function(string $finalSelectSql) use ($db, $SCAFFOLD, $bind) {
        $sql = $SCAFFOLD . "\n" . $finalSelectSql;
        $stmt = $db->prepare($sql);
        foreach ($bind as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    };

    // ---- Module queries ----

    $kpi = $run(<<<SQL
SELECT
  (SELECT category_slug FROM bounds) AS category_slug,
  (SELECT time_window FROM bounds)   AS time_window,
  (SELECT time_min FROM bounds)      AS time_min,
  (SELECT time_max FROM bounds)      AS time_max,
  (SELECT count(*) FROM base_articles) AS corpus_articles,
  (SELECT min(pub_date) FROM base_articles) AS corpus_min_pub_date,
  (SELECT max(pub_date) FROM base_articles) AS corpus_max_pub_date
;
SQL);

    if (!$kpi) {
        $analysisFail('KPI query returned 0 rows.');
        return;
    }

    $corpus_count = (int)($kpi[0]['corpus_articles'] ?? 0);

    // Optional: small-corpus fallback (auto widen to 7d)
    if ($corpus_count < 10 && $time_window === '24h') {
        $time_window = '7d';
        $bind[':time_window'] = '7d';
        $kpi = $run(<<<SQL
SELECT
  (SELECT category_slug FROM bounds) AS category_slug,
  (SELECT time_window FROM bounds)   AS time_window,
  (SELECT time_min FROM bounds)      AS time_min,
  (SELECT time_max FROM bounds)      AS time_max,
  (SELECT count(*) FROM base_articles) AS corpus_articles,
  (SELECT min(pub_date) FROM base_articles) AS corpus_min_pub_date,
  (SELECT max(pub_date) FROM base_articles) AS corpus_max_pub_date
;
SQL);
        $corpus_count = (int)($kpi[0]['corpus_articles'] ?? 0);
    }

    $timeseries = $run(<<<SQL
SELECT
  CASE
    WHEN (SELECT time_window FROM bounds) = '24h' THEN date_trunc('hour', pub_date)
    ELSE date_trunc('day', pub_date)
  END AS bucket,
  count(*) AS articles
FROM base_articles
GROUP BY 1
ORDER BY 1;
SQL);

    $topics_chart = $run(<<<SQL
SELECT
  topic_bucket,
  round(sum(weight_sum), 4) AS weight_sum
FROM (
  SELECT
    CASE WHEN rn <= 8 THEN topic ELSE 'Other' END AS topic_bucket,
    weight_sum
  FROM (
    SELECT
      topic,
      weight_sum,
      row_number() OVER (ORDER BY weight_sum DESC, topic) AS rn
    FROM (
      SELECT
        topic,
        sum(weight) AS weight_sum
      FROM topics
      WHERE weight IS NOT NULL
      GROUP BY 1
    ) topic_sums
  ) ranked
) bucketed
GROUP BY 1
ORDER BY
  CASE WHEN topic_bucket = 'Other' THEN 9999 ELSE 1 END,
  weight_sum DESC;
SQL);

    $topics_table = $run(<<<SQL
SELECT
  topic,
  round(sum(weight), 4) AS weight_sum,
  count(DISTINCT article_id) AS articles_contributing
FROM topics
WHERE weight IS NOT NULL
GROUP BY 1
ORDER BY weight_sum DESC
LIMIT 25;
SQL);

    $sentiment = $run(<<<SQL
SELECT
  sentiment_label,
  count(*) AS articles
FROM domainized
GROUP BY 1
ORDER BY articles DESC;
SQL);

    $sources = $run(<<<SQL
SELECT
  domain,
  count(*) AS articles,
  round((count(*)::numeric / (SELECT count(*) FROM base_articles)) * 100, 2) AS pct
FROM domainized
WHERE domain IS NOT NULL AND domain <> ''
GROUP BY 1
ORDER BY articles DESC, domain
LIMIT 25;
SQL);

    $entities = $run(<<<SQL
SELECT
  entity_text,
  max(entity_label) AS entity_label,
  count(DISTINCT article_id) AS articles
FROM entities
GROUP BY 1
ORDER BY articles DESC, entity_text
LIMIT 25;
SQL);

    $articles = $run(<<<SQL
SELECT
  pub_date,
  domain,
  title,
  url,
  author,
  sentiment_label,
  sentiment_score
FROM domainized
ORDER BY pub_date DESC;
SQL);

} catch (Throwable $e) {
    $analysisFail('Unexpected error in Analysis page', $e);
    return;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <?php
    // Fallbacks
    $analysis_subject = ucfirst($category ?? 'Current News');
    $analysis_scope   = $time_window   ?? 'Overview';
    $site_name        = 'Scroll News';

    $meta_title = "{$analysis_subject} — {$analysis_scope} Analysis | {$site_name}";
  ?>
  <title><?= htmlspecialchars($meta_title) ?></title>

  <?php
    $meta_description = "In-depth analysis of {$analysis_subject}, breaking down dominant topics, sentiment trends, and media sources shaping the conversation.";
  ?>
  <meta name="description" content="<?= htmlspecialchars($meta_description) ?>">

  <meta name="author" content="Scroll News" />

  <!-- Favicon-->
  <link rel="icon" type="image/png" href="assets/img/play-green.png" />


  <!-- Font Awesome icons (free version)-->
  <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous"></script>
  <!-- Google fonts-->
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css" />
  <link href="https://fonts.googleapis.com/css?family=Droid+Serif:400,700,400italic,700italic" rel="stylesheet" type="text/css" />
  <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700" rel="stylesheet" type="text/css" />


  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700&family=Lato&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600&family=Inter&display=swap" rel="stylesheet">


  <!-- Core theme CSS (includes Bootstrap)-->
  <link href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>" rel="stylesheet" />
  <link href="css/custom.css?v=<?php echo filemtime(__DIR__ . '/css/custom.css'); ?>" rel="stylesheet" />

  <style>
    /* minimal styling; swap for your site CSS */
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; }
    .row { display: flex; flex-wrap: wrap; }
    .card { border: 1px solid #ddd; border-radius: 10px; padding: 12px; background: #fff; }
    .card h3 { margin: 0 0 8px 0; font-size: 16px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border-bottom: 1px solid #eee; padding: 6px 8px; text-align: left; font-size: 13px; }
    th { font-weight: 650; }
    .kpis { display: grid; grid-template-columns: repeat(4, minmax(160px, 1fr)); gap: 10px; }
    .kpi { border: 1px solid #eee; border-radius: 10px; padding: 10px; }
    .kpi .label { font-size: 12px; color: #666; }
    .kpi .val { font-size: 18px; font-weight: 700; }
    .note { font-size: 12px; color: #666; }
    
    .corpus a { color: #0000ee; }

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

    .card-eyebrow{
        font-size: 11px;
        color: #666;
        letter-spacing: .04em;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .bar-table .bar-row{
        position: relative;
    }

    .bar-table .bar-row::before{
        content:"";
        position:absolute;
        left: 6px;
        right: 6px;
        top: 3px;
        bottom: 3px;
        width: calc(var(--bar) - 12px); /* keeps padding feeling consistent */
        max-width: calc(100% - 12px);
        background: rgba(0,0,0,0.06);
        border-radius: 8px;
        z-index: 0;
    }

    .bar-table .bar-row td{
        position: relative;
        z-index: 1;
    }

    .bar-table .bar-row:hover::before{
        background: rgba(0,0,0,0.09);
    }

    .bar-cell{
        position: relative;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .bar-cell::before{
        content:"";
        position:absolute;
        left: 6px;
        right: 6px;
        top: 4px;
        bottom: 4px;
        width: calc(var(--bar));
        max-width: calc(100% - 12px);
        background: rgba(0,0,0,0.06);
        border-radius: 8px;
        z-index: 0;
    }

    .bar-cell > span{
        position: relative;
        z-index: 1;
    }


  </style>
</head>
<body class="bg-light">

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

<!-- topnav-->
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
                <a class="btn btn-black btn-social mx-2" title="Control Room" href="control-room.php"><i class="fas fa-dashboard"></i></a>
            </div>
            <div class="col-lg-4 d-flex text-lg-right" style="">
                <div class="ml-auto">
                    <a href="about.php" class="mr-3">About</a>
                    <a class="search-button" href="search.php" title="Search" aria-label="Search">🔍</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<div class="container-fluid">

    <h1 style="margin:0 0 6px 0;" class="mt-3">Analysis</h1>
    <div class="note">
    Category: <strong><?= htmlspecialchars($category) ?></strong> |
    Window: <strong><?= htmlspecialchars($time_window) ?></strong> |
    Corpus: <strong><?= (int)($kpi[0]['corpus_articles'] ?? 0) ?></strong> articles
    </div>

    <div class="row">
        <div class="col-12 col-lg-12">
            <div class="card" style="margin-top:12px;">
                <h3>Corpus KPIs</h3>
                <div class="kpis">
                    <div class="kpi"><div class="label">Articles</div><div class="val"><?= (int)($kpi[0]['corpus_articles'] ?? 0) ?></div></div>
                    <div class="kpi"><div class="label">From</div><div class="val"><?= htmlspecialchars($kpi[0]['corpus_min_pub_date'] ?? '') ?></div></div>
                    <div class="kpi"><div class="label">To</div><div class="val"><?= htmlspecialchars($kpi[0]['corpus_max_pub_date'] ?? '') ?></div></div>
                    <div class="kpi"><div class="label">Range</div><div class="val"><?= htmlspecialchars($kpi[0]['time_min'] ?? '') ?> → <?= htmlspecialchars($kpi[0]['time_max'] ?? '') ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" style="margin-top:12px;">
    <div class="col-12 col-lg-6">
        <div class="card" style="flex:1; min-width:320px;">
            <div class="card-eyebrow">Who’s being talked about</div>
            <h3>Top Entities</h3>

            <?php
                // Canonicalize + dedupe common variants
                $canon = function(string $s): string {
                    $s = strtolower(trim($s));

                    // normalize punctuation + whitespace
                    $s = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $s); // remove punctuation like dots
                    $s = preg_replace('/\s+/', ' ', $s);

                    // common aliases
                    $map = [
                        'trump' => 'donald trump',
                        'donald j trump' => 'donald trump',
                        'president trump' => 'donald trump',

                        'us' => 'u.s.',
                        'usa' => 'u.s.',
                        'united states' => 'u.s.',
                        'united states of america' => 'u.s.',

                        'u s' => 'u.s.',

                        'republican' => 'republicans',
                        'democratic' => 'democrats',
                    ];

                    return $map[$s] ?? $s;
                };

                // Merge rows by canonical entity (sum articles; pick best label)
                $entityMap = [];
                foreach ($entities as $r) {
                    $raw = (string)($r['entity_text'] ?? '');
                    if ($raw === '') continue;

                    $key = $canon($raw);
                    $article_count = (int)($r['articles'] ?? 0);
                    $label = (string)($r['entity_label'] ?? '');

                    if (!isset($entityMap[$key])) {
                        $entityMap[$key] = [
                            'entity' => $key,
                            'label' => $label,
                            'articles' => $article_count,
                        ];
                    } else {
                        $entityMap[$key]['articles'] += $article_count;

                        // Prefer PERSON over other labels if mixed
                        if ($entityMap[$key]['label'] !== 'PERSON' && $label === 'PERSON') {
                            $entityMap[$key]['label'] = 'PERSON';
                        } elseif ($entityMap[$key]['label'] === '' && $label !== '') {
                            $entityMap[$key]['label'] = $label;
                        }
                    }
                }

                // Sort by articles desc
                $entities_deduped = array_values($entityMap);
                usort($entities_deduped, function($a, $b) {
                    return ($b['articles'] <=> $a['articles']) ?: strcmp($a['entity'], $b['entity']);
                });

                // Limit to 25 for nicer height balance (optional)
                $entities_deduped = array_slice($entities_deduped, 0, 25);

                // Max for bar scaling
                $max_articles = 0;
                foreach ($entities_deduped as $row) {
                    $max_articles = max($max_articles, (int)$row['articles']);
                }

                // Display casing helper (keep u.s. uppercase)
                $pretty = function(string $s): string {
                    //if ($s === 'u.s.') return 'U.S.';
                    //return ucwords($s);
                    
                    // don't uppercase
                    return $s;
                };
            ?>

            <table class="bar-table">
                <thead>
                <tr>
                    <th>Entity</th>
                    <th>Label</th>
                    <th style="text-align:right;">Articles</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($entities_deduped as $row):
                    $article_count = (int)$row['articles'];
                    $pctBar = ($max_articles > 0) ? round(($article_count / $max_articles) * 100, 2) : 0;
                ?>
                <tr class="bar-row" style="--bar: <?= $pctBar ?>%;">
                    <td><?= htmlspecialchars($pretty($row['entity'])) ?></td>
                    <td><?= htmlspecialchars($row['label'] ?: '—') ?></td>
                    <td style="text-align:right;"><?= $article_count ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card" style="flex:1; min-width:320px;">
            <div class="card-eyebrow">Who’s talking</div>
            <h3>Top Sources (Domains)</h3>

            <?php
                // Compute max for bar scaling
                $max_articles = 0;
                foreach ($sources as $r) {
                    $max_articles = max($max_articles, (int)($r['articles'] ?? 0));
                }
            ?>

            <table class="bar-table">
                <thead><tr><th>Domain</th><th style="text-align:right;">Articles</th><th style="text-align:right;">%</th></tr></thead>
                <tbody>
                <?php foreach ($sources as $r): 
                    $article_count = (int)($r['articles'] ?? 0);
                    $pctBar = ($max_articles > 0) ? round(($article_count / $max_articles) * 100, 2) : 0;
                ?>
                <tr class="bar-row" style="--bar: <?= $pctBar ?>%;">
                    <td><?= htmlspecialchars($r['domain'] ?? '') ?></td>
                    <td style="text-align:right;"><?= $article_count ?></td>
                    <td style="text-align:right;"><?= htmlspecialchars($r['pct'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <div class="row" style="margin-top:12px;">
    <div class="col-12 col-lg-6">
        <div class="card" style="flex:1; min-width:320px;">
            <div class="card-eyebrow">What’s being discussed</div>
            <h3>Top Topics</h3>

            <?php
                // Max weight for bar scaling
                $maxWeight = 0.0;
                foreach ($topics_chart as $r) {
                    $maxWeight = max($maxWeight, (float)($r['weight_sum'] ?? 0));
                }
            ?>

            <table>
                <thead>
                <tr>
                    <th>Topic</th>
                    <th style="text-align:right;">Weight</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($topics_chart as $r):
                    $w = (float)($r['weight_sum'] ?? 0);
                    $pctBar = ($maxWeight > 0) ? round(($w / $maxWeight) * 100, 2) : 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($r['topic_bucket'] ?? '') ?></td>
                    <td class="bar-cell" style="--bar: <?= $pctBar ?>%;">
                    <span><?= htmlspecialchars($r['weight_sum'] ?? '') ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card" style="flex:1; min-width:320px;">
            <div class="card-eyebrow">How it feels</div>
            <h3>Sentiment</h3>

            <?php
                // Max articles for bar scaling
                $maxSent = 0;
                foreach ($sentiment as $r) {
                    $maxSent = max($maxSent, (int)($r['articles'] ?? 0));
                }
            ?>

            <table>
                <thead>
                <tr>
                    <th>Label</th>
                    <th style="text-align:right;">Articles</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($sentiment as $r):
                    $v = (int)($r['articles'] ?? 0);
                    $pctBar = ($maxSent > 0) ? round(($v / $maxSent) * 100, 2) : 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($r['sentiment_label'] ?? '') ?></td>
                    <td class="bar-cell" style="--bar: <?= $pctBar ?>%;">
                    <span><?= $v ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-12">
            <div class="card corpus" style="margin-top:12px;">
                <h3>Articles included</h3>
                <table>
                    <thead>
                    <tr>
                        <th>Pub Date</th><th>Domain</th><th>Title</th><th>Author</th><th>Sent</th><th>Score</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($articles as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['pub_date'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['domain'] ?? '') ?></td>
                        <td>
                        <a href="<?= htmlspecialchars($r['url'] ?? '') ?>" target="_blank" rel="noopener">
                            <?= htmlspecialchars($r['title'] ?? '') ?>
                        </a>
                        </td>
                        <td><?= htmlspecialchars($r['author'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['sentiment_label'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['sentiment_score'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Footer-->        
<div class="bg-dark" style="height: 338px;">        
    <footer class="footer footer-bottom bg-white py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-4 text-lg-left">Copyright © Scroll News 2026</div>
                <div class="col-lg-4 my-3 my-lg-0">
                    <a class="btn btn-black btn-social mx-2" title="X profile" href="https://x.com/scrollnewsio" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
                    <a class="btn btn-black btn-social mx-2" title="History" href="scroll-history.php" data-loading><i class="fas fa-history"></i></a>
                    <a class="btn btn-green btn-social mx-2" title="Stumble through articles" href="newsroom.php" data-loading><i class="fas fa-play"></i></a>
                    <a class="btn btn-black btn-social mx-2" title="Control Room" href="control-room.php"><i class="fas fa-dashboard"></i></a>
                    <a class="btn btn-black btn-social mx-2" title="IG profile" href="https://www.instagram.com/scrollnewsio/" target="_blank"><i class="fa-brands fa-instagram"></i></a>
                </div>
                <div class="col-lg-4 text-lg-right font-weight-bold">
                    <a href="index.php" data-loading>scroll news</a>
                    <br>
                    <a href="about.php" class="text-muted small mr-3">About</a>
                    <a href="terms.php" class="text-muted small mr-3">Terms</a>
                    <a href="privacy.php" class="text-muted small">Privacy</a>
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

    function goToAnalytics() {
        if (!isMobile()) {
        //place script you don't want to run on mobile here
            $(".loader").fadeIn("slow");
        }
    }

    function isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

</script>

<script>
    (function(){
        const overlay = document.getElementById('loadingOverlay');
        const show = () => overlay && (overlay.hidden = false);
        const hide = () => overlay && (overlay.hidden = true);

        // Show spinner when navigating away (page links/forms)
        //window.addEventListener('beforeunload', show);

        // Hide when page is ready (covers BFCache too)
        window.addEventListener('pageshow', hide);

        // For specific buttons/links, add data-loading attribute
        document.addEventListener('click', function(e){
        const t = e.target.closest('[data-loading]');
        if (t) show();
        });

        // Optional: inline button spinner (keeps overlay too)
        document.addEventListener('click', function(e){
        const btn = e.target.closest('[data-loading-btn]');
        if (!btn) return;
        btn.dataset.originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span>&nbsp;Loading…';
        btn.classList.add('disabled'); btn.setAttribute('aria-busy','true');
        });

        // Minimal CSS for inline button spinner:
        const style = document.createElement('style');
        style.textContent = '.btn-spinner{display:inline-block;width:1em;height:1em;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:spin .6s linear infinite;vertical-align:-0.125em}';
        document.head.appendChild(style);
    })();
</script>

</body>
</html>
