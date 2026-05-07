/**
 * js/po_notifications.js
 *
 * Handles browser push-notification permission and
 * the notification banner UI.
 *
 * Exports: canNotify(), pushNotif(title, body), initNotifBanner()
 */

'use strict';

export function canNotify() {
    return ('Notification' in window) && Notification.permission === 'granted';
}

export function pushNotif(title, body) {
    if (!canNotify()) return;
    try {
        new Notification(title, { body, icon: '/favicon.ico' });
    } catch (_) {}
}

export function initNotifBanner() {
    const banner  = document.getElementById('notif-banner');
    const nbAllow = document.getElementById('nb-allow');
    const nbDismis = document.getElementById('nb-dismiss');

    if (!('Notification' in window)) return;

    if (Notification.permission === 'default' && !sessionStorage.getItem('nb-gone')) {
        banner.style.display = 'flex';
    }

    nbAllow?.addEventListener('click', () => {
        Notification.requestPermission().then(p => {
            banner.style.display = 'none';
            if (p === 'granted') pushNotif('✅ Notifications enabled', "You'll get alerts for PO status changes.");
        });
    });

    nbDismis?.addEventListener('click', () => {
        banner.style.display = 'none';
        sessionStorage.setItem('nb-gone', '1');
    });
}
