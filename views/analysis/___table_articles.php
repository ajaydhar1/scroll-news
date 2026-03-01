<?php
// views/analysis/_table_articles.php
//
// Expected vars in scope:
// - $articles (array)
// - $time_window (string)
// - favicon_for_domain() available (from includes/analysis_helpers.php)

// Local analyze-link builder (preserve w/from/to/etc)
$analysisHref = function(string $ctx, string $val) use ($time_window) {
    $params = $_GET;
    $params['context'] = $ctx;
    $params['value']   = $val;
    if (!isset($params['w'])) $params['w'] = $time_window;
    return '?' . http_build_query($params);
};

// Sentiment emoji map (keep consistent)
$sentimentEmoji = [
    'positive' => '🙂',
    'negative' => '☹️',
    'neutral'  => '😐',
    'unknown'  => '🤷',
];

// ---------------------------------------------------------------------
// Helpers (guarded to avoid redeclare if view reused elsewhere)
// ---------------------------------------------------------------------
if (!function_exists('norm')) {
    function norm($s) {
        $s = mb_strtolower(trim($s ?? ''));
        $s = preg_replace('/\s+/', ' ', $s);
        return $s;
    }
}

if (!function_exists('pipeList')) {
    function pipeList($arr) {
        $arr = array_map('norm', $arr ?? []);
        $arr = array_values(array_filter(array_unique($arr)));
        return implode('|', $arr);
    }
}

if (!function_exists('nlpStrings')) {
    function nlpStrings($value): array {
        if (!is_array($value)) return [];

        $out = [];
        foreach ($value as $item) {
            if (is_string($item)) {
                $out[] = $item;
                continue;
            }
            if (is_array($item)) {
                foreach (['text', 'name', 'value', 'label'] as $k) {
                    if (isset($item[$k]) && is_string($item[$k])) {
                        $out[] = $item[$k];
                        break;
                    }
                }
            }
        }
        return $out;
    }
}

if (!function_exists('nlpTopicKeys')) {
    function nlpTopicKeys($topics): array {
        if (!is_array($topics)) return [];

        $keys = array_keys($topics);
        $keys = array_values(array_filter($keys, fn($k) => is_string($k) && trim($k) !== ''));
        return $keys;
    }
}

if (!function_exists('canonEntity')) {
    function canonEntity(string $s): string {
        $s = mb_strtolower(trim($s));

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
    }
}

if (!function_exists('canonEntityListFromNlp')) {
    function canonEntityListFromNlp($nlpEntities): array {
        if (!is_array($nlpEntities)) return [];

        $out = [];
        foreach ($nlpEntities as $item) {
            $raw = '';

            if (is_string($item)) {
                $raw = $item;
            } elseif (is_array($item) && isset($item['text']) && is_string($item['text'])) {
                $raw = $item['text'];
            }

            $raw = trim($raw);
            if ($raw === '') continue;

            $out[] = canonEntity($raw);
        }

        $out = array_values(array_filter(array_unique($out), fn($v) => $v !== ''));
        return $out;
    }
}
?>

<div class="card corpus table-responsive" style="margin-top:12px; margin-bottom:12px;">
  <h3>Articles included</h3>

  <div id="corpusFilters" class="mb-3">

    <!-- Active filters line -->
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div id="corpusActiveFilters" class="d-flex flex-wrap gap-2"></div>
      <div class="d-flex align-items-center gap-2">
        <div class="text-muted small" id="corpusCountLine"></div>
        <button class="corpus-chip" id="corpusClearBtn" type="button">Clear</button>
      </div>
    </div>

    <!-- Top chips -->
    <div class="mb-2">
      <div class="text-muted small mb-1">Top entities</div>
      <div class="d-flex flex-wrap gap-2" id="topEntityChips"></div>
    </div>

    <div class="mb-2">
      <div class="text-muted small mb-1">Top sources</div>
      <div class="d-flex flex-wrap gap-2" id="topSourceChips"></div>
    </div>

    <div class="mb-3">
      <div class="text-muted small mb-1">Top topics</div>
      <div class="d-flex flex-wrap gap-2" id="topTopicChips"></div>
    </div>

    <!-- Typeahead + search + dropdowns -->
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Filter entity…</label>
        <input class="form-control form-control-sm" id="entityInput" list="entityList" placeholder="Type an entity">
        <datalist id="entityList"></datalist>
      </div>

      <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Filter source…</label>
        <input class="form-control form-control-sm" id="sourceInput" list="sourceList" placeholder="Type a source">
        <datalist id="sourceList"></datalist>
      </div>

      <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Headline contains…</label>
        <input class="form-control form-control-sm" id="titleInput" placeholder="Search title/headline">
      </div>

      <div class="col-md-1">
        <label class="form-label small text-muted mb-1">Sentiment</label>
        <select class="form-select form-select-sm" id="sentimentSelect">
          <option value="">All</option>
          <option value="positive">Positive</option>
          <option value="neutral">Neutral</option>
          <option value="negative">Negative</option>
          <option value="unknown">Unknown</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small text-muted mb-1">Category</label>
        <select class="form-select form-select-sm" id="corpusCorpusCategorySelect">
          <option value="">All</option>
          <!-- render categories server-side OR populate via JS -->
        </select>
      </div>
    </div>
  </div>

  <?php if (empty($articles)): ?>
      <div class="text-muted small" style="padding:12px;">
          No articles found for this corpus.
      </div>
  <?php else: ?>

  <table>
    <thead>
      <tr>
        <th>Pub Date</th>
        <th>Category</th>
        <th>Domain</th>
        <th>Title</th>
        <th>Analyze</th>
        <th>Author</th>
        <th>Sent</th>
        <th>Score</th>
      </tr>
    </thead>

    <tbody>
    <?php foreach ($articles as $r):

        // --- helpers for this row ---
        $safeStr = static fn($v) => trim((string)($v ?? ''));
        $lc      = static fn($s) => strtolower(trim((string)$s));

        // Category
        $cat      = $lc($r['source_slug'] ?? '');
        $catLabel = ($cat !== '') ? ucfirst($cat) : '—';
        $catUrl   = ($cat !== '') ? $analysisHref('category', $cat) : null;

        // Domain + favicon
        $domain     = $lc($r['domain'] ?? '');
        $faviconUrl = ($domain !== '') ? favicon_for_domain($domain) : null;

        // URL + title
        $url   = $safeStr($r['url'] ?? '');
        $title = $safeStr($r['title'] ?? '');

        // Author
        $author = $safeStr($r['author'] ?? '');

        // Sentiment
        $sentLabel = $lc($r['sentiment_label'] ?? '');
        $emoji     = $sentimentEmoji[$sentLabel] ?? '❓';
        $sentScore = $safeStr($r['sentiment_score'] ?? '');

        // Pub date normalize -> timestamp (or null)
        $pubRaw = $r['pub_date'] ?? null;
        $pub_ts = null;

        if (is_numeric($pubRaw)) {
            $pub_ts = (int)$pubRaw;
        } elseif (is_string($pubRaw) && trim($pubRaw) !== '') {
            $tmp = strtotime($pubRaw);
            if ($tmp !== false) $pub_ts = $tmp;
        }

        $pubDisplay = $safeStr($r['pub_date'] ?? '');

        // Build Analyze URL (filter out null/empty values)
        $params = array_filter([
            'url'      => $url ?: null,
            'category' => ($cat !== '') ? ucfirst($cat) : null,
            'pub_date' => $pub_ts,
            'db'       => 1,
        ], static fn($v) => $v !== null && $v !== '');

        $qs = http_build_query($params);

        // Decode NLP JSON (if present)
        $nlpRaw = $r['nlp'] ?? '';
        $nlp = [];

        if (is_array($nlpRaw)) {
            $nlp = $nlpRaw;
        } elseif (is_string($nlpRaw) && trim($nlpRaw) !== '') {
            $tmp = json_decode($nlpRaw, true);
            if (is_array($tmp)) $nlp = $tmp;
        }

        $entityList = [];
        $topicList  = [];

        if (isset($nlp['entities'])) $entityList = canonEntityListFromNlp($nlp['entities']);
        elseif (isset($nlp['entity_list'])) $entityList = canonEntityListFromNlp($nlp['entity_list']);
        elseif (isset($nlp['extracted_entities'])) $entityList = canonEntityListFromNlp($nlp['extracted_entities']);

        if (isset($nlp['topics']) && is_array($nlp['topics'])) {
            $isAssoc = array_keys($nlp['topics']) !== range(0, count($nlp['topics']) - 1);
            $topicList = $isAssoc ? nlpTopicKeys($nlp['topics']) : nlpStrings($nlp['topics']);
        } elseif (isset($nlp['topic_list'])) {
            $topicList = nlpStrings($nlp['topic_list']);
        } elseif (isset($nlp['themes'])) {
            $topicList = nlpStrings($nlp['themes']);
        }

    ?>
      <tr class="corpus-row"
          data-entity-list="<?= htmlspecialchars(pipeList($entityList), ENT_QUOTES) ?>"
          data-source="<?= htmlspecialchars(norm($domain), ENT_QUOTES) ?>"
          data-topic-list="<?= htmlspecialchars(pipeList($topicList), ENT_QUOTES) ?>"
          data-category="<?= htmlspecialchars(norm($cat), ENT_QUOTES) ?>"
          data-sentiment="<?= htmlspecialchars(norm($sentLabel ?: 'unknown'), ENT_QUOTES) ?>"
          data-title="<?= htmlspecialchars(norm($title), ENT_QUOTES) ?>"
      >
        <td><?= htmlspecialchars($pubDisplay) ?></td>

        <td>
          <?php if ($catUrl): ?>
            <a href="<?= htmlspecialchars($catUrl) ?>" title="Analyze category" data-loading>
              <?= htmlspecialchars($catLabel) ?>
            </a>
          <?php else: ?>
            <span class="muted">—</span>
          <?php endif; ?>
        </td>

        <td class="sn-domain-cell">
          <?php if (!empty($domain)): ?>
            <?php
              $domainValue = urlencode(strtolower($domain));
              $internalUrl = "/analysis.php?context=pub&value={$domainValue}&w=7d";
            ?>
            <a
              href="<?= htmlspecialchars($internalUrl, ENT_QUOTES, 'UTF-8') ?>"
              class="sn-domain-filter-link"
              data-loading
            >
              <?php if ($faviconUrl): ?>
                <img
                  src="<?= htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8') ?>"
                  alt=""
                  class="sn-favicon"
                  loading="lazy"
                >
              <?php endif; ?>
              <span><?= htmlspecialchars($domain) ?></span>
            </a>
          <?php else: ?>
            <span>—</span>
          <?php endif; ?>
        </td>

        <td>
          <?php if ($url !== ''): ?>
            <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener">
              <?= htmlspecialchars($title !== '' ? $title : $url) ?>
            </a>
          <?php else: ?>
            <span class="muted"><?= htmlspecialchars($title !== '' ? $title : '—') ?></span>
          <?php endif; ?>
        </td>

        <td>
          <?php if ($qs !== ''): ?>
            <a href="newsroom.php?<?= htmlspecialchars($qs) ?>"
               class="btn btn-sm btn-green"
               data-loading>Analyze</a>
          <?php else: ?>
            <span class="muted">—</span>
          <?php endif; ?>
        </td>

        <td><?= htmlspecialchars($author) ?></td>

        <td>
          <span class="sent-emoji"><?= htmlspecialchars($emoji) ?></span>
          <?= htmlspecialchars($sentLabel !== '' ? $sentLabel : '—') ?>
        </td>

        <td><?= htmlspecialchars($sentScore !== '' ? $sentScore : '—') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php endif; ?>
</div>