<?php
    $finalRequirement = $finalRequirement ?? [];
    $formReadOnly = !empty($formReadOnly);
?>
<section class="card final-form-card">
    <div class="final-form-head">
        <span class="final-form-icon final-form-icon--job" aria-hidden="true">
            <svg class="final-form-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                <rect width="20" height="14" x="2" y="6" rx="2"/>
            </svg>
        </span>
        <div class="final-form-head-copy">
            <h2>Job Description</h2>
            <p class="muted">Provide a detailed description of duties and responsibilities.</p>
        </div>
    </div>

    <form method="post" class="form js-validate final-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="student_save_final_job_description">

        <div class="final-form-field">
            <label class="final-form-label" for="job-position-held">Position Held <span class="req-star">*</span></label>
            <input id="job-position-held" type="text" name="position_held" <?= $formReadOnly ? 'readonly' : 'required' ?> maxlength="255" placeholder="Enter position held..." value="<?= e($finalRequirement['position_held'] ?? '') ?>" autocomplete="organization-title">
        </div>

        <div class="final-form-field">
            <label class="final-form-label" for="job-description-text">Job Description (Duties and Responsibilities) <span class="req-star">*</span></label>
            <p class="final-form-hint">Provide a detailed description of the duties and responsibilities of this position.</p>
            <textarea id="job-description-text" name="job_description" <?= $formReadOnly ? 'readonly' : 'required' ?> maxlength="2000" rows="7" placeholder="Write detailed description of duties and responsibilities..."><?= e($finalRequirement['job_description'] ?? '') ?></textarea>
        </div>

        <?php if (!$formReadOnly): ?>
        <button class="btn btn-primary final-form-submit" type="submit">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
            <span>Save Job Description</span>
        </button>
        <?php endif; ?>
    </form>
</section>
