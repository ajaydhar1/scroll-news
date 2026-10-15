/* Reading Lens: progressive enhancement.
   Presets (data-deep / data-trusted / href) are the single source of truth.
   Selecting a preset, flipping a switch, the dot field, the summary and the CTA all derive from one state. */
(function () {
    'use strict';

    var root = document.getElementById('snRl');
    if (!root) return;

    var list = document.getElementById('snRlPresets');
    var presets = Array.prototype.slice.call(root.querySelectorAll('.sn-rl-preset'));
    var switches = Array.prototype.slice.call(root.querySelectorAll('.sn-rl-switch'));
    var tune = document.getElementById('snRlTune');
    var field = document.getElementById('snRlField');
    var summary = document.getElementById('snRlSummary');
    var cta = document.getElementById('snRlCta');
    if (!list || !presets.length || !cta) return;

    var current = null;

    function flag(el, key) {
        return el.getAttribute('data-' + key) === '1';
    }

    function findByFlags(deep, trusted) {
        for (var i = 0; i < presets.length; i++) {
            if (flag(presets[i], 'deep') === deep && flag(presets[i], 'trusted') === trusted) {
                return presets[i];
            }
        }
        return null;
    }

    function select(preset) {
        if (!preset) return;
        current = preset;
        var deep = flag(preset, 'deep');
        var trusted = flag(preset, 'trusted');

        presets.forEach(function (p) {
            var on = p === preset;
            p.setAttribute('aria-checked', on ? 'true' : 'false');
            p.tabIndex = on ? 0 : -1;
        });

        switches.forEach(function (s) {
            var on = s.getAttribute('data-switch') === 'deep' ? deep : trusted;
            s.setAttribute('aria-checked', on ? 'true' : 'false');
        });

        if (field) {
            field.setAttribute('data-deep', deep ? '1' : '0');
            field.setAttribute('data-trusted', trusted ? '1' : '0');
        }
        if (summary) summary.textContent = preset.getAttribute('data-summary') || '';
        cta.setAttribute('href', preset.getAttribute('href'));
    }

    function move(delta) {
        var i = presets.indexOf(current);
        var next = presets[(i + delta + presets.length) % presets.length];
        select(next);
        next.focus();
    }

    // Upgrade: links become a radio group.
    list.setAttribute('role', 'radiogroup');
    Array.prototype.forEach.call(list.children, function (li) {
        li.setAttribute('role', 'presentation');
    });
    presets.forEach(function (p) {
        p.setAttribute('role', 'radio');
    });
    if (tune) tune.hidden = false;
    root.classList.add('is-ready');

    presets.forEach(function (p) {
        p.addEventListener('click', function (e) {
            // Let modified clicks open the feed in a new tab/window as usual.
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button > 0) return;
            e.preventDefault();
            select(p);
        });

        p.addEventListener('keydown', function (e) {
            switch (e.key) {
                case 'ArrowDown':
                case 'ArrowRight':
                    e.preventDefault();
                    move(1);
                    break;
                case 'ArrowUp':
                case 'ArrowLeft':
                    e.preventDefault();
                    move(-1);
                    break;
                case 'Home':
                    e.preventDefault();
                    select(presets[0]);
                    presets[0].focus();
                    break;
                case 'End':
                    e.preventDefault();
                    select(presets[presets.length - 1]);
                    presets[presets.length - 1].focus();
                    break;
                case ' ':
                case 'Spacebar':
                    e.preventDefault();
                    select(p);
                    break;
            }
        });
    });

    switches.forEach(function (s) {
        s.addEventListener('click', function () {
            var deep = flag(current, 'deep');
            var trusted = flag(current, 'trusted');
            if (s.getAttribute('data-switch') === 'deep') deep = !deep;
            else trusted = !trusted;
            select(findByFlags(deep, trusted));
        });
    });

    select(root.querySelector('.sn-rl-preset[data-preset="' + root.getAttribute('data-default') + '"]') || presets[0]);
})();
