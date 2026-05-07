/**
 * po_notifications.js
 * ─────────────────────────────────────────────────────────────────────────────
 * Drop this file in your project root, then include it in both pages:
 *   <script src="po_notifications.js"></script>
 *
 * Features:
 *  • Requests browser notification permission on first load
 *  • Dashboard: auto-refreshes table data every 10 s with countdown pill
 *  • Dashboard: fires a browser notification when PO status changes vs last load
 *  • create_po.php: fires a notification after successful PO save
 * ─────────────────────────────────────────────────────────────────────────────
 */

(function () {
  'use strict';

  /* ── 1. Request notification permission ─────────────────────────────────── */
  function requestNotifPermission() {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'default') {
      Notification.requestPermission();
    }
  }
  requestNotifPermission();

  /* ── 2. Fire a browser notification ─────────────────────────────────────── */
  function notify(title, body, icon) {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    try {
      new Notification(title, {
        body: body,
        icon: icon || '/favicon.ico',
        badge: '/favicon.ico',
        tag: 'po-system',          // replaces previous so they don't stack
        renotify: true,
      });
    } catch (e) {
      // Some browsers block in non-HTTPS; fail silently
    }
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     DASHBOARD  (dashboard.php)
     Injects a countdown pill, polls get_po_status.php every 10 s and
     re-renders rows that changed, firing notifications for status changes.
  ═══════════════════════════════════════════════════════════════════════════ */
  if (document.getElementById('po-table')) {
    injectCountdownPill();
    startDashboardPoller();
  }

  /* ── Countdown pill UI ───────────────────────────────────────────────────── */
  function injectCountdownPill() {
    const header = document.querySelector('.table-card-header');
    if (!header) return;

    const pill = document.createElement('div');
    pill.id = 'refresh-pill';
    pill.style.cssText = `
      display:inline-flex;align-items:center;gap:6px;
      background:#f0f2f5;border:1.5px solid #e0e3e8;
      border-radius:20px;padding:5px 12px;
      font-size:12px;font-weight:600;color:#555;
      font-family:'DM Sans',sans-serif;
      transition:background 0.3s,color 0.3s;
      user-select:none;
    `;

    const dot = document.createElement('span');
    dot.id = 'refresh-dot';
    dot.style.cssText = `
      width:7px;height:7px;border-radius:50%;
      background:#43a047;display:inline-block;
      animation:pillPulse 1.5s ease-in-out infinite;
    `;

    const label = document.createElement('span');
    label.id = 'refresh-label';
    label.textContent = 'Refreshing in 10s';

    pill.appendChild(dot);
    pill.appendChild(label);
    header.appendChild(pill);

    // Inject keyframe if not already present
    if (!document.getElementById('pill-style')) {
      const s = document.createElement('style');
      s.id = 'pill-style';
      s.textContent = `
        @keyframes pillPulse {
          0%,100%{opacity:1;transform:scale(1)}
          50%{opacity:.4;transform:scale(.75)}
        }
        @keyframes pillFlash {
          0%{background:#e8f5e9;color:#2e7d32}
          100%{background:#f0f2f5;color:#555}
        }
        .po-row-updated {
          animation: rowHighlight 1.5s ease both;
        }
        @keyframes rowHighlight {
          0%{background:#fffde7}
          100%{background:transparent}
        }
      `;
      document.head.appendChild(s);
    }
  }

  function setCountdown(sec) {
    const label = document.getElementById('refresh-label');
    const pill  = document.getElementById('refresh-pill');
    const dot   = document.getElementById('refresh-dot');
    if (!label) return;

    if (sec <= 0) {
      label.textContent = 'Refreshing…';
      if (dot)  dot.style.background = '#1e88e5';
      if (pill) { pill.style.background = '#e3f2fd'; pill.style.color = '#1565c0'; }
    } else {
      label.textContent = `Refresh in ${sec}s`;
      if (dot)  dot.style.background = '#43a047';
      if (pill) { pill.style.background = '#f0f2f5'; pill.style.color = '#555'; }
    }
  }

  /* ── Dashboard poller ────────────────────────────────────────────────────── */
  let countdown = 10;
  let prevStatuses = collectCurrentStatuses();   // snapshot on page load

  function collectCurrentStatuses() {
    const map = {};
    document.querySelectorAll('#po-table tbody tr[data-po-id]').forEach(tr => {
      map[tr.dataset.poId] = tr.dataset.poStatus;
    });
    return map;
  }

  function startDashboardPoller() {
    // Tick every second for countdown
    setInterval(() => {
      countdown--;
      setCountdown(countdown);
      if (countdown <= 0) {
        countdown = 10;
        fetchAndRefresh();
      }
    }, 1000);
  }

  function fetchAndRefresh() {
    fetch('get_po_status.php?_=' + Date.now(), { credentials: 'same-origin' })
      .then(r => r.json())
      .then(data => {
        if (!data.success) return;
        applyUpdates(data.rows);
        updateStats(data.stats);
        updateSearchIndex();
        flashPill();
      })
      .catch(() => { /* silent fail – no network scare */ });
  }

  function applyUpdates(rows) {
    const tbody = document.querySelector('#po-table tbody');
    if (!tbody) return;

    rows.forEach(row => {
      const existing = tbody.querySelector(`tr[data-po-id="${row.id}"]`);
      if (existing) {
        const oldStatus = existing.dataset.poStatus;
        if (oldStatus !== row.po_status) {
          // Status changed — update cell and fire notification
          const statusCell = existing.querySelector('.status-badge');
          if (statusCell) {
            statusCell.className = `status-badge ${statusClass(row.po_status)}`;
            statusCell.querySelector('.dot').className = 'dot';
            statusCell.lastChild.textContent = ' ' + formatStatus(row.po_status);
          }
          existing.dataset.poStatus = row.po_status;
          existing.classList.remove('po-row-updated');
          // Force reflow to restart animation
          void existing.offsetWidth;
          existing.classList.add('po-row-updated');

          notify(
            '📋 PO Status Changed',
            `PO ${row.po_number}: ${formatStatus(oldStatus)} → ${formatStatus(row.po_status)}`,
            '/favicon.ico'
          );
          prevStatuses[row.id] = row.po_status;
        }
      } else {
        // Brand-new PO — prepend row
        const tr = buildRow(row);
        tbody.insertBefore(tr, tbody.firstChild);
        notify(
          '🆕 New Purchase Order',
          `PO ${row.po_number} created on ${row.platform}`,
          '/favicon.ico'
        );
      }
    });
  }

  function updateStats(stats) {
    if (!stats) return;
    const map = {
      'stat-total':     stats.total,
      'stat-open':      stats.open,
      'stat-needs':     stats.needs_schedule,
      'stat-scheduled': stats.scheduled,
      'stat-done':      stats.done,
    };
    Object.entries(map).forEach(([id, val]) => {
      const el = document.getElementById(id);
      if (el && el.textContent != val) {
        el.textContent = val;
        el.style.transition = 'transform 0.2s';
        el.style.transform = 'scale(1.2)';
        setTimeout(() => el.style.transform = 'scale(1)', 200);
      }
    });
  }

  function updateSearchIndex() {
    // Re-attach search listener (rows may have changed)
    const input = document.getElementById('search-input');
    if (input && !input._listenerAttached) {
      input.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#po-table tbody tr').forEach(tr => {
          tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
      input._listenerAttached = true;
    }
  }

  function flashPill() {
    const pill = document.getElementById('refresh-pill');
    if (!pill) return;
    pill.style.animation = 'none';
    void pill.offsetWidth;
    pill.style.animation = 'pillFlash 0.8s ease forwards';
    setTimeout(() => pill.style.animation = '', 800);
  }

  function statusClass(s) {
    const map = {
      pending:                    'status-pending',
      in_progress:                'status-in_progress',
      sent_to_schedule_delivery:  'status-sent_to_schedule_delivery',
      delivery_date_scheduled:    'status-delivery_date_scheduled',
      done:                       'status-done',
    };
    return map[(s || '').toLowerCase()] || 'status-other';
  }

  function formatStatus(s) {
    return (s || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
  }

  function buildRow(row) {
    // Minimal row for newly-detected POs
    const tr = document.createElement('tr');
    tr.dataset.poId     = row.id;
    tr.dataset.poStatus = row.po_status;
    tr.classList.add('po-row-updated');
    tr.innerHTML = `
      <td style="color:#bbb;font-size:12px;font-family:'DM Mono',monospace">${row.id}</td>
      <td><span class="po-num">${esc(row.po_number)}</span></td>
      <td><span class="badge ${platformClass(row.platform)}">${esc(row.platform)}</span></td>
      <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(row.factory_name)}</td>
      <td style="font-size:12px;color:#666">${formatDate(row.release_date)}</td>
      <td style="font-size:12px;color:#666">${formatDate(row.expiry_date)}</td>
      <td><span class="status-badge ${statusClass(row.po_status)}">
        <span class="dot"></span> ${formatStatus(row.po_status)}
      </span></td>
      <td><span style="color:#ccc;font-size:12px">—</span></td>
      <td><span style="color:#ccc;font-size:12px">—</span></td>
      <td style="font-size:12px;color:#666">${esc(row.creator_name)}</td>
      <td>
        <div class="action-group">
          <a href="po_view.php?id=${row.id}" class="action-link action-view">
            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>View
          </a>
        </div>
      </td>
    `;
    return tr;
  }

  function platformClass(p) {
    const map = { instamart:'badge-instamart', blinkit:'badge-blinkit', zepto:'badge-zepto', flipkart:'badge-flipkart' };
    return map[(p || '').toLowerCase()] || 'badge-default';
  }

  function formatDate(d) {
    if (!d) return '—';
    const dt = new Date(d);
    return isNaN(dt) ? d : dt.toLocaleDateString('en-GB');
  }

  function esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     CREATE PO PAGE (create_po.php)
     Intercepts the form submit, fires notification after a successful save.
  ═══════════════════════════════════════════════════════════════════════════ */
  const poForm = document.querySelector('form[action="save_po.php"]');
  if (poForm) {
    poForm.addEventListener('submit', function () {
      // Store intent in sessionStorage; save_po.php will redirect back,
      // and on the next page load the notification fires.
      const poNum = document.getElementById('po_number')?.value || 'New PO';
      sessionStorage.setItem('po_created', poNum);
    });

    // On any page load, check if we just created a PO
    const created = sessionStorage.getItem('po_created');
    if (created) {
      sessionStorage.removeItem('po_created');
      notify(
        '✅ Purchase Order Created',
        `PO ${created} has been saved successfully.`,
        '/favicon.ico'
      );
    }
  }

  // Also fire when landing on dashboard after a create
  if (document.getElementById('po-table')) {
    const created = sessionStorage.getItem('po_created');
    if (created) {
      sessionStorage.removeItem('po_created');
      setTimeout(() => notify(
        '✅ Purchase Order Created',
        `PO ${created} has been saved successfully.`,
        '/favicon.ico'
      ), 800);
    }
  }

})();