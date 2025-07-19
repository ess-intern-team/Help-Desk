<?php
session_start();
// For testing: set user_id in session if not set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create New Ticket | IT Help Desk</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Your existing CSS here (same as original) */
        body {
            background: linear-gradient(to bottom right, #f0f4ff, #e0f2f7);
            color: #4A5568;
            font-family: 'Inter', sans-serif;
        }

        .main-container {
            background: rgba(31, 41, 55, 0.7);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(75, 85, 99, 0.5);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            border-radius: 1rem;
            padding: 2rem;
            max-width: 600px;
            margin: auto;
        }

        /* Input styles... (keep as you like) */
        .input-group {
            position: relative;
        }

        .input-group input,
        .input-group textarea,
        .custom-select {
            padding-left: 40px;
            background-color: rgba(55, 65, 81, 0.9);
            border-color: rgba(107, 114, 128, 0.7);
            color: #e2e8f0;
            border-radius: 0.375rem;
            border-width: 1px;
            width: 100%;
            padding: 0.75rem 1rem;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .input-group textarea+.input-icon {
            top: 15px;
            transform: translateY(0);
        }

        .custom-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none'%3e%3cpath d='M7 8L10 11L13 8' stroke='%2394a3b8' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
            padding-left: 1rem;
        }

        button[type="submit"] {
            background-color: #6c63ff;
            color: white;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            width: 100%;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #564fd8;
        }
    </style>
</head>

<body>
    <nav style="background:#2d3748; padding:1rem; color:white;">
        <a href="admin_dashboard.php" style="color:white; text-decoration:none;">← Back to Dashboard</a>
    </nav>

    <main class="main-container">
        <h1 class="text-3xl font-extrabold text-white mb-6 text-center">Submit a New Ticket</h1>
        <p class="text-gray-300 mb-8 text-center">Please provide details about your issue.</p>

        <form id="ticketForm" enctype="multipart/form-data" novalidate>
            <div class="input-group mb-4">
                <label for="subject" class="block text-sm font-medium text-gray-300 mb-1">Subject</label>
                <i class="fas fa-heading input-icon"></i>
                <input id="subject" name="subject" type="text" required placeholder="e.g., Internet not working" />
            </div>

            <div class="input-group mb-4">
                <label for="description" class="block text-sm font-medium text-gray-300 mb-1">Description</label>
                <i class="fas fa-file-alt input-icon"></i>
                <textarea id="description" name="description" rows="5" required placeholder="Describe your issue in detail..."></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-300 mb-1">Category</label>
                    <select id="category" name="category" required class="custom-select">
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
                    <select id="priority" name="priority" required class="custom-select">
                        <option value="">Select priority</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>

            <button type="submit">Submit Ticket</button>
        </form>
    </main>

    <script>
        document.getElementById('ticketForm').addEventListener('submit', function(event) {
            event.preventDefault();

            // Collect form data
            const subject = document.getElementById('subject').value.trim();
            const description = document.getElementById('description').value.trim();
            const category = document.getElementById('category').value;
            const priority = document.getElementById('priority').value;
            const user_id = <?php echo json_encode($_SESSION['user_id']); ?>;

            if (!subject || !description || !category || !priority) {
                alert('Please fill in all required fields.');
                return;
            }

            fetch('http://localhost:3000/api/tickets', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        title: subject,
                        description: description,
                        category: category,
                        priority: priority,
                        user_id: user_id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                    } else if (data.message) {
                        alert(data.message + ' Ticket ID: ' + data.ticket_id);
                        document.getElementById('ticketForm').reset();
                    } else {
                        alert('Unexpected response from server.');
                    }
                })

        });
    </script>
</body>

</html>