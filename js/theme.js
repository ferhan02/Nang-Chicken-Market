(function () {
    'use strict';

    const STORAGE_KEY = 'nang-theme';
    const root = document.documentElement;

    function getSavedTheme() {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);

            if (stored === 'dark' || stored === 'light') {
                return stored;
            }
        } catch (error) {
            console.warn('Theme storage is unavailable.');
        }

        // Nang Chicken Market default theme
        return 'light';
    }

    function saveTheme(theme) {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (error) {
            console.warn('Could not save theme.');
        }
    }

    function currentTheme() {
        return root.getAttribute('data-theme') === 'dark'
            ? 'dark'
            : 'light';
    }

    function updateButtons(theme) {
        const isDark = theme === 'dark';

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            const icon = button.querySelector('i');
            const label = button.querySelector('.theme-toggle-label');

            button.setAttribute(
                'aria-pressed',
                String(isDark)
            );

            button.setAttribute(
                'aria-label',
                isDark ? 'Switch to light mode' : 'Switch to dark mode'
            );

            button.setAttribute(
                'title',
                isDark ? 'Switch to light mode' : 'Switch to dark mode'
            );

            if (icon) {
                icon.className = isDark
                    ? 'fa-solid fa-sun'
                    : 'fa-solid fa-moon';
            }

            if (label) {
                /*
                   Label shows CURRENT theme instead
                   of the theme the button switches to.
                */
                label.textContent = isDark ? 'Dark' : 'Light';
            }
        });
    }

    function applyTheme(theme, save = false) {
        if (theme !== 'dark') {
            theme = 'light';
        }

        root.setAttribute('data-theme', theme);
        root.style.colorScheme = theme;

        if (save) {
            saveTheme(theme);
        }

        updateButtons(theme);
    }

    /*
       Apply saved theme immediately.
       Do not automatically fall back to operating-system dark mode.
    */
    applyTheme(getSavedTheme(), false);

    document.addEventListener('DOMContentLoaded', () => {

        updateButtons(currentTheme());

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-theme-toggle]');

            if (!button) {
                return;
            }

            const nextTheme =
                currentTheme() === 'dark'
                    ? 'light'
                    : 'dark';

            applyTheme(nextTheme, true);
        });
    });

    /*
       Re-sync theme when browser restores a page
       from its back/forward cache.
    */
    window.addEventListener('pageshow', () => {
        applyTheme(getSavedTheme(), false);
    });

    /*
       Keeps multiple tabs/windows synchronized.
    */
    window.addEventListener('storage', (event) => {
        if (event.key === STORAGE_KEY) {
            applyTheme(getSavedTheme(), false);
        }
    });

})();