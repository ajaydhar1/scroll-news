/* js/sn-search.js
   Search page behavior:
   - Mode switches (keyword / nlp)
   - Toggles (deep_dive / high_signal)
   - Auto-submit selects
   - Loading overlay
*/

(function () {
  function $(id) {
    return document.getElementById(id);
  }

  function showOverlay() {
    const overlay = $('sn-search-loading');
    if (overlay) overlay.classList.add('active');
  }

  // Submit in a way that triggers the submit event (so overlay logic is consistent)
  function safeSubmit(form) {
    if (!form) return;

    // Ensure the overlay becomes visible before navigation.
    // (Some fast navigations otherwise don't paint.)
    showOverlay();

    // requestSubmit triggers submit handlers + native validation
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
    } else {
      // Fallback: dispatch submit event manually then submit
      // (Old browsers)
      try {
        const evt = new Event('submit', { bubbles: true, cancelable: true });
        const notCanceled = form.dispatchEvent(evt);
        if (notCanceled) form.submit();
      } catch (e) {
        form.submit();
      }
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    const form = $('sn-search-form');
    if (!form) return;

    const modeInput       = $('mode-input');
    const deepDiveInput   = $('deep-dive-input');
    const highSignalInput = $('high-signal-input');

    // --- Loading overlay on any "natural" submits too ---
    // (This will fire for real submit actions and requestSubmit() calls)
    form.addEventListener('submit', function () {
      showOverlay();
    });

    // --- Mode buttons: [data-sn-mode="classic"|"nlp"] ---
    document.querySelectorAll('[data-sn-mode]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const mode = btn.getAttribute('data-sn-mode') || 'classic';

        if (modeInput) modeInput.value = mode;

        // Turning off Deep Dive when switching away from NLP
        if (mode !== 'nlp' && deepDiveInput) deepDiveInput.value = '';

        safeSubmit(form);
      });
    });

    // --- Toggle buttons: [data-sn-toggle="deep_dive"|"high_signal"] ---
    document.querySelectorAll('[data-sn-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const key = btn.getAttribute('data-sn-toggle');
        if (!key) return;

        // If this toggle is only valid for a mode, enforce it
        const onlyWhen = btn.getAttribute('data-sn-only-when-mode');
        if (onlyWhen && modeInput && modeInput.value !== onlyWhen) {
          // Optional: if you want, auto-switch mode:
          // modeInput.value = onlyWhen;
          // (then continue)
          return;
        }

        let input = null;
        if (key === 'deep_dive') input = deepDiveInput;
        if (key === 'high_signal') input = highSignalInput;
        if (!input) return;

        input.value = (input.value === '1') ? '' : '1';

        safeSubmit(form);
      });
    });

    // --- Auto-submit selects (range/sentiment/emotion)
    // Add data-sn-autosubmit="1" to each select you want to auto-submit.
    document.querySelectorAll('[data-sn-autosubmit]').forEach(function (el) {
      el.addEventListener('change', function () {
        safeSubmit(form);
      });
    });

    // Optional: if you keep any links/buttons with [data-sn-loading], show overlay on click
    // (This matches your pattern across the site.)
    const scope = document.getElementById('services'); // your page-section id
    if (scope) {
      scope.querySelectorAll('[data-sn-loading]').forEach(el => {
        el.addEventListener('click', () => showOverlay());
      });
    }
  });
})();