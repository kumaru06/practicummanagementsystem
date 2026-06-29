document.addEventListener('DOMContentLoaded', () => {
    initPortalGate();
    if (document.querySelector('.js-portal-shell')) {
        initPortalLogin();
    }
});

function initPortalGate() {
    const gate = document.querySelector('.js-portal-gate');
    const host = document.querySelector('.js-portal-ajax-host');
    const button = document.querySelector('.js-portal-gate-open');
    if (!gate || !host || !button) return;

    const fetchUrl = button.dataset.portalFetch || 'auth.php?partial=portal';

    button.addEventListener('click', async () => {
        if (button.disabled) return;

        button.disabled = true;
        button.classList.add('is-loading');

        try {
            const response = await fetch(fetchUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Portal request failed');
            }

            const html = await response.text();
            host.innerHTML = html;
            gate.hidden = true;
            host.hidden = false;

            initPortalLogin();
            initAjaxPortalForms(host);
        } catch {
            button.disabled = false;
            window.alert('Unable to load the login portal. Please try again.');
        } finally {
            button.classList.remove('is-loading');
        }
    });
}

function initAjaxPortalForms(root) {
    if (typeof window.initFormValidation === 'function') {
        window.initFormValidation(root);
        return;
    }

    root.querySelectorAll('.js-validate').forEach(form => {
        if (form.dataset.validateBound === '1') return;
        form.dataset.validateBound = '1';
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
            }
        });
    });
}

function initPortalLogin() {
    const shell = document.querySelector('.js-portal-shell:not([data-portal-initialized])');
    if (!shell) return;

    shell.dataset.portalInitialized = '1';

    const selectView = shell.querySelector('[data-portal-view="select"]');
    const formView = shell.querySelector('[data-portal-view="form"]');
    const forms = shell.querySelectorAll('[data-portal-form]');
    const badgeLabel = shell.querySelector('.js-portal-badge-label');
    const backLink = shell.querySelector('.js-portal-back');
    const alertEl = shell.querySelector('.js-portal-alert');
    const stage = shell.querySelector('.portal-stage');
    const baseUrl = shell.dataset.portalBase || 'auth.php';
    const portalLabels = parsePortalLabels(shell.dataset.portals);

    let activePortal = shell.dataset.activePortal || '';
    let animating = false;

    shell.querySelectorAll('.js-portal-open').forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            const role = link.dataset.portal;
            if (!role) return;
            link.classList.add('is-opening');
            window.setTimeout(() => link.classList.remove('is-opening'), 220);
            openPortal(role);
        });
    });

    backLink?.addEventListener('click', event => {
        event.preventDefault();
        backLink.classList.add('is-pressed');
        window.setTimeout(() => backLink.classList.remove('is-pressed'), 320);
        closePortal();
    });

    if (!window.__portalPopstateBound) {
        window.addEventListener('popstate', event => {
            const currentShell = document.querySelector('.js-portal-shell');
            if (!currentShell || typeof currentShell.__openPortal !== 'function') return;

            const portal = event.state?.portal || null;
            if (portal) {
                currentShell.__openPortal(portal, { push: false, direction: 'forward' });
            } else {
                currentShell.__closePortal({ push: false, direction: 'back' });
            }
        });
        window.__portalPopstateBound = true;
    }

    history.replaceState(
        { portal: activePortal || null },
        '',
        activePortal ? `${baseUrl}?portal=${encodeURIComponent(activePortal)}` : baseUrl
    );

    function syncStageHeight() {
        if (!stage || !selectView || !formView) return;
        stage.style.minHeight = '';
        stage.style.removeProperty('--portal-stage-height');
    }

    syncStageHeight();
    window.addEventListener('resize', syncStageHeight);

    function parsePortalLabels(raw) {
        if (!raw) return {};
        try {
            return JSON.parse(raw);
        } catch {
            return {};
        }
    }

    function wait(ms) {
        return new Promise(resolve => window.setTimeout(resolve, ms));
    }

    function setTitle(role) {
        const label = portalLabels[role]?.label || 'Login';
        document.title = `${label} - AMA Practicum System`;
    }

    function showFormForRole(role) {
        forms.forEach(form => {
            form.classList.toggle('is-active', form.dataset.portalForm === role);
        });
        if (badgeLabel) {
            badgeLabel.textContent = portalLabels[role]?.label || role;
        }
        activePortal = role;
        shell.dataset.activePortal = role;
        setTitle(role);
    }

    async function switchView(fromView, toView, direction) {
        if (!fromView || !toView || animating) return;
        animating = true;

        if (stage) {
            syncStageHeight();
            stage.classList.add('is-transitioning');
        }

        fromView.classList.add(direction === 'back' ? 'is-leaving-back' : 'is-leaving');
        await wait(220);

        fromView.classList.remove('is-active', 'is-leaving', 'is-leaving-back');
        toView.classList.add('is-active', direction === 'back' ? 'is-entering-back' : 'is-entering');
        await wait(20);
        toView.classList.remove('is-entering', 'is-entering-back');

        stage?.classList.remove('is-transitioning');
        syncStageHeight();
        animating = false;
    }

    async function openPortal(role, { push = true, direction = 'forward' } = {}) {
        if (!role || !formView || !selectView) return;
        if (activePortal === role && formView.classList.contains('is-active') && !animating) return;

        showFormForRole(role);
        clearAlert();

        if (selectView.classList.contains('is-active')) {
            await switchView(selectView, formView, direction);
        } else {
            selectView.classList.remove('is-active');
            formView.classList.add('is-active');
        }

        if (push) {
            history.pushState({ portal: role }, '', `${baseUrl}?portal=${encodeURIComponent(role)}`);
        }

        const activeForm = shell.querySelector(`[data-portal-form="${role}"]`);
        const focusTarget = activeForm?.querySelector('input[name="email"], input[name="password"]');
        focusTarget?.focus({ preventScroll: true });
    }

    async function closePortal({ push = true, direction = 'back' } = {}) {
        if (!formView || !selectView || !formView.classList.contains('is-active')) return;

        activePortal = '';
        shell.dataset.activePortal = '';
        document.title = 'Choose Login Portal - AMA Practicum System';
        clearAlert();

        await switchView(formView, selectView, direction);

        if (push) {
            history.pushState({ portal: null }, '', baseUrl);
        }
    }

    function clearAlert() {
        if (!alertEl) return;
        alertEl.textContent = '';
        alertEl.classList.add('is-hidden');
    }

    shell.__openPortal = openPortal;
    shell.__closePortal = closePortal;
}

window.initPortalLogin = initPortalLogin;
