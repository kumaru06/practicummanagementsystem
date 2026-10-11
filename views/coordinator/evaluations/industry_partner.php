<?php
    $studentEvaluation = $studentEvaluation ?? [];
    $partnerRatings = !empty($studentEvaluation['partner_ratings']) ? json_decode($studentEvaluation['partner_ratings'], true) : [];
    $criteria = StudentEvaluation::industryPartnerCriteria();
    $grade = (float)($studentEvaluation['partner_grade'] ?? 0);
    $stars = static fn (int $n): string => str_repeat("\u{2605}", max(0, min(5, $n))) . str_repeat("\u{2606}", 5 - max(0, min(5, $n)));
    $evalBackUrl = $evalBackUrl ?? ('index.php?r=coordinator_student_final&student_id=' . (int)($student['id'] ?? 0));
    $evalBackLabel = $evalBackLabel ?? 'Back to Final Requirements';
?>
<a class="final-form-back" href="<?= e($evalBackUrl) ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    <?= e($evalBackLabel) ?>
</a>

<section class="card final-form-card eval-form-card final-readonly-card eval-readonly-card">
    <div class="final-form-head">
        <span class="final-form-icon final-form-icon--partner" aria-hidden="true">
            <svg class="final-req-icon" viewBox="0 0 24 24"><path fill="currentColor" d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg>
        </span>
        <div class="final-form-head-copy">
            <h2>Host Training Establishment Evaluation</h2>
            <p class="muted"><?= e($student['name'] ?? 'Student') ?>  ·  <?= e($student['student_no'] ?? '') ?></p>
        </div>
        <div class="eval-result-grade">
            <span class="eval-result-grade-value"><?= e(number_format($grade, 2)) ?>%</span>
            <span class="eval-result-grade-label">Overall Rating</span>
        </div>
    </div>

    <div class="status-callout info final-req-note eval-readonly-note">
        <strong>Coordinator only</strong>
        <p>This student evaluation of the host training establishment and OJT supervisor is not visible to the host training establishment.</p>
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
                    <span class="eval-row-label"><span class="eval-row-num"><?= $rowIndex ?>.</span> <?= e($def['label']) ?></span>
                    <span class="eval-row-weight"><?= e($def['weight']) ?>%</span>
                    <span class="eval-row-rating"><span class="eval-static-stars"><?= $stars($r) ?></span> <span class="eval-static-score"><?= $r ?: '-' ?></span></span>
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
