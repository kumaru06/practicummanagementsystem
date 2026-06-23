<?php
$totalCoordinators = count($coordinators);
$activeCoordinators = count(array_filter($coordinators, static fn ($c) => (int)($c['is_active'] ?? 0) === 1));
$inactiveCoordinators = $totalCoordinators - $activeCoordinators;
?>

<div class="partner-page coordinator-page">

    <div class="partner-page-intro">
        <div class="partner-page-intro-copy">
            <p class="partner-page-eyebrow">Coordinator Management</p>
            <p class="partner-page-desc">Create OJT coordinator accounts, manage access credentials, and maintain signature files for endorsement letters.</p>
        </div>
    </div>

    <div class="partner-stats-strip coordinator-stats-strip">
        <div class="partner-stat-card partner-stat-total">
            <div class="partner-stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="partner-stat-body">
                <span>Coordinators</span>
                <strong><?= (int)$totalCoordinators ?></strong>
            </div>
        </div>
        <div class="partner-stat-card partner-stat-active">
            <div class="partner-stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="partner-stat-body">
                <span>Active</span>
                <strong><?= (int)$activeCoordinators ?></strong>
            </div>
        </div>
        <div class="partner-stat-card partner-stat-inactive">
            <div class="partner-stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            </div>
            <div class="partner-stat-body">
                <span>Inactive</span>
                <strong><?= (int)$inactiveCoordinators ?></strong>
            </div>
        </div>
    </div>

    <div class="partner-admin-layout coordinators-layout">

        <section class="partner-panel coordinator-create-card">
            <div class="partner-panel-head">
                <div class="partner-icon-wrap partner-icon-wrap--add">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                </div>
                <div class="partner-panel-head-text">
                    <h2>Create OJT Coordinator</h2>
                    <p>Provision coordinator access with secure credentials.</p>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data" class="form js-validate coordinator-create-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="admin_create_coordinator">

                <div class="partner-form-section">
                    <div class="partner-form-section-head">Account Details</div>
                    <div class="partner-form-fields">
                        <label class="partner-field">
                            <span class="partner-field-label">ID Number <em>*</em></span>
                            <input required name="id_number" autocomplete="off" placeholder="e.g. 20240001"
                                inputmode="numeric" pattern="[0-9]+"
                                title="ID Number must contain digits only"
                                oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                        </label>
                        <label class="partner-field">
                            <span class="partner-field-label">Full Name <em>*</em></span>
                            <input required name="name" autocomplete="name" placeholder="e.g. Maria Santos"
                                data-capitalize-words
                                pattern="[A-Za-z\s\-\.]+"
                                title="Full Name must contain letters only">
                        </label>
                        <label class="partner-field">
                            <span class="partner-field-label">Email <em>*</em></span>
                            <input required type="email" name="email" autocomplete="email" placeholder="coordinator@ama.edu.ph"
                                pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}"
                                title="Please enter a valid email address">
                        </label>
                        <label class="partner-field">
                            <span class="partner-field-label">Department <em>*</em></span>
                            <input required name="department" value="OJT Department">
                        </label>
                    </div>

                    <label class="partner-field partner-field--full coordinator-upload-field">
                        <span class="partner-field-label">Signature <em>*</em></span>
                        <input type="file" name="signature_file" accept="image/png,image/jpeg" required>
                        <span class="hint muted">PNG or JPG, max 2MB. Appears above the name on endorsement letters. A transparent PNG looks best.</span>
                    </label>

                    <div class="partner-credential-strip">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <div>
                            <strong>Auto credential delivery</strong>
                            <span>A temporary password is generated and emailed to the coordinator on creation.</span>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary coordinator-create-btn" type="submit"><span class="btn-text">Create Coordinator</span><span class="spinner"></span></button>
            </form>
        </section>

        <section class="partner-panel coordinator-list-card">
            <div class="partner-panel-head partner-panel-head--split">
                <div class="partner-panel-head-main">
                    <div class="partner-icon-wrap partner-icon-wrap--directory">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="partner-panel-head-text">
                        <div class="partner-panel-title-row">
                            <h2>All Coordinators</h2>
                            <span class="partner-count-badge"><?= (int)$totalCoordinators ?> listed</span>
                        </div>
                        <p>Active coordinator accounts in the system.</p>
                    </div>
                </div>
            </div>

            <div class="coordinator-list-scroll">
                <?php if ($coordinators): ?>
                    <div class="coordinator-card-grid">
                        <?php foreach ($coordinators as $u): ?>
                            <?php $idNumber = trim((string)($u['id_number'] ?? '')); ?>
                            <article class="coordinator-card">
                                <div class="coordinator-card-top">
                                    <div class="coordinator-card-brand">
                                        <span class="coordinator-card-avatar"><?= e(strtoupper(substr($u['name'] ?? 'C', 0, 1))) ?></span>
                                        <div class="coordinator-card-brand-copy">
                                            <h3 class="coordinator-card-name" title="<?= e($u['name']) ?>"><?= e($u['name']) ?></h3>
                                            <?php if ($idNumber !== ''): ?>
                                                <p>ID <?= e($idNumber) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="badge coordinator-card-status <?= $u['is_active'] ? 'active' : 'inactive' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span>
                                </div>

                                <div class="coordinator-card-email">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                    <div>
                                        <span>Email</span>
                                        <strong title="<?= e($u['email']) ?>"><?= e($u['email']) ?></strong>
                                    </div>
                                </div>

                                <?php if ((int)$u['id'] !== (int)current_user()['id']): ?>
                                    <div class="coordinator-card-footer">
                                        <button class="btn btn-small btn-primary coordinator-edit-btn" type="button"
                                            data-edit-coordinator="<?= (int)$u['id'] ?>"
                                            data-name="<?= e($u['name']) ?>"
                                            data-email="<?= e($u['email']) ?>"
                                            data-id-number="<?= e($u['id_number'] ?? '') ?>"
                                            data-department="<?= e($u['department'] ?? 'OJT Department') ?>"
                                            data-signature="<?= e($u['signature_file'] ?? '') ?>">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            Edit
                                        </button>
                                        <form method="post" class="coordinator-action-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="admin_reset_user_credentials">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="redirect" value="admin_coordinators">
                                            <button class="btn btn-small coordinator-secondary-btn" type="submit">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                                                Resend
                                            </button>
                                        </form>
                                        <form method="post" class="coordinator-action-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="admin_toggle_user">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="active" value="<?= $u['is_active'] ? 0 : 1 ?>">
                                            <input type="hidden" name="redirect" value="admin_coordinators">
                                            <button class="btn btn-small coordinator-danger-btn" type="submit">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                                <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="coordinator-card-footer coordinator-card-footer--self">
                                        <span class="coordinator-self-note">Current account</span>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="coordinator-empty-state">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <strong>No coordinators yet</strong>
                        <span>Create the first OJT coordinator using the form on the left.</span>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<!-- Edit Coordinator Modal -->
<div class="coordinator-edit-overlay" id="coordinatorEditOverlay" aria-hidden="true">
    <div class="coordinator-edit-modal" role="dialog" aria-modal="true" aria-labelledby="coordinatorEditTitle">
        <div class="coordinator-edit-modal-head">
            <div class="coordinator-edit-modal-head-main">
                <div class="partner-icon-wrap partner-icon-wrap--add coordinator-edit-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </div>
                <div>
                    <h2 id="coordinatorEditTitle">Edit Coordinator</h2>
                    <p>Update account details and signature file.</p>
                </div>
            </div>
            <button type="button" class="coordinator-edit-modal-close" id="coordinatorEditClose" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <form method="post" enctype="multipart/form-data" class="coordinator-edit-form js-validate" id="coordinatorEditForm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="admin_update_coordinator">
            <input type="hidden" name="user_id" id="editCoordUserId">

            <div class="coordinator-edit-modal-body">
                <div class="coordinator-edit-section">
                    <div class="coordinator-edit-section-head">Account Details</div>
                    <div class="partner-form-fields coordinator-edit-fields">
                        <label class="partner-field">
                            <span class="partner-field-label">ID Number</span>
                            <input name="id_number" id="editCoordIdNumber" autocomplete="off" placeholder="e.g. 20240001"
                                inputmode="numeric" pattern="[0-9]+"
                                title="ID Number must contain digits only"
                                oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                        </label>
                        <label class="partner-field">
                            <span class="partner-field-label">Department <em>*</em></span>
                            <input required name="department" id="editCoordDepartment" placeholder="OJT Department">
                        </label>
                        <label class="partner-field partner-field--full">
                            <span class="partner-field-label">Full Name <em>*</em></span>
                            <input required name="name" id="editCoordName" autocomplete="name" placeholder="e.g. Maria Santos"
                                data-capitalize-words
                                pattern="[A-Za-z\s\-\.]+"
                                title="Full Name must contain letters only">
                        </label>
                        <label class="partner-field partner-field--full">
                            <span class="partner-field-label">Email <em>*</em></span>
                            <input required type="email" name="email" id="editCoordEmail" autocomplete="email" placeholder="coordinator@ama.edu.ph"
                                pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}"
                                title="Please enter a valid email address">
                        </label>
                    </div>
                </div>

                <div class="coordinator-edit-section">
                    <div class="coordinator-edit-section-head">Signature</div>
                    <div id="editCoordSigPreview" class="coordinator-sig-panel" hidden>
                        <div class="coordinator-sig-panel-preview">
                            <img id="editCoordSigImg" src="" alt="Current signature preview">
                        </div>
                        <div class="coordinator-sig-panel-copy">
                            <strong>Current signature on file</strong>
                            <span>Upload a new file below to replace it.</span>
                        </div>
                    </div>
                    <label class="partner-field partner-field--full coordinator-upload-field coordinator-upload-field--modal">
                        <input type="file" name="signature_file" accept="image/png,image/jpeg">
                        <span class="hint muted">Leave empty to keep the current signature. PNG or JPG, max 2MB.</span>
                    </label>
                </div>
            </div>

            <div class="coordinator-edit-modal-actions">
                <button type="button" class="btn btn-small coordinator-edit-cancel-btn" id="coordinatorEditCancel">Cancel</button>
                <button type="submit" class="btn btn-primary coordinator-edit-save-btn"><span class="btn-text">Save Changes</span><span class="spinner"></span></button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.getElementById('coordinatorEditOverlay');
    const closeBtn = document.getElementById('coordinatorEditClose');
    const cancelBtn = document.getElementById('coordinatorEditCancel');
    if (!overlay || !closeBtn || !cancelBtn) return;

    const closeModal = () => {
        overlay.classList.remove('open');
        overlay.setAttribute('aria-hidden', 'true');
    };

    const openModal = () => {
        overlay.classList.add('open');
        overlay.setAttribute('aria-hidden', 'false');
    };

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
    });

    document.querySelectorAll('[data-edit-coordinator]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('editCoordUserId').value = btn.dataset.editCoordinator;
            document.getElementById('editCoordName').value = btn.dataset.name;
            document.getElementById('editCoordEmail').value = btn.dataset.email;
            document.getElementById('editCoordIdNumber').value = btn.dataset.idNumber || '';
            document.getElementById('editCoordDepartment').value = btn.dataset.department || 'OJT Department';

            const sigPreview = document.getElementById('editCoordSigPreview');
            const sigImg = document.getElementById('editCoordSigImg');
            if (btn.dataset.signature) {
                sigImg.src = btn.dataset.signature;
                sigPreview.hidden = false;
            } else {
                sigImg.src = '';
                sigPreview.hidden = true;
            }

            openModal();
        });
    });
});
</script>
