// Global scripts loaded on every page (see partials/assets.blade.php).

document.addEventListener('trix-before-initialize', () => {
    const { config } = window.Trix;

    if (config.textAttributes.subscript) {
        return;
    }

    config.textAttributes.subscript = {
        tagName: 'sub',
        inheritable: true,
    };

    config.textAttributes.superscript = {
        tagName: 'sup',
        inheritable: true,
    };

    const getDefaultHTML = config.toolbar.getDefaultHTML;

    config.toolbar.getDefaultHTML = () => getDefaultHTML().replace(
        '</span>\n\n      <span class="trix-button-group trix-button-group--block-tools"',
        `<button type="button" class="trix-button" data-trix-attribute="subscript" title="Subscript" tabindex="-1">x&#8322;</button>
        <button type="button" class="trix-button" data-trix-attribute="superscript" title="Superscript" tabindex="-1">x&#178;</button>
      </span>\n\n      <span class="trix-button-group trix-button-group--block-tools"`,
    );
});

document.addEventListener('trix-attachment-add', (event) => {
    const { attachment } = event;

    if (!attachment.file) {
        return;
    }

    const uploadUrl = event.target.getAttribute('data-upload-url');
    const formData = new FormData();
    formData.append('file', attachment.file);

    const fail = () => {
        event.target.editor.removeAttachment(attachment);
        window.alert('That file could not be uploaded. Please try again.');
    };

    // XHR rather than fetch() because fetch can't report upload progress.
    const xhr = new XMLHttpRequest();
    xhr.open('POST', uploadUrl);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    // Laravel sets an XSRF-TOKEN cookie on every response for exactly this.
    const xsrf = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
    if (xsrf) {
        xhr.setRequestHeader('X-XSRF-TOKEN', decodeURIComponent(xsrf.split('=')[1]));
    }

    xhr.upload.addEventListener('progress', (progressEvent) => {
        if (progressEvent.lengthComputable) {
            attachment.setUploadProgress(Math.round((progressEvent.loaded / progressEvent.total) * 100));
        }
    });

    xhr.addEventListener('load', () => {
        if (xhr.status < 200 || xhr.status >= 300) {
            fail();
            return;
        }

        const { url } = JSON.parse(xhr.responseText);
        attachment.setAttributes({ url, href: url });
    });

    xhr.addEventListener('error', fail);
    xhr.send(formData);
});
