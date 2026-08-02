
/**
 * This code will read from the theme config file and insert the correct styling to the html pages.
 */

// Wait for page to load
document.addEventListener('DOMContentLoaded', function () {
    var siteThemeConfigPath = '/FinalProject/config/site_theme.json';
    var cssLocationPath = '/FinalProject/css/';

    // function to load correct css style in a given html page based on the name var 
    function applyTheme(name) {
        // If there exists a non default styling (link element), ex Christmas, then remove it
        var existing = document.getElementById('current-custom-styling');
        if (existing) existing.remove();

        // Stop if the styling is default
        if (!name || name === 'default') return;

        // Create a new "link" element which will point to the custom styling
        var themeLink = document.createElement('link');
        themeLink.id = 'current-custom-styling'; // set id, so we can identify and remove styling later
        themeLink.rel = 'stylesheet';
        themeLink.href = cssLocationPath + name + '_style.css';

        // Inject the new css page link to the html
        document.head.appendChild(themeLink);
    }


    // Read from theme config file, set cache to no store, so we dont end up having old styles on pages
    fetch(siteThemeConfigPath, { cache: 'no-store' })
        // if file loads successfully it will be converted from JSON text to a js object
        .then(function (response) {
            if (!response.ok) return null;
            return response.json();
        })

        // Use response to get the theme, input into apply theme function
        .then(function (config) {
            if (config && config.theme) applyTheme(config.theme);
        });
});