 <?php
// User session storage for cart
session_start();

require_once "database/db.php";

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

if (count($_SESSION["cart"]) === 0) {
    header("Location: cart.php");
    exit;
}

$cartSubtotalCents = 0;
$totalItemQuantity = 0;

foreach ($_SESSION["cart"] as $item) {
    $cartSubtotalCents += $item["unit_price_cents"] * $item["quantity"];
    $totalItemQuantity += $item["quantity"];
}


// Get info from server related to orders
$orderPlaced = false;
$orderNumber = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $address = trim($_POST["address"] ?? "");
    // basic error checking 
    if ($name === "" || $email === "" || $address === "") {
        $_SESSION["checkout_message"] =
            "Please fill in all checkout details.";

        header("Location: checkout.php");
        exit;
    }

    
    $_SESSION["cart"] = [];
    $orderPlaced = true;
    $orderNumber = random_int(100000, 999999);
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
                <strong>#<?= (int) $orderNumber ?></strong>.
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