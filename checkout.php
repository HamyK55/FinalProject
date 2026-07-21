<?php
/*
 * Start the session so the page can read the shopper's cart.
 */
session_start();

require_once "database/db.php";

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

if (count($_SESSION["cart"]) === 0) {
    header("Location: cart.php");
    exit;
}

/*
 * Work out the cart total and count how many items are inside it.
 */
$cartSubtotalCents = 0;
$totalItemQuantity = 0;
$cartQuantitiesByProduct = [];

foreach ($_SESSION["cart"] as $item) {
    $cartSubtotalCents += $item["unit_price_cents"] * $item["quantity"];
    $totalItemQuantity += $item["quantity"];

    if (!isset($cartQuantitiesByProduct[$item["product_id"]])) {
        $cartQuantitiesByProduct[$item["product_id"]] = 0;
    }

    $cartQuantitiesByProduct[$item["product_id"]] += $item["quantity"];
}


/*
 * Set up the checkout result before the form is submitted.
 */
$orderPlaced = false;
$orderNumber = null;
$orderError = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $address = trim($_POST["address"] ?? "");

    /*
     * Make sure the shopper filled in the important details.
     */
    if ($name === "" || $email === "" || $address === "") {
        $_SESSION["checkout_message"] =
            "Please fill in all checkout details.";

        header("Location: checkout.php");
        exit;
    }


    $orderNumber = sprintf(
        "ORD-%s-%04d",
        date("YmdHis"),
        random_int(0, 9999)
    );

    /*
     * Give the order its own number so it can be found later.
     */
    try {
        $connection->beginTransaction();

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
                1,
                :order_number,
                :full_name,
                :email,
                :shipping_address,
                :subtotal_cents,
                :total_cents
            )
        ";

        /*
         * Save the order details into the database.
         */
        $orderStatement = $connection->prepare($orderSql);
        $orderStatement->execute([
            "order_number" => $orderNumber,
            "full_name" => $name,
            "email" => $email,
            "shipping_address" => $address,
            "subtotal_cents" => $cartSubtotalCents,
            "total_cents" => $cartSubtotalCents
        ]);

        $stockStatement = $connection->prepare(
            "
            UPDATE products
            SET stock = stock - :quantity
            WHERE product_id = :product_id
              AND stock >= :quantity
              AND is_active = 1
        "
        );

        /*
         * Reduce stock for every product in the cart.
         */
        foreach ($cartQuantitiesByProduct as $productId => $quantity) {
            $stockStatement->execute([
                "product_id" => (int) $productId,
                "quantity" => (int) $quantity
            ]);

            if ($stockStatement->rowCount() === 0) {
                throw new PDOException(
                    "Not enough stock available for product ID " .
                    (int) $productId
                );
            }
        }

        $connection->commit();

        $_SESSION["cart"] = [];
        $orderPlaced = true;
    } catch (PDOException $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }

        /*
         * Show a simple message if the order could not be saved.
         */
        $orderError =
            "We could not save your order right now. Please try again.";

        $_SESSION["checkout_message"] = $orderError;
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
    <meta name="description" content="Complete your Olive Tree Soap Co. order.">
    <title>Checkout | Olive Tree Soap Co.</title>
    <link rel="stylesheet" href="css/default_style.css">
</head>
<body>

    <header class="site-header">
        <a class="site-brand" href="index.php">Olive Tree Soap Co.</a>
        <a class="site-cart-link" href="cart.php">View Cart</a>
    </header>

    <main class="product-details">

        <a class="back-link" href="cart.php">
            &larr; Back to Cart
        </a>

        <?php if ($orderPlaced): ?>

            <h1>Order Complete</h1>

            <p>
                Thank you for your order. Your order number is
                <strong>#<?= htmlspecialchars($orderNumber) ?></strong>.
            </p>

            <p>
                Your cart has been cleared and your order has been received.
            </p>

            <a class="button-link" href="index.php">
                Return to Shop
            </a>

        <?php else: ?>

            <h1>Checkout</h1>

            <?php if ($checkoutMessage): ?>
                <div class="cart-message">
                    <?= htmlspecialchars($checkoutMessage) ?>
                </div>
            <?php endif; ?>

            <p>
                You have <?= (int) $totalItemQuantity ?> item(s) in your cart.
                Subtotal: $<?= number_format($cartSubtotalCents / 100, 2) ?>
            </p>

            <!-- List the items the shopper is about to buy. -->
            <section class="checkout-items">
                <h2>Your Order</h2>

                <ul>
                    <?php foreach ($_SESSION["cart"] as $item): ?>
                        <li>
                            <?= htmlspecialchars($item["product_name"]) ?>
                            x <?= (int) $item["quantity"] ?>
                            - $<?= number_format(
                                ($item["unit_price_cents"] * $item["quantity"]) / 100,
                                2
                            ) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <!-- This form collects the details needed to place the order. -->
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

                <!-- Show the final note and the button to place the order. -->
                <div class="checkout-summary">
                    <p class="checkout-note">
                        Payment will be handled after this simple demo checkout.
                    </p>

                    <button class="add-to-cart" type="submit">
                        Place Order
                    </button>
                </div>

            </form>

        <?php endif; ?>

    </main>

</body>
</html>