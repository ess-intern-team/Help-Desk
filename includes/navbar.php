<!-- Navbar -->
<nav class="fixed top-0 left-0 right-0 z-40 flex items-center justify-between h-16 px-4 bg-white dark:bg-gray-900 shadow-md border-b border-gray-200 dark:border-gray-700 lg:ml-64 transition-all duration-300"
    :class="{ 'lg:ml-0': !sidebarOpen }">
    <!-- Mobile menu button -->
    <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="text-gray-500 dark:text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none lg:hidden">
        <i class="fas fa-bars text-xl"></i>
    </button>

    <!-- Brand/Logo (visible only on mobile when sidebar is closed) -->
    <div class="flex items-center lg:hidden" x-show="!mobileSidebarOpen">
        <span class="ml-2 text-xl font-bold text-gray-900 dark:text-white">HelpDesk Pro</span>
    </div>

    <!-- Search bar (optional, could be added here) -->
    <div class="flex-1 mx-4 hidden md:block">
        <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <i class="fas fa-search text-gray-400"></i>
            </span>
            <input type="text" placeholder="Search..."
                class="w-full pl-10 pr-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light text-sm">
        </div>
    </div>

    <!-- Right-side actions -->
    <div class="flex items-center space-x-4">
        <!-- Notifications -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none transition-colors duration-200">
                <i class="fas fa-bell text-lg"></i>
                <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white dark:ring-gray-900"></span>
            </button>
            <div x-show="open" @click.away="open = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50 border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 border-b border-gray-100 dark:border-gray-700">Notifications</div>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    New ticket #123 assigned to you.
                </a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    Your report is ready.
                </a>
                <div class="px-4 py-2 text-center text-xs text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700">
                    <a href="#" class="hover:underline">View all notifications</a>
                </div>
            </div>
        </div>

        <!-- User profile dropdown -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none">
                <img class="w-9 h-9 rounded-full border-2 border-primary dark:border-primary-light object-cover" src="https://placehold.co/100x100/A0AEC0/FFFFFF?text=JD" alt="User profile">
                <span class="hidden md:block text-sm font-medium text-gray-900 dark:text-white">John Doe</span>
                <i class="fas fa-chevron-down text-xs text-gray-500 dark:text-gray-400 hidden md:block"></i>
            </button>
            <div x-show="open" @click.away="open = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50 border border-gray-200 dark:border-gray-700">
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="fas fa-user-circle mr-2"></i> Profile
                </a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="fas fa-cog mr-2"></i> Settings
                </a>
                <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="fas fa-sign-out-alt mr-2"></i> Sign out
                </a>
            </div>
        </div>
    </div>
</nav>