<?php
include 'partials/header.php';

checkLogin();
checkRole(['super']);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid user ID");
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']);
    $username = trim($_POST['username']);
    $role     = trim($_POST['role']);

    // Update without password
    if (empty($_POST['password'])) {

        $stmt = $conn->prepare("
            UPDATE users
            SET name = ?, username = ?, role = ?
            WHERE id = ?
        ");

        $stmt->bind_param("sssi", $name, $username, $role, $id);

    } else {

        $password = $_POST['password'];

        $stmt = $conn->prepare("
            UPDATE users
            SET name = ?, username = ?, password = ?, role = ?
            WHERE id = ?
        ");

        $stmt->bind_param("ssssi", $name, $username, $password, $role, $id);
    }

    $stmt->execute();

    header("Location: users_list.php?updated=1");
    exit;
}
?>

<div style="max-width:600px;margin:40px auto;background:#fff;padding:30px;border-radius:16px;border:1px solid #eee;">

    <h2>Edit User</h2>

    <form method="POST">

        <div style="margin-bottom:16px;">
            <label>Name</label><br>
            <input type="text"
                   name="name"
                   value="<?= htmlspecialchars($user['name']) ?>"
                   required
                   style="width:100%;padding:12px;">
        </div>

        <div style="margin-bottom:16px;">
            <label>Username</label><br>
            <input type="text"
                   name="username"
                   value="<?= htmlspecialchars($user['username']) ?>"
                   required
                   style="width:100%;padding:12px;">
        </div>

        <div style="margin-bottom:16px;">
            <label>New Password (leave blank to keep old)</label><br>
            <input type="text"
                   name="password"
                   style="width:100%;padding:12px;">
        </div>

        <div style="margin-bottom:20px;">
            <label>Role</label><br>

            <select name="role" style="width:100%;padding:12px;">

                <option value="super" <?= $user['role']=='super'?'selected':'' ?>>Super</option>
                <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
                <option value="user" <?= $user['role']=='user'?'selected':'' ?>>User</option>
                <option value="viewer" <?= $user['role']=='viewer'?'selected':'' ?>>Viewer</option>

            </select>
        </div>

        <button type="submit"
                style="background:#1a1a2e;color:#fff;padding:12px 18px;border:none;border-radius:10px;cursor:pointer;">
            Update User
        </button>

    </form>
</div>

<?php include 'partials/footer.php'; ?>