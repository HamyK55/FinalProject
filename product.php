<?php

require_once "database/db.php";

/*
 * Get the product ID from the URL.
 */
$productId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$productId) {
    http_response_code(400);
    die("Invalid product ID.");
}

/*
 * Retrieve the selected product.
 */
$sql = "
    SELECT
        products.product_id,
        products.product_name,
        products.description,
        products.base_price,
        products.stock,
        categories.category_name
    FROM products
    INNER JOIN categories
        ON products.category_id = categories.category_id
    WHERE products.product_id = :product_id
      AND products.is_active = 1
";
//turns sql into prepared statement
$stmt = $connection->prepare($sql);

//adds in live product id 
$stmt->execute([
    "product_id" => $productId
]);

// Get Product Details into an array that we can use later
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    die("Product not found.");
}

/*
 * Retrieve the options for this product.
 */
$optionSql = "
    SELECT
        option_id,
        option_name,
        option_value,
        price_adjustment
    FROM product_options
    WHERE product_id = :product_id
    ORDER BY option_name, option_id
";

$optionStmt = $connection->prepare($optionSql);

$optionStmt->execute([
    "product_id" => $productId
]);

$options = $optionStmt->fetchAll(PDO::FETCH_ASSOC);

/*
 * Group option values by option name.
 */
$optionGroups = [];

foreach ($options as $option) {
    $optionGroups[$option["option_name"]][] = $option;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($product["product_name"]) ?>
    </title>
    <link rel="stylesheet" href="css/default_style.css">
</head>

<body>

    <header class="site-header">
        <a class="site-brand" href="index.php">
            Olive Tree Soap Co.
        </a>

        <a class="site-cart-link" href="cart.php">
            View Cart
        </a>
    </header>

    <!-- Main product Detail card, populate page with uptodate info directly from database $product [field name]-->
    <main class="product-details">

        <a class="back-link" href="index.php">
            &larr; Back to Products
        </a>

        <p class="category">
            <?= htmlspecialchars($product["category_name"]) ?>
        </p>

        <h1>
            <?= htmlspecialchars($product["product_name"]) ?>
        </h1>

        <p>
            <?= htmlspecialchars($product["description"] ?? "") ?>
        </p>

        <p class="price">
            Price:
            <span id="display-price">
                $<?= number_format($product["base_price"], 2) ?>
            </span>
        </p>

        <p>
            In stock: <?= (int) $product["stock"] ?>
        </p>

        <form class="product-form" action="cart.php" method="post">

            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int) $productId ?>">

            <!-- Create option groups based on number of options in database, will make more if new options are added -->
            <?php foreach ($optionGroups as $optionName => $values): ?>

                <!-- Render one dropdown for each option group from the database. -->
                <div class="option-group">

                    <label>
                        <?= htmlspecialchars($optionName) ?>
                    </label>

                    <select class="product-option" name="option_ids[]" required>

                        <?php foreach ($values as $option): ?>

                            <!-- Store the option ID for form use and the price change for JavaScript. -->
                            <option
                                value="<?= (int) $option["option_id"] ?>"
                                data-adjustment="<?=
                                    htmlspecialchars(
                                        $option["price_adjustment"]
                                    )
                                ?>"
                            >
                                <?= htmlspecialchars($option["option_value"]) ?>

                                <!-- Show the extra cost only when this choice adds to the base price. -->
                                <?php if ($option["price_adjustment"] > 0): ?>
                                    (+$<?= number_format(
                                        $option["price_adjustment"],
                                        2
                                    ) ?>)
                                <?php endif; ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            <?php endforeach; ?>

            <div class="product-actions">
                <label class="quantity-field">
                    Quantity
                    <input type="number" name="quantity" min="1" value="1" required>
                </label>

                <button class="add-to-cart" type="submit">
                    Add to Cart
                </button>
            </div>
        </form>

    </main>

    <script>
        // Start with the product's base price from PHP.
        const basePrice =
            <?= json_encode((float) $product["base_price"]) ?>;

        // Collect every option dropdown on the page.
        const optionMenus =
            document.querySelectorAll(".product-option");

        // This is the element that shows the live calculated price.
        const displayPrice =
            document.getElementById("display-price");

        // Recalculate the total by adding each selected option's price adjustment.
        function updatePrice() {
            let updatedPrice = basePrice;

            optionMenus.forEach(function (menu) {
                const selectedOption =
                    menu.options[menu.selectedIndex];

                updatedPrice += Number(
                    selectedOption.dataset.adjustment
                );
            });

            displayPrice.textContent =
                "$" + updatedPrice.toFixed(2);
        }

        // listen for an update in the options and then Update the price 
        optionMenus.forEach(function (menu) {
            menu.addEventListener("change", updatePrice);
        });

        // Run once on page load so the displayed price starts in sync.
        updatePrice();
    </script>

</body>
</html>