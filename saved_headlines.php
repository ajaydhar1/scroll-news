<?php
// saved_headlines.php
$page_title = "Saved Headlines";
?>
<!doctype html>
<html lang="en">
<head>
  <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($page_title) ?></title>

  <?php
  // If you have shared includes, use them:
  // require_once __DIR__ . "/___head.php";
  ?>

  <style>
    .saved-wrap { max-width: 980px; margin: 24px auto; padding: 0 16px; }
    .saved-top { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom: 14px; }
    .saved-title { font-size: 1.4rem; font-weight: 700; }
    .saved-sub { opacity: .75; font-size: .95rem; }
    .saved-actions { display:flex; gap:8px; align-items:center; }
    .btn { border: 1px solid rgba(255,255,255,.15); padding: 8px 10px; border-radius: 10px; cursor:pointer; background: rgba(255,255,255,.06); }
    .btn:hover { background: rgba(255,255,255,.10); }
    .saved-list { display:flex; flex-direction:column; gap:10px; margin-top: 14px; }
    .saved-item { border: 1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.04); border-radius: 14px; padding: 12px 12px; display:flex; justify-content:space-between; gap:12px; }
    .saved-left { min-width: 0; }
    .saved-link { font-weight: 650; text-decoration:none; display:inline-block; }
    .saved-link:hover { text-decoration:underline; }
    .saved-meta { margin-top:6px; font-size:.9rem; opacity:.72; display:flex; gap:10px; flex-wrap:wrap; }
    .pill { border:1px solid rgba(255,255,255,.15); border-radius:999px; padding:2px 8px; font-size:.8rem; opacity:.9; }
    .empty { opacity:.75; padding: 18px; border:1px dashed rgba(255,255,255,.2); border-radius: 14px; }
    .muted { opacity: .7; }
  </style>
</head>

<body>
  <?php
  // If you have a site header/nav include:
  // require_once __DIR__ . "/___nav.php";
  ?>

  <div class="saved-wrap">
    <div class="saved-top">
      <div>
        <div class="saved-title">Saved Headlines</div>
        <div class="saved-sub">Your personal “keep list” from First Look.</div>
      </div>

      <div class="saved-actions">
        <a class="btn" href="/">← Home</a>
        <button class="btn" id="clearSavedBtn" type="button">Clear all</button>
      </div>
    </div>

    <div id="savedCount" class="muted"></div>
    <div id="savedList" class="saved-list"></div>
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

  function removeById(id) {
    const list = getSaved().filter(x => x && x.id !== id);
    setSaved(list);
    return list;
  }

  function formatTime(ts) {
    if (!ts) return '';
    const d = new Date(ts);
    if (isNaN(d.getTime())) return '';
    return d.toLocaleString();
  }

  function render() {
    const listEl = document.getElementById('savedList');
    const countEl = document.getElementById('savedCount');

    const items = getSaved()
      .filter(Boolean)
      .sort((a,b) => (b.saved_at||0) - (a.saved_at||0));

    countEl.textContent = items.length ? `${items.length} saved` : '';

    if (!items.length) {
      listEl.innerHTML = `<div class="empty">No saved headlines yet. Go to the homepage → First Look → tap <b>Save</b> on one you like.</div>`;
      return;
    }

    listEl.innerHTML = items.map(item => {
      const title = escapeHtml(item.title || '');
      const url = escapeAttr(item.url || '#');
      const source = escapeHtml(item.source_slug || item.source || '');
      const savedAt = formatTime(item.saved_at);
      const pub = item.pub_date ? escapeHtml(item.pub_date) : '';

      return `
        <div class="saved-item" data-id="${escapeAttr(item.id)}">
          <div class="saved-left">
            <a class="saved-link" href="${url}" target="_blank" rel="noopener noreferrer">${title}</a>
            <div class="saved-meta">
              ${source ? `<span class="pill">${source}</span>` : ''}
              ${pub ? `<span class="pill">${pub}</span>` : ''}
              ${savedAt ? `<span class="pill">Saved: ${escapeHtml(savedAt)}</span>` : ''}
            </div>
          </div>
          <div>
            <button class="btn unsaveBtn" type="button">Unsave</button>
          </div>
        </div>
      `;
    }).join('');

    document.querySelectorAll('.unsaveBtn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const card = e.target.closest('.saved-item');
        const id = card?.getAttribute('data-id');
        if (!id) return;
        removeById(id);
        render();

        // Let First Look update its buttons if it’s open in another tab.
        window.dispatchEvent(new StorageEvent('storage', { key: KEY }));
      });
    });
  }

  document.getElementById('clearSavedBtn').addEventListener('click', () => {
    localStorage.removeItem(KEY);
    render();
    window.dispatchEvent(new StorageEvent('storage', { key: KEY }));
  });

  // Keep UI updated if another tab saves/unsaves
  window.addEventListener('storage', (e) => {
    if (e.key === KEY) render();
  });

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }
  function escapeAttr(s) {
    return String(s).replace(/"/g, '&quot;');
  }

  render();
})();
</script>
</body>
</html>
