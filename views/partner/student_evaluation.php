<?php
    $studentEvaluation = $studentEvaluation ?? [];
    $partnerRatings = !empty($studentEvaluation['partner_ratings']) ? json_decode($studentEvaluation['partner_ratings'], true) : [];
    $criteria = StudentEvaluation::industryPartnerCriteria();
    $grade = (float)($studentEvaluation['partner_grade'] ?? 0);
    $stars = static fn (int $n): string => str_repeat("\u{2605}", max(0, min(5, $n))) . str_repeat("\u{2606}", 5 - max(0, min(5, $n)));
?>
<a class="final-form-back" href="<?= e(route_url('partner.portal', ['enrollment' => (int)($student['id'] ?? 0)])) ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Back to Portal
</a>

<section class="card final-form-card eval-form-card final-readonly-card">
    <div class="final-form-head">
        <span class="final-form-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v6M12 17v6M4.22 4.22l4.24 4.24M15.54 15.54l4.24 4.24M1 12h6M17 12h6M4.22 19.78l4.24-4.24M15.54 8.46l4.24-4.24"/></svg>
        </span>
        <div>
            <h2>Student Evaluation of Your Organization</h2>
            <p class="muted"><?= e($student['student_name'] ?? 'Student') ?> · <?= e($student['student_no'] ?? '') ?></p>
        </div>
        <div class="eval-result-grade">
            <span class="eval-result-grade-value"><?= e(number_format($grade, 2)) ?>%</span>
            <span class="eval-result-grade-label">Overall Rating</span>
        </div>
    </div>

    <div class="status-callout info final-req-note" style="margin-top:0;margin-bottom:18px;">
        <strong>Confidential student feedback</strong>
        <p>This evaluation reflects the student's experience with your organization and OJT supervisor during their practicum.</p>
    </div>

    <?php $rowIndex = 0; ?>
    <?php foreach ($criteria as $sectionName => $items): ?>
        <div class="eval-section">
            <div class="eval-section-head">
                <span class="eval-section-title"><?= e($sectionName) ?></span>
                <span class="eval-col-weight">Weight</span>
                <span class="eval-col-rating">Rating</span>
            </div>
            <?php foreach ($items as $key => $def): $rowIndex++; $r = (int)($partnerRatings[$key] ?? 0); ?>
                <div class="eval-row">
                    <span class="eval-row-num"><?= $rowIndex ?>.</span>
                    <span class="eval-row-label"><?= e($def['label']) ?></span>
                    <span class="eval-row-weight"><?= e($def['weight']) ?>%</span>
                    <span class="eval-row-rating"><span class="eval-static-stars"><?= $stars($r) ?></span> <?= $r ?: '-' ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <div class="eval-total-row">
        <span class="eval-total-label">Overall Rating</span>
        <span class="eval-total-weight">100%</span>
        <span class="eval-total-value"><?= e(number_format($grade, 2)) ?>%</span>
    </div>

    <div class="final-readonly-field">
        <span class="final-readonly-label">Comments from Student</span>
        <div class="final-readonly-value final-readonly-text"><?= nl2br(e($studentEvaluation['partner_comments'] ?? '')) ?: '<span class="muted">No comments.</span>' ?></div>
    </div>
</section>
