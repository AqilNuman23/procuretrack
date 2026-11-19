(() => {
    const dialog = document.querySelector('[data-form-error-dialog]');
    if (!dialog) return;

    const list = dialog.querySelector('[data-form-error-messages]');
    let returnFocus;
    dialog.addEventListener('close', () => returnFocus?.focus());
    window.showFormErrors = (messages, focusTarget) => {
        returnFocus = focusTarget;
        list.replaceChildren(...messages.map(message => {
            const item = document.createElement('li');
            item.textContent = message;
            return item;
        }));
        if (!dialog.open) dialog.showModal();
    };
})();
