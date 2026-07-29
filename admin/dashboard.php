<?php
/*
 * This file is the admin dashboard for the application.
 * It provides links to various admin sections and displays a welcome message.
 */
session_start();

if (($_SESSION["user_role"] ?? "") !== "admin") {
    header("Location: ../login.php");
    exit;
}

$adminName = $_SESSION["user_name"] ?? "Admin";

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin dashboard for managing the Olive Tree Soap Co.">
	<meta name="keywords" content="adminpage, olivetree, dashboard, management">
    <title>Admin Dashboard | Olive Tree Soap Co.</title>
    <link rel="stylesheet" href="../css/default_style.css">
    <script src="../js/theme-switcher.js" defer></script>
</head>

<body class="admin-page">

    <header class="site-header">
        <span class="site-brand">Olive Tree Soap Co.</span>

        <div class="site-links">
            <a class="site-link" href="dashboard.php">Admin Dashboard</a>
             <a class="site-link" href="../helpPages/help_center.html">Help Center</a>
            <a class="site-link" href="../user-pages/logout.php">Logout</a>
           
            <span class="site-user-note">
                Hi, <?= htmlspecialchars($adminName) ?>
            </span>
        </div>
    </header>

    <main class="admin-panel">
        <div class="admin-panel-topbar">
            <h1>Admin Dashboard</h1>
            <a class="site-link" href="../helpPages/help_center.html">Get Help</a>
        </div>

        <p>
            Choose a section below.
        </p>

        <nav class="site-links" aria-label="Admin sections">
            <a class="site-link" href="user_management.php">User Management</a>
            <a class="site-link" href="order_management.php">Order Management</a>
            <a class="site-link" href="site_templates.php">Site Templates</a>
            <a class="site-link" href="sales_page.php">Sales Page</a>
            <a class="site-link" href="inventory_management.php">Inventory Management</a>
            <a class="site-link" href="add_product.php">Add Product</a>
        </nav>

        <section class="admin-placeholder">
            <h2>Welcome</h2>
            <p>
                This is the starting point for the admin tools.
            </p>
        </section>
    </main>

</body>

</html>