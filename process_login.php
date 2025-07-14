<?php
// process_login.php

// Start the session
session_start();

// Include database connection
require_once 'db_connection.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data and sanitize
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    $errors = [];

    if (empty($username)) {
        $errors[] = 'Username is required.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    // If no validation errors, proceed with authentication
    if (empty($errors)) {
        try {
            // Prepare SQL statement to prevent SQL injection
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            // Check if user exists
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verify password (assuming passwords are hashed)
                if (password_verify($password, $user['password'])) {
                    // Password is correct, set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in'] = true;

                    // Regenerate session ID for security
                    session_regenerate_id(true);

                    // Redirect to dashboard or home page
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $errors[] = 'Invalid username or password.';
                }
            } else {
                $errors[] = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    // If we got here, there were errors - store them in session and redirect back
    $_SESSION['login_errors'] = $errors;
    $_SESSION['login_username'] = $username; // Return the username for convenience
    header('Location: login.php');
    exit();
} else {
    // If someone tries to access this page directly, redirect them
    header('Location: login.php');
    exit();
}
