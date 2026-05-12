document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initToasts();
    initFloatingLabels();
    initCustomFilterSelects();
    initCustomDatePickers();
    initPhoneInputs();
    initCharacterCounters();
    initForms();
    initCounters();
    initWizards();
    initEnrollmentAutomation();
    initViewToggles();
    initTimelineDetails();
    initEmailLogViews();
    initRequirementReviewModals();
    initNotifications();
    initCoordinatorCardAlignment();
    document.querySelectorAll('.data-table').forEach(table => enhanceTable(table));
    document.querySelector('#modal .modal-close')?.addEventListener('click', closeSlidePanel);
    document.addEventListener('click', handleOutsideMenus);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeSlidePanel(); closeNotifications(); closeRequirementReviewModals(); closeCustomSelects(); closeCustomDatePickers(); } });
    initStudentModal();
    renderDashboardCharts();
});

function initCoordinatorCardAlignment() {
    const layout = document.querySelector('.coordinators-layout');
    const createCard = document.querySelector('.coordinator-create-card');
    const listCard = document.querySelector('.coordinator-list-card');

    if (!layout || !createCard || !listCard) return;

    const syncHeights = () => {
        listCard.style.height = '';
        listCard.style.minHeight = '';

        const isStackedLayout = window.innerWidth <= 1180;
        if (isStackedLayout) return;

        const createCardHeight = Math.ceil(createCard.getBoundingClientRect().height);
        if (!createCardHeight) return;

        listCard.style.height = `${createCardHeight}px`;
        listCard.style.minHeight = `${createCardHeight}px`;
    };

    syncHeights();
    window.addEventListener('resize', syncHeights);
    window.addEventListener('load', syncHeights);

    if (window.ResizeObserver) {
        const observer = new ResizeObserver(() => syncHeights());
        observer.observe(createCard);
        observer.observe(layout);
    }
}

function initCustomDatePickers() {
    document.querySelectorAll('.filter-date-picker').forEach(picker => {
        if (picker.dataset.enhanced === '1') return;

        picker.dataset.enhanced = '1';

        const input = picker.querySelector('input[type="hidden"]');
        const trigger = picker.querySelector('.filter-date-trigger');
        const value = picker.querySelector('.filter-date-value');
        const panel = picker.querySelector('.filter-date-panel');
        if (!input || !trigger || !value || !panel) return;

        const initialDate = parseCustomDateValue(input.value);
        const state = {
            selected: initialDate,
            view: initialDate ? new Date(initialDate.getFullYear(), initialDate.getMonth(), 1) : new Date(new Date().getFullYear(), new Date().getMonth(), 1),
        };

        const sync = () => {
            value.textContent = state.selected ? formatCustomDateDisplay(state.selected) : 'mm/dd/yyyy';
            picker.classList.toggle('is-placeholder', !state.selected);
        };

        const render = () => {
            panel.innerHTML = buildCustomDatePanel(state);
        };

        trigger.addEventListener('click', event => {
            event.stopPropagation();
            const opening = !picker.classList.contains('is-open');
            closeCustomSelects();
            closeCustomDatePickers(opening ? picker : null);
            picker.classList.toggle('is-open', opening);
            panel.hidden = !opening;
            trigger.setAttribute('aria-expanded', String(opening));
            if (opening) render();
        });

        panel.addEventListener('click', event => {
            const nav = event.target.closest('[data-date-nav]');
            if (nav) {
                const delta = Number(nav.dataset.dateNav || 0);
                state.view = new Date(state.view.getFullYear(), state.view.getMonth() + delta, 1);
                render();
                return;
            }

            const action = event.target.closest('[data-date-action]');
            if (action) {
                if (action.dataset.dateAction === 'clear') {
                    state.selected = null;
                    input.value = '';
                    sync();
                    closeCustomDatePickers();
                }

                if (action.dataset.dateAction === 'today') {
                    const today = stripTime(new Date());
                    state.selected = today;
                    state.view = new Date(today.getFullYear(), today.getMonth(), 1);
                    input.value = formatCustomDateValue(today);
                    sync();
                    closeCustomDatePickers();
                }

                return;
            }

            const day = event.target.closest('[data-date-value]');
            if (!day) return;

            const selectedDate = parseCustomDateValue(day.dataset.dateValue || '');
            if (!selectedDate) return;

            state.selected = selectedDate;
            state.view = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
            input.value = formatCustomDateValue(selectedDate);
            sync();
            closeCustomDatePickers();
        });

        sync();
        render();
    });
}

function buildCustomDatePanel(state) {
    const today = stripTime(new Date());
    const monthLabel = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(state.view);
    const weekDays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
    const firstDay = new Date(state.view.getFullYear(), state.view.getMonth(), 1);
    const start = new Date(firstDay);
    start.setDate(firstDay.getDate() - firstDay.getDay());

    const cells = [];
    for (let index = 0; index < 42; index += 1) {
        const date = new Date(start);
        date.setDate(start.getDate() + index);

        const classes = ['filter-date-day'];
        if (date.getMonth() !== state.view.getMonth()) classes.push('is-outside');
        if (isSameCustomDate(date, today)) classes.push('is-today');
        if (state.selected && isSameCustomDate(date, state.selected)) classes.push('is-selected');

        cells.push(`
            <button
                class="${classes.join(' ')}"
                type="button"
                data-date-value="${formatCustomDateValue(date)}"
                aria-pressed="${state.selected && isSameCustomDate(date, state.selected) ? 'true' : 'false'}"
            >${date.getDate()}</button>
        `);
    }

    return `
        <div class="filter-date-calendar" role="dialog" aria-label="Calendar picker">
            <div class="filter-date-calendar-header">
                <button class="filter-date-nav" type="button" data-date-nav="-1" aria-label="Previous month"></button>
                <div class="filter-date-title">${monthLabel}</div>
                <button class="filter-date-nav" type="button" data-date-nav="1" aria-label="Next month"></button>
            </div>
            <div class="filter-date-weekdays">${weekDays.map(day => `<span>${day}</span>`).join('')}</div>
            <div class="filter-date-grid">${cells.join('')}</div>
            <div class="filter-date-actions">
                <button class="filter-date-action" type="button" data-date-action="clear">Clear</button>
                <button class="filter-date-action" type="button" data-date-action="today">Today</button>
            </div>
        </div>
    `;
}

function parseCustomDateValue(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(String(value || ''))) return null;
    const [year, month, day] = value.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return null;
    return date;
}

function formatCustomDateValue(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function formatCustomDateDisplay(date) {
    return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}/${date.getFullYear()}`;
}

function stripTime(date) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function isSameCustomDate(first, second) {
    return first.getFullYear() === second.getFullYear()
        && first.getMonth() === second.getMonth()
        && first.getDate() === second.getDate();
}

function initCustomFilterSelects() {
    document.querySelectorAll('.email-filter-bare .filter-select-wrap select').forEach((select, index) => {
        const wrap = select.closest('.filter-select-wrap');
        if (!wrap || wrap.dataset.enhanced === '1') return;

        wrap.dataset.enhanced = '1';
        wrap.classList.add('is-enhanced');

        const fieldLabel = wrap.closest('.filter-control')?.querySelector('.filter-label')?.textContent?.trim() || 'Select';
        const custom = document.createElement('div');
        custom.className = 'custom-select';

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'custom-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-label', fieldLabel);
        trigger.setAttribute('aria-controls', `custom-select-menu-${index}`);

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
        menu.id = `custom-select-menu-${index}`;
        menu.setAttribute('role', 'listbox');
        menu.setAttribute('aria-label', fieldLabel);

        [...select.options].forEach((option, optionIndex) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'custom-select-option';
            item.setAttribute('role', 'option');
            item.dataset.value = option.value;
            item.dataset.index = String(optionIndex);
            item.innerHTML = `
                <span class="custom-select-option-dot" aria-hidden="true"></span>
                <span class="custom-select-option-label">${escapeHtml(option.textContent.trim())}</span>
            `;
            item.addEventListener('click', () => {
                select.selectedIndex = optionIndex;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                closeCustomSelects();
                trigger.focus();
            });
            item.addEventListener('keydown', event => handleCustomSelectOptionKeys(event, custom));
            menu.appendChild(item);
        });

        trigger.append(copy, caret);
        custom.append(trigger, menu);
        wrap.appendChild(custom);

        const syncState = () => {
            const selectedOption = select.selectedOptions[0] || select.options[0];
            const hasValue = !!(selectedOption?.value || '').trim();
            value.textContent = selectedOption?.textContent?.trim() || fieldLabel;
            custom.classList.toggle('is-placeholder', !hasValue);
            [...menu.querySelectorAll('.custom-select-option')].forEach(item => {
                const selected = item.dataset.value === (selectedOption?.value || '');
                item.classList.toggle('is-selected', selected);
                item.setAttribute('aria-selected', String(selected));
                item.tabIndex = selected ? 0 : -1;
            });
        };

        const setOpen = open => {
            custom.classList.toggle('is-open', open);
            trigger.setAttribute('aria-expanded', String(open));
        };

        trigger.addEventListener('click', event => {
            event.stopPropagation();
            const opening = !custom.classList.contains('is-open');
            closeCustomSelects(opening ? custom : null);
            setOpen(opening);
            if (opening) focusCustomSelectOption(custom);
        });

        trigger.addEventListener('keydown', event => {
            if (!['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) return;
            event.preventDefault();
            if (!custom.classList.contains('is-open')) {
                closeCustomSelects(custom);
                setOpen(true);
            }
            focusCustomSelectOption(custom, event.key === 'ArrowUp' ? 'last' : 'selected');
        });

        select.addEventListener('change', syncState);
        syncState();
    });
}

function focusCustomSelectOption(custom, mode = 'selected') {
    const items = [...custom.querySelectorAll('.custom-select-option')];
    if (!items.length) return;
    const target = mode === 'last'
        ? items[items.length - 1]
        : items.find(item => item.classList.contains('is-selected')) || items[0];
    target.focus();
}

function handleCustomSelectOptionKeys(event, custom) {
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
        closeCustomSelects();
        custom.querySelector('.custom-select-trigger')?.focus();
    }
}

function closeCustomSelects(except = null) {
    document.querySelectorAll('.custom-select.is-open').forEach(custom => {
        if (except && custom === except) return;
        custom.classList.remove('is-open');
        custom.querySelector('.custom-select-trigger')?.setAttribute('aria-expanded', 'false');
    });
}

function closeCustomDatePickers(except = null) {
    document.querySelectorAll('.filter-date-picker.is-open').forEach(picker => {
        if (except && picker === except) return;
        picker.classList.remove('is-open');
        picker.querySelector('.filter-date-panel')?.setAttribute('hidden', 'hidden');
        picker.querySelector('.filter-date-trigger')?.setAttribute('aria-expanded', 'false');
    });
}

function initNotifications() {
    const menu  = document.getElementById('notifMenu');
    const btn   = document.getElementById('notifBtn');
    const panel = document.getElementById('notifPanel');
    if (!menu || !btn || !panel) return;

    function positionPanel() {
        const rect = btn.getBoundingClientRect();
        const panelWidth = Math.min(370, window.innerWidth - 32);
        const left = Math.max(16, Math.min(rect.right - panelWidth, window.innerWidth - panelWidth - 16));
        panel.style.top = (rect.bottom + 12) + 'px';
        panel.style.left = left + 'px';
        panel.style.width = panelWidth + 'px';
        panel.style.setProperty('--caret-left', Math.round(rect.left + (rect.width / 2) - left - 7) + 'px');
    }

    btn.addEventListener('click', e => {
        e.stopPropagation();
        const opening = !panel.classList.contains('is-open');
        if (opening) {
            panel.hidden = false;
            positionPanel();
        }
        panel.classList.toggle('is-open', opening);
        menu.classList.toggle('is-open', opening);   // for button active styling
        btn.setAttribute('aria-expanded', String(opening));
        if (!opening) panel.hidden = true;
    });

    window.addEventListener('resize', () => {
        if (panel.classList.contains('is-open')) positionPanel();
    });
}

function closeNotifications() {
    const panel = document.getElementById('notifPanel');
    const menu  = document.getElementById('notifMenu');
    const btn   = document.getElementById('notifBtn');
    if (!panel) return;
    panel.classList.remove('is-open');
    panel.hidden = true;
    menu?.classList.remove('is-open');
    btn?.setAttribute('aria-expanded', 'false');
}

function initSidebar() {
    if (localStorage.getItem('sidebarCollapsed') === '1') document.body.classList.add('sidebar-collapsed');
    document.querySelector('.sidebar-toggle')?.addEventListener('click', () => {
        document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
    });
    // Collapsible nav groups
    document.querySelectorAll('.nav-group-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.nav-group').classList.toggle('open');
        });
    });
}

function initToasts() {
    document.querySelectorAll('.toast').forEach((toast, i) => {
        setTimeout(() => toast.classList.add('show'), 80 + i * 120);
        setTimeout(() => toast.classList.remove('show'), 4200 + i * 150);
    });
}

function initFloatingLabels() {
    document.querySelectorAll('.form label, .filter-bar label, .partner-form-fields label, .partner-form-section > label').forEach(label => {
        if (label.classList.contains('no-floating-label') || label.querySelector('.label-text')) return;
        const text = [...label.childNodes].find(n => n.nodeType === Node.TEXT_NODE && n.textContent.trim());
        const field = label.querySelector('input,select,textarea');
        if (!text || !field || field.type === 'hidden' || field.type === 'file') return;
        const span = document.createElement('span');
        span.className = 'label-text';
        span.textContent = text.textContent.trim();
        text.textContent = '';
        label.insertBefore(span, field);
        label.classList.add('floating-label');
        if (field.tagName === 'TEXTAREA') label.classList.add('floating-textarea');
        const sync = () => label.classList.toggle('has-value', !!field.value);
        field.addEventListener('input', sync);
        field.addEventListener('change', sync);
        sync();
    });
}

function formatPhilippineMobile(value) {
    let digits = String(value || '').replace(/\D/g, '');
    if (digits.startsWith('63')) digits = digits.slice(2);
    if (digits.startsWith('0')) digits = digits.slice(1);
    digits = digits.slice(0, 10);
    if (!digits) return '';
    const parts = [digits.slice(0, 3), digits.slice(3, 6), digits.slice(6, 10)].filter(Boolean);
    return `+63 ${parts.join(' ')}`.trim();
}

function initPhoneInputs() {
    document.querySelectorAll('input[data-phone-format="ph"]').forEach(input => {
        const sync = () => {
            input.value = formatPhilippineMobile(input.value);
            input.closest('label')?.classList.toggle('has-value', !!input.value);
        };
        input.addEventListener('input', sync);
        input.addEventListener('paste', () => requestAnimationFrame(sync));
        input.addEventListener('blur', sync);
        sync();
    });
}

function initCharacterCounters() {
    document.querySelectorAll('textarea').forEach(textarea => {
        if (!textarea.maxLength || textarea.maxLength < 0) textarea.maxLength = 500;
        const counter = document.createElement('small');
        counter.className = 'char-counter';
        textarea.insertAdjacentElement('afterend', counter);
        const update = () => {
            const remaining = textarea.maxLength - textarea.value.length;
            counter.textContent = `${remaining} characters remaining`;
            counter.classList.toggle('warning', remaining <= Math.min(50, textarea.maxLength * 0.1));
        };
        textarea.addEventListener('input', update);
        update();
    });
}

function initForms() {
    const getAssociatedControls = form => {
        const selector = form.id
            ? `input[form="${form.id}"], select[form="${form.id}"], textarea[form="${form.id}"]`
            : '';
        const localControls = [...form.querySelectorAll('input,select,textarea')];
        const externalControls = selector ? [...document.querySelectorAll(selector)] : [];

        return [...new Set([...localControls, ...externalControls])];
    };

    const getAssociatedSubmitButtons = form => {
        const selector = form.id
            ? `button[type="submit"][form="${form.id}"], input[type="submit"][form="${form.id}"]`
            : '';
        const localButtons = [...form.querySelectorAll('button[type="submit"],input[type="submit"]')];
        const externalButtons = selector ? [...document.querySelectorAll(selector)] : [];

        return [...new Set([...localButtons, ...externalButtons])];
    };

    const validateRequiredCheckboxGroup = form => {
        const groupName = form.dataset.requireCheckboxGroup;
        if (!groupName) return true;

        const selector = form.id
            ? `input[type="checkbox"][name="${groupName}"][form="${form.id}"]`
            : `input[type="checkbox"][name="${groupName}"]`;
        const checkboxes = [...document.querySelectorAll(selector)].filter(checkbox => !checkbox.disabled);
        if (!checkboxes.length) return true;

        const hasSelection = checkboxes.some(checkbox => checkbox.checked);
        const message = hasSelection ? '' : (form.dataset.requireCheckboxMessage || 'Please select at least one option.');

        checkboxes.forEach(checkbox => checkbox.setCustomValidity(''));
        checkboxes[0].setCustomValidity(message);

        return hasSelection;
    };

    document.querySelectorAll('.js-validate').forEach(form => {
        const controls = getAssociatedControls(form);
        const submitButtons = getAssociatedSubmitButtons(form);
        const markTouched = () => controls.forEach(el => el.classList.add('touched'));

        controls.forEach(el => {
            const eventName = ['checkbox', 'radio'].includes(el.type) ? 'change' : 'blur';
            el.addEventListener(eventName, () => {
                el.classList.add('touched');
                validateRequiredCheckboxGroup(form);
            });
        });

        form.addEventListener('submit', e => {
            const hasValidCheckboxGroup = validateRequiredCheckboxGroup(form);
            if (!hasValidCheckboxGroup || !form.checkValidity()) {
                e.preventDefault();
                markTouched();
                form.reportValidity();
                return;
            }

            const btn = e.submitter || submitButtons[0] || null;
            if (btn) { btn.classList.add('loading'); btn.disabled = true; }
        });
    });
}

function initCounters() {
    document.querySelectorAll('.metric strong').forEach(el => {
        const raw = el.textContent.replace(/,/g, '').trim();
        if (!/^\d+(\.\d+)?$/.test(raw)) return;
        const target = Number(raw);
        const duration = 900;
        const start = performance.now();
        const decimals = raw.includes('.') ? 2 : 0;
        const tick = now => {
            const pct = Math.min(1, (now - start) / duration);
            const value = target * (1 - Math.pow(1 - pct, 3));
            el.textContent = value.toLocaleString(undefined, { maximumFractionDigits: decimals, minimumFractionDigits: decimals });
            if (pct < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    });
}

function initWizards() {
    document.querySelectorAll('[data-wizard]').forEach(form => {
        let index = 0;
        const panels = [...form.querySelectorAll('.wizard-step')];
        const steps = [...form.querySelectorAll('.wizard-steps span')];
        const show = next => {
            index = Math.max(0, Math.min(next, panels.length - 1));
            panels.forEach((p, i) => p.classList.toggle('active', i === index));
            steps.forEach((s, i) => s.classList.toggle('active', i <= index));
            updateWizardSummary(form);
        };
        form.querySelectorAll('.wizard-next').forEach(btn => btn.addEventListener('click', () => {
            const fields = [...panels[index].querySelectorAll('input,select,textarea')];
            if (fields.some(field => !field.checkValidity())) { fields.forEach(field => field.classList.add('touched')); return; }
            show(index + 1);
        }));
        form.querySelectorAll('.wizard-prev').forEach(btn => btn.addEventListener('click', () => show(index - 1)));
        show(0);
    });
}

function updateWizardSummary(form) {
    const box = form.querySelector('.confirm-box');
    if (!box) return;
    const student = form.querySelector('[name="student_id"]')?.selectedOptions[0]?.textContent || '-';
    const company = form.querySelector('[name="company_id"]')?.selectedOptions[0]?.textContent || '-';
    const start = form.querySelector('[name="start_date"]')?.value || '-';
    const end = form.querySelector('[name="end_date"]')?.value || '-';
    const hours = form.querySelector('[name="required_hours"]')?.value || '-';
    box.innerHTML = `<h3>Confirm Enrollment</h3><p><strong>Student:</strong> ${escapeHtml(student)}</p><p><strong>Company:</strong> ${escapeHtml(company)}</p><p><strong>Schedule:</strong> ${escapeHtml(start)} to ${escapeHtml(end)}</p><p><strong>Required Hours:</strong> ${escapeHtml(hours)}</p><p class="muted">Submitting will send the student enrollment and company deployment emails.</p>`;
}

function initEnrollmentAutomation() {
    document.querySelectorAll('form [name="student_id"]').forEach(studentSelect => {
        const form = studentSelect.closest('form');
        const companySelect = form?.querySelector('[name="company_id"]');
        const hoursInput = form?.querySelector('[name="required_hours"]');
        if (!form || !companySelect || !hoursInput) return;
        const sync = () => {
            const selected = studentSelect.selectedOptions[0];
            const programId = selected?.dataset.programId || '';
            const requiredHours = selected?.dataset.requiredHours || '';
            hoursInput.value = requiredHours;
            [...companySelect.options].forEach(option => {
                if (!option.value) return;
                const accepted = (option.dataset.programIds || '').split(',').filter(Boolean);
                const visible = !programId || accepted.includes(programId);
                option.hidden = !visible;
                option.disabled = !visible;
            });
            if (companySelect.selectedOptions[0]?.disabled) companySelect.value = '';
            updateWizardSummary(form);
        };
        studentSelect.addEventListener('change', sync);
        companySelect.addEventListener('change', () => updateWizardSummary(form));
        sync();
    });
}

function initViewToggles() {
    document.querySelectorAll('.view-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const wrapper = document.getElementById(btn.dataset.target);
            wrapper?.classList.toggle('cards-mode');
            btn.textContent = wrapper?.classList.contains('cards-mode') ? 'Table View' : 'Card View';
        });
    });
    document.querySelectorAll('.student-list-card .table-search').forEach(search => {
        search.addEventListener('input', () => {
            const q = search.value.toLowerCase();
            document.querySelectorAll('.student-card').forEach(card => card.style.display = card.dataset.search.includes(q) ? '' : 'none');
        });
    });
    document.querySelectorAll('.student-card').forEach(card => {
        card.addEventListener('click', () => {
            const title = card.querySelector('h3')?.textContent || 'Student';
            const body = card.querySelector('p')?.textContent || '';
            const status = card.querySelector('.badge')?.textContent || '';
            const company = card.querySelector('small')?.textContent || '';
            openSlidePanel(`<h2>${escapeHtml(title)}</h2><div class="detail-row"><span>Student</span><strong>${escapeHtml(body)}</strong></div><div class="detail-row"><span>Status</span><strong>${escapeHtml(status)}</strong></div><div class="detail-row"><span>Company</span><strong>${escapeHtml(company)}</strong></div>`);
        });
    });
}

function enhanceTable(table) {
    const card = table.closest('.card');
    const search = card?.querySelector('.table-search');
    const tbody = table.tBodies[0];
    if (!tbody) return;
    addTableTools(table);
    let rows = [...tbody.rows];
    let page = 1;
    const perPage = 10;
    const filtered = () => {
        const q = (search?.value || '').toLowerCase();
        return rows.filter(r => r.innerText.toLowerCase().includes(q));
    };
    const render = () => {
        const list = filtered();
        tbody.innerHTML = '';
        list.slice((page - 1) * perPage, page * perPage).forEach(r => tbody.appendChild(r));
        const pager = card?.querySelector('.pagination');
        if (pager) {
            pager.innerHTML = '';
            const pages = Math.max(1, Math.ceil(list.length / perPage));
            for (let i = 1; i <= pages; i++) {
                const b = document.createElement('button');
                b.textContent = i;
                b.className = i === page ? 'active' : '';
                b.type = 'button';
                b.onclick = () => { page = i; render(); };
                pager.appendChild(b);
            }
        }
        attachRowDetails(table);
        applyHiddenColumns(table);
    };
    search?.addEventListener('input', () => { page = 1; render(); });
    table.querySelectorAll('th[data-sort]').forEach((th, i) => {
        let asc = true;
        th.addEventListener('click', () => {
            rows.sort((a, b) => asc ? a.cells[i].innerText.localeCompare(b.cells[i].innerText) : b.cells[i].innerText.localeCompare(a.cells[i].innerText));
            asc = !asc;
            render();
        });
    });
    table._getFilteredRows = filtered;
    render();
}

function addTableTools(table) {
    const wrap = table.closest('.table-wrap');
    if (!wrap || wrap.previousElementSibling?.classList.contains('table-tools')) return;
    const tools = document.createElement('div');
    tools.className = 'table-tools';
    tools.innerHTML = '<button class="btn btn-small export-csv" type="button">Export CSV</button><div class="column-menu"><button class="btn btn-small column-toggle" type="button">Columns</button><div class="column-options"></div></div>';
    wrap.insertAdjacentElement('beforebegin', tools);
    tools.querySelector('.export-csv').addEventListener('click', () => exportCsv(table));
    const options = tools.querySelector('.column-options');
    [...table.tHead.rows[0].cells].forEach((th, i) => {
        const label = document.createElement('label');
        label.innerHTML = `<input type="checkbox" checked data-col="${i}"> ${escapeHtml(th.innerText || 'Column ' + (i + 1))}`;
        options.appendChild(label);
    label.querySelector('input').addEventListener('change', e => setColumnVisible(table, i, e.target.checked));
    });
    tools.querySelector('.column-toggle').addEventListener('click', () => options.classList.toggle('open'));
}

function handleOutsideMenus(event) {
    document.querySelectorAll('.column-options.open').forEach(menu => {
        if (!menu.parentElement.contains(event.target)) menu.classList.remove('open');
    });
    if (!event.target.closest('.custom-select')) closeCustomSelects();
    if (!event.target.closest('.filter-date-picker')) closeCustomDatePickers();
    const panel = document.querySelector('.notif-panel');
    const btn   = document.getElementById('notifBtn');
    if (panel && panel.classList.contains('is-open') && !panel.contains(event.target) && event.target !== btn && !btn?.contains(event.target)) {
        closeNotifications();
    }
}

function setColumnVisible(table, index, visible) {
    table._hiddenCols = table._hiddenCols || new Set();
    if (visible) table._hiddenCols.delete(index); else table._hiddenCols.add(index);
    applyHiddenColumns(table);
}

function applyHiddenColumns(table) {
    const hidden = table._hiddenCols || new Set();
    [...table.rows].forEach(row => [...row.cells].forEach((cell, i) => { cell.style.display = hidden.has(i) ? 'none' : ''; }));
}

function exportCsv(table) {
    const rows = [table.tHead.rows[0], ...table.tBodies[0].rows];
    const csv = rows.map(row => [...row.cells].filter(cell => cell.style.display !== 'none').map(cell => `"${cell.innerText.replace(/"/g, '""').trim()}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'table-export.csv';
    a.click();
    URL.revokeObjectURL(a.href);
}

function attachRowDetails(table) {
    if (table.classList.contains('no-row-details')) return;
    [...table.tBodies[0].rows].forEach(row => {
        if (row.dataset.detailReady) return;
        row.dataset.detailReady = '1';
        row.addEventListener('click', e => {
            if (e.target.closest('a,button,form,input,select,textarea')) return;
            const headers = [...table.tHead.rows[0].cells].map(th => th.innerText.trim());
            let extraFields = [];
            if (row.dataset.idNumber) {
                extraFields.push({
                    label: 'ID Number',
                    value: row.dataset.idNumber,
                });
            }
            if (row.dataset.detailFields) {
                try {
                    const parsed = JSON.parse(row.dataset.detailFields);
                    if (Array.isArray(parsed)) extraFields = parsed;
                } catch {
                    extraFields = [];
                }
            }
            const extraHtml = extraFields.map(field => `<div class="detail-row"><span>${escapeHtml(field?.label || 'Field')}</span><strong>${escapeHtml(field?.value || '-')}</strong></div>`).join('');
            const html = extraHtml + [...row.cells].map((cell, i) => `<div class="detail-row"><span>${escapeHtml(headers[i] || 'Field')}</span><strong>${escapeHtml(cell.innerText.trim())}</strong></div>`).join('');
            openSlidePanel('<h2>Record Details</h2>' + html);
        });
    });
}

function initTimelineDetails() {
    document.querySelectorAll('.timeline-item').forEach(item => {
        item.addEventListener('click', () => {
            const parts = (item.dataset.detail || '').split('|');
            openSlidePanel(`<h2>Activity Details</h2>${parts.map((p, i) => `<div class="detail-row"><span>${['Date','Time','Hours','Tasks'][i] || 'Info'}</span><strong>${escapeHtml(p)}</strong></div>`).join('')}`);
        });
    });
}

function openSlidePanel(html) {
    const modal = document.getElementById('modal');
    const body  = document.getElementById('modal-body');
    if (!modal || !body) return;
    body.innerHTML = html;
    modal.classList.add('open');
    modal.addEventListener('click', e => { if (e.target === modal) closeSlidePanel(); }, { once: true });
}
function closeSlidePanel() {
    document.getElementById('modal')?.classList.remove('open');
    document.getElementById('studentModal')?.classList.remove('open');
}
function escapeHtml(value) {
    return String(value).replace(/[&<>'"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[c]));
}

function formatLabel(value) {
    return String(value || '')
        .replaceAll('_', ' ')
        .replace(/\b\w/g, char => char.toUpperCase());
}

function initEmailLogViews() {
    document.querySelectorAll('.email-log-view').forEach(button => {
        button.addEventListener('click', () => {
            const data = button.dataset;
            openSlidePanel(`
                <h2>Email Log Details</h2>
                <div class="detail-row"><span>Sent At</span><strong>${escapeHtml(data.sentAt || '')}</strong></div>
                <div class="detail-row"><span>Recipient</span><strong>${escapeHtml(data.recipient || '')}</strong></div>
                <div class="detail-row"><span>Subject</span><strong>${escapeHtml(data.subject || '')}</strong></div>
                <div class="detail-row"><span>Type</span><strong>${escapeHtml(formatLabel(data.type || ''))}</strong></div>
                <div class="detail-row"><span>Status</span><strong>${escapeHtml(formatLabel(data.status || ''))}</strong></div>
                <div class="detail-row"><span>Error Message</span><strong>${escapeHtml(data.error || 'No error message')}</strong></div>
            `);
        });
    });
}

function renderDashboardCharts() {
    if (!window.dashboardCharts) return;

    function drawAll() {
        drawBars('monthlyChart', window.dashboardCharts.monthlyTrends || [], '', false, true);
        drawPie('statusChart',   window.dashboardCharts.statusDistribution || []);
        drawBars('courseChart',  window.dashboardCharts.completionRates || [], '%', true);
    }

    drawAll();

    // Redraw on window resize (covers zoom changes too)
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(drawAll, 80);
    });

    // ResizeObserver for container size changes (more reliable)
    if (window.ResizeObserver) {
        const ro = new ResizeObserver(() => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(drawAll, 80);
        });
        ['monthlyChart', 'statusChart', 'courseChart'].forEach(id => {
            const el = document.getElementById(id);
            if (el && el.parentElement) ro.observe(el.parentElement);
        });
    }
}
// ─── Chart helpers ────────────────────────────────────────────────────────────
const CHART_COLORS = ['#8B1A1A', '#c0392b', '#16a34a', '#f59e0b', '#dc2626', '#8b5cf6', '#0891b2', '#64748b'];
const CHART_BAR_COLOR = '#8B1A1A';

function prepCanvas(id) {
    const c = document.getElementById(id);
    if (!c) return null;
    const dpr = window.devicePixelRatio || 1;
    // Clear fixed size so browser can recalculate responsive dimensions
    c.style.width  = '100%';
    c.style.height = '';
    c.removeAttribute('width');
    c.removeAttribute('height');
    const cssW = c.offsetWidth || c.parentElement.clientWidth;
    const cssH = 320;
    c.width  = cssW * dpr;
    c.height = cssH * dpr;
    c.style.width  = cssW + 'px';
    c.style.height = cssH + 'px';
    const ctx = c.getContext('2d');
    ctx.scale(dpr, dpr);
    ctx.clearRect(0, 0, cssW, cssH);
    return { c, ctx, w: cssW, h: cssH };
}

function chartFont(ctx, size = 12, weight = '500') {
    ctx.font = `${weight} ${size}px "Plus Jakarta Sans", system-ui, sans-serif`;
}

function drawEmpty(ctx, w, h) {
    chartFont(ctx, 13);
    ctx.fillStyle = '#94a3b8';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('No data available', w / 2, h / 2);
}

function niceMax(val) {
    if (val <= 0) return 10;
    const exp = Math.pow(10, Math.floor(Math.log10(val)));
    return Math.ceil(val / exp) * exp;
}

function roundRect(ctx, x, y, w, h, r) {
    if (h <= 0) return;
    r = Math.min(r, h / 2, w / 2);
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + r);
    ctx.lineTo(x + w, y + h);
    ctx.lineTo(x, y + h);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
}

// ─── Donut / Pie ─────────────────────────────────────────────────────────────
function drawPie(id, data) {
    const p = prepCanvas(id);
    if (!p) return;
    const { ctx, w, h } = p;
    const total = data.reduce((s, d) => s + Number(d.value || 0), 0);
    if (!total) { drawEmpty(ctx, w, h); return; }

    // layout: calculate exact space for donut, center it above legend
    const legendRowH  = 44;
    const legendRows  = data.length;
    const legendH     = legendRows * legendRowH + 8;
    const donutSpace  = h - legendH;          // pixels available for the circle
    const r   = Math.min(w * 0.38, donutSpace / 2 * 0.88);
    const cx  = w / 2;
    const cy  = donutSpace / 2;              // true vertical centre of donut area
    const inner = r * 0.55;
    let start = -Math.PI / 2;

    // slices
    data.forEach((d, i) => {
        const val = Number(d.value || 0);
        const sweep = (val / total) * Math.PI * 2;
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, r, start, start + sweep);
        ctx.closePath();
        ctx.fillStyle = CHART_COLORS[i % CHART_COLORS.length];
        ctx.fill();
        start += sweep;
    });

    // donut hole
    ctx.beginPath();
    ctx.arc(cx, cy, inner, 0, Math.PI * 2);
    ctx.fillStyle = '#fff';
    ctx.fill();

    // centre label
    chartFont(ctx, 14, '700');
    ctx.fillStyle = '#172033';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(total, cx, cy - 9);
    chartFont(ctx, 10, '500');
    ctx.fillStyle = '#64748b';
    ctx.fillText('Total', cx, cy + 10);

    // legend below donut — centred horizontally
    const legendTop = donutSpace + 8;
    const swatchW   = 12;
    const colW      = 110; // fixed column width for centering
    const totalLegW = data.length * colW;
    const legendStartX = (w - totalLegW) / 2;

    data.forEach((d, i) => {
        const val = Number(d.value || 0);
        const pct = Math.round((val / total) * 100);
        const x   = legendStartX + i * colW;
        const y   = legendTop + 12;

        // swatch
        ctx.fillStyle = CHART_COLORS[i % CHART_COLORS.length];
        roundRect(ctx, x, y, swatchW, swatchW, 3);
        ctx.fill();

        // label
        chartFont(ctx, 12, '600');
        ctx.fillStyle = '#172033';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'top';
        ctx.fillText(String(d.label).charAt(0).toUpperCase() + String(d.label).slice(1), x + swatchW + 6, y);

        // value
        chartFont(ctx, 11, '700');
        ctx.fillStyle = '#64748b';
        ctx.fillText(`${val}  (${pct}%)`, x + swatchW + 6, y + 17);
    });
}

// ─── Bar chart (auto horizontal when many bars) ─────────────────────────────
const MONTH_NAMES = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
function fmtBarLabel(lbl) {
    // "2025-11" → "Nov '25"
    const m = String(lbl).match(/^(\d{4})-(\d{2})$/);
    if (m) return MONTH_NAMES[parseInt(m[2], 10) - 1] + ' \'' + m[1].slice(2);
    return String(lbl);
}
function drawBars(id, data, suffix = '', forceHorizontal = false, forceVertical = false) {
    const horizontal = !forceVertical && (forceHorizontal || data.length > 5);
    if (horizontal) { drawHBars(id, data, suffix); return; }

    const p = prepCanvas(id);
    if (!p) return;
    const { ctx, w, h } = p;
    if (!data.length) { drawEmpty(ctx, w, h); return; }

    const pad = { top: 30, right: 24, bottom: 66, left: 52 };
    const gW = w - pad.left - pad.right;
    const gH = h - pad.top - pad.bottom;
    const maxVal = niceMax(Math.max(...data.map(d => Number(d.value || 0)), 1));
    const ticks = 5;

    ctx.strokeStyle = '#e8edf5';
    ctx.lineWidth = 1;
    chartFont(ctx, 11, '500');
    ctx.fillStyle = '#94a3b8';
    ctx.textAlign = 'right';
    ctx.textBaseline = 'middle';
    for (let i = 0; i <= ticks; i++) {
        const val = (maxVal / ticks) * i;
        const y = pad.top + gH - (val / maxVal) * gH;
        ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(pad.left + gW, y); ctx.stroke();
        ctx.fillText(Math.round(val) + suffix, pad.left - 8, y);
    }

    ctx.strokeStyle = '#cbd5e1'; ctx.lineWidth = 1.5;
    ctx.beginPath(); ctx.moveTo(pad.left, pad.top); ctx.lineTo(pad.left, pad.top + gH); ctx.lineTo(pad.left + gW, pad.top + gH); ctx.stroke();

    const gap = 0.82;
    const barW = Math.max(4, (gW / data.length) * (1 - gap));
    const step = gW / data.length;
    data.forEach((d, i) => {
        const val = Number(d.value || 0);
        const bH  = (val / maxVal) * gH;
        const x   = pad.left + i * step + (step - barW) / 2;
        const y   = pad.top + gH - bH;
        const grad = ctx.createLinearGradient(x, y, x, pad.top + gH);
        grad.addColorStop(0, '#c0392b'); grad.addColorStop(1, '#8B1A1A');
        ctx.fillStyle = val > 0 ? grad : '#e2e8f0';
        roundRect(ctx, x, y, barW, bH || 2, 6); ctx.fill();
        if (val > 0) {
            chartFont(ctx, 11, '700'); ctx.fillStyle = '#172033'; ctx.textAlign = 'center'; ctx.textBaseline = 'bottom';
            ctx.fillText(val + suffix, x + barW / 2, y - 4);
        }
        chartFont(ctx, 11, '500'); ctx.fillStyle = '#64748b';
        const labelStr = fmtBarLabel(d.label);
        ctx.save(); ctx.translate(x + barW / 2, pad.top + gH + 10); ctx.rotate(-Math.PI / 4);
        ctx.textAlign = 'right'; ctx.textBaseline = 'middle';
        ctx.fillText(labelStr, 0, 0); ctx.restore();
    });
}

function drawHBars(id, data, suffix = '') {
    if (!data.length) return;
    const barH    = 36;
    const gapH    = 14;
    const padR    = 56;
    const padTop  = 16;
    const padBot  = 40;  // enough room for tick labels below the chart area
    const totalH  = padTop + data.length * (barH + gapH) - gapH + padBot;

    const c = document.getElementById(id);
    if (!c) return;
    const dpr  = window.devicePixelRatio || 1;
    c.style.width = '100%';
    c.removeAttribute('width');
    c.removeAttribute('height');
    const cssW = c.offsetWidth || c.parentElement.clientWidth || 700;

    // Measure max label width dynamically so labels never get clipped
    const measureCtx = document.createElement('canvas').getContext('2d');
    measureCtx.font = '600 12px "Plus Jakarta Sans", system-ui, sans-serif';
    const maxTextW = Math.max(...data.map(d => measureCtx.measureText(String(d.label)).width));
    const padL = Math.min(Math.ceil(maxTextW) + 20, 280);
    c.width  = cssW * dpr;
    c.height = totalH * dpr;
    c.style.setProperty('width',  cssW   + 'px', 'important');
    c.style.setProperty('height', totalH + 'px', 'important');
    const ctx = c.getContext('2d');
    ctx.scale(dpr, dpr);
    ctx.clearRect(0, 0, cssW, totalH);

    const gW     = cssW - padL - padR;
    const maxVal = niceMax(Math.max(...data.map(d => Number(d.value || 0)), 1));

    // faint vertical grid lines
    const ticks = 4;
    ctx.strokeStyle = '#e8edf5'; ctx.lineWidth = 1;
    chartFont(ctx, 10, '500'); ctx.fillStyle = '#94a3b8'; ctx.textAlign = 'center'; ctx.textBaseline = 'top';
    for (let i = 0; i <= ticks; i++) {
        const x = padL + (i / ticks) * gW;
        ctx.beginPath(); ctx.moveTo(x, padTop); ctx.lineTo(x, padTop + totalH - padBot); ctx.stroke();
        ctx.fillText(Math.round((maxVal / ticks) * i) + suffix, x, padTop + totalH - padBot + 4);
    }

    const hitRegions = [];
    data.forEach((d, i) => {
        const val  = Number(d.value || 0);
        const bW   = (val / maxVal) * gW;
        const y    = padTop + i * (barH + gapH);
        const x    = padL;
        hitRegions.push({ y, h: barH, label: d.label, val });

        // label — truncate by pixel width, not character count
        chartFont(ctx, 12, '600');
        ctx.fillStyle = '#172033';
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        const labelStr = String(d.label);
        const maxLabelW = padL - 14;
        let truncated = labelStr;
        while (truncated.length > 1 && ctx.measureText(truncated).width > maxLabelW) {
            truncated = truncated.slice(0, -1);
        }
        if (truncated !== labelStr) truncated = truncated.slice(0, -1) + '…';
        ctx.fillText(truncated, padL - 10, y + barH / 2);

        // bar
        const grad = ctx.createLinearGradient(x, y, x + bW, y);
        grad.addColorStop(0, '#c0392b');
        grad.addColorStop(1, '#8B1A1A');
        ctx.fillStyle = val > 0 ? grad : '#e2e8f0';
        roundRect(ctx, x, y, Math.max(bW, 4), barH, 8);
        ctx.fill();

        // value label
        chartFont(ctx, 11, '700');
        ctx.fillStyle = val > 0 && bW > 40 ? '#fff' : '#172033';
        ctx.textAlign = val > 0 && bW > 40 ? 'right' : 'left';
        ctx.textBaseline = 'middle';
        if (val > 0 && bW > 40) {
            ctx.fillText(val + suffix, x + bW - 10, y + barH / 2);
        } else {
            ctx.fillText(val + suffix, x + bW + 8, y + barH / 2);
        }
    });
    if (id === 'courseChart') {
        window._courseHitRegions = hitRegions;
        window._courseChartPadL  = padL;
        window._courseTotalH     = totalH;
        attachCourseChartInteraction();
    }
}

function attachCourseChartInteraction() {
    const canvas = document.getElementById('courseChart');
    if (!canvas || canvas._interactionAttached) return;
    canvas._interactionAttached = true;

    // ── Tooltip element ──────────────────────────────────────────────────────
    let tip = document.getElementById('_courseTooltip');
    if (!tip) {
        tip = document.createElement('div');
        tip.id = '_courseTooltip';
        tip.className = 'course-chart-tip';
        document.body.appendChild(tip);
    }

    function getHoveredBar(e) {
        const rect = canvas.getBoundingClientRect();
        const logH = window._courseTotalH || rect.height;
        const rawY = (e.clientY - rect.top) * (logH / rect.height);
        return (window._courseHitRegions || []).find(r => rawY >= r.y && rawY <= r.y + r.h) || null;
    }

    canvas.addEventListener('mousemove', e => {
        const bar = getHoveredBar(e);
        if (!bar) { tip.classList.remove('visible'); canvas.style.cursor = ''; return; }
        const students = (window.dashboardCharts.courseStudents || {})[bar.label] || [];
        canvas.style.cursor = students.length ? 'pointer' : 'default';
        const names = students.slice(0, 5).map(s => `<span>${escapeHtml(s.name)}<em>${s.pct}%</em></span>`).join('');
        const more  = students.length > 5 ? `<span class="tip-more">+${students.length - 5} more</span>` : '';
        tip.innerHTML = `<strong>${escapeHtml(bar.label)}</strong>${names}${more}${students.length ? '<small>Click to see details</small>' : ''}`;
        tip.classList.add('visible');
        const x = Math.min(e.clientX + 14, window.innerWidth - tip.offsetWidth - 12);
        const y = Math.max(e.clientY - tip.offsetHeight / 2, 8);
        tip.style.left = x + 'px';
        tip.style.top  = y + 'px';
    });

    canvas.addEventListener('mouseleave', () => {
        tip.classList.remove('visible');
        canvas.style.cursor = '';
    });

    canvas.addEventListener('click', e => {
        const bar      = getHoveredBar(e);
        if (!bar) return;
        const students = (window.dashboardCharts.courseStudents || {})[bar.label] || [];
        if (!students.length) return;
        tip.classList.remove('visible');

        const rows = students.map(s => {
            const pct   = Math.min(s.pct, 100);
            const color = pct >= 100 ? '#16a34a' : pct >= 50 ? '#f59e0b' : '#8B1A1A';
            return `
                <div class="cprogress-row">
                    <div class="cprogress-header">
                        <div>
                            <span class="cprogress-name">${escapeHtml(s.name)}</span>
                            <span class="cprogress-id">${escapeHtml(s.student_no || '')}</span>
                        </div>
                        <span class="cprogress-pct" style="color:${color}">${pct}%</span>
                    </div>
                    <div class="cprogress-track">
                        <div class="cprogress-fill" style="width:${pct}%;background:${color}"></div>
                    </div>
                    <div class="cprogress-sub">${s.logged}h logged / ${s.required}h required</div>
                </div>`;
        }).join('');

        openSlidePanel(`
            <h2>${escapeHtml(bar.label)}</h2>
            <p class="cprogress-meta">${students.length} student${students.length !== 1 ? 's' : ''} &mdash; avg ${bar.val}% completion</p>
            <div class="cprogress-list">${rows}</div>
        `);
    });
}

// ─── Line chart ──────────────────────────────────────────────────────────────
function drawLine(id, data) {
    const p = prepCanvas(id);
    if (!p) return;
    const { ctx, w, h } = p;
    if (!data.length) { drawEmpty(ctx, w, h); return; }

    const pad = { top: 30, right: 24, bottom: 62, left: 52 };
    const gW = w - pad.left - pad.right;
    const gH = h - pad.top - pad.bottom;
    const maxVal = niceMax(Math.max(...data.map(d => Number(d.value || 0)), 1));
    const ticks = 5;

    // grid + y labels
    ctx.strokeStyle = '#e8edf5';
    ctx.lineWidth = 1;
    chartFont(ctx, 11, '500');
    ctx.fillStyle = '#94a3b8';
    ctx.textAlign = 'right';
    ctx.textBaseline = 'middle';
    for (let i = 0; i <= ticks; i++) {
        const val = (maxVal / ticks) * i;
        const y = pad.top + gH - (val / maxVal) * gH;
        ctx.beginPath();
        ctx.moveTo(pad.left, y);
        ctx.lineTo(pad.left + gW, y);
        ctx.stroke();
        ctx.fillText(Math.round(val), pad.left - 8, y);
    }

    // axes
    ctx.strokeStyle = '#cbd5e1';
    ctx.lineWidth = 1.5;
    ctx.beginPath();
    ctx.moveTo(pad.left, pad.top);
    ctx.lineTo(pad.left, pad.top + gH);
    ctx.lineTo(pad.left + gW, pad.top + gH);
    ctx.stroke();

    const pts = data.map((d, i) => ({
        x: pad.left + (data.length === 1 ? gW / 2 : i * (gW / (data.length - 1))),
        y: pad.top + gH - (Number(d.value || 0) / maxVal) * gH,
        d
    }));

    // area fill
    const areaGrad = ctx.createLinearGradient(0, pad.top, 0, pad.top + gH);
    areaGrad.addColorStop(0, 'rgba(139,26,26,0.18)');
    areaGrad.addColorStop(1, 'rgba(139,26,26,0)');
    ctx.beginPath();
    pts.forEach((pt, i) => i === 0 ? ctx.moveTo(pt.x, pt.y) : ctx.lineTo(pt.x, pt.y));
    ctx.lineTo(pts[pts.length - 1].x, pad.top + gH);
    ctx.lineTo(pts[0].x, pad.top + gH);
    ctx.closePath();
    ctx.fillStyle = areaGrad;
    ctx.fill();

    // line
    ctx.strokeStyle = '#8B1A1A';
    ctx.lineWidth = 3;
    ctx.lineJoin = 'round';
    ctx.beginPath();
    pts.forEach((pt, i) => i === 0 ? ctx.moveTo(pt.x, pt.y) : ctx.lineTo(pt.x, pt.y));
    ctx.stroke();

    // dots + labels
    pts.forEach(pt => {
        // outer ring
        ctx.beginPath();
        ctx.arc(pt.x, pt.y, 7, 0, Math.PI * 2);
        ctx.fillStyle = '#fff';
        ctx.fill();
        ctx.strokeStyle = '#8B1A1A';
        ctx.lineWidth = 2.5;
        ctx.stroke();
        // inner dot
        ctx.beginPath();
        ctx.arc(pt.x, pt.y, 3.5, 0, Math.PI * 2);
        ctx.fillStyle = '#8B1A1A';
        ctx.fill();

        // value above dot
        chartFont(ctx, 11, '700');
        ctx.fillStyle = '#172033';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'bottom';
        ctx.fillText(pt.d.value, pt.x, pt.y - 12);

        // x label – draw after dots so we can do rotation outside the per-dot loop
    });

    // x-axis labels: skip any that would overlap, rotate -35°
    chartFont(ctx, 11, '500');
    ctx.fillStyle = '#64748b';
    const labelY = pad.top + gH + 10;
    const minGap = 52; // minimum px between label centres before skipping
    let lastDrawnX = -Infinity;
    // decide step: draw every Nth label so neighbours are ≥ minGap apart
    const step = Math.ceil(minGap / (pts.length > 1 ? gW / (pts.length - 1) : 1));
    pts.forEach((pt, i) => {
        if (i % step !== 0 && i !== pts.length - 1) return;
        if (pt.x - lastDrawnX < minGap && i !== pts.length - 1) return;
        lastDrawnX = pt.x;
        ctx.save();
        ctx.translate(pt.x, labelY);
        ctx.rotate(-Math.PI / 5); // -36°
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        ctx.fillText(String(pt.d.label), 0, 0);
        ctx.restore();
    });}

function closeStudentModal() { closeSlidePanel(); }

function closeRequirementReviewModals() {
    document.querySelectorAll('.requirement-review-modal.open').forEach(modal => modal.classList.remove('open'));
}

function initRequirementReviewModals() {
    document.querySelectorAll('[data-review-modal]').forEach(button => {
        button.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            const modal = document.getElementById(button.dataset.reviewModal || '');
            if (!modal) return;
            closeStudentModal();
            closeRequirementReviewModals();
            modal.classList.add('open');
        });
    });

    document.querySelectorAll('.requirement-review-modal').forEach(modal => {
        modal.querySelector('.requirement-review-modal-close')?.addEventListener('click', () => modal.classList.remove('open'));
        modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('open'); });
    });
}

function initStudentModal() {
    const modal = document.getElementById('studentModal');
    if (!modal) return;

    // Close handlers
    document.getElementById('studentModalClose')?.addEventListener('click', closeStudentModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeStudentModal(); });

    // Open handler — event delegation on table body
    document.addEventListener('click', e => {
        const btn = e.target.closest('.student-view-btn');
        if (!btn) return;

        closeRequirementReviewModals();

        const d = btn.dataset;
        document.getElementById('sm-name').textContent        = d.name || '';
        document.getElementById('sm-email').textContent       = d.email || '';
        document.getElementById('sm-student-no').textContent  = d.studentNo || '';
        document.getElementById('sm-course').textContent      = d.course || '';
        document.getElementById('sm-year-level').textContent  = d.yearLevel || '';
        const bdRaw = d.birthdate || '';
        document.getElementById('sm-birthdate').textContent   = bdRaw ? new Date(bdRaw + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '—';
        document.getElementById('sm-company').textContent     = d.company || '';
        document.getElementById('sm-progress').textContent    = `${d.rendered} / ${d.required} hrs (${d.percent}%)`;

        // Status badge
        const statusEl = document.getElementById('sm-status');
        statusEl.innerHTML = '';
        const badge = document.createElement('span');
        badge.className = `badge ${d.status}`;
        badge.textContent = d.status;
        statusEl.appendChild(badge);

        // COR link
        const corWrap = document.getElementById('sm-cor-wrap');
        const corLink = document.getElementById('sm-cor-link');
        if (d.cor && d.cor.trim() !== '') {
            corLink.href = d.cor;
            corWrap.style.display = '';
        } else {
            corWrap.style.display = 'none';
        }

        // Edit email form
        document.getElementById('sm-email-csrf').value    = d.csrf || '';
        document.getElementById('sm-email-user-id').value = d.userId || '';
        document.getElementById('sm-email-input').value   = d.email || '';

        // Reset form
        document.getElementById('sm-csrf').value       = d.csrf || '';
        document.getElementById('sm-student-id').value = d.studentId || '';

        modal.classList.add('open');
    });
}