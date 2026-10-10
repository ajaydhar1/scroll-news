(function () {
    'use strict';

    const STORAGE_KEY = 'scrollnews:ui-theme';
    const DARK_THEME = 'dark';
    const root = document.documentElement;
    let transitionResetFrame = 0;

    function getTheme() {
        try {
            return window.localStorage.getItem(STORAGE_KEY) === DARK_THEME ? DARK_THEME : 'mindpour';
        } catch (error) {
            return 'mindpour';
        }
    }

    function syncThemeControls(theme) {
        const darkActive = theme === DARK_THEME;
        const toggle = document.getElementById('themeToggle');
        const icon = document.getElementById('themeToggleIcon');
        if (toggle) {
            toggle.setAttribute('aria-label', darkActive ? 'Switch to Mindpour theme' : 'Switch to Dark City theme');
            toggle.setAttribute('title', darkActive ? 'Switch to Mindpour theme' : 'Switch to Dark City theme');
            toggle.setAttribute('aria-pressed', darkActive ? 'true' : 'false');
        }
        if (icon) {
            icon.setAttribute('class', darkActive ? 'fas fa-sun' : 'fas fa-moon');
        }
        const legacyToggle = document.querySelector('.theme-toggle');
        if (legacyToggle && !icon) {
            legacyToggle.textContent = darkActive ? '\u2600\ufe0f' : '\ud83c\udf19';
        }
    }

    function applyTheme(theme, persist) {
        const activeTheme = theme === DARK_THEME ? DARK_THEME : 'mindpour';
        if (transitionResetFrame) {
            cancelAnimationFrame(transitionResetFrame);
        }
        root.classList.add('ui-theme-switching');
        root.dataset.uiTheme = activeTheme;
        transitionResetFrame = requestAnimationFrame(function () {
            transitionResetFrame = requestAnimationFrame(function () {
                root.classList.remove('ui-theme-switching');
                transitionResetFrame = 0;
            });
        });

        syncThemeControls(activeTheme);

        if (persist) {
            try {
                window.localStorage.setItem(STORAGE_KEY, activeTheme);
            } catch (error) {
                // The current-page selection still works when storage is unavailable.
            }
        }
    }

    applyTheme(getTheme(), false);

    document.addEventListener('DOMContentLoaded', function () {
        syncThemeControls(root.dataset.uiTheme);
        const toggle = document.getElementById('themeToggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                applyTheme(root.dataset.uiTheme === DARK_THEME ? 'mindpour' : DARK_THEME, true);
            });
        }
    });
})();
