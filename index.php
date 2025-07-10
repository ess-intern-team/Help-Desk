<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Welcome to ESSA Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        /* Fullscreen hero section */
        body,
        html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1470&q=80') no-repeat center center fixed;
            background-size: cover;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }

        .container-hero {
            position: relative;
            z-index: 2;
            max-width: 700px;
            text-align: center;
            padding: 40px;
            border-radius: 15px;
            background: rgba(0, 0, 0, 0.5);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
        }

        h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: 2px;
        }

        h2 {
            font-weight: 300;
            margin-bottom: 2rem;
            font-style: italic;
        }

        .btn-group .btn {
            min-width: 140px;
            font-weight: 600;
            font-size: 1.2rem;
            border-radius: 30px;
            padding: 12px 25px;
            transition: all 0.3s ease;
        }

        .btn-login {
            background: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }

        .btn-login:hover {
            background: #084cd3;
            border-color: #084cd3;
        }

        .btn-signup {
            background: transparent;
            border: 2px solid white;
            color: white;
            margin-left: 15px;
        }

        .btn-signup:hover {
            background: white;
            color: #000;
            border-color: white;
        }

        @media (max-width: 576px) {
            h1 {
                font-size: 2.2rem;
            }

            h2 {
                font-size: 1.1rem;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn-signup {
                margin-left: 0;
                margin-top: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="overlay"></div>

    <div class="container-hero">
        <h1>Welcome to ESSA</h1>
        <h2>Located at Ethio Ceramic Building, Addis Ababa</h2>
        <p>Your reliable IT Help Request Tracker system</p>

        <div class="btn-group mt-4" role="group" aria-label="Login and Signup buttons">
            <a href="login.php" class="btn btn-login">Login</a>
            <a href="register.php" class="btn btn-signup">Sign Up</a>
        </div>
    </div>

</body>

</html>