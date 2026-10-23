<?php
// ___story_threads.php
// Story Threads: long-running stories discovered from NLP entity co-tagging over the last 21 days.
// Output is cached as HTML by the 2-minute home warmer (key: homepage_story_threads).
require_once BASE_PATH . '/core/timelines/___timelines.php';
require_once BASE_PATH . '/core/story_threads/___story_threads.php';
require_once BASE_PATH . '/core/topics/___topics.php';

$stE = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

try {
    $stDb = _pdo_or_null();
    if (!$stDb) {
        error_log('[StoryThreads] DB handle not available');
        return;
    }
    $stDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stNow = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $stModel = sn_st_build_model(sn_st_fetch_articles($stDb, $stNow), $stNow);

    // Complement the Featured Coverage Timeline: link curated timelines, skip the featured story
    $stStories = sn_tl_all_stories();
    $stModels = [];
    foreach ($stStories as $stSlug => $stDef) {
        $stModels[$stSlug] = sn_tl_peek_model($stDef);
    }
    $stPick = sn_tl_pick_featured($stStories, $stModels);
    $stThreads = sn_st_link_timelines($stModel['threads'], $stStories, $stPick['story']['slug'] ?? null);
    $stTopics = $stModel['topics'];
} catch (Throwable $e) {
    error_log('[StoryThreads] ' . $e->getMessage());
    return;
}

if (!$stThreads && !$stTopics) {
    return;
}

$stTz = new DateTimeZone(SN_ST_TZ);
$stDays = $stModel['days'];
$stDayLabel = static fn(string $ymd): string => (new DateTimeImmutable($ymd, new DateTimeZone(SN_ST_TZ)))->format('M j');
$stPlural = static fn(int $n, string $one, string $many): string => $n . ' ' . ($n === 1 ? $one : $many);
$stNlp = static fn(string $q, string $mode = 'nlp'): string => '/search.php?' . http_build_query([
    'q' => $q, 'range' => 'all', 'mode' => $mode, 'deep_dive' => '', 'high_signal' => '',
]);

// 21-bar pulse strip. Bars scale to the thread's own peak; the final (partial) day is hatched.
$stPulse = static function (array $series, int $peak, array $days, string $label) use ($stE, $stDayLabel): void {
    $n = count($series);
    $w = 7;
    $gap = 3;
    $h = 44;
    $total = $n * ($w + $gap) - $gap;
    echo '<svg class="sn-st-pulse" viewBox="0 0 ' . $total . ' ' . $h . '" preserveAspectRatio="none" role="img" aria-label="' . $stE($label) . '" focusable="false">';
    foreach ($series as $i => $c) {
        $bh = $c > 0 ? max(4, (int)round($c / max(1, $peak) * ($h - 2))) : 2;
        $cls = 'sn-st-bar' . ($c === 0 ? ' is-zero' : '') . ($c === $peak && $peak > 0 ? ' is-peak' : '') . ($i === $n - 1 ? ' is-today' : '');
        echo '<rect class="' . $cls . '" x="' . ($i * ($w + $gap)) . '" y="' . ($h - $bh) . '" width="' . $w . '" height="' . $bh . '" rx="1.5">'
            . '<title>' . $stE($stDayLabel($days[$i]) . ': ' . $c . ($c === 1 ? ' article' : ' articles')) . '</title></rect>';
    }
    echo '</svg>';
};

$stUpdated = $stModel['asof'];
?>
<section class="sn-st" aria-labelledby="snStTitle" data-sn-st>
  <script>
    (function () {
      var s = document.currentScript, el = s && s.parentElement;
      if (!el) return;
      if (!('IntersectionObserver' in window)) { el.classList.add('is-in'); return; }
      var io = new IntersectionObserver(function (es) {
        es.forEach(function (e) { if (e.isIntersecting) { el.classList.add('is-in'); io.disconnect(); } });
      }, { rootMargin: '0px 0px -10% 0px' });
      io.observe(el);
    })();
  </script>

  <header class="sn-st-head">
    <div class="sn-st-head-copy">
      <p class="sn-st-eyebrow"><span class="sn-st-eyebrow-dot" aria-hidden="true"></span>Story Threads</p>
      <h2 class="sn-st-title" id="snStTitle">Stories still unfolding, and what they connect to</h2>
      <p class="sn-st-dek">Each thread is a story that has kept appearing across publishers for weeks. The strip shows daily coverage; the chips show the people and places most often tagged alongside it.</p>
    </div>
    <p class="sn-st-pill">Last 21 days · updated <time datetime="<?= $stE(gmdate('c', $stUpdated)) ?>" data-sn-time><?= $stE((new DateTimeImmutable('@' . $stUpdated))->setTimezone($stTz)->format('M j, g:i A')) ?></time></p>
  </header>

  <div class="sn-st-live sn-st-sr" role="status" aria-live="polite" data-st-live></div>

  <?php if ($stThreads): ?>
    <ol class="sn-st-list">
      <?php foreach ($stThreads as $ti => $t):
          $tid = 'snSt' . $stE($t['id']);
          $statusKey = $t['status']['key'];
          $tl = $t['timeline'];
          $followHref = $tl ? '/timeline.php?story=' . rawurlencode($tl['slug']) : $stNlp($t['name']);
          $followLabel = $tl ? 'Open the Coverage Timeline' : 'Follow this story';
          $pulseLabel = 'Daily coverage for ' . $t['name'] . ', ' . $stDayLabel($stDays[0]) . ' to ' . $stDayLabel($stDays[count($stDays) - 1])
              . '. Peak: ' . $stPlural($t['peak'], 'article', 'articles') . ' on ' . $stDayLabel($t['peak_day']) . '.';
          $cast = $t['cast'];
          $castByEntity = [];
          foreach ($cast as $m) {
              $castByEntity[$m['entity']] = $m;
          }
          $previews = $t['previews'];
          $primary = array_slice($previews, 0, 2);
          $more = array_slice($previews, 2);
          $memberCount = count($cast);
      ?>
      <li class="sn-st-thread sn-st-s-<?= $stE($statusKey) ?><?= $ti === 0 ? ' is-open' : '' ?>" data-st-thread style="--st-i: <?= (int)$ti ?>">
        <div class="sn-st-main">
          <button type="button" class="sn-st-toggle" aria-expanded="<?= $ti === 0 ? 'true' : 'false' ?>" aria-controls="<?= $tid ?>-body" data-st-toggle>
            <span class="sn-st-toggle-text">
              <span class="sn-st-kicker"><?= $stE($stPlural($t['publishers'], 'publisher', 'publishers')) ?> · <?= $stE($stDayLabel($t['first'])) ?> – <?= $stE($stDayLabel($t['last'])) ?></span>
              <span class="sn-st-name"><?= $stE($t['name']) ?></span>
            </span>
            <span class="sn-st-status sn-st-status-<?= $stE($statusKey) ?>"><span class="sn-st-glyph" aria-hidden="true"></span><?= $stE($t['status']['label']) ?></span>
            <span class="sn-st-caret" aria-hidden="true"></span>
          </button>

          <div class="sn-st-body" id="<?= $tid ?>-body" data-st-body>
            <p class="sn-st-why"><?= $stE($t['status']['why']) ?></p>
            <p class="sn-st-stats"><strong><?= (int)$t['n'] ?></strong> articles · <strong><?= (int)$t['active'] ?></strong> of 21 days</p>

            <div class="sn-st-pulse-wrap">
              <?php $stPulse($t['series'], (int)$t['peak'], $stDays, $pulseLabel); ?>
              <div class="sn-st-pulse-axis" aria-hidden="true"><span><?= $stE($stDayLabel($stDays[0])) ?></span><span>Today</span></div>
            </div>

            <?php if ($tl): ?>
              <p class="sn-st-tl"><span class="sn-st-tl-tag">Coverage Timeline</span>
                <?= $stE($tl['title']) ?><?php if (!empty($tl['chapter']['title'])): ?> · latest chapter: <?= $stE($tl['chapter']['title']) ?><?php endif; ?></p>
            <?php else: ?>
              <p class="sn-st-tl is-auto"><span class="sn-st-tl-tag">Discovered by analysis</span> Grouped automatically from entity tags, not editorially curated.</p>
            <?php endif; ?>

            <div class="sn-st-cast" role="group" aria-label="Frequently tagged with <?= $stE($t['name']) ?>">
              <span class="sn-st-cast-label">Tagged together</span>
              <?php foreach ($cast as $m): ?>
                <button type="button" class="sn-st-chip" aria-pressed="false" data-st-chip="<?= $stE($m['entity']) ?>">
                  <?= $stE($m['name']) ?><span class="sn-st-chip-n"><?= (int)$m['shared'] ?></span>
                  <span class="sn-st-sr"> of <?= (int)$t['n'] ?> articles</span>
                </button>
              <?php endforeach; ?>
            </div>

            <div class="sn-st-actions">
              <a class="sn-st-btn is-primary" href="<?= $stE($followHref) ?>" data-loading><?= $stE($followLabel) ?> <span aria-hidden="true">→</span></a>
              <button type="button" class="sn-st-btn" aria-expanded="false" aria-controls="<?= $tid ?>-web" data-st-web-toggle>Connections</button>
              <details class="sn-st-more" data-st-more>
                <summary class="sn-st-btn">More</summary>
                <ul class="sn-st-more-menu">
                  <li><a href="<?= $stE($stNlp($t['name'], 'nlp')) ?>" data-loading>Scroll NLP search</a></li>
                  <li><a href="<?= $stE($stNlp($t['name'], 'classic')) ?>" data-loading>Classic search</a></li>
                  <li><a href="https://www.google.com/search?<?= $stE(http_build_query(['q' => "what's happening with " . $t['name'], 'udm' => '50'])) ?>" target="_blank" rel="noopener noreferrer">Synopsis</a></li>
                  <li><a href="https://www.google.com/search?<?= $stE(http_build_query(['q' => $t['name']])) ?>" target="_blank" rel="noopener noreferrer">Google</a></li>
                  <li><a href="https://www.youtube.com/results?<?= $stE(http_build_query(['search_query' => $t['name']])) ?>" target="_blank" rel="noopener noreferrer">YouTube</a></li>
                </ul>
              </details>
            </div>

            <div class="sn-st-web" id="<?= $tid ?>-web" hidden data-st-web>
              <div class="sn-st-web-bar">
                <h3 class="sn-st-web-title">How <?= $stE($t['name']) ?> connects</h3>
                <div class="sn-st-seg" role="group" aria-label="Connections view">
                  <button type="button" class="is-active" aria-pressed="true" data-st-view="table">Table</button>
                  <button type="button" aria-pressed="false" data-st-view="web">Web</button>
                </div>
              </div>

              <div class="sn-st-web-table" data-st-table>
                <table>
                  <caption class="sn-st-sr">Articles in the <?= $stE($t['name']) ?> thread that are also tagged with each connected entity, and entity pairs tagged together</caption>
                  <thead><tr><th scope="col">Tagged together</th><th scope="col">Articles</th><th scope="col">Share of thread</th></tr></thead>
                  <tbody>
                    <?php foreach ($cast as $m): ?>
                      <tr>
                        <th scope="row"><?= $stE($t['name']) ?> + <?= $stE($m['name']) ?></th>
                        <td><?= (int)$m['shared'] ?></td>
                        <td><?= (int)$m['pct'] ?>%</td>
                      </tr>
                    <?php endforeach; ?>
                    <?php foreach ($t['pairs'] as $p): ?>
                      <tr class="is-pair">
                        <th scope="row"><?= $stE($castByEntity[$p['a']]['name']) ?> + <?= $stE($castByEntity[$p['b']]['name']) ?></th>
                        <td><?= (int)$p['shared'] ?></td>
                        <td><span class="sn-st-muted">all articles</span></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div class="sn-st-web-graph" data-st-graph hidden>
                <?php
                  $pos = ['__lead' => [50, 50]];
                  foreach ($cast as $k => $m) {
                      $ang = -M_PI / 2 + 2 * M_PI * $k / max(1, $memberCount);
                      $pos[$m['entity']] = [round(50 + 36 * cos($ang), 2), round(50 + 34 * sin($ang), 2)];
                  }
                  $maxShared = max(1, ...array_map(static fn($m) => $m['shared'], $cast));
                ?>
                <div class="sn-st-graph-stage" aria-hidden="true">
                  <svg viewBox="0 0 100 100" preserveAspectRatio="none" focusable="false">
                    <?php foreach ($t['pairs'] as $p): ?>
                      <line class="sn-st-edge is-pair" x1="<?= $pos[$p['a']][0] ?>" y1="<?= $pos[$p['a']][1] ?>" x2="<?= $pos[$p['b']][0] ?>" y2="<?= $pos[$p['b']][1] ?>" style="stroke-width: <?= round(0.8 + 1.2 * $p['shared'] / $maxShared, 2) ?>px"/>
                    <?php endforeach; ?>
                    <?php foreach ($cast as $m): ?>
                      <line class="sn-st-edge" data-st-edge="<?= $stE($m['entity']) ?>" x1="50" y1="50" x2="<?= $pos[$m['entity']][0] ?>" y2="<?= $pos[$m['entity']][1] ?>" style="stroke-width: <?= round(1.5 + 5 * $m['shared'] / $maxShared, 2) ?>px"/>
                    <?php endforeach; ?>
                  </svg>
                  <span class="sn-st-node is-lead" style="left: 50%; top: 50%"><?= $stE($t['name']) ?></span>
                  <?php foreach ($cast as $m): ?>
                    <button type="button" class="sn-st-node" tabindex="-1" data-st-node="<?= $stE($m['entity']) ?>" style="left: <?= $pos[$m['entity']][0] ?>%; top: <?= $pos[$m['entity']][1] ?>%">
                      <?= $stE($m['name']) ?><span class="sn-st-node-n"><?= (int)$m['shared'] ?></span>
                    </button>
                  <?php endforeach; ?>
                </div>
                <p class="sn-st-web-note" data-st-note>Select a name to see how often it is tagged with <?= $stE($t['name']) ?>.</p>
                <a class="sn-st-web-read" href="#" hidden data-st-read data-st-lead="<?= $stE($t['name']) ?>">Read these articles <span aria-hidden="true">→</span></a>
              </div>

              <p class="sn-st-foot">Line thickness and numbers are counts of articles tagged with both names. Co-tagging shows that names appear together, not that one causes or explains the other.</p>
            </div>
          </div>
        </div>

        <div class="sn-st-previews" data-st-previews>
          <?php
            $stRenderPreview = static function (array $a, bool $extra) use ($stE, $stTz): void {
                $url = (string)$a['url'];
                $title = (string)$a['title'];
                $dt = (new DateTimeImmutable('@' . (int)$a['ts']))->setTimezone($stTz);
                $iso = $dt->format('c');
                $host = (string)$a['host'];
                $cat = (string)$a['cat'];
                $catSlug = trim((string)preg_replace('/\s+/', '-', strtolower($cat)));
                $profile = $host !== '' ? (sn_publisher_profile_url($host) ?: '') : '';
                $emoji = function_exists('fl_headline_emoji') ? (fl_headline_emoji($title) ?: '📰') : '📰';
                $qs = http_build_query(['url' => $url, 'category' => $cat, 'pub_date' => (int)$a['ts'], 'db' => 1]);
                ?>
                <article class="sn-st-prev" data-ents="<?= $stE(implode('|', $a['ents'])) ?>">
                  <?php if ($a['image'] !== ''): ?>
                    <img class="sn-st-prev-img" src="<?= $stE($a['image']) ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer" width="96" height="72">
                  <?php endif; ?>
                  <div class="sn-st-prev-body">
                    <p class="sn-st-prev-meta">
                      <?php if ($a['badge'] !== ''): ?><span class="sn-st-badge"><?= $stE($a['badge']) ?></span><?php endif; ?>
                      <?php if ($host !== ''): ?>
                        <img class="sn-st-fav" src="https://www.google.com/s2/favicons?sz=64&amp;domain=<?= $stE(rawurlencode($host)) ?>" alt="" width="14" height="14" loading="lazy" decoding="async" referrerpolicy="no-referrer">
                        <?php if ($profile): ?><a href="<?= $stE($profile) ?>" data-loading><?= $stE($host) ?></a><?php else: ?><span><?= $stE($host) ?></span><?php endif; ?>
                      <?php endif; ?>
                      <time datetime="<?= $stE($iso) ?>" data-sn-time><?= $stE($dt->format('M j, g:i A')) ?></time>
                    </p>
                    <h3 class="sn-st-prev-title"><a href="<?= $stE($url) ?>" target="_blank" rel="noopener noreferrer"
                       data-article-url="<?= $stE($url) ?>"
                       data-article-title="<?= $stE($title) ?>"
                       data-article-source="<?= $stE($cat) ?>"
                       data-article-image="<?= $stE($a['image']) ?>"
                       data-article-pub-date="<?= $stE($iso) ?>"
                       data-article-kind="external"><?= $stE($emoji . ' ' . $title) ?></a></h3>
                    <?php if ($a['desc'] !== ''): ?><p class="sn-st-prev-desc"><?= $stE($a['desc']) ?></p><?php endif; ?>
                    <p class="sn-st-prev-links">
                      <a class="sn-st-link" href="<?= $stE($url) ?>" target="_blank" rel="noopener noreferrer"
                         data-article-url="<?= $stE($url) ?>"
                         data-article-title="<?= $stE($title) ?>"
                         data-article-source="<?= $stE($cat) ?>"
                         data-article-image="<?= $stE($a['image']) ?>"
                         data-article-pub-date="<?= $stE($iso) ?>"
                         data-article-kind="external">Read story</a>
                      <a class="sn-st-link" href="/newsroom.php?<?= $stE($qs) ?>" data-loading>Analyze</a>
                      <?php if ($catSlug !== ''): ?><span class="sn-st-link is-quiet"><?= $stE($cat) ?></span><?php endif; ?>
                    </p>
                  </div>
                </article>
                <?php
            };
            foreach ($primary as $a) { $stRenderPreview($a, false); }
          ?>
          <?php if ($more): ?>
            <details class="sn-st-morearticles" data-st-morearticles>
              <summary>Show <?= (int)count($more) ?> more <?= count($more) === 1 ? 'article' : 'articles' ?></summary>
              <?php foreach ($more as $a) { $stRenderPreview($a, true); } ?>
            </details>
          <?php endif; ?>
          <p class="sn-st-filter-note" hidden data-st-filter-note></p>
        </div>
      </li>
      <?php endforeach; ?>
    </ol>
  <?php endif; ?>

  <?php if ($stTopics): ?>
    <div class="sn-st-standing" aria-labelledby="snStStanding">
      <h3 class="sn-st-standing-title" id="snStStanding">Always in the news</h3>
      <ul class="sn-st-topics">
        <?php foreach ($stTopics as $tp):
            $sp = $tp['series'];
            $spMax = max(1, max($sp));
            $pts = [];
            foreach ($sp as $i => $c) {
                $pts[] = round($i * 4, 1) . ',' . round(14 - $c / $spMax * 13, 1);
            }
        ?>
          <li class="sn-st-topic">
            <a href="<?= $stE(sn_topic_url($tp['topic'])) ?>" data-loading>
              <span class="sn-st-topic-name"><?= $stE($tp['label']) ?></span>
              <span class="sn-st-topic-n"><?= (int)$tp['n'] ?><span class="sn-st-sr"> articles in 21 days</span></span>
              <svg class="sn-st-spark" viewBox="0 0 80 15" aria-hidden="true" focusable="false"><polyline points="<?= $stE(implode(' ', $pts)) ?>"/></svg>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</section>
