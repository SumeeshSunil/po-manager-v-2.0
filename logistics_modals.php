<!-- Dispatch Modal -->
<div class="reschedule-modal-overlay" id="dispatch-modal-overlay">
    <div class="reschedule-modal" style="max-width: 500px;">
        <div class="reschedule-modal-header">
            <div class="reschedule-modal-icon" style="background:#e3f2fd;"><svg viewBox="0 0 24 24" fill="none" stroke="#1565c0" stroke-width="2"><path d="M1 3h15v13H1zM16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
            <div>
                <div class="reschedule-modal-title">Vehicle Dispatch</div>
                <div class="reschedule-modal-subtitle" id="disp-po-num"></div>
            </div>
        </div>
        <form action="process_dispatch.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="po_id" id="disp-po-id">
            <input type="hidden" name="stage" value="dispatch">
            
            <label>Vehicle Number *</label>
            <input type="text" name="vehicle_number" required placeholder="KL-XX-0000" style="width:100%; padding:8px; margin-bottom:10px; border-radius:8px; border:1px solid #ddd;">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div>
                    <label>Temperature (°C) *</label>
                    <input type="number" step="0.1" name="temp" required style="width:100%; padding:8px; border-radius:8px; border:1px solid #ddd;">
                </div>
                <div>
                    <label>Date & Time *</label>
                    <input type="datetime-local" name="dispatch_dt" required value="<?= date('Y-m-d\TH:i') ?>" style="width:100%; padding:8px; border-radius:8px; border:1px solid #ddd;">
                </div>
            </div>

            <label style="margin-top:15px; display:block;">Bill PDF *</label>
            <input type="file" name="bill_pdf" accept=".pdf" required>

            <label style="margin-top:15px; display:block;">Temp Photo (Drag & Drop) *</label>
            <div id="drop-zone" style="border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 12px; background: #fafafa; cursor: pointer;">
                <p id="drop-text">Drag photo here or click to upload</p>
                <input type="file" name="temp_photo" id="temp-file" accept="image/*" required style="display:none;">
                <img id="preview" style="max-width:100%; max-height:150px; display:none; margin-top:10px; border-radius:8px;">
            </div>

            <div class="reschedule-modal-actions">
                <button type="button" class="reschedule-modal-cancel" onclick="closeDispatchModal()">Cancel</button>
                <button type="submit" class="reschedule-modal-confirm" style="background:#1565c0">Save Dispatch</button>
            </div>
        </form>
    </div>
</div>

<!-- Handover Modal -->
<div class="reschedule-modal-overlay" id="handover-modal-overlay">
    <div class="reschedule-modal">
        <div class="reschedule-modal-title">Arrival & Handover</div>
        <form action="process_dispatch.php" method="POST">
            <input type="hidden" name="po_id" id="hand-po-id">
            <input type="hidden" name="stage" value="handover">
            
            <label>Arrival Temp (°C) *</label>
            <input type="number" step="0.1" name="arrival_temp" required style="width:100%; padding:8px; border-radius:8px; border:1px solid #ddd; margin-bottom:10px;">
            
            <label>Delivery Status</label>
            <select name="outcome" id="outcome-select" onchange="toggleReason(this.value)" style="width:100%; padding:8px; border-radius:8px; border:1px solid #ddd;">
                <option value="success">Success (Delivered)</option>
                <option value="rejected">Rejected at Destination</option>
            </select>

            <div id="reason-box" style="display:none; margin-top:10px;">
                <label>Rejection Reason *</label>
                <textarea name="reason" placeholder="Why was it rejected?" style="width:100%; height:80px; padding:8px; border-radius:8px; border:1px solid #ddd;"></textarea>
            </div>

            <div class="reschedule-modal-actions">
                <button type="button" class="reschedule-modal-cancel" onclick="closeHandoverModal()">Cancel</button>
                <button type="submit" class="reschedule-modal-confirm">Submit Decision</button>
            </div>
        </form>
    </div>
</div>

<script>
const dz = document.getElementById('drop-zone');
const fi = document.getElementById('temp-file');
const pr = document.getElementById('preview');

dz.onclick = () => fi.click();
dz.ondragover = (e) => { e.preventDefault(); dz.style.borderColor = '#1565c0'; };
dz.ondragleave = () => dz.style.borderColor = '#ccc';
dz.ondrop = (e) => {
    e.preventDefault();
    fi.files = e.dataTransfer.files;
    updatePreview(fi.files[0]);
};
fi.onchange = () => updatePreview(fi.files[0]);

function updatePreview(file) {
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            pr.src = e.target.result;
            pr.style.display = 'block';
            document.getElementById('drop-text').style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
}

function openDispatchModal(id, num) {
    document.getElementById('disp-po-id').value = id;
    document.getElementById('disp-po-num').innerText = 'PO #' + num;
    document.getElementById('dispatch-modal-overlay').classList.add('active');
}
function openHandoverModal(id, num) {
    document.getElementById('hand-po-id').value = id;
    document.getElementById('handover-modal-overlay').classList.add('active');
}
function toggleReason(val) { document.getElementById('reason-box').style.display = (val === 'rejected') ? 'block' : 'none'; }
function closeDispatchModal() { document.getElementById('dispatch-modal-overlay').classList.remove('active'); }
function closeHandoverModal() { document.getElementById('handover-modal-overlay').classList.remove('active'); }
</script>