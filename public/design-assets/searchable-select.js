(() => {
    const controls = new WeakMap();
    const messages = JSON.parse(document.querySelector('#ui-messages')?.textContent ?? '{}');
    const t = text => messages[text] ?? text;
    let nextId = 0;
    let opened;
    let validationTarget;

    const enhance = (select) => {
        if (controls.has(select)) return controls.get(select);
        const id = `searchable-select-${++nextId}`;
        const fieldLabel = select.labels[0];
        const label = select.getAttribute('aria-label')
            || fieldLabel?.querySelector('span')?.textContent.trim().replace(/\s*\*$/, '')
            || fieldLabel?.textContent.trim()
            || select.name;
        const wrapper = document.createElement('div');
        wrapper.className = 'searchable-select';
        wrapper.innerHTML = `
            <button type="button" class="searchable-select-trigger" aria-expanded="false">
                <span></span>
            </button>
            <div class="searchable-select-popup" hidden>
                <input type="search" role="combobox" aria-autocomplete="list" aria-expanded="false"
                    autocomplete="off" placeholder="">
                <div class="searchable-select-options" role="listbox"></div>
                <p class="searchable-select-empty" role="status" hidden></p>
            </div>
            <p class="searchable-select-error" role="alert" hidden></p>`;
        const trigger = wrapper.querySelector('button');
        const caption = trigger.querySelector('span');
        const popup = wrapper.querySelector('.searchable-select-popup');
        const input = popup.querySelector('input');
        const list = popup.querySelector('[role=listbox]');
        const empty = popup.querySelector('[role=status]');
        const error = wrapper.querySelector('[role=alert]');
        error.id = `${id}-error`;
        input.placeholder = t('Search options');
        empty.textContent = t('No matching options.');
        if (select.multiple) list.setAttribute('aria-multiselectable', 'true');
        let matches = [];
        let active = -1;

        popup.id = `${id}-popup`;
        list.id = `${id}-options`;
        trigger.setAttribute('aria-controls', popup.id);
        input.setAttribute('aria-controls', list.id);
        input.setAttribute('aria-label', `${t('Search options')} — ${label}`);
        list.setAttribute('aria-label', label);

        const sync = () => {
            const selected = [...select.selectedOptions].map(option => option.text);
            caption.textContent = selected.join(', ') || t(select.multiple ? 'Choose options' : 'Choose an option');
            trigger.title = caption.textContent;
            trigger.disabled = select.matches(':disabled');
            trigger.setAttribute('aria-invalid', String(!error.hidden || select.getAttribute('aria-invalid') === 'true'));
            const description = [select.getAttribute('aria-describedby'), error.hidden ? '' : error.id].filter(Boolean).join(' ');
            if (description) trigger.setAttribute('aria-describedby', description);
            else trigger.removeAttribute('aria-describedby');
            trigger.setAttribute('aria-label', `${label} — ${caption.textContent}`);
        };
        const highlight = (index) => {
            list.children[active]?.classList.remove('is-active');
            active = index;
            const result = list.children[active];
            if (result) {
                result.classList.add('is-active');
                input.setAttribute('aria-activedescendant', result.id);
                result.scrollIntoView({ block: 'nearest' });
            } else {
                input.removeAttribute('aria-activedescendant');
            }
        };
        const close = (restoreFocus = false) => {
            popup.hidden = true;
            if (opened?.wrapper === wrapper) opened = undefined;
            trigger.setAttribute('aria-expanded', 'false');
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            if (restoreFocus) trigger.focus();
        };
        const choose = (option) => {
            if (select.multiple) {
                option.selected = !option.selected;
                list.children[matches.indexOf(option)].setAttribute('aria-selected', String(option.selected));
                highlight(matches.indexOf(option));
                input.focus();
            } else {
                select.selectedIndex = option.index;
                close(true);
            }
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        };
        const renderOptions = (preferSelected = false) => {
            const query = input.value.trim().toLocaleLowerCase();
            matches = [...select.options].filter(option =>
                !option.disabled && !option.closest('optgroup[disabled]') && option.text.toLocaleLowerCase().includes(query));
            list.replaceChildren(...matches.map(option => {
                const result = document.createElement('div');
                result.id = `${id}-option-${option.index}`;
                result.setAttribute('role', 'option');
                result.setAttribute('aria-selected', String(option.selected));
                result.textContent = option.text;
                result.addEventListener('mousedown', event => event.preventDefault());
                result.addEventListener('click', () => choose(option));
                return result;
            }));
            empty.hidden = matches.length > 0;
            active = -1;
            highlight(matches.length ? Math.max(0, preferSelected ? matches.findIndex(option => option.selected) : 0) : -1);
        };
        const open = () => {
            if (trigger.disabled || !popup.hidden) return;
            opened?.close();
            opened = { wrapper, fieldLabel, close };
            input.value = '';
            popup.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            input.setAttribute('aria-expanded', 'true');
            input.focus();
            renderOptions(true);
        };

        trigger.addEventListener('click', () => popup.hidden ? open() : close(true));
        trigger.addEventListener('keydown', event => {
            if (!['ArrowDown', 'ArrowUp'].includes(event.key)) return;
            event.preventDefault();
            open();
        });
        fieldLabel?.addEventListener('click', event => {
            if (wrapper.contains(event.target) || event.target.closest('a, button, input, select, textarea')) return;
            event.preventDefault();
            open();
        });
        input.addEventListener('input', () => renderOptions());
        input.addEventListener('keydown', event => {
            if (event.isComposing) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                close(true);
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (matches[active]) choose(matches[active]);
            } else if (matches.length && ['ArrowDown', 'ArrowUp'].includes(event.key)) {
                event.preventDefault();
                const step = event.key === 'ArrowDown' ? 1 : -1;
                highlight((active + step + matches.length) % matches.length);
            } else if (event.key === 'Tab' && event.shiftKey) {
                event.preventDefault();
                close(true);
            }
        });
        wrapper.addEventListener('focusout', event => {
            if (event.relatedTarget && !wrapper.contains(event.relatedTarget)) close();
        });
        select.addEventListener('change', () => {
            error.hidden = true;
            sync();
        });
        select.addEventListener('select-refresh', () => {
            error.hidden = true;
            sync();
        });
        select.addEventListener('invalid', event => {
            if (validationTarget || !select.isConnected) {
                event.preventDefault();
                return;
            }
            event.preventDefault();
            validationTarget = trigger;
            error.textContent = select.validationMessage;
            error.hidden = false;
            sync();
            trigger.focus();
            queueMicrotask(() => { validationTarget = undefined; });
        });
        new MutationObserver(() => {
            sync();
            if (trigger.disabled) close();
            else if (!popup.hidden) renderOptions(true);
        }).observe(select, { childList: true, subtree: true, attributes: true, characterData: true });

        select.after(wrapper);
        select.hidden = true;
        controls.set(select, trigger);
        sync();
        return trigger;
    };

    document.addEventListener('click', event => {
        if (opened && !opened.wrapper.contains(event.target) && !opened.fieldLabel?.contains(event.target)) {
            opened.close();
        }
    });
    document.addEventListener('reset', event => {
        setTimeout(() => {
            event.target.querySelectorAll('select').forEach(select => select.dispatchEvent(new Event('select-refresh')));
        });
    });

    window.SearchableSelect = { enhance };
    document.querySelectorAll('select').forEach(enhance);
    new MutationObserver(records => {
        for (const record of records) {
            for (const node of record.addedNodes) {
                if (node.nodeType !== Node.ELEMENT_NODE) continue;
                if (node.matches('select')) enhance(node);
                else node.querySelectorAll('select').forEach(enhance);
            }
        }
    }).observe(document.body, { childList: true, subtree: true });
})();
