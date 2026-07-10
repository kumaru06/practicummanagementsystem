<?php
$deactivationReasons = [
    'dropped' => 'Dropped',
    'complete_ojt' => 'Complete OJT',
    'failed' => 'Failed',
    'other' => 'Other',
];

$students = $students ?? [];
$programs = $programs ?? [];
$totalStudents = count($students);
$activeCount = count(array_filter($students, static fn($s) => !empty($s['is_active'])));
$inactiveCount = max(0, $totalStudents - $activeCount);
$ojtActiveCount = count(array_filter($students, static fn($s) => ($s['deployment_status'] ?? '') === 'active'));
?>
<div class="admin-students-v2">
    <nav class="asu-breadcrumb" aria-label="Breadcrumb">
        <a href="index.php?r=admin">Dashboard</a>
        <span class="asu-breadcrumb-sep" aria-hidden="true">&rsaquo;</span>
        <span aria-current="page">Manage Student</span>
    </nav>

    <div class="asu-stats-strip">
        <article class="asu-stat-card asu-stat-total">
            <div class="asu-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            </div>
            <div class="asu-stat-body">
                <span>Total Students</span>
                <strong><?= $totalStudents ?></strong>
            </div>
        </article>
        <article class="asu-stat-card asu-stat-active">
            <div class="asu-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 1 3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 11-2-2 1.41-1.41L10 11.17l3.59-3.59L15 9l-5 5z"/></svg>
            </div>
            <div class="asu-stat-body">
                <span>Active Accounts</span>
                <strong><?= $activeCount ?></strong>
            </div>
        </article>
        <article class="asu-stat-card asu-stat-inactive">
            <div class="asu-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8 0-1.85.63-3.55 1.69-4.9L16.9 18.31C15.55 19.37 13.85 20 12 20zm6.31-3.1L7.1 5.69C8.45 4.63 10.15 4 12 4c4.42 0 8 3.58 8 8 0 1.85-.63 3.55-1.69 4.9z"/></svg>
            </div>
            <div class="asu-stat-body">
                <span>Inactive</span>
                <strong><?= $inactiveCount ?></strong>
            </div>
        </article>
        <article class="asu-stat-card asu-stat-ojt">
            <div class="asu-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
            </div>
            <div class="asu-stat-body">
                <span>OJT In Progress</span>
                <strong><?= $ojtActiveCount ?></strong>
            </div>
        </article>
    </div>

    <section class="card asu-directory-card admin-users-page" data-admin-students-directory>
        <div class="asu-directory-head">
            <div class="asu-directory-copy">
                <span class="asu-eyebrow">Student Directory</span>
                <h2>Enrolled Students</h2>
                <p>Search, review profiles, and manage student account status from one place.</p>
            </div>
            <div class="asu-directory-badge" aria-live="polite">
                <strong><?= $totalStudents ?></strong>
                <span>Listed</span>
            </div>
        </div>

        <div class="asu-toolbar">
            <div class="asu-search-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                <input class="table-search asu-table-search" type="search" placeholder="Search students..." autocomplete="off">
            </div>
            <div class="asu-toolbar-actions">
                <?php if ($programs): ?>
                <label class="filter-select-wrap asu-filter-select">
                    <select data-asu-program-filter data-select-label="Program" aria-label="Filter by program">
                        <option value="all">All Programs</option>
                        <?php foreach ($programs as $program): ?>
                            <option value="<?= (int)$program['id'] ?>"><?= e($program['code'] . ' — ' . $program['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php endif; ?>
                <button class="btn btn-primary asu-create-student-btn" type="button" data-asu-create-student>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    Create Student
                </button>
                <?php if ($totalStudents > 0): ?>
                <button class="btn btn-small asu-export-btn" type="button" data-asu-export>Export CSV</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($totalStudents === 0): ?>
            <div class="asu-empty">
                <div class="asu-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg>
                </div>
                <p class="asu-empty-title">No students yet</p>
                <p class="asu-empty-sub">Approved student accounts will appear here for management.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap asu-table-wrap">
                <table class="data-table no-row-details asu-students-table" data-no-tools data-per-page="10">
                    <thead>
                        <tr>
                            <th data-sort>Last Name</th>
                            <th data-sort>First Name</th>
                            <th data-sort>Middle Name</th>
                            <th data-sort>Email</th>
                            <th data-sort>Student ID</th>
                            <th data-sort class="asu-col-program">Program</th>
                            <th>Status</th>
                            <th class="asu-col-action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                        <?php
                            $required = (float)($s['required_hours'] ?? $s['program_required_hours'] ?? 0);
                            $rendered = (float)($s['rendered_hours'] ?? 0);
                            $percent = $required > 0 ? (int)round(($rendered / $required) * 100) : 0;
                            $studentPhotoUrl = student_profile_photo_url($s);
                            $initial = strtoupper(mb_substr((string)($s['last_name'] ?? $s['name'] ?? 'S'), 0, 1));
                            $corUrl = !empty($s['cor_file']) ? asset($s['cor_file']) : '';
                            $moaUrl = !empty($s['company_moa_mou_file']) && !empty($s['company_id'])
                                ? 'index.php?r=coordinator_partner_document&company_id=' . (int)$s['company_id']
                                : '';
                            $isSelf = (int)($s['user_id'] ?? 0) === (int)current_user()['id'];
                            $firstName = trim((string)($s['first_name'] ?? ''));
                            $middleName = trim((string)($s['middle_name'] ?? ''));
                            $lastName = trim((string)($s['last_name'] ?? ''));
                            if ($firstName === '' && $lastName === '' && !empty($s['name'])) {
                                $nameParts = preg_split('/\s+/', trim((string)$s['name']), 2);
                                $firstName = $nameParts[0] ?? '';
                                $lastName = $nameParts[1] ?? '';
                            }
                            $fullName = trim($firstName . ' ' . $middleName . ' ' . $lastName);
                            $fullName = preg_replace('/\s+/', ' ', $fullName) ?: ($s['name'] ?? 'Student');
                        ?>
                        <tr data-program-id="<?= (int)($s['program_id'] ?? 0) ?>"
                            data-search="<?= e(strtolower(trim($lastName . ' ' . $firstName . ' ' . $middleName . ' ' . ($s['email'] ?? '') . ' ' . ($s['student_no'] ?? '') . ' ' . ($s['course'] ?? '') . ' ' . ($s['program_code'] ?? '')))) ?>">
                            <td class="asu-name-cell">
                                <div class="asu-student-cell">
                                    <span class="asu-student-avatar aco-avatar-tone--<?= (abs((int)($s['user_id'] ?? $s['id'] ?? 0)) % 6) + 1 ?><?= $studentPhotoUrl ? ' asu-student-avatar--photo' : '' ?>">
                                        <?php if ($studentPhotoUrl): ?>
                                            <img src="<?= e($studentPhotoUrl) ?>" alt="">
                                        <?php else: ?>
                                            <?= e($initial) ?>
                                        <?php endif; ?>
                                    </span>
                                    <span><?= $lastName !== '' ? e($lastName) : '<span class="muted">—</span>' ?></span>
                                </div>
                            </td>
                            <td class="asu-name-cell"><?= $firstName !== '' ? e($firstName) : '<span class="muted">—</span>' ?></td>
                            <td class="asu-name-cell"><?= $middleName !== '' ? e($middleName) : '<span class="muted">—</span>' ?></td>
                            <td><a class="asu-email-link" href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a></td>
                            <td class="center-cell">
                                <?php if ($s['student_no']): ?>
                                    <span class="asu-usn-badge"><?= e($s['student_no']) ?></span>
                                <?php else: ?>
                                    <span class="muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="asu-col-program"><div class="asu-program-cell" title="<?= e($s['course'] ?? '') ?>"><?= $s['course'] ? e($s['course']) : '<span class="muted">—</span>' ?></div></td>
                            <td class="center-cell">
                                <span class="asu-status-pill <?= $s['is_active'] ? 'is-active' : 'is-inactive' ?>">
                                    <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="admin-users-action-cell asu-col-action">
                                <?php if (!$isSelf): ?>
                                <details class="admin-user-action-menu">
                                    <summary class="admin-user-action-trigger" aria-label="Student actions">
                                        <svg class="admin-user-action-trigger-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm0 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                                        <span>Actions</span>
                                        <svg class="admin-user-action-trigger-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                                    </summary>
                                    <div class="admin-user-action-panel">
                                        <button class="admin-user-action-item student-view-btn" type="button"
                                            data-name="<?= e($fullName) ?>"
                                            data-email="<?= e($s['email']) ?>"
                                            data-photo-url="<?= e($studentPhotoUrl) ?>"
                                            data-initial="<?= e($initial) ?>"
                                            data-student-no="<?= e($s['student_no']) ?>"
                                            data-course="<?= e($s['course']) ?>"
                                            data-year-level="<?= e($s['year_level']) ?>"
                                            data-birthdate="<?= e($s['birthdate'] ?? '') ?>"
                                            data-company="<?= e($s['company_name'] ?? '—') ?>"
                                            data-status="<?= e($s['deployment_status'] ?? 'pending') ?>"
                                            data-predeployment-status="<?= e(str_replace('_', ' ', $s['predeployment_status'] ?? 'not_submitted')) ?>"
                                            data-orientation-datetime="<?= e($s['orientation_datetime'] ?? '') ?>"
                                            data-orientation-notes="<?= e($s['orientation_notes'] ?? '') ?>"
                                            data-official-start-date="<?= e($s['official_start_date'] ?? '') ?>"
                                            data-projected-end-date="<?= e($s['projected_end_date'] ?? '') ?>"
                                            data-rendered="<?= number_format($rendered, 2) ?>"
                                            data-required="<?= number_format($required, 2) ?>"
                                            data-percent="<?= $percent ?>"
                                            data-cor="<?= e($corUrl) ?>"
                                            data-moa-mou="<?= e($moaUrl) ?>"
                                            data-student-id="<?= (int)$s['id'] ?>"
                                            data-user-id="<?= (int)$s['user_id'] ?>"
                                            data-coordinator="<?= e($s['coordinator_name'] ?? '—') ?>">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg>
                                            View Profile
                                        </button>

                                        <?php if ($s['is_active']): ?>
                                        <details class="admin-user-action-submenu">
                                            <summary class="admin-user-action-item admin-user-action-item--danger">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z"/></svg>
                                                Deactivate Student
                                            </summary>
                                            <div class="admin-user-action-submenu-panel">
                                                <p class="admin-user-action-submenu-label">Reason for deactivation</p>
                                                <?php foreach ($deactivationReasons as $reasonKey => $reasonLabel): ?>
                                                    <?php if ($reasonKey === 'other'): ?>
                                                    <details class="admin-user-action-other">
                                                        <summary class="admin-user-action-item"><?= e($reasonLabel) ?></summary>
                                                        <form method="post" class="admin-deactivate-form admin-user-action-other-form">
                                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                            <input type="hidden" name="action" value="admin_deactivate_student">
                                                            <input type="hidden" name="user_id" value="<?= (int)$s['user_id'] ?>">
                                                            <input type="hidden" name="reason" value="other">
                                                            <label class="admin-user-action-notes-label">
                                                                <span>Reason details</span>
                                                                <textarea name="notes" rows="3" required placeholder="Please specify the reason..."></textarea>
                                                            </label>
                                                            <button class="btn btn-small btn-danger-outline admin-user-action-submit" type="submit" data-reason-label="<?= e($reasonLabel) ?>">Confirm Deactivate</button>
                                                        </form>
                                                    </details>
                                                    <?php else: ?>
                                                    <form method="post" class="admin-deactivate-form">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="admin_deactivate_student">
                                                        <input type="hidden" name="user_id" value="<?= (int)$s['user_id'] ?>">
                                                        <input type="hidden" name="reason" value="<?= e($reasonKey) ?>">
                                                        <button class="admin-user-action-item admin-user-action-item--danger" type="submit" data-reason-label="<?= e($reasonLabel) ?>"><?= e($reasonLabel) ?></button>
                                                    </form>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </details>
                                        <?php else: ?>
                                        <form method="post" class="admin-activate-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="admin_toggle_user">
                                            <input type="hidden" name="user_id" value="<?= (int)$s['user_id'] ?>">
                                            <input type="hidden" name="active" value="1">
                                            <input type="hidden" name="redirect" value="admin_users">
                                            <button class="admin-user-action-item admin-user-action-item--success" type="submit">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                                Activate Student
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </details>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <footer class="asu-table-footer">
                <div class="pagination"></div>
            </footer>
        <?php endif; ?>
    </section>
</div>

<div class="modal" id="studentModal">
    <div class="modal-card student-panel-modal">
        <button class="modal-close student-panel-close" id="studentModalClose" type="button" aria-label="Close profile">&times;</button>
        <div class="student-panel-hero">
            <div class="student-panel-hero-content">
                <span class="student-panel-avatar" id="sm-avatar-wrap">
                    <img id="sm-photo" class="is-hidden" alt="">
                    <span id="sm-initial" class="student-panel-avatar-fallback is-hidden"></span>
                </span>
                <div class="student-panel-hero-copy">
                    <span class="student-panel-kicker">Student Profile</span>
                    <h2 id="sm-name" class="student-panel-name"></h2>
                    <p id="sm-email" class="student-panel-email"></p>
                    <div class="student-panel-chips">
                        <span class="student-panel-chip" id="sm-chip-id"></span>
                        <span class="student-panel-chip" id="sm-chip-year"></span>
                        <span class="student-panel-chip student-panel-chip-status" id="sm-chip-status"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="student-panel-body">
            <div class="student-panel-progress-card">
                <div class="student-panel-progress-head">
                    <span class="sm-label">OJT Progress</span>
                    <strong id="sm-progress-text"></strong>
                </div>
                <div class="student-panel-progress-track"><span id="sm-progress-bar"></span></div>
            </div>
            <div class="student-panel-section">
                <h3 class="student-panel-section-title">Academic Details</h3>
                <div class="sm-details-grid student-panel-grid">
                    <div class="student-panel-item student-panel-item-wide"><span class="sm-label">Course</span><strong id="sm-course"></strong></div>
                    <div class="student-panel-item"><span class="sm-label">Birthdate</span><strong id="sm-birthdate"></strong></div>
                    <div class="student-panel-item"><span class="sm-label">Year Level</span><strong id="sm-year-level"></strong></div>
                    <div class="student-panel-item student-panel-item-wide admin-only-profile-field is-hidden"><span class="sm-label">Coordinator</span><strong id="sm-coordinator"></strong></div>
                </div>
            </div>
            <div class="student-panel-section">
                <h3 class="student-panel-section-title">Deployment &amp; Orientation</h3>
                <div class="sm-details-grid student-panel-grid">
                    <div class="student-panel-item"><span class="sm-label">Company</span><strong id="sm-company"></strong></div>
                    <div class="student-panel-item"><span class="sm-label">Pre-Deployment</span><div id="sm-predeployment"></div></div>
                    <div class="student-panel-item"><span class="sm-label">Official OJT Start</span><strong id="sm-official-start"></strong></div>
                    <div class="student-panel-item"><span class="sm-label">Projected End</span><strong id="sm-projected-end"></strong></div>
                    <div class="student-panel-item student-panel-item-wide"><span class="sm-label">Orientation Date/Time</span><strong id="sm-orientation-datetime"></strong></div>
                </div>
            </div>
            <div class="student-panel-section">
                <h3 class="student-panel-section-title">Orientation Notes</h3>
                <div class="student-panel-notes-box" id="sm-orientation-notes"></div>
            </div>
            <div class="student-panel-footer admin-users-profile-footer is-hidden">
                <div class="student-panel-doc-actions">
                    <a id="sm-cor-link" class="btn btn-small btn-ghost is-hidden" target="_blank" href="#">View COR</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$coordinators = $coordinators ?? [];
$activeCoordinators = array_values(array_filter(
    $coordinators,
    static fn ($c) => (int)($c['is_active'] ?? 0) === 1
));
?>
<div class="asu-create-overlay" id="asuCreateStudentOverlay" aria-hidden="true">
    <div class="asu-create-modal" role="dialog" aria-modal="true" aria-labelledby="asuCreateStudentTitle">
        <div class="asu-create-modal-head">
            <div class="asu-create-modal-head-main">
                <div class="asu-create-modal-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Zm0-8h5v2H8V9Z"/></svg>
                </div>
                <div>
                    <h2 id="asuCreateStudentTitle">Create Student from COR</h2>
                    <p>Add a student account, upload COR, and assign an OJT coordinator.</p>
                </div>
            </div>
            <button type="button" class="asu-create-modal-close" id="asuCreateStudentClose" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <form method="post" enctype="multipart/form-data" class="form js-validate asu-create-form" id="asuCreateStudentForm" data-submit-async="1" data-submit-processing-title="Creating student profile..." data-submit-processing-message="Saving student details and uploading the COR document. Please wait.">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="admin_create_student">

            <div class="asu-create-modal-body">
                <div class="asu-create-section">
                    <div class="asu-create-section-head">1. Student Identity</div>
                    <div class="asu-create-fields">
                        <label class="asu-create-field asu-create-field--full">
                            <span>Student ID/USN <em>*</em></span>
                            <input required name="student_no" autocomplete="off" inputmode="numeric" pattern="\d+" title="Student ID/USN must contain numbers only" oninput="this.value=this.value.replace(/\D/g,'')" data-admin-student-no-check placeholder="e.g. 2024001234">
                            <span class="field-check-message" data-admin-student-no-message hidden aria-live="polite"></span>
                        </label>
                        <label class="asu-create-field">
                            <span>First Name <em>*</em></span>
                            <input required name="first_name" autocomplete="given-name" pattern="[A-Za-z\s\-\.]+" title="First name must contain letters only" data-capitalize-words placeholder="e.g. Kuramu">
                        </label>
                        <label class="asu-create-field">
                            <span>Middle Name</span>
                            <input name="middle_name" autocomplete="additional-name" pattern="[A-Za-z\s\-\.]*" title="Middle name must contain letters only" data-capitalize-words placeholder="Optional">
                        </label>
                        <label class="asu-create-field">
                            <span>Last Name <em>*</em></span>
                            <input required name="last_name" autocomplete="family-name" pattern="[A-Za-z\s\-\.]+" title="Last name must contain letters only" data-capitalize-words placeholder="e.g. Doreyan">
                        </label>
                        <label class="asu-create-field asu-create-field--full">
                            <span>Email <em>*</em></span>
                            <input required type="email" name="email" autocomplete="email" data-admin-student-email-check placeholder="student@ama.edu.ph">
                            <span class="field-check-message" data-admin-student-email-message hidden aria-live="polite"></span>
                        </label>
                    </div>
                </div>

                <div class="asu-create-section">
                    <div class="asu-create-section-head">2. Academic Details</div>
                    <div class="asu-create-fields">
                        <label class="asu-create-field asu-create-field--full">
                            <span>Course <em>*</em></span>
                            <select required name="program_id">
                                <option value="">— Select course —</option>
                                <?php foreach ($programs as $program): ?>
                                    <option value="<?= (int)$program['id'] ?>"><?= e($program['code'] . ' — ' . $program['name'] . ' (' . $program['required_hours'] . ' hrs)') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="asu-create-field">
                            <span>Year Level <em>*</em></span>
                            <select required name="year_level">
                                <option value="">— Select year level —</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </label>
                        <label class="asu-create-field">
                            <span>Birthdate <em>*</em></span>
                            <span class="filter-date-picker form-date-picker is-placeholder" data-date-required="1" data-date-max="<?= date('Y-m-d', strtotime('-20 years')) ?>">
                                <input type="hidden" name="birthdate" value="">
                                <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select birthdate">
                                    <span class="filter-date-value">mm/dd/yyyy</span>
                                    <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                                </button>
                                <div class="filter-date-panel" hidden></div>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="asu-create-section">
                    <div class="asu-create-section-head">3. Assignment & COR</div>
                    <div class="asu-create-fields">
                        <label class="asu-create-field asu-create-field--full">
                            <span>Assign Coordinator <em>*</em></span>
                            <select required name="coordinator_id">
                                <option value="">— Select coordinator —</option>
                                <?php foreach ($activeCoordinators as $coord): ?>
                                    <option value="<?= (int)$coord['id'] ?>"><?= e(full_name($coord) ?: ($coord['name'] ?? 'Coordinator')) ?><?= !empty($coord['department']) ? ' — ' . e($coord['department']) : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="asu-create-field asu-create-field--full asu-create-file-field">
                            <span>COR PDF/JPG/PNG <em>*</em></span>
                            <div class="asu-create-file-row" data-asu-cor-dropzone>
                                <input required type="file" name="cor_file" accept=".pdf,.jpg,.jpeg,.png" id="asuCorFileInput">
                                <button type="button" class="asu-create-file-choose" data-asu-cor-browse>Choose file</button>
                                <span class="asu-create-file-name muted" data-asu-cor-filename>No file chosen</span>
                                <button type="button" class="asu-create-file-clear" data-asu-cor-clear hidden aria-label="Remove file">&times;</button>
                            </div>
                            <small class="muted">PDF, JPG, or PNG. Credentials are emailed when the coordinator enrolls the student.</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="asu-create-modal-actions">
                <button type="button" class="btn btn-small" id="asuCreateStudentCancel">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <span class="btn-text">Create Student</span>
                    <span class="spinner"></span>
                </button>
            </div>
        </form>
    </div>
</div>
