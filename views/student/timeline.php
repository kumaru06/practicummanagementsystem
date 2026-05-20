<section class="card">
    <div class="section-head"><h2>Activity Timeline</h2><span class="muted">Daily time records and weekly submissions</span></div>
    <div class="timeline<?= !$dtrs && !$weeklyReports ? ' is-empty' : '' ?>">
        <?php if (!$dtrs && !$weeklyReports): ?><p class="muted">No practicum activity submitted yet.</p><?php endif; ?>
        <?php foreach ($dtrs as $d): ?>
            <article class="timeline-item" data-detail="<?= e($d['work_date'] . '|' . $d['time_in'] . ' - ' . $d['time_out'] . '|' . $d['hours'] . ' hours|' . $d['tasks_done']) ?>">
                <span class="timeline-dot"></span>
                <div class="timeline-card"><strong><?= e($d['work_date']) ?></strong><small><?= e($d['time_in']) ?> - <?= e($d['time_out']) ?> · <?= e((string)$d['hours']) ?> hours</small><p><?= e($d['tasks_done']) ?></p></div>
            </article>
        <?php endforeach; ?>
        <?php foreach ($weeklyReports as $r): ?>
            <article class="timeline-item">
                <span class="timeline-dot"></span>
                <div class="timeline-card"><strong>Weekly Report <?= (int)$r['week_no'] ?></strong><small><?= e($r['created_at'] ?? '') ?></small><p><?= $r['file_path'] ? '<a class="btn btn-small" target="_blank" href="' . e(asset($r['file_path'])) . '">View PDF</a>' : e($r['report_text'] ?? '') ?></p></div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
