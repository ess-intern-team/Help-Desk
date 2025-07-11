<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Welcome to ESSA Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            background: linear-gradient(120deg, #e0f7fa, #e3f2fd);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        .top-nav {
            position: absolute;
            top: 30px;
            right: 50px;
            z-index: 999;
        }

        .top-nav a {
            margin-left: 15px;
            padding: 14px 28px;
            font-weight: 700;
            text-decoration: none;
            border-radius: 30px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .btn-login {
            background-color: #0d6efd;
            color: white;
        }

        .btn-login:hover {
            background-color: #084cd3;
        }

        .btn-signup {
            border: 2px solid #0d6efd;
            color: #0d6efd;
            background: transparent;
        }

        .btn-signup:hover {
            background-color: #0d6efd;
            color: white;
        }

        .welcome-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            padding: 40px;
        }

        .logo {
            width: 180px;
            height: auto;
            margin-bottom: 35px;
            /* CSS to remove white background */
            filter: brightness(1.1) drop-shadow(0 0 0 transparent);
            mix-blend-mode: multiply;
        }

        h1 {
            font-size: 4rem;
            font-weight: 900;
            color: #1d4ed8;
            margin-bottom: 20px;
        }

        h3 {
            font-size: 2.5rem;
            color: #2563eb;
            margin-bottom: 20px;
            font-weight: 600;
        }

        p {
            font-size: 1.5rem;
            color: #374151;
            max-width: 750px;
            margin-bottom: 20px;
            line-height: 1.8;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2.5rem;
            }

            h3 {
                font-size: 1.6rem;
            }

            p {
                font-size: 1.1rem;
            }

            .logo {
                width: 130px;
            }

            .top-nav {
                right: 20px;
                top: 20px;
            }

            .top-nav a {
                font-size: 1rem;
                padding: 10px 20px;
            }
        }
    </style>
</head>

<body>
    <!-- 🔝 Top Right Login/Sign Up -->
    <div class="top-nav">
        <a href="login.php" class="btn btn-login">Login</a>
        <a href="register.php" class="btn btn-signup">Sign Up</a>
    </div>

    <!-- 🌟 Welcome Center -->
    <div class="welcome-section">
        <img src="assets/images/ESSA.png" alt="ESSA Logo" class="logo" />
        <h1>Welcome to ESSA Helpdesk</h1>
        <h3>Ethiopian Statistical Service</h3>
        <p>Your reliable, fast, and modern IT Help Request Tracking System. Built to simplify your support journey and make your work easier.</p>
    </div>
</body>

</html>