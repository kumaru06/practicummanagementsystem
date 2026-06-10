<?php
$profilePhotoUrl = student_profile_photo_url($student ?? null);
$studentInitial = strtoupper(substr($student['name'] ?? 'S', 0, 1));
$studentEmail = trim((string)($student['email'] ?? current_user()['email'] ?? ''));
?>
<section class="card student-profile-card">
    <div class="student-profile-shell">
        <aside class="student-profile-sidebar" aria-label="Student profile summary">
            <span class="profile-eyebrow">Student Resume Profile</span>
            <div class="profile-photo-frame profile-photo-frame-lg">
                <img class="<?= $profilePhotoUrl === '' ? 'is-hidden' : '' ?>"<?= $profilePhotoUrl !== '' ? ' src="' . e($profilePhotoUrl) . '"' : '' ?> alt="<?= e($student['name'] ?? 'Student') ?> profile photo" data-profile-photo-preview>
                <span class="profile-photo-fallback <?= $profilePhotoUrl !== '' ? 'is-hidden' : '' ?>" data-profile-photo-fallback><?= e($studentInitial) ?></span>
            </div>
            <div class="profile-identity">
                <h2><?= e($student['name'] ?? 'Student') ?></h2>
                <?php if ($studentEmail !== ''): ?><p class="profile-identity-email"><?= e($studentEmail) ?></p><?php endif; ?>
                <span><?= e($student['course'] ?? 'Course not set') ?></span>
            </div>
            <div class="profile-id-card">
                <small>Student ID Number</small>
                <strong><?= e($student['student_no'] ?? '—') ?></strong>
            </div>
            <div class="profile-summary-grid">
                <div><small>Year Level</small><strong><?= e($student['year_level'] ?? '—') ?></strong></div>
                <div><small>Section ID</small><strong><?= e($student['section'] ?? '—') ?></strong></div>
            </div>
            <p class="profile-photo-note"><?= $profilePhotoUrl !== '' ? 'Your current profile photo is visible here. Choose a new file to preview and replace it.' : 'Upload a clear JPG or PNG profile photo to complete your student record.' ?></p>
        </aside>

        <div class="student-profile-main">
            <div class="student-profile-hero">
                <div class="student-profile-title">
                    <span class="profile-kicker">Edit Student Profile</span>
                    <h2>Complete Your Basic Resume Profile</h2>
                    <p class="muted">Keep your student details accurate so coordinators and industry partners can validate your OJT records.</p>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data" class="form js-validate student-profile-form" data-student-profile-form>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="student_save_profile">

                <div class="profile-form-section">
                    <div class="profile-section-title"><span>01</span><div><strong>Student Information</strong><small>Verified account details</small></div></div>
                    <div class="profile-account-identity app-user-identity app-user-identity--profile" aria-label="Signed-in account">
                        <span class="app-user-identity__avatar <?= $profilePhotoUrl !== '' ? 'app-user-identity__avatar--photo' : '' ?>" data-profile-inline-avatar>
                            <img class="<?= $profilePhotoUrl === '' ? 'is-hidden' : '' ?>"<?= $profilePhotoUrl !== '' ? ' src="' . e($profilePhotoUrl) . '"' : '' ?> alt="<?= e($student['name'] ?? 'Student') ?> profile photo" data-profile-photo-preview-inline>
                            <span class="profile-inline-avatar-fallback <?= $profilePhotoUrl !== '' ? 'is-hidden' : '' ?>" data-profile-initial-inline><?= e($studentInitial) ?></span>
                        </span>
                        <div class="app-user-identity__meta">
                            <strong><?= e($student['name'] ?? 'Student') ?></strong>
                            <?php if ($studentEmail !== ''): ?><small><?= e($studentEmail) ?></small><?php endif; ?>
                        </div>
                    </div>
                    <div class="grid two student-profile-grid">
                        <label>Full Name<input required value="<?= e($student['name'] ?? '') ?>" disabled></label>
                        <label>Student ID Number<input required value="<?= e($student['student_no'] ?? '') ?>" disabled></label>
                        <label>Course<input required value="<?= e($student['course'] ?? '') ?>" disabled></label>
                        <label>Year Level<input required name="year_level" value="<?= e($student['year_level'] ?? '') ?>"></label>
                        <label>Section ID<input required name="section" value="<?= e($student['section'] ?? '') ?>"></label>
                        <label>Contact Number<input required name="contact_number" value="<?= e($student['contact_number'] ?? '') ?>"></label>
                    </div>
                </div>

                <div class="profile-form-section">
                    <div class="profile-section-title"><span>02</span><div><strong>Profile Photo</strong><small>Shown on your student profile</small></div></div>
                    <label class="profile-photo-input">Upload Photo<input <?= empty($student['photo_file']) ? 'required' : '' ?> type="file" name="photo_file" accept=".jpg,.jpeg,.png" data-profile-photo-input><small><?= empty($student['photo_file']) ? 'Required: upload a clear JPG or PNG profile photo.' : 'Optional: choose a new JPG or PNG to replace your current photo.' ?></small></label>
                </div>

                <div class="profile-form-section">
                    <div class="profile-section-title"><span>03</span><div><strong>Emergency Contact</strong><small>Person to notify when needed</small></div></div>
                    <div class="grid two student-profile-grid">
                        <label>Emergency Contact Name<input required name="emergency_contact_name" value="<?= e($student['emergency_contact_name'] ?? '') ?>"></label>
                        <label>Emergency Contact Number<input required name="emergency_contact_number" value="<?= e($student['emergency_contact_number'] ?? '') ?>"></label>
                    </div>
                </div>

                <div class="profile-form-section">
                    <div class="profile-section-title"><span>04</span><div><strong>Address</strong><small>Current home address</small></div></div>
                    <label>Address<textarea required name="address"><?= e($student['address'] ?? '') ?></textarea></label>
                </div>

                <div class="student-profile-actions">
                    <button class="btn btn-primary" type="submit"><span class="btn-text"><?= !empty($profileCompleted) ? 'Save Profile Changes' : 'Save Profile & Unlock Dashboard' ?></span><span class="spinner"></span></button>
                </div>
            </form>
        </div>
    </div>
</section>
