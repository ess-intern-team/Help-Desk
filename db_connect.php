<?php
// db_connect.php
$host = 'localhost'; // Your database host
$db = 'helpdesk_db'; // Your database name
$user = 'root'; // Your database username
$pass = ''; // Your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Log the error for debugging, but don't display sensitive info to users
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed. Please try again later."); // Generic error message for users
}
