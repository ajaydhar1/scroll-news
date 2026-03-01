<?php
// includes/analysis_modules.php

declare(strict_types=1);

function analysis_run_modules(PDO $db, string $SCAFFOLD, array $bind): array
{
    $run = function (string $finalSelectSql) use ($db, $SCAFFOLD, $bind): array {
        $sql = $SCAFFOLD . "\n" . $finalSelectSql;
        $stmt = $db->prepare($sql);
        foreach ($bind as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    };

    // ================================
    // KPI
    // ================================
    $kpi = $run(<<<SQL
SELECT
  (SELECT ctx  FROM bounds) AS context,
  (SELECT val  FROM bounds) AS value,
  (SELECT time_window FROM bounds) AS time_window,
  (SELECT time_min    FROM bounds) AS time_min,
  (SELECT time_max    FROM bounds) AS time_max,
  (SELECT count(*)    FROM base_articles) AS corpus_articles,
  (SELECT min(pub_date) FROM base_articles) AS corpus_min_pub_date,
  (SELECT max(pub_date) FROM base_articles) AS corpus_max_pub_date
;
SQL);

    if (!$kpi) {
        return [];
    }

    // ================================
    // Timeseries
    // ================================
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

    // ================================
    // Topics chart
    // ================================
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
      SELECT topic, sum(weight) AS weight_sum
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

    // ================================
    // Topics table
    // ================================
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

    // ================================
    // Sentiment
    // ================================
    $sentiment = $run(<<<SQL
SELECT
  sentiment_label,
  count(*) AS articles
FROM domainized
GROUP BY 1
ORDER BY articles DESC;
SQL);

    // ================================
    // Sources
    // ================================
    $sources = $run(<<<SQL
SELECT
  domain,
  count(*) AS articles,
  round((count(*)::numeric / NULLIF((SELECT count(*) FROM base_articles),0)) * 100, 2) AS pct
FROM domainized
WHERE domain IS NOT NULL AND domain <> ''
GROUP BY 1
ORDER BY articles DESC, domain
LIMIT 25;
SQL);

    // ================================
    // Entities
    // ================================
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

    // ================================
    // Articles
    // ================================
    $articles = $run(<<<SQL
SELECT
  pub_date,
  source_slug,
  domain,
  title,
  url,
  author,
  sentiment_label,
  sentiment_score,
  nlp
FROM domainized
ORDER BY pub_date DESC;
SQL);

    return [
        'kpi'           => $kpi,
        'timeseries'    => $timeseries,
        'topics_chart'  => $topics_chart,
        'topics_table'  => $topics_table,
        'sentiment'     => $sentiment,
        'sources'       => $sources,
        'entities'      => $entities,
        'articles'      => $articles,
    ];
}