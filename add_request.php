<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Ticket | IT Help Desk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom Variables for consistent theming - ALIGNED WITH PROFILE.PHP */
        :root {
            --primary: #6C63FF;
            /* Vibrant purple */
            --primary-dark: #564FD8;
            --primary-light: #E9E8FF;
            --secondary: #4FD1C5;
            /* Teal */
            --accent: #FF6584;
            /* Pink */
            --success: #68D391;
            --warning: #F6AD55;
            --danger: #FC8181;
            --dark: #2D3748;
            --light: #F7FAFC;
            --bg-main: #F8F9FF;
            /* Light purple background - now mainly for reference if needed */
            --bg-card: #FFFFFF;
            /* Pure white cards - now mainly for reference if needed */
        }

        /* BODY BACKGROUND: Changed from solid black to a vibrant gradient */
        body {
            /* Tailwind gradient classes */
            background: linear-gradient(to bottom right, #f0f4ff, #e0f2f7);
            /* Soft blend of light blue and very light purple/white */
            color: #4A5568;
            /* Default text color, adjust as needed for elements outside main-container */
            font-family: 'Inter', sans-serif;
        }

        /* Main container styling - kept dark translucent to float on new light background */
        .main-container {
            background: rgba(31, 41, 55, 0.7);
            /* bg-gray-800 with transparency */
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(75, 85, 99, 0.5);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .input-group {
            position: relative;
        }

        .input-group input,
        .input-group textarea,
        .custom-select {
            padding-left: 40px;
            background-color: rgba(55, 65, 81, 0.9);
            /* Dark background for inputs */
            border-color: rgba(107, 114, 128, 0.7);
            color: #e2e8f0;
            /* Light text color for inputs */
        }

        .input-group input::placeholder,
        .input-group textarea::placeholder {
            color: #67e8f9;
            /* cyan-300 for placeholders */
        }

        .input-group input:focus,
        .input-group textarea:focus,
        .custom-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px var(--primary);
            outline: none;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            /* gray-400 for icons */
        }

        /* Specific icon positioning for textarea */
        .input-group textarea+.input-icon {
            top: 15px;
            /* Adjust for multiline textareas */
            transform: translateY(0);
        }

        .custom-select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none'%3e%3cpath d='M7 8L10 11L13 8' stroke='%2394a3b8' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
            /* Ensure space for the arrow */
            padding-left: 15px;
            /* Override default for select, as it won't have an icon inside */
        }

        /* Animations for background blobs (kept, will now float on light gradient) */
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

<body class="min-h-screen flex flex-col antialiased bg-gradient-to-br from-purple-100 to-indigo-200">
    <div class="fixed inset-0 overflow-hidden -z-10">
        <div class="absolute top-0 left-0 w-full h-full opacity-10">
            <div class="absolute top-20 left-10 w-64 h-64 bg-purple-400 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob animation-delay-2000"></div>
            <div class="absolute top-60 right-20 w-64 h-64 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob"></div>
            <div class="absolute bottom-20 left-1/2 w-64 h-64 bg-pink-400 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob animation-delay-4000"></div>
        </div>
    </div>

    <nav class="fixed w-full bg-gray-800/80 backdrop-blur-md border-b border-gray-700 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-primary">HelpDesk Pro</span>
                    <a href="dashboard.php" class="ml-8 px-3 py-2 rounded-md text-sm font-medium text-gray-200 hover:text-white hover:bg-gray-700 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="tickets.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-200 hover:text-white transition-colors">Tickets</a>
                    <a href="add_request.php" class="px-3 py-2 rounded-md text-sm font-medium text-white bg-primary hover:bg-primary-dark transition-colors">Add Request</a>
                    <a href="profile.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-200 hover:text-white transition-colors">Profile</a>
                    <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-red-400 hover:text-red-300 hover:bg-gray-700 transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-24 flex items-center justify-center">
        <div class="main-container rounded-xl p-8 md:p-10 shadow-lg max-w-2xl w-full">
            <h1 class="text-3xl font-extrabold text-white mb-6 text-center">Submit a New Ticket</h1>
            <p class="text-gray-300 mb-8 text-center">Please provide details about your issue.</p>

            <form class="space-y-6" action="process_create_ticket.php" method="POST" enctype="multipart/form-data">
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-300 mb-1">Subject</label>
                    <div class="input-group">
                        <i class="fas fa-heading input-icon"></i>
                        <input id="subject" name="subject" type="text" required class="block w-full px-3 py-3 border rounded-md shadow-sm placeholder-cyan-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="e.g., Internet not working">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-300 mb-1">Description</label>
                    <div class="input-group">
                        <i class="fas fa-file-alt input-icon"></i>
                        <textarea id="description" name="description" rows="5" required class="block w-full px-3 py-3 border rounded-md shadow-sm placeholder-cyan-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="Describe your issue in detail..."></textarea>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-300 mb-1">Category</label>
                        <select id="category" name="category" required class="custom-select block w-full px-3 py-3 border rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                            <option value="">Select a category</option>
                            <option value="network">Network</option>
                            <option value="software">Software</option>
                            <option value="hardware">Hardware</option>
                            <option value="security">Security</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-300 mb-1">Priority</label>
                        <select id="priority" name="priority" required class="custom-select block w-full px-3 py-3 border rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                            <option value="">Select priority</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="attachments" class="block text-sm font-medium text-gray-300 mb-1">Attachments (Optional)</label>
                    <input id="attachments" name="attachments[]" type="file" multiple
                        class="block w-full text-sm text-white bg-gray-800 border border-gray-600 rounded-md shadow-sm cursor-pointer
           file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
           file:text-sm file:font-semibold file:bg-primary file:text-white
           hover:file:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">

                    <p class="mt-2 text-sm text-gray-300">
                        📎 Max file size: <strong>5MB</strong>. Allowed types: <strong>JPG, PNG, PDF, DOCX</strong>.
                    </p>


                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        Submit Ticket
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>

</html>