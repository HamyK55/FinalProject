<?php

/*
 * This file acts as the initial home page for the application
 * It features all the products available 
 * 
 * Please note, the general format of this code is going to be similar accross the other pages. As such other pages may not be as heavily commented on the reused sections of code. 
 */

session_start(); // Start browser session which holds data across pages, such as the logged in user. 

require_once "database/db.php"; //Import the database connection file for use throughout the page

$loggedInUserName = $_SESSION["user_name"] ?? null; // Get the logged in user's name from the session, if available. If not, set it to null.

// Prepare the sql query to fetch all the active products in the stores selection
$sql = "
	SELECT
		products.product_id,
		products.product_name,
		products.description,
		products.base_price,
		products.stock,
		categories.category_name,
		pi.image_path AS image_path
	FROM products
	INNER JOIN categories
		ON products.category_id = categories.category_id
	LEFT JOIN product_images pi ON products.product_id = pi.product_id AND pi.is_primary = 1
	WHERE products.is_active = 1
	ORDER BY categories.category_name, products.product_name
";


$stmt = $connection->query($sql); // Execute the query using the database connection established in db.php
$products = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all the results as an associative array, which can be easily accessed using column names as keys. This will be used to display the products on the page.


// end of php code, the rest of the page is html with some ebedded php to display the products and user information. 
?>


<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description"
		content="This page displays all the products in product selection and acts as the home of the app">
	<meta name="keywords" content="home, olivetree, soap, skincare">

	<title>Our Products</title>
	<!--
	The default stylesheet is always loaded. The theme-switcher script checks
	the site theme configuration and adds another stylesheet when a special
	theme, such as Christmas or Halloween, is active.
	-->
	<link rel="stylesheet" href="css/default_style.css">
	<script src="js/theme-switcher.js" defer></script>
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<body>

	<!-- The header section of the page, which includes the site title and navigation links. The links displayed depend on whether a user is logged in or not. -->
	<header class="site-header">

		<a class="site-link" href="index.php">
			Olive Tree Soap Co.
		</a>

		<!-- The site-links div contains links for viewing the cart, order history, login/logout, and registration. Depending on it the user is logged it in will show the appropriate links. -->
		<div class="site-links">

			<a class="site-link" href="user-pages/aboutUs.html">
				About Us
			</a>

			<a class="site-cart-link" href="user-pages/cart.php">
				View Cart
			</a>
			<?php if ($loggedInUserName): ?>

				<a class="site-link" href="user-pages/order_history.php">
					Order History
				</a>

				<a class="site-link" href="user-pages/logout.php">
					Logout
				</a>

				<span class="site-user-note">
					Hi, <?= htmlspecialchars($loggedInUserName) ?>
				</span>

			<?php else: ?>

				<a class="site-link" href="user-pages/login.php">
					Login
				</a>

				<a class="site-link" href="user-pages/register.php">
					Register
				</a>

			<?php endif; ?>






		</div>
	</header>


	<h1>Our Products</h1>


	<!-- Contains all the products available in the store. If no products are available, a message will be displayed instead. -->
	<main class="product-container">

		<?php if (count($products) === 0): ?>

			<p>No products were found.</p>

		<?php else: ?>

			<!-- Loop through each product from the database and create a new visual card for the product. Each card will contain live information fetched from the DB like stock count, price and a link to a unique product page for each one -->
			<?php foreach ($products as $product): ?>

				<article class="product-card">

					<!-- Render Img -->
					<?php if (!empty($product['image_path'])): ?>
						<img class="product-image" src="<?= htmlspecialchars($product['image_path']) ?>"
							alt="<?= htmlspecialchars($product['product_name']) ?>">
					<?php endif; ?>

					<p class="category">
						<?= htmlspecialchars($product["category_name"]) ?>
					</p>

					<h2>
						<?= htmlspecialchars($product["product_name"]) ?>
					</h2>

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

					<!-- Generate a unique link to the product page based on the product id -->
					<a class="view-product" href="user-pages/product.php?id=<?= (int) $product["product_id"] ?>">
						View Product
					</a>
				</article>

			<?php endforeach; ?>

		<?php endif; ?>




	</main>


	<p>All Images on this page, and across the site are the property of the Olive Tree Soap Company Copyright 2026</p>


</body>

</html>