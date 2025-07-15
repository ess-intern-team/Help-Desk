<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Help Desk</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #1e40af;
            margin: 0;
        }

        .role-switch {
            position: relative;
        }

        .role-switch select {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info span {
            margin-left: 10px;
        }

        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-box {
            text-align: center;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            width: 100px;
        }

        .requests {
            margin-bottom: 20px;
        }

        .request-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .request-item {
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 10px;
            background-color: #fff;
        }

        .request-item .status {
            color: #f59e0b;
            font-weight: bold;
        }

        button {
            background-color: #1e40af;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #1e3a8a;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>IT Help Desk</h1>
            <div class="role-switch">
                <select>
                    <option>User</option>
                    <option>Department Head</option>
                    <option>Specialist</option>
                </select>
            </div>
            <div class="user-info">
                <span>John Doe</span>
                <span>3</span>
            </div>
        </div>
        <div class="requests">
            <h2>My Requests</h2>
            <p>Track and manage your IT support requests</p>
            <div class="stats">
                <div class="stat-box">Total Requests <br> 1</div>
                <div class="stat-box">Pending <br> 1</div>
                <div class="stat-box">In Progress <br> 0</div>
                <div class="stat-box">Resolved <br> 0</div>
            </div>
            <div class="request-filter">
                <input type="text" placeholder="Search requests...">
                <select>
                    <option>All Status</option>
                </select>
                <button>New Request</button>
            </div>
            <div class="request-item">
                <span class="status">Computer Won't Start <span>pending</span> <span>high</span></span>
                <p>My computer suddenly stopped working this morning. No lights, no sounds.</p>
                <p>pending | Category: hardware | Created: 1/15/2024</p>
            </div>
        </div>
    </div>
</body>

</html>