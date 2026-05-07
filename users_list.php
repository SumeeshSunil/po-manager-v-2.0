<?php ob_start(); ?>
<?php
include 'partials/header.php';

checkLogin();
checkRole(['super']);

/*
|--------------------------------------------------------------------------
| DELETE USER (SAFE POST METHOD)
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {

    $del_id = intval($_POST['delete_user']);

    // Prevent deleting yourself
    if ($del_id == $_SESSION['user_id']) {
        header("Location: users_list.php?error=selfdelete");
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();

    header("Location: users_list.php?deleted=1");
    exit;
}

/*
|--------------------------------------------------------------------------
| FETCH USERS
|--------------------------------------------------------------------------
*/
$result = $conn->query("
    SELECT id, name, username, password, role, created_at
    FROM users
    ORDER BY id DESC
");

$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
* {
    box-sizing: border-box;
}

.user-page {
    min-height: 100vh;
    background: #f0f2f5;
    padding: 32px 20px 60px;
    font-family: 'DM Sans', sans-serif;
}

.user-wrap {
    max-width: 1100px;
    margin: 0 auto;
}

/* HEADER */
.user-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.user-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.user-header-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: #1a1a2e;
    display: flex;
    align-items: center;
    justify-content: center;
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
}

.user-header p {
    margin: 4px 0 0;
    font-size: 13px;
    color: #888;
}

/* CARD */
.user-card {
    background: #fff;
    border: 1px solid #e7eaf0;
    border-radius: 18px;
    box-shadow: 0 10px 35px rgba(20,25,40,0.06);
    overflow: hidden;
}

.user-card-head {
    padding: 18px 22px;
    border-bottom: 1px solid #edf0f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.user-card-head-left {
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

.user-count-badge {
    background: #f0f2f5;
    color: #555;
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 999px;
}

/* ALERTS */
.alert {
    border-radius: 12px;
    padding: 12px 14px;
    font-size: 13px;
    font-weight: 500;
    margin: 16px 22px;
}

.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}

.alert-danger {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}

/* TABLE */
.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}

thead tr {
    background: #f7f8fb;
    border-bottom: 1px solid #edf0f5;
}

thead th {
    padding: 12px 18px;
    text-align: left;
    font-size: 11.5px;
    font-weight: 700;
    color: #8a8f98;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}

tbody tr {
    border-bottom: 1px solid #f0f2f5;
    transition: background 0.15s ease;
}

tbody tr:hover {
    background: #fafbfd;
}

tbody td {
    padding: 14px 18px;
    color: #2c2c3e;
    vertical-align: middle;
}

/* USER */
.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #1a1a2e;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    text-transform: uppercase;
}

.user-info-text .name {
    font-weight: 600;
    color: #1a1a2e;
}

.user-info-text .uname {
    font-size: 12px;
    color: #888;
    font-family: 'DM Mono', monospace;
}

/* ROLE BADGE */
.role-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 600;
}

.role-super {
    background: #ede7f6;
    color: #4527a0;
}

.role-admin {
    background: #e3f2fd;
    color: #1565c0;
}

.role-user {
    background: #e8f5e9;
    color: #2e7d32;
}

.role-viewer {
    background: #fff8e1;
    color: #e65100;
}

.role-default {
    background: #f0f2f5;
    color: #555;
}

/* PASSWORD */
.pw-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pw-mask {
    font-size: 15px;
    letter-spacing: 2px;
    color: #aaa;
}

.pw-text {
    font-family: 'DM Mono', monospace;
    font-size: 13px;
}

.eye-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    border-radius: 8px;
    color: #aaa;
}

.eye-btn:hover {
    background: #f0f2f5;
    color: #1a1a2e;
}

.eye-btn svg {
    width: 16px;
    height: 16px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
}

/* ACTION BUTTONS */
.action-btns {
    display: flex;
    gap: 8px;
    align-items: center;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 34px;
    padding: 0 13px;
    border-radius: 10px;
    border: none;
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: 0.15s ease;
    font-family: 'DM Sans', sans-serif;
}

.btn svg {
    width: 14px;
    height: 14px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
}

.btn-primary {
    background: #1a1a2e;
    color: #fff;
}

.btn-primary:hover {
    background: #2d2d4e;
}

.btn-secondary {
    background: #edeff3;
    color: #555;
}

.btn-secondary:hover {
    background: #dfe3ea;
}

.btn-danger {
    background: #ffebee;
    color: #c62828;
}

.btn-danger:hover {
    background: #ffcdd2;
}

/* EMPTY */
.empty-state {
    text-align: center;
    padding: 48px 20px;
    color: #aaa;
}

.card-footer {
    padding: 14px 22px;
    border-top: 1px solid #edf0f5;
    background: #f7f8fb;
    font-size: 12px;
    color: #8a8f98;
}

@media (max-width: 640px) {
    thead th:nth-child(5),
    tbody td:nth-child(5) {
        display: none;
    }
}
</style>

<div class="user-page">

    <div class="user-wrap">

        <!-- HEADER -->
        <div class="user-header">

            <div class="user-header-left">

                <div class="user-header-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                </div>

                <div>
                    <h2>User Management</h2>
                    <p>View and manage all system users</p>
                </div>

            </div>

            <a href="create_user.php" class="btn btn-primary">

                <svg viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>

                Add User

            </a>

        </div>

        <!-- CARD -->
        <div class="user-card">

            <div class="user-card-head">

                <div class="user-card-head-left">
                    <div class="dot"></div>
                    <span>All Users</span>
                </div>

                <span class="user-count-badge">
                    <?= count($users) ?> total
                </span>

            </div>

            <!-- ALERTS -->
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">
                    User deleted successfully.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">
                    User updated successfully.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'selfdelete'): ?>
                <div class="alert alert-danger">
                    You cannot delete your own account.
                </div>
            <?php endif; ?>

            <!-- TABLE -->
            <?php if (empty($users)): ?>

                <div class="empty-state">
                    No users found.
                </div>

            <?php else: ?>

                <div class="table-wrap">

                    <table>

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Password</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($users as $i => $user): ?>

                            <?php
                            $role = $user['role'];

                            $roleClass = in_array($role, ['super','admin','user','viewer'])
                                ? "role-$role"
                                : "role-default";
                            ?>

                            <tr>

                                <td>
                                    <?= $i + 1 ?>
                                </td>

                                <td>

                                    <div class="user-info">

                                        <div class="user-avatar">
                                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                        </div>

                                        <div class="user-info-text">

                                            <div class="name">
                                                <?= htmlspecialchars($user['name']) ?>
                                            </div>

                                            <div class="uname">
                                                @<?= htmlspecialchars($user['username']) ?>
                                            </div>

                                        </div>

                                    </div>

                                </td>

                                <td>

                                    <div class="pw-cell">

                                        <span class="pw-mask" id="mask-<?= $user['id'] ?>">
                                            ••••••••
                                        </span>

                                        <span class="pw-text"
                                              id="text-<?= $user['id'] ?>"
                                              style="display:none;">
                                            <?= htmlspecialchars($user['password']) ?>
                                        </span>

                                        <button class="eye-btn"
                                                type="button"
                                                onclick="togglePassword(<?= $user['id'] ?>)">

                                            <svg id="icon-<?= $user['id'] ?>" viewBox="0 0 24 24">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>

                                        </button>

                                    </div>

                                </td>

                                <td>

                                    <span class="role-badge <?= $roleClass ?>">
                                        <?= htmlspecialchars($role) ?>
                                    </span>

                                </td>

                                <td>

                                    <?= date('d M Y', strtotime($user['created_at'])) ?>

                                </td>

                                <td>

                                    <div class="action-btns">

                                        <!-- EDIT -->
                                        <a href="edit_user.php?id=<?= $user['id'] ?>"
                                           class="btn btn-secondary">

                                            <svg viewBox="0 0 24 24">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>

                                            Edit

                                        </a>

                                        <!-- DELETE -->
                                        <form method="POST"
                                              style="display:inline;"
                                              onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($user['name'])) ?> ?');">

                                            <input type="hidden"
                                                   name="delete_user"
                                                   value="<?= $user['id'] ?>">

                                            <button type="submit"
                                                    class="btn btn-danger">

                                                <svg viewBox="0 0 24 24">
                                                    <polyline points="3 6 5 6 21 6"/>
                                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                    <path d="M10 11v6M14 11v6"/>
                                                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                                </svg>

                                                Delete

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <div class="card-footer">
                    Showing <?= count($users) ?> users
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<script>
function togglePassword(id) {

    const mask = document.getElementById('mask-' + id);
    const text = document.getElementById('text-' + id);

    const hidden = text.style.display === 'none';

    mask.style.display = hidden ? 'none' : 'inline';
    text.style.display = hidden ? 'inline' : 'none';
}
</script>

<?php include 'partials/footer.php'; ?>
<?php ob_end_flush(); ?>