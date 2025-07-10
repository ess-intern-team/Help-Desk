    <!-- Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- Chart.js for reports -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom JS -->
    <script src="/assets/js/scripts.js"></script>

    <script>
        // Simple page routing for the sidebar links
        document.addEventListener('DOMContentLoaded', function() {
            // Dark mode toggle handler
            const darkModeToggle = document.getElementById('darkModeToggle');
            if (darkModeToggle) {
                darkModeToggle.addEventListener('change', function() {
                    document.documentElement.classList.toggle('dark', this.checked);
                    localStorage.setItem('darkMode', this.checked);
                });
            }

            // Initialize dark mode from localStorage
            if (localStorage.getItem('darkMode') === 'true') {
                document.documentElement.classList.add('dark');
                if (darkModeToggle) darkModeToggle.checked = true;
            }

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Initialize charts if on reports page
            if (window.location.pathname.includes('reports.php')) {
                initCharts();
            }
        });

        // Initialize charts for reports
        function initCharts() {
            // Ticket Status Chart
            const statusCtx = document.getElementById('ticketStatusChart');
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Open', 'In Progress', 'Resolved', 'Closed'],
                        datasets: [{
                            data: [24, 18, 86, 14],
                            backgroundColor: [
                                '#F59E0B',
                                '#3B82F6',
                                '#10B981',
                                '#6B7280'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            }
                        }
                    }
                });
            }

            // Ticket Priority Chart
            const priorityCtx = document.getElementById('ticketPriorityChart');
            if (priorityCtx) {
                new Chart(priorityCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Critical', 'High', 'Medium', 'Low'],
                        datasets: [{
                            label: 'Tickets by Priority',
                            data: [8, 22, 45, 67],
                            backgroundColor: [
                                '#8B5CF6',
                                '#EF4444',
                                '#F59E0B',
                                '#10B981'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Monthly Tickets Chart
            const monthlyCtx = document.getElementById('monthlyTicketsChart');
            if (monthlyCtx) {
                new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        datasets: [{
                            label: 'Tickets Created',
                            data: [45, 52, 68, 72, 86, 94, 102, 110, 95, 88, 76, 62],
                            fill: false,
                            borderColor: '#4F46E5',
                            tension: 0.1
                        }, {
                            label: 'Tickets Resolved',
                            data: [38, 45, 52, 60, 72, 78, 85, 92, 88, 76, 65, 58],
                            fill: false,
                            borderColor: '#10B981',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        }
    </script>
    </body>

    </html>