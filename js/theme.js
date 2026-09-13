(function () {
    'use strict';

    /* Prevent duplicate <script> tags from registering two click handlers. */
    if (window.__NANG_THEME_INITIALIZED__) {
        return;
    }
    window.__NANG_THEME_INITIALIZED__ = true;

    const STORAGE_KEY = 'nang-theme';
    const root = document.documentElement;
    const TOGGLE_SELECTOR = '[data-theme-toggle], .theme-toggle';

    function getSavedTheme() {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            return stored === 'dark' ? 'dark' : 'light';
        } catch (error) {
            return 'light';
        }
    }

    function saveTheme(theme) {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (error) {
            /* Theme still works for the current page if storage is unavailable. */
        }
    }

    function currentTheme() {
        return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    function updateButtons(theme) {
        const isDark = theme === 'dark';

        document.querySelectorAll(TOGGLE_SELECTOR).forEach((button) => {
            const icon = button.querySelector('i');
            const label =
                button.querySelector('.theme-toggle-label') ||
                button.querySelector('span');

            button.setAttribute('aria-pressed', String(isDark));
            button.setAttribute(
                'aria-label',
                isDark ? 'Switch to light theme' : 'Switch to dark theme'
            );
            button.setAttribute(
                'title',
                isDark ? 'Switch to light theme' : 'Switch to dark theme'
            );

            /* Icon and text describe the theme the button will switch to. */
            if (icon) {
                icon.className = isDark
                    ? 'fa-solid fa-sun'
                    : 'fa-solid fa-moon';
            }

            if (label) {
                label.textContent = isDark ? 'Light' : 'Dark';
            }
        });
    }

    function applyTheme(theme, save = false) {
        const resolvedTheme = theme === 'dark' ? 'dark' : 'light';

        root.setAttribute('data-theme', resolvedTheme);
        root.style.colorScheme = resolvedTheme;

        if (save) {
            saveTheme(resolvedTheme);
        }

        updateButtons(resolvedTheme);
    }

    function bindThemeControls() {
        if (document.documentElement.dataset.nangThemeBound === 'true') {
            updateButtons(currentTheme());
            return;
        }

        document.documentElement.dataset.nangThemeBound = 'true';

        document.addEventListener('click', (event) => {
            const button = event.target.closest(TOGGLE_SELECTOR);
            if (!button) {
                return;
            }

            const nextTheme =
                currentTheme() === 'dark' ? 'light' : 'dark';

            applyTheme(nextTheme, true);
        });

        updateButtons(currentTheme());
    }

    /* Apply the saved theme immediately to minimise light/dark flashing. */
    applyTheme(getSavedTheme());

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindThemeControls, {
            once: true
        });
    } else {
        bindThemeControls();
    }

    window.addEventListener('pageshow', () => {
        applyTheme(getSavedTheme());
    });

    window.addEventListener('storage', (event) => {
        if (event.key === STORAGE_KEY) {
            applyTheme(getSavedTheme());
        }
    });
})();
