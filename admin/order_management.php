<?php
session_start();

if (($_SESSION["user_role"] ?? "") !== "admin") {
    header("Location: ../login.php");
    exit;
}

$adminName = $_SESSION["user_name"] ?? "Admin";

/*
 * Order Management (Admin)
 * ------------------------
 * Purpose: Provide administrators with a list of orders and simple actions
 * to mark orders as fulfilled or cancel (delete) them.
 *  - Process POST actions: 'fulfill' (update status) and 'cancel' (delete order and items).
 *  - Use transactions when removing order items and the order to ensure DB consistency.
 *  - Fetch orders and their items efficiently (grouped) for rendering.
 */

require_once __DIR__ . "/../database/db.php";

// Flash message
$message = $_SESSION["order_management_message"] ?? null;
unset($_SESSION["order_management_message"]);

// Handle POST actions: fulfill or cancel
if ($_SERVER["REQUEST_METHOD"] === 'POST') {
    $action = $_POST['action'] ?? '';
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

    if ($orderId === false || $orderId === null) {
        $_SESSION["order_management_message"] = "Invalid order selected.";
        header("Location: order_management.php");
        exit;
    }

    try {
        if ($action === 'fulfill') {
            $stmt = $connection->prepare("UPDATE orders SET status = 'Fulfilled' WHERE order_id = :order_id");
            $stmt->execute(['order_id' => $orderId]);
            $_SESSION["order_management_message"] = "Order marked as fulfilled.";
        } elseif ($action === 'cancel') {
            // Delete order items then the order inside a transaction
            $connection->beginTransaction();
            $delItems = $connection->prepare("DELETE FROM order_items WHERE order_id = :order_id");
            $delItems->execute(['order_id' => $orderId]);
            $delOrder = $connection->prepare("DELETE FROM orders WHERE order_id = :order_id");
            $delOrder->execute(['order_id' => $orderId]);
            $connection->commit();
            $_SESSION["order_management_message"] = "Order cancelled and removed.";
        } else {
            $_SESSION["order_management_message"] = "Unknown action.";
        }
    } catch (PDOException $e) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        $_SESSION["order_management_message"] = "An error occurred while updating the order.";
    }

    header("Location: order_management.php");
    exit;
}

// Fetch orders and order items grouped
$ordersSql = "
    SELECT
        o.order_id,
        o.order_number,
        o.user_id,
        o.full_name,
        o.email,
        o.subtotal_cents,
        o.total_cents,
        o.status,
        o.created_at
    FROM orders o
    ORDER BY o.created_at DESC
";
$ordersStmt = $connection->prepare($ordersSql);
$ordersStmt->execute();
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

$orderItemsByOrderId = [];
if (count($orders) > 0) {
    $orderIds = array_map(static fn(array $o): int => (int) $o['order_id'], $orders);
    $placeholders = implode(', ', array_fill(0, count($orderIds), '?'));
    $itemsSql = "SELECT order_id, product_name, quantity, line_total_cents, options_json FROM order_items WHERE order_id IN ($placeholders) ORDER BY order_item_id ASC";
    $itemsStmt = $connection->prepare($itemsSql);
    $itemsStmt->execute($orderIds);
    foreach ($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $it) {
        $orderItemsByOrderId[(int)$it['order_id']][] = $it;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management | Olive Tree Soap Co.</title>
    <link rel="stylesheet" href="../css/default_style.css">
    <script src="../js/theme-switcher.js" defer></script>
    <meta name="keywords" content="order-management, admin-page">
</head>

<body class="admin-page">

    <header class="site-header">
        <span class="site-brand">Olive Tree Soap Co.</span>

        <div class="site-links">
            <a class="site-link" href="dashboard.php">Admin Dashboard</a>
            <a class="site-link" href="../user-pages/logout.php">Logout</a>
            <span class="site-user-note">Hi, <?= htmlspecialchars($adminName) ?></span>
        </div>
    </header>

    <main class="admin-panel">
        <h1>Order Management</h1>

        <?php if (!empty($message)): ?>
            <div class="cart-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="user-management">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['order_number']) ?></td>
                            <td><?= htmlspecialchars($order['full_name']) ?></td>
                            <td><?= htmlspecialchars($order['email']) ?></td>
                            <td>$<?= number_format($order['total_cents'] / 100, 2) ?></td>
                            <td><?= htmlspecialchars($order['status']) ?></td>
                            <td><?= htmlspecialchars(date('M j, Y g:i a', strtotime($order['created_at']))) ?></td>
                            <td>
                                <div class="actions">
                                    <?php if (strtolower($order['status']) !== 'fulfilled'): ?>
                                        <form method="post" action="order_management.php" onsubmit="return confirm('Mark this order as fulfilled?');">
                                            <input type="hidden" name="order_id" value="<?= (int)$order['order_id'] ?>">
                                            <input type="hidden" name="action" value="fulfill">
                                            <button class="btn btn-neutral" type="submit">Fulfill</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="order_management.php" onsubmit="return confirm('Cancel and delete this order? This cannot be undone.');">
                                        <input type="hidden" name="order_id" value="<?= (int)$order['order_id'] ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button class="btn btn-danger" type="submit">Cancel</button>
                                    </form>

                                    <!-- Toggle details -->
                                    <button class="btn btn-neutral" type="button" onclick="document.getElementById('details_<?= (int)$order['order_id'] ?>').classList.toggle('hidden')">Details</button>
                                </div>
                            </td>
                        </tr>
                        <tr id="details_<?= (int)$order['order_id'] ?>" class="hidden">
                            <td colspan="7" class="order-details">
                                <?php $items = $orderItemsByOrderId[(int)$order['order_id']] ?? []; ?>
                                <?php if (count($items) === 0): ?>
                                    <div class="empty-cart">No items found for this order.</div>
                                <?php else: ?>
                                    <ul>
                                        <?php foreach ($items as $it): ?>
                                            <li><?= htmlspecialchars($it['product_name']) ?> x <?= (int)$it['quantity'] ?> — $<?= number_format($it['line_total_cents'] / 100, 2) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

    

</body>

</html>