/*
 * Lesson video tracking. Counts the seconds of the video actually played
 * (dragging the slider ahead doesn't count), remembers them in this browser
 * so a lesson can be watched over several sittings, and reports the share
 * watched to the server, which completes the lesson at the threshold.
 */
(function () {
    const box = document.querySelector('[data-lesson-video]');
    if (!box) {
        return;
    }

    const iframe = box.querySelector('iframe');
    const provider = box.dataset.provider;
    const progressUrl = box.dataset.progressUrl;
    const token = box.dataset.token;
    const threshold = Number(box.dataset.threshold || 90);
    const storageKey = 'lesson-video:' + box.dataset.key;
    const label = document.querySelector('[data-video-percent]');
    const bar = document.querySelector('[data-video-bar]');

    let watched = new Set();
    try {
        (JSON.parse(localStorage.getItem(storageKey) || '[]') || []).forEach((second) => watched.add(second));
    } catch (e) {}

    let duration = 0;
    let last = null;
    let reported = Number(box.dataset.percent || 0);
    let completed = box.dataset.completed === '1';
    let sending = false;

    const percent = () => (duration > 0 ? Math.min(100, Math.floor((watched.size / Math.floor(duration)) * 100)) : 0);

    const render = (value) => {
        if (label) label.textContent = value + '%';
        if (bar) bar.style.width = value + '%';
    };
    render(Math.max(reported, percent()));

    const save = () => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(Array.from(watched)));
        } catch (e) {}
    };

    const report = () => {
        const value = percent();
        if (sending || value <= reported || (value - reported < 5 && value < threshold)) {
            return;
        }
        sending = true;
        fetch(progressUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ percent: value }),
        })
            .then((response) => (response.ok ? response.json() : null))
            .then((data) => {
                if (!data) return;
                reported = data.percent;
                if (data.completed && !completed) {
                    completed = true;
                    document.dispatchEvent(new CustomEvent('lesson-video:completed'));
                }
            })
            .catch(() => {})
            .finally(() => { sending = false; });
    };

    // Called with the current position (seconds) while the video plays.
    const tick = (seconds, total) => {
        if (total > 0) duration = total;
        const second = Math.floor(seconds);
        // Only count continuous playback: a jump of more than 2s is a seek.
        if (last !== null && second >= last && second - last <= 2) {
            for (let s = last; s <= second; s++) watched.add(s);
        }
        last = second;
        render(Math.max(reported, percent()));
        if (watched.size % 10 === 0) save();
        report();
    };
    const seeked = () => { last = null; save(); };

    const load = (src, ready) => {
        if (document.querySelector('script[src="' + src + '"]')) return ready();
        const script = document.createElement('script');
        script.src = src;
        script.onload = ready;
        document.head.appendChild(script);
    };

    if (provider === 'youtube') {
        let timer = null;
        window.onYouTubeIframeAPIReady = () => {
            const player = new window.YT.Player(iframe, {
                events: {
                    onStateChange: (event) => {
                        clearInterval(timer);
                        if (event.data === window.YT.PlayerState.PLAYING) {
                            timer = setInterval(() => tick(player.getCurrentTime(), player.getDuration()), 1000);
                        } else {
                            seeked();
                        }
                    },
                },
            });
        };
        load('https://www.youtube.com/iframe_api', () => {});
    } else if (provider === 'vimeo') {
        load('https://player.vimeo.com/api/player.js', () => {
            const player = new window.Vimeo.Player(iframe);
            player.on('timeupdate', (data) => tick(data.seconds, data.duration));
            player.on('seeked', seeked);
            player.on('pause', seeked);
        });
    } else if (provider === 'bunny') {
        load('https://assets.mediadelivery.net/playerjs/player-0.1.0.min.js', () => {
            const player = new window.playerjs.Player(iframe);
            player.on('ready', () => {
                player.on('timeupdate', (data) => tick(data.seconds, data.duration));
                player.on('pause', seeked);
            });
        });
    }

    window.addEventListener('pagehide', save);
})();
