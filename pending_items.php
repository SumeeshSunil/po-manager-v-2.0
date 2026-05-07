<?php
include 'partials/header.php';
checkLogin();
checkRole(['super', 'admin', 'viewer', 'user']);
?>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  .pending-page {
    min-height: 100vh;
    background: #f0f2f5;
    padding: 32px 24px 60px;
    font-family: 'DM Sans', sans-serif;
  }

  .page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
  }

  .page-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .page-header-icon {
    width: 44px;
    height: 44px;
    background: #c62828;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .page-header-icon svg {
    width: 22px;
    height: 22px;
    stroke: #fff;
    fill: none;
    stroke-width: 1.8;
  }

  .page-header h2 {
    font-size: 22px;
    font-weight: 600;
    color: #1a1a2e;
    letter-spacing: -0.3px;
  }

  .page-header p {
    font-size: 13px;
    color: #888;
    margin-top: 2px;
  }

  .stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
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
    background: #c62828;
    flex-shrink: 0;
  }

  .table-card-header span {
    font-size: 13px;
    font-weight: 600;
    color: #1a1a2e;
    text-transform: uppercase;
    letter-spacing: 0.6px;
  }

  .search-box {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f0f2f5;
    border: 1.5px solid #e0e3e8;
    border-radius: 8px;
    padding: 6px 12px;
  }

  .search-box svg {
    width: 14px;
    height: 14px;
    stroke: #aaa;
    fill: none;
    stroke-width: 2;
    flex-shrink: 0;
  }

  .search-box input {
    border: none;
    background: transparent;
    outline: none;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    color: #1a1a2e;
    width: 180px;
  }

  .search-box input::placeholder {
    color: #bbb;
  }

  .pending-table {
    width: 100%;
    border-collapse: collapse;
  }

  .pending-table th {
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

  .pending-table td {
    padding: 14px 16px;
    font-size: 13px;
    color: #333;
    border-bottom: 1px solid #f0f2f5;
    vertical-align: middle;
  }

  .pending-table tr:last-child td {
    border-bottom: none;
  }

  .pending-table tbody tr {
    transition: background 0.12s;
  }

  .pending-table tbody tr:hover {
    background: #fafbfc;
  }

  .item-code-tag {
    font-family: 'DM Mono', monospace;
    font-size: 12px;
    font-weight: 500;
    color: #1a1a2e;
    background: #f0f2f5;
    padding: 3px 8px;
    border-radius: 6px;
  }

  .qty-cell {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .qty-val {
    font-family: 'DM Mono', monospace;
    font-size: 13px;
    font-weight: 600;
  }

  .qty-bar-wrap {
    flex: 1;
    height: 5px;
    background: #e8eaed;
    border-radius: 99px;
    overflow: hidden;
    min-width: 60px;
  }

  .qty-bar {
    height: 100%;
    border-radius: 99px;
    transition: width 0.4s;
  }

  .remaining-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #ffebee;
    color: #c62828;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    font-family: 'DM Mono', monospace;
  }

  .remaining-pill svg {
    width: 12px;
    height: 12px;
    stroke: #c62828;
    fill: none;
    stroke-width: 2.5;
  }

  .action-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 12px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    color: #1a1a2e;
    background: #f0f2f5;
    transition: background 0.15s;
  }

  .action-link:hover {
    background: #e4e7ec;
  }

  .action-link svg {
    width: 13px;
    height: 13px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
  }

  .empty-state {
    text-align: center;
    padding: 60px 24px;
    color: #aaa;
    font-size: 14px;
  }

  .empty-state svg {
    width: 40px;
    height: 40px;
    stroke: #ddd;
    fill: none;
    stroke-width: 1.5;
    margin-bottom: 12px;
    display: block;
    margin: 0 auto 12px;
  }
</style>

<?php

// ===============================
// GET SESSION DATA
// ===============================

$userRole = $_SESSION['role'] ?? '';
$userCompany = $_SESSION['user_company'] ?? '';


// ===============================
// FACTORY FILTER
// ===============================

$factoryFilter = "";

if (
  $userRole === 'user' &&
  !empty($userCompany)
) {

  $safeCompany = $conn->real_escape_string(
    trim($userCompany)
  );

  $factoryFilter = "
        WHERE po.factory_name = '$safeCompany'
    ";
}

// ===============================
// MAIN QUERY
// ===============================

$sql = "
    SELECT 
        pi.item_code,
        pi.item_description,

        SUM(pi.qty) AS total_initial_qty,

        SUM(
            CASE
                WHEN po.po_status = 'done' THEN pi.qty
                ELSE COALESCE(pi.deliverable_qty, 0)
            END
        ) AS total_updated_qty,

        SUM(
            CASE
                WHEN po.po_status = 'done' THEN 0
                ELSE pi.qty - COALESCE(pi.deliverable_qty, 0)
            END
        ) AS total_remaining_qty

    FROM po_items pi

    INNER JOIN purchase_orders po 
        ON po.id = pi.po_id

    $factoryFilter

    GROUP BY 
        pi.item_code, 
        pi.item_description

    HAVING SUM(
        CASE 
            WHEN po.po_status != 'done' THEN 1
            ELSE 0
        END
    ) > 0

    ORDER BY pi.item_description ASC
";

$result = $conn->query($sql);

$rows = [];

while ($row = $result->fetch_assoc()) {
  $rows[] = $row;
}

$totalItems     = count($rows);
$totalRemaining = array_sum(array_column($rows, 'total_remaining_qty'));
$totalInitial   = array_sum(array_column($rows, 'total_initial_qty'));
$totalDelivered = array_sum(array_column($rows, 'total_updated_qty'));



?>

<div class="pending-page">

  <div class="page-header">
    <div class="page-header-left">
      <div class="page-header-icon">
        <svg viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" />
          <line x1="12" y1="8" x2="12" y2="12" />
          <line x1="12" y1="16" x2="12.01" y2="16" />
        </svg>
      </div>
      <div>
        <h2>Pending Items Summary</h2>
        <p>Items with remaining quantity based on actual PO status</p>
      </div>
    </div>
  </div>

  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-label">Unique Items</div>
      <div class="stat-value"><?php echo $totalItems; ?></div>
      <div class="stat-sub">Across all relevant POs</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Ordered</div>
      <div class="stat-value"><?php echo number_format($totalInitial); ?></div>
      <div class="stat-sub">Units initially placed</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Delivered</div>
      <div class="stat-value" style="color:#2e7d32"><?php echo number_format($totalDelivered); ?></div>
      <div class="stat-sub">Units fulfilled</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Remaining</div>
      <div class="stat-value" style="color:#c62828"><?php echo number_format($totalRemaining); ?></div>
      <div class="stat-sub">Still pending</div>
    </div>
  </div>

  <div class="table-card">
    <div class="table-card-header">
      <div class="table-card-header-left">
        <div class="section-dot"></div>
        <span>Pending Items</span>
      </div>
      <div class="search-box">
        <svg viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8" />
          <line x1="21" y1="21" x2="16.65" y2="16.65" />
        </svg>
        <input type="text" id="search-input" placeholder="Search items...">
      </div>
    </div>

    <div style="overflow-x:auto">
      <table class="pending-table" id="pending-table">
        <thead>
          <tr>
            <th>Item Code</th>
            <th>Item Name</th>
            <th>Ordered Qty</th>
            <th>Delivered Qty</th>
            <th>Remaining</th>
            <th>Fulfillment</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="7">
                <div class="empty-state">
                  <svg viewBox="0 0 24 24">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                    <polyline points="22 4 12 14.01 9 11.01" />
                  </svg>
                  <div>No pending items — all caught up!</div>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $row):
              $pct = $row['total_initial_qty'] > 0
                ? round(($row['total_updated_qty'] / $row['total_initial_qty']) * 100)
                : 0;

              $barColor = $pct >= 80 ? '#43a047' : ($pct >= 40 ? '#ffb300' : '#e53935');
            ?>
              <tr>
                <td>
                  <span class="item-code-tag"><?php echo htmlspecialchars($row['item_code']); ?></span>
                </td>

                <td style="font-weight:500; color:#1a1a2e">
                  <?php echo htmlspecialchars($row['item_description']); ?>
                </td>

                <td>
                  <span style="font-family:'DM Mono',monospace; font-size:13px; font-weight:600; color:#555">
                    <?php echo number_format($row['total_initial_qty']); ?>
                  </span>
                </td>

                <td>
                  <span style="font-family:'DM Mono',monospace; font-size:13px; font-weight:600; color:#2e7d32">
                    <?php echo number_format($row['total_updated_qty']); ?>
                  </span>
                </td>

                <td>
                  <span class="remaining-pill">
                    <svg viewBox="0 0 24 24">
                      <line x1="12" y1="5" x2="12" y2="19" />
                      <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    <?php echo number_format($row['total_remaining_qty']); ?>
                  </span>
                </td>

                <td style="min-width:120px">
                  <div class="qty-cell">
                    <span style="font-size:11px; font-weight:600; color:#888; font-family:'DM Mono',monospace; min-width:32px">
                      <?php echo $pct; ?>%
                    </span>
                    <div class="qty-bar-wrap">
                      <div class="qty-bar" style="width:<?php echo $pct; ?>%; background:<?php echo $barColor; ?>"></div>
                    </div>
                  </div>
                </td>

                <td>
                  <a href="pending_item_detail.php?item_code=<?php echo urlencode($row['item_code']); ?>" class="action-link">
                    <svg viewBox="0 0 24 24">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                    View
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
  document.getElementById('search-input').addEventListener('input', function() {

    const q = this.value.toLowerCase();

    document.querySelectorAll('#pending-table tbody tr').forEach(tr => {

      tr.style.display = tr.textContent
        .toLowerCase()
        .includes(q) ?
        '' :
        'none';

    });

  });
</script>

<?php include 'partials/footer.php'; ?>