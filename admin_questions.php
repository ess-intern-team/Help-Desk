<?php
// admin_questions.php - Admin interface to view user questions
require_once 'admin_auth.php'; // Your admin authentication

// Database connection
$conn = new mysqli('localhost', 'root', '', 'helpdesk_db');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$status = $_GET['status'] ?? 'pending';
$valid_statuses = ['pending', 'answered', 'rejected'];
$status = in_array($status, $valid_statuses) ? $status : 'pending';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_question'])) {
        $id = (int)$_POST['id'];
        $answer = $conn->real_escape_string($_POST['answer'] ?? '');
        $status = $conn->real_escape_string($_POST['status'] ?? 'pending');
        $notes = $conn->real_escape_string($_POST['admin_notes'] ?? '');

        $stmt = $conn->prepare("UPDATE faq_questions SET 
                               answer = ?, 
                               status = ?, 
                               admin_notes = ?,
                               updated_at = NOW()
                               WHERE id = ?");
        $stmt->bind_param("sssi", $answer, $status, $notes, $id);
        $stmt->execute();

        $_SESSION['message'] = "Question updated successfully!";
        header("Location: admin_questions.php?status=$status");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Questions | ESSA Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .question-card {
            transition: all 0.3s;
        }

        .question-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }

        .pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .answered {
            background-color: #d4edda;
            color: #155724;
        }

        .rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .nav-pills .nav-link.active {
            background-color: #4e73df;
        }
    </style>
</head>

<body>
    <?php include 'admin_nav.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include 'admin_sidebar.php'; ?>
            </div>

            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-question-circle"></i> User Questions</h2>
                    <div class="btn-group">
                        <a href="?status=pending" class="btn btn-outline-primary <?= $status == 'pending' ? 'active' : '' ?>">
                            Pending <span class="badge bg-secondary"><?= get_count($conn, 'pending') ?></span>
                        </a>
                        <a href="?status=answered" class="btn btn-outline-success <?= $status == 'answered' ? 'active' : '' ?>">
                            Answered <span class="badge bg-secondary"><?= get_count($conn, 'answered') ?></span>
                        </a>
                        <a href="?status=rejected" class="btn btn-outline-danger <?= $status == 'rejected' ? 'active' : '' ?>">
                            Rejected <span class="badge bg-secondary"><?= get_count($conn, 'rejected') ?></span>
                        </a>
                    </div>
                </div>

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <div class="row">
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM faq_questions WHERE status = ? ORDER BY created_at DESC");
                    $stmt->bind_param("s", $status);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo render_question_card($row);
                        }
                    } else {
                        echo '<div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="bi bi-question-lg display-4 text-muted mb-3"></i>
                                        <h3>No ' . ucfirst($status) . ' Questions</h3>
                                        <p class="text-muted">There are no ' . $status . ' questions at this time.</p>
                                    </div>
                                </div>
                              </div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for answering questions -->
    <div class="modal fade" id="answerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Respond to Question</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="modalBody">
                        <!-- Content will be loaded via AJAX -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_question" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load question details into modal
        function loadQuestion(id) {
            fetch('get_question.php?id=' + id)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalBody').innerHTML = data;
                    const modal = new bootstrap.Modal(document.getElementById('answerModal'));
                    modal.show();
                });
        }
    </script>
</body>

</html>

<?php
// Helper functions
function get_count($conn, $status)
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM faq_questions WHERE status = ?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0];
}

function render_question_card($row)
{
    $date = date('M j, Y g:i a', strtotime($row['created_at']));
    $updated = $row['updated_at'] ? 'Updated: ' . date('M j, Y g:i a', strtotime($row['updated_at'])) : '';

    return '<div class="col-md-6 mb-4">
                <div class="card question-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge status-badge ' . $row['status'] . '">' . ucfirst($row['status']) . '</span>
                            <small class="text-muted">' . $date . '</small>
                        </div>
                        <h5 class="card-title">' . htmlspecialchars($row['name']) . '</h5>
                        <h6 class="card-subtitle mb-2 text-muted">' . htmlspecialchars($row['email']) . '</h6>
                        <p class="card-text mt-3">' . nl2br(htmlspecialchars($row['question'])) . '</p>
                        ' . ($row['answer'] ? '<div class="alert alert-light mt-3"><strong>Answer:</strong><br>' . nl2br(htmlspecialchars($row['answer'])) . '</div>' : '') . '
                    </div>
                    <div class="card-footer bg-white">
                        <button onclick="loadQuestion(' . $row['id'] . ')" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Respond
                        </button>
                        <small class="text-muted float-end">' . $updated . '</small>
                    </div>
                </div>
            </div>';
}
