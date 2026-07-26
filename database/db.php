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
    // Establish connection to db, using PDO which is the php data object.
    $connection = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    // Set the error mode to exception, so whenever we use the connection, it will throw an error and we can handle it with try catch in each file
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $error) {
    die("Database connection failed: " . $error->getMessage());
}