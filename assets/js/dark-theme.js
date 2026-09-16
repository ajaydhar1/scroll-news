(function () {
    'use strict';

    const STORAGE_KEY = 'scrollnews:ui-theme';
    const DARK_THEME = 'dark';
    const root = document.documentElement;

    function getTheme() {
        try {
            return window.localStorage.getItem(STORAGE_KEY) === DARK_THEME ? DARK_THEME : 'mindpour';
        } catch (error) {
            return 'mindpour';
        }
    }

    function applyTheme(theme, persist) {
        const activeTheme = theme === DARK_THEME ? DARK_THEME : 'mindpour';
        root.dataset.uiTheme = activeTheme;

        const mindpourStylesheet = document.getElementById('mindpour-theme');
        const darkStylesheet = document.getElementById('dark-theme');
        if (mindpourStylesheet) mindpourStylesheet.disabled = false;
        if (darkStylesheet) darkStylesheet.disabled = activeTheme !== DARK_THEME;

        const toggle = document.getElementById('themeToggle');
        const icon = document.getElementById('themeToggleIcon');
        if (toggle) {
            const darkActive = activeTheme === DARK_THEME;
            toggle.setAttribute('aria-label', darkActive ? 'Switch to Mindpour theme' : 'Switch to Dark City theme');
            toggle.setAttribute('title', darkActive ? 'Switch to Mindpour theme' : 'Switch to Dark City theme');
            toggle.setAttribute('aria-pressed', darkActive ? 'true' : 'false');
        }
        if (icon) {
            icon.setAttribute('class', activeTheme === DARK_THEME ? 'fas fa-sun' : 'fas fa-moon');
        }

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
        applyTheme(getTheme(), false);
        const toggle = document.getElementById('themeToggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                applyTheme(root.dataset.uiTheme === DARK_THEME ? 'mindpour' : DARK_THEME, true);
            });
        }
    });
})();
