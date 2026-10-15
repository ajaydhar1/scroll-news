<?php
// ___featured_timeline.php
// Homepage "Featured Coverage Timeline" band. Built from the cached timeline model only
// (sn_tl_peek_model): this file never queries the database or rebuilds a timeline.
// If no model is cached it renders a lightweight preview from the story definition.
require_once BASE_PATH . '/core/timelines/___timelines.php';
require_once BASE_PATH . '/views/timeline/___card.php';

$ftlStories = sn_tl_all_stories();
$ftlModels = [];
foreach ($ftlStories as $ftlSlug => $ftlDef) {
    $ftlModels[$ftlSlug] = sn_tl_peek_model($ftlDef);
}
$ftlPick = sn_tl_pick_featured($ftlStories, $ftlModels);
if (!$ftlPick) {
    return;
}

$ftlStory  = $ftlPick['story'];
$ftlModel  = $ftlPick['model'];
$ftlSlug   = $ftlStory['slug'];
$ftlActive = ($ftlStory['status'] ?? 'closed') === 'active';
$ftlHref   = '/timeline.php?story=' . rawurlencode($ftlSlug);
$ftlTz     = new DateTimeZone(SN_TL_TZ);
$ftlToday  = sn_tl_today();
$ftlChapters = $ftlStory['chapters'] ?? [];

// Time span the skyline and rail share
if ($ftlModel && !empty($ftlModel['series'])) {
    $ftlSeries = $ftlModel['series'];
    $ftlStart  = $ftlSeries[0]['date'];
    $ftlEnd    = end($ftlSeries)['to'];
} else {
    $ftlSeries = [];
    $ftlStart  = $ftlChapters ? $ftlChapters[0]['from'] : ($ftlStory['window']['from'] ?? $ftlToday);
    $ftlEnd    = $ftlActive ? $ftlToday : ($ftlChapters ? end($ftlChapters)['to'] : ($ftlStory['window']['to'] ?? $ftlToday));
}
$ftlT0   = strtotime($ftlStart . ' UTC');
$ftlSpan = max(86400, strtotime($ftlEnd . ' UTC') - $ftlT0);
$ftlHasLatest = $ftlActive && (!$ftlModel || ($ftlModel['stats']['latest_core'] ?? 0) > 0 || !$ftlChapters);

// Chapter nodes: proportional to date, with a minimum spacing so neighbours never overlap
$ftlGap = 6.0;
$ftlLo = 2.5;
$ftlHi = $ftlHasLatest ? 97.5 - $ftlGap : 97.5;
$ftlNodes = [];
$ftlPrev = null;
foreach ($ftlChapters as $i => $ch) {
    $raw = max(0, min(1, (strtotime($ch['from'] . ' UTC') - $ftlT0) / $ftlSpan)) * 100;
    $p = max($ftlLo, $raw, $ftlPrev === null ? $ftlLo : $ftlPrev + $ftlGap);
    $ftlNodes[$i] = $p;
    $ftlPrev = $p;
}
if ($ftlNodes && end($ftlNodes) > $ftlHi) {
    $last = end($ftlNodes);
    $scale = ($ftlHi - $ftlLo) / max(0.01, $last - $ftlLo);
    foreach ($ftlNodes as $i => $p) {
        $ftlNodes[$i] = $ftlLo + ($p - $ftlLo) * $scale;
    }
}

$ftlRange = static function (array $ch): string {
    $a = sn_tl_fmt_day($ch['from'], 'M j');
    $b = sn_tl_fmt_day($ch['to'], 'M j');
    return $a === $b ? $a : $a . ' – ' . $b;
};

// Preview articles: newest unreviewed coverage for active stories, peak-day coverage otherwise
$ftlItems = [];
$ftlItemsLabel = '';
if ($ftlModel) {
    $pool = [];
    $latestFrom = $ftlModel['review']['latest_from'] ?? null;
    if ($ftlActive) {
        foreach ($ftlModel['days'] as $d => $info) {
            if ($latestFrom !== null && $d >= $latestFrom) {
                $pool = array_merge($pool, $info['articles']);
            }
        }
        $ftlItemsLabel = $latestFrom !== null ? 'Latest coverage' : 'Newest coverage';
        if (!$pool) {
            foreach (array_reverse($ftlModel['days'], true) as $info) {
                $pool = array_merge($pool, $info['articles']);
                if (count($pool) >= 12) {
                    break;
                }
            }
        }
    } else {
        $peak = $ftlModel['stats']['peak_day'] ?? null;
        $pool = $peak ? ($ftlModel['days'][$peak]['articles'] ?? []) : [];
        $ftlItemsLabel = $peak ? 'Peak coverage · ' . sn_tl_fmt_day($peak, 'M j') : 'Key coverage';
    }
    $pool = array_values(array_filter($pool, static fn($a) => $a['tier'] === 'core'));
    usort($pool, static fn($a, $b) => [$b['dt'], $b['id']] <=> [$a['dt'], $a['id']]);
    $seenHost = [];
    foreach ($pool as $a) {
        if (isset($seenHost[$a['host']]) && count($pool) > 6) {
            continue; // prefer a mix of publishers
        }
        $seenHost[$a['host']] = true;
        $ftlItems[] = $a;
        if (count($ftlItems) === 3) {
            break;
        }
    }
}
$ftlLeadHasImage = $ftlItems && !empty($ftlStory['hero_image']) && preg_match('#^https?://#i', $ftlItems[0]['image']);

// Preview outline (no model): the last chapters of the editorial outline
$ftlOutline = [];
if (!$ftlModel && $ftlChapters) {
    $ftlOutline = array_slice($ftlChapters, -3, 3, true);
}

$ftlStats = $ftlModel['stats'] ?? null;
$ftlAsOf = $ftlModel ? (new DateTimeImmutable('@' . (int)$ftlModel['generated_at']))->setTimezone($ftlTz)->format('M j') : null;
$ftlWindowText = sn_tl_fmt_day($ftlStart, 'M j, Y') . ' – ' . ($ftlActive ? 'now' : sn_tl_fmt_day($ftlEnd, 'M j, Y'));
?>
<section class="sn-ftl sn-ftl-theme-<?= sn_tl_e($ftlSlug) ?><?= $ftlModel ? '' : ' is-preview' ?><?= $ftlActive ? ' is-active' : '' ?>" aria-labelledby="snFtlTitle" data-sn-ftl>
  <div class="sn-ftl-band">

    <div class="sn-ftl-top">
      <span class="sn-ftl-eyebrow">Featured Coverage Timeline</span>
      <?php if ($ftlActive): ?>
        <span class="sn-ftl-pill sn-ftl-pill-live"><span class="sn-ftl-dot" aria-hidden="true"></span>Developing · Updates automatically</span>
      <?php else: ?>
        <span class="sn-ftl-pill">Archived story · <?= sn_tl_e($ftlWindowText) ?></span>
      <?php endif; ?>
      <a class="sn-ftl-all" href="/timeline.php" data-loading>All timelines <span aria-hidden="true">→</span></a>
    </div>

    <div class="sn-ftl-grid">

      <div class="sn-ftl-main">
        <p class="sn-ftl-kicker"><?= sn_tl_e($ftlStory['kicker'] ?? '') ?></p>
        <h2 class="sn-ftl-title" id="snFtlTitle"><a href="<?= sn_tl_e($ftlHref) ?>" data-loading><?= sn_tl_e($ftlStory['title']) ?></a></h2>
        <p class="sn-ftl-dek"><?= sn_tl_e($ftlStory['dek'] ?? '') ?></p>

        <?php if ($ftlStats): ?>
          <dl class="sn-ftl-stats">
            <div><dt>Articles</dt><dd><?= number_format($ftlStats['core_articles']) ?></dd></div>
            <div><dt>Publishers</dt><dd><?= number_format($ftlStats['publishers']) ?></dd></div>
            <div><dt>Chapters</dt><dd><?= count($ftlChapters) ?></dd></div>
          </dl>
          <p class="sn-ftl-asof"><span class="sn-ftl-tag sn-ftl-tag-data">From the data</span> Counts from archived articles, as of <?= sn_tl_e($ftlAsOf) ?>.</p>
        <?php else: ?>
          <p class="sn-ftl-asof"><span class="sn-ftl-tag sn-ftl-tag-edit">Editor's note</span> Chapter outline preview · <?= count($ftlChapters) ?> chapters, <?= sn_tl_e($ftlWindowText) ?>.</p>
        <?php endif; ?>

        <a class="sn-ftl-cta" href="<?= sn_tl_e($ftlHref) ?>" data-loading>Open the timeline <span aria-hidden="true">→</span></a>
      </div>

      <div class="sn-ftl-viz">
        <?php if ($ftlSeries): ?>
          <div class="sn-ftl-sky"><?php sn_tl_render_sparkline($ftlSeries, ['height' => 64]); ?></div>
        <?php else: ?>
          <div class="sn-ftl-sky sn-ftl-sky-empty" aria-hidden="true"></div>
        <?php endif; ?>

        <?php if ($ftlNodes || $ftlHasLatest): ?>
          <ol class="sn-ftl-rail" aria-label="Chapters of the <?= sn_tl_e($ftlStory['title']) ?> timeline">
            <?php foreach ($ftlChapters as $i => $ch):
                $mc = $ftlModel['chapters'][$i]['core_articles'] ?? null; ?>
              <li class="sn-ftl-node" style="--p: <?= round($ftlNodes[$i], 2) ?>%; --i: <?= (int)$i ?>">
                <a href="<?= sn_tl_e($ftlHref) ?>#chapter-<?= (int)$i ?>" data-loading
                   aria-label="Chapter <?= $i + 1 ?>: <?= sn_tl_e($ch['title']) ?>, <?= sn_tl_e($ftlRange($ch)) ?>">
                  <span class="sn-ftl-pt"><?= $i + 1 ?></span>
                  <span class="sn-ftl-tip" role="tooltip">
                    <strong><?= sn_tl_e($ch['title']) ?></strong>
                    <span><?= sn_tl_e($ftlRange($ch)) ?><?= $mc !== null ? ' · ' . (int)$mc . ' articles' : '' ?></span>
                  </span>
                </a>
              </li>
            <?php endforeach; ?>
            <?php if ($ftlHasLatest): ?>
              <li class="sn-ftl-node sn-ftl-node-latest" style="--p: 97.5%; --i: <?= count($ftlChapters) ?>">
                <a href="<?= sn_tl_e($ftlHref) ?>#latest" data-loading aria-label="Latest coverage, awaiting editorial review">
                  <span class="sn-ftl-pt" aria-hidden="true"></span>
                  <span class="sn-ftl-tip" role="tooltip">
                    <strong>Latest coverage</strong>
                    <span>Awaiting editorial review<?= $ftlModel && ($ftlStats['latest_core'] ?? 0) ? ' · ' . (int)$ftlStats['latest_core'] . ' articles' : '' ?></span>
                  </span>
                </a>
              </li>
            <?php endif; ?>
          </ol>

          <ul class="sn-ftl-chips" aria-label="Chapters">
            <?php foreach ($ftlChapters as $i => $ch): ?>
              <li><a href="<?= sn_tl_e($ftlHref) ?>#chapter-<?= (int)$i ?>" data-loading>
                <span class="sn-ftl-chip-n"><?= $i + 1 ?></span>
                <span class="sn-ftl-chip-t"><?= sn_tl_e($ch['title']) ?></span>
                <span class="sn-ftl-chip-d"><?= sn_tl_e($ftlRange($ch)) ?></span>
              </a></li>
            <?php endforeach; ?>
            <?php if ($ftlHasLatest): ?>
              <li class="is-latest"><a href="<?= sn_tl_e($ftlHref) ?>#latest" data-loading>
                <span class="sn-ftl-chip-n" aria-hidden="true">•</span>
                <span class="sn-ftl-chip-t">Latest coverage</span>
                <span class="sn-ftl-chip-d">Awaiting review</span>
              </a></li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>

        <div class="sn-ftl-axis" aria-hidden="true">
          <span><?= sn_tl_e(sn_tl_fmt_day($ftlStart, 'M j, Y')) ?></span>
          <span><?= $ftlActive ? 'Today' : sn_tl_e(sn_tl_fmt_day($ftlEnd, 'M j, Y')) ?></span>
        </div>

        <?php if ($ftlItems): ?>
          <div class="sn-ftl-latest">
            <div class="sn-ftl-latest-head">
              <span class="sn-ftl-latest-label"><?= sn_tl_e($ftlItemsLabel) ?></span>
              <?php if ($ftlActive && ($ftlModel['review']['latest_from'] ?? null) !== null): ?>
                <span class="sn-ftl-tag sn-ftl-tag-pending">Awaiting editorial review</span>
              <?php else: ?>
                <span class="sn-ftl-tag sn-ftl-tag-data">From the data</span>
              <?php endif; ?>
            </div>
            <ul class="sn-ftl-items">
              <?php foreach ($ftlItems as $k => $a):
                  $img = ($k === 0 && $ftlLeadHasImage) ? $a['image'] : ''; ?>
                <li class="sn-ftl-item<?= $k === 0 ? ' is-lead' : '' ?>">
                  <?php if ($k === 0): ?>
                    <span class="sn-ftl-thumb<?= $img === '' ? ' is-type' : '' ?>" aria-hidden="true">
                      <?php if ($img !== ''): ?>
                        <img src="<?= sn_tl_e($img) ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"
                             onerror="this.parentNode.classList.add('is-type');this.remove();">
                      <?php endif; ?>
                      <span class="sn-ftl-mono"><?= sn_tl_e(strtoupper(mb_substr($a['host'], 0, 1))) ?></span>
                    </span>
                  <?php endif; ?>
                  <div class="sn-ftl-item-body">
                    <div class="sn-ftl-item-meta">
                      <span class="sn-ftl-item-host"><?= sn_tl_e($a['host']) ?></span>
                      <time datetime="<?= sn_tl_e($a['iso']) ?>"><?= sn_tl_e($a['dt']->format('M j')) ?></time>
                    </div>
                    <a class="sn-ftl-item-title link-read article-link" href="<?= sn_tl_e($a['url']) ?>" target="_blank" rel="noopener noreferrer"
                       data-article-url="<?= sn_tl_e($a['url']) ?>"
                       data-article-title="<?= sn_tl_e($a['title']) ?>"
                       data-article-source="<?= sn_tl_e($a['category']) ?>"
                       data-article-image="<?= sn_tl_e($a['image']) ?>"
                       data-article-pub-date="<?= sn_tl_e($a['iso']) ?>"
                       data-article-kind="external"><?= sn_tl_e($a['title']) ?></a>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php elseif ($ftlOutline): ?>
          <div class="sn-ftl-latest">
            <div class="sn-ftl-latest-head">
              <span class="sn-ftl-latest-label">Chapter outline</span>
              <span class="sn-ftl-tag sn-ftl-tag-edit">Editor's note</span>
            </div>
            <ul class="sn-ftl-items">
              <?php foreach ($ftlOutline as $i => $ch): ?>
                <li class="sn-ftl-item">
                  <div class="sn-ftl-item-body">
                    <div class="sn-ftl-item-meta"><span class="sn-ftl-item-host">Chapter <?= $i + 1 ?></span><span><?= sn_tl_e($ftlRange($ch)) ?></span></div>
                    <a class="sn-ftl-item-title" href="<?= sn_tl_e($ftlHref) ?>#chapter-<?= (int)$i ?>" data-loading><?= sn_tl_e($ch['title']) ?></a>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</section>
