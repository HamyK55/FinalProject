<?php

session_start();

require_once "../database/db.php";

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

/*
 * Checkout page overview
 * ---------------------
 * This page renders a simple checkout form and processes order submissions.
 * Server-side responsibilities:
 *  - Ensure the cart is not empty and the user is logged in before placing an order.
 *  - Validate submitted checkout fields (name, email, address).
 *  - Create an order and associated order_items inside a single database transaction. Info is retrieved from the session cart.
 *  - Deduct stock for ordered products, failing the transaction if any product lacks sufficient stock.
 *  - Clear the session cart on success and display an order confirmation.
 *  - On failure, roll back and show a friendly error message.
 */

// redirect to cart
if (count($_SESSION["cart"]) === 0) {
    header("Location: cart.php");
    exit;
}

// Set Session vars for use throughout the page
$userId = (int) ($_SESSION["user_id"] ?? 0);
$loggedInUserName = $_SESSION["user_name"] ?? null;

$cartSubtotalCents = 0;
$totalItemQuantity = 0;
$cartQuantitiesByProduct = [];

// Get Total price of the items in the cart as well as the total quantity of items in the cart. This is used for display and validation during checkout.
foreach ($_SESSION["cart"] as $item) {
    $cartSubtotalCents += $item["unit_price_cents"] * $item["quantity"];
    $totalItemQuantity += $item["quantity"];

    // Track total quantity per product for stock validation
    if (!isset($cartQuantitiesByProduct[$item["product_id"]])) {
        $cartQuantitiesByProduct[$item["product_id"]] = 0;
    }

    $cartQuantitiesByProduct[$item["product_id"]] += $item["quantity"];
}

$orderPlaced = false;
$orderNumber = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Ensure the user is logged in before attempting to place an order
    if ($userId <= 0) {
        $_SESSION["checkout_message"] = "Please log in before placing your order.";
        header("Location: checkout.php");
        exit;
    }

    // Validate required checkout fields
    $name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $address = trim($_POST["address"] ?? "");

    if ($name === "" || $email === "" || $address === "") {
        $_SESSION["checkout_message"] = "Please fill in all checkout details.";
        header("Location: checkout.php");
        exit;
    }

    // Generate a human-readable order number for confirmation and records
    $orderNumber = sprintf("ORD-%s-%04d", date("YmdHis"), random_int(0, 9999));

    try {
        // Begin a single transaction that will create the order, order items and decrement stock.
        // If any step fails (insert or stock update), the transaction will be rolled back.
        $connection->beginTransaction();

        // Insert the top-level order record
        $orderSql = "
            INSERT INTO orders (
                user_id,
                order_number,
                full_name,
                email,
                shipping_address,
                subtotal_cents,
                total_cents
            ) VALUES (
                :user_id,
                :order_number,
                :full_name,
                :email,
                :shipping_address,
                :subtotal_cents,
                :total_cents
            )
        ";

        $orderStatement = $connection->prepare($orderSql);
        $orderStatement->execute([
            "user_id" => $userId,
            "order_number" => $orderNumber,
            "full_name" => $name,
            "email" => $email,
            "shipping_address" => $address,
            "subtotal_cents" => $cartSubtotalCents,
            "total_cents" => $cartSubtotalCents
        ]);

        $orderId = (int) $connection->lastInsertId();

        // Insert each cart item as an order_items row
        $orderItemSql = "
            INSERT INTO order_items (
                order_id,
                user_id,
                order_number,
                product_id,
                product_name,
                category_name,
                unit_price_cents,
                quantity,
                line_total_cents,
                options_json
            ) VALUES (
                :order_id,
                :user_id,
                :order_number,
                :product_id,
                :product_name,
                :category_name,
                :unit_price_cents,
                :quantity,
                :line_total_cents,
                :options_json
            )
        ";

        $orderItemStatement = $connection->prepare($orderItemSql);

        foreach ($_SESSION["cart"] as $item) {
            $orderItemStatement->execute([
                "order_id" => $orderId,
                "user_id" => $userId,
                "order_number" => $orderNumber,
                "product_id" => (int) $item["product_id"],
                "product_name" => $item["product_name"],
                "category_name" => $item["category_name"],
                "unit_price_cents" => (int) $item["unit_price_cents"],
                "quantity" => (int) $item["quantity"],
                "line_total_cents" => (int) $item["unit_price_cents"] * (int) $item["quantity"],
                "options_json" => json_encode($item["options"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ]);
        }

        // Decrement stock for each product. The WHERE clause ensures we only update when enough stock exists.
        // If rowCount() is 0 after executing, it means not enough stock was available and we must abort.
        $stockStatement = $connection->prepare("\n            UPDATE products\n            SET stock = stock - :quantity\n            WHERE product_id = :product_id\n              AND stock >= :quantity\n              AND is_active = 1\n        ");

        foreach ($cartQuantitiesByProduct as $productId => $quantity) {
            $stockStatement->execute([
                "product_id" => (int) $productId,
                "quantity" => (int) $quantity
            ]);

            if ($stockStatement->rowCount() === 0) {
                throw new PDOException("Not enough stock available for product ID " . (int) $productId);
            }
        }

        // Commit the transaction when all inserts and stock updates succeed
        $connection->commit();

        // Clear the user's session cart and mark order as placed for the UI
        $_SESSION["cart"] = [];
        $orderPlaced = true;
    } catch (PDOException $error) {
        // Roll back on any error and show a friendly message without exposing details
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }

        $_SESSION["checkout_message"] = "We could not save your order right now. Please try again.";
    }
}

$checkoutMessage = $_SESSION["checkout_message"] ?? null;
unset($_SESSION["checkout_message"]);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Complete your order and submit your cart">
	<meta name="keywords" content="lipbalm, olivetree, soap, skincare, =checkout, order, submit">
    <meta name="description" content="Complete your Olive Tree Soap Co. order.">
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <title>Checkout | Olive Tree Soap Co.</title>
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

        <a class="back-link" href="cart.php">&larr; Back to Cart</a>

        <?php if (!$loggedInUserName): ?>
            <div class="cart-message">Please log in to complete checkout and save your order.</div>
        <?php endif; ?>

        <?php if ($orderPlaced): ?>
            <h1>Order Complete</h1>
            <p>Thank you for your order. Your order number is <strong>#<?= htmlspecialchars($orderNumber) ?></strong>.</p>
            <p>Your cart has been cleared and your order has been received.</p>
            <a class="button-link" href="../index.php">Return to Shop</a>
        <?php else: ?>
            <h1>Checkout</h1>

            <?php if ($checkoutMessage): ?>
                <div class="cart-message"><?= htmlspecialchars($checkoutMessage) ?></div>
            <?php endif; ?>

            <p>You have <?= (int) $totalItemQuantity ?> item(s) in your cart. Subtotal:
                $<?= number_format($cartSubtotalCents / 100, 2) ?></p>

            <!-- Order summary: shows items the user is about to purchase with quantities and line totals -->
            <section class="checkout-items">
                <h2>Your Order</h2>
                <ul>
                    <?php foreach ($_SESSION["cart"] as $item): ?>
                        <li><?= htmlspecialchars($item["product_name"]) ?> x <?= (int) $item["quantity"] ?> -
                            $<?= number_format(($item["unit_price_cents"] * $item["quantity"]) / 100, 2) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <?php if ($loggedInUserName): ?>
                <!-- Checkout form: collects shipping and contact details and submits to this page -->
                <form class="product-form" action="checkout.php" method="post">
                    <div class="option-group">
                        <label for="full_name">Full Name</label>
                        <input id="full_name" type="text" name="full_name" required>
                    </div>
                    <div class="option-group">
                        <label for="email">Email Address</label>
                        <input id="email" type="email" name="email" required>
                    </div>
                    <div class="option-group">
                        <label for="address">Shipping Address</label>
                        <textarea id="address" name="address" rows="4" required></textarea>
                    </div>
                    <div class="checkout-summary">
                        <p class="checkout-note">Payment will be handled after this simple demo checkout.</p>
                        <button class="add-to-cart" type="submit">Place Order</button>
                    </div>
                </form>
            <?php else: ?>
                <p><a class="button-link" href="login.php">Log in to checkout</a></p>
            <?php endif; ?>

        <?php endif; ?>

    </main>

</body>

</html>