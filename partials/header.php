<?php require_once 'config.php';

if (!isset($_SESSION['user_company']) && isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT company FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if (!empty($r['company'])) $_SESSION['user_company'] = $r['company'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PO Manager</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="shortcut icon" href="assets/images/favicon.svg">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f0f2f5;
            font-family: 'DM Sans', sans-serif;
            color: #1a1a2e;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #e7eaf0;
            box-shadow: 0 8px 22px rgba(20, 25, 40, 0.04);
        }

        .topbar-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 14px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: #1a1a2e;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 22px rgba(26, 26, 46, 0.16);
            flex-shrink: 0;
        }

        .brand-icon svg {
            width: 20px;
            height: 20px;
            stroke: #fff;
            fill: none;
            stroke-width: 2;
        }

        .brand-text h1 {
            margin: 0;
            font-size: 18px;
            line-height: 1.1;
            color: #1a1a2e;
            letter-spacing: -0.3px;
        }

        .brand-text p {
            margin: 3px 0 0;
            font-size: 12px;
            color: #7d8590;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-links a {
            text-decoration: none;
            color: #49505a;
            background: #f6f8fb;
            border: 1px solid #e4e8ef;
            padding: 9px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            transition: 0.18s ease;
        }

        .nav-links a:hover {
            background: #1a1a2e;
            color: #fff;
            border-color: #1a1a2e;
            transform: translateY(-1px);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-left: auto;
        }

        .user-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #f6f8fb;
            border: 1px solid #e4e8ef;
            border-radius: 14px;
            padding: 8px 12px;
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #1a1a2e;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .user-meta {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .user-meta .name {
            font-size: 13px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .user-meta .role {
            font-size: 11px;
            color: #7d8590;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .topbar-actions a {
            text-decoration: none;
            padding: 9px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            transition: 0.18s ease;
        }

        .action-secondary {
            background: #eef2f7;
            color: #1a1a2e;
            border: 1px solid #dde3ec;
        }

        .action-secondary:hover {
            background: #e4eaf2;
        }

        .action-logout {
            background: #1a1a2e;
            color: #fff;
            border: 1px solid #1a1a2e;
        }

        .action-logout:hover {
            background: #2d2d4e;
            border-color: #2d2d4e;
        }

        .page-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 22px;
        }

        @media (max-width: 900px) {
            .topbar-inner {
                align-items: flex-start;
            }

            .topbar-left,
            .topbar-right {
                width: 100%;
            }

            .topbar-right {
                justify-content: space-between;
            }
        }

        @media (max-width: 640px) {
            .brand-text h1 {
                font-size: 16px;
            }

            .nav-links,
            .topbar-actions {
                width: 100%;
            }

            .nav-links a,
            .topbar-actions a {
                text-align: center;
            }

            .user-pill {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <div class="topbar">
        <div class="topbar-inner">
            <div class="topbar-left">
                <a href="dashboard.php" class="brand">
                    <div class="brand-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z"></path>
                            <path d="M9 12l2 2 4-4"></path>
                        </svg>
                    </div>
                    <div class="brand-text">
                        <h1>PO Manager</h1>
                        <p>Purchase order workflow system</p>
                    </div>
                </a>

                <div class="nav-links">
                    <a href="dashboard.php">Dashboard</a>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super'): ?>
                        <a href="create_user.php">Create User</a>
                        <a href="users_list.php">View Users</a>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="create_po.php">Create PO</a>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['super', 'admin', 'viewer'])): ?>
                        <a href="pending_items.php">Pending Items</a>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['user'])): ?>
                        <a href="pending_items.php">Pending Items</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="topbar-right">
                    <div class="user-pill">
                        <div class="user-avatar">
                            <?= strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="user-meta">
                            <span class="name"><?= htmlspecialchars($_SESSION['name']) ?></span>
                            <span class="role"><?= htmlspecialchars($_SESSION['role']) ?></span>
                        </div>
                    </div>

                    <div class="topbar-actions">
                        <a href="change_password.php" class="action-secondary">Change Password</a>
                        <a href="logout.php" class="action-logout">Logout</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="page-shell">