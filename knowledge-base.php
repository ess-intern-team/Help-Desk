<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Knowledge Base - ESSA Helpdesk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border-radius: 15px;
        }

        a.btn-outline-primary {
            min-width: 180px;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h3 class="mb-4">📚 Knowledge Base</h3>
        <div class="row row-cols-1 row-cols-md-2 g-4">

            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">🛜 Networking Problems</h5>
                        <p class="card-text">Restart your router, check Ethernet cables, reconnect to Wi-Fi. If unresolved, contact the network administrator or IT support.</p>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">💻 Software Issues</h5>
                        <p class="card-text">Make sure your software is up to date. Reinstall or repair the application. Check system compatibility and permissions.</p>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">🔐 Cybersecurity Concerns</h5>
                        <p class="card-text">Change your password immediately if you suspect a breach. Report phishing emails. Keep antivirus and firewall enabled.</p>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">🖥 Hardware or Maintenance Needs</h5>
                        <p class="card-text">Turn off the device before inspection. Check power cables, screen, keyboard. Clean with appropriate tools. For issues, submit a ticket.</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Back to Dashboard button centered at bottom -->
        <div class="text-center mt-5 mb-4">
            <a href="user_dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>

</html>