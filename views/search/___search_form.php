<?php
// ___search_form.php
// Expects $snSearch array.

$mode = $snSearch['mode'] ?? 'classic';
$q = $snSearch['q'] ?? '';
$range = $snSearch['range'] ?? 'all';
$sentiment = $snSearch['sentiment'] ?? '';
$emotion = $snSearch['emotion'] ?? '';
$deepDiveActive = !empty($snSearch['deep_dive_active']);
$highSignalActive = !empty($snSearch['high_signal_active']);
?>

<form id="sn-search-form" method="get" action="search.php" class="mb-4">
  <div class="row g-2 align-items-center search-toolbar">
    <div class="col-md-6">
      <input
        type="text"
        name="q"
        class="form-control"
        placeholder="Search headlines…"
        value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
      >
    </div>

    <div class="col-md-6 d-flex flex-wrap gap-2 justify-content-md-end mt-2 mt-md-0">

      <!-- Mode pills -->
      <div class="btn-group btn-group-sm me-2" role="group" aria-label="Search mode">
        <button type="button"
                class="btn <?= ($mode === 'classic') ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                data-sn-mode="classic">
          Keyword
        </button>

        <button type="button"
                class="btn <?= ($mode === 'nlp') ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                data-sn-mode="nlp">
          Smart (NLP)
        </button>
      </div>

      <!-- Range -->
      <select name="range" class="form-select form-select-sm w-auto me-2" data-sn-autosubmit>
        <option value="all"   <?= ($range === 'all')   ? 'selected' : ''; ?>>All time</option>
        <option value="24h"   <?= ($range === '24h')   ? 'selected' : ''; ?>>Last 24 hours</option>
        <option value="older" <?= ($range === 'older') ? 'selected' : ''; ?>>Older than 24 hours</option>
      </select>

      <?php if ($mode === 'nlp'): ?>
        <!-- Sentiment -->
        <select name="sentiment" class="form-select form-select-sm w-auto me-2" data-sn-autosubmit>
          <option value=""         <?= empty($sentiment)         ? 'selected' : ''; ?>>Any sentiment</option>
          <option value="positive" <?= ($sentiment === 'positive') ? 'selected' : ''; ?>>Positive</option>
          <option value="neutral"  <?= ($sentiment === 'neutral')  ? 'selected' : ''; ?>>Neutral</option>
          <option value="negative" <?= ($sentiment === 'negative') ? 'selected' : ''; ?>>Negative</option>
        </select>

        <!-- Emotion -->
        <select name="emotion" class="form-select form-select-sm w-auto me-2" data-sn-autosubmit>
          <option value=""      <?= empty($emotion)      ? 'selected' : ''; ?>>Any emotion</option>
          <option value="Love"  <?= ($emotion === 'Love')  ? 'selected' : ''; ?>>Love</option>
          <option value="Angry" <?= ($emotion === 'Angry') ? 'selected' : ''; ?>>Angry</option>
          <option value="Ahah"  <?= ($emotion === 'Ahah')  ? 'selected' : ''; ?>>Ahah</option>
          <option value="Wow"   <?= ($emotion === 'Wow')   ? 'selected' : ''; ?>>Wow</option>
          <option value="Sad"   <?= ($emotion === 'Sad')   ? 'selected' : ''; ?>>Sad</option>
        </select>
      <?php endif; ?>

      <button type="submit" class="btn btn-sm btn-success">Search</button>

      <div class="scroll-article-badges">
        <?php if ($mode === 'nlp'): ?>
          <button type="button"
                  class="scroll-badge scroll-badge-deep-dive <?= $deepDiveActive ? 'scroll-badge-active' : ''; ?>"
                  data-sn-toggle="deep_dive"
                  data-sn-only-when-mode="nlp">
            DEEP DIVE
          </button>
        <?php endif; ?>

        <button type="button"
                class="scroll-badge scroll-badge-high-signal-publisher <?= $highSignalActive ? 'scroll-badge-active' : ''; ?>"
                data-sn-toggle="high_signal">
          HIGH-SIGNAL PUBLISHER
        </button>
      </div>

      <!-- Hidden inputs -->
      <input type="hidden" name="mode" id="mode-input" value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="deep_dive" id="deep-dive-input" value="<?= $deepDiveActive ? '1' : ''; ?>">
      <input type="hidden" name="high_signal" id="high-signal-input" value="<?= $highSignalActive ? '1' : ''; ?>">

    </div>
  </div>
</form>