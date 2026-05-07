<?php
/**
 * includes/po_table_rows.php
 *
 * Renders the <tbody> content for the PO table.
 * Requires: $rows (array), $isAdmin (bool)
 */

if (empty($rows)): ?>
    <tr id="empty-row">
        <td colspan="13">
            <div class="empty-state">
                <svg viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
                <div>No purchase orders yet</div>
            </div>
        </td>
    </tr>
<?php else: ?>
    <?php foreach ($rows as $row):
        $plt    = strtolower($row['platform'] ?? '');
        $pClass = match ($plt) {
            'instamart' => 'badge-instamart',
            'blinkit'   => 'badge-blinkit',
            'zepto'     => 'badge-zepto',
            'flipkart'  => 'badge-flipkart',
            default     => 'badge-default',
        };
        $st     = strtolower($row['po_status'] ?? '');
        $sClass = match ($st) {
            'pending'                   => 'status-pending',
            'in_progress'               => 'status-in_progress',
            'sent_to_schedule_delivery' => 'status-sent_to_schedule_delivery',
            'delivery_date_scheduled'   => 'status-delivery_date_scheduled',
            'done'                      => 'status-done',
            'rejected'                  => 'status-rejected',
            default                     => 'status-other',
        };

        $canDone       = $isAdmin && $st === 'delivery_date_scheduled';
        $canReject     = $isAdmin && $st === 'delivery_date_scheduled';
        $canReschedule = $isAdmin && in_array($st, ['delivery_date_scheduled', 'rejected']);

        $showExp        = !empty($row['expected_delivery_date']);
        $showSch        = !empty($row['delivery_schedule_date']);
        $showReschedule = !empty($row['reschedule_date']);
        $showBuyerExp   = !empty($row['buyer_expected_date']);
    ?>
    <tr data-po-id="<?= (int)$row['id'] ?>"
        data-po-status="<?= htmlspecialchars($row['po_status'] ?? '') ?>">

        <td style="color:#bbb;font-size:12px;font-family:'DM Mono',monospace"><?= (int)$row['id'] ?></td>

        <td><span class="po-num"><?= htmlspecialchars($row['po_number']) ?></span></td>

        <td>
            <span class="badge <?= $pClass ?>">
                <?= htmlspecialchars($row['platform'] ?? '—') ?>
            </span>
        </td>

        <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
            title="<?= htmlspecialchars($row['factory_name'] ?? '') ?>">
            <?= htmlspecialchars($row['factory_name'] ?? '—') ?>
        </td>

        <td style="font-size:12px;color:#666">
            <?= !empty($row['release_date']) ? date('d-m-Y', strtotime($row['release_date'])) : '—' ?>
        </td>

        <td style="font-size:12px;color:#666">
            <?= !empty($row['expiry_date']) ? date('d-m-Y', strtotime($row['expiry_date'])) : '—' ?>
        </td>

        <td>
            <?php if ($showBuyerExp): ?>
                <span class="schedule-pill" style="background:#e3f2fd;color:#1565c0;">
                    <svg viewBox="0 0 24 24" style="stroke:#1565c0">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <?= date('d-m-Y', strtotime($row['buyer_expected_date'])) ?>
                </span>
            <?php else: ?>
                <span style="color:#ccc;font-size:12px">—</span>
            <?php endif; ?>
        </td>

        <td>
            <?php if ($st === 'rejected'): ?>
                <span class="status-badge status-rejected"
                      onclick="showRejectReason(this, <?= json_encode($row['rejection_reason'] ?? '') ?>)">
                    <span class="dot"></span>
                    Rejected
                    <svg class="info-icon" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="8"/>
                        <line x1="12" y1="12" x2="12" y2="16"/>
                    </svg>
                </span>
            <?php else: ?>
                <span class="status-badge <?= $sClass ?>">
                    <span class="dot"></span>
                    <?= ucfirst(str_replace('_', ' ', $row['po_status'] ?? 'N/A')) ?>
                </span>
            <?php endif; ?>
        </td>

        <td>
            <?php if ($showExp): ?>
                <span class="schedule-pill">
                    <svg viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <?= date('d-m-Y', strtotime($row['expected_delivery_date'])) ?>
                </span>
            <?php else: ?>
                <span style="color:#ccc;font-size:12px">—</span>
            <?php endif; ?>
        </td>

        <td>
            <?php if ($showSch): ?>
                <span class="schedule-pill">
                    <svg viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <?= date('d-m-Y', strtotime($row['delivery_schedule_date'])) ?>
                </span>
            <?php else: ?>
                <span style="color:#ccc;font-size:12px">—</span>
            <?php endif; ?>
        </td>

        <td>
            <?php if ($showReschedule): ?>
                <span class="schedule-pill" style="background:#e8eaf6;color:#283593;">
                    <svg viewBox="0 0 24 24" style="stroke:#283593">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <?= date('d-m-Y', strtotime($row['reschedule_date'])) ?>
                </span>
            <?php else: ?>
                <span style="color:#ccc;font-size:12px">—</span>
            <?php endif; ?>
        </td>

        <td style="font-size:12px;color:#666"><?= htmlspecialchars($row['creator_name'] ?? '—') ?></td>

        <td>
            <div class="action-group">
                <a href="po_view.php?id=<?= (int)$row['id'] ?>" class="action-link action-view">
                    <svg viewBox="0 0 24 24">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>View
                </a>

                <?php if (!empty($row['pdf_file_path'])): ?>
                    <a href="<?= htmlspecialchars($row['pdf_file_path']) ?>"
                       target="_blank" class="action-link action-pdf">
                        <svg viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>PDF
                    </a>
                <?php else: ?>
                    <span class="no-pdf">No PDF</span>
                <?php endif; ?>

                <?php if ($canReschedule): ?>
                    <button type="button" class="action-btn action-reschedule"
                        onclick="openRescheduleModal(
                            <?= (int)$row['id'] ?>,
                            '<?= htmlspecialchars(addslashes($row['po_number']), ENT_QUOTES) ?>'
                        )">
                        <svg viewBox="0 0 24 24">
                            <path d="M23 4v6h-6"/>
                            <path d="M1 20v-6h6"/>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                        </svg>Reschedule
                    </button>
                <?php endif; ?>

                <?php if ($canDone): ?>
                    <form method="POST" action="mark_po_done.php"
                          onsubmit="return confirm('Mark this PO as done?');"
                          style="display:inline;">
                        <input type="hidden" name="po_id" value="<?= (int)$row['id'] ?>">
                        <button type="submit" class="action-btn action-done">
                            <svg viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>Mark Done
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($canReject): ?>
                    <button type="button" class="action-btn action-reject"
                        onclick="openRejectModal(
                            <?= (int)$row['id'] ?>,
                            '<?= htmlspecialchars(addslashes($row['po_number']), ENT_QUOTES) ?>'
                        )">
                        <svg viewBox="0 0 24 24">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>Reject
                    </button>
                <?php endif; ?>

                <a href="po_workflow_history.php?po_id=<?= (int)$row['id'] ?>"
                   class="action-link action-view">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 8v4l3 3"/>
                        <circle cx="12" cy="12" r="9"/>
                    </svg>History
                </a>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
<?php endif; ?>
