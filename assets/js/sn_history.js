(function() {
  const STORAGE_KEY = 'sn_article_history';
  const MAX_ITEMS = 200;
  const SYNC_ENDPOINT = '/account/api/reading-history-save.php';

  function getHistory() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return [];

      const parsed = JSON.parse(raw);
      if (!Array.isArray(parsed)) return [];

      // Backward compatibility:
      // Older items will not have `synced`, so treat them as unsynced.
      return parsed.map(item => ({
        ...item,
        synced: item.synced === true
      }));
    } catch (e) {
      console.warn('Error parsing history from localStorage', e);
      return [];
    }
  }

  function saveHistory(history) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(history));
    } catch (e) {
      console.warn('Error saving history to localStorage', e);
    }
  }

  function updateHistoryItemSyncStatus(url, clickedAt, synced) {
    const history = getHistory();

    const idx = history.findIndex(item =>
      item.url === url && item.clicked_at === clickedAt
    );

    if (idx === -1) return;

    history[idx].synced = synced;
    saveHistory(history);
  }

  async function syncItem(item) {
    try {
      const response = await fetch(SYNC_ENDPOINT, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify(item)
      });

      if (!response.ok) {
        return false;
      }

      const data = await response.json();
      return data.success === true;
    } catch (e) {
      console.warn('Reading history sync failed', e);
      return false;
    }
  }

  async function syncUnsyncedHistory() {
    const history = getHistory();
    const unsyncedItems = history.filter(item => item.synced !== true);

    if (!unsyncedItems.length) return;

    for (const item of unsyncedItems) {
      if (!item.url || !item.title) continue;

      const synced = await syncItem(item);

      if (synced) {
        updateHistoryItemSyncStatus(item.url, item.clicked_at, true);
      }
    }
  }

  async function addToHistory(item) {
    const history = getHistory();

    // Remove existing entry with same URL so we can re-add at the top locally.
    // Database still stores every view event because every click is posted.
    const idx = history.findIndex(h => h.url === item.url);
    if (idx !== -1) {
      history.splice(idx, 1);
    }

    item.synced = false;

    history.unshift(item);

    if (history.length > MAX_ITEMS) {
      history.length = MAX_ITEMS;
    }

    saveHistory(history);

    const synced = await syncItem(item);

    if (synced) {
      updateHistoryItemSyncStatus(item.url, item.clicked_at, true);
    }
  }

  document.addEventListener('click', function(e) {
    const link = e.target.closest('a[data-article-url]');
    if (!link) return;

    const item = {
      url: link.getAttribute('data-article-url') || link.href,
      title: link.getAttribute('data-article-title') || link.textContent.trim(),
      source: link.getAttribute('data-article-source') || '',
      image: link.getAttribute('data-article-image') || '',
      pub_date: link.getAttribute('data-article-pub-date') || '',
      kind: link.getAttribute('data-article-kind') || 'external',
      clicked_at: new Date().toISOString(),
      synced: false
    };

    addToHistory(item);
  });

  // Try to sync older LocalStorage history when the user is signed in.
  // If signed out, the endpoint returns 401 and items remain unsynced.
 setTimeout(syncUnsyncedHistory, 1500);

  window.ScrollNewsHistory = {
    getHistory,
    syncUnsyncedHistory
  };
})();