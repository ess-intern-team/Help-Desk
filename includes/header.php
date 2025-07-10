<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' | ' : ''; ?>IT Help Desk Pro</title>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/styles.css">

    <style>
        /* Modern Color Scheme */
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --primary-light: #C7D2FE;
            --secondary: #10B981;
            --accent: #F59E0B;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --dark: #1F2937;
            --light: #F9FAFB;
            --bg-main: #F3F4F6;
        }

        body {
            background-color: var(--bg-main);
            color: #374151;
            font-family: 'Inter', sans-serif;
        }

        /* Glass Morphism Effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Hover Effects */
        .hover-scale {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .hover-scale:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        /* Status Badges */
        .status-badge {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
        }

        .status-open {
            @apply bg-yellow-100 text-yellow-800;
        }

        .status-in-progress {
            @apply bg-blue-100 text-blue-800;
        }

        .status-resolved {
            @apply bg-green-100 text-green-800;
        }

        .status-closed {
            @apply bg-gray-100 text-gray-800;
        }

        /* Priority Badges */
        .priority-badge {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
        }

        .priority-critical {
            @apply bg-purple-100 text-purple-800;
        }

        .priority-high {
            @apply bg-red-100 text-red-800;
        }

        .priority-medium {
            @apply bg-yellow-100 text-yellow-800;
        }

        .priority-low {
            @apply bg-green-100 text-green-800;
        }

        /* Category Badges */
        .category-badge {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
        }

        .category-networking {
            @apply bg-indigo-100 text-indigo-800;
        }

        .category-software {
            @apply bg-blue-100 text-blue-800;
        }

        .category-security {
            @apply bg-pink-100 text-pink-800;
        }

        .category-hardware {
            @apply bg-cyan-100 text-cyan-800;
        }

        /* Animation for cards */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        /* Data table styles */
        .data-table th {
            @apply px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider;
        }

        .data-table td {
            @apply px-6 py-4 whitespace-nowrap text-sm;
        }

        .data-table tr:nth-child(even) {
            @apply bg-gray-50;
        }

        /* Form styles */
        .form-input {
            @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm;
        }

        .form-label {
            @apply block text-sm font-medium text-gray-700 mb-1;
        }

        .form-select {
            @apply mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md;
        }
    </style>
</head>

<body class="h-full">
    <!-- ✅ Top Navbar -->
    <header class="w-full bg-white shadow-sm fixed top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-xl font-bold text-indigo-600 hover:text-indigo-800 flex items-center">
                        <i class="fas fa-headset mr-2 text-indigo-500"></i>ESSA Helpdesk
                    </a>
                </div>

                <!-- Navigation -->
                <div class="hidden md:flex space-x-4">
                    <a href="dashboard.php" class="text-sm font-medium text-gray-700 hover:text-indigo-600">Dashboard</a>
                    <a href="tickets.php" class="text-sm font-medium text-gray-700 hover:text-indigo-600">Tickets</a>
                    <a href="add_request.php" class="text-sm font-medium text-gray-700 hover:text-indigo-600">Add Request</a>
                    <a href="reports.php" class="text-sm font-medium text-gray-700 hover:text-indigo-600">Reports</a>
                    <a href="knowledge-base.php" class="text-sm font-medium text-gray-700 hover:text-indigo-600">Knowledge</a>
                    <a href="settings.php" class="text-sm font-medium text-gray-700 hover:text-indigo-600">Settings</a>
                </div>

                <!-- Right Menu -->
                <div class="flex items-center space-x-4">
                    <!-- Theme toggle (future use) -->
                    <button class="hidden md:inline text-gray-500 hover:text-indigo-600" title="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>

                    <!-- User Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 text-sm text-gray-700 hover:text-indigo-600 focus:outline-none">
                            <img src="/assets/images/default-avatar.png" alt="Avatar" class="w-8 h-8 rounded-full shadow-sm">
                            <span class="hidden md:inline">Hi, <?= $_SESSION['user_name'] ?? 'Guest'; ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>

                        <div x-show="open" @click.away="open = false"
                            x-transition
                            class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg z-50 py-2">
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">👤 Profile</a>
                            <a href="activity_log.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">📜 Activity Log</a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-100">🚪 Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Spacer to prevent content hiding behind fixed header -->
    <div class="h-16"></div>
</body>

</html>