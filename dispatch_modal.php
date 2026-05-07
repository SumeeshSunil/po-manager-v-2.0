<!-- Overlay for all logistics modals -->
<div class="reschedule-modal-overlay" id="logistics-modal-overlay">
    <div class="reschedule-modal" style="max-width: 500px;">
        <div id="modal-content-dispatch" style="display:none;">
            <div class="reschedule-modal-header">
                <div class="reschedule-modal-icon" style="background:#e3f2fd;"><svg viewBox="0 0 24 24" style="stroke:#1565c0" fill="none" stroke-width="2"><path d="M1 3h15v13H1zM16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
                <div><div class="reschedule-modal-title">Vehicle Dispatch</div><div class="reschedule-modal-subtitle" id="disp-po-num"></div></div>
            </div>
            <form action="process_dispatch.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="po_id" id="disp-po-id"><input type="hidden" name="stage" value="dispatch">
                <label>Vehicle Number *</label><input type="text" name="vehicle_number" required placeholder="KL-07-CD-1234" style="width:100%; padding:10px; margin-bottom:10px; border:1.5px solid #ddd; border-radius:8px;">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div><label>Temp (°C) *</label><input type="number" step="0.1" name="temp" required style="width:100%; padding:10px; border:1.5px solid #ddd; border-radius:8px;"></div>
                    <div><label>Dispatch Date/Time *</label><input type="datetime-local" name="dispatch_dt" required value="<?=date('Y-m-d\TH:i')?>" style="width:100%; padding:10px; border:1.5px solid #ddd; border-radius:8px;"></div>
                </div>
                <label style="margin-top:10px;">Upload Bill PDF *</label><input type="file" name="bill_pdf" accept=".pdf" required style="margin-bottom:10px;">
                <label>Temp Photo (Capture) *</label><input type="file" name="temp_photo" accept="image/*" required capture="camera">
                <div class="reschedule-modal-actions">
                    <button type="button" class="reschedule-modal-cancel" onclick="closeLogisticsModal()">Cancel</button>
                    <button type="submit" class="reschedule-modal-confirm" style="background:#1565c0">Start Dispatch</button>
                </div>
            </form>
        </div>

        <div id="modal-content-arrival" style="display:none;">
            <div class="reschedule-modal-header">
                <div class="reschedule-modal-icon" style="background:#e8f5e9;"><svg viewBox="0 0 24 24" style="stroke:#2e7d32" fill="none" stroke-width="2"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/></svg></div>
                <div><div class="reschedule-modal-title">Vehicle Arrival</div><div id="arr-po-num" class="reschedule-modal-subtitle"></div></div>
            </div>
            <form action="process_dispatch.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="po_id" id="arr-po-id"><input type="hidden" name="stage" value="arrival">
                <label>Arrival Temperature (°C) *</label><input type="number" step="0.1" name="temp" required style="width:100%; padding:10px; border:1.5px solid #ddd; border-radius:8px;">
                <label style="margin-top:10px;">Arrival Temp Photo *</label><input type="file" name="temp_photo" accept="image/*" required capture="camera">
                <div class="reschedule-modal-actions"><button type="button" class="reschedule-modal-cancel" onclick="closeLogisticsModal()">Cancel</button><button type="submit" class="reschedule-modal-confirm" style="background:#2e7d32">Confirm Arrival</button></div>
            </form>
        </div>

        <div id="modal-content-handover" style="display:none;">
            <div class="reschedule-modal-title">Final Handover Decision</div>
            <form action="process_dispatch.php" method="POST" style="margin-top:15px;">
                <input type="hidden" name="po_id" id="hand-po-id"><input type="hidden" name="stage" value="handover">
                <label>Decision</label>
                <select name="outcome" onchange="document.getElementById('hand-rej').style.display=(this.value==='rejected'?'block':'none')" style="width:100%; padding:10px; border-radius:8px; border:1.5px solid #ddd;">
                    <option value="success">Success (Handover Done)</option>
                    <option value="rejected">Rejected by Buyer</option>
                </select>
                <div id="hand-rej" style="display:none; margin-top:10px;"><label>Reason *</label><textarea name="reason" style="width:100%; padding:10px; border:1.5px solid #ddd; border-radius:8px;"></textarea></div>
                <div class="reschedule-modal-actions"><button type="button" class="reschedule-modal-cancel" onclick="closeLogisticsModal()">Cancel</button><button type="submit" class="reschedule-modal-confirm">Submit Decision</button></div>
            </form>
        </div>
    </div>
</div>

<script>
function openDisp(id, num) { showModal('dispatch'); document.getElementById('disp-po-id').value=id; document.getElementById('disp-po-num').innerText='PO #'+num; }
function openArr(id, num) { showModal('arrival'); document.getElementById('arr-po-id').value=id; document.getElementById('arr-po-num').innerText='PO #'+num; }
function openHand(id) { showModal('handover'); document.getElementById('hand-po-id').value=id; }
function showModal(stage) {
    document.getElementById('logistics-modal-overlay').classList.add('active');
    ['dispatch','arrival','handover'].forEach(s => document.getElementById('modal-content-'+s).style.display = (s===stage?'block':'none'));
}
function closeLogisticsModal() { document.getElementById('logistics-modal-overlay').classList.remove('active'); }
</script>