// Loaded as <script type="module">; pdf.js wraps the cross-origin worker in
// a same-origin blob itself, so it can come straight from the CDN.
import * as pdfjsLib from 'https://cdn.jsdelivr.net/npm/pdfjs-dist@6.1.200/build/pdf.min.mjs';

pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@6.1.200/build/pdf.worker.min.mjs';

function debounce(fn, delayMs) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), delayMs);
    };
}

async function initPdfReader(container) {
    const streamUrl = container.dataset.streamUrl;
    const progressUrl = container.dataset.progressUrl;
    const csrfToken = container.dataset.csrfToken;
    const initialPage = parseInt(container.dataset.initialPage || '1', 10) || 1;

    const canvas = container.querySelector('[data-pdf-canvas]');
    const pageIndicator = container.querySelector('[data-pdf-page-indicator]');
    const prevButton = container.querySelector('[data-pdf-prev]');
    const nextButton = container.querySelector('[data-pdf-next]');
    const context = canvas.getContext('2d');

    const loadingTask = pdfjsLib.getDocument({ url: streamUrl, withCredentials: true });
    const pdf = await loadingTask.promise;

    let currentPage = Math.min(Math.max(initialPage, 1), pdf.numPages);

    const saveProgress = debounce((page) => {
        fetch(progressUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ position: String(page) }),
        }).catch(() => {});
    }, 800);

    async function renderPage(pageNumber) {
        const page = await pdf.getPage(pageNumber);
        const viewport = page.getViewport({ scale: 1.5 });
        canvas.width = viewport.width;
        canvas.height = viewport.height;

        await page.render({ canvasContext: context, viewport }).promise;

        pageIndicator.textContent = `Page ${pageNumber} of ${pdf.numPages}`;
        prevButton.disabled = pageNumber <= 1;
        nextButton.disabled = pageNumber >= pdf.numPages;
    }

    prevButton.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage -= 1;
            renderPage(currentPage);
            saveProgress(currentPage);
        }
    });

    nextButton.addEventListener('click', () => {
        if (currentPage < pdf.numPages) {
            currentPage += 1;
            renderPage(currentPage);
            saveProgress(currentPage);
        }
    });

    await renderPage(currentPage);
}

document.querySelectorAll('[data-pdf-reader]').forEach((container) => {
    initPdfReader(container).catch((error) => {
        console.error('Failed to load PDF', error);
        container.textContent = 'This PDF could not be loaded.';
    });
});
