<?php $studentEvaluations = $studentEvaluations ?? []; ?>
<div class="admin-eval-page">
<section class="card">
    <div class="section-head section-head-split">
        <div><h2>HTE Final Evaluations</h2></div>
        <input class="table-search table-search-wide" placeholder="Search evaluations...">
    </div>
    <?php if (empty($evaluations)): ?>
        <p class="muted" style="padding:24px 0">No evaluations have been submitted yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="data-table no-row-details eval-hte-table" data-hide-column-toggle><thead><tr>
        <th data-sort>Student</th>
        <th data-sort>Student ID</th>
        <th data-sort>Degree Program</th>
        <th data-sort>Academic Term</th>
        <th data-sort>HTE</th>
        <th class="eval-grade" data-sort>Final Grade</th>
        <th data-sort>Submitted</th>
        <th>Actions</th>
    </tr></thead><tbody>
        <?php foreach ($evaluations as $ev): ?>
        <?php
            $programName = trim((string)($ev['course'] ?? ''));
            $academicTerm = trim((string)($ev['academic_term'] ?? ''));
            $comment = trim((string)($ev['comments'] ?? ''));
            $gradeLabel = isset($ev['final_grade']) && $ev['final_grade'] !== null
                ? number_format((float)$ev['final_grade'], 2) . '%'
                : ((int)$ev['rating'] . ' / 5');
            $submittedLabel = date('M j, Y', strtotime($ev['submitted_at']));
            $certificateUrl = !empty($ev['certificate_file']) ? asset($ev['certificate_file']) : '';
            $photoUrl = profile_photo_url($ev);
        ?>
        <tr data-eval-record data-eval-kind="hte" data-student="<?= e($ev['student_name']) ?>" data-student-no="<?= e($ev['student_no']) ?>" data-program="<?= e($programName) ?>" data-term="<?= e($academicTerm) ?>" data-hte="<?= e($ev['company_name']) ?>" data-grade="<?= e($gradeLabel) ?>" data-submitted="<?= e($submittedLabel) ?>"<?php if ($photoUrl !== ''): ?> data-photo="<?= e($photoUrl) ?>"<?php endif; ?><?php if ($certificateUrl !== ''): ?> data-certificate="<?= e($certificateUrl) ?>"<?php endif; ?><?php if ($comment !== ''): ?> data-comment="<?= e($comment) ?>"<?php endif; ?>>
            <td><?= e($ev['student_name']) ?></td>
            <td><?= e($ev['student_no']) ?></td>
            <td class="eval-program"><?= e($programName) ?></td>
            <td><?= e($academicTerm !== '' ? $academicTerm : '—') ?></td>
            <td><?= e($ev['company_name']) ?></td>
            <td class="eval-grade"><strong><?= e($gradeLabel) ?></strong></td>
            <td><?= e($submittedLabel) ?></td>
            <td><button type="button" class="btn btn-small" data-eval-open>View Details</button></td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <div class="pagination"></div>
    <?php endif; ?>
</section>

<section class="card" style="margin-top:22px">
    <div class="section-head section-head-split">
        <div><h2>Student Evaluations</h2></div>
        <input class="table-search table-search-wide" placeholder="Search student evaluations...">
    </div>
    <?php if (empty($studentEvaluations)): ?>
        <p class="muted" style="padding:24px 0">No student evaluations have been submitted yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="data-table no-row-details" data-hide-column-toggle><thead><tr>
        <th data-sort>Student</th>
        <th data-sort>Student ID</th>
        <th data-sort>Type</th>
        <th data-sort>HTE</th>
        <th class="eval-grade" data-sort>Rating</th>
        <th data-sort>Submitted</th>
        <th>Action</th>
    </tr></thead><tbody>
        <?php foreach ($studentEvaluations as $row): ?>
        <?php
            $companyName = trim((string)($row['company_name'] ?? ''));
            $studentGrade = isset($row['grade']) && $row['grade'] !== null ? number_format((float)$row['grade'], 2) . '%' : '—';
            $studentSubmitted = !empty($row['submitted_at']) ? date('M j, Y', strtotime((string)$row['submitted_at'])) : '—';
            $studentViewUrl = route_url('admin.student_evaluation', ['student_id' => (int)$row['student_id'], 'type' => (string)$row['type']]);
        ?>
        <tr data-eval-record data-eval-kind="student" data-student="<?= e($row['student_name'] ?? '') ?>" data-student-no="<?= e($row['student_no'] ?? '') ?>" data-type="<?= e($row['type_label'] ?? '') ?>" data-hte="<?= e($companyName) ?>" data-grade="<?= e($studentGrade) ?>" data-submitted="<?= e($studentSubmitted) ?>" data-href="<?= e($studentViewUrl) ?>">
            <td><?= e($row['student_name'] ?? '') ?></td>
            <td><?= e($row['student_no'] ?? '') ?></td>
            <td><?= e($row['type_label'] ?? '') ?></td>
            <td><?= e($companyName !== '' ? $companyName : '—') ?></td>
            <td class="eval-grade"><strong><?= e($studentGrade) ?></strong></td>
            <td><?= e($studentSubmitted) ?></td>
            <td>
                <button type="button" class="btn btn-small" data-eval-student-view="<?= e($studentViewUrl . '&partial=content') ?>">View</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <div class="pagination"></div>
    <?php endif; ?>
</section>
</div>
