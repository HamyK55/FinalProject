<?php
session_start();

// Require admin
if (($_SESSION["user_role"] ?? "") !== "admin") {
    header("Location: ../login.php");
    exit;
}

$adminName = $_SESSION["user_name"] ?? "Admin";

require_once __DIR__ . "/../database/db.php";

/*
 * User management actions
 * - disable: sets is_active = 0
 * - promote: sets role = 'admin'
 * - reset: sets password_hash to the hash of '111111'
 * Messages are stored in session and displayed after a redirect.
 */
$message = $_SESSION["user_management_message"] ?? null;
unset($_SESSION["user_management_message"]);

if ($_SERVER["REQUEST_METHOD"] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if ($userId === false || $userId === null) {
        $_SESSION["user_management_message"] = "Invalid user selected.";
        header("Location: user_management.php");
        exit;
    }

    // Prevent admin from modifying their own account for dangerous actions
    $currentAdminId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId === $currentAdminId && in_array($action, ['disable', 'promote', 'set_role'])) {
        $_SESSION["user_management_message"] = "You cannot perform that action on your own account.";
        header("Location: user_management.php");
        exit;
    }

    try {
        if ($action === 'disable') {
            $stmt = $connection->prepare("UPDATE users SET is_active = 0 WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            $_SESSION["user_management_message"] = "The user account was disabled.";
        } elseif ($action === 'promote') {
            $stmt = $connection->prepare("UPDATE users SET role = 'admin' WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            $_SESSION["user_management_message"] = "The user was promoted to admin.";
        } elseif ($action === 'set_role') {
            $newRole = $_POST['role'] ?? '';
            $allowed = ['customer', 'admin'];
            if (!in_array($newRole, $allowed, true)) {
                $_SESSION["user_management_message"] = "Invalid role specified.";
            } else {
                $stmt = $connection->prepare("UPDATE users SET role = :role WHERE user_id = :user_id");
                $stmt->execute(['role' => $newRole, 'user_id' => $userId]);
                $_SESSION["user_management_message"] = "The user's role was changed to {$newRole}.";
            }
        } elseif ($action === 'reset') {
            // Set temp password and store its hash
            $tempPassword = '111111';
            
            $newHash = password_hash($tempPassword, PASSWORD_DEFAULT);
            $stmt = $connection->prepare("UPDATE users SET password_hash = :hash WHERE user_id = :user_id");
            $stmt->execute(['hash' => $newHash, 'user_id' => $userId]);
            $_SESSION["user_management_message"] = "The user's password was reset. Temporary password: {$tempPassword}";
        } elseif ($action === 'reinstate') {
            $stmt = $connection->prepare("UPDATE users SET is_active = 1 WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            $_SESSION["user_management_message"] = "The user account was reinstated.";
        } else {
            $_SESSION["user_management_message"] = "Unknown action.";
        }
    } catch (PDOException $e) {
        $_SESSION["user_management_message"] = "An error occurred while updating the user.";
    }

    header("Location: user_management.php");
    exit;
}

// Fetch users for display
$usersStmt = $connection->query("SELECT user_id, first_name, last_name, email, role, is_active, created_at FROM users ORDER BY created_at DESC");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage user accounts and their permissions">
	<meta name="keywords" content="adminpage, olivetree, user management">
    <title>User Management | Olive Tree Soap Co.</title>
    <link rel="stylesheet" href="../css/default_style.css">
    <script src="../js/theme-switcher.js" defer></script>
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
</head>

<body class="admin-page">

    <header class="site-header">
        <span class="site-brand">Olive Tree Soap Co.</span>

        <div class="site-links">
            <a class="site-link" href="dashboard.php">Admin Dashboard</a>
            <a class="site-link" href="../helpPages/help_center.html">Help Center</a>
            <a class="site-link" href="../user-pages/logout.php">Logout</a>
            
            <span class="site-user-note">Hi, <?= htmlspecialchars($adminName) ?></span>
        </div>
    </header>

    <main class="admin-panel">
        <div class="admin-panel-topbar">
            <h1>User Management</h1>
            <a class="site-link" href="../helpPages/user_management_help.html">Get Help</a>
        </div>

        <!-- Status message area (set after actions like disable/promote/reset) -->
        <?php if (!empty($message)): ?>
            <div class="cart-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Users table: list all users with action buttons -->
        <section class="user-management">
            <table style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align:left; border-bottom: 1px solid #ddd;">
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="width:240px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr style="border-bottom: 1px solid #f0efe9;">
                            <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td><?= ((int)$user['is_active'] === 1) ? 'Active' : 'Disabled' ?></td>
                            <td><?= htmlspecialchars(date('M j, Y', strtotime($user['created_at'] ?? 'now'))) ?></td>
                            <td>
                                <div class="actions">
                                    <!-- Disable / Reinstate -->
                                    <?php if ((int)$user['is_active'] === 1): ?>
                                        <form action="user_management.php" method="post" onsubmit="return confirm('Disable this user account?');">
                                            <input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>">
                                            <input type="hidden" name="action" value="disable">
                                            <button class="btn btn-danger" type="submit">Disable</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="user_management.php" method="post" onsubmit="return confirm('Reinstate this user account?');">
                                            <input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>">
                                            <input type="hidden" name="action" value="reinstate">
                                            <button class="btn btn-neutral" type="submit">Reinstate</button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Role selector: change a user's role via dropdown (labelled) -->
                                    <form action="user_management.php" method="post">
                                        <input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>">
                                        <input type="hidden" name="action" value="set_role">
                                        <?php $selectId = 'role_select_' . (int)$user['user_id']; ?>
                                        <label for="<?= $selectId ?>">Role</label>
                                        <select id="<?= $selectId ?>" name="role" onchange="if(confirm('Change role to ' + this.value + '?')) this.form.submit();" <?= ((int)$user['user_id'] === (int)($_SESSION['user_id'] ?? 0)) ? 'disabled' : '' ?> >
                                            <option value="customer" <?= ($user['role'] === 'customer') ? 'selected' : '' ?>>Customer</option>
                                            <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                    </form>

                                    <!-- Reset password to a temporary random value and show it to admin -->
                                    <form action="user_management.php" method="post" onsubmit="return confirm('Reset this user\'s password to a temporary value?');">
                                        <input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>">
                                        <input type="hidden" name="action" value="reset">
                                        <button class="btn btn-neutral" type="submit">Reset PW</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

</body>

</html>