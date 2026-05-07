<?php
include 'partials/header.php';
checkLogin();

$po_id = isset($_GET['po_id']) ? (int)$_GET['po_id'] : 0;

if ($po_id <= 0) {
    die("Invalid PO ID.");
}

// Fetch PO — we still need pdf_file_path for the PO Created row,
// and the file columns as a fallback for rows logged before this update.
$poStmt = $conn->prepare("SELECT po_number, pdf_file_path,
                                  dispatch_temp_photo, dispatch_bill_copy,
                                  arrival_temp_photo
                           FROM purchase_orders WHERE id = ?");
$poStmt->bind_param("i", $po_id);
$poStmt->execute();
$po = $poStmt->get_result()->fetch_assoc();

if (!$po) {
    die("PO not found.");
}

$stmt = $conn->prepare("SELECT h.*, u.name AS done_by_name
                        FROM po_workflow_history h
                        LEFT JOIN users u ON h.done_by = u.id
                        WHERE h.po_id = ?
                        ORDER BY h.done_at DESC");
$stmt->bind_param("i", $po_id);
$stmt->execute();
$result = $stmt->get_result();

/**
 * Extract file attachments for a single history row.
 *
 * Strategy (newest rows first, oldest as fallback):
 *  1. Parse structured keys embedded in the action_note by dispatch_action.php
 *     (temp_photo_path:…  |  bill_copy_path:…  |  arrival_photo_path:…)
 *  2. Fall back to the current purchase_orders columns for rows logged before
 *     the note-embedding was introduced.
 *  3. For PO-created rows use pdf_file_path.
 */
function getFilesForRow(string $actionType, string $note, array $po): array
{
    $files = [];

    // ── PO Created row ────────────────────────────────────────────────────────
    $poCreatedTypes = ['PO Created', 'po_created', 'created'];
    if (in_array($actionType, $poCreatedTypes)) {
        if (!empty($po['pdf_file_path'])) {
            $files[] = [
                'label' => 'PO Document',
                'path'  => $po['pdf_file_path'],
                'color' => '#c62828',
                'bg'    => '#ffebee',
            ];
        }
        return $files;
    }

    // ── Dispatch row ──────────────────────────────────────────────────────────
    $dispatchTypes = ['dispatch_recorded', 'dispatch_details_saved'];
    if (in_array($actionType, $dispatchTypes)) {

        // Primary: path embedded in the note
        $tempPhotoPath = extractNotePath($note, 'temp_photo_path');
        $billCopyPath  = extractNotePath($note, 'bill_copy_path');

        // Fallback: current PO columns (for rows logged before this update)
        if (!$tempPhotoPath) $tempPhotoPath = $po['dispatch_temp_photo'] ?? '';
        if (!$billCopyPath)  $billCopyPath  = $po['dispatch_bill_copy']  ?? '';

        if ($tempPhotoPath) {
            $files[] = ['label' => 'Temp Photo', 'path' => $tempPhotoPath, 'color' => '#00796b', 'bg' => '#e0f2f1'];
        }
        if ($billCopyPath) {
            $files[] = ['label' => 'Bill Copy',  'path' => $billCopyPath,  'color' => '#1565c0', 'bg' => '#e3f2fd'];
        }
        return $files;
    }

    // ── Arrival row ───────────────────────────────────────────────────────────
    $arrivalTypes = ['arrival_recorded', 'arrival_details_saved'];
    if (in_array($actionType, $arrivalTypes)) {

        $arrivalPhotoPath = extractNotePath($note, 'arrival_photo_path');

        if (!$arrivalPhotoPath) $arrivalPhotoPath = $po['arrival_temp_photo'] ?? '';

        if ($arrivalPhotoPath) {
            $files[] = ['label' => 'Arrival Photo', 'path' => $arrivalPhotoPath, 'color' => '#6a1b9a', 'bg' => '#f3e5f5'];
        }
        return $files;
    }

    return $files;
}

/**
 * Pull a key:value pair out of the pipe-delimited action_note.
 * e.g. "Vehicle: KL07 | temp_photo_path:uploads/dispatch/po_1_disp_temp_…jpg"
 * Returns the path string or empty string if not found.
 */
function extractNotePath(string $note, string $key): string
{
    // Match  key:anything  up to the next pipe separator or end of string
    if (preg_match('/' . preg_quote($key, '/') . ':([^|]+)/i', $note, $m)) {
        return trim($m[1]);
    }
    return '';
}

/**
 * Strip the embedded path tokens from the note so they don't show as raw text
 * to the user. Returns the human-readable portion only.
 */
function cleanNote(string $note): string
{
    $keys = ['temp_photo_path', 'bill_copy_path', 'arrival_photo_path'];
    $parts = array_map('trim', explode('|', $note));
    $cleaned = array_filter($parts, function ($part) use ($keys) {
        foreach ($keys as $k) {
            if (stripos($part, $k . ':') === 0) return false;
        }
        return true;
    });
    return implode(' | ', $cleaned);
}

function isImageFile(string $path): bool
{
    return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}
?>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  .history-page {
    min-height: 100vh;
    background: #f0f2f5;
    padding: 32px 24px 60px;
    font-family: 'DM Sans', sans-serif;
  }

  .back-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 20px;
    text-decoration: none;
    background: #1a1a2e;
    color: #fff;
    padding: 10px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    transition: background .15s;
  }
  .back-btn:hover { background: #2d2d4e; }

  .history-card {
    background: #fff;
    border: 1px solid #e8eaed;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.04);
  }
  .history-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e8eaed;
    background: #fafafa;
    display: flex;
    align-items: center;
    gap: 14px;
  }
  .history-header-icon {
    width: 42px; height: 42px;
    background: #1a1a2e; border-radius: 12px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .history-header-icon svg { width: 20px; height: 20px; stroke: #fff; fill: none; stroke-width: 1.8; }
  .history-header h2 { font-size: 18px; font-weight: 700; color: #1a1a2e; margin-bottom: 3px; }
  .history-header p  { font-size: 13px; color: #777; }

  .history-table { width: 100%; border-collapse: collapse; }
  .history-table th {
    padding: 11px 16px;
    text-align: left;
    background: #fafafa;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #888;
    border-bottom: 1px solid #e8eaed;
    white-space: nowrap;
  }
  .history-table td {
    padding: 13px 16px;
    font-size: 13px;
    color: #333;
    border-bottom: 1px solid #f0f2f5;
    vertical-align: top;
  }
  .history-table tr:last-child td { border-bottom: none; }
  .history-table tbody tr:hover { background: #fafbfc; }

  .action-pill {
    display: inline-block;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 700; letter-spacing: .3px;
    background: #f0f2f5; color: #555;
  }
  .status-pill {
    display: inline-block;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
    background: #e8f5e9; color: #2e7d32;
  }

  /* Note + file chips stacked */
  .note-cell  { display: flex; flex-direction: column; gap: 9px; }
  .note-text  { color: #555; font-size: 12.5px; line-height: 1.55; }
  .file-chips { display: flex; flex-wrap: wrap; gap: 7px; }

  .file-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 11px; border-radius: 8px; border: 1.5px solid transparent;
    font-size: 11.5px; font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer; background: none;
    transition: filter .15s, transform .1s;
  }
  .file-chip:hover { filter: brightness(.91); transform: translateY(-1px); }
  .file-chip svg { width: 13px; height: 13px; fill: none; stroke-width: 2; flex-shrink: 0; }

  .user-chip {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 12px; font-weight: 600; color: #1a1a2e;
  }
  .user-chip-avatar {
    width: 22px; height: 22px; border-radius: 50%;
    background: #1a1a2e; color: #fff;
    font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; text-transform: uppercase;
  }
  .date-mono  { font-family: 'DM Mono', monospace; font-size: 11.5px; color: #777; white-space: nowrap; }
  .serial-num { font-family: 'DM Mono', monospace; font-size: 12px; color: #bbb; }

  .empty { padding: 48px 24px; text-align: center; color: #aaa; font-size: 14px; }
  .empty svg { width: 36px; height: 36px; stroke: #ddd; fill: none; stroke-width: 1.5; margin-bottom: 10px; }

  /* ── Lightbox ── */
  #lightbox-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.82); z-index: 99999;
    align-items: center; justify-content: center;
    padding: 20px; backdrop-filter: blur(3px);
  }
  #lightbox-overlay.active { display: flex; }
  #lightbox-box {
    background: #1a1a2e; border-radius: 16px; overflow: hidden;
    max-width: 90vw; max-height: 90vh;
    display: flex; flex-direction: column;
    box-shadow: 0 20px 80px rgba(0,0,0,.5);
    animation: lbIn .2s ease;
  }
  @keyframes lbIn { from{opacity:0;transform:scale(.94)} to{opacity:1;transform:scale(1)} }
  #lightbox-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 18px; border-bottom: 1px solid rgba(255,255,255,.08); flex-shrink: 0;
  }
  #lightbox-label { font-size: 13px; font-weight: 600; color: #e0e0e0; font-family: 'DM Sans', sans-serif; }
  .lb-toolbar-right { display: flex; align-items: center; gap: 8px; }
  .lb-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 12px; border-radius: 8px; border: none; cursor: pointer;
    font-size: 12px; font-weight: 600; font-family: 'DM Sans', sans-serif;
  }
  .lb-btn svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2; }
  .lb-btn-open  { background: #2d2d4e; color: #90caf9; }
  .lb-btn-open:hover  { background: #3a3a5e; }
  .lb-btn-close { background: #3a1a1a; color: #ef9a9a; }
  .lb-btn-close:hover { background: #4a2020; }
  #lightbox-body {
    overflow: auto; flex: 1;
    display: flex; align-items: center; justify-content: center; padding: 16px;
  }
  #lightbox-img { max-width: 100%; max-height: calc(90vh - 80px); border-radius: 8px; display: block; }
  #lightbox-pdf { width: min(820px, 80vw); height: calc(90vh - 80px); border: none; border-radius: 8px; display: block; }
</style>

<div class="history-page">
  <a href="dashboard.php" class="back-btn">
    <svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:#fff;fill:none;stroke-width:2.5"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Dashboard
  </a>

  <div class="history-card">
    <div class="history-header">
      <div class="history-header-icon">
        <svg viewBox="0 0 24 24"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
      </div>
      <div>
        <h2>PO Workflow History</h2>
        <p>Purchase Order: <strong><?= htmlspecialchars($po['po_number']) ?></strong></p>
      </div>
    </div>

    <?php if ($result->num_rows > 0): ?>
      <div style="overflow-x:auto">
        <table class="history-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Action</th>
              <th>Status</th>
              <th>Note &amp; Attachments</th>
              <th>Done By</th>
              <th>Date &amp; Time</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 1; while ($row = $result->fetch_assoc()):
              $rawNote  = $row['action_note'] ?? '';
              $rowFiles = getFilesForRow($row['action_type'], $rawNote, $po);
              $cleanedNote = cleanNote($rawNote);
            ?>
              <tr>
                <td><span class="serial-num"><?= $i++ ?></span></td>

                <td><span class="action-pill"><?= htmlspecialchars($row['action_type']) ?></span></td>

                <td><span class="status-pill"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $row['status_value']))) ?></span></td>

                <!-- Note text + file chips stacked in one cell -->
                <td>
                  <div class="note-cell">
                    <span class="note-text"><?= nl2br(htmlspecialchars($cleanedNote ?: '—')) ?></span>

                    <?php if (!empty($rowFiles)): ?>
                      <div class="file-chips">
                        <?php foreach ($rowFiles as $f):
                          $isImg = isImageFile($f['path']);
                          $type  = $isImg ? 'image' : 'pdf';
                          $safeP = htmlspecialchars($f['path'], ENT_QUOTES);
                          $safeL = htmlspecialchars($f['label'], ENT_QUOTES);
                        ?>
                          <button type="button"
                                  class="file-chip"
                                  style="background:<?= $f['bg'] ?>;color:<?= $f['color'] ?>;border-color:<?= $f['color'] ?>33"
                                  onclick="openLightbox('<?= $safeP ?>','<?= $safeL ?>','<?= $type ?>')">
                            <?php if ($isImg): ?>
                              <svg viewBox="0 0 24 24" style="stroke:<?= $f['color'] ?>">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                              </svg>
                            <?php else: ?>
                              <svg viewBox="0 0 24 24" style="stroke:<?= $f['color'] ?>">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                              </svg>
                            <?php endif; ?>
                            <?= htmlspecialchars($f['label']) ?>
                          </button>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>

                <td>
                  <?php if (!empty($row['done_by_name'])): ?>
                    <div class="user-chip">
                      <div class="user-chip-avatar"><?= mb_substr($row['done_by_name'], 0, 1) ?></div>
                      <?= htmlspecialchars($row['done_by_name']) ?>
                    </div>
                  <?php else: ?>
                    <span style="color:#ccc;font-size:12px">—</span>
                  <?php endif; ?>
                </td>

                <td><span class="date-mono"><?= date('d-m-Y h:i A', strtotime($row['done_at'])) ?></span></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>No workflow history found for this PO.</div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── Lightbox ── -->
<div id="lightbox-overlay">
  <div id="lightbox-box">
    <div id="lightbox-toolbar">
      <span id="lightbox-label"></span>
      <div class="lb-toolbar-right">
        <a id="lb-open-btn" href="#" target="_blank" class="lb-btn lb-btn-open">
          <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Open in New Tab
        </a>
        <button type="button" class="lb-btn lb-btn-close" onclick="closeLightbox()">
          <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          Close
        </button>
      </div>
    </div>
    <div id="lightbox-body">
      <img  id="lightbox-img" src="" alt="" style="display:none">
      <iframe id="lightbox-pdf" src="" style="display:none" title="PDF Viewer"></iframe>
    </div>
  </div>
</div>

<script>
var lbOverlay = document.getElementById('lightbox-overlay');
var lbLabel   = document.getElementById('lightbox-label');
var lbImg     = document.getElementById('lightbox-img');
var lbPdf     = document.getElementById('lightbox-pdf');
var lbOpenBtn = document.getElementById('lb-open-btn');

function openLightbox(path, label, type) {
    lbLabel.textContent = label;
    lbOpenBtn.href = path;
    if (type === 'image') {
        lbImg.src = path; lbImg.alt = label;
        lbImg.style.display = 'block';
        lbPdf.style.display = 'none'; lbPdf.src = '';
    } else {
        lbPdf.src = path;
        lbPdf.style.display = 'block';
        lbImg.style.display = 'none'; lbImg.src = '';
    }
    lbOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    lbOverlay.classList.remove('active');
    document.body.style.overflow = '';
    setTimeout(function () { lbImg.src = ''; lbPdf.src = ''; }, 200);
}

lbOverlay.addEventListener('click', function (e) { if (e.target === lbOverlay) closeLightbox(); });
document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && lbOverlay.classList.contains('active')) closeLightbox(); });
</script>

<?php include 'partials/footer.php'; ?>