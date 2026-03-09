<?php
// views/analysis/_card_top_topics.php
//
// Expected vars in scope:
// - $topics_chart (array)
// - $time_window (string)

// Build analysis links while preserving existing params (w/from/to/etc)
$analysisHref = function(string $ctx, string $val) use ($time_window) {
    $params = $_GET;
    $params['context'] = $ctx;
    $params['value']   = $val;
    if (!isset($params['w'])) $params['w'] = $time_window;
    return '?' . http_build_query($params);
};

// Local topic normalization for magnet payload (avoid dependency on norm())
$normTopic = function(string $s): string {
    return strtolower(trim($s));
};

// Max weight for bar scaling
$maxWeight = 0.0;
foreach ($topics_chart as $r) {
    $maxWeight = max($maxWeight, (float)($r['weight_sum'] ?? 0));
}
?>

<div class="card" style="flex:1; min-width:320px; margin-top:12px;">
  <div class="card-eyebrow">How the stories are being framed</div>
  <h3>Top Narrative Frames</h3>

  <?php if (empty($topics_chart)): ?>
      <div class="text-muted small" style="padding:12px;">
          No narrative frames found for this corpus.
      </div>
  <?php else: ?>

  <table class="bar-table bar-cells">
      <thead>
          <tr>
              <th>Topic</th>
              <th style="text-align:right;">Weight</th>
              <th style="text-align:right;">Actions</th>
          </tr>
      </thead>

      <tbody>
      <?php foreach ($topics_chart as $r):
          $topic = (string)($r['topic_bucket'] ?? '');
          $w = (float)($r['weight_sum'] ?? 0);
          $pctBar = ($maxWeight > 0) ? round(($w / $maxWeight) * 100, 2) : 0;

          $isOther = ($topic === 'Other');
          $analyzeUrl = !$isOther ? $analysisHref('topic', $topic) : null;
      ?>
          <tr>
              <td><?= htmlspecialchars((string)($r['topic_bucket'] ?? '')) ?></td>

              <td class="bar-cell" style="--bar: <?= $pctBar ?>%;">
                  <span class="bar-fill" aria-hidden="true"></span>
                  <span><?= htmlspecialchars((string)($r['weight_sum'] ?? '')) ?></span>
              </td>

              <td style="text-align:right; white-space:nowrap;">
                  <?php if (!$isOther): ?>
                      <a class="sn-btn"
                         href="<?= htmlspecialchars((string)$analyzeUrl) ?>"
                         title="Analyze topic"
                         data-loading>
                          📊
                      </a>

                      <a class="sn-btn sn-corpus-magnet"
                         href="#"
                         title="Filter corpus by <?= htmlspecialchars($topic) ?>"
                         data-topic="<?= htmlspecialchars($normTopic($topic), ENT_QUOTES, 'UTF-8') ?>"
                         onclick="return false;">
                          🧲
                      </a>
                  <?php else: ?>
                      <span class="muted">—</span>
                  <?php endif; ?>
              </td>
          </tr>
      <?php endforeach; ?>
      </tbody>
  </table>

  <?php endif; ?>
</div>