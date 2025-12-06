<?php
// history.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
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
        <a href="scroll-history.php" class="btn btn-sm btn-outline-light">
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
// If you put the logger snippet in a global JS, this should already exist:
//   window.ScrollNewsHistory.getHistory()
(function() {
    const container = document.getElementById('history-list');

    if (!window.ScrollNewsHistory || typeof ScrollNewsHistory.getHistory !== 'function') {
        container.innerHTML = '<p class="text-muted">History is not available right now.</p>';
        return;
    }

    const history = ScrollNewsHistory.getHistory();

    if (!history.length) {
        container.innerHTML = `
            <div class="alert alert-secondary">
                No articles viewed yet. Visit the <a href="newsroom.php">Newsroom</a> to start reading.
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
            // If pub_date is a unix timestamp string, you can convert it:
            // const dt = new Date(parseInt(item.pub_date, 10) * 1000);
            // parts.push('Published: ' + dt.toLocaleString());
            parts.push('Published: ' + item.pub_date);
        }

        if (item.clicked_at) {
            const dt = new Date(item.clicked_at);
            if (!Number.isNaN(dt.getTime())) {
                parts.push('Viewed: ' + dt.toLocaleString());
            }
        }

        // Kind badge (Newsroom vs Publisher)
        if (item.kind === 'analyze') {
            parts.push('Type: Newsroom view');
        } else if (item.kind === 'external') {
            parts.push('Type: Publisher view');
        }

        metaEl.textContent = parts.join(' • ');

        // Buttons row
        const actions = document.createElement('div');
        actions.className = 'd-flex flex-wrap gap-2 mt-2';

        // Determine URLs
        const analyzeUrl  = item.analyze_url  || (item.kind === 'analyze'  ? item.url : null);
        const externalUrl = item.external_url || (item.kind === 'external' ? item.url : null);

        // Primary button based on kind
        if (item.kind === 'analyze' && analyzeUrl) {
            const btn = document.createElement('a');
            btn.className = 'btn btn-sm btn-primary';
            btn.href = analyzeUrl;
            btn.textContent = 'Open in Newsroom';
            actions.appendChild(btn);
        } else if (item.kind === 'external' && externalUrl) {
            const btn = document.createElement('a');
            btn.className = 'btn btn-sm btn-primary';
            btn.href = externalUrl;
            btn.target = '_blank';
            btn.rel = 'noopener noreferrer';
            btn.textContent = 'Open Publisher Site';
            actions.appendChild(btn);
        }

        // Secondary buttons: show both if we have both URLs
        if (analyzeUrl && externalUrl && analyzeUrl !== externalUrl) {
            // Newsroom secondary
            if (!(item.kind === 'analyze' && analyzeUrl === item.url)) {
                const secNewsroom = document.createElement('a');
                secNewsroom.className = 'btn btn-sm btn-outline-secondary';
                secNewsroom.href = analyzeUrl;
                secNewsroom.textContent = 'Newsroom';
                actions.appendChild(secNewsroom);
            }

            // Publisher secondary
            if (!(item.kind === 'external' && externalUrl === item.url)) {
                const secPub = document.createElement('a');
                secPub.className = 'btn btn-sm btn-outline-secondary';
                secPub.href = externalUrl;
                secPub.target = '_blank';
                secPub.rel = 'noopener noreferrer';
                secPub.textContent = 'Publisher';
                actions.appendChild(secPub);
            }
        }

        // Fallback: if we have no specific URLs, at least let them reopen item.url
        if (!actions.childNodes.length && item.url) {
            const fallbackBtn = document.createElement('a');
            fallbackBtn.className = 'btn btn-sm btn-primary';
            fallbackBtn.href = item.url;
            fallbackBtn.textContent = 'Open link';
            actions.appendChild(fallbackBtn);
        }

        body.appendChild(titleEl);
        body.appendChild(metaEl);
        body.appendChild(actions);

        card.appendChild(body);
        container.appendChild(card);
    });
})();
</script>

</body>
</html>
