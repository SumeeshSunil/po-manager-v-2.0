<?php

function savePoWorkflow($conn, $po_id, $action_type, $status_value, $action_note = null, $done_by = null) {
    $stmt = $conn->prepare("INSERT INTO po_workflow_history (po_id, action_type, status_value, action_note, done_by, done_at)
                            VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssi", $po_id, $action_type, $status_value, $action_note, $done_by);
    $stmt->execute();
}
?>