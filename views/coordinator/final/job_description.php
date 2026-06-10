<?php $finalRequirement = $finalRequirement ?? []; ?>
<a class="final-form-back" href="index.php?r=coordinator_student_final&amp;student_id=<?= (int)($student['id'] ?? 0) ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Back to Final Requirements
</a>

<section class="card final-form-card final-readonly-card">
    <div class="final-form-head">
        <span class="final-form-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>
        </span>
        <div>
            <h2>Job Description</h2>
            <p class="muted"><?= e($student['name'] ?? 'Student') ?> · <?= e($student['student_no'] ?? '') ?></p>
        </div>
    </div>

    <div class="final-readonly-field">
        <span class="final-readonly-label">Position Held</span>
        <div class="final-readonly-value"><?= e($finalRequirement['position_held'] ?? '-') ?></div>
    </div>

    <div class="final-readonly-field">
        <span class="final-readonly-label">Job Description (Duties and Responsibilities)</span>
        <div class="final-readonly-value final-readonly-text"><?= nl2br(e($finalRequirement['job_description'] ?? '-')) ?></div>
    </div>
</section>
