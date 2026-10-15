/* Story Threads interactions: mobile accordion, chip filtering, relationship web, More menu. */
(function () {
  'use strict';

  var roots = document.querySelectorAll('[data-sn-st]');
  if (!roots.length) return;

  var narrow = window.matchMedia('(max-width: 640px)');
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');

  function announce(root, msg) {
    var live = root.querySelector('[data-st-live]');
    if (!live) return;
    live.textContent = '';
    window.setTimeout(function () { live.textContent = msg; }, 30);
  }

  function plural(n, one, many) { return n + ' ' + (n === 1 ? one : many); }

  function init(root) {
    root.classList.add('sn-st--js');
    var threads = root.querySelectorAll('[data-st-thread]');

    // Accordion applies on narrow screens only; desktop always shows every thread open
    function syncAccordion() {
      Array.prototype.forEach.call(threads, function (th, i) {
        var btn = th.querySelector('[data-st-toggle]');
        if (!btn) return;
        if (!narrow.matches) {
          btn.setAttribute('aria-expanded', 'true');
          btn.removeAttribute('aria-controls');
          btn.disabled = true;
        } else {
          btn.disabled = false;
          var body = th.querySelector('[data-st-body]');
          if (body && body.id) btn.setAttribute('aria-controls', body.id);
          btn.setAttribute('aria-expanded', th.classList.contains('is-open') ? 'true' : 'false');
        }
      });
    }
    var firstSync = true;
    function onModeChange() {
      if (firstSync) { firstSync = false; }
      syncAccordion();
    }
    if (narrow.addEventListener) narrow.addEventListener('change', onModeChange);
    syncAccordion();

    Array.prototype.forEach.call(threads, function (th) {
      var btn = th.querySelector('[data-st-toggle]');
      var chips = th.querySelectorAll('[data-st-chip]');
      var nodes = th.querySelectorAll('[data-st-node]');
      var edges = th.querySelectorAll('[data-st-edge]');
      var prevs = th.querySelectorAll('.sn-st-prev');
      var note = th.querySelector('[data-st-note]');
      var readLink = th.querySelector('[data-st-read]');
      var filterNote = th.querySelector('[data-st-filter-note]');
      var webToggle = th.querySelector('[data-st-web-toggle]');
      var web = th.querySelector('[data-st-web]');
      var more = th.querySelector('[data-st-morearticles]');
      var name = (th.querySelector('.sn-st-name') || {}).textContent || '';
      var active = null;

      if (btn) {
        btn.addEventListener('click', function () {
          if (!narrow.matches) return;
          var open = !th.classList.contains('is-open');
          th.classList.toggle('is-open', open);
          btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
      }

      function select(entity, label) {
        active = entity;
        Array.prototype.forEach.call(chips, function (c) {
          c.setAttribute('aria-pressed', entity && c.getAttribute('data-st-chip') === entity ? 'true' : 'false');
        });
        Array.prototype.forEach.call(nodes, function (n) {
          n.classList.toggle('is-on', !!entity && n.getAttribute('data-st-node') === entity);
        });
        Array.prototype.forEach.call(edges, function (e) {
          e.classList.toggle('is-on', !!entity && e.getAttribute('data-st-edge') === entity);
        });

        var hits = 0;
        Array.prototype.forEach.call(prevs, function (p) {
          var ents = (p.getAttribute('data-ents') || '').split('|');
          var match = !entity || ents.indexOf(entity) !== -1;
          p.classList.toggle('is-dim', !match);
          if (match) hits++;
        });
        if (entity && more && !more.open) {
          var hiddenHit = more.querySelector('.sn-st-prev:not(.is-dim)');
          if (hiddenHit) more.open = true;
        }

        var chipEl = entity ? th.querySelector('[data-st-chip="' + entity + '"]') : null;
        var count = chipEl ? (chipEl.querySelector('.sn-st-chip-n') || {}).textContent : '';
        var msg = entity
          ? name + ' and ' + label + ' are tagged together in ' + plural(parseInt(count, 10) || 0, 'article', 'articles') + '. ' + plural(hits, 'preview matches', 'previews match') + '.'
          : 'Filter cleared.';
        if (note) note.textContent = entity ? msg : 'Select a name to see how often it is tagged with ' + name + '.';
        if (filterNote) {
          filterNote.hidden = !entity;
          filterNote.textContent = entity ? (hits ? 'Highlighting ' + plural(hits, 'preview', 'previews') + ' that mention ' + label + '.' : 'None of the previews shown mention ' + label + '; use the link in Connections to read all of them.') : '';
        }
        if (readLink) {
          readLink.hidden = !entity;
          if (entity) {
            var q = (readLink.getAttribute('data-st-lead') || '') + ' ' + label;
            readLink.href = '/search.php?q=' + encodeURIComponent(q) + '&range=all&mode=nlp&deep_dive=&high_signal=';
          }
        }
        announce(root, msg);
      }

      Array.prototype.forEach.call(chips, function (c) {
        c.addEventListener('click', function () {
          var ent = c.getAttribute('data-st-chip');
          var label = c.firstChild ? c.firstChild.textContent.trim() : ent;
          select(active === ent ? null : ent, label);
        });
      });
      Array.prototype.forEach.call(nodes, function (n) {
        n.addEventListener('click', function () {
          var ent = n.getAttribute('data-st-node');
          var label = n.firstChild ? n.firstChild.textContent.trim() : ent;
          select(active === ent ? null : ent, label);
        });
      });

      if (webToggle && web) {
        webToggle.addEventListener('click', function () {
          var open = web.hidden;
          web.hidden = !open;
          webToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        var table = web.querySelector('[data-st-table]');
        var graph = web.querySelector('[data-st-graph]');
        var views = web.querySelectorAll('[data-st-view]');
        Array.prototype.forEach.call(views, function (v) {
          v.addEventListener('click', function () {
            var web_on = v.getAttribute('data-st-view') === 'web';
            if (table) table.hidden = web_on;
            if (graph) graph.hidden = !web_on;
            Array.prototype.forEach.call(views, function (o) {
              var on = o === v;
              o.classList.toggle('is-active', on);
              o.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            // Nodes duplicate the chips for keyboard users only when the web is shown
            Array.prototype.forEach.call(nodes, function (n) { n.tabIndex = web_on ? 0 : -1; });
            if (!web_on) select(null, '');
          });
        });
      }
    });

    // Close an open More menu on outside click or Escape
    document.addEventListener('click', function (ev) {
      root.querySelectorAll('[data-st-more][open]').forEach(function (d) {
        if (!d.contains(ev.target)) d.open = false;
      });
    });
    root.addEventListener('keydown', function (ev) {
      if (ev.key !== 'Escape') return;
      var d = ev.target.closest && ev.target.closest('[data-st-more][open]');
      if (d) {
        d.open = false;
        var s = d.querySelector('summary');
        if (s) s.focus();
      }
    });

    if (reduce.matches) root.classList.add('is-in');
  }

  Array.prototype.forEach.call(roots, init);
})();
