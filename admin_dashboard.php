<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Seid Hussen';
    $_SESSION['role'] = 'Admin';
}

$user_name = $_SESSION['user_name'] ?? 'Admin';
$role = $_SESSION['role'] ?? 'admin';

$totalTickets = 0;
$resolvedTickets = 0;
$pendingTickets = 0;
$cancelledTickets = 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - ESSA Helpdesk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://kit.fontawesome.com/a2e0e6cfd3.js" crossorigin="anonymous"></script>

    <style>
        body {
            background-color: #f8f9fa;
        }

        .dashboard-header {
            padding: 20px;
            background: #0d6efd;
            color: white;
            border-bottom: 4px solid #084cd3;
        }

        .card {
            border-radius: 15px;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: scale(1.02);
        }

        .icon-large {
            font-size: 2rem;
        }

        .quick-actions .btn {
            padding: 15px 20px;
            font-weight: 600;
        }

        .chart-container {
            max-width: 700px;
            margin: 40px auto;
        }

        footer {
            text-align: center;
            font-size: 14px;
            color: #6c757d;
            margin-top: 40px;
        }

        .notif-icon {
            font-size: 1.5rem;
            color: white;
            margin-right: 20px;
            cursor: pointer;
            position: relative;
            transition: 0.3s;
        }

        .notif-icon:hover {
            color: #ffc107;
        }

        .notif-icon {
            font-size: 1.5rem;
            color: white;
            position: relative;
            cursor: pointer;
        }

        .notif-badge {
            position: absolute;
            top: -8px;
            right: -10px;
            background: red;
            color: white;
            font-size: 0.6rem;
            padding: 2px 6px;
            border-radius: 50%;
        }


        .dropdown-menu {
            right: 0;
            left: auto;
        }

        .profile-group {
            gap: 15px;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-center">
        <h3>👨‍💻 Admin Dashboard - ESSA Helpdesk</h3>

        <div class="d-flex align-items-center gap-3">

            <!-- 🔔 Notifications Dropdown -->
            <div class="dropdown">
                <a class="notif-icon text-white dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <span class="notif-badge">🔔</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <h6 class="dropdown-header text-primary">Recent Notifications</h6>
                    <li class="text-center py-3">
                        <p class="text-muted mb-0">No notifications found</p>
                    </li>
                    <li><a class="dropdown-item text-center text-primary fw-bold" href="notifications.php">📋 View All Notifications</a></li>
                </ul>
            </div>

            <!-- 👤 Profile Dropdown -->
            <div class="dropdown">
                <a href="#" class="text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <strong><?= htmlspecialchars($user_name) ?></strong> (<?= htmlspecialchars($role) ?>)
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>View Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>


    <div class="container mt-5">

        <!-- Ticket Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow bg-primary text-white">
                    <div class="card-body">
                        <h5><i class="fas fa-ticket-alt me-2 icon-large"></i>Total Tickets</h5>
                        <h2><?= $totalTickets ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow bg-success text-white">
                    <div class="card-body">
                        <h5><i class="fas fa-check-circle me-2 icon-large"></i>Resolved</h5>
                        <h2><?= $resolvedTickets ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow bg-warning text-dark">
                    <div class="card-body">
                        <h5><i class="fas fa-spinner me-2 icon-large"></i>Pending</h5>
                        <h2><?= $pendingTickets ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow bg-danger text-white">
                    <div class="card-body">
                        <h5><i class="fas fa-times-circle me-2 icon-large"></i>Cancelled</h5>
                        <h2><?= $cancelledTickets ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Access -->
        <div class="row quick-actions mb-4">
            <div class="col-md-3">
                <a href="view_requests.php" class="btn btn-outline-primary w-100"><i class="fas fa-list-alt me-2"></i>View All Requests</a>
            </div>
            <div class="col-md-3">
                <a href="reports.php" class="btn btn-outline-success w-100"><i class="fas fa-chart-line me-2"></i>Reports</a>
            </div>
            <div class="col-md-3">
                <a href="add_request.php" class="btn btn-outline-info w-100"><i class="fas fa-envelope-open-text me-2"></i>New Requests</a>
            </div>
            <div class="col-md-3">
                <a href="ticket_analytics.php" class="btn btn-outline-dark w-100"><i class="fas fa-chart-pie me-2"></i>Ticket Analytics</a>
            </div>
        </div>

        <!-- Chart -->
        <div class="chart-container">
            <canvas id="ticketChart"></canvas>
        </div>

    </div>

    <footer class="mt-5 mb-3">
        &copy; <?= date("Y") ?> ESSA Helpdesk | Developed by intern teams
    </footer>

    <!-- Chart Logic -->
    <script>
        const ctx = document.getElementById('ticketChart').getContext('2d');
        const ticketChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Resolved', 'Pending', 'Cancelled'],
                datasets: [{
                    label: 'Tickets',
                    data: [<?= $resolvedTickets ?>, <?= $pendingTickets ?>, <?= $cancelledTickets ?>],
                    backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Ticket Status Overview'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>

    <!-- Bootstrap JS for dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>