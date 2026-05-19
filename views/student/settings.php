<div class="grid two">
    <section class="card">
        <div class="section-head"><div><h2>Account Settings</h2><p class="muted">Manage your password and profile information.</p></div></div>
        <div class="action-list account-settings-actions">
            <a class="btn btn-primary" href="<?= e(route_url('student.profile')) ?>">Edit Profile</a>
            <a class="btn" href="<?= e(route_url('student.password.edit')) ?>">Change Password</a>
        </div>
    </section>
    <section class="card">
        <h2>Profile Status</h2>
        <p><strong>Name:</strong> <?= e($student['name'] ?? '') ?></p>
        <p><strong>Student No:</strong> <?= e($student['student_no'] ?? '') ?></p>
        <p><strong>Course:</strong> <?= e($student['course'] ?? '') ?></p>
        <p><strong>Status:</strong> <span class="badge <?= !empty($student['profile_completed']) ? 'active' : 'pending' ?>"><?= !empty($student['profile_completed']) ? 'Complete' : 'Incomplete' ?></span></p>
    </section>
</div>
