<?php
session_start();
require_once 'db_connect.php';

// ✅ Get email and password from the login form
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header("Location: login.php?error=Missing email or password");
    exit();
}

// ✅ Query user by email
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ If user exists and password is correct
if ($user && password_verify($password, $user['password-hash'])) {
    // ✅ Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['department'] = $user['company'];
    $_SESSION['logged_in'] = true;

    // ✅ Redirect based on role
    if ($user['role'] === 'department_manager') {
        header("Location: department_manager.php");
    } elseif ($user['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: user_dashboard.php");
    }

    exit();
} else {
    // ❌ Login failed
    header("Location: login.php?error=Invalid email or password");
    exit();
}
