(() => {
    const messages = JSON.parse(document.querySelector('#ui-messages')?.textContent || '{}');
    const t = text => messages[text] || text;
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
        input.type = showing ? 'text' : 'password'; button.textContent = showing ? t('Hide') : t('Show');
        button.setAttribute('aria-label', showing ? t('Hide password') : t('Show password')); button.setAttribute('aria-pressed', String(showing));
    }));
    document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => { if (!confirm(form.dataset.confirm)) event.preventDefault(); }));
    document.querySelectorAll('form').forEach(form => form.addEventListener('submit', event => {
        if (event.defaultPrevented || form.method.toLowerCase() !== 'post') return;
        if (form.dataset.submitting === 'true') { event.preventDefault(); return; }
        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');
        // Keep the submitter enabled so its name/value is included in the request.
        form.querySelectorAll('button[type="submit"],button:not([type])').forEach(button => button.setAttribute('aria-disabled', 'true'));
        const message = document.createElement('p'); message.setAttribute('role', 'status'); message.textContent = t('Saving, please wait…'); form.append(message);
    }));
    window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });
    const switchDialog = document.querySelector('#switch-user-dialog');
    if (switchDialog) {
        const open = document.querySelector('[data-switch-open]');
        const search = switchDialog.querySelector('[data-switch-search]');
        const users = switchDialog.querySelector('[data-switch-users]');
        const state = switchDialog.querySelector('[data-switch-state]');
        const submit = switchDialog.querySelector('[data-switch-submit]');
        const retry = switchDialog.querySelector('[data-switch-retry]');
        let pending;
        const load = async () => {
            pending?.abort();
            const controller = new AbortController(); pending = controller;
            users.replaceChildren(); submit.disabled = true; retry.hidden = true;
            state.textContent = t('Loading users…'); users.setAttribute('aria-busy', 'true');
            try {
                const url = new URL(switchDialog.dataset.usersUrl, location.origin);
                url.searchParams.set('search', search.elements.search.value);
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
                if (!response.ok) throw new Error('Request failed');
                const data = await response.json();
                for (const user of data.users) {
                    const option = document.createElement('option'); option.value = user.id;
                    option.textContent = `${user.name} · ${user.worker_id}${user.team ? ' · ' + user.team : ''}`;
                    users.append(option);
                }
                users.selectedIndex = -1;
                state.textContent = data.users.length ? t('Select a person to continue.') : t('No active accounts match. Try another search.');
            } catch (error) {
                if (error.name === 'AbortError') return;
                state.textContent = t('Could not load users. Retry or refresh if your session has expired.'); retry.hidden = false;
            } finally { if (pending === controller) users.removeAttribute('aria-busy'); }
        };
        open.addEventListener('click', () => { switchDialog.showModal(); search.elements.search.focus(); load(); });
        switchDialog.querySelectorAll('[data-switch-close]').forEach(button => button.addEventListener('click', () => switchDialog.close()));
        switchDialog.addEventListener('close', () => { pending?.abort(); open.focus(); });
        search.addEventListener('submit', event => { event.preventDefault(); load(); });
        retry.addEventListener('click', load);
        users.addEventListener('change', () => { submit.disabled = !users.value; });
    }
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
