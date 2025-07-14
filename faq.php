<?php
// FAQ.php - Modern FAQ System for ESSA Helpdesk

// Database configuration
$db_host = 'localhost';
$db_user = 'root'; // XAMPP default
$db_pass = ''; // XAMPP default
$db_name = 'helpdesk_db';

// Create connection with error handling
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Set charset to utf8mb4 for full Unicode support
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("<div class='alert alert-danger container mt-5'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// Page metadata
$page_title = "Frequently Asked Questions | ESSA Helpdesk";
$page_description = "Find instant answers to common questions about our services and support.";

// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- Favicon -->
    <link rel="icon" href="assets/favicon.ico" type="image/x-icon">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Custom CSS -->
    <style>
        :root {
            --essa-primary: #4e73df;
            --essa-secondary: #858796;
            --essa-success: #1cc88a;
            --essa-info: #36b9cc;
            --essa-warning: #f6c23e;
            --essa-danger: #e74a3b;
            --essa-light: #f8f9fc;
            --essa-dark: #5a5c69;
        }

        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
        }

        .faq-header {
            background: linear-gradient(135deg, var(--essa-primary) 0%, #224abe 100%);
            color: white;
            border-radius: 0.35rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .accordion-button:not(.collapsed) {
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--essa-primary);
            font-weight: 600;
        }

        .accordion-button:focus {
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }

        .ask-question-card {
            border-left: 0.25rem solid var(--essa-primary);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        .quick-links .list-group-item {
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .quick-links .list-group-item:hover {
            background-color: rgba(78, 115, 223, 0.05);
            border-left: 3px solid var(--essa-primary);
        }

        .search-box {
            position: relative;
        }

        .search-box .form-control {
            padding-left: 2.5rem;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--essa-secondary);
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-headset me-2"></i>ESSA Helpdesk
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="tickets.php"><i class="fas fa-ticket-alt me-1"></i> Tickets</a></li>
                    <li class="nav-item"><a class="nav-link" href="add_request.php"><i class="fas fa-plus-circle me-1"></i> Add Request</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-1"></i> Reports</a></li>
                    <li class="nav-item"><a class="nav-link active" href="faq.php"><i class="fas fa-question-circle me-1"></i> Knowledge</a></li>
                    <li class="nav-item"><a class="nav-link" href="settings.php"><i class="fas fa-cog me-1"></i> Settings</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=Guest&background=random" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                            <span>Hi, Guest</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="activity_log.php"><i class="fas fa-scroll me-2"></i> Activity Log</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <!-- Header Section -->
        <div class="faq-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-question-circle me-2"></i>Frequently Asked Questions</h1>
                    <p class="lead mb-0">Find instant answers to common questions about our services and support.</p>
                </div>
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" placeholder="Search FAQs..." id="faqSearch">
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <div class="col-lg-8">
                <?php
                // Display success/error messages
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_question'])) {
                    $name = $conn->real_escape_string($_POST['name'] ?? '');
                    $email = $conn->real_escape_string($_POST['email'] ?? '');
                    $question = $conn->real_escape_string($_POST['question'] ?? '');

                    try {
                        $stmt = $conn->prepare("INSERT INTO faq_questions (name, email, question) VALUES (?, ?, ?)");
                        $stmt->bind_param("sss", $name, $email, $question);

                        if ($stmt->execute()) {
                            echo '<div class="alert alert-success alert-dismissible fade show">
                                    <i class="fas fa-check-circle me-2"></i>Thank you! Your question has been submitted.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                  </div>';
                        } else {
                            throw new Exception("Database error");
                        }
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle me-2"></i>Error submitting question. Please try again.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>';
                    }
                }

                // FAQ Categories
                $categories = [
                    'general' => ['General Questions', 'fas fa-question'],
                    'technical' => ['Technical Support', 'fas fa-laptop-code'],
                    'billing' => ['Billing & Payments', 'fas fa-credit-card'],
                    'account' => ['Account Management', 'fas fa-user-cog']
                ];

                foreach ($categories as $category => $details) {
                    echo '<div class="card mb-4">
                            <div class="card-header bg-white">
                                <h2 class="h5 mb-0"><i class="' . $details[1] . ' me-2"></i>' . $details[0] . '</h2>
                            </div>
                            <div class="card-body">';

                    try {
                        $stmt = $conn->prepare("SELECT id, question, answer FROM faqs WHERE category = ? AND status = 'published' ORDER BY display_order");
                        $stmt->bind_param("s", $category);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            echo '<div class="accordion" id="' . $category . 'Accordion">';

                            while ($row = $result->fetch_assoc()) {
                                $itemId = $category . '-' . $row['id'];
                                echo '<div class="accordion-item border-0 mb-2">
                                        <h3 class="accordion-header" id="heading' . $itemId . '">
                                            <button class="accordion-button collapsed shadow-none rounded" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#collapse' . $itemId . '">
                                                ' . htmlspecialchars($row['question']) . '
                                            </button>
                                        </h3>
                                        <div id="collapse' . $itemId . '" class="accordion-collapse collapse" 
                                             data-bs-parent="#' . $category . 'Accordion">
                                            <div class="accordion-body pt-0">
                                                ' . nl2br(htmlspecialchars($row['answer'])) . '
                                            </div>
                                        </div>
                                      </div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="text-center py-4">
                                    <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">No FAQs found in this category</p>
                                  </div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="alert alert-warning">Unable to load FAQs at this time.</div>';
                    }

                    echo '</div></div>';
                }
                ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Ask Question Card -->
                <div class="card ask-question-card mb-4">
                    <div class="card-body">
                        <h3 class="h5 card-title"><i class="fas fa-paper-plane me-2"></i>Ask a Question</h3>
                        <p class="text-muted">Can't find what you're looking for? Submit your question and we'll get back to you.</p>

                        <form method="post">
                            <div class="mb-3">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="question" class="form-label">Your Question</label>
                                <textarea class="form-control" id="question" name="question" rows="4" required></textarea>
                            </div>
                            <button type="submit" name="submit_question" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i>Submit Question
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h3 class="h5 mb-0"><i class="fas fa-link me-2"></i>Quick Links</h3>
                    </div>
                    <div class="list-group list-group-flush quick-links">
                        <a href="contact.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-envelope me-2"></i>Contact Support
                        </a>
                        <a href="knowledgebase.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-book me-2"></i>Knowledge Base
                        </a>
                        <a href="tutorials.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-video me-2"></i>Video Tutorials
                        </a>
                        <a href="community.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-users me-2"></i>Community Forum
                        </a>
                        <a href="status.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-server me-2"></i>System Status
                        </a>
                    </div>
                </div>

                <!-- Stats Widget -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h3 class="h5 mb-0"><i class="fas fa-chart-pie me-2"></i>Knowledge Stats</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $stats = $conn->query("SELECT 
                                (SELECT COUNT(*) FROM faqs WHERE status = 'published') AS total_faqs,
                                (SELECT COUNT(*) FROM faq_questions WHERE status = 'answered') AS answered_questions,
                                (SELECT COUNT(*) FROM knowledge_articles) AS knowledge_articles")->fetch_assoc();

                            echo '<div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Published FAQs:</span>
                                    <span class="fw-bold">' . ($stats['total_faqs'] ?? 0) . '</span>
                                  </div>
                                  <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Answered Questions:</span>
                                    <span class="fw-bold">' . ($stats['answered_questions'] ?? 0) . '</span>
                                  </div>
                                  <div class="d-flex justify-content-between">
                                    <span class="text-muted">Knowledge Articles:</span>
                                    <span class="fw-bold">' . ($stats['knowledge_articles'] ?? 0) . '</span>
                                  </div>';
                        } catch (Exception $e) {
                            echo '<p class="text-muted">Stats unavailable</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-headset me-2"></i>ESSA Helpdesk</h5>
                    <p class="text-muted">24/7 support for all your technical needs.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="about.php" class="text-decoration-none text-muted">About Us</a></li>
                        <li><a href="contact.php" class="text-decoration-none text-muted">Contact</a></li>
                        <li><a href="privacy.php" class="text-decoration-none text-muted">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Connect</h5>
                    <div class="social-links">
                        <a href="#" class="text-muted me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-muted me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-muted me-2"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4 bg-secondary">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?= date('Y') ?> ESSA Helpdesk. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">v1.0.0</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <!-- Custom Script -->
    <script>
        // FAQ Search Functionality
        document.getElementById('faqSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const accordionItems = document.querySelectorAll('.accordion-item');

            accordionItems.forEach(item => {
                const question = item.querySelector('.accordion-button').textContent.toLowerCase();
                const answer = item.querySelector('.accordion-body')?.textContent.toLowerCase() || '';

                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';

                    // Open matching items
                    const collapse = item.querySelector('.accordion-collapse');
                    if (collapse && !collapse.classList.contains('show')) {
                        new bootstrap.Collapse(collapse, {
                            toggle: true
                        });
                    }
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Theme switcher
        const storedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    </script>
</body>

</html>
<?php
// Close database connection
$conn->close();

// Flush output buffer
ob_end_flush();
?>