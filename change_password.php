<?php
include 'config.php';
checkLogin();
include 'partials/header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($current_password === '' || $new_password === '' || $confirm_password === '') {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirm password do not match.';
    } elseif (strlen($new_password) < 4) {
        $error = 'New password must be at least 4 characters.';
    } else {
        $user_id = (int)$_SESSION['user_id'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            $error = 'User not found.';
        } elseif ($current_password !== $user['password']) {
            $error = 'Current password is incorrect.';
        } else {
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $new_password, $user_id);

            if ($update->execute()) {
                $message = 'Password changed successfully.';
            } else {
                $error = 'Failed to update password.';
            }
        }
    }
}
?>

<style>
.change-pass-wrap {
    max-width: 520px;
    margin: 24px auto;
}

.change-pass-card {
    background: rgba(255, 255, 255, 0.94);
    border: 1px solid #e7eaf0;
    border-radius: 22px;
    box-shadow: 0 18px 50px rgba(20, 25, 40, 0.08);
    overflow: hidden;
}

.change-pass-head {
    padding: 20px 24px;
    border-bottom: 1px solid #edf0f5;
    display: flex;
    align-items: center;
    gap: 10px;
}

.change-pass-head .dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: #1a1a2e;
}

.change-pass-head span {
    font-size: 15px;
    font-weight: 600;
    color: #1a1a2e;
}

.change-pass-body {
    padding: 24px;
}

.info-box,
.error-box,
.success-box {
    margin-bottom: 16px;
    border-radius: 12px;
    padding: 12px 14px;
    font-size: 13px;
    font-weight: 500;
}

.error-box {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}

.success-box {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
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

.change-pass-btn {
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

.change-pass-btn:hover {
    background: #2d2d4e;
}
</style>

<div class="change-pass-wrap">
    <div class="change-pass-card">
        <div class="change-pass-head">
            <div class="dot"></div>
            <span>Change Password</span>
        </div>

        <div class="change-pass-body">
            <?php if ($error): ?>
                <div class="error-box"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="success-box"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="field-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" name="current_password" id="current_password" required>
                </div>

                <div class="field-group">
                    <label for="new_password">New Password</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>

                <div class="field-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>

                <button type="submit" class="change-pass-btn">Update Password</button>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>