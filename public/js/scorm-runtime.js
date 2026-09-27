/*
 * SCORM runtime for lesson pages. Packages look for window.API (SCORM 1.2)
 * or window.API_1484_11 (SCORM 2004) in a parent window; this provides both,
 * restores the learner's saved data, and saves back to the server on Commit,
 * Finish/Terminate, every minute while there are changes, and on leaving.
 */
(function () {
    const box = document.querySelector('[data-scorm]');
    if (!box) {
        return;
    }

    const version = box.dataset.version === '2004' ? '2004' : '1.2';
    const commitUrl = box.dataset.commitUrl;
    const token = box.dataset.token;
    const learner = { id: box.dataset.learnerId, name: box.dataset.learnerName };
    let saved = {};
    try { saved = JSON.parse(box.dataset.state || '{}') || {}; } catch (e) {}

    const resuming = Boolean(saved['cmi.suspend_data'] || saved['cmi.core.lesson_location'] || saved['cmi.location']);
    const defaults = version === '2004'
        ? {
            'cmi.learner_id': learner.id, 'cmi.learner_name': learner.name, 'cmi.completion_status': 'unknown',
            'cmi.success_status': 'unknown', 'cmi.mode': 'normal', 'cmi.credit': 'credit', 'cmi.location': '',
            'cmi.suspend_data': '', 'cmi.launch_data': '', 'cmi.total_time': 'PT0S', 'cmi.completion_threshold': '',
            'cmi.scaled_passing_score': '', 'cmi.max_time_allowed': '', 'cmi.time_limit_action': 'continue,no message',
        }
        : {
            'cmi.core.student_id': learner.id, 'cmi.core.student_name': learner.name, 'cmi.core.lesson_status': 'not attempted',
            'cmi.core.lesson_location': '', 'cmi.core.credit': 'credit', 'cmi.core.lesson_mode': 'normal',
            'cmi.core.total_time': '0000:00:00.00', 'cmi.suspend_data': '', 'cmi.launch_data': '', 'cmi.student_data.mastery_score': '',
        };

    const data = Object.assign({}, defaults, saved);
    data[version === '2004' ? 'cmi.entry' : 'cmi.core.entry'] = resuming ? 'resume' : (version === '2004' ? 'ab-initio' : 'ab-initio');
    const readOnly = new Set(Object.keys(defaults).filter((key) => /student_|learner_|credit|mode|total_time|launch_data|mastery|threshold|passing|max_time|time_limit/.test(key)).concat(['cmi.core.entry', 'cmi.entry']));

    let lastError = '0';
    let dirty = false;
    let initialized = false;
    let finished = false;

    const setError = (code) => { lastError = String(code); return code === 0; };
    const persistable = () => {
        const out = {};
        Object.keys(data).forEach((key) => { if (!readOnly.has(key) && !(key in defaults && data[key] === defaults[key])) out[key] = String(data[key]); });
        return out;
    };

    const save = (keepalive) => {
        if (!dirty && !keepalive) return Promise.resolve();
        dirty = false;
        return fetch(commitUrl, {
            method: 'POST',
            keepalive: Boolean(keepalive),
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ state: persistable() }),
        })
            .then((response) => (response.ok ? response.json() : null))
            .then((result) => { if (result && result.completed) document.dispatchEvent(new CustomEvent('scorm:completed')); })
            .catch(() => { dirty = true; });
    };

    const count = (prefix) => {
        const indices = new Set();
        Object.keys(data).forEach((key) => {
            const match = key.startsWith(prefix) && key.slice(prefix.length).match(/^(\d+)\./);
            if (match) indices.add(match[1]);
        });
        return String(indices.size);
    };

    const getValue = (key) => {
        if (!initialized) { setError(version === '2004' ? 122 : 301); return ''; }
        setError(0);
        if (key.endsWith('._count')) return count(key.slice(0, -6));
        if (key.endsWith('._children')) return '';
        return key in data ? String(data[key]) : '';
    };

    const setValue = (key, value) => {
        if (!initialized || finished) { setError(version === '2004' ? 132 : 301); return 'false'; }
        if (readOnly.has(key)) { setError(version === '2004' ? 404 : 403); return 'false'; }
        data[key] = String(value);
        dirty = true;
        return setError(0) ? 'true' : 'false';
    };

    const init = () => { initialized = true; finished = false; setError(0); return 'true'; };
    const commit = () => { save(false); setError(0); return 'true'; };
    const finish = () => { finished = true; save(true); setError(0); return 'true'; };
    const errorString = (code) => ({ 0: 'No error', 101: 'General exception', 122: 'Retrieve data before initialization', 132: 'Store data before initialization', 301: 'Not initialized', 403: 'Element is read only', 404: 'Element is read only' })[code] || 'Unknown error';

    // SCORM 1.2
    window.API = {
        LMSInitialize: init, LMSFinish: finish, LMSCommit: commit,
        LMSGetValue: getValue, LMSSetValue: setValue,
        LMSGetLastError: () => lastError, LMSGetErrorString: errorString, LMSGetDiagnostic: errorString,
    };
    // SCORM 2004
    window.API_1484_11 = {
        Initialize: init, Terminate: finish, Commit: commit,
        GetValue: getValue, SetValue: setValue,
        GetLastError: () => lastError, GetErrorString: errorString, GetDiagnostic: errorString,
    };

    // Start the package only now that the API is in place.
    const frame = box.querySelector('iframe[data-src]');
    if (frame) frame.src = frame.dataset.src;

    setInterval(() => save(false), 60000);
    window.addEventListener('pagehide', () => { if (dirty) save(true); });
})();
