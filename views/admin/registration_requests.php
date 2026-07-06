<?php
$pendingCount = count($requests ?? []);
?>
<div class="reg-req-v2">
    <nav class="reg-req-breadcrumb" aria-label="Breadcrumb">
        <a href="index.php?r=admin_users">Manage Users</a>
        <span class="reg-req-breadcrumb-sep" aria-hidden="true">&rsaquo;</span>
        <span aria-current="page">Registration Requests</span>
    </nav>

    <section class="card reg-req-pending-card">
        <div class="reg-req-card-head">
            <div class="reg-req-card-copy">
                <span class="reg-req-eyebrow">Student Self-Registration</span>
                <h2>Pending Registration Requests</h2>
                <p>Review email-verified student applications awaiting administrator approval.</p>
            </div>
            <?php if ($pendingCount > 0): ?>
                <div class="reg-req-pending-badge" aria-live="polite">
                    <strong><?= $pendingCount ?></strong>
                    <span>Pending</span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($pendingCount === 0): ?>
            <div class="reg-req-empty">
                <div class="reg-req-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg>
                </div>
                <p class="reg-req-empty-title">No pending registrations</p>
                <p class="reg-req-empty-sub">New student registration requests will appear here for approval.</p>
            </div>
        <?php else: ?>
            <div class="reg-req-toolbar">
                <button class="reg-req-export-btn" type="button" data-reg-req-export>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                    Export CSV
                </button>
            </div>

            <div class="table-wrap reg-req-table-wrap">
                <table class="data-table reg-req-table no-row-details" data-no-tools>
                    <thead>
                        <tr>
                            <th data-sort>Last Name</th>
                            <th data-sort>First Name</th>
                            <th data-sort>USN</th>
                            <th data-sort>Email</th>
                            <th data-sort>Course</th>
                            <th>Verified</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <?php
                            $verifiedAt = !empty($request['email_verified_at'])
                                ? date('M d, Y g:i A', strtotime((string)$request['email_verified_at']))
                                : '';
                            $submittedAt = date('M d, Y g:i A', strtotime((string)$request['created_at']));
                            $corUrl = !empty($request['cor_file']) ? asset($request['cor_file']) : '';
                            $fullName = trim(($request['last_name'] ?? '') . ', ' . ($request['first_name'] ?? ''));
                            $courseLabel = trim((string)(($request['program_code'] ?? '') !== '' ? $request['program_code'] . ' — ' . ($request['program_name'] ?? '') : ($request['program_name'] ?? '')));
                            if ($courseLabel === '') {
                                $courseLabel = '—';
                            }
                            ?>
                            <tr
                                class="reg-req-row"
                                data-request-id="<?= (int)$request['id'] ?>"
                                data-last-name="<?= e($request['last_name']) ?>"
                                data-first-name="<?= e($request['first_name']) ?>"
                                data-middle-name="<?= e($request['middle_name'] ?? '') ?>"
                                data-student-no="<?= e($request['student_no']) ?>"
                                data-email="<?= e($request['email']) ?>"
                                data-course="<?= e($courseLabel !== '—' ? $courseLabel : '') ?>"
                                data-verified-at="<?= e($verifiedAt) ?>"
                                data-submitted-at="<?= e($submittedAt) ?>"
                                data-cor-url="<?= e($corUrl) ?>"
                                data-full-name="<?= e($fullName) ?>"
                            >
                                <td>
                                    <div class="reg-req-name-cell">
                                        <span class="reg-req-avatar"><?= e(strtoupper(substr((string)$request['last_name'], 0, 1))) ?></span>
                                        <span><?= e($request['last_name']) ?></span>
                                    </div>
                                </td>
                                <td><?= e($request['first_name']) ?></td>
                                <td class="reg-req-usn"><?= e($request['student_no']) ?></td>
                                <td class="reg-req-email"><?= e($request['email']) ?></td>
                                <td><?= $courseLabel !== '—' ? e($courseLabel) : '<span class="muted">—</span>' ?></td>
                                <td>
                                    <?php if ($verifiedAt !== ''): ?>
                                        <span class="reg-req-verified-badge">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                            Verified
                                        </span>
                                    <?php else: ?>
                                        <span class="reg-req-legacy-badge">Legacy</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="reg-req-row-actions">
                                        <?php if ($corUrl !== ''): ?>
                                            <a class="reg-req-cor-btn" href="<?= e($corUrl) ?>" target="_blank" rel="noopener noreferrer">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                                View COR
                                            </a>
                                        <?php endif; ?>
                                        <button class="reg-req-review-btn" type="button" data-reg-req-review>
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                            Review Request
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card reg-req-review-card" id="regReqReviewPanel" data-reg-req-panel hidden>
        <div class="reg-req-review-head">
            <div class="reg-req-review-head-copy">
                <span class="reg-req-review-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </span>
                <div>
                    <h2>Review Registration Request</h2>
                    <p>Verify student details and assign a coordinator before approving.</p>
                </div>
            </div>
            <button class="reg-req-review-close" type="button" data-reg-req-close aria-label="Close review panel">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="reg-req-review-body">
            <div class="reg-req-review-info">
                <h3>Student Information</h3>
                <dl class="reg-req-detail-grid">
                    <div class="reg-req-detail-item">
                        <dt>Last Name</dt>
                        <dd data-reg-field="last-name">—</dd>
                    </div>
                    <div class="reg-req-detail-item">
                        <dt>First Name</dt>
                        <dd data-reg-field="first-name">—</dd>
                    </div>
                    <div class="reg-req-detail-item">
                        <dt>Middle Name</dt>
                        <dd data-reg-field="middle-name">—</dd>
                    </div>
                    <div class="reg-req-detail-item">
                        <dt>USN</dt>
                        <dd data-reg-field="student-no">—</dd>
                    </div>
                    <div class="reg-req-detail-item reg-req-detail-item--wide">
                        <dt>Email</dt>
                        <dd data-reg-field="email">—</dd>
                    </div>
                    <div class="reg-req-detail-item">
                        <dt>Course</dt>
                        <dd data-reg-field="course">—</dd>
                    </div>
                    <div class="reg-req-detail-item">
                        <dt>Verified</dt>
                        <dd data-reg-field="verified-at">—</dd>
                    </div>
                    <div class="reg-req-detail-item">
                        <dt>Submitted</dt>
                        <dd data-reg-field="submitted-at">—</dd>
                    </div>
                </dl>

                <div class="reg-req-document-block">
                    <h4>Document</h4>
                    <p>Certificate of Registration (COR)</p>
                    <a class="reg-req-doc-btn" href="#" target="_blank" rel="noopener noreferrer" data-reg-cor-link hidden>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                        View COR
                    </a>
                    <span class="reg-req-doc-missing" data-reg-cor-missing hidden>No COR uploaded</span>
                </div>
            </div>

            <div class="reg-req-review-actions">
                <form method="post" class="reg-req-approve-form" id="regReqApproveForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="admin_review_registration_request">
                    <input type="hidden" name="request_id" value="" data-reg-request-id>
                    <input type="hidden" name="decision" value="approve">

                    <label class="reg-req-field-label" for="regReqCoordinator">Assign Coordinator <span class="field-required">*</span></label>
                    <div class="reg-req-field">
                        <select id="regReqCoordinator" name="coordinator_id" aria-describedby="regReqCoordinatorError">
                            <option value="">Select Coordinator</option>
                            <?php foreach ($coordinators as $coordinator): ?>
                                <?php if ((int)($coordinator['is_active'] ?? 0) !== 1) continue; ?>
                                <option value="<?= (int)$coordinator['id'] ?>"><?= e($coordinator['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="reg-req-field-error" id="regReqCoordinatorError" data-reg-coordinator-error hidden>Please select a coordinator.</p>
                    </div>

                    <div class="reg-req-info-box">
                        <strong>Upon approval:</strong>
                        <ul>
                            <li>The student account will be activated</li>
                            <li>A coordinator will be assigned to manage their OJT</li>
                            <li>The student will receive access to the student portal</li>
                        </ul>
                    </div>

                    <div class="reg-req-form-actions">
                        <button class="reg-req-decline-btn" type="button" data-reg-decline>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18 18 6M6 6l12 12"/></svg>
                            Decline Request
                        </button>
                        <button class="reg-req-approve-btn" type="submit">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            Approve &amp; Assign
                        </button>
                    </div>
                </form>

                <form method="post" class="reg-req-decline-form" id="regReqDeclineForm" hidden>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="admin_review_registration_request">
                    <input type="hidden" name="request_id" value="" data-reg-request-id>
                    <input type="hidden" name="decision" value="decline">
                </form>
            </div>
        </div>
    </section>
</div>
