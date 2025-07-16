<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Demo placeholders
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Seid Hussen';
    $_SESSION['role'] = 'Admin';
}

require_once 'db_connect.php';

// These variables are now guaranteed to be set due to the strict session check above
$specialist_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$error = null;
$success = null;
$assigned_requests = [];

// --- Handle POST Requests (Form Submissions) ---

// Handle Specialist updating status or providing response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_request_status'])) {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $new_status = trim($_POST['new_status'] ?? '');
    $specialist_response = trim($_POST['specialist_response'] ?? '');

    // Validate inputs
    if ($request_id === false || $request_id === null || !in_array($new_status, ['in_progress', 'completed'])) {
        $error = "Invalid request or status.";
    } elseif ($new_status === 'completed' && empty($specialist_response)) {
        $error = "Response is required when marking as 'Completed'.";
    } else {
        try {
            $pdo->beginTransaction();

            $update_query = "UPDATE requests SET status = ?, updated_at = NOW() ";
            $params = [$new_status];

            if ($new_status === 'completed') {
                $update_query .= ", specialist_response = ?, completed_at = NOW() ";
                $params[] = $specialist_response;
            } else {
                // If status is 'in_progress' and it was 'completed' before, clear response
                // This scenario might need more thought if 'in_progress' is chosen after 'completed'
                // For simplicity, we'll just update status and leave response if already there for 'in_progress'
            }

            $update_query .= "WHERE id = ? AND specialist_id = ?";
            $params[] = $request_id;
            $params[] = $specialist_id;

            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute($params);

            if ($update_stmt->rowCount() > 0) {
                // Notify the Department Head
                $req_info_stmt = $pdo->prepare("SELECT user_id, title, department, forwarded_by FROM requests WHERE id = ?");
                $req_info_stmt->execute([$request_id]);
                $req_info = $req_info_stmt->fetch(PDO::FETCH_ASSOC);

                $member_id = $req_info['user_id'];
                $department_of_request = $req_info['department'];
                $request_title = $req_info['title'];
                $forwarded_by_head_id = $req_info['forwarded_by']; // The actual head who forwarded it

                if ($new_status === 'completed') {
                    $notif_msg = "Request '" . htmlspecialchars($request_title) . "' (ID: " . $request_id . ") has been resolved by " . htmlspecialchars($full_name) . " and is awaiting your review.";
                    if ($forwarded_by_head_id) {
                        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, request_id, message) VALUES (?, ?, ?)");
                        $notif_stmt->execute([$forwarded_by_head_id, $request_id, $notif_msg]);
                    } else {
                        // Fallback: Notify all heads in that department if forwarded_by is null (unlikely with FK)
                        $head_stmt = $pdo->prepare("SELECT id FROM users WHERE department = ? AND role = 'head'");
                        $head_stmt->execute([$department_of_request]);
                        $department_heads = $head_stmt->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($department_heads as $head_user_id) {
                            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, request_id, message) VALUES (?, ?, ?)");
                            $notif_stmt->execute([$head_user_id, $request_id, $notif_msg]);
                        }
                    }
                } else { // status changed to 'in_progress'
                    // Could notify head, but often just for completed status
                }

                $pdo->commit();
                $success = "Request #" . $request_id . " status updated to '" . htmlspecialchars(str_replace('_', ' ', $new_status)) . "'!";
                // Redirect to self to clear POST data and show success message
                header('Location: ' . $_SERVER['PHP_SELF'] . '?success=' . urlencode($success));
                exit();
            } else {
                $pdo->rollBack();
                $error = "Failed to update request status. It might not be assigned to you or its status has changed.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error updating request: " . $e->getMessage();
            error_log("IT Specialist DB Error (Update Request): " . $e->getMessage());
        }
    }
}


// --- Fetch Data for Dashboard ---

// Fetch requests assigned to this specialist
try {
    // IMPORTANT: If you are seeing "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'u.full_name'",
    // it means your 'users' table in the 'helpdesk_db' database is missing the 'full_name' column or it's misspelled.
    // The query below is CORRECT for a database that has the 'full_name' column.
    // You MUST fix your database schema as described above (using phpMyAdmin or SQL client).
    $stmt = $pdo->prepare("SELECT r.*, u.full_name as member_name, h.full_name as head_forwarder_name
                           FROM requests r
                           JOIN users u ON r.user_id = u.id
                           LEFT JOIN users h ON r.forwarded_by = h.id
                           WHERE r.specialist_id = ? AND r.status IN ('forwarded', 'in_progress', 'completed')
                           ORDER BY r.status ASC, r.forwarded_at DESC");
    $stmt->execute([$specialist_id]);
    $assigned_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching assigned requests: " . $e->getMessage();
    error_log("IT Specialist DB Error (Fetch Assigned Requests): " . $e->getMessage());
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
    <title><?= htmlspecialchars($full_name ?? 'IT Specialist') ?> - IT Specialist Dashboard</title>
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

        .table tr.status-completed {
            background-color: #e0ffe0;
        }

        /* Highlight rows that are completed */
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-4 text-center border-bottom">
                    <h4 class="mb-0">
                        <i class="fas fa-tools me-2"></i>IT Support
                    </h4>
                    <small class="text-muted">Specialist Panel</small>
                </div>
                <div class="p-4 text-center border-bottom">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($full_name ?? 'IT Specialist') ?>&background=3f37c9&color=fff"
                        alt="User" class="rounded-circle mb-2" width="80">
                    <h6 class="mb-0"><?= htmlspecialchars($full_name ?? 'IT Specialist') ?></h6>
                    <small class="text-muted">IT Specialist</small>
                </div>
                <nav class="nav flex-column p-3">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </nav>
            </div>

            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>
                        <i class="fas fa-clipboard-list me-2"></i> Assigned Requests
                    </h3>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-tasks me-2"></i> Your Assigned Tasks
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($assigned_requests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Member</th>
                                            <th>Department</th>
                                            <th>Title</th>
                                            <th>Status</th>
                                            <th>Forwarded By</th>
                                            <th>Forwarded On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assigned_requests as $req): ?>
                                            <tr class="status-<?= strtolower($req['status']) ?>">
                                                <td>#<?= htmlspecialchars($req['id']) ?></td>
                                                <td><?= htmlspecialchars($req['member_name']) ?></td>
                                                <td><?= htmlspecialchars($req['department']) ?></td>
                                                <td><?= htmlspecialchars($req['title']) ?></td>
                                                <td><span class="badge badge-<?= strtolower($req['status']) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $req['status']))) ?></span></td>
                                                <td><?= htmlspecialchars($req['head_forwarder_name'] ?? 'N/A') ?></td>
                                                <td><?= date('M j, Y g:i a', strtotime($req['forwarded_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#updateStatusModal"
                                                        data-request-id="<?= htmlspecialchars($req['id']) ?>"
                                                        data-request-title="<?= htmlspecialchars($req['title']) ?>"
                                                        data-current-status="<?= htmlspecialchars($req['status']) ?>"
                                                        data-specialist-response="<?= htmlspecialchars($req['specialist_response'] ?? '') ?>">
                                                        <i class="fas fa-edit me-1"></i> Update Status
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewRequestModal"
                                                        data-id="<?= htmlspecialchars($req['id']) ?>"
                                                        data-title="<?= htmlspecialchars($req['title']) ?>"
                                                        data-description="<?= htmlspecialchars($req['description']) ?>"
                                                        data-priority="<?= htmlspecialchars($req['priority']) ?>"
                                                        data-status="<?= htmlspecialchars($req['status']) ?>"
                                                        data-created-at="<?= date('M j, Y g:i a', strtotime($req['created_at'])) ?>"
                                                        data-member-name="<?= htmlspecialchars($req['member_name']) ?>"
                                                        data-forwarded-at="<?= $req['forwarded_at'] ? date('M j, Y g:i a', strtotime($req['forwarded_at'])) : 'N/A' ?>"
                                                        data-forwarded-by="<?= htmlspecialchars($req['head_forwarder_name'] ?? 'N/A') ?>"
                                                        data-specialist-name="<?= htmlspecialchars($full_name) ?>"
                                                        data-head-notes="<?= htmlspecialchars($req['head_notes'] ?? 'No notes from Department Head.') ?>"
                                                        data-specialist-response="<?= htmlspecialchars($req['specialist_response'] ?? 'No response yet.') ?>"
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
                                <i class="fas fa-hourglass-half fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Requests Assigned To You</h5>
                                <p class="text-muted">New requests will appear here after being forwarded by a Department Head.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="request_id" id="updateStatusRequestId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateStatusModalLabel">Update Request Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Request Title:</strong> <span id="updateStatusRequestTitle"></span></p>
                        <div class="mb-3">
                            <label for="newStatusSelect" class="form-label">New Status</label>
                            <select class="form-select" id="newStatusSelect" name="new_status" required>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed (Send Response)</option>
                            </select>
                        </div>
                        <div class="mb-3" id="specialistResponseGroup" style="display: none;">
                            <label for="specialistResponseTextarea" class="form-label">Your Resolution / Response to Department Head</label>
                            <textarea class="form-control" id="specialistResponseTextarea" name="specialist_response" rows="5"
                                placeholder="Describe the resolution or actions taken..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_request_status" class="btn btn-primary">Save Changes</button>
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
                    <p class="text-muted"><small>Submitted by: <span id="viewRequestMemberName"></span> on <span id="viewRequestCreatedAt"></span></small></p>
                    <p><strong class="me-2">Priority:</strong> <span id="viewRequestPriority"></span></p>
                    <p><strong class="me-2">Current Status:</strong> <span class="badge" id="viewRequestStatus"></span></p>
                    <hr>
                    <h6>Description:</h6>
                    <p id="viewRequestDescription"></p>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Forwarding Details:</h6>
                            <p><small>Forwarded By: <span id="viewForwardedBy"></span></small></p>
                            <p><small>Forwarded At: <span id="viewForwardedAt"></span></small></p>
                            <p><small>Assigned Specialist: <span id="viewSpecialistName"></span></small></p>
                            <p><small>Notes from Head: <span id="viewHeadNotes" class="text-muted fst-italic"></span></small></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Your Resolution (to Head):</h6>
                            <p id="viewSpecialistResponse" class="text-muted fst-italic"></p>
                        </div>
                    </div>
                    <hr>
                    <h6>Final Response to Member:</h6>
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
            // Update Status Modal Logic
            const updateStatusModal = document.getElementById('updateStatusModal');
            if (updateStatusModal) {
                // Store the change event listener function in a variable so it can be removed
                let newStatusChangeListener = function() {
                    const specialistResponseGroup = document.getElementById('specialistResponseGroup');
                    const specialistResponseTextarea = document.getElementById('specialistResponseTextarea');

                    if (this.value === 'completed') {
                        specialistResponseGroup.style.display = 'block';
                        specialistResponseTextarea.setAttribute('required', 'required');
                    } else {
                        specialistResponseGroup.style.display = 'none';
                        specialistResponseTextarea.removeAttribute('required');
                    }
                };

                updateStatusModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    document.getElementById('updateStatusRequestId').value = button.getAttribute('data-request-id');
                    document.getElementById('updateStatusRequestTitle').textContent = button.getAttribute('data-request-title');

                    const currentStatus = button.getAttribute('data-current-status');
                    const specialistResponse = button.getAttribute('data-specialist-response');

                    const newStatusSelect = document.getElementById('newStatusSelect');
                    const specialistResponseGroup = document.getElementById('specialistResponseGroup');
                    const specialistResponseTextarea = document.getElementById('specialistResponseTextarea');

                    // Remove existing listener to prevent duplicates before adding new one
                    newStatusSelect.removeEventListener('change', newStatusChangeListener);

                    // Set current status in dropdown
                    newStatusSelect.value = currentStatus;

                    // Show/hide response textarea based on current status or selection
                    if (currentStatus === 'completed' || newStatusSelect.value === 'completed') {
                        specialistResponseGroup.style.display = 'block';
                        specialistResponseTextarea.value = specialistResponse;
                        specialistResponseTextarea.setAttribute('required', 'required'); // Make required if completed
                    } else {
                        specialistResponseGroup.style.display = 'none';
                        specialistResponseTextarea.value = '';
                        specialistResponseTextarea.removeAttribute('required');
                    }

                    // Listen for changes in the dropdown to dynamically show/hide response field
                    newStatusSelect.addEventListener('change', newStatusChangeListener);
                });
            }

            // View Request Modal (reused) Logic
            const viewRequestModal = document.getElementById('viewRequestModal');
            if (viewRequestModal) {
                viewRequestModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget; // Button that triggered the modal
                    document.getElementById('viewRequestId').textContent = button.getAttribute('data-id');
                    document.getElementById('viewRequestTitle').textContent = button.getAttribute('data-title');
                    document.getElementById('viewRequestMemberName').textContent = button.getAttribute('data-member-name');
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