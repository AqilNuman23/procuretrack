(() => {
    const drawer = document.querySelector('.sidebar');
    const toggle = document.querySelector('[data-drawer-open]');
    const backdrop = document.querySelector('.drawer-backdrop');
    const close = () => {
        if (!drawer) return;
        drawer.classList.remove('open'); drawer.removeAttribute('role'); drawer.removeAttribute('aria-modal');
        document.body.classList.remove('drawer-open'); backdrop.hidden = true; toggle.setAttribute('aria-expanded', 'false');
        document.querySelectorAll('.topbar,#main').forEach(el => { el.inert = false; });
    };
    toggle?.addEventListener('click', () => {
        drawer.classList.add('open'); drawer.setAttribute('role', 'dialog'); drawer.setAttribute('aria-modal', 'true');
        document.body.classList.add('drawer-open'); backdrop.hidden = false; toggle.setAttribute('aria-expanded', 'true');
        document.querySelectorAll('.topbar,#main').forEach(el => { el.inert = true; });
        drawer.querySelector('[data-drawer-close]').focus();
    });
    document.querySelectorAll('[data-drawer-close]').forEach(el => el.addEventListener('click', () => { close(); toggle.focus(); }));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            if (drawer?.classList.contains('open')) { close(); toggle.focus(); }
            document.querySelector('.account-menu')?.removeAttribute('open');
        }
        if (event.key === 'Tab' && drawer?.classList.contains('open')) {
            const elements = [...drawer.querySelectorAll('a,button')].filter(el => el.getClientRects().length);
            if (event.shiftKey && document.activeElement === elements[0]) { event.preventDefault(); elements.at(-1).focus(); }
            else if (!event.shiftKey && document.activeElement === elements.at(-1)) { event.preventDefault(); elements[0].focus(); }
        }
    });
    matchMedia('(min-width:1001px)').addEventListener('change', event => { if (event.matches) close(); });
    document.addEventListener('click', event => { const menu = document.querySelector('.account-menu'); if (menu && !menu.contains(event.target)) menu.removeAttribute('open'); });
    document.querySelectorAll('.password-toggle').forEach(button => button.addEventListener('click', () => {
        const input = button.previousElementSibling; const showing = input.type === 'password';
        input.type = showing ? 'text' : 'password'; button.textContent = showing ? 'Hide' : 'Show';
        button.setAttribute('aria-label', showing ? 'Hide password' : 'Show password'); button.setAttribute('aria-pressed', String(showing));
    }));
    document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => { if (!confirm(form.dataset.confirm)) event.preventDefault(); }));
    const rows = document.querySelector('[data-group-rows]');
    if (rows) {
        const template = rows.firstElementChild.cloneNode(true); let counter = 1;
        document.querySelector('[data-add-entry]').addEventListener('click', () => {
            const row = template.cloneNode(true);
            row.querySelectorAll('[name]').forEach(input => { input.name = input.name.replace(/entries\[\d+\]/, `entries[${counter}]`); });
            counter++; rows.append(row); row.querySelector('select').focus();
        });
        rows.addEventListener('click', event => { const button = event.target.closest('[data-remove-entry]'); if (button) button.closest('.group-entry').remove(); });
    }
})();
