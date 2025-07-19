<!-- ✅ view_requests.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Help Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h3 class="mb-4">📋 View All Help Requests</h3>
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>User</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <!-- Replace with dynamic rows -->
                <tr>
                    <td>1</td>
                    <td>Example Issue</td>
                    <td><span class="badge bg-warning text-dark">Pending</span></td>
                    <td>High</td>
                    <td>John Doe</td>
                    <td>2025-07-09</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-center text-muted">Dynamic data to be loaded here from database</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>