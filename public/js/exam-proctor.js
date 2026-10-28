/*
 * Webcam proctoring for exams.
 *
 * [data-proctor-check]   The camera check before a proctored exam: turn the
 *                        camera on, take an ID photo (sent with the start
 *                        form as a data: URL), and agree to monitoring.
 * [data-proctor-session] During the attempt: a small self-view, a photo
 *                        every interval (with jitter) and whenever something
 *                        is flagged, a full-screen prompt, and a block over
 *                        the page while the camera is off.
 *
 * Tab switches and copy/paste are logged separately by the integrity
 * monitor; this adds full-screen exits, camera loss and extra screens.
 */
(function () {
    'use strict';

    const readText = (root) => {
        try {
            return JSON.parse(root.querySelector('[data-proctor-text]').textContent);
        } catch (e) {
            return {};
        }
    };

    const cameraError = (error, text) => {
        if (!window.isSecureContext) return text.insecure || text.unsupported;
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return text.unsupported;
        switch (error && error.name) {
            case 'NotAllowedError':
            case 'SecurityError':
                return text.blocked;
            case 'NotFoundError':
            case 'OverconstrainedError':
                return text.missing;
            case 'NotReadableError':
            case 'AbortError':
                return text.busy;
            default:
                return text.blocked;
        }
    };

    const openCamera = () => {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return Promise.reject(new Error('unsupported'));
        }
        return navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false,
        });
    };

    // A JPEG of the current video frame, at most `maxWidth` pixels wide.
    const frame = (video, maxWidth) => {
        if (!video.videoWidth) return null;
        const scale = Math.min(1, maxWidth / video.videoWidth);
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(video.videoWidth * scale);
        canvas.height = Math.round(video.videoHeight * scale);
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        return canvas;
    };

    // Inline style rather than the `hidden` class, which loses to a button's own display class.
    const show = (el, visible) => {
        if (!el) return;
        el.classList.remove('hidden');
        el.style.display = visible ? '' : 'none';
    };

    /* ---------------------------------------------------------------- check */

    const check = document.querySelector('[data-proctor-check]');
    if (check) {
        const text = readText(check);
        const video = check.querySelector('[data-proctor-video]');
        const preview = check.querySelector('[data-proctor-preview]');
        const placeholder = check.querySelector('[data-proctor-placeholder]');
        const message = check.querySelector('[data-proctor-message]');
        const cameraButton = check.querySelector('[data-proctor-camera]');
        const captureButton = check.querySelector('[data-proctor-capture]');
        const retakeButton = check.querySelector('[data-proctor-retake]');
        const photo = check.querySelector('[data-proctor-photo]');
        const consent = check.querySelector('[data-proctor-consent]');
        let stream = null;

        const say = (value) => { message.textContent = value || ''; };

        const startCamera = () => {
            say(text.starting);
            cameraButton.disabled = true;
            openCamera().then((s) => {
                stream = s;
                video.srcObject = s;
                show(video, true);
                show(preview, false);
                show(placeholder, false);
                show(cameraButton, false);
                show(captureButton, true);
                show(retakeButton, false);
                say(text.ready);
            }).catch((error) => {
                cameraButton.disabled = false;
                say(cameraError(error, text));
            });
        };

        cameraButton.addEventListener('click', startCamera);

        captureButton.addEventListener('click', () => {
            const canvas = frame(video, 960);
            if (!canvas) return;
            photo.value = canvas.toDataURL('image/jpeg', 0.85);
            preview.src = photo.value;
            show(preview, true);
            show(video, false);
            show(captureButton, false);
            show(retakeButton, true);
            say(text.taken);
        });

        retakeButton.addEventListener('click', () => {
            photo.value = '';
            show(preview, false);
            show(video, true);
            show(retakeButton, false);
            show(captureButton, true);
            say(text.ready);
        });

        check.addEventListener('submit', (event) => {
            if (!photo.value) {
                event.preventDefault();
                say(text.needPhoto);
                return;
            }
            if (consent && !consent.checked) {
                event.preventDefault();
                say(text.needConsent);
                consent.focus();
                return;
            }
            if (stream) stream.getTracks().forEach((track) => track.stop());
        });
    }

    /* -------------------------------------------------------------- session */

    const session = document.querySelector('[data-proctor-session]');
    if (!session) return;

    const text = readText(session);
    const video = session.querySelector('[data-proctor-video]');
    const blocker = session.querySelector('[data-proctor-blocker]');
    const blockerMessage = blocker.querySelector('[data-proctor-message]');
    const fullscreenBar = session.querySelector('[data-proctor-fullscreen]');
    const snapshotUrl = session.dataset.snapshotUrl;
    const eventUrl = session.dataset.eventUrl;
    const csrf = session.dataset.csrf;
    const intervalMs = Math.max(15, parseInt(session.dataset.interval, 10) || 60) * 1000;
    const storageKey = 'proctor:' + session.dataset.attempt;

    let leaving = false;
    let cameraOk = false;
    let timer = null;

    // Pages change between questions; only the candidate leaving full screen counts.
    window.addEventListener('beforeunload', () => { leaving = true; });
    document.addEventListener('submit', () => { leaving = true; }, true);
    document.addEventListener('click', (event) => {
        const link = event.target.closest && event.target.closest('a[href]');
        if (link && !link.target && link.getAttribute('href').charAt(0) !== '#') leaving = true;
    }, true);

    const remember = (key, value) => {
        try {
            if (value === undefined) return window.sessionStorage.getItem(storageKey + ':' + key);
            window.sessionStorage.setItem(storageKey + ':' + key, String(value));
        } catch (e) { /* private mode: no memory between pages */ }
        return null;
    };

    const report = (eventType) => {
        const data = new FormData();
        data.append('_token', csrf);
        data.append('event_type', eventType);
        if (navigator.sendBeacon) {
            navigator.sendBeacon(eventUrl, data);
        } else {
            fetch(eventUrl, { method: 'POST', body: data, keepalive: true, credentials: 'same-origin' });
        }
    };

    const snapshot = (kind, eventType) => {
        if (!cameraOk) return;
        const canvas = frame(video, 640);
        if (!canvas) return;
        canvas.toBlob((blob) => {
            if (!blob) return;
            const data = new FormData();
            data.append('image', blob, 'snapshot.jpg');
            data.append('kind', kind);
            if (eventType) data.append('event_type', eventType);
            fetch(snapshotUrl, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            }).catch(() => {});
            if (kind === 'interval') remember('last', Date.now());
        }, 'image/jpeg', 0.7);
    };

    // The next regular photo, carried across question pages so fast answering doesn't multiply them.
    const schedule = () => {
        clearTimeout(timer);
        const last = parseInt(remember('last'), 10) || 0;
        const jitter = intervalMs * (0.75 + Math.random() * 0.5);
        const wait = last ? Math.max(3000, last + jitter - Date.now()) : 5000;
        timer = setTimeout(() => {
            snapshot('interval');
            schedule();
        }, wait);
    };

    const block = (message) => {
        blockerMessage.textContent = message || '';
        blocker.classList.remove('hidden');
        blocker.classList.add('flex');
    };

    const unblock = () => {
        blocker.classList.add('hidden');
        blocker.classList.remove('flex');
    };

    const cameraLost = (message) => {
        if (cameraOk || remember('camera') !== 'off') {
            report('camera_off');
            remember('camera', 'off');
        }
        cameraOk = false;
        clearTimeout(timer);
        block(message);
    };

    // A camera just released by the previous page can take a moment to free
    // up, so try a few times before telling the candidate it's off.
    const startCamera = (triesLeft) => {
        openCamera().then((stream) => {
            video.srcObject = stream;
            stream.getVideoTracks().forEach((track) => {
                track.addEventListener('ended', () => cameraLost(text.blocked));
            });
            cameraOk = true;
            remember('camera', 'on');
            unblock();
            schedule();
        }).catch((error) => {
            const denied = error && (error.name === 'NotAllowedError' || error.name === 'SecurityError');
            if (triesLeft > 0 && !denied) {
                setTimeout(() => startCamera(triesLeft - 1), 1000);
                return;
            }
            cameraLost(cameraError(error, text));
        });
    };

    blocker.querySelector('[data-proctor-retry]').addEventListener('click', () => startCamera(1));
    startCamera(3);

    // Photos at the moment something is flagged (the integrity monitor logs the event itself).
    window.addEventListener('blur', () => { if (!leaving) snapshot('event', 'window_blur'); });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && !leaving) snapshot('event', 'visibility_hidden');
    });

    // Full screen: prompt until the candidate is in it; log leaving it.
    if (document.fullscreenEnabled && document.documentElement.requestFullscreen) {
        const update = () => show(fullscreenBar, !document.fullscreenElement);
        session.querySelector('[data-proctor-enter-fullscreen]').addEventListener('click', () => {
            document.documentElement.requestFullscreen().catch(() => {});
        });
        document.addEventListener('fullscreenchange', () => {
            update();
            if (!document.fullscreenElement && !leaving) {
                report('fullscreen_exit');
                snapshot('event', 'fullscreen_exit');
            }
        });
        update();
    }

    // A second monitor (Chromium browsers) — reported once per attempt.
    if (window.screen && window.screen.isExtended && remember('screens') !== 'reported') {
        report('multiple_screens');
        remember('screens', 'reported');
    }
})();
