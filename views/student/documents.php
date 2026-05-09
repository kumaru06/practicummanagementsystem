<?php require __DIR__ . '/partials/predeployment_requirements.php'; ?>

<section class="card">
    <h2>My Documents</h2>
    <p>COR: <?php if ($student && !empty($student['cor_file'])): ?><a class="btn btn-small" target="_blank" href="<?= e(asset($student['cor_file'])) ?>">View COR</a><?php else: ?><span class="muted">No COR uploaded</span><?php endif; ?></p>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Week</th><th>File</th></tr></thead>
            <tbody>
                <?php if (empty($weeklyReports)): ?><tr><td colspan="2" class="muted">No weekly reports uploaded yet.</td></tr><?php endif; ?>
                <?php foreach ($weeklyReports as $r): ?>
                    <tr><td><?= (int)$r['week_no'] ?></td><td><?= $r['file_path'] ? '<a class="btn btn-small" target="_blank" href="' . e(asset($r['file_path'])) . '">View PDF</a>' : '-' ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
