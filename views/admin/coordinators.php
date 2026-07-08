<?php
$coordinators = $coordinators ?? [];
$totalCoordinators = count($coordinators);
$activeCoordinators = count(array_filter($coordinators, static fn ($c) => (int)($c['is_active'] ?? 0) === 1));
$inactiveCoordinators = $totalCoordinators - $activeCoordinators;
?>
<div class="admin-coordinators-v2">
    <nav class="aco-breadcrumb" aria-label="Breadcrumb">
        <a href="index.php?r=admin">Dashboard</a>
        <span class="aco-breadcrumb-sep" aria-hidden="true">&rsaquo;</span>
        <span aria-current="page">Manage Coordinators</span>
    </nav>

    <div class="aco-stats-strip">
        <article class="aco-stat-card aco-stat-total">
            <div class="aco-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            </div>
            <div class="aco-stat-body">
                <span>Total Coordinators</span>
                <strong><?= $totalCoordinators ?></strong>
            </div>
        </article>
        <article class="aco-stat-card aco-stat-active">
            <div class="aco-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 1 3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 11-2-2 1.41-1.41L10 11.17l3.59-3.59L15 9l-5 5z"/></svg>
            </div>
            <div class="aco-stat-body">
                <span>Active Accounts</span>
                <strong><?= $activeCoordinators ?></strong>
            </div>
        </article>
        <article class="aco-stat-card aco-stat-inactive">
            <div class="aco-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8 0-1.85.63-3.55 1.69-4.9L16.9 18.31C15.55 19.37 13.85 20 12 20zm6.31-3.1L7.1 5.69C8.45 4.63 10.15 4 12 4c4.42 0 8 3.58 8 8 0 1.85-.63 3.55-1.69 4.9z"/></svg>
            </div>
            <div class="aco-stat-body">
                <span>Inactive</span>
                <strong><?= $inactiveCoordinators ?></strong>
            </div>
        </article>
    </div>

    <details class="aco-create-panel" open>
        <summary class="aco-create-panel-toggle">
            <span class="aco-create-panel-toggle-main">
                <span class="aco-create-panel-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                </span>
                <span class="aco-create-panel-copy">
                    <strong>Create OJT Coordinator</strong>
                    <span>Provision access, assign department, and upload endorsement signature.</span>
                </span>
            </span>
            <span class="aco-create-panel-chevron" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>
            </span>
        </summary>

        <div class="aco-create-panel-body">
            <form method="post" enctype="multipart/form-data" class="form js-validate aco-create-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="admin_create_coordinator">

                <div class="aco-form-grid">
                    <label class="aco-field">
                        <span class="aco-field-label">ID Number <em>*</em></span>
                        <input required name="id_number" autocomplete="off" placeholder="e.g. 20240001"
                            inputmode="numeric" pattern="[0-9]+"
                            title="ID Number must contain digits only"
                            oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                            data-coordinator-id-check>
                        <span class="field-check-message" data-coordinator-id-message hidden aria-live="polite"></span>
                    </label>
                    <label class="aco-field">
                        <span class="aco-field-label">First Name <em>*</em></span>
                        <input required name="first_name" autocomplete="given-name" placeholder="e.g. Maria"
                            data-capitalize-words pattern="[A-Za-z\s\-\.]+" title="First name must contain letters only">
                    </label>
                    <label class="aco-field">
                        <span class="aco-field-label">Middle Name</span>
                        <input name="middle_name" autocomplete="additional-name" placeholder="Optional"
                            data-capitalize-words pattern="[A-Za-z\s\-\.]*" title="Middle name must contain letters only">
                    </label>
                    <label class="aco-field">
                        <span class="aco-field-label">Last Name <em>*</em></span>
                        <input required name="last_name" autocomplete="family-name" placeholder="e.g. Santos"
                            data-capitalize-words pattern="[A-Za-z\s\-\.]+" title="Last name must contain letters only">
                    </label>
                    <label class="aco-field">
                        <span class="aco-field-label">Email <em>*</em></span>
                        <input required type="email" name="email" autocomplete="email" placeholder="coordinator@ama.edu.ph"
                            pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}"
                            title="Please enter a valid email address"
                            data-coordinator-email-check>
                        <span class="field-check-message" data-coordinator-email-message hidden aria-live="polite"></span>
                    </label>
                    <label class="aco-field">
                        <span class="aco-field-label">Department <em>*</em></span>
                        <input required name="department" value="OJT Department" autocomplete="organization">
                    </label>
                    <label class="aco-field aco-field--signature">
                        <span class="aco-field-label">Signature <em>*</em></span>
                        <div class="aco-signature-upload">
                            <input type="file" name="signature_file" accept="image/png,image/jpeg" required>
                            <div class="aco-signature-upload-copy">
                                <strong>Upload signature image</strong>
                                <span>PNG or JPG, max 2MB. Transparent PNG recommended for endorsement letters.</span>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="aco-create-foot">
                    <div class="aco-credential-note">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18 8h-1V6a5 5 0 0 0-10 0v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2zm-6 9a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm3.1-9H8.9V6a3.1 3.1 0 0 1 6.2 0V8z"/></svg>
                        <div>
                            <strong>Auto credential delivery</strong>
                            <span>A temporary password is generated and emailed when the account is created.</span>
                        </div>
                    </div>
                    <button class="btn btn-primary aco-create-submit" type="submit">
                        <span class="btn-text">Create Coordinator</span>
                        <span class="spinner"></span>
                    </button>
                </div>
            </form>
        </div>
    </details>

    <section class="card aco-directory-card admin-coordinators-page" data-coordinator-directory>
        <div class="aco-directory-head">
            <div class="aco-directory-copy">
                <span class="aco-eyebrow">Coordinator Directory</span>
                <h2>All Coordinators</h2>
                <p>Search, filter, and manage OJT coordinator accounts from one directory.</p>
            </div>
            <div class="aco-directory-badge" aria-live="polite">
                <strong><?= $totalCoordinators ?></strong>
                <span>Listed</span>
            </div>
        </div>

        <?php if ($totalCoordinators === 0): ?>
            <div class="aco-empty">
                <div class="aco-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path fill="currentColor" d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                </div>
                <p class="aco-empty-title">No coordinators yet</p>
                <p class="aco-empty-sub">Create the first OJT coordinator using the form above.</p>
            </div>
        <?php else: ?>
        <div class="aco-toolbar">
            <div class="aco-search-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                <input class="table-search aco-table-search" type="search" placeholder="Search by name, email, ID, or department..." autocomplete="off">
            </div>
            <div class="aco-filter-pills" role="group" aria-label="Filter by status">
                <button class="aco-filter-pill is-active" type="button" data-coordinator-filter="all">All</button>
                <button class="aco-filter-pill" type="button" data-coordinator-filter="active">Active</button>
                <button class="aco-filter-pill" type="button" data-coordinator-filter="inactive">Inactive</button>
            </div>
        </div>

        <div class="aco-directory-body">
        <div class="table-wrap aco-table-wrap">
            <table class="data-table no-row-details aco-coordinators-table" data-per-page="10" data-hide-column-toggle>
                <thead>
                    <tr>
                        <th data-sort>Last Name</th>
                        <th data-sort>First Name</th>
                        <th data-sort>Middle Name</th>
                        <th data-sort>Email</th>
                        <th data-sort>ID Number</th>
                        <th data-sort>Department</th>
                        <th>Status</th>
                        <th class="aco-col-action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coordinators as $u): ?>
                    <?php
                        $firstName = trim((string)($u['first_name'] ?? ''));
                        $middleName = trim((string)($u['middle_name'] ?? ''));
                        $lastName = trim((string)($u['last_name'] ?? ''));
                        $fullName = trim($firstName . ' ' . $middleName . ' ' . $lastName);
                        $fullName = preg_replace('/\s+/', ' ', $fullName) ?: ($u['name'] ?? 'Coordinator');
                        $initial = strtoupper(mb_substr((string)($u['last_name'] ?? $u['name'] ?? 'C'), 0, 1));
                        $idNumber = trim((string)($u['id_number'] ?? ''));
                        $department = trim((string)($u['department'] ?? 'OJT Department'));
                        $sigUrl = !empty($u['signature_file']) ? asset($u['signature_file']) : '';
                        $isSelf = (int)$u['id'] === (int)current_user()['id'];
                        $tone = coordinator_avatar_tone((int)$u['id']);
                        $statusKey = !empty($u['is_active']) ? 'active' : 'inactive';
                    ?>
                    <tr data-search="<?= e(strtolower(trim($lastName . ' ' . $firstName . ' ' . $middleName . ' ' . $u['email'] . ' ' . $idNumber . ' ' . $department))) ?>"
                        data-coordinator-status="<?= e($statusKey) ?>">
                        <td class="aco-name-cell">
                            <div class="aco-person-cell">
                                <span class="aco-avatar aco-avatar-tone--<?= $tone ?>"><?= e($initial) ?></span>
                                <span><?= $lastName !== '' ? e($lastName) : '<span class="muted">—</span>' ?></span>
                            </div>
                        </td>
                        <td class="aco-name-cell"><?= $firstName !== '' ? e($firstName) : '<span class="muted">—</span>' ?></td>
                        <td class="aco-name-cell"><?= $middleName !== '' ? e($middleName) : '<span class="muted">—</span>' ?></td>
                        <td><a class="aco-email-link" href="mailto:<?= e($u['email']) ?>"><?= e($u['email']) ?></a></td>
                        <td class="center-cell">
                            <?php if ($idNumber !== ''): ?>
                                <span class="aco-id-badge"><?= e($idNumber) ?></span>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><div class="aco-dept-cell" title="<?= e($department) ?>"><?= e($department) ?></div></td>
                        <td class="center-cell">
                            <span class="aco-status-pill <?= $u['is_active'] ? 'is-active' : 'is-inactive' ?>">
                                <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="admin-users-action-cell aco-col-action">
                            <?php if ($isSelf): ?>
                                <span class="aco-self-note muted">Current account</span>
                            <?php else: ?>
                            <details class="admin-user-action-menu">
                                <summary class="admin-user-action-trigger" aria-label="Coordinator actions">
                                    <svg class="admin-user-action-trigger-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm0 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                                    <span>Actions</span>
                                    <svg class="admin-user-action-trigger-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                                </summary>
                                <div class="admin-user-action-panel">
                                    <button class="admin-user-action-item" type="button"
                                        data-edit-coordinator="<?= (int)$u['id'] ?>"
                                        data-first-name="<?= e($u['first_name'] ?? '') ?>"
                                        data-middle-name="<?= e($u['middle_name'] ?? '') ?>"
                                        data-last-name="<?= e($u['last_name'] ?? '') ?>"
                                        data-email="<?= e($u['email']) ?>"
                                        data-id-number="<?= e($u['id_number'] ?? '') ?>"
                                        data-department="<?= e($department) ?>"
                                        data-signature="<?= e($sigUrl) ?>">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1.003 1.003 0 0 0 0-1.42l-2.34-2.34a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z"/></svg>
                                        Edit Coordinator
                                    </button>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="admin_toggle_user">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="active" value="<?= $u['is_active'] ? 0 : 1 ?>">
                                        <input type="hidden" name="redirect" value="admin_coordinators">
                                        <button class="admin-user-action-item <?= $u['is_active'] ? 'admin-user-action-item--danger' : 'admin-user-action-item--success' ?>" type="submit">
                                            <?php if ($u['is_active']): ?>
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z"/></svg>
                                                Deactivate Coordinator
                                            <?php else: ?>
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                                Activate Coordinator
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </div>
                            </details>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>
        <footer class="aco-table-footer">
            <div class="pagination"></div>
        </footer>
        <?php endif; ?>
    </section>
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
                        <label class="partner-field">
                            <span class="partner-field-label">First Name <em>*</em></span>
                            <input required name="first_name" id="editCoordFirstName" autocomplete="given-name" placeholder="e.g. Maria"
                                data-capitalize-words pattern="[A-Za-z\s\-\.]+" title="First name must contain letters only">
                        </label>
                        <label class="partner-field">
                            <span class="partner-field-label">Middle Name</span>
                            <input name="middle_name" id="editCoordMiddleName" autocomplete="additional-name" placeholder="Optional"
                                data-capitalize-words pattern="[A-Za-z\s\-\.]*" title="Middle name must contain letters only">
                        </label>
                        <label class="partner-field">
                            <span class="partner-field-label">Last Name <em>*</em></span>
                            <input required name="last_name" id="editCoordLastName" autocomplete="family-name" placeholder="e.g. Santos"
                                data-capitalize-words pattern="[A-Za-z\s\-\.]+" title="Last name must contain letters only">
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

    const MODAL_ANIM_MS = 300;
    let closeTimer = null;

    const finishClose = () => {
        overlay.classList.remove('is-closing');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-coordinator-edit-open');
        closeTimer = null;
    };

    const closeModal = () => {
        if (!overlay.classList.contains('open') || overlay.classList.contains('is-closing')) return;

        if (closeTimer) {
            clearTimeout(closeTimer);
        }

        overlay.classList.add('is-closing');
        overlay.classList.remove('open');
        closeTimer = window.setTimeout(finishClose, MODAL_ANIM_MS);
    };

    const openModal = () => {
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }

        overlay.classList.remove('is-closing');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-coordinator-edit-open');

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                overlay.classList.add('open');
            });
        });
    };

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
    });

    document.querySelectorAll('[data-edit-coordinator]').forEach(btn => {
        btn.addEventListener('click', () => {
            const menu = btn.closest('.admin-user-action-menu');
            if (menu) menu.removeAttribute('open');

            document.getElementById('editCoordUserId').value = btn.dataset.editCoordinator;
            document.getElementById('editCoordFirstName').value = btn.dataset.firstName || '';
            document.getElementById('editCoordMiddleName').value = btn.dataset.middleName || '';
            document.getElementById('editCoordLastName').value = btn.dataset.lastName || '';
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
