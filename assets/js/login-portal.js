(function preventLoginImageDrag() {
    document.addEventListener('dragstart', function (event) {
        const target = event.target;
        if (!(target instanceof HTMLImageElement)) return;
        if (!target.closest('.login-page')) return;
        event.preventDefault();
    }, true);

    function markLoginImagesUndraggable(root) {
        (root || document).querySelectorAll('.login-page img').forEach(function (img) {
            img.setAttribute('draggable', 'false');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            markLoginImagesUndraggable();
        });
    } else {
        markLoginImagesUndraggable();
    }

    window.markLoginImagesUndraggable = markLoginImagesUndraggable;
})();

function getLoginFormShell() {
    return document.querySelector('.js-login-form-shell') || document.querySelector('.login-form-shell');
}

function waitLoginShell(ms) {
    return new Promise(resolve => window.setTimeout(resolve, ms));
}

const loginPartialCache = new Map();

function prefetchLoginPartial(url) {
    const fetchUrl = String(url || '').trim();
    if (!fetchUrl || loginPartialCache.has(fetchUrl)) return;

    loginPartialCache.set(fetchUrl, fetch(fetchUrl, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'text/html',
        },
        credentials: 'same-origin',
    }).then(response => {
        if (!response.ok) {
            loginPartialCache.delete(fetchUrl);
            throw new Error('Partial request failed');
        }
        return response.text();
    }).catch(error => {
        loginPartialCache.delete(fetchUrl);
        throw error;
    }));
}

async function fetchLoginPartial(url) {
    const fetchUrl = String(url || '').trim();
    if (!fetchUrl) throw new Error('Missing partial URL');

    const cached = loginPartialCache.get(fetchUrl);
    if (cached) {
        loginPartialCache.delete(fetchUrl);
        return cached;
    }

    const html = await fetch(fetchUrl, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'text/html',
        },
        credentials: 'same-origin',
    }).then(response => {
        if (!response.ok) throw new Error('Partial request failed');
        return response.text();
    });

    return html;
}

function bindLoginPartialPrefetch(root, selector) {
    root.querySelectorAll(selector).forEach(el => {
        if (el.dataset.partialPrefetchBound === '1') return;
        el.dataset.partialPrefetchBound = '1';
        const warm = () => {
            const url = el.dataset.forgotFetch || el.dataset.portalFetch || '';
            if (url) prefetchLoginPartial(url);
        };
        el.addEventListener('mouseenter', warm, { passive: true });
        el.addEventListener('focus', warm, { passive: true });
        el.addEventListener('touchstart', warm, { passive: true });
    });
}

function buildForgotFetchUrl(role = '') {
    const params = new URLSearchParams({ partial: 'view' });
    if (role) params.set('role', role);
    return `forgot-password.php?${params.toString()}`;
}

function buildForgotPageUrl(role = '') {
    return role ? `forgot-password.php?role=${encodeURIComponent(role)}` : 'auth.php';
}

async function transitionLoginShell(shellHost, getHtml, onMounted, direction = 'forward') {
    const isBack = direction === 'back';
    const leaveClass = isBack ? 'is-shell-leaving-back' : 'is-shell-leaving';
    const enterClass = isBack ? 'is-shell-entering-back' : 'is-shell-entering';

    const html = await getHtml();

    shellHost.classList.add('is-shell-transitioning');

    const leavingCard = shellHost.querySelector('.portal-login-card, .forgot-password-card');
    if (leavingCard) {
        leavingCard.classList.add(leaveClass);
    }

    await waitLoginShell(180);

    shellHost.innerHTML = html;
    if (typeof window.markLoginImagesUndraggable === 'function') {
        window.markLoginImagesUndraggable(shellHost);
    }

    const enteringCard = shellHost.querySelector('.portal-login-card, .forgot-password-card');
    if (!enteringCard) {
        shellHost.classList.remove('is-shell-transitioning');
        requestAnimationFrame(() => onMounted?.());
        return html;
    }

    enteringCard.classList.add('is-ajax-swap', enterClass);
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            enteringCard.classList.remove(enterClass);
            shellHost.classList.remove('is-shell-transitioning');
            requestAnimationFrame(() => onMounted?.());
        });
    });

    return html;
}

function clearLoginShellTransition(shellHost) {
    shellHost?.classList.remove('is-shell-transitioning');
    shellHost?.querySelector('.portal-login-card, .forgot-password-card')
        ?.classList.remove('is-shell-leaving', 'is-shell-leaving-back', 'is-shell-entering', 'is-shell-entering-back');
}

document.addEventListener('DOMContentLoaded', () => {
    initPortalGate();
    initPortalForgotLinks(document);
    initForgotBackDelegation();
    if (document.querySelector('.js-portal-shell')) {
        initPortalLogin();
    }
    if (document.querySelector('.js-forgot-shell')) {
        initForgotPasswordShell(document);
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
            if (typeof window.markLoginImagesUndraggable === 'function') {
                window.markLoginImagesUndraggable(host);
            }
            gate.hidden = true;
            host.hidden = false;

            initPortalLogin();
            initAjaxPortalForms(host);
            initPortalForgotLinks(host);
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
    const forgotView = shell.querySelector('[data-portal-view="forgot"]');
    const forgotHost = shell.querySelector('.js-forgot-view-host');
    const forms = shell.querySelectorAll('[data-portal-form]');
    const badgeLabel = shell.querySelector('.js-portal-badge-label');
    const backLink = shell.querySelector('.js-portal-back');
    const alertEl = shell.querySelector('.js-portal-alert');
    const stage = shell.querySelector('.portal-stage');
    const baseUrl = shell.dataset.portalBase || 'auth.php';
    const portalLabels = parsePortalLabels(shell.dataset.portals);

    let activePortal = shell.dataset.activePortal || '';
    let animating = false;
    let forgotRole = activePortal || '';

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
            const standaloneForgot = document.querySelector('.js-forgot-shell:not(.js-portal-shell)');
            const currentShell = document.querySelector('.js-portal-shell');

            if (standaloneForgot) {
                if (event.state?.view === 'forgot') {
                    openForgotPassword(event.state.forgotUrl || '', { push: false });
                    return;
                }

                closeForgotPassword({
                    push: false,
                    portal: event.state?.portal || null,
                });
                return;
            }

            if (!currentShell) return;

            if (event.state?.view === 'forgot') {
                currentShell.__openForgot?.({
                    push: false,
                    role: event.state.portal || '',
                });
                return;
            }

            if (currentShell.querySelector('[data-portal-view="forgot"]')?.classList.contains('is-active')) {
                currentShell.__closeForgot?.({ push: false });
            }

            if (typeof currentShell.__openPortal !== 'function') return;

            const portal = event.state?.portal || null;
            if (portal) {
                currentShell.__openPortal(portal, { push: false, direction: 'forward' });
            } else if (!currentShell.querySelector('[data-portal-view="forgot"]')?.classList.contains('is-active')) {
                currentShell.__closePortal({ push: false, direction: 'back' });
            }
        });
        window.__portalPopstateBound = true;
    }

    history.replaceState(
        { portal: activePortal || null, view: null },
        '',
        activePortal ? `${baseUrl}?portal=${encodeURIComponent(activePortal)}` : baseUrl
    );

    function syncStageHeight() {
        if (!stage) return;
        stage.style.minHeight = '';
        stage.style.removeProperty('--portal-stage-height');
    }

    function getActivePortalView() {
        if (selectView?.classList.contains('is-active')) return selectView;
        if (formView?.classList.contains('is-active')) return formView;
        if (forgotView?.classList.contains('is-active')) return forgotView;
        return selectView;
    }

    async function loadForgotContent(role = '') {
        if (!forgotHost) return;

        const nextRole = role || activePortal || '';
        const fetchUrl = buildForgotFetchUrl(nextRole);
        if (!fetchUrl) throw new Error('Missing forgot partial URL');

        if (forgotHost.dataset.loaded === '1' && forgotHost.dataset.forgotRole === nextRole) {
            initForgotPasswordShell(forgotHost);
            return;
        }

        const html = await fetchLoginPartial(fetchUrl);
        forgotHost.innerHTML = html;
        if (typeof window.markLoginImagesUndraggable === 'function') {
            window.markLoginImagesUndraggable(forgotHost);
        }
        forgotHost.dataset.loaded = '1';
        forgotHost.dataset.forgotRole = nextRole;
        forgotHost.dataset.forgotFetch = fetchUrl;
        forgotRole = nextRole;
        initForgotPasswordShell(forgotHost);
    }

    syncStageHeight();
    window.addEventListener('resize', syncStageHeight);

    if (forgotHost?.dataset.loaded === '1') {
        initForgotPasswordShell(forgotHost);
    }

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
            history.pushState({ portal: role, view: null }, '', `${baseUrl}?portal=${encodeURIComponent(role)}`);
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
            history.pushState({ portal: null, view: null }, '', baseUrl);
        }
    }

    async function openForgotInPortal({ push = true, role = '' } = {}) {
        if (!forgotView) return;

        const targetRole = role || activePortal || '';
        if (!targetRole) return;

        try {
            await loadForgotContent(targetRole);

            document.title = 'Forgot Password - AMA Practicum System';

            const fromView = getActivePortalView();
            if (fromView === forgotView) return;

            if (!formView?.classList.contains('is-active')) {
                showFormForRole(targetRole);
                if (selectView?.classList.contains('is-active')) {
                    selectView.classList.remove('is-active');
                }
                formView?.classList.add('is-active');
            }

            await switchView(fromView, forgotView, 'forward');

            if (push) {
                history.pushState(
                    { view: 'forgot', portal: targetRole },
                    '',
                    buildForgotPageUrl(targetRole)
                );
            }
        } catch {
            window.alert('Unable to load the password recovery form. Please try again.');
        }
    }

    async function closeForgotInPortal({ push = true, portal = '' } = {}) {
        if (!forgotView || !forgotView.classList.contains('is-active')) return;

        const returnPortal = portal || forgotHost?.dataset.forgotRole || forgotRole || activePortal || '';
        const targetView = returnPortal && formView ? formView : selectView;
        if (!targetView) return;

        if (returnPortal && formView) {
            showFormForRole(returnPortal);
            document.title = `${portalLabels[returnPortal]?.label || 'Login'} - AMA Practicum System`;
        } else {
            document.title = 'Choose Login Portal - AMA Practicum System';
        }

        await switchView(forgotView, targetView, 'back');

        if (push) {
            if (returnPortal && formView) {
                history.pushState(
                    { portal: returnPortal, view: null },
                    '',
                    `${baseUrl}?portal=${encodeURIComponent(returnPortal)}`
                );
            } else {
                history.pushState({ portal: null, view: null }, '', baseUrl);
            }
        }
    }

    function clearAlert() {
        if (!alertEl) return;
        alertEl.textContent = '';
        alertEl.classList.add('is-hidden');
    }

    shell.__openPortal = openPortal;
    shell.__closePortal = closePortal;
    shell.__openForgot = openForgotInPortal;
    shell.__closeForgot = closeForgotInPortal;
}

function initForgotBackDelegation() {
    if (window.__forgotBackDelegated) return;
    window.__forgotBackDelegated = true;

    document.addEventListener('click', async event => {
        const back = event.target.closest('.js-forgot-back');
        if (!back) return;

        event.preventDefault();
        event.stopPropagation();

        const portalShell = back.closest('.js-portal-shell');
        if (portalShell?.__closeForgot && back.closest('[data-portal-view="forgot"], .js-forgot-view-host')) {
            back.classList.add('is-pressed');
            window.setTimeout(() => back.classList.remove('is-pressed'), 320);
            await portalShell.__closeForgot({
                portal: back.dataset.portalRole || '',
            });
            return;
        }

        closeForgotPassword({
            fetchUrl: back.dataset.portalFetch || back.closest('.js-forgot-shell')?.dataset.portalFetch || '',
        });
    });

    bindLoginPartialPrefetch(document, '.js-forgot-back');
}

function initPortalForgotLinks(root = document) {
    bindLoginPartialPrefetch(root, '.js-portal-forgot-open');

    root.querySelectorAll('.js-portal-forgot-open').forEach(link => {
        if (link.dataset.forgotBound === '1') return;
        link.dataset.forgotBound = '1';
        link.addEventListener('click', async event => {
            event.preventDefault();
            const fetchUrl = link.dataset.forgotFetch || link.getAttribute('href') || '';
            if (!fetchUrl) return;

            link.classList.add('is-opening');
            window.setTimeout(() => link.classList.remove('is-opening'), 220);

            const portalShell = link.closest('.js-portal-shell');
            const forgotRole = link.dataset.forgotRole || '';
            if (portalShell?.__openForgot) {
                await portalShell.__openForgot({ role: forgotRole });
                return;
            }

            openForgotPassword(fetchUrl);
        });
    });
}

async function openForgotPassword(fetchUrl, { push = true } = {}) {
    const shellHost = getLoginFormShell();
    if (!shellHost || !fetchUrl) return;

    try {
        await transitionLoginShell(shellHost, () => fetchLoginPartial(fetchUrl), () => {
            document.title = 'Forgot Password - AMA Practicum System';
            initForgotPasswordShell(shellHost);
        }, 'forward');

        if (push) {
            history.pushState({ view: 'forgot', forgotUrl: fetchUrl }, '', fetchUrl.split('?')[0]);
        }
    } catch {
        clearLoginShellTransition(shellHost);
        window.alert('Unable to load the password recovery form. Please try again.');
    }
}

async function closeForgotPassword({ push = true, portal = null, fetchUrl = '' } = {}) {
    const shellHost = getLoginFormShell();
    const forgotShell = document.querySelector('.js-forgot-shell');
    if (!shellHost || !forgotShell) return;

    const portalFetchUrl = fetchUrl
        || forgotShell.dataset.portalFetch
        || document.querySelector('[data-portal-fetch]')?.dataset.portalFetch
        || 'auth.php?partial=portal';

    try {
        await transitionLoginShell(shellHost, () => fetchLoginPartial(portalFetchUrl), () => {
            initPortalLogin();
            initAjaxPortalForms(shellHost);
            initPortalForgotLinks(shellHost);
        }, 'back');

        if (portal) {
            const shell = document.querySelector('.js-portal-shell');
            shell?.__openPortal(portal, { push: false, direction: 'back' });
        } else {
            document.title = 'Choose Login Portal - AMA Practicum System';
        }

        if (push) {
            const baseUrl = document.querySelector('.js-portal-shell')?.dataset.portalBase || 'auth.php';
            history.pushState({ view: null, portal: portal || null }, '', portal ? `${baseUrl}?portal=${encodeURIComponent(portal)}` : baseUrl);
        }
    } catch {
        window.location.href = portalFetchUrl.replace('?partial=portal', '') || 'auth.php';
    }
}

function initForgotPasswordShell(root = document) {
    const forgotShell = root.querySelector('.js-forgot-shell') || document.querySelector('.js-forgot-shell');
    if (forgotShell?.dataset.portalFetch) {
        prefetchLoginPartial(forgotShell.dataset.portalFetch);
    }

    if (typeof window.initLoginCustomSelects === 'function') {
        window.initLoginCustomSelects();
    }

    root.querySelectorAll('[data-forgot-password-form]').forEach(form => {
        if (form.dataset.forgotFormBound === '1') return;
        form.dataset.forgotFormBound = '1';

        if (form.closest('.js-forgot-view-host')) {
            form.dataset.forgotAjax = '1';
        }

        const roleSelect = form.querySelector('[data-forgot-role]');
        const hiddenRole = form.querySelector('input[type="hidden"][name="role"]');
        const label = form.querySelector('[data-forgot-identifier-label]');
        const labels = {
            student: 'USN (Student ID)',
            coordinator: 'Coordinator ID',
            partner: 'Partner ID',
        };
        const syncLabel = () => {
            if (!label) return;
            const roleValue = roleSelect?.value || hiddenRole?.value || form.dataset.forgotRoleFixed || '';
            label.textContent = labels[roleValue] || 'Account ID';
        };
        roleSelect?.addEventListener('change', syncLabel);
        syncLabel();

        if (form.dataset.forgotAjax !== '1') return;

        form.addEventListener('submit', async event => {
            event.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            const forgotHost = form.closest('.js-forgot-view-host');
            const shellHost = getLoginFormShell();
            if (!shellHost) return;

            submitBtn?.setAttribute('disabled', 'disabled');
            submitBtn?.classList.add('is-loading');

            try {
                const responseHtml = await fetch(form.action || '', {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'text/html',
                    },
                    credentials: 'same-origin',
                }).then(response => {
                    if (!response.ok) throw new Error('Submit failed');
                    return response.text();
                });

                if (forgotHost) {
                    forgotHost.innerHTML = responseHtml;
                    if (typeof window.markLoginImagesUndraggable === 'function') {
                        window.markLoginImagesUndraggable(forgotHost);
                    }
                    forgotHost.dataset.loaded = '1';
                    initForgotPasswordShell(forgotHost);
                    return;
                }

                await transitionLoginShell(shellHost, () => Promise.resolve(responseHtml), () => initForgotPasswordShell(shellHost), 'forward');
            } catch {
                clearLoginShellTransition(shellHost);
                window.alert('Unable to submit your reset request. Please try again.');
            } finally {
                submitBtn?.removeAttribute('disabled');
                submitBtn?.classList.remove('is-loading');
            }
        });
    });
}

window.initPortalLogin = initPortalLogin;
window.initPortalForgotLinks = initPortalForgotLinks;
window.initForgotPasswordShell = initForgotPasswordShell;
window.openForgotPassword = openForgotPassword;
window.closeForgotPassword = closeForgotPassword;
