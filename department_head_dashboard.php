<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Demo placeholders
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Seid Hussen';
    $_SESSION['role'] = 'Admin';
}
require_once 'db_connect.php';
$head_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$department = $_SESSION['department'];

$error = null;
$success = null;
$pending_requests = [];
$forwarded_requests = [];
$responses_from_specialists = []; // New list for responses to be sent to members
$specialists = []; // To hold IT specialists in the IT department

// --- Handle POST Requests (Form Submissions) ---

// Handle request forwarding (Head -> Specialist)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forward_request'])) {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $specialist_id = filter_input(INPUT_POST, 'specialist_id', FILTER_VALIDATE_INT);
    $head_notes = trim($_POST['head_notes'] ?? '');

    if ($request_id === false || $request_id === null || $specialist_id === false || $specialist_id === null) {
        $error = "Invalid request or specialist selection. Please try again.";
    } else {
        try {
            $pdo->beginTransaction();

            // Update request status to 'forwarded' and assign specialist
            $update_stmt = $pdo->prepare("UPDATE requests
                                         SET status = 'forwarded',
                                             forwarded_by = ?,
                                             forwarded_at = NOW(),
                                             specialist_id = ?,
                                             head_notes = ?
                                         WHERE id = ? AND department = ? AND status = 'pending'");
            $update_stmt->execute([$head_id, $specialist_id, $head_notes, $request_id, $department]);

            if ($update_stmt->rowCount() > 0) {
                // Get request title for notification
                $req_title_stmt = $pdo->prepare("SELECT title FROM requests WHERE id = ?");
                $req_title_stmt->execute([$request_id]);
                $req_title = $req_title_stmt->fetchColumn();

                // Notify the assigned IT specialist
                $notif_msg = "New request assigned by " . htmlspecialchars($full_name) . ": " . htmlspecialchars($req_title) . " (ID: " . $request_id . ").";
                $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, request_id, message) VALUES (?, ?, ?)");
                $notif_stmt->execute([$specialist_id, $request_id, $notif_msg]);

                $pdo->commit();
                $success = "Request #" . $request_id . " forwarded to specialist successfully!";
                header('Location: ' . $_SERVER['PHP_SELF'] . '?success=' . urlencode($success));
                exit();
            } else {
                $pdo->rollBack();
                $error = "Failed to forward request. It might have already been forwarded or changed status.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error forwarding request: " . $e->getMessage();
            error_log("Department Head DB Error (Forward Request): " . $e->getMessage());
        }
    }
}

// Handle sending response to member (Head -> Member)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_response_to_member'])) {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $response_to_member = trim($_POST['response_to_member'] ?? '');

    if ($request_id === false || $request_id === null || empty($response_to_member)) {
        $error = "Invalid request ID or empty response.";
    } else {
        try {
            $pdo->beginTransaction();

            // Update request with final response and mark as 'closed' (or 'completed' if you prefer)
            $update_stmt = $pdo->prepare("UPDATE requests
                                         SET status = 'closed',
                                             response_to_member = ?,
                                             closed_at = NOW()
                                         WHERE id = ? AND department = ? AND status = 'completed'"); // Only allow if specialist completed it
            $update_stmt->execute([$response_to_member, $request_id, $department]);

            if ($update_stmt->rowCount() > 0) {
                // Get the original member who submitted the request for notification
                $user_stmt = $pdo->prepare("SELECT user_id, title FROM requests WHERE id = ?");
                $user_stmt->execute([$request_id]);
                $req_info = $user_stmt->fetch(PDO::FETCH_ASSOC);
                $member_user_id = $req_info['user_id'];
                $req_title = $req_info['title'];

                if ($member_user_id) {
                    // Notify the member
                    $notif_msg = "Your request '" . htmlspecialchars($req_title) . "' (ID: " . $request_id . ") has been resolved and closed by your Department Head.";
                    $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, request_id, message) VALUES (?, ?, ?)");
                    $notif_stmt->execute([$member_user_id, $request_id, $notif_msg]);
                }

                $pdo->commit();
                $success = "Final response for request #" . $request_id . " sent to member and request closed!";
                header('Location: ' . $_SERVER['PHP_SELF'] . '?success=' . urlencode($success));
                exit();
            } else {
                $pdo->rollBack();
                $error = "Failed to send response to member. Request might not be ready or status has changed.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error sending response to member: " . $e->getMessage();
            error_log("Department Head DB Error (Send Response to Member): " . $e->getMessage());
        }
    }
}


// --- Fetch Data for Dashboard ---

// 1. Fetch pending requests from this department
try {
    $pending_stmt = $pdo->prepare("SELECT r.*, u.full_name as member_name
                                   FROM requests r
                                   JOIN users u ON r.user_id = u.id
                                   WHERE r.department = ? AND r.status = 'pending'
                                   ORDER BY r.priority DESC, r.created_at ASC");
    $pending_stmt->execute([$department]);
    $pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching pending requests: " . $e->getMessage();
    error_log("Department Head DB Error (Fetch Pending): " . $e->getMessage());
}

// 2. Fetch forwarded requests by this head (or for this department, if head can see all forwarded requests)
// For simplicity, showing all forwarded requests within the department
try {
    $forwarded_stmt = $pdo->prepare("SELECT r.*, u.full_name as member_name, s.full_name as specialist_name
                                     FROM requests r
                                     JOIN users u ON r.user_id = u.id
                                     LEFT JOIN users s ON r.specialist_id = s.id
                                     WHERE r.department = ? AND r.status = 'forwarded'
                                     ORDER BY r.forwarded_at DESC");
    $forwarded_stmt->execute([$department]);
    $forwarded_requests = $forwarded_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching forwarded requests: " . $e->getMessage();
    error_log("Department Head DB Error (Fetch Forwarded): " . $e->getMessage());
}

// 3. Fetch requests where specialists have provided a response ('completed' status by specialist)
// but head hasn't yet sent it to the member ('response_to_member' is NULL and 'status' is 'completed')
try {
    $responses_stmt = $pdo->prepare("SELECT r.*, u.full_name as member_name, s.full_name as specialist_name
                                     FROM requests r
                                     JOIN users u ON r.user_id = u.id
                                     JOIN users s ON r.specialist_id = s.id
                                     WHERE r.department = ? AND r.status = 'completed' AND r.response_to_member IS NULL
                                     ORDER BY r.completed_at DESC");
    $responses_stmt->execute([$department]);
    $responses_from_specialists = $responses_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching specialist responses: " . $e->getMessage();
    error_log("Department Head DB Error (Fetch Specialist Responses): " . $e->getMessage());
}


// 4. Fetch IT specialists for the forwarding dropdown (Only IT specialists from 'IT' department)
try {
    $specialists_stmt = $pdo->prepare("SELECT id, full_name, specialties FROM users WHERE role = 'specialist' AND department = 'IT'");
    $specialists_stmt->execute();
    $specialists = $specialists_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching IT specialists: " . $e->getMessage();
    error_log("Department Head DB Error (Fetch Specialists): " . $e->getMessage());
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
    <title><?= htmlspecialchars($department) ?> Department Head Dashboard</title>
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

        /* Completed by specialist, awaiting head review */
        .badge-closed {
            background-color: #e2e8f0;
            color: #475569;
        }

        /* Closed by head, response sent to member */
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

        /* Highlight rows awaiting head action */
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-4 text-center border-bottom">
                    <h4 class="mb-0">
                        <i class="fas fa-building me-2"></i>
                        <?= htmlspecialchars($department) ?>
                    </h4>
                    <small class="text-muted">Department Head</small>
                </div>
                <div class="p-4 text-center border-bottom">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($full_name) ?>&background=3f37c9&color=fff"
                        alt="User" class="rounded-circle mb-2" width="80">
                    <h6 class="mb-0"><?= htmlspecialchars($full_name) ?></h6>
                    <small class="text-muted">Department Head</small>
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
                        <i class="fas fa-headset me-2"></i>
                        Department Head Dashboard
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
                            <i class="fas fa-list me-2"></i> Pending Requests from Department Members
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($pending_requests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Member</th>
                                            <th>Title</th>
                                            <th>Priority</th>
                                            <th>Submitted On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_requests as $req): ?>
                                            <tr class="priority-<?= strtolower($req['priority']) ?>">
                                                <td>#<?= htmlspecialchars($req['id']) ?></td>
                                                <td><?= htmlspecialchars($req['member_name']) ?></td>
                                                <td><?= htmlspecialchars($req['title']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($req['priority']) ?>
                                                    <?php if ($req['priority'] == 'High'): ?>
                                                        <span class="badge bg-danger ms-1">!</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('M j, Y g:i a', strtotime($req['created_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#forwardModal"
                                                        data-request-id="<?= htmlspecialchars($req['id']) ?>"
                                                        data-request-title="<?= htmlspecialchars($req['title']) ?>"
                                                        data-request-description="<?= htmlspecialchars($req['description']) ?>"
                                                        data-member-name="<?= htmlspecialchars($req['member_name']) ?>">
                                                        <i class="fas fa-share me-1"></i> Forward
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewRequestModal"
                                                        data-id="<?= htmlspecialchars($req['id']) ?>"
                                                        data-title="<?= htmlspecialchars($req['title']) ?>"
                                                        data-description="<?= htmlspecialchars($req['description']) ?>"
                                                        data-priority="<?= htmlspecialchars($req['priority']) ?>"
                                                        data-status="<?= htmlspecialchars($req['status']) ?>"
                                                        data-created-at="<?= date('M j, Y g:i a', strtotime($req['created_at'])) ?>"
                                                        data-member-name="<?= htmlspecialchars($req['member_name']) ?>">
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
                                <p class="text-muted">There are currently no new requests from your department members.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-reply-all me-2"></i> Responses from IT Specialists
                            <span class="badge bg-warning text-dark ms-2"><?= count($responses_from_specialists) ?> awaiting action</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($responses_from_specialists) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Member</th>
                                            <th>Title</th>
                                            <th>Specialist</th>
                                            <th>Completed At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($responses_from_specialists as $req): ?>
                                            <tr class="status-completed">
                                                <td>#<?= htmlspecialchars($req['id']) ?></td>
                                                <td><?= htmlspecialchars($req['member_name']) ?></td>
                                                <td><?= htmlspecialchars($req['title']) ?></td>
                                                <td><?= htmlspecialchars($req['specialist_name']) ?></td>
                                                <td><?= date('M j, Y g:i a', strtotime($req['completed_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#sendResponseToMemberModal"
                                                        data-request-id="<?= htmlspecialchars($req['id']) ?>"
                                                        data-request-title="<?= htmlspecialchars($req['title']) ?>"
                                                        data-specialist-response="<?= htmlspecialchars($req['specialist_response'] ?? 'N/A') ?>"
                                                        data-member-name="<?= htmlspecialchars($req['member_name']) ?>">
                                                        <i class="fas fa-paper-plane me-1"></i> Send Response
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewRequestModal"
                                                        data-id="<?= htmlspecialchars($req['id']) ?>"
                                                        data-title="<?= htmlspecialchars($req['title']) ?>"
                                                        data-description="<?= htmlspecialchars($req['description']) ?>"
                                                        data-priority="<?= htmlspecialchars($req['priority']) ?>"
                                                        data-status="<?= htmlspecialchars($req['status']) ?>"
                                                        data-created-at="<?= date('M j, Y g:i a', strtotime($req['created_at'])) ?>"
                                                        data-forwarded-at="<?= $req['forwarded_at'] ? date('M j, Y g:i a', strtotime($req['forwarded_at'])) : 'N/A' ?>"
                                                        data-forwarded-by="<?= htmlspecialchars($full_name) ?>"
                                                        data-specialist-name="<?= htmlspecialchars($req['specialist_name']) ?>"
                                                        data-head-notes="<?= htmlspecialchars($req['head_notes'] ?? 'No notes from Department Head.') ?>"
                                                        data-specialist-response="<?= htmlspecialchars($req['specialist_response'] ?? 'No response from specialist yet.') ?>"
                                                        data-response-to-member="<?= htmlspecialchars($req['response_to_member'] ?? 'N/A') ?>">
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
                                <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Specialist Responses Awaiting Action</h5>
                                <p class="text-muted">All requests resolved by IT have been forwarded to members, or no new responses yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-share me-2"></i> Currently Forwarded Requests
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($forwarded_requests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Member</th>
                                            <th>Title</th>
                                            <th>Assigned Specialist</th>
                                            <th>Forwarded On</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($forwarded_requests as $req): ?>
                                            <tr>
                                                <td>#<?= htmlspecialchars($req['id']) ?></td>
                                                <td><?= htmlspecialchars($req['member_name']) ?></td>
                                                <td><?= htmlspecialchars($req['title']) ?></td>
                                                <td><?= htmlspecialchars($req['specialist_name'] ?? 'N/A') ?></td>
                                                <td><?= date('M j, Y g:i a', strtotime($req['forwarded_at'])) ?></td>
                                                <td><span class="badge badge-<?= strtolower($req['status']) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $req['status']))) ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewRequestModal"
                                                        data-id="<?= htmlspecialchars($req['id']) ?>"
                                                        data-title="<?= htmlspecialchars($req['title']) ?>"
                                                        data-description="<?= htmlspecialchars($req['description']) ?>"
                                                        data-priority="<?= htmlspecialchars($req['priority']) ?>"
                                                        data-status="<?= htmlspecialchars($req['status']) ?>"
                                                        data-created-at="<?= date('M j, Y g:i a', strtotime($req['created_at'])) ?>"
                                                        data-forwarded-at="<?= $req['forwarded_at'] ? date('M j, Y g:i a', strtotime($req['forwarded_at'])) : 'N/A' ?>"
                                                        data-forwarded-by="<?= htmlspecialchars($full_name) ?>"
                                                        data-specialist-name="<?= htmlspecialchars($req['specialist_name'] ?? 'N/A') ?>"
                                                        data-head-notes="<?= htmlspecialchars($req['head_notes'] ?? 'No notes from Department Head.') ?>"
                                                        data-specialist-response="<?= htmlspecialchars($req['specialist_response'] ?? 'No response from specialist yet.') ?>"
                                                        data-response-to-member="<?= htmlspecialchars($req['response_to_member'] ?? 'N/A') ?>">
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
                                <i class="fas fa-share-alt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Currently Forwarded Requests</h5>
                                <p class="text-muted">You haven't forwarded any requests that are still 'forwarded' or 'in progress'.</p>
                            </div>
                        <?php endif; ?>
                    </div>
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
                        <h5 class="modal-title" id="forwardModalLabel">Forward Request to IT Specialist</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Request Title:</strong> <span id="modalRequestTitle"></span></p>
                        <p><strong>From Member:</strong> <span id="modalMemberName"></span></p>
                        <hr>
                        <div class="mb-3">
                            <label for="specialistSelect" class="form-label">Select IT Specialist</label>
                            <select class="form-select" id="specialistSelect" name="specialist_id" required>
                                <?php if (count($specialists) > 0): ?>
                                    <?php foreach ($specialists as $spec): ?>
                                        <option value="<?= htmlspecialchars($spec['id']) ?>">
                                            <?= htmlspecialchars($spec['full_name']) ?> (<?= htmlspecialchars($spec['specialties']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled selected>No IT specialists available</option>
                                <?php endif; ?>
                            </select>
                            <?php if (count($specialists) === 0): ?>
                                <small class="text-danger mt-2">No IT specialists found. Please ensure specialists are added to the system under the 'IT' department.</small>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="headNotesTextarea" class="form-label">Notes for Specialist (Optional)</label>
                            <textarea class="form-control" id="headNotesTextarea" name="head_notes" rows="3"
                                placeholder="Add any specific instructions or details for the IT specialist..."></textarea>
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

    <div class="modal fade" id="sendResponseToMemberModal" tabindex="-1" aria-labelledby="sendResponseToMemberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="request_id" id="responseToMemberRequestId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="sendResponseToMemberModalLabel">Send Final Response to Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Request Title:</strong> <span id="responseToMemberRequestTitle"></span></p>
                        <p><strong>For Member:</strong> <span id="responseToMemberMemberName"></span></p>
                        <hr>
                        <h6>Specialist's Resolution:</h6>
                        <p class="alert alert-info" id="specialistResolutionDisplay"></p>
                        <hr>
                        <div class="mb-3">
                            <label for="responseToMemberTextarea" class="form-label">Your Message to the Member</label>
                            <textarea class="form-control" id="responseToMemberTextarea" name="response_to_member" rows="5" required
                                placeholder="Summarize the resolution or add any final notes for the member..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="send_response_to_member" class="btn btn-success">Send & Close Request</button>
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
                            <p><small>Notes to Specialist: <span id="viewHeadNotes" class="text-muted fst-italic"></span></small></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Specialist's Response:</h6>
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
            // Forward Modal Logic
            const forwardModal = document.getElementById('forwardModal');
            if (forwardModal) {
                forwardModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    document.getElementById('forwardRequestId').value = button.getAttribute('data-request-id');
                    document.getElementById('modalRequestTitle').textContent = button.getAttribute('data-request-title');
                    document.getElementById('modalMemberName').textContent = button.getAttribute('data-member-name');
                    document.getElementById('headNotesTextarea').value = ''; // Clear notes on new open
                });
            }

            // Send Response to Member Modal Logic
            const sendResponseToMemberModal = document.getElementById('sendResponseToMemberModal');
            if (sendResponseToMemberModal) {
                sendResponseToMemberModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    document.getElementById('responseToMemberRequestId').value = button.getAttribute('data-request-id');
                    document.getElementById('responseToMemberRequestTitle').textContent = button.getAttribute('data-request-title');
                    document.getElementById('responseToMemberMemberName').textContent = button.getAttribute('data-member-name');
                    document.getElementById('specialistResolutionDisplay').textContent = button.getAttribute('data-specialist-response');
                    document.getElementById('responseToMemberTextarea').value = ''; // Clear textarea on new open
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