<?php
// db_connect.php
$servername = "localhost"; // Your database host
$username = "root";       // Your database username (often 'root' for local development)
$password = "";           // Your database password (empty for 'root' on XAMPP/WAMP default)
$dbname = "helpdesk_db"; // Your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Set character set to UTF-8
$conn->set_charset("utf8mb4");
