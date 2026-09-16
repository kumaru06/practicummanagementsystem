<?php
$profilePhotoUrl = student_profile_photo_url($student ?? null);
$studentInitial = strtoupper(substr($student['name'] ?? 'S', 0, 1));
$studentEmail = trim((string)($student['email'] ?? current_user()['email'] ?? ''));
$isProfileComplete = !empty($profileCompleted);
$currentGender = trim((string)($student['gender'] ?? ''));
$genderOptions = ['Male', 'Female', 'Other'];
$yearLevelOptions = ['3rd Year', '4th Year'];
$currentYearLevel = trim((string)($student['year_level'] ?? ''));
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
$hasLegacyAddressOnly = student_has_legacy_address_only($student ?? []);
$displayAddress = student_display_address($student ?? []);
$studentBirthdateRaw = trim((string)($student['birthdate'] ?? ''));
$studentBirthdateDisplay = '—';
if ($studentBirthdateRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $studentBirthdateRaw)) {
    $birthTs = strtotime($studentBirthdateRaw);
    if ($birthTs) {
        $studentBirthdateDisplay = date('F j, Y', $birthTs);
    }
}

$courseLabel = trim((string)($student['course'] ?? ''));
?>
<div class="student-profile-page spf-v2 spf-studio" data-student-profile-studio>

    <header class="spf-studio-head">
        <?php if ($isProfileComplete): ?>
            <nav class="spf-crumbs" aria-label="Breadcrumb">
                <a href="<?= e(route_url('student.dashboard')) ?>">Workspace</a>
                <span aria-hidden="true">›</span>
                <a href="<?= e(route_url('student.settings')) ?>">Settings</a>
                <span aria-hidden="true">›</span>
                <span>My profile</span>
            </nav>
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

        <?php if (!$isProfileComplete): ?>
        <div class="spf-studio-head-row">
            <div class="spf-studio-head-copy">
                <h1>Complete your student profile.</h1>
                <p>Fill in the required details below to unlock your student dashboard.</p>
            </div>
            <span class="spf-status-badge badge pending">Incomplete</span>
        </div>
        <?php endif; ?>
    </header>

    <?php if (!$isProfileComplete): ?>
        <div class="spf-progress-card" role="status">
            <div class="spf-progress-copy">
                <strong>Almost there</strong>
                <span>Complete all tabs and upload a photo to access the portal.</span>
            </div>
            <div class="spf-progress-track" aria-hidden="true"><span class="spf-progress-fill"></span></div>
        </div>
    <?php endif; ?>

    <form
        id="studentProfileForm"
        method="post"
        enctype="multipart/form-data"
        class="form js-validate student-profile-form spf-form spf-studio-form"
        data-student-profile-form
        data-profile-photo-crop
        data-philippine-address-form
        data-address-api="<?= e(route_url('psgc.api')) ?>"
        data-legacy-only="<?= $hasLegacyAddressOnly ? '1' : '0' ?>"
        data-address-province-code="<?= e($student['address_province_code'] ?? '') ?>"
        data-address-municipality-code="<?= e($student['address_municipality_code'] ?? '') ?>"
        data-address-barangay-code="<?= e($student['address_barangay_code'] ?? '') ?>"
        data-profile-dirty-form
    >
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="student_save_profile">

        <section class="spf-hero" aria-label="Student profile summary">
            <div class="spf-hero-bg" aria-hidden="true">
                <img
                    class="spf-hero-cover"
                    src="<?= e(asset_url('assets/image/student-profile-cover.jpg')) ?>"
                    alt=""
                    decoding="async"
                >
                <div class="spf-hero-scrim"></div>
            </div>
            <div class="spf-hero-photo">
                <div class="profile-photo-frame spf-hero-frame">
                    <img
                        class="<?= $profilePhotoUrl === '' ? 'is-hidden' : '' ?>"
                        <?= $profilePhotoUrl !== '' ? ' src="' . e($profilePhotoUrl) . '"' : '' ?>
                        alt="<?= e($studentDisplayName) ?> profile photo"
                        data-profile-photo-preview
                    >
                    <span class="profile-photo-fallback <?= $profilePhotoUrl !== '' ? 'is-hidden' : '' ?>" data-profile-photo-fallback><?= e($studentInitial) ?></span>
                </div>
                <label class="spf-hero-camera profile-photo-input" title="<?= $profilePhotoUrl !== '' ? 'Replace photo' : 'Upload photo' ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3Z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><circle cx="12" cy="13" r="3.25" fill="none" stroke="currentColor" stroke-width="1.75"/></svg>
                    <span class="sr-only"><?= $profilePhotoUrl !== '' ? 'Replace photo' : 'Upload photo' ?></span>
                    <input <?= empty($student['photo_file']) ? 'required' : '' ?> type="file" name="photo_file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" data-profile-photo-input>
                </label>
            </div>

            <div class="spf-hero-main">
                <span class="spf-hero-kicker">Your student profile</span>
                <h2 class="spf-hero-name"><?= e($studentDisplayName) ?></h2>
                <p class="spf-hero-meta">
                    <span class="spf-hero-chip" data-profile-course-preview><?= e($courseLabel !== '' ? $courseLabel : 'Course not set') ?></span>
                    <span class="spf-hero-chip spf-hero-chip--year" data-profile-year-preview><?= e($currentYearLevel !== '' ? $currentYearLevel : 'Year level') ?></span>
                </p>
            </div>

            <div class="spf-hero-id">
                <span class="spf-hero-kicker spf-hero-kicker--id">Student number</span>
                <strong class="spf-hero-id-no"><?= e($student['student_no'] ?? '—') ?></strong>
                <small class="spf-hero-id-school">AMA Computer College</small>
            </div>
        </section>

        <div class="spf-tabs" role="tablist" aria-label="Profile sections" data-profile-tabs>
            <button type="button" class="spf-tab is-active" role="tab" id="tab-personal" aria-selected="true" aria-controls="panel-personal" data-profile-tab="personal">Personal details</button>
            <button type="button" class="spf-tab" role="tab" id="tab-emergency" aria-selected="false" aria-controls="panel-emergency" data-profile-tab="emergency" tabindex="-1">Emergency contact</button>
            <button type="button" class="spf-tab" role="tab" id="tab-address" aria-selected="false" aria-controls="panel-address" data-profile-tab="address" tabindex="-1">Home address</button>
        </div>

        <div class="spf-panels">
            <section
                class="spf-panel is-active"
                role="tabpanel"
                id="panel-personal"
                aria-labelledby="tab-personal"
                data-profile-panel="personal"
            >
                <div class="spf-panel-card">
                    <header class="spf-panel-head">
                        <div>
                            <span class="spf-panel-step">01 / Essentials</span>
                            <h3>Personal details</h3>
                            <p>Contact info for your coordinator — academic records stay verified and locked.</p>
                        </div>
                    </header>
                    <div class="spf-field-grid spf-field-grid--personal">
                        <label class="spf-field spf-field--readonly spf-field--full-row">
                            <span class="spf-field-label">Email address <span class="spf-field-tag"><?= $verifiedTagSvg ?> Verified</span></span>
                            <input value="<?= e($studentEmail) ?>" disabled readonly tabindex="-1" autocomplete="email">
                        </label>
                        <label class="spf-field spf-field--full-row">
                            <span class="spf-field-label">Phone number</span>
                            <input required name="contact_number" value="<?= e($student['contact_number'] ?? '') ?>" placeholder="09XX XXX XXXX" autocomplete="tel">
                        </label>
                        <label class="spf-field">
                            <span class="spf-field-label">Year level</span>
                            <select required name="year_level" class="pf-native-select" data-profile-year-select>
                                <option value="" disabled <?= $currentYearLevel === '' ? 'selected' : '' ?>>Select year level</option>
                                <?php foreach ($yearLevelOptions as $option): ?>
                                    <option value="<?= e($option) ?>" <?= $currentYearLevel === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                <?php endforeach; ?>
                                <?php if ($currentYearLevel !== '' && !in_array($currentYearLevel, $yearLevelOptions, true)): ?>
                                    <option value="<?= e($currentYearLevel) ?>" selected><?= e($currentYearLevel) ?></option>
                                <?php endif; ?>
                            </select>
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
                    </div>

                    <details class="spf-verified-drawer">
                        <summary>
                            <span class="spf-verified-drawer-icon" aria-hidden="true"><?= $verifiedTagSvg ?></span>
                            <span>Verified academic record</span>
                            <svg class="spf-verified-drawer-chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="M5 7.5 10 12.5 15 7.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </summary>
                        <div class="spf-verified-drawer-body">
                            <div class="spf-field-grid spf-field-grid--verified">
                                <div class="spf-name-block spf-field--full-row">
                                    <div class="spf-name-block-head">
                                        <span class="spf-field-label">Legal name</span>
                                        <span class="spf-field-tag"><?= $verifiedTagSvg ?> Verified</span>
                                    </div>
                                    <div class="spf-field-grid spf-field-grid--name">
                                        <label class="spf-field spf-field--readonly">
                                            <span class="spf-field-label">First name</span>
                                            <input required value="<?= e($studentFirstName) ?>" disabled autocomplete="given-name">
                                        </label>
                                        <label class="spf-field spf-field--readonly">
                                            <span class="spf-field-label">Middle name</span>
                                            <input value="<?= e($studentMiddleName !== '' ? $studentMiddleName : '—') ?>" disabled autocomplete="additional-name">
                                        </label>
                                        <label class="spf-field spf-field--readonly">
                                            <span class="spf-field-label">Last name</span>
                                            <input required value="<?= e($studentLastName) ?>" disabled autocomplete="family-name">
                                        </label>
                                    </div>
                                </div>
                                <label class="spf-field spf-field--readonly">
                                    <span class="spf-field-label">Student ID</span>
                                    <input required value="<?= e($student['student_no'] ?? '') ?>" disabled>
                                </label>
                                <label class="spf-field spf-field--readonly">
                                    <span class="spf-field-label">Birthdate</span>
                                    <input value="<?= e($studentBirthdateDisplay) ?>" disabled readonly tabindex="-1" aria-readonly="true">
                                </label>
                                <label class="spf-field spf-field--readonly spf-field--full-row">
                                    <span class="spf-field-label">Course</span>
                                    <input required value="<?= e($student['course'] ?? '') ?>" disabled>
                                </label>
                                <p class="spf-verified-gender-note spf-field--full-row" hidden>
                                    <span data-profile-gender-preview><?= e($currentGender !== '' ? $currentGender : '—') ?></span>
                                </p>
                            </div>
                        </div>
                    </details>
                </div>
            </section>

            <section
                class="spf-panel"
                role="tabpanel"
                id="panel-emergency"
                aria-labelledby="tab-emergency"
                data-profile-panel="emergency"
                hidden
            >
                <div class="spf-panel-card">
                    <header class="spf-panel-head">
                        <div>
                            <span class="spf-panel-step">02 / Safety net</span>
                            <h3>Emergency contact</h3>
                            <p>Person to reach if something happens during your OJT deployment.</p>
                        </div>
                    </header>
                    <div class="spf-field-grid">
                        <label class="spf-field">
                            <span class="spf-field-label">Contact name</span>
                            <input required name="emergency_contact_name" value="<?= e($student['emergency_contact_name'] ?? '') ?>" placeholder="Full name" autocomplete="name">
                        </label>
                        <label class="spf-field">
                            <span class="spf-field-label">Contact number</span>
                            <input required name="emergency_contact_number" value="<?= e($student['emergency_contact_number'] ?? '') ?>" placeholder="09XX XXX XXXX" autocomplete="tel">
                        </label>
                    </div>
                </div>
            </section>

            <section
                class="spf-panel"
                role="tabpanel"
                id="panel-address"
                aria-labelledby="tab-address"
                data-profile-panel="address"
                hidden
            >
                <div class="spf-panel-card">
                    <header class="spf-panel-head">
                        <div>
                            <span class="spf-panel-step">03 / Where you live</span>
                            <h3>Home address</h3>
                            <p>Your current residential address for school and HTE records.</p>
                        </div>
                    </header>
                    <div class="spf-address-block" data-address-block<?= $hasLegacyAddressOnly ? ' hidden' : '' ?>>
                        <div class="spf-field-grid">
                            <label class="spf-field spf-address-select-wrap">
                                <span class="spf-field-label">Province</span>
                                <select name="address_province_code" data-address-province-select<?= $hasLegacyAddressOnly ? '' : ' required' ?>>
                                    <option value="" disabled <?= empty($student['address_province_code']) ? 'selected' : '' ?>>Select province</option>
                                </select>
                                <input type="hidden" name="address_province" value="<?= e($student['address_province'] ?? '') ?>" data-address-province-name>
                            </label>
                            <label class="spf-field spf-address-select-wrap">
                                <span class="spf-field-label">Municipality / City</span>
                                <select name="address_municipality_code" data-address-municipality-select<?= $hasLegacyAddressOnly ? '' : ' required' ?> disabled>
                                    <option value="" disabled selected>Select municipality / city</option>
                                </select>
                                <input type="hidden" name="address_municipality" value="<?= e($student['address_municipality'] ?? '') ?>" data-address-municipality-name>
                            </label>
                            <label class="spf-field spf-address-select-wrap">
                                <span class="spf-field-label">Barangay</span>
                                <select name="address_barangay_code" data-address-barangay-select<?= $hasLegacyAddressOnly ? '' : ' required' ?> disabled>
                                    <option value="" disabled selected>Select barangay</option>
                                </select>
                                <input type="hidden" name="address_barangay" value="<?= e($student['address_barangay'] ?? '') ?>" data-address-barangay-name>
                            </label>
                            <label class="spf-field">
                                <span class="spf-field-label">Street / House No.</span>
                                <input name="address_street" value="<?= e($student['address_street'] ?? '') ?>" placeholder="e.g. 123 Rizal St." data-address-street<?= $hasLegacyAddressOnly ? '' : ' required' ?>>
                            </label>
                        </div>
                    </div>
                    <div class="spf-address-legacy<?= $hasLegacyAddressOnly ? '' : ' is-hidden' ?>" data-address-legacy-note>
                        <div class="spf-address-legacy-card">
                            <span class="spf-field-label">Current address on file</span>
                            <p data-address-legacy-text><?= e($displayAddress) ?></p>
                            <button class="btn btn-small" type="button" data-address-update-toggle>Update to new address format</button>
                        </div>
                    </div>
                    <input type="hidden" name="address" value="<?= e($displayAddress) ?>" data-address-composed>
                </div>
            </section>
        </div>

        <div class="spf-studio-dock spf-actions" data-profile-dock>
            <p class="spf-dock-status" data-profile-dirty-status>
                <span class="spf-dock-dot" aria-hidden="true"></span>
                <span data-profile-dirty-label>No unsaved changes</span>
            </p>
            <div class="spf-dock-actions">
                <button class="btn spf-cancel-btn" type="button" data-profile-discard>Discard</button>
                <button class="btn btn-primary spf-save-btn" type="submit">
                    <span class="btn-text"><?= $isProfileComplete ? 'Save changes' : 'Save profile & unlock dashboard' ?></span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17 17 7M10 7h7v7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="spinner"></span>
                </button>
            </div>
        </div>
    </form>
</div>

<div class="spf-crop-overlay" data-profile-crop-overlay hidden aria-hidden="true">
    <div class="spf-crop-modal" role="dialog" aria-modal="true" aria-labelledby="spf-crop-title">
        <div class="spf-crop-head">
            <div class="spf-crop-head-copy">
                <span class="spf-crop-eyebrow">Profile Photo</span>
                <h2 id="spf-crop-title">Crop your headshot</h2>
                <p>Drag to reposition and use the slider to zoom. Your photo will be saved as a square.</p>
            </div>
            <button class="spf-crop-close" type="button" aria-label="Close crop editor" data-profile-crop-cancel><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>
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
