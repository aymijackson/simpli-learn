import ePub from 'epubjs';

function debounce(fn, delayMs) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), delayMs);
    };
}

async function initEpubReader(container) {
    const streamUrl = container.dataset.streamUrl;
    const progressUrl = container.dataset.progressUrl;
    const csrfToken = container.dataset.csrfToken;
    const initialLocation = container.dataset.initialLocation || undefined;

    const viewer = container.querySelector('[data-epub-viewer]');
    const prevButton = container.querySelector('[data-epub-prev]');
    const nextButton = container.querySelector('[data-epub-next]');

    // Same-origin request — the browser sends the session cookie
    // automatically, no special credentials option is needed.
    const book = ePub(streamUrl, { openAs: 'epub' });
    const rendition = book.renderTo(viewer, { width: '100%', height: '75vh' });

    const saveProgress = debounce((cfi) => {
        fetch(progressUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ position: cfi }),
        }).catch(() => {});
    }, 800);

    rendition.on('relocated', (location) => {
        saveProgress(location.start.cfi);
    });

    prevButton.addEventListener('click', () => rendition.prev());
    nextButton.addEventListener('click', () => rendition.next());

    await rendition.display(initialLocation);
}

document.querySelectorAll('[data-epub-reader]').forEach((container) => {
    initEpubReader(container).catch((error) => {
        console.error('Failed to load EPUB', error);
        container.textContent = 'This EPUB could not be loaded.';
    });
});
