<?php
$finalEvaluations = $finalEvaluations ?? [];
$studentFeedback = $studentFeedback ?? [];
?>
<div class="partner-evaluations-page">
    <section class="pe-hero card">
        <div>
            <h2>Evaluations History</h2>
            <p class="muted">Final evaluations you submitted and feedback students shared about your organization.</p>
        </div>
    </section>

    <section class="card pe-section">
        <div class="pe-section-head">
            <div>
                <h3>Final Evaluations You Submitted</h3>
                <p class="muted">Your official student performance evaluations and certificates.</p>
            </div>
            <span class="pe-count"><?= count($finalEvaluations) ?></span>
        </div>
        <?php if (empty($finalEvaluations)): ?>
            <div class="pe-empty"><p class="muted">No final evaluations submitted yet.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Final Grade</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($finalEvaluations as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= e($row['student_name']) ?></strong>
                                    <small class="muted block"><?= e($row['student_no']) ?></small>
                                </td>
                                <td><?= e(trim(($row['course'] ?? '') . ' ' . ($row['year_level'] ?? ''))) ?></td>
                                <td><strong><?= e(number_format((float)($row['final_grade'] ?? 0), 2)) ?>%</strong></td>
                                <td><?= !empty($row['submitted_at']) ? e(date('M j, Y', strtotime($row['submitted_at']))) : '—' ?></td>
                                <td class="pe-actions">
                                    <a class="btn btn-small" href="<?= e(route_url('partner.evaluate', ['enrollment' => (int)$row['enrollment_id']])) ?>">View / Edit</a>
                                    <a class="btn btn-small" href="<?= e(route_url('partner.portal', ['enrollment' => (int)$row['enrollment_id']])) ?>">Portal</a>
                                    <?php if (!empty($row['certificate_file'])): ?>
                                        <a class="btn btn-small" target="_blank" href="<?= e(asset($row['certificate_file'])) ?>">Certificate</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card pe-section">
        <div class="pe-section-head">
            <div>
                <h3>Student Feedback About Your Organization</h3>
                <p class="muted">Evaluations students submitted about your organization and OJT supervisor.</p>
            </div>
            <span class="pe-count"><?= count($studentFeedback) ?></span>
        </div>
        <?php if (empty($studentFeedback)): ?>
            <div class="pe-empty"><p class="muted">No student feedback submitted yet.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Overall Rating</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentFeedback as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= e($row['student_name']) ?></strong>
                                    <small class="muted block"><?= e($row['student_no']) ?></small>
                                </td>
                                <td><?= e(trim(($row['course'] ?? '') . ' ' . ($row['year_level'] ?? ''))) ?></td>
                                <td><strong><?= e(number_format((float)($row['partner_grade'] ?? 0), 2)) ?>%</strong></td>
                                <td><?= !empty($row['updated_at']) ? e(date('M j, Y', strtotime($row['updated_at']))) : '—' ?></td>
                                <td class="pe-actions">
                                    <a class="btn btn-small" href="<?= e(route_url('partner.student_evaluation', ['student_id' => (int)$row['student_id']])) ?>">View Feedback</a>
                                    <a class="btn btn-small" href="<?= e(route_url('partner.portal', ['enrollment' => (int)$row['enrollment_id']])) ?>">Portal</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
