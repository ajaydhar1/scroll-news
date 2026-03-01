<?php
// views/analysis/_card_top_entities.php
//
// Expected vars in scope:
// - $entities (raw entities result from module query)
// - $time_window (current window, for link building)

// ------------------------------
// Canonicalization + dedupe
// ------------------------------

$canon = function(string $s): string {
    $s = strtolower(trim($s));

    // normalize punctuation + whitespace
    $s = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $s);
    $s = preg_replace('/\s+/', ' ', $s);

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

// Merge by canonical entity
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

        // Prefer PERSON if mixed
        if ($entityMap[$key]['label'] !== 'PERSON' && $label === 'PERSON') {
            $entityMap[$key]['label'] = 'PERSON';
        } elseif ($entityMap[$key]['label'] === '' && $label !== '') {
            $entityMap[$key]['label'] = $label;
        }
    }
}

// Sort
$entities_deduped = array_values($entityMap);
usort($entities_deduped, function($a, $b) {
    return ($b['articles'] <=> $a['articles'])
        ?: strcmp($a['entity'], $b['entity']);
});

// Limit
$entities_deduped = array_slice($entities_deduped, 0, 25);

// Max for bar scaling
$max_articles = 0;
foreach ($entities_deduped as $row) {
    $max_articles = max($max_articles, (int)$row['articles']);
}

// Pretty display helper
$pretty = function(string $s): string {
    return $s;
};

// Build analysis link (preserve query params)
$analysisHref = function(string $ctx, string $val) use ($time_window) {
    $params = $_GET;
    $params['context'] = $ctx;
    $params['value']   = $val;

    if (!isset($params['w'])) {
        $params['w'] = $time_window;
    }

    return '?' . http_build_query($params);
};

// Build search link
$searchHref = function(
    string $query,
    string $mode = 'classic',
    string $range = 'all'
) {
    $params = [
        'q'           => $query,
        'range'       => $range,
        'mode'        => $mode,
        'deep_dive'   => '',
        'high_signal' => '',
    ];

    return '/search.php?' . http_build_query($params);
};
?>

<div class="card table-responsive" style="flex:1; min-width:320px; margin-top:12px;">
  <div class="card-eyebrow">Who’s being talked about</div>
  <h3>Top Entities</h3>

  <?php if (empty($entities_deduped)): ?>
      <div class="text-muted small" style="padding:12px;">
          No entities found for this corpus.
      </div>
  <?php else: ?>

  <table class="bar-table">
      <thead>
          <tr>
              <th>Entity</th>
              <th>Label</th>
              <th style="text-align:right;">Mentions</th>
              <th style="text-align:right;">Actions</th>
          </tr>
      </thead>
      <tbody>

      <?php foreach ($entities_deduped as $row):
          $article_count = (int)$row['articles'];
          $pctBar = ($max_articles > 0)
              ? round(($article_count / $max_articles) * 100, 2)
              : 0;

          $entityValue = (string)$row['entity'];
          $analyzeUrl  = $analysisHref('entity', $entityValue);
          $searchUrl   = $searchHref($entityValue);
      ?>

      <tr class="bar-row" style="--bar: <?= $pctBar ?>%;">
          <td class="bar-cell">
              <span class="bar-fill" aria-hidden="true"></span>
              <?= htmlspecialchars($pretty($row['entity'])) ?>
          </td>

          <td><?= htmlspecialchars($row['label'] ?: '—') ?></td>

          <td style="text-align:right;">
              <?= $article_count ?>
          </td>

          <td style="text-align:right; white-space:nowrap;">

              <a class="sn-btn"
                 href="<?= htmlspecialchars($analyzeUrl) ?>"
                 title="Analyze entity"
                 data-loading>
                  📊
              </a>

              <a class="sn-btn"
                 href="<?= htmlspecialchars($searchUrl) ?>"
                 title="Search all articles"
                 data-loading>
                  🔍
              </a>

              <a class="sn-btn sn-corpus-magnet"
                 href="#"
                 title="Filter corpus by this entity"
                 data-entity="<?= htmlspecialchars($entityValue, ENT_QUOTES) ?>"
                 onclick="return false;">
                  🧲
              </a>

          </td>
      </tr>

      <?php endforeach; ?>

      </tbody>
  </table>

  <?php endif; ?>
</div>