<?php
session_start();

// Database Configuration
$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "helpdesk_db";

// Database Connection
try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->error);
    }
} catch (Exception $e) {
    die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['forward_ticket'])) {
    try {
        // Validate inputs
        $ticketId = (int)$_POST['ticket_id'];
        $forwardTo = htmlspecialchars($_POST['forward_to']);
        $forwardNotes = htmlspecialchars($_POST['forward_notes'] ?? '');
        $currentUser = $_SESSION['user'] ?? 'it_head_general';

        // Begin transaction
        $conn->begin_transaction();

        // 1. Get original ticket details
        $sql = "SELECT * FROM messages WHERE id = ? FOR UPDATE";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $ticketId);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $originalTicket = $result->fetch_assoc();
        $stmt->close();

        if (!$originalTicket) {
            throw new Exception("Original ticket not found.");
        }

        // 2. Create forwarded message
        $sql = "INSERT INTO messages (
                    sender, receiver, 
                    role_from, role_to, 
                    title, message, 
                    category, priority, 
                    related_ticket_id, status
                ) VALUES (?, ?, 'ithead', 'specialist', ?, ?, ?, ?, ?, 'forwarded')";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $title = "Fwd: " . $originalTicket['title'];
        $message = "Forwarded by: " . $currentUser . "\n\n" .
            "Original Message:\n" . $originalTicket['message'] . "\n\n" .
            "Forward Notes:\n" . $forwardNotes;
        $category = $originalTicket['category'];
        $priority = $originalTicket['priority'];

        $stmt->bind_param(
            "ssssssi",
            $currentUser,
            $forwardTo,
            $title,
            $message,
            $category,
            $priority,
            $ticketId
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();

        // 3. Update original ticket status
        $sql = "UPDATE messages SET status = 'forwarded' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $ticketId);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();

        // Commit transaction
        $conn->commit();

        $_SESSION['displayMessage'] = "Ticket forwarded to specialist successfully!";
        $_SESSION['messageType'] = "success";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['displayMessage'] = "Error: " . $e->getMessage();
        $_SESSION['messageType'] = "error";
        error_log("Forwarding error: " . $e->getMessage());
    }

    header("Location: ithead.php?user=" . urlencode($currentUser));
    exit();
}

$conn->close();
