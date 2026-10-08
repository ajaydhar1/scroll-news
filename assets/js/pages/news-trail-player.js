(function () {
    const dataElement = document.getElementById('trailItemsData');
    if (!dataElement) return;

    let trailItems = [];
    try {
        trailItems = JSON.parse(dataElement.textContent || '[]');
    } catch (error) {
        console.error('Unable to load News Trail items', error);
    }

    let currentIndex = 0;
    const frame = document.getElementById('trailFrame');
    const frameWrap = document.getElementById('trailFrameWrap');
    const title = document.getElementById('trailTitle');
    const activityLabel = document.getElementById('trailActivityLabel');
    const meta = document.getElementById('trailItemMeta');
    const storyAction = document.getElementById('openOriginal');
    const activityPanel = document.getElementById('trailActivityPanel');
    const activitySymbol = document.getElementById('trailActivitySymbol');
    const eventLabel = document.getElementById('trailEventLabel');
    const eventTitle = document.getElementById('trailEventTitle');
    const eventSummary = document.getElementById('trailEventSummary');
    const eventAction = document.getElementById('trailEventAction');
    const position = document.getElementById('trailPosition');
    const controlsPosition = document.getElementById('trailControlsPosition');
    const progress = document.getElementById('trailProgress');
    const progressFill = document.getElementById('trailProgressFill');
    const previousButton = document.getElementById('prevTrailItem');
    const nextButton = document.getElementById('nextTrailItem');
    const player = document.querySelector('.trail-player');
    const footer = document.querySelector('footer.footer');

    if ('IntersectionObserver' in window && player) {
        let playerVisible = false;
        let footerVisible = false;
        const playerObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.target === player) {
                    playerVisible = entry.isIntersecting && entry.intersectionRatio >= 0.15;
                } else if (entry.target === footer) {
                    footerVisible = entry.isIntersecting;
                }
            });
            document.body.classList.toggle('trail-controls-fixed', playerVisible && !footerVisible);
        }, { threshold: [0, 0.15] });

        playerObserver.observe(player);
        if (footer) playerObserver.observe(footer);
    }

    const activityNames = {
        reading: 'Read',
        saved: 'Saved',
        search: 'Search',
        shuffle: 'Shuffle'
    };

    function itemDate(value) {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return new Intl.DateTimeFormat(undefined, {
            hour: 'numeric',
            minute: '2-digit'
        }).format(date);
    }

    function renderEmptyState() {
        title.textContent = 'No trail items found';
        activityLabel.textContent = 'Empty trail';
        meta.textContent = 'There is nothing to play for this date.';
        frame.removeAttribute('src');
        frameWrap.hidden = true;
        activityPanel.hidden = true;
        storyAction.hidden = true;
        position.textContent = 'Item 0 of 0';
        controlsPosition.textContent = '0 / 0';
        progress.setAttribute('aria-valuemax', '1');
        progress.setAttribute('aria-valuenow', '0');
        progressFill.style.width = '0%';
        previousButton.disabled = true;
        nextButton.disabled = true;
    }

    function renderTrailItem() {
        if (!trailItems.length) {
            renderEmptyState();
            return;
        }

        const item = trailItems[currentIndex];
        const type = item.activity_type || 'reading';
        const itemNumber = currentIndex + 1;
        const count = trailItems.length;
        const date = itemDate(item.activity_at);
        const source = item.source && item.source !== 'Search' && item.source !== 'Shuffle'
            ? item.source
            : '';
        const metadata = [source, date].filter(Boolean);

        title.textContent = item.title || 'Untitled trail item';
        activityLabel.textContent = activityNames[type] || 'Trail activity';
        meta.textContent = metadata.join(' · ');
        position.textContent = `Item ${itemNumber} of ${count}`;
        controlsPosition.textContent = `${itemNumber} / ${count}`;
        progress.setAttribute('aria-valuemax', String(count));
        progress.setAttribute('aria-valuenow', String(itemNumber));
        progressFill.style.width = `${(itemNumber / count) * 100}%`;

        const isStory = type === 'reading' || type === 'saved' || !['search', 'shuffle'].includes(type);
        frameWrap.hidden = !isStory;
        activityPanel.hidden = isStory;
        storyAction.hidden = !isStory || !item.url;
        previousButton.disabled = currentIndex === 0;
        nextButton.disabled = currentIndex === count - 1;

        if (isStory) {
            frame.title = `${activityNames[type] || 'Story'}: ${item.title || 'News article'}`;
            frame.src = item.player_url || item.url;
            storyAction.href = item.publisher_url || item.url || '#';
            storyAction.textContent = type === 'saved' ? 'Open saved story' : 'Open publisher story';
            eventAction.removeAttribute('href');
            return;
        }

        frame.removeAttribute('src');
        storyAction.removeAttribute('href');
        eventLabel.textContent = type === 'search' ? 'Search activity' : 'Shuffle activity';
        activitySymbol.textContent = type === 'search' ? '⌕' : '↝';
        eventTitle.textContent = item.title || (type === 'search' ? 'Search' : 'Shuffle session');
        eventSummary.textContent = type === 'search'
            ? 'This search was part of the trail. Reopen it to return to its results and filters.'
            : 'This shuffled set was part of the trail. Reopen the session to continue browsing.';
        eventAction.href = item.url || '#';
        eventAction.textContent = type === 'search'
            ? 'Reopen search'
            : (item.title && item.title.toLowerCase().includes('browse') ? 'Reopen browse shuffle' : 'Reopen shuffle');
    }

    previousButton.addEventListener('click', function () {
        if (currentIndex > 0) {
            currentIndex--;
            renderTrailItem();
        }
    });

    nextButton.addEventListener('click', function () {
        if (currentIndex < trailItems.length - 1) {
            currentIndex++;
            renderTrailItem();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) return;
        if (event.target.closest('input, textarea, select, button, a, [contenteditable="true"]')) return;

        if (event.key === 'ArrowLeft' && currentIndex > 0) {
            currentIndex--;
            renderTrailItem();
        } else if (event.key === 'ArrowRight' && currentIndex < trailItems.length - 1) {
            currentIndex++;
            renderTrailItem();
        }
    });

    renderTrailItem();
})();