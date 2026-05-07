<?php
include 'partials/header.php';

checkLogin();
checkRole(['super', 'admin', 'viewer', 'user']);


// ===============================
// ITEM CODE
// ===============================

$item_code = isset($_GET['item_code'])
  ? trim($_GET['item_code'])
  : '';

if ($item_code == '') {
  die("Invalid item code");
}


// ===============================
// USER DETAILS
// ===============================

$userRole = $_SESSION['role'] ?? '';
$userCompany = $_SESSION['user_company'] ?? '';


// ===============================
// COMPANY FILTER
// ===============================

$companyFilter = "";

if (
  $userRole === 'user' &&
  !empty($userCompany)
) {

  $companyFilter = "
        AND po.factory_name = ?
    ";
}


// ===============================
// QUERY
// ===============================

$sql = "
    SELECT 
        pi.item_code,
        pi.item_description,
        po.po_number,
        po.factory_name,
        po.platform,
        po.po_status,
        pi.qty AS initial_qty,
        COALESCE(pi.deliverable_qty, 0) AS db_updated_qty,
        pi.user_status,
        pi.expected_delivery_date,
        po.delivery_schedule_date,
        pi.reason,
        u.name AS updated_by_name

    FROM po_items pi

    INNER JOIN purchase_orders po 
        ON po.id = pi.po_id

    LEFT JOIN users u 
        ON pi.updated_by = u.id

    WHERE pi.item_code = ?
    $companyFilter

    ORDER BY 
        CASE 
            WHEN po.po_status = 'pending' THEN 1
            WHEN po.po_status = 'open' THEN 2
            WHEN po.po_status = 'sent_to_schedule_delivery' THEN 3
            WHEN po.po_status = 'delivery_date_scheduled' THEN 4
            WHEN po.po_status = 'done' THEN 5
            ELSE 6
        END,
        po.factory_name ASC,
        po.po_number ASC
";

$stmt = $conn->prepare($sql);


// ===============================
// BIND PARAMS
// ===============================

if (
  $userRole === 'user' &&
  !empty($userCompany)
) {

  $stmt->bind_param(
    "ss",
    $item_code,
    $userCompany
  );
} else {

  $stmt->bind_param(
    "s",
    $item_code
  );
}


// ===============================
// EXECUTE
// ===============================

$stmt->execute();

$result = $stmt->get_result();


// ===============================
// PROCESS ROWS
// ===============================

$rows = [];

while ($row = $result->fetch_assoc()) {

  $poStatus = strtolower($row['po_status'] ?? '');

  if ($poStatus === 'done') {

    $row['display_delivered_qty'] = (float)$row['initial_qty'];

    $row['display_remaining_qty'] = 0;
  } else {

    $row['display_delivered_qty'] = (float)$row['db_updated_qty'];

    $row['display_remaining_qty'] = max(
      0,
      (float)$row['initial_qty'] - (float)$row['db_updated_qty']
    );
  }

  $rows[] = $row;
}


// ===============================
// EMPTY CHECK
// ===============================

if (empty($rows)) {

  echo "<p>No records found for this item.</p>";

  include 'partials/footer.php';

  exit();
}


// ===============================
// SUMMARY
// ===============================

$firstRow = $rows[0];

$totalInitial = array_sum(
  array_column($rows, 'initial_qty')
);

$totalDelivered = array_sum(
  array_column($rows, 'display_delivered_qty')
);

$totalRemaining = array_sum(
  array_column($rows, 'display_remaining_qty')
);

$pct = $totalInitial > 0
  ? round(($totalDelivered / $totalInitial) * 100)
  : 0;

?>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  .detail-page {
    min-height: 100vh;
    background: #f0f2f5;
    padding: 32px 24px 60px;
    font-family: 'DM Sans', sans-serif;
  }

  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #888;
    text-decoration: none;
    margin-bottom: 20px;
    transition: color 0.15s;
  }

  .back-link:hover {
    color: #1a1a2e;
  }

  .back-link svg {
    width: 15px;
    height: 15px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
  }

  .page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
  }

  .page-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .page-header-icon {
    width: 48px;
    height: 48px;
    background: #1a1a2e;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .page-header-icon svg {
    width: 24px;
    height: 24px;
    stroke: #fff;
    fill: none;
    stroke-width: 1.8;
  }

  .page-header h2 {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a2e;
    letter-spacing: -0.3px;
  }

  .page-header .item-code-big {
    font-family: 'DM Mono', monospace;
    font-size: 13px;
    font-weight: 500;
    background: #f0f2f5;
    color: #555;
    padding: 3px 10px;
    border-radius: 7px;
    display: inline-block;
    margin-top: 5px;
  }

  .stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
  }

  .stat-card {
    background: #fff;
    border: 1px solid #e8eaed;
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
  }

  .stat-card .stat-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #999;
    margin-bottom: 6px;
  }

  .stat-card .stat-value {
    font-size: 26px;
    font-weight: 700;
    color: #1a1a2e;
    font-family: 'DM Mono', monospace;
    line-height: 1;
  }

  .stat-card .stat-sub {
    font-size: 12px;
    color: #aaa;
    margin-top: 4px;
  }

  .progress-card {
    background: #fff;
    border: 1px solid #e8eaed;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
  }

  .progress-card .prog-label {
    font-size: 13px;
    font-weight: 600;
    color: #1a1a2e;
    white-space: nowrap;
  }

  .progress-card .prog-pct {
    font-size: 22px;
    font-weight: 700;
    font-family: 'DM Mono', monospace;
    color: #1a1a2e;
    min-width: 52px;
  }

  .prog-bar-wrap {
    flex: 1;
    height: 8px;
    background: #e8eaed;
    border-radius: 99px;
    overflow: hidden;
    min-width: 120px;
  }

  .prog-bar {
    height: 100%;
    border-radius: 99px;
    transition: width 0.6s ease;
  }

  .table-card {
    background: #fff;
    border: 1px solid #e8eaed;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
  }

  .table-card-header {
    padding: 16px 24px;
    background: #fafafa;
    border-bottom: 1px solid #e8eaed;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
  }

  .table-card-header-left {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .section-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #1a1a2e;
    flex-shrink: 0;
  }

  .table-card-header span {
    font-size: 13px;
    font-weight: 600;
    color: #1a1a2e;
    text-transform: uppercase;
    letter-spacing: 0.6px;
  }

  .row-count {
    font-size: 12px;
    color: #aaa;
    font-weight: 500;
    background: #f0f2f5;
    padding: 3px 10px;
    border-radius: 20px;
  }

  .detail-table {
    width: 100%;
    border-collapse: collapse;
  }

  .detail-table th {
    padding: 11px 16px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #888;
    background: #fafafa;
    border-bottom: 1px solid #e8eaed;
    text-align: left;
    white-space: nowrap;
  }

  .detail-table td {
    padding: 13px 16px;
    font-size: 13px;
    color: #333;
    border-bottom: 1px solid #f0f2f5;
    vertical-align: middle;
  }

  .detail-table tr:last-child td {
    border-bottom: none;
  }

  .detail-table tbody tr:hover {
    background: #fafbfc;
  }

  .po-num {
    font-family: 'DM Mono', monospace;
    font-size: 12px;
    font-weight: 500;
    color: #1a1a2e;
    background: #f0f2f5;
    padding: 3px 8px;
    border-radius: 6px;
  }

  .badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
  }

  .badge-instamart {
    background: #fff3e0;
    color: #e65100;
  }

  .badge-blinkit {
    background: #f9fbe7;
    color: #827717;
  }

  .badge-zepto {
    background: #fce4ec;
    color: #880e4f;
  }

  .badge-flipkart {
    background: #e3f2fd;
    color: #0d47a1;
  }

  .badge-default {
    background: #f0f2f5;
    color: #555;
  }

  .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
  }

  .status-badge .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
  }

  .status-pending {
    background: #fff8e1;
    color: #f57f17;
  }

  .status-pending .dot {
    background: #ffb300;
  }

  .status-open {
    background: #e3f2fd;
    color: #1565c0;
  }

  .status-open .dot {
    background: #1e88e5;
  }

  .status-sent {
    background: #ede7f6;
    color: #6a1b9a;
  }

  .status-sent .dot {
    background: #8e24aa;
  }

  .status-scheduled {
    background: #e0f7fa;
    color: #006064;
  }

  .status-scheduled .dot {
    background: #00acc1;
  }

  .status-done {
    background: #e8f5e9;
    color: #2e7d32;
  }

  .status-done .dot {
    background: #43a047;
  }

  .status-other {
    background: #f0f2f5;
    color: #666;
  }

  .status-other .dot {
    background: #bbb;
  }

  .user-status {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    background: #f0f2f5;
    color: #555;
  }

  .remaining-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #ffebee;
    color: #c62828;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    font-family: 'DM Mono', monospace;
  }

  .zero-pill {
    background: #e8f5e9;
    color: #2e7d32;
  }

  .qty-num {
    font-family: 'DM Mono', monospace;
    font-size: 13px;
    font-weight: 600;
  }

  .reason-text {
    max-width: 180px;
    font-size: 12px;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .date-pill {
    display: inline-block;
    background: #f0f7ff;
    color: #1565c0;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
  }

  .na-text {
    color: #ccc;
    font-size: 12px;
  }
</style>

<div class="detail-page">

  <a href="pending_items.php" class="back-link">
    <svg viewBox="0 0 24 24">
      <polyline points="15 18 9 12 15 6" />
    </svg>
    Back to Pending Items
  </a>

  <div class="page-header">
    <div class="page-header-left">
      <div class="page-header-icon">
        <svg viewBox="0 0 24 24">
          <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
          <line x1="7" y1="7" x2="7.01" y2="7" />
        </svg>
      </div>
      <div>
        <h2><?php echo htmlspecialchars($firstRow['item_description']); ?></h2>
        <span class="item-code-big"><?php echo htmlspecialchars($firstRow['item_code']); ?></span>
      </div>
    </div>
  </div>

  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-label">Total POs</div>
      <div class="stat-value"><?php echo count($rows); ?></div>
      <div class="stat-sub">With this item</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Ordered</div>
      <div class="stat-value"><?php echo number_format($totalInitial); ?></div>
      <div class="stat-sub">Units</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Delivered</div>
      <div class="stat-value" style="color:#2e7d32"><?php echo number_format($totalDelivered); ?></div>
      <div class="stat-sub">Units</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Remaining</div>
      <div class="stat-value" style="color:#c62828"><?php echo number_format($totalRemaining); ?></div>
      <div class="stat-sub">Units</div>
    </div>
  </div>

  <?php $barColor = $pct >= 80 ? '#43a047' : ($pct >= 40 ? '#ffb300' : '#e53935'); ?>
  <div class="progress-card">
    <div class="prog-label">Overall Fulfillment</div>
    <div class="prog-pct"><?php echo $pct; ?>%</div>
    <div class="prog-bar-wrap">
      <div class="prog-bar" style="width:<?php echo $pct; ?>%; background:<?php echo $barColor; ?>"></div>
    </div>
  </div>

  <div class="table-card">
    <div class="table-card-header">
      <div class="table-card-header-left">
        <div class="section-dot"></div>
        <span>PO Breakdown</span>
      </div>
      <span class="row-count"><?php echo count($rows); ?> record(s)</span>
    </div>

    <div style="overflow-x:auto">
      <table class="detail-table">
        <thead>
          <tr>
            <th>PO Number</th>
            <th>Factory</th>
            <th>Platform</th>
            <th>Delivery Status</th>
            <th>Ordered</th>
            <th>Delivered</th>
            <th>Remaining</th>
            <th>User Status</th>
            <th>Expected Delivery</th>
            <th>Delivery Schedule</th>
            <th>Reason</th>
            <th>Updated By</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php
            $platform = strtolower($row['platform'] ?? '');
            $platformClass = match ($platform) {
              'instamart' => 'badge-instamart',
              'blinkit'   => 'badge-blinkit',
              'zepto'     => 'badge-zepto',
              'flipkart'  => 'badge-flipkart',
              default     => 'badge-default',
            };

            $poStatus = strtolower($row['po_status'] ?? '');
            $statusClass = match ($poStatus) {
              'pending'                    => 'status-pending',
              'open'                       => 'status-open',
              'sent_to_schedule_delivery'  => 'status-sent',
              'delivery_date_scheduled'    => 'status-scheduled',
              'done'                       => 'status-done',
              default                      => 'status-other',
            };

            $statusLabel = match ($poStatus) {
              'sent_to_schedule_delivery' => 'Sent to Schedule',
              'delivery_date_scheduled'   => 'Delivery Scheduled',
              'done'                      => 'Delivered',
              default                     => ucwords(str_replace('_', ' ', $row['po_status'] ?? '—')),
            };
            ?>
            <tr>
              <td><span class="po-num"><?php echo htmlspecialchars($row['po_number']); ?></span></td>

              <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="<?php echo htmlspecialchars($row['factory_name']); ?>">
                <?php echo htmlspecialchars($row['factory_name']); ?>
              </td>

              <td>
                <span class="badge <?php echo $platformClass; ?>">
                  <?php echo htmlspecialchars($row['platform'] ?? '—'); ?>
                </span>
              </td>

              <td>
                <span class="status-badge <?php echo $statusClass; ?>">
                  <span class="dot"></span>
                  <?php echo htmlspecialchars($statusLabel); ?>
                </span>
              </td>

              <td>
                <span class="qty-num"><?php echo number_format($row['initial_qty']); ?></span>
              </td>

              <td>
                <span class="qty-num" style="color:#2e7d32"><?php echo number_format($row['display_delivered_qty']); ?></span>
              </td>

              <td>
                <?php if ($row['display_remaining_qty'] <= 0): ?>
                  <span class="remaining-pill zero-pill">✓ Delivered</span>
                <?php else: ?>
                  <span class="remaining-pill"><?php echo number_format($row['display_remaining_qty']); ?></span>
                <?php endif; ?>
              </td>

              <td>
                <?php if (!empty($row['user_status'])): ?>
                  <span class="user-status"><?php echo htmlspecialchars($row['user_status']); ?></span>
                <?php else: ?>
                  <span class="na-text">—</span>
                <?php endif; ?>
              </td>

              <td>
                <?php if (!empty($row['expected_delivery_date'])): ?>
                  <span class="date-pill"><?php echo date('d-m-Y', strtotime($row['expected_delivery_date'])); ?></span>
                <?php else: ?>
                  <span class="na-text">—</span>
                <?php endif; ?>
              </td>

              <td>
                <?php if (!empty($row['delivery_schedule_date'])): ?>
                  <span class="date-pill"><?php echo date('d-m-Y', strtotime($row['delivery_schedule_date'])); ?></span>
                <?php else: ?>
                  <span class="na-text">—</span>
                <?php endif; ?>
              </td>

              <td>
                <?php if (!empty($row['reason'])): ?>
                  <span class="reason-text" title="<?php echo htmlspecialchars($row['reason']); ?>">
                    <?php echo htmlspecialchars($row['reason']); ?>
                  </span>
                <?php else: ?>
                  <span class="na-text">—</span>
                <?php endif; ?>
              </td>

              <td style="font-size:12px; color:#666">
                <?php echo htmlspecialchars($row['updated_by_name'] ?? '—'); ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php include 'partials/footer.php'; ?>