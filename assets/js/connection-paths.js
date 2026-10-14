/* Copy the connection path into the clipboard, including on local HTTP installs. */
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy-connection]');
    if (!button) return;
    const field = document.getElementById(button.dataset.copyConnection);
    if (!field) return;
    const feedback = button.parentElement.querySelector('.connection-feedback');
    let copied = false;
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(field.value);
            copied = true;
        } catch (_) { /* Fall back to manual selection below. */ }
    }
    if (!copied) {
        field.focus();
        field.select();
        try { copied = document.execCommand('copy'); } catch (_) { copied = false; }
    }
    if (feedback) {
        feedback.textContent = copied
            ? 'Copied. Paste these commands into the Bitburner terminal.'
            : 'Select the highlighted commands and press Ctrl+C to copy.';
    }
});
