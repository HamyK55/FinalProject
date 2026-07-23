<?php

session_start();

require_once "../database/db.php";
$loggedInUserName = $_SESSION["user_name"] ?? null;

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

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

    if ($action === "clear") {
        $_SESSION["cart"] = [];
        $_SESSION["cart_message"] = "Your shopping cart was cleared.";
        header("Location: cart.php");
        exit;
    }

    $cartIndex = filter_input(INPUT_POST, "cart_index", FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 0]
    ]);

    if ($cartIndex === false || !isset($_SESSION["cart"][$cartIndex])) {
        $_SESSION["cart_message"] = "The selected cart item was not found.";
        header("Location: cart.php");
        exit;
    }

    if ($action === "remove") {
        unset($_SESSION["cart"][$cartIndex]);
        $_SESSION["cart"] = array_values($_SESSION["cart"]);
        $_SESSION["cart_message"] = "The item was removed from your cart.";
        header("Location: cart.php");
        exit;
    }

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
    <title>Shopping Cart | Olive Tree Soap Co.</title>
    <link rel="stylesheet" href="../css/default_style.css">
</head>
<body>

    <main class="cart-container">
        <div class="cart-header">
            <h1>Shopping Cart (<?= (int) $totalItemQuantity ?>)</h1>
            <a class="button-link" href="../index.php">Continue Shopping</a>
        </div>

        <?php if ($cartMessage): ?>
            <div class="cart-message"><?= htmlspecialchars($cartMessage) ?></div>
        <?php endif; ?>

        <?php if (count($_SESSION["cart"]) === 0): ?>
            <section class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Browse our products and add something to your cart.</p>
                <a class="button-link" href="../index.php">Browse Products</a>
            </section>
        <?php else: ?>
            <?php foreach ($_SESSION["cart"] as $cartIndex => $item): ?>
                <?php $lineTotalCents = $item["unit_price_cents"] * $item["quantity"]; ?>
                <article class="cart-item">
                    <div>
                        <h2><a href="product.php?id=<?= (int) $item["product_id"] ?>"><?= htmlspecialchars($item["product_name"]) ?></a></h2>
                        <?php if (!empty($item["options"])): ?>
                            <ul class="cart-options">
                                <?php foreach ($item["options"] as $option): ?>
                                    <li><strong><?= htmlspecialchars($option["option_name"]) ?>:</strong> <?= htmlspecialchars($option["option_value"]) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <p>Unit price: $<?= number_format($item["unit_price_cents"] / 100, 2) ?></p>
                    </div>
                    <div>
                        <form class="quantity-form" action="cart.php" method="post">
                            <input type="hidden" name="cart_index" value="<?= (int) $cartIndex ?>">
                            <label>Quantity <input class="quantity-input" type="number" name="quantity" min="1" value="<?= (int) $item["quantity"] ?>" required></label>
                            <button type="submit" name="action" value="update">Update</button>
                            <button class="remove-button" type="submit" name="action" value="remove">Remove</button>
                        </form>
                    </div>
                    <p class="cart-price">Line total: $<?= number_format($lineTotalCents / 100, 2) ?></p>
                </article>
            <?php endforeach; ?>

            <section class="cart-summary">
                <p>Total cart quantity: <?= (int) $totalItemQuantity ?></p>
                <p class="cart-total">Subtotal: $<?= number_format($cartSubtotalCents / 100, 2) ?></p>
                <div class="cart-actions">
                    <form action="cart.php" method="post"><button class="clear-button" type="submit" name="action" value="clear">Clear Cart</button></form>
                    <a class="button-link" href="checkout.php">Proceed to Checkout</a>
                </div>
            </section>
        <?php endif; ?>

    </main>

</body>
</html>