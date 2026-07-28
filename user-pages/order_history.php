<?php

session_start();

require_once "../database/db.php";

$loggedInUserName = $_SESSION["user_name"] ?? null;
$loggedInUserId = (int) ($_SESSION["user_id"] ?? 0);
$orders = [];

/*
 * Order history page - responsibilities
 * ------------------------------------
 * - If a user is logged in, load their orders and associated order items from the database.
 * - Group order_items by order_id for easier rendering below.
 * - Render a simple list of orders and the items in each order, or show helpful messages
 *   when the user is not logged in or has no orders.
 */

if ($loggedInUserId > 0) {
    $ordersSql = "
        SELECT
            order_id,
            order_number,
            full_name,
            email,
            subtotal_cents,
            total_cents,
            status,
            created_at
        FROM orders
        WHERE user_id = :user_id
        ORDER BY created_at DESC, order_id DESC
    ";

    $ordersStatement = $connection->prepare($ordersSql);
    // Execute prepared statement to fetch the user's top-level order records
    $ordersStatement->execute(["user_id" => $loggedInUserId]);
    $orders = $ordersStatement->fetchAll(PDO::FETCH_ASSOC);
}

$orderItemsByOrderId = [];

if ($loggedInUserId > 0 && count($orders) > 0) {
    $orderIds = array_map(static fn(array $order): int => (int) $order["order_id"], $orders);
    $placeholders = implode(", ", array_fill(0, count($orderIds), "?"));

    $orderItemsSql = "
        SELECT
            order_id,
            product_name,
            category_name,
            unit_price_cents,
            quantity,
            line_total_cents,
            options_json
        FROM order_items
        WHERE user_id = ?
          AND order_id IN ($placeholders)
        ORDER BY order_id DESC, order_item_id ASC
    ";

    $orderItemsStatement = $connection->prepare($orderItemsSql);
    // Fetch all order_items for the user's orders in one query using positional placeholders
    $orderItemsStatement->execute(array_merge([$loggedInUserId], $orderIds));

    // Group returned items by order_id so the template can render them under each order
    foreach ($orderItemsStatement->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $orderItemsByOrderId[(int) $item["order_id"]][] = $item;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History | Olive Tree Soap Co.</title>
    <meta name="description" content="order history page to see past orders and their details">
	<meta name="keywords" content="lipbalm, olivetree, soap, skincare, history, pastOrders">
    <link rel="stylesheet" href="../css/default_style.css">
    <script src="../js/theme-switcher.js" defer></script>
</head>

<body>

    <header class="site-header">
        <a class="site-link" href="../index.php">Olive Tree Soap Co.</a>
        <div class="site-links">
            <a class="site-cart-link" href="cart.php">View Cart</a>
            <?php if ($loggedInUserName): ?>
                <a class="site-link" href="order_history.php">Order History</a>
                <a class="site-link" href="logout.php">Logout</a>
                <span class="site-user-note">Hi, <?= htmlspecialchars($loggedInUserName) ?></span>
            <?php else: ?>
                <a class="site-link" href="login.php">Login</a>
                <a class="site-link" href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="product-details">
        <h1>Order History</h1>
        
        <!-- Show certain links, based on if the user is logged in or not -->
        <?php if (!$loggedInUserId): ?>
            <div class="cart-message">Please log in to see your saved orders.</div>
            <p><a class="site-link" href="login.php">Login here</a> or <a class="site-link" href="register.php">create an
                    account</a>.</p>
        <?php elseif (count($orders) === 0): ?>
            <div class="empty-cart">
                <h2>No orders yet</h2>
                <p>Once you place an order, it will show up here.</p>
            </div>
        <?php else: ?>
            <div class="order-list">
                <?php foreach ($orders as $order): ?>
                    <article class="order-card">
                        <h2>Order <?= htmlspecialchars($order["order_number"]) ?></h2>
                        <p>Date: <?= htmlspecialchars(date("M j, Y g:i a", strtotime($order["created_at"]))) ?></p>
                        <p>Status: <?= htmlspecialchars($order["status"]) ?></p>
                        <p>Total: $<?= number_format($order["total_cents"] / 100, 2) ?></p>
                        <?php if (!empty($orderItemsByOrderId[(int) $order["order_id"]])): ?>
                            <ul>
                                <?php foreach ($orderItemsByOrderId[(int) $order["order_id"]] as $item): ?>
                                    <li>
                                        <?= htmlspecialchars($item["product_name"]) ?> x <?= (int) $item["quantity"] ?> -
                                        $<?= number_format($item["line_total_cents"] / 100, 2) ?>
                                        <?php $itemOptions = json_decode($item["options_json"], true); ?>
                                        <?php if (is_array($itemOptions) && count($itemOptions) > 0): ?>
                                            <ul>
                                                <?php foreach ($itemOptions as $option): ?>
                                                    <li><?= htmlspecialchars($option["option_name"] ?? "Option") ?>:
                                                        <?= htmlspecialchars($option["option_value"] ?? "") ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

</body>

</html>