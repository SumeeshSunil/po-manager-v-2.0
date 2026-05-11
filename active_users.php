<?php
include 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super'])) {
    header("Location: dashboard.php");
    exit();
}

// Clean up stale sessions older than 5 minutes
$conn->query("DELETE FROM active_sessions WHERE last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");

// Fetch active users
$result = $conn->query("
    SELECT name, username, role, ip_address, login_at, last_seen
    FROM active_sessions
    ORDER BY last_seen DESC
");
?>

<?php include 'partials/header.php'; ?>

<div style="padding: 24px;">
    <h2 style="margin: 0 0 18px; font-size: 20px; color: #1a1a2e;">Active Users</h2>

    <?php if ($result->num_rows === 0): ?>
        <p style="color: #7d8590;">No active users right now.</p>
    <?php else: ?>
        <table style="width:100%; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
            <thead>
                <tr style="background:#1a1a2e; color:#fff; font-size:13px;">
                    <th style="padding:12px 16px; text-align:left;">Name</th>
                    <th style="padding:12px 16px; text-align:left;">Username</th>
                    <th style="padding:12px 16px; text-align:left;">Role</th>
                    <th style="padding:12px 16px; text-align:left;">IP Address</th>
                    <th style="padding:12px 16px; text-align:left;">Logged In</th>
                    <th style="padding:12px 16px; text-align:left;">Last Seen</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #f0f2f5; font-size:13px;">
                        <td style="padding:12px 16px;"><?= htmlspecialchars($row['name']) ?></td>
                        <td style="padding:12px 16px;"><?= htmlspecialchars($row['username']) ?></td>
                        <td style="padding:12px 16px;"><?= htmlspecialchars($row['role']) ?></td>
                        <td style="padding:12px 16px; font-family: monospace;"><?= htmlspecialchars($row['ip_address']) ?></td>
                        <td style="padding:12px 16px;"><?= $row['login_at'] ?></td>
                        <td style="padding:12px 16px;"><?= $row['last_seen'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Auto-refresh every 30 seconds -->
<script>
    setTimeout(() => location.reload(), 30000);
</script>

<?php include 'partials/footer.php'; ?>