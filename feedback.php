<?php
session_start();
require 'db_connect.php';

$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name || !$email || !$message) {
        $errorMessage = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";
    } else {
        // TODO: Insert feedback into DB (replace with actual DB code)
        // Example:
        // $stmt = $pdo->prepare("INSERT INTO feedback (name, email, message, created_at) VALUES (?, ?, ?, NOW())");
        // $stmt->execute([$name, $email, $message]);

        $successMessage = "Thank you for your feedback!";
        // Clear form inputs
        $name = $email = $message = '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Feedback - ESSA Helpdesk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            background: linear-gradient(135deg, #eef2ff, #e0f7fa);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .feedback-card {
            max-width: 600px;
            margin: 3rem auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .feedback-card:hover {
            transform: translateY(-5px);
        }

        .btn-primary {
            background-color: #6c63ff;
            border: none;
        }

        .btn-primary:hover {
            background-color: #5848c2;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="feedback-card">
        <h2 class="mb-4 text-center text-primary"><i class="fas fa-comment-dots me-2"></i>Submit Feedback</h2>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <form method="POST" action="feedback.php" novalidate>
            <div class="mb-3">
                <label for="name" class="form-label">Your Name <span class="text-danger">*</span></label>
                <input
                    type="text"
                    class="form-control"
                    id="name"
                    name="name"
                    placeholder="John Doe"
                    required
                    value="<?= htmlspecialchars($name ?? '') ?>" />
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    placeholder="you@example.com"
                    required
                    value="<?= htmlspecialchars($email ?? '') ?>" />
            </div>

            <div class="mb-3">
                <label for="message" class="form-label">Your Feedback <span class="text-danger">*</span></label>
                <textarea
                    class="form-control"
                    id="message"
                    name="message"
                    rows="5"
                    placeholder="Write your message here..."
                    required><?= htmlspecialchars($message ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-paper-plane me-2"></i>Send Feedback
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-link text-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

</body>

</html>