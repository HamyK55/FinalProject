<?php

session_start();

require_once "../database/db.php";

/*
 * Register page responsibilities
 * ----------------------------
 * - Prevent access for already-authenticated users by redirecting them to their landing page.
 * - On POST, validate the submitted sign-up fields, ensure passwords match, and check for
 *   existing users with the same email.
 * - Create a new user record with a hashed password, sign them in by populating session values,
 *   and redirect to their order history.
 * - Provide generic user-facing messages for validation failures to avoid leaking sensitive info.
 */


// Redirect already-signed-in users to the appropriate page
if (isset($_SESSION["user_id"])) {
    if (($_SESSION["user_role"] ?? "") === "admin") {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: order_history.php");
    }
    exit;
}

$message = "";
$firstName = "";
$lastName = "";
$email = "";

// Handle form submission: validate, check duplicate email, create user, and sign in
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    // Basic validation: ensure required fields are present
    if ($firstName === "" || $lastName === "" || $email === "" || $password === "") {
        $message = "Please fill in all of the sign up details.";
    } elseif ($password !== $confirmPassword) {
        // Password confirmation mismatch
        $message = "The passwords do not match.";
    } else {
        // Check whether the email is already registered
        $emailCheckSql = "
            SELECT user_id
            FROM users
            WHERE email = :email
            LIMIT 1
        ";

        $emailCheckStatement = $connection->prepare($emailCheckSql);
        $emailCheckStatement->execute(["email" => $email]);

        $existingUser = $emailCheckStatement->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            // Do not allow duplicate registrations for the same email
            $message = "That email is already being used.";
        } else {
            // Hash the password and create the new user record
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $registerSql = "
                INSERT INTO users (
                    first_name,
                    last_name,
                    email,
                    password_hash,
                    role,
                    is_active
                ) VALUES (
                    :first_name,
                    :last_name,
                    :email,
                    :password_hash,
                    'customer',
                    1
                )
            ";

            $registerStatement = $connection->prepare($registerSql);
            $registerStatement->execute([
                "first_name" => $firstName,
                "last_name" => $lastName,
                "email" => $email,
                "password_hash" => $passwordHash
            ]);

            // Sign the user in by storing values in session and redirecting
            $_SESSION["user_id"] = (int) $connection->lastInsertId();
            $_SESSION["user_name"] = trim($firstName . " " . $lastName);
            $_SESSION["user_email"] = $email;
            $_SESSION["user_role"] = "customer";

            header("Location: order_history.php");
            exit;
        }
    }
}

$loggedInUserName = $_SESSION["user_name"] ?? null;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Olive Tree Soap Co.</title>
    <link rel="stylesheet" href="../css/default_style.css">
    <script src="../js/theme-switcher.js" defer></script>
</head>

<body>

    <header class="site-header">
        <a class="site-link" href="../index.php">Olive Tree Soap Co.</a>
        <div class="site-links">
            <a class="site-cart-link" href="cart.php">View Cart</a>
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
        <h1>Register</h1>

        <!-- Display messages from validation or duplicate-email checks -->
        <?php if ($message): ?>
            <div class="cart-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Registration form: collects user details and posts back to this page -->
        <form class="product-form" action="register.php" method="post">
            <div class="option-group">
                <label for="first_name">First Name</label>
                <input id="first_name" type="text" name="first_name" value="<?= htmlspecialchars($firstName) ?>" required>
            </div>

            <div class="option-group">
                <label for="last_name">Last Name</label>
                <input id="last_name" type="text" name="last_name" value="<?= htmlspecialchars($lastName) ?>" required>
            </div>

            <div class="option-group">
                <label for="email">Email Address</label>
                <input id="email" type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>

            <div class="option-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>
            </div>

            <div class="option-group">
                <label for="confirm_password">Confirm Password</label>
                <input id="confirm_password" type="password" name="confirm_password" required>
            </div>

            <button class="add-to-cart" type="submit">Create Account</button>
        </form>

        <p>Already have an account? <a class="site-link" href="login.php">Login here</a></p>
    </main>

</body>

</html>