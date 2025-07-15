<?php
// db_connect.php
$host = "localhost";
$dbname = "helpdesk_db";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Test connection (remove in production)
    // $pdo->query("SELECT 1")->fetch();
    // error_log("Database connection successful");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}


