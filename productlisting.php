<?php

require_once "database/db.php";

// Create sql query to get all active products from the database
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

    <style>
        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background-color: #f5f2ea;
            color: #333;
        }

        h1 {
            text-align: center;
        }

        .product-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            max-width: 1100px;
            margin: 30px auto;
        }

        .product-card {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: white;
        }

        .category {
            color: #68794c;
            font-weight: bold;
        }

        .price {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .out-of-stock {
            color: #a00000;
        }
    </style>
</head>

<body>

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
                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </main>

</body>
</html>