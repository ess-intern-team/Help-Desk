<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details | IT Help Desk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom Variables for consistent theming */
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #eef2ff;
            --secondary: #3f37c9;
            --accent: #f72585;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #ef233c;
            --dark: #2b2d42;
            --light: #f8f9fa;
            --gray-base: #6c757d;
            --white: #ffffff;
        }

        /* Main container styling for consistency with auth pages */
        .main-container {
            background: rgba(31, 41, 55, 0.7);
            /* bg-gray-800 with transparency */
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(75, 85, 99, 0.5);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        /* Status badges */
        .status-open {
            background-color: #f8961e;
        }

        /* warning */
        .status-in-progress {
            background-color: #4361ee;
        }

        /* primary */
        .status-resolved {
            background-color: #4cc9f0;
        }

        /* success */
        .status-closed {
            background-color: #6c757d;
        }

        /* gray */

        /* Comment section styling */
        .comment-box {
            background-color: rgba(55, 65, 81, 0.8);
            /* bg-gray-700 with transparency */
            border: 1px solid rgba(107, 114, 128, 0.5);
        }

        .input-group textarea {
            background-color: rgba(55, 65, 81, 0.9);
            border-color: rgba(107, 114, 128, 0.7);
            color: #e2e8f0;
        }

        .input-group textarea::placeholder {
            color: #67e8f9;
            /* cyan-300 for placeholders */
        }

        .input-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px var(--primary);
            outline: none;
        }

        /* Animations for background blobs */
        @keyframes blob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }

            33% {
                transform: translate(30px, -50px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }

            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }

        .animate-blob {
            animation: blob 7s infinite;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }

        .animation-delay-4000 {
            animation-delay: 4s;
        }
    </style>
</head>

<body class="bg-gray-900 min-h-screen flex flex-col antialiased">
    <!-- Animated Background Elements -->
    <div class="fixed inset-0 overflow-hidden -z-10">
        <div class="absolute top-0 left-0 w-full h-full opacity-5">
            <div class="absolute top-20 left-10 w-64 h-64 bg-indigo-600 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob animation-delay-2000"></div>
            <div class="absolute top-60 right-20 w-64 h-64 bg-teal-600 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob"></div>
            <div class="absolute bottom-20 left-1/2 w-64 h-64 bg-orange-600 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob animation-delay-4000"></div>
        </div>
    </div>

    <!-- Navigation Bar -->
    <nav class="fixed w-full bg-gray-800/80 backdrop-blur-md border-b border-gray-700 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-lime-300">HelpDesk Pro</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="tickets.php" class="px-3 py-2 rounded-md text-sm font-medium text-white bg-primary-dark hover:bg-primary transition-colors">Tickets</a>
                    <a href="add_request.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-700 transition-colors">Create Ticket</a>
                    <a href="profile.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-700 transition-colors">Profile</a>
                    <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-red-400 hover:text-red-300 hover:bg-gray-700 transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="main-container rounded-xl p-8 md:p-10 shadow-lg max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-extrabold text-white">Ticket #12345: Network Connectivity Issue</h1>
                <span class="px-4 py-2 rounded-full text-sm font-semibold text-white status-open">Open</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8 mb-8">
                <div>
                    <p class="text-gray-400 text-sm">Category:</p>
                    <p class="text-white font-medium">Network</p>
                </div>
                <div>
                    <p class="text-gray-400 text-sm">Priority:</p>
                    <p class="text-white font-medium">High</p>
                </div>
                <div>
                    <p class="text-gray-400 text-sm">Created By:</p>
                    <p class="text-white font-medium">Seid Hussen</p>
                </div>
                <div>
                    <p class="text-gray-400 text-sm">Assigned To:</p>
                    <p class="text-white font-medium">John Doe (Network Team)</p>
                </div>
                <div>
                    <p class="text-gray-400 text-sm">Created On:</p>
                    <p class="text-white font-medium">2024-07-08 10:30 AM</p>
                </div>
                <div>
                    <p class="text-gray-400 text-sm">Last Updated:</p>
                    <p class="text-white font-medium">2024-07-08 02:15 PM</p>
                </div>
            </div>

            <div class="mb-8">
                <h2 class="text-xl font-semibold text-white mb-3">Description</h2>
                <div class="comment-box rounded-md p-4 text-gray-300">
                    <p>"My internet connection keeps dropping intermittently throughout the day, affecting my work calls and access to cloud applications. It seems to happen randomly, sometimes every few minutes, sometimes every hour. I've tried restarting my router and computer, but the issue persists. This is severely impacting my productivity."</p>
                </div>
            </div>

            <div class="mb-8">
                <h2 class="text-xl font-semibold text-white mb-3">Updates & Comments</h2>
                <div class="space-y-4">
                    <!-- Example Comment 1 -->
                    <div class="comment-box rounded-md p-4">
                        <div class="flex items-center mb-2">
                            <span class="font-semibold text-white mr-2">John Doe</span>
                            <span class="text-gray-400 text-sm">2024-07-08 01:00 PM</span>
                        </div>
                        <p class="text-gray-300">"Acknowledged ticket. Initiating remote diagnostic tools to check network stability and router logs. Will provide an update within the hour."</p>
                    </div>
                    <!-- Example Comment 2 -->
                    <div class="comment-box rounded-md p-4">
                        <div class="flex items-center mb-2">
                            <span class="font-semibold text-white mr-2">Seid Hussen</span>
                            <span class="text-gray-400 text-sm">2024-07-08 01:45 PM</span>
                        </div>
                        <p class="text-gray-300">"Thanks, John. Still experiencing drops. Let me know if you need me to try anything on my end."</p>
                    </div>
                </div>
            </div>

            <div class="mb-8">
                <h2 class="text-xl font-semibold text-white mb-3">Add a Comment</h2>
                <form class="space-y-4" action="process_add_comment.php" method="POST">
                    <input type="hidden" name="ticket_id" value="12345">
                    <div class="input-group">
                        <textarea name="comment_text" rows="3" required class="block w-full px-3 py-3 border rounded-md shadow-sm placeholder-cyan-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="Type your comment or update here..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-4">
                        <button type="submit" class="px-6 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors">
                            Post Comment
                        </button>
                        <!-- Example of a status update button (visible to IT teams) -->
                        <button type="button" class="px-6 py-2 bg-success text-white rounded-md hover:bg-success-dark transition-colors">
                            Resolve Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>

</html>