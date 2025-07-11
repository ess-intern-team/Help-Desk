<?php
session_start();
// Require authentication for admin dashboard
if (!isset($_SESSION['user_id'])) {
    // Demo placeholders
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Seid Hussen';
    $_SESSION['role'] = 'Admin';
}

// Get user info from session
$user_name = $_SESSION['user_name'] ?? 'Admin';
$role = $_SESSION['role'] ?? 'admin';

// Initialize ticket counts (these will be populated by your backend)
$totalTickets = 0;
$resolvedTickets = 0;
$pendingTickets = 0;
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
            max-width: 600px;
            margin: 40px auto;
        }

        footer {
            text-align: center;
            font-size: 14px;
            color: #6c757d;
            margin-top: 40px;
        }
    </style>
</head>

<body>

    <div class="dashboard-header d-flex justify-content-between align-items-center">
        <h3>👨‍💻 Admin Dashboard - ESSA Helpdesk</h3>
        <div>
            Welcome, <strong><?= htmlspecialchars($user_name) ?></strong> (<?= htmlspecialchars($role) ?>)
            <a href="logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
        </div>
    </div>

    <div class="container mt-5">

        <!-- Ticket Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow bg-primary text-white">
                    <div class="card-body">
                        <h5><i class="fas fa-ticket-alt me-2 icon-large"></i>Total Tickets</h5>
                        <h2><?= $totalTickets ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow bg-success text-white">
                    <div class="card-body">
                        <h5><i class="fas fa-check-circle me-2 icon-large"></i>Resolved</h5>
                        <h2><?= $resolvedTickets ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow bg-warning text-dark">
                    <div class="card-body">
                        <h5><i class="fas fa-spinner me-2 icon-large"></i>Pending</h5>
                        <h2><?= $pendingTickets ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Access Buttons -->
        <div class="row quick-actions mb-4">
            <div class="col-md-3">
                <a href="view_requests.php" class="btn btn-outline-primary w-100"><i class="fas fa-list-alt me-2"></i>View All Requests</a>
            </div>
            <div class="col-md-3">
                <a href="manage_users.php" class="btn btn-outline-dark w-100"><i class="fas fa-users-cog me-2"></i>Manage Users</a>
            </div>
            <div class="col-md-3">
                <a href="reports.php" class="btn btn-outline-success w-100"><i class="fas fa-chart-line me-2"></i>Reports</a>
            </div>
            <div class="col-md-3">
                <a href="settings.php" class="btn btn-outline-secondary w-100"><i class="fas fa-cogs me-2"></i>System Settings</a>
            </div>
        </div>

        <!-- Analytics Chart -->
        <div class="chart-container">
            <canvas id="ticketChart"></canvas>
        </div>

        <!-- Notifications (design only for now) -->
        <div class="card shadow-sm mt-5">
            <div class="card-header bg-info text-white">
                <i class="fas fa-bell"></i> Notifications
            </div>
            <div class="card-body">
                <p>No new notifications. (Connect backend to fetch updates)</p>
            </div>
        </div>

    </div>

    <footer class="mt-5 mb-3">
        &copy; <?= date("Y") ?> ESSA Helpdesk | Developed by Seid Hussen
    </footer>

    <!-- Chart Logic -->
    <script>
        const ctx = document.getElementById('ticketChart').getContext('2d');
        const ticketChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Resolved', 'Pending'],
                datasets: [{
                    label: 'Tickets',
                    data: [<?= $resolvedTickets ?>, <?= $pendingTickets ?>],
                    backgroundColor: ['#198754', '#ffc107'],
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

</body>

</html>