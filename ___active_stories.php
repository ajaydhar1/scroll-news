<?php
// ___active_stories.php
// Active Stories panel (Power Centers + Story Clusters) sourced from SQL

if (!function_exists('_pdo_or_null')) {
    require_once __DIR__ . '/___modules.php';
}

$ACTIVE_STORIES_DEBUG = true; // set true temporarily while testing

$activeStoriesFail = function(string $msg, ?Throwable $e = null) use ($ACTIVE_STORIES_DEBUG) {
    error_log('[ActiveStories] ' . $msg . ($e ? (' | ' . $e->getMessage()) : ''));
    if ($ACTIVE_STORIES_DEBUG) {
        echo '<div class="alert alert-warning small" style="margin:10px 0;">';
        echo '<strong>Active Stories error:</strong> ' . htmlspecialchars($msg);
        if ($e) echo '<br><code>' . htmlspecialchars($e->getMessage()) . '</code>';
        echo '</div>';
    }
};

try {
    $db = _pdo_or_null();
    if (!$db) throw new Exception("DB handle not available");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = <<<SQL
      WITH params AS (
        SELECT
          NOW() - INTERVAL '3 weeks' AS since_3w,
          NOW() - INTERVAL '3 days'  AS since_3d
      ),
      sports_terms AS (
        SELECT UNNEST(ARRAY[
          'goal',
          'nfl','nba','mlb','nhl',
          'afc','nfc',
          'afc north','afc south','afc east','afc west',
          'nfc north','nfc south','nfc east','nfc west',
          'mls','nascar',
          'playoffs','wild card','super bowl',
          'tom brady','colin cowherd','fox sports',
          'cfp','college football playoff'
        ]) AS term
      ),
      media_terms AS (
        SELECT UNNEST(ARRAY[
          'cnn','fox news','msnbc','cbs news','abc news','nbc news','reuters',
          'associated press','ap news','the guardian','new york times','washington post',
          'usa today','npr','bbc','al jazeera','bloomberg'
        ]) AS term
      ),
      ents_raw AS (
        SELECT
          a.id AS article_id,
          a.pub_date,
          a.created_at,
          a.title,
          a.description,
          a.url,
          a.source_slug,
          LOWER(TRIM(e->>'text')) AS entity,
          e->>'label'             AS label,
          COALESCE((e->>'count')::int, 1) AS cnt
        FROM articles a
        CROSS JOIN LATERAL jsonb_array_elements(a.nlp->'entities') e
        JOIN params p ON TRUE
        WHERE a.created_at >= p.since_3w
          AND jsonb_exists(a.nlp, 'entities')
          AND jsonb_typeof(a.nlp->'entities') = 'array'
          AND COALESCE(e->>'text','') <> ''
          AND LENGTH(TRIM(e->>'text')) >= 3
      ),
      ents AS (
        SELECT
          article_id, pub_date, created_at, title, description, url, source_slug,
          CASE WHEN entity = 'trump' THEN 'donald trump' ELSE entity END AS entity,
          CASE WHEN entity = 'trump' THEN 'PERSON'       ELSE label  END AS label,
          cnt
        FROM ents_raw
      ),
      ents_storygrade AS (
        SELECT e.*
        FROM ents e
        WHERE e.label IN ('PERSON','ORG','GPE')
          AND e.entity NOT IN (SELECT term FROM sports_terms)
          AND e.entity NOT IN (SELECT term FROM media_terms)
          AND e.entity NOT IN ('steelers','lakers','warriors','yankees','dodgers','red sox','patriots','cowboys','packers')
      ),
      entity_totals AS (
        SELECT entity, label, SUM(cnt) AS total_count
        FROM ents_storygrade
        GROUP BY entity, label
      ),
      top_entities AS (
        SELECT *
        FROM entity_totals
        ORDER BY total_count DESC
        LIMIT 120
      ),
      pairs AS (
        SELECT
          e1.entity AS entity,
          e1.label  AS label,
          e2.entity AS co_entity,
          e2.label  AS co_label,
          COUNT(DISTINCT e1.article_id) AS shared_articles
        FROM ents_storygrade e1
        JOIN ents_storygrade e2
          ON e1.article_id = e2.article_id
         AND e1.entity <> e2.entity
        JOIN top_entities t
          ON t.entity = e1.entity AND t.label = e1.label
        WHERE e2.entity NOT IN (SELECT term FROM sports_terms)
          AND e2.entity NOT IN (SELECT term FROM media_terms)
        GROUP BY e1.entity, e1.label, e2.entity, e2.label
      ),
      ranked_pairs AS (
        SELECT
          p.*,
          ROW_NUMBER() OVER (
            PARTITION BY p.entity, p.label
            ORDER BY p.shared_articles DESC
          ) AS rn
        FROM pairs p
      ),
      co_entities AS (
        SELECT
          entity,
          label,
          ARRAY_AGG(co_entity ORDER BY shared_articles DESC) FILTER (WHERE rn <= 4) AS co_entity_arr
        FROM ranked_pairs
        GROUP BY entity, label
      ),
      power_centers AS (
        SELECT * FROM (VALUES
          ('donald trump','PERSON'),
          ('u.s','GPE'),
          ('us','GPE'),
          ('washington','GPE'),
          ('congress','ORG'),
          ('house','ORG'),
          ('senate','ORG'),
          ('white house','ORG')
        ) AS t(entity, label)
      ),
      story_stop_entities AS (
        SELECT UNNEST(ARRAY[
          'one','american','state'
        ]) AS entity
      ),
      panel_candidates AS (
        SELECT
          'power_center'::text AS item_type,
          t.entity,
          t.label,
          t.total_count,
          COALESCE(c.co_entity_arr, ARRAY[]::text[]) AS co_entities
        FROM top_entities t
        JOIN power_centers pc
          ON pc.entity = t.entity AND pc.label = t.label
        LEFT JOIN co_entities c
          ON c.entity = t.entity AND c.label = t.label

        UNION ALL

        SELECT
          'story_cluster'::text AS item_type,
          t.entity,
          t.label,
          t.total_count,
          COALESCE(c.co_entity_arr, ARRAY[]::text[]) AS co_entities
        FROM top_entities t
        JOIN co_entities c
          ON c.entity = t.entity AND c.label = t.label
        LEFT JOIN power_centers pc
          ON pc.entity = t.entity AND pc.label = t.label
        WHERE pc.entity IS NULL
          AND t.entity NOT IN (SELECT entity FROM story_stop_entities)
          AND CARDINALITY(c.co_entity_arr) >= 3
          AND t.entity <> 'donald trump'
      ),
      panel_labeled AS (
        SELECT
          pc.*,
          CASE
            WHEN pc.item_type = 'power_center' THEN INITCAP(pc.entity)
            ELSE INITCAP(pc.entity) || ' — ' || array_to_string(pc.co_entities[1:4], ' / ')
          END AS display_label
        FROM panel_candidates pc
      ),
      power_ranked AS (
        SELECT *
        FROM panel_labeled
        WHERE item_type = 'power_center'
        ORDER BY total_count DESC
      ),
      story_ranked AS (
        SELECT *
        FROM panel_labeled
        WHERE item_type = 'story_cluster'
        ORDER BY total_count DESC
        LIMIT 10
      ),
      panel_ranked AS (
        SELECT * FROM power_ranked
        UNION ALL
        SELECT * FROM story_ranked
      )
      SELECT
        pr.item_type,
        pr.entity,
        pr.label,
        pr.total_count,
        pr.co_entities,
        pr.display_label,
        COALESCE(prev.previews, '[]'::jsonb) AS previews
      FROM panel_ranked pr
      LEFT JOIN LATERAL (
        SELECT jsonb_agg(
                 jsonb_build_object(
                   'title', x.title,
                   'source', x.source_slug,
                   'pub_date', x.pub_date,
                   'url', x.url
                 )
                 ORDER BY x.pub_date DESC NULLS LAST
               ) AS previews
        FROM (
          SELECT a.title, a.source_slug, a.pub_date, a.url
          FROM articles a
          JOIN params p ON TRUE
          WHERE a.created_at >= p.since_3d
            AND a.source_slug <> 'sports'
            AND NOT (pr.label = 'GPE' AND a.source_slug = 'entertainment')
            AND (
              a.title ILIKE ('%' || pr.entity || '%')
              OR a.description ILIKE ('%' || pr.entity || '%')
            )
            AND (
              pr.item_type = 'power_center'
              OR pr.label = 'GPE'
              OR EXISTS (
                SELECT 1
                FROM UNNEST(pr.co_entities[1:4]) ce
                WHERE a.title ILIKE ('%' || ce || '%')
                   OR a.description ILIKE ('%' || ce || '%')
              )
            )
          ORDER BY a.pub_date DESC NULLS LAST
          LIMIT 3
        ) x
      ) prev ON TRUE
      WHERE pr.item_type = 'power_center'
         OR jsonb_array_length(COALESCE(prev.previews, '[]'::jsonb)) >= 2
      ORDER BY
        CASE WHEN pr.item_type = 'power_center' THEN 0 ELSE 1 END,
        pr.total_count DESC;
    SQL;

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        $activeStoriesFail('Query returned 0 rows (nothing to render).');
        return;
    }

    // Helpers
    $build_search_url = function(string $q, string $mode = 'nlp'): string {
        return 'https://scrollnews.io/search.php?' . http_build_query([
            'q'          => $q,
            'range'      => 'all',
            'mode'       => $mode,
            'deep_dive'  => '',
            'high_signal'=> '',
        ]);
    };

    $build_google_url = function(string $q): string {
        return 'https://www.google.com/search?' . http_build_query(['q' => $q]);
    };

    $build_youtube_url = function(string $q): string {
        return 'https://www.youtube.com/results?' . http_build_query(['search_query' => $q]);
    };

    $decode_previews = function($json) {
        if (!$json) return [];
        if (is_array($json)) return $json;
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    };

    $normalize_co_entities = function($cos): array {
        if (is_string($cos)) {
            $cos = trim($cos, "{}");
            if ($cos === '') return [];
            return array_map(function($x){
                return trim($x, "\" ");
            }, explode(",", $cos));
        }
        return is_array($cos) ? $cos : [];
    };

    $build_query_for_row = function(array $row) use ($normalize_co_entities): string {
        $entity = $row['entity'] ?? '';
        $cos = $normalize_co_entities($row['co_entities'] ?? []);

        if (($row['item_type'] ?? '') === 'power_center') return $entity;

        // story clusters: entity + top 2 co-entities
        $top = array_slice($cos, 0, 2);
        $parts = array_filter(array_merge([$entity], $top));
        return trim(implode(' ', $parts));
    };

    // Group rows
    $power = [];
    $stories = [];

    foreach ($rows as $r) {
        $type = $r['item_type'] ?? '';
        if ($type === 'power_center') $power[] = $r;
        if ($type === 'story_cluster') $stories[] = $r;
    }

    // Iteration 2: keep only top 2 persistent stories
    $stories = array_slice($stories, 0, 2);

} catch (Throwable $e) {
    $activeStoriesFail('Unexpected error in Active Stories widget', $e);
    return;
}
?>

<style>
/* Iteration 2 polish */
.sn-card-active-stories .panel-title {
  font-weight: 800;
  color: #111;
  letter-spacing: -0.1px;
}
.sn-card-active-stories .panel-subtitle {
  font-size: 12px;
  color: #6b7280;
  margin-top: 2px;
}

.sn-card-active-stories .section-label {
  font-size: 12px;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: .04em;
  margin-bottom: 8px;
}

.sn-card-active-stories .power-centers {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 14px;
}

.sn-card-active-stories .power-center {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 8px 10px;
  border: 1px solid rgba(0,0,0,0.06);
  border-radius: 10px;
  background: rgba(0,0,0,0.01);
}

.sn-card-active-stories .entity-name {
  font-weight: 600;
  color: #111;
}

.sn-card-active-stories .story-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
}

@media (min-width: 992px) {
  .sn-card-active-stories .story-grid {
    grid-template-columns: 1fr 1fr; /* left/right split */
  }
}

.sn-card-active-stories .story-card {
  border: 1px solid rgba(0,0,0,0.06);
  border-radius: 12px;
  padding: 12px;
  background: #fff;
}

.sn-card-active-stories .story-title {
  font-weight: 800;
  color: #111;
  text-decoration: none;
  line-height: 1.25;
}
.sn-card-active-stories .story-title:hover {
  color: #2cae86;
}

.sn-card-active-stories .story-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-top: 6px;
}

.sn-card-active-stories .cooccurrence-label {
  font-size: 12px;
  color: #6b7280;
}

.sn-card-active-stories .story-weight {
  font-size: 12px;
  color: #6b7280;
  white-space: nowrap;
}
.sn-card-active-stories .story-weight .window {
  font-weight: 700;
  color: #9ca3af;
}
.sn-card-active-stories .story-weight .dot {
  margin: 0 6px;
  color: #d1d5db;
}

.sn-card-active-stories .btn-row {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 10px;
}

.sn-card-active-stories .mini-meta {
  font-size: 12px;
  color: #6b7280;
}

.sn-card-active-stories .headline-link {
  color: #111;
  text-decoration: none;
}
.sn-card-active-stories .headline-link:hover {
  color: #2cae86;
}
</style>

<div class="container-fluid mb-5">

  <div class="card h-100 intel-card sn-card-active-stories">
    <div class="card-body">

      <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
          <h3 class="h6 mb-0 panel-title">🧭 Persistent News Story Hub</h3>
          <div class="panel-subtitle">Ongoing narratives with sustained momentum</div>
        </div>
        <span class="text-muted small">last 3 weeks</span>
      </div>

      <?php if (!empty($power)): ?>
        <div class="mb-3">
          <div class="section-label">Power Centers</div>

          <div class="power-centers">
            <?php foreach ($power as $row): ?>
              <?php
                $entity = $row['entity'] ?? '';
                $label  = $row['display_label'] ?? $entity ?? 'Entity';

                $q = $build_query_for_row($row);
                $scrollKeyword = $build_search_url($q, 'nlp');
                $google = $build_google_url($q);
                $yt = $build_youtube_url($q);
              ?>
              <div class="power-center">
                <span class="entity-name"><?= htmlspecialchars($label) ?></span>

                <div class="btn-row" style="margin-top:0;">
                  <a class="btn btn-green btn-gray-border btn-xs" href="<?= htmlspecialchars($scrollKeyword) ?>" data-loading>
                    Scroll News
                  </a>
                  <a class="btn btn-outline-secondary btn-xs" href="<?= htmlspecialchars($google) ?>" target="_blank" rel="noopener">Google</a>
                  <a class="btn btn-outline-secondary btn-xs" href="<?= htmlspecialchars($yt) ?>" target="_blank" rel="noopener">YouTube</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($stories)): ?>
        <div>
          <div class="section-label">Persistent Stories</div>

          <div class="story-grid">
            <?php foreach ($stories as $row): ?>
              <?php
                $label = $row['display_label'] ?? $row['entity'] ?? 'Story';

                $q = $build_query_for_row($row);
                $scrollClassic = $build_search_url($q, 'classic');
                $scrollNlp = $build_search_url($q, 'nlp');
                $google = $build_google_url($q);
                $yt = $build_youtube_url($q);

                $previews = $decode_previews($row['previews'] ?? null);

                $co = $normalize_co_entities($row['co_entities'] ?? []);
                $coCount = count($co);

                $total = (int)($row['total_count'] ?? 0);
              ?>

              <div class="story-card">
                <a class="story-title" href="<?= htmlspecialchars($scrollClassic) ?>" data-loading>
                  <?= htmlspecialchars($label) ?>
                </a>

                <div class="story-meta">
                  <div class="cooccurrence-label">
                    (co-occurrence cluster<?= $coCount ? ': ' . $coCount . ' co-entities' : '' ?>)
                  </div>

                  <div class="story-weight" title="Signals in window">
                    <span class="window">w3</span><span class="dot">·</span><span class="count"><?= $total ?> signals</span>
                  </div>
                </div>

                <div class="btn-row">
                  <a class="btn btn-green btn-gray-border btn-xs" href="<?= htmlspecialchars($scrollClassic) ?>" data-loading>Scroll (Classic)</a>
                  <a class="btn btn-outline-secondary btn-xs" href="<?= htmlspecialchars($scrollNlp) ?>" data-loading>Scroll (NLP)</a>
                  <a class="btn btn-outline-secondary btn-xs" href="<?= htmlspecialchars($google) ?>" target="_blank" rel="noopener">Google</a>
                  <a class="btn btn-outline-secondary btn-xs" href="<?= htmlspecialchars($yt) ?>" target="_blank" rel="noopener">YouTube</a>
                </div>

                <?php if (!empty($previews)): ?>
                  <ul class="list-unstyled mb-0 mt-2">
                    <?php foreach ($previews as $p): ?>
                      <?php
                        $pUrl = $p['url'] ?? '#';
                        $pTitle = $p['title'] ?? '(untitled)';
                        $pSource = $p['source'] ?? '';
                        $pDate = $p['pub_date'] ?? '';
                      ?>
                      <li class="mb-2">
                        <a class="headline-link" href="<?= htmlspecialchars($pUrl) ?>" target="_blank" rel="noopener">
                          <?= htmlspecialchars($pTitle) ?>
                        </a>
                        <div class="mini-meta">
                          <?= htmlspecialchars($pSource) ?>
                          <?php if ($pDate): ?> · <?= htmlspecialchars($pDate) ?><?php endif; ?>
                        </div>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <div class="mini-meta mt-2">No recent articles.</div>
                <?php endif; ?>
              </div>

            <?php endforeach; ?>
          </div>

        </div>
      <?php endif; ?>

    </div>
  </div>
</div>
