<?php
    $requirement = $requirement ?? [];
    $requirementKey = (string)($requirementKey ?? ($requirement['requirement_key'] ?? ''));
    $student = $student ?? [];
    $studentId = (int)($student['id'] ?? 0);
    $status = (string)($requirement['status'] ?? 'pending');
    $filePath = (string)($requirement['file_path'] ?? '');
    $isForm = requirement_is_form_path($filePath);
    $formSection = $isForm ? requirement_form_section_key($requirementKey) : null;
    $finalRequirement = $finalRequirement ?? [];
    $canReview = $filePath !== '' && ($requirement['owner'] ?? 'student') === 'student'
        && in_array($status, ['uploaded', 'approved'], true);
    $canApprove = $canReview && $status === 'uploaded';
    $canReject = $canReview;
?>
<section class="card final-readonly-card">
    <div class="section-head section-head-split">
        <div>
            <a class="muted" href="index.php?r=coordinator_student_final&amp;student_id=<?= $studentId ?>">&larr; Back to final requirements</a>
            <h2><?= e($requirement['requirement_name'] ?? 'Document') ?></h2>
            <p class="muted"><?= e($requirement['notes'] ?? '') ?></p>
        </div>
        <span class="badge <?= e($status) ?>"><?= e(str_replace('_', ' ', $status)) ?></span>
    </div>

    <?php if ($isForm && $formSection): ?>
        <?php require __DIR__ . '/../../shared/requirement_form_readonly.php'; ?>
    <?php elseif ($filePath !== '' && !$isForm): ?>
        <a class="btn btn-primary" target="_blank" href="<?= e(asset($filePath)) ?>">View uploaded file</a>
    <?php else: ?>
        <p class="muted">No file uploaded.</p>
    <?php endif; ?>

    <?php if (!empty($requirement['review_notes'])): ?>
        <div class="requirement-review-note">
            <strong>Review notes:</strong> <?= e($requirement['review_notes']) ?>
        </div>
    <?php endif; ?>

    <?php if ($canReview): ?>
        <div class="requirement-review-actions" style="margin-top: 1.25rem; display: grid; gap: 0.75rem;">
            <p class="muted" style="margin: 0;"><?= $status === 'approved' ? 'This document was approved. You can revoke approval if it was reviewed by mistake.' : 'Review this submission to mark it approved or rejected.' ?></p>
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-start;">
                <?php if ($canApprove): ?>
                <form method="post" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="coordinator_review_requirement">
                    <input type="hidden" name="student_id" value="<?= $studentId ?>">
                    <input type="hidden" name="requirement_key" value="<?= e($requirementKey) ?>">
                    <input type="hidden" name="status" value="approved">
                    <button class="btn btn-primary" type="submit">Approve Document</button>
                </form>
                <?php endif; ?>
                <?php if ($canReject): ?>
                <form method="post" class="inline requirement-review-reject-form" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="coordinator_review_requirement">
                    <input type="hidden" name="student_id" value="<?= $studentId ?>">
                    <input type="hidden" name="requirement_key" value="<?= e($requirementKey) ?>">
                    <input type="hidden" name="status" value="rejected">
                    <input class="requirement-review-note" name="notes" placeholder="Reason for rejection" required maxlength="500">
                    <button class="btn" type="submit"><?= $status === 'approved' ? 'Revoke approval' : 'Reject' ?></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
