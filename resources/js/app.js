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

let audioContext = null;

/**
 * Prepares audio. Must be called from a tap handler: iOS only lets sound
 * start from a user gesture, and once the context is running later sounds
 * (e.g. after the server has answered) are allowed.
 */
window.unlockAudio = function () {
    try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) {
            return;
        }

        audioContext = audioContext || new AudioContextClass();

        if (audioContext.state === 'suspended') {
            audioContext.resume();
        }
    } catch (error) {
        // No audio available: the app is fully usable without sound.
    }
};

function playTone(frequency, startOffsetSeconds, durationSeconds, { type = 'sine', volume = 0.16 } = {}) {
    const start = audioContext.currentTime + startOffsetSeconds;
    const oscillator = audioContext.createOscillator();
    const gain = audioContext.createGain();

    oscillator.type = type;
    oscillator.frequency.value = frequency;

    // Quick fade in and out so the tone doesn't click.
    gain.gain.setValueAtTime(0.0001, start);
    gain.gain.exponentialRampToValueAtTime(volume, start + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, start + durationSeconds);

    oscillator.connect(gain);
    gain.connect(audioContext.destination);
    oscillator.start(start);
    oscillator.stop(start + durationSeconds + 0.05);
}

/**
 * Short synthesized feedback, no audio files needed. "correct" is a bright
 * rising two-note chime; "wrong" is a soft, low, gently falling "boop", never
 * a harsh buzzer.
 */
window.playFeedbackSound = function (kind) {
    if (!audioContext || audioContext.state !== 'running') {
        return;
    }

    try {
        if (kind === 'correct') {
            playTone(659.25, 0, 0.16, { type: 'triangle' });
            playTone(880, 0.12, 0.26, { type: 'triangle' });
        } else {
            playTone(392, 0, 0.18, { volume: 0.12 });
            playTone(311.13, 0.14, 0.28, { volume: 0.12 });
        }
    } catch (error) {
        // Ignore: sound is a nicety.
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
