<?php
    $finalRequirement = $finalRequirement ?? [];
    $finalSections = $finalSections ?? [];
    $docIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>';
    $allSubmitted = true;
    foreach (array_keys($finalSections) as $sectionKey) {
        if ((string)($finalRequirement[$sectionKey . '_status'] ?? 'pending') !== 'submitted') {
            $allSubmitted = false;
            break;
        }
    }
?>
<section class="card final-req-card">
    <div class="section-head final-req-head">
        <span class="final-req-step-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/></svg>
        </span>
        <div>
            <h2>Student - Submit the Following</h2>
            <p class="muted">Please input the required information below.</p>
        </div>
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
                        $actionLabel = $status === 'submitted' ? 'Edit' : 'Input';
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
                            <a class="btn btn-primary btn-small" href="index.php?r=student_documents_final&amp;doc=<?= e($key) ?>"><?= e($actionLabel) ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="status-callout <?= $allSubmitted ? 'success' : 'info' ?> final-req-note">
        <?php if ($allSubmitted): ?>
            <strong>All final requirements submitted</strong>
            <p>You have completed all the required documents. You may still edit any entry.</p>
        <?php else: ?>
            <strong>Action needed</strong>
            <p>Please input all required information to complete your final requirements.</p>
        <?php endif; ?>
    </div>
</section>

<?php 
    $studentEvaluation = $studentEvaluation ?? [];
    $evaluationSections = $evaluationSections ?? [];
    $evalIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v6M12 17v6M4.22 4.22l4.24 4.24M15.54 15.54l4.24 4.24M1 12h6M17 12h6M4.22 19.78l4.24-4.24M15.54 8.46l4.24-4.24"/></svg>';
?>
<section class="card final-req-card">
    <div class="section-head final-req-head">
        <span class="final-req-step-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v6M12 17v6M4.22 4.22l4.24 4.24M15.54 15.54l4.24 4.24M1 12h6M17 12h6M4.22 19.78l4.24-4.24M15.54 8.46l4.24-4.24"/></svg>
        </span>
        <div>
            <h2>2. Evaluations</h2>
            <p class="muted">Evaluate your overall performance during OJT.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table final-req-table" data-no-enhance>
            <thead>
                <tr><th>Evaluation</th><th>Description</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($evaluationSections as $key => $section): ?>
                    <?php
                        $status = StudentEvaluation::statusFor($studentEvaluation, $key);
                        $statusLabel = $status === 'submitted' ? 'Completed' : 'Pending';
                        $actionLabel = $status === 'submitted' ? 'Edit' : 'Evaluate';
                    ?>
                    <tr>
                        <td>
                            <span class="final-req-doc">
                                <span class="final-req-doc-icon final-eval-icon"><?= $evalIcon ?></span>
                                <strong><?= e($section['name']) ?></strong>
                            </span>
                        </td>
                        <td class="final-req-desc"><?= e($section['description']) ?></td>
                        <td><span class="badge <?= e($status) ?>"><?= e($statusLabel) ?></span></td>
                        <td>
                            <a class="btn btn-primary btn-small" href="index.php?r=student_documents_final&amp;eval=<?= e($key) ?>"><?= e($actionLabel) ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
