<?php
session_start();

// Force demo session for testing - REMOVE for real login
$_SESSION['user_id'] = 1;
$_SESSION['name'] = 'Seid Hussen';
$_SESSION['role'] = 'user';

// Now role check is always true for testing
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}


require_once __DIR__ . '/db_connect.php';

$userId = $_SESSION['user_id'];

// Define valid categories and priorities
$valid_categories = [
    'Networking problems',
    'Software issues',
    'Cybersecurity concerns',
    'Hardware or maintenance needs'
];

$valid_priorities = ['Low', 'Medium', 'High', 'Urgent'];

// Handle form submission for new request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $status = 'New';
    $created_at = date('Y-m-d H:i:s');

    // Validate category & priority
    if (
        $title && $description &&
        in_array($priority, $valid_priorities) &&
        in_array($category, $valid_categories)
    ) {
        $sql = "INSERT INTO help_requests (user_id, title, description, category, priority, status, created_at) 
                VALUES ($userId, '$title', '$description', '$category', '$priority', '$status', '$created_at')";
        if (mysqli_query($conn, $sql)) {
            header("Location: user_dashboard.php?msg=Request+submitted+successfully");
            exit();
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    } else {
        $error = "Please fill all fields correctly.";
    }
}

// Fetch user's requests
$myRequests = mysqli_query($conn, "SELECT * FROM help_requests WHERE user_id = $userId ORDER BY created_at DESC");

// Fetch notifications/messages dynamically from DB
$notifications = [];
$notifQuery = mysqli_query($conn, "SELECT message, created_at FROM notifications WHERE user_id = $userId ORDER BY created_at DESC LIMIT 10");
if ($notifQuery) {
    while ($row = mysqli_fetch_assoc($notifQuery)) {
        $notifications[] = [
            'message' => $row['message'],
            'date' => date('Y-m-d', strtotime($row['created_at']))
        ];
    }
}

// Fetch user's profile photo for top right display
$userPhoto = 'https://via.placeholder.com/56?text=No+Photo'; // default placeholder
$photoPath = __DIR__ . "/uploads/profile_photos/";

$sqlPhoto = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
$sqlPhoto->bind_param("i", $userId);
$sqlPhoto->execute();
$resPhoto = $sqlPhoto->get_result();
if ($resPhoto && $resPhoto->num_rows > 0) {
    $row = $resPhoto->fetch_assoc();
    if ($row['profile_photo'] && file_exists($photoPath . $row['profile_photo'])) {
        $userPhoto = "uploads/profile_photos/" . $row['profile_photo'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Dashboard - ESSA Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .header {
            background: #2563eb;
            color: white;
            padding: 20px 30px;
        }

        .header h3 {
            margin: 0;
        }

        .btn-primary,
        .btn-danger {
            border-radius: 25px;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .status-New {
            color: #0d6efd;
            font-weight: 700;
        }

        .status-In\ Progress {
            color: #fd7e14;
            font-weight: 700;
        }

        .status-Resolved {
            color: #198754;
            font-weight: 700;
        }

        .form-label {
            font-weight: 600;
        }

        @media (max-width: 768px) {

            .header,
            .container {
                padding: 15px;
            }
        }

        /* User photo styles */
        .user-photo-link {
            display: inline-block;
            width: 56px;
            /* larger size */
            height: 56px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid white;
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            transition: transform 0.2s ease;
            margin-left: 8px;
            /* margin between logout button and photo */
        }

        .user-photo-link:hover {
            transform: scale(1.05);
        }

        .user-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    </style>
</head>

<body>
    <div class="header d-flex justify-content-between align-items-center flex-wrap">
        <h3>Welcome, <?= htmlspecialchars($_SESSION['name']); ?></h3>
        <div class="mt-2 mt-md-0 d-flex align-items-center">
            <a href="logout.php" class="btn btn-danger">Logout</a>
            <!-- User photo as clickable link -->
            <a href="profile.php" title="Profile/Settings" class="user-photo-link">
                <img src="<?= htmlspecialchars($userPhoto); ?>" alt="User Photo" class="user-photo" />
            </a>
        </div>
    </div>

    <div class="container my-4">
        <!-- New Request Form -->
        <div class="card mb-4 p-4">
            <h4>Submit a New Help Request</h4>
            <?php if (!empty($error)) : ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php elseif (isset($_GET['msg'])) : ?>
                <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
            <?php endif; ?>
            <form method="POST" action="user_dashboard.php" class="mt-3">
                <div class="mb-3">
                    <label for="title" class="form-label">Issue Title</label>
                    <input type="text" id="title" name="title" class="form-control" maxlength="100" required />
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Issue Description</label>
                    <textarea id="description" name="description" rows="4" class="form-control" maxlength="1000" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select id="category" name="category" class="form-select" required>
                        <option value="" disabled selected>Select Category</option>
                        <?php foreach ($valid_categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="priority" class="form-label">Priority</label>
                    <select id="priority" name="priority" class="form-select" required>
                        <option value="" disabled selected>Select Priority</option>
                        <?php foreach ($valid_priorities as $pri): ?>
                            <option value="<?= htmlspecialchars($pri) ?>"><?= htmlspecialchars($pri) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="submit_request" class="btn btn-primary">Submit Request</button>
            </form>
        </div>

        <!-- Your Help Requests -->
        <div class="card p-4 mb-4">
            <h4>Your Submitted Requests</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Submitted On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($myRequests) > 0) : ?>
                            <?php while ($req = mysqli_fetch_assoc($myRequests)) : ?>
                                <tr>
                                    <td><?= $req['id']; ?></td>
                                    <td><?= htmlspecialchars($req['title']); ?></td>
                                    <td><?= htmlspecialchars($req['category']); ?></td>
                                    <td class="status-<?= str_replace(' ', '\\ ', $req['status']); ?>"><?= $req['status']; ?></td>
                                    <td><?= $req['priority']; ?></td>
                                    <td><?= $req['created_at']; ?></td>
                                    <td>
                                        <?php if ($req['status'] === 'New') : ?>
                                            <a href="edit_request.php?id=<?= $req['id']; ?>" class="btn btn-sm btn-primary me-1">Edit</a>
                                            <a href="cancel_request.php?id=<?= $req['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this request?');">Cancel</a>
                                        <?php else : ?>
                                            <span class="text-muted">No actions</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="text-center">No requests found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Notifications/Messages -->
        <div class="card p-4 mb-4">
            <h4>Notifications</h4>
            <?php if (count($notifications) > 0) : ?>
                <ul class="list-group">
                    <?php foreach ($notifications as $note) : ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($note['message']); ?>
                            <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($note['date']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="text-muted">No new notifications.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>