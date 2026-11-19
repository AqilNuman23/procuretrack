/* Preview interactions only: no API calls, cookies, persistence, or account changes. */
(() => {
    const toast = document.querySelector('.toast');
    let toastTimer;
    const announce = (message) => {
        toast.textContent = message;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 6500);
    };

    const drawer = document.querySelector('.sidebar');
    const drawerToggle = document.querySelector('[data-drawer-open]');
    const backdrop = document.querySelector('.drawer-backdrop');
    const closeDrawer = () => {
        if (!drawer) return;
        drawer.classList.remove('open');
        drawer.removeAttribute('role');
        drawer.removeAttribute('aria-modal');
        document.body.classList.remove('drawer-open');
        backdrop.hidden = true;
        drawerToggle.setAttribute('aria-expanded', 'false');
        document.querySelectorAll('.topbar, #main').forEach(el => { el.inert = false; });
    };
    drawerToggle?.addEventListener('click', () => {
        drawer.classList.add('open');
        drawer.setAttribute('role', 'dialog');
        drawer.setAttribute('aria-modal', 'true');
        document.body.classList.add('drawer-open');
        backdrop.hidden = false;
        drawerToggle.setAttribute('aria-expanded', 'true');
        document.querySelectorAll('.topbar, #main').forEach(el => { el.inert = true; });
        drawer.querySelector('[data-drawer-close]').focus();
    });
    document.querySelectorAll('[data-drawer-close]').forEach(button => button.addEventListener('click', () => { closeDrawer(); drawerToggle.focus(); }));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            if (drawer?.classList.contains('open')) { closeDrawer(); drawerToggle.focus(); }
            document.querySelector('.account-menu')?.removeAttribute('open');
        }
        if (event.key === 'Tab' && drawer?.classList.contains('open')) {
            const focusable = [...drawer.querySelectorAll('a,button')].filter(el => el.getClientRects().length);
            const first = focusable[0], last = focusable.at(-1);
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    window.matchMedia('(min-width:1001px)').addEventListener('change', event => { if (event.matches) closeDrawer(); });
    document.addEventListener('click', event => {
        const account = document.querySelector('.account-menu');
        if (account && !account.contains(event.target)) account.removeAttribute('open');
    });

    document.querySelectorAll('.password-toggle').forEach(button => button.addEventListener('click', () => {
        const input = button.previousElementSibling;
        const showing = input.type === 'password';
        input.type = showing ? 'text' : 'password';
        button.textContent = showing ? 'Hide' : 'Show';
        button.setAttribute('aria-pressed', String(showing));
        button.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
    }));
    document.querySelectorAll('[data-preview-form]').forEach(form => form.addEventListener('submit', event => {
        event.preventDefault();
        const confirmation = form.querySelector('[data-confirm-password]');
        const error = form.querySelector('.form-error');
        const passwordFields = [...form.querySelectorAll('.password-toggle')].map(button => button.previousElementSibling);
        if (confirmation && confirmation.value !== passwordFields.at(-2)?.value) {
            error.textContent = 'The passwords do not match. Check both fields and try again.';
            error.hidden = false;
            confirmation.setAttribute('aria-invalid', 'true');
            window.showFormErrors([error.textContent], confirmation);
            return;
        }
        error.hidden = true;
        confirmation?.removeAttribute('aria-invalid');
        if (form.dataset.next) window.location.assign(form.dataset.next);
        else announce('Preview complete. Nothing was saved, sent, or changed.');
    }));

    const dialog = document.querySelector('#preview-dialog');
    let actionName;
    document.querySelectorAll('[data-action]').forEach(button => button.addEventListener('click', () => {
        actionName = button.dataset.action;
        dialog.querySelector('#dialog-title').textContent = actionName;
        const reason = dialog.querySelector('[name=reason]');
        reason.value = '';
        reason.required = button.hasAttribute('data-reason');
        dialog.querySelector('#reason-field').hidden = !reason.required;
        dialog.showModal();
    }));
    dialog.querySelector('[data-dialog-close]').addEventListener('click', () => dialog.close());
    dialog.querySelector('form').addEventListener('submit', event => {
        event.preventDefault();
        dialog.close();
        announce(`${actionName}: preview complete. No real action was performed.`);
    });

    document.querySelectorAll('[data-table]').forEach(table => {
        const tbody = table.querySelector('tbody');
        const rows = [...table.querySelectorAll('[data-record]')];
        const search = table.querySelector('[data-search]');
        const filter = table.querySelector('[data-filter]');
        const state = table.querySelector('[data-state]');
        const size = table.querySelector('[data-page-size]');
        const content = table.querySelector('[data-table-content]');
        const message = table.querySelector('[data-table-state]');
        const count = table.querySelector('[data-result-count]');
        const prev = table.querySelector('[data-prev]');
        const next = table.querySelector('[data-next]');
        const filterForm = table.querySelector('[data-preview-filters]');
        const filterDialog = filterForm.closest('dialog');
        let appliedFilter = filter.value;
        let appliedState = state?.value ?? 'success';
        table.querySelector('[data-preview-filters-open]').addEventListener('click', () => {
            filter.value = appliedFilter;
            filter.dispatchEvent(new Event('select-refresh'));
            if (state) {
                state.value = appliedState;
                state.dispatchEvent(new Event('select-refresh'));
            }
            filterDialog.showModal();
        });
        filterDialog.querySelector('[data-preview-filters-close]').addEventListener('click', () => filterDialog.close());
        filterForm.addEventListener('submit', () => {
            appliedFilter = filter.value;
            appliedState = state?.value ?? 'success';
            page = 1;
            render();
        });
        let page = 1, sortColumn = -1, descending = false;
        const render = () => {
            const result = rows.filter(row => row.textContent.toLowerCase().includes(search.value.toLowerCase().trim()) && (!appliedFilter || row.dataset.filterValue === appliedFilter));
            if (sortColumn >= 0) result.sort((a, b) => a.cells[sortColumn].textContent.trim().localeCompare(b.cells[sortColumn].textContent.trim(), undefined, { numeric: true }) * (descending ? -1 : 1));
            const pages = Math.max(1, Math.ceil(result.length / Number(size.value)));
            page = Math.min(page, pages);
            rows.forEach(row => { row.hidden = true; });
            const showing = result.slice((page - 1) * size.value, page * size.value);
            showing.forEach(row => { row.hidden = false; tbody.append(row); });
            const visible = appliedState === 'success' && result.length > 0;
            content.hidden = !visible;
            message.hidden = visible;
            if (!visible) {
                const states = {
                    loading: ['Loading example records…', 'This is a paused loading preview. Switch to populated to continue.'],
                    error: ['We couldn’t load this list.', 'Example failure. Your data has not been replaced by zero counts.'],
                    empty: ['Nothing here yet.', 'Records will appear here once they are available.'],
                    success: ['No matching records.', 'Try another search or clear the filters.'],
                };
                const [title, description] = states[appliedState];
                message.replaceChildren();
                const heading = document.createElement('h3'); heading.textContent = title;
                const copy = document.createElement('p'); copy.textContent = description;
                message.append(heading, copy);
                if (['error', 'success'].includes(appliedState)) {
                    const reset = document.createElement('button'); reset.className = 'button secondary'; reset.type = 'button';
                    reset.textContent = appliedState === 'error' ? 'Try again · preview' : 'Clear filters';
                    reset.addEventListener('click', () => { appliedState = 'success'; search.value = ''; appliedFilter = ''; page = 1; render(); });
                    message.append(reset);
                }
            }
            count.textContent = visible ? `Showing ${(page - 1) * size.value + 1}–${Math.min(page * size.value, result.length)} of ${result.length} records` : (appliedState === 'loading' || appliedState === 'error' ? 'Records unavailable' : '0 records shown');
            table.querySelector('[data-page-number]').textContent = `${page} / ${visible ? pages : 1}`;
            prev.disabled = !visible || page <= 1;
            next.disabled = !visible || page >= pages;
        };
        [search, size].forEach(control => control.addEventListener(control === search ? 'input' : 'change', () => { page = 1; render(); }));
        prev.addEventListener('click', () => { page--; render(); });
        next.addEventListener('click', () => { page++; render(); });
        table.querySelectorAll('[data-sort]').forEach(button => button.addEventListener('click', () => {
            const column = Number(button.dataset.sort);
            descending = sortColumn === column ? !descending : false;
            sortColumn = column;
            table.querySelectorAll('th[aria-sort]').forEach(th => th.setAttribute('aria-sort', 'none'));
            button.closest('th').setAttribute('aria-sort', descending ? 'descending' : 'ascending');
            render();
        }));
        render();
    });
})();
