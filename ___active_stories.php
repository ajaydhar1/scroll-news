<?php
// ___active_stories.php
// Active Stories panel (Power Centers + Story Clusters) sourced from SQL

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
          NOW() - INTERVAL '10 days' AS since_7d
      ),

      excluded_publishers AS (
        -- keep this list tiny + explicit; add more rows if needed
        SELECT UNNEST(ARRAY[
          'floridapolitics.com'
        ]) AS host
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

          -- EXCLUDE PUBLISHERS (prevents ranking domination)
          AND NOT EXISTS (
            SELECT 1
            FROM excluded_publishers ep
            WHERE COALESCE(a.url,'') ILIKE ('%' || ep.host || '%')
          )
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
            'url', x.url,
            'image_url', x.image_url
          )
          ORDER BY x.pub_date DESC NULLS LAST
        ) AS previews
        FROM (
          SELECT
            a.title,
            a.source_slug,
            a.pub_date,
            a.url,
            COALESCE(a.media_url, '') AS image_url
          FROM articles a
          JOIN params p ON TRUE
          WHERE a.created_at >= p.since_7d
            AND a.source_slug <> 'sports'
            AND a.nlp IS NOT NULL
            AND NOT (pr.label = 'GPE' AND a.source_slug = 'entertainment')

            -- EXCLUDE PUBLISHERS (previews too)
            AND NOT EXISTS (
              SELECT 1
              FROM excluded_publishers ep
              WHERE COALESCE(a.url,'') ILIKE ('%' || ep.host || '%')
            )

            -- MAIN TERM MUST BE VISIBLE TO USERS
            AND (
              a.title ILIKE ('%' || pr.entity || '%')
              OR a.description ILIKE ('%' || pr.entity || '%')
            )

            AND (
              pr.item_type = 'power_center'
              OR (
                SELECT COUNT(*)
                FROM (
                  SELECT DISTINCT term
                  FROM unnest(pr.co_entities[1:4]) AS u(term)
                ) AS ce
                WHERE COALESCE(ce.term, '') <> ''
                  AND (
                    a.title ILIKE ('%' || ce.term || '%')
                    OR a.description ILIKE ('%' || ce.term || '%')

                    OR EXISTS (
                      SELECT 1
                      FROM jsonb_object_keys(
                        CASE
                          WHEN jsonb_typeof(a.nlp->'topics') = 'object' THEN a.nlp->'topics'
                          ELSE '{}'::jsonb
                        END
                      ) AS k(key)
                      WHERE key ILIKE ('%' || ce.term || '%')
                    )

                    OR EXISTS (
                      SELECT 1
                      FROM jsonb_array_elements_text(
                        CASE
                          WHEN jsonb_typeof(a.nlp->'keywords') = 'array' THEN a.nlp->'keywords'
                          ELSE '[]'::jsonb
                        END
                      ) AS kw(val)
                      WHERE val ILIKE ('%' || ce.term || '%')
                    )

                    OR EXISTS (
                      SELECT 1
                      FROM jsonb_array_elements(
                        CASE
                          WHEN jsonb_typeof(a.nlp->'entities') = 'array' THEN a.nlp->'entities'
                          ELSE '[]'::jsonb
                        END
                      ) AS ent(obj)
                      WHERE COALESCE(ent.obj->>'text', '') ILIKE ('%' || ce.term || '%')
                    )
                  )
              ) >= 2
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
    $build_synopsis_url = function(string $q): string {
        return 'https://www.google.com/search?' . http_build_query([
            'q'   => "what's happening with " . $q,
            'udm' => 50
        ]);
    };

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

    // Iteration 2: keep only top 4 persistent stories
    $stories = array_slice($stories, 0, 4);

} catch (Throwable $e) {
    $activeStoriesFail('Unexpected error in Active Stories widget', $e);
    return;
}
?>

<div class="container-fluid mt-4 mb-5">

  <div class="card h-100 intel-card sn-card-active-stories">
    <div class="card-body">

      <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
          <div class="sn-time-marker">OVER TIME</div>
          <h3 class="h6 mb-0 panel-title">🧭 Long-Running Story Hub</h3>
          <div class="panel-subtitle">Stories that unfold over time with a clear narrative arc</div>
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
                $synopsis = $build_synopsis_url($q);
                $scrollClassic = $build_search_url($q, 'classic');
                $scrollNlp = $build_search_url($q, 'nlp');
                $google = $build_google_url($q);
                $yt = $build_youtube_url($q);
              ?>
              <div class="power-center">
                <span class="entity-name"><?= htmlspecialchars($label) ?></span>

                <div class="btn-row" style="margin-top:0;">
                  <a class="btn btn-green btn-gray-border btn-xs" href="<?= htmlspecialchars($synopsis) ?>" target="_blank" rel="noopener" title="AI overview of recent developments">Synopsis</a>
                  <a class="btn btn-outline-secondary btn-xs" href="<?= htmlspecialchars($scrollClassic) ?>" data-loading>
                    Scroll (Classic)
                  </a>
                  <a class="btn btn-outline-secondary btn-xs" href="<?= htmlspecialchars($scrollNlp) ?>" data-loading>
                    Scroll (NLP)
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
          <div class="section-label">Long-Running Stories</div>

          <div class="story-grid">
            <?php foreach ($stories as $row): ?>
              <?php
                $label = $row['display_label'] ?? $row['entity'] ?? 'Story';

                // Replace em dash and slashes with spaces
                $topics = preg_replace('/[—\/]/u', ' ', $label);

                // Normalize whitespace
                $topics = trim(preg_replace('/\s+/', ' ', $topics));

                $smart_nlp_search = $build_search_url($topics, 'nlp');

                $q = $build_query_for_row($row);
                $synopsis = $build_synopsis_url($q);
                $google = $build_google_url($q);
                $yt = $build_youtube_url($q);

                $previews = $decode_previews($row['previews'] ?? null);

                $co = $normalize_co_entities($row['co_entities'] ?? []);
                $coCount = count($co);

                $total = (int)($row['total_count'] ?? 0);
              ?>

              <div class="story-card">
                <a class="story-title" href="<?= htmlspecialchars($smart_nlp_search) ?>" data-loading>
                  <?= htmlspecialchars($label) ?> 🔍 
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
                  <a class="btn btn-green btn-gray-border btn-xs" href="<?= htmlspecialchars($synopsis) ?>" target="_blank" rel="noopener" title="AI summary of this ongoing story">Synopsis</a>
                  <a class="btn btn-outline-secondary btn-xs" href="<?= htmlspecialchars($google) ?>" target="_blank" rel="noopener">Google</a>
                  <a class="btn btn-outline-secondary btn-xs" href="<?= htmlspecialchars($yt) ?>" target="_blank" rel="noopener">YouTube</a>
                </div>

                <?php if (!empty($previews)): ?>
                  <ul class="list-unstyled mb-0 mt-2">
                    <?php foreach ($previews as $p): ?>
                      <?php
                        $pUrl   = $p['url'] ?? '#';
                        $pTitle = $p['title'] ?? '(untitled)';
                        $pSource= $p['source'] ?? '';
                        $pDate  = $p['pub_date'] ?? '';

                        // whatever name you choose in SQL — keep a couple aliases for safety
                        $pImg = $p['image_url'] ?? $p['image'] ?? $p['img'] ?? '';

                        $pub_ts = strtotime($pDate);
                        $qs = http_build_query([
                            'url'      => $pUrl,
                            'category' => ucfirst($pSource ?? ''),
                            'pub_date' => $pub_ts,
                            'db'       => 1,
                        ]);

                      ?>
                      <li class="story-preview-item mb-2">
                        <?php if (!empty($pImg)): ?>
                          <a class="story-thumb-link" href="<?= htmlspecialchars($pUrl) ?>" target="_blank" rel="noopener">
                            <img
                              class="story-thumb"
                              src="<?= htmlspecialchars($pImg) ?>"
                              alt=""
                              loading="lazy"
                              referrerpolicy="no-referrer"
                              onerror="this.style.display='none';"
                            />
                          </a>
                        <?php else: ?>
                          <div class="story-thumb story-thumb--placeholder" aria-hidden="true"></div>
                        <?php endif; ?>

                        <div class="story-preview-text">
                          <a class="headline-link" href="<?= htmlspecialchars($pUrl) ?>" target="_blank" rel="noopener">
                            <?= htmlspecialchars((fl_headline_emoji($pTitle) ?? '📰') . ' ' . $pTitle) ?>
                          </a>
                          
                          <?php
                              $publisherDomain = '';
                              $faviconUrl = '';
                              $publisherAnalysisUrl = '';
                              $categoryAnalysisUrl = '';

                              if (!empty($pUrl)) {
                                  $parsed = parse_url($pUrl);
                                  $publisherDomain = $parsed['host'] ?? '';
                                  $publisherDomain = preg_replace('/^www\./', '', $publisherDomain);

                                  if ($publisherDomain) {
                                      $faviconUrl = "https://www.google.com/s2/favicons?sz=64&domain={$publisherDomain}";
                                      $publisherAnalysisUrl = "/analysis.php?context=pub&value=" . urlencode($publisherDomain) . "&w=30d";
                                  }
                              }

                              if (!empty($pSource)) {
                                  $categorySlug = strtolower(trim($pSource));
                                  $categorySlug = preg_replace('/\s+/', '-', $categorySlug);
                                  $categoryAnalysisUrl = "/analysis.php?context=category&value=" . urlencode($categorySlug) . "&w=30d";
                              }
                          ?>

                          <div class="mini-meta d-flex align-items-center gap-1 flex-wrap small">

                              <?php if ($publisherDomain && $publisherAnalysisUrl): ?>
                                  <a href="<?= htmlspecialchars($publisherAnalysisUrl) ?>"
                                    class="story-meta-link d-inline-flex align-items-center gap-1 text-decoration-none"
                                    data-loading>
                                    
                                      <?php if ($faviconUrl): ?>
                                          <img src="<?= htmlspecialchars($faviconUrl) ?>"
                                              alt=""
                                              width="14"
                                              height="14"
                                              class="story-favicon">
                                      <?php endif; ?>

                                      <span><?= htmlspecialchars($publisherDomain) ?></span>
                                  </a>
                                  ·
                              <?php endif; ?>

                              <?php if ($pSource && $categoryAnalysisUrl): ?>
                                  <a href="<?= htmlspecialchars($categoryAnalysisUrl) ?>"
                                    class="story-meta-link text-decoration-none"
                                    data-loading>
                                      <?= htmlspecialchars($pSource) ?>
                                  </a>
                              <?php endif; ?>

                              <?php if ($pDate): ?>
                                  · <span><?= htmlspecialchars($pDate) ?></span>
                              <?php endif; ?>

                          </div>

                          <div class="btn-group btn-group-xs mt-2" role="group">
                            <a
                                href="<?php echo htmlspecialchars($pUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                class="btn btn-secondary"
                                target="_blank"
                                rel="noopener"
                                data-article-url="<?php echo htmlspecialchars($pUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                data-article-title="<?= htmlspecialchars($pTitle) ?>"
                                data-article-source="<?= htmlspecialchars($pSource) ?>"
                                data-article-image="<?= htmlspecialchars($pImg) ?>"
                                data-article-pub-date="<?= htmlspecialchars($pDate) ?>"
                                data-article-kind="external"
                            >
                                Read story
                            </a>
                            <a
                                href="newsroom.php?<?= htmlspecialchars($qs) ?>"
                                class="btn btn-green btn-gray-border"
                                data-loading
                            >
                                Analyze
                            </a>
                        </div>

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
