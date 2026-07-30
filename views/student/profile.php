<?php

$profilePhotoUrl = student_profile_photo_url($student ?? null);

$studentInitial = strtoupper(substr($student['name'] ?? 'S', 0, 1));

$studentEmail = trim((string)($student['email'] ?? current_user()['email'] ?? ''));

$isProfileComplete = !empty($profileCompleted);

$currentGender = trim((string)($student['gender'] ?? ''));

$genderOptions = ['Male', 'Female', 'Other'];

$studentDisplayName = full_name($student) ?: trim((string)($student['name'] ?? 'Student'));

$studentFirstName = trim((string)($student['first_name'] ?? ''));

$studentMiddleName = trim((string)($student['middle_name'] ?? ''));

$studentLastName = trim((string)($student['last_name'] ?? ''));

if ($studentFirstName === '' && $studentLastName === '') {

    $nameParts = split_person_name((string)($student['name'] ?? ''));

    $studentFirstName = $nameParts['first_name'];

    $studentMiddleName = $nameParts['middle_name'];

    $studentLastName = $nameParts['last_name'];

}

$verifiedTagSvg = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.5a6.5 6.5 0 1 0 0 13 6.5 6.5 0 0 0 0-13Z" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M5.5 8 7 9.5 10.5 6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';

?>

<div class="student-profile-page spf-v2">

    <header class="spf-page-head">

        <?php if ($isProfileComplete): ?>

            <a class="spf-back-link" href="<?= e(route_url('student.settings')) ?>">

                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>

                <span>Back to Settings</span>

            </a>

        <?php else: ?>

            <div class="spf-onboard-chip" aria-hidden="true">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M8 6V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v1" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round"/>
                    <rect x="6" y="6" width="12" height="15" rx="2" fill="none" stroke="currentColor" stroke-width="1.85"/>
                    <path d="M9.5 12.5 11 14l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9.5 17h5" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round"/>
                </svg>

                <span>Setup</span>

            </div>

        <?php endif; ?>

        <div class="spf-page-head-copy">

            <span class="spf-eyebrow"><?= $isProfileComplete ? 'Account' : 'Welcome' ?></span>

            <h1><?= $isProfileComplete ? 'Profile Details' : 'Complete Your Profile' ?></h1>

            <p><?= $isProfileComplete ? 'Update your contact information, emergency contact, and profile photo.' : 'Fill in the required details below to unlock your student dashboard.' ?></p>

        </div>

        <span class="spf-status-badge badge <?= $isProfileComplete ? 'active' : 'pending' ?>"><?= $isProfileComplete ? 'Profile Complete' : 'Incomplete' ?></span>

    </header>



    <?php if (!$isProfileComplete): ?>

        <div class="spf-progress-card" role="status">

            <div class="spf-progress-copy">

                <strong>Almost there</strong>

                <span>Complete all fields and upload a photo to access the portal.</span>

            </div>

            <div class="spf-progress-track" aria-hidden="true"><span class="spf-progress-fill"></span></div>

        </div>

    <?php endif; ?>



    <section class="card student-profile-card spf-card">

        <form id="studentProfileForm" method="post" enctype="multipart/form-data" class="form js-validate student-profile-form spf-form" data-student-profile-form data-profile-photo-crop>

            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <input type="hidden" name="action" value="student_save_profile">



            <div class="student-profile-shell spf-shell">

                <aside class="student-profile-sidebar spf-aside" aria-label="Profile summary">

                    <div class="spf-photo-stack">

                        <div class="spf-photo-ring">

                            <div class="profile-photo-frame profile-photo-frame-lg spf-photo-frame">

                                <img class="<?= $profilePhotoUrl === '' ? 'is-hidden' : '' ?>"<?= $profilePhotoUrl !== '' ? ' src="' . e($profilePhotoUrl) . '"' : '' ?> alt="<?= e($studentDisplayName) ?> profile photo" data-profile-photo-preview>

                                <span class="profile-photo-fallback <?= $profilePhotoUrl !== '' ? 'is-hidden' : '' ?>" data-profile-photo-fallback><?= e($studentInitial) ?></span>

                            </div>

                        </div>

                        <label class="spf-photo-upload profile-photo-input">

                            <span class="spf-photo-upload-icon" aria-hidden="true">

                                <svg viewBox="0 0 24 24"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3Z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><circle cx="12" cy="13" r="3.25" fill="none" stroke="currentColor" stroke-width="1.75"/></svg>

                            </span>

                            <span class="spf-photo-upload-copy">

                                <strong><?= $profilePhotoUrl !== '' ? 'Replace photo' : 'Upload photo' ?></strong>

                                <small>JPG or PNG · crop to square headshot</small>

                            </span>

                            <input <?= empty($student['photo_file']) ? 'required' : '' ?> type="file" name="photo_file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" data-profile-photo-input>

                        </label>

                    </div>



                    <div class="profile-identity spf-identity">

                        <h2><?= e($studentDisplayName) ?></h2>

                        <?php if ($studentEmail !== ''): ?><p class="profile-identity-email"><?= e($studentEmail) ?></p><?php endif; ?>

                        <span class="spf-course-tag">

                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M6 12v5c0 1.66 2.69 3 6 3s6-1.34 6-3v-5" fill="none" stroke="currentColor" stroke-width="1.75"/></svg>

                            <?= e($student['course'] ?? 'Course not set') ?>

                        </span>

                    </div>



                    <dl class="spf-meta-list">

                        <div class="spf-meta-item">

                            <dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 6H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-5" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M10 6V4a2 2 0 1 1 4 0v2" fill="none" stroke="currentColor" stroke-width="1.75"/></svg> Student ID</dt>

                            <dd><?= e($student['student_no'] ?? '—') ?></dd>

                        </div>

                        <div class="spf-meta-item">

                            <dt><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M16 2v4M8 2v4M3 10h18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg> Year Level</dt>

                            <dd><?= e($student['year_level'] ?? '—') ?></dd>

                        </div>

                        <div class="spf-meta-item">

                            <dt><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M5 20c0-3.87 3.13-7 7-7s7 3.13 7 7" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg> Gender</dt>

                            <dd data-profile-gender-preview><?= e($currentGender !== '' ? $currentGender : '—') ?></dd>

                        </div>

                    </dl>



                    <p class="profile-photo-note spf-aside-note">

                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M12 8v4M12 16h.01" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>

                        <span><?= $profilePhotoUrl !== '' ? 'Your photo appears on your student record and coordinator views.' : 'Upload a profile photo to finish your basic resume profile.' ?></span>

                    </p>

                </aside>



                <div class="student-profile-main spf-main">

                    <div class="profile-form-section spf-section">

                        <div class="profile-section-title spf-section-head">

                            <span class="spf-section-icon spf-section-icon--student" aria-hidden="true">

                                <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><circle cx="12" cy="7" r="4" fill="none" stroke="currentColor" stroke-width="1.75"/></svg>

                            </span>

                            <div>

                                <strong>Student Information</strong>

                                <small>Verified account details and editable contact fields</small>

                            </div>

                        </div>



                        <div class="spf-verified-group">

                            <div class="student-profile-grid spf-field-grid spf-field-grid--verified">

                                <div class="spf-name-block spf-field--full-row">

                                    <div class="spf-name-block-head">

                                        <span class="spf-field-label">Legal Name</span>

                                        <span class="spf-field-tag"><?= $verifiedTagSvg ?> Verified</span>

                                    </div>

                                    <div class="spf-field-grid spf-field-grid--name">

                                        <label class="spf-field spf-field--readonly">

                                            <span class="spf-field-label">First Name</span>

                                            <input required value="<?= e($studentFirstName) ?>" disabled autocomplete="given-name">

                                        </label>

                                        <label class="spf-field spf-field--readonly">

                                            <span class="spf-field-label">Middle Name</span>

                                            <input value="<?= e($studentMiddleName !== '' ? $studentMiddleName : '—') ?>" disabled autocomplete="additional-name">

                                        </label>

                                        <label class="spf-field spf-field--readonly">

                                            <span class="spf-field-label">Last Name</span>

                                            <input required value="<?= e($studentLastName) ?>" disabled autocomplete="family-name">

                                        </label>

                                    </div>

                                </div>

                                <label class="spf-field spf-field--readonly spf-field--full-row">

                                    <span class="spf-field-label">Student ID Number <span class="spf-field-tag"><?= $verifiedTagSvg ?> Verified</span></span>

                                    <input required value="<?= e($student['student_no'] ?? '') ?>" disabled>

                                </label>

                                <label class="spf-field spf-field--readonly spf-field--full-row">

                                    <span class="spf-field-label">Course <span class="spf-field-tag"><?= $verifiedTagSvg ?> Verified</span></span>

                                    <input required value="<?= e($student['course'] ?? '') ?>" disabled>

                                </label>

                            </div>

                        </div>



                        <div class="grid two student-profile-grid spf-field-grid">

                            <label class="spf-field">

                                <span class="spf-field-label">Year Level</span>

                                <input required name="year_level" value="<?= e($student['year_level'] ?? '') ?>" placeholder="e.g. 4th Year">

                            </label>

                            <label class="spf-field">

                                <span class="spf-field-label">Gender</span>

                                <select required name="gender" class="pf-native-select" data-profile-gender-select>

                                    <option value="" disabled <?= $currentGender === '' ? 'selected' : '' ?>>Select gender</option>

                                    <?php foreach ($genderOptions as $option): ?>

                                        <option value="<?= e($option) ?>" <?= $currentGender === $option ? 'selected' : '' ?>><?= e($option) ?></option>

                                    <?php endforeach; ?>

                                </select>

                            </label>

                            <label class="spf-field spf-field--full-row">

                                <span class="spf-field-label">Contact Number</span>

                                <input required name="contact_number" value="<?= e($student['contact_number'] ?? '') ?>" placeholder="09XX XXX XXXX">

                            </label>

                        </div>

                    </div>



                    <div class="profile-form-section spf-section">

                        <div class="profile-section-title spf-section-head">

                            <span class="spf-section-icon spf-section-icon--emergency" aria-hidden="true">

                                <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>

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

                            <span class="spf-section-icon spf-section-icon--address" aria-hidden="true">

                                <svg viewBox="0 0 24 24" aria-hidden="true">

                                    <path d="M3 10.5 12 3l9 7.5" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round"/>

                                    <path d="M5 9.75V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.75" fill="none" stroke="currentColor" stroke-width="1.85"/>

                                    <path d="M10 20v-5.5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1V20" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linejoin="round"/>

                                </svg>

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

                        <?php if ($isProfileComplete): ?>

                            <a class="btn spf-cancel-btn" href="<?= e(route_url('student.settings')) ?>">Cancel</a>

                        <?php endif; ?>

                        <button class="btn btn-primary spf-save-btn" type="submit">

                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11l3 3L22 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>

                            <span class="btn-text"><?= $isProfileComplete ? 'Save Profile Changes' : 'Save Profile & Unlock Dashboard' ?></span>

                            <span class="spinner"></span>

                        </button>

                    </div>

                </div>

            </div>

        </form>

    </section>

</div>



<div class="spf-crop-overlay" data-profile-crop-overlay hidden aria-hidden="true">

    <div class="spf-crop-modal" role="dialog" aria-modal="true" aria-labelledby="spf-crop-title">

        <div class="spf-crop-head">

            <div class="spf-crop-head-copy">

                <span class="spf-crop-eyebrow">Profile Photo</span>

                <h2 id="spf-crop-title">Crop your headshot</h2>

                <p>Drag to reposition and use the slider to zoom. Your photo will be saved as a square.</p>

            </div>

            <button class="spf-crop-close" type="button" aria-label="Close crop editor" data-profile-crop-cancel>&times;</button>

        </div>

        <div class="spf-crop-body">

            <div class="spf-crop-stage" data-profile-crop-stage>

                <img alt="" data-profile-crop-image draggable="false">

                <div class="spf-crop-frame" aria-hidden="true"></div>

            </div>

            <div class="spf-crop-controls">

                <label class="spf-crop-zoom">

                    <span>Zoom</span>

                    <input type="range" min="1" max="3" step="0.01" value="1" data-profile-crop-zoom>

                </label>

                <div class="spf-crop-hint">Tip: Center your face inside the square frame.</div>

            </div>

        </div>

        <div class="spf-crop-actions">

            <button class="btn" type="button" data-profile-crop-cancel>Cancel</button>

            <button class="btn btn-primary spf-crop-apply" type="button" data-profile-crop-apply>

                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4L19 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>

                Apply Crop

            </button>

        </div>

    </div>

</div>


