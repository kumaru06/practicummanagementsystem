<section class="card">
    <div class="section-head section-head-split">
        <div>
            <h2>OJT Placement</h2>
            <p class="muted">Track student placements with industry partners.</p>
        </div>
        <input class="table-search table-search-wide" placeholder="Search placements...">
    </div>
    <?php if (empty($placements)): ?>
        <p class="muted" style="padding:24px 0">No OJT placements have been recorded yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="data-table"><thead><tr>
        <th data-sort>Student</th>
        <th data-sort>Course</th>
        <th data-sort>Company</th>
        <th data-sort>Position</th>
        <th data-sort>Start Date</th>
        <th data-sort>End Date</th>
        <th data-sort>Status</th>
    </tr></thead><tbody>
        <?php foreach ($placements as $placement): ?>
        <?php
            $status = (string)($placement['status'] ?? 'pending');
            $statusClass = in_array($status, ['active', 'pending', 'completed'], true) ? $status : 'pending';
        ?>
        <tr>
            <td><?= e($placement['student_name']) ?></td>
            <td><?= e($placement['course']) ?></td>
            <td><?= e($placement['company_name']) ?></td>
            <td><?= !empty($placement['position_held']) ? e($placement['position_held']) : '<span class="muted">&mdash;</span>' ?></td>
            <td><?= !empty($placement['placement_start']) ? e(date('M j, Y', strtotime((string)$placement['placement_start']))) : '<span class="muted">&mdash;</span>' ?></td>
            <td><?= !empty($placement['placement_end']) ? e(date('M j, Y', strtotime((string)$placement['placement_end']))) : '<span class="muted">&mdash;</span>' ?></td>
            <td><span class="badge <?= e($statusClass) ?>"><?= e(ucfirst($status)) ?></span></td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <div class="pagination"></div>
    <?php endif; ?>
</section>
