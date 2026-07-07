function initLoginCustomSelects() {
    document.querySelectorAll('.login-page .portal-select-wrap select').forEach((select, index) => {
        if (select.dataset.enhanced === '1') return;

        const wrap = select.parentElement;
        if (!wrap) return;

        select.dataset.enhanced = '1';
        wrap.classList.add('is-enhanced', 'select-enhanced-wrap');

        const fieldLabel = select.dataset.selectLabel || 'Select';
        const custom = document.createElement('div');
        custom.className = 'custom-select login-custom-select';

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'custom-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-label', fieldLabel);
        trigger.setAttribute('aria-controls', `login-custom-select-menu-${index}`);

        const copy = document.createElement('span');
        copy.className = 'custom-select-copy';

        const value = document.createElement('span');
        value.className = 'custom-select-value';

        copy.append(value);

        const caret = document.createElement('span');
        caret.className = 'custom-select-caret';
        caret.setAttribute('aria-hidden', 'true');

        const menu = document.createElement('div');
        menu.className = 'custom-select-menu';
        menu.id = `login-custom-select-menu-${index}`;
        menu.setAttribute('role', 'listbox');
        menu.setAttribute('aria-label', fieldLabel);

        trigger.append(copy, caret);
        custom.append(trigger, menu);
        wrap.appendChild(custom);

        const renderOptions = () => {
            menu.innerHTML = '';
            [...select.options].forEach((option, optionIndex) => {
                if (option.hidden) return;
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'custom-select-option';
                item.setAttribute('role', 'option');
                item.dataset.value = option.value;
                item.dataset.index = String(optionIndex);
                item.disabled = option.disabled;
                item.innerHTML = `
                    <span class="custom-select-option-dot" aria-hidden="true"></span>
                    <span class="custom-select-option-label">${escapeLoginHtml(option.textContent.trim())}</span>
                `;
                item.addEventListener('click', () => {
                    if (option.disabled) return;
                    select.selectedIndex = optionIndex;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    closeLoginCustomSelects();
                    trigger.focus();
                });
                item.addEventListener('keydown', event => handleLoginCustomSelectKeys(event, custom));
                menu.appendChild(item);
            });
        };

        const syncState = () => {
            renderOptions();
            const selectedOption = select.selectedOptions[0] || select.options[0];
            const hasValue = !!(selectedOption?.value || '').trim();
            value.textContent = selectedOption?.textContent?.trim() || fieldLabel;
            custom.classList.toggle('is-placeholder', !hasValue);
            custom.classList.toggle('is-disabled', select.disabled);
            trigger.disabled = select.disabled;
            [...menu.querySelectorAll('.custom-select-option')].forEach(item => {
                const selected = item.dataset.index === String(selectedOption?.index ?? select.selectedIndex);
                item.classList.toggle('is-selected', selected);
                item.setAttribute('aria-selected', String(selected));
                item.tabIndex = selected && !item.disabled ? 0 : -1;
            });
        };

        const setOpen = open => {
            custom.classList.toggle('is-open', open);
            trigger.setAttribute('aria-expanded', String(open));
            wrap.closest('.portal-login-card')?.classList.toggle('has-open-select', open);
        };

        trigger.addEventListener('click', event => {
            event.stopPropagation();
            syncState();
            const opening = !custom.classList.contains('is-open');
            closeLoginCustomSelects(opening ? custom : null);
            setOpen(opening);
            if (opening) focusLoginCustomSelectOption(custom);
        });

        trigger.addEventListener('keydown', event => {
            if (!['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) return;
            event.preventDefault();
            if (!custom.classList.contains('is-open')) {
                closeLoginCustomSelects(custom);
                setOpen(true);
            }
            focusLoginCustomSelectOption(custom, event.key === 'ArrowUp' ? 'last' : 'selected');
        });

        select.addEventListener('change', syncState);
        syncState();
    });
}

function focusLoginCustomSelectOption(custom, mode = 'selected') {
    const items = [...custom.querySelectorAll('.custom-select-option')];
    if (!items.length) return;
    const target = mode === 'last'
        ? items[items.length - 1]
        : items.find(item => item.classList.contains('is-selected')) || items[0];
    target.focus();
}

function handleLoginCustomSelectKeys(event, custom) {
    const items = [...custom.querySelectorAll('.custom-select-option')];
    const index = items.indexOf(event.currentTarget);
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        items[(index + 1) % items.length]?.focus();
    }
    if (event.key === 'ArrowUp') {
        event.preventDefault();
        items[(index - 1 + items.length) % items.length]?.focus();
    }
    if (event.key === 'Escape') {
        event.preventDefault();
        closeLoginCustomSelects();
        custom.querySelector('.custom-select-trigger')?.focus();
    }
}

function closeLoginCustomSelects(except = null) {
    document.querySelectorAll('.login-page .custom-select.is-open').forEach(custom => {
        if (except && custom === except) return;
        custom.classList.remove('is-open');
        custom.querySelector('.custom-select-trigger')?.setAttribute('aria-expanded', 'false');
        custom.closest('.portal-login-card')?.classList.remove('has-open-select');
    });
}

function escapeLoginHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

document.addEventListener('DOMContentLoaded', () => {
    initLoginCustomSelects();
    document.addEventListener('click', event => {
        if (!event.target.closest('.login-page .custom-select')) {
            closeLoginCustomSelects();
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeLoginCustomSelects();
    });
});

window.initLoginCustomSelects = initLoginCustomSelects;
window.closeLoginCustomSelects = closeLoginCustomSelects;
