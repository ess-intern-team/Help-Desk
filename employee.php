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
        $_SESSION['displayMessage'] = "You must be logged in to access this page.";
        $_SESSION['messageType'] = "danger";
        redirect('login.php');
    }
    if ($requiredRole && $_SESSION['role'] !== $requiredRole) {
        $_SESSION['displayMessage'] = "You do not have permission to access this page. Your role: " . ucfirst($_SESSION['role']);
        $_SESSION['messageType'] = "danger";
        redirect('index.php');
    }
}

requireLogin('employee');
$currentUser = $_SESSION['username'];
$currentRole = $_SESSION['role'];
$conn = getDbConnection();

// Get a valid IT Head for ticket submission
$sql = "SELECT username FROM users WHERE role = 'ithead' LIMIT 1";
$result = $conn->query($sql);
$itHead = $result && $result->num_rows > 0 ? $result->fetch_assoc()['username'] : null;
if (!$itHead) {
    error_log("No IT Head found in the database for employee ticket submission.");
    $_SESSION['displayMessage'] = "No IT Head available to receive tickets. Please contact the administrator.";
    $_SESSION['messageType'] = "danger";
    redirect('employee.php');
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_ticket'])) {
    try {
        $requiredFields = ['title', 'message', 'category', 'priority'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("All fields are required.");
            }
        }

        $title = htmlspecialchars($_POST['title']);
        $message = htmlspecialchars($_POST['message']);
        $category = htmlspecialchars($_POST['category']);
        $priority = htmlspecialchars($_POST['priority']);
        $receiver = $itHead;

        $allowedCategories = ['hardware', 'software', 'network', 'account', 'other'];
        $allowedPriorities = ['high', 'medium', 'low'];
        if (!in_array(strtolower($category), $allowedCategories)) {
            throw new Exception("Invalid category selected.");
        }
        if (!in_array(strtolower($priority), $allowedPriorities)) {
            throw new Exception("Invalid priority selected.");
        }

        $sql = "INSERT INTO messages (sender, receiver, role_from, role_to, title, message, category, priority, status)
                VALUES (?, ?, 'employee', 'ithead', ?, ?, ?, ?, 'open')";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("ssssss", $currentUser, $receiver, $title, $message, $category, $priority);
        if ($stmt->execute()) {
            $_SESSION['displayMessage'] = "Your ticket has been submitted successfully! Ticket ID: " . $conn->insert_id;
            $_SESSION['messageType'] = "success";
        } else {
            throw new Exception("Failed to submit ticket: " . $stmt->error);
        }
        $stmt->close();
        redirect('employee.php');
    } catch (Exception $e) {
        error_log("Ticket submission error for employee " . $currentUser . ": " . $e->getMessage());
        $_SESSION['displayMessage'] = $e->getMessage();
        $_SESSION['messageType'] = "danger";
        redirect('employee.php');
    }
}

// Fetch Tickets sent by current Employee
$myTickets = [];
$sql = "SELECT m.*,
            resp.message as response_message,
            resp.sent_at as response_sent_at,
            resp.sender as response_sender,
            resp.status as response_status
        FROM messages m
        LEFT JOIN messages resp ON m.id = resp.parent_id AND resp.role_from = 'ithead' AND resp.role_to = 'employee'
        WHERE m.sender = ? AND m.role_from = 'employee' AND m.parent_id IS NULL
        ORDER BY m.sent_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $currentUser);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $myTickets[] = $row;
    }
    $stmt->close();
} else {
    error_log("Error fetching employee tickets: " . $conn->error);
}

// Fetch responses received from IT Head
$receivedResponses = [];
$sql = "SELECT m.*,
            parent.sender as original_ticket_sender,
            parent.title as original_ticket_title,
            parent.message as original_ticket_message,
            parent.status as original_ticket_status
        FROM messages m
        JOIN messages parent ON m.parent_id = parent.id
        WHERE m.receiver = ? AND m.role_from = 'ithead' AND m.role_to = 'employee'
        ORDER BY m.sent_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $currentUser);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $receivedResponses[] = $row;
    }
    $stmt->close();
} else {
    error_log("Error fetching received responses for employee: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal</title>
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

        .status-closed {
            background-color: #e2e3e5;
            color: #6c757d;
        }

        .status-forwarded {
            background-color: #fff3cd;
            color: #856404;
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

        .original-ticket-info {
            background-color: #e9ecef;
            border-left: 4px solid #0d6efd;
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
                    <h1 class="display-5 fw-bold text-primary"><i class="bi bi-person-fill"></i> Employee Portal</h1>
                    <p class="lead">Welcome, <?= htmlspecialchars($currentUser) ?> (<?= ucfirst($currentRole) ?>)</p>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                </div>

                <?php displayMessage(); ?>

                <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="create-tab" data-bs-toggle="tab" data-bs-target="#create-tab-pane" type="button" role="tab" aria-controls="create-tab-pane" aria-selected="true">
                            <i class="bi bi-plus-circle"></i> Create New Ticket
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="my-tickets-tab" data-bs-toggle="tab" data-bs-target="#my-tickets-tab-pane" type="button" role="tab" aria-controls="my-tickets-tab-pane" aria-selected="false">
                            <i class="bi bi-ticket-perforated"></i> Your Submitted Tickets (<?= count($myTickets) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="responses-tab" data-bs-toggle="tab" data-bs-target="#responses-tab-pane" type="button" role="tab" aria-controls="responses-tab-pane" aria-selected="false">
                            <i class="bi bi-chat-left-text"></i> IT Head Responses (<?= count($receivedResponses) ?>)
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="create-tab-pane" role="tabpanel" aria-labelledby="create-tab" tabindex="0">
                        <div class="card p-4">
                            <h4 class="mb-4">Submit a New Support Ticket</h4>
                            <form action="" method="POST">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Subject / Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required placeholder="e.g., Internet not working">
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Description of Issue</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Provide a detailed description..."></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="category" class="form-label">Category</label>
                                        <select class="form-select" id="category" name="category" required>
                                            <option value="">Select Category</option>
                                            <option value="hardware">Hardware</option>
                                            <option value="software">Software</option>
                                            <option value="network">Network</option>
                                            <option value="account">Account</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="priority" class="form-label">Priority</label>
                                        <select class="form-select" id="priority" name="priority" required>
                                            <option value="">Select Priority</option>
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" name="submit_ticket" class="btn btn-primary mt-3">
                                    <i class="bi bi-send"></i> Submit Ticket
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="my-tickets-tab-pane" role="tabpanel" aria-labelledby="my-tickets-tab" tabindex="0">
                        <?php if (empty($myTickets)): ?>
                            <div class="alert alert-info">You have not submitted any tickets yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ticket ID</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Date Submitted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($myTickets as $ticket): ?>
                                            <tr class="<?= 'priority-' . strtolower($ticket['priority']) ?>">
                                                <td>#<?= $ticket['id'] ?></td>
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
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewTicketModal<?= $ticket['id'] ?>">
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="viewTicketModal<?= $ticket['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Ticket #<?= $ticket['id'] ?>: <?= htmlspecialchars($ticket['title']) ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>Status:</strong> <span class="status-badge status-<?= $ticket['status'] ?>"><?= ucfirst($ticket['status']) ?></span></p>
                                                            <p><strong>Category:</strong> <?= ucfirst($ticket['category']) ?></p>
                                                            <p><strong>Priority:</strong>
                                                                <span class="badge bg-<?= strtolower($ticket['priority']) === 'high' ? 'danger' : (strtolower($ticket['priority']) === 'medium' ? 'warning' : 'success') ?>">
                                                                    <?= ucfirst(strtolower($ticket['priority'])) ?>
                                                                </span>
                                                            </p>
                                                            <hr>
                                                            <h6>Your Message:</h6>
                                                            <div class="card bg-light p-3 mb-3">
                                                                <?= nl2br(htmlspecialchars($ticket['message'])) ?>
                                                            </div>
                                                            <?php if (!empty($ticket['response_message'])): ?>
                                                                <h6>IT Head Response (<?= htmlspecialchars($ticket['response_sender']) ?> on <?= date('M j, Y \a\t g:i a', strtotime($ticket['response_sent_at'])) ?>):</h6>
                                                                <div class="card bg-light p-3">
                                                                    <?= nl2br(htmlspecialchars($ticket['response_message'])) ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="alert alert-info">No response from IT Head yet.</div>
                                                            <?php endif; ?>
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

                    <div class="tab-pane fade" id="responses-tab-pane" role="tabpanel" aria-labelledby="responses-tab" tabindex="0">
                        <?php if (empty($receivedResponses)): ?>
                            <div class="alert alert-info">No direct responses from IT Heads yet.</div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($receivedResponses as $response): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-info text-dark">
                                                <h6 class="mb-0">Response for Your Ticket #<?= $response['parent_id'] ?></h6>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text"><strong>From IT Head:</strong> <?= htmlspecialchars($response['sender']) ?></p>
                                                <p class="card-text"><strong>Response Subject:</strong> <?= htmlspecialchars($response['title']) ?></p>
                                                <p class="card-text">
                                                    <?= nl2br(htmlspecialchars(substr($response['message'], 0, 150))) ?>...
                                                </p>
                                                <small class="text-muted">Received on <?= date('M j, Y g:i a', strtotime($response['sent_at'])) ?></small>
                                                <button class="btn btn-sm btn-outline-primary float-end" data-bs-toggle="modal" data-bs-target="#viewResponseModal<?= $response['id'] ?>">
                                                    View Details
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="viewResponseModal<?= $response['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Response Details for Ticket #<?= $response['parent_id'] ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="original-ticket-info mb-3">
                                                        <h6>Your Original Ticket (ID: <?= $response['parent_id'] ?>):</h6>
                                                        <p><strong>Title:</strong> <?= htmlspecialchars($response['original_ticket_title']) ?></p>
                                                        <p><strong>Message:</strong></p>
                                                        <div class="card bg-white p-3">
                                                            <?= nl2br(htmlspecialchars($response['original_ticket_message'])) ?>
                                                        </div>
                                                        <p class="mt-2 text-muted"><small>Status: <?= ucfirst($response['original_ticket_status']) ?></small></p>
                                                    </div>
                                                    <h6>IT Head's Response (from <?= htmlspecialchars($response['sender']) ?>):</h6>
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