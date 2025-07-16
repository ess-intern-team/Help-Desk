<?php
session_start(); // This MUST be the very first line of PHP code in the file!

// --- START: Session Initialization for Demo/Development ---
// In a real, production application, this entire block should be replaced
// with robust user authentication logic (e.g., after a successful login).
// For demonstration purposes, we will always ensure these session variables are set.
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Seid Hussen';
$_SESSION['name'] = 'Seid Hussen';       // Ensures 'name' is always set for display and notifications
$_SESSION['role'] = 'Admin';
$_SESSION['department'] = 'IT';         // Ensures 'department' is always set for filtering requests
// --- END: Session Initialization for Demo/Development ---

// Strict session data check. If any essential part of the session is missing,
// the script will stop. In a production environment, you'd redirect to a login page.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['department']) || !isset($_SESSION['name'])) {
    // For a real application, use: header('Location: login.php'); exit();
    die("Error: User session not properly set. Please log in.");
}

// Include your database connection file.
// Ensure 'db_connect.php' properly creates a PDO object named $pdo.
require_once 'db_connect.php';

// Assign session variables to local variables for easier use
$department = $_SESSION['department'];
$head_id = $_SESSION['user_id'];
$userName = $_SESSION['name']; // Using 'name' for the current user's display name

// Initialize variables to hold messages and data
$error = null;
$success = null;
$requests = []; // To hold pending requests
$specialists = []; // To hold IT specialists
$forwarded_requests = []; // To hold forwarded requests
$resolved_this_week_count = 0; // To hold the count of resolved requests

// --- Fetch Data from Database ---

// 1. Get pending requests from department users
try {
    $requests_stmt = $pdo->prepare("SELECT r.*, u.username as user_name
                                     FROM requests r
                                     JOIN users u ON r.user_id = u.id
                                     WHERE u.department = ? AND r.status = 'pending'
                                     ORDER BY r.priority DESC, r.created_at");
    $requests_stmt->execute([$department]);
    $requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching pending requests: " . $e->getMessage();
    error_log("DB Error (Pending Requests): " . $e->getMessage()); // Log detailed error
}

// 2. Get IT specialists relevant to the department (or all, depending on logic)
try {
    $specialists_stmt = $pdo->prepare("SELECT id, name, specialties FROM it_specialists
                                         WHERE specialties LIKE ? OR specialties = 'General'"); // Added 'General' specialty fallback
    $specialists_stmt->execute(["%$department%"]);
    $specialists = $specialists_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching specialists: " . $e->getMessage();
    error_log("DB Error (Specialists): " . $e->getMessage()); // Log detailed error
}

// 3. Get count of resolved requests for this department this week (SQL Injection Fixed)
try {
    $resolved_stmt = $pdo->prepare("SELECT COUNT(*) FROM requests
                                    WHERE status = 'completed'
                                    AND department = ?
                                    AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $resolved_stmt->execute([$department]);
    $resolved_this_week_count = $resolved_stmt->fetchColumn();
} catch (PDOException $e) {
    $error = "Error fetching resolved requests count: " . $e->getMessage();
    error_log("DB Error (Resolved Count): " . $e->getMessage()); // Log detailed error
}


// --- Handle POST Requests (Form Submissions) ---

// Handle request forwarding
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forward_request'])) {
    // Validate and sanitize inputs
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $specialist_id = filter_input(INPUT_POST, 'specialist_id', FILTER_VALIDATE_INT);
    $notes = trim($_POST['notes']); // Trim whitespace

    if ($request_id === false || $request_id === null ||
        $specialist_id === false || $specialist_id === null ||
        empty($specialists) // Cannot forward if no specialists available
        ) {
        $error = "Invalid request or specialist selection. Please try again.";
    } else {
        try {
            $pdo->beginTransaction();

            // Update request status in the requests table
            $update_stmt = $pdo->prepare("UPDATE requests
                                         SET status = 'forwarded',
                                             forwarded_by = ?,
                                             forwarded_at = NOW(),
                                             specialist_id = ?,
                                             head_notes = ?
                                         WHERE id = ?");
            $update_stmt->execute([$head_id, $specialist_id, $notes, $request_id]);

            // Create a notification for the selected IT specialist
            $notif_stmt = $pdo->prepare("INSERT INTO notifications
                                         (user_id, request_id, message, created_at)
                                         VALUES (?, ?, ?, NOW())");
            $message = "New request forwarded from " . $department . " department. (ID: " . $request_id . ")";
            $notif_stmt->execute([$specialist_id, $request_id, $message]);

            $pdo->commit();
            $success = "Request forwarded successfully!";

            // Redirect to prevent form re-submission on refresh (PRG Pattern)
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack(); // Rollback transaction on error
            $error = "Error forwarding request: " . $e->getMessage();
            error_log("DB Error (Forward Request): " . $e->getMessage());
        }
    }
}

// Handle responses from specialists (This block assumes the Department Head can also "respond"
// to a request directly, marking it as completed. If this is meant only for IT Specialists,
// it should be moved to their respective dashboard.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_response'])) {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $response = trim($_POST['response']);

    if ($request_id === false || $request_id === null || empty($response)) {
        $error = "Invalid request ID or empty response.";
    } else {
        try {
            $pdo->beginTransaction();

            // Update the request with the response and mark as completed
            $update_stmt = $pdo->prepare("UPDATE requests
                                         SET status = 'completed',
                                             response = ?,
                                             completed_at = NOW()
                                         WHERE id = ?");
            $update_stmt->execute([$response, $request_id]);

            // Get the original user ID who submitted the request for notification
            $user_stmt = $pdo->prepare("SELECT user_id FROM requests WHERE id = ?");
            $user_stmt->execute([$request_id]);
            $user_id = $user_stmt->fetchColumn();

            if ($user_id) {
                // Create a notification for the user whose request was resolved
                $notif_message = "Your request (ID: " . $request_id . ") has been resolved by " . $userName . " (Department Head).";
                $notif_stmt = $pdo->prepare("INSERT INTO notifications
                                             (user_id, request_id, message, created_at)
                                             VALUES (?, ?, ?, NOW())");
                $notif_stmt->execute([$user_id, $request_id, $notif_message]);
            }

            $pdo->commit();
            $success = "Response sent to user successfully!";

            // Redirect to prevent form re-submission
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error sending response: " . $e->getMessage();
            error_log("DB Error (Send Response): " . $e->getMessage());
        }
    }
}

---
### The fix for "Unknown column 'u.name'" is below:
---
// --- Re-fetch Forwarded Requests (after potential form submission) ---
// This ensures the list is up-to-date after a forward action.
try {
    $forwarded_stmt = $pdo->prepare("SELECT r.*, u.username as user_name, s.name as specialist_name
                                     FROM requests r
                                     JOIN users u ON r.user_id = u.id
                                     LEFT JOIN it_specialists s ON r.specialist_id = s.id -- LEFT JOIN to still show if specialist_id is null or invalid
                                     WHERE u.department = ? AND r.status = 'forwarded'
                                     ORDER BY r.forwarded_at DESC");
    $forwarded_stmt->execute([$department]);
    $forwarded_requests = $forwarded_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching forwarded requests: " . $e->getMessage();
    error_log("DB Error (Forwarded Requests Re-fetch): " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_SESSION['department']) ?> Department Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3f37c9;
            --primary-dark: #2a2670;
            --secondary: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background-color: white;
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .nav-pills .nav-link.active {
            background-color: var(--primary);
        }

        .priority-high {
            border-left: 4px solid #f72585;
        }

        .priority-medium {
            border-left: 4px solid #f8961e;
        }

        .priority-low {
            border-left: 4px solid #4cc9f0;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .badge-pending {
            background-color: #ffedd5;
            color: #9a3412;
        }

        .badge-forwarded {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .badge-completed {
            background-color: #dcfce7;
            color: #166534;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-4 text-center border-bottom">
                    <h4 class="mb-0">
                        <i class="fas fa-building me-2"></i>
                        <?= htmlspecialchars($_SESSION['department']) ?>
                    </h4>
                    <small class="text-muted">Department Manager</small>
                </div>

                <div class="p-4 text-center border-bottom">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=3f37c9&color=fff"
                        alt="User" class="rounded-circle mb-2" width="80">
                    <h6 class="mb-0"><?= htmlspecialchars($_SESSION['name']) ?></h6>
                    <small class="text-muted">Department Head</small>
                </div>

                <nav class="nav flex-column p-3">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-ticket-alt me-2"></i> Requests
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-users me-2"></i> Department Members
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-chart-bar me-2"></i> Reports
                    </a>
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </nav>
            </div>

            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>
                        <i class="fas fa-headset me-2"></i>
                        IT Helpdesk Dashboard
                    </h3>
                    <div>
                        <span class="badge bg-primary me-2">
                            <i class="fas fa-building me-1"></i> <?= htmlspecialchars($_SESSION['department']) ?>
                        </span>
                        <span class="badge bg-secondary">
                            <i class="fas fa-user-tie me-1"></i> Department Head
                        </span>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-1">Pending Requests</h6>
                                        <h3 class="mb-0"><?= count($requests) ?></h3>
                                    </div>
                                    <i class="fas fa-clock fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-1">Resolved This Week</h6>
                                        <h3 class="mb-0">
                                            <?= $resolved_this_week_count; ?>
                                        </h3>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-1">Available Specialists</h6>
                                        <h3 class="mb-0"><?= count($specialists) ?></h3>
                                    </div>
                                    <i class="fas fa-user-shield fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Pending Requests from Department Members
                        </h5>
                        <div class="input-group" style="width: 250px;">
                            <span class="input-group-text bg-transparent"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search requests...">
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <?php if (count($requests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Title</th>
                                            <th>Priority</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $request): ?>
                                            <tr class="priority-<?= strtolower($request['priority']) ?>">
                                                <td>#<?= htmlspecialchars($request['id']) ?></td>
                                                <td><?= htmlspecialchars($request['user_name']) ?></td>
                                                <td><?= htmlspecialchars($request['title']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($request['priority']) ?>
                                                    <?php if ($request['priority'] == 'High'): ?>
                                                        <span class="badge bg-danger ms-1">!</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('M j, g:i a', strtotime($request['created_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                        data-bs-target="#forwardModal"
                                                        data-request-id="<?= htmlspecialchars($request['id']) ?>">
                                                        <i class="fas fa-share me-1"></i> Forward
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                                        data-bs-target="#viewModal"
                                                        data-request-id="<?= htmlspecialchars($request['id']) ?>"
                                                        data-title="<?= htmlspecialchars($request['title']) ?>"
                                                        data-description="<?= htmlspecialchars($request['description']) ?>"
                                                        data-created-at="<?= date('M j, g:i a', strtotime($request['created_at'])) ?>"
                                                        data-user-name="<?= htmlspecialchars($request['user_name']) ?>">
                                                        <i class="fas fa-eye me-1"></i> View
                                                    </button>
                                                    </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Pending Requests</h5>
                                <p class="text-muted">There are currently no pending requests from your department members.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-share me-2"></i>
                            Forwarded to IT Specialists
                        </h5>
                    </div>

                    <div class="card-body p-0">
                        <?php if (count($forwarded_requests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Title</th>
                                            <th>Specialist</th>
                                            <th>Forwarded</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($forwarded_requests as $req): ?>
                                            <tr>
                                                <td>#<?= htmlspecialchars($req['id']) ?></td>
                                                <td><?= htmlspecialchars($req['user_name']) ?></td>
                                                <td><?= htmlspecialchars($req['title']) ?></td>
                                                <td><?= htmlspecialchars($req['specialist_name'] ?? 'N/A') ?></td> <td><?= date('M j, g:i a', strtotime($req['forwarded_at'])) ?></td>
                                                <td>
                                                    <span class="badge badge-forwarded">Forwarded</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-share-alt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Forwarded Requests</h5>
                                <p class="text-muted">You haven't forwarded any requests to IT specialists yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6 id="viewTitle"></h6>
                        <small class="text-muted">From: <span id="viewUser"></span></small>
                    </div>
                    <div class="mb-3">
                        <p id="viewDescription" class="text-muted"></p>
                    </div>
                    <div>
                        <small class="text-muted">Submitted: <span id="viewCreatedAt"></span></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forwardModal" tabindex="-1" aria-labelledby="forwardModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="request_id" id="forwardRequestId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="forwardModalLabel">Forward to IT Specialist</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="specialistSelect" class="form-label">Select Specialist</label>
                            <select class="form-select" id="specialistSelect" name="specialist_id" required>
                                <?php if (count($specialists) > 0): ?>
                                    <?php foreach ($specialists as $spec): ?>
                                        <option value="<?= htmlspecialchars($spec['id']) ?>">
                                            <?= htmlspecialchars($spec['name']) ?> - <?= htmlspecialchars($spec['specialties']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled selected>No IT specialists available</option>
                                <?php endif; ?>
                            </select>
                            <?php if (count($specialists) === 0): ?>
                                <small class="text-danger mt-2">No IT specialists found for your department. Please ensure specialists are added to the system.</small>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="notesTextarea" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="notesTextarea" name="notes" rows="3"
                                placeholder="Add any additional information for the specialist..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="forward_request" class="btn btn-primary"
                            <?= (count($specialists) === 0) ? 'disabled' : '' ?>>Forward Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="request_id" id="responseRequestId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="responseModalLabel">Send Response to User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="responseTextarea" class="form-label">Response</label>
                            <textarea class="form-control" id="responseTextarea" name="response" rows="5" required
                                placeholder="Enter the solution or response for the user..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="send_response" class="btn btn-primary">Send Response</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View Modal
        const viewModal = document.getElementById('viewModal');
        if (viewModal) {
            viewModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget; // Button that triggered the modal
                viewModal.querySelector('#viewTitle').textContent = button.getAttribute('data-title');
                viewModal.querySelector('#viewUser').textContent = button.getAttribute('data-user-name');
                viewModal.querySelector('#viewDescription').textContent = button.getAttribute('data-description');
                viewModal.querySelector('#viewCreatedAt').textContent = button.getAttribute('data-created-at');
            });
        }

        // Forward Modal
        const forwardModal = document.getElementById('forwardModal');
        if (forwardModal) {
            forwardModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                forwardModal.querySelector('#forwardRequestId').value = button.getAttribute('data-request-id');
                // Optional: Clear previous notes if any
                forwardModal.querySelector('#notesTextarea').value = '';
            });
        }

        // Response Modal
        const responseModal = document.getElementById('responseModal');
        if (responseModal) {
            responseModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                responseModal.querySelector('#responseRequestId').value = button.getAttribute('data-request-id');
                // Optional: Clear previous response if any
                responseModal.querySelector('#responseTextarea').value = '';
            });
        }
    </script>
</body>

</html>