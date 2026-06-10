document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initToasts();
    initFloatingLabels();
    initCustomFilterSelects();
    initDateTimePickers();
    initCustomDatePickers();
    initPhoneInputs();
    initCharacterCounters();
    initDtrTimeLocks();
    initForms();
    initCounters();
    initWizards();
    initEnrollmentAutomation();
    initEnrollmentDirectory();
    initViewToggles();
    initTimelineDetails();
    initEmailLogViews();
    initRequirementReviewModals();
    initNotifications();
    try { initWeeklyReportUpload(); } catch (err) { console.warn('Weekly report upload init failed:', err); }
    initMoaLibrary();
    initCoordinatorCardAlignment();
    initConfirmActions();
    initStudentMobileTapProxy();
    initStudentProfilePhotoPreview();
    document.querySelectorAll('.data-table').forEach(table => enhanceTable(table));
    document.querySelector('#modal .modal-close')?.addEventListener('click', closeSlidePanel);
    document.addEventListener('click', handleOutsideMenus);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeSlidePanel(); closeNotifications(); closeRequirementReviewModals(); closeCustomSelects(); closeCustomDatePickers(); closeDtrTimePicker(); } });
    initStudentModal();
    renderDashboardCharts();
});

function initStudentProfilePhotoPreview() {
    const input = document.querySelector('[data-profile-photo-input]');
    const preview = document.querySelector('[data-profile-photo-preview]');
    const fallback = document.querySelector('[data-profile-photo-fallback]');
    const inlinePreview = document.querySelector('[data-profile-photo-preview-inline]');
    const inlineFallback = document.querySelector('[data-profile-initial-inline]');
    const inlineAvatar = document.querySelector('[data-profile-inline-avatar]');

    const setInlinePhoto = url => {
        if (!inlinePreview) return;
        if (!url) {
            inlinePreview.classList.add('is-hidden');
            inlinePreview.removeAttribute('src');
            inlineFallback?.classList.remove('is-hidden');
            inlineAvatar?.classList.remove('app-user-identity__avatar--photo');
            return;
        }
        inlinePreview.src = url;
        inlinePreview.classList.remove('is-hidden');
        inlineFallback?.classList.add('is-hidden');
        inlineAvatar?.classList.add('app-user-identity__avatar--photo');
    };

    const setSidebarPhoto = url => {
        if (!preview || !fallback) return;
        if (!url) {
            preview.classList.add('is-hidden');
            preview.removeAttribute('src');
            fallback.classList.remove('is-hidden');
            return;
        }
        preview.src = url;
        preview.classList.remove('is-hidden');
        fallback.classList.add('is-hidden');
    };

    const showPhoto = url => {
        setSidebarPhoto(url);
        setInlinePhoto(url);
    };

    const showFallback = () => showPhoto('');

    if (preview && fallback) {
        const previewSrc = (preview.getAttribute('src') || '').trim();
        if (!previewSrc) {
            setSidebarPhoto('');
        } else {
            preview.addEventListener('error', () => setSidebarPhoto(''), { once: true });
        }
    }

    const inlineSrc = (inlinePreview?.getAttribute('src') || '').trim();
    if (inlineSrc) {
        setInlinePhoto(inlineSrc);
        inlinePreview?.addEventListener('error', () => setInlinePhoto(''), { once: true });
    } else {
        setInlinePhoto('');
    }

    input?.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file || !file.type.startsWith('image/')) {
            showFallback();
            return;
        }

        showPhoto(URL.createObjectURL(file));
    });
}

function initConfirmActions() {
    document.querySelectorAll('[data-confirm]').forEach(element => {
        if (element.dataset.confirmReady === '1') return;
        element.dataset.confirmReady = '1';
        element.addEventListener('click', async event => {
            event.preventDefault();
            event.stopPropagation();
            const confirmed = await showConfirmModal(element.dataset.confirm || 'Are you sure?', {
                title: element.dataset.confirmTitle || 'Confirm action',
                confirmText: element.dataset.confirmOk || 'Continue',
                cancelText: element.dataset.confirmCancel || 'Cancel',
            });
            if (!confirmed) return;
            if (element.tagName === 'A' && element.href) {
                window.location.href = element.href;
                return;
            }
            element.closest('form')?.requestSubmit();
        });
    });
}

function showConfirmModal(message, options = {}) {
    return new Promise(resolve => {
        const existing = document.querySelector('.app-confirm-overlay');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.className = 'app-confirm-overlay';
        overlay.innerHTML = `
            <div class="app-confirm-card" role="dialog" aria-modal="true" aria-labelledby="app-confirm-title">
                <div class="app-confirm-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 1 0 20a10 10 0 0 1 0-20Zm0 5a1 1 0 0 0-1 1v4.2a1 1 0 0 0 2 0V8a1 1 0 0 0-1-1Zm0 9.75a1.25 1.25 0 1 0 0-2.5a1.25 1.25 0 0 0 0 2.5Z"/></svg>
                </div>
                <div class="app-confirm-copy">
                    <h2 id="app-confirm-title">${escapeHtml(options.title || 'Confirm action')}</h2>
                    <p>${escapeHtml(message)}</p>
                </div>
                <div class="app-confirm-actions">
                    <button class="btn btn-ghost app-confirm-cancel" type="button">${escapeHtml(options.cancelText || 'Cancel')}</button>
                    <button class="btn btn-primary app-confirm-ok" type="button">${escapeHtml(options.confirmText || 'Continue')}</button>
                </div>
            </div>
        `;

        const close = value => {
            overlay.classList.remove('is-open');
            document.removeEventListener('keydown', onKeydown);
            setTimeout(() => overlay.remove(), 160);
            resolve(value);
        };
        const onKeydown = event => {
            if (event.key === 'Escape') close(false);
        };

        overlay.addEventListener('click', event => {
            if (event.target === overlay) close(false);
        });
        overlay.querySelector('.app-confirm-cancel')?.addEventListener('click', () => close(false));
        overlay.querySelector('.app-confirm-ok')?.addEventListener('click', () => close(true));
        document.addEventListener('keydown', onKeydown);
        document.body.appendChild(overlay);
        requestAnimationFrame(() => {
            overlay.classList.add('is-open');
            overlay.querySelector('.app-confirm-ok')?.focus();
        });
    });
}

function showAlertModal(message, options = {}) {
    return new Promise(resolve => {
        const existing = document.querySelector('.app-confirm-overlay');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.className = 'app-confirm-overlay';
        overlay.innerHTML = `
            <div class="app-confirm-card" role="alertdialog" aria-modal="true" aria-labelledby="app-alert-title">
                <div class="app-confirm-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 1 0 20a10 10 0 0 1 0-20Zm0 5a1 1 0 0 0-1 1v4.2a1 1 0 0 0 2 0V8a1 1 0 0 0-1-1Zm0 9.75a1.25 1.25 0 1 0 0-2.5a1.25 1.25 0 0 0 0 2.5Z"/></svg>
                </div>
                <div class="app-confirm-copy">
                    <h2 id="app-alert-title">${escapeHtml(options.title || 'Notice')}</h2>
                    <p>${escapeHtml(message)}</p>
                </div>
                <div class="app-confirm-actions">
                    <button class="btn btn-primary app-alert-ok" type="button">${escapeHtml(options.confirmText || 'OK')}</button>
                </div>
            </div>
        `;

        const close = () => {
            overlay.classList.remove('is-open');
            document.removeEventListener('keydown', onKeydown);
            setTimeout(() => overlay.remove(), 160);
            resolve();
        };
        const onKeydown = event => {
            if (event.key === 'Escape' || event.key === 'Enter') close();
        };

        overlay.addEventListener('click', event => {
            if (event.target === overlay) close();
        });
        overlay.querySelector('.app-alert-ok')?.addEventListener('click', close);
        document.addEventListener('keydown', onKeydown);
        document.body.appendChild(overlay);
        requestAnimationFrame(() => {
            overlay.classList.add('is-open');
            overlay.querySelector('.app-alert-ok')?.focus();
        });
    });
}

function initStudentMobileTapProxy() {
    if (!document.body.classList.contains('role-student')) return;

    let proxying = false;
    let suppressUntil = 0;
    const isMobileStudentLayout = () => window.matchMedia('(max-width: 720px)').matches;
    const clickableSelector = 'button,a,input:not([type="hidden"]),textarea,select,[data-time-lock-toggle],[data-time-picker-trigger],.filter-date-trigger';
    const skipSelector = '.notif-panel,.topbar,.sidebar,.global-cal-panel,.global-datetime-panel,.dtr-time-panel';

    const findContentControl = (x, y) => {
        const content = document.querySelector('.role-student .content');
        if (!content) return null;

        const elements = document.elementsFromPoint(x, y);
        for (const element of elements) {
            if (!element || element === document.documentElement || element === document.body) continue;
            if (element.closest(skipSelector)) continue;
            if (!content.contains(element)) continue;

            const control = element.matches(clickableSelector) ? element : element.closest(clickableSelector);
            if (control && content.contains(control)) return control;
        }
        return null;
    };

    const forwardTap = event => {
        if (proxying || !isMobileStudentLayout()) return;
        if (Date.now() < suppressUntil) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }
        const point = event.changedTouches?.[0] || event.touches?.[0] || event;
        if (typeof point.clientX !== 'number' || typeof point.clientY !== 'number') return;

        const topbar = document.querySelector('.role-student .topbar')?.getBoundingClientRect();
        const sidebar = document.querySelector('.role-student .sidebar')?.getBoundingClientRect();
        if (topbar && point.clientY <= topbar.bottom) return;
        if (sidebar && point.clientY >= sidebar.top) return;

        const control = findContentControl(point.clientX, point.clientY);
        if (!control) return;

        event.preventDefault();
        event.stopImmediatePropagation();
        proxying = true;
        suppressUntil = Date.now() + 450;
        try {
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(control.tagName) && control.type !== 'button' && control.type !== 'submit') {
                control.focus();
                if (control.type === 'file') control.click();
            } else {
                control.click();
            }
        } finally {
            setTimeout(() => { proxying = false; }, 0);
        }
    };

    document.addEventListener('touchend', forwardTap, { capture: true, passive: false });
    document.addEventListener('click', forwardTap, { capture: true });
}

function initMoaLibrary() {
    const library = document.querySelector('[data-cdoc-library]');
    if (!library) return;

    const searchInput = library.querySelector('[data-cdoc-search]');
    const filters = Array.from(library.querySelectorAll('[data-cdoc-filter]'));
    const cards = Array.from(library.querySelectorAll('[data-cdoc-card]'));
    const count = library.querySelector('[data-cdoc-visible-count]');
    const noResults = library.querySelector('[data-cdoc-no-results]');
    let activeFilter = 'all';

    const applyFilters = () => {
        const query = (searchInput?.value || '').trim().toLowerCase();
        let visible = 0;

        cards.forEach(card => {
            const status = card.dataset.status || '';
            const searchable = card.dataset.search || '';
            const matchesStatus = activeFilter === 'all' || status === activeFilter;
            const matchesSearch = query === '' || searchable.includes(query);
            const shouldShow = matchesStatus && matchesSearch;

            card.hidden = !shouldShow;
            if (shouldShow) visible += 1;
        });

        if (count) count.textContent = String(visible);
        if (noResults) noResults.hidden = visible !== 0;
    };

    searchInput?.addEventListener('input', applyFilters);
    filters.forEach(button => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.cdocFilter || 'all';
            filters.forEach(item => item.classList.toggle('is-active', item === button));
            applyFilters();
        });
    });

    applyFilters();
}

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

/* ── Global shared calendar panel (escapes all overflow/transform ancestors) ── */
let _globalCalPanel = null;
let _globalCalActivePicker = null;
let _globalCalState = null;

function getGlobalCalPanel() {
    if (_globalCalPanel) return _globalCalPanel;
    _globalCalPanel = document.createElement('div');
    _globalCalPanel.className = 'global-cal-panel';
    _globalCalPanel.hidden = true;
    document.body.appendChild(_globalCalPanel);

    _globalCalPanel.addEventListener('mousedown', e => e.stopPropagation());
    _globalCalPanel.addEventListener('click', e => {
        e.stopPropagation();
        const nav = e.target.closest('[data-date-nav]');
        if (nav && _globalCalState) {
            const delta = Number(nav.dataset.dateNav || 0);
            _globalCalState.view = new Date(_globalCalState.view.getFullYear(), _globalCalState.view.getMonth() + delta, 1);
            _globalCalPanel.innerHTML = buildCustomDatePanel(_globalCalState);
            return;
        }
        const action = e.target.closest('[data-date-action]');
        if (action && _globalCalActivePicker) {
            const { input, state, sync } = _globalCalActivePicker;
            if (action.dataset.dateAction === 'clear') {
                state.selected = null; input.value = ''; sync();
            }
            if (action.dataset.dateAction === 'today') {
                const today = stripTime(new Date());
                state.selected = today; state.view = new Date(today.getFullYear(), today.getMonth(), 1);
                input.value = formatCustomDateValue(today); sync();
            }
            closeGlobalCalPanel();
            return;
        }
        const day = e.target.closest('[data-date-value]');
        if (day && _globalCalActivePicker) {
            const { input, state, sync, picker } = _globalCalActivePicker;
            const selectedDate = parseCustomDateValue(day.dataset.dateValue || '');
            if (!selectedDate) return;
            state.selected = selectedDate;
            state.view = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
            input.value = formatCustomDateValue(selectedDate);
            picker.classList.remove('date-required-error');
            sync();
            closeGlobalCalPanel();
        }
    });
    _globalCalPanel.addEventListener('change', e => {
        const yearSelect = e.target.closest('[data-date-year]');
        if (!yearSelect || !_globalCalState) return;
        const selectedYear = Number(yearSelect.value || 0);
        if (!selectedYear) return;
        _globalCalState.view = new Date(selectedYear, _globalCalState.view.getMonth(), 1);
        _globalCalPanel.innerHTML = buildCustomDatePanel(_globalCalState);
    });
    return _globalCalPanel;
}

function positionGlobalCalPanel(trigger) {
    const panel = getGlobalCalPanel();
    const rect = trigger.getBoundingClientRect();
    const panelW = 308;
    const panelH = 360;
    const vpW = window.innerWidth;
    const vpH = window.innerHeight;
    let left = rect.left;
    if (left + panelW > vpW - 8) left = vpW - panelW - 8;
    if (left < 8) left = 8;
    let top;
    if (rect.bottom + 6 + panelH <= vpH - 8) {
        top = rect.bottom + 6;
    } else {
        top = rect.top - 6 - panelH;
        if (top < 8) top = 8;
    }
    panel.style.top = top + 'px';
    panel.style.left = left + 'px';
}

function openGlobalCalPanel(pickerCtx) {
    _globalCalActivePicker = pickerCtx;
    _globalCalState = pickerCtx.state;
    const panel = getGlobalCalPanel();
    panel.innerHTML = buildCustomDatePanel(_globalCalState);
    positionGlobalCalPanel(pickerCtx.trigger);
    panel.hidden = false;
    requestAnimationFrame(() => panel.classList.add('is-open'));
}

function closeGlobalCalPanel() {
    if (!_globalCalPanel) return;
    _globalCalPanel.classList.remove('is-open');
    _globalCalPanel.hidden = true;
    if (_globalCalActivePicker) {
        _globalCalActivePicker.picker.classList.remove('is-open');
        _globalCalActivePicker.picker.querySelector('.filter-date-trigger')?.setAttribute('aria-expanded', 'false');
        _globalCalActivePicker = null;
    }
    _globalCalState = null;
}

/* ── Global DateTime Picker (calendar + time) ── */
let _globalDtPanel = null;
let _globalDtActivePicker = null;
let _globalDtState = null;

function getGlobalDtPanel() {
    if (_globalDtPanel) return _globalDtPanel;
    _globalDtPanel = document.createElement('div');
    _globalDtPanel.className = 'global-datetime-panel';
    _globalDtPanel.hidden = true;
    document.body.appendChild(_globalDtPanel);

    _globalDtPanel.addEventListener('mousedown', e => e.stopPropagation());
    _globalDtPanel.addEventListener('click', e => {
        e.stopPropagation();
        if (!_globalDtState || !_globalDtActivePicker) return;
        const { input, state, sync, picker } = _globalDtActivePicker;

        const nav = e.target.closest('[data-date-nav]');
        if (nav) {
            state.view = new Date(state.view.getFullYear(), state.view.getMonth() + Number(nav.dataset.dateNav || 0), 1);
            renderDtPanel(); return;
        }
        const action = e.target.closest('[data-date-action]');
        if (action) {
            if (action.dataset.dateAction === 'clear') {
                state.selected = null; state.hour = 9; state.minute = 0; state.period = 'AM';
                input.value = ''; sync();
            }
            if (action.dataset.dateAction === 'today') {
                const today = stripTime(new Date());
                state.selected = today; state.view = new Date(today.getFullYear(), today.getMonth(), 1);
            }
            renderDtPanel(); return;
        }
        const day = e.target.closest('[data-date-value]');
        if (day) {
            const d = parseCustomDateValue(day.dataset.dateValue || '');
            if (!d) return;
            state.selected = d;
            state.view = new Date(d.getFullYear(), d.getMonth(), 1);
            picker.classList.remove('date-required-error');
            commitDtValue(); renderDtPanel(); return;
        }
        const hour = e.target.closest('[data-dt-hour]');
        if (hour) { state.hour = Number(hour.dataset.dtHour); commitDtValue(); renderDtPanel(); scrollDtCol(hour); return; }
        const minute = e.target.closest('[data-dt-minute]');
        if (minute) { state.minute = Number(minute.dataset.dtMinute); commitDtValue(); renderDtPanel(); scrollDtCol(minute); return; }
        const period = e.target.closest('[data-dt-period]');
        if (period) { state.period = period.dataset.dtPeriod; commitDtValue(); renderDtPanel(); return; }
        const confirm = e.target.closest('[data-dt-confirm]');
        if (confirm) { closeGlobalDtPanel(); return; }
    });
    _globalDtPanel.addEventListener('change', e => {
        const yearSelect = e.target.closest('[data-date-year]');
        if (!yearSelect || !_globalDtState) return;
        _globalDtState.view = new Date(Number(yearSelect.value), _globalDtState.view.getMonth(), 1);
        renderDtPanel();
    });
    return _globalDtPanel;
}

function scrollDtCol(btn) {
    const col = btn.closest('.datetime-time-col');
    if (col) btn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

function commitDtValue() {
    if (!_globalDtActivePicker || !_globalDtState) return;
    const { input, sync, state } = _globalDtActivePicker;
    if (!state.selected) return;
    let h24 = state.hour % 12;
    if (state.period === 'PM') h24 += 12;
    if (state.period === 'AM' && state.hour === 12) h24 = 0;
    const ds = formatCustomDateValue(state.selected);
    const ts = String(h24).padStart(2, '0') + ':' + String(state.minute).padStart(2, '0');
    input.value = ds + 'T' + ts;
    sync();
}

function renderDtPanel() {
    if (!_globalDtPanel || !_globalDtState) return;
    _globalDtPanel.innerHTML = buildDateTimePanel(_globalDtState);
    const panel = _globalDtPanel;
    requestAnimationFrame(() => {
        panel.querySelectorAll('.datetime-time-col').forEach(col => {
            const sel = col.querySelector('.is-selected');
            if (sel) sel.scrollIntoView({ block: 'center', behavior: 'instant' });
        });
    });
}

function buildDateTimePanel(state) {
    const calendarHtml = buildCustomDatePanel(state);
    const hours = [];
    for (let h = 1; h <= 12; h++) {
        hours.push(`<button class="datetime-time-item${state.hour === h ? ' is-selected' : ''}" type="button" data-dt-hour="${h}">${h}</button>`);
    }
    const minutes = [];
    for (let m = 0; m < 60; m += 5) {
        minutes.push(`<button class="datetime-time-item${state.minute === m ? ' is-selected' : ''}" type="button" data-dt-minute="${m}">${String(m).padStart(2, '0')}</button>`);
    }
    const periods = ['AM', 'PM'].map(p =>
        `<button class="datetime-time-item${state.period === p ? ' is-selected' : ''}" type="button" data-dt-period="${p}">${p}</button>`
    ).join('');

    const previewTime = state.selected
        ? `${state.hour}:${String(state.minute).padStart(2, '0')} ${state.period}`
        : '--:--';

    return `<div class="datetime-panel-layout">${calendarHtml}<div class="datetime-time-picker"><div class="datetime-time-header">Time · ${previewTime}</div><div class="datetime-time-cols"><div class="datetime-time-col">${hours.join('')}</div><div class="datetime-time-col">${minutes.join('')}</div><div class="datetime-time-period-col">${periods}</div></div><div class="datetime-confirm-row"><button class="btn btn-small" type="button" data-dt-confirm>Done</button></div></div></div>`;
}

function positionGlobalDtPanel(trigger) {
    const panel = getGlobalDtPanel();
    const rect = trigger.getBoundingClientRect();
    const isMobile = window.innerWidth <= 520;
    const panelW = isMobile ? Math.min(308, window.innerWidth - 16) : 470;
    const panelH = isMobile ? 520 : 400;
    const vpW = window.innerWidth;
    const vpH = window.innerHeight;
    let left = rect.left;
    if (left + panelW > vpW - 8) left = vpW - panelW - 8;
    if (left < 8) left = 8;
    let top = rect.bottom + 6;
    if (top + panelH > vpH - 8) top = rect.top - 6 - panelH;
    if (top < 8) top = 8;
    panel.style.top = top + 'px';
    panel.style.left = left + 'px';
}

function openGlobalDtPanel(pickerCtx) {
    _globalDtActivePicker = pickerCtx;
    _globalDtState = pickerCtx.state;
    const panel = getGlobalDtPanel();
    renderDtPanel();
    positionGlobalDtPanel(pickerCtx.trigger);
    panel.hidden = false;
    requestAnimationFrame(() => panel.classList.add('is-open'));
}

function closeGlobalDtPanel() {
    if (!_globalDtPanel) return;
    _globalDtPanel.classList.remove('is-open');
    _globalDtPanel.hidden = true;
    if (_globalDtActivePicker) {
        _globalDtActivePicker.picker.classList.remove('is-open');
        _globalDtActivePicker.picker.querySelector('.filter-date-trigger')?.setAttribute('aria-expanded', 'false');
        _globalDtActivePicker = null;
    }
    _globalDtState = null;
}

function initDateTimePickers() {
    document.querySelectorAll('.form-datetime-picker').forEach(picker => {
        if (picker.dataset.enhanced === '1') return;
        picker.dataset.enhanced = '1';

        const input = picker.querySelector('input[type="hidden"]');
        const trigger = picker.querySelector('.filter-date-trigger');
        const value = picker.querySelector('.filter-date-value');
        if (!input || !trigger || !value) return;

        let initialDate = null;
        let initialHour = 9, initialMinute = 0, initialPeriod = 'AM';
        if (input.value) {
            const parts = input.value.split('T');
            initialDate = parseCustomDateValue(parts[0]);
            if (parts[1]) {
                const [h, m] = parts[1].split(':').map(Number);
                initialPeriod = h >= 12 ? 'PM' : 'AM';
                initialHour = h % 12 || 12;
                initialMinute = Math.round(m / 5) * 5;
                if (initialMinute >= 60) initialMinute = 55;
            }
        }

        const state = {
            selected: initialDate,
            view: initialDate ? new Date(initialDate.getFullYear(), initialDate.getMonth(), 1) : new Date(new Date().getFullYear(), new Date().getMonth(), 1),
            max: null,
            hour: initialHour,
            minute: initialMinute,
            period: initialPeriod,
        };

        const formatDisplay = () => {
            if (!state.selected) return 'Select date & time';
            const d = formatCustomDateDisplay(state.selected);
            return `${d} ${state.hour}:${String(state.minute).padStart(2, '0')} ${state.period}`;
        };

        const sync = () => {
            value.textContent = formatDisplay();
            picker.classList.toggle('is-placeholder', !state.selected);
        };

        trigger.addEventListener('click', e => {
            e.stopPropagation();
            const isOpen = picker.classList.contains('is-open');
            closeCustomSelects();
            closeGlobalCalPanel();
            closeGlobalDtPanel();
            if (!isOpen) {
                picker.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                openGlobalDtPanel({ picker, trigger, input, state, sync });
            }
        });

        sync();
    });
}

function initCustomDatePickers() {
    document.querySelectorAll('.filter-date-picker').forEach(picker => {
        if (picker.dataset.enhanced === '1') return;
        picker.dataset.enhanced = '1';

        const input = picker.querySelector('input[type="hidden"]');
        const trigger = picker.querySelector('.filter-date-trigger');
        const value = picker.querySelector('.filter-date-value');
        const isFormPicker = picker.classList.contains('form-date-picker');

        // For non-form pickers keep the inline panel approach
        const panel = !isFormPicker ? picker.querySelector('.filter-date-panel') : null;
        if (!input || !trigger || !value) return;
        if (!isFormPicker && !panel) return;

        const initialDate = parseCustomDateValue(input.value);
        const maxDate = parseCustomDateValue(picker.dataset.dateMax || '');
        const minDate = parseCustomDateValue(picker.dataset.dateMin || '');
        const state = {
            selected: initialDate,
            view: initialDate ? new Date(initialDate.getFullYear(), initialDate.getMonth(), 1) : (minDate ? new Date(minDate.getFullYear(), minDate.getMonth(), 1) : (maxDate ? new Date(maxDate.getFullYear(), maxDate.getMonth(), 1) : new Date(new Date().getFullYear(), new Date().getMonth(), 1))),
            max: maxDate || null,
            min: minDate || null,
        };

        const sync = () => {
            value.textContent = state.selected ? formatCustomDateDisplay(state.selected) : 'mm/dd/yyyy';
            picker.classList.toggle('is-placeholder', !state.selected);
        };

        if (isFormPicker) {
            // Remove the inline placeholder panel — all rendering is via global panel
            picker.querySelector('.filter-date-panel')?.remove();

            trigger.addEventListener('click', event => {
                event.stopPropagation();
                const isOpen = picker.classList.contains('is-open');
                closeCustomSelects();
                closeGlobalCalPanel();
                if (!isOpen) {
                    picker.classList.add('is-open');
                    trigger.setAttribute('aria-expanded', 'true');
                    openGlobalCalPanel({ picker, trigger, input, state, sync });
                }
            });
        } else {
            // Original inline panel logic for filter date pickers (email logs)
            const render = () => { panel.innerHTML = buildCustomDatePanel(state); };

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
                    render(); return;
                }
                const action = event.target.closest('[data-date-action]');
                if (action) {
                    if (action.dataset.dateAction === 'clear') { state.selected = null; input.value = ''; sync(); closeCustomDatePickers(); }
                    if (action.dataset.dateAction === 'today') {
                        const today = stripTime(new Date());
                        state.selected = today; state.view = new Date(today.getFullYear(), today.getMonth(), 1);
                        input.value = formatCustomDateValue(today); sync(); closeCustomDatePickers();
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
                picker.classList.remove('date-required-error');
                sync(); closeCustomDatePickers();
            });

            panel.addEventListener('change', event => {
                const yearSelect = event.target.closest('[data-date-year]');
                if (!yearSelect) return;
                const selectedYear = Number(yearSelect.value || 0);
                if (!selectedYear) return;
                state.view = new Date(selectedYear, state.view.getMonth(), 1);
                render();
            });

            render();
        }

        sync();
    });
}

function buildCustomDatePanel(state) {
    const today = stripTime(new Date());
    const monthLabel = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(state.view);
    const weekDays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
    const firstDay = new Date(state.view.getFullYear(), state.view.getMonth(), 1);
    const start = new Date(firstDay);
    start.setDate(firstDay.getDate() - firstDay.getDay());
    const currentYear = today.getFullYear();
    const maxYear = state.max ? state.max.getFullYear() : currentYear + 10;
    const minYearBase = state.min ? state.min.getFullYear() : maxYear - 100;
    const minYear = Math.min(minYearBase, state.selected?.getFullYear() ?? minYearBase, state.view.getFullYear());
    const years = [];
    for (let year = maxYear; year >= minYear; year -= 1) {
        years.push(`<option value="${year}" ${year === state.view.getFullYear() ? 'selected' : ''}>${year}</option>`);
    }

    const cells = [];
    for (let index = 0; index < 42; index += 1) {
        const date = new Date(start);
        date.setDate(start.getDate() + index);

        const classes = ['filter-date-day'];
        if (date.getMonth() !== state.view.getMonth()) classes.push('is-outside');
        if (isSameCustomDate(date, today)) classes.push('is-today');
        if (state.selected && isSameCustomDate(date, state.selected)) classes.push('is-selected');
        const isDisabled = (state.max && date > state.max) || (state.min && date < state.min);
        if (isDisabled) classes.push('is-disabled');

        cells.push(`
            <button
                class="${classes.join(' ')}"
                type="button"
                ${isDisabled ? 'disabled aria-disabled="true"' : `data-date-value="${formatCustomDateValue(date)}"`}
                aria-pressed="${state.selected && isSameCustomDate(date, state.selected) ? 'true' : 'false'}"
            >${date.getDate()}</button>
        `);
    }

    return `
        <div class="filter-date-calendar" role="dialog" aria-label="Calendar picker">
            <div class="filter-date-calendar-header">
                <button class="filter-date-nav" type="button" data-date-nav="-1" aria-label="Previous month"></button>
                <div class="filter-date-title-wrap">
                    <div class="filter-date-title">${monthLabel}</div>
                    <label class="filter-date-year-wrap" aria-label="Select year">
                        <select class="filter-date-year-select" data-date-year>
                            ${years.join('')}
                        </select>
                    </label>
                </div>
                <button class="filter-date-nav" type="button" data-date-nav="1" aria-label="Next month"></button>
            </div>
            <div class="filter-date-weekdays">${weekDays.map(day => `<span>${day}</span>`).join('')}</div>
            <div class="filter-date-grid">${cells.join('')}</div>
            <div class="filter-date-actions">
                <button class="filter-date-action" type="button" data-date-action="clear">Clear</button>
                ${(!state.max || today <= state.max) && (!state.min || today >= state.min) ? `<button class="filter-date-action" type="button" data-date-action="today">Today</button>` : ''}
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
    document.querySelectorAll('select').forEach((select, index) => {
        if (select.dataset.enhanced === '1' || select.classList.contains('filter-date-year-select') || select.multiple || select.dataset.nativeSelect === '1') return;

        const wrap = select.closest('.filter-select-wrap') || select.parentElement;
        if (!wrap) return;

        select.dataset.enhanced = '1';
        wrap.classList.add('is-enhanced', 'select-enhanced-wrap');

        const fieldLabel = wrap.closest('.filter-control')?.querySelector('.filter-label')?.textContent?.trim()
            || wrap.querySelector('span')?.textContent?.replace('*', '').trim()
            || select.getAttribute('aria-label')
            || 'Select';
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

        trigger.append(copy, caret);
        custom.append(trigger, menu);
        wrap.appendChild(custom);

        let optionSignature = '';

        const renderOptions = () => {
            const nextSignature = [...select.options].map(option => [option.value, option.textContent, option.hidden, option.disabled].join('|')).join('::');
            if (nextSignature === optionSignature && menu.children.length) return;
            optionSignature = nextSignature;
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
                    <span class="custom-select-option-label">${escapeHtml(option.textContent.trim())}</span>
                `;
                item.addEventListener('click', () => {
                    if (option.disabled) return;
                    select.selectedIndex = optionIndex;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    closeCustomSelects();
                    trigger.focus();
                });
                item.addEventListener('keydown', event => handleCustomSelectOptionKeys(event, custom));
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
        };

        trigger.addEventListener('click', event => {
            event.stopPropagation();
            syncState();
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
        select._syncCustomSelect = syncState;
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
    if (!except) closeGlobalCalPanel();
    document.querySelectorAll('.filter-date-picker.is-open').forEach(picker => {
        if (except && picker === except) return;
        picker.classList.remove('is-open');
        const floatingPanel = picker._floatingPanel;
        if (floatingPanel) {
            floatingPanel.hidden = true;
            floatingPanel.classList.remove('is-open');
        } else {
            picker.querySelector('.filter-date-panel')?.setAttribute('hidden', 'hidden');
        }
        picker.querySelector('.filter-date-trigger')?.setAttribute('aria-expanded', 'false');
        if (picker._parentCard) { picker._parentCard.classList.remove('picker-open'); picker._parentCard = null; }
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

let _dtrTimePanel = null;
let _dtrTimeContext = null;
let _dtrTimeState = null;

function formatDtrTimeDisplay(value) {
    const match = String(value || '').match(/^(\d{1,2}):(\d{2})/);
    if (!match) return '--:-- --';
    const hour24 = Number(match[1]);
    const minute = match[2];
    const suffix = hour24 >= 12 ? 'PM' : 'AM';
    const hour12 = hour24 % 12 || 12;
    return `${String(hour12).padStart(2, '0')}:${minute} ${suffix}`;
}

function toDtrTimeValue(hour, minute, period) {
    let hour24 = Number(hour) % 12;
    if (period === 'PM') hour24 += 12;
    return `${String(hour24).padStart(2, '0')}:${String(Number(minute) || 0).padStart(2, '0')}`;
}

function parseDtrTimeValue(value) {
    const match = String(value || '').match(/^(\d{1,2}):(\d{2})/);
    const fallback = new Date();
    const hour24 = match ? Number(match[1]) : fallback.getHours();
    const minute = match ? Number(match[2]) : fallback.getMinutes();
    return {
        hour: hour24 % 12 || 12,
        minute: Math.max(0, Math.min(59, minute)),
        period: hour24 >= 12 ? 'PM' : 'AM',
    };
}

function getDtrTimePanel() {
    if (_dtrTimePanel) return _dtrTimePanel;
    _dtrTimePanel = document.createElement('div');
    _dtrTimePanel.className = 'dtr-time-panel';
    _dtrTimePanel.hidden = true;
    document.body.appendChild(_dtrTimePanel);

    _dtrTimePanel.addEventListener('mousedown', e => e.stopPropagation());
    _dtrTimePanel.addEventListener('click', e => {
        e.stopPropagation();
        if (!_dtrTimeState || !_dtrTimeContext) return;
        const hour = e.target.closest('[data-dtr-hour]');
        if (hour) {
            _dtrTimeState.hour = Number(hour.dataset.dtrHour || 12);
            renderDtrTimePanel();
            return;
        }
        const period = e.target.closest('[data-dtr-period]');
        if (period) {
            _dtrTimeState.period = period.dataset.dtrPeriod || 'AM';
            renderDtrTimePanel();
            return;
        }
        const now = e.target.closest('[data-dtr-now]');
        if (now) {
            _dtrTimeState = parseDtrTimeValue(`${String(new Date().getHours()).padStart(2, '0')}:${String(new Date().getMinutes()).padStart(2, '0')}`);
            renderDtrTimePanel();
            return;
        }
        const set = e.target.closest('[data-dtr-set-time]');
        if (set) {
            const minuteInput = _dtrTimePanel.querySelector('[data-dtr-minute]');
            _dtrTimeState.minute = Math.max(0, Math.min(59, Number(minuteInput?.value || 0)));
            _dtrTimeContext.input.value = toDtrTimeValue(_dtrTimeState.hour, _dtrTimeState.minute, _dtrTimeState.period);
            _dtrTimeContext.sync();
            closeDtrTimePicker();
            _dtrTimeContext.saveButton?.focus();
        }
    });
    _dtrTimePanel.addEventListener('input', e => {
        const minuteInput = e.target.closest('[data-dtr-minute]');
        if (!minuteInput || !_dtrTimeState) return;
        minuteInput.value = String(Math.max(0, Math.min(59, Number(minuteInput.value || 0)))).padStart(2, '0');
        _dtrTimeState.minute = Number(minuteInput.value || 0);
    });
    return _dtrTimePanel;
}

function renderDtrTimePanel() {
    const panel = getDtrTimePanel();
    const hours = Array.from({ length: 12 }, (_, i) => i + 1).map(hour => `<button type="button" class="dtr-time-chip ${hour === _dtrTimeState.hour ? 'is-active' : ''}" data-dtr-hour="${hour}">${String(hour).padStart(2, '0')}</button>`).join('');
    panel.innerHTML = `<div class="dtr-time-panel-head"><strong>Choose Time</strong><button type="button" data-dtr-now>Use Now</button></div><div class="dtr-time-preview">${formatDtrTimeDisplay(toDtrTimeValue(_dtrTimeState.hour, _dtrTimeState.minute, _dtrTimeState.period))}</div><div class="dtr-time-hours">${hours}</div><div class="dtr-time-bottom"><label>Minute<input type="number" min="0" max="59" value="${String(_dtrTimeState.minute).padStart(2, '0')}" data-dtr-minute></label><div class="dtr-time-periods"><button type="button" class="${_dtrTimeState.period === 'AM' ? 'is-active' : ''}" data-dtr-period="AM">AM</button><button type="button" class="${_dtrTimeState.period === 'PM' ? 'is-active' : ''}" data-dtr-period="PM">PM</button></div></div><button type="button" class="btn btn-primary dtr-time-set" data-dtr-set-time>Set Time</button>`;
}

function openDtrTimePicker(context) {
    closeDtrTimePicker();
    _dtrTimeContext = context;
    _dtrTimeState = parseDtrTimeValue(context.input.value);
    const panel = getDtrTimePanel();
    renderDtrTimePanel();
    const rect = context.trigger.getBoundingClientRect();
    const viewportPadding = 10;
    const width = Math.min(320, window.innerWidth - (viewportPadding * 2));
    const panelHeight = Math.min(panel.getBoundingClientRect().height || 380, window.innerHeight - (viewportPadding * 2));
    const belowTop = rect.bottom + 8;
    const aboveTop = rect.top - panelHeight - 8;
    const hasRoomBelow = belowTop + panelHeight <= window.innerHeight - viewportPadding;
    panel.style.width = `${width}px`;
    panel.style.left = `${Math.max(viewportPadding, Math.min(rect.left, window.innerWidth - width - viewportPadding))}px`;
    panel.style.top = `${Math.max(viewportPadding, hasRoomBelow ? belowTop : aboveTop)}px`;
    panel.hidden = false;
    requestAnimationFrame(() => panel.classList.add('is-open'));
}

function closeDtrTimePicker() {
    if (!_dtrTimePanel) return;
    _dtrTimePanel.classList.remove('is-open');
    _dtrTimePanel.hidden = true;
    _dtrTimeContext = null;
    _dtrTimeState = null;
}

function initDtrTimeLocks() {
    document.querySelectorAll('[data-dtr-lock-flow]').forEach(form => {
        const groups = [...form.querySelectorAll('[data-time-lock-group]')].map(group => ({
            group,
            input: group.querySelector('[data-lockable-time]'),
            trigger: group.querySelector('[data-time-picker-trigger]'),
            display: group.querySelector('[data-time-display]'),
            button: group.querySelector('[data-time-lock-toggle]'),
            locked: group.dataset.savedLocked === '1' && !!group.querySelector('[data-lockable-time]')?.value,
        })).filter(item => item.input && item.button && item.trigger && item.display);
        const tasks = form.querySelector('[data-dtr-tasks]');
        const submit = form.querySelector('[data-dtr-submit]');
        if (!groups.length || !tasks || !submit) return;

        const saveDraft = () => {
            const body = new URLSearchParams();
            body.set('csrf_token', form.querySelector('input[name="csrf_token"]')?.value || '');
            body.set('action', 'student_save_dtr_draft');
            body.set('work_date', form.querySelector('input[name="work_date"]')?.value || '');
            body.set('time_in', groups[0]?.input.value || '');
            body.set('time_out', groups[1]?.input.value || '');
            body.set('time_in_locked', groups[0]?.locked ? '1' : '0');
            body.set('time_out_locked', groups[1]?.locked ? '1' : '0');
            return fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body,
            }).catch(() => null);
        };

        const sync = () => {
            const timeInLocked = groups[0]?.locked || false;
            const timeOutLocked = groups[1]?.locked || false;

            groups.forEach((item, index) => {
                const mustWait = index === 1 && !timeInLocked;
                item.input.disabled = false;
                item.trigger.disabled = false;
                item.button.disabled = false;
                item.trigger.setAttribute('aria-disabled', String(mustWait || item.locked));
                item.button.setAttribute('aria-disabled', String(mustWait));
                item.display.textContent = formatDtrTimeDisplay(item.input.value);
                item.group.classList.toggle('is-locked', item.locked);
                item.group.classList.toggle('is-waiting', mustWait);
                item.group.classList.toggle('has-time', !!item.input.value);
                item.button.textContent = item.locked ? item.button.dataset.editLabel : item.button.dataset.applyLabel;
            });

            tasks.disabled = !timeOutLocked;
            submit.disabled = !timeOutLocked;
            form.classList.toggle('dtr-ready-for-tasks', timeOutLocked);
        };

        const unlockFrom = startIndex => {
            groups.slice(startIndex).forEach(item => {
                item.locked = false;
                item.group.classList.remove('is-locked');
            });
        };

        groups.forEach((item, index) => {
            let lastTap = 0;
            const runOnce = (event, handler) => {
                const now = Date.now();
                if (event.type !== 'keydown' && now - lastTap < 350) return;
                lastTap = now;
                event.preventDefault();
                event.stopPropagation();
                handler();
            };

            const openPicker = () => {
                if (item.trigger.getAttribute('aria-disabled') === 'true') return;
                openDtrTimePicker({ input: item.input, trigger: item.trigger, saveButton: item.button, sync });
            };

            const toggleLock = () => {
                sync();
                if (item.button.getAttribute('aria-disabled') === 'true') return;
                if (item.locked) {
                    unlockFrom(index);
                    if (index === 0) {
                        groups[1].input.value = '';
                        tasks.value = '';
                        tasks.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    sync();
                    saveDraft();
                    item.trigger.focus();
                    return;
                }
                if (!item.input.value) {
                    item.group.classList.add('needs-time');
                    openPicker();
                    return;
                }
                item.group.classList.remove('needs-time');
                item.locked = true;
                sync();
                saveDraft();
                const nextInput = groups[index + 1]?.input || tasks;
                const nextTrigger = groups[index + 1]?.trigger || null;
                (nextTrigger || nextInput)?.focus();
            };

            item.input.addEventListener('input', sync);
            item.input.addEventListener('change', sync);
            item.input.addEventListener('blur', sync);
            item.input.addEventListener('keyup', sync);
            ['pointerup', 'touchend', 'click'].forEach(eventName => {
                item.trigger.addEventListener(eventName, event => runOnce(event, openPicker), { passive: false });
                item.button.addEventListener(eventName, event => runOnce(event, toggleLock), { passive: false });
            });
            item.trigger.addEventListener('keydown', event => {
                if (!['Enter', ' '].includes(event.key)) return;
                runOnce(event, openPicker);
            });
            item.button.addEventListener('keydown', event => {
                if (!['Enter', ' '].includes(event.key)) return;
                runOnce(event, toggleLock);
            });
        });

        sync();
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
            const reqDatePickers = [...form.querySelectorAll('.filter-date-picker[data-date-required]')];
            const missingDates = reqDatePickers.filter(p => !p.querySelector('input[type="hidden"]')?.value);
            missingDates.forEach(p => p.classList.add('date-required-error'));
            if (!hasValidCheckboxGroup || !form.checkValidity() || missingDates.length) {
                e.preventDefault();
                markTouched();
                if (!missingDates.length) form.reportValidity();
                return;
            }

            if (form.dataset.confirmSubmit && form.dataset.confirmedSubmit !== '1') {
                e.preventDefault();
                const btn = e.submitter || submitButtons[0] || null;
                showConfirmModal(form.dataset.confirmSubmit, {
                    title: form.dataset.confirmTitle || 'Confirm submission',
                    confirmText: form.dataset.confirmOk || 'Submit',
                    cancelText: form.dataset.confirmCancel || 'Review again',
                }).then(confirmed => {
                    if (!confirmed) return;
                    form.dataset.confirmedSubmit = '1';
                    if (btn && typeof form.requestSubmit === 'function') {
                        form.requestSubmit(btn);
                    } else if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
                return;
            }
            delete form.dataset.confirmedSubmit;

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
            const studentSelect = form.querySelector('[name="student_id"]');
            const selectedStudent = studentSelect?.selectedOptions?.[0];
            if (selectedStudent?.dataset.isEnrolled === '1') {
                showAlertModal('This student is already enrolled. Please try again.', {
                    title: 'Student already enrolled',
                    confirmText: 'OK'
                });
                studentSelect.value = '';
                updateWizardSummary(form);
                return;
            }
            const fields = [...panels[index].querySelectorAll('input,select,textarea')];
            const reqDatePickers = [...panels[index].querySelectorAll('.filter-date-picker[data-date-required]')];
            const missingDates = reqDatePickers.filter(p => !p.querySelector('input[type="hidden"]')?.value);
            if (fields.some(field => !field.checkValidity()) || missingDates.length) {
                fields.forEach(field => field.classList.add('touched'));
                missingDates.forEach(p => p.classList.add('date-required-error'));
                return;
            }
            reqDatePickers.forEach(p => p.classList.remove('date-required-error'));
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
    const start = form.querySelector('[name="term_start_date"]')?.value || '-';
    const end = form.querySelector('[name="term_end_date"]')?.value || '-';
    const hours = form.querySelector('[name="required_hours"]')?.value || '-';
    box.innerHTML = `
        <h3><span class="confirm-icon"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></span>Confirm Enrollment</h3>
        <div class="confirm-grid">
            <div class="confirm-row"><span class="confirm-label">Student</span><span class="confirm-value">${escapeHtml(student)}</span></div>
            <div class="confirm-row"><span class="confirm-label">Company</span><span class="confirm-value">${escapeHtml(company)}</span></div>
            <div class="confirm-row"><span class="confirm-label">Schedule</span><span class="confirm-value">${escapeHtml(start)} to ${escapeHtml(end)}</span></div>
            <div class="confirm-row"><span class="confirm-label">Required Hours</span><span class="confirm-value">${escapeHtml(hours)}</span></div>
        </div>
        <div class="confirm-note"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg><span>Submitting will send the student enrollment and company deployment emails.</span></div>`;
}

function initEnrollmentAutomation() {
    document.querySelectorAll('form [name="student_id"]').forEach(studentSelect => {
        const form = studentSelect.closest('form');
        const companySelect = form?.querySelector('[name="company_id"]');
        const hoursInput = form?.querySelector('[name="required_hours"]');
        const companyDocPreview = form?.querySelector('[data-company-doc-preview]');
        const companyDocLink = form?.querySelector('[data-company-doc-link]');
        if (!form || !companySelect || !hoursInput) return;
        const clearSelection = () => {
            studentSelect.value = '';
            hoursInput.value = '';
        };
        const resetCompanies = () => {
            [...companySelect.options].forEach(option => {
                option.hidden = false;
                option.disabled = false;
            });
            companySelect.value = '';
            companySelect._syncCustomSelect?.();
        };
        const sync = () => {
            const selected = studentSelect.selectedOptions[0];
            if (selected?.dataset.isEnrolled === '1') {
                clearSelection();
                resetCompanies();
                syncCompanyDocument();
                updateWizardSummary(form);
                showAlertModal('This student is already enrolled. Please try again.', {
                    title: 'Student already enrolled',
                    confirmText: 'OK'
                });
                return;
            }
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
            companySelect._syncCustomSelect?.();
            updateWizardSummary(form);
        };
        const syncCompanyDocument = () => {
            if (!companyDocPreview || !companyDocLink) return;
            const selectedCompany = companySelect.selectedOptions[0];
            const docUrl = selectedCompany?.dataset.moaMou || '';
            if (docUrl) {
                companyDocLink.href = docUrl;
                companyDocPreview.hidden = false;
            } else {
                companyDocLink.href = '#';
                companyDocPreview.hidden = true;
            }
        };
        studentSelect.addEventListener('change', sync);
        companySelect.addEventListener('change', () => {
            updateWizardSummary(form);
            syncCompanyDocument();
        });
        if (studentSelect.selectedOptions[0]?.dataset.isEnrolled === '1') {
            clearSelection();
            resetCompanies();
            syncCompanyDocument();
            updateWizardSummary(form);
            return;
        }
        sync();
        syncCompanyDocument();
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
    if (table.hasAttribute('data-no-enhance')) return;
    const card = table.closest('.card');
    const search = card?.querySelector('.table-search');
    const tbody = table.tBodies[0];
    if (!tbody) return;
    if (!table.hasAttribute('data-no-tools')) {
        addTableTools(table);
    }
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
    const exportPdf = table.dataset.export === 'pdf';
    const exportBtn = exportPdf
        ? '<button class="btn btn-small export-pdf" type="button">Export PDF</button>'
        : '<button class="btn btn-small export-csv" type="button">Export CSV</button>';
    tools.innerHTML = exportBtn + '<div class="column-menu"><button class="btn btn-small column-toggle" type="button">Columns</button><div class="column-options"></div></div>';
    wrap.insertAdjacentElement('beforebegin', tools);
    if (exportPdf) {
        tools.querySelector('.export-pdf').addEventListener('click', () => {
            const url = table.dataset.exportUrl;
            if (url) window.open(url, '_blank');
        });
    } else {
        tools.querySelector('.export-csv').addEventListener('click', () => exportCsv(table));
    }
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
    if (!event.target.closest('.filter-date-picker') && !event.target.closest('.global-cal-panel')) closeCustomDatePickers();
    if (!event.target.closest('.form-datetime-picker') && !event.target.closest('.global-datetime-panel')) closeGlobalDtPanel();
    if (!event.target.closest('.dtr-time-panel') && !event.target.closest('[data-time-picker-trigger]')) closeDtrTimePicker();
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

    document.querySelectorAll('.js-review-form').forEach(form => {
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            if (!btn || btn.disabled) return;
            const status = form.dataset.reviewStatus;
            if (status === 'rejected') {
                const note = form.querySelector('.requirement-review-note');
                if (note && !note.value.trim()) {
                    note.focus();
                    note.classList.add('touched');
                    return;
                }
            }
            const origText = btn.textContent;
            btn.disabled = true;
            btn.textContent = status === 'approved' ? 'Approving...' : 'Rejecting...';
            const allBtns = form.closest('[data-review-actions]')?.querySelectorAll('button');
            allBtns?.forEach(b => b.disabled = true);
            try {
                const resp = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                });
                const data = await resp.json();
                if (!data.ok) throw new Error(data.message || 'Review failed.');
                const modal = form.closest('.requirement-review-modal');
                const article = form.closest('.requirement-review-item');
                if (article) {
                    article.className = `requirement-review-item status-${data.requirement_status}`;
                    const badge = article.querySelector('[data-req-status-badge]');
                    if (badge) {
                        badge.className = `badge ${data.requirement_status}`;
                        badge.textContent = data.requirement_status;
                    }
                    const subtitle = article.querySelector('.requirement-review-head small');
                    if (subtitle) {
                        subtitle.textContent = data.requirement_status === 'approved'
                            ? 'Reviewed and approved'
                            : 'Rejected — needs revision';
                    }
                    const actions = article.querySelector('[data-review-actions]');
                    if (actions) {
                        const isApproved = data.requirement_status === 'approved';
                        const rejectionNote = form.querySelector('.requirement-review-note')?.value || '';
                        const icon = isApproved
                            ? '<svg viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>'
                            : '<svg viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/></svg>';
                        const label = isApproved ? 'Approved successfully' : 'Rejected';
                        const noteHtml = !isApproved && rejectionNote
                            ? `<span class="result-note">${escapeHtml(rejectionNote)}</span>`
                            : '';
                        actions.innerHTML = `<div class="requirement-review-result result-${data.requirement_status}">${icon}<span>${label}${noteHtml}</span></div>`;
                    }
                }
                if (modal) {
                    const modalBadge = modal.querySelector('[data-modal-status-badge]');
                    if (modalBadge) {
                        const ps = data.predeployment_status;
                        modalBadge.className = `badge ${ps}`;
                        modalBadge.textContent = ps.replace(/_/g, ' ');
                    }
                    const forwardBox = modal.querySelector('[data-forward-box]');
                    if (forwardBox) {
                        forwardBox.style.display = data.predeployment_status === 'approved' ? '' : 'none';
                    }
                }
                const studentId = modal?.dataset.studentId;
                if (studentId) {
                    document.querySelectorAll('tr .student-predeployment-cell').forEach(cell => {
                        const reviewBtn = cell.querySelector(`[data-review-modal="reviewModal-${studentId}"]`);
                        if (!reviewBtn) return;
                        const badge = cell.querySelector('.badge');
                        if (badge) {
                            badge.className = `badge ${data.predeployment_status}`;
                            badge.textContent = data.predeployment_status.replace(/_/g, ' ');
                        }
                    });
                }
            } catch (err) {
                allBtns?.forEach(b => b.disabled = false);
                btn.textContent = origText;
                showAlertModal(err.message || 'Something went wrong. Please try again.', {
                    title: 'Review failed',
                    confirmText: 'OK',
                });
            }
        });
    });
}

function initStudentModal() {
    const modal = document.getElementById('studentModal');
    if (!modal) return;

    const formatDateTime = value => {
        if (!value) return '—';
        const normalized = String(value).replace(' ', 'T');
        const date = new Date(normalized);
        return Number.isNaN(date.getTime()) ? value : date.toLocaleString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    };

    const formatDate = value => {
        if (!value) return '—';
        const date = new Date(`${value}T00:00:00`);
        return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    };

    // Close handlers
    document.getElementById('studentModalClose')?.addEventListener('click', closeStudentModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeStudentModal(); });

    // Open handler — event delegation on table body
    document.addEventListener('click', e => {
        const btn = e.target.closest('.student-view-btn');
        if (!btn) return;

        closeRequirementReviewModals();

        const d = btn.dataset;
        const photoEl = document.getElementById('sm-photo');
        const initialEl = document.getElementById('sm-initial');
        const avatarWrap = document.getElementById('sm-avatar-wrap');
        const photoUrl = (d.photoUrl || '').trim();
        if (photoEl && initialEl && avatarWrap) {
            if (photoUrl) {
                photoEl.src = photoUrl;
                photoEl.alt = `${d.name || 'Student'} profile photo`;
                photoEl.classList.remove('is-hidden');
                initialEl.classList.add('is-hidden');
                avatarWrap.classList.add('has-photo');
            } else {
                photoEl.classList.add('is-hidden');
                photoEl.removeAttribute('src');
                initialEl.textContent = d.initial || (d.name || 'S').charAt(0).toUpperCase();
                initialEl.classList.remove('is-hidden');
                avatarWrap.classList.remove('has-photo');
            }
        }
        document.getElementById('sm-name').textContent = d.name || '';
        document.getElementById('sm-email').textContent = d.email || '';
        document.getElementById('sm-chip-id').textContent = d.studentNo ? `ID ${d.studentNo}` : 'ID —';
        document.getElementById('sm-chip-year').textContent = d.yearLevel ? `${d.yearLevel}` : 'Year —';
        const statusChip = document.getElementById('sm-chip-status');
        statusChip.textContent = formatLabel(d.status || 'pending');
        statusChip.className = `student-panel-chip student-panel-chip-status is-${(d.status || 'pending').replaceAll('_', '-')}`;

        document.getElementById('sm-course').textContent = d.course || '—';
        document.getElementById('sm-year-level').textContent = d.yearLevel || '—';
        const bdRaw = d.birthdate || '';
        document.getElementById('sm-birthdate').textContent = bdRaw ? new Date(bdRaw + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '—';
        document.getElementById('sm-company').textContent = d.company || '—';

        const percent = Math.max(0, Math.min(100, Number.parseInt(d.percent, 10) || 0));
        document.getElementById('sm-progress-text').textContent = `${d.rendered} / ${d.required} hrs (${percent}%)`;
        const progressBar = document.getElementById('sm-progress-bar');
        if (progressBar) progressBar.style.width = `${percent}%`;

        const predeployEl = document.getElementById('sm-predeployment');
        const predeployKey = String(d.predeploymentStatus || 'not submitted').toLowerCase().replaceAll(' ', '_');
        predeployEl.innerHTML = `<span class="coord-status-pill ${escapeHtml(predeployKey)}">${escapeHtml(formatLabel(d.predeploymentStatus || 'Not submitted'))}</span>`;

        document.getElementById('sm-orientation-datetime').textContent = formatDateTime(d.orientationDatetime || '');
        document.getElementById('sm-official-start').textContent = formatDate(d.officialStartDate || '');
        document.getElementById('sm-projected-end').textContent = formatDate(d.projectedEndDate || '');
        document.getElementById('sm-orientation-notes').textContent = d.orientationNotes || 'No orientation instructions recorded yet.';

        const finalLink = document.getElementById('sm-final-link');
        if (finalLink && d.finalUrl) finalLink.href = d.finalUrl;

        const corLink = document.getElementById('sm-cor-link');
        if (corLink) {
            if (d.cor && d.cor.trim() !== '') {
                corLink.href = d.cor;
                corLink.classList.remove('is-hidden');
            } else {
                corLink.classList.add('is-hidden');
            }
        }

        const moaLink = document.getElementById('sm-moa-link');
        if (moaLink) {
            if (d.moaMou && d.moaMou.trim() !== '') {
                moaLink.href = d.moaMou;
                moaLink.classList.remove('is-hidden');
            } else {
                moaLink.classList.add('is-hidden');
            }
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

    const params = new URLSearchParams(window.location.search);
    const focusStudent = params.get('focus_student');
    if (focusStudent) {
        const escapedFocusStudent = window.CSS?.escape ? CSS.escape(focusStudent) : focusStudent.replace(/"/g, '\\"');
        const targetButton = document.querySelector(`.student-view-btn[data-student-id="${escapedFocusStudent}"]`);
        if (targetButton) {
            targetButton.closest('tr')?.classList.add('is-selected-row');
            setTimeout(() => targetButton.click(), 120);
        }
    }
}

function initEnrollmentDirectory() {
    document.querySelectorAll('[data-enrollment-directory]').forEach(directory => {
        const table = directory.querySelector('[data-enrollment-directory-table]');
        const wizardForm = document.querySelector('[data-wizard]');
        const studentSelect = wizardForm?.querySelector('[name="student_id"]');
        if (!table || !studentSelect) return;

        table.tBodies[0]?.addEventListener('click', event => {
            const row = event.target.closest('.enrollment-directory-row');
            if (!row) return;

            const isEnrolled = row.dataset.studentEnrolled === '1';
            const studentId = row.dataset.studentId || '';
            document.querySelectorAll('.enrollment-directory-row.is-selected-row').forEach(activeRow => activeRow.classList.remove('is-selected-row'));
            row.classList.add('is-selected-row');

            if (isEnrolled) {
                showAlertModal('This student is already enrolled. Please try again.', {
                    title: 'Student already enrolled',
                    confirmText: 'OK'
                });
                return;
            }

            studentSelect.value = studentId;
            studentSelect.dispatchEvent(new Event('change', { bubbles: true }));
            wizardForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}

function initWeeklyReportUpload() {
    const form = document.getElementById('weeklyReportForm');
    if (!form) return;

    const dropzone = document.getElementById('wrDropzone');
    const fileInput = document.getElementById('wrFileInput');
    const browseLink = document.getElementById('wrBrowseLink');
    const previewRow = document.getElementById('wrPreviewRow');
    if (!dropzone || !fileInput || !browseLink || !previewRow) return;
    const textarea = form.querySelector('textarea[name="accomplishments"]');
    const charCurrent = form.querySelector('[data-char-current]');
    const MAX_SIZE = 10 * 1024 * 1024;
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];
    const ALLOWED_EXTS = ['.jpg', '.jpeg', '.png', '.pdf'];
    let proofFiles = [];

    if (textarea && charCurrent) {
        textarea.addEventListener('input', () => {
            charCurrent.textContent = textarea.value.length;
        });
    }

    browseLink.addEventListener('click', () => fileInput.click());
    dropzone.addEventListener('click', e => {
        if (e.target.closest('#wrBrowseLink')) return;
        fileInput.click();
    });

    dropzone.addEventListener('dragover', e => {
        e.preventDefault();
        dropzone.classList.add('is-dragover');
    });
    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('is-dragover');
    });
    dropzone.addEventListener('drop', e => {
        e.preventDefault();
        dropzone.classList.remove('is-dragover');
        addFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', () => {
        addFiles(fileInput.files);
        fileInput.value = '';
    });

    function addFiles(fileList) {
        for (const file of fileList) {
            const ext = '.' + file.name.split('.').pop().toLowerCase();
            if (!ALLOWED_TYPES.includes(file.type) && !ALLOWED_EXTS.includes(ext)) {
                alert(file.name + ' is not a supported file type. Please upload JPG, PNG, or PDF files.');
                continue;
            }
            if (file.size > MAX_SIZE) {
                alert(file.name + ' exceeds the 10MB file size limit.');
                continue;
            }
            proofFiles.push(file);
            renderPreview(file, proofFiles.length - 1);
        }
    }

    function renderPreview(file, index) {
        const item = document.createElement('div');
        item.className = 'wr-preview-item';
        item.dataset.index = index;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'wr-preview-remove';
        removeBtn.innerHTML = '&times;';
        removeBtn.addEventListener('click', () => removeFile(index));
        item.appendChild(removeBtn);

        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.alt = file.name;
            const reader = new FileReader();
            reader.onload = e => { img.src = e.target.result; };
            reader.readAsDataURL(file);
            item.appendChild(img);
        } else {
            const icon = document.createElement('div');
            icon.className = 'wr-file-icon';
            icon.innerHTML = '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M6 2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6H6Zm7 1.5L18.5 9H13V3.5ZM8 12h8v2H8v-2Zm0 4h5v2H8v-2Z"/></svg>'
                + '<span>' + (file.name.length > 12 ? file.name.slice(0, 10) + '...' : file.name) + '</span>';
            item.appendChild(icon);
        }

        previewRow.appendChild(item);
    }

    function removeFile(index) {
        proofFiles[index] = null;
        const el = previewRow.querySelector('[data-index="' + index + '"]');
        if (el) el.remove();
    }

    form.addEventListener('submit', e => {
        const activeFiles = proofFiles.filter(f => f !== null);
        if (activeFiles.length > 0) {
            const dt = new DataTransfer();
            activeFiles.forEach(f => dt.items.add(f));
            const dynamicInput = document.createElement('input');
            dynamicInput.type = 'file';
            dynamicInput.name = 'proof_files[]';
            dynamicInput.multiple = true;
            dynamicInput.hidden = true;
            dynamicInput.files = dt.files;
            form.appendChild(dynamicInput);
        }
    });
}