<?php

$dbConfigPath = __DIR__ . "/db_config.php";

if (!file_exists($dbConfigPath)) {
    die("Database configuration file is missing.");
}

$dbConfig = require $dbConfigPath;

$host = $dbConfig["host"];
$dbname = $dbConfig["dbname"];
$username = $dbConfig["username"];
$password = $dbConfig["password"];


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