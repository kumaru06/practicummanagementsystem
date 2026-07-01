<?php
    $statusIcons = [
        'approved' => '<svg class="requirement-status-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>',
        'rejected' => '<svg class="requirement-status-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/></svg>',
        'pending'  => '<svg class="requirement-status-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 17h-.01v-2H12v2Zm0-4h-.01V7H12v6z"/></svg>',
    ];
    $predeploymentStatus = $enrollment['predeployment_status'] ?? 'not_submitted';

    $allRequirementsApproved = true;
    $allRequirementsUploaded = true;
    $hasRejectedRequirements = false;
    $hasBulkUploadSlots = false;
    $approvedCount = 0;
    $totalRequirements = count($requirements);
    $studentModelForBulk = !empty($student) ? new Student(db()) : null;
    foreach ($requirements as $checkReq) {
        if (empty($checkReq['file_path'])) $allRequirementsUploaded = false;
        if (!empty($checkReq['file_path']) && ($checkReq['status'] ?? '') === 'approved') $approvedCount++;
        if (empty($checkReq['file_path']) || ($checkReq['status'] ?? '') !== 'approved') $allRequirementsApproved = false;
        if (!empty($checkReq['file_path']) && ($checkReq['status'] ?? '') === 'rejected') $hasRejectedRequirements = true;
    }
    if ($studentModelForBulk && !empty($student)) {
        foreach (array_keys($requirements) as $bulkKey) {
            if ($studentModelForBulk->canUploadRequirement((int)$student['id'], (string)$bulkKey)) {
                $hasBulkUploadSlots = true;
                break;
            }
        }
    }
    $progressPct = $totalRequirements > 0 ? round(($approvedCount / $totalRequirements) * 100) : 0;
?>
<section class="card requirements-card">
    <div class="section-head section-head-split">
        <div>
            <h2>Pre-Deployment Requirements</h2>
            <p class="muted">Upload all required documents, then submit them for coordinator review. If one file is rejected, only that file needs to be corrected.</p>
        </div>
        <span class="badge <?= e($predeploymentStatus) ?>"><?= e(str_replace('_', ' ', $predeploymentStatus)) ?></span>
    </div>

    <div class="requirements-progress">
        <div class="requirements-progress-head">
            <span><strong><?= (int)$approvedCount ?></strong> of <strong><?= (int)$totalRequirements ?></strong> documents approved</span>
            <span class="requirements-progress-pct"><?= (int)$progressPct ?>%</span>
        </div>
        <div class="requirements-progress-track"><span style="width: <?= (int)$progressPct ?>%"></span></div>
    </div>

    <?php if ($hasBulkUploadSlots): ?>
    <form method="post" enctype="multipart/form-data" class="bulk-requirement-uploader">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="student_upload_requirements_bulk">
        <div class="bulk-upload-head">
            <div>
                <span class="bulk-upload-eyebrow">Upload faster</span>
                <h3>Upload multiple requirements at once</h3>
                <p class="muted">Choose files for all available requirements, then submit once. Accepted files: PDF, JPG, PNG, max 8MB each.</p>
            </div>
            <button class="btn btn-primary" type="submit">Upload Selected Files</button>
        </div>
        <div class="bulk-upload-grid">
            <?php foreach ($requirements as $bulkKey => $bulkReq): ?>
                <?php
                    $canBulkUpload = $studentModelForBulk && $studentModelForBulk->canUploadRequirement((int)$student['id'], (string)$bulkKey);
                    $bulkMessage = $studentModelForBulk ? $studentModelForBulk->requirementUploadMessage((int)$student['id'], (string)$bulkKey) : 'Enrollment required';
                ?>
                <label class="bulk-upload-item<?= $canBulkUpload ? '' : ' is-locked' ?>">
                    <span><?= e($bulkReq['requirement_name']) ?></span>
                    <?php if ($canBulkUpload): ?>
                        <input type="file" name="requirements[<?= e($bulkKey) ?>]" accept=".pdf,.jpg,.jpeg,.png">
                    <?php else: ?>
                        <em><?= e($bulkMessage) ?></em>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </div>
    </form>
    <?php endif; ?>

    <div class="requirements-list">
        <?php foreach ($requirements as $key => $req): ?>
            <?php
                $requirementStatus = $req['status'] ?? 'pending';
                $hasRequirementFile = !empty($req['file_path']);
                $iconStatus = $hasRequirementFile ? $requirementStatus : 'pending';
                $statusIcon = $statusIcons[$iconStatus] ?? $statusIcons['pending'];
                $canUploadRequirement = false;
                $uploadStatusLabel = 'Enrollment required';
                if (!empty($student)) {
                    $studentModel = new Student(db());
                    $canUploadRequirement = $studentModel->canUploadRequirement((int)$student['id'], (string)$key);
                    $uploadStatusLabel = $studentModel->requirementUploadMessage((int)$student['id'], (string)$key);
                }
            ?>
            <article class="requirement-card status-<?= e($hasRequirementFile ? $requirementStatus : 'empty') ?>">
                <div class="requirement-card-top">
                    <div class="requirement-card-head">
                        <span class="requirement-status-icon icon-<?= e($iconStatus) ?>"><?= $statusIcon ?></span>
                        <div class="requirement-card-info">
                            <h4><?= e($req['requirement_name']) ?></h4>
                            <?php if (!empty($req['notes'])): ?><p class="requirement-card-notes"><?= e($req['notes']) ?></p><?php endif; ?>
                            <?php if ($key === 'guardian_consent'): ?>
                                <a class="requirement-template-link" href="<?= e(asset('template/PARENT GUARDIAN_(OJT) CONSENT FORM.docx')) ?>" download>Download template</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="badge <?= e($requirementStatus) ?>"><?= e(str_replace('_', ' ', $requirementStatus)) ?></span>
                </div>

                <?php if (!empty($req['review_notes'])): ?>
                    <div class="requirement-review-note">
                        <svg class="requirement-note-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                        <span><strong>Coordinator note:</strong> <?= e($req['review_notes']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="requirement-card-actions">
                    <?php if ($hasRequirementFile): ?>
                        <a class="requirement-file-chip" target="_blank" href="<?= e(asset($req['file_path'])) ?>">
                            <svg class="requirement-file-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5Z"/></svg>
                            View file
                        </a>
                    <?php else: ?>
                        <span class="requirement-empty-chip">Not uploaded yet</span>
                    <?php endif; ?>

                    <?php if ($canUploadRequirement): ?>
                    <form method="post" enctype="multipart/form-data" class="requirement-upload-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="student_upload_requirement">
                        <input type="hidden" name="requirement_key" value="<?= e($key) ?>">
                        <input required type="file" name="requirement_file" accept=".pdf,.jpg,.jpeg,.png">
                        <button class="btn btn-small" type="submit"><?= $predeploymentStatus === 'needs_revision' ? 'Replace File' : 'Upload' ?></button>
                    </form>
                    <?php else: ?>
                        <span class="requirement-lock">
                            <svg class="requirement-lock-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18 8h-1V6a5 5 0 0 0-10 0v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2Zm-7 8.5V17h2v-1.5a1.5 1.5 0 1 0-2 0ZM9 8V6a3 3 0 0 1 6 0v2H9Z"/></svg>
                            <?= e($uploadStatusLabel) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (empty($enrollment)): ?>
        <div class="status-callout info"><strong>Enrollment required</strong><p>Your coordinator must enroll you in OJT before pre-deployment submission unlocks.</p><button class="btn btn-primary" type="button" disabled>Submit for Review Locked</button></div>
    <?php elseif ($allRequirementsApproved): ?>
        <div class="status-callout success"><strong>All documents approved</strong><p>You're all set — every pre-deployment requirement has been approved by your coordinator.</p><button class="btn btn-primary" type="button" disabled>Documents Already Approved</button></div>
    <?php elseif ($hasRejectedRequirements): ?>
        <div class="status-callout warning"><strong>Revision required</strong><p>Only the rejected document is unlocked. Replace it first, then it will return to coordinator review automatically.</p><button class="btn btn-primary" type="button" disabled>Fix Rejected Document</button></div>
    <?php elseif ($predeploymentStatus === 'submitted'): ?>
        <div class="status-callout info"><strong>Documents under review</strong><p>You already submitted your requirements. The button is locked to prevent duplicate submissions.</p><button class="btn btn-primary" type="button" disabled>Already Submitted</button></div>
    <?php elseif ($predeploymentStatus === 'not_submitted' && $allRequirementsUploaded): ?>
        <form method="post" class="status-callout success"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="student_submit_requirements"><strong>Ready to submit</strong><p>All required documents have been uploaded. Submit them for coordinator review.</p><button class="btn btn-primary" type="submit">Submit for Review</button></form>
    <?php else: ?>
        <div class="status-callout info"><strong>Upload all requirements first</strong><p>Submit for Review will unlock after all required documents have been uploaded.</p><button class="btn btn-primary" type="button" disabled>Submit for Review Locked</button></div>
    <?php endif; ?>
</section>
