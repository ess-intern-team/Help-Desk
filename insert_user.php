<?php
require_once 'db_connect.php';

$email = "seidhussen0707@gmail.com";

// 1. Check for existing email
$check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
$check->execute([$email]);

if ($check->fetchColumn() > 0) {
    echo "⚠️ User with this email already exists!";
    exit;
}

// 2. Insert only if not found
$first_name = "Seid";
$last_name = "Hussen";
$plainPassword = "Seid2986@";
$hashed = password_hash($plainPassword, PASSWORD_DEFAULT);
$role = "user";
$company = "DBU";
$profile_photo = null;
$created_at = date("Y-m-d H:i:s");
$updated_at = $created_at;

$stmt = $pdo->prepare("SELECT id, first_name, last_name, email, `password-hash`, role FROM users WHERE email = :email LIMIT 1");



$stmt->execute([
    $first_name,
    $last_name,
    $email,
    $hashed,
    $company,
    $role,
    $profile_photo,
    $created_at,
    $updated_at
]);

echo "✅ User created successfully!";
