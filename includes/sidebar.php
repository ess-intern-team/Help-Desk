<!-- Sidebar -->
<div class="fixed inset-y-0 left-0 z-50 flex flex-col w-64 transition-all duration-300 transform bg-white dark:bg-gray-800 shadow-xl"
    :class="{ '-translate-x-full': !sidebarOpen && !mobileSidebarOpen }">

    <!-- Sidebar header -->
    <div class="flex items-center justify-between h-16 px-4 border-b dark:border-gray-700">
        <div class="flex items-center">
            <span class="ml-2 text-xl font-bold text-indigo-600 dark:text-indigo-400">HelpDesk Pro</span>
        </div>
        <button @click="sidebarOpen = false; mobileSidebarOpen = false" class="text-gray-500 dark:text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 lg:hidden">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Sidebar content -->
    <div class="flex flex-col flex-1 overflow-y-auto">
        <nav class="flex-1 px-2 py-4 space-y-1">
            <!-- Dashboard Link -->
            <a href="index.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-indigo-50 dark:bg-gray-700 text-indigo-700 dark:text-indigo-300 border-r-4 border-indigo-600' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                <i class="fas fa-tachometer-alt w-5 mr-3 text-center"></i>
                <span>Dashboard</span>
            </a>

            <!-- Tickets Link -->
            <a href="tickets.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'tickets.php' ? 'bg-indigo-50 dark:bg-gray-700 text-indigo-700 dark:text-indigo-300 border-r-4 border-indigo-600' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                <i class="fas fa-ticket-alt w-5 mr-3 text-center"></i>
                <span>Tickets</span>
                <span class="ml-auto inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-indigo-600 rounded-full">24</span>
            </a>

            <!-- Create Ticket Link -->
            <a href="create-ticket.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'create-ticket.php' ? 'bg-indigo-50 dark:bg-gray-700 text-indigo-700 dark:text-indigo-300 border-r-4 border-indigo-600' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                <i class="fas fa-plus-circle w-5 mr-3 text-center"></i>
                <span>Create Ticket</span>
            </a>

            <!-- Users Link -->
            <a href="users.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-indigo-50 dark:bg-gray-700 text-indigo-700 dark:text-indigo-300 border-r-4 border-indigo-600' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                <i class="fas fa-users w-5 mr-3 text-center"></i>
                <span>Users</span>
            </a>

            <!-- Reports Link -->
            <a href="reports.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'bg-indigo-50 dark:bg-gray-700 text-indigo-700 dark:text-indigo-300 border-r-4 border-indigo-600' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                <i class="fas fa-chart-bar w-5 mr-3 text-center"></i>
                <span>Reports</span>
            </a>

            <!-- Knowledge Base Link -->
            <a href="knowledge-base.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'knowledge-base.php' ? 'bg-indigo-50 dark:bg-gray-700 text-indigo-700 dark:text-indigo-300 border-r-4 border-indigo-600' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                <i class="fas fa-book w-5 mr-3 text-center"></i>
                <span>Knowledge Base</span>
            </a>

            <!-- Settings Link -->
            <a href="settings.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-indigo-50 dark:bg-gray-700 text-indigo-700 dark:text-indigo-300 border-r-4 border-indigo-600' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                <i class="fas fa-cog w-5 mr-3 text-center"></i>
                <span>Settings</span>
            </a>
        </nav>

        <!-- Sidebar footer -->
        <div class="p-4 border-t dark:border-gray-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <img class="w-10 h-10 rounded-full" src="https://ui-avatars.com/api/?name=Admin+User&background=4F46E5&color=fff" alt="Admin User">
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Admin User</p>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Administrator</p>
                </div>
            </div>
            <div class="mt-3 space-y-2">
                <a href="profile.php" class="block px-3 py-2 text-sm font-medium text-center rounded-md text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-gray-700 hover:bg-indigo-100 dark:hover:bg-gray-600">
                    <i class="fas fa-user mr-2"></i> Profile
                </a>
                <a href="logout.php" class="block px-3 py-2 text-sm font-medium text-center rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-sign-out-alt mr-2"></i> Sign out
                </a>
            </div>
        </div>
    </div>
</div>