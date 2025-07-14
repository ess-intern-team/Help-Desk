<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Seid Hussen';
    $_SESSION['role'] = 'Admin';
}

// የዳታቤዝ ግንኙነት ፋይል አስገባ
require_once 'db_connect.php';

// የተጠቃሚ መረጃ ከሴሽን ያግኙ
$userId = $_SESSION['user_id'];
$departmentId = $_SESSION['department_id'] ?? null; // የዲፓርትመንት ID ከሴሽን

// የዲፓርትመንት ስም ከዳታቤዝ ያግኙ
$departmentName = 'ያልታወቀ ዲፓርትመንት';
if ($departmentId) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
        $stmt->execute([$departmentId]);
        $departmentName = $stmt->fetchColumn() ?? $departmentName;
    } catch (PDOException $e) {
        error_log("የዲፓርትመንት ስም በማምጣት ላይ ስህተት: " . $e->getMessage());
    }
}

// የሚሰሩ የጥያቄ ምድቦች እና ተዛማጅ የአይቲ ስፔሻሊስቶች (ለምድብ)
$validCategories = [
    'የኔትወርክ ችግሮች' => ['fas fa-network-wired', 'it_network'],
    'የሶፍትዌር ችግሮች' => ['fas fa-laptop-code', 'it_software'],
    'የሳይበር ደህንነት ጉዳዮች' => ['fas fa-shield-alt', 'it_security'],
    'የሃርድዌር ወይም የጥገና ፍላጎቶች' => ['fas fa-tools', 'it_hardware']
];

// የቅድሚያ ደረጃዎች ለሁኔታ
$validPriorities = [
    'ዝቅተኛ' => 'text-info',
    'መካከለኛ' => 'text-warning',
    'ከፍተኛ' => 'text-danger',
    'አስቸኳይ' => 'text-danger fw-bold'
];

$successMessage = '';
$errorMessage = '';

// 2. የጥያቄ ምድብ (Assignment) አያያዝ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_request'])) {
    $requestId = $_POST['request_id'];
    $assignedToUserId = $_POST['assigned_to_user_id'];

    try {
        $pdo->beginTransaction();

        // ጥያቄውን ለአይቲ ሰራተኛ መድብ እና ሁኔታውን ወደ 'In Progress' ቀይር
        $assignSql = "UPDATE help_requests SET assigned_to_user_id = ?, status = 'In Progress' WHERE id = ? AND department_id = ?";
        $assignStmt = $pdo->prepare($assignSql);
        $assignStmt->execute([$assignedToUserId, $requestId, $departmentId]);

        // ለ ማሳወቂያዎች የጠያቂውን እና የተመደበለትን የአይቲ ሰራተኛ ስም ያግኙ
        $reqDetailsStmt = $pdo->prepare("
            SELECT hr.title, u_req.name AS requester_name, u_assigned.name AS assigned_name
            FROM help_requests hr
            JOIN users u_req ON hr.user_id = u_req.id
            LEFT JOIN users u_assigned ON ? = u_assigned.id /* እዚህ ላይ assignedToUserId ን በቀጥታ ተጠቀም */
            WHERE hr.id = ?
        ");
        $reqDetailsStmt->execute([$assignedToUserId, $requestId]);
        $requestDetails = $reqDetailsStmt->fetch(PDO::FETCH_ASSOC);

        $requesterName = $requestDetails['requester_name'] ?? 'አንድ ተጠቃሚ';
        $assignedName = $requestDetails['assigned_name'] ?? 'የአይቲ ሰራተኛ';
        $requestTitle = htmlspecialchars(substr($requestDetails['title'], 0, 30)) . '...';

        // ለተመደበው የአይቲ ሰራተኛ ማሳወቂያ ፍጠር
        $messageForAssigned = "አዲስ ጥያቄ ከ " . htmlspecialchars($requesterName) . " (ዲፓርትመንት: " . htmlspecialchars($departmentName) . "): " . $requestTitle . " ተመድቦልዎታል";
        $notifAssignedSql = "INSERT INTO notifications (user_id, request_id, message, created_at) VALUES (?, ?, ?, NOW())";
        $notifAssignedStmt = $pdo->prepare($notifAssignedSql);
        $notifAssignedStmt->execute([$assignedToUserId, $requestId, $messageForAssigned]);

        // ለጠያቂው ጥያቄው 'In Progress' እንደሆነ እና እንደተመደበ የሚያሳውቅ መልዕክት
        $messageForRequester = "የእርስዎ ጥያቄ '" . $requestTitle . "' አሁን በሂደት ላይ ነው እና ለ " . htmlspecialchars($assignedName) . " ተመድቧል::";
        $requesterIdStmt = $pdo->prepare("SELECT user_id FROM help_requests WHERE id = ?");
        $requesterIdStmt->execute([$requestId]);
        $requesterId = $requesterIdStmt->fetchColumn();

        if ($requesterId) {
            $notifRequesterSql = "INSERT INTO notifications (user_id, request_id, message, created_at) VALUES (?, ?, ?, NOW())";
            $notifRequesterStmt = $pdo->prepare($notifRequesterSql);
            $notifRequesterStmt->execute([$requesterId, $requestId, $messageForRequester]);
        }

        $pdo->commit();
        $successMessage = "ጥያቄ #$requestId ለ " . htmlspecialchars($assignedName) . " በተሳካ ሁኔታ ተመድቧል::";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errorMessage = "ጥያቄ በመመደብ ላይ ስህተት: " . $e->getMessage();
        error_log("ጥያቄ በመመደብ ላይ ስህተት: " . $e->getMessage());
    }
}

// 3. የአይቲ ምላሽ ለጠያቂው ማስተላለፍ አያያዝ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forward_response'])) {
    $requestId = $_POST['request_id'];

    try {
        $pdo->beginTransaction();

        // ምላሹ እንደተላለፈ ምልክት አድርግ
        $forwardSql = "UPDATE help_requests SET manager_forwarded_at = NOW() WHERE id = ? AND department_id = ?";
        $forwardStmt = $pdo->prepare($forwardSql);
        $forwardStmt->execute([$requestId, $departmentId]);

        // ለማሳወቂያ የጥያቄ ዝርዝሮችን ያግኙ
        $reqDetailsStmt = $pdo->prepare("
            SELECT hr.title, hr.it_response, u_req.id as requester_id, u_req.name as requester_name
            FROM help_requests hr
            JOIN users u_req ON hr.user_id = u_req.id
            WHERE hr.id = ? AND hr.department_id = ?
        ");
        $reqDetailsStmt->execute([$requestId, $departmentId]);
        $requestDetails = $reqDetailsStmt->fetch(PDO::FETCH_ASSOC);

        if ($requestDetails) {
            $messageForRequester = "ለእርስዎ ጥያቄ '" . htmlspecialchars(substr($requestDetails['title'], 0, 30)) . "...' ምላሽ ተሰጥቷል። የዲፓርትመንት ተወካይ ማዘመኛ: " . htmlspecialchars($requestDetails['it_response'] ?? 'ምንም የተወሰነ ምላሽ በIT አልተሰጠም።');
            $notifRequesterSql = "INSERT INTO notifications (user_id, request_id, message, created_at) VALUES (?, ?, ?, NOW())";
            $notifRequesterStmt = $pdo->prepare($notifRequesterSql);
            $notifRequesterStmt->execute([$requestDetails['requester_id'], $requestId, $messageForRequester]);

            $successMessage = "ለጥያቄ #$requestId የተሰጠው ምላሽ ለ " . htmlspecialchars($requestDetails['requester_name']) . " በተሳካ ሁኔታ ተላልፏል::";
        } else {
            $errorMessage = "ጥያቄ አልተገኘም ወይም በርስዎ ዲፓርትመንት ውስጥ አይደለም።";
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errorMessage = "ምላሽ በማስተላለፍ ላይ ስህተት: " . $e->getMessage();
        error_log("ምላሽ በማስተላለፍ ላይ ስህተት: " . $e->getMessage());
    }
}


// 4. አዲስ ጥያቄ ማስገባት (የዲፓርትመንት ኃላፊ በሌሎች ስም ሊያስገባ ይችላል)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = $_POST['priority'];
    $category = $_POST['category'];
    $selectedUserId = $_POST['user_id'] ?? $userId; // የራስ ወይም የተመረጠ ተጠቃሚ
    $status = 'New';
    $createdAt = date('Y-m-d H:i:s');

    // ግብዓቶችን አረጋግጥ
    if (
        $title && $description &&
        array_key_exists($priority, $validPriorities) &&
        array_key_exists($category, $validCategories)
    ) {
        try {
            $pdo->beginTransaction();

            // ጥያቄውን አስገባ
            $sql = "INSERT INTO help_requests
                    (user_id, department_id, title, description, category, priority, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$selectedUserId, $departmentId, $title, $description, $category, $priority, $status, $createdAt]);

            // ያስገባኸው ጥያቄ ID ያግኙ
            $requestId = $pdo->lastInsertId();

            // ለአይቲ ሰራተኛ ማሳወቂያ ፍጠር (ለአዲስ ጥያቄ አጠቃላይ ማሳወቂያ)
            $requesterName = $_SESSION['name'];
            if ($selectedUserId != $userId) {
                // በሌላ ሰው ስም ካስገባኸው የጠያቂውን ትክክለኛ ስም ያግኙ
                $nameStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                $nameStmt->execute([$selectedUserId]);
                $requesterName = $nameStmt->fetchColumn() ?? $requesterName;
            }

            $message = "አዲስ ጥያቄ ከ " . htmlspecialchars($requesterName) . " (ዲፓርትመንት: " . htmlspecialchars($departmentName) . "): " . substr($title, 0, 30) . "...";
            $notifSql = "INSERT INTO notifications
                          (user_id, request_id, message, created_at)
                          SELECT id, ?, ?, ? FROM users WHERE role IN ('admin', 'it_staff', 'it_network', 'it_software', 'it_security', 'it_hardware')"; // የሚመለከታቸውን የአይቲ ሚናዎችን ያሳውቁ
            $notifStmt = $pdo->prepare($notifSql);
            $notifStmt->execute([$requestId, $message, $createdAt]);

            // ለትክክለኛው ጠያቂ ማሳወቂያ ፍጠር (ከአስገባው የተለየ ከሆነ)
            if ($selectedUserId != $userId) {
                $userMessage = "የእርስዎ ተወካይ በእርስዎ ስም ጥያቄ አስገብተዋል:: " . substr($title, 0, 30) . "...";
                $userNotifStmt = $pdo->prepare("INSERT INTO notifications (user_id, request_id, message, created_at) VALUES (?, ?, ?, ?)");
                $userNotifStmt->execute([$selectedUserId, $requestId, $userMessage, $createdAt]);
            }

            $pdo->commit();

            $successMessage = "ጥያቄ በተሳካ ሁኔታ ቀርቧል::";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errorMessage = "የዳታቤዝ ስህተት: " . $e->getMessage();
            error_log("ጥያቄ በመላክ ላይ ስህተት: " . $e->getMessage());
        }
    } else {
        $errorMessage = "እባክዎ ሁሉንም መስኮች በትክክል ይሙሉ::";
    }
}

// 5. የአይቲ ሰራተኞችን ዝርዝር ለምድብ ዝርዝር (dropdown) ማምጣት
$itStaff = [];
try {
    $itStmt = $pdo->query("SELECT id, name, role FROM users WHERE role LIKE 'it_%' OR role = 'admin' ORDER BY name");
    $itStaff = $itStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("የአይቲ ሰራተኞችን በማምጣት ላይ ስህተት: " . $e->getMessage());
}

// የአይቲ ሰራተኞችን እንደ ስፔሻሊቲ በመለየት (ሚናው ለስፔሻሊቲ የሚያገለግል ከሆነ፣ ለምሳሌ 'it_network')
$itStaffBySpecialty = [];
foreach ($itStaff as $staff) {
    if (strpos($staff['role'], 'it_') === 0) {
        $specialty = substr($staff['role'], 3); // ለምሳሌ: 'network' ከ 'it_network'
        $itStaffBySpecialty[$specialty][] = $staff;
    } elseif ($staff['role'] === 'admin') {
        // አድሚኖች ማንኛውንም ምድብ ሊይዙ ይችላሉ፣ ወይም ለየብቻ ሊዘረዝሯቸው ይችላሉ
        $itStaffBySpecialty['admin'][] = $staff;
    } else {
        // አጠቃላይ የአይቲ ሰራተኛ
        $itStaffBySpecialty['general'][] = $staff;
    }
}


// 6. ለ 'ጥያቄ ለማን?' (Request For) ዝርዝር የዲፓርትመንት አባላትን ማምጣት
$departmentMembers = [];
try {
    $memberStmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.profile_photo
        FROM users u
        WHERE u.department_id = ? AND u.id != ? AND u.role = 'user' /* መደበኛ ተጠቃሚዎች ብቻ፣ ተወካዮች/ኃላፊዎች አይደሉም */
        ORDER BY u.name
    ");
    $memberStmt->execute([$departmentId, $userId]);
    $departmentMembers = $memberStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("የዲፓርትመንት አባላትን በማምጣት ላይ ስህተት: " . $e->getMessage());
}

// 7. ለዲፓርትመንት ኃላፊው ጥያቄዎችን ማምጣት
// ይህ አሁን ለዲፓርትመንቱ ያሉትን ሁሉንም ጥያቄዎች ያመጣል፣ ያልተመደቡትን ጨምሮ
$departmentRequests = [];
try {
    $stmt = $pdo->prepare("
        SELECT hr.*, u.name as requester_name, u.profile_photo as requester_photo,
               assigned_user.name as assigned_to_name
        FROM help_requests hr
        LEFT JOIN users u ON hr.user_id = u.id
        LEFT JOIN users assigned_user ON hr.assigned_to_user_id = assigned_user.id
        WHERE hr.department_id = ?
        ORDER BY
            CASE hr.priority
                WHEN 'አስቸኳይ' THEN 1
                WHEN 'ከፍተኛ' THEN 2
                WHEN 'መካከለኛ' THEN 3
                ELSE 4
            END,
            hr.created_at DESC
    ");
    $stmt->execute([$departmentId]);
    $departmentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("የዲፓርትመንት ጥያቄዎችን በማምጣት ላይ ስህተት: " . $e->getMessage());
    $departmentRequests = [];
}

// 8. ላልተነበቡ ማሳወቂያዎች ብዛት
$unreadCount = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unreadCount = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("ማሳወቂያዎችን በመቁጠር ላይ ስህተት: " . $e->getMessage());
}

// 9. የተጠቃሚ ፕሮፋይል ፎቶን ያግኙ
$userPhoto = 'assets/images/default-profile.png';
try {
    $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $photo = $stmt->fetchColumn();

    if ($photo && file_exists("uploads/profile_photos/" . $photo)) {
        $userPhoto = "uploads/profile_photos/" . $photo;
    }
} catch (PDOException $e) {
    error_log("የተጠቃሚ ፎቶን በማምጣት ላይ ስህተት: " . $e->getMessage());
}

// 10. ለአጭር ጊዜ ማሳወቂያዎች (dropdown) የቅርብ ጊዜ ማሳወቂያዎችን ያግኙ
$notifications = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, message, created_at, is_read
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("ማሳወቂያዎችን በማምጣት ላይ ስህተት: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="am">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>የዲፓርትመንት ኃላፊ ዳሽቦርድ - የእርዳታ ማዕከል ስርዓት</title>

    <link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4cc9f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fb;
            color: #333;
        }

        .dashboard-header {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 12px 12px 0 0 !important;
            padding: 1.25rem 1.5rem;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-new {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .status-in-progress {
            background-color: #ffedd5;
            color: #9a3412;
        }

        .status-resolved {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-closed {
            background-color: #e2e8f0;
            color: #475569;
        }

        .status-cancelled {
            background-color: #ffe4e6;
            color: #e11d48;
        }

        .department-badge {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .request-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .request-card:hover {
            transform: translateX(5px);
        }

        .request-urgent {
            border-left-color: var(--danger);
        }

        .request-high {
            border-left-color: var(--warning);
        }

        .request-medium {
            border-left-color: var(--info);
        }

        .request-low {
            border-left-color: var(--success);
        }

        .category-icon {
            font-size: 1.25rem;
            margin-right: 8px;
            color: var(--primary);
        }

        .faq-item {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                padding: 0.75rem 1rem;
            }

            .card-header {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <header class="dashboard-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <a href="department-manager.php" class="text-decoration-none">
                <h4 class="mb-0 fw-bold text-primary">
                    <i class="fas fa-headset me-2"></i>የእርዳታ ማዕከል
                </h4>
            </a>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn btn-link text-dark position-relative p-0" type="button" id="notificationDropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell fs-5"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="notification-badge"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="notificationDropdown"
                    style="width: 320px;">
                    <li class="px-3 py-2 border-bottom bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">ማሳወቂያዎች</h6>
                            <?php if ($unreadCount > 0): ?>
                                <small><a href="mark_notifications_read.php" class="text-primary">ሁሉንም እንዳነበብክ ምልክት አድርግ</a></small>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $note): ?>
                            <li>
                                <a class="dropdown-item notification-item <?= $note['is_read'] ? '' : 'bg-light' ?>"
                                    href="view_request.php?id=<?= $note['request_id'] ?? '' ?>">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 text-primary me-2">
                                            <i class="fas fa-<?= $note['is_read'] ? 'envelope-open' : 'envelope' ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1"><?= htmlspecialchars($note['message']) ?></p>
                                            <small class="text-muted"><?= date('M j, g:i a', strtotime($note['created_at'])) ?></small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li class="text-center border-top">
                            <a class="dropdown-item text-primary fw-bold" href="notifications.php">
                                <i class="fas fa-list me-1"></i> ሁሉንም ማሳወቂያዎች ይመልከቱ
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="text-center py-3">
                            <p class="text-muted mb-0">ምንም ማሳወቂያዎች አልተገኙም</p>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="dropdown">
                <button class="btn btn-link text-dark p-0 d-flex align-items-center" type="button" id="userDropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?= htmlspecialchars($userPhoto) ?>" alt="የተጠቃሚ ምስል" class="user-avatar me-2">
                    <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['name']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>የእኔ ገጽ</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>ቅንብሮች</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ውጣ</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main class="container py-4">
        <div class="card mb-4 border-0 bg-primary bg-gradient text-white">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="card-title mb-2">እንኳን ደህና መጡ፣ የዲፓርትመንት ኃላፊ!</h3>
                        <p class="card-text mb-3">
                            ለ **<?= htmlspecialchars($departmentName) ?>** ዲፓርትመንት ጥያቄዎችን እየተቆጣጠሩ ነው።
                            በዲፓርትመንትዎ አባላት ስም ጥያቄዎችን ማስገባት እና የዲፓርትመንት ጥያቄዎችን ለአይቲ ሰራተኞች መመደብ ይችላሉ።
                        </p>
                        <span class="badge bg-light text-primary">
                            <i class="fas fa-building me-1"></i> <?= htmlspecialchars($departmentName) ?>
                        </span>
                        <span class="badge bg-white text-primary ms-2">
                            <i class="fas fa-star me-1"></i> የዲፓርትመንት ኃላፊ
                        </span>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="knowledge_base.php" class="btn btn-light btn-sm">
                            <i class="fas fa-book me-1"></i> የዕውቀት መሠረት
                        </a>
                        <a href="#faq-section" class="btn btn-outline-light btn-sm ms-2">
                            <i class="fas fa-question-circle me-1"></i> በተደጋጋሚ የሚጠየቁ ጥያቄዎች
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?= $successMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $errorMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-5 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle text-primary me-2"></i>
                            ለዲፓርትመንት የእርዳታ ጥያቄ ያስገቡ
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="requestForm">
                            <div class="mb-3">
                                <label for="user_id" class="form-label">ጥያቄው ለማን ነው?</label>
                                <select class="form-select" id="user_id" name="user_id" required>
                                    <option value="<?= $userId ?>">ለራሴ (<?= htmlspecialchars($_SESSION['name']) ?>)</option>
                                    <?php foreach ($departmentMembers as $member): ?>
                                        <option value="<?= $member['id'] ?>">
                                            <?= htmlspecialchars($member['name']) ?> (<?= htmlspecialchars($member['email']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="title" class="form-label">የችግሩ ርዕስ *</label>
                                <input type="text" class="form-control" id="title" name="title" required
                                    placeholder="ችግሩን በአጭሩ ይግለጹ" maxlength="100">
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label">ምድብ *</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="" disabled selected>ምድብ ይምረጡ</option>
                                    <?php foreach ($validCategories as $cat => $data): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>">
                                            <?= htmlspecialchars($cat) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">ዝርዝር መግለጫ *</label>
                                <textarea class="form-control" id="description" name="description" rows="4"
                                    required placeholder="ስለ ችግሩ ዝርዝር መረጃ ይስጡ"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="priority" class="form-label">ቅድሚያ *</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="" disabled selected>የቅድሚያ ደረጃ ይምረጡ</option>
                                    <?php foreach ($validPriorities as $pri => $class): ?>
                                        <option value="<?= htmlspecialchars($pri) ?>">
                                            <?= htmlspecialchars($pri) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" name="submit_request" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-paper-plane me-2"></i> ጥያቄ ያስገቡ
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-ticket-alt text-primary me-2"></i>
                            የዲፓርትመንት ጥያቄዎች (<?= count($departmentRequests) ?>)
                            <?php
                            $newRequestsCount = 0;
                            foreach ($departmentRequests as $req) {
                                if ($req['status'] === 'New') {
                                    $newRequestsCount++;
                                }
                            }
                            if ($newRequestsCount > 0) {
                                echo '<span class="badge bg-danger ms-2">' . $newRequestsCount . ' አዲስ</span>';
                            }
                            ?>
                        </h5>
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <span class="input-group-text bg-transparent"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="ጥያቄዎችን ይፈልጉ..." id="searchRequests">
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <?php if (!empty($departmentRequests)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>ጠያቂ</th>
                                            <th>ርዕስ</th>
                                            <th>ምድብ</th>
                                            <th>ሁኔታ</th>
                                            <th>ቅድሚያ</th>
                                            <th>ተመድቦለታል</th>
                                            <th>ድርጊቶች</th>
                                        </tr>
                                    </thead>
                                    <tbody id="requestsTableBody">
                                        <?php foreach ($departmentRequests as $request): ?>
                                            <tr class="request-<?= strtolower(str_replace(' ', '-', $request['priority'])) ?>">
                                                <td>#<?= $request['id'] ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($request['requester_photo'])): ?>
                                                            <img src="uploads/profile_photos/<?= htmlspecialchars($request['requester_photo']) ?>"
                                                                alt="<?= htmlspecialchars($request['requester_name']) ?>"
                                                                class="rounded-circle me-2" width="30" height="30">
                                                        <?php else: ?>
                                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2"
                                                                style="width: 30px; height: 30px;">
                                                                <?= strtoupper(substr($request['requester_name'], 0, 1)) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div><?= htmlspecialchars($request['requester_name']) ?></div>
                                                            <small class="text-muted"><?= date('M j', strtotime($request['created_at'])) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <a href="view_request.php?id=<?= $request['id'] ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($request['title']) ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info-subtle text-info">
                                                        <i class="<?= $validCategories[$request['category']][0] ?? 'fas fa-question' ?> me-1"></i>
                                                        <?= htmlspecialchars($request['category']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $request['status'])) ?>">
                                                        <?= $request['status'] ?>
                                                    </span>
                                                </td>
                                                <td class="<?= $validPriorities[$request['priority']] ?>">
                                                    <?= $request['priority'] ?>
                                                </td>
                                                <td>
                                                    <?php if ($request['assigned_to_name']): ?>
                                                        <span class="badge bg-success-subtle text-success">
                                                            <i class="fas fa-user-check me-1"></i>
                                                            <?= htmlspecialchars($request['assigned_to_name']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-subtle text-warning">
                                                            <i class="fas fa-hourglass-start me-1"></i>
                                                            ያልተመደበ
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="view_request.php?id=<?= $request['id'] ?>" class="btn btn-sm btn-outline-primary"
                                                            data-bs-toggle="tooltip" title="ዝርዝሮችን ይመልከቱ">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($request['status'] === 'New' || ($request['status'] === 'In Progress' && !$request['assigned_to_user_id'])): // አስፈላጊ ከሆነ እንደገና መመደብ ይችላል 
                                                        ?>
                                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                                data-bs-toggle="modal" data-bs-target="#assignRequestModal"
                                                                data-request-id="<?= $request['id'] ?>"
                                                                data-request-title="<?= htmlspecialchars($request['title']) ?>"
                                                                data-request-category="<?= htmlspecialchars($request['category']) ?>"
                                                                title="ጥያቄ ይመድቡ">
                                                                <i class="fas fa-user-tag"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($request['status'] === 'Resolved' && empty($request['manager_forwarded_at'])): // የተፈታ ከሆነ እና ገና ካልተላለፈ ብቻ 
                                                        ?>
                                                            <button type="button" class="btn btn-sm btn-outline-info"
                                                                data-bs-toggle="modal" data-bs-target="#forwardResponseModal"
                                                                data-request-id="<?= $request['id'] ?>"
                                                                data-request-title="<?= htmlspecialchars($request['title']) ?>"
                                                                data-it-response="<?= htmlspecialchars($request['it_response'] ?? 'ምንም የተወሰነ ምላሽ በIT አልተሰጠም።') ?>"
                                                                title="የአይቲ ምላሽ ያስተላልፉ">
                                                                <i class="fas fa-share-square"></i>
                                                            </button>
                                                        <?php elseif ($request['status'] === 'Resolved' && !empty($request['manager_forwarded_at'])): ?>
                                                            <span class="badge bg-primary-subtle text-primary" data-bs-toggle="tooltip" title="ምላሽ ተላልፏል">
                                                                <i class="fas fa-paper-plane"></i> ተላልፏል
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                <p class="text-muted">ለርስዎ ዲፓርትመንት ምንም የእርዳታ ጥያቄዎች አልተገኙም።</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#newRequestModal">
                                    <i class="fas fa-plus-circle me-2"></i> አዲስ ጥያቄ ያስገቡ
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-5">

        <section id="faq-section" class="py-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-question-circle text-primary me-2"></i> በተደጋጋሚ የሚጠየቁ ጥያቄዎች
                    </h5>
                </div>
                <div class="card-body">
                    <div class="accordion accordion-flush" id="faqAccordion">
                        <div class="accordion-item faq-item">
                            <h2 class="accordion-header" id="flush-headingOne">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#flush-collapseOne" aria-expanded="false"
                                    aria-controls="flush-collapseOne">
                                    ለዲፓርትመንት አባሎቼ አዲስ የእርዳታ ጥያቄ እንዴት አስገባለሁ?
                                </button>
                            </h2>
                            <div id="flush-collapseOne" class="accordion-collapse collapse"
                                aria-labelledby="flush-headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    በዳሽቦርድዎ በግራ በኩል ያለውን "ለዲፓርትመንት የእርዳታ ጥያቄ ያስገቡ" ቅጽ ይጠቀሙ።
                                    ከ "ጥያቄው ለማን ነው?" ዝርዝር ውስጥ የዲፓርትመንቱን አባል ይምረጡ፣ የችግሩን ዝርዝር ይሙሉ እና ያስገቡ።
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item faq-item">
                            <h2 class="accordion-header" id="flush-headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#flush-collapseTwo" aria-expanded="false"
                                    aria-controls="flush-collapseTwo">
                                    ጥያቄን ለአይቲ ስፔሻሊስት እንዴት መመደብ እችላለሁ?
                                </button>
                            </h2>
                            <div id="flush-collapseTwo" class="accordion-collapse collapse"
                                aria-labelledby="flush-headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    በ "የዲፓርትመንት ጥያቄዎች" ሠንጠረዥ ውስጥ መመደብ የሚፈልጉትን ጥያቄ ያግኙ።
                                    በ "ድርጊቶች" አምድ ውስጥ ያለውን
                                    <button class="btn btn-sm btn-outline-success"><i class="fas fa-user-tag"></i></button>
                                    ቁልፍን ይጫኑ። አንድ ሞዳል (modal) ይታያል፣ ከዚያ የጥያቄውን ምድብ (ለምሳሌ: ኔትወርክ፣ ሶፍትዌር)
                                    በመጠቀም ተገቢውን የአይቲ ሰራተኛ መምረጥ ይችላሉ።
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item faq-item">
                            <h2 class="accordion-header" id="flush-headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#flush-collapseThree" aria-expanded="false"
                                    aria-controls="flush-collapseThree">
                                    የተለያዩ የጥያቄ ሁኔታዎች ምንድናቸው?
                                </button>
                            </h2>
                            <div id="flush-collapseThree" class="accordion-collapse collapse"
                                aria-labelledby="flush-headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <ul>
                                        <li>**አዲስ:** ጥያቄው ገና ቀርቧል።</li>
                                        <li>**በሂደት ላይ:** አንድ የአይቲ ሰራተኛ ተመድቧል እና በጥያቄው ላይ እየሰራ ነው።</li>
                                        <li>**ተፈቷል:** የአይቲ ሰራተኛው ጥያቄውን አጠናቋል።</li>
                                        <li>**ተዘግቷል:** ጥያቄው ተፈቷል እና በጠያቂው ተረጋግጧል ወይም ከተወሰነ ጊዜ በኋላ በራስ-ሰር ተዘግቷል።</li>
                                        <li>**ተሰርዟል:** ጥያቄው በጠያቂው ወይም በዲፓርትመንት ተወካዩ ተሰርዟል።</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item faq-item">
                            <h2 class="accordion-header" id="flush-headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#flush-collapseFour" aria-expanded="false"
                                    aria-controls="flush-collapseFour">
                                    የአይቲ ምላሽን ለተጠቃሚ እንዴት አስተላልፋለሁ?
                                </button>
                            </h2>
                            <div id="flush-collapseFour" class="accordion-collapse collapse"
                                aria-labelledby="flush-headingFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    የአይቲ ስፔሻሊስት ጥያቄ ሲፈታ እና ምላሽ ሲሰጥ፣ የጥያቄው ሁኔታ ወደ 'ተፈቷል' ይቀየራል።
                                    በ 'ድርጊቶች' አምድ ውስጥ
                                    <button class="btn btn-sm btn-outline-info"><i class="fas fa-share-square"></i></button>
                                    ቁልፍን ያያሉ። ይህንን ቁልፍ በመጫን የአይቲ ምላሹን መገምገም እና ከዚያም ለዋናው ጠያቂ ማስተላለፍ ይችላሉ።
                                    ይህም ለተጠቃሚው ስለመፍትሄው ያሳውቃል።
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="modal fade" id="assignRequestModal" tabindex="-1" aria-labelledby="assignRequestModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignRequestModalLabel">ጥያቄ ይመድቡ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ዝጋ"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="modalRequestId">
                        <input type="hidden" name="assignment_category" id="modalRequestCategory">
                        <p>ጥያቄውን ይመድቡ: "<strong id="modalRequestTitle"></strong>" ለአይቲ ሰራተኛ።</p>

                        <div class="mb-3">
                            <label for="assigned_to_user_id" class="form-label">ለአይቲ ሰራተኛ ይመድቡ *</label>
                            <select class="form-select" id="assigned_to_user_id" name="assigned_to_user_id" required>
                                <option value="" disabled selected>የአይቲ ሰራተኛ ይምረጡ</option>
                                <?php
                                // የአይቲ ሰራተኞችን በተዛማጅ ስፔሻሊቲ ወይም በአጠቃላይ አሳይ
                                foreach ($validCategories as $categoryName => $categoryData) {
                                    $itSpecialtyKey = str_replace('it_', '', $categoryData[1]); // ለምሳሌ: 'network', 'software'
                                    if (isset($itStaffBySpecialty[$itSpecialtyKey])) {
                                        echo '<optgroup label="' . htmlspecialchars($categoryName) . ' (' . ucfirst($itSpecialtyKey) . ' ስፔሻሊስቶች)">';
                                        foreach ($itStaffBySpecialty[$itSpecialtyKey] as $staff) {
                                            echo '<option value="' . $staff['id'] . '">' . htmlspecialchars($staff['name']) . '</option>';
                                        }
                                        echo '</optgroup>';
                                    }
                                }
                                // ለየት ያለ ምድብ ያልተሰጣቸውን አጠቃላይ የአይቲ ሰራተኞች/አድሚኖች ጨምር
                                if (isset($itStaffBySpecialty['admin']) || isset($itStaffBySpecialty['general'])) {
                                    echo '<optgroup label="አጠቃላይ የአይቲ ሰራተኛ / አድሚኖች">';
                                    if (isset($itStaffBySpecialty['admin'])) {
                                        foreach ($itStaffBySpecialty['admin'] as $staff) {
                                            echo '<option value="' . $staff['id'] . '">' . htmlspecialchars($staff['name']) . ' (አድሚን)</option>';
                                        }
                                    }
                                    if (isset($itStaffBySpecialty['general'])) {
                                        foreach ($itStaffBySpecialty['general'] as $staff) {
                                            echo '<option value="' . $staff['id'] . '">' . htmlspecialchars($staff['name']) . ' (አጠቃላይ አይቲ)</option>';
                                        }
                                    }
                                    echo '</optgroup>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ሰርዝ</button>
                        <button type="submit" name="assign_request" class="btn btn-primary">ምድብ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forwardResponseModal" tabindex="-1" aria-labelledby="forwardResponseModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="forwardResponseModalLabel">የአይቲ ምላሽ ያስተላልፉ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ዝጋ"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="forwardModalRequestId">
                        <p>ለጥያቄው የአይቲ ምላሹን ሊያስተላልፉ ነው: "<strong id="forwardModalRequestTitle"></strong>"።</p>
                        <p class="text-muted">የአይቲ ምላሽ:</p>
                        <blockquote class="blockquote bg-light p-3 rounded" id="forwardModalItResponse">
                        </blockquote>
                        <p class="mt-3">ይህንን ምላሽ ለዋናው ጠያቂ ማስተላለፍ ይፈልጋሉ?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ሰርዝ</button>
                        <button type="submit" name="forward_response" class="btn btn-primary">ምላሽ ያስተላልፉ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // የTooltips ማስጀመሪያ
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // የጥያቄ ምድብ ሞዳል መረጃ ማስገቢያ አያያዝ
            var assignRequestModal = document.getElementById('assignRequestModal');
            if (assignRequestModal) {
                assignRequestModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget; // ሞዳሉን ያነሳው ቁልፍ
                    var requestId = button.getAttribute('data-request-id');
                    var requestTitle = button.getAttribute('data-request-title');
                    var requestCategory = button.getAttribute('data-request-category');

                    var modalRequestId = assignRequestModal.querySelector('#modalRequestId');
                    var modalRequestTitle = assignRequestModal.querySelector('#modalRequestTitle');
                    var modalRequestCategory = assignRequestModal.querySelector('#modalRequestCategory');
                    // var assignedToSelect = assignRequestModal.querySelector('#assigned_to_user_id'); // ይህኛው በቀጥታ አይጣልም፣ ዝርዝሩ አስቀድሞ ተሞልቷል።

                    modalRequestId.value = requestId;
                    modalRequestTitle.textContent = requestTitle;
                    modalRequestCategory.value = requestCategory; // ለምድብ ምድብ የተደበቀ ግብዓት ያዘጋጁ
                });
            }


            // የ Forward Response Modal መረጃ ማስገቢያ አያያዝ
            var forwardResponseModal = document.getElementById('forwardResponseModal');
            if (forwardResponseModal) {
                forwardResponseModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget; // ሞዳሉን ያነሳው ቁልፍ
                    var requestId = button.getAttribute('data-request-id');
                    var requestTitle = button.getAttribute('data-request-title');
                    var itResponse = button.getAttribute('data-it-response');

                    var forwardModalRequestId = forwardResponseModal.querySelector('#forwardModalRequestId');
                    var forwardModalRequestTitle = forwardResponseModal.querySelector('#forwardModalRequestTitle');
                    var forwardModalItResponse = forwardResponseModal.querySelector('#forwardModalItResponse');

                    forwardModalRequestId.value = requestId;
                    forwardModalRequestTitle.textContent = requestTitle;
                    forwardModalItResponse.textContent = itResponse;
                });
            }


            // ለጥያቄዎች ሠንጠረዥ የቀጥታ ፍለጋ ተግባር
            const searchRequestsInput = document.getElementById('searchRequests');
            const requestsTableBody = document.getElementById('requestsTableBody');
            const tableRows = requestsTableBody ? requestsTableBody.querySelectorAll('tr') : [];

            if (searchRequestsInput) {
                searchRequestsInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    tableRows.forEach(row => {
                        const rowText = row.textContent.toLowerCase();
                        if (rowText.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>

</html>