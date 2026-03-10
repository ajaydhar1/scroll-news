/**
 * textroom.js
 * Consolidates all the inline JS from textroom.php into one file.
 *
 * Requires on the page:
 *  - window.TEXTROOM = { fromDb: bool, text: string }
 *  - jQuery + Bootstrap (popover)
 *
 * Safe to call multiple times (e.g., after analytics HTML is injected).
 */

/* global $ */

(function () {
  'use strict';

  const NS = (window.ScrollNews = window.ScrollNews || {});

  // -------------------------
  // Utilities
  // -------------------------
  function isMobile() {
    return (
      window.matchMedia?.('(pointer: coarse)').matches ||
      window.innerWidth < 768
    );
  }

  function qs(sel, root = document) {
    return root.querySelector(sel);
  }

  function qsa(sel, root = document) {
    return Array.from(root.querySelectorAll(sel));
  }

  function safeText(el, text) {
    if (!el) return;
    el.textContent = text;
  }

  function once(el, event, handler, opts) {
    if (!el) return;
    el.addEventListener(event, handler, Object.assign({ once: true }, opts || {}));
  }

  // -------------------------
  // 1) Social tab toggles
  // -------------------------
  function setupSocialTabs() {
    const configs = [
      { key: 'insta',   label: 'Instagram' },
      { key: 'twitter', label: 'Twitter' },
      { key: 'google',  label: 'Google' },
      { key: 'youtube', label: 'Youtube' },
      { key: 'search',  label: 'Scroll Search' },
    ];

    function activate(key) {
      const cfg = configs.find(c => c.key === key);
      if (!cfg) return;

      safeText(qs('#sm-tags'), cfg.label);

      configs.forEach(c => {
        const pane = qs(`#${c.key}-tags`);
        if (pane) pane.style.display = (c.key === key) ? 'block' : 'none';
      });

      configs.forEach(c => {
        const icon = qs(`#${c.key}-icon`);
        if (!icon) return;
        icon.style.color = (c.key === key) ? 'var(--brand-color)' : '#34495E';
      });
    }

    configs.forEach(c => {
      const link = qs(`#${c.key}-link`);
      if (!link) return;

      if (link.dataset.snBound === '1') return;
      link.dataset.snBound = '1';

      link.addEventListener('click', function (e) {
        e.preventDefault();
        activate(c.key);
      });
    });
  }

  // -------------------------
  // 2) Definitions popovers (Wikipedia + hashtags)
  // -------------------------
  function setupDefinitionPopovers() {
    try { $('[data-toggle="popover"]').popover('dispose'); } catch (_) {}

    const enableDefinitionPopovers = false;
    if (!enableDefinitionPopovers) return;

    const wikiLinks = qsa('#wikipedia a');
    const hashLinks = qsa('.hashtag a');

    if (!wikiLinks.length && !hashLinks.length) return;

    const popoverTrigger = isMobile() ? 'click' : 'hover';

    function prepLinks(links, placement) {
      links.forEach(a => {
        a.setAttribute('data-container', 'body');
        a.setAttribute('data-toggle', 'popover');
        a.setAttribute('data-placement', placement || 'auto');
        a.setAttribute('data-content', a.getAttribute('data-content') || 'temp');
        a.setAttribute('data-html', 'true');
      });
    }

    prepLinks(wikiLinks, 'bottom');
    prepLinks(hashLinks, 'auto');

    if (!document.body.dataset.snPopCloseBound) {
      document.body.dataset.snPopCloseBound = '1';

      document.addEventListener('click', function (e) {
        const t = e.target;

        if (t && t.closest && t.closest('.popover')) return;
        if (t && t.closest && (t.closest('#wikipedia a') || t.closest('.hashtag a'))) return;

        try { $('#wikipedia a').popover('hide'); } catch (_) {}
        try { $('.hashtag a').popover('hide'); } catch (_) {}

        window.__snTapped = 0;
      }, true);
    }

    try {
      $('[data-toggle="popover"]').popover('dispose');
    } catch (_) {}

    try {
      $('[data-toggle="popover"]').popover({
        trigger: popoverTrigger,
        boundary: 'window',
        html: true
      });
    } catch (_) {}

    function getDefinitions($el, term) {
      if (!$el || !term) return;

      $.ajax({
        url: '/api/definitions.php?term=' + encodeURIComponent(term) + '&label=' + encodeURIComponent($el.attr('data-label') || ''),
        type: 'GET',
        success: function (result) {
          let def = '';
          if ((result !== '') && (result !== '<strong>. </strong> ')) {
            def = result;
          } else {
            def = 'No definition found.';
          }

          const isHovered = $el.is(':hover');
          const allowShow = !isMobile() ? isHovered : true;

          if (allowShow) {
            $el.attr('data-content', def);

            try {
              $('[data-toggle="popover"]').popover('dispose');
            } catch (_) {}

            try {
              $('[data-toggle="popover"]').popover({
                trigger: popoverTrigger,
                boundary: 'window',
                html: true
              });
            } catch (_) {}

            try { $el.popover('show'); } catch (_) {}
          }
        }
      });
    }

    if (!isMobile()) {
      wikiLinks.forEach(a => {
        if (a.dataset.snDefBound === '1') return;
        a.dataset.snDefBound = '1';

        $(a).on('mouseenter', function () {
          getDefinitions($(this), $(this).text());
        }).on('mouseleave', function () {
          try { $(this).popover('hide'); } catch (_) {}
        });
      });

      hashLinks.forEach(a => {
        if (a.dataset.snDefBound === '1') return;
        a.dataset.snDefBound = '1';

        $(a).on('mouseenter', function () {
          getDefinitions($(this), $(this).attr('data-hashtext'));
        }).on('mouseleave', function () {
          try { $(this).popover('hide'); } catch (_) {}
        });
      });

      return;
    }

    let tapped = window.__snTapped || 0;

    function bindTap(selector, getTerm, hideAll) {
      qsa(selector).forEach(a => {
        if (a.dataset.snDefTapBound === '1') return;
        a.dataset.snDefTapBound = '1';

        a.addEventListener('click', function (e) {
          const $this = $(this);

          if (tapped === 0) {
            getDefinitions($this, getTerm($this));
            tapped = 1;
            window.__snTapped = 1;

            e.preventDefault();
            e.stopImmediatePropagation();
            return;
          }

          hideAll();
          tapped = 0;
          window.__snTapped = 0;

          const attr = $this.attr('aria-describedby');
          if (attr === false || typeof attr === 'undefined') {
            e.preventDefault();
            e.stopImmediatePropagation();

            getDefinitions($this, getTerm($this));
            tapped = 1;
            window.__snTapped = 1;
          }
        }, true);
      });
    }

    bindTap(
      '#wikipedia a',
      ($a) => $a.text(),
      () => { try { $('#wikipedia a').popover('hide'); } catch (_) {} }
    );

    bindTap(
      '.hashtag a',
      ($a) => $a.attr('data-hashtext'),
      () => { try { $('.hashtag a').popover('hide'); } catch (_) {} }
    );
  }

  // -------------------------
  // 3) Loading overlay click handler
  // -------------------------
  function setupLoadingOverlay() {
    const overlay = qs('#loadingOverlay');
    const show = () => overlay && (overlay.hidden = false);
    const hide = () => overlay && (overlay.hidden = true);

    if (document.body.dataset.snOverlayBound === '1') return;
    document.body.dataset.snOverlayBound = '1';

    window.addEventListener('pageshow', hide);

    document.addEventListener('click', function (e) {
      const t = e.target.closest && e.target.closest('[data-loading]');
      if (t) show();
    });

    document.addEventListener('click', function (e) {
      const btn = e.target.closest && e.target.closest('[data-loading-btn]');
      if (!btn) return;

      btn.dataset.originalHtml = btn.innerHTML;
      btn.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span>&nbsp;Loading…';
      btn.classList.add('disabled');
      btn.setAttribute('aria-busy', 'true');
    });

    const styleId = 'sn-btn-spinner-style';
    if (!qs('#' + styleId)) {
      const style = document.createElement('style');
      style.id = styleId;
      style.textContent = '.btn-spinner{display:inline-block;width:1em;height:1em;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:snspin .6s linear infinite;vertical-align:-0.125em}@keyframes snspin{to{transform:rotate(360deg)}}';
      document.head.appendChild(style);
    }
  }

  // -------------------------
  // 8) Analytics UI bootstrap
  // -------------------------
  function setupAnalyticsUI() {
    setupSocialTabs();
    setupDefinitionPopovers();
  }

  NS.setupAnalyticsUI = setupAnalyticsUI;

  // -------------------------
  // 9) Non-DB analyze flow (loader + analyze.php POST)
  // -------------------------
  function runAnalyzeFlowIfNeeded() {
    const cfg = window.TEXTROOM || {};
    const fromDb = !!cfg.fromDb;

    if (fromDb) {
      setupAnalyticsUI();
      return;
    }

    const text = (cfg.text || '').trim();
    if (!text) return;

    const $loader = $('#analytics-loader');
    const $results = $('#analytics-results');

    if (!$results.length) return;

    if ($loader.length) $loader.show();
    $results.empty().removeClass('d-none');

    $.ajax({
      type: 'POST',
      url: '/api/analyze.php',
      data: { text: text },

      success: function (msg) {
        if ($loader.length) $loader.hide();
        $results.html(msg);
        setupAnalyticsUI();
      },

      error: function () {
        if ($loader.length) $loader.hide();
        $results.html(
          '<div class="alert alert-warning mb-0">Sorry—we could not analyze this text right now.</div>'
        );
      }
    });
  }

  // -------------------------
  // Wikipedia entity overlay
  // -------------------------
  $(function () {
    $(document).on('click', '.js-wiki', function (e) {
      e.preventDefault();

      $.fancybox.open({
        src: $(this).attr('href'),
        type: 'iframe',
        width: '95%',
        height: '95%',
        autoSize: false,
        fitToView: false,
        closeClick: false,
        openEffect: 'none',
        closeEffect: 'none'
      });
    });
  });

  // -------------------------
  // Boot
  // -------------------------
  function initTextroomPage() {
    setupLoadingOverlay();
    runAnalyzeFlowIfNeeded();
    setupAnalyticsUI();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTextroomPage);
  } else {
    initTextroomPage();
  }

})();