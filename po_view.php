<?php
include 'partials/header.php';
checkLogin();

$po_id = (int)$_GET['id'];

$poStmt = $conn->prepare("SELECT * FROM purchase_orders WHERE id = ?");
$poStmt->bind_param("i", $po_id);
$poStmt->execute();
$poResult = $poStmt->get_result();
$po = $poResult->fetch_assoc();

if (!$po) {
  die("PO not found.");
}

$itemStmt = $conn->prepare("SELECT pi.*, u.name AS updated_by_name
                            FROM po_items pi
                            LEFT JOIN users u ON pi.updated_by = u.id
                            WHERE pi.po_id = ?");
$itemStmt->bind_param("i", $po_id);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();
?>

<style>
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  .po-view-page {
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

  .info-card {
    background: #fff;
    border: 1px solid #e8eaed;
    border-radius: 16px;
    padding: 24px 28px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    margin-bottom: 20px;
  }

  .info-card-title {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #999;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .section-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #c62828;
    flex-shrink: 0;
  }

  .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
  }

  .info-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #aaa;
    margin-bottom: 4px;
  }

  .info-value {
    font-size: 14px;
    font-weight: 500;
    color: #1a1a2e;
  }

  .status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
  }

  .status-pending {
    background: #fff8e1;
    color: #f59f00;
  }

  .status-in_progress {
    background: #e3f2fd;
    color: #1565c0;
  }

  .status-done {
    background: #e8f5e9;
    color: #2e7d32;
  }

  .status-sent_to_schedule_delivery {
    background: #ede7f6;
    color: #6a1b9a;
  }

  .status-delivery_date_scheduled {
    background: #ede7f6;
    color: #6a1b9a;
  }

  .status-rejected {
    background: #fce4ec;
    color: #b71c1c;
  }

  .pdf-links {
    display: flex;
    gap: 10px;
  }

  .pdf-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    background: #f0f2f5;
    color: #1a1a2e;
    transition: background 0.15s;
  }

  .pdf-btn:hover {
    background: #e4e7ec;
  }

  .pdf-btn svg {
    width: 13px;
    height: 13px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
  }

  .form-card {
    background: #fff;
    border: 1px solid #e8eaed;
    border-radius: 16px;
    padding: 24px 28px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    margin-bottom: 20px;
  }

  .form-card-title {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #999;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .form-group {
    margin-bottom: 16px;
  }

  .form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #555;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
  }

  .form-group input[type="date"] {
    padding: 9px 14px;
    border: 1.5px solid #e0e3e8;
    border-radius: 9px;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    color: #1a1a2e;
    background: #fafafa;
    outline: none;
    transition: border-color 0.15s;
  }

  .form-group input[type="date"]:focus {
    border-color: #c62828;
    background: #fff;
  }

  .btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    background: #c62828;
    color: #fff;
    border: none;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    transition: background 0.15s;
  }

  .btn-primary:hover {
    background: #b71c1c;
  }

  .btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    background: #fff;
    color: #1a1a2e;
    border: 1px solid #ddd;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
  }

  .table-card {
    background: #fff;
    border: 1px solid #e8eaed;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    margin-bottom: 20px;
  }

  .table-card-header {
    padding: 16px 24px;
    background: #fafafa;
    border-bottom: 1px solid #e8eaed;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .table-card-header span {
    font-size: 13px;
    font-weight: 600;
    color: #1a1a2e;
    text-transform: uppercase;
    letter-spacing: 0.6px;
  }

  .po-table {
    width: 100%;
    border-collapse: collapse;
  }

  .po-table th {
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

  .po-table td {
    padding: 14px 16px;
    font-size: 13px;
    color: #333;
    border-bottom: 1px solid #f0f2f5;
    vertical-align: middle;
  }

  .po-table tr:last-child td {
    border-bottom: none;
  }

  .po-table tbody tr:hover {
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

  .qty-val {
    font-family: 'DM Mono', monospace;
    font-size: 13px;
    font-weight: 600;
    color: #1a1a2e;
  }

  .updated-by {
    font-size: 12px;
    color: #888;
  }

  .updated-at {
    font-size: 12px;
    color: #aaa;
    font-family: 'DM Mono', monospace;
  }

  .expected-date-display {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #e8f5e9;
    color: #2e7d32;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    font-family: 'DM Mono', monospace;
  }

  .expected-date-display svg {
    width: 12px;
    height: 12px;
    stroke: #2e7d32;
    fill: none;
    stroke-width: 2.5;
  }

  .reschedule-date-display {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #e8eaf6;
    color: #283593;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    font-family: 'DM Mono', monospace;
  }

  .reschedule-date-display svg {
    width: 12px;
    height: 12px;
    stroke: #283593;
    fill: none;
    stroke-width: 2.5;
  }

  .not-set {
    color: #aaa;
    font-size: 13px;
    font-style: italic;
  }

  .deliverable-input {
    width: 110px;
    padding: 8px 10px;
    border: 1.5px solid #e0e3e8;
    border-radius: 9px;
    font-size: 13px;
    font-family: 'DM Mono', monospace;
    outline: none;
  }

  .deliverable-input:focus {
    border-color: #c62828;
  }

  .modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }

  .modal-box {
    width: 900px;
    max-width: 96%;
    max-height: 90vh;
    overflow-y: auto;
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
  }

  .modal-box h3 {
    font-size: 18px;
    margin-bottom: 8px;
    color: #1a1a2e;
  }

  .modal-box p {
    font-size: 13px;
    color: #666;
    margin-bottom: 16px;
  }

  .reason-textarea {
    width: 100%;
    min-height: 90px;
    margin-top: 10px;
    padding: 12px;
    border: 1.5px solid #e0e3e8;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    resize: vertical;
    outline: none;
  }

  .reason-textarea:focus {
    border-color: #c62828;
  }

  .modal-actions {
    margin-top: 18px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
  }
</style>

<div class="po-view-page">

  <div class="page-header">
    <div class="page-header-left">
      <div class="page-header-icon">
        <svg viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <div>
        <h2>PO Details</h2>
        <p>Purchase Order — <?php echo htmlspecialchars($po['po_number']); ?></p>
      </div>
    </div>
  </div>

  <div class="info-card">
    <div class="info-card-title"><span class="section-dot"></span> Order Information</div>
    <div class="info-grid">

      <div class="info-item">
        <div class="info-label">PO Number</div>
        <div class="info-value">
          <span class="item-code-tag"><?php echo htmlspecialchars($po['po_number']); ?></span>
        </div>
      </div>

      <div class="info-item">
        <div class="info-label">Platform</div>
        <div class="info-value"><?php echo htmlspecialchars($po['platform']); ?></div>
      </div>

      <div class="info-item">
        <div class="info-label">Factory Name</div>
        <div class="info-value"><?php echo htmlspecialchars($po['factory_name']); ?></div>
      </div>

      <div class="info-item">
        <div class="info-label">Release Date</div>
        <div class="info-value"><?php echo htmlspecialchars($po['release_date']); ?></div>
      </div>

      <div class="info-item">
        <div class="info-label">Buyer Expected Date</div>
        <div class="info-value"><?php echo htmlspecialchars($po['buyer_expected_date']); ?></div>
      </div>

      <div class="info-item">
        <div class="info-label">Expiry Date</div>
        <div class="info-value"><?php echo htmlspecialchars($po['expiry_date']); ?></div>
      </div>

      <div class="info-item">
        <div class="info-label">PO Status</div>
        <div class="info-value">
          <span class="status-badge status-<?php echo htmlspecialchars($po['po_status']); ?>">
            <?php echo ucfirst(str_replace('_', ' ', $po['po_status'])); ?>
          </span>
        </div>
      </div>

      <div class="info-item">
        <div class="info-label">PO PDF</div>
        <div class="info-value">
          <?php if (!empty($po['pdf_file_path'])): ?>
            <div class="pdf-links">
              <a href="<?php echo $po['pdf_file_path']; ?>" target="_blank" class="pdf-btn">
                <svg viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                View
              </a>
              <a href="<?php echo $po['pdf_file_path']; ?>" download class="pdf-btn">
                <svg viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download
              </a>
            </div>
          <?php else: ?>
            <span class="not-set">Not uploaded</span>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($po['expected_delivery_date'])): ?>
        <div class="info-item">
          <div class="info-label">Expected Delivery Date</div>
          <div class="info-value">
            <span class="expected-date-display">
              <svg viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <?php echo date('d-m-Y', strtotime($po['expected_delivery_date'])); ?>
            </span>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($po['delivery_schedule_date'])): ?>
        <div class="info-item">
          <div class="info-label">Delivery Schedule Date</div>
          <div class="info-value">
            <span class="expected-date-display">
              <svg viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <?php echo date('d-m-Y', strtotime($po['delivery_schedule_date'])); ?>
            </span>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($po['reschedule_date'])): ?>
        <div class="info-item">
          <div class="info-label">Reschedule Date</div>
          <div class="info-value">
            <span class="reschedule-date-display">
              <svg viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <?php echo date('d-m-Y', strtotime($po['reschedule_date'])); ?>
            </span>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <?php if ($_SESSION['role'] == 'admin' && $po['po_status'] === 'sent_to_schedule_delivery' && empty($po['delivery_schedule_date'])): ?>
    <div class="form-card">
      <div class="form-card-title"><span class="section-dot"></span> Set Delivery Schedule Date</div>
      <form method="POST" action="save_delivery_schedule.php">
        <input type="hidden" name="po_id" value="<?php echo $po_id; ?>">

        <div class="form-group">
          <label>Delivery Schedule Date</label>
          <input type="date" name="delivery_schedule_date" required>
        </div>

        <button type="submit" class="btn-primary">
          <svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:#fff;fill:none;stroke-width:2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          Save Schedule Date
        </button>
      </form>
    </div>
  <?php endif; ?>

  <?php if (
    ($_SESSION['role'] == 'user' || $_SESSION['role'] == 'admin') &&
    $po['po_status'] != 'sent_to_schedule_delivery' &&
    $po['po_status'] != 'done' &&
    empty($po['delivery_schedule_date'])
  ): ?>
    <div class="form-card">
      <div class="form-card-title"><span class="section-dot"></span> Expected Delivery Date</div>
      <form method="POST" action="save_item_action.php" id="itemActionForm">
        <input type="hidden" name="po_id" value="<?php echo $po_id; ?>">
        <input type="hidden" name="short_reason_hidden" id="shortReasonHidden">

        <div class="form-group">
          <label>Expected Delivery Date for this PO</label>
          <input type="date" name="expected_delivery_date" value="<?php echo $po['expected_delivery_date'] ?? ''; ?>" required>
        </div>

        <div class="table-card" style="margin-top:20px; margin-bottom:20px;">
          <div class="table-card-header">
            <span class="section-dot"></span>
            <span>Items</span>
          </div>
          <table class="po-table">
            <thead>
              <tr>
                <th>Item Code</th>
                <th>Description</th>
                <th>PO Qty</th>
                <th>Deliverable Qty</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($item = $itemResult->fetch_assoc()): ?>
                <?php
                $poQty = (int)$item['qty'];
                $deliverableQty = isset($item['deliverable_qty']) && $item['deliverable_qty'] !== null && (int)$item['deliverable_qty'] > 0
                  ? (int)$item['deliverable_qty']
                  : $poQty;
                ?>
                <tr>
                  <td><span class="item-code-tag"><?php echo htmlspecialchars($item['item_code']); ?></span></td>
                  <td><?php echo htmlspecialchars($item['item_description']); ?></td>
                  <td><span class="qty-val"><?php echo htmlspecialchars($item['qty']); ?></span></td>
                  <td>
                    <input
                      type="number"
                      class="deliverable-input"
                      name="deliverable_qty[<?php echo (int)$item['id']; ?>]"
                      value="<?php echo htmlspecialchars($deliverableQty); ?>"
                      min="0"
                      max="<?php echo htmlspecialchars($poQty); ?>"
                      data-qty="<?php echo htmlspecialchars($poQty); ?>"
                      data-code="<?php echo htmlspecialchars($item['item_code']); ?>"
                      data-desc="<?php echo htmlspecialchars($item['item_description']); ?>"
                      required>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <button type="submit" class="btn-primary">
          <svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:#fff;fill:none;stroke-width:2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          Save
        </button>
      </form>
    </div>
  <?php else: ?>
    <div class="table-card">
      <div class="table-card-header">
        <span class="section-dot"></span>
        <span>Items</span>
      </div>
      <table class="po-table">
        <thead>
          <tr>
            <th>Item Code</th>
            <th>Description</th>
            <th>PO Qty</th>
            <th>Deliverable Qty</th>
            <th>Short Qty</th>
            <th>Status</th>
            <th>Reason</th>
            <th>Updated By</th>
            <th>Updated At</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $itemStmt2 = $conn->prepare("SELECT pi.*, u.name AS updated_by_name
                                       FROM po_items pi
                                       LEFT JOIN users u ON pi.updated_by = u.id
                                       WHERE pi.po_id = ?");
          $itemStmt2->bind_param("i", $po_id);
          $itemStmt2->execute();
          $itemResult2 = $itemStmt2->get_result();

          while ($item = $itemResult2->fetch_assoc()):
            $poQty = (int)$item['qty'];
            $deliverableQty = isset($item['deliverable_qty']) && $item['deliverable_qty'] !== null
              ? (int)$item['deliverable_qty']
              : $poQty;
            $shortQty = max($poQty - $deliverableQty, 0);
          ?>
            <tr>
              <td><span class="item-code-tag"><?php echo htmlspecialchars($item['item_code']); ?></span></td>
              <td><?php echo htmlspecialchars($item['item_description']); ?></td>
              <td><span class="qty-val"><?php echo htmlspecialchars($item['qty']); ?></span></td>
              <td><span class="qty-val"><?php echo htmlspecialchars($deliverableQty); ?></span></td>
              <td><span class="qty-val"><?php echo htmlspecialchars($shortQty); ?></span></td>
              <td><?php echo htmlspecialchars($item['user_status'] ?? 'pending'); ?></td>
              <td><?php echo htmlspecialchars($item['reason'] ?? '—'); ?></td>
              <td><span class="updated-by"><?php echo htmlspecialchars($item['updated_by_name'] ?? '—'); ?></span></td>
              <td><span class="updated-at"><?php echo htmlspecialchars($item['updated_at'] ?? '—'); ?></span></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>

<div class="modal-overlay" id="shortModal">
  <div class="modal-box">
    <h3>Short Delivery Preview</h3>
    <p>These items have deliverable quantity less than PO quantity. Please enter the reason.</p>

    <table class="po-table">
      <thead>
        <tr>
          <th>Item Code</th>
          <th>Description</th>
          <th>PO Qty</th>
          <th>Deliverable Qty</th>
          <th>Short Qty</th>
        </tr>
      </thead>
      <tbody id="shortPreviewBody"></tbody>
    </table>

    <div class="form-group" style="margin-top:16px;">
      <label>Reason</label>
      <textarea id="shortReasonBox" class="reason-textarea" placeholder="Enter reason for short delivery"></textarea>
    </div>

    <div class="modal-actions">
      <button type="button" class="btn-secondary" onclick="closeShortModal()">Cancel</button>
      <button type="button" class="btn-primary" onclick="confirmShortSubmit()">Confirm & Save</button>
    </div>
  </div>
</div>

<script>
  let allowSubmit = false;
  const itemActionForm = document.getElementById("itemActionForm");

  if (itemActionForm) {
    itemActionForm.addEventListener("submit", function(e) {
      if (allowSubmit) {
        return true;
      }

      const inputs = document.querySelectorAll(".deliverable-input");
      const shortItems = [];
      let hasError = false;

      inputs.forEach(function(input) {
        const poQty = parseInt(input.dataset.qty, 10);
        const deliverableQty = parseInt(input.value, 10);

        if (hasError) return;

        if (isNaN(deliverableQty) || deliverableQty < 0) {
          alert("Deliverable qty cannot be empty or negative.");
          input.focus();
          hasError = true;
          return;
        }

        if (deliverableQty > poQty) {
          alert("Deliverable qty cannot be greater than PO qty.");
          input.focus();
          hasError = true;
          return;
        }

        if (deliverableQty < poQty) {
          shortItems.push({
            code: input.dataset.code,
            desc: input.dataset.desc,
            poQty: poQty,
            deliverableQty: deliverableQty,
            shortQty: poQty - deliverableQty
          });
        }
      });

      if (hasError) {
        e.preventDefault();
        return false;
      }

      if (shortItems.length > 0) {
        e.preventDefault();
        showShortModal(shortItems);
        return false;
      }
    });
  }

  function showShortModal(items) {
    const tbody = document.getElementById("shortPreviewBody");
    tbody.innerHTML = "";

    items.forEach(function(item) {
      tbody.innerHTML += `
      <tr>
        <td><span class="item-code-tag">${escapeHtml(item.code)}</span></td>
        <td>${escapeHtml(item.desc)}</td>
        <td><span class="qty-val">${item.poQty}</span></td>
        <td><span class="qty-val">${item.deliverableQty}</span></td>
        <td><span class="qty-val">${item.shortQty}</span></td>
      </tr>
    `;
    });

    document.getElementById("shortModal").style.display = "flex";
  }

  function closeShortModal() {
    document.getElementById("shortModal").style.display = "none";
  }

  function confirmShortSubmit() {
    const reason = document.getElementById("shortReasonBox").value.trim();

    if (reason === "") {
      alert("Please enter reason.");
      document.getElementById("shortReasonBox").focus();
      return;
    }

    document.getElementById("shortReasonHidden").value = reason;
    allowSubmit = true;
    itemActionForm.submit();
  }

  function escapeHtml(text) {
    return String(text).replace(/[&<>"']/g, function(m) {
      return ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      })[m];
    });
  }
</script>

<?php include 'partials/footer.php'; ?>