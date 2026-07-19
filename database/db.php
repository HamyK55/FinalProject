<?php

$host = "localhost";
$dbname = "finalproject"; 
$username = "root";
$password = "";              // Usually blank in a default XAMPP setup


try {
    $connection = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $error) { 
    die("Database connection failed: " . $error->getMessage());
}