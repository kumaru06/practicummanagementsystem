<?php 
    $studentEvaluation = $studentEvaluation ?? [];
    $partnerRatings = !empty($studentEvaluation['partner_ratings']) ? json_decode($studentEvaluation['partner_ratings'], true) : [];
    $criteria = StudentEvaluation::industryPartnerCriteria();
    $stars = static fn (int $n): string => str_repeat("\u{2605}", max(0, min(5, $n))) . str_repeat("\u{2606}", 5 - max(0, min(5, $n)));
?>
<a class="final-form-back" href="index.php?r=student_documents_final">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Back to Final Requirements
</a>

<section class="card final-form-card eval-form-card">
    <div class="final-form-head">
        <span class="final-form-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v6M12 17v6M4.22 4.22l4.24 4.24M15.54 15.54l4.24 4.24M1 12h6M17 12h6M4.22 19.78l4.24-4.24M15.54 8.46l4.24-4.24"/></svg>
        </span>
        <div>
            <h2>Industry Partner Evaluation</h2>
            <p class="muted">Evaluate your OJT experience by rating your Industry Partner and OJT Supervisor.</p>
        </div>
    </div>

    <form method="post" action="index.php" class="form js-validate final-form eval-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="student_save_evaluation_partner">

        <?php $rowIndex = 0; ?>
        <?php foreach ($criteria as $sectionName => $items): ?>
            <div class="eval-section">
                <div class="eval-section-head">
                    <span class="eval-section-title"><?= e($sectionName) ?></span>
                    <span class="eval-col-weight">Weight</span>
                    <span class="eval-col-rating">Rating (1-5)</span>
                </div>
                <?php foreach ($items as $key => $def): $rowIndex++; ?>
                    <div class="eval-row">
                        <span class="eval-row-num"><?= $rowIndex ?>.</span>
                        <span class="eval-row-label"><?= e($def['label']) ?></span>
                        <span class="eval-row-weight"><?= e($def['weight']) ?>%</span>
                        <div class="eval-row-rating">
                            <span class="star-rating">
                                <?php $current = (int)($partnerRatings[$key] ?? 0); ?>
                                <?php for ($star = 5; $star >= 1; $star--): ?>
                                    <input type="radio" name="<?= e($key) ?>" value="<?= $star ?>" id="<?= e($key . '_' . $star) ?>" <?= $current === $star ? 'checked' : '' ?> required>
                                    <label for="<?= e($key . '_' . $star) ?>" title="<?= $star ?> star<?= $star > 1 ? 's' : '' ?>">&#9733;</label>
                                <?php endfor; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <label class="final-form-field">
            <span class="final-form-label">Comments</span>
            <span class="final-form-hint">Write your comments here.</span>
            <textarea name="partner_comments" maxlength="2000" rows="4" placeholder="Write your comments here..."><?= e($studentEvaluation['partner_comments'] ?? '') ?></textarea>
        </label>

        <button class="btn btn-primary final-form-submit" type="submit">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
            <span>Save Industry Partner Evaluation</span>
        </button>
    </form>
</section>