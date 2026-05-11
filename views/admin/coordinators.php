<?php $totalCoordinators = count($coordinators); ?>

<div class="grid two coordinators-layout">
    <section class="card coordinator-create-card">
        <div class="card-head"><h2>Create OJT Coordinator</h2><p class="muted">Provision coordinator access with secure credentials.</p></div>
        <div class="coordinator-create-toolbar-spacer" aria-hidden="true"></div>
        <form method="post" class="form js-validate coordinator-create-form">
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
