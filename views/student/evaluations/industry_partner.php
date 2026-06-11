<?php
    $studentEvaluation = $studentEvaluation ?? [];
    $partnerRatings = !empty($studentEvaluation['partner_ratings']) ? json_decode($studentEvaluation['partner_ratings'], true) : [];
    $criteria = StudentEvaluation::industryPartnerCriteria();
    $svgAttrs = 'class="final-req-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
    $partnerIcon = '<svg ' . $svgAttrs . '><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01"/></svg>';
?>
<section class="card final-form-card eval-form-card">
    <div class="final-form-head">
        <span class="final-form-icon final-form-icon--partner"><?= $partnerIcon ?></span>
        <div class="final-form-head-copy">
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
                        <span class="eval-row-label"><span class="eval-row-num"><?= $rowIndex ?>.</span> <?= e($def['label']) ?></span>
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
