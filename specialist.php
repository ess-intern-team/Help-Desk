<?php
session_start();
require_once 'db_connect.php';

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
                        var toast = bootstrap.Toast.getInstance(toastEl) || new bootstrap.Toast(toastEl);
                        toast.hide();
                    }, 5000);
                }
            });
        </script>";
    }
}

function requireLogin($requiredRole = null)
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['role'])) {
        error_log("Login check failed: Missing session variables.");
        $_SESSION['displayMessage'] = "You must be logged in to access this page.";
        $_SESSION['messageType'] = "danger";
        redirect('login.php');
    }
    if ($requiredRole && $_SESSION['role'] !== $requiredRole) {
        error_log("Role check failed: User role {$_SESSION['role']} does not match required role $requiredRole.");
        $_SESSION['displayMessage'] = "You do not have permission to access this page. Your role: " . ucfirst($_SESSION['role']);
        $_SESSION['messageType'] = "danger";
        redirect('index.php');
    }
}

requireLogin('specialist');
$currentUser = strtolower(trim($_SESSION['username'])); // Normalize and trim username
$currentRole = $_SESSION['role'];
$conn = getDbConnection();

error_log("Specialist session started for user: $currentUser, role: $currentRole, user_id: {$_SESSION['user_id']}");
error_log("Raw Session Data: " . print_r($_SESSION, true));
displayMessage();

// Handle Form Submission (Respond to IT Head)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_response'])) {
    try {
        $requiredFields = ['ticket_id', 'receiver', 'message'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("All fields are required.");
            }
        }

        $ticketId = (int)$_POST['ticket_id'];
        $receiver = htmlspecialchars($_POST['receiver']);
        $message = htmlspecialchars($_POST['message']);

        error_log("Response attempt by $currentUser for ticket #$ticketId to $receiver with message: $message");

        $sql = "SELECT category, priority, title, parent_id, status FROM messages WHERE id = ? AND LOWER(receiver) = LOWER(?) AND role_from = 'ithead' AND role_to = 'specialist'";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error preparing ticket check: " . $conn->error);
        }
        $stmt->bind_param("is", $ticketId, $currentUser);
        $stmt->execute();
        $result = $stmt->get_result();
        $itHeadForwardedTicket = $result->fetch_assoc();
        $stmt->close();

        if (!$itHeadForwardedTicket) {
            throw new Exception("Ticket #$ticketId not found or you don't have permission to respond.");
        }
        if ($itHeadForwardedTicket['status'] === 'responded') {
            throw new Exception("This ticket has already been responded to.");
        }

        $originalEmployeeTicketId = $itHeadForwardedTicket['parent_id'];
        if (!$originalEmployeeTicketId) {
            throw new Exception("Could not find original employee ticket for ticket #$ticketId.");
        }

        $category = $itHeadForwardedTicket['category'] ?? 'unknown';
        $priority = $itHeadForwardedTicket['priority'] ?? 'low';
        $title = "Specialist Response: " . $itHeadForwardedTicket['title'];

        $sql = "INSERT INTO messages (sender, receiver, role_from, role_to, title, message, category, priority, parent_id, status, sent_at)
                VALUES (?, ?, 'specialist', 'ithead', ?, ?, ?, ?, ?, 'responded', NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error preparing response insert: " . $conn->error);
        }
        $stmt->bind_param("sssssi", $currentUser, $receiver, $title, $message, $category, $priority, $originalEmployeeTicketId);
        if ($stmt->execute()) {
            $newResponseId = $conn->insert_id;
            error_log("Response inserted with ID: $newResponseId");
            $updateSql = "UPDATE messages SET status = 'responded' WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            if (!$updateStmt) {
                throw new Exception("Database error preparing status update: " . $conn->error);
            }
            $updateStmt->bind_param("i", $ticketId);
            if ($updateStmt->execute()) {
                error_log("Ticket #$ticketId status updated to 'responded'");
            } else {
                throw new Exception("Failed to update ticket status: " . $updateStmt->error);
            }
            $updateStmt->close();
            $_SESSION['displayMessage'] = "Response sent to " . htmlspecialchars($receiver) . " successfully!";
            $_SESSION['messageType'] = "success";
        } else {
            throw new Exception("Failed to send response: " . $stmt->error);
        }
        $stmt->close();
        redirect('specialist.php');
    } catch (Exception $e) {
        error_log("Response error for specialist $currentUser: " . $e->getMessage());
        $_SESSION['displayMessage'] = $e->getMessage();
        $_SESSION['messageType'] = "danger";
        redirect('specialist.php');
    }
}

// Fetch Tickets from IT Heads
$itHeadTickets = [];
$sql = "SELECT m.id, m.sender, m.receiver, m.role_from, m.role_to, m.title, m.message, m.category, m.priority, m.status, m.sent_at, m.parent_id,
        original_emp_ticket.sender as original_employee_sender,
        original_emp_ticket.message as original_employee_message,
        original_emp_ticket.title as original_employee_title
        FROM messages m
        LEFT JOIN messages original_emp_ticket ON m.parent_id = original_emp_ticket.id
        WHERE LOWER(m.receiver) = LOWER(?) AND m.role_to = 'specialist'
        ORDER BY m.sent_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $currentUser);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $itHeadTickets[] = $row;
            error_log("Main Query - Fetched ticket #{$row['id']} for specialist $currentUser: From {$row['sender']}, Receiver: {$row['receiver']}, Status: {$row['status']}, Parent ID: " . ($row['parent_id'] ?: 'NULL'));
        }
        error_log("Main Query - Total tickets fetched for $currentUser: " . count($itHeadTickets));
    } else {
        error_log("Main Query - Execution failed for specialist $currentUser: " . $stmt->error);
        $_SESSION['displayMessage'] = "Error executing ticket query: " . $stmt->error;
        $_SESSION['messageType'] = "danger";
    }
    $stmt->close();
} else {
    error_log("Main Query - Prepare failed for specialist $currentUser: " . $conn->error);
    $_SESSION['displayMessage'] = "Error preparing ticket query: " . $conn->error;
    $_SESSION['messageType'] = "danger";
}

// Diagnostic Query to Log All Matching Tickets
$diagnosticTickets = [];
$sqlDiagnostic = "SELECT id, sender, receiver, role_from, role_to, status, sent_at, parent_id
                  FROM messages
                  WHERE role_to = 'specialist'";
$resultDiagnostic = $conn->query($sqlDiagnostic);
if ($resultDiagnostic) {
    while ($row = $resultDiagnostic->fetch_assoc()) {
        $diagnosticTickets[] = $row;
        error_log("Diagnostic Query - Found ticket #{$row['id']} for any specialist: Receiver: {$row['receiver']}, Status: {$row['status']}, Parent ID: " . ($row['parent_id'] ?: 'NULL'));
    }
    error_log("Diagnostic Query - Total tickets for any specialist: " . count($diagnosticTickets));
} else {
    error_log("Diagnostic Query - Failed for specialist $currentUser: " . $conn->error);
}

// Debug Query for Verification
$debugTickets = [];
$sqlDebug = "SELECT id, sender, receiver, role_from, role_to, title, status, sent_at, parent_id
             FROM messages
             WHERE LOWER(receiver) = LOWER(?) AND role_to = 'specialist'";
$stmtDebug = $conn->prepare($sqlDebug);
if ($stmtDebug) {
    $stmtDebug->bind_param("s", $currentUser);
    if ($stmtDebug->execute()) {
        $resultDebug = $stmtDebug->get_result();
        while ($row = $resultDebug->fetch_assoc()) {
            $debugTickets[] = $row;
            error_log("Debug Query - Found ticket #{$row['id']} for specialist $currentUser: From {$row['sender']}, Receiver: {$row['receiver']}, Status: {$row['status']}, Parent ID: " . ($row['parent_id'] ?: 'NULL'));
        }
        error_log("Debug Query - Total tickets fetched for $currentUser: " . count($debugTickets));
    } else {
        error_log("Debug Query - Execution failed for specialist $currentUser: " . $stmtDebug->error);
    }
    $stmtDebug->close();
} else {
    error_log("Debug Query - Prepare failed for specialist $currentUser: " . $conn->error);
}

// Fetch Responses sent to IT Heads
$sentResponses = [];
$sql = "SELECT m.id, m.sender, m.receiver, m.role_from, m.role_to, m.title, m.message, m.category, m.priority, m.status, m.sent_at, m.parent_id,
        original_emp_ticket.sender as original_employee_sender,
        original_emp_ticket.title as original_employee_title,
        original_emp_ticket.message as original_employee_message
        FROM messages m
        LEFT JOIN messages original_emp_ticket ON m.parent_id = original_emp_ticket.id
        WHERE LOWER(m.sender) = LOWER(?) AND m.role_from = 'specialist' AND m.role_to = 'ithead'
        ORDER BY m.sent_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $currentUser);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $sentResponses[] = $row;
            error_log("Fetched sent response #{$row['id']} by specialist $currentUser to {$row['receiver']}");
        }
        error_log("Sent Responses - Total fetched for $currentUser: " . count($sentResponses));
    } else {
        error_log("Sent Responses - Execution failed for specialist $currentUser: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Sent Responses - Prepare failed for specialist $currentUser: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Specialist Portal</title>
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

        .status-forwarded {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-responded {
            background-color: #d4edda;
            color: #155724;
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
            background-color: #e9ecef;
            border-left: 3px solid #0d6efd;
            padding: 10px;
            margin-bottom: 15px;
        }

        .response-meta {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .debug-info {
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold text-primary"><i class="bi bi-gear-fill"></i> Specialist Portal</h1>
                    <p class="lead">Welcome, <?= htmlspecialchars($currentUser) ?> (<?= ucfirst($currentRole) ?>)</p>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                </div>

                <?php displayMessage(); ?>

                <!-- Debug Information -->
                <div class="debug-info">
                    <h6>Debug: Session and Ticket Information for <?= htmlspecialchars($currentUser) ?></h6>
                    <p>Session Username: <?= htmlspecialchars($currentUser) ?></p>
                    <p>Session Role: <?= htmlspecialchars($currentRole) ?></p>
                    <p>Session User ID: <?= htmlspecialchars($_SESSION['user_id'] ?? 'Not set') ?></p>
                    <p>Main Query Tickets Found: <?= count($itHeadTickets) ?></p>
                    <p>Debug Query Tickets Found: <?= count($debugTickets) ?></p>
                    <p>Diagnostic Query Tickets Found: <?= count($diagnosticTickets) ?></p>
                    <?php if (!empty($debugTickets)): ?>
                        <p>Debug Query Results:</p>
                        <ul>
                            <?php foreach ($debugTickets as $dt): ?>
                                <li>Ticket #<?= htmlspecialchars($dt['id'] ?? '') ?>: From <?= htmlspecialchars($dt['sender'] ?? 'Unknown') ?> to <?= htmlspecialchars($dt['receiver'] ?? 'Unknown') ?>, Status: <?= htmlspecialchars($dt['status'] ?? 'open') ?>, Role: <?= htmlspecialchars($dt['role_to'] ?? 'Unknown') ?>, Parent ID: <?= htmlspecialchars($dt['parent_id'] ?? 'NULL') ?>, Sent: <?= htmlspecialchars($dt['sent_at'] ?? 'Unknown') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No tickets found in debug query.</p>
                    <?php endif; ?>
                    <?php if (!empty($diagnosticTickets) && empty($itHeadTickets) && !empty($debugTickets)): ?>
                        <p class="text-danger">Warning: Diagnostic query found tickets, but main query returned none. Check session username or database data.</p>
                    <?php endif; ?>
                </div>

                <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests-tab-pane" type="button" role="tab">
                            <i class="bi bi-list-task"></i> Assigned Tickets (<?= count($itHeadTickets) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="responses-tab" data-bs-toggle="tab" data-bs-target="#responses-tab-pane" type="button" role="tab">
                            <i class="bi bi-send-check"></i> Your Responses (<?= count($sentResponses) ?>)
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="requests-tab-pane" role="tabpanel" tabindex="0">
                        <?php if (empty($itHeadTickets)): ?>
                            <div class="alert alert-info">No tickets assigned to you yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ticket ID</th>
                                            <th>From IT Head</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($itHeadTickets as $ticket): ?>
                                            <tr class="priority-<?= htmlspecialchars(strtolower($ticket['priority'] ?? 'low')) ?>">
                                                <td>#<?= htmlspecialchars($ticket['id'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($ticket['sender'] ?? 'Unknown') ?></td>
                                                <td><?= htmlspecialchars($ticket['title'] ?? 'No Title') ?></td>
                                                <td><?= htmlspecialchars(ucfirst($ticket['category'] ?? 'unknown')) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= htmlspecialchars(strtolower($ticket['priority'] ?? 'low') === 'high' ? 'danger' : ($ticket['priority'] ?? 'low' === 'medium' ? 'warning' : 'success')) ?>">
                                                        <?= htmlspecialchars(ucfirst(strtolower($ticket['priority'] ?? 'low'))) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars(date('M j, Y', strtotime($ticket['sent_at'] ?? 'now'))) ?></td>
                                                <td>
                                                    <span class="status-badge status-<?= htmlspecialchars($ticket['status'] ?? 'open') ?>">
                                                        <?= htmlspecialchars(ucfirst($ticket['status'] ?? 'open')) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?= htmlspecialchars($ticket['id'] ?? '') ?>">
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                    <?php if (($ticket['status'] ?? 'open') === 'forwarded'): ?>
                                                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#respondModal<?= htmlspecialchars($ticket['id'] ?? '') ?>">
                                                            <i class="bi bi-reply"></i> Respond
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="viewModal<?= htmlspecialchars($ticket['id'] ?? '') ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Ticket #<?= htmlspecialchars($ticket['id'] ?? '') ?>: <?= htmlspecialchars($ticket['title'] ?? 'No Title') ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <p><strong>From IT Head:</strong> <?= htmlspecialchars($ticket['sender'] ?? 'Unknown') ?></p>
                                                                    <p><strong>Category:</strong> <?= htmlspecialchars(ucfirst($ticket['category'] ?? 'unknown')) ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <p><strong>Priority:</strong>
                                                                        <span class="badge bg-<?= htmlspecialchars(strtolower($ticket['priority'] ?? 'low') === 'high' ? 'danger' : ($ticket['priority'] ?? 'low' === 'medium' ? 'warning' : 'success')) ?>">
                                                                            <?= htmlspecialchars(ucfirst(strtolower($ticket['priority'] ?? 'low'))) ?>
                                                                        </span>
                                                                    </p>
                                                                    <p><strong>Status:</strong>
                                                                        <span class="status-badge status-<?= htmlspecialchars($ticket['status'] ?? 'open') ?>">
                                                                            <?= htmlspecialchars(ucfirst($ticket['status'] ?? 'open')) ?>
                                                                        </span>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <hr>
                                                            <h6>IT Head's Note (Ticket ID: #<?= htmlspecialchars($ticket['id'] ?? '') ?>):</h6>
                                                            <div class="card bg-light p-3">
                                                                <?= nl2br(htmlspecialchars($ticket['message'] ?? 'No message')) ?>
                                                            </div>
                                                            <?php if (!empty($ticket['original_employee_sender'])): ?>
                                                                <div class="mb-3 original-message-card">
                                                                    <h6>Original Employee Ticket (from <?= htmlspecialchars($ticket['original_employee_sender'] ?? 'Unknown') ?>, ID: <?= htmlspecialchars($ticket['parent_id'] ?? 'NULL') ?>):</h6>
                                                                    <p><strong>Subject:</strong> <?= htmlspecialchars($ticket['original_employee_title'] ?? 'No Title') ?></p>
                                                                    <div class="card bg-white p-3">
                                                                        <?= nl2br(htmlspecialchars($ticket['original_employee_message'] ?? 'No message')) ?>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                            <p class="text-muted">
                                                                <small>Received on <?= htmlspecialchars(date('F j, Y \a\t g:i a', strtotime($ticket['sent_at'] ?? 'now'))) ?></small>
                                                            </p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal fade" id="respondModal<?= htmlspecialchars($ticket['id'] ?? '') ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <form method="POST" action="specialist.php">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Respond to Ticket #<?= htmlspecialchars($ticket['id'] ?? '') ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id'] ?? '') ?>">
                                                                <input type="hidden" name="receiver" value="<?= htmlspecialchars($ticket['sender'] ?? '') ?>">
                                                                <div class="original-message-card mb-4">
                                                                    <h6>IT Head's Request (from <?= htmlspecialchars($ticket['sender'] ?? 'Unknown') ?>):</h6>
                                                                    <p><strong><?= htmlspecialchars($ticket['title'] ?? 'No Title') ?></strong></p>
                                                                    <div class="card bg-light p-3 mb-2">
                                                                        <?= nl2br(htmlspecialchars($ticket['message'] ?? 'No message')) ?>
                                                                    </div>
                                                                    <?php if (!empty($ticket['original_employee_sender'])): ?>
                                                                        <h6 class="mt-3">Employee's Original Ticket (from <?= htmlspecialchars($ticket['original_employee_sender'] ?? 'Unknown') ?>):</h6>
                                                                        <p><strong>Subject:</strong> <?= htmlspecialchars($ticket['original_employee_title'] ?? 'No Title') ?></p>
                                                                        <div class="card bg-white p-3">
                                                                            <?= nl2br(htmlspecialchars($ticket['original_employee_message'] ?? 'No message')) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <p class="text-muted"><small>Received on <?= htmlspecialchars(date('F j, Y \a\t g:i a', strtotime($ticket['sent_at'] ?? 'now'))) ?></small></p>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="message<?= htmlspecialchars($ticket['id'] ?? '') ?>" class="form-label">Your Response to IT Head</label>
                                                                    <textarea class="form-control" id="message<?= htmlspecialchars($ticket['id'] ?? '') ?>" name="message" rows="5" placeholder="Enter your detailed response..." required></textarea>
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
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="responses-tab-pane" role="tabpanel" tabindex="0">
                        <?php if (empty($sentResponses)): ?>
                            <div class="alert alert-info">You have not sent any responses yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ticket ID</th>
                                            <th>Sent To</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Date Sent</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sentResponses as $response): ?>
                                            <tr class="priority-<?= htmlspecialchars(strtolower($response['priority'] ?? 'low')) ?>">
                                                <td>#<?= htmlspecialchars($response['id'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($response['receiver'] ?? 'Unknown') ?></td>
                                                <td><?= htmlspecialchars($response['title'] ?? 'No Title') ?></td>
                                                <td><?= htmlspecialchars(ucfirst($response['category'] ?? 'unknown')) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= htmlspecialchars(strtolower($response['priority'] ?? 'low') === 'high' ? 'danger' : ($response['priority'] ?? 'low' === 'medium' ? 'warning' : 'success')) ?>">
                                                        <?= htmlspecialchars(ucfirst(strtolower($response['priority'] ?? 'low'))) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars(date('M j, Y', strtotime($response['sent_at'] ?? 'now'))) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewResponseModal<?= htmlspecialchars($response['id'] ?? '') ?>">
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="viewResponseModal<?= htmlspecialchars($response['id'] ?? '') ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Response to Ticket #<?= htmlspecialchars($response['parent_id'] ?? 'NULL') ?>: <?= htmlspecialchars($response['title'] ?? 'No Title') ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="original-message-card mb-4">
                                                                <h6>Original Employee Ticket (from <?= htmlspecialchars($response['original_employee_sender'] ?? 'Unknown') ?>):</h6>
                                                                <p><strong>Subject:</strong> <?= htmlspecialchars($response['original_employee_title'] ?? 'No Title') ?></p>
                                                                <div class="card bg-white p-3">
                                                                    <?= nl2br(htmlspecialchars($response['original_employee_message'] ?? 'No message')) ?>
                                                                </div>
                                                            </div>
                                                            <h6>Your Response (to <?= htmlspecialchars($response['receiver'] ?? 'Unknown') ?>):</h6>
                                                            <div class="card bg-light p-3">
                                                                <?= nl2br(htmlspecialchars($response['message'] ?? 'No message')) ?>
                                                            </div>
                                                            <p class="response-meta">
                                                                <small>Sent on <?= htmlspecialchars(date('F j, Y \a\t g:i a', strtotime($response['sent_at'] ?? 'now'))) ?></small>
                                                            </p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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

            // Ensure form submission works within modals
            var responseForms = document.querySelectorAll('form');
            responseForms.forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    var formData = new FormData(form);
                    fetch(form.action, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(() => {
                            form.closest('.modal').querySelector('[data-bs-dismiss="modal"]').click();
                            location.reload();
                        })
                        .catch(error => {
                            console.error('Error submitting form:', error);
                            alert('Failed to send response. Check console for details.');
                        });
                });
            });
        });
    </script>
</body>

</html>