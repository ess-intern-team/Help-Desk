<?php
session_start();

// 1. First include the database connection with error handling
try {
    require 'db_connect.php';

    // Verify connection was successful
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Check user session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // for testing - ensure this ID exists in DB
    $_SESSION['name'] = 'Seid Hussen';
    $_SESSION['role'] = 'user';
}

$userId = $_SESSION['user_id'];
$error = "";
$success = "";
$redirectAfterSuccess = false;

// Fetch user info
try {
    $sql = "SELECT first_name, last_name, email, profile_photo, password_hash FROM users WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}


// Handle profile update
if (isset($_POST['update_profile'])) {
    $first = trim($_POST['first_name']);
    $last = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $profile_photo = $user['profile_photo'];

    if (!$first || !$last || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❗ Please enter valid first name, last name, and email.";
    } else {
        // Handle photo upload
        if (!empty($_FILES['profile_photo']['name'])) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif'];
            $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $type = $_FILES['profile_photo']['type'];
            $size = $_FILES['profile_photo']['size'];

            if (!in_array($type, $allowed)) {
                $error = "Only JPG, PNG, or GIF files allowed.";
            } elseif ($size > 2 * 1024 * 1024) {
                $error = "Max file size is 2MB.";
            } else {
                $newName = 'user_' . $userId . '_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/uploads/profile_photos/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $dest = $uploadDir . $newName;

                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dest)) {
                    if ($profile_photo && file_exists($uploadDir . $profile_photo)) {
                        unlink($uploadDir . $profile_photo);
                    }
                    $profile_photo = $newName;
                } else {
                    $error = "Failed to upload profile photo.";
                }
            }
        }

        if (!$error) {
            try {
                $updateSql = "UPDATE users SET first_name = :first, last_name = :last, email = :email, profile_photo = :photo WHERE id = :id";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([
                    ':first' => $first,
                    ':last' => $last,
                    ':email' => $email,
                    ':photo' => $profile_photo,
                    ':id' => $userId
                ]);

                $success = "✅ Profile updated successfully. Redirecting to dashboard...";
                $_SESSION['name'] = $first . ' ' . $last;
                $user['first_name'] = $first;
                $user['last_name'] = $last;
                $user['email'] = $email;
                $user['profile_photo'] = $profile_photo;
                $redirectAfterSuccess = true;
            } catch (PDOException $e) {
                $error = "Update failed: " . $e->getMessage();
            }
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new || !$confirm) {
        $error = "❗ Fill in all password fields.";
    } elseif (!password_verify($current, $user['password_hash'])) {
        $error = "❗ Current password incorrect.";
    } elseif ($new !== $confirm) {
        $error = "❗ New passwords do not match.";
    } elseif (strlen($new) < 6) {
        $error = "❗ Password too short.";
    } else {
        try {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $updatePwdSql = "UPDATE users SET password_hash = :pwd WHERE id = :id";
            $stmtPwd = $conn->prepare($updatePwdSql);
            $stmtPwd->execute([':pwd' => $newHash, ':id' => $userId]);
            $success = "✅ Password changed successfully. Redirecting to dashboard...";
            $redirectAfterSuccess = true;
        } catch (PDOException $e) {
            $error = "❌ Password change failed: " . $e->getMessage();
        }
    }
}

function profilePhotoUrl($filename)
{
    $path = __DIR__ . "/uploads/profile_photos/$filename";
    return ($filename && file_exists($path)) ? "uploads/profile_photos/$filename" : "https://via.placeholder.com/150?text=No+Photo";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Profile - ESSA Helpdesk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: #eef2f7;
            font-family: 'Segoe UI', sans-serif;
        }

        .container {
            max-width: 750px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .profile-photo {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #2563eb;
        }

        .btn {
            border-radius: 30px;
        }

        h2 {
            font-weight: 700;
            color: #1e3a8a;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 class="text-center mb-4">Profile Settings</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="text-center mb-3">
                <img src="<?= profilePhotoUrl($user['profile_photo']); ?>" class="profile-photo" alt="Profile Photo" />
            </div>

            <div class="mb-3">
                <label class="form-label">Change Profile Photo</label>
                <input type="file" name="profile_photo" class="form-control" accept="image/*" />
            </div>

            <div class="mb-3">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']); ?>" required />
            </div>

            <div class="mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']); ?>" required />
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" required />
            </div>

            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
        </form>

        <hr class="my-5" />

        <h4>Change Password</h4>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required />
            </div>

            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required />
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required />
            </div>

            <button type="submit" name="change_password" class="btn btn-danger">Change Password</button>
        </form>
    </div>

    <?php if ($redirectAfterSuccess): ?>
        <script>
            setTimeout(() => {
                window.location.href = 'user_dashboard.php'; // Adjust if necessary
            }, 2500);
        </script>
    <?php endif; ?>
</body>

</html>