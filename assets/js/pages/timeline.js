(function () {
  'use strict';
  var root = document.documentElement;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var days = Array.prototype.slice.call(document.querySelectorAll('.sn-tl-day'));
  if (!days.length) return;
  root.classList.add('sn-tl-js');

  var related = document.getElementById('snTlRelated');
  var select = document.getElementById('snTlPublisher');
  var bars = Array.prototype.slice.call(document.querySelectorAll('.sn-tl-spark a[data-day]'));
  var chapterLinks = Array.prototype.slice.call(document.querySelectorAll('.sn-tl-minimap a[data-chapter]'));
  var chapters = Array.prototype.slice.call(document.querySelectorAll('.sn-tl-chapter'));

  // Reveal on scroll
  var revealables = days.concat(chapters);
  if ('IntersectionObserver' in window && !reduce) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
    revealables.forEach(function (el) { io.observe(el); });
  } else {
    revealables.forEach(function (el) { el.classList.add('is-in'); });
  }

  // Publisher filter (progressive enhancement)
  function applyFilters() {
    var host = select ? select.value : '';
    var showRelated = related && related.checked;
    days.forEach(function (day) {
      var visible = 0;
      Array.prototype.forEach.call(day.querySelectorAll('.sn-tl-card-wrap'), function (card) {
        var hide = host && card.getAttribute('data-host') !== host;
        card.classList.toggle('is-filtered', !!hide);
        if (!hide && (showRelated || card.getAttribute('data-tier') === 'core')) visible++;
      });
      day.classList.toggle('is-empty', visible === 0);
      if (host) {
        var more = day.querySelector('details.sn-tl-more');
        if (more) more.open = true;
      }
    });
  }
  if (select) {
    select.closest('.sn-tl-pubfilter').hidden = false;
    select.addEventListener('change', applyFilters);
  }
  if (related) related.addEventListener('change', applyFilters);

  // Scroll spy: current day -> scrubber bar, rail dot, chapter in mini-map
  function setCurrent(day) {
    var date = day.getAttribute('data-day');
    days.forEach(function (d) { d.classList.toggle('is-current', d === day); });
    bars.forEach(function (b) { b.classList.toggle('is-active', b.getAttribute('data-day') === date); });
    var chapterIdx = null;
    var node = day;
    while (node) {
      if (node.classList && node.classList.contains('sn-tl-chapter')) {
        chapterIdx = node.id.replace('chapter-', '');
        break;
      }
      node = node.previousElementSibling;
    }
    chapterLinks.forEach(function (a) {
      a.classList.toggle('is-active', a.getAttribute('data-chapter') === chapterIdx);
    });
  }

  if ('IntersectionObserver' in window) {
    var current = null;
    var spy = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) current = e.target;
      });
      if (current && !current.classList.contains('is-empty')) setCurrent(current);
    }, { rootMargin: '-35% 0px -55% 0px', threshold: 0 });
    days.forEach(function (d) { spy.observe(d); });
  }

  // Smooth scroll for scrubber bars
  bars.forEach(function (b) {
    b.addEventListener('click', function (ev) {
      var target = document.getElementById('day-' + b.getAttribute('data-day'));
      if (!target) return;
      ev.preventDefault();
      target.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
      if (history.replaceState) history.replaceState(null, '', '#day-' + b.getAttribute('data-day'));
    });
  });

  // Deep links to a related card should reveal related coverage first
  if (location.hash && related && !related.checked) {
    var t = document.querySelector(location.hash.replace(/[^#\w-]/g, ''));
    if (t && t.closest && t.closest('.is-related, .is-related-only')) {
      related.checked = true;
      applyFilters();
      t.scrollIntoView();
    }
  }
  applyFilters();
})();
