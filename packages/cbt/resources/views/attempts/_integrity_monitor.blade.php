@if ($exam->integrity_monitoring_enabled)
    <script>
        (function () {
            const endpoint = @js(route('cbt.attempts.integrity.store', $attempt));
            const csrfToken = @js(csrf_token());
            const lastReported = {};

            const report = (eventType) => {
                const now = Date.now();
                if (lastReported[eventType] && now - lastReported[eventType] < 2000) {
                    return;
                }
                lastReported[eventType] = now;

                const data = new FormData();
                data.append('_token', csrfToken);
                data.append('event_type', eventType);

                if (navigator.sendBeacon) {
                    navigator.sendBeacon(endpoint, data);
                } else {
                    fetch(endpoint, { method: 'POST', body: data, keepalive: true });
                }
            };

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    report('visibility_hidden');
                }
            });

            window.addEventListener('blur', () => report('window_blur'));
            document.addEventListener('copy', () => report('copy'));
            document.addEventListener('paste', () => report('paste'));
        })();
    </script>
@endif
