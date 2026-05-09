<section class="card">
    <div class="card-head"><h2>Complete Your Basic Resume Profile</h2><p class="muted">You must complete this profile before accessing your student dashboard.</p></div>
    <form method="post" enctype="multipart/form-data" class="form js-validate">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="student_save_profile">
        <div class="grid two">
            <label>Full Name<input required value="<?= e($student['name'] ?? '') ?>" disabled></label>
            <label>Student ID Number<input required value="<?= e($student['student_no'] ?? '') ?>" disabled></label>
            <label>Photo<input <?= empty($student['photo_file']) ? 'required' : '' ?> type="file" name="photo_file" accept=".jpg,.jpeg,.png"></label>
            <label>Contact Number<input required name="contact_number" value="<?= e($student['contact_number'] ?? '') ?>"></label>
            <label>Course<input required value="<?= e($student['course'] ?? '') ?>" disabled></label>
            <label>Year Level<input required name="year_level" value="<?= e($student['year_level'] ?? '') ?>"></label>
            <label>Section<input required name="section" value="<?= e($student['section'] ?? '') ?>"></label>
            <label>Emergency Contact Name<input required name="emergency_contact_name" value="<?= e($student['emergency_contact_name'] ?? '') ?>"></label>
            <label>Emergency Contact Number<input required name="emergency_contact_number" value="<?= e($student['emergency_contact_number'] ?? '') ?>"></label>
            <label>Guardian Name<input required name="guardian_name" value="<?= e($student['guardian_name'] ?? '') ?>"></label>
            <label>Guardian Contact<input required name="guardian_contact" value="<?= e($student['guardian_contact'] ?? '') ?>"></label>
        </div>
        <label>Address<textarea required name="address"><?= e($student['address'] ?? '') ?></textarea></label>
        <button class="btn btn-primary" type="submit"><span class="btn-text">Save Profile & Unlock Dashboard</span><span class="spinner"></span></button>
    </form>
</section>
