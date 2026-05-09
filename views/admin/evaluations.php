<section class="card">
    <div class="section-head section-head-split">
        <div><h2>All Evaluations</h2><p class="muted">Final evaluations submitted by partner companies for deployed students.</p></div>
        <input class="table-search table-search-wide" placeholder="Search evaluations...">
    </div>
    <?php if (empty($evaluations)): ?>
        <p class="muted" style="padding:24px 0">No evaluations have been submitted yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="data-table"><thead><tr>
        <th data-sort>Student</th>
        <th data-sort>Student No.</th>
        <th data-sort>Course</th>
        <th data-sort>Company</th>
        <th data-sort>Rating</th>
        <th>Comments</th>
        <th data-sort>Submitted</th>
    </tr></thead><tbody>
        <?php foreach ($evaluations as $ev): ?>
        <tr>
            <td><?= e($ev['student_name']) ?></td>
            <td><?= e($ev['student_no']) ?></td>
            <td><?= e($ev['course'] . ' ' . $ev['year_level']) ?></td>
            <td><?= e($ev['company_name']) ?></td>
            <td><strong><?= (int)$ev['rating'] ?></strong> / 5</td>
            <td style="max-width:300px;white-space:normal"><?= e($ev['comments']) ?></td>
            <td><?= e(date('M j, Y', strtotime($ev['submitted_at']))) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <div class="pagination"></div>
    <?php endif; ?>
</section>
