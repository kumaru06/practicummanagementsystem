<?php $finalRequirement = $finalRequirement ?? []; ?>
<a class="final-form-back" href="index.php?r=coordinator_student_final&amp;student_id=<?= (int)($student['id'] ?? 0) ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Back to Final Requirements
</a>

<section class="card final-form-card final-readonly-card">
    <div class="final-form-head">
        <span class="final-form-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/><path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/></svg>
        </span>
        <div>
            <h2>Company's Profile</h2>
            <p class="muted"><?= e($student['name'] ?? 'Student') ?> · <?= e($student['student_no'] ?? '') ?></p>
        </div>
    </div>

    <?php
        $fields = [
            ['History', 'Brief history of the company', $finalRequirement['company_history'] ?? ''],
            ['Description', 'Company Profile', $finalRequirement['company_description'] ?? ''],
            ['Mission', 'Mission Statement', $finalRequirement['company_mission'] ?? ''],
            ['Vision', 'Vision Statement', $finalRequirement['company_vision'] ?? ''],
        ];
    ?>
    <?php foreach ($fields as [$label, $hint, $value]): ?>
        <div class="final-readonly-field">
            <span class="final-readonly-label"><?= e($label) ?></span>
            <span class="final-form-hint"><?= e($hint) ?></span>
            <div class="final-readonly-value final-readonly-text"><?= nl2br(e($value !== '' ? $value : '-')) ?></div>
        </div>
    <?php endforeach; ?>
</section>
