<?php
/**
 * ___first_look.php
 * First Look panel (top 1–2 items per publisher) with file cache
 *
 * Goal: NEVER block homepage on cold RSS fetches.
 * Strategy: stale-while-revalidate (render cached immediately, refresh async if stale).
 */

require_once __DIR__ . '/___modules.php';

$FIRST_LOOK_TTL_SECONDS = 600; // 10 min
$FIRST_LOOK_MAX_ITEMS_PER_FEED = 2;

// Pick 12–15 stable feeds for MVP (you can expand later)
$FIRST_LOOK_FEEDS = [
  ['name'=>'Reuters',        'rss'=>'http://feeds.reuters.com/reuters/topNews',                 'url'=>'https://www.reuters.com'],
  ['name'=>'BBC',            'rss'=>'http://feeds.bbci.co.uk/news/rss.xml',                      'url'=>'https://www.bbc.com/news'],
  ['name'=>'NBC',            'rss'=>'http://feeds.nbcnews.com/feeds/topstories',                 'url'=>'https://www.nbcnews.com'],
  ['name'=>'Fox',            'rss'=>'http://feeds.foxnews.com/foxnews/latest',                   'url'=>'https://www.foxnews.com'],
  ['name'=>'NYT',            'rss'=>'http://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml',  'url'=>'https://www.nytimes.com'],
  ['name'=>'The Hill',       'rss'=>'http://thehill.com/rss/syndicator/19110',                   'url'=>'https://thehill.com'],
  ['name'=>'The Guardian',   'rss'=>'https://www.theguardian.com/business/economics/rss',       'url'=>'https://www.theguardian.com'],
  ['name'=>'CNBC',           'rss'=>'https://www.cnbc.com/id/100003114/device/rss/rss.html',     'url'=>'https://www.cnbc.com'],
  ['name'=>'MarketWatch',    'rss'=>'http://feeds.marketwatch.com/marketwatch/topstories/',     'url'=>'https://www.marketwatch.com'],
  ['name'=>'BusinessInsider','rss'=>'http://feeds2.feedburner.com/businessinsider',             'url'=>'https://www.businessinsider.com'],
  ['name'=>'Daily Mail',     'rss'=>'http://www.dailymail.co.uk/articles.rss',                   'url'=>'https://www.dailymail.co.uk'],
  ['name'=>'NY Post',        'rss'=>'https://nypost.com/feed/',                                  'url'=>'https://nypost.com'],
  ['name'=>'Drudge',         'rss'=>'http://feeds.feedburner.com/DrudgeReportFeed',             'url'=>'https://drudgereport.com'],
];

function fl_cache_dir(): string {
  $dir = __DIR__ . '/cache';
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  return $dir;
}

function fl_cache_path(): string {
  return fl_cache_dir() . '/first_look.json';
}

function fl_lock_path(string $cachePath): string {
  return $cachePath . '.lock';
}

function fl_safe_get_json(string $path): ?array {
  if (!is_file($path)) return null;
  $raw = @file_get_contents($path);
  if ($raw === false) return null;
  $data = json_decode($raw, true);
  return is_array($data) ? $data : null;
}

function fl_safe_put_json(string $path, array $data): void {
  $tmp = $path . '.tmp';
  @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX);
  @rename($tmp, $path);
}

function fl_fetch_rss_top_items(string $rssUrl, int $maxItems = 2): array {
  $ctx = stream_context_create([
    'http' => [
      'timeout' => 4,
      'user_agent' => 'ScrollNewsFirstLook/1.0 (+https://scroll.news)',
      'header' => "Accept: application/rss+xml, application/xml;q=0.9, */*;q=0.8\r\n",
    ],
    'ssl' => [
      'verify_peer' => true,
      'verify_peer_name' => true,
    ],
  ]);

  $xml = @file_get_contents($rssUrl, false, $ctx);
  if (!$xml) return [];

  // Some feeds contain invalid chars; try a light cleanup
  $xml = preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x80-\xFF]/', '', $xml);

  libxml_use_internal_errors(true);
  $feed = @simplexml_load_string($xml);
  if (!$feed) return [];

  $items = [];

  // RSS 2.0: channel->item; Atom: entry
  if (isset($feed->channel->item)) {
    foreach ($feed->channel->item as $it) {
      $title = trim((string)($it->title ?? ''));
      $link  = trim((string)($it->link ?? ''));
      $date  = trim((string)($it->pubDate ?? $it->date ?? ''));

      if ($title === '' || $link === '') continue;
      $ts = $date ? strtotime($date) : 0;

      $items[] = ['title'=>$title, 'url'=>$link, 'ts'=>$ts ?: 0, 'emo'=>fl_headline_emoji($title)];
      if (count($items) >= $maxItems) break;
    }
  } elseif (isset($feed->entry)) {
    foreach ($feed->entry as $it) {
      $title = trim((string)($it->title ?? ''));
      $link  = '';

      if (isset($it->link)) {
        foreach ($it->link as $ln) {
          $href = (string)($ln['href'] ?? '');
          if ($href) { $link = $href; break; }
        }
      }

      $date = trim((string)($it->updated ?? $it->published ?? ''));

      if ($title === '' || $link === '') continue;
      $ts = $date ? strtotime($date) : 0;

      $items[] = ['title'=>$title, 'url'=>$link, 'ts'=>$ts ?: 0, 'emo'=>fl_headline_emoji($title)];
      if (count($items) >= $maxItems) break;
    }
  }

  return $items;
}

function fl_build_payload(array $feeds, int $perFeed): array {
  $out = [];
  foreach ($feeds as $f) {
    if (empty($f['rss'])) continue;

    $items = fl_fetch_rss_top_items($f['rss'], $perFeed);
    if (!$items) continue;

    $out[] = [
      'name'  => $f['name'],
      'home'  => $f['url'],
      'items' => $items,
    ];
  }

  // Sort publishers by most-recent item so the panel feels “alive”
  usort($out, function($a, $b){
    $at = $a['items'][0]['ts'] ?? 0;
    $bt = $b['items'][0]['ts'] ?? 0;
    return $bt <=> $at;
  });

  return $out;
}

/**
 * Attempt a refresh but never block page render.
 * If we can get the lock, we register a shutdown function to refresh cache.
 */
function fl_trigger_background_refresh(string $cachePath, array $feeds, int $perFeed): void {
  $lockFile = fl_lock_path($cachePath);
  $lock = @fopen($lockFile, 'c');
  if (!$lock) return;

  // Non-blocking lock: if another request is refreshing, do nothing.
  if (!@flock($lock, LOCK_EX|LOCK_NB)) {
    @fclose($lock);
    return;
  }

  // Refresh after the response is sent (best-effort).
  register_shutdown_function(function() use ($lock, $cachePath, $feeds, $perFeed) {
    $payload = fl_build_payload($feeds, $perFeed);
    fl_safe_put_json($cachePath, [
      'fetched_at' => time(),
      'feeds' => $payload,
    ]);
    @flock($lock, LOCK_UN);
    @fclose($lock);
  });

  // If PHP-FPM, send response early so user doesn’t wait on shutdown work.
  if (function_exists('fastcgi_finish_request')) {
    @fastcgi_finish_request();
  }
}

// ----------------- Cache read + refresh decision -----------------

$cachePath = fl_cache_path();
$cached = fl_safe_get_json($cachePath);
$now = time();

$hasCache = is_array($cached) && isset($cached['fetched_at'], $cached['feeds']) && is_array($cached['feeds']);
$age = $hasCache ? ($now - (int)$cached['fetched_at']) : PHP_INT_MAX;
$isFresh = $hasCache && $age >= 0 && $age < $FIRST_LOOK_TTL_SECONDS;

// Warm mode: hit ___first_look.php?warm=1 to prime cache without full UI
if (isset($_GET['warm']) && $_GET['warm'] == '1') {
  if (!$isFresh) {
    // In warm mode, it’s OK to do the work (explicit call)
    $payload = fl_build_payload($FIRST_LOOK_FEEDS, $FIRST_LOOK_MAX_ITEMS_PER_FEED);
    fl_safe_put_json($cachePath, [
      'fetched_at' => $now,
      'feeds' => $payload,
    ]);
    $cached = fl_safe_get_json($cachePath);
  }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'ok' => true,
    'fresh' => $isFresh,
    'age_seconds' => $hasCache ? $age : null,
    'feeds_count' => is_array($cached['feeds'] ?? null) ? count($cached['feeds']) : 0,
    'cache_path' => basename($cachePath),
  ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  return;
}

// If no cache yet: render fast placeholder (don’t fetch on homepage)
if (!$hasCache) {
  ?>
  <div class="toy-box firstlook-box">
    <div class="firstlook-head">
      <div>
        <div class="firstlook-title">First Look</div>
        <div class="firstlook-sub">Warming up…</div>
      </div>
      <?php // <a class="firstlook-more" href="/first-look.php">See more →</a> ?>
    </div>
    <div style="color:rgba(255,255,255,.6);font-size:12px;padding:0 4px 6px;">
      First Look is building its cache. Refresh in a moment.
    </div>
  </div>
  <?php
  // Kick a background refresh attempt (best-effort) so it fills soon.
  fl_trigger_background_refresh($cachePath, $FIRST_LOOK_FEEDS, $FIRST_LOOK_MAX_ITEMS_PER_FEED);
  return;
}

// Cache exists: render immediately (even if stale)
$feedsData = $cached['feeds'] ?? [];
$fetchedAt = (int)($cached['fetched_at'] ?? 0);
$ageSec = $fetchedAt ? max(0, $now - $fetchedAt) : null;

// If stale: trigger background refresh but do NOT block
if (!$isFresh) {
  fl_trigger_background_refresh($cachePath, $FIRST_LOOK_FEEDS, $FIRST_LOOK_MAX_ITEMS_PER_FEED);
}

// Fail-soft if cache got corrupted
if (!is_array($feedsData) || count($feedsData) === 0) {
  ?>
  <div class="toy-box firstlook-box">
    <div class="firstlook-head">
      <div class="firstlook-title">First Look</div>
      <div class="firstlook-sub">Multi-source snapshot (temporarily unavailable)</div>
    </div>
  </div>
  <?php
  return;
}
?>

<style>
.firstlook-box{
  margin-top: 14px;
  padding: 14px 14px 10px;
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,.10);
  background: rgba(10,12,20,.55);
  backdrop-filter: blur(10px);
}
.firstlook-head{
  display:flex; align-items:flex-end; gap:10px; justify-content:space-between;
  padding: 2px 4px 10px;
}
.firstlook-title{
  font-weight: 800;
  letter-spacing: .2px;
}
.firstlook-sub{
  color: rgba(255,255,255,.65);
  font-size: 12px;
  white-space: nowrap;
}
.firstlook-grid{
  display:grid;
  gap: 10px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}
@media (min-width: 901px){
  .firstlook-grid{ grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (min-width: 1201px){
  .firstlook-grid{ grid-template-columns: repeat(4, minmax(0, 1fr)); }
}
.firstlook-col{
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 14px;
  overflow:hidden;
  background: rgba(5,7,20,.55);
}
.firstlook-colhead{
  display:flex; align-items:center; justify-content:space-between; gap:10px;
  padding: 10px 10px 8px;
  border-bottom: 1px solid rgba(255,255,255,.06);
}
.firstlook-pill{
  display:inline-flex; align-items:center; gap:8px;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(23,213,255,.12);
  border: 1px solid rgba(23,213,255,.25);
  color: rgba(255,255,255,.92);
  font-weight: 750;
  font-size: 12px;
  text-decoration:none;
}
.firstlook-count{
  color: rgba(255,255,255,.45);
  font-size: 12px;
}
.firstlook-list{
  padding: 10px 12px 12px;
  display:flex; flex-direction:column; gap:10px;
}
.firstlook-item a{
  color: rgba(255,255,255,.92);
  text-decoration:none;
  font-weight: 650;
  line-height: 1.25;
}
.firstlook-item a:hover{ text-decoration: underline; }
.firstlook-time{
  margin-top: 4px;
  color: rgba(255,255,255,.50);
  font-size: 11px;
}
.firstlook-foot{
  display:flex; justify-content:space-between; align-items:center;
  padding: 10px 4px 0;
  color: rgba(255,255,255,.55);
  font-size: 12px;
}
.firstlook-more{
  color: rgba(23,213,255,.95);
  text-decoration:none;
  font-weight: 700;
}
.firstlook-more:hover{ text-decoration: underline; color: rgba(23, 213, 255, .8); }
</style>

<style>

.firstlook-save-btn{
  border: 1px solid rgba(255,255,255,.15);
  background: rgba(255,255,255,.06);
  border-radius: 10px;
  /* padding: 6px 10px; */
  cursor: pointer;
  font-size: .85rem;
  /* margin-left: 10px; */
  white-space: nowrap;
}
.firstlook-save-btn:hover{ background: rgba(255,255,255,.10); }
.firstlook-save-btn.is-saved{
  background: rgba(255,255,255,.14);
}
.firstlook-row{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
}
.firstlook-link{ flex: 1; min-width: 0; }

</style>

<div class="container-fluid">
  <div class="toy-box firstlook-box">
    <div class="firstlook-head">
      <div>
        <div class="sn-time-marker">TODAY</div>
        <div class="firstlook-title">First Look</div>
        <div class="firstlook-sub">
          Front page of front pages<?php if ($ageSec !== null) echo " • updated " . (int)round($ageSec/60) . "m ago"; ?>
        </div>
      </div>
      <a href="saved_headlines.php" class="firstlook-more" style="">Saved Headlines</a>
    </div>

    <div class="firstlook-grid">
      <?php foreach ($feedsData as $f): ?>
        <div class="firstlook-col">
          <div class="firstlook-colhead">
            <a class="firstlook-pill" href="<?php echo htmlspecialchars($f['home']); ?>" target="_blank" rel="noopener">
              <?php echo htmlspecialchars($f['name']); ?>
            </a>
            <div class="firstlook-count"><?php echo count($f['items'] ?? []); ?></div>
          </div>

          <div class="firstlook-list">
            <?php foreach (($f['items'] ?? []) as $it): ?>
              <?php
                // Example variables you likely have:
                $url   = $it['url'] ?? '';
                $title = $it['title'] ?? '';
                $src   = $f['name'] ?? '';
                $pub   = $it['s'] ?? '';
              ?>
              <div class="firstlook-item">
                <a href="<?php echo htmlspecialchars($it['url']); ?>" target="_blank" rel="noopener">
                  <?php echo htmlspecialchars(($it['emo'] ?? '📰') . ' ' . ($it['title'] ?? '')); ?>
                </a>
                <button
                  type="button"
                  class="firstlook-save-btn"
                  data-url="<?= htmlspecialchars($url) ?>"
                  data-title="<?= htmlspecialchars($title) ?>"
                  data-source="<?= htmlspecialchars($src) ?>"
                  data-pub="<?= htmlspecialchars($pub) ?>"
                  aria-label="Save headline"
                >Save</button>
                <?php if (!empty($it['ts'])): ?>
                  <div class="firstlook-time"><?php echo date('g:ia', (int)$it['ts']); ?></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="firstlook-foot">
      <div>One–two headlines per source • cached</div>
      <div></div>
    </div>
  </div>
</div>

<script>
(function () {
  const KEY = 'scrollnews:saved_firstlook:v1';

  function safeParse(json, fallback) {
    try { return JSON.parse(json); } catch { return fallback; }
  }
  function getSaved() {
    return safeParse(localStorage.getItem(KEY) || '[]', []);
  }
  function setSaved(list) {
    localStorage.setItem(KEY, JSON.stringify(list));
  }

  // Stable ID so "same headline" toggles properly
  function makeId(url, title) {
    const s = (url || '') + '|' + (title || '');
    // light hash
    let h = 0;
    for (let i=0; i<s.length; i++) h = ((h<<5) - h) + s.charCodeAt(i) | 0;
    return 'h' + Math.abs(h);
  }

  function isSaved(id) {
    return getSaved().some(x => x && x.id === id);
  }

  function save(item) {
    const list = getSaved();
    if (!list.some(x => x && x.id === item.id)) {
      list.push(item);
      setSaved(list);
    }
  }

  function unsave(id) {
    const list = getSaved().filter(x => x && x.id !== id);
    setSaved(list);
  }

  function setBtnState(btn, saved) {
    btn.textContent = saved ? 'Unsave' : 'Save';
    btn.classList.toggle('is-saved', saved);
    btn.setAttribute('aria-label', saved ? 'Unsave headline' : 'Save headline');
  }

  function initButtons() {
    document.querySelectorAll('.firstlook-save-btn').forEach(btn => {
      const url   = btn.getAttribute('data-url') || '';
      const title = btn.getAttribute('data-title') || '';
      const src   = btn.getAttribute('data-source') || '';
      const pub   = btn.getAttribute('data-pub') || '';

      const id = makeId(url, title);
      btn.dataset.id = id;

      setBtnState(btn, isSaved(id));

      btn.addEventListener('click', () => {
        const savedNow = isSaved(id);

        if (savedNow) {
          unsave(id);
          setBtnState(btn, false);
        } else {
          save({
            id,
            url,
            title,
            source_slug: src,
            pub_date: pub,
            saved_at: Date.now()
          });
          setBtnState(btn, true);
        }

        // update across tabs/pages
        window.dispatchEvent(new StorageEvent('storage', { key: KEY }));
      });
    });
  }

  // Update button states if Saved page or another tab changes localStorage
  window.addEventListener('storage', (e) => {
    if (e.key !== KEY) return;
    document.querySelectorAll('.firstlook-save-btn').forEach(btn => {
      const id = btn.dataset.id;
      if (!id) return;
      setBtnState(btn, isSaved(id));
    });
  });

  initButtons();
})();
</script>
