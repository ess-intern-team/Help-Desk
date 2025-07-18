<?php
// db_connect.php
// This file is assumed to handle your database connection.

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // IMPORTANT: Use a strong password in production!
define('DB_NAME', 'helpdesks_db'); // Make sure this matches your database name

/**
 * Establishes and returns a database connection.
 * @return mysqli The database connection object.
 * @throws Exception If the database connection fails.
 */
function getDbConnection()
{
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            // Log the error for debugging, but don't show sensitive info to user
            error_log("Database connection failed: " . $conn->connect_error);
            // In a real application, you might redirect to an error page or show a more generic message.
            throw new Exception("Database connection failed. Please try again later.");
        }
        return $conn;
    } catch (Exception $e) {
        // For production, you might die with a generic message or redirect
        die("<div class='alert alert-danger'>A database error occurred: " . htmlspecialchars($e->getMessage()) . "</div>");
    }
}
