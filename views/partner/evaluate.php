<?php
$criteria = Evaluation::criteria();
$savedRatings = Evaluation::decodeRatings($evaluation['criteria_ratings'] ?? null);
$enrollmentId = (int)($selected['id'] ?? 0);
$rowIndex = 0;
?>
<div class="eval-page">
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

        <form method="post" enctype="multipart/form-data" class="form js-validate eval-form" id="evalForm">
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

<style>
.eval-page { max-width: 980px; margin: 0 auto; }
.eval-back { margin-bottom: 14px; }
.eval-card { padding: 28px 30px; }
.eval-head { display: flex; align-items: center; gap: 14px; margin-bottom: 8px; }
.eval-head-icon { width: 46px; height: 46px; border-radius: 12px; background: #fde8e8; color: #b91c1c; display: flex; align-items: center; justify-content: center; flex: none; }
.eval-head h2 { font-size: 1.4rem; font-weight: 800; }
.eval-section { margin-top: 22px; }
.eval-section-head { display: flex; align-items: center; background: #fdeaea; border-radius: 8px; padding: 10px 14px; font-weight: 700; color: #b91c1c; font-size: .85rem; letter-spacing: .02em; }
.eval-section-title { flex: 1; }
.eval-col-weight { width: 80px; text-align: center; }
.eval-col-rating { width: 160px; text-align: center; }
.eval-row { display: flex; align-items: center; padding: 12px 14px; border-bottom: 1px solid #f0f0f0; }
.eval-row-num { width: 26px; color: #888; font-weight: 600; }
.eval-row-label { flex: 1; padding-right: 12px; font-size: .92rem; }
.eval-row-weight { width: 80px; text-align: center; color: #555; font-weight: 600; }
.eval-row-rating { width: 160px; display: flex; justify-content: center; }
.star-rating { display: inline-flex; flex-direction: row-reverse; gap: 2px; position: relative; }
.star-rating input { position: absolute; bottom: 0; left: 50%; width: 1px; height: 1px; opacity: 0; pointer-events: none; margin: 0; }
.star-rating label { color: #d8d8d8; cursor: pointer; font-size: 22px; line-height: 1; transition: color .12s; }
.star-rating label:hover, .star-rating label:hover ~ label { color: #f5b301; }
.star-rating input:checked ~ label { color: #f5b301; }
.eval-total-row { display: flex; align-items: center; background: #fdeaea; border-radius: 8px; padding: 12px 14px; margin-top: 14px; font-weight: 800; color: #b91c1c; }
.eval-total-label { flex: 1; }
.eval-total-weight { width: 80px; text-align: center; }
.eval-total-value { width: 160px; text-align: center; }
.eval-field { margin-top: 22px; display: flex; flex-direction: column; }
.eval-field-label { font-weight: 700; margin-bottom: 8px; }
.eval-field textarea { width: 100%; border: 1px solid #ddd; border-radius: 8px; padding: 12px; resize: vertical; font: inherit; }
.eval-char-count { align-self: flex-end; font-size: .8rem; color: #999; margin-top: 6px; }
.eval-cert-existing { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.eval-upload { display: flex; align-items: center; gap: 14px; border: 1px dashed #e0a0a0; border-radius: 10px; padding: 16px 18px; background: #fffafa; }
.eval-upload-icon { width: 42px; height: 42px; border-radius: 50%; background: #fde8e8; color: #b91c1c; display: flex; align-items: center; justify-content: center; flex: none; }
.eval-upload-copy { flex: 1; display: flex; flex-direction: column; }
.eval-upload-copy small { color: #999; }
.eval-upload-btn { cursor: pointer; }
.eval-cert-name { margin-top: 8px; }
.eval-actions { margin-top: 26px; display: flex; justify-content: center; }
.eval-actions .btn-primary { min-width: 240px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('evalForm');
    if (!form) return;

    const totalValue = document.getElementById('evalTotalValue');
    const computeTotal = function () {
        let total = 0;
        let allRated = true;
        form.querySelectorAll('.star-rating').forEach(function (widget) {
            const weight = parseFloat(widget.dataset.weight) || 0;
            const checked = widget.querySelector('input:checked');
            if (checked) {
                total += (parseInt(checked.value, 10) / 5) * weight;
            } else {
                allRated = false;
            }
        });
        totalValue.textContent = allRated ? total.toFixed(2) + '%' : '\u2014';
    };
    form.querySelectorAll('.star-rating input').forEach(function (input) {
        input.addEventListener('change', computeTotal);
    });
    computeTotal();

    const comments = document.getElementById('evalComments');
    const charCount = document.getElementById('evalCharCount');
    const updateCount = function () { charCount.textContent = (500 - comments.value.length); };
    comments.addEventListener('input', updateCount);
    updateCount();

    const certInput = document.getElementById('evalCertInput');
    const certName = document.getElementById('evalCertName');
    if (certInput) {
        certInput.addEventListener('change', function () {
            certName.textContent = certInput.files.length ? 'Selected: ' + certInput.files[0].name : '';
        });
    }
});
</script>
