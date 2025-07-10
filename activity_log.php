<?php
session_start();
require 'db_connect.php';

// Empty array for now — no hardcoded data
$activityLogs = [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Activity Log - ESSA Helpdesk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(to bottom right, #f9f9fb, #e6f0ff);
            font-family: 'Segoe UI', sans-serif;
        }

        .log-card {
            background: #fff;
            border-left: 4px solid #0d6efd;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease-in-out;
        }

        .log-card:hover {
            background-color: #f5faff;
            transform: scale(1.005);
        }

        .log-meta {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .no-data {
            text-align: center;
            color: #999;
            padding: 60px 20px;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <h3 class="text-center mb-4">📜 User Activity Log</h3>

        <?php if (count($activityLogs) > 0): ?>
            <?php foreach ($activityLogs as $log): ?>
                <div class="log-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($log[0]) ?></strong> - <?= htmlspecialchars($log[1]) ?>
                        </div>
                        <div class="log-meta">
                            <i class="fas fa-clock me-1"></i><?= htmlspecialchars($log[2]) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-history fa-3x mb-3"></i>
                <h5>No activity records yet</h5>
                <p>System actions and user activity will appear here once available.</p>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

</body>

</html>