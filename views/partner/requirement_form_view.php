<?php
    $requirement = $requirement ?? [];
    $student = $student ?? [];
    $formSection = (string)($formSection ?? '');
    $finalRequirement = $finalRequirement ?? [];
    $enrollmentId = (int)($student['id'] ?? 0);
?>
<section class="card final-readonly-card">
    <div class="section-head section-head-split">
        <div>
            <a class="muted" href="<?= e(route_url('partner.portal', ['enrollment' => $enrollmentId])) ?>">&larr; Back to student</a>
            <h2><?= e($requirement['requirement_name'] ?? 'Document') ?></h2>
            <p class="muted"><?= e($student['name'] ?? 'Student') ?></p>
        </div>
        <span class="badge approved">approved</span>
    </div>
    <?php require __DIR__ . '/../shared/requirement_form_readonly.php'; ?>
</section>
