<?php
// index.php
session_start(); // Start the session

// Include necessary files (e.g., db_connect, functions)
// require_once 'db_connect.php'; // Not strictly needed on index.php if not querying DB for unauthenticated users

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

// *** IMPORTANT LOGIC HERE ***
// If user is ALREADY logged in, redirect them to their respective portal
if (isset($_SESSION['user_id'])) {
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
        // If an unknown role, redirect to a default safe page or logout
        default:
            redirect('logout.php'); // Or some default dashboard
            break;
    }
}

// If the user is NOT logged in, they will see the landing page below.
// No specific user data ($currentUser, $currentRole) is needed for this landing page.
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Welcome to ESSA Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            background: linear-gradient(120deg, #e0f7fa, #e3f2fd);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
            /* Prevents scrollbars if content slightly overflows */
        }

        .top-nav {
            position: absolute;
            top: 30px;
            right: 50px;
            z-index: 999;
        }

        .top-nav a {
            margin-left: 15px;
            padding: 14px 28px;
            font-weight: 700;
            text-decoration: none;
            border-radius: 30px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .btn-login {
            background-color: #0d6efd;
            color: white;
        }

        .btn-login:hover {
            background-color: #084cd3;
        }

        /* Signup button is not directly used on this index.php, but keeping style in case you add it back */
        .btn-signup {
            border: 2px solid #0d6efd;
            color: #0d6efd;
            background: transparent;
        }

        .btn-signup:hover {
            background-color: #0d6efd;
            color: white;
        }

        .welcome-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            padding: 40px;
        }

        .logo {
            width: 180px;
            height: auto;
            margin-bottom: 35px;
            /* CSS to remove white background - ensure your ESSA.png has transparency for best results */
            /* If it's a white background, these might not make it truly transparent. */
            /* You might need an actual transparent PNG. */
            filter: brightness(1.1) drop-shadow(0 0 0 transparent);
            mix-blend-mode: multiply;
        }

        h1 {
            font-size: 4rem;
            font-weight: 900;
            color: #1d4ed8;
            margin-bottom: 20px;
        }

        h3 {
            font-size: 2.5rem;
            color: #2563eb;
            margin-bottom: 20px;
            font-weight: 600;
        }

        p {
            font-size: 1.5rem;
            color: #374151;
            max-width: 750px;
            margin-bottom: 20px;
            line-height: 1.8;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2.5rem;
            }

            h3 {
                font-size: 1.6rem;
            }

            p {
                font-size: 1.1rem;
            }

            .logo {
                width: 130px;
            }

            .top-nav {
                right: 20px;
                top: 20px;
            }

            .top-nav a {
                font-size: 1rem;
                padding: 10px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="top-nav">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="login.php" class="btn btn-login">Login</a>
        <?php else: ?>
            <a href="<?php
                        // Redirect to specific portal if already logged in. This redundancy helps if direct access to index.php occurs.
                        switch ($_SESSION['role'] ?? ''):
                            case 'employee':
                                echo 'employee.php';
                                break;
                            case 'ithead':
                                echo 'ithead.php';
                                break;
                            case 'specialist':
                                echo 'specialist.php';
                                break;
                            default:
                                echo 'logout.php';
                                break; // Fallback or generic dashboard
                        endswitch;
                        ?>" class="btn btn-login">Go to Dashboard</a>
            <a href="logout.php" class="btn btn-signup">Logout</a>
        <?php endif; ?>
    </div>

    <?php displayMessage(); ?>

    <div class="welcome-section">
        <img src="assets/images/ESSA.png" alt="ESSA Logo" class="logo" />
        <h1>Welcome to ESSA Helpdesk</h1>
        <h3>Ethiopian Statistical Service</h3>
        <p>Your reliable, fast, and modern IT Help Request Tracking System. Built to simplify your support journey and make your work easier.</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>