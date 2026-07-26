<?php

session_start();

// Remove user information from session info to logout.
unset($_SESSION["user_id"]);
unset($_SESSION["user_name"]);
unset($_SESSION["user_email"]);
unset($_SESSION["user_role"]);

// Redirect the user to the homepage after logging out.
header("Location: ../index.php");
exit;