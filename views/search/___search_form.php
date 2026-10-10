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

<form id="sn-search-form" method="get" action="search.php" class="sn-search-form mb-4">
  <div class="sn-search-panel">
    <label class="sn-search-label" for="sn-search-query">Search headlines</label>
    <div class="sn-search-query-row">
      <input
        type="search"
        name="q"
        id="sn-search-query"
        class="form-control sn-search-query"
        placeholder="Try a name, company, place, or topic"
        value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
      >
      <button type="submit" class="sn-search-submit">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <span>Search</span>
      </button>
    </div>

    <div class="sn-search-options">
      <fieldset class="sn-search-mode">
        <legend>Search mode</legend>
        <div class="sn-search-segment" role="group" aria-label="Search mode">
          <button type="button" class="sn-search-segment-option <?= ($mode === 'classic') ? 'is-selected' : ''; ?>"
                  data-sn-mode="classic" aria-pressed="<?= ($mode === 'classic') ? 'true' : 'false'; ?>">
            Keyword
          </button>
          <button type="button" class="sn-search-segment-option <?= ($mode === 'nlp') ? 'is-selected' : ''; ?>"
                  data-sn-mode="nlp" aria-pressed="<?= ($mode === 'nlp') ? 'true' : 'false'; ?>">
            Smart
          </button>
        </div>
      </fieldset>

      <div class="sn-search-filter-grid">
        <div class="sn-search-filter">
          <label for="sn-search-range">Time range</label>
          <select name="range" id="sn-search-range" class="form-select">
            <option value="all"   <?= ($range === 'all')   ? 'selected' : ''; ?>>All time</option>
            <option value="24h"   <?= ($range === '24h')   ? 'selected' : ''; ?>>Last 24 hours</option>
            <option value="older" <?= ($range === 'older') ? 'selected' : ''; ?>>Older than 24 hours</option>
          </select>
        </div>

        <div class="sn-search-smart-filters" data-sn-smart-filters <?= ($mode === 'nlp') ? '' : 'hidden'; ?>>
          <div class="sn-search-filter">
            <label for="sn-search-sentiment">Sentiment</label>
            <select name="sentiment" id="sn-search-sentiment" class="form-select" <?= ($mode === 'nlp') ? '' : 'disabled'; ?>>
              <option value=""         <?= empty($sentiment) ? 'selected' : ''; ?>>Any sentiment</option>
              <option value="positive" <?= ($sentiment === 'positive') ? 'selected' : ''; ?>>Positive</option>
              <option value="neutral"  <?= ($sentiment === 'neutral') ? 'selected' : ''; ?>>Neutral</option>
              <option value="negative" <?= ($sentiment === 'negative') ? 'selected' : ''; ?>>Negative</option>
            </select>
          </div>

          <div class="sn-search-filter">
            <label for="sn-search-emotion">Emotion</label>
            <select name="emotion" id="sn-search-emotion" class="form-select" <?= ($mode === 'nlp') ? '' : 'disabled'; ?>>
              <option value=""      <?= empty($emotion) ? 'selected' : ''; ?>>Any emotion</option>
              <option value="Love"  <?= ($emotion === 'Love') ? 'selected' : ''; ?>>Love</option>
              <option value="Angry" <?= ($emotion === 'Angry') ? 'selected' : ''; ?>>Angry</option>
              <option value="Ahah"  <?= ($emotion === 'Ahah') ? 'selected' : ''; ?>>Ahah</option>
              <option value="Wow"   <?= ($emotion === 'Wow') ? 'selected' : ''; ?>>Wow</option>
              <option value="Sad"   <?= ($emotion === 'Sad') ? 'selected' : ''; ?>>Sad</option>
            </select>
          </div>
        </div>
      </div>

      <fieldset class="sn-search-refinements">
        <legend>Refine results</legend>
        <div class="sn-search-toggles">
          <button type="button" class="sn-search-toggle" data-sn-toggle="deep_dive"
                  role="switch" aria-checked="<?= $deepDiveActive ? 'true' : 'false'; ?>"
                  <?= ($mode === 'nlp') ? '' : 'hidden'; ?>>
            <span>Deep Dive</span>
            <span class="sn-search-switch" aria-hidden="true"></span>
          </button>
          <button type="button" class="sn-search-toggle" data-sn-toggle="high_signal"
                  role="switch" aria-checked="<?= $highSignalActive ? 'true' : 'false'; ?>">
            <span>High-signal publishers</span>
            <span class="sn-search-switch" aria-hidden="true"></span>
          </button>
        </div>
      </fieldset>
    </div>

    <input type="hidden" name="mode" id="mode-input" value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="deep_dive" id="deep-dive-input" value="<?= $deepDiveActive ? '1' : ''; ?>">
    <input type="hidden" name="high_signal" id="high-signal-input" value="<?= $highSignalActive ? '1' : ''; ?>">
    <p id="sn-search-loading" class="sn-search-status" role="status" aria-live="polite" aria-atomic="true" hidden>Searching headlines…</p>
  </div>
</form>