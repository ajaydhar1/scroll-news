<?php
// analysis.php
// Unified Analysis page: entity, pub, topic, sent, category

define('BASE_PATH', __DIR__);
require_once BASE_PATH . "/core/___modules.php";

$ANALYSIS_DEBUG = true; // toggle while building

// ---- includes (keep these super short so the file stays scannable) ----
require_once BASE_PATH . '/core/analysis/___analysis_helpers.php';
require_once BASE_PATH . '/core/analysis/___analysis_params.php';
require_once BASE_PATH . '/core/analysis/___analysis_scaffolds.php';

// ---- Hygiene toggles ----
$require_nlp_ok    = 1;
$require_status_ok = 1;

// Optional: if your server isn't -05 always, you can centralize this later.
// For now we keep your original bind behavior.
$DEFAULT_FROM_TZ = '-05';
$DEFAULT_TO_TZ   = '-05';

try {

    // ---- Read + validate URL params (moved out to include) ----
    $p = analysis_read_params();

    $context     = $p['context'];
    $value       = $p['value'];
    $time_window = $p['time_window'];
    $custom_from = $p['custom_from']; // 'YYYY-MM-DD' or null
    $custom_to   = $p['custom_to'];   // 'YYYY-MM-DD' or null

    // ---- DB ----
    $db = _pdo_or_null();
    if (!$db) throw new Exception("DB handle not available");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ---- Pick scaffold by context ----
    $SCAFFOLD = analysis_scaffold_for($context);
    if (!$SCAFFOLD) throw new Exception("No scaffold selected for context: " . $context);

    // ---- Bind vars (shared across all module queries) ----
    $bind = [
        ':time_window'        => $time_window,
        ':custom_from'        => ($custom_from ? ($custom_from . ' 00:00:00' . $DEFAULT_FROM_TZ) : ('2000-01-01 00:00:00' . $DEFAULT_FROM_TZ)),
        ':custom_to'          => ($custom_to   ? ($custom_to   . ' 23:59:59' . $DEFAULT_TO_TZ)   : ('2099-01-01 00:00:00' . $DEFAULT_TO_TZ)),
        ':require_nlp_ok'     => $require_nlp_ok,
        ':require_status_ok'  => $require_status_ok,
        ':value'              => $value,   // context-specific meaning
        ':context'            => $context, // useful for KPI display
    ];

    // ---- Query runner: appends FINAL SELECT onto the scaffold ----
    $run = function (string $finalSelectSql) use ($db, $SCAFFOLD, $bind): array {
        $sql = $SCAFFOLD . "\n" . $finalSelectSql;
        $stmt = $db->prepare($sql);
        foreach ($bind as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    };

    // =========================================================================
    // Module queries
    // =========================================================================

    require_once BASE_PATH . '/core/analysis/___analysis_modules.php';

    $modules = analysis_run_modules($db, $SCAFFOLD, $bind);

    if (empty($modules['kpi'])) {
        analysis_fail('KPI query returned 0 rows.', null, $ANALYSIS_DEBUG);
        return;
    }

    $kpi           = $modules['kpi'];
    $timeseries    = $modules['timeseries'];
    $topics_chart  = $modules['topics_chart'];
    $topics_table  = $modules['topics_table'];
    $sentiment     = $modules['sentiment'];
    $sources       = $modules['sources'];
    $entities      = $modules['entities'];
    $articles      = $modules['articles'];

    // =========================================================================
    // Derived values used by the template (safe defaults)
    // =========================================================================

    $context_label   = (string)($kpi[0]['context'] ?? $context);
    $value_label     = (string)($kpi[0]['value'] ?? $value);
    $time_window_lbl = (string)($kpi[0]['time_window'] ?? $time_window);

    // time_min/time_max are timestamptz; keep raw + a formatted version if you want
    $time_min = (string)($kpi[0]['time_min'] ?? '');
    $time_max = (string)($kpi[0]['time_max'] ?? '');

    // Optional: if you want to show favicon for pubs on the page header
    $header_favicon = null;
    if ($context === 'pub' && $value !== '') {
        $header_favicon = favicon_for_domain($value);
    }

} catch (Throwable $e) {
    analysis_fail('Unexpected error in Analysis page', $e, $ANALYSIS_DEBUG);
    return;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <?php
  // Build a friendly, compact title for the browser tab / SEO
  $ctxLabelMap = [
    'entity'    => 'Entity',
    'topic'     => 'Topic',
    'pub'       => 'Publisher',
    'sent'      => 'Sentiment',
    'category'  => 'Category',
  ];

  $ctxLabel = $ctxLabelMap[$context] ?? ucfirst((string)$context);

  // Window label
  $wLabelMap = ['24h' => '24h', '7d' => '7d', '30d' => '30d', 'custom' => 'Custom'];
  $wLabel = $wLabelMap[$time_window] ?? (string)$time_window;

  // Corpus count (optional)
  $corpusCount = (int)($kpi[0]['corpus_articles'] ?? 0);

  // Value formatting: keep title short, but still informative
  $val = (string)$value;
  $val = trim($val);
  if ($val === '') $val = 'All';
  if (mb_strlen($val) > 60) $val = mb_substr($val, 0, 57) . '…';

  // Optional: make sentiment nicer
  if ($context === 'sent') $val = ucfirst(strtolower($val));

  // Final title
  $pageTitle = "{$ctxLabel}: {$val} · {$wLabel} · {$corpusCount} articles · Text & Content Analysis";
  ?>
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <?php
  // Optional meta description (nice-to-have)
  $metaDesc = "Analysis for {$ctxLabel} \"{$val}\" over {$wLabel}. Corpus size: {$corpusCount} articles.";
  ?>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">

  <meta name="author" content="Scroll News" />

  <!-- Favicon-->
  <link rel="icon" type="image/png" href="assets/img/play-green.png" />

  <!-- jQuery min-->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

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
  <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
  <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
  <link href="/assets/css/pages/analysis.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/analysis.css'); ?>" rel="stylesheet" />

</head>
<body class="bg-light">

  <!-- Top nav-->        
  <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

  <div class="container-fluid">

      <h1 style="margin:0 0 6px 0;" class="text-center mt-3">Text & Content Analysis</h1>
      <div class="note text-center">
      Context:
      <strong><?= htmlspecialchars($context) ?></strong>
      <span class="muted">(<?= htmlspecialchars($value) ?>)</span>
      &nbsp;|&nbsp;

      Window:
      <?php
      $params = $_GET;
      $params['w'] = '24h';
      ?>
      <a href="?<?= http_build_query($params) ?>" class="<?= $time_window === '24h' ? 'active' : '' ?>" data-loading>24h</a> ·
      <?php
      $params['w'] = '7d';
      ?>
      <a href="?<?= http_build_query($params) ?>" class="<?= $time_window === '7d'  ? 'active' : '' ?>" data-loading>7d</a> ·
      <?php
      $params['w'] = '30d';
      ?>
      <a href="?<?= http_build_query($params) ?>" class="<?= $time_window === '30d' ? 'active' : '' ?>" data-loading>30d</a>
      &nbsp;|&nbsp;

      Corpus:
      <strong><?= (int)($kpi[0]['corpus_articles'] ?? 0) ?></strong> articles
      </div>

      <?php
      $ctxLabelMap = [
      'entity'   => 'Entity',
      'topic'    => 'Topic',
      'pub'      => 'Publisher',
      'sent'     => 'Sentiment',
      'category' => 'Category',
      ];

      $ctxLabel = $ctxLabelMap[$context] ?? ucfirst($context);

      // Format value for display
      $ctxValue = (string)($kpi[0]['value'] ?? $value ?? '');
      $ctxValue = trim($ctxValue);
      if ($context === 'sent') {
          $ctxValue = ucfirst(strtolower($ctxValue));
      }
      ?>

      <div class="analysis-desc text-center mt-2">
          <p>
              This page analyzes how a selected entity or topic appears in recent news coverage within the specified time window.
              Metrics and breakdowns are derived from the active article corpus.
          </p>
      </div>

      <div class="row">
          <div class="col-12 col-lg-12">
              <!-- Corpus overview: context, window, size, bounds -->
              <?php require BASE_PATH . '/views/analysis/___card_corpus_kpis.php'; ?>
          </div>
      </div>

      <div class="row">
          <div class="col-12 col-lg-6">
              <!-- NLP: Top entities (deduped + magnet/filter actions) -->
              <?php require BASE_PATH . '/views/analysis/___card_top_entities.php'; ?>
          </div>

          <div class="col-12 col-lg-6">
              <!-- NLP: Top source domains (favicons + magnet/filter actions) -->
              <?php require BASE_PATH . '/views/analysis/___card_top_sources.php'; ?>
          </div>
      </div>

      <div class="row">
          <div class="col-12 col-lg-6">
              <!-- NLP: Top topics (Top N + Other + magnet/filter actions) -->
              <?php require BASE_PATH . '/views/analysis/___card_top_topics.php'; ?>
          </div>

          <div class="col-12 col-lg-6">
              <!-- NLP: Sentiment distribution (labels + emojis + magnet/filter actions) -->
              <?php require BASE_PATH . '/views/analysis/___card_sentiment.php'; ?>
          </div>
      </div>

      <div class="row">
          <div class="col-12 col-lg-12">
              <!-- Corpus: Article table + client-side filters (chips/typeahead/magnets) -->
              <?php require BASE_PATH . '/views/analysis/___table_articles.php'; ?>
          </div>
      </div>
  </div>

  <!-- Footer-->        
  <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>
  
  <!-- Modals-->        
  <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

  <!-- Core JS (Bootstrap 4 requires jQuery first) -->
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" defer></script>

  <script src="/assets/js/pages/components/analysis-corpus.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/pages/components/analysis-corpus.js'); ?>" defer></script>

</body>
</html>
