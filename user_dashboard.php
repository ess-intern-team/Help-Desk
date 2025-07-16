<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Help Desk - My Requests</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Inter', sans-serif;
            /* Using Inter as per instructions */
            margin: 0;
            background-color: #f0f2f5;
            line-height: 1.6;
            color: #333;
        }

        .app-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
            /* Include padding in width */
        }

        .page-title {
            color: #333;
            margin-bottom: 5px;
            font-size: 2em;
            font-weight: 600;
        }

        .page-description {
            color: #666;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.1em;
        }

        /* Header Styles */
        .header {
            background-color: #fff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            flex-wrap: wrap;
            gap: 15px;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 22px;
            font-weight: bold;
            color: #333;
        }

        /* Simple SVG icon for IT Help Desk - replace with actual logo if available */
        .logo-icon {
            width: 32px;
            height: 32px;
            margin-right: 10px;
            vertical-align: middle;
            fill: #007bff;
            /* Color for the SVG icon */
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 25px;
            flex-wrap: wrap;
        }

        .switch-role label {
            margin-right: 10px;
            color: #555;
            font-size: 0.95em;
            font-weight: 500;
        }

        .role-dropdown {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 0.95em;
            background-color: #f9f9f9;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .role-dropdown:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            padding-right: 10px;
            border-right: 1px solid #eee;
        }

        .user-info span:first-child {
            font-weight: bold;
            color: #333;
        }

        .user-status {
            font-size: 14px;
            color: #777;
        }

        /* Dashboard Cards Styles */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: #fff;
            padding: 20px;
            border-radius: 12px;
            /* More rounded corners */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            /* Softer shadow */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            border-left: 6px solid;
            /* For colored left border */
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        .card h3 {
            margin-top: 0;
            color: #555;
            font-size: 1.1em;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 2.5em;
            /* Larger number */
            font-weight: bold;
            margin: 0;
            color: #222;
        }

        .info-icon {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #aaa;
            outline: none;
            padding: 5px;
            border-radius: 50%;
            transition: background-color 0.2s ease, color 0.2s ease;
            text-decoration: none;
            /* For anchor tag */
            display: inline-block;
            /* For anchor tag */
            line-height: 1;
            /* For anchor tag */
        }

        .info-icon:hover {
            background-color: #f0f0f0;
            color: #777;
        }

        /* Specific card colors for left border */
        .total-requests {
            border-color: #007bff;
        }

        /* Blue */
        .pending {
            border-color: #ffc107;
        }

        /* Orange/Yellow */
        .in-progress {
            border-color: #17a2b8;
        }

        /* Teal */
        .resolved {
            border-color: #28a745;
        }

        /* Green */

        /* Request List Styles */
        .request-list-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .controls {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input,
        .status-dropdown {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            min-width: 150px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .search-input:focus,
        .status-dropdown:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .status-dropdown {
            background-color: #f9f9f9;
            cursor: pointer;
        }

        .new-request-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s ease, transform 0.1s ease;
            text-decoration: none;
            /* For anchor tag */
        }

        .new-request-btn:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }

        .new-request-btn:active {
            transform: translateY(0);
        }

        .requests-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .request-card {
            border: 1px solid #eee;
            padding: 15px 20px;
            border-radius: 10px;
            background-color: #fcfcfc;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-left: 5px solid;
            /* For status color */
        }

        .request-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        .request-card h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.2em;
            font-weight: 600;
        }

        .status-tag {
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 0.8em;
            font-weight: bold;
            color: white;
            text-transform: capitalize;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* Specific tag colors (matching dashboard colors) */
        .status-tag.pending,
        .request-card.pending {
            background-color: #ffc107;
            border-color: #ffc107;
        }

        .status-tag.in-progress,
        .request-card.in-progress {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }

        .status-tag.resolved,
        .request-card.resolved {
            background-color: #28a745;
            border-color: #28a745;
        }

        /* Default for pending card if no specific background is set */
        .request-card.pending {
            background-color: #fff8e1;
        }

        /* Lighter yellow */
        .request-card.in-progress {
            background-color: #e0f7fa;
        }

        /* Lighter teal */
        .request-card.resolved {
            background-color: #e8f5e9;
        }

        /* Lighter green */


        .request-card p {
            color: #555;
            line-height: 1.5;
            margin-bottom: 10px;
            font-size: 0.95em;
        }

        .request-meta {
            font-size: 0.85em;
            color: #777;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .request-meta .icon {
            margin-right: 5px;
            color: #999;
        }

        /* Hidden class for JS filtering */
        .request-card.hidden {
            display: none;
        }

        /* Custom Modal Styles (kept for general messages) */
        .modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Stay in place */
            z-index: 1000;
            /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            /* Full width */
            height: 100%;
            /* Full height */
            overflow: auto;
            /* Enable scroll if needed */
            background-color: rgba(0, 0, 0, 0.4);
            /* Black w/ opacity */
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 30px;
            border: 1px solid #888;
            width: 80%;
            /* Could be responsive */
            max-width: 500px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-close-btn {
            color: #aaa;
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .modal-close-btn:hover,
        .modal-close-btn:focus {
            color: #333;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-header {
            font-size: 1.5em;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .modal-body {
            margin-bottom: 20px;
            color: #555;
            line-height: 1.6;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
        }

        .modal-footer button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: background-color 0.2s ease;
        }

        .modal-footer button:hover {
            background-color: #0056b3;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px 20px;
            }

            .user-section {
                width: 100%;
                justify-content: space-between;
                margin-top: 10px;
            }

            .user-info {
                border-right: none;
                padding-right: 0;
            }

            .main-content {
                padding: 15px;
            }

            .dashboard-cards {
                grid-template-columns: 1fr;
                /* Stack cards on small screens */
            }

            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input,
            .status-dropdown,
            .new-request-btn {
                width: 100%;
                min-width: unset;
            }
        }
    </style>
</head>

<body>

    <?php
    // --- PHP Data Simulation ---
    // This array will hold your request data. In a real application, this would come from a database.
    // For this frontend-only example, we'll manage it in JavaScript after initial render.
    $requests = [
        [
            'id' => 1,
            'title' => "Computer Won't Start",
            'status' => 'pending',
            'category' => 'hardware',
            'description' => 'My computer suddenly stopped working this morning. No lights, no sounds.',
            'createdDate' => '1/15/2024',
        ],
        [
            'id' => 2,
            'title' => 'Software Installation Issue',
            'status' => 'in progress',
            'category' => 'software',
            'description' => 'Need help installing the new accounting software. Getting an error code 0x80070005.',
            'createdDate' => '1/20/2024',
        ],
        [
            'id' => 3,
            'title' => 'Network Connectivity Problems',
            'status' => 'resolved',
            'category' => 'network',
            'description' => 'Cannot connect to the office Wi-Fi network from my laptop.',
            'createdDate' => '1/22/2024',
        ],
        [
            'id' => 4,
            'title' => 'Printer Not Responding',
            'status' => 'pending',
            'category' => 'hardware',
            'description' => 'The printer in room 305 is not responding to print commands.',
            'createdDate' => '1/25/2024',
        ],
    ];

    // Calculate dashboard counts based on the initial PHP data
    $totalRequests = count($requests);
    $pendingRequests = count(array_filter($requests, fn($req) => $req['status'] === 'pending'));
    $inProgressRequests = count(array_filter($requests, fn($req) => $req['status'] === 'in progress'));
    $resolvedRequests = count(array_filter($requests, fn($req) => $req['status'] === 'resolved'));

    // Get current user role from URL parameter, default to 'User'
    $currentUserRole = isset($_GET['role']) ? htmlspecialchars($_GET['role']) : 'User';
    ?>

    <div class="app-container">
        <!-- Header Section -->
        <header class="header">
            <div class="logo">
                <!-- Simple SVG for a gear icon -->
                <svg class="logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z" />
                    <path d="M12 6.5c-.83 0-1.5-.67-1.5-1.5S11.17 3.5 12 3.5s1.5.67 1.5 1.5S12.83 6.5 12 6.5zm0 11c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5.5 12c.83 0 1.5-.67 1.5-1.5S6.33 9 5.5 9s-1.5.67-1.5 1.5.67 1.5 1.5 1.5zM18.5 12c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM12 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5.5 6.5c.83 0 1.5-.67 1.5-1.5S6.33 3.5 5.5 3.5s-1.5.67-1.5 1.5.67 1.5 1.5 1.5zM18.5 6.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM12 1.5c-.83 0-1.5-.67-1.5-1.5S11.17 0 12 0s1.5.67 1.5 1.5S12.83 1.5 12 1.5zM12 22.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM1.5 12c.83 0 1.5-.67 1.5-1.5S2.33 9 1.5 9s-1.5.67-1.5 1.5.67 1.5 1.5 1.5zM22.5 12c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM18.5 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5.5 18.5c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5-1.5.67-1.5 1.5.67 1.5 1.5 1.5z" />
                </svg>
                IT Help Desk
            </div>
            <div class="user-section">
                <div class="switch-role">
                    <label for="role-select">Switch Role:</label>
                    <select
                        id="role-select"
                        class="role-dropdown"
                        onchange="window.location.href = '?role=' + this.value;">
                        <option value="User" <?php echo ($currentUserRole === 'User') ? 'selected' : ''; ?>>User</option>
                        <option value="Department Head" <?php echo ($currentUserRole === 'Department Head') ? 'selected' : ''; ?>>Department Head</option>
                        <option value="Specialist" <?php echo ($currentUserRole === 'Specialist') ? 'selected' : ''; ?>>Specialist</option>
                    </select>
                </div>
                <div class="user-info">
                    <span>John Doe</span>
                    <span class="user-status"><?php echo htmlspecialchars($currentUserRole); ?></span>
                </div>
            </div>
        </header>

        <div class="main-content">
            <h1 class="page-title">My Requests</h1>
            <p class="page-description">Track and manage your IT support requests</p>

            <!-- Dashboard Section -->
            <div class="dashboard-cards">
                <div class="card total-requests">
                    <h3>Total Requests</h3>
                    <p id="total-requests-count"><?php echo $totalRequests; ?></p>
                    <a href="info.php?type=total" class="info-icon" title="View details about Total Requests">i</a>
                </div>
                <div class="card pending">
                    <h3>Pending</h3>
                    <p id="pending-requests-count"><?php echo $pendingRequests; ?></p>
                    <a href="info.php?type=pending" class="info-icon" title="View details about Pending Requests">i</a>
                </div>
                <div class="card in-progress">
                    <h3>In Progress</h3>
                    <p id="in-progress-requests-count"><?php echo $inProgressRequests; ?></p>
                    <a href="info.php?type=in_progress" class="info-icon" title="View details about In Progress Requests">i</a>
                </div>
                <div class="card resolved">
                    <h3>Resolved</h3>
                    <p id="resolved-requests-count"><?php echo $resolvedRequests; ?></p>
                    <a href="info.php?type=resolved" class="info-icon" title="View details about Resolved Requests">&#10003;</a>
                </div>
            </div>

            <!-- Request List Section -->
            <div class="request-list-section">
                <div class="controls">
                    <input
                        type="text"
                        placeholder="Search requests..."
                        id="search-input"
                        class="search-input" />
                    <select
                        id="status-filter"
                        class="status-dropdown">
                        <option value="All Status">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="in progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                    </select>
                    <a href="new_request.php" id="new-request-button" class="new-request-btn">
                        + New Request
                    </a>
                </div>

                <div class="requests-container" id="requests-container">
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $request): ?>
                            <div class="request-card <?php echo str_replace(' ', '-', $request['status']); ?>"
                                data-id="<?php echo $request['id']; ?>"
                                data-status="<?php echo $request['status']; ?>"
                                data-title="<?php echo strtolower($request['title']); ?>"
                                data-description="<?php echo strtolower($request['description']); ?>"
                                data-category="<?php echo strtolower($request['category']); ?>"
                                data-created-date="<?php echo $request['createdDate']; ?>">
                                <h3>
                                    <?php echo htmlspecialchars($request['title']); ?>
                                    <span class="status-tag <?php echo str_replace(' ', '-', $request['status']); ?>">
                                        <?php echo htmlspecialchars(ucwords($request['status'])); ?>
                                    </span>
                                </h3>
                                <p><?php echo htmlspecialchars($request['description']); ?></p>
                                <div class="request-meta">
                                    <span><span class="icon">&#9200;</span> <?php echo htmlspecialchars(ucwords($request['status'])); ?></span>
                                    <span>Category: <?php echo htmlspecialchars(ucwords($request['category'])); ?></span>
                                    <span>Created: <?php echo htmlspecialchars($request['createdDate']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p id="no-requests-message">No requests found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Modal Structure (kept for general messages) -->
    <div id="custom-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close-btn">&times;</span>
            <h3 id="modal-header" class="modal-header"></h3>
            <div id="modal-body" class="modal-body"></div>
            <div class="modal-footer">
                <button id="modal-ok-btn">OK</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- DOM Element References ---
            const searchInput = document.getElementById('search-input');
            const statusFilter = document.getElementById('status-filter');
            const requestsContainer = document.getElementById('requests-container');
            const noRequestsMessage = document.getElementById('no-requests-message');

            const totalRequestsCount = document.getElementById('total-requests-count');
            const pendingRequestsCount = document.getElementById('pending-requests-count');
            const inProgressRequestsCount = document.getElementById('in-progress-requests-count');
            const resolvedRequestsCount = document.getElementById('resolved-requests-count');

            const customModal = document.getElementById('custom-modal');
            const modalHeader = document.getElementById('modal-header');
            const modalBody = document.getElementById('modal-body');
            const modalCloseBtn = document.querySelector('.modal-close-btn');
            const modalOkBtn = document.getElementById('modal-ok-btn');

            // --- Initial Data (Client-side mirror of PHP data) ---
            // We'll use this array in JS to manage requests dynamically.
            // In a real app, this would be fetched from an API.
            let requestsData = [
                <?php foreach ($requests as $request): ?> {
                        id: <?php echo $request['id']; ?>,
                        title: '<?php echo addslashes($request['title']); ?>',
                        status: '<?php echo addslashes($request['status']); ?>',
                        category: '<?php echo addslashes($request['category']); ?>',
                        description: '<?php echo addslashes($request['description']); ?>',
                        createdDate: '<?php echo addslashes($request['createdDate']); ?>'
                    },
                <?php endforeach; ?>
            ];

            // --- Helper Function to Show Custom Modal (Still available for general messages) ---
            function showModal(headerText, bodyText) {
                modalHeader.textContent = headerText;
                modalBody.textContent = bodyText;
                customModal.style.display = 'flex'; // Use flex to center
            }

            // --- Helper Function to Hide Custom Modal ---
            function hideModal() {
                customModal.style.display = 'none';
            }

            // --- Event Listeners for Modal ---
            modalCloseBtn.addEventListener('click', hideModal);
            modalOkBtn.addEventListener('click', hideModal);
            window.addEventListener('click', function(event) {
                if (event.target === customModal) {
                    hideModal();
                }
            });

            // --- Function to Render Request Cards ---
            function renderRequests(filteredData) {
                requestsContainer.innerHTML = ''; // Clear existing cards
                if (filteredData.length === 0) {
                    if (!noRequestsMessage) { // Create if it doesn't exist
                        const p = document.createElement('p');
                        p.id = 'no-requests-message';
                        p.textContent = 'No requests found.';
                        requestsContainer.appendChild(p);
                    } else { // Show if it exists but was hidden
                        noRequestsMessage.style.display = 'block';
                    }
                } else {
                    if (noRequestsMessage) { // Hide if it exists
                        noRequestsMessage.style.display = 'none';
                    }
                    filteredData.forEach(request => {
                        const card = document.createElement('div');
                        card.classList.add('request-card', request.status.replace(' ', '-'));
                        card.dataset.id = request.id;
                        card.dataset.status = request.status;
                        card.dataset.title = request.title.toLowerCase();
                        card.dataset.description = request.description.toLowerCase();
                        card.dataset.category = request.category.toLowerCase();
                        card.dataset.createdDate = request.createdDate;

                        card.innerHTML = `
                            <h3>
                                ${escapeHTML(request.title)}
                                <span class="status-tag ${request.status.replace(' ', '-')}">
                                    ${capitalizeFirstLetter(request.status)}
                                </span>
                            </h3>
                            <p>${escapeHTML(request.description)}</p>
                            <div class="request-meta">
                                <span><span class="icon">&#9200;</span> ${capitalizeFirstLetter(request.status)}</span>
                                <span>Category: ${capitalizeFirstLetter(request.category)}</span>
                                <span>Created: ${escapeHTML(request.createdDate)}</span>
                            </div>
                        `;
                        requestsContainer.appendChild(card);
                    });
                }
            }

            // --- Function to Update Dashboard Counts ---
            function updateDashboardCounts() {
                const total = requestsData.length;
                const pending = requestsData.filter(r => r.status === 'pending').length;
                const inProgress = requestsData.filter(r => r.status === 'in progress').length;
                const resolved = requestsData.filter(r => r.status === 'resolved').length;

                totalRequestsCount.textContent = total;
                pendingRequestsCount.textContent = pending;
                inProgressRequestsCount.textContent = inProgress;
                resolvedRequestsCount.textContent = resolved;
            }

            // --- Function to Filter Requests ---
            function filterRequests() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedStatus = statusFilter.value.toLowerCase();

                const filteredData = requestsData.filter(request => {
                    const matchesSearch = request.title.toLowerCase().includes(searchTerm) ||
                        request.description.toLowerCase().includes(searchTerm);
                    const matchesStatus = (selectedStatus === 'all status' || request.status === selectedStatus);
                    return matchesSearch && matchesStatus;
                });
                renderRequests(filteredData);
            }

            // --- Utility Functions ---
            function capitalizeFirstLetter(string) {
                return string.charAt(0).toUpperCase() + string.slice(1);
            }

            function escapeHTML(str) {
                const div = document.createElement('div');
                div.appendChild(document.createTextNode(str));
                return div.innerHTML;
            }

            // --- Event Listeners for Search and Filter ---
            searchInput.addEventListener('keyup', filterRequests);
            statusFilter.addEventListener('change', filterRequests);

            // --- Initial Render and Count Update ---
            filterRequests(); // Apply initial filters (which is none, so shows all)
            updateDashboardCounts(); // Ensure counts are up-to-date on load
        });
    </script>
</body>

</html>