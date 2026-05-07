<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "User not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="shortcut icon" href="assets/images/favicon.svg">

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.75), transparent 28%),
                radial-gradient(circle at bottom right, rgba(255, 255, 255, 0.4), transparent 22%),
                #f0f2f5;
            font-family: 'DM Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-shell {
            width: 100%;
            max-width: 440px;
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            justify-content: center;
            margin-bottom: 22px;
        }

        .login-brand-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: #1a1a2e;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 22px rgba(26, 26, 46, 0.18);
        }

        .login-brand-icon svg {
            width: 24px;
            height: 24px;
            stroke: #fff;
            fill: none;
            stroke-width: 2;
        }

        .login-brand-text h1 {
            margin: 0;
            font-size: 26px;
            line-height: 1.1;
            color: #1a1a2e;
            letter-spacing: -0.5px;
        }

        .login-brand-text p {
            margin: 5px 0 0;
            font-size: 13px;
            color: #7d8590;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            border: 1px solid #e7eaf0;
            border-radius: 22px;
            box-shadow: 0 18px 50px rgba(20, 25, 40, 0.10);
            overflow: hidden;
        }

        .login-card-head {
            padding: 20px 24px;
            border-bottom: 1px solid #edf0f5;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-card-head .dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #1a1a2e;
        }

        .login-card-head span {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a2e;
        }

        .login-card-body {
            padding: 24px;
        }

        .error-box {
            margin-bottom: 16px;
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 13px;
            font-weight: 500;
        }

        .field-group {
            margin-bottom: 16px;
        }

        .field-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #444;
        }

        .field-group input {
            width: 100%;
            min-height: 48px;
            border: 1.5px solid #e0e3e8;
            border-radius: 12px;
            background: #f9fafc;
            padding: 0 14px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            color: #1a1a2e;
            outline: none;
            transition: 0.18s ease;
        }

        .field-group input:focus {
            border-color: #1a1a2e;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(26, 26, 46, 0.06);
        }

        .login-btn {
            width: 100%;
            min-height: 48px;
            border: none;
            border-radius: 12px;
            background: #1a1a2e;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: 0.15s ease;
            margin-top: 6px;
        }

        .login-btn:hover {
            background: #2d2d4e;
        }

        .login-footer {
            margin-top: 16px;
            text-align: center;
            font-size: 12px;
            color: #8a8f98;
        }

        @media (max-width: 520px) {
            .login-brand {
                align-items: flex-start;
                justify-content: flex-start;
            }

            .login-brand-text h1 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>
    <div class="login-shell">
        <div class="login-brand">
            <div class="login-brand-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z"></path>
                    <path d="M9 12l2 2 4-4"></path>
                </svg>
            </div>
            <div class="login-brand-text">
                <h1>PO Manager</h1>
                <p>Sign in to continue to your dashboard</p>
            </div>
        </div>

        <div class="login-card">
            <div class="login-card-head">
                <div class="dot"></div>
                <span>Login</span>
            </div>

            <div class="login-card-body">
                <?php if (isset($error)): ?>
                    <div class="error-box"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="field-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" required placeholder="Enter your username">
                    </div>

                    <div class="field-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required placeholder="Enter your password">
                    </div>

                    <button type="submit" class="login-btn">Login</button>
                </form>

                <div class="login-footer">
                    Authorized users only
                </div>
            </div>
        </div>
    </div>
</body>

</html>