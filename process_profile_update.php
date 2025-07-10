<?php
error_reporting(E_ALL); // TEMPORARY: For debugging PHP errors
ini_set('display_errors', 1); // TEMPORARY: For debugging PHP errors

session_start(); // Start the session to access session variables

require_once 'db_connect.php'; // Adjust this path if your db_connect.php is in a different location

// Check if the user is logged in. If not, return an error.
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in. Please log in again.']);
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.',
    'new_profile_photo' => null, // To send back the new photo path if updated
    'removed_photo' => false // To indicate if photo was removed
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_photo_in_db = null;
    // First, fetch the current profile photo path and the hashed password from the database
    // IMPORTANT: Use backticks for `password-hash` here!
    $stmt_select_data = $conn->prepare("SELECT profile_photo, `password-hash` FROM users WHERE id = ?");
    if (!$stmt_select_data) {
        $response['message'] = 'Error preparing select statement for photo/password: ' . $conn->error;
        echo json_encode($response);
        exit();
    }
    $stmt_select_data->bind_param("i", $user_id);
    $stmt_select_data->execute();
    $stmt_select_data->bind_result($current_photo_in_db, $current_hashed_password);
    $stmt_select_data->fetch();
    $stmt_select_data->close();


    // --- 1. Handle Profile Photo Upload or Removal ---
    $profile_photo_path_for_db = $current_photo_in_db; // Keep current path by default

    // Case 1: A new photo is uploaded
    if (isset($_FILES['profile-photo']) && $_FILES['profile-photo']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/profile_photos/"; // Directory where photos will be stored
        // Create directory if it doesn't exist
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['profile-photo']['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid() . '.' . $file_extension; // Generate unique filename
        $target_file = $target_dir . $unique_filename;

        // Validate file type
        $image_mimetypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES['profile-photo']['type'], $image_mimetypes)) {
            $response['message'] = 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.';
            echo json_encode($response);
            exit();
        }

        // Validate file size (e.g., max 5MB)
        if ($_FILES['profile-photo']['size'] > 5 * 1024 * 1024) { // 5 MB
            $response['message'] = 'File size exceeds the maximum limit (5MB).';
            echo json_encode($response);
            exit();
        }

        if (move_uploaded_file($_FILES['profile-photo']['tmp_name'], $target_file)) {
            // Delete old photo if it exists and is not a default UI-Avatar URL
            if ($current_photo_in_db && file_exists($current_photo_in_db) && strpos($current_photo_in_db, 'ui-avatars.com') === false) {
                unlink($current_photo_in_db);
            }
            $profile_photo_path_for_db = $target_file; // Update to new path
            $response['new_profile_photo'] = $target_file; // Send back new path for client-side update
        } else {
            $response['message'] = 'Error uploading profile photo.';
            echo json_encode($response);
            exit();
        }
    }
    // Case 2: Photo removal is explicitly requested and no new photo uploaded
    else if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === 'true') {
        if ($current_photo_in_db && file_exists($current_photo_in_db) && strpos($current_photo_in_db, 'ui-avatars.com') === false) {
            unlink($current_photo_in_db); // Delete the actual file
        }
        $profile_photo_path_for_db = null; // Set to null in DB
        $response['removed_photo'] = true; // Indicate photo was removed
    }


    // --- 2. Handle Password Change ---
    $current_password_input = $_POST['current-password'] ?? '';
    $new_password_input = $_POST['new-password'] ?? '';
    $confirm_password_input = $_POST['confirm-password'] ?? '';

    $password_hash_for_db = null; // Will store the new hashed password if updated

    if (!empty($new_password_input) || !empty($current_password_input) || !empty($confirm_password_input)) {
        // If current password is empty, but new/confirm are not, it's an error
        if (empty($current_password_input)) {
            $response['message'] = 'Please enter your current password to change or clear passwords.';
            echo json_encode($response);
            exit();
        }

        // Verify current password against the hashed password from the database
        if ($current_hashed_password && password_verify($current_password_input, $current_hashed_password)) {
            if ($new_password_input === $confirm_password_input) {
                if (strlen($new_password_input) >= 8) {
                    $password_hash_for_db = password_hash($new_password_input, PASSWORD_DEFAULT);
                } else {
                    $response['message'] = 'New password must be at least 8 characters long.';
                    echo json_encode($response);
                    exit();
                }
            } else {
                $response['message'] = 'New password and confirm password do not match.';
                echo json_encode($response);
                exit();
            }
        } else {
            $response['message'] = 'Current password is incorrect.';
            echo json_encode($response);
            exit();
        }
    }

    // --- 3. Handle Other Profile Information Updates ---
    $first_name = $_POST['first-name'] ?? '';
    $last_name = $_POST['last-name'] ?? '';
    $email = $_POST['email'] ?? '';
    $company = $_POST['company'] ?? '';
    $role = $_POST['role'] ?? '';

    // Prepare update statement dynamically
    $sql_update_parts = [];
    $params = [];
    $types = "";

    // Add general profile fields
    $sql_update_parts[] = "first_name = ?";
    $params[] = $first_name;
    $types .= "s";

    $sql_update_parts[] = "last_name = ?";
    $params[] = $last_name;
    $types .= "s";

    $sql_update_parts[] = "email = ?";
    $params[] = $email;
    $types .= "s";

    $sql_update_parts[] = "company = ?";
    $params[] = $company;
    $types .= "s";

    $sql_update_parts[] = "role = ?";
    $params[] = $role;
    $types .= "s";

    // Add profile photo path to update if it has changed (new upload or removal)
    if ($profile_photo_path_for_db !== $current_photo_in_db) {
        $sql_update_parts[] = "profile_photo = ?";
        $params[] = $profile_photo_path_for_db;
        $types .= "s";
    }

    // Add password hash to update if password was changed
    if ($password_hash_for_db !== null) {
        // IMPORTANT: Use backticks for `password-hash` here!
        $sql_update_parts[] = "`password-hash` = ?";
        $params[] = $password_hash_for_db;
        $types .= "s";
    }

    if (!empty($sql_update_parts)) {
        $sql = "UPDATE users SET " . implode(", ", $sql_update_parts) . " WHERE id = ?";
        $params[] = $user_id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // Using call_user_func_array for binding parameters
            $bind_names = array($types);
            for ($i = 0; $i < count($params); $i++) {
                $bind_name = 'bind' . $i;
                $$bind_name = &$params[$i];
                $bind_names[] = &$$bind_name;
            }
            call_user_func_array(array($stmt, 'bind_param'), $bind_names);


            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Profile updated successfully!';

                // Update session variables immediately to reflect changes
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                $_SESSION['company'] = $company;
                $_SESSION['role'] = $role;
                $_SESSION['profile_photo'] = $profile_photo_path_for_db; // Update with the new path
            } else {
                $response['message'] = 'Error executing update: ' . $stmt->error; // More specific error
            }
            $stmt->close();
        } else {
            $response['message'] = 'Error preparing update statement: ' . $conn->error;
        }
    } else {
        $response['message'] = 'No changes submitted or detected.';
        $response['success'] = true; // Still a success if no changes were needed
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
exit();
