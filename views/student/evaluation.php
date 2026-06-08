<?php
$criteria = Evaluation::criteria();
$ratings = Evaluation::decodeRatings($evaluation['criteria_ratings'] ?? null);
$hasEvaluation = !empty($evaluation);
$grade = (float)($evaluation['final_grade'] ?? 0);
$rowIndex = 0;
$stars = static fn (int $n): string => str_repeat("\u{2605}", max(0, min(5, $n))) . str_repeat("\u{2606}", 5 - max(0, min(5, $n)));
?>
<div class="eval-page">
    <?php if (!$hasEvaluation): ?>
        <section class="card eval-empty">
            <div class="eval-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="40" height="40" fill="currentColor"><path d="M9 2h6a2 2 0 0 1 2 2h1a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1a2 2 0 0 1 2-2Zm0 2v2h6V4H9Z"/></svg>
            </div>
            <h2>No Evaluation Yet</h2>
            <p class="muted">Your Industry Partner has not submitted your final evaluation yet. It will appear here once completed.</p>
        </section>
    <?php else: ?>
        <section class="card eval-card">
            <div class="eval-result-head">
                <div>
                    <span class="student-section-label">Final OJT Evaluation</span>
                    <h2>Your Performance Rating</h2>
                    <p class="muted">Submitted by your Industry Partner<?= !empty($evaluation['submitted_at']) ? ' on ' . e(date('F j, Y', strtotime($evaluation['submitted_at']))) : '' ?>.</p>
                </div>
                <div class="eval-result-grade">
                    <span class="eval-result-grade-value"><?= e(number_format($grade, 2)) ?>%</span>
                    <span class="eval-result-grade-label">Final Grade</span>
                </div>
            </div>

            <?php foreach ($criteria as $sectionName => $items): ?>
                <div class="eval-section">
                    <div class="eval-section-head">
                        <span class="eval-section-title"><?= e(strtoupper($sectionName)) ?></span>
                        <span class="eval-col-weight">Weight</span>
                        <span class="eval-col-rating">Rating</span>
                    </div>
                    <?php foreach ($items as $key => $def): $rowIndex++; $r = (int)($ratings[$key] ?? 0); ?>
                        <div class="eval-row">
                            <span class="eval-row-num"><?= $rowIndex ?>.</span>
                            <span class="eval-row-label"><?= e($def['label']) ?></span>
                            <span class="eval-row-weight"><?= (int)$def['weight'] ?>%</span>
                            <span class="eval-row-rating"><span class="eval-static-stars"><?= $stars($r) ?></span> <?= $r ?: '-' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div class="eval-total-row">
                <span class="eval-total-label">Total Rating</span>
                <span class="eval-total-weight">100%</span>
                <span class="eval-total-value"><?= e(number_format($grade, 2)) ?>%</span>
            </div>

            <div class="eval-field">
                <span class="eval-field-label">Comments from Industry Partner</span>
                <div class="eval-comment-box"><?= nl2br(e($evaluation['comments'] ?? '')) ?: '<span class="muted">No comments.</span>' ?></div>
            </div>

            <?php if (!empty($evaluation['certificate_file'])): ?>
                <div class="eval-field">
                    <span class="eval-field-label">Certificate of Completion</span>
                    <a class="btn btn-primary" target="_blank" href="<?= e(asset($evaluation['certificate_file'])) ?>">View / Download Certificate</a>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<style>
.eval-page { max-width: 980px; margin: 0 auto; }
.eval-card { padding: 28px 30px; }
.eval-empty { text-align: center; padding: 50px 30px; }
.eval-empty-icon { color: #b91c1c; margin-bottom: 12px; }
.eval-empty h2 { font-size: 1.3rem; font-weight: 800; margin-bottom: 6px; }
.eval-result-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; flex-wrap: wrap; margin-bottom: 10px; }
.eval-result-head h2 { font-size: 1.4rem; font-weight: 800; }
.eval-result-grade { text-align: center; background: #fdeaea; border-radius: 12px; padding: 14px 26px; }
.eval-result-grade-value { display: block; font-size: 2rem; font-weight: 800; color: #b91c1c; line-height: 1; }
.eval-result-grade-label { font-size: .8rem; color: #b91c1c; font-weight: 600; }
.eval-section { margin-top: 22px; }
.eval-section-head { display: flex; align-items: center; background: #fdeaea; border-radius: 8px; padding: 10px 14px; font-weight: 700; color: #b91c1c; font-size: .85rem; }
.eval-section-title { flex: 1; }
.eval-col-weight { width: 80px; text-align: center; }
.eval-col-rating { width: 160px; text-align: center; }
.eval-row { display: flex; align-items: center; padding: 12px 14px; border-bottom: 1px solid #f0f0f0; }
.eval-row-num { width: 26px; color: #888; font-weight: 600; }
.eval-row-label { flex: 1; padding-right: 12px; font-size: .92rem; }
.eval-row-weight { width: 80px; text-align: center; color: #555; font-weight: 600; }
.eval-row-rating { width: 160px; text-align: center; }
.eval-static-stars { color: #f5b301; letter-spacing: 1px; }
.eval-total-row { display: flex; align-items: center; background: #fdeaea; border-radius: 8px; padding: 12px 14px; margin-top: 14px; font-weight: 800; color: #b91c1c; }
.eval-total-label { flex: 1; }
.eval-total-weight { width: 80px; text-align: center; }
.eval-total-value { width: 160px; text-align: center; }
.eval-field { margin-top: 22px; display: flex; flex-direction: column; }
.eval-field-label { font-weight: 700; margin-bottom: 8px; }
.eval-comment-box { background: #f8f8f8; border-radius: 8px; padding: 14px; }
</style>
