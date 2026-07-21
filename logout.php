<?php

/*
 * Log the shopper out and send them back to the home page.
 */
session_start();

unset($_SESSION["user_id"]);
unset($_SESSION["user_name"]);
unset($_SESSION["user_email"]);
unset($_SESSION["user_role"]);

// send the shopper back to the home page after logging out
header("Location: index.php");
exit;