<section class="card">
    <div class="section-head section-head-split">
        <div><h2>Pre-Deployment Requirements</h2><p class="muted">Upload all required documents, then submit them for coordinator review. If one file is rejected, only that file needs to be corrected.</p></div>
        <span class="badge <?= e($enrollment['predeployment_status'] ?? 'not_submitted') ?>"><?= e(str_replace('_', ' ', $enrollment['predeployment_status'] ?? 'not_submitted')) ?></span>
    </div>
    <?php
        $allRequirementsApproved = true;
        $allRequirementsUploaded = true;
        $hasRejectedRequirements = false;
        $hasBulkUploadSlots = false;
        $studentModelForBulk = !empty($student) ? new Student(db()) : null;
        foreach ($requirements as $checkReq) {
            if (empty($checkReq['file_path'])) $allRequirementsUploaded = false;
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
    ?>
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
    <div class="table-wrap requirement-table-wrap"><table class="data-table"><thead><tr><th>Requirement</th><th>Notes</th><th>File</th><th>Status</th><th>Upload</th></tr></thead><tbody>
        <?php foreach ($requirements as $key => $req): ?>
            <?php
                $predeploymentStatus = $enrollment['predeployment_status'] ?? 'not_submitted';
                $requirementStatus = $req['status'] ?? 'pending';
                $hasRequirementFile = !empty($req['file_path']);
                $canUploadRequirement = false;
                $uploadStatusLabel = 'Enrollment required';
                if (!empty($student)) {
                    $studentModel = new Student(db());
                    $canUploadRequirement = $studentModel->canUploadRequirement((int)$student['id'], (string)$key);
                    $uploadStatusLabel = $studentModel->requirementUploadMessage((int)$student['id'], (string)$key);
                }
            ?>
            <tr>
                <td>
                    <?= e($req['requirement_name']) ?>
                    <?php if ($key === 'guardian_consent'): ?>
                        <a class="requirement-template-link" href="<?= e(asset('template/PARENT GUARDIAN_(OJT) CONSENT FORM.docx')) ?>" download>Download template</a>
                    <?php endif; ?>
                </td>
                <td><?= e($req['notes'] ?? '') ?></td>
                <td><?= !empty($req['file_path']) ? '<a class="btn btn-small" target="_blank" href="' . e(asset($req['file_path'])) . '">View</a>' : '<span class="muted">Not uploaded</span>' ?></td>
                <td>
                    <span class="badge <?= e($requirementStatus) ?>"><?= e(str_replace('_', ' ', $requirementStatus)) ?></span>
                    <?php if (!empty($req['review_notes'])): ?><div class="muted" style="margin-top:6px;font-size:.8rem;line-height:1.4;"><?= e($req['review_notes']) ?></div><?php endif; ?>
                </td>
                <td>
                    <?php if ($canUploadRequirement): ?>
                    <form method="post" enctype="multipart/form-data" class="inline" style="display:flex;gap:8px;align-items:center">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="student_upload_requirement">
                        <input type="hidden" name="requirement_key" value="<?= e($key) ?>">
                        <input required type="file" name="requirement_file" accept=".pdf,.jpg,.jpeg,.png">
                        <button class="btn btn-small" type="submit"><?= $predeploymentStatus === 'needs_revision' ? 'Replace File' : 'Upload' ?></button>
                    </form>
                    <?php else: ?><span class="muted"><?= e($uploadStatusLabel) ?></span><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <?php if (empty($enrollment)): ?>
        <div class="status-callout info" style="margin-top:16px"><strong>Enrollment required.</strong><p class="muted">Your coordinator must enroll you in OJT before pre-deployment submission unlocks.</p><button class="btn btn-primary" type="button" disabled>Submit for Review Locked</button></div>
    <?php elseif ($allRequirementsApproved): ?>
        <div class="status-callout success" style="margin-top:16px"><strong>All documents approved.</strong><button class="btn btn-primary" type="button" disabled>Documents Already Approved</button></div>
    <?php elseif ($hasRejectedRequirements): ?>
        <div class="status-callout warning" style="margin-top:16px"><strong>Revision required.</strong><p class="muted">Only the rejected document is unlocked. Replace it first, then it will return to coordinator review automatically.</p><button class="btn btn-primary" type="button" disabled>Fix Rejected Document</button></div>
    <?php elseif (($enrollment['predeployment_status'] ?? 'not_submitted') === 'submitted'): ?>
        <div class="status-callout info" style="margin-top:16px"><strong>Documents under review.</strong><p class="muted">You already submitted your requirements. The button is locked to prevent duplicate submissions.</p><button class="btn btn-primary" type="button" disabled>Already Submitted</button></div>
    <?php elseif (($enrollment['predeployment_status'] ?? 'not_submitted') === 'not_submitted' && $allRequirementsUploaded): ?>
        <form method="post" style="margin-top:16px"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="student_submit_requirements"><button class="btn btn-primary" type="submit">Submit for Review</button></form>
    <?php else: ?>
        <div class="status-callout info" style="margin-top:16px"><strong>Upload all requirements first.</strong><p class="muted">Submit for Review will unlock after all five required documents have been uploaded.</p><button class="btn btn-primary" type="button" disabled>Submit for Review Locked</button></div>
    <?php endif; ?>
</section>
