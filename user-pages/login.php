<?php

session_start();

require_once "../database/db.php";

/*
 * Login page responsibilities
 * ---------------------------
 * - If a user is already authenticated, redirect them to the appropriate page (admin dashboard or order history).
 * - On POST, validate submitted credentials, verify the password hash, and populate session values on success.
 * - On failure, show a generic message (do not reveal which field was wrong).
 */

// If already logged in, send the user to the appropriate landing page
if (isset($_SESSION["user_id"])) {
    if (($_SESSION["user_role"] ?? "") === "admin") {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: order_history.php");
    }
    exit;
}

$message = "";
$email = "";

// Authenticate submitted credentials on POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    // Basic presence validation for email and password
    if ($email === "" || $password === "") {
        $message = "Please enter your email and password.";
    } else {
        // Fetch the user record for the provided email
        $userSql = "
            SELECT
                user_id,
                first_name,
                last_name,
                email,
                password_hash,
                role,
                is_active
            FROM users
            WHERE email = :email
            LIMIT 1
        ";

        $userStatement = $connection->prepare($userSql);
        $userStatement->execute(["email" => $email]);

        $user = $userStatement->fetch(PDO::FETCH_ASSOC);

        // Verify the account is active and the password matches the stored hash
        if ($user && (int) $user["is_active"] === 1 && password_verify($password, $user["password_hash"])) {
            // Store minimal user info in session and redirect based on role
            $_SESSION["user_id"] = (int) $user["user_id"];
            $_SESSION["user_name"] = trim($user["first_name"] . " " . $user["last_name"]);
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["user_role"] = $user["role"];

            if ($user["role"] === "admin") {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: order_history.php");
            }
            exit;
        }

        // Generic error message - avoid leaking whether the email or password was incorrect
        $message = "The email or password was not right.";
    }
}

$loggedInUserName = $_SESSION["user_name"] ?? null;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login page for the olivetree">
	<meta name="keywords" content="lipbalm, olivetree, soap, skincare, login, user-signin">
    <title>Login | Olive Tree Soap Co.</title>
    <link rel="stylesheet" href="../css/default_style.css">
    <script src="../js/theme-switcher.js" defer></script>
</head>

<body>

    <header class="site-header">
        <a class="site-link" href="../index.php">Olive Tree Soap Co.</a>
        <div class="site-links">
            <a class="site-cart-link" href="cart.php">View Cart</a>
            <!-- Display different navigation links based on user role -->
            <?php if ($loggedInUserName): ?>
                <a class="site-link" href="order_history.php">Order History</a>
                <a class="site-link" href="logout.php">Logout</a>
                <span class="site-user-note">Hi, <?= htmlspecialchars($loggedInUserName) ?></span>

            <?php else: ?>
                <a class="site-link" href="login.php">Login</a>
                <a class="site-link" href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="product-details">
        <h1>Login</h1>

        <!-- Display any validation or authentication messages to the user -->
        <?php if ($message): ?>
            <div class="cart-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Login form: posts email and password back to this page for authentication -->
        <form class="product-form" action="login.php" method="post">
            <div class="option-group">
                <label for="email">Email Address</label>
                <input id="email" type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>

            <div class="option-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>
            </div>

            <button class="add-to-cart" type="submit">Login</button>
        </form>

        <p>Don't have an account? <a class="site-link" href="register.php">Register here</a></p>
    </main>

</body>

</html>