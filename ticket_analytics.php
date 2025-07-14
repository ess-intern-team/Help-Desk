<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Simulated static data for chart
$totalTickets = 30;
$resolvedTickets = 18;
$pendingTickets = 8;
$cancelledTickets = 4;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Ticket Analytics - ESSA Helpdesk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap + Chart.js -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            background-color: #f8f9fa;
        }

        .header-bar {
            background-color: #0d6efd;
            color: white;
            padding: 20px;
            border-bottom: 4px solid #084cd3;
        }

        .chart-container {
            max-width: 700px;
            margin: 50px auto;
        }

        footer {
            text-align: center;
            margin-top: 60px;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <div class="header-bar d-flex justify-content-between align-items-center">
        <h4 class="mb-0">📊 Ticket Analytics</h4>
        <a href="admin_dashboard.php" class="btn btn-light btn-sm">← Back to Dashboard</a>
    </div>

    <!-- Chart Area -->
    <div class="chart-container">
        <canvas id="ticketStatusChart"></canvas>
    </div>

    <!-- Info Cards -->
    <div class="container text-center mt-5">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card bg-success text-white shadow">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-check-circle me-2"></i>Resolved Tickets</h5>
                        <h2><?= $resolvedTickets ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark shadow">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-spinner me-2"></i>Pending Tickets</h5>
                        <h2><?= $pendingTickets ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white shadow">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-times-circle me-2"></i>Cancelled Tickets</h5>
                        <h2><?= $cancelledTickets ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mb-3">
        &copy; <?= date('Y') ?> ESSA Helpdesk | Analytics by Seid Hussen
    </footer>

    <!-- Chart Script -->
    <script>
        const ctx = document.getElementById('ticketStatusChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Resolved', 'Pending', 'Cancelled'],
                datasets: [{
                    label: 'Ticket Status',
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

    <!-- FontAwesome -->
    <script src="https://kit.fontawesome.com/a2e0e6cfd3.js" crossorigin="anonymous"></script>

</body>

</html>