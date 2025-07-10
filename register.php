<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | IT Help Desk</title>
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
            /* Used for hover states on primary elements */
            --secondary: #3f37c9;
            /* Keep for potential accents */
            --accent: #f72585;
            /* Keep for potential accents */
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #ef233c;
            --dark: #2b2d42;
            /* Reference for very dark elements */
            --light: #f8f9fa;
            /* Reference for very light elements */
            --gray-base: #6c757d;
            /* Base gray for general text/icons */
            --white: #ffffff;
        }

        /* Updated auth-bg for a subtle dark gradient */
        .auth-bg {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.2) 0%, rgba(63, 55, 201, 0.1) 100%);
        }

        /* auth-container now has a subtle glassmorphism effect on dark background */
        .auth-container {
            background: rgba(31, 41, 55, 0.7);
            /* bg-gray-800 with transparency, slightly adjusted for purple theme */
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(75, 85, 99, 0.5);
            /* Lighter border for definition */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            /* Stronger shadow for depth */
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            padding-left: 40px;
            /* Adjust input field colors for dark theme */
            background-color: rgba(55, 65, 81, 0.9);
            /* bg-gray-700 with slight transparency */
            border-color: rgba(107, 114, 128, 0.7);
            /* gray-500 with transparency */
            color: #e2e8f0;
            /* light gray text for inputs */
        }

        /* UPDATED: Placeholder color to cyan-300 */
        .input-group input::placeholder {
            color: #67e8f9;
            /* cyan-300 for placeholders */
        }

        .input-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px var(--primary);
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            /* gray-400 for icons */
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            /* gray-400 for icons */
            cursor: pointer;
        }

        /* Custom style for select element */
        .custom-select {
            background-color: rgba(55, 65, 81, 0.9);
            border-color: rgba(107, 114, 128, 0.7);
            color: #e2e8f0;
            padding-left: 15px;
            /* Adjust padding for select as it doesn't have an icon by default */
            -webkit-appearance: none;
            /* Remove default arrow on WebKit browsers */
            -moz-appearance: none;
            /* Remove default arrow on Firefox */
            appearance: none;
            /* Remove default arrow */
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none'%3e%3cpath d='M7 8L10 11L13 8' stroke='%2394a3b8' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3e%3c/svg%3e");
            /* Custom arrow */
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1.5em 1.5em;
        }

        .custom-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px var(--primary);
            outline: none;
        }

        /* Checkbox style adjustment for dark theme */
        input[type="checkbox"] {
            border-color: rgba(107, 114, 128, 0.7);
            /* dark gray border */
            background-color: rgba(55, 65, 81, 0.9);
            /* dark gray background */
        }

        input[type="checkbox"]:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        /* Animations for background blobs (repeated for self-containment) */
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

<body class="bg-gray-900 min-h-screen flex items-center justify-center p-4 antialiased"> <!-- UPDATED: Body background to gray-900 -->
    <!-- Animated Background Elements - adapted from index.php -->
    <div class="fixed inset-0 overflow-hidden -z-10">
        <div class="absolute top-0 left-0 w-full h-full opacity-5">
            <!-- Adjusted blob colors for more vibrancy against dark background -->
            <div class="absolute top-20 left-10 w-64 h-64 bg-indigo-600 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob animation-delay-2000"></div>
            <div class="absolute top-60 right-20 w-64 h-64 bg-teal-600 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob"></div>
            <div class="absolute bottom-20 left-1/2 w-64 h-64 bg-orange-600 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob animation-delay-4000"></div>
        </div>
    </div>

    <div class="auth-container rounded-xl overflow-hidden max-w-4xl w-full flex flex-col md:flex-row shadow-lg relative z-10">
        <!-- Left Side - Form -->
        <div class="w-full md:w-full p-8 md:p-12">
            <div class="text-center md:text-left">
                <div class="flex justify-center md:justify-start">
                    <!-- Removed the SVG icon -->
                    <span class="ml-2 text-xl font-bold text-lime-300 self-center">HelpDesk Pro</span>
                </div>
                <h1 class="mt-8 text-3xl font-extrabold text-white">Create Your Account</h1>
                <p class="mt-2 text-gray-300">Start your 14-day free trial</p>
            </div>

            <form class="mt-8 space-y-6" action="process_register.php" method="POST">
                <div class="rounded-md shadow-sm space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="first-name" class="block text-sm font-medium text-gray-300 mb-1">First name</label>
                            <div class="input-group">
                                <i class="fas fa-user input-icon"></i>
                                <input id="first-name" name="first-name" type="text" autocomplete="given-name" required class="appearance-none block w-full px-3 py-3 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="Seid" value="Seid">
                            </div>
                        </div>
                        <div>
                            <label for="last-name" class="block text-sm font-medium text-gray-300 mb-1">Last name</label>
                            <div class="input-group">
                                <i class="fas fa-user input-icon"></i>
                                <input id="last-name" name="last-name" type="text" autocomplete="family-name" required class="appearance-none block w-full px-3 py-3 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="Hussen" value="Hussen">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope input-icon"></i>
                            <input id="email" name="email" type="email" autocomplete="email" required class="appearance-none block w-full px-3 py-3 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="you@example.com">
                        </div>
                    </div>
                    <div>
                        <label for="company" class="block text-sm font-medium text-gray-300 mb-1">Company (Optional)</label>
                        <div class="input-group">
                            <i class="fas fa-building input-icon"></i>
                            <input id="company" name="company" type="text" autocomplete="organization" class="appearance-none block w-full px-3 py-3 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="Your Company">
                        </div>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input id="password" name="password" type="password" autocomplete="new-password" required class="appearance-none block w-full px-3 py-3 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm pr-10" placeholder="••••••••">
                            <i class="far fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">Must be at least 8 characters</p>
                    </div>
                    <div>
                        <label for="confirm-password" class="block text-sm font-medium text-gray-300 mb-1">Confirm Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input id="confirm-password" name="confirm-password" type="password" autocomplete="new-password" required class="appearance-none block w-full px-3 py-3 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm pr-10" placeholder="••••••••">
                            <i class="far fa-eye password-toggle" id="toggleConfirmPassword"></i>
                        </div>
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-300 mb-1">I am a...</label>
                        <select id="role" name="role" required class="custom-select block w-full px-3 py-3 border rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                            <option value="">Select your role</option>
                            <option value="user">End User (Need IT Support)</option>
                            <option value="network_team">Network Team Member</option>
                            <option value="software_team">Software Team Member</option>
                            <option value="security_team">Security Team Member</option>
                            <option value="hardware_team">Hardware Team Member</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center">
                    <input id="terms" name="terms" type="checkbox" required class="h-4 w-4 text-primary focus:ring-primary border-gray-600 rounded">
                    <label for="terms" class="ml-2 block text-sm text-gray-300">
                        I agree to the <a href="#" class="font-medium text-primary hover:text-primary-light">Terms of Service</a> and <a href="#" class="font-medium text-primary hover:text-primary-light">Privacy Policy</a>
                    </label>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-primary-light group-hover:text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                            </svg>
                        </span>
                        Create Account
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-600"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-gray-800 text-gray-400">Or sign up with</span>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3">
                    <div>
                        <a href="#" class="w-full inline-flex justify-center py-3 px-6 border border-blue-800 rounded-md shadow-sm bg-blue-700 text-sm font-medium text-white hover:bg-blue-600 transition-colors">
                            Google
                            <i class="fab fa-google text-red-400 ml-2"></i>
                        </a>
                    </div>
                    <div>
                        <a href="#" class="w-full inline-flex justify-center py-3 px-6 border border-blue-800 rounded-md shadow-sm bg-blue-700 text-sm font-medium text-white hover:bg-blue-600 transition-colors">
                            Microsoft
                            <i class="fab fa-microsoft text-blue-400 ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-300">
                    Already have an account?
                    <a href="login.php" class="font-medium text-primary hover:text-primary-light">Sign in</a>
                </p>
            </div>
        </div>

        <!-- Removed the entire right side image/text block -->
    </div>

    <script>
        // Password toggle functionality
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
        const confirmPassword = document.querySelector('#confirm-password');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>