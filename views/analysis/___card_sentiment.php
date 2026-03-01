<?php
// views/analysis/_card_sentiment.php
//
// Expected vars in scope:
// - $sentiment (array)
// - $time_window (string)

// Build analysis links while preserving existing params (w/from/to/etc)
$analysisHref = function(string $ctx, string $val) use ($time_window) {
    $params = $_GET;
    $params['context'] = $ctx;
    $params['value']   = $val;
    if (!isset($params['w'])) $params['w'] = $time_window;
    return '?' . http_build_query($params);
};

// Max articles for bar scaling
$maxSent = 0;
foreach ($sentiment as $r) {
    $maxSent = max($maxSent, (int)($r['articles'] ?? 0));
}

$sentimentEmoji = [
    'positive' => '🙂',
    'negative' => '☹️',
    'neutral'  => '😐',
    'unknown'  => '🤷',
];
?>

<div class="card" style="flex:1; min-width:320px; margin-top:12px;">
  <div class="card-eyebrow">How it feels</div>
  <h3>Sentiment</h3>

  <?php if (empty($sentiment)): ?>
      <div class="text-muted small" style="padding:12px;">
          No sentiment data found for this corpus.
      </div>
  <?php else: ?>

  <table class="bar-table bar-cells">
      <thead>
          <tr>
              <th>Label</th>
              <th style="text-align:right;">Articles</th>
              <th style="text-align:right;">Actions</th>
          </tr>
      </thead>

      <tbody>
      <?php foreach ($sentiment as $r):
          $labelRaw = (string)($r['sentiment_label'] ?? '');
          $labelVal = strtolower(trim($labelRaw));
          $labelPretty = $labelVal !== '' ? ucfirst($labelVal) : '—';

          $v = (int)($r['articles'] ?? 0);
          $pctBar = ($maxSent > 0) ? round(($v / $maxSent) * 100, 2) : 0;

          $analyzeUrl = ($labelVal !== '') ? $analysisHref('sent', $labelVal) : null;
          $emoji = $sentimentEmoji[$labelVal] ?? '❓';
      ?>
          <tr>
              <td>
                  <span class="sent-emoji"><?= $emoji ?></span>
                  <?= htmlspecialchars($labelPretty) ?>
              </td>

              <td class="bar-cell" style="--bar: <?= $pctBar ?>%;">
                  <span class="bar-fill" aria-hidden="true"></span>
                  <span><?= $v ?></span>
              </td>

              <td style="text-align:right; white-space:nowrap;">
                  <?php if ($labelVal !== ''): ?>
                      <a class="sn-btn"
                         href="<?= htmlspecialchars((string)$analyzeUrl) ?>"
                         title="Analyze sentiment"
                         data-loading>
                          📊
                      </a>

                      <a class="sn-btn sn-corpus-magnet"
                         href="#"
                         title="Filter corpus by <?= htmlspecialchars($labelVal) ?>"
                         data-sentiment="<?= htmlspecialchars($labelVal, ENT_QUOTES, 'UTF-8') ?>"
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