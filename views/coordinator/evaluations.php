<?php $criteriaFlat = Evaluation::criteriaFlat(); ?>
<section class="card">
    <div class="section-head section-head-split">
        <div><h2>Evaluations</h2><p class="muted">Final evaluations submitted by Industry Partners for your students.</p></div>
        <input class="table-search table-search-wide" placeholder="Search evaluations...">
    </div>
    <?php if (empty($evaluations)): ?>
        <p class="muted" style="padding:24px 0">No evaluations have been submitted yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="data-table"><thead><tr>
        <th data-sort>Student</th>
        <th data-sort>Student ID</th>
        <th data-sort>Course</th>
        <th data-sort>Company</th>
        <th data-sort>Final Grade</th>
        <th>Certificate</th>
        <th data-sort>Submitted</th>
        <th>Details</th>
    </tr></thead><tbody>
        <?php foreach ($evaluations as $ev): ?>
        <tr>
            <td><?= e($ev['student_name']) ?></td>
            <td><?= e($ev['student_no']) ?></td>
            <td><?= e($ev['course'] . ' ' . $ev['year_level']) ?></td>
            <td><?= e($ev['company_name']) ?></td>
            <td><strong><?= $ev['final_grade'] !== null ? e(number_format((float)$ev['final_grade'], 2)) . '%' : ((int)$ev['rating'] . ' / 5') ?></strong></td>
            <td><?php if (!empty($ev['certificate_file'])): ?><a class="btn btn-small" target="_blank" href="<?= e(asset($ev['certificate_file'])) ?>">View</a><?php else: ?><span class="muted">&mdash;</span><?php endif; ?></td>
            <td><?= e(date('M j, Y', strtotime($ev['submitted_at']))) ?></td>
            <td><button type="button" class="btn btn-small eval-view-btn" data-eval-view='<?= e(json_encode([
                'student' => $ev['student_name'],
                'company' => $ev['company_name'],
                'grade' => $ev['final_grade'],
                'rating' => (int)$ev['rating'],
                'comments' => $ev['comments'],
                'ratings' => Evaluation::decodeRatings($ev['criteria_ratings'] ?? null),
                'certificate' => !empty($ev['certificate_file']) ? asset($ev['certificate_file']) : '',
            ], JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>View Breakdown</button></td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <div class="pagination"></div>
    <?php endif; ?>
</section>

<div class="eval-detail-overlay" id="evalDetailOverlay">
    <div class="eval-detail-modal">
        <div class="eval-detail-head">
            <h2>Evaluation Breakdown</h2>
            <button type="button" class="eval-detail-close" id="evalDetailClose">&times;</button>
        </div>
        <div class="eval-detail-body" id="evalDetailBody"></div>
    </div>
</div>

<script>
window.AMA_EVAL_CRITERIA = <?= json_encode(array_map(static fn ($d) => ['label' => $d['label'], 'weight' => $d['weight']], $criteriaFlat)) ?>;
</script>

<style>
.eval-detail-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 1000; align-items: center; justify-content: center; }
.eval-detail-overlay.open { display: flex; }
.eval-detail-modal { background: #fff; border-radius: 12px; width: 95%; max-width: 640px; max-height: 90vh; overflow-y: auto; padding: 24px 26px; box-shadow: 0 12px 40px rgba(0,0,0,.18); }
.eval-detail-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.eval-detail-head h2 { font-size: 1.15rem; font-weight: 800; }
.eval-detail-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666; }
.eval-detail-grade { display: flex; align-items: baseline; gap: 10px; margin-bottom: 14px; }
.eval-detail-grade strong { font-size: 1.8rem; color: #b91c1c; }
.eval-detail-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
.eval-detail-table th, .eval-detail-table td { text-align: left; padding: 8px 6px; border-bottom: 1px solid #f0f0f0; }
.eval-detail-table td.num { text-align: center; white-space: nowrap; }
.eval-detail-stars { color: #f5b301; letter-spacing: 1px; }
.eval-detail-comments { margin-top: 14px; background: #f8f8f8; border-radius: 8px; padding: 12px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.getElementById('evalDetailOverlay');
    const body = document.getElementById('evalDetailBody');
    const closeBtn = document.getElementById('evalDetailClose');
    const criteria = window.AMA_EVAL_CRITERIA || {};

    const close = () => overlay.classList.remove('open');
    closeBtn.addEventListener('click', close);
    overlay.addEventListener('click', e => { if (e.target === overlay) close(); });

    const stars = n => '\u2605'.repeat(n) + '\u2606'.repeat(5 - n);

    document.querySelectorAll('.eval-view-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const data = JSON.parse(btn.dataset.evalView);
            let rows = '';
            Object.keys(criteria).forEach(function (key, i) {
                const c = criteria[key];
                const r = parseInt(data.ratings[key] || 0, 10);
                rows += '<tr><td class="num">' + (i + 1) + '</td><td>' + c.label + '</td><td class="num">' + c.weight + '%</td>' +
                    '<td class="num"><span class="eval-detail-stars">' + stars(r) + '</span> ' + (r || '-') + '</td></tr>';
            });
            const gradeText = data.grade !== null && data.grade !== undefined ? parseFloat(data.grade).toFixed(2) + '%' : (data.rating + ' / 5');
            let html = '<div class="eval-detail-grade"><strong>' + gradeText + '</strong><span>Final Grade &middot; ' + data.student + ' &middot; ' + data.company + '</span></div>';
            html += '<table class="eval-detail-table"><thead><tr><th class="num">#</th><th>Criteria</th><th class="num">Weight</th><th class="num">Rating</th></tr></thead><tbody>' + rows + '</tbody></table>';
            html += '<div class="eval-detail-comments"><strong>Comments:</strong><br>' + (data.comments ? data.comments.replace(/</g, '&lt;') : '\u2014') + '</div>';
            if (data.certificate) {
                html += '<div style="margin-top:14px"><a class="btn btn-small" target="_blank" href="' + data.certificate + '">View Certificate of Completion</a></div>';
            }
            body.innerHTML = html;
            overlay.classList.add('open');
        });
    });
});
</script>
