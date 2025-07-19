<?php
session_start();
require 'db_connect.php';

// Empty array: no hardcoded tickets
$archivedTickets = [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Archived Tickets - ESSA Helpdesk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(to bottom right, #f0f4f8, #dff1fc);
            font-family: 'Segoe UI', sans-serif;
        }

        .archive-card {
            background-color: #fff;
            border-left: 5px solid #0d6efd;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease-in-out;
        }

        .archive-card:hover {
            background-color: #f1f9ff;
            transform: scale(1.01);
        }

        .status-pill {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            color: white;
        }

        .status-Resolved {
            background-color: #28a745;
        }

        .status-Closed {
            background-color: #6c757d;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #888;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <h3 class="text-center mb-4">🗃 Archived Tickets</h3>

        <?php if (count($archivedTickets) > 0): ?>
            <div class="row">
                <?php foreach ($archivedTickets as $ticket): ?>
                    <div class="col-md-6 mb-4">
                        <div class="archive-card p-3 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0"> <?= htmlspecialchars($ticket[1]) ?> </h5>
                                <span class="status-pill status-<?= htmlspecialchars($ticket[2]) ?>"> <?= htmlspecialchars($ticket[2]) ?> </span>
                            </div>
                            <div class="text-muted">Ticket ID: <strong><?= htmlspecialchars($ticket[0]) ?></strong></div>
                            <div class="text-muted">Archived On: <?= htmlspecialchars($ticket[3]) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-archive fa-3x mb-3"></i>
                <h5>No archived tickets yet</h5>
                <p>Once tickets are resolved or closed, they'll appear here.</p>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

</body>

</html>