<?php
    $finalRequirement = $finalRequirement ?? [];
    $formReadOnly = !empty($formReadOnly);
    $poFields = FinalRequirement::PERSONAL_OBSERVATION_FIELDS;
    $poTotal = count($poFields);
    $svgAttrs = 'class="final-form-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
    $poIcons = [
        'po_facilities' => '<svg ' . $svgAttrs . '><path d="M6 22V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v18"/><path d="M14 10h4a2 2 0 0 1 2 2v10"/><path d="M6 12h4"/><path d="M6 16h4"/><path d="M10 8h.01"/><path d="M10 12h.01"/><path d="M10 16h.01"/><path d="M18 14h.01"/><path d="M18 18h.01"/></svg>',
        'po_services' => '<svg ' . $svgAttrs . '><rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><path d="M12 12v.01"/></svg>',
        'po_employee' => '<svg ' . $svgAttrs . '><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'po_management' => '<svg ' . $svgAttrs . '><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/></svg>',
        'po_organization' => '<svg ' . $svgAttrs . '><rect x="9" y="2" width="6" height="6" rx="1"/><rect x="2" y="16" width="6" height="6" rx="1"/><rect x="16" y="16" width="6" height="6" rx="1"/><path d="M12 8v4"/><path d="M12 12H5v4"/><path d="M12 12h7v4"/></svg>',
        'po_recommendation' => '<svg ' . $svgAttrs . '><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
    ];
?>
<section class="card final-form-card po-card">
    <div class="final-form-head">
        <span class="final-form-icon final-form-icon--observation" aria-hidden="true">
            <svg class="final-form-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 20h9"/>
                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
            </svg>
        </span>
        <div class="final-form-head-copy">
            <h2>Personal Observations</h2>
            <p class="muted">Answer one section at a time based on your OJT experience.</p>
        </div>
    </div>

    <form
        method="post"
        class="form js-validate final-form js-po-wizard"
        data-po-readonly="<?= $formReadOnly ? '1' : '0' ?>"
        data-po-total="<?= (int)$poTotal ?>"
        novalidate
    >
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="student_save_final_personal_observation">

        <div class="po-wizard-progress" aria-live="polite">
            <div class="po-wizard-progress-meta">
                <strong data-po-step-label>Question 1 of <?= (int)$poTotal ?></strong>
                <span data-po-step-title></span>
            </div>
            <div class="po-wizard-bar" role="progressbar" aria-valuemin="1" aria-valuemax="<?= (int)$poTotal ?>" aria-valuenow="1">
                <span class="po-wizard-bar-fill" data-po-progress-fill></span>
            </div>
            <div class="po-wizard-dots" data-po-dots aria-hidden="true">
                <?php for ($i = 0; $i < $poTotal; $i++): ?>
                    <span class="po-wizard-dot<?= $i === 0 ? ' is-active' : '' ?>" data-po-dot="<?= (int)$i ?>"></span>
                <?php endfor; ?>
            </div>
        </div>

        <div class="po-field-list">
            <?php $stepIndex = 0; foreach ($poFields as $column => $field): ?>
                <div class="po-field<?= $stepIndex === 0 ? ' is-active' : '' ?>" data-po-step="<?= (int)$stepIndex ?>" data-po-title="<?= e($field[0]) ?>"<?= $stepIndex === 0 ? '' : ' hidden' ?>>
                    <div class="po-field-head">
                        <span class="po-field-icon"><?= $poIcons[$column] ?? '' ?></span>
                        <div class="po-field-copy">
                            <span class="po-field-eyebrow"><?= e($field[0]) ?></span>
                            <label class="po-field-label" for="<?= e($column) ?>">
                                <?= e($field[1]) ?> <span class="req-star">*</span>
                            </label>
                        </div>
                    </div>
                    <div class="po-field-input">
                        <textarea
                            id="<?= e($column) ?>"
                            name="<?= e($column) ?>"
                            <?= $formReadOnly ? 'readonly' : '' ?>
                            maxlength="2000"
                            rows="8"
                            placeholder="<?= e($field[2]) ?>"
                            data-po-input
                        ><?= e($finalRequirement[$column] ?? '') ?></textarea>
                        <p class="po-step-error" data-po-error hidden>Please fill in this section before continuing.</p>
                    </div>
                </div>
            <?php $stepIndex++; endforeach; ?>
        </div>

        <div class="po-wizard-nav">
            <button type="button" class="btn btn-small po-wizard-btn po-wizard-btn--prev" data-po-prev hidden>
                Previous
            </button>
            <div class="po-wizard-nav-spacer"></div>
            <?php if ($formReadOnly): ?>
                <button type="button" class="btn btn-small btn-primary po-wizard-btn" data-po-next>
                    Next question
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-small btn-primary po-wizard-btn" data-po-next>
                    Next question
                </button>
                <button type="submit" class="btn btn-primary final-form-submit po-wizard-btn" data-po-save hidden>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
                    <span>Save Personal Observations</span>
                </button>
            <?php endif; ?>
        </div>
    </form>
</section>
<script>
(() => {
    const form = document.querySelector('.js-po-wizard');
    if (!form) return;

    const readonly = form.dataset.poReadonly === '1';
    const total = Math.max(1, parseInt(form.dataset.poTotal || '1', 10));
    const steps = Array.from(form.querySelectorAll('[data-po-step]'));
    const prevBtn = form.querySelector('[data-po-prev]');
    const nextBtn = form.querySelector('[data-po-next]');
    const saveBtn = form.querySelector('[data-po-save]');
    const stepLabel = form.querySelector('[data-po-step-label]');
    const stepTitle = form.querySelector('[data-po-step-title]');
    const progressFill = form.querySelector('[data-po-progress-fill]');
    const progressBar = form.querySelector('.po-wizard-bar');
    const dots = Array.from(form.querySelectorAll('[data-po-dot]'));
    let index = 0;

    const currentStep = () => steps[index];
    const currentInput = () => currentStep()?.querySelector('[data-po-input]');
    const currentError = () => currentStep()?.querySelector('[data-po-error]');

    const showError = (show) => {
        const error = currentError();
        if (!error) return;
        error.hidden = !show;
        currentStep()?.classList.toggle('has-error', show);
    };

    const validateCurrent = () => {
        if (readonly) return true;
        const input = currentInput();
        const ok = !!(input && input.value.trim());
        showError(!ok);
        if (!ok && input) input.focus();
        return ok;
    };

    const render = () => {
        steps.forEach((step, i) => {
            const active = i === index;
            step.classList.toggle('is-active', active);
            if (active) {
                step.removeAttribute('hidden');
            } else {
                step.setAttribute('hidden', '');
            }
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === index);
            dot.classList.toggle('is-done', i < index);
        });
        if (stepLabel) stepLabel.textContent = `Question ${index + 1} of ${total}`;
        if (stepTitle) stepTitle.textContent = currentStep()?.dataset.poTitle || '';
        if (progressFill) progressFill.style.width = `${((index + 1) / total) * 100}%`;
        if (progressBar) progressBar.setAttribute('aria-valuenow', String(index + 1));
        if (prevBtn) prevBtn.hidden = index === 0;
        const last = index >= total - 1;
        if (nextBtn) nextBtn.hidden = last;
        if (saveBtn) saveBtn.hidden = !last || readonly;
        showError(false);
    };

    prevBtn?.addEventListener('click', () => {
        if (index <= 0) return;
        index -= 1;
        render();
    });

    nextBtn?.addEventListener('click', () => {
        if (!validateCurrent()) return;
        if (index >= total - 1) return;
        index += 1;
        render();
    });

    form.addEventListener('submit', (event) => {
        if (readonly) {
            event.preventDefault();
            return;
        }
        // Ensure every step is filled before save.
        for (let i = 0; i < steps.length; i += 1) {
            const input = steps[i].querySelector('[data-po-input]');
            if (!input || !input.value.trim()) {
                event.preventDefault();
                index = i;
                render();
                showError(true);
                input?.focus();
                return;
            }
        }
    });

    form.querySelectorAll('[data-po-input]').forEach((input) => {
        input.addEventListener('input', () => {
            if (input === currentInput()) showError(false);
        });
    });
    render();
})();
</script>
