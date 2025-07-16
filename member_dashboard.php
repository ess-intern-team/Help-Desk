<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Demo placeholders
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Seid Hussen';
    $_SESSION['role'] = 'Admin';
}
require_once 'db_connect.php';
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$department = $_SESSION['department'];

$error = null;
$success = null;
$my_requests = [];

// Handle new request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'Low';

    if (empty($title) || empty($description)) {
        $error = "Title and description cannot be empty.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO requests (user_id, department, title, description, priority) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $department, $title, $description, $priority]);
            $success = "Your request has been submitted successfully!";

            // Optionally, notify the department head
            $head_stmt = $pdo->prepare("SELECT id FROM users WHERE department = ? AND role = 'head'");
            $head_stmt->execute([$department]);
            $department_heads = $head_stmt->fetchAll(PDO::FETCH_COLUMN);

            if ($department_heads) {
                $request_id = $pdo->lastInsertId();
                foreach ($department_heads as $head_id) {
                    $notif_msg = "New pending request from " . htmlspecialchars($full_name) . " (ID: " . $request_id . ").";
                    $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, request_id, message) VALUES (?, ?, ?)");
                    $notif_stmt->execute([$head_id, $request_id, $notif_msg]);
                }
            }

            // PRG Pattern
            header('Location: ' . $_SERVER['PHP_SELF'] . '?success=' . urlencode($success));
            exit();
        } catch (PDOException $e) {
            $error = "Error submitting request: " . $e->getMessage();
            error_log("Member Dashboard DB Error (Submit Request): " . $e->getMessage());
        }
    }
}

// Fetch member's requests
try {
    $stmt = $pdo->prepare("SELECT r.*,
                                  COALESCE(sh.full_name, 'N/A') as specialist_name,
                                  COALESCE(bh.full_name, 'N/A') as head_forwarder_name
                           FROM requests r
                           LEFT JOIN users sh ON r.specialist_id = sh.id
                           LEFT JOIN users bh ON r.forwarded_by = bh.id
                           WHERE r.user_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$user_id]);
    $my_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching your requests: " . $e->getMessage();
    error_log("Member Dashboard DB Error (Fetch Requests): " . $e->getMessage());
}

// Handle GET success message
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($full_name) ?> - Member Dashboard</title>
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

        .badge-in_progress {
            background-color: #fff3cd;
            color: #664d03;
        }

        .badge-completed {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-closed {
            background-color: #e2e8f0;
            color: #475569;
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
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-4 text-center border-bottom">
                    <h4 class="mb-0">
                        <i class="fas fa-headset me-2"></i>Helpdesk
                    </h4>
                    <small class="text-muted">Member Panel</small>
                </div>
                <div class="p-4 text-center border-bottom">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($full_name) ?>&background=3f37c9&color=fff"
                        alt="User" class="rounded-circle mb-2" width="80">
                    <h6 class="mb-0"><?= htmlspecialchars($full_name) ?></h6>
                    <small class="text-muted"><?= htmlspecialchars($department) ?> Department</small>
                </div>
                <nav class="nav flex-column p-3">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-home me-2"></i> Dashboard
                    </a>
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </nav>
            </div>

            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>
                        <i class="fas fa-tachometer-alt me-2"></i> My Dashboard
                    </h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                        <i class="fas fa-plus-circle me-1"></i> New Request
                    </button>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i> My Requests
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($my_requests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Submitted On</th>
                                            <th>Specialist</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($my_requests as $req): ?>
                                            <tr class="priority-<?= strtolower($req['priority']) ?>">
                                                <td>#<?= htmlspecialchars($req['id']) ?></td>
                                                <td><?= htmlspecialchars($req['title']) ?></td>
                                                <td><?= htmlspecialchars($req['priority']) ?></td>
                                                <td><span class="badge badge-<?= strtolower($req['status']) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $req['status']))) ?></span></td>
                                                <td><?= date('M j, Y g:i a', strtotime($req['created_at'])) ?></td>
                                                <td><?= htmlspecialchars($req['specialist_name'] ?? 'N/A') ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewRequestModal"
                                                        data-id="<?= htmlspecialchars($req['id']) ?>"
                                                        data-title="<?= htmlspecialchars($req['title']) ?>"
                                                        data-description="<?= htmlspecialchars($req['description']) ?>"
                                                        data-priority="<?= htmlspecialchars($req['priority']) ?>"
                                                        data-status="<?= htmlspecialchars($req['status']) ?>"
                                                        data-created-at="<?= date('M j, Y g:i a', strtotime($req['created_at'])) ?>"
                                                        data-forwarded-at="<?= $req['forwarded_at'] ? date('M j, Y g:i a', strtotime($req['forwarded_at'])) : 'N/A' ?>"
                                                        data-forwarded-by="<?= htmlspecialchars($req['head_forwarder_name'] ?? 'N/A') ?>"
                                                        data-specialist-name="<?= htmlspecialchars($req['specialist_name'] ?? 'N/A') ?>"
                                                        data-head-notes="<?= htmlspecialchars($req['head_notes'] ?? 'No notes from Department Head.') ?>"
                                                        data-specialist-response="<?= htmlspecialchars($req['specialist_response'] ?? 'No response from specialist yet.') ?>"
                                                        data-response-to-member="<?= htmlspecialchars($req['response_to_member'] ?? 'No final response yet.') ?>">
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
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Requests Submitted Yet</h5>
                                <p class="text-muted">Click "New Request" to get started.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newRequestModal" tabindex="-1" aria-labelledby="newRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newRequestModalLabel">Submit New Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="requestTitle" class="form-label">Request Title</label>
                            <input type="text" class="form-control" id="requestTitle" name="title" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label for="requestDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="requestDescription" name="description" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="requestPriority" class="form-label">Priority</label>
                            <select class="form-select" id="requestPriority" name="priority">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submit_request" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewRequestModal" tabindex="-1" aria-labelledby="viewRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewRequestModalLabel">Request Details #<span id="viewRequestId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4 id="viewRequestTitle"></h4>
                    <p class="text-muted"><small>Submitted on: <span id="viewRequestCreatedAt"></span></small></p>
                    <p><strong class="me-2">Priority:</strong> <span id="viewRequestPriority"></span></p>
                    <p><strong class="me-2">Current Status:</strong> <span class="badge" id="viewRequestStatus"></span></p>
                    <hr>
                    <h6>Description:</h6>
                    <p id="viewRequestDescription"></p>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Forwarded Details:</h6>
                            <p><small>Forwarded By: <span id="viewForwardedBy"></span></small></p>
                            <p><small>Forwarded At: <span id="viewForwardedAt"></span></small></p>
                            <p><small>Assigned Specialist: <span id="viewSpecialistName"></span></small></p>
                            <p><small>Notes from Head: <span id="viewHeadNotes" class="text-muted fst-italic"></span></small></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Specialist's Response:</h6>
                            <p id="viewSpecialistResponse" class="text-muted fst-italic"></p>
                        </div>
                    </div>
                    <hr>
                    <h6>Final Response to You:</h6>
                    <p id="viewResponseToMember" class="text-success fw-bold"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const viewRequestModal = document.getElementById('viewRequestModal');
            if (viewRequestModal) {
                viewRequestModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget; // Button that triggered the modal
                    document.getElementById('viewRequestId').textContent = button.getAttribute('data-id');
                    document.getElementById('viewRequestTitle').textContent = button.getAttribute('data-title');
                    document.getElementById('viewRequestCreatedAt').textContent = button.getAttribute('data-created-at');
                    document.getElementById('viewRequestPriority').textContent = button.getAttribute('data-priority');
                    document.getElementById('viewRequestDescription').textContent = button.getAttribute('data-description');

                    const statusSpan = document.getElementById('viewRequestStatus');
                    const status = button.getAttribute('data-status');
                    statusSpan.textContent = status.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
                    statusSpan.className = `badge badge-${status.toLowerCase()}`;

                    document.getElementById('viewForwardedBy').textContent = button.getAttribute('data-forwarded-by');
                    document.getElementById('viewForwardedAt').textContent = button.getAttribute('data-forwarded-at');
                    document.getElementById('viewSpecialistName').textContent = button.getAttribute('data-specialist-name');
                    document.getElementById('viewHeadNotes').textContent = button.getAttribute('data-head-notes');
                    document.getElementById('viewSpecialistResponse').textContent = button.getAttribute('data-specialist-response');
                    document.getElementById('viewResponseToMember').textContent = button.getAttribute('data-response-to-member');
                });
            }
        });
    </script>
</body>

</html>