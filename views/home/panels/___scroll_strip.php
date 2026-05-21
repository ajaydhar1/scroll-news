<?php
// Try to pull recent articles from the DB first
$scroll_strip_articles = [];

try {

    $db = _pdo_or_null();

    if (!$db) {
        throw new Exception("DB handle not available");
    }

    // Pull recent articles + NLP
    $sql = "
        SELECT 
            url,
            title,
            source_slug,
            media_url AS image_url,
            pub_date,
            nlp
        FROM articles
        WHERE 
            url IS NOT NULL 
            AND title IS NOT NULL
            AND media_url IS NOT NULL
            AND nlp IS NOT NULL
            AND jsonb_typeof(nlp->'entities') = 'array'
            AND jsonb_array_length(nlp->'entities') > 0
            AND deleted_at IS NULL
        ORDER BY pub_date DESC
        LIMIT 12
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rows && count($rows) > 0) {
        $enriched = [];

        foreach ($rows as $row) {
            $nlpRaw = $row['nlp'] ?? null;

            if (is_string($nlpRaw)) {
                $nlp = json_decode($nlpRaw, true) ?: [];
            } elseif (is_array($nlpRaw)) {
                $nlp = $nlpRaw;
            } else {
                $nlp = [];
            }

            // 🔹 Build an article-like array for badge helpers
            $articleForBadges = $row;
            $articleForBadges['nlp'] = $nlp;

            // Use your existing helper: returns array of ['slug', 'label', 'tooltip']
            $badges = scroll_get_article_badges($articleForBadges);

            // 1) Hashtags from keywords (first 3)
            $keywords = $nlp['keywords'] ?? [];
            $hashtagsRaw = [];
            foreach ($keywords as $kw) {
                $kw = trim((string)$kw);
                if ($kw === '') continue;
                if ($kw[0] !== '#') {
                    $kw = '#' . $kw;
                }
                $hashtagsRaw[] = $kw;
            }
            $hashtags = array_slice($hashtagsRaw, 0, 3);

            // 2) Sentiment
            $sentimentLabel = $nlp['sentiment']['label'] ?? null;
            $sentimentScore = $nlp['sentiment']['score'] ?? null; // e.g. 0.1712

            // 3) Emotional reaction (top 3, normalized to true 100% distribution)
            $emotionsRaw = $nlp['emotional_reaction'] ?? [];
            $emotions = sn_normalized_emotion_distribution(
                is_array($emotionsRaw) ? $emotionsRaw : [],
                3
            );

            $enriched[] = [
                'url'             => $row['url'],
                'title'           => $row['title'],
                'source_slug'     => $row['source_slug'],
                'image_url'       => $row['image_url'],
                'pub_date'        => $row['pub_date'],
                'hashtags'        => $hashtags,
                'sentiment_label' => $sentimentLabel,
                'sentiment_score' => $sentimentScore,
                'emotions'        => $emotions,
                'badges'          => $badges,   // 🔹 NEW: send badges to JS
            ];
        }

        $scroll_strip_articles = $enriched;
    }
} catch (Throwable $e) {
    // Log for debugging, but silently fall back to RSS in the UI
    error_log("scroll_strip DB error: " . $e->getMessage());
    $scroll_strip_articles = [];
}
?>

<!-- ========== Article Strip (HTML + CSS + JS) ========== -->
<section class="sn-section bg-light">
  <div class="container-fluid">
    <div class="text-center">
        <h2 class="section-heading text-uppercase">Top Stories</h2>
    </div>
    <div class="sn-header">
      <div class="sn-header-left">
        <div class="sn-time-marker">LAST FEW HOURS</div>
        <h2>Scroll Strip</h2>
      </div>

      <div class="sn-controls">
        <button class="sn-btn" data-dir="left" aria-label="Scroll left">←</button>
        <button class="sn-btn" data-dir="right" aria-label="Scroll right">→</button>
      </div>
    </div>

    <div class="sn-strip-wrapper">
      <div class="sn-strip" id="newsStrip" role="list" aria-label="Top stories"></div>
    </div>
  </div>
</section>

<script>
  // DB articles injected from PHP (if any)
  window.SN_SCROLL_STRIP_DB = <?php
    echo json_encode(
        $scroll_strip_articles,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE |
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    );
  ?> || [];
</script>

<script src="/assets/js/panels/scroll-strip.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/panels/scroll-strip.js'); ?>"></script>
