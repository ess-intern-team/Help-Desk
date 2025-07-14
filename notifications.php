<?php
session_start();
require 'db_connect.php';

// Empty array: no dummy notifications
$notifications = [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Notifications - ESSA Helpdesk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(to right, #e6f0ff, #f7f9fc);
            font-family: 'Segoe UI', sans-serif;
        }

        .notification-card {
            border-left: 5px solid #0d6efd;
            border-radius: 10px;
            transition: all 0.2s ease-in-out;
            background-color: #fff;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
        }

        .notification-card:hover {
            transform: scale(1.01);
            background-color: #f0f8ff;
        }

        .notification-icon {
            font-size: 1.4rem;
            color: #0d6efd;
        }

        .notification-time {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #888;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <h3 class="mb-4 text-center">🔔 Your Notifications</h3>

        <?php if (count($notifications) > 0): ?>
            <div class="row">
                <?php foreach ($notifications as $notif): ?>
                    <div class="col-md-6 mb-4">
                        <div class="notification-card p-3 d-flex align-items-start">
                            <div class="me-3">
                                <i class="fas fa-bell notification-icon"></i>
                            </div>
                            <div>
                                <div class="fw-semibold"> <?= htmlspecialchars($notif[0]) ?> </div>
                                <div class="notification-time"> <?= htmlspecialchars($notif[1]) ?> </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <h5>No notifications yet</h5>
                <p>You're all caught up!</p>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="user_dashboard.php" class="btn btn-outline-primary">
                <a href="admin_dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

</body>

</html>