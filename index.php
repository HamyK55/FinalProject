<?php

session_start();

require_once "database/db.php";

$loggedInUserName = $_SESSION["user_name"] ?? null;

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
    WHERE products.is_active = 1
    ORDER BY categories.category_name, products.product_name
";

$stmt = $connection->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Our Products</title>
    <link rel="stylesheet" href="css/default_style.css">
</head>

<body>

    <header class="site-header">
        <a class="site-brand" href="index.php">
            Olive Tree Soap Co.
        </a>

        <div class="site-links">
            <a class="site-cart-link" href="cart.php">
                View Cart
            </a>
            <!-- shows the order history button if user is logged in, but if not, it shows the register and login button -->
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

    <h1>Our Products</h1>

    <main class="product-container">

        <?php if (count($products) === 0): ?>

            <p>No products were found.</p>

        <?php else: ?>

            <?php foreach ($products as $product): ?>

                <article class="product-card">
                    <p class="category">
                        <?= htmlspecialchars($product["category_name"]) ?>
                    </p>

                    <h2>
                        <?= htmlspecialchars($product["product_name"]) ?>
                    </h2>

                    <p>
                        <?= htmlspecialchars($product["description"] ?? "") ?>
                    </p>

                    <p class="price">
                        $<?= number_format($product["base_price"], 2) ?>
                    </p>

                    <?php if ($product["stock"] > 0): ?>
                        <p>
                            In stock: <?= (int) $product["stock"] ?>
                        </p>
                    <?php else: ?>
                        <p class="out-of-stock">Out of stock</p>
                    <?php endif; ?>

                    <!-- Link to specific product description --> 
                    <a class="view-product" href="product.php?id=<?= (int) $product["product_id"] ?>">
                        View Product
                    </a>
                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </main>

</body>
</html>