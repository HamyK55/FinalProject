<?php

session_start();

if (($_SESSION["user_role"] ?? "") !== "admin") {
    header("Location: ../login.php");
    exit;
}

$adminName = $_SESSION["user_name"] ?? "Admin";

/*
 * Add Product (Admin)
 * -------------------
 * Responsibilities:
 *  - Render a form for creating a new product (name, description, category, price, stock, active flag).
 *  - Accept multiple image uploads, store files under `images/product_images/` and record paths in `product_images`.
 *  - Use a DB transaction so product and images are saved atomically.
 *  - Use prepared statements and basic validation for inputs and uploaded files.
 */

require_once __DIR__ . "/../database/db.php";

// Fetch categories for the select dropdown
$catsStmt = $connection->prepare("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$catsStmt->execute();
$categories = $catsStmt->fetchAll(PDO::FETCH_ASSOC);
$categoryIds = array_map(static fn($c) => (int)$c['category_id'], $categories);

// Flash message for user feedback
$message = $_SESSION['add_product_message'] ?? null;
unset($_SESSION['add_product_message']);

// Handle form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priceRaw = $_POST['base_price'] ?? '';
    $stockRaw = $_POST['stock'] ?? '';
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0;

    // Basic validation
    $price = is_numeric($priceRaw) ? round((float)$priceRaw, 2) : null;
    $stock = filter_var($stockRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

    if ($name === '' || $price === null || $stock === false) {
        $_SESSION['add_product_message'] = 'Please provide a valid product name, price, and stock (stock >= 0).';
        header('Location: add_product.php');
        exit;
    }

    if ($categoryId === false || $categoryId === null || !in_array($categoryId, $categoryIds, true)) {
        $_SESSION['add_product_message'] = 'Please select a valid category.';
        header('Location: add_product.php');
        exit;
    }

    // Prepare upload directory
    $uploadDir = __DIR__ . '/../images/product_images';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Begin transaction: insert product then images
    try {
        $connection->beginTransaction();

        $insertSql = "INSERT INTO products (category_id, product_name, description, base_price, stock, is_active) VALUES (:category_id, :name, :desc, :price, :stock, :is_active)";
        $insertStmt = $connection->prepare($insertSql);
        $insertStmt->execute([
            'category_id' => $categoryId,
            'name' => $name,
            'desc' => $description,
            'price' => $price,
            'stock' => $stock,
            'is_active' => $isActive,
        ]);

        $productId = (int)$connection->lastInsertId();

        // Handle uploaded images (optional)
        $uploadedAny = false;
        if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $files = $_FILES['images'];
            // Prepare insert for images
            $imgInsert = $connection->prepare("INSERT INTO product_images (product_id, image_path, alt_text, is_primary, sort_order) VALUES (:product_id, :image_path, :alt_text, :is_primary, :sort_order)");

            $isPrimarySet = false;
            // Loop through files
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    continue; // skip errors silently
                }

                $tmpName = $files['tmp_name'][$i];
                $origName = $files['name'][$i];
                $fileSize = $files['size'][$i];

                // Basic file type check using MIME type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

                if (!isset($allowed[$mime])) {
                    continue; // skip unsupported types
                }

                $ext = $allowed[$mime];
                // Generate safe filename
                $safeBase = preg_replace('/[^a-z0-9\-]/i', '-', pathinfo($origName, PATHINFO_FILENAME));
                $unique = sprintf('%s_%s.%s', substr($safeBase, 0, 50), bin2hex(random_bytes(6)), $ext);
                $destination = $uploadDir . '/' . $unique;

                if (!move_uploaded_file($tmpName, $destination)) {
                    continue; // skip if cannot move
                }

                // Public path saved in DB (relative)
                $publicPath = 'images/product_images/' . $unique;

                $isPrimary = $isPrimarySet ? 0 : 1; // first successful upload is primary
                $isPrimarySet = true;

                $imgInsert->execute([
                    'product_id' => $productId,
                    'image_path' => $publicPath,
                    'alt_text' => $origName,
                    'is_primary' => $isPrimary,
                    'sort_order' => $i,
                ]);

                $uploadedAny = true;
            }
        }

        $connection->commit();
        $_SESSION['add_product_message'] = 'Product added successfully.' . ($uploadedAny ? ' Images uploaded.' : ' No images uploaded.');
    } catch (Exception $e) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        $_SESSION['add_product_message'] = 'Error adding product.';
    }

    header('Location: add_product.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | Olive Tree Soap Co.</title>
    <link rel="stylesheet" href="../css/default_style.css">
    <script src="../js/theme-switcher.js" defer></script>
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
        <h1>Add Product</h1>

        <?php if (!empty($message)): ?>
            <div class="cart-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post" action="add_product.php" enctype="multipart/form-data" class="product-form">
            <div class="option-group">
                <label for="product_name">Product Name</label>
                <input id="product_name" type="text" name="product_name" required>
            </div>

            <div class="option-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"></textarea>
            </div>

            <div class="option-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="option-group">
                <label for="base_price">Base Price (e.g. 9.99)</label>
                <input id="base_price" type="text" name="base_price" required>
            </div>

            <div class="option-group">
                <label for="stock">Stock</label>
                <input id="stock" type="number" name="stock" min="0" value="0" required>
            </div>

            <div class="option-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" checked> Active
                </label>
            </div>

            <div class="option-group">
                <label for="images">Product Images (multiple allowed)</label>
                <input id="images" type="file" name="images[]" multiple accept="image/*">
                <p class="checkout-note">Images will be stored in <strong>images/product_images/</strong>.</p>
            </div>

            <button class="add-to-cart" type="submit">Add Product</button>
        </form>

    </main>

</body>

</html>