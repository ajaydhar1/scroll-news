/**
 * newsroom_page.js
 * Consolidates all the inline JS from newsroom.php into one file.
 *
 * Requires on the page:
 *  - window.NEWSROOM = { fromDb: bool, url: string, intro?: { shouldRun?: bool }, ... }
 *  - jQuery + Bootstrap (popover)
 *  - lottie (optional, only used when fromDb === false)
 *  - introJs (optional)
 *
 * Safe to call multiple times (e.g., after analytics HTML is injected).
 */

/* global $, introJs, lottie */

(function () {
  'use strict';

  const NS = (window.ScrollNews = window.ScrollNews || {});

  // -------------------------
  // Utilities
  // -------------------------
  function isMobile() {
    // Keep it simple + deterministic
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

  function safeShow(el) {
    if (!el) return;
    el.style.display = 'block';
  }

  function safeHide(el) {
    if (!el) return;
    el.style.display = 'none';
  }

  function once(el, event, handler, opts) {
    if (!el) return;
    el.addEventListener(event, handler, Object.assign({ once: true }, opts || {}));
  }

  // -------------------------
  // 1) Social tab toggles
  // -------------------------
  function setupSocialTabs() {
    // Matches your existing IDs:
    // links: #insta-link, #twitter-link, #google-link, #youtube-link, #search-link
    // labels: #sm-tags
    // panes: #insta-tags, #twitter-tags, #google-tags, #youtube-tags, #search-tags
    // icons: #insta-icon, #twitter-icon, #google-icon, #youtube-icon, #search-icon

    const configs = [
      { key: 'insta',   label: 'Instagram' },
      { key: 'twitter', label: 'Twitter' },
      { key: 'google',  label: 'Google' },
      { key: 'youtube', label: 'Youtube' },
      { key: 'search',  label: 'News Search' },
    ];

    function activate(key) {
      const cfg = configs.find(c => c.key === key);
      if (!cfg) return;

      // Label
      safeText(qs('#sm-tags'), cfg.label);

      // Panes
      configs.forEach(c => {
        const pane = qs(`#${c.key}-tags`);
        if (pane) pane.style.display = (c.key === key) ? 'block' : 'none';
      });

      // Icons
      configs.forEach(c => {
        const icon = qs(`#${c.key}-icon`);
        if (!icon) return;
        icon.style.color = (c.key === key) ? 'var(--brand-color)' : '#34495E';
      });
    }

    // Wire clicks
    configs.forEach(c => {
      const link = qs(`#${c.key}-link`);
      if (!link) return;

      // Prevent double-binding if setupAnalyticsUI() is called again
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
    const wikiLinks = qsa('#wikipedia a');
    const hashLinks = qsa('.hashtag a');

    // If nothing exists yet (common before AJAX inject), skip silently.
    if (!wikiLinks.length && !hashLinks.length) return;

    // Mark required attributes (idempotent)
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

    // One-time global click closer (idempotent)
    if (!document.body.dataset.snPopCloseBound) {
      document.body.dataset.snPopCloseBound = '1';

      document.addEventListener('click', function (e) {
        const t = e.target;

        // If click is inside a popover, ignore
        if (t && t.closest && t.closest('.popover')) return;

        // If click is on a relevant anchor, ignore (handled elsewhere)
        if (t && t.closest && (t.closest('#wikipedia a') || t.closest('.hashtag a'))) return;

        // Close popovers
        try { $('#wikipedia a').popover('hide'); } catch (_) {}
        try { $('.hashtag a').popover('hide'); } catch (_) {}

        // Reset tap state
        window.__snTapped = 0;
      }, true);
    }

    // Bootstrap popover init (safe)
    try {
      $('[data-toggle="popover"]').popover({
        boundary: 'window',
        html: true
      });
    } catch (_) {}

    // Fetch definition + show
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

          // Only show if still relevant (hovered for desktop)
          const isHovered = $el.is(':hover');
          const allowShow = !isMobile() ? isHovered : true;

          if (allowShow) {
            $el.attr('data-content', def);
            try {
              $('[data-toggle="popover"]').popover({
                boundary: 'window',
                html: true
              });
            } catch (_) {}
            try { $el.popover('show'); } catch (_) {}
          }
        }
      });
    }

    // Desktop: hover show/hide
    if (!isMobile()) {
      // Bind once per element
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

    // Mobile: tap behavior (your existing logic)
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

          // Close all
          hideAll();
          tapped = 0;
          window.__snTapped = 0;

          // If different link, prevent and show popover
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

    // Bind once
    if (document.body.dataset.snOverlayBound === '1') return;
    document.body.dataset.snOverlayBound = '1';

    window.addEventListener('pageshow', hide);

    document.addEventListener('click', function (e) {
      const t = e.target.closest && e.target.closest('[data-loading]');
      if (t) show();
    });

    // Optional: inline button spinner (keeps overlay too)
    document.addEventListener('click', function (e) {
      const btn = e.target.closest && e.target.closest('[data-loading-btn]');
      if (!btn) return;

      btn.dataset.originalHtml = btn.innerHTML;
      btn.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span>&nbsp;Loading…';
      btn.classList.add('disabled');
      btn.setAttribute('aria-busy', 'true');
    });

    // Minimal CSS for inline button spinner:
    const styleId = 'sn-btn-spinner-style';
    if (!qs('#' + styleId)) {
      const style = document.createElement('style');
      style.id = styleId;
      style.textContent = '.btn-spinner{display:inline-block;width:1em;height:1em;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:snspin .6s linear infinite;vertical-align:-0.125em}@keyframes snspin{to{transform:rotate(360deg)}}';
      document.head.appendChild(style);
    }
  }

  // -------------------------
  // 4) Article image loader/fallback (#shot)
  // -------------------------
  function setupShotLoader() {
    const img = qs('#shot');
    const imgLoader = qs('#img-loader');
    if (!img) return;

    // Allow HTML to override fallback via data-fallback-src
    const fallbackSrc = img.getAttribute('data-fallback-src') || 'assets/img/news-placeholder.jpg';

    const hideLoader = () => {
      if (imgLoader) imgLoader.style.display = 'none';
    };

    if (img.complete && img.naturalWidth > 0) hideLoader();

    img.addEventListener('load', hideLoader);

    img.addEventListener('error', function () {
      hideLoader();
      if (img.src !== fallbackSrc) img.src = fallbackSrc;
    });
  }

  // -------------------------
  // 5) IntroJS gating
  // -------------------------
  function maybeRunIntro() {
    const cfg = window.NEWSROOM || {};
    const shouldRun = !!(cfg.intro && cfg.intro.shouldRun);
    if (!shouldRun) return;

    if (typeof introJs !== 'function') return;

    // Run once per page load
    if (window.__snIntroRan) return;
    window.__snIntroRan = true;

    try {
      introJs()
        .setOptions({
          highlightClass: 'custom-highlight',
          overlayOpacity: 0.5
        })
        .start();
    } catch (_) {}
  }

  // -------------------------
  // 6) History write via #history-meta
  // -------------------------
  function writeHistoryFromMeta() {
    // If you already have sn_history.js doing this, guard to avoid double writes.
    if (window.__snHistoryWritten) return;

    const metaEl = qs('#history-meta');
    if (!metaEl) return;

    const historyItem = {
      url: metaEl.dataset.articleUrl || window.location.href,
      title: metaEl.dataset.articleTitle || document.title,
      source: metaEl.dataset.articleSource || '',
      image: metaEl.dataset.articleImage || '',
      pub_date: metaEl.dataset.articlePubDate || null,
      kind: metaEl.dataset.articleKind || 'external',
      clicked_at: new Date().toISOString()
    };

    if (!historyItem.url) return;

    const STORAGE_KEY = 'sn_article_history';

    try {
      const existing = localStorage.getItem(STORAGE_KEY);
      let list = existing ? JSON.parse(existing) : [];

      // De-dupe by URL
      list = list.filter(item => item && item.url !== historyItem.url);

      // Add to front
      list.unshift(historyItem);

      // Cap length
      list = list.slice(0, 200);

      localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
      window.__snHistoryWritten = true;
    } catch (e) {
      if (console && console.warn) console.warn('history save failed', e);
    }
  }

  // -------------------------
  // 7) Reanalyze function (global)
  // -------------------------
  async function reanalyzeAnalytics(url, container = '#analytics') {
    const el = qs(container);
    if (!el) return;

    el.innerHTML = `
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="placeholder-glow">
            <span class="placeholder col-7"></span>
            <span class="placeholder col-4"></span>
            <span class="placeholder col-6"></span>
          </div>
        </div>
      </div>`;

    const form = new URLSearchParams({ url, revalidate: '1' });

    try {
      const res = await fetch('analyze.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: form
      });

      const html = await res.text();
      el.innerHTML = html;

      // Re-bind UI behavior to freshly injected content
      setupAnalyticsUI();
    } catch (e) {
      el.innerHTML = `<div class="alert alert-warning mb-0">Sorry—reanalysis failed.</div>`;
    }
  }

  // Expose for inline onclick="reanalyzeAnalytics(...)"
  window.reanalyzeAnalytics = reanalyzeAnalytics;

  // -------------------------
  // 8) Analytics UI bootstrap
  // -------------------------
  function setupAnalyticsUI() {
    setupSocialTabs();
    setupDefinitionPopovers();
  }

  // Expose if other modules want to call it
  NS.setupAnalyticsUI = setupAnalyticsUI;

  // -------------------------
  // 9) Non-DB analyze flow (lottie + analyze.php POST)
  // -------------------------
  function runAnalyzeFlowIfNeeded() {
    const cfg = window.NEWSROOM || {};
    const fromDb = !!cfg.fromDb;

    // If DB mode, the analytics HTML is already on the page
    if (fromDb) {
      setupAnalyticsUI();
      return;
    }

    // Non-DB mode: show lottie + inject analyze.php response
    const url = cfg.url;
    if (!url) return;

    // Lottie
    try {
      const ele = qs('#lottie');
      if (ele && typeof lottie !== 'undefined' && lottie && typeof lottie.loadAnimation === 'function') {
        lottie.loadAnimation({
          container: ele,
          renderer: 'svg',
          loop: true,
          autoplay: true,
          path: 'assets/img/animation-w500-h500.json'
        });
      }
    } catch (_) {}

    // Load analytics
    const $analytics = $('#analytics');
    if (!$analytics.length) return;

    $.ajax({
      type: 'POST',
      url: 'analyze.php',
      data: { url: url },
      success: function (msg) {
        $analytics.html(msg);
        setupAnalyticsUI();
      },
      error: function () {
        $analytics.html('<div class="alert alert-warning mb-0">Sorry—we could not analyze this article right now.</div>');
      }
    });
  }

  // -------------------------
  // Boot
  // -------------------------
  function initNewsroomPage() {
    setupLoadingOverlay();
    setupShotLoader();
    maybeRunIntro();
    writeHistoryFromMeta();
    runAnalyzeFlowIfNeeded();

    // If analytics already exists, bind immediately
    setupAnalyticsUI();
  }

  // Run when ready (file is loaded with defer in your plan)
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNewsroomPage);
  } else {
    initNewsroomPage();
  }

})();