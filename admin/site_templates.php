<?php

// Start the session and make sure the current user is an administrator
session_start();

if (($_SESSION["user_role"] ?? "") !== "admin") {
    header("Location: ../login.php");
    exit;
}

$adminName = $_SESSION["user_name"] ?? "Admin";

// Location of the file that stores the currently selected theme
$configPath = __DIR__ . "/../config/site_theme.json";

// These are the only themes that the administrator is allowed to select
$allowedThemes = ["default", "christmas", "halloween"];

/*
 * When the administrator submits the form, this section checks the selected
 * theme and saves it in site_theme.json. All website pages read this file
 * through theme-switcher.js to determine which stylesheet should be loaded.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["set_theme"])) {
    $selectedTheme = $_POST["theme_choice"] ?? "default";

    // Use the default theme if an unexpected value was submitted
    if (!in_array($selectedTheme, $allowedThemes, true)) {
        $selectedTheme = "default";
    }

    // Convert the selected theme into JSON
    $themeData = [
        "theme" => $selectedTheme
    ];

    $jsonData = json_encode($themeData, JSON_PRETTY_PRINT);

    // Try to save the selected theme
    if ($jsonData !== false && file_put_contents($configPath, $jsonData) !== false) {
        $_SESSION["theme_message"] = "The website theme was changed successfully.";
    } else {
        $_SESSION["theme_message"] = "The theme could not be saved.";
    }

    // Redirect so refreshing the page does not submit the form again
    header("Location: site_templates.php");
    exit;
}

/*
 * Read the current theme from the JSON file. This is used to automatically
 * select the correct option when the administrator opens this page.
 */
$currentTheme = "default";

if (is_readable($configPath)) {
    $jsonData = file_get_contents($configPath);
    $themeData = json_decode($jsonData, true);

    if (
        is_array($themeData) &&
        isset($themeData["theme"]) &&
        in_array($themeData["theme"], $allowedThemes, true)
    ) {
        $currentTheme = $themeData["theme"];
    }
}

// Get the message created after changing the theme
$themeMessage = $_SESSION["theme_message"] ?? null;
unset($_SESSION["theme_message"]);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Site Templates | Olive Tree Soap Co.</title>

    <!--
        The default stylesheet is always loaded. The theme-switcher script
        checks site_theme.json and loads another stylesheet when needed.
    -->
    <link rel="stylesheet" href="../css/default_style.css">
    <script src="../js/theme-switcher.js" defer></script>
</head>

<body class="admin-page">

    <header class="site-header">
        <span class="site-brand">Olive Tree Soap Co.</span>

        <div class="site-links">
            <a class="site-link" href="dashboard.php">Admin Dashboard</a>
            <a class="site-link" href="../user-pages/logout.php">Logout</a>

            <span class="site-user-note">
                Hi, <?= htmlspecialchars($adminName) ?>
            </span>
        </div>
    </header>

    <main class="admin-panel">
        <h1>Site Templates</h1>

        <section class="admin-placeholder">
            <h2>Choose a website theme</h2>

            <?php if ($themeMessage !== null): ?>
                <p class="status-message">
                    <?= htmlspecialchars($themeMessage) ?>
                </p>
            <?php endif; ?>

            <form method="post" action="site_templates.php">
                <label for="theme_choice">Site theme:</label>

                <select id="theme_choice" name="theme_choice">
                    <option
                        value="default"
                        <?= $currentTheme === "default" ? "selected" : "" ?>
                    >
                        Default
                    </option>

                    <option
                        value="christmas"
                        <?= $currentTheme === "christmas" ? "selected" : "" ?>
                    >
                        Christmas
                    </option>

                    <option
                        value="halloween"
                        <?= $currentTheme === "halloween" ? "selected" : "" ?>
                    >
                        Halloween
                    </option>
                </select>

                <button
                    class="btn btn-neutral"
                    type="submit"
                    name="set_theme"
                >
                    Apply Theme
                </button>
            </form>

            <p>
                Current theme:
                <strong><?= htmlspecialchars(ucfirst($currentTheme)) ?></strong>
            </p>
        </section>
    </main>

</body>

</html>