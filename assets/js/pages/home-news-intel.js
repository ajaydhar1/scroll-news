/* News Intelligence "Signal Map": lens switcher, focus stage, related-signal navigation.
   Markup is fully readable without this script; it only adds interaction on top. */
(function () {
    'use strict';

    var roots = document.querySelectorAll('[data-sn-nip]');
    if (!roots.length) { return; }

    var desktopMq = window.matchMedia ? window.matchMedia('(min-width: 900px)') : { matches: true };
    var reduceMq = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };

    function init(root) {
        var tabs = Array.prototype.slice.call(root.querySelectorAll('.sn-nip-tab'));
        var panels = Array.prototype.slice.call(root.querySelectorAll('.sn-nip-panel'));
        var live = root.querySelector('[data-sn-live]');
        if (!tabs.length || !panels.length) { return; }

        root.classList.add('sn-nip--js');

        panels.forEach(function (p) { p.setAttribute('role', 'tabpanel'); });

        function announce(msg) {
            if (!live) { return; }
            live.textContent = '';
            window.setTimeout(function () { live.textContent = msg; }, 30);
        }

        function panelFor(lens) {
            return root.querySelector('.sn-nip-panel[data-l="' + lens + '"]');
        }

        function showLens(lens, focusTab) {
            tabs.forEach(function (t) {
                var on = t.getAttribute('data-l') === lens;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.setAttribute('tabindex', on ? '0' : '-1');
                if (on && focusTab) { t.focus(); }
            });
            panels.forEach(function (p) {
                p.classList.toggle('is-active', p.getAttribute('data-l') === lens);
            });
        }

        function activeLens() {
            var t = root.querySelector('.sn-nip-tab.is-active');
            return t ? t.getAttribute('data-l') : null;
        }

        function selectTerm(li, opts) {
            opts = opts || {};
            var panel = li.closest('.sn-nip-panel');
            var btn = li.querySelector('.sn-nip-sel--btn');
            var wasActive = li.classList.contains('is-active');

            // On the mobile accordion a second tap collapses; on desktop one signal is always on stage.
            var collapse = wasActive && !desktopMq.matches && !opts.keepOpen;

            panel.querySelectorAll('.sn-nip-term').forEach(function (other) {
                var on = other === li && !collapse;
                other.classList.toggle('is-active', on);
                var b = other.querySelector('.sn-nip-sel--btn');
                if (b) { b.setAttribute('aria-expanded', on ? 'true' : 'false'); }
            });

            if (!collapse) {
                var name = li.querySelector('.sn-nip-name');
                var count = li.querySelector('.sn-nip-count strong');
                announce('Showing ' + (name ? name.textContent : '') + (count ? ': ' + count.textContent + ' articles in the last 24 hours' : ''));
                if (!desktopMq.matches && opts.scroll !== false) { keepInView(li); }
            }
            if (opts.focus && btn) { btn.focus({ preventScroll: true }); }
        }

        function keepInView(el) {
            var r = el.getBoundingClientRect();
            if (r.top < 0 || r.top > window.innerHeight * 0.6) {
                el.scrollIntoView({ block: 'start', behavior: reduceMq.matches ? 'auto' : 'smooth' });
            }
        }

        // Lens tabs: click, plus arrow / Home / End keys (automatic activation: switching is instant and local)
        tabs.forEach(function (tab, i) {
            tab.addEventListener('click', function () { showLens(tab.getAttribute('data-l'), false); });
            tab.addEventListener('keydown', function (e) {
                var next = null;
                if (e.key === 'ArrowRight') { next = tabs[(i + 1) % tabs.length]; }
                else if (e.key === 'ArrowLeft') { next = tabs[(i - 1 + tabs.length) % tabs.length]; }
                else if (e.key === 'Home') { next = tabs[0]; }
                else if (e.key === 'End') { next = tabs[tabs.length - 1]; }
                if (next) {
                    e.preventDefault();
                    showLens(next.getAttribute('data-l'), true);
                }
            });
        });

        // Signals: click / Enter / Space select; Up / Down move through the ranked list; hover previews on desktop mice.
        panels.forEach(function (panel) {
            var items = Array.prototype.slice.call(panel.querySelectorAll('.sn-nip-term'));
            var hoverTimer = null;

            items.forEach(function (li, i) {
                var btn = li.querySelector('.sn-nip-sel--btn');
                if (!btn) { return; }

                btn.addEventListener('click', function () { selectTerm(li); });

                btn.addEventListener('keydown', function (e) {
                    var target = null;
                    if (e.key === 'ArrowDown') { target = items[i + 1]; }
                    else if (e.key === 'ArrowUp') { target = items[i - 1]; }
                    else if (e.key === 'Home') { target = items[0]; }
                    else if (e.key === 'End') { target = items[items.length - 1]; }
                    if (target) {
                        e.preventDefault();
                        selectTerm(target, { focus: true, keepOpen: true, scroll: false });
                    }
                });

                btn.addEventListener('pointerenter', function (e) {
                    if (e.pointerType !== 'mouse' || !desktopMq.matches || li.classList.contains('is-active')) { return; }
                    window.clearTimeout(hoverTimer);
                    hoverTimer = window.setTimeout(function () { selectTerm(li, { keepOpen: true, scroll: false }); }, 140);
                });
                btn.addEventListener('pointerleave', function () { window.clearTimeout(hoverTimer); });
            });
        });

        // Related signals jump across lenses
        root.addEventListener('click', function (e) {
            var link = e.target.closest ? e.target.closest('a[data-jump]') : null;
            if (!link) { return; }
            var id = (link.getAttribute('href') || '').replace(/^#/, '');
            var li = id ? document.getElementById(id) : null;
            if (!li || !root.contains(li)) { return; }

            e.preventDefault();
            var lens = li.getAttribute('data-l');
            if (lens !== activeLens()) { showLens(lens, false); }
            selectTerm(li, { focus: true, keepOpen: true, scroll: false });
            keepInView(li);
        });

        // Localise the "updated" time
        var timeEl = root.querySelector('time[data-sn-time]');
        if (timeEl && timeEl.dateTime) {
            var d = new Date(timeEl.dateTime);
            if (!isNaN(d.getTime())) {
                try { timeEl.textContent = d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }); } catch (err) { /* keep UTC text */ }
            }
        }
    }

    // Live wire: relative times in the reader's clock
    function relTimes() {
        var els = document.querySelectorAll('.sn-lw time[data-sn-rel]');
        var now = Date.now();
        els.forEach(function (el) {
            var t = new Date(el.dateTime).getTime();
            if (isNaN(t)) { return; }
            var mins = Math.max(0, Math.round((now - t) / 60000));
            var txt;
            if (mins < 1) { txt = 'Just now'; }
            else if (mins < 60) { txt = mins + ' min ago'; }
            else if (mins < 1440) { var h = Math.round(mins / 60); txt = h + (h === 1 ? ' hour ago' : ' hours ago'); }
            else { return; }
            el.textContent = txt;
        });
    }

    roots.forEach(init);
    relTimes();
})();
