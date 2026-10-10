(function () {
  function $(id) {
    return document.getElementById(id);
  }

  function showStatus() {
    const status = $('sn-search-loading');
    const form = $('sn-search-form');
    if (status) status.hidden = false;
    if (form) form.setAttribute('aria-busy', 'true');
  }

  function syncMode(mode, form) {
    const modeInput = $('mode-input');
    const deepDiveInput = $('deep-dive-input');
    const smartFilters = form.querySelector('[data-sn-smart-filters]');
    const deepDiveToggle = form.querySelector('[data-sn-toggle="deep_dive"]');

    if (modeInput) modeInput.value = mode;

    document.querySelectorAll('[data-sn-mode]').forEach(function (button) {
      const selected = button.getAttribute('data-sn-mode') === mode;
      button.classList.toggle('is-selected', selected);
      button.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });

    if (smartFilters) {
      smartFilters.hidden = mode !== 'nlp';
      smartFilters.querySelectorAll('select').forEach(function (select) {
        select.disabled = mode !== 'nlp';
      });
    }

    if (deepDiveToggle) deepDiveToggle.hidden = mode !== 'nlp';
    if (mode !== 'nlp' && deepDiveInput) {
      deepDiveInput.value = '';
      if (deepDiveToggle) deepDiveToggle.setAttribute('aria-checked', 'false');
    }
  }

  function hideStatus() {
    const status = $('sn-search-loading');
    const form = $('sn-search-form');
    if (status) status.hidden = true;
    if (form) form.removeAttribute('aria-busy');
  }

  window.addEventListener('pageshow', hideStatus);

  document.addEventListener('DOMContentLoaded', function () {
    const form = $('sn-search-form');
    if (!form) return;

    const deepDiveInput = $('deep-dive-input');
    const highSignalInput = $('high-signal-input');

    form.addEventListener('submit', showStatus);

    document.querySelectorAll('[data-sn-mode]').forEach(function (button) {
      button.addEventListener('click', function () {
        syncMode(button.getAttribute('data-sn-mode') || 'classic', form);
      });
    });

    document.querySelectorAll('[data-sn-toggle]').forEach(function (button) {
      button.addEventListener('click', function () {
        const input = button.getAttribute('data-sn-toggle') === 'deep_dive'
          ? deepDiveInput
          : highSignalInput;
        if (!input) return;

        const active = input.value !== '1';
        input.value = active ? '1' : '';
        button.setAttribute('aria-checked', active ? 'true' : 'false');
      });
    });

    const scope = document.getElementById('services');
    if (scope) {
      scope.querySelectorAll('[data-sn-loading]').forEach(function (link) {
        link.addEventListener('click', showStatus);
      });
    }
  });
})();