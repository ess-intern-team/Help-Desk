<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Demo placeholders
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Seid Hussen';
    $_SESSION['role'] = 'Admin';
}
$user_name = $_SESSION['user_name'];
$role = $_SESSION['role'];

// Dummy data placeholders
$totalRequests = '';
$resolvedRequests = '';
$pendingRequests = '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Helpdesk Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        /* Navbar */
        .navbar-brand {
            font-weight: bold;
        }

        #menuToggle {
            cursor: pointer;
            font-size: 1.8rem;
            color: white;
            border: none;
            background: none;
        }

        /* Sidebar */
        #menuContainer {
            position: fixed;
            top: 56px;
            /* navbar height */
            left: 0;
            width: 220px;
            background-color: #343a40;
            height: 100%;
            padding-top: 1rem;
            display: none;
            z-index: 1100;
            overflow-y: auto;
            transition: transform 0.3s ease;
            transform: translateX(-100%);
        }

        #menuContainer.show {
            display: block;
            transform: translateX(0);
        }

        #menuContainer a {
            display: block;
            color: #adb5bd;
            padding: 12px 20px;
            text-decoration: none;
            font-weight: 500;
            border-left: 4px solid transparent;
            transition: background-color 0.2s, border-color 0.2s;
        }

        #menuContainer a:hover {
            background-color: #495057;
            color: white;
            border-left-color: #0d6efd;
        }

        /* Shift content when sidebar open */
        #content {
            padding: 20px;
            margin-top: 56px;
            /* navbar height */
            transition: margin-left 0.3s ease;
        }

        #content.shifted {
            margin-left: 220px;
        }

        /* Responsive for small devices */
        @media (max-width: 768px) {
            #menuContainer {
                width: 100%;
                height: auto;
                position: fixed;
                top: 56px;
                left: 0;
                transform: translateY(-100%);
            }

            #menuContainer.show {
                transform: translateY(0);
                display: block;
            }

            #content.shifted {
                margin-left: 0;
            }

            #menuContainer a {
                border-left: none;
                padding: 12px;
                border-bottom: 1px solid #495057;
            }
        }
    </style>
</head>

<body>

    <!-- Top Navbar -->
    <nav class="navbar navbar-dark bg-dark shadow-sm fixed-top">
        <div class="container-fluid d-flex align-items-center">
            <button id="menuToggle" aria-label="Toggle menu">&#9776;</button>
            <a class="navbar-brand ms-3" href="#">IT HelpDesk</a>

            <div class="d-flex ms-auto align-items-center gap-3">

                <a href="search.php" class="text-white" title="Search"><i class="fas fa-search"></i></a>

                <a href="notifications.php" class="text-white position-relative" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">

                        <span class="visually-hidden">unread notifications</span>
                    </span>
                </a>

                <a href="feedback.php" class="text-white" title="Feedback"><i class="fas fa-comment-dots"></i></a>

                <div class="text-white ms-3 d-flex align-items-center">
                    Welcome, <?= htmlspecialchars($user_name) ?> (<?= htmlspecialchars($role) ?>)
                    <a href="logout.php" class="btn btn-sm btn-outline-light ms-3">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar menu -->
    <nav id="menuContainer" aria-label="Main menu">
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="tickets.php">🎫 Tickets</a>
        <a href="add_request.php">➕ Add Request</a>
        <a href="reports.php">📊 Reports</a>
        <a href="knowledge-base.php">📚 Knowledge Base</a>
        <a href="settings.php">⚙️ Settings</a>
        <a href="search.php">🔍 Search</a>
        <a href="feedback.php">💬 Feedback</a>
        <a href="notifications.php">🔔 Notifications</a>
        <a href="activity_log.php">📜 Activity Log</a>
        <a href="archive.php">🗄️ Archive</a>
    </nav>

    <!-- Main content -->
    <div id="content" class="container">
        <!-- Info Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Total Requests</h5>
                        <h2><?= htmlspecialchars($totalRequests) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Resolved</h5>
                        <h2><?= htmlspecialchars($resolvedRequests) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Pending</h5>
                        <h2><?= htmlspecialchars($pendingRequests) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-3 mb-2">
                <a href="add_request.php" class="btn btn-outline-primary w-100">➕ Add Request</a>
            </div>
            <div class="col-md-3 mb-2">
                <a href="view_requests.php" class="btn btn-outline-success w-100">📋 View All Requests</a>
            </div>
            <div class="col-md-3 mb-2">
                <a href="manage_users.php" class="btn btn-outline-dark w-100">👥 Manage Users</a>
            </div>
            <div class="col-md-3 mb-2">
                <a href="reports.php" class="btn btn-outline-secondary w-100">📊 Generate Report</a>
            </div>
        </div>

        <!-- Chart -->
        <div class="chart-container mb-5">
            <canvas id="statusChart"></canvas>
        </div>

        <!-- Recent Requests -->
        <div class="card shadow-sm mb-5">
            <div class="card-header bg-dark text-white">📌 Latest Help Requests</div>
            <div class="card-body p-0">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>User</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No requests available (backend pending)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Chart script -->
    <script>
        const ctx = document.getElementById('statusChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Resolved', 'Pending'],
                datasets: [{
                    label: 'Requests',
                    data: [<?= json_encode($resolvedRequests ?: 0) ?>, <?= json_encode($pendingRequests ?: 0) ?>],
                    backgroundColor: ['#198754', '#ffc107'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'Request Status Overview'
                    }
                }
            }
        });

        // Toggle sidebar menu
        document.getElementById('menuToggle').addEventListener('click', function() {
            const menu = document.getElementById('menuContainer');
            menu.classList.toggle('show');

            const content = document.getElementById('content');
            if (window.innerWidth > 768) {
                content.classList.toggle('shifted');
            }
        });
    </script>
</body>

</html>