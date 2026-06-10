<?php
    $finalRequirement = $finalRequirement ?? [];
    $finalSections = $finalSections ?? [];
    $summary = (new FinalRequirement(db()))->overallSummary($finalRequirement);
    $docIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>';
?>
<a class="final-form-back" href="index.php?r=coordinator_students">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Back to My Students
</a>

<div class="status-callout info final-student-banner">
    <strong><?= e($student['name'] ?? 'Student') ?></strong>
    <p><?= e($student['student_no'] ?? '') ?> · Review final requirements and student evaluations (coordinator only — not visible to industry partners).</p>
</div>

<section class="card final-req-card">
    <div class="section-head final-req-head">
        <span class="final-req-step-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/></svg>
        </span>
        <div>
            <h2>Final Requirements — <?= e($student['name'] ?? 'Student') ?></h2>
            <p class="muted"><?= e($student['student_no'] ?? '') ?> · Review submitted post-OJT documents.</p>
        </div>
        <span class="badge <?= e($summary['class']) ?>"><?= e($summary['label']) ?></span>
    </div>

    <div class="table-wrap">
        <table class="data-table final-req-table" data-no-enhance>
            <thead>
                <tr><th>Document</th><th>Description</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($finalSections as $key => $section): ?>
                    <?php
                        $status = (string)($finalRequirement[$key . '_status'] ?? 'pending');
                        $status = $status !== '' ? $status : 'pending';
                        $statusLabel = $status === 'submitted' ? 'Submitted' : 'Pending';
                    ?>
                    <tr>
                        <td>
                            <span class="final-req-doc">
                                <span class="final-req-doc-icon"><?= $docIcon ?></span>
                                <strong><?= e($section['name']) ?></strong>
                            </span>
                        </td>
                        <td class="final-req-desc"><?= e($section['description']) ?></td>
                        <td><span class="badge <?= e($status) ?>"><?= e($statusLabel) ?></span></td>
                        <td>
                            <?php if ($status === 'submitted'): ?>
                                <a class="btn btn-primary btn-small" href="index.php?r=coordinator_student_final&amp;student_id=<?= (int)$student['id'] ?>&amp;doc=<?= e($key) ?>">View</a>
                            <?php else: ?>
                                <span class="muted">Not submitted</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="status-callout <?= $summary['submitted'] === $summary['total'] ? 'success' : 'info' ?> final-req-note">
        <?php if ($summary['submitted'] === $summary['total']): ?>
            <strong>All final requirements submitted</strong>
            <p>This student has completed all three final requirement documents.</p>
        <?php elseif ($summary['submitted'] > 0): ?>
            <strong>In progress</strong>
            <p><?= (int)$summary['submitted'] ?> of <?= (int)$summary['total'] ?> documents submitted so far.</p>
        <?php else: ?>
            <strong>Not started</strong>
            <p>This student has not submitted any final requirement documents yet.</p>
        <?php endif; ?>
    </div>
</section>

<?php
    $studentEvaluation = $studentEvaluation ?? [];
    $evaluationSections = $evaluationSections ?? FinalRequirement::EVALUATION_SECTIONS;
    $evalIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v6M12 17v6M4.22 4.22l4.24 4.24M15.54 15.54l4.24 4.24M1 12h6M17 12h6M4.22 19.78l4.24-4.24M15.54 8.46l4.24-4.24"/></svg>';
    $evalSubmitted = 0;
    foreach (array_keys($evaluationSections) as $evalKey) {
        if (StudentEvaluation::statusFor($studentEvaluation, $evalKey) === 'submitted') {
            $evalSubmitted++;
        }
    }
    $evalTotal = count($evaluationSections);
?>
<section class="card final-req-card">
    <div class="section-head final-req-head">
        <span class="final-req-step-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v6M12 17v6M4.22 4.22l4.24 4.24M15.54 15.54l4.24 4.24M1 12h6M17 12h6M4.22 19.78l4.24-4.24M15.54 8.46l4.24-4.24"/></svg>
        </span>
        <div>
            <h2>Student Evaluations</h2>
            <p class="muted">Visible to OJT coordinator only — industry partners cannot access these.</p>
        </div>
        <span class="badge <?= $evalSubmitted === $evalTotal ? 'submitted' : ($evalSubmitted > 0 ? 'pending' : 'not_submitted') ?>"><?= $evalSubmitted ?>/<?= $evalTotal ?> completed</span>
    </div>

    <div class="table-wrap">
        <table class="data-table final-req-table" data-no-enhance>
            <thead>
                <tr><th>Evaluation</th><th>Description</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($evaluationSections as $evalKey => $evalSection): ?>
                    <?php
                        $evalStatus = StudentEvaluation::statusFor($studentEvaluation, $evalKey);
                        $evalLabel = $evalStatus === 'submitted' ? 'Completed' : 'Pending';
                    ?>
                    <tr>
                        <td>
                            <span class="final-req-doc">
                                <span class="final-req-doc-icon final-eval-icon"><?= $evalIcon ?></span>
                                <strong><?= e($evalSection['name']) ?></strong>
                            </span>
                        </td>
                        <td class="final-req-desc"><?= e($evalSection['description']) ?></td>
                        <td><span class="badge <?= e($evalStatus) ?>"><?= e($evalLabel) ?></span></td>
                        <td>
                            <?php if ($evalStatus === 'submitted'): ?>
                                <a class="btn btn-primary btn-small" href="index.php?r=coordinator_student_final&amp;student_id=<?= (int)$student['id'] ?>&amp;eval=<?= e($evalKey) ?>">View</a>
                            <?php else: ?>
                                <span class="muted">Not submitted</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="status-callout <?= $evalSubmitted === $evalTotal ? 'success' : 'info' ?> final-req-note">
        <?php if ($evalSubmitted === $evalTotal): ?>
            <strong>All evaluations completed</strong>
            <p>This student has submitted both industry partner and coordinator evaluations.</p>
        <?php elseif ($evalSubmitted > 0): ?>
            <strong>In progress</strong>
            <p><?= (int)$evalSubmitted ?> of <?= (int)$evalTotal ?> student evaluations submitted so far.</p>
        <?php else: ?>
            <strong>Evaluations pending</strong>
            <p>This student has not submitted any evaluations yet.</p>
        <?php endif; ?>
    </div>
</section>
