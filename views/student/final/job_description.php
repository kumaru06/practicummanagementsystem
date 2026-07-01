<?php
    $finalRequirement = $finalRequirement ?? [];
    $svgAttrs = 'class="final-req-icon" viewBox="0 0 24 24" aria-hidden="true"';
    $jobIcon = '<svg ' . $svgAttrs . '><path fill="currentColor" d="M10 2h4a2 2 0 0 1 2 2v1h4a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h4V4a2 2 0 0 1 2-2Zm0 4V4h-4v2h4Zm-2 8v2h4v-2H8Z"/></svg>';
?>
<section class="card final-form-card">
    <div class="final-form-head">
        <span class="final-form-icon final-form-icon--job"><?= $jobIcon ?></span>
        <div class="final-form-head-copy">
            <h2>Job Description</h2>
            <p class="muted">Provide a detailed description of duties and responsibilities.</p>
        </div>
    </div>

    <form method="post" class="form js-validate final-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="student_save_final_job_description">

        <label class="final-form-field">
            <span class="final-form-label">Position Held <span class="req-star">*</span></span>
            <input type="text" name="position_held" required maxlength="255" placeholder="Enter position held..." value="<?= e($finalRequirement['position_held'] ?? '') ?>">
        </label>

        <label class="final-form-field">
            <span class="final-form-label">Job Description (Duties and Responsibilities) <span class="req-star">*</span></span>
            <span class="final-form-hint">Provide a detailed description of the duties and responsibilities of this position.</span>
            <textarea name="job_description" required maxlength="2000" rows="7" placeholder="Write detailed description of duties and responsibilities..."><?= e($finalRequirement['job_description'] ?? '') ?></textarea>
        </label>

        <button class="btn btn-primary final-form-submit" type="submit">
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4Zm-5 16H8v-2h4v2Zm4-4H8v-2h8v2Zm0-5V5l3 3h-3Z"/></svg>
            <span>Save Job Description</span>
        </button>
    </form>
</section>
