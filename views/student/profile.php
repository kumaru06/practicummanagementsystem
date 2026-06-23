<?php
$profilePhotoUrl = student_profile_photo_url($student ?? null);
$studentInitial = strtoupper(substr($student['name'] ?? 'S', 0, 1));
$studentEmail = trim((string)($student['email'] ?? current_user()['email'] ?? ''));
$isProfileComplete = !empty($profileCompleted);
?>
<div class="student-profile-page spf-v2">
    <header class="spf-page-head">
        <a class="spf-back-link" href="<?= e(route_url('student.settings')) ?>">
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>Back to Settings</span>
        </a>
        <div class="spf-page-head-copy">
            <span class="spf-eyebrow">Account</span>
            <h1>Profile Details</h1>
            <p>Update your contact information, emergency contact, and profile photo.</p>
        </div>
        <span class="spf-status-badge badge <?= $isProfileComplete ? 'active' : 'pending' ?>"><?= $isProfileComplete ? 'Profile Complete' : 'Incomplete' ?></span>
    </header>

    <section class="card student-profile-card spf-card">
        <form id="studentProfileForm" method="post" enctype="multipart/form-data" class="form js-validate student-profile-form spf-form" data-student-profile-form>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="student_save_profile">

            <div class="student-profile-shell spf-shell">
                <aside class="student-profile-sidebar spf-aside" aria-label="Profile summary">
                    <div class="spf-photo-stack">
                        <div class="profile-photo-frame profile-photo-frame-lg spf-photo-frame">
                            <img class="<?= $profilePhotoUrl === '' ? 'is-hidden' : '' ?>"<?= $profilePhotoUrl !== '' ? ' src="' . e($profilePhotoUrl) . '"' : '' ?> alt="<?= e($student['name'] ?? 'Student') ?> profile photo" data-profile-photo-preview>
                            <span class="profile-photo-fallback <?= $profilePhotoUrl !== '' ? 'is-hidden' : '' ?>" data-profile-photo-fallback><?= e($studentInitial) ?></span>
                        </div>
                        <label class="spf-photo-upload profile-photo-input">
                            <span class="spf-photo-upload-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 16V4m0 0 8 8m-8-8-8 8"/><path d="M4 20h16"/></svg>
                            </span>
                            <span class="spf-photo-upload-copy">
                                <strong><?= $profilePhotoUrl !== '' ? 'Replace photo' : 'Upload photo' ?></strong>
                                <small>JPG or PNG, clear headshot recommended</small>
                            </span>
                            <input <?= empty($student['photo_file']) ? 'required' : '' ?> type="file" name="photo_file" accept=".jpg,.jpeg,.png" data-profile-photo-input>
                        </label>
                    </div>

                    <div class="profile-identity spf-identity">
                        <h2><?= e($student['name'] ?? 'Student') ?></h2>
                        <?php if ($studentEmail !== ''): ?><p class="profile-identity-email"><?= e($studentEmail) ?></p><?php endif; ?>
                        <span class="spf-course-tag"><?= e($student['course'] ?? 'Course not set') ?></span>
                    </div>

                    <dl class="spf-meta-list">
                        <div class="spf-meta-item">
                            <dt>Student ID</dt>
                            <dd><?= e($student['student_no'] ?? '—') ?></dd>
                        </div>
                        <div class="spf-meta-item">
                            <dt>Year Level</dt>
                            <dd><?= e($student['year_level'] ?? '—') ?></dd>
                        </div>
                        <div class="spf-meta-item">
                            <dt>Section</dt>
                            <dd><?= e($student['section'] ?? '—') ?></dd>
                        </div>
                    </dl>

                    <p class="profile-photo-note spf-aside-note">
                        <?= $profilePhotoUrl !== '' ? 'Your photo appears on your student record and coordinator views.' : 'Upload a profile photo to finish your basic resume profile.' ?>
                    </p>
                </aside>

                <div class="student-profile-main spf-main">
                    <div class="profile-form-section spf-section">
                        <div class="profile-section-title spf-section-head">
                            <span class="spf-section-num" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M6 21v-1a6 6 0 0 1 12 0v1"/></svg>
                            </span>
                            <div>
                                <strong>Student Information</strong>
                                <small>Verified account details and editable contact fields</small>
                            </div>
                        </div>

                        <div class="grid two student-profile-grid spf-field-grid">
                            <label class="spf-field spf-field--readonly">
                                <span class="spf-field-label">Full Name <span class="spf-field-tag">Verified</span></span>
                                <input required value="<?= e($student['name'] ?? '') ?>" disabled>
                            </label>
                            <label class="spf-field spf-field--readonly">
                                <span class="spf-field-label">Student ID Number <span class="spf-field-tag">Verified</span></span>
                                <input required value="<?= e($student['student_no'] ?? '') ?>" disabled>
                            </label>
                            <label class="spf-field spf-field--readonly">
                                <span class="spf-field-label">Course <span class="spf-field-tag">Verified</span></span>
                                <input required value="<?= e($student['course'] ?? '') ?>" disabled>
                            </label>
                            <label class="spf-field">
                                <span class="spf-field-label">Year Level</span>
                                <input required name="year_level" value="<?= e($student['year_level'] ?? '') ?>" placeholder="e.g. 4th Year">
                            </label>
                            <label class="spf-field">
                                <span class="spf-field-label">Section ID</span>
                                <input required name="section" value="<?= e($student['section'] ?? '') ?>" placeholder="e.g. 3">
                            </label>
                            <label class="spf-field">
                                <span class="spf-field-label">Contact Number</span>
                                <input required name="contact_number" value="<?= e($student['contact_number'] ?? '') ?>" placeholder="09XX XXX XXXX">
                            </label>
                        </div>
                    </div>

                    <div class="profile-form-section spf-section">
                        <div class="profile-section-title spf-section-head">
                            <span class="spf-section-num" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 22s7-4.35 7-11a4 4 0 0 0-7-2.65A4 4 0 0 0 5 11c0 6.65 7 11 7 11Z"/></svg>
                            </span>
                            <div>
                                <strong>Emergency Contact</strong>
                                <small>Person to reach in case of emergency</small>
                            </div>
                        </div>
                        <div class="grid two student-profile-grid spf-field-grid">
                            <label class="spf-field">
                                <span class="spf-field-label">Contact Name</span>
                                <input required name="emergency_contact_name" value="<?= e($student['emergency_contact_name'] ?? '') ?>" placeholder="Full name">
                            </label>
                            <label class="spf-field">
                                <span class="spf-field-label">Contact Number</span>
                                <input required name="emergency_contact_number" value="<?= e($student['emergency_contact_number'] ?? '') ?>" placeholder="09XX XXX XXXX">
                            </label>
                        </div>
                    </div>

                    <div class="profile-form-section spf-section">
                        <div class="profile-section-title spf-section-head">
                            <span class="spf-section-num" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>
                            </span>
                            <div>
                                <strong>Home Address</strong>
                                <small>Your current residential address</small>
                            </div>
                        </div>
                        <label class="spf-field spf-field--full">
                            <span class="spf-field-label">Address</span>
                            <textarea required name="address" placeholder="Street, barangay, city, province"><?= e($student['address'] ?? '') ?></textarea>
                        </label>
                    </div>

                    <div class="student-profile-actions spf-actions">
                        <a class="btn spf-cancel-btn" href="<?= e(route_url('student.settings')) ?>">Cancel</a>
                        <button class="btn btn-primary" type="submit">
                            <span class="btn-text"><?= $isProfileComplete ? 'Save Profile Changes' : 'Save Profile & Unlock Dashboard' ?></span>
                            <span class="spinner"></span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>
