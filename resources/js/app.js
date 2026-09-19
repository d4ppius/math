import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Defined before Alpine.start(): x-init on the summary page calls it immediately.
/**
 * Confetti burst for the end-of-session summary. The library is imported on
 * demand, so it only costs a download on that page, never on the practice
 * screen. Silent for people who asked their device to reduce motion.
 */
window.celebrate = async function ({ big = false } = {}) {
    try {
        const { default: confetti } = await import('canvas-confetti');

        const base = {
            colors: ['#f97316', '#3b82f6', '#22c55e', '#facc15', '#a855f7', '#ec4899'],
            disableForReducedMotion: true,
            zIndex: 100,
        };

        confetti({ ...base, particleCount: 90, spread: 75, origin: { y: 0.6 } });

        if (big) {
            // A new badge deserves cannons from both sides.
            setTimeout(() => confetti({ ...base, particleCount: 70, angle: 60, spread: 70, origin: { x: 0, y: 0.7 } }), 350);
            setTimeout(() => confetti({ ...base, particleCount: 70, angle: 120, spread: 70, origin: { x: 1, y: 0.7 } }), 550);
        }
    } catch (error) {
        // Offline or blocked: the summary works fine without confetti.
    }
};

Alpine.start();

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js').catch(() => {
        // Ignore: push simply won't work, rest of the app is unaffected.
    });
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);

    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

/**
 * Requests notification permission, subscribes this browser to Web Push,
 * and posts the subscription to the server. Returns true on success.
 */
window.subscribeToPush = async function (vapidPublicKey, subscribeUrl, csrfToken) {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return { ok: false, reason: 'unsupported' };
    }

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        return { ok: false, reason: 'denied' };
    }

    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
    });

    const response = await fetch(subscribeUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
        body: JSON.stringify(subscription.toJSON()),
    });

    return { ok: response.ok, reason: response.ok ? null : 'server_error' };
};
