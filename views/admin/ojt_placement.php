<section class="card" data-ojt-placement-directory>
    <div class="section-head section-head-split">
        <div>
            <h2>OJT Placement</h2>
            <p class="muted">Track student placements with host training establishments.</p>
        </div>
        <div class="ojt-placement-toolbar">
            <label class="filter-select-wrap asu-filter-select">
                <select data-ojt-placement-status-filter data-select-label="Status" aria-label="Filter by status">
                    <option value="active" selected>Active</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="all">All Status</option>
                </select>
            </label>
            <input class="table-search table-search-wide" placeholder="Search placements...">
        </div>
    </div>
    <?php if (empty($placements)): ?>
        <p class="muted" style="padding:24px 0">No OJT placements have been recorded yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="data-table" data-hide-column-toggle><thead><tr>
        <th data-sort>Student</th>
        <th data-sort>Course</th>
        <th data-sort>Company</th>
        <th data-sort>Start Date</th>
        <th data-sort>End Date</th>
        <th data-sort>Status</th>
    </tr></thead><tbody>
        <?php foreach ($placements as $placement): ?>
        <?php
            $status = (string)($placement['status'] ?? 'pending');
            $statusClass = in_array($status, ['active', 'pending', 'completed'], true) ? $status : 'pending';
            $searchHaystack = strtolower(trim(
                ($placement['student_name'] ?? '') . ' ' .
                ($placement['course'] ?? '') . ' ' .
                ($placement['company_name'] ?? '') . ' ' .
                $status
            ));
        ?>
        <tr data-placement-status="<?= e($statusClass) ?>" data-search="<?= e($searchHaystack) ?>">
            <td><?= e($placement['student_name']) ?></td>
            <td><?= e($placement['course']) ?></td>
            <td><?= e($placement['company_name']) ?></td>
            <td><?= !empty($placement['placement_start']) ? e(date('M j, Y', strtotime((string)$placement['placement_start']))) : '<span class="muted">&mdash;</span>' ?></td>
            <td><?= !empty($placement['placement_end']) ? e(date('M j, Y', strtotime((string)$placement['placement_end']))) : '<span class="muted">&mdash;</span>' ?></td>
            <td><span class="badge <?= e($statusClass) ?>"><?= e(ucfirst($status)) ?></span></td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <div class="pagination"></div>
    <?php endif; ?>
</section>
