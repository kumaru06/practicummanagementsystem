<?php $totalCoordinators = count($coordinators); /* v2 */ ?>

<div class="grid two coordinators-layout">
    <section class="card coordinator-create-card">
        <div class="card-head"><h2>Create OJT Coordinator</h2><p class="muted">Provision coordinator access with secure credentials.</p></div>
        <div class="coordinator-create-toolbar-spacer" aria-hidden="true"></div>
        <form method="post" enctype="multipart/form-data" class="form js-validate coordinator-create-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="admin_create_coordinator">
            <label>
                <span class="label-text">ID Number <span class="field-required">*</span></span>
                <input required name="id_number" autocomplete="off"
                    inputmode="numeric" pattern="[0-9]+"
                    title="ID Number must contain digits only"
                    oninput="this.value=this.value.replace(/[^0-9]/g,'')">
            </label>
            <label>
                <span class="label-text">Full Name <span class="field-required">*</span></span>
                <input required name="name" autocomplete="name"
                    pattern="[A-Za-z\s\-\.]+"
                    title="Full Name must contain letters only"
                    oninput="this.value=this.value.replace(/[^A-Za-z\s\-\.]/g,'')">
            </label>
            <label>
                <span class="label-text">Email <span class="field-required">*</span></span>
                <input required type="email" name="email" autocomplete="email"
                    pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}"
                    title="Please enter a valid email address (e.g. name@example.com)">
            </label>
            <label>
                <span class="label-text">Department <span class="field-required">*</span></span>
                <input required name="department" value="OJT Department">
            </label>
            <label>
                <span class="label-text">Signature <span class="field-required">*</span></span>
                <input type="file" name="signature_file" accept="image/png,image/jpeg" required>
                <span class="hint muted">PNG or JPG, max 2MB. Appears above the name on endorsement letters. A transparent PNG looks best.</span>
            </label>
            <p class="muted">A temporary password will be generated and emailed to the coordinator.</p>
            <button class="btn btn-primary" type="submit"><span class="btn-text">Create Coordinator</span><span class="spinner"></span></button>
        </form>
    </section>

    <section class="card coordinator-list-card">
        <div class="coordinator-list-head">
            <div class="card-head"><h2>All Coordinators</h2><p class="muted">Active coordinator accounts in the system.</p></div>
            <span class="coordinator-count-pill"><?= (int)$totalCoordinators ?> listed</span>
        </div>

        <div class="coordinator-table-wrap">
            <div class="coordinator-list-scroll">
                <table class="data-table coordinator-table">
                    <thead>
                        <tr>
                            <th data-sort>Name</th>
                            <th data-sort>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coordinators as $u): ?>
                        <tr data-id-number="<?= e(trim((string)($u['id_number'] ?? '')) !== '' ? (string)$u['id_number'] : '-') ?>">
                            <td>
                                <div class="coordinator-name-cell">
                                    <span class="table-avatar"><?= e(strtoupper(substr($u['name'] ?? 'C', 0, 1))) ?></span>
                                    <div>
                                        <strong><?= e($u['name']) ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td class="coordinator-email-cell"><?= e($u['email']) ?></td>
                            <td><span class="badge <?= $u['is_active'] ? 'active' : 'inactive' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                            <td>
                                <?php if ((int)$u['id'] !== (int)current_user()['id']): ?>
                                    <div class="coordinator-row-actions">
                                        <button class="btn btn-small btn-primary coordinator-edit-btn" type="button"
                                            data-edit-coordinator="<?= (int)$u['id'] ?>"
                                            data-name="<?= e($u['name']) ?>"
                                            data-email="<?= e($u['email']) ?>"
                                            data-id-number="<?= e($u['id_number'] ?? '') ?>"
                                            data-department="<?= e($u['department'] ?? 'OJT Department') ?>"
                                            data-signature="<?= e($u['signature_file'] ?? '') ?>">Edit</button>
                                        <form method="post" class="coordinator-action-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="admin_reset_user_credentials">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="redirect" value="admin_coordinators">
                                            <button class="btn btn-small coordinator-secondary-btn" type="submit">Resend Email</button>
                                        </form>
                                        <form method="post" class="coordinator-action-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="admin_toggle_user">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="active" value="<?= $u['is_active'] ? 0 : 1 ?>">
                                            <input type="hidden" name="redirect" value="admin_coordinators">
                                            <button class="btn btn-small <?= $u['is_active'] ? '' : 'btn-primary' ?>" type="submit"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="coordinator-self-note">Current account</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<!-- Edit Coordinator Modal -->
<div class="coordinator-edit-overlay" id="coordinatorEditOverlay">
    <div class="coordinator-edit-modal">
        <div class="coordinator-edit-modal-head">
            <h2>Edit Coordinator</h2>
            <button type="button" class="coordinator-edit-modal-close" id="coordinatorEditClose">&times;</button>
        </div>
        <form method="post" enctype="multipart/form-data" class="form js-validate" id="coordinatorEditForm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="admin_update_coordinator">
            <input type="hidden" name="user_id" id="editCoordUserId">
            <label>
                <span class="label-text">ID Number</span>
                <input name="id_number" id="editCoordIdNumber" autocomplete="off"
                    inputmode="numeric" pattern="[0-9]+"
                    title="ID Number must contain digits only"
                    oninput="this.value=this.value.replace(/[^0-9]/g,'')">
            </label>
            <label>
                <span class="label-text">Full Name <span class="field-required">*</span></span>
                <input required name="name" id="editCoordName" autocomplete="name"
                    pattern="[A-Za-z\s\-\.]+"
                    title="Full Name must contain letters only"
                    oninput="this.value=this.value.replace(/[^A-Za-z\s\-\.]/g,'')">
            </label>
            <label>
                <span class="label-text">Email <span class="field-required">*</span></span>
                <input required type="email" name="email" id="editCoordEmail" autocomplete="email"
                    pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}"
                    title="Please enter a valid email address (e.g. name@example.com)">
            </label>
            <label>
                <span class="label-text">Department <span class="field-required">*</span></span>
                <input required name="department" id="editCoordDepartment">
            </label>
            <label>
                <span class="label-text">Signature</span>
                <div id="editCoordSigPreview" class="coordinator-sig-preview" style="display:none;">
                    <img id="editCoordSigImg" src="" alt="Current signature">
                    <span class="muted">Current signature on file</span>
                </div>
                <input type="file" name="signature_file" accept="image/png,image/jpeg">
                <span class="hint muted">Leave empty to keep the current signature. PNG or JPG, max 2MB.</span>
            </label>
            <div class="coordinator-edit-modal-actions">
                <button type="button" class="btn" id="coordinatorEditCancel">Cancel</button>
                <button type="submit" class="btn btn-primary"><span class="btn-text">Save Changes</span><span class="spinner"></span></button>
            </div>
        </form>
    </div>
</div>

<style>
.coordinator-edit-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.coordinator-edit-overlay.open {
    display: flex;
}
.coordinator-edit-modal {
    background: #fff;
    border-radius: 12px;
    width: 95%;
    max-width: 480px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 28px 28px 20px;
    box-shadow: 0 12px 40px rgba(0,0,0,.18);
}
.coordinator-edit-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}
.coordinator-edit-modal-head h2 {
    font-size: 1.15rem;
    font-weight: 700;
}
.coordinator-edit-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
    line-height: 1;
}
.coordinator-edit-modal-close:hover { color: #000; }
.coordinator-edit-modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 18px;
}
.coordinator-sig-preview {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}
.coordinator-sig-preview img {
    max-height: 50px;
    max-width: 140px;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 4px;
    background: #fafafa;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const createCard = document.querySelector('.coordinator-create-card');
    const listCard = document.querySelector('.coordinator-list-card');

    if (!createCard || !listCard) {
        return;
    }

    const syncCoordinatorCardHeights = function () {
        listCard.style.height = '';
        listCard.style.minHeight = '';

        if (window.innerWidth <= 1180) {
            return;
        }

        const createHeight = Math.ceil(createCard.getBoundingClientRect().height);
        if (!createHeight) {
            return;
        }

        listCard.style.height = createHeight + 'px';
        listCard.style.minHeight = createHeight + 'px';
    };

    syncCoordinatorCardHeights();
    window.addEventListener('load', syncCoordinatorCardHeights);
    window.addEventListener('resize', syncCoordinatorCardHeights);

    if (window.ResizeObserver) {
        const observer = new ResizeObserver(syncCoordinatorCardHeights);
        observer.observe(createCard);
    }

    // Edit Coordinator modal
    const overlay = document.getElementById('coordinatorEditOverlay');
    const closeBtn = document.getElementById('coordinatorEditClose');
    const cancelBtn = document.getElementById('coordinatorEditCancel');

    const closeModal = () => overlay.classList.remove('open');
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });

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
                sigPreview.style.display = '';
            } else {
                sigImg.src = '';
                sigPreview.style.display = 'none';
            }

            overlay.classList.add('open');
        });
    });
});
</script>
