<?php
// includes/analysis_scaffolds.php
//
// Stores the Analysis page SQL scaffolds (CTEs) by context.
// Each scaffold MUST end with the marker: "-- FINAL SELECT"
// so analysis.php can append a final SELECT block.
//
// Contexts: category | pub | sent | topic | entity

declare(strict_types=1);

function analysis_scaffold_for(string $context): ?string
{
    $context = strtolower(trim($context));

    switch ($context) {

        // ---------------------------------------------------------------------
        // category: value = source_slug (e.g. politics)
        // ---------------------------------------------------------------------
        case 'category':
            return <<<SQL
WITH
params (ctx, val, time_window, custom_from, custom_to, require_nlp_ok, require_status_ok) AS (
  VALUES (
    :context,
    :value,
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
    AND a.source_slug = b.val
    AND (b.require_status_ok = 0 OR a.status = 'ok')
    AND (b.require_nlp_ok = 0 OR a.nlp IS NOT NULL)
),
domainized AS (
  SELECT
    b.*,
    lower(
      regexp_replace(
        split_part(split_part(b.url, '://', 2), '/', 1),
        '^www\\.',
        ''
      )
    ) AS domain,

    -- Legacy label stored by NLP API (keep for debugging / reference)
    COALESCE(b.nlp::jsonb #>> '{sentiment,label}', 'unknown') AS sentiment_label,

    -- Raw score stored by NLP API
    NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric AS sentiment_score,

    -- ✅ New: bucket computed from score using your thresholds
    CASE
      WHEN NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '') IS NULL THEN 'unknown'
      WHEN (NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric) >= :sent_pos THEN 'positive'
      WHEN (NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric) <= :sent_neg THEN 'negative'
      ELSE 'neutral'
    END AS sentiment_bucket

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
      WHEN d.nlp IS NOT NULL AND jsonb_typeof(d.nlp::jsonb->'topics') = 'object'
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
      WHEN d.nlp IS NOT NULL AND jsonb_typeof(d.nlp::jsonb->'entities') = 'array'
        THEN d.nlp::jsonb->'entities'
      ELSE '[]'::jsonb
    END
  ) e
  WHERE COALESCE(e->>'text','') <> ''
)
-- FINAL SELECT
SQL;

        // ---------------------------------------------------------------------
        // pub: value = domain parsed from url (e.g. nytimes.com)
        // Matches the parsed domain in WHERE to avoid needing domainized first.
        // ---------------------------------------------------------------------
        case 'pub':
            return <<<SQL
WITH
params (ctx, val, time_window, custom_from, custom_to, require_nlp_ok, require_status_ok) AS (
  VALUES (
    :context,
    :value,
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
    AND lower(
      regexp_replace(
        split_part(split_part(a.url, '://', 2), '/', 1),
        '^www\\.',
        ''
      )
    ) = b.val
    AND (b.require_status_ok = 0 OR a.status = 'ok')
    AND (b.require_nlp_ok = 0 OR a.nlp IS NOT NULL)
),
domainized AS (
  SELECT
    b.*,
    lower(
      regexp_replace(
        split_part(split_part(b.url, '://', 2), '/', 1),
        '^www\\.',
        ''
      )
    ) AS domain,

    -- Legacy label stored by NLP API (keep for debugging / reference)
    COALESCE(b.nlp::jsonb #>> '{sentiment,label}', 'unknown') AS sentiment_label,

    -- Raw score stored by NLP API
    NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric AS sentiment_score,

    -- ✅ New: bucket computed from score using your thresholds
    CASE
      WHEN NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '') IS NULL THEN 'unknown'
      WHEN (NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric) >= :sent_pos THEN 'positive'
      WHEN (NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric) <= :sent_neg THEN 'negative'
      ELSE 'neutral'
    END AS sentiment_bucket

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
      WHEN d.nlp IS NOT NULL AND jsonb_typeof(d.nlp::jsonb->'topics') = 'object'
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
      WHEN d.nlp IS NOT NULL AND jsonb_typeof(d.nlp::jsonb->'entities') = 'array'
        THEN d.nlp::jsonb->'entities'
      ELSE '[]'::jsonb
    END
  ) e
  WHERE COALESCE(e->>'text','') <> ''
)
-- FINAL SELECT
SQL;

        // ---------------------------------------------------------------------
        // sent: value = sentiment label in nlp->sentiment->label
        // NLP must exist for sentiment-specific corpus
        // ---------------------------------------------------------------------
        case 'sent':
            return <<<SQL
WITH
params (ctx, val, time_window, custom_from, custom_to, require_nlp_ok, require_status_ok) AS (
  VALUES (
    :context,
    :value,
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
    AND (b.require_status_ok = 0 OR a.status = 'ok')
    AND (
      b.require_nlp_ok = 0
      OR (a.nlp IS NOT NULL)
    )
    AND a.nlp IS NOT NULL
),
domainized AS (
  SELECT
    b.*,
    lower(
      regexp_replace(
        split_part(split_part(b.url, '://', 2), '/', 1),
        '^www\\.',
        ''
      )
    ) AS domain,

    COALESCE(b.nlp::jsonb #>> '{sentiment,label}', 'unknown') AS sentiment_label,
    NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric AS sentiment_score,

    CASE
      WHEN NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '') IS NULL THEN 'unknown'
      WHEN (NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric) >= :sent_pos THEN 'positive'
      WHEN (NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric) <= :sent_neg THEN 'negative'
      ELSE 'neutral'
    END AS sentiment_bucket

  FROM base_articles b
  WHERE
    CASE
      WHEN NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '') IS NULL THEN 'unknown'
      WHEN (NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric) >= :sent_pos THEN 'positive'
      WHEN (NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric) <= :sent_neg THEN 'negative'
      ELSE 'neutral'
    END = b.val
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
      WHEN d.nlp IS NOT NULL AND jsonb_typeof(d.nlp::jsonb->'topics') = 'object'
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
      WHEN d.nlp IS NOT NULL AND jsonb_typeof(d.nlp::jsonb->'entities') = 'array'
        THEN d.nlp::jsonb->'entities'
      ELSE '[]'::jsonb
    END
  ) e
  WHERE COALESCE(e->>'text','') <> ''
)
-- FINAL SELECT
SQL;

        // ---------------------------------------------------------------------
        // topic: value = topic key in nlp->topics object
        // ---------------------------------------------------------------------
        case 'topic':
            return <<<SQL
WITH
params (ctx, val, time_window, custom_from, custom_to, require_nlp_ok, require_status_ok) AS (
  VALUES (
    :context,
    :value,
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
    AND (b.require_status_ok = 0 OR a.status = 'ok')
    AND (
      b.require_nlp_ok = 0
      OR (a.nlp IS NOT NULL)
    )
    AND a.nlp IS NOT NULL
    AND jsonb_typeof(a.nlp::jsonb->'topics') = 'object'
    AND jsonb_exists(a.nlp::jsonb->'topics', b.val)
),
domainized AS (
  SELECT
    b.*,
    lower(
      regexp_replace(
        split_part(split_part(b.url, '://', 2), '/', 1),
        '^www\\.',
        ''
      )
    ) AS domain,

    -- Legacy label stored by NLP API (keep for debugging / reference)
    COALESCE(b.nlp::jsonb #>> '{sentiment,label}', 'unknown') AS sentiment_label,

    -- Raw score stored by NLP API
    NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric AS sentiment_score,

    -- ✅ New: bucket computed from score using your thresholds
    CASE
      WHEN NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '') IS NULL THEN 'unknown'
      WHEN (NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric) >= :sent_pos THEN 'positive'
      WHEN (NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric) <= :sent_neg THEN 'negative'
      ELSE 'neutral'
    END AS sentiment_bucket

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
      WHEN d.nlp IS NOT NULL AND jsonb_typeof(d.nlp::jsonb->'topics') = 'object'
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
      WHEN d.nlp IS NOT NULL AND jsonb_typeof(d.nlp::jsonb->'entities') = 'array'
        THEN d.nlp::jsonb->'entities'
      ELSE '[]'::jsonb
    END
  ) e
  WHERE COALESCE(e->>'text','') <> ''
)
-- FINAL SELECT
SQL;

        // ---------------------------------------------------------------------
        // entity: value = entity text match (case-insensitive) inside nlp->entities array
        // ---------------------------------------------------------------------
        case 'entity':
            return <<<SQL
WITH
params (ctx, val, time_window, custom_from, custom_to, require_nlp_ok, require_status_ok) AS (
  VALUES (
    :context,
    :value,
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
    AND (b.require_status_ok = 0 OR a.status = 'ok')
    AND (
      b.require_nlp_ok = 0
      OR (a.nlp IS NOT NULL)
    )
    AND a.nlp IS NOT NULL
    AND jsonb_typeof(a.nlp::jsonb->'entities') = 'array'
    AND EXISTS (
      SELECT 1
      FROM jsonb_array_elements(a.nlp::jsonb->'entities') e
      WHERE lower(trim(COALESCE(e->>'text',''))) = lower(b.val)
    )
),
domainized AS (
  SELECT
    b.*,
    lower(
      regexp_replace(
        split_part(split_part(b.url, '://', 2), '/', 1),
        '^www\\.',
        ''
      )
    ) AS domain,

    -- Legacy label stored by NLP API (keep for debugging / reference)
    COALESCE(b.nlp::jsonb #>> '{sentiment,label}', 'unknown') AS sentiment_label,

    -- Raw score stored by NLP API
    NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric AS sentiment_score,

    -- ✅ New: bucket computed from score using your thresholds
    CASE
      WHEN NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '') IS NULL THEN 'unknown'
      WHEN (NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric) >= :sent_pos THEN 'positive'
      WHEN (NULLIF(b.nlp::jsonb #>> '{sentiment,score}', '')::numeric) <= :sent_neg THEN 'negative'
      ELSE 'neutral'
    END AS sentiment_bucket

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
      WHEN d.nlp IS NOT NULL AND jsonb_typeof(d.nlp::jsonb->'topics') = 'object'
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
      WHEN d.nlp IS NOT NULL AND jsonb_typeof(d.nlp::jsonb->'entities') = 'array'
        THEN d.nlp::jsonb->'entities'
      ELSE '[]'::jsonb
    END
  ) e
  WHERE COALESCE(e->>'text','') <> ''
)
-- FINAL SELECT
SQL;
    }

    return null;
}