document.addEventListener('DOMContentLoaded', function() {
    // File upload display
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
            const fileDisplay = this.nextElementSibling.nextElementSibling;
            fileDisplay.textContent = fileName;
        });
    });
    
    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    }
    
    // Ticket category selection effect
    const categorySelect = document.getElementById('category');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            // You can add logic here to show/hide fields based on category
        });
    }
});

// Chart initialization for dashboard
function initTicketChart() {
    const ctx = document.getElementById('ticketChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Networking', 'Software', 'Security', 'Hardware', 'Other'],
            datasets: [{
                data: [25, 40, 15, 30, 10],
                backgroundColor: [
                    'rgba(114, 9, 183, 0.8)',
                    'rgba(72, 149, 239, 0.8)',
                    'rgba(247, 37, 133, 0.8)',
                    'rgba(76, 201, 240, 0.8)',
                    'rgba(108, 117, 125, 0.8)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}