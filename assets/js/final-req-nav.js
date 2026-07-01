document.addEventListener('DOMContentLoaded', () => {
    initFinalReqNav();
});

function initFinalReqNav() {
    const shell = document.querySelector('.js-final-req-shell');
    if (!shell) return;

    const listView = shell.querySelector('[data-final-view="list"]');
    const formView = shell.querySelector('[data-final-view="form"]');
    const panels = shell.querySelectorAll('[data-final-panel]');
    const backLink = shell.querySelector('.js-final-req-back');
    const formWrap = shell.querySelector('.js-final-req-form-wrap');
    const stage = shell.querySelector('.final-req-stage');
    const pageTitle = shell.dataset.pageTitle || 'Final Requirements';
    const baseUrl = shell.dataset.baseUrl || 'index.php?r=student_documents_final';
    const panelTitles = parsePanelTitles(shell.dataset.panelTitles);
    const widePanels = new Set((shell.dataset.widePanels || '').split(',').map(v => v.trim()).filter(Boolean));
    const topbarTitle = document.querySelector('.topbar-copy h1');

    let activePanel = shell.dataset.activePanel || '';
    let activeKind = shell.dataset.activeKind || '';
    let animating = false;

    shell.querySelectorAll('.js-final-req-open').forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            const panel = link.dataset.finalPanel;
            const kind = link.dataset.finalKind;
            if (!panel || !kind) return;
            link.classList.add('is-opening');
            window.setTimeout(() => link.classList.remove('is-opening'), 220);
            openPanel(panel, kind);
        });
    });

    backLink?.addEventListener('click', event => {
        event.preventDefault();
        backLink.classList.add('is-pressed');
        window.setTimeout(() => backLink.classList.remove('is-pressed'), 320);
        closePanel();
    });

    window.addEventListener('popstate', event => {
        const state = event.state || {};
        if (state.panel && state.kind) {
            openPanel(state.panel, state.kind, { push: false, direction: 'forward' });
        } else {
            closePanel({ push: false, direction: 'back' });
        }
    });

    history.replaceState(
        buildState(activePanel, activeKind),
        '',
        buildUrl(activePanel, activeKind)
    );

    function parsePanelTitles(raw) {
        if (!raw) return {};
        try {
            return JSON.parse(raw);
        } catch {
            return {};
        }
    }

    function buildState(panel, kind) {
        return panel && kind ? { panel, kind } : { panel: null, kind: null };
    }

    function buildUrl(panel, kind) {
        if (!panel || !kind) return baseUrl;
        const param = kind === 'doc' ? 'doc' : 'eval';
        return `${baseUrl}&${param}=${encodeURIComponent(panel)}`;
    }

    function wait(ms) {
        return new Promise(resolve => window.setTimeout(resolve, ms));
    }

    function setTitle(panel) {
        const title = panel ? (panelTitles[panel] || pageTitle) : pageTitle;
        if (topbarTitle) topbarTitle.textContent = title;
        document.title = `${title} - AMA Practicum System`;
    }

    function resetPanelForms(panelKey) {
        if (!panelKey) return;

        const panelEl = shell.querySelector(`.final-req-panel[data-final-panel="${panelKey}"]`);
        if (!panelEl) return;

        panelEl.querySelectorAll('form').forEach(form => {
            form.reset();
            delete form.dataset.confirmedSubmit;
            form.querySelectorAll('.touched').forEach(el => el.classList.remove('touched'));
            form.querySelectorAll('textarea, input:not([type="hidden"])').forEach(el => {
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    }

    function showPanel(panel) {
        panels.forEach(item => {
            item.classList.toggle('is-active', item.dataset.finalPanel === panel);
        });
        if (formWrap) {
            formWrap.classList.toggle('final-form-page--wide', widePanels.has(panel));
        }
        activePanel = panel;
        activeKind = panel ? activeKind : '';
        shell.dataset.activePanel = panel;
        setTitle(panel);
    }

    async function switchView(fromView, toView, direction) {
        if (!fromView || !toView || animating) return;
        animating = true;

        if (stage) {
            stage.style.setProperty('--final-req-stage-height', `${Math.max(fromView.offsetHeight, toView.offsetHeight, 320)}px`);
            stage.classList.add('is-transitioning');
        }

        fromView.classList.add(direction === 'back' ? 'is-leaving-back' : 'is-leaving');
        await wait(220);

        fromView.classList.remove('is-active', 'is-leaving', 'is-leaving-back');
        toView.classList.add('is-active', direction === 'back' ? 'is-entering-back' : 'is-entering');
        await wait(20);
        toView.classList.remove('is-entering', 'is-entering-back');

        stage?.classList.remove('is-transitioning');
        stage?.style.removeProperty('--final-req-stage-height');
        animating = false;
    }

    async function openPanel(panel, kind, { push = true, direction = 'forward' } = {}) {
        if (!panel || !kind || !formView || !listView) return;
        if (activePanel === panel && formView.classList.contains('is-active') && !animating) return;

        const previousPanel = activePanel;
        activeKind = kind;
        shell.dataset.activeKind = kind;
        showPanel(panel);

        if (listView.classList.contains('is-active')) {
            await switchView(listView, formView, direction);
        } else {
            if (previousPanel && previousPanel !== panel) {
                resetPanelForms(previousPanel);
            }
            listView.classList.remove('is-active');
            formView.classList.add('is-active');
        }

        if (push) {
            history.pushState(buildState(panel, kind), '', buildUrl(panel, kind));
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async function closePanel({ push = true, direction = 'back' } = {}) {
        if (!formView || !listView || !formView.classList.contains('is-active')) return;

        resetPanelForms(activePanel);

        activePanel = '';
        activeKind = '';
        shell.dataset.activePanel = '';
        shell.dataset.activeKind = '';
        setTitle('');

        await switchView(formView, listView, direction);

        if (push) {
            history.pushState(buildState('', ''), '', baseUrl);
        }
    }
}
