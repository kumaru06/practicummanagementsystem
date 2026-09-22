<?php $studentEvaluations = $studentEvaluations ?? []; ?>
<section class="card">
    <div class="section-head section-head-split">
        <div><h2>HTE Final Evaluations</h2><p class="muted">Final evaluations submitted by Host Training Establishments for deployed students.</p></div>
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

<section class="card" style="margin-top:22px">
    <div class="section-head section-head-split">
        <div><h2>Student Evaluations</h2><p class="muted">Evaluations submitted by students for their Host Training Establishment and OJT coordinator. Visible to administrators only.</p></div>
        <input class="table-search table-search-wide" placeholder="Search student evaluations...">
    </div>
    <?php if (empty($studentEvaluations)): ?>
        <p class="muted" style="padding:24px 0">No student evaluations have been submitted yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="data-table"><thead><tr>
        <th data-sort>Student</th>
        <th data-sort>Student ID</th>
        <th data-sort>Type</th>
        <th data-sort>Company</th>
        <th data-sort>Rating</th>
        <th data-sort>Submitted</th>
        <th>Action</th>
    </tr></thead><tbody>
        <?php foreach ($studentEvaluations as $row): ?>
        <tr>
            <td><?= e($row['student_name'] ?? '') ?></td>
            <td><?= e($row['student_no'] ?? '') ?></td>
            <td><?= e($row['type_label'] ?? '') ?></td>
            <td><?= e($row['company_name'] ?? '—') ?></td>
            <td><strong><?= isset($row['grade']) && $row['grade'] !== null ? e(number_format((float)$row['grade'], 2)) . '%' : '—' ?></strong></td>
            <td><?= !empty($row['submitted_at']) ? e(date('M j, Y', strtotime((string)$row['submitted_at']))) : '—' ?></td>
            <td>
                <a class="btn btn-small" href="<?= e(route_url('admin.student_evaluation', ['student_id' => (int)$row['student_id'], 'type' => (string)$row['type']])) ?>">View</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <div class="pagination"></div>
    <?php endif; ?>
</section>
