<?php
    $stages = $stages ?? [];
    $activeStage = (int)($activeStage ?? 1);
    $statusMeta = [
        'approved'       => ['label' => 'Approved',       'cls' => 'is-approved'],
        'submitted'      => ['label' => 'Under review',   'cls' => 'is-submitted'],
        'needs_revision' => ['label' => 'Needs revision', 'cls' => 'is-rejected'],
        'in_progress'    => ['label' => 'In progress',    'cls' => 'is-progress'],
        'not_started'    => ['label' => 'Not started',    'cls' => 'is-idle'],
        'locked'         => ['label' => 'Locked',         'cls' => 'is-locked'],
    ];
    $stageDescriptions = [
        1 => 'Upload your pre-deployment requirements, then submit them for coordinator review.',
        2 => 'View your endorsement letter and upload your signed confidentiality agreement.',
        3 => 'Submit your during and post-OJT documents and complete your self-evaluations when your OJT hours are complete.',
    ];

    $currentStage = $stages[$activeStage] ?? null;
    $headStatusMeta = $currentStage ? ($statusMeta[$currentStage['status']] ?? $statusMeta['locked']) : $statusMeta['locked'];
    $stageLabel = $currentStage['label'] ?? ('Stage ' . $activeStage);
    $stageRequirements = $currentStage['requirements'] ?? [];
    $stageItemCount = count($stageRequirements);
    $stageSavedCount = count(array_filter($stageRequirements, static function ($req) {
        if (($req['kind'] ?? 'upload') === 'evaluation') {
            return ($req['status'] ?? '') === 'approved';
        }
        return !empty($req['file_path']);
    }));
    $activePanel = $activePanel ?? '';
    $activeKind = $activeKind ?? '';
    $showPanelForm = $activeStage === 3 && $activePanel !== '';
?>
<div class="docs-comply-page docs-comply-page--single">
    <?php if (!$showPanelForm): ?>
    <header class="docs-comply-head">
        <div class="docs-comply-head__main">
            <span class="docs-comply-eyebrow">OJT Documents · Stage <?= (int)$activeStage ?> of 3</span>
            <h1><?= e($stageLabel) ?></h1>
            <p class="muted"><?= e($stageDescriptions[$activeStage] ?? '') ?></p>
        </div>
        <div class="docs-comply-head__aside">
            <span class="badge docs-stage-badge <?= e($headStatusMeta['cls']) ?>"><?= e($headStatusMeta['label']) ?></span>
            <div class="docs-stage-stats" aria-label="Stage summary">
                <div class="docs-stage-stat">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5Z"/></svg>
                    <div><strong><?= (int)$stageItemCount ?></strong><span>Items</span></div>
                </div>
                <div class="docs-stage-stat">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm-8.71 12.29-3.3-3.29 1.42-1.42 1.88 1.88 5.46-5.46 1.42 1.42-6.88 6.87Z"/></svg>
                    <div><strong><?= (int)$stageSavedCount ?></strong><span>Saved</span></div>
                </div>
                <div class="docs-stage-stat">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10.01 10.01 0 0 0 12 2Zm1 15h-2v-2h2Zm0-4h-2V7h2Z"/></svg>
                    <div><strong><?= e($headStatusMeta['label']) ?></strong><span>Status</span></div>
                </div>
            </div>
        </div>
    </header>

    <?php require __DIR__ . '/partials/documents_workflow_stepper.php'; ?>

    <?php if ($activeStage === 1): ?>
        <section class="docs-stage" id="stage-1">
            <?php require __DIR__ . '/partials/predeployment_requirements.php'; ?>
        </section>
    <?php elseif ($currentStage): ?>
        <?php $accessible = (bool)$currentStage['accessible']; ?>
        <section class="card requirements-card docs-stage docs-stage--<?= (int)$activeStage ?><?= $accessible ? '' : ' is-locked' ?>" id="stage-<?= (int)$activeStage ?>">
            <div class="section-head section-head-split">
                <div>
                    <h2><?= $activeStage === 2 ? 'Endorsement &amp; Confidentiality' : 'During &amp; Post-OJT Documents' ?></h2>
                    <p class="muted">
                        <?= $activeStage === 2
                            ? 'Your endorsement letter appears here once your coordinator forwards your documents. Upload your signed confidentiality agreement for review.'
                            : 'Complete each document below. Fill out forms or upload files for review, and finish self-evaluations when unlocked.' ?>
                    </p>
                </div>
            </div>

            <?php if (!$accessible): ?>
                <div class="docs-stage-lock">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18 8h-1V6a5 5 0 0 0-10 0v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2Zm-6 9a2 2 0 1 1 0-4 2 2 0 0 1 0 4ZM9 8V6a3 3 0 0 1 6 0v2H9Z"/></svg>
                    <div>
                        <strong>Stage locked</strong>
                        <p><?= e($currentStage['lock_message']) ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="requirements-list">
                    <?php foreach ($currentStage['requirements'] as $key => $req): ?>
                        <?php
                            $reqKind = $req['kind'] ?? 'upload';
                            $isEvaluation = $reqKind === 'evaluation';
                            $isForm = $reqKind === 'form';
                            $evalKey = (string)($req['evaluation_key'] ?? '');
                            $formKey = (string)($req['form_key'] ?? '');
                            $reqStatus = $req['status'] ?? 'pending';
                            $viewUrl = trim((string)($req['view_url'] ?? ''));
                            $hasFile = !$isEvaluation && (!empty($req['file_path']) || $viewUrl !== '');
                            $fileHref = $viewUrl !== ''
                                ? $viewUrl
                                : (!empty($req['file_path']) && !requirement_is_form_path((string)$req['file_path'])
                                    ? asset((string)$req['file_path'])
                                    : '');
                            $evalComplete = $isEvaluation && $reqStatus === 'approved';
                            $iconStatus = ($hasFile || $evalComplete) ? ($evalComplete ? 'approved' : $reqStatus) : 'pending';
                            $owner = $req['owner'] ?? 'student';
                            $canUpload = !$isEvaluation && !empty($req['can_upload']);
                            $uploadMessage = (string)($req['upload_message'] ?? '');
                            $cardStatus = ($hasFile || $evalComplete) ? ($evalComplete ? 'approved' : $reqStatus) : 'empty';
                            $evalLocked = $isEvaluation && !($canAccessFinalRequirements ?? false);
                        ?>
                        <article class="requirement-card status-<?= e($cardStatus) ?><?= $isEvaluation ? ' requirement-card--evaluation' : '' ?><?= $isForm ? ' requirement-card--form' : '' ?>" id="requirement-<?= e($key) ?>">
                            <div class="requirement-card-top">
                                <div class="requirement-card-head">
                                    <span class="requirement-status-icon <?= e(requirement_card_icon_class((string)$key)) ?>"><?= requirement_card_icon((string)$key) ?></span>
                                    <div class="requirement-card-info">
                                        <h4><?= e($req['requirement_name']) ?></h4>
                                        <?php if (!empty($req['notes'])): ?><p class="requirement-card-notes"><?= e($req['notes']) ?></p><?php endif; ?>
                                        <?php if ($isEvaluation): ?>
                                            <span class="requirement-owner-chip">Self-evaluation</span>
                                        <?php elseif ($isForm): ?>
                                            <span class="requirement-owner-chip">Fill-out form</span>
                                        <?php elseif ($owner === 'coordinator'): ?>
                                            <span class="requirement-owner-chip">Provided by coordinator</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="badge <?= e($evalComplete ? 'approved' : $reqStatus) ?>"><?= e($evalComplete ? 'completed' : str_replace('_', ' ', $reqStatus)) ?></span>
                            </div>

                            <?php if (!$isEvaluation && !empty($req['review_notes'])): ?>
                                <div class="requirement-review-note">
                                    <svg class="requirement-note-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                                    <span><strong>Coordinator note:</strong> <?= e($req['review_notes']) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="requirement-card-actions">
                                <?php if ($isEvaluation): ?>
                                    <?php if ($evalComplete): ?>
                                        <span class="requirement-empty-chip requirement-empty-chip--success">Evaluation completed</span>
                                    <?php elseif ($evalLocked): ?>
                                        <span class="requirement-lock">
                                            <svg class="requirement-lock-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18 8h-1V6a5 5 0 0 0-10 0v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2Zm-7 8.5V17h2v-1.5a1.5 1.5 0 1 0-2 0ZM9 8V6a3 3 0 0 1 6 0v2H9Z"/></svg>
                                            <?= e($finalRequirementsLockMessage ?? 'Unlocks when required OJT hours are complete.') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="requirement-empty-chip">Not completed yet</span>
                                    <?php endif; ?>

                                    <?php if (!$evalLocked): ?>
                                        <a class="btn btn-small" href="<?= e(route_url('student.documents', ['stage' => 3, 'eval' => $evalKey])) ?>">
                                            <?= $evalComplete ? 'Edit Evaluation' : 'Start Evaluation' ?>
                                        </a>
                                    <?php endif; ?>
                                <?php elseif ($isForm): ?>
                                    <?php if ($hasFile): ?>
                                        <span class="requirement-empty-chip<?= $reqStatus === 'approved' ? ' requirement-empty-chip--success' : '' ?>">
                                            <?= $reqStatus === 'approved' ? 'Form approved' : ($reqStatus === 'rejected' ? 'Needs revision' : 'Submitted for review') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="requirement-empty-chip">Not filled out yet</span>
                                    <?php endif; ?>
                                    <a class="btn btn-small" href="<?= e(route_url('student.documents', ['stage' => 3, 'doc' => $formKey !== '' ? $formKey : $key])) ?>">
                                        <?= $canUpload ? ($hasFile ? 'Edit Form' : 'Fill Out') : 'View Form' ?>
                                    </a>
                                <?php else: ?>
                                    <?php if ($hasFile && $fileHref !== ''): ?>
                                        <a class="requirement-file-chip" target="_blank" href="<?= e($fileHref) ?>">
                                            <svg class="requirement-file-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5Z"/></svg>
                                            View file
                                        </a>
                                    <?php elseif ($owner === 'coordinator'): ?>
                                        <span class="requirement-empty-chip">Waiting for coordinator</span>
                                    <?php else: ?>
                                        <span class="requirement-empty-chip">Not uploaded yet</span>
                                    <?php endif; ?>

                                    <?php if ($owner === 'student' && $canUpload): ?>
                                        <form method="post" enctype="multipart/form-data" class="requirement-upload-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="student_upload_requirement">
                                            <input type="hidden" name="requirement_key" value="<?= e($key) ?>">
                                            <input required type="file" name="requirement_file" accept=".pdf,.jpg,.jpeg,.png">
                                            <button class="btn btn-small" type="submit"><?= $reqStatus === 'rejected' ? 'Replace File' : 'Upload' ?></button>
                                        </form>
                                    <?php elseif ($owner === 'student' && !$canUpload && $uploadMessage !== ''): ?>
                                        <span class="requirement-lock">
                                            <svg class="requirement-lock-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18 8h-1V6a5 5 0 0 0-10 0v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2Zm-7 8.5V17h2v-1.5a1.5 1.5 0 1 0-2 0ZM9 8V6a3 3 0 0 1 6 0v2H9Z"/></svg>
                                            <?= e($uploadMessage) ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($showPanelForm): ?>
        <?php if (($activeKind ?? '') === 'eval'): ?>
            <?php require __DIR__ . '/partials/stage3_evaluation_form.php'; ?>
        <?php else: ?>
            <?php require __DIR__ . '/partials/stage3_form_panel.php'; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script>
(() => {
    const hash = window.location.hash;
    if (!hash.startsWith('#requirement-')) return;
    const target = document.querySelector(hash);
    if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.add('requirement-card--focused');
    }
})();
</script>
