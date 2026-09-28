// Deterrents for "secure" library files shown in the platform's viewers.
// Any element marked [data-protected] gets:
//   - no right-click menu, dragging, copying, saving (Ctrl/Cmd+S), printing
//     (Ctrl/Cmd+P) or select-all while the reader is open;
//   - a faint, tiled watermark with the viewer's name (data-watermark), so a
//     screenshot still shows whose copy it was.
// Nothing on a web page can stop screenshots or a determined person with
// developer tools; this removes the easy routes and makes copies traceable.
(() => {
    const regions = [...document.querySelectorAll('[data-protected]')];
    if (regions.length === 0) {
        return;
    }

    const inRegion = (target) => regions.some((region) => region.contains(target));
    const stop = (event) => {
        if (inRegion(event.target)) {
            event.preventDefault();
        }
    };

    document.addEventListener('contextmenu', stop, true);
    document.addEventListener('dragstart', stop, true);
    document.addEventListener('copy', stop, true);
    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && ['s', 'p', 'c', 'a'].includes(event.key.toLowerCase())) {
            event.preventDefault();
        }
    });

    /** A repeating, rotated text tile as an SVG data URI. */
    function watermarkTile(text) {
        const safe = text.replace(/[<>&"']/g, (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;' })[c]);
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="360" height="200">`
            + `<text x="180" y="100" text-anchor="middle" transform="rotate(-24 180 100)" `
            + `font-family="system-ui, sans-serif" font-size="14" fill="rgba(15,23,42,0.14)">${safe}</text></svg>`;

        return `url("data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}")`;
    }

    regions.forEach((region) => {
        const text = region.dataset.watermark;
        if (!text) {
            return;
        }
        region.style.position = region.style.position || 'relative';
        const overlay = document.createElement('div');
        overlay.setAttribute('aria-hidden', 'true');
        overlay.style.cssText = 'position:absolute;inset:0;pointer-events:none;z-index:5;background-repeat:repeat;';
        overlay.style.backgroundImage = watermarkTile(text);
        region.appendChild(overlay);
    });

    // Readers that render inside their own document (EPUB chapters in an
    // iframe) or onto a canvas (PDF pages) use these.
    window.libraryProtect = {
        /** Apply the same blocks inside an iframe's document. */
        protectDocument(doc) {
            ['contextmenu', 'dragstart', 'copy', 'selectstart'].forEach((type) => doc.addEventListener(type, (event) => event.preventDefault(), true));
            doc.addEventListener('keydown', (event) => {
                if ((event.ctrlKey || event.metaKey) && ['s', 'p', 'c', 'a'].includes(event.key.toLowerCase())) {
                    event.preventDefault();
                }
            });
        },

        /** Draw the watermark into a canvas, so even an extracted image carries it. */
        stampCanvas(canvas, text) {
            if (!text) {
                return;
            }
            const context = canvas.getContext('2d');
            context.save();
            context.fillStyle = 'rgba(15, 23, 42, 0.12)';
            context.font = `${Math.round(canvas.width / 48)}px system-ui, sans-serif`;
            context.textAlign = 'center';
            const stepX = canvas.width / 2.5;
            const stepY = canvas.height / 6;
            for (let y = stepY / 2; y < canvas.height + stepY; y += stepY) {
                for (let x = 0; x < canvas.width + stepX; x += stepX) {
                    context.save();
                    context.translate(x + ((y / stepY) % 2) * (stepX / 2), y);
                    context.rotate(-0.42);
                    context.fillText(text, 0, 0);
                    context.restore();
                }
            }
            context.restore();
        },
    };
})();
