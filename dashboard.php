<?php
session_start();

// Include RequestProcessor
require_once 'RequestProcessor.php';

// Initialize RequestProcessor
$processor = new RequestProcessor();

// Process incoming requests
$processor->process();

// Helper function to format dates
function formatDateTime($dateTimeString)
{
    if (empty($dateTimeString)) {
        return 'N/A';
    }
    try {
        $dateTime = new DateTime($dateTimeString);
        return $dateTime->format('M d, Y, h:i a');
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}

// Initialize variables
$error = null;
$success = null;
$showLoginForm = false;
$selectedDepartment = null;

// Database connection (assumed to be in RequestProcessor.php or db_connect.php)
require_once 'db_connect.php';

// Fetch all departments
$departments = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching departments: " . $e->getMessage());
    $error = "Error loading departments.";
}

// Handle department button click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_department'])) {
    $showLoginForm = true;
    $selectedDepartment = $_POST['department_id'];
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $departmentId = $_POST['department_id'];

    try {
        $stmt = $pdo->prepare("SELECT id, name, password, role, department_id, profile_photo FROM users WHERE email = ? AND role = 'manager'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Verify department manager status
            $managerCheck = $pdo->prepare("SELECT department_id FROM department_managers WHERE user_id = ? AND department_id = ?");
            $managerCheck->execute([$user['id'], $departmentId]);
            if ($managerCheck->rowCount() > 0) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department_id'] = $user['department_id'];
                $_SESSION['profile_photo'] = $user['profile_photo'];
                header("Location: department-manager.php");
                exit();
            } else {
                $error = "You are not authorized as the manager for this department.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "An error occurred during login. Please try again.";
    }
}

// If logged in, proceed with manager dashboard
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'manager') {
    $userId = $_SESSION['user_id'];
    $departmentId = $_SESSION['department_id'] ?? null;

    // Verify manager status
    $isManager = false;
    try {
        $managerCheck = $pdo->prepare("SELECT id FROM department_managers WHERE user_id = ? AND department_id = ?");
        $managerCheck->execute([$userId, $departmentId]);
        $isManager = $managerCheck->rowCount() > 0;
        if (!$isManager) {
            session_destroy();
            header("Location: department-manager.php?error=Unauthorized+access");
            exit();
        }
    } catch (Exception $e) {
        error_log("Error checking manager status: " . $e->getMessage());
        $error = "Error verifying manager status.";
    }

    // Fetch data using RequestProcessor
    $pendingRequests = $processor->getPendingRequests();
    $itSpecialists = $processor->getITSpecialists();
    $forwardedRequests = $processor->getForwardedRequests();
    $resolvedCountLastWeek = $processor->countResolvedRequestsLastWeek();
    $allTickets = $processor->getAllTickets();

    // Filter requests by department
    $pendingRequests = array_filter($pendingRequests, function ($request) use ($departmentId) {
        return isset($request['department_id']) && $request['department_id'] == $departmentId;
    });
    $forwardedRequests = array_filter($forwardedRequests, function ($request) use ($departmentId) {
        return isset($request['department_id']) && $request['department_id'] == $departmentId;
    });
    $allTickets = array_filter($allTickets, function ($ticket) use ($departmentId) {
        return isset($ticket['department_id']) && $ticket['department_id'] == $departmentId;
    });

    // Handle request assignment
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_request'])) {
        $requestId = $_POST['request_id'];
        $assignedTo = $_POST['assigned_to'];
        $createdAt = date('Y-m-d H:i:s');

        try {
            $pdo->beginTransaction();

            // Update request
            $assignStmt = $pdo->prepare("UPDATE help_requests SET assigned_to = ?, status = 'Assigned' WHERE id = ? AND department_id = ?");
            $assignStmt->execute([$assignedTo, $requestId, $departmentId]);

            // Notify specialist
            $requestStmt = $pdo->prepare("SELECT title FROM help_requests WHERE id = ?");
            $requestStmt->execute([$requestId]);
            $requestTitle = $requestStmt->fetchColumn();

            $managerName = $_SESSION['name'];
            $message = "New assignment from $managerName: " . substr($requestTitle, 0, 30) . "...";
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, request_id, message, created_at) VALUES (?, ?, ?, ?)");
            $notifStmt->execute([$assignedTo, $requestId, $message, $createdAt]);

            // Notify requester
            $userStmt = $pdo->prepare("SELECT user_id FROM help_requests WHERE id = ?");
            $userStmt->execute([$requestId]);
            $requesterId = $userStmt->fetchColumn();
            $userMessage = "Your request '$requestTitle' has been assigned to an IT specialist.";
            $userNotifStmt = $pdo->prepare("INSERT INTO notifications (user_id, request_id, message, created_at) VALUES (?, ?, ?, ?)");
            $userNotifStmt->execute([$requesterId, $requestId, $userMessage, $createdAt]);

            $pdo->commit();
            $success = "Request assigned successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Assignment error: " . $e->getMessage());
            $error = "Failed to assign request.";
        }
    }

    // Handle forwarding specialist response
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forward_response'])) {
        $requestId = $_POST['request_id'];
        $createdAt = date('Y-m-d H:i:s');

        try {
            $pdo->beginTransaction();

            // Fetch request details
            $requestStmt = $pdo->prepare("SELECT user_id, title, specialist_response FROM help_requests WHERE id = ? AND department_id = ?");
            $requestStmt->execute([$requestId, $departmentId]);
            $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

            if ($request && $request['specialist_response']) {
                // Mark response as forwarded
                $updateStmt = $pdo->prepare("UPDATE help_requests SET response_forwarded = 1, status = 'Resolved' WHERE id = ?");
                $updateStmt->execute([$requestId]);

                // Notify requester
                $message = "Response to your request '" . substr($request['title'], 0, 30) . "...': " . substr($request['specialist_response'], 0, 50) . "...";
                $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, request_id, message, created_at) VALUES (?, ?, ?, ?)");
                $notifStmt->execute([$request['user_id'], $requestId, $message, $createdAt]);

                $pdo->commit();
                $success = "Response forwarded successfully.";
            } else {
                $pdo->rollBack();
                $error = "No response available to forward.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Forwarding error: " . $e->getMessage());
            $error = "Failed to forward response.";
        }
    }

    // Fetch user's profile photo
    $userPhoto = 'assets/images/default-profile.png';
    try {
        $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $photo = $stmt->fetchColumn();
        if ($photo && file_exists("uploads/profile_photos/" . $photo)) {
            $userPhoto = "uploads/profile_photos/" . $photo;
        }
    } catch (PDOException $e) {
        error_log("Error fetching user photo: " . $e->getMessage());
    }

    // Get department name
    $departmentName = 'Unknown Department';
    if ($departmentId) {
        try {
            $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
            $stmt->execute([$departmentId]);
            $departmentName = $stmt->fetchColumn() ?? $departmentName;
        } catch (PDOException $e) {
            error_log("Error fetching department: " . $e->getMessage());
        }
    }

    // Count unread notifications
    $unreadCount = 0;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $unreadCount = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error counting notifications: " . $e->getMessage());
    }

    // Get recent notifications
    $notifications = [];
    try {
        $stmt = $pdo->prepare("
            SELECT id, message, created_at, is_read 
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching notifications: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Helpdesk - Department Manager Dashboard</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #3f37c9;
            --primary-dark: #2a2a8e;
            --secondary: #5a51e3;
            --accent: #ffc107;
            --light: #f0f2f5;
            --dark: #212529;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--light) 0%, #e9ecef 100%);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
            padding-top: 20px;
            color: white;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: #e0e0e0;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: var(--secondary);
            color: white;
            border-left: 5px solid var(--accent);
        }

        .sidebar .nav-link i {
            margin-right: 12px;
            font-size: 1.2em;
        }

        .content {
            margin-left: 250px;
            padding: 2rem;
            flex: 1;
        }

        .department-buttons {
            max-width: 800px;
            margin: 2rem auto;
            text-align: center;
        }

        .department-button {
            margin: 0.5rem;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 500;
            border-radius: 10px;
            background-color: var(--primary);
            color: white;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .department-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            background-color: var(--secondary);
        }

        .login-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .metric-box {
            background: linear-gradient(135deg, #e0f2f7, #b3e5fc);
            border: none;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            font-size: 1.2em;
            color: #01579b;
        }

        .metric-box h3 {
            margin-top: 0;
            color: #004d87;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            color: white;
        }

        .status-new {
            background-color: #007bff;
        }

        .status-assigned {
            background-color: #ffc107;
        }

        .status-in-progress {
            background-color: #ff9800;
        }

        .status-resolved {
            background-color: #28a745;
        }

        .priority-high {
            color: var(--danger);
            font-weight: bold;
        }

        .priority-medium {
            color: var(--warning);
            font-weight: bold;
        }

        .priority-low {
            color: var(--success);
            font-weight: bold;
        }

        .priority-urgent {
            color: var(--danger);
            font-weight: bold;
            text-transform: uppercase;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .request-card {
            border-left: 5px solid;
            transition: all 0.3s ease;
        }

        .request-card:hover {
            transform: translateX(8px);
        }

        .request-urgent {
            border-left-color: var(--danger);
        }

        .request-high {
            border-left-color: var(--warning);
        }

        .request-medium {
            border-left-color: var(--info);
        }

        .request-low {
            border-left-color: var(--success);
        }

        .category-icon {
            font-size: 1.5rem;
            margin-right: 10px;
            color: var(--primary);
        }

        .response-text {
            max-height: 120px;
            overflow-y: auto;
            font-size: 0.9rem;
        }

        .btn-action {
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-action:hover {
            transform: scale(1.1);
        }

        .modal-content {
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .content {
                margin-left: 0;
                padding: 1rem;
            }

            .department-buttons {
                margin: 1rem;
            }

            .department-button {
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
            }

            .login-container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .response-text {
                max-height: 80px;
            }
        }
    </style>
</head>

<body>
    <?php if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager'): ?>
        <!-- Department Selection Section -->
        <main class="d-flex align-items-center py-5">
            <div class="department-buttons">
                <h3 class="text-center mb-4 fw-bold text-primary">IT Helpdesk - Manager Login</h3>
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_GET['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (!$showLoginForm): ?>
                    <div class="d-flex flex-wrap justify-content-center">
                        <?php foreach ($departments as $dept): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="department_id" value="<?= $dept['id'] ?>">
                                <button type="submit" name="select_department" class="btn department-button">
                                    <?= htmlspecialchars($dept['name']) ?> Head
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                    <?php if (empty($departments)): ?>
                        <p class="text-center text-muted mt-4">No departments available.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Login Form -->
                    <div class="login-container">
                        <h4 class="text-center mb-4">Login for <?= htmlspecialchars($departments[array_search($selectedDepartment, array_column($departments, 'id'))]['name']) ?> Manager</h4>
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="department_id" value="<?= htmlspecialchars($selectedDepartment) ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">Please enter your password.</div>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    <?php else: ?>
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="text-center py-4">
                <img src="<?= htmlspecialchars($userPhoto) ?>" alt="User Avatar" class="user-avatar mb-2">
                <h5><?= htmlspecialchars($_SESSION['name']) ?></h5>
                <p class="text-muted small">
                    Department Manager <br> (<?= htmlspecialchars($departmentName) ?>)
                </p>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="department-manager.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_request.php"><i class="fas fa-ticket-alt"></i> Requests</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php"><i class="fas fa-users"></i> Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">IT Helpdesk - <?= htmlspecialchars($departmentName) ?> Dashboard</h1>
                <div class="d-flex align-items-center gap-3">
                    <!-- Notification Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-link text-dark position-relative p-0" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell fs-4"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="notification-badge"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end p-0 shadow-lg" aria-labelledby="notificationDropdown" style="width: 340px;">
                            <li class="px-3 py-2 border-bottom bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">Notifications</h6>
                                    <?php if ($unreadCount > 0): ?>
                                        <small><a href="mark_notifications_read.php" class="text-primary">Mark all as read</a></small>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php if (!empty($notifications)): ?>
                                <?php foreach ($notifications as $note): ?>
                                    <li>
                                        <a class="dropdown-item notification-item <?= $note['is_read'] ? '' : 'bg-light' ?>" href="view_request.php?id=<?= $note['request_id'] ?? '' ?>">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 text-primary me-3">
                                                    <i class="fas fa-<?= $note['is_read'] ? 'envelope-open' : 'envelope' ?> fs-5"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <p class="mb-1"><?= htmlspecialchars($note['message']) ?></p>
                                                    <small class="text-muted"><?= date('M j, g:i a', strtotime($note['created_at'])) ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <li class="text-center border-top">
                                    <a class="dropdown-item text-primary fw-bold" href="notifications.php">
                                        <i class="fas fa-list me-1"></i> View All Notifications
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="text-center py-3">
                                    <p class="text-muted mb-0">No notifications found</p>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Metrics -->
            <div class="row">
                <div class="col-md-4">
                    <div class="metric-box">
                        <h3>Pending Requests</h3>
                        <p class="fs-2"><?php echo count($pendingRequests); ?></p>
                        <i class="fas fa-hourglass-half fa-3x"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-box">
                        <h3>Resolved This Week</h3>
                        <p class="fs-2"><?php echo htmlspecialchars($resolvedCountLastWeek); ?></p>
                        <i class="fas fa-check-circle fa-3x"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-box">
                        <h3>Available Specialists</h3>
                        <p class="fs-2"><?php echo count($itSpecialists); ?></p>
                        <i class="fas fa-user-tie fa-3x"></i>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_GET['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Pending Requests -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Pending Requests from <?= htmlspecialchars($departmentName) ?></h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($pendingRequests)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Priority</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingRequests as $request): ?>
                                        <tr class="request-card request-<?= strtolower($request['priority'] ?? 'low') ?>">
                                            <td>#<?= htmlspecialchars($request['id'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($request['user_name'] ?? 'N/A User') ?></td>
                                            <td>
                                                <a href="view_request.php?id=<?= $request['id'] ?>" class="text-decoration-none text-primary">
                                                    <?= htmlspecialchars($request['title'] ?? 'No Title') ?>
                                                </a>
                                            </td>
                                            <td>
                                                <i class="fas fa-<?= isset($request['category']) && in_array($request['category'], ['Networking problems', 'Software issues', 'Cybersecurity concerns', 'Hardware or maintenance needs']) ? ['Networking problems' => 'network-wired', 'Software issues' => 'laptop-code', 'Cybersecurity concerns' => 'shield-alt', 'Hardware or maintenance needs' => 'tools'][$request['category']] : 'question' ?> category-icon"></i>
                                                <?= htmlspecialchars($request['category'] ?? 'N/A') ?>
                                            </td>
                                            <td class="priority-<?= strtolower($request['priority'] ?? 'low') ?>">
                                                <?= htmlspecialchars($request['priority'] ?? 'Low') ?>
                                            </td>
                                            <td><?= formatDateTime($request['created_at'] ?? '') ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="view_request.php?id=<?= $request['id'] ?>" class="btn btn-sm btn-primary btn-action" data-bs-toggle="tooltip" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-secondary btn-action" data-bs-toggle="modal" data-bs-target="#assignModal-<?= $request['id'] ?>" data-bs-toggle="tooltip" title="Assign to Specialist">
                                                        <i class="fas fa-user-plus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Assign Specialist Modal -->
                                        <div class="modal fade" id="assignModal-<?= $request['id'] ?>" tabindex="-1" aria-labelledby="assignModalLabel-<?= $request['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="assignModalLabel-<?= $request['id'] ?>">Assign Request #<?= $request['id'] ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                            <div class="mb-3">
                                                                <label for="assigned_to-<?= $request['id'] ?>" class="form-label">Select IT Specialist</label>
                                                                <select class="form-select" id="assigned_to-<?= $request['id'] ?>" name="assigned_to" required>
                                                                    <option value="" disabled selected>Choose a specialist</option>
                                                                    <?php foreach ($itSpecialists as $specialist): ?>
                                                                        <option value="<?= $specialist['id'] ?>">
                                                                            <?= htmlspecialchars($specialist['name']) ?> (<?= htmlspecialchars($specialist['expertise'] ?? 'General') ?>)
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="assign_request" class="btn btn-primary">Assign</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted fw-bold">No Pending Requests</h5>
                            <p class="text-muted">No pending requests from your department.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Forwarded Requests -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Forwarded to IT Specialists</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($forwardedRequests)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Title</th>
                                        <th>Specialist</th>
                                        <th>Response</th>
                                        <th>Forwarded On</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($forwardedRequests as $request): ?>
                                        <tr class="request-card request-<?= strtolower($request['priority'] ?? 'low') ?>">
                                            <td>#<?= htmlspecialchars($request['id'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($request['user_name'] ?? 'N/A User') ?></td>
                                            <td>
                                                <a href="view_request.php?id=<?= $request['id'] ?>" class="text-decoration-none text-primary">
                                                    <?= htmlspecialchars($request['title'] ?? 'No Title') ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($request['forwarded_to'] ?? $request['assigned_to'] ?? 'N/A') ?></td>
                                            <td>
                                                <?php if (isset($request['specialist_response']) && $request['specialist_response']): ?>
                                                    <div class="response-text">
                                                        <?= htmlspecialchars(substr($request['specialist_response'], 0, 50)) . '...' ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No response yet</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= formatDateTime($request['forwarded_at'] ?? $request['created_at'] ?? '') ?></td>
                                            <td><span class="status-badge status-<?= strtolower($request['status'] ?? 'assigned') ?>"><?= htmlspecialchars($request['status'] ?? 'Assigned') ?></span></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="view_request.php?id=<?= $request['id'] ?>" class="btn btn-sm btn-primary btn-action" data-bs-toggle="tooltip" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (isset($request['specialist_response']) && $request['specialist_response'] && !(isset($request['response_forwarded']) && $request['response_forwarded'])): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                            <button type="submit" name="forward_response" class="btn btn-sm btn-success btn-action" data-bs-toggle="tooltip" title="Forward Response to Requester" onclick="return confirm('Forward this response to the requester?');">
                                                                <i class="fas fa-share"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <a href="#" class="btn btn-sm btn-warning btn-action" data-bs-toggle="tooltip" title="Remind Specialist">
                                                        <i class="fas fa-bell"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-share fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted fw-bold">No Forwarded Requests</h5>
                            <p class="text-muted">No requests have been forwarded to specialists.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- All Tickets -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">All Tickets for <?= htmlspecialchars($departmentName) ?></h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($allTickets)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Subject</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Assigned To</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allTickets as $ticket): ?>
                                        <tr class="request-card request-<?= strtolower($ticket['priority'] ?? 'low') ?>">
                                            <td>#<?= htmlspecialchars($ticket['id'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($ticket['user_name'] ?? 'N/A User') ?></td>
                                            <td>
                                                <a href="view_request.php?id=<?= $ticket['id'] ?>" class="text-decoration-none text-primary">
                                                    <?= htmlspecialchars($ticket['subject'] ?? $ticket['title'] ?? 'No Subject') ?>
                                                </a>
                                            </td>
                                            <td>
                                                <i class="fas fa-<?= isset($ticket['category']) && in_array($ticket['category'], ['Networking problems', 'Software issues', 'Cybersecurity concerns', 'Hardware or maintenance needs']) ? ['Networking problems' => 'network-wired', 'Software issues' => 'laptop-code', 'Cybersecurity concerns' => 'shield-alt', 'Hardware or maintenance needs' => 'tools'][$ticket['category']] : 'question' ?> category-icon"></i>
                                                <?= htmlspecialchars($ticket['category'] ?? 'N/A') ?>
                                            </td>
                                            <td><span class="status-badge status-<?= strtolower($ticket['status'] ?? 'new') ?>"><?= htmlspecialchars($ticket['status'] ?? 'New') ?></span></td>
                                            <td><?= htmlspecialchars($ticket['assigned_to'] ?? 'Unassigned') ?></td>
                                            <td><?= formatDateTime($ticket['created_at'] ?? '') ?></td>
                                            <td>
                                                <a href="view_request.php?id=<?= $ticket['id'] ?>" class="btn btn-sm btn-primary btn-action" data-bs-toggle="tooltip" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted fw-bold">No Tickets Available</h5>
                            <p class="text-muted">No tickets have been submitted for your department.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer -->
            <footer class="py-4 mt-5 text-center text-muted border-top">
                <p class="mb-0">IT Helpdesk System © <?= date('Y') ?> | Designed for Efficiency</p>
            </footer>
        </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            (function() {
                'use strict';
                const forms = document.querySelectorAll('.needs-validation');
                Array.from(forms).forEach(form => {
                    form.addEventListener('submit', event => {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            })();

            // Enable tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

            // Mark notifications as read
            const notificationDropdown = document.getElementById('notificationDropdown');
            if (notificationDropdown) {
                notificationDropdown.addEventListener('shown.bs.dropdown', function() {
                    fetch('mark_notifications_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            userId: <?= isset($userId) ? $userId : 'null' ?>
                        })
                    }).then(response => {
                        if (response.ok) {
                            const badge = document.querySelector('.notification-badge');
                            if (badge) badge.style.display = 'none';
                            document.querySelectorAll('.notification-item').forEach(item => {
                                item.classList.remove('bg-light');
                            });
                        }
                    });
                });
            }
        });
    </script>
</body>

</html>