<?php
// ithead.php
session_start(); // Ensure session is started for authentication

// Assuming db_connect.php provides a getDbConnection() function
require_once 'db_connect.php';

// --- Helper Functions (Ensuring they are defined before first use) ---
function redirect($page, $params = [])
{
    $url = $page;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    header("Location: " . $url);
    exit();
}

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

function requireLogin($requiredRole = null)
{
    // If not logged in at all, redirect to login page
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['role'])) {
        $_SESSION['displayMessage'] = "You must be logged in to access this page.";
        $_SESSION['messageType'] = "danger";
        redirect('login.php'); // Assuming login.php handles initial login
    }

    // If logged in but role doesn't match required role, redirect to index or an error page
    if ($requiredRole && $_SESSION['role'] !== $requiredRole) {
        $_SESSION['displayMessage'] = "You do not have permission to access this page. Your role: " . ucfirst($_SESSION['role']);
        $_SESSION['messageType'] = "danger";
        redirect('index.php'); // Redirect to a general dashboard or login
    }
}
// --- End Helper Functions ---


requireLogin('ithead'); // Ensure only IT Heads can access

$currentUser = $_SESSION['username'];
$currentRole = $_SESSION['role'];

$conn = getDbConnection(); // Get the database connection

// Handle Messages from Session (PRG pattern)
displayMessage(); // Use the helper function

// Handle Form Submission (Respond to Employee or Forward to Specialist)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['submit_response'])) {
            // Validate inputs for response
            $requiredFields = ['ticket_id', 'receiver', 'message'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("All fields are required for response.");
                }
            }

            $ticketId = (int)$_POST['ticket_id']; // This is the ID of the employee's original ticket
            $receiver = htmlspecialchars($_POST['receiver']); // This is the employee's username
            $message = htmlspecialchars($_POST['message']);

            // Get original employee ticket details to use for the response's title, category, priority
            $sql = "SELECT category, priority, title FROM messages WHERE id = ? AND sender = ? AND role_from = 'employee' AND role_to = 'ithead'";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error preparing statement for original ticket: " . $conn->error);
            }
            $stmt->bind_param("is", $ticketId, $receiver); // Ensure it's the correct original ticket from the correct employee
            $stmt->execute();
            $result = $stmt->get_result();
            $originalEmployeeTicket = $result->fetch_assoc();
            $stmt->close();

            if (!$originalEmployeeTicket) {
                throw new Exception("Original employee ticket not found or invalid. Cannot respond.");
            }

            $responseTitle = "Re: " . $originalEmployeeTicket['title'];
            $category = $originalEmployeeTicket['category'];
            $priority = $originalEmployeeTicket['priority'];
            $parentId = $ticketId; // The IT Head's response links back to the original employee ticket

            $sql = "INSERT INTO messages (sender, receiver, role_from, role_to, title, message, category, priority, parent_id, status)
                    VALUES (?, ?, 'ithead', 'employee', ?, ?, ?, ?, ?, 'responded')";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error preparing response statement: " . $conn->error);
            }
            $stmt->bind_param("ssssssi", $currentUser, $receiver, $responseTitle, $message, $category, $priority, $parentId);

            if ($stmt->execute()) {
                // Update original employee ticket status to 'responded'
                $updateSql = "UPDATE messages SET status = 'responded' WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("i", $ticketId);
                $updateStmt->execute();
                $updateStmt->close();

                $_SESSION['displayMessage'] = "Response sent to " . htmlspecialchars($receiver) . " successfully!";
                $_SESSION['messageType'] = "success";
            } else {
                throw new Exception("Failed to send response: " . $stmt->error);
            }
            $stmt->close();
            redirect('ithead.php'); // PRG pattern
        } elseif (isset($_POST['submit_forward'])) {
            // Validate inputs for forwarding
            $requiredFields = ['ticket_id', 'specialist_receiver', 'forward_message'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("All fields are required for forwarding.");
                }
            }

            $ticketId = (int)$_POST['ticket_id']; // This is the ID of the employee's original ticket
            $specialistReceiver = htmlspecialchars($_POST['specialist_receiver']);
            $forwardMessage = htmlspecialchars($_POST['forward_message']);

            // Get original employee ticket details for forwarding
            $sql = "SELECT sender, title, message, category, priority FROM messages WHERE id = ? AND role_from = 'employee' AND role_to = 'ithead'";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error preparing statement for original ticket: " . $conn->error);
            }
            $stmt->bind_param("i", $ticketId);
            $stmt->execute();
            $result = $stmt->get_result();
            $originalEmployeeTicket = $result->fetch_assoc();
            $stmt->close();

            if (!$originalEmployeeTicket) {
                throw new Exception("Original employee ticket not found or cannot be forwarded.");
            }

            $forwardTitle = "FW: " . $originalEmployeeTicket['title'];
            $forwardedMessageContent = "Original Sender: " . $originalEmployeeTicket['sender'] . "\n"
                . "Original Message:\n" . $originalEmployeeTicket['message'] . "\n\n"
                . "IT Head's Note:\n" . $forwardMessage;

            // Use the original category and priority for the forwarded ticket
            $category = $originalEmployeeTicket['category'];
            $priority = $originalEmployeeTicket['priority'];
            $parentId = $ticketId; // The forwarded message links back to the original employee ticket

            $sql = "INSERT INTO messages (sender, receiver, role_from, role_to, title, message, category, priority, parent_id, status)
                    VALUES (?, ?, 'ithead', 'specialist', ?, ?, ?, ?, ?, 'forwarded')";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error preparing forward statement: " . $conn->error);
            }
            $stmt->bind_param("ssssssi", $currentUser, $specialistReceiver, $forwardTitle, $forwardedMessageContent, $category, $priority, $parentId);

            if ($stmt->execute()) {
                // Update original employee ticket status to 'forwarded'
                $updateSql = "UPDATE messages SET status = 'forwarded' WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("i", $ticketId);
                $updateStmt->execute();
                $updateStmt->close();

                $_SESSION['displayMessage'] = "Ticket #" . $ticketId . " forwarded to " . htmlspecialchars($specialistReceiver) . " successfully!";
                $_SESSION['messageType'] = "success";
            } else {
                throw new Exception("Failed to forward ticket: " . $stmt->error);
            }
            $stmt->close();
            redirect('ithead.php'); // PRG pattern
        }
    } catch (Exception $e) {
        error_log("IT Head action error for " . $currentUser . ": " . $e->getMessage());
        $_SESSION['displayMessage'] = $e->getMessage();
        $_SESSION['messageType'] = "danger";
        redirect('ithead.php'); // Redirect with error message
    }
}

// Fetch Tickets from Employees (initial tickets directed to this IT Head)
$employeeTickets = [];
$sql = "SELECT m.*,
            emp.username as employee_name,
            -- Check for specialist response to this specific employee ticket
            specialist_resp.message as specialist_response_message,
            specialist_resp.sent_at as specialist_response_sent_at,
            specialist_resp.sender as specialist_response_sender
        FROM messages m
        JOIN users emp ON m.sender = emp.username AND emp.role = 'employee'
        LEFT JOIN messages specialist_resp ON m.id = specialist_resp.parent_id AND specialist_resp.role_from = 'specialist' AND specialist_resp.role_to = 'ithead'
        WHERE m.receiver = ? AND m.role_to = 'ithead' AND m.parent_id IS NULL -- Only original employee tickets directed to this IT Head
        ORDER BY m.sent_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $currentUser);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $employeeTickets[] = $row;
    }
    $stmt->close();
} else {
    error_log("Error fetching employee tickets for IT Head " . $currentUser . ": " . $conn->error);
}

// Fetch Responses from Specialists (where specialist sent to THIS IT Head)
$specialistResponses = [];
$sql = "SELECT m.*,
            original_emp_ticket.sender as original_employee_sender,
            original_emp_ticket.title as original_employee_title,
            original_emp_ticket.message as original_employee_message,
            original_emp_ticket.status as original_employee_status
        FROM messages m
        JOIN messages original_emp_ticket ON m.parent_id = original_emp_ticket.id AND original_emp_ticket.role_from = 'employee'
        WHERE m.receiver = ? AND m.role_from = 'specialist' AND m.role_to = 'ithead'
        ORDER BY m.sent_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $currentUser);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $specialistResponses[] = $row;
    }
    $stmt->close();
} else {
    error_log("Error fetching specialist responses for IT Head " . $currentUser . ": " . $conn->error);
}

// Fetch a list of specialists for forwarding (assuming 'specialist' is a role in your users table)
$specialists = [];
$sql = "SELECT username FROM users WHERE role = 'specialist' ORDER BY username ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $specialists[] = $row['username'];
    }
} else {
    error_log("Error fetching specialists for IT Head " . $currentUser . ": " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Head Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .priority-high {
            border-left: 4px solid #dc3545;
        }

        .priority-medium {
            border-left: 4px solid #ffc107;
        }

        .priority-low {
            border-left: 4px solid #28a745;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .status-open {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-responded {
            background-color: #d4edda;
            color: #155724;
        }

        .status-forwarded {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-closed {
            background-color: #e2e3e5;
            color: #6c757d;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }

        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #0d6efd;
        }

        .original-message-card {
            background-color: #f8f9fa;
            border-left: 3px solid #0d6efd;
            padding: 10px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold text-info">
                        <i class="bi bi-briefcase-fill"></i> IT Head Portal
                    </h1>
                    <p class="lead">Welcome, <?= htmlspecialchars($currentUser) ?> (<?= ucfirst($currentRole) ?>)</p>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                </div>

                <?php displayMessage(); ?>

                <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="employee-tickets-tab" data-bs-toggle="tab" data-bs-target="#employee-tickets-tab-pane" type="button" role="tab" aria-controls="employee-tickets-tab-pane" aria-selected="true">
                            <i class="bi bi-person-lines-fill"></i> Employee Tickets (<?= count($employeeTickets) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="specialist-responses-tab" data-bs-toggle="tab" data-bs-target="#specialist-responses-tab-pane" type="button" role="tab" aria-controls="specialist-responses-tab-pane" aria-selected="false">
                            <i class="bi bi-reply-all"></i> Specialist Responses (<?= count($specialistResponses) ?>)
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="employee-tickets-tab-pane" role="tabpanel" aria-labelledby="employee-tickets-tab" tabindex="0">
                        <?php if (empty($employeeTickets)): ?>
                            <div class="alert alert-info">No new tickets from employees.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ticket ID</th>
                                            <th>From</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($employeeTickets as $ticket): ?>
                                            <tr class="<?= 'priority-' . strtolower($ticket['priority']) ?>">
                                                <td>#<?= $ticket['id'] ?></td>
                                                <td><?= htmlspecialchars($ticket['sender']) ?></td>
                                                <td><?= htmlspecialchars($ticket['title']) ?></td>
                                                <td><?= ucfirst($ticket['category']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= strtolower($ticket['priority']) === 'high' ? 'danger' : (strtolower($ticket['priority']) === 'medium' ? 'warning' : 'success') ?>">
                                                        <?= ucfirst(strtolower($ticket['priority'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?= $ticket['status'] ?>">
                                                        <?= ucfirst($ticket['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M j, Y', strtotime($ticket['sent_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewEmployeeTicketModal<?= $ticket['id'] ?>">
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                    <?php if ($ticket['status'] !== 'responded'): // Can always forward, but only respond once to the direct ticket unless it's a follow-up. For simplicity, prevent multiple direct responses to same 'open' ticket. 
                                                    ?>
                                                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#respondEmployeeModal<?= $ticket['id'] ?>">
                                                            <i class="bi bi-reply"></i> Respond
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($ticket['status'] !== 'forwarded'): // Can't forward if already forwarded 
                                                    ?>
                                                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#forwardSpecialistModal<?= $ticket['id'] ?>">
                                                            <i class="bi bi-share"></i> Forward
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="viewEmployeeTicketModal<?= $ticket['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Ticket #<?= $ticket['id'] ?>: <?= htmlspecialchars($ticket['title']) ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>From:</strong> <?= htmlspecialchars($ticket['sender']) ?></p>
                                                            <p><strong>Category:</strong> <?= ucfirst($ticket['category']) ?></p>
                                                            <p><strong>Priority:</strong>
                                                                <span class="badge bg-<?= strtolower($ticket['priority']) === 'high' ? 'danger' : (strtolower($ticket['priority']) === 'medium' ? 'warning' : 'success') ?>">
                                                                    <?= ucfirst(strtolower($ticket['priority'])) ?>
                                                                </span>
                                                            </p>
                                                            <p><strong>Status:</strong>
                                                                <span class="status-badge status-<?= $ticket['status'] ?>">
                                                                    <?= ucfirst($ticket['status']) ?>
                                                                </span>
                                                            </p>
                                                            <hr>
                                                            <h6>Employee's Message:</h6>
                                                            <div class="card bg-light p-3 mb-3">
                                                                <?= nl2br(htmlspecialchars($ticket['message'])) ?>
                                                            </div>

                                                            <?php if (!empty($ticket['specialist_response_message'])): ?>
                                                                <h6>Specialist Response (from <?= htmlspecialchars($ticket['specialist_response_sender']) ?> on <?= date('M j, Y \a\t g:i a', strtotime($ticket['specialist_response_sent_at'])) ?>):</h6>
                                                                <div class="card bg-light p-3">
                                                                    <?= nl2br(htmlspecialchars($ticket['specialist_response_message'])) ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="alert alert-info">No specialist response yet for this ticket.</div>
                                                            <?php endif; ?>

                                                            <p class="text-muted mt-3"><small>Submitted on <?= date('F j, Y \a\t g:i a', strtotime($ticket['sent_at'])) ?></small></p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal fade" id="respondEmployeeModal<?= $ticket['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <form method="POST" action="">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Respond to Employee Ticket #<?= $ticket['id'] ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                                                <input type="hidden" name="receiver" value="<?= htmlspecialchars($ticket['sender']) ?>">

                                                                <div class="original-message-card mb-4">
                                                                    <h6>Original Ticket from <?= htmlspecialchars($ticket['sender']) ?>:</h6>
                                                                    <p><strong><?= htmlspecialchars($ticket['title']) ?></strong></p>
                                                                    <div class="card bg-light p-3 mb-2">
                                                                        <?= nl2br(htmlspecialchars($ticket['message'])) ?>
                                                                    </div>
                                                                    <p class="text-muted"><small>Submitted on <?= date('F j, Y \a\t g:i a', strtotime($ticket['sent_at'])) ?></small></p>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label for="message<?= $ticket['id'] ?>" class="form-label">Your Response to Employee</label>
                                                                    <textarea class="form-control" id="message<?= $ticket['id'] ?>" name="message" rows="5" placeholder="Enter your detailed response to the employee..." required></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="submit_response" class="btn btn-success">
                                                                    <i class="bi bi-send"></i> Send Response
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal fade" id="forwardSpecialistModal<?= $ticket['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <form method="POST" action="">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Forward Ticket #<?= $ticket['id'] ?> to Specialist</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">

                                                                <div class="original-message-card mb-4">
                                                                    <h6>Original Ticket from <?= htmlspecialchars($ticket['sender']) ?>:</h6>
                                                                    <p><strong><?= htmlspecialchars($ticket['title']) ?></strong></p>
                                                                    <div class="card bg-light p-3 mb-2">
                                                                        <?= nl2br(htmlspecialchars($ticket['message'])) ?>
                                                                    </div>
                                                                    <p class="text-muted"><small>Submitted on <?= date('F j, Y \a\t g:i a', strtotime($ticket['sent_at'])) ?></small></p>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label for="specialist_receiver<?= $ticket['id'] ?>" class="form-label">Forward to Specialist</label>
                                                                    <select class="form-select" id="specialist_receiver<?= $ticket['id'] ?>" name="specialist_receiver" required>
                                                                        <option value="">Select Specialist</option>
                                                                        <?php foreach ($specialists as $specialist): ?>
                                                                            <option value="<?= htmlspecialchars($specialist) ?>"><?= htmlspecialchars($specialist) ?></option>
                                                                        <?php endforeach; ?>
                                                                        <?php if (empty($specialists)): ?>
                                                                            <option value="" disabled>No specialists found</option>
                                                                        <?php endif; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="forward_message<?= $ticket['id'] ?>" class="form-label">Note for Specialist</label>
                                                                    <textarea class="form-control" id="forward_message<?= $ticket['id'] ?>" name="forward_message" rows="4" placeholder="Add any specific instructions or context for the specialist..." required></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="submit_forward" class="btn btn-warning">
                                                                    <i class="bi bi-share"></i> Forward Ticket
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="specialist-responses-tab-pane" role="tabpanel" aria-labelledby="specialist-responses-tab" tabindex="0">
                        <?php if (empty($specialistResponses)): ?>
                            <div class="alert alert-info">No responses from specialists yet.</div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($specialistResponses as $response): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-success text-white">
                                                <h6 class="mb-0">Response from <?= htmlspecialchars($response['sender']) ?></h6>
                                            </div>
                                            <div class="card-body">
                                                <h5 class="card-title"><?= htmlspecialchars($response['title']) ?></h5>
                                                <div class="original-message-card mb-3">
                                                    <p><strong>Original Employee Ticket (from <?= htmlspecialchars($response['original_employee_sender']) ?>):</strong> <?= htmlspecialchars($response['original_employee_title']) ?></p>
                                                    <div class="card bg-light p-2">
                                                        <?= nl2br(htmlspecialchars(substr($response['original_employee_message'], 0, 150))) ?>...
                                                    </div>
                                                </div>
                                                <p class="card-text">
                                                    <?= nl2br(htmlspecialchars(substr($response['message'], 0, 150))) ?>...
                                                </p>
                                                <small class="text-muted">Received on <?= date('M j, Y g:i a', strtotime($response['sent_at'])) ?></small>
                                                <button class="btn btn-sm btn-outline-primary float-end" data-bs-toggle="modal" data-bs-target="#viewSpecialistResponseModal<?= $response['id'] ?>">
                                                    View Details
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="viewSpecialistResponseModal<?= $response['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Specialist Response Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="original-message-card mb-4">
                                                        <h6>Original Employee Ticket (ID: <?= $response['parent_id'] ?>) from <?= htmlspecialchars($response['original_employee_sender']) ?>:</h6>
                                                        <p><strong>Title:</strong> <?= htmlspecialchars($response['original_employee_title']) ?></p>
                                                        <div class="card bg-light p-3 mb-3">
                                                            <?= nl2br(htmlspecialchars($response['original_employee_message'])) ?>
                                                        </div>
                                                    </div>

                                                    <h6>Specialist's Response (from <?= htmlspecialchars($response['sender']) ?>):</h6>
                                                    <div class="card bg-light p-3 mb-3">
                                                        <?= nl2br(htmlspecialchars($response['message'])) ?>
                                                    </div>
                                                    <p class="text-muted"><small>Response sent on <?= date('F j, Y \a\t g:i a', strtotime($response['sent_at'])) ?></small></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabEls.forEach(function(tabEl) {
                tabEl.addEventListener('click', function(event) {
                    event.preventDefault();
                    var tab = new bootstrap.Tab(tabEl);
                    tab.show();
                });
            });
        });
    </script>
</body>

</html>