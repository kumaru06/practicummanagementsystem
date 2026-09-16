<?php
$profilePhotoUrl = student_profile_photo_url($student ?? null);
$studentInitial = strtoupper(substr((string)($student['name'] ?? 'S'), 0, 1));
$studentDisplayName = full_name($student ?? []) ?: trim((string)($student['name'] ?? 'Student'));
$courseLabel = trim((string)($student['course'] ?? ''));
$yearLevel = trim((string)($student['year_level'] ?? ''));
$isProfileComplete = !empty($student['profile_completed']);
?>
<div class="student-settings-page sss-studio">
    <header class="sss-head">
        <nav class="spf-crumbs" aria-label="Breadcrumb">
            <a href="<?= e(route_url('student.dashboard')) ?>">Workspace</a>
            <span aria-hidden="true">›</span>
            <span>Settings</span>
        </nav>
        <div class="sss-head-copy">
            <h1>Account settings</h1>
            <p>Manage your profile details and password from one place.</p>
        </div>
    </header>

    <section class="sss-hero" aria-label="Account summary">
        <div class="sss-hero-bg" aria-hidden="true">
            <img
                class="sss-hero-cover"
                src="<?= e(asset_url('assets/image/student-profile-cover.jpg')) ?>"
                alt=""
                decoding="async"
            >
            <div class="sss-hero-scrim"></div>
        </div>

        <div class="sss-hero-photo">
            <div class="sss-hero-frame">
                <?php if ($profilePhotoUrl !== ''): ?>
                    <img src="<?= e($profilePhotoUrl) ?>" alt="<?= e($studentDisplayName) ?> profile photo">
                <?php else: ?>
                    <span class="sss-hero-fallback"><?= e($studentInitial) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="sss-hero-main">
            <span class="sss-hero-kicker">Signed in as</span>
            <h2 class="sss-hero-name"><?= e($studentDisplayName) ?></h2>
            <p class="sss-hero-meta">
                <?php if ($courseLabel !== ''): ?>
                    <span class="sss-hero-chip"><?= e($courseLabel) ?></span>
                <?php endif; ?>
                <?php if ($yearLevel !== ''): ?>
                    <span class="sss-hero-chip sss-hero-chip--year"><?= e($yearLevel) ?></span>
                <?php endif; ?>
            </p>
        </div>

        <div class="sss-hero-id">
            <span class="sss-hero-kicker sss-hero-kicker--id">Student number</span>
            <strong><?= e($student['student_no'] ?? '—') ?></strong>
            <small>AMA Computer College</small>
        </div>
    </section>

    <div class="sss-grid">
        <section class="sss-card sss-card--primary">
            <div class="sss-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.2 0-7 2.1-7 4.5V20h14v-1.5C19 16.1 16.2 14 12 14Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            </div>
            <div class="sss-card-copy">
                <span class="sss-eyebrow">Profile</span>
                <h2>My student profile</h2>
                <p>Update contact details, emergency contact, home address, and photo.</p>
            </div>
            <a class="btn btn-primary sss-cta" href="<?= e(route_url('student.profile')) ?>">
                <span>Edit profile</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17 17 7M10 7h7v7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </section>

        <section class="sss-card">
            <div class="sss-card-icon sss-card-icon--muted" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M7 11V8a5 5 0 0 1 10 0v3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><rect x="5" y="11" width="14" height="10" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="16" r="1.35" fill="currentColor"/></svg>
            </div>
            <div class="sss-card-copy">
                <span class="sss-eyebrow">Security</span>
                <h2>Password</h2>
                <p>Change your account password to keep your portal secure.</p>
            </div>
            <a class="btn sss-cta sss-cta--ghost" href="<?= e(route_url('student.password.edit')) ?>">Change password</a>
        </section>

        <section class="sss-card sss-status-card">
            <div class="sss-status-head">
                <div class="sss-card-copy">
                    <span class="sss-eyebrow">Status</span>
                    <h2>Profile status</h2>
                </div>
                <span class="badge <?= $isProfileComplete ? 'active' : 'pending' ?>">
                    <?= $isProfileComplete ? 'Complete' : 'Incomplete' ?>
                </span>
            </div>
            <dl class="sss-status-list">
                <div>
                    <dt>Name</dt>
                    <dd><?= e($studentDisplayName) ?></dd>
                </div>
                <div>
                    <dt>Student ID</dt>
                    <dd><?= e($student['student_no'] ?? '—') ?></dd>
                </div>
                <div>
                    <dt>Course</dt>
                    <dd><?= e($courseLabel !== '' ? $courseLabel : '—') ?></dd>
                </div>
                <div>
                    <dt>Year level</dt>
                    <dd><?= e($yearLevel !== '' ? $yearLevel : '—') ?></dd>
                </div>
            </dl>
        </section>
    </div>
</div>
