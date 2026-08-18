<?php
$criteria = Evaluation::criteria();
$savedRatings = Evaluation::decodeRatings($evaluation['criteria_ratings'] ?? null);
$enrollmentId = (int)($selected['id'] ?? 0);
$rowIndex = 0;
?>
<div class="partner-eval-page">
    <div class="eval-back">
        <a class="btn btn-small" href="<?= e(route_url('partner.portal', ['enrollment' => $enrollmentId])) ?>">&larr; Back to Portal</a>
    </div>

    <section class="card eval-card">
        <div class="eval-head">
            <div class="eval-head-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor"><path d="M9 2h6a2 2 0 0 1 2 2h1a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1a2 2 0 0 1 2-2Zm0 2v2h6V4H9Zm-1 7 1.4-1.4 1.6 1.6 3.6-3.6L16 9l-5 5-3-3Z"/></svg>
            </div>
            <div>
                <h2>Final Evaluation</h2>
                <p class="muted">Rate each item according to the indicated percentage. Student: <strong><?= e($selected['student_name'] ?? '') ?></strong></p>
            </div>
        </div>

        <form method="post" enctype="multipart/form-data" class="form js-validate eval-form partner-eval-form" id="evalForm" data-partner-eval-form>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="partner_submit_evaluation">
            <input type="hidden" name="enrollment_id" value="<?= $enrollmentId ?>">

            <?php foreach ($criteria as $sectionName => $items): ?>
                <div class="eval-section">
                    <div class="eval-section-head">
                        <span class="eval-section-title"><?= e(strtoupper($sectionName)) ?></span>
                        <span class="eval-col-weight">Weight</span>
                        <span class="eval-col-rating">Rating (1&ndash;5)</span>
                    </div>
                    <?php foreach ($items as $key => $def): $rowIndex++; $current = (int)($savedRatings[$key] ?? 0); ?>
                        <div class="eval-row">
                            <span class="eval-row-num"><?= $rowIndex ?>.</span>
                            <span class="eval-row-label"><?= e($def['label']) ?></span>
                            <span class="eval-row-weight"><?= (int)$def['weight'] ?>%</span>
                            <span class="eval-row-rating">
                                <span class="star-rating" data-weight="<?= (int)$def['weight'] ?>">
                                    <?php for ($star = 5; $star >= 1; $star--): ?>
                                        <input type="radio" id="<?= e($key) ?>-<?= $star ?>" name="criteria[<?= e($key) ?>]" value="<?= $star ?>" required <?= $current === $star ? 'checked' : '' ?>>
                                        <label for="<?= e($key) ?>-<?= $star ?>" title="<?= $star ?> star<?= $star > 1 ? 's' : '' ?>">&#9733;</label>
                                    <?php endfor; ?>
                                </span>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div class="eval-total-row">
                <span class="eval-total-label">Total Rating</span>
                <span class="eval-total-weight">100%</span>
                <span class="eval-total-value" id="evalTotalValue">&mdash;</span>
            </div>

            <div class="eval-field">
                <label class="eval-field-label" for="evalComments">Comments</label>
                <textarea id="evalComments" name="comments" maxlength="500" rows="4" required placeholder="Write your comments here..."><?= e($evaluation['comments'] ?? '') ?></textarea>
                <span class="eval-char-count"><span id="evalCharCount">500</span> characters remaining</span>
            </div>

            <div class="eval-field">
                <label class="eval-field-label">Certificate of Completion <span class="field-required">*</span></label>
                <?php if (!empty($evaluation['certificate_file'])): ?>
                    <div class="eval-cert-existing">
                        <span class="muted">A certificate is already on file.</span>
                        <a class="btn btn-small" target="_blank" href="<?= e(asset($evaluation['certificate_file'])) ?>">View Current</a>
                    </div>
                <?php endif; ?>
                <div class="eval-upload">
                    <div class="eval-upload-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M19 15v4H5v-4H3v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4h-2ZM11 3.83 7.41 7.41 6 6l6-6 6 6-1.41 1.41L13 3.83V15h-2V3.83Z"/></svg>
                    </div>
                    <div class="eval-upload-copy">
                        <strong>Upload your certificate of completion</strong>
                        <small>Accepted formats: PDF, JPG, PNG (Max. 8MB)</small>
                    </div>
                    <label class="btn btn-small eval-upload-btn">
                        <span>Upload File</span>
                        <input type="file" name="certificate_file" accept="application/pdf,image/jpeg,image/png" <?= empty($evaluation['certificate_file']) ? 'required' : '' ?> hidden id="evalCertInput">
                    </label>
                </div>
                <span class="eval-cert-name muted" id="evalCertName"></span>
            </div>

            <div class="eval-actions">
                <button class="btn btn-primary eval-submit" type="submit">
                    <span class="btn-text"><?= !empty($evaluation) ? 'Update Evaluation' : 'Submit Evaluation' ?></span>
                    <span class="spinner"></span>
                </button>
            </div>
        </form>
    </section>
</div>
