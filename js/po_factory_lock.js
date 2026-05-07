/**
 * js/po_factory_lock.js
 *
 * Manages the factory-lock flow for 'user' role:
 *   - Shows the company selection modal on first visit
 *   - Persists choice to DB (save_user_company.php) and localStorage
 *   - Locks the factory filter dropdown so users can only see their factory
 *
 * Exports: initFactoryLock() → returns the locked factory string or null
 */

'use strict';

export function initFactoryLock({ requiresLock, currentUserId, sessionCompany }) {
    if (!requiresLock) return null; // Admins / super / viewers skip all of this

    const LS_KEY = `po_locked_factory_uid_${currentUserId}`;

    // ── Helpers ────────────────────────────────────────────────────────────

    function loadSavedFactory() {
        // DB value (via PHP session) always wins — syncs across devices
        if (sessionCompany) {
            try { localStorage.setItem(LS_KEY, sessionCompany); } catch (_) {}
            return sessionCompany;
        }
        try { return localStorage.getItem(LS_KEY); } catch (_) { return null; }
    }

    function saveFactoryChoice(factory) {
        try { localStorage.setItem(LS_KEY, factory); } catch (_) {}
        fetch('save_user_company.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: `company=${encodeURIComponent(factory)}`,
        }).catch(() => {}); // silently ignore network errors
    }

    function applyLockedFactory(factory) {
        const factoryFilter = document.getElementById('factory-filter');
        const factoryBox    = document.getElementById('factory-filter-box');
        const wrap          = document.getElementById('locked-company-wrap');
        const nameEl        = document.getElementById('locked-company-name');
        const emojiEl       = document.getElementById('locked-company-emoji');

        if (wrap)    wrap.style.display = '';
        if (nameEl)  nameEl.textContent  = factory;
        if (emojiEl) emojiEl.textContent = '🏭';

        if (factoryFilter) {
            // Ensure option exists (may have been built before the lock)
            const exists = Array.from(factoryFilter.options).some(o => o.value === factory);
            if (!exists) {
                const opt = document.createElement('option');
                opt.value = factory;
                opt.textContent = factory;
                factoryFilter.appendChild(opt);
            }
            factoryFilter.value    = factory;
            factoryFilter.disabled = true;
        }
        if (factoryBox) factoryBox.classList.add('is-locked');
    }

    // ── Company modal UI ───────────────────────────────────────────────────

    const companyOverlay  = document.getElementById('company-modal-overlay');
    const companyGrid     = document.getElementById('company-option-grid');
    const companyConfirm  = document.getElementById('company-modal-confirm');
    const companyErrorMsg = document.getElementById('company-modal-error-msg');
    let   selectedFactory = null;

    companyGrid?.addEventListener('click', ev => {
        const opt = ev.target.closest('.company-option');
        if (!opt) return;
        companyGrid.querySelectorAll('.company-option').forEach(el => el.classList.remove('selected'));
        opt.classList.add('selected');
        selectedFactory = opt.dataset.value;
        if (companyConfirm)  companyConfirm.disabled = false;
        if (companyErrorMsg) companyErrorMsg.style.display = 'none';
    });

    companyConfirm?.addEventListener('click', () => {
        if (!selectedFactory) {
            if (companyErrorMsg) companyErrorMsg.style.display = 'block';
            return;
        }
        saveFactoryChoice(selectedFactory);
        companyOverlay?.classList.remove('active');
        applyLockedFactory(selectedFactory);

        // Trigger a filter re-render now that the lock is set
        // (imported lazily to avoid circular deps)
        import('./po_filters.js').then(m => m.applyFiltersAndRender?.()).catch(() => {});
    });

    // ESC does NOT close the company modal — selection is mandatory

    // ── Bootstrap ──────────────────────────────────────────────────────────
    const saved = loadSavedFactory();
    if (saved) {
        applyLockedFactory(saved);
        return saved;
    } else {
        companyOverlay?.classList.add('active'); // First-time user
        return null;
    }
}
