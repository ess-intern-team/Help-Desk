<?php
session_start();

// Handle language selection
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$currentLang = $_SESSION['lang'] ?? 'en';

// Define language strings
$texts = [
    'en' => [
        'title' => 'Language Settings',
        'select' => 'Select Language',
        'english' => 'English',
        'amharic' => 'Amharic',
        'dashboard' => 'Go to Dashboard',
    ],
    'am' => [
        'title' => 'የቋንቋ ምርጫ',
        'select' => 'ቋንቋ ይምረጡ',
        'english' => 'እንግሊዝኛ',
        'amharic' => 'አማርኛ',
        'dashboard' => 'ወደ ዳሽቦርድ ሂዱ',
    ]
];
$t = $texts[$currentLang];
?>

<!DOCTYPE html>
<html lang="<?= $currentLang ?>">

<head>
    <meta charset="UTF-8">
    <title><?= $t['title'] ?> - IT Helpdesk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #eef2f7, #e0f7fa);
            font-family: 'Segoe UI', sans-serif;
        }

        .lang-box {
            max-width: 450px;
            margin: 80px auto;
            padding: 30px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.07);
        }

        .lang-box h3 {
            margin-bottom: 20px;
            color: #343a40;
        }

        .lang-btn {
            width: 100%;
            margin-bottom: 10px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            text-decoration: none;
            color: #0d6efd;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="lang-box text-center">
        <h3><?= $t['select'] ?></h3>

        <a href="?lang=en" class="btn btn-outline-primary lang-btn"><?= $t['english'] ?></a>
        <a href="?lang=am" class="btn btn-outline-success lang-btn"><?= $t['amharic'] ?></a>

        <div class="back-link">
            <a href="dashboard.php"><?= $t['dashboard'] ?></a>
        </div>
    </div>
</body>

</html>