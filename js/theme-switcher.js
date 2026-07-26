/*
 * Loads the theme selected in the site theme file.
 * The default stylesheet stays active, and another stylesheet is added
 * when a different theme, such as Christmas or Halloween, is selected.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Find the default stylesheet so the project folder path can be determined
    var defaultLink = document.querySelector('link[href$="default_style.css"]');
    if (!defaultLink) return;

    try {
        var urlObj = new URL(defaultLink.href, window.location.origin);

        // Find the main project folder
        var rootPath = urlObj.pathname.replace(
            /\/css\/default_style\.css(\?.*)?$/,
            '/'
        );

        if (rootPath === urlObj.pathname) {
            // Use the current folder if the path could not be found
            rootPath = urlObj.pathname.replace(/\/[^\/]*$/, '/');
        }

        var origin = urlObj.origin;
        var cssBase = origin + rootPath + 'css/';
        var configUrl = origin + rootPath + 'config/site_theme.json';

        // Remove the old theme and load the selected theme
        function applyTheme(name) {
            var existing = document.getElementById('theme-override');

            if (existing) {
                existing.parentNode.removeChild(existing);
            }

            if (!name || name === 'default') return;

            var href = cssBase + name + '_style.css';
            var themeLink = document.createElement('link');

            themeLink.id = 'theme-override';
            themeLink.rel = 'stylesheet';
            themeLink.href = href;

            document.head.appendChild(themeLink);
        }

        // Read the selected theme from the JSON file
        fetch(configUrl, { cache: 'no-store' })
            .then(function (response) {
                if (!response.ok) return null;
                return response.json();
            })
            .then(function (config) {
                if (!config || !config.theme) return;
                applyTheme(config.theme);
            })
            .catch(function () {
                // Keep using the default theme if the file cannot be loaded
            });
    } catch (error) {
        // Keep using the default theme if something goes wrong
    }
});