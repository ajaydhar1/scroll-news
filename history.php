<?php
// history.php
define('BASE_PATH', __DIR__);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <meta charset="utf-8" />
    <title>Your Reading History · Scroll News</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap / your main CSS bundle -->
    <link rel="stylesheet" href="assets/css/main.css" />
</head>
<body class="sn-page sn-page-history">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Your Reading History</h1>
        <a href="scroll-archive.php" class="btn btn-sm btn-outline-light">
            ← Scroll History
        </a>
    </div>

    <p class="text-muted small mb-4">
        This page shows the articles you’ve opened recently. 
        Entries include both <strong>Newsroom</strong> views and
        <strong>Publisher</strong> views.
    </p>

    <div id="history-list" class="history-list">
        <p>Loading your history…</p>
    </div>
</div>

<script>
(function() {
    const STORAGE_KEY = 'sn_article_history';
    const container = document.getElementById('history-list');

    function getHistoryDirect() {
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

    const history = getHistoryDirect();

    if (!history.length) {
        container.innerHTML = `
            <div class="alert alert-secondary">
                No articles viewed yet. Visit the <a href="/newsroom.php">Newsroom</a> to start reading.
            </div>
        `;
        return;
    }

    container.innerHTML = '';

    history.forEach(item => {
        const card = document.createElement('div');
        card.className = 'history-item card mb-3';

        const body = document.createElement('div');
        body.className = 'card-body';

        // Title
        const titleEl = document.createElement('h2');
        titleEl.className = 'h6 mb-2';
        titleEl.textContent = item.title || item.url || 'Untitled article';

        // Meta line
        const metaEl = document.createElement('div');
        metaEl.className = 'text-muted small mb-2';

        const parts = [];

        if (item.source) {
          parts.push(item.source);
        }

        if (item.pub_date) {
          const dtPub = new Date(item.pub_date);
          const nicePub = formatNiceDate(dtPub);
          if (nicePub) {
            parts.push('Published: ' + nicePub);
          }
        }

        if (item.clicked_at) {
          const dtClick = new Date(item.clicked_at);
          const niceClick = formatNiceDate(dtClick);
          if (niceClick) {
            parts.push('Viewed: ' + niceClick);
          }
        }

        if (item.kind === 'analyze') {
            parts.push('Type: Newsroom view');
        } else if (item.kind === 'external') {
            parts.push('Type: Publisher view');
        }

        metaEl.textContent = parts.join(' • ');

        // Buttons row
        const actions = document.createElement('div');
        actions.className = 'd-flex flex-wrap gap-2 mt-2';

        // Basic behavior: just reopen the saved URL
        if (item.url) {
            const btn = document.createElement('a');
            btn.className = 'btn btn-sm btn-primary';
            btn.href = item.url;

            if (item.kind === 'external') {
                btn.target = '_blank';
                btn.rel = 'noopener noreferrer';
                btn.textContent = 'Open Publisher Site';
            } else if (item.kind === 'analyze') {
                btn.textContent = 'Open in Newsroom';
            } else {
                btn.textContent = 'Open link';
            }

            actions.appendChild(btn);
        }

        body.appendChild(titleEl);
        body.appendChild(metaEl);
        body.appendChild(actions);

        card.appendChild(body);
        container.appendChild(card);
    });
})();

function formatNiceDate(dt) {
  if (!(dt instanceof Date) || Number.isNaN(dt.getTime())) {
    return '';
  }

  const days   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
  const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  const dayName   = days[dt.getDay()];
  const monthName = months[dt.getMonth()];
  const dayNum    = String(dt.getDate()).padStart(2, '0');
  const year      = dt.getFullYear();

  let hours   = dt.getHours();
  const mins  = String(dt.getMinutes()).padStart(2, '0');
  const ampm  = hours >= 12 ? 'PM' : 'AM';
  let hour12  = hours % 12;
  if (hour12 === 0) hour12 = 12;

  return `${dayName} ${monthName} ${dayNum} ${year} ${hour12}:${mins} ${ampm}`;
}


</script>

</body>
</html>
