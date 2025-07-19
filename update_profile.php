<?php
session_start();

// Simulate updating in DB
$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password']; // You will hash this in real DB
$photo = $_FILES['photo'];

$uploadDir = 'uploads/';
$photoName = 'default.png'; // fallback photo

if ($photo['name'] != '') {
    $photoName = time() . '_' . basename($photo['name']);
    move_uploaded_file($photo['tmp_name'], $uploadDir . $photoName);
}

// Simulated success (in real use: save into database)
$_SESSION['user'] = [
    'name' => $name,
    'email' => $email,
    'photo' => $photoName
];

// ✅ Redirect back to profile with success message
echo "<script>alert('Profile updated successfully!'); window.location.href='profile.php';</script>";
