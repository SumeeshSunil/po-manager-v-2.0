<?php
include 'partials/header.php';
checkLogin();
checkRole(['super']);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $username, $password, $role);

    if ($stmt->execute()) {
        $success = "User created successfully";
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
* { box-sizing: border-box; }

.user-page {
    min-height: 100vh;
    background: #f0f2f5;
    padding: 32px 20px 60px;
    font-family: 'DM Sans', sans-serif;
}

.user-wrap {
    max-width: 760px;
    margin: 0 auto;
}

.user-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
}

.user-header-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: #1a1a2e;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.user-header-icon svg {
    width: 22px;
    height: 22px;
    stroke: #fff;
    fill: none;
    stroke-width: 2;
}

.user-header h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #1a1a2e;
    letter-spacing: -0.4px;
}

.user-header p {
    margin: 4px 0 0;
    font-size: 13px;
    color: #888;
}

.user-card {
    background: #fff;
    border: 1px solid #e7eaf0;
    border-radius: 18px;
    box-shadow: 0 10px 35px rgba(20, 25, 40, 0.06);
    overflow: hidden;
}

.user-card-head {
    padding: 18px 22px;
    border-bottom: 1px solid #edf0f5;
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-card-head .dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: #1a1a2e;
}

.user-card-head span {
    font-size: 15px;
    font-weight: 600;
    color: #1a1a2e;
}

.user-card-body {
    padding: 24px 22px 26px;
}

.alert {
    border-radius: 12px;
    padding: 12px 14px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 16px;
}

.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}

.alert-error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.field-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.field-group.full {
    grid-column: 1 / -1;
}

.field-group label {
    font-size: 13px;
    font-weight: 600;
    color: #444;
}

.field-group input,
.field-group select {
    width: 100%;
    min-height: 46px;
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

.field-group input:focus,
.field-group select:focus {
    border-color: #1a1a2e;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(26, 26, 46, 0.06);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 0 18px;
    border-radius: 12px;
    border: none;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    transition: 0.15s ease;
}

.btn-secondary {
    background: #edeff3;
    color: #555;
}

.btn-secondary:hover {
    background: #e1e5eb;
}

.btn-primary {
    background: #1a1a2e;
    color: #fff;
}

.btn-primary:hover {
    background: #2d2d4e;
}

.helper-note {
    margin-top: 14px;
    font-size: 12px;
    color: #8a8f98;
}

@media (max-width: 640px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }
}
</style>

<div class="user-page">
    <div class="user-wrap">
        <div class="user-header">
            <div class="user-header-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z"></path>
                    <path d="M4 20a8 8 0 0 1 16 0"></path>
                </svg>
            </div>
            <div>
                <h2>Create User</h2>
                <p>Add a new system user with role access.</p>
            </div>
        </div>

        <div class="user-card">
            <div class="user-card-head">
                <div class="dot"></div>
                <span>User Details</span>
            </div>

            <div class="user-card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="field-group full">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" required placeholder="Enter full name">
                        </div>

                        <div class="field-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" required placeholder="Enter username">
                        </div>

                        <div class="field-group">
                            <label for="password">Password</label>
                            <input type="text" name="password" id="password" required placeholder="Enter password">
                        </div>

                        <div class="field-group full">
                            <label for="role">Role</label>
                            <select name="role" id="role" required>
                                <option value="">Select role</option>
                                <option value="admin">Admin</option>
                                <option value="user">User</option>
                                <option value="viewer">Viewer</option>
                                <option value="dispatch_supervisor">Dispatch Supervisor</option>
                                
                                
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="dashboard.php" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>

                    <div class="helper-note">
                        Make sure the username is unique before creating the account.
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>