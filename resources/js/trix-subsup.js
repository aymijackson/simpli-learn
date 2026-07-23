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
