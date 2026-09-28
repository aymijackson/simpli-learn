// Support chat: the floating bubble (people asking for help) and the
// inbox thread (owners / platform admins answering). Talks to the server
// with fetch and polls for new messages — no websockets, so it works on
// shared hosting. Message text is only ever set with textContent.
(() => {
    if (window.__supportChatLoaded) {
        return;
    }
    window.__supportChatLoaded = true;

    const FAST = 4000; // while a conversation is on screen
    const SLOW = 30000; // bubble closed: just keep the unread badge fresh

    function messageItem(message, youLabel) {
        const li = document.createElement('li');
        li.className = 'flex ' + (message.mine ? 'justify-end' : 'justify-start');
        li.dataset.id = message.id;

        const wrap = document.createElement('div');
        wrap.className = 'max-w-[85%]';

        const bubble = document.createElement('div');
        bubble.className = 'rounded-2xl px-3.5 py-2 text-sm leading-relaxed whitespace-pre-line '
            + (message.mine ? 'rounded-br-md bg-brand-600 text-white' : 'rounded-bl-md bg-white text-slate-800 ring-1 ring-slate-200');
        bubble.textContent = message.body;

        const meta = document.createElement('p');
        meta.className = 'mt-1 px-1 text-[11px] text-slate-500' + (message.mine ? ' text-right' : '');
        meta.textContent = (message.mine ? '' : message.sender + ' · ') + message.time;

        wrap.append(bubble, meta);
        li.append(wrap);

        return li;
    }

    function initThread(thread) {
        const list = thread.querySelector('[data-support-messages]');
        const form = thread.querySelector('[data-support-form]');
        const input = form.querySelector('textarea');
        const error = thread.querySelector('[data-support-error]');
        const isBubble = thread.dataset.mode === 'bubble';
        let lastId = parseInt(thread.dataset.lastId || '0', 10) || 0;
        let busy = false;

        const scrollDown = () => { list.scrollTop = list.scrollHeight; };

        function add(messages) {
            let added = false;
            messages.forEach((message) => {
                if (message.id <= lastId || list.querySelector(`[data-id="${message.id}"]`)) {
                    return;
                }
                list.querySelector('[data-support-empty]')?.remove();
                list.append(messageItem(message, thread.dataset.you));
                lastId = Math.max(lastId, message.id);
                added = true;
            });
            if (added) {
                scrollDown();
            }

            return added;
        }

        async function poll({ read = true } = {}) {
            if (busy) {
                return null;
            }
            busy = true;
            try {
                const url = new URL(thread.dataset.pollUrl, window.location.href);
                url.searchParams.set('after', String(lastId));
                if (isBubble && read) {
                    url.searchParams.set('read', '1');
                }
                const response = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                if (!response.ok) {
                    return null;
                }
                const data = await response.json();
                if (read || !isBubble) {
                    add(data.messages || []);
                }

                return data;
            } catch {
                return null;
            } finally {
                busy = false;
            }
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const body = input.value.trim();
            if (!body) {
                return;
            }
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            error.classList.add('hidden');
            try {
                const response = await fetch(thread.dataset.sendUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': thread.dataset.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ body }),
                });
                if (!response.ok) {
                    throw new Error(String(response.status));
                }
                const data = await response.json();
                add([data.message]);
                input.value = '';
            } catch {
                error.textContent = thread.dataset.sendError || 'Your message could not be sent.';
                error.classList.remove('hidden');
            } finally {
                button.disabled = false;
                input.focus();
            }
        });

        // Enter sends; Shift+Enter makes a new line.
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                form.requestSubmit();
            }
        });

        return { poll, scrollDown, input };
    }

    // --- Inbox thread (staff): always live while on screen.
    document.querySelectorAll('[data-support-thread][data-mode="staff"]').forEach((thread) => {
        const chat = initThread(thread);
        chat.scrollDown();
        const tick = async () => {
            if (!document.hidden) {
                await chat.poll();
            }
            setTimeout(tick, FAST);
        };
        setTimeout(tick, FAST);
    });

    // --- Bubble (people asking for help).
    document.querySelectorAll('[data-support-bubble]').forEach((bubble) => {
        const panel = bubble.querySelector('[data-support-panel]');
        const toggle = bubble.querySelector('[data-support-toggle]');
        const badge = bubble.querySelector('[data-support-unread]');
        const chat = initThread(bubble.querySelector('[data-support-thread]'));
        let timer = null;

        const isOpen = () => !panel.hidden;

        function showUnread(count) {
            badge.hidden = !count;
            badge.textContent = count > 9 ? '9+' : String(count || '');
        }

        async function tick() {
            clearTimeout(timer);
            if (!document.hidden) {
                const data = await chat.poll({ read: isOpen() });
                if (data) {
                    showUnread(isOpen() ? 0 : data.unread);
                }
            }
            timer = setTimeout(tick, isOpen() ? FAST : SLOW);
        }

        function open() {
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            showUnread(0);
            tick();
            chat.scrollDown();
            chat.input.focus();
        }

        function close() {
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
            tick();
        }

        toggle.addEventListener('click', () => (isOpen() ? close() : open()));
        bubble.querySelector('[data-support-close]').addEventListener('click', close);
        panel.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close();
            }
        });
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                tick();
            }
        });

        // A "Support replied" notification links to …#support.
        if (window.location.hash === '#support') {
            open();
        } else {
            tick();
        }
    });
})();
