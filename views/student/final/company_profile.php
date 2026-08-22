<?php
    $finalRequirement = $finalRequirement ?? [];
    $formReadOnly = !empty($formReadOnly);
?>
<section class="card final-form-card">
    <div class="final-form-head">
        <span class="final-form-icon final-form-icon--company" aria-hidden="true">
            <svg class="final-form-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/>
                <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/>
                <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/>
                <path d="M10 6h4"/>
                <path d="M10 10h4"/>
                <path d="M10 14h4"/>
                <path d="M10 18h4"/>
            </svg>
        </span>
        <div class="final-form-head-copy">
            <h2>Company's Profile</h2>
            <p class="muted">Provide information about your company's history, description, mission and vision.</p>
        </div>
    </div>

    <form method="post" class="form js-validate final-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="student_save_final_company_profile">

        <div class="final-form-field">
            <label class="final-form-label" for="company-history">History <span class="req-star">*</span></label>
            <p class="final-form-hint">Brief history of the company</p>
            <textarea id="company-history" name="company_history" <?= $formReadOnly ? 'readonly' : 'required' ?> maxlength="2000" rows="5" placeholder="Enter brief history of the company..."><?= e($finalRequirement['company_history'] ?? '') ?></textarea>
        </div>

        <div class="final-form-field">
            <label class="final-form-label" for="company-description">Description <span class="req-star">*</span></label>
            <p class="final-form-hint">Company Profile</p>
            <textarea id="company-description" name="company_description" <?= $formReadOnly ? 'readonly' : 'required' ?> maxlength="2000" rows="5" placeholder="Enter company profile..."><?= e($finalRequirement['company_description'] ?? '') ?></textarea>
        </div>

        <div class="final-form-field">
            <label class="final-form-label" for="company-mission">Mission <span class="req-star">*</span></label>
            <p class="final-form-hint">Mission Statement</p>
            <textarea id="company-mission" name="company_mission" <?= $formReadOnly ? 'readonly' : 'required' ?> maxlength="2000" rows="5" placeholder="Enter mission statement..."><?= e($finalRequirement['company_mission'] ?? '') ?></textarea>
        </div>

        <div class="final-form-field">
            <label class="final-form-label" for="company-vision">Vision <span class="req-star">*</span></label>
            <p class="final-form-hint">Vision Statement</p>
            <textarea id="company-vision" name="company_vision" <?= $formReadOnly ? 'readonly' : 'required' ?> maxlength="2000" rows="5" placeholder="Enter vision statement..."><?= e($finalRequirement['company_vision'] ?? '') ?></textarea>
        </div>

        <?php if (!$formReadOnly): ?>
        <button class="btn btn-primary final-form-submit" type="submit">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
            <span>Save Company Profile</span>
        </button>
        <?php endif; ?>
    </form>
</section>
