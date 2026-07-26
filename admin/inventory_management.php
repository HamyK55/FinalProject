<?php
session_start();

if (($_SESSION["user_role"] ?? "") !== "admin") {
    header("Location: ../login.php");
    exit;
}

$adminName = $_SESSION["user_name"] ?? "Admin";

require_once __DIR__ . "/../database/db.php";

// Fetch categories for the category select
$catsStmt = $connection->prepare("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$catsStmt->execute();
$categories = $catsStmt->fetchAll(PDO::FETCH_ASSOC);
$categoryIds = array_map(static fn($c) => (int)$c['category_id'], $categories);

// Flash message
$message = $_SESSION["inventory_message"] ?? null;
unset($_SESSION["inventory_message"]);

// Handle update product POST
if ($_SERVER["REQUEST_METHOD"] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['product_name'] ?? '');
    $priceRaw = $_POST['base_price'] ?? '';
    $stockRaw = $_POST['stock'] ?? '';
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $isActiveRaw = $_POST['is_active'] ?? '0';
    $isActive = ($isActiveRaw === '1') ? 1 : 0;

    if ($productId === false || $productId === null) {
        $_SESSION['inventory_message'] = 'Invalid product id.';
        header('Location: inventory_management.php');
        exit;
    }

    // Validate and sanitize inputs
    $price = is_numeric($priceRaw) ? round((float)$priceRaw, 2) : null;
    $stock = filter_var($stockRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

    if ($name === '' || $price === null || $stock === false) {
        $_SESSION['inventory_message'] = 'Please provide a valid name, price, and stock (stock must be 0 or more).';
        header('Location: inventory_management.php');
        exit;
    }

    // Validate category
    if ($categoryId === false || $categoryId === null || !in_array($categoryId, $categoryIds, true)) {
        $_SESSION['inventory_message'] = 'Please select a valid category.';
        header('Location: inventory_management.php');
        exit;
    }

    try {
        $stmt = $connection->prepare("UPDATE products SET product_name = :name, base_price = :price, stock = :stock, category_id = :category_id, is_active = :is_active WHERE product_id = :product_id");
        $stmt->execute(['name' => $name, 'price' => $price, 'stock' => $stock, 'category_id' => $categoryId, 'is_active' => $isActive, 'product_id' => $productId]);
        $_SESSION['inventory_message'] = 'Product updated successfully.';
    } catch (PDOException $e) {
        $_SESSION['inventory_message'] = 'Error updating product.';
    }

    header('Location: inventory_management.php');
    exit;
}

// Fetch products
$sql = "
    SELECT p.product_id, p.product_name, p.base_price, p.stock, p.is_active, p.category_id, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.product_name ASC
";
$stmt = $connection->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management | Olive Tree Soap Co.</title>
    <link rel="stylesheet" href="../css/default_style.css">
</head>

<body class="admin-page">

    <header class="site-header">
        <span class="site-brand">Olive Tree Soap Co.</span>

        <div class="site-links">
            <a class="site-link" href="dashboard.php">Admin Dashboard</a>
            <a class="site-link" href="../user-pages/logout.php">Logout</a>
            <span class="site-user-note">Hi, <?= htmlspecialchars($adminName) ?></span>
        </div>
    </header>

    <main class="admin-panel">
        <h1>Inventory Management</h1>

        <?php if (!empty($message)): ?>
            <div class="cart-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="user-management inventory-management">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= (int)$p['product_id'] ?></td>
                                <td>
                                <form method="post" action="inventory_management.php" class="inline-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                                    <input type="text" name="product_name" value="<?= htmlspecialchars($p['product_name']) ?>" required>
                                </td>
                                <td>
                                    <select name="category_id" required>
                                    <option value="">--</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= (int)$cat['category_id'] ?>" <?= ((int)$p['category_id'] === (int)$cat['category_id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['category_name']) ?></option>
                                    <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="base_price" value="<?= number_format((float)$p['base_price'], 2, '.', '') ?>" required>
                                </td>
                                <td>
                                    <input type="number" name="stock" min="0" value="<?= (int)$p['stock'] ?>" required>
                                </td>
                                <td>
                                    <select name="is_active">
                                    <option value="1" <?= ((int)$p['is_active'] === 1) ? 'selected' : '' ?>>Yes</option>
                                    <option value="0" <?= ((int)$p['is_active'] === 0) ? 'selected' : '' ?>>No</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-neutral" type="submit">Update</button>
                                </form>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

</body>

</html>