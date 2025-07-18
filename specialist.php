<?php
// specialist.php
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


requireLogin('specialist'); // Ensure only specialists can access this page

// User Context from session
$currentUser = $_SESSION['username'];
$currentRole = $_SESSION['role'];

$conn = getDbConnection(); // Get the database connection

// Handle Messages from Session (PRG pattern)
displayMessage(); // Use the helper function

// Handle Form Submission (Respond to IT Head)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_response'])) {
    try {
        // Validate inputs
        $requiredFields = ['ticket_id', 'receiver', 'message'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("All fields are required.");
            }
        }

        $ticketId = (int)$_POST['ticket_id']; // This is the ID of the IT Head's forwarded message
        $receiver = htmlspecialchars($_POST['receiver']); // This is the IT Head's username
        $message = htmlspecialchars($_POST['message']);

        // Get the details of the IT Head's forwarded message (which has parent_id linking to original employee ticket)
        $sql = "SELECT category, priority, title, parent_id FROM messages WHERE id = ? AND receiver = ? AND role_from = 'ithead' AND role_to = 'specialist'";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt->bind_param("is", $ticketId, $currentUser);
        $stmt->execute();
        $result = $stmt->get_result();
        $itHeadForwardedTicket = $result->fetch_assoc();
        $stmt->close();

        if (!$itHeadForwardedTicket) {
            throw new Exception("Ticket not found or you don't have permission to respond.");
        }

        // The specialist's response's parent_id should be the ID of the *original employee ticket*
        $originalEmployeeTicketId = $itHeadForwardedTicket['parent_id'];
        if (!$originalEmployeeTicketId) {
            throw new Exception("Could not find original employee ticket linked to this forwarded request.");
        }

        // Validate category and priority (taken from the forwarded ticket for consistency)
        $allowedCategories = ['hardware', 'software', 'network', 'account', 'other'];
        $allowedPriorities = ['high', 'medium', 'low'];
        $category = strtolower($itHeadForwardedTicket['category']);
        $priority = strtolower($itHeadForwardedTicket['priority']);
        if (!in_array($category, $allowedCategories)) {
            throw new Exception("Invalid category value: " . $itHeadForwardedTicket['category']);
        }
        if (!in_array($priority, $allowedPriorities)) {
            throw new Exception("Invalid priority value: " . $itHeadForwardedTicket['priority']);
        }

        // Create response message to IT Head
        $sql = "INSERT INTO messages (sender, receiver, role_from, role_to, title, message, category, priority, parent_id, status)
                 VALUES (?, ?, 'specialist', 'ithead', ?, ?, ?, ?, ?, 'responded')";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $title = "Specialist Response: " . $itHeadForwardedTicket['title']; // Use forwarded ticket's title
        $stmt->bind_param("ssssssi", $currentUser, $receiver, $title, $message, $category, $priority, $originalEmployeeTicketId);

        if ($stmt->execute()) {
            // Update the status of the IT Head's *forwarded* message to 'responded'
            $updateSql = "UPDATE messages SET status = 'responded' WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("i", $ticketId); // Update the forwarded message, not the original employee one
            $updateStmt->execute();
            $updateStmt->close();

            $_SESSION['displayMessage'] = "Response sent to " . htmlspecialchars($receiver) . " successfully!";
            $_SESSION['messageType'] = "success";
        } else {
            throw new Exception("Failed to send response: " . $stmt->error);
        }

        $stmt->close();
        redirect('specialist.php'); // PRG pattern
    } catch (Exception $e) {
        error_log("Response error for specialist " . $currentUser . ": " . $e->getMessage());
        $_SESSION['displayMessage'] = $e->getMessage();
        $_SESSION['messageType'] = "danger";
        redirect('specialist.php'); // Redirect with error message
    }
}

// Fetch Tickets from IT Heads assigned to this specialist
$itHeadTickets = [];
// This query fetches messages where the current specialist is the receiver, and the sender is an IT Head.
// It also joins with the original employee ticket using the parent_id to get full context.
$sql = "SELECT m.*,
            original_emp_ticket.sender as original_employee_sender,
            original_emp_ticket.message as original_employee_message,
            original_emp_ticket.title as original_employee_title
         FROM messages m
         LEFT JOIN messages original_emp_ticket ON m.parent_id = original_emp_ticket.id AND original_emp_ticket.role_from = 'employee'
         WHERE m.receiver = ? AND m.role_from = 'ithead' AND m.role_to = 'specialist'
         ORDER BY m.sent_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $currentUser);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $itHeadTickets[] = $row;
    }
    $stmt->close();
} else {
    error_log("Error fetching IT Head tickets for specialist " . $currentUser . ": " . $conn->error);
}

// Fetch Responses sent to IT Heads by this specialist
$sentResponses = [];
// This query fetches messages where the current specialist is the sender, and the receiver is an IT Head.
// It joins with the original employee ticket via the parent_id to show context of the response.
$sql = "SELECT m.*,
            original_emp_ticket.sender as original_employee_sender,
            original_emp_ticket.title as original_employee_title,
            original_emp_ticket.message as original_employee_message
         FROM messages m
         JOIN messages original_emp_ticket ON m.parent_id = original_emp_ticket.id AND original_emp_ticket.role_from = 'employee'
         WHERE m.sender = ? AND m.role_from = 'specialist' AND m.role_to = 'ithead'
         ORDER BY m.sent_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $currentUser);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sentResponses[] = $row;
    }
    $stmt->close();
} else {
    error_log("Error fetching sent responses by specialist " . $currentUser . ": " . $conn->error);
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
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold text-primary">
                        <i class="bi bi-gear-fill"></i> Specialist Portal
                    </h1>
                    <p class="lead">Welcome, <?= htmlspecialchars($currentUser) ?> (<?= ucfirst($currentRole) ?>)</p>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                </div>

                <?php displayMessage(); ?>

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
                                                <td><?= date('M j, Y', strtotime($ticket['sent_at'])) ?></td>
                                                <td>
                                                    <span class="status-badge status-<?= $ticket['status'] ?>">
                                                        <?= ucfirst($ticket['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?= $ticket['id'] ?>">
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                    <?php if ($ticket['status'] === 'forwarded'): // Only respond to tickets that are "forwarded" (not already "responded") 
                                                    ?>
                                                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#respondModal<?= $ticket['id'] ?>">
                                                            <i class="bi bi-reply"></i> Respond
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="viewModal<?= $ticket['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Ticket #<?= $ticket['id'] ?>: <?= htmlspecialchars($ticket['title']) ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <p><strong>From IT Head:</strong> <?= htmlspecialchars($ticket['sender']) ?></p>
                                                                    <p><strong>Category:</strong> <?= ucfirst($ticket['category']) ?></p>
                                                                </div>
                                                                <div class="col-md-6">
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
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <h6>IT Head's Note (Ticket ID: #<?= $ticket['id'] ?>):</h6>
                                                                <div class="card bg-light p-3">
                                                                    <?= nl2br(htmlspecialchars($ticket['message'])) ?>
                                                                </div>
                                                            </div>

                                                            <?php if (!empty($ticket['original_employee_sender'])): ?>
                                                                <div class="mb-3 original-message-card">
                                                                    <h6>Original Employee Ticket (from <?= htmlspecialchars($ticket['original_employee_sender']) ?>, ID: <?= $ticket['parent_id'] ?>):</h6>
                                                                    <p><strong>Subject:</strong> <?= htmlspecialchars($ticket['original_employee_title']) ?></p>
                                                                    <div class="card bg-white p-3">
                                                                        <?= nl2br(htmlspecialchars($ticket['original_employee_message'])) ?>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>

                                                            <div class="mb-3">
                                                                <p class="text-muted">
                                                                    <small>Received on <?= date('F j, Y \a\t g:i a', strtotime($ticket['sent_at'])) ?></small>
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal fade" id="respondModal<?= $ticket['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <form method="POST" action="">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Respond to Ticket #<?= $ticket['id'] ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                                                <input type="hidden" name="receiver" value="<?= htmlspecialchars($ticket['sender']) ?>">

                                                                <div class="original-message-card mb-4">
                                                                    <h6>IT Head's Request (from <?= htmlspecialchars($ticket['sender']) ?>):</h6>
                                                                    <p><strong><?= htmlspecialchars($ticket['title']) ?></strong></p>
                                                                    <div class="card bg-light p-3 mb-2">
                                                                        <?= nl2br(htmlspecialchars($ticket['message'])) ?>
                                                                    </div>
                                                                    <?php if (!empty($ticket['original_employee_sender'])): ?>
                                                                        <h6 class="mt-3">Employee's Original Ticket (from <?= htmlspecialchars($ticket['original_employee_sender']) ?>):</h6>
                                                                        <p><strong><?= htmlspecialchars($ticket['original_employee_title']) ?></p>
                                                                        <div class="card bg-light p-3 mb-2">
                                                                            <?= nl2br(htmlspecialchars($ticket['original_employee_message'])) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <p class="response-meta">
                                                                        <small>Received on <?= date('F j, Y \a\t g:i a', strtotime($ticket['sent_at'])) ?></small>
                                                                    </p>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label for="message<?= $ticket['id'] ?>" class="form-label">Your Response</label>
                                                                    <textarea class="form-control" id="message<?= $ticket['id'] ?>" name="message" rows="5" placeholder="Enter your detailed response to the IT Head..." required></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="submit_response" class="btn btn-primary">
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
                            <div class="alert alert-info">You haven't sent any responses yet.</div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($sentResponses as $response): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-success text-white">
                                                <h6 class="mb-0">Response to <?= htmlspecialchars($response['receiver']) ?> for Ticket ID: <?= $response['parent_id'] ?></h6>
                                            </div>
                                            <div class="card-body">
                                                <h5 class="card-title"><?= htmlspecialchars($response['title']) ?></h5>

                                                <div class="original-message-card mb-3">
                                                    <p><strong>Original Employee Ticket (from <?= htmlspecialchars($response['original_employee_sender']) ?>):</strong> <?= htmlspecialchars($response['original_employee_title']) ?></p>
                                                    <div class="card bg-light p-2">
                                                        <?= nl2br(htmlspecialchars(substr($response['original_employee_message'], 0, 150))) ?>...
                                                    </div>
                                                </div>

                                                <div class="card-text mb-3">
                                                    <?= nl2br(htmlspecialchars(substr($response['message'], 0, 150))) ?>...
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <?= date('M j, Y g:i a', strtotime($response['sent_at'])) ?>
                                                    </small>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#responseModal<?= $response['id'] ?>">
                                                        Details
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="responseModal<?= $response['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Your Response to <?= htmlspecialchars($response['receiver']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="original-message-card mb-4">
                                                        <h6>Original Employee Ticket (ID: <?= $response['parent_id'] ?>) from <?= htmlspecialchars($response['original_employee_sender']) ?>:</h6>
                                                        <p><strong><?= htmlspecialchars($response['original_employee_title']) ?></strong></p>
                                                        <div class="card bg-light p-3">
                                                            <?= nl2br(htmlspecialchars($response['original_employee_message'])) ?>
                                                        </div>
                                                    </div>

                                                    <div class="mb-4">
                                                        <h6>Your Response:</h6>
                                                        <div class="card bg-light p-3">
                                                            <?= nl2br(htmlspecialchars($response['message'])) ?>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <p class="text-muted">
                                                            <small>Sent on <?= date('F j, Y \a\t g:i a', strtotime($response['sent_at'])) ?></small>
                                                        </p>
                                                    </div>
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