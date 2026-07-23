document.addEventListener('trix-attachment-add', (event) => {
    const { attachment } = event;

    if (!attachment.file) {
        return;
    }

    const uploadUrl = event.target.getAttribute('data-upload-url');
    const formData = new FormData();
    formData.append('file', attachment.file);

    window.axios
        .post(uploadUrl, formData, {
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    attachment.setUploadProgress(Math.round((progressEvent.loaded / progressEvent.total) * 100));
                }
            },
        })
        .then((response) => {
            attachment.setAttributes({
                url: response.data.url,
                href: response.data.url,
            });
        })
        .catch(() => {
            event.target.editor.removeAttachment(attachment);
            window.alert('That file could not be uploaded. Please try again.');
        });
});
