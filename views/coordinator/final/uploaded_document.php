<?php
    $requirement = $requirement ?? [];
    $student = $student ?? [];
    $studentId = (int)($student['id'] ?? 0);
    $status = (string)($requirement['status'] ?? 'pending');
    $filePath = (string)($requirement['file_path'] ?? '');
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

    <?php if ($filePath !== ''): ?>
        <a class="btn btn-primary" target="_blank" href="<?= e(asset($filePath)) ?>">View uploaded file</a>
    <?php else: ?>
        <p class="muted">No file uploaded.</p>
    <?php endif; ?>

    <?php if (!empty($requirement['review_notes'])): ?>
        <div class="requirement-review-note">
            <strong>Review notes:</strong> <?= e($requirement['review_notes']) ?>
        </div>
    <?php endif; ?>
</section>
