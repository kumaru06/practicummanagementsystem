<div class="grid two">
    <section class="card">
        <div class="card-head"><h2>Create OJT Coordinator</h2><p class="muted">Provision coordinator access with secure credentials.</p></div>
        <form method="post" class="form js-validate">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="admin_create_coordinator">
            <label>Full Name<input required name="name"></label>
            <label>Email<input required type="email" name="email"></label>
            <label>Department<input required name="department" value="OJT Department"></label>
            <p class="muted">A temporary password will be generated and emailed to the coordinator.</p>
            <button class="btn btn-primary" type="submit"><span class="btn-text">Create Coordinator</span><span class="spinner"></span></button>
        </form>
    </section>

    <section class="card">
        <div class="card-head"><h2>All Coordinators</h2><p class="muted">Active coordinator accounts in the system.</p></div>
        <div class="table-wrap"><table class="data-table"><thead><tr><th data-sort>Name</th><th data-sort>Email</th><th>Status</th><th>Action</th></tr></thead><tbody>
            <?php foreach ($coordinators as $u): ?>
            <tr>
                <td><?= e($u['name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><span class="badge <?= $u['is_active'] ? 'active' : 'inactive' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td><?php if ((int)$u['id'] !== (int)current_user()['id']): ?>
                    <form method="post" class="inline">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="admin_toggle_user">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="active" value="<?= $u['is_active'] ? 0 : 1 ?>">
                        <input type="hidden" name="redirect" value="admin_coordinators">
                        <button class="btn btn-small" type="submit"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                    </form>
                <?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody></table></div>
    </section>
</div>
