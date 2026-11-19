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
    const submissionErrors = {
        401: 'Your session or account access changed. Sign in again before submitting.',
        403: 'You don’t have permission to submit this form.',
        419: 'Your form session expired. Reload this page before submitting again.',
        429: 'Too many attempts. Wait a moment before trying again.',
    };
    document.addEventListener('submit', async event => {
        const form = event.target;
        if (event.defaultPrevented || form.method !== 'post') return;
        const submitter = event.submitter;
        const action = new URL(submitter?.hasAttribute('formaction') ? submitter.formAction : form.action);
        if (action.origin !== location.origin || !window.showFormErrors) return;
        event.preventDefault();
        if (form.dataset.submitting === 'true') return;

        const data = new FormData(form, submitter);
        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');
        const message = document.createElement('p');
        message.setAttribute('role', 'status');
        message.textContent = t('Saving, please wait…');
        form.append(message);

        let navigating = false;
        let errors = [t('We couldn’t confirm the result. Check whether your changes were saved before trying again.')];
        try {
            const response = await fetch(action, {
                method: 'POST',
                body: data,
                redirect: 'error',
                headers: { Accept: 'application/json', 'X-ProcureTrack-Form': '1' },
            });

            if (response.ok) {
                const result = await response.json();
                if (typeof result.redirect === 'string') {
                    const destination = new URL(result.redirect, action);
                    if (destination.origin === location.origin) {
                        location.assign(destination);
                        navigating = true;
                        return;
                    }
                }
            } else if (response.status === 422) {
                const result = await response.json();
                const details = Object.values(result.errors ?? {}).flat();
                errors = details.length ? details.map(t) : [t('Please check the information you entered.')];
            } else if (submissionErrors[response.status]) {
                errors = [t(submissionErrors[response.status])];
            }
        } catch {
            // A lost response can follow a successful write, so do not resubmit automatically.
        } finally {
            if (!navigating) {
                delete form.dataset.submitting;
                form.removeAttribute('aria-busy');
                message.remove();
                window.showFormErrors(errors, submitter ?? document.activeElement);
            }
        }
    });
    document.querySelectorAll('[data-table-controls]').forEach(controls => {
        const dialog = controls.querySelector('dialog');
        const trigger = controls.querySelector('[data-table-filters-open]');
        trigger.addEventListener('click', () => dialog.showModal());
        dialog.querySelector('[data-table-filters-close]').addEventListener('click', () => dialog.close());

        const search = controls.querySelector('input[type="search"]');
        search.addEventListener('input', event => {
            if (!event.isComposing && search.defaultValue.trim() && search.value === '') {
                search.form.requestSubmit();
            }
        });
    });

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
            counter++; rows.append(row);
            const select = row.querySelector('select');
            (window.SearchableSelect?.enhance(select) ?? select).focus();
        });
        rows.addEventListener('click', event => { const button = event.target.closest('[data-remove-entry]'); if (button) button.closest('.group-entry').remove(); });
    }
})();
