<section class="card">
    <div class="section-head section-head-split">
        <div><h2>All Evaluations</h2><p class="muted">Final evaluations submitted by Industry Partners for deployed students.</p></div>
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
        <th>Comments</th>
        <th data-sort>Submitted</th>
    </tr></thead><tbody>
        <?php foreach ($evaluations as $ev): ?>
        <tr>
            <td><?= e($ev['student_name']) ?></td>
            <td><?= e($ev['student_no']) ?></td>
            <td><?= e($ev['course'] . ' ' . $ev['year_level']) ?></td>
            <td><?= e($ev['company_name']) ?></td>
            <td><strong><?= isset($ev['final_grade']) && $ev['final_grade'] !== null ? e(number_format((float)$ev['final_grade'], 2)) . '%' : ((int)$ev['rating'] . ' / 5') ?></strong></td>
            <td><?php if (!empty($ev['certificate_file'])): ?><a class="btn btn-small" target="_blank" href="<?= e(asset($ev['certificate_file'])) ?>">View</a><?php else: ?><span class="muted">&mdash;</span><?php endif; ?></td>
            <td style="max-width:300px;white-space:normal"><?= e($ev['comments']) ?></td>
            <td><?= e(date('M j, Y', strtotime($ev['submitted_at']))) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <div class="pagination"></div>
    <?php endif; ?>
</section>
