<?php

// Boilerplate code to setup db connection and shared variables in session. 
session_start();

require_once "../database/db.php";
$loggedInUserName = $_SESSION["user_name"] ?? null;

// Initialize the cart in the session if it doesn't exist yet. This ensures that we always have a cart array to work with.
if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

/*
 * Shopping cart POST handler - Summary
 * ----------------------------------
 * This section handles all POST requests made to this page (add, clear, remove, update).
 * For POST actions:
 *  1. Read action from $_POST and validate required inputs.
 *  2. For add: validate product and options against the database, compute unit price (in cents),
 *     check stock, and append the item to $_SESSION['cart'].
 *  3. For clear: empty the session cart array.
 *  4. For remove and update: validate the provided cart index, then remove or change quantity
 *     after checking stock in the database.
 *  5. Each option sets a user-visible message in $_SESSION['cart_message'] and uses a
 *     POST → Redirect → GET pattern (header('Location: cart.php')) to avoid double-submits.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // Add item to cart 
    // Validate incoming product_id and quantity, verify product+options in DB,
    // compute unit price (base + option adjustments) in cents, ensure stock is available,
    // then append the item to the session cart and redirect with a message.
    if ($action === "add") {
        $productId = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT, [
            "options" => ["min_range" => 1]
        ]);

        if (!$productId || !$quantity) {
            $_SESSION["cart_message"] = "Please choose a valid product and quantity.";
            header("Location: cart.php");
            exit;
        }

        // Fetch product details which are active from the database to validate and get pricing information
        $productSql = "
            SELECT
                products.product_id,
                products.product_name,
                products.base_price,
                products.stock,
                categories.category_name
            FROM products
            INNER JOIN categories
                ON products.category_id = categories.category_id
            WHERE products.product_id = :product_id
              AND products.is_active = 1
        ";

        $productStatement = $connection->prepare($productSql);
        $productStatement->execute(["product_id" => $productId]);
        $product = $productStatement->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $_SESSION["cart_message"] = "This product is no longer available.";
            header("Location: cart.php");
            exit;
        }

        $selectedOptionIds = $_POST["option_ids"] ?? [];
        if (!is_array($selectedOptionIds)) {
            $selectedOptionIds = [];
        }

        $selectedOptionIds = array_values(array_unique(array_map("intval", $selectedOptionIds)));
        $optionRows = [];

        
        if (count($selectedOptionIds) > 0) {
            $placeholders = implode(", ", array_fill(0, count($selectedOptionIds), "?"));

            $optionSql = "
                SELECT
                    option_id,
                    option_name,
                    option_value,
                    price_adjustment
                FROM product_options
                WHERE product_id = ?
                  AND option_id IN ($placeholders)
                ORDER BY option_name, option_id
            ";

            $optionStatement = $connection->prepare($optionSql);
            $optionStatement->execute(array_merge([$productId], $selectedOptionIds));
            $optionRows = $optionStatement->fetchAll(PDO::FETCH_ASSOC);
        }

        if (count($optionRows) !== count($selectedOptionIds)) {
            $_SESSION["cart_message"] = "One or more selected options were invalid.";
            header("Location: cart.php");
            exit;
        }

        $basePriceCents = (int) round(((float) $product["base_price"]) * 100);
        $optionPriceCents = 0;
        $cartOptions = [];

        foreach ($optionRows as $optionRow) {
            $optionPriceCents += (int) round(((float) $optionRow["price_adjustment"]) * 100);
            $cartOptions[] = [
                "option_name" => $optionRow["option_name"],
                "option_value" => $optionRow["option_value"]
            ];
        }

        $unitPriceCents = $basePriceCents + $optionPriceCents;
        $requestedQuantity = (int) $quantity;

        if ($requestedQuantity > (int) $product["stock"]) {
            $_SESSION["cart_message"] = "Only " . (int) $product["stock"] . " of this product are currently available.";
            header("Location: cart.php");
            exit;
        }

        // Add the product to the cart session variable
        $_SESSION["cart"][] = [
            "product_id" => (int) $product["product_id"],
            "product_name" => $product["product_name"],
            "description" => $product["description"],
            "category_name" => $product["category_name"],
            "unit_price_cents" => $unitPriceCents,
            "quantity" => $requestedQuantity,
            "options" => $cartOptions
        ];

        $_SESSION["cart_message"] = "The item was added to your cart.";
        header("Location: cart.php");
        exit;
    }

    // === Clear cart ===
    // Remove all items from the session cart and redirect with a status message.
    if ($action === "clear") {
        $_SESSION["cart"] = [];
        $_SESSION["cart_message"] = "Your shopping cart was cleared.";
        header("Location: cart.php");
        exit;
    }

    $cartIndex = filter_input(INPUT_POST, "cart_index", FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 0]
    ]);

    // Validate cart index for remove/update actions: ensure it's a non-negative integer
    // and that an item exists at that index in the session cart.
    if ($cartIndex === false || !isset($_SESSION["cart"][$cartIndex])) {
        $_SESSION["cart_message"] = "The selected cart item was not found.";
        header("Location: cart.php");
        exit;
    }

    // Remove item 
    // Remove the item at the validated cart index and reindex the session array.
    if ($action === "remove") {
        unset($_SESSION["cart"][$cartIndex]);
        $_SESSION["cart"] = array_values($_SESSION["cart"]);
        $_SESSION["cart_message"] = "The item was removed from your cart.";
        header("Location: cart.php");
        exit;
    }

    // Update quantity
    // Validate new quantity, confirm current product stock from DB, then update
    // the session cart item's quantity and redirect with a status message.
    if ($action === "update") {
        $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT, [
            "options" => ["min_range" => 1]
        ]);

        if (!$quantity) {
            $_SESSION["cart_message"] = "The quantity must be at least 1.";
            header("Location: cart.php");
            exit;
        }

        $cartItem = $_SESSION["cart"][$cartIndex];

        $stockSql = "
            SELECT stock
            FROM products
            WHERE product_id = :product_id
              AND is_active = 1
        ";

        $stockStatement = $connection->prepare($stockSql);
        $stockStatement->execute(["product_id" => $cartItem["product_id"]]);

        $productStock = $stockStatement->fetchColumn();

        if ($productStock === false) {
            $_SESSION["cart_message"] = "This product is no longer available.";
            header("Location: cart.php");
            exit;
        }

        if ($quantity > (int) $productStock) {
            $_SESSION["cart_message"] = "Only " . (int) $productStock . " of this product are currently available.";
            header("Location: cart.php");
            exit;
        }

        $_SESSION["cart"][$cartIndex]["quantity"] = $quantity;
        $_SESSION["cart_message"] = "The cart quantity was updated.";
        header("Location: cart.php");
        exit;
    }

    $_SESSION["cart_message"] = "The requested cart action was invalid.";
    header("Location: cart.php");
    exit;
}


$cartMessage = $_SESSION["cart_message"] ?? null;
unset($_SESSION["cart_message"]);

$cartSubtotalCents = 0;
$totalItemQuantity = 0;

foreach ($_SESSION["cart"] as $item) {
    $lineTotalCents = $item["unit_price_cents"] * $item["quantity"];
    $cartSubtotalCents += $lineTotalCents;
    $totalItemQuantity += $item["quantity"];
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="View and update your Olive Tree Soap Co. shopping cart.">
	<meta name="keywords" content="lipbalm, olivetree, soap, skincare, product page">
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <title>Shopping Cart | Olive Tree Soap Co.</title>
    <link rel="stylesheet" href="../css/default_style.css">
    <script src="../js/theme-switcher.js" defer></script>
</head>

<body>

    <main class="cart-container">

        <!-- Page header: cart title and continue-shopping link -->
        <div class="cart-header">
            <h1>Shopping Cart (<?= (int) $totalItemQuantity ?>)</h1>
            <a class="button-link" href="../index.php">Continue Shopping</a>
        </div>

        <!-- Status message area: shows the result of the last cart action (add/update/remove/clear) -->
        <?php if ($cartMessage): ?>
            <div class="cart-message"><?= htmlspecialchars($cartMessage) ?></div>
        <?php endif; ?>

        <!-- Empty-cart state which is shown when there are no items in the session cart -->
        <?php if (count($_SESSION["cart"]) === 0): ?>
            <section class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Browse our products and add something to your cart.</p>
                <a class="button-link" href="../index.php">Browse Products</a>
            </section>

        <!-- Cart items list: iterate and display each cart item with buttons for updating -->
        <?php else: ?>
            <?php foreach ($_SESSION["cart"] as $cartIndex => $item): ?>
                <?php $lineTotalCents = $item["unit_price_cents"] * $item["quantity"]; ?>

                <article class="cart-item">
                    <!-- Cart item left section: product name, options, unit price -->   
                    <div class="cart-item-left">
                        <h2>
                            <a href="product.php?id=<?= (int) $item["product_id"] ?>"><?= htmlspecialchars($item["product_name"]) ?></a>
                        </h2>

                        <?php if (!empty($item["options"])): ?>
                            <ul class="cart-options">
                                <?php foreach ($item["options"] as $option): ?>
                                    <li>
                                        <strong><?= htmlspecialchars($option["option_name"]) ?>:</strong>
                                        <?= htmlspecialchars($option["option_value"]) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <p>Unit price: $<?= number_format($item["unit_price_cents"] / 100, 2) ?></p>
                    </div>
                    
                    <!-- Cart item right section which holds all the buttons for updating. As well as form to update quantity or remove the item from the cart -->
                    <div class="cart-item-right">
                        <form class="quantity-form" action="cart.php" method="post">
                            <input type="hidden" name="cart_index" value="<?= (int) $cartIndex ?>">
                            <label>Quantity
                                <input class="quantity-input" type="number" name="quantity" min="1"
                                       value="<?= (int) $item["quantity"] ?>" required>
                            </label>
                            <button type="submit" name="action" value="update">Update</button>
                            <button class="remove-button" type="submit" name="action" value="remove">Remove</button>
                        </form>
                    </div>

                    <p class="cart-price">Line total: $<?= number_format($lineTotalCents / 100, 2) ?></p>
                </article>

            <?php endforeach; ?>

            <!-- Cart summary: totals and actions -->
            <section class="cart-summary">
                <p>Total cart quantity: <?= (int) $totalItemQuantity ?></p>
                <p class="cart-total">Subtotal: $<?= number_format($cartSubtotalCents / 100, 2) ?></p>
                <div class="cart-actions">
                    <form action="cart.php" method="post">
                        <button class="clear-button" type="submit" name="action" value="clear">Clear Cart</button>
                    </form>
                    <a class="button-link" href="checkout.php">Proceed to Checkout</a>
                </div>
            </section>
        <?php endif; ?>

    </main>

</body>

</html>