<?php
// views/analysis/_card_top_sources.php
//
// Expected vars in scope:
// - $sources (from module query)
// - $time_window (string)
// - favicon_for_domain() helper available (from analysis_helpers.php)

// Build analysis links while preserving existing params (w/from/to/etc)
$analysisHref = function(string $ctx, string $val) use ($time_window) {
    $params = $_GET;
    $params['context'] = $ctx;
    $params['value']   = $val;
    if (!isset($params['w'])) $params['w'] = $time_window;
    return '?' . http_build_query($params);
};

// Compute max for bar scaling
$max_articles = 0;
foreach ($sources as $r) {
    $max_articles = max($max_articles, (int)($r['articles'] ?? 0));
}
?>

<div class="card table-responsive" style="flex:1; min-width:320px; margin-top:12px;">
  <div class="card-eyebrow">Who’s talking</div>
  <h3>Top Sources (Domains)</h3>

  <?php if (empty($sources)): ?>
      <div class="text-muted small" style="padding:12px;">
          No domains found for this corpus.
      </div>
  <?php else: ?>

  <table class="bar-table">
      <thead>
          <tr>
              <th>Domain</th>
              <th style="text-align:right;">Articles</th>
              <th style="text-align:right;">%</th>
              <th style="text-align:right;">Actions</th>
          </tr>
      </thead>

      <tbody>
      <?php foreach ($sources as $r):
          $article_count = (int)($r['articles'] ?? 0);
          $pctBar = ($max_articles > 0)
              ? round(($article_count / $max_articles) * 100, 2)
              : 0;

          $domain = strtolower(trim((string)($r['domain'] ?? '')));
          if ($domain === '') continue;

          $analyzeUrl = $analysisHref('pub', $domain);
          $faviconUrl = favicon_for_domain($domain);

          $href = preg_match('#^https?://#', $domain)
              ? $domain
              : 'https://' . $domain;

          $pct = (string)($r['pct'] ?? '');
      ?>
          <tr class="bar-row" style="--bar: <?= $pctBar ?>%;">
              <td class="sn-domain-cell bar-cell">
                  <span class="bar-fill" aria-hidden="true"></span>

                  <a
                      href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="sn-domain-link"
                  >
                      <?php if ($faviconUrl): ?>
                          <img
                              src="<?= htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8') ?>"
                              alt=""
                              class="sn-favicon"
                              loading="lazy"
                          >
                      <?php endif; ?>

                      <span><?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') ?></span>
                  </a>
              </td>

              <td style="text-align:right;"><?= $article_count ?></td>

              <td style="text-align:right;">
                  <?= htmlspecialchars($pct, ENT_QUOTES, 'UTF-8') ?>
              </td>

              <td style="text-align:right; white-space:nowrap;">
                  <a class="sn-btn"
                     href="<?= htmlspecialchars($analyzeUrl, ENT_QUOTES, 'UTF-8') ?>"
                     title="Analyze publisher"
                     data-loading>
                      📊
                  </a>

                  <a class="sn-btn"
                     href="<?= htmlspecialchars('https://' . $domain, ENT_QUOTES, 'UTF-8') ?>"
                     title="View publisher"
                     target="_blank"
                     rel="noopener noreferrer">
                      📰
                  </a>

                  <a class="sn-btn sn-corpus-magnet"
                     href="#"
                     title="Filter corpus by this publisher"
                     data-source="<?= htmlspecialchars(strtolower($domain), ENT_QUOTES, 'UTF-8') ?>"
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