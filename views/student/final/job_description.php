<?php $finalRequirement = $finalRequirement ?? []; ?>
<a class="final-form-back" href="index.php?r=student_documents_final">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Back to Final Requirement
</a>

<section class="card final-form-card">
    <div class="final-form-head">
        <span class="final-form-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>
        </span>
        <div>
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
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
            <span>Save Job Description</span>
        </button>
    </form>
</section>
