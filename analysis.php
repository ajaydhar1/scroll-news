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
    :require_nlp_ok,
    :require_status_ok
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
      OR (a.nlp IS NOT NULL AND (a.nlp::jsonb ? 'entities'))
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
WITH topic_sums AS (
  SELECT topic, sum(weight) AS weight_sum
  FROM topics
  WHERE weight IS NOT NULL
  GROUP BY 1
),
ranked AS (
  SELECT
    topic,
    weight_sum,
    row_number() OVER (ORDER BY weight_sum DESC, topic) AS rn
  FROM topic_sums
)
SELECT
  CASE WHEN rn <= 8 THEN topic ELSE 'Other' END AS topic_bucket,
  round(sum(weight_sum), 4) AS weight_sum
FROM ranked
GROUP BY 1
ORDER BY
  CASE WHEN topic_bucket='Other' THEN 9999 ELSE 1 END,
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
LIMIT 50;
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
  <title>Scroll News — Analysis</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    /* minimal styling; swap for your site CSS */
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; margin: 18px; }
    .row { display: flex; gap: 16px; flex-wrap: wrap; }
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
  </style>
</head>
<body>

<h1 style="margin:0 0 6px 0;">Analysis</h1>
<div class="note">
  Category: <strong><?= htmlspecialchars($category) ?></strong> |
  Window: <strong><?= htmlspecialchars($time_window) ?></strong> |
  Corpus: <strong><?= (int)($kpi[0]['corpus_articles'] ?? 0) ?></strong> articles
</div>

<div class="card" style="margin-top:12px;">
  <h3>Corpus KPIs</h3>
  <div class="kpis">
    <div class="kpi"><div class="label">Articles</div><div class="val"><?= (int)($kpi[0]['corpus_articles'] ?? 0) ?></div></div>
    <div class="kpi"><div class="label">From</div><div class="val"><?= htmlspecialchars($kpi[0]['corpus_min_pub_date'] ?? '') ?></div></div>
    <div class="kpi"><div class="label">To</div><div class="val"><?= htmlspecialchars($kpi[0]['corpus_max_pub_date'] ?? '') ?></div></div>
    <div class="kpi"><div class="label">Range</div><div class="val"><?= htmlspecialchars($kpi[0]['time_min'] ?? '') ?> → <?= htmlspecialchars($kpi[0]['time_max'] ?? '') ?></div></div>
  </div>
</div>

<div class="row" style="margin-top:12px;">
  <div class="card" style="flex:1; min-width:320px;">
    <h3>Top Topics</h3>
    <table>
      <thead><tr><th>Topic</th><th>Weight</th></tr></thead>
      <tbody>
      <?php foreach ($topics_chart as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['topic_bucket'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['weight_sum'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card" style="flex:1; min-width:320px;">
    <h3>Top Sources (Domains)</h3>
    <table>
      <thead><tr><th>Domain</th><th>Articles</th><th>%</th></tr></thead>
      <tbody>
      <?php foreach ($sources as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['domain'] ?? '') ?></td>
          <td><?= (int)($r['articles'] ?? 0) ?></td>
          <td><?= htmlspecialchars($r['pct'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="row" style="margin-top:12px;">
  <div class="card" style="flex:1; min-width:320px;">
    <h3>Top Entities</h3>
    <table>
      <thead><tr><th>Entity</th><th>Label</th><th>Articles</th></tr></thead>
      <tbody>
      <?php foreach ($entities as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['entity_text'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['entity_label'] ?? '') ?></td>
          <td><?= (int)($r['articles'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card" style="flex:1; min-width:320px;">
    <h3>Sentiment</h3>
    <table>
      <thead><tr><th>Label</th><th>Articles</th></tr></thead>
      <tbody>
      <?php foreach ($sentiment as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['sentiment_label'] ?? '') ?></td>
          <td><?= (int)($r['articles'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card" style="margin-top:12px;">
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

</body>
</html>
