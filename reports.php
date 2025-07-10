<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Reports | ESSA HelpDesk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(to right, #dbeafe, #e0f2fe, #ede9fe);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
        }

        .report-title {
            font-size: 2rem;
            font-weight: 700;
            color: #334155;
        }

        .card-glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            padding: 30px;
        }

        a.btn-outline-primary {
            min-width: 180px;
            font-weight: 600;
        }

        footer {
            margin-top: 40px;
            font-size: 14px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <h3 class="text-center mb-4 report-title">📊 Ticket Summary Reports</h3>

        <div class="card card-glass shadow-lg p-4">
            <h5 class="mb-3 text-center">Monthly Created Tickets</h5>
            <div class="chart-container">
                <canvas id="reportChart"></canvas>
            </div>
        </div>

        <!-- Back to Dashboard button centered at bottom -->
        <div class="text-center mt-5 mb-4">
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>

        <footer>
            <p>© 2025 ESSA IT HelpDesk – Addis Ababa, inside Ethio Ceramic Building</p>
        </footer>
    </div>

    <script>
        const ctx = document.getElementById('reportChart').getContext('2d');
        const reportChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June'], // Will be dynamic later
                datasets: [{
                    label: 'Tickets Per Month',
                    data: [0, 0, 0, 0, 0, 0], // Replace with backend data later
                    backgroundColor: '#6366f1', // Indigo
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Request Volume (Auto Updates After Backend)',
                        font: {
                            size: 18,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: '#0f172a',
                        titleColor: '#ffffff',
                        bodyColor: '#e0f2fe'
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#475569'
                        },
                        grid: {
                            color: '#cbd5e1'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#475569'
                        },
                        grid: {
                            color: '#f1f5f9'
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>