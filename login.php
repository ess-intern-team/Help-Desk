<?php
// login.php
session_start(); // Start the session at the very beginning

// Include the database connection
require_once 'db_connect.php';

// Helper function for redirection (same as in other files)
function redirect($page, $params = [])
{
    $url = $page;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    header("Location: " . $url);
    exit();
}

// Function to display messages (same as in other files)
function displayMessage()
{
    if (isset($_SESSION['displayMessage']) && !empty($_SESSION['displayMessage'])) {
        $message = htmlspecialchars($_SESSION['displayMessage']);
        $type = htmlspecialchars($_SESSION['messageType'] ?? 'info');
        unset($_SESSION['displayMessage']);
        unset($_SESSION['messageType']);
        echo "
        <div class='toast-container'>
            <div class='toast show align-items-center text-white bg-{$type} border-0' role='alert' aria-live='assertive' aria-atomic='true'>
                <div class='d-flex'>
                    <div class='toast-body'>
                        {$message}
                    </div>
                    <button type='button' class='btn-close btn-close-white me-2 m-auto' data-bs-dismiss='toast' aria-label='Close'></button>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var toastEl = document.querySelector('.toast');
                if (toastEl) {
                    setTimeout(function() {
                        var toast = bootstrap.Toast.getInstance(toastEl);
                        if (toast) {
                            toast.hide();
                        }
                    }, 5000); // Hide after 5 seconds
                }
            });
        </script>";
    }
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // User is already logged in, redirect to appropriate portal
    switch ($_SESSION['role']) {
        case 'employee':
            redirect('employee.php');
            break;
        case 'ithead':
            redirect('ithead.php');
            break;
        case 'specialist':
            redirect('specialist.php');
            break;
        default:
            redirect('index.php'); // Or a generic dashboard
            break;
    }
}

$conn = getDbConnection(); // Get the database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CHANGE 1: Get 'email' from the form instead of 'username'
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $password = htmlspecialchars(trim($_POST['password'] ?? ''));

    if (empty($email) || empty($password)) {
        $_SESSION['displayMessage'] = "Please enter both email and password."; // CHANGE MESSAGE
        $_SESSION['messageType'] = "danger";
        redirect('login.php');
    }

    // CHANGE 2: Query by 'email' column instead of 'username'
    // Also, fetch the 'username' if you still need it for displaying later, or remove it from SELECT if not
    $sql = "SELECT id, username, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // CHANGE 3: Bind the email variable
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verify the hashed password
            if (password_verify($password, $user['password'])) {
                // Password is correct, set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username']; // Still store username if needed
                $_SESSION['role'] = $user['role'];
                $_SESSION['displayMessage'] = "Welcome, " . htmlspecialchars($user['username']) . "!"; // Display username
                $_SESSION['messageType'] = "success";

                // Redirect based on role
                switch ($user['role']) {
                    case 'employee':
                        redirect('employee.php');
                        break;
                    case 'ithead':
                        redirect('ithead.php');
                        break;
                    case 'specialist':
                        redirect('specialist.php');
                        break;
                    default:
                        // For any other role, redirect to a general page
                        redirect('index.php');
                        break;
                }
            } else {
                $_SESSION['displayMessage'] = "Invalid email or password."; // CHANGE MESSAGE
                $_SESSION['messageType'] = "danger";
                redirect('login.php');
            }
        } else {
            $_SESSION['displayMessage'] = "Invalid email or password."; // CHANGE MESSAGE
            $_SESSION['messageType'] = "danger";
            redirect('login.php');
        }
        $stmt->close();
    } else {
        // Database query preparation failed
        $_SESSION['displayMessage'] = "A database error occurred. Please try again.";
        $_SESSION['messageType'] = "danger";
        error_log("Login prepare failed: " . $conn->error);
        redirect('login.php');
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Helpdesk System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            /* Corrected gradient syntax */
            /* Modern gradient background */
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .login-container h2 {
            color: #333;
            margin-bottom: 30px;
            font-weight: 700;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            border-color: #0d6efd;
        }

        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 10px 25px;
            border-radius: 8px;
            font-size: 1.1rem;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2 class="mb-4"><i class="bi bi-person-circle me-2"></i>Helpdesk Login</h2>
        <?php displayMessage(); ?>
        <form action="login.php" method="POST">
            <div class="mb-3">
                <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="Email Address" required>
            </div>
            <div class="mb-4">
                <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100">Login</button>
        </form>
        <p class="mt-4 text-muted">
            <small>Don't have an account? Contact Admin.</small>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>