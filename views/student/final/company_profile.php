<?php $finalRequirement = $finalRequirement ?? []; ?>
<a class="final-form-back" href="index.php?r=student_documents_final">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Back to Final Requirement
</a>

<section class="card final-form-card">
    <div class="final-form-head">
        <span class="final-form-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/><path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/></svg>
        </span>
        <div>
            <h2>Company's Profile</h2>
            <p class="muted">Provide information about your company's history, description, mission and vision.</p>
        </div>
    </div>

    <form method="post" class="form js-validate final-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="student_save_final_company_profile">

        <label class="final-form-field">
            <span class="final-form-label">History <span class="req-star">*</span></span>
            <span class="final-form-hint">Brief history of the company</span>
            <textarea name="company_history" required maxlength="2000" rows="5" placeholder="Enter brief history of the company..."><?= e($finalRequirement['company_history'] ?? '') ?></textarea>
        </label>

        <label class="final-form-field">
            <span class="final-form-label">Description <span class="req-star">*</span></span>
            <span class="final-form-hint">Company Profile</span>
            <textarea name="company_description" required maxlength="2000" rows="5" placeholder="Enter company profile..."><?= e($finalRequirement['company_description'] ?? '') ?></textarea>
        </label>

        <label class="final-form-field">
            <span class="final-form-label">Mission <span class="req-star">*</span></span>
            <span class="final-form-hint">Mission Statement</span>
            <textarea name="company_mission" required maxlength="2000" rows="5" placeholder="Enter mission statement..."><?= e($finalRequirement['company_mission'] ?? '') ?></textarea>
        </label>

        <label class="final-form-field">
            <span class="final-form-label">Vision <span class="req-star">*</span></span>
            <span class="final-form-hint">Vision Statement</span>
            <textarea name="company_vision" required maxlength="2000" rows="5" placeholder="Enter vision statement..."><?= e($finalRequirement['company_vision'] ?? '') ?></textarea>
        </label>

        <button class="btn btn-primary final-form-submit" type="submit">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
            <span>Save Company Profile</span>
        </button>
    </form>
</section>
