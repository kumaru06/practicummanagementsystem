<?php
$enrolledCount = 0;
foreach ($students as $studentRow) {
    if (!empty($studentRow['enrollment_id'])) {
        $enrolledCount++;
    }
}
$unenrolledCount = count($students) - $enrolledCount;
$totalStudents = count($students);
?>

<div class="enrollment-v3">
    <nav class="enr-breadcrumb" aria-label="Breadcrumb">
        <a href="index.php?r=coordinator">Dashboard</a>
        <span class="enr-breadcrumb-sep" aria-hidden="true">&rsaquo;</span>
        <span aria-current="page">Student Enrollment</span>
    </nav>

    <div class="enr-stats-strip">
        <article class="enr-stat-card enr-stat-total">
            <div class="enr-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            </div>
            <div class="enr-stat-body">
                <span>My Students</span>
                <strong><?= $totalStudents ?></strong>
            </div>
        </article>
        <article class="enr-stat-card enr-stat-enrolled">
            <div class="enr-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            </div>
            <div class="enr-stat-body">
                <span>Enrolled</span>
                <strong><?= $enrolledCount ?></strong>
            </div>
        </article>
        <article class="enr-stat-card enr-stat-pending">
            <div class="enr-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            </div>
            <div class="enr-stat-body">
                <span>Ready to Enroll</span>
                <strong><?= $unenrolledCount ?></strong>
            </div>
        </article>
    </div>

    <section class="card enr-directory-card" data-enrollment-directory>
        <div class="enr-directory-head">
            <div class="enr-directory-copy">
                <span class="enr-eyebrow">Enrollment Roster</span>
                <h2>Student Enrollment</h2>
            </div>
            <button class="btn btn-primary enr-enroll-btn" type="button" data-enr-open-wizard<?= $unenrolledCount === 0 ? ' disabled' : '' ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Enroll Student
            </button>
        </div>

        <div class="enr-toolbar">
            <div class="enr-filters" role="group" aria-label="Filter by status">
                <button type="button" class="enr-filter-pill is-active" data-enrollment-filter="all">All <strong><?= $totalStudents ?></strong></button>
                <button type="button" class="enr-filter-pill enr-filter-pill--enrolled" data-enrollment-filter="enrolled">Enrolled <strong><?= $enrolledCount ?></strong></button>
                <button type="button" class="enr-filter-pill enr-filter-pill--pending" data-enrollment-filter="unenrolled">Unenrolled <strong><?= $unenrolledCount ?></strong></button>
            </div>
            <div class="enr-toolbar-actions">
                <div class="enr-search-wrap">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5Zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14Z"/></svg>
                    <input class="table-search enrollment-table-search" placeholder="Search by name or student ID..." aria-label="Search student enrollment status">
                </div>
                <button class="btn btn-small enr-export-btn" type="button" data-enr-export>Export CSV</button>
            </div>
        </div>

        <?php if ($totalStudents === 0): ?>
            <div class="enr-empty">
                <div class="enr-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg>
                </div>
                <p class="enr-empty-title">No students assigned yet</p>
                <p class="enr-empty-sub">Ask the admin to create a student account and assign it to you.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap enr-table-wrap">
                <table class="data-table no-row-details enrollment-data-table" data-enrollment-directory-table data-no-tools data-per-page="10" data-hide-column-toggle>
                    <thead>
                        <tr>
                            <th data-sort>Student</th>
                            <th data-sort>Student ID</th>
                            <th data-sort>Status</th>
                            <th class="enr-col-action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                            <?php
                            $isEnrolled = !empty($s['enrollment_id']);
                            $initials = '';
                            $nameParts = preg_split('/\s+/', trim($s['name'] ?? ''), 2);
                            if (!empty($nameParts[0])) $initials .= strtoupper(substr($nameParts[0], 0, 1));
                            if (!empty($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));
                            if ($initials === '') $initials = '?';
                            ?>
                            <tr class="enrollment-directory-row" data-student-id="<?= (int)$s['id'] ?>" data-student-enrolled="<?= $isEnrolled ? '1' : '0' ?>">
                                <td>
                                    <div class="enrollment-student-cell">
                                        <span class="enrollment-student-avatar" aria-hidden="true"><?= e($initials) ?></span>
                                        <span>
                                            <?= e($s['name']) ?><br>
                                            <small><?= e($s['program_code'] ?? $s['course']) ?></small>
                                        </span>
                                    </div>
                                </td>
                                <td><span class="enrollment-student-id"><?= e($s['student_no']) ?></span></td>
                                <td>
                                    <span class="badge enrollment-status-badge <?= $isEnrolled ? 'enrolled' : 'unenrolled' ?>">
                                        <span class="enrollment-status-dot" aria-hidden="true"></span>
                                        <?= $isEnrolled ? 'Enrolled' : 'Unenrolled' ?>
                                    </span>
                                </td>
                                <td class="enr-col-action">
                                    <?php if ($isEnrolled): ?>
                                        <span class="muted enr-action-muted">Enrolled</span>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-small enr-row-enroll" data-enr-enroll-student="<?= (int)$s['id'] ?>">Enroll</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination enrollment-pagination"></div>
        <?php endif; ?>
    </section>
</div>

<div class="enr-wizard-overlay" id="enrWizardOverlay" aria-hidden="true">
    <div class="enr-wizard-modal" role="dialog" aria-modal="true" aria-labelledby="enrWizardTitle">
        <div class="enr-wizard-modal-head">
            <div class="enr-wizard-modal-head-main">
                <div class="enr-wizard-modal-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
                <div>
                    <h2 id="enrWizardTitle">Enroll Student in OJT</h2>
                    <p>Assign a host training establishment and send enrollment emails.</p>
                </div>
            </div>
            <button type="button" class="enr-wizard-modal-close" id="enrWizardClose" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <div class="enr-wizard-modal-body" id="enrollment-wizard-panel">
            <form method="post" class="form js-validate wizard-form enrollment-wizard-form" data-wizard data-confirm-submit="Enroll this student and send the enrollment/deployment emails now? Please verify the student, company, dates, and required hours before continuing." data-confirm-title="Confirm OJT enrollment" data-confirm-ok="Enroll & send emails" data-confirm-cancel="Review details" data-confirm-async="1" data-confirm-processing-title="Enrolling student..." data-confirm-processing-message="Sending enrollment emails and preparing deployment details. This may take a moment." data-confirm-success-title="Enrollment complete" data-confirm-success-ok="Done">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="coordinator_enroll_student">

                <div class="wizard-steps enrollment-wizard-steps" aria-label="Enrollment progress">
                    <div class="enrollment-wizard-step" data-wizard-step-indicator>
                        <span class="enrollment-wizard-dot"><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg></span>
                        <em>Student</em>
                    </div>
                    <div class="enrollment-wizard-connector" aria-hidden="true"></div>
                    <div class="enrollment-wizard-step" data-wizard-step-indicator>
                        <span class="enrollment-wizard-dot"><svg viewBox="0 0 24 24"><path d="M3 21h18v-2H3v2ZM3 8v8h18V8H3Zm0-6v4h18V2H3Z"/></svg></span>
                        <em>Company & Dates</em>
                    </div>
                    <div class="enrollment-wizard-connector" aria-hidden="true"></div>
                    <div class="enrollment-wizard-step" data-wizard-step-indicator>
                        <span class="enrollment-wizard-dot"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></span>
                        <em>Confirm</em>
                    </div>
                </div>

                <div class="wizard-step active">
                    <div class="enrollment-wizard-step-intro">
                        <h3>Select a student</h3>
                        <p class="muted">Choose an unenrolled student from your roster.</p>
                    </div>
                    <label><span>Student <span class="field-required">*</span></span><select required name="student_id"><option value="">— Select student —</option><?php foreach ($students as $s): if (!empty($s['enrollment_id'])) continue; ?><option value="<?= (int)$s['id'] ?>" data-program-id="<?= (int)($s['program_id'] ?? 0) ?>" data-required-hours="<?= (int)($s['program_required_hours'] ?? 0) ?>" data-is-enrolled="0"><?= e($s['name'] . ' - ' . $s['student_no'] . ' (' . ($s['program_code'] ?? $s['course']) . ')') ?></option><?php endforeach; ?></select></label>
                    <div class="wizard-actions enrollment-wizard-actions enrollment-wizard-actions--end">
                        <button class="btn btn-primary wizard-next enrollment-wizard-next" type="button">
                            Continue
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                        </button>
                    </div>
                </div>

                <div class="wizard-step">
                    <div class="enrollment-wizard-step-intro">
                        <h3>Placement details</h3>
                        <p class="muted">Assign a host training establishment and confirm the academic term schedule.</p>
                    </div>
                    <label><span>Host Training Establishment <span class="field-required">*</span></span><select required name="company_id"><option value="">— Select Host Training Establishment —</option><?php foreach ($companies as $c): ?><option value="<?= (int)$c['id'] ?>" data-program-ids="<?= e($c['accepted_program_ids'] ?? '') ?>" data-moa-mou="<?= e(!empty($c['moa_mou_file']) ? 'index.php?r=coordinator_partner_document&company_id=' . (int)$c['id'] : '') ?>"><?= e($c['name'] . (!empty($c['accepted_programs']) ? ' — ' . $c['accepted_programs'] : '')) ?></option><?php endforeach; ?></select></label>
                    <div class="company-doc-preview enrollment-company-doc" data-company-doc-preview hidden>
                        <div class="enrollment-company-doc-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5Z"/></svg></div>
                        <div>
                            <span class="muted">Host Training Establishment MOA/MOU</span>
                            <a class="enrollment-company-doc-link" data-company-doc-link target="_blank" href="#">View document</a>
                        </div>
                    </div>
                    <label><span>Academic Term <span class="field-required">*</span></span>
                        <select required name="academic_term" data-term-autofill="1">
                            <option value="">— Select Term —</option>
                            <?php foreach (($terms ?? []) as $t): ?>
                                <?php
                                $tStart = trim((string)($t['term_start_date'] ?? ''));
                                $tEnd = trim((string)($t['term_end_date'] ?? ''));
                                $tReady = $tStart !== '' && $tEnd !== '';
                                ?>
                                <option
                                    value="<?= e($t['term_label']) ?>"
                                    data-term-start="<?= e($tStart) ?>"
                                    data-term-end="<?= e($tEnd) ?>"
                                    <?= $tReady ? '' : 'disabled' ?>
                                ><?= e($t['term_label']) ?><?= $tReady ? '' : ' (dates not set)' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <p class="enroll-term-hint enrollment-term-hint">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                        Term dates are set by the admin and fill in automatically.
                    </p>
                    <div class="enrollment-date-row">
                        <label><span>Term Start Date</span>
                            <span class="filter-date-picker form-date-picker is-placeholder is-readonly" data-date-required="1" data-date-readonly="1" data-term-start-picker="1">
                                <input type="hidden" name="term_start_date" value="">
                                <button class="filter-date-trigger" type="button" tabindex="-1" aria-hidden="true" disabled>
                                    <span class="filter-date-value">Select academic term first</span>
                                    <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                                </button>
                            </span>
                        </label>
                        <label><span>Term End Date</span>
                            <span class="filter-date-picker form-date-picker is-placeholder is-readonly" data-date-required="1" data-date-readonly="1" data-term-end-picker="1">
                                <input type="hidden" name="term_end_date" value="">
                                <button class="filter-date-trigger" type="button" tabindex="-1" aria-hidden="true" disabled>
                                    <span class="filter-date-value">Select academic term first</span>
                                    <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                                </button>
                            </span>
                        </label>
                    </div>
                    <label><span>Required Hours</span><input required readonly type="number" min="1" name="required_hours" class="enrollment-hours-input"></label>
                    <div class="wizard-actions enrollment-wizard-actions">
                        <button class="btn btn-small wizard-prev enrollment-wizard-back" type="button">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.41 16.59 10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z"/></svg>
                            Back
                        </button>
                        <button class="btn btn-primary wizard-next enrollment-wizard-next" type="button">
                            Continue
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                        </button>
                    </div>
                </div>

                <div class="wizard-step">
                    <div class="confirm-box enrollment-confirm-box"></div>
                    <div class="wizard-actions enrollment-wizard-actions">
                        <button class="btn btn-small wizard-prev enrollment-wizard-back" type="button">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.41 16.59 10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z"/></svg>
                            Back
                        </button>
                        <button class="btn btn-primary enrollment-submit-btn" type="submit">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                            <span class="btn-text">Enroll & Send Emails</span>
                            <span class="spinner"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
