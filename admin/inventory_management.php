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
$categoryIds = array_map(static fn($c) => (int) $c['category_id'], $categories); // Convert category IDs to integers

// clear message
$message = $_SESSION["inventory_message"] ?? null;
unset($_SESSION["inventory_message"]);

// Handle update product POST (supports replacing primary image and updating description)
if ($_SERVER["REQUEST_METHOD"] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    // Set variables from POST data and clean up data
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priceRaw = $_POST['base_price'] ?? '';
    $stockRaw = $_POST['stock'] ?? '';
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $isActiveRaw = $_POST['is_active'] ?? '0';
    $isActive = ($isActiveRaw === '1') ? 1 : 0;
    $altText = trim($_POST['alt_text'] ?? '');

    // Exit if product_id is invalid
    if ($productId === false || $productId === null) {
        $_SESSION['inventory_message'] = 'Invalid product id.';
        header('Location: inventory_management.php');
        exit;
    }

    // Change price and stock to proper types, validate them
    $price = is_numeric($priceRaw) ? round((float) $priceRaw, 2) : null;
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

    // Begin transaction for product update + optional image replacement
    try {
        $connection->beginTransaction();

        // Update product details in the database
        $updateSql = "UPDATE products SET product_name = :name, description = :description, base_price = :price, stock = :stock, category_id = :category_id, is_active = :is_active WHERE product_id = :product_id";
        $stmt = $connection->prepare($updateSql);
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
            'category_id' => $categoryId,
            'is_active' => $isActive,
            'product_id' => $productId
        ]);


        
        $uploadedAny = false;

        /**
         * Please Note I used the https://www.php.net/manual/en/features.file-upload.php as a reference for learning how to handle file uploads.
         */

        // Handle uploaded image(s) (optional) similar to add_product.php, replace primary image if new images are uploaded
        if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $files = $_FILES['images'];
            $imgInsert = $connection->prepare("INSERT INTO product_images (product_id, image_path, alt_text, is_primary, sort_order) VALUES (:product_id, :image_path, :alt_text, :is_primary, :sort_order)");

        
            $isPrimarySet = false;

            // Loop through uploaded files
            for ($i = 0; $i < count($files['name']); $i++) {

                // Skip files that had upload errors
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }

                // Validate file type and generate a unique filename
                $tmpName = $files['tmp_name'][$i];
                $origName = $files['name'][$i];

                // Use finfo to determine the MIME type of the uploaded file
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                // Allowed MIME types and their corresponding file extensions
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

                // Skip files that are not allowed
                if (!isset($allowed[$mime])) {
                    continue;
                }

                // Generate a unique filename to avoid overwriting existing files
                $ext = $allowed[$mime];
                $safeBase = preg_replace('/[^a-z0-9\-]/i', '-', pathinfo($origName, PATHINFO_FILENAME)); // Remove unsafe characters from the original filename specifically for the base name 
                $unique = sprintf('%s_%s.%s', substr($safeBase, 0, 50), bin2hex(random_bytes(6)), $ext); // Limit base name to 50 chars for safety

                // Ensure the upload directory exists
                $uploadDir = __DIR__ . '/../images/product_images';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Move the uploaded file to the destination directory
                $destination = $uploadDir . '/' . $unique;
                if (!move_uploaded_file($tmpName, $destination)) {
                    continue;
                }

                $publicPath = 'images/product_images/' . $unique;

                // Determine if this image should be set as primary
                if (!$isPrimarySet) {
                    // unset existing primary
                    $unsetStmt = $connection->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id');
                    $unsetStmt->execute(['product_id' => $productId]);
                    $isPrimary = 1;
                    $isPrimarySet = true;
                } else {
                    $isPrimary = 0;
                }

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
        $_SESSION['inventory_message'] = 'Product updated successfully.';
    } catch (Throwable $e) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        $_SESSION['inventory_message'] = 'Error updating product: ' . ($e->getMessage() ?: 'unknown error');
    }

    header('Location: inventory_management.php');
    exit;
}

// Fetch all products from the database to display in the inventory management table
$sql = "
    SELECT p.product_id, p.product_name, p.description, p.base_price, p.stock, p.is_active, p.category_id, c.category_name
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
    <meta name="description" content="Manage your product inventory">
    <meta name="keywords" content="adminpage, olivetree, inventory, products">
    <title>Inventory Management | Olive Tree Soap Co.</title>
    <link rel="stylesheet" href="../css/default_style.css">
    <script src="../js/theme-switcher.js" defer></script>
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
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
        <div class="admin-panel-topbar">
            <h1>Inventory Management</h1>
            <a class="site-link" href="../helpPages/inventory_management_help.html">Get Help</a>
        </div>

        <!-- Error and regular message display -->
        <?php if (!empty($message)): ?>
            <div class="cart-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="user-management inventory-management">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Display each product in a table row with editable fields and an update form -->
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= (int) $p['product_id'] ?></td>
                            <td>
                                <input type="text" name="product_name"
                                    form="inventory-update-<?= (int) $p['product_id'] ?>"
                                    value="<?= htmlspecialchars($p['product_name']) ?>" required>
                            </td>
                            <td>
                                <textarea name="description" rows="2"
                                    form="inventory-update-<?= (int) $p['product_id'] ?>"
                                    style="width:100%;"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
                            </td>
                            <td>
                                <select name="category_id" form="inventory-update-<?= (int) $p['product_id'] ?>" required>
                                    <option value="">--</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= (int) $cat['category_id'] ?>"
                                            <?= ((int) $p['category_id'] === (int) $cat['category_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="base_price"
                                    form="inventory-update-<?= (int) $p['product_id'] ?>"
                                    value="<?= number_format((float) $p['base_price'], 2, '.', '') ?>" required>
                            </td>
                            <td>
                                <input type="number" name="stock" min="0"
                                    form="inventory-update-<?= (int) $p['product_id'] ?>"
                                    value="<?= (int) $p['stock'] ?>" required>
                            </td>
                            <td>
                                <select name="is_active" form="inventory-update-<?= (int) $p['product_id'] ?>">
                                    <option value="1" <?= ((int) $p['is_active'] === 1) ? 'selected' : '' ?>>Yes</option>
                                    <option value="0" <?= ((int) $p['is_active'] === 0) ? 'selected' : '' ?>>No</option>
                                </select>
                            </td>
                            <td>
                                <!-- Form for updating product details and uploading images -->
                                <form method="post" action="inventory_management.php" class="inline-form"
                                    enctype="multipart/form-data" id="inventory-update-<?= (int) $p['product_id'] ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?= (int) $p['product_id'] ?>">
                                    <!-- Add file input for image upload -->
                                <div style="margin-bottom:6px;">
                                    <label style="display:block;font-size:0.9em;margin-bottom:4px;">Upload images
                                        (optional)</label>
                                        
                                    <input type="file" name="images[]" accept="image/*" multiple
                                        form="inventory-update-<?= (int) $p['product_id'] ?>">
                                    <p style="font-size:0.85em;color:#666;margin:4px 0 0;">First uploaded image becomes
                                        primary.</p>
                                </div>
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