<?php

session_start();

if (($_SESSION["user_role"] ?? "") !== "admin") {
    header("Location: ../login.php");
    exit;
}

$adminName = $_SESSION["user_name"] ?? "Admin";
/*
 * Sales Page (Admin)
 * ------------------
 * Purpose: Provide a simple sales summary for administrators. 
 *  - Totals for fulfilled orders (units sold and revenue),
 *  - Pending totals (units and revenue for non-fulfilled orders),
 *  - Breakdown of units sold and revenue by product for fulfilled orders.
 *
 * Implementation notes:
 *  - Uses `order_items` joined with `orders` to aggregate by product.
 *  - Revenue values are stored in cents in the DB and are converted to dollars for display.
 *  - All database access uses prepared statements.
 */

require_once __DIR__ . "/../database/db.php";

// Get sold items only from fulfilled orders)
$soldSql = <<<'SQL'
    SELECT
        oi.product_id,
        oi.product_name,
        SUM(oi.quantity) AS total_quantity,
        SUM(oi.line_total_cents) AS total_revenue_cents
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    WHERE LOWER(o.status) = 'fulfilled'
    GROUP BY oi.product_id, oi.product_name
    ORDER BY total_quantity DESC
SQL;
$soldStmt = $connection->prepare($soldSql);
$soldStmt->execute();
$soldRows = $soldStmt->fetchAll(PDO::FETCH_ASSOC);

// Compute overall sold totals from the aggregated rows
$totalSoldUnits = 0;
$totalRevenueCents = 0;
foreach ($soldRows as $r) {
    $totalSoldUnits += (int)$r['total_quantity'];
    $totalRevenueCents += (int)$r['total_revenue_cents'];
}

// Query Pending items & revenue (orders not yet fulfilled)

$pendingSql = <<<'SQL'
    SELECT
        SUM(oi.quantity) AS pending_units,
        SUM(oi.line_total_cents) AS pending_revenue_cents
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    WHERE LOWER(o.status) != 'fulfilled'
SQL;
$pendingStmt = $connection->prepare($pendingSql);
$pendingStmt->execute();
$pending = $pendingStmt->fetch(PDO::FETCH_ASSOC);

$pendingUnits = (int)($pending['pending_units'] ?? 0);
$pendingRevenueCents = (int)($pending['pending_revenue_cents'] ?? 0);


// Query for orders not yet fulfilled

$pendingByProductSql = <<<'SQL'
    SELECT
        oi.product_id,
        oi.product_name,
        SUM(oi.quantity) AS pending_quantity,
        SUM(oi.line_total_cents) AS pending_revenue_cents
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    WHERE LOWER(o.status) != 'fulfilled'
    GROUP BY oi.product_id, oi.product_name
    ORDER BY pending_quantity DESC
SQL;
$pendingByProductStmt = $connection->prepare($pendingByProductSql);
$pendingByProductStmt->execute();
$pendingRows = $pendingByProductStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="View sales data over the lifetime of the store">
	<meta name="keywords" content="adminpage, olivetree, sales, revenue, orders">
    <title>Sales Page | Olive Tree Soap Co.</title>
    <link rel="stylesheet" href="../css/default_style.css">
    <script src="../js/theme-switcher.js" defer></script>
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
</head>

<body class="admin-page">

    <header class="site-header">
        <span class="site-brand">Olive Tree Soap Co.</span>

        <div class="site-links">
            <a class="site-link" href="dashboard.php">Admin Dashboard</a>
            <a class="site-link" href="../helpPages/help_center.html">Help Center</a>
            <a class="site-link" href="../user-pages/logout.php">Logout</a>
            <span class="site-user-note">Hi, <?= htmlspecialchars($adminName) ?></span>
        </div>
    </header>

    <main class="admin-panel">
        <div class="admin-panel-topbar">
            <h1>Sales Page</h1>
            <a class="site-link" href="../helpPages/sales_page_help.html">Get Help</a>
        </div>
        

        <section class="admin-report">
            <h2>Overview</h2>
            <p>Total units sold: <strong><?= (int)$totalSoldUnits ?></strong></p>
            <p>Total revenue: <strong>$<?= number_format($totalRevenueCents / 100, 2) ?></strong></p>
            <p>Pending units: <strong><?= (int)$pendingUnits ?></strong></p>
            <p>Pending revenue: <strong>$<?= number_format($pendingRevenueCents / 100, 2) ?></strong></p>
        </section>

        <section class="admin-report">
            <h2>Pending by Product (Not Fulfilled)</h2>
            <?php if (count($pendingRows) === 0): ?>
                <div class="empty-cart">No pending items.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Pending Units</th>
                            <th>Pending Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRows as $prow): ?>
                            <tr>
                                <td><?= htmlspecialchars($prow['product_name']) ?></td>
                                <td><?= (int)$prow['pending_quantity'] ?></td>
                                <td>$<?= number_format(((int)$prow['pending_revenue_cents']) / 100, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section class="admin-report">
            <h2>Sold by Product (Fulfilled)</h2>
            <?php if (count($soldRows) === 0): ?>
                <div class="empty-cart">No sales yet.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($soldRows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= (int)$row['total_quantity'] ?></td>
                                <td>$<?= number_format(((int)$row['total_revenue_cents']) / 100, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>

</body>

</html>