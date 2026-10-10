<div id="snProductLoadingOverlay" class="loading-overlay" aria-live="polite" aria-busy="true" hidden>
    <div class="loading-spinner" role="status" aria-label="Loading"></div>
</div>

<script>
    (function() {
        const overlay = document.getElementById('snProductLoadingOverlay');
        const show = () => overlay && (overlay.hidden = false);
        const hide = () => overlay && (overlay.hidden = true);

        window.addEventListener('pageshow', hide);
        document.addEventListener('click', function(event) {
            const el = event.target.closest('[data-loading]');
            if (!el || event.defaultPrevented) return;
            if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

            const link = el.closest('a');
            if (link) {
                const target = link.getAttribute('target');
                if (target && target !== '_self') return;
                if (link.hasAttribute('download')) return;
                const href = link.getAttribute('href') || '';
                if (href === '' || href.charAt(0) === '#') return;
            }
            show();
        });
    })();
</script>