<?php
$coordOjtStatus = static function (array $student): array {
    $status = strtolower((string)($student['deployment_status'] ?? ''));
    if ($status === 'completed') {
        return ['key' => 'completed', 'label' => 'Completed', 'hint' => 'OJT completed', 'class' => 'completed'];
    }
    if ($status === 'active' && !empty($student['official_start_date'])) {
        return ['key' => 'started', 'label' => 'Started', 'hint' => 'OJT in progress', 'class' => 'started'];
    }
    return ['key' => 'not_started', 'label' => 'Not Yet Started', 'hint' => 'No start date yet', 'class' => 'not-started'];
};

$coordPredeploymentDisplay = static function (string $status): array {
    $completedStates = ['forwarded', 'accepted', 'orientation_scheduled', 'orientation_completed'];
    if (in_array($status, $completedStates, true)) {
        return ['label' => '✓ Completed', 'class' => 'completed', 'reviewable' => false];
    }
    if ($status === 'approved') {
        return ['label' => '✓ Completed', 'class' => 'completed', 'reviewable' => true];
    }
    if ($status === 'submitted') {
        return ['label' => 'In Review', 'class' => 'submitted', 'reviewable' => true];
    }
    return ['label' => 'Not Submitted', 'class' => 'not_submitted', 'reviewable' => false];
};

$formatOjtDate = static function (?string $date): string {
    if ($date === null || trim($date) === '') {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('M d, Y', $ts) : '—';
};

$termOptions = [];
foreach ($students as $studentRow) {
    $termLabel = trim((string)($studentRow['academic_term'] ?? ''));
    if ($termLabel !== '') {
        $termOptions[$termLabel] = $termLabel;
    }
}
ksort($termOptions);
?>

<div class="my-students-v2" data-my-students-directory>

    <div class="ms-stats-strip">
        <article class="ms-stat-card ms-stat-total">
            <div class="ms-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="ms-stat-body">
                <span>Total Students</span>
                <strong data-ms-stat="total"><?= (int)($stats['total'] ?? count($students)) ?></strong>
            </div>
        </article>
        <article class="ms-stat-card ms-stat-started">
            <div class="ms-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
            <div class="ms-stat-body">
                <span>OJT Started</span>
                <strong data-ms-stat="started"><?= (int)($stats['started'] ?? 0) ?></strong>
            </div>
        </article>
        <article class="ms-stat-card ms-stat-pending">
            <div class="ms-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="ms-stat-body">
                <span>Not Yet Started</span>
                <strong data-ms-stat="not_started"><?= (int)($stats['not_started'] ?? 0) ?></strong>
            </div>
        </article>
        <article class="ms-stat-card ms-stat-completed">
            <div class="ms-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
            </div>
            <div class="ms-stat-body">
                <span>OJT Completed</span>
                <strong data-ms-stat="completed"><?= (int)($stats['completed'] ?? 0) ?></strong>
            </div>
        </article>
    </div>

    <section class="card ms-directory-card">
        <header class="ms-directory-head">
            <div class="ms-directory-copy">
                <h2>My Students</h2>
                <p class="muted">Track pre-deployment, final requirements, and evaluations in one place.</p>
            </div>
            <div class="ms-directory-toolbar" role="group" aria-label="Filter students">
                <div class="ms-search-wrap">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5Zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14Z"/></svg>
                    <input class="table-search ms-table-search" type="search" placeholder="Search by student name or ID…" aria-label="Search students">
                </div>
                <label class="filter-select-wrap ms-filter-select">
                    <span class="visually-hidden">OJT Status</span>
                    <select data-ms-ojt-filter aria-label="Filter by OJT status">
                        <option value="all">OJT Status</option>
                        <option value="started">Started</option>
                        <option value="not_started">Not Yet Started</option>
                        <option value="completed">Completed</option>
                    </select>
                </label>
                <label class="filter-select-wrap ms-filter-select">
                    <span class="visually-hidden">Term / Batch</span>
                    <select data-ms-term-filter aria-label="Filter by term or batch">
                        <option value="all">Term / Batch</option>
                        <?php foreach ($termOptions as $termLabel): ?>
                            <option value="<?= e($termLabel) ?>"><?= e($termLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="btn btn-small ms-export-btn" type="button" data-ms-export>
                    <span class="ms-export-icon" aria-hidden="true"><?php readfile(__DIR__ . '/../../assets/image/icon/dl.svg'); ?></span>
                    Export
                </button>
            </div>
        </header>

        <div class="table-wrap ms-table-wrap">
            <table class="data-table coord-students-table ms-students-table no-row-details" data-no-tools data-per-page="6" data-ms-students-table>
                <thead>
                    <tr>
                        <th data-sort>Student</th>
                        <th data-sort>Student ID</th>
                        <th data-sort>OJT Status</th>
                        <th data-sort>OJT Start Date</th>
                        <th data-sort>OJT End Date</th>
                        <th>Pre-Deployment</th>
                        <th>Final &amp; Evaluations</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <?php
                            $required = (float)($s['required_hours'] ?? 0);
                            $rendered = (float)($s['rendered_hours'] ?? 0);
                            $percent = $required > 0 ? min(100, round(($rendered / $required) * 100)) : 0;
                            $initial = strtoupper(substr((string)($s['name'] ?? 'S'), 0, 1));
                            $studentPhotoUrl = student_profile_photo_url($s);
                            $studentRequirements = $requirementsByStudent[(int)$s['id']] ?? [];
                            $evalRow = $studentEvaluationsByStudent[(int)$s['id']] ?? [];
                            $partnerEvalStatus = StudentEvaluation::statusFor($evalRow, 'industry_partner');
                            $coordEvalStatus = StudentEvaluation::statusFor($evalRow, 'coordinator');
                            $evalDone = ($partnerEvalStatus === 'submitted' ? 1 : 0) + ($coordEvalStatus === 'submitted' ? 1 : 0);
                            $ojtStatus = $coordOjtStatus($s);
                            $predeployment = $coordPredeploymentDisplay((string)($s['predeployment_status'] ?? 'not_submitted'));
                            $ojtEndDate = $s['projected_end_date'] ?? $s['end_date'] ?? null;
                            $termLabel = trim((string)($s['academic_term'] ?? ''));
                        ?>
                        <tr
                            data-ojt-status="<?= e($ojtStatus['key']) ?>"
                            data-academic-term="<?= e($termLabel) ?>"
                            data-search="<?= e(strtolower($s['name'] . ' ' . $s['student_no'] . ' ' . $s['email'])) ?>"
                        >
                            <td>
                                <div class="coord-student-identity">
                                    <?php if ($studentPhotoUrl !== ''): ?>
                                        <span class="coord-student-avatar coord-student-avatar--photo"><img src="<?= e($studentPhotoUrl) ?>" alt="<?= e($s['name']) ?> profile photo"></span>
                                    <?php else: ?>
                                        <span class="coord-student-avatar"><?= e($initial) ?></span>
                                    <?php endif; ?>
                                    <div class="coord-student-meta">
                                        <strong><?= e($s['name']) ?></strong>
                                        <small><?= e($s['email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="coord-student-id"><?= e($s['student_no']) ?></span></td>
                            <td>
                                <div class="ms-ojt-status">
                                    <span class="ms-ojt-badge ms-ojt-badge--<?= e($ojtStatus['class']) ?>"><?= e($ojtStatus['label']) ?></span>
                                    <small class="ms-ojt-hint"><?= e($ojtStatus['hint']) ?></small>
                                </div>
                            </td>
                            <td><span class="ms-date-cell"><?= e($formatOjtDate($s['official_start_date'] ?? null)) ?></span></td>
                            <td><span class="ms-date-cell"><?= e($formatOjtDate($ojtEndDate)) ?></span></td>
                            <td>
                                <?php if ($predeployment['reviewable']): ?>
                                    <button class="ms-predeploy-badge ms-predeploy-badge--<?= e($predeployment['class']) ?> requirement-review-launch" type="button" data-review-modal="reviewModal-<?= (int)$s['id'] ?>"><?= e($predeployment['label']) ?></button>
                                <?php else: ?>
                                    <span class="ms-predeploy-badge ms-predeploy-badge--<?= e($predeployment['class']) ?>"><?= e($predeployment['label']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="ms-eval-progress <?= $evalDone >= 2 ? 'is-complete' : 'is-pending' ?>" href="index.php?r=coordinator_student_final&amp;student_id=<?= (int)$s['id'] ?>"><?= (int)$evalDone ?>/2 done</a>
                            </td>
                            <td class="coord-actions-cell">
                                <button class="btn btn-small btn-ghost student-view-btn"
                                    data-name="<?= e($s['name']) ?>"
                                    data-email="<?= e($s['email']) ?>"
                                    data-photo-url="<?= e($studentPhotoUrl) ?>"
                                    data-initial="<?= e($initial) ?>"
                                    data-student-no="<?= e($s['student_no']) ?>"
                                    data-course="<?= e($s['course']) ?>"
                                    data-year-level="<?= e($s['year_level']) ?>"
                                    data-birthdate="<?= e($s['birthdate'] ?? '') ?>"
                                    data-company="<?= e($s['company_name'] ?? '-') ?>"
                                    data-status="<?= e($s['deployment_status'] ?? 'pending') ?>"
                                    data-predeployment-status="<?= e(str_replace('_', ' ', $s['predeployment_status'] ?? 'not_submitted')) ?>"
                                    data-orientation-datetime="<?= e($s['orientation_datetime'] ?? '') ?>"
                                    data-orientation-notes="<?= e($s['orientation_notes'] ?? '') ?>"
                                    data-official-start-date="<?= e($s['official_start_date'] ?? '') ?>"
                                    data-projected-end-date="<?= e($s['projected_end_date'] ?? '') ?>"
                                    data-rendered="<?= number_format($rendered, 2) ?>"
                                    data-required="<?= number_format($required, 2) ?>"
                                    data-percent="<?= $percent ?>"
                                    data-cor="<?= e($s['cor_file'] ?? '') ?>"
                                    data-moa-mou="<?= e(!empty($s['company_moa_mou_file']) && !empty($s['company_id']) ? 'index.php?r=coordinator_partner_document&company_id=' . (int)$s['company_id'] : '') ?>"
                                    data-student-id="<?= (int)$s['id'] ?>"
                                    data-user-id="<?= (int)$s['user_id'] ?>"
                                    data-final-url="index.php?r=coordinator_student_final&amp;student_id=<?= (int)$s['id'] ?>"
                                    data-csrf="<?= e(csrf_token()) ?>"
                                    type="button">View profile</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <footer class="ms-table-footer">
            <p class="ms-pagination-info" data-pagination-info>Showing 0 to 0 of 0 entries</p>
            <div class="pagination ms-pagination" data-pagination-nav></div>
        </footer>
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
            <div class="student-panel-footer">
                <div class="student-panel-doc-actions">
                    <a id="sm-final-link" class="btn btn-small btn-primary" href="#">Open Final Section</a>
                    <a id="sm-cor-link" class="btn btn-small btn-ghost is-hidden" target="_blank" href="#">View COR</a>
                    <a id="sm-moa-link" class="btn btn-small btn-ghost is-hidden" target="_blank" href="#">View MOA/MOU</a>
                </div>
                <details class="student-panel-reset">
                    <summary>Account Actions</summary>
                    <div class="student-panel-reset-stack">
                        <form method="post" class="student-panel-reset-form" id="sm-email-form">
                            <input type="hidden" name="csrf_token" id="sm-email-csrf">
                            <input type="hidden" name="action" value="coordinator_update_student_email">
                            <input type="hidden" name="user_id" id="sm-email-user-id">
                            <label class="student-panel-field-label">Update Email
                                <input type="email" name="email" id="sm-email-input" required placeholder="Enter new email address">
                            </label>
                            <button class="btn btn-small" type="submit">Save Email</button>
                        </form>
                        <form method="post" class="student-panel-reset-form">
                            <input type="hidden" name="csrf_token" id="sm-csrf">
                            <input type="hidden" name="action" value="coordinator_reset_password">
                            <input type="hidden" name="student_id" id="sm-student-id">
                            <button class="btn btn-small btn-ghost" type="submit">Send Password Reset</button>
                        </form>
                    </div>
                </details>
            </div>
        </div>
    </div>
</div>

<?php foreach ($students as $s): ?>
    <?php $studentRequirements = $requirementsByStudent[(int)$s['id']] ?? []; ?>
    <?php if (in_array($s['predeployment_status'] ?? '', ['submitted', 'approved'], true)): ?>
        <div class="modal requirement-review-modal" id="reviewModal-<?= (int)$s['id'] ?>" data-student-id="<?= (int)$s['id'] ?>">
            <div class="modal-card requirement-review-modal-card">
                <button class="modal-close requirement-review-modal-close" type="button" aria-label="Close review panel">&times;</button>
                <div class="requirement-review-modal-header">
                    <div>
                        <h2>Review Documents</h2>
                        <p><?= e($s['name']) ?> • <?= e($s['student_no']) ?></p>
                    </div>
                    <span class="badge <?= e($s['predeployment_status'] ?? 'not_submitted') ?>" data-modal-status-badge><?= e(str_replace('_', ' ', $s['predeployment_status'] ?? 'not_submitted')) ?></span>
                </div>
                <div class="requirement-review-modal-body">
                    <div class="requirement-review-modal-summary">
                        <span><?= count($studentRequirements) ?> requirement<?= count($studentRequirements) === 1 ? '' : 's' ?></span>
                        <strong>Review each uploaded file below</strong>
                    </div>
                    <div class="requirement-review-modal-grid">
                        <?php foreach ($studentRequirements as $req): ?>
                            <article class="requirement-review-item status-<?= e($req['status'] ?? 'pending') ?>" data-requirement-key="<?= e($req['requirement_key']) ?>">
                                <div class="requirement-review-head">
                                    <div>
                                        <strong class="requirement-review-title"><?= e($req['requirement_name']) ?></strong>
                                        <small class="muted"><?= !empty($req['file_path']) ? 'Uploaded file ready for review' : 'No file uploaded yet' ?></small>
                                    </div>
                                    <span class="badge <?= e($req['status'] ?? 'pending') ?>" data-req-status-badge><?= e($req['status'] ?? 'pending') ?></span>
                                </div>
                                <div class="requirement-review-file-row"><?= !empty($req['file_path']) ? '<a class="btn btn-small requirement-review-file" target="_blank" href="' . e($req['file_path']) . '">View File</a>' : '<span class="requirement-review-empty">No file uploaded</span>' ?></div>
                                <?php if (!empty($req['file_path']) && ($s['predeployment_status'] ?? '') === 'submitted'): ?>
                                    <div class="requirement-review-actions" data-review-actions>
                                        <form method="post" class="inline js-review-form" data-review-status="approved">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="coordinator_review_requirement">
                                            <input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>">
                                            <input type="hidden" name="requirement_key" value="<?= e($req['requirement_key']) ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button class="btn btn-small" type="submit">Approve</button>
                                        </form>
                                        <form method="post" class="inline requirement-review-reject-form js-review-form" data-review-status="rejected">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="coordinator_review_requirement">
                                            <input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>">
                                            <input type="hidden" name="requirement_key" value="<?= e($req['requirement_key']) ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <input class="requirement-review-note" name="notes" placeholder="Reason for rejection">
                                            <button class="btn btn-small" type="submit">Reject</button>
                                        </form>
                                    </div>
                                <?php elseif (!empty($req['review_notes'])): ?>
                                    <div class="requirement-review-notes"><span>Review notes</span><strong><?= e($req['review_notes']) ?></strong></div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($s['enrollment_id'])): ?>
                        <div class="requirement-forward-box" data-forward-box<?= ($s['predeployment_status'] ?? '') !== 'approved' ? ' style="display:none"' : '' ?>>
                            <div>
                                <strong>Ready to forward deployment</strong>
                                <small>The endorsement letter will be generated automatically and sent to the Industry Partner along with the approved documents.</small>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <a class="btn btn-small" target="_blank" href="<?= e(route_url('coordinator.preview_endorsement', ['enrollment' => (int)$s['enrollment_id']])) ?>">Preview Letter</a>
                                <form method="post" class="form requirement-forward-form" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="coordinator_forward_deployment">
                                    <input type="hidden" name="enrollment_id" value="<?= (int)$s['enrollment_id'] ?>">
                                    <button class="btn btn-small" type="submit">Approve &amp; Forward</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
