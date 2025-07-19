<?php
// Initialize session
session_start();

// Bypass authentication for development - REMOVE IN PRODUCTION
$_SESSION['user_id'] = 1; // Force authentication
$_SESSION['username'] = 'Admin'; // Set default username
$_SESSION['email'] = 'admin@example.com'; // Set default email

// Original authentication check (now bypassed)
if (!isset($_SESSION['user_id']) && false) {  // '&& false' disables the redirect
    header('Location: login.php');
    exit();
}

// Rest of your original includes directory creation code
if (!is_dir(__DIR__ . '/includes')) {
    mkdir(__DIR__ . '/includes', 0755, true);
}

$includes = ['header.php', 'navbar.php', 'sidebar.php', 'footer.php'];
foreach ($includes as $file) {
    if (!file_exists(__DIR__ . "/includes/$file")) {
        file_put_contents(__DIR__ . "/includes/$file", "<!-- $file -->");
    }
}

// Pagination logic
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 6;
$total_items = 0; // Will be set from database later
$total_pages = ceil($total_items / $per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management | HelpDesk Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* CSS Positioning Examples */
        .fixed-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
        }

        /* Modern Design Styles */
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }

        .main-content {
            padding-top: 64px;
        }

        .ticket-card {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .ticket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
        }

        .open-badge {
            background-color: #fef3c7;
            color: #92400e;
        }

        .progress-badge {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .resolved-badge {
            background-color: #dcfce7;
            color: #166534;
        }

        .closed-badge {
            background-color: #f3f4f6;
            color: #374151;
        }

        .priority-indicator {
            width: 12px;
            height: 12px;
            border-radius: 9999px;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .critical {
            background-color: #ef4444;
        }

        .high {
            background-color: #f97316;
        }

        .medium {
            background-color: #eab308;
        }

        .low {
            background-color: #10b981;
        }

        .empty-state {
            min-height: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .empty-state-icon {
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body class="antialiased">
    <!-- Fixed Navigation -->
    <nav class="fixed-nav bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-indigo-600">HelpDesk Pro</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="user_dashboard.php" class="ml-8 px-3 py-2 rounded-md text-sm font-medium text-black hover:text-primary hover:bg-gray-100 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                    </a>
                    <a href="tickets.php" class="px-3 py-2 rounded-md text-sm font-medium bg-indigo-100 text-indigo-700">Tickets</a>
                    <a href="add_request.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-indigo-600">Create Ticket</a>
                    <a href="profile.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-indigo-600">Profile</a>
                    <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Area - Full width since sidebar is removed -->
    <main class="main-content">
        <!-- Sticky Header -->
        <div class="sticky-header bg-white border-b border-gray-200 py-4 px-6">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Ticket Management</h1>
                    <p class="text-gray-600">View and manage all support requests</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="add_request.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                        <i class="fas fa-plus-circle mr-2"></i> New Ticket
                    </a>
                </div>
            </div>
        </div>

        <!-- Search/Filters -->
        <div class="relative-container bg-gray-50 px-6 py-4">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search Box -->
                    <div class="md:col-span-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Search tickets...">
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <select class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">All Statuses</option>
                            <option value="open">Open</option>
                            <option value="in-progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <!-- Priority Filter -->
                    <div>
                        <select class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">All Priorities</option>
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tickets Grid - Empty State -->
        <div class="px-6 py-6">
            <div class="max-w-7xl mx-auto">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No tickets found</h3>
                    <p class="text-gray-600 mb-6">Create your first ticket or check back later</p>
                    <a href="add_request.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                        <i class="fas fa-plus-circle mr-2"></i> Create Ticket
                    </a>
                </div>

                <!-- Functional Pagination -->
                <div class="mt-8 flex items-center justify-between">
                    <div class="hidden sm:block">
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?= ($current_page - 1) * $per_page + 1 ?></span> to
                            <span class="font-medium"><?= min($current_page * $per_page, $total_items) ?></span> of
                            <span class="font-medium"><?= $total_items ?></span> results
                        </p>
                    </div>
                    <div class="flex-1 flex justify-between sm:justify-end">
                        <a href="?page=<?= max(1, $current_page - 1) ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 <?= $current_page <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?>">
                            Previous
                        </a>
                        <div class="hidden sm:flex space-x-2 mx-4">
                            <?php for ($i = 1; $i <= min($total_pages, 5); $i++): ?>
                                <a href="?page=<?= $i ?>" class="<?= $i == $current_page ? 'bg-indigo-100 text-indigo-700' : 'text-gray-700' ?> px-3 py-1 rounded-md text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            <?php if ($total_pages > 5): ?>
                                <span class="text-gray-500 px-3 py-1">...</span>
                                <a href="?page=<?= $total_pages ?>" class="text-gray-700 px-3 py-1 rounded-md text-sm font-medium">
                                    <?= $total_pages ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <a href="?page=<?= min($total_pages, $current_page + 1) ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 <?= $current_page >= $total_pages ? 'opacity-50 cursor-not-allowed' : '' ?>">
                            Next
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Filter functionality
        document.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', function() {
                console.log('Filter changed:', this.value);
                // Implement actual filtering here when backend is ready
            });
        });

        // Search functionality
        document.querySelector('input[type="text"]').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                console.log('Search for:', this.value);
                // Implement search when backend is ready
            }
        });
    </script>
</body>

</html>