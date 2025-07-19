<?php
session_start();
require 'db_connect.php';

$searchTerm = trim($_GET['q'] ?? '');
$results = [];

// TODO: Replace below with actual DB search query after backend is ready
// For now, empty results if no search or fixed sample empty array

if ($searchTerm !== '') {
    // Example: Fetch results from DB here
    // $stmt = $pdo->prepare("SELECT * FROM tickets WHERE title LIKE ? OR description LIKE ?");
    // $stmt->execute(["%$searchTerm%", "%$searchTerm%"]);
    // $results = $stmt->fetchAll();

    // Temporarily empty results so page shows clean empty state
    $results = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Search Tickets - ESSA Helpdesk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            background: linear-gradient(120deg, #d0e6ff, #f0f9ff);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .search-container {
            max-width: 800px;
            margin: 3rem auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #6c63ff;
            border: none;
        }

        .btn-primary:hover {
            background-color: #5848c2;
        }

        .result-card {
            border-left: 5px solid #6c63ff;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 6px 18px rgba(108, 99, 255, 0.15);
            transition: transform 0.2s ease-in-out;
        }

        .result-card:hover {
            background-color: #f5f5ff;
            transform: translateX(5px);
        }

        .no-results {
            text-align: center;
            color: #777;
            padding: 60px 20px;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="search-container">
        <h2 class="mb-4 text-primary"><i class="fas fa-search me-2"></i>Search Tickets</h2>

        <form class="mb-4" method="GET" action="search.php">
            <div class="input-group">
                <input
                    type="search"
                    name="q"
                    class="form-control"
                    placeholder="Search tickets by keywords..."
                    value="<?= htmlspecialchars($searchTerm) ?>"
                    aria-label="Search tickets"
                    required />
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>

        <?php if ($searchTerm === ''): ?>
            <div class="no-results">
                <i class="fas fa-info-circle fa-3x mb-3"></i>
                <h5>Start typing above to search tickets.</h5>
            </div>
        <?php elseif (count($results) === 0): ?>
            <div class="no-results">
                <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                <h5>No tickets found matching "<?= htmlspecialchars($searchTerm) ?>"</h5>
                <p>Try different keywords or check back later.</p>
            </div>
        <?php else: ?>
            <?php foreach ($results as $ticket): ?>
                <div class="result-card">
                    <h5><?= htmlspecialchars($ticket['title']) ?></h5>
                    <p><?= htmlspecialchars(substr($ticket['description'], 0, 150)) ?>...</p>
                    <small class="text-muted">Submitted on: <?= htmlspecialchars($ticket['created_at']) ?></small>
                </div>
            <?php endforeach; ?>
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