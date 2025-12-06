(function() {
  const STORAGE_KEY = 'sn_article_history';
  const MAX_ITEMS = 200;

  function getHistory() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return [];
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
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

  function addToHistory(item) {
    const history = getHistory();

    // Remove existing entry with same URL so we can re-add at the top
    const idx = history.findIndex(h => h.url === item.url);
    if (idx !== -1) {
      history.splice(idx, 1);
    }

    // Add to front
    history.unshift(item);

    // Trim
    if (history.length > MAX_ITEMS) {
      history.length = MAX_ITEMS;
    }

    saveHistory(history);
  }

  // Global click handler for article links
  document.addEventListener('click', function(e) {
    const link = e.target.closest('a[data-article-url]');
    if (!link) return;

    const item = {
      url: link.getAttribute('data-article-url') || link.href,
      title: link.getAttribute('data-article-title') || link.textContent.trim(),
      source: link.getAttribute('data-article-source') || '',
      image: link.getAttribute('data-article-image') || '',
      pub_date: link.getAttribute('data-article-pub-date') || '',
      kind: link.getAttribute('data-article-kind') || 'external', // ← NEW
      clicked_at: new Date().toISOString()
    };

    addToHistory(item);
  });

  // Expose getter globally if you want to use it on history page
  window.ScrollNewsHistory = { getHistory };
})();
