<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Demo placeholders
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Seid Hussen';
    $_SESSION['role'] = 'Admin';
}

require_once 'db_connect.php';

$department = $_SESSION['department'];
$head_id = $_SESSION['user_id'];

// Get pending requests from department users
try {
    $requests_stmt = $pdo->prepare("SELECT r.*, u.name as user_name 
                                   FROM requests r
                                   JOIN users u ON r.user_id = u.id
                                   WHERE u.department = ? AND r.status = 'pending'
                                   ORDER BY r.priority DESC, r.created_at");
    $requests_stmt->execute([$department]);
    $requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching requests: " . $e->getMessage();
}

// Get IT specialists
try {
    $specialists_stmt = $pdo->prepare("SELECT * FROM it_specialists 
                                     WHERE specialties LIKE ?");
    $specialists_stmt->execute(["%$department%"]);
    $specialists = $specialists_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching specialists: " . $e->getMessage();
}

// Handle request forwarding
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forward_request'])) {
    $request_id = $_POST['request_id'];
    $specialist_id = $_POST['specialist_id'];
    $notes = trim($_POST['notes']);

    try {
        $pdo->beginTransaction();

        // Update request status
        $update_stmt = $pdo->prepare("UPDATE requests 
                                    SET status = 'forwarded', 
                                        forwarded_by = ?,
                                        forwarded_at = NOW(),
                                        specialist_id = ?,
                                        head_notes = ?
                                    WHERE id = ?");
        $update_stmt->execute([$head_id, $specialist_id, $notes, $request_id]);

        // Create notification for specialist
        $notif_stmt = $pdo->prepare("INSERT INTO notifications 
                                    (user_id, request_id, message, created_at)
                                    VALUES (?, ?, ?, NOW())");
        $message = "New request forwarded from " . $_SESSION['department'] . " department";
        $notif_stmt->execute([$specialist_id, $request_id, $message]);

        $pdo->commit();
        $success = "Request forwarded successfully";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error forwarding request: " . $e->getMessage();
    }
}

// Handle responses from specialists
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_response'])) {
    $request_id = $_POST['request_id'];
    $response = trim($_POST['response']);

    try {
        $pdo->beginTransaction();

        // Update request with response
        $update_stmt = $pdo->prepare("UPDATE requests 
                                    SET status = 'completed', 
                                        response = ?,
                                        completed_at = NOW()
                                    WHERE id = ?");
        $update_stmt->execute([$response, $request_id]);

        // Get user ID for notification
        $user_stmt = $pdo->prepare("SELECT user_id FROM requests WHERE id = ?");
        $user_stmt->execute([$request_id]);
        $user_id = $user_stmt->fetchColumn();

        // Create notification for user
        $notif_stmt = $pdo->prepare("INSERT INTO notifications 
                                    (user_id, request_id, message, created_at)
                                    VALUES (?, ?, ?, NOW())");
        $message = "Your request has been resolved by " . $_SESSION['name'];
        $notif_stmt->execute([$user_id, $request_id, $message]);

        $pdo->commit();
        $success = "Response sent to user successfully";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error sending response: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_SESSION['department'] ?> Department Manager</title>
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
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-4 text-center border-bottom">
                    <h4 class="mb-0">
                        <i class="fas fa-building me-2"></i>
                        <?= $_SESSION['department'] ?>
                    </h4>
                    <small class="text-muted">Department Manager</small>
                </div>

                <div class="p-4 text-center border-bottom">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=3f37c9&color=fff"
                        alt="User" class="rounded-circle mb-2" width="80">
                    <h6 class="mb-0"><?= $_SESSION['name'] ?></h6>
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

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>
                        <i class="fas fa-headset me-2"></i>
                        IT Helpdesk Dashboard
                    </h3>
                    <div>
                        <span class="badge bg-primary me-2">
                            <i class="fas fa-building me-1"></i> <?= $_SESSION['department'] ?>
                        </span>
                        <span class="badge bg-secondary">
                            <i class="fas fa-user-tie me-1"></i> Department Head
                        </span>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
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
                                            <?php
                                            $resolved = $pdo->query("SELECT COUNT(*) FROM requests 
                                                                        WHERE status = 'completed' 
                                                                        AND department = '{$_SESSION['department']}'
                                                                        AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
                                                ->fetchColumn();
                                            echo $resolved;
                                            ?>
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

                <!-- Pending Requests -->
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
                                                <td>#<?= $request['id'] ?></td>
                                                <td><?= $request['user_name'] ?></td>
                                                <td><?= htmlspecialchars($request['title']) ?></td>
                                                <td>
                                                    <?= $request['priority'] ?>
                                                    <?php if ($request['priority'] == 'High'): ?>
                                                        <span class="badge bg-danger ms-1">!</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('M j, g:i a', strtotime($request['created_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                        data-bs-target="#forwardModal"
                                                        data-request-id="<?= $request['id'] ?>">
                                                        <i class="fas fa-share me-1"></i> Forward
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                                        data-bs-target="#viewModal"
                                                        data-request-id="<?= $request['id'] ?>"
                                                        data-title="<?= htmlspecialchars($request['title']) ?>"
                                                        data-description="<?= htmlspecialchars($request['description']) ?>"
                                                        data-created-at="<?= date('M j, g:i a', strtotime($request['created_at'])) ?>"
                                                        data-user-name="<?= $request['user_name'] ?>">
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

                <!-- Forwarded Requests -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-share me-2"></i>
                            Forwarded to IT Specialists
                        </h5>
                    </div>

                    <div class="card-body p-0">
                        <?php
                        $forwarded_stmt = $pdo->prepare("SELECT r.*, u.name as user_name, s.name as specialist_name 
                                                            FROM requests r
                                                            JOIN users u ON r.user_id = u.id
                                                            JOIN it_specialists s ON r.specialist_id = s.id
                                                            WHERE u.department = ? AND r.status = 'forwarded'
                                                            ORDER BY r.forwarded_at DESC");
                        $forwarded_stmt->execute([$department]);
                        $forwarded_requests = $forwarded_stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

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
                                                <td>#<?= $req['id'] ?></td>
                                                <td><?= $req['user_name'] ?></td>
                                                <td><?= htmlspecialchars($req['title']) ?></td>
                                                <td><?= $req['specialist_name'] ?></td>
                                                <td><?= date('M j, g:i a', strtotime($req['forwarded_at'])) ?></td>
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

    <!-- View Request Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

    <!-- Forward Request Modal -->
    <div class="modal fade" id="forwardModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="request_id" id="forwardRequestId">
                    <div class="modal-header">
                        <h5 class="modal-title">Forward to IT Specialist</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Specialist</label>
                            <select class="form-select" name="specialist_id" required>
                                <?php foreach ($specialists as $spec): ?>
                                    <option value="<?= $spec['id'] ?>">
                                        <?= $spec['name'] ?> - <?= $spec['specialties'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Additional Notes</label>
                            <textarea class="form-control" name="notes" rows="3"
                                placeholder="Add any additional information for the specialist..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="forward_request" class="btn btn-primary">Forward Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Response Modal -->
    <div class="modal fade" id="responseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="request_id" id="responseRequestId">
                    <div class="modal-header">
                        <h5 class="modal-title">Send Response to User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Response</label>
                            <textarea class="form-control" name="response" rows="5" required
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
                const button = event.relatedTarget;
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
            });
        }

        // Response Modal
        const responseModal = document.getElementById('responseModal');
        if (responseModal) {
            responseModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                responseModal.querySelector('#responseRequestId').value = button.getAttribute('data-request-id');
            });
        }
    </script>
</body>

</html>