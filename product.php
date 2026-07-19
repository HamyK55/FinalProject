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

$stmt = $connection->prepare($sql);

$stmt->execute([
    "product_id" => $productId
]);

// Get Product Details
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

    <style>
        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #f5f2ea;
        }

        .product-details {
            max-width: 650px;
            margin: 30px auto;
            padding: 30px;
            border-radius: 12px;
            background-color: white;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        .category {
            color: #68794c;
            font-weight: bold;
        }

        .price {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .option-group {
            margin: 20px 0;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        select {
            width: 100%;
            padding: 10px;
        }

        .back-link {
            color: #53613d;
        }
    </style>
</head>

<body>
    <!-- Main product Detail card -->
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

        <?php foreach ($optionGroups as $optionName => $values): ?>

        <!-- Create option groups, and script will dynamically change price based on options chosen -->

            <div class="option-group">

                <label>
                    <?= htmlspecialchars($optionName) ?>
                </label>

                <select class="product-option">

                    <?php foreach ($values as $option): ?>

                        <option
                            value="<?= (int) $option["option_id"] ?>"
                            data-adjustment="<?=
                                htmlspecialchars(
                                    $option["price_adjustment"]
                                )
                            ?>"
                        >
                            <?= htmlspecialchars($option["option_value"]) ?>

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

    </main>

    <script>
        const basePrice =
            <?= json_encode((float) $product["base_price"]) ?>;

        const optionMenus =
            document.querySelectorAll(".product-option");

        const displayPrice =
            document.getElementById("display-price");

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

        optionMenus.forEach(function (menu) {
            menu.addEventListener("change", updatePrice);
        });

        updatePrice();
    </script>

</body>
</html>