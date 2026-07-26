<?php

// Boiler plate setup, same as index.php, but this page is for a single product and will display the product details and options.
session_start();

require_once "../database/db.php";

$loggedInUserName = $_SESSION["user_name"] ?? null;

// Get the product ID from the query string and validate it as an integer. If it's not valid, return a 400 Bad Request response.
$productId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$productId) {
    http_response_code(400);
    die("Invalid product ID.");
}

// Prepare sql query to fetch product details. 
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


// Prepare the query to fetch product details, use the product ID from the query string to get the specific product. If the product is not found, return a 404 Not Found response.

$stmt = $connection->prepare($sql); 
$stmt->execute([ 
    "product_id" => $productId
]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    die("Product not found.");
}

// Prep sql query to get product options for the specific product. 
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

$optionGroups = [];

foreach ($options as $option) {
    $optionGroups[$option["option_name"]][] = $option;
}

// Fetch product images (primary first)
$imagesStmt = $connection->prepare(
    "SELECT image_path, alt_text, is_primary FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, sort_order ASC"
);
$imagesStmt->execute(["product_id" => $productId]);
$images = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);

// end of php code
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($product["product_name"]) ?>
    </title>
    <link rel="stylesheet" href="../css/default_style.css">
</head>

<body>

    <!-- Html follows similar structure as index.php -->
    <header class="site-header">
        <a class="site-link" href="../index.php">
            Olive Tree Soap Co.
        </a>

        <div class="site-links">
            <a class="site-cart-link" href="cart.php">
                View Cart
            </a>

            <?php if ($loggedInUserName): ?>

                <a class="site-link" href="order_history.php">
                    Order History
                </a>

                <a class="site-link" href="logout.php">
                    Logout
                </a>

                <span class="site-user-note">
                    Hi, <?= htmlspecialchars($loggedInUserName) ?>
                </span>

            <?php else: ?>

                <a class="site-link" href="login.php">
                    Login
                </a>

                <a class="site-link" href="register.php">
                    Register
                </a>

            <?php endif; ?>
        </div>
    </header>

    <main class="product-details">

        <a class="back-link" href="../index.php">
            &larr; Back to Products
        </a>

        <p class="category">
            <?= htmlspecialchars($product["category_name"]) ?>
        </p>

        <h1>
            <?= htmlspecialchars($product["product_name"]) ?>
        </h1>
        <!-- Load product image onto screen -->
        <?php if (!empty($images)): ?>
            <?php $main = $images[0]; ?>
            <?php $mainSrc = '../' . ltrim($main['image_path'], '/'); ?>
            <img id="product-main-image" class="product-main-image" src="<?= htmlspecialchars($mainSrc) ?>" alt="<?= htmlspecialchars($main['alt_text'] ?? $product['product_name']) ?>">

            <?php if (count($images) > 1): ?>
                <div class="product-gallery">
                    <?php foreach ($images as $img): ?>
                        <?php $thumbSrc = '../' . ltrim($img['image_path'], '/'); ?>
                        <img class="product-thumb" src="<?= htmlspecialchars($thumbSrc) ?>" alt="<?= htmlspecialchars($img['alt_text'] ?? '') ?>">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

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

        <!-- Form to add the product to the cart, with options and quantity. The form will submit to cart.php with the action "add" and the product ID. The options will be sent as an array of option IDs. -->
        <form class="product-form" action="cart.php" method="post">

            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int) $productId ?>">

            <?php foreach ($optionGroups as $optionName => $values): ?>

                <div class="option-group">

                    <label>
                        <?= htmlspecialchars($optionName) ?>
                    </label>

                    <select class="product-option" name="option_ids[]" required>

                        <?php foreach ($values as $option): ?>

                            <option value="<?= (int) $option["option_id"] ?>"
                                data-adjustment="<?= htmlspecialchars($option["price_adjustment"]) ?>">
                                <?= htmlspecialchars($option["option_value"]) ?>

                                <?php if ($option["price_adjustment"] > 0): ?>
                                    (+$<?= number_format($option["price_adjustment"], 2) ?>)
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
        <!-- JavaScript to dynamically update the displayed price based on selected options. It listens for changes in the option dropdowns and recalculates the total price accordingly. -->
        const basePrice = <?= json_encode((float) $product["base_price"]) ?>;
        const optionMenus = document.querySelectorAll(".product-option");
        const displayPrice = document.getElementById("display-price");

        function updatePrice() {
            let updatedPrice = basePrice;

            optionMenus.forEach(function (menu) {
                const selectedOption = menu.options[menu.selectedIndex];
                updatedPrice += Number(selectedOption.dataset.adjustment);
            });

            displayPrice.textContent = "$" + updatedPrice.toFixed(2);
        }

        optionMenus.forEach(function (menu) {
            menu.addEventListener("change", updatePrice);
        });

        updatePrice();

        // Swap main image when a thumbnail is clicked
        const thumbs = document.querySelectorAll('.product-thumb');
        const mainImage = document.getElementById('product-main-image');
        if (thumbs && mainImage) {
            thumbs.forEach(t => t.addEventListener('click', function () {
                mainImage.src = this.src;
                mainImage.alt = this.alt || mainImage.alt;
                thumbs.forEach(x => x.classList.remove('selected'));
                this.classList.add('selected');
            }));
        }
    </script>

</body>

</html>