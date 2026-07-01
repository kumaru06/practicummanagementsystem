<?php
    $studentEvaluation = $studentEvaluation ?? [];
    $coordinatorRatings = !empty($studentEvaluation['coordinator_ratings']) ? json_decode($studentEvaluation['coordinator_ratings'], true) : [];
    $criteria = StudentEvaluation::coordinatorCriteria();
    $svgAttrs = 'class="final-req-icon" viewBox="0 0 24 24" aria-hidden="true"';
    $coordinatorIcon = '<svg ' . $svgAttrs . '><path fill="currentColor" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg>';
?>
<section class="card final-form-card eval-form-card">
    <div class="final-form-head">
        <span class="final-form-icon final-form-icon--eval"><?= $coordinatorIcon ?></span>
        <div class="final-form-head-copy">
            <h2>OJT Coordinator Evaluation</h2>
            <p class="muted">Evaluate the support and guidance provided by your OJT Coordinator.</p>
        </div>
    </div>

    <form method="post" action="index.php" class="form js-validate final-form eval-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="student_save_evaluation_coordinator">

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
                                <?php $current = (int)($coordinatorRatings[$key] ?? 0); ?>
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
            <textarea name="coordinator_comments" maxlength="2000" rows="4" placeholder="Write your comments here..."><?= e($studentEvaluation['coordinator_comments'] ?? '') ?></textarea>
        </label>

        <button class="btn btn-primary final-form-submit" type="submit">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
            <span>Save OJT Coordinator Evaluation</span>
        </button>
    </form>
</section>
