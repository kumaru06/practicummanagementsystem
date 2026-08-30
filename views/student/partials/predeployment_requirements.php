<?php
    $predeploymentStatus = $enrollment['predeployment_status'] ?? ($predeploymentStatus ?? 'not_submitted');

    $studentRequirements = array_filter($requirements, static fn (array $req): bool => ($req['owner'] ?? 'student') === 'student');
    $allRequirementsApproved = true;
    $allRequirementsUploaded = true;
    $hasRejectedRequirements = false;
    $hasBulkUploadSlots = false;
    $approvedCount = 0;
    $totalRequirements = count($studentRequirements);
    $uploadedCount = 0;
    $studentModelForBulk = !empty($student) ? new Student(db()) : null;
    foreach ($studentRequirements as $checkReq) {
        $hasFile = !empty($checkReq['file_path']);
        if (!$hasFile) {
            $allRequirementsUploaded = false;
        } else {
            $uploadedCount++;
        }
        if ($hasFile && ($checkReq['status'] ?? '') === 'approved') $approvedCount++;
        if (!$hasFile || ($checkReq['status'] ?? '') !== 'approved') $allRequirementsApproved = false;
        if ($hasFile && ($checkReq['status'] ?? '') === 'rejected') $hasRejectedRequirements = true;
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
    $uploadedPct = $totalRequirements > 0 ? round(($uploadedCount / $totalRequirements) * 100) : 0;
?>
<section class="card requirements-card">
    <div class="predeploy-panel">
        <div class="predeploy-panel__top">
            <div class="predeploy-panel__copy">
                <div class="predeploy-panel__meta">
                    <span class="docs-stage-eyebrow">Stage 1 · Pre-deployment</span>
                    <span class="badge docs-stage-badge predeploy-status-badge predeploy-status-badge--<?= e($predeploymentStatus) ?>"><?= e(str_replace('_', ' ', $predeploymentStatus)) ?></span>
                </div>
                <h2>Pre-Deployment Requirements</h2>
            </div>
            <div class="predeploy-panel__stats" aria-label="Pre-deployment summary">
                <div class="predeploy-stat predeploy-stat--uploaded">
                    <span class="predeploy-stat__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path fill="currentColor" d="M11 16V7.85L8.4 10.45 7 9l5-5 5 5-1.41 1.42L13 7.85V16h-2ZM5 18h14v2H5v-2Z"/></svg>
                    </span>
                    <strong><?= (int)$uploadedCount ?></strong>
                    <span>Uploaded</span>
                </div>
                <div class="predeploy-stat predeploy-stat--approved">
                    <span class="predeploy-stat__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2Zm-2 14-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8Z"/></svg>
                    </span>
                    <strong><?= (int)$approvedCount ?></strong>
                    <span>Approved</span>
                </div>
                <div class="predeploy-stat predeploy-stat--needed">
                    <span class="predeploy-stat__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path fill="currentColor" d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm-8 2h7v2h-7V5Zm0 4h7v2h-7V9Zm-7 4h14v2H4v-2Z"/></svg>
                    </span>
                    <strong><?= (int)$totalRequirements ?></strong>
                    <span>Needed</span>
                </div>
            </div>
        </div>

        <div class="predeploy-panel__progress requirements-progress requirements-progress--modern">
            <div class="requirements-progress-head">
                <span><strong><?= (int)$approvedCount ?></strong> of <strong><?= (int)$totalRequirements ?></strong> required documents approved</span>
                <span class="requirements-progress-pct"><?= (int)$progressPct ?>%</span>
            </div>
            <div class="requirements-progress-track"><span style="width: <?= (int)$progressPct ?>%"></span></div>
        </div>
    </div>

    <?php
        $predeploymentAdvanced = in_array($predeploymentStatus, ['approved', 'forwarded', 'accepted', 'orientation_scheduled', 'orientation_completed'], true);
        $hasMissingUploads = $uploadedCount < $totalRequirements;
    ?>
    <?php if ($hasMissingUploads && $predeploymentAdvanced && !$hasRejectedRequirements): ?>
        <div class="status-callout warning">
            <strong>Additional document required</strong>
            <p>New requirements were added to 1st to Comply. Please upload any missing required files so your coordinator can review them.</p>
        </div>
    <?php endif; ?>

    <?php if ($hasBulkUploadSlots): ?>
    <form method="post" enctype="multipart/form-data" class="bulk-requirement-uploader">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="student_upload_requirements_bulk">
        <div class="bulk-upload-head">
            <div>
                <span class="bulk-upload-eyebrow">Quick upload</span>
                <h3>Upload multiple requirements at once</h3>
                <p class="muted">Choose files for all available requirements, then submit once. Accepted files: PDF, JPG, PNG. Max 8MB each.</p>
            </div>
            <button class="btn btn-primary" type="submit">Upload Selected Files</button>
        </div>
        <div class="bulk-upload-grid">
            <?php foreach ($requirements as $bulkKey => $bulkReq): ?>
                <?php
                    $canBulkUpload = $studentModelForBulk && $studentModelForBulk->canUploadRequirement((int)$student['id'], (string)$bulkKey);
                    $bulkMessage = $studentModelForBulk ? $studentModelForBulk->requirementUploadMessage((int)$student['id'], (string)$bulkKey) : 'Ready to upload';
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
                $displayStatus = $hasRequirementFile ? $requirementStatus : 'not_uploaded';
                $iconStatus = $hasRequirementFile ? $requirementStatus : 'pending';
                $canUploadRequirement = false;
                $uploadStatusLabel = 'Ready to upload';
                if (!empty($student)) {
                    $studentModel = new Student(db());
                    $canUploadRequirement = $studentModel->canUploadRequirement((int)$student['id'], (string)$key);
                    $uploadStatusLabel = $studentModel->requirementUploadMessage((int)$student['id'], (string)$key);
                }
            ?>
            <article class="requirement-card status-<?= e($hasRequirementFile ? $requirementStatus : 'empty') ?>" id="requirement-<?= e($key) ?>">
                <div class="requirement-card-top">
                    <div class="requirement-card-head">
                        <span class="requirement-status-icon <?= e(requirement_card_icon_class((string)$key)) ?>"><?= requirement_card_icon((string)$key) ?></span>
                        <div class="requirement-card-info">
                            <h4><?= e($req['requirement_name']) ?></h4>
                            <?php if (!empty($req['notes'])): ?><p class="requirement-card-notes"><?= e($req['notes']) ?></p><?php endif; ?>
                            <?php if ($key === 'guardian_consent'): ?>
                                <a class="requirement-template-link" href="<?= e(asset('template/PARENT GUARDIAN_(OJT) CONSENT FORM.docx')) ?>" download>Download template</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="badge <?= e($displayStatus) ?>"><?= e(str_replace('_', ' ', $displayStatus)) ?></span>
                </div>

                <?php if (!empty($req['review_notes'])): ?>
                    <div class="requirement-review-note">
                        <svg class="requirement-note-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                        <span><strong>Coordinator note:</strong> <?= e($req['review_notes']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="requirement-card-actions">
                    <?php if ($hasRequirementFile): ?>
                        <a class="requirement-file-chip" target="_blank" href="<?= e(asset($req['file_path'])) ?>">View file</a>
                    <?php else: ?>
                        <span class="requirement-empty-chip">Not uploaded yet</span>
                    <?php endif; ?>

                    <?php if ($canUploadRequirement): ?>
                    <form method="post" enctype="multipart/form-data" class="requirement-upload-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="student_upload_requirement">
                        <input type="hidden" name="requirement_key" value="<?= e($key) ?>">
                        <input required type="file" name="requirement_file" accept=".pdf,.jpg,.jpeg,.png">
                        <button class="btn btn-small" type="submit"><?= $requirementStatus === 'rejected' ? 'Replace File' : 'Upload' ?></button>
                    </form>
                    <?php elseif (!$hasRequirementFile): ?>
                        <span class="requirement-lock">
                            <svg class="requirement-lock-svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18 8h-1V6a5 5 0 0 0-10 0v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2Zm-7 8.5V17h2v-1.5a1.5 1.5 0 1 0-2 0ZM9 8V6a3 3 0 0 1 6 0v2H9Z"/></svg>
                            <?= e($uploadStatusLabel) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($allRequirementsApproved): ?>
        <div class="status-callout success"><strong>All documents approved</strong><p>You're all set — every pre-deployment requirement has been approved by your coordinator.</p><button class="btn btn-primary" type="button" disabled>Documents Already Approved</button></div>
    <?php elseif ($hasRejectedRequirements): ?>
        <div class="status-callout warning"><strong>Revision required</strong><p>Only the rejected document is unlocked. Upload a corrected file — it will automatically return to coordinator review. No need to press Submit for Review again.</p><button class="btn btn-primary" type="button" disabled>Replace Rejected File</button></div>
    <?php elseif ($predeploymentStatus === 'needs_revision'): ?>
        <div class="status-callout warning"><strong>Revision required</strong><p>Replace the rejected document below. Once uploaded, it goes back to your coordinator automatically.</p><button class="btn btn-primary" type="button" disabled>Replace Rejected File</button></div>
    <?php elseif ($predeploymentStatus === 'submitted'): ?>
        <div class="status-callout info"><strong>Documents under review</strong><p>You already submitted your requirements. The button is locked to prevent duplicate submissions.</p><button class="btn btn-primary" type="button" disabled>Already Submitted</button></div>
    <?php elseif ($allRequirementsUploaded): ?>
        <form method="post" class="status-callout success"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="student_submit_requirements"><strong>Ready to submit</strong><p>All required documents have been uploaded. Submit them for coordinator review.</p><button class="btn btn-primary" type="submit">Submit for Review</button></form>
    <?php else: ?>
        <div class="status-callout info"><strong>Upload all requirements first</strong><p>Submit for Review will unlock after all required documents have been uploaded.</p><button class="btn btn-primary" type="button" disabled>Submit for Review Locked</button></div>
    <?php endif; ?>
</section>
