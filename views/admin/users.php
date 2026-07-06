<section class="card">
    <div class="section-head section-head-split">
        <input class="table-search table-search-wide" placeholder="Search users...">
    </div>
    <div class="table-wrap"><table class="data-table"><thead><tr><th data-sort>Last Name</th><th data-sort>First Name</th><th data-sort>Middle Name</th><th data-sort>Email</th><th data-sort>Student ID</th><th data-sort>Course</th><th>Status</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($allUsers as $u): ?>
        <tr>
            <td>
                <div class="user-name-cell">
                    <span class="table-avatar"><?= strtoupper(mb_substr((string)($u['last_name'] ?? $u['name'] ?? 'A'), 0, 1)) ?></span>
                    <span><?= e($u['last_name'] ?? '—') ?></span>
                </div>
            </td>
            <td><?= e($u['first_name'] ?? '—') ?></td>
            <td><?= e($u['middle_name'] ?? '—') ?></td>
            <td class="muted-cell"><?= e($u['email']) ?></td>
            <td class="center-cell"><?= $u['student_no'] ? e($u['student_no']) : '<span class="muted">—</span>' ?></td>
            <td><div class="course-cell" title="<?= e($u['course'] ?? '') ?>"><?= $u['course'] ? e($u['course']) : '<span class="muted">—</span>' ?></div></td>
            <td class="center-cell"><span class="badge <?= $u['is_active'] ? 'active' : 'inactive' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td><?php if ((int)$u['id'] !== (int)current_user()['id']): ?>
                <form method="post" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="admin_toggle_user">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <input type="hidden" name="active" value="<?= $u['is_active'] ? 0 : 1 ?>">
                    <input type="hidden" name="redirect" value="admin_users">
                    <button class="btn btn-small <?= $u['is_active'] ? 'btn-danger-outline' : 'btn-success-outline' ?>" type="submit"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                </form>
            <?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div>
    <div class="pagination"></div>
</section>
