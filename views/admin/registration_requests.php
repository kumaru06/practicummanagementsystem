<?php
$pendingCount = count($requests ?? []);
?>
<div class="reg-req-v2">
    <nav class="reg-req-breadcrumb" aria-label="Breadcrumb">
        <a href="index.php?r=admin_users">Manage Users</a>
        <span class="reg-req-breadcrumb-sep" aria-hidden="true">&rsaquo;</span>
        <span aria-current="page">Student Account Requests</span>
    </nav>

    <section class="card reg-req-pending-card">
        <div class="reg-req-card-head">
            <div class="reg-req-card-copy">
                <span class="reg-req-eyebrow">Student Self-Registration</span>
                <h2>Pending Student Account Requests</h2>
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
                <p class="reg-req-empty-sub">New student account requests will appear here for approval.</p>
            </div>
        <?php else: ?>
            <div class="reg-req-toolbar">
                <button class="reg-req-export-btn" type="button" data-reg-req-export>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                    Export CSV
                </button>
            </div>

            <div class="table-wrap reg-req-table-wrap">
                <table class="data-table reg-req-table no-row-details" data-no-tools data-no-enhance>
                    <thead>
                        <tr>
                            <th class="reg-req-col-num">#</th>
                            <th data-sort>Last Name</th>
                            <th data-sort>First Name</th>
                            <th data-sort>Middle Name</th>
                            <th data-sort>USN</th>
                            <th data-sort>Email</th>
                            <th data-sort class="reg-req-col-course">Program / Course</th>
                            <th data-sort class="reg-req-col-submitted">Submitted</th>
                            <th class="reg-req-col-action">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $index => $request): ?>
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
                            $middleName = trim((string)($request['middle_name'] ?? ''));
                            ?>
                            <tr
                                class="reg-req-row"
                                data-request-id="<?= (int)$request['id'] ?>"
                                data-last-name="<?= e($request['last_name']) ?>"
                                data-first-name="<?= e($request['first_name']) ?>"
                                data-middle-name="<?= e($middleName) ?>"
                                data-student-no="<?= e($request['student_no']) ?>"
                                data-email="<?= e($request['email']) ?>"
                                data-course="<?= e($courseLabel !== '—' ? $courseLabel : '') ?>"
                                data-verified-at="<?= e($verifiedAt) ?>"
                                data-submitted-at="<?= e($submittedAt) ?>"
                                data-cor-url="<?= e($corUrl) ?>"
                                data-full-name="<?= e($fullName) ?>"
                            >
                                <td class="reg-req-col-num"><?= $index + 1 ?></td>
                                <td class="reg-req-name"><?= e($request['last_name']) ?></td>
                                <td><?= e($request['first_name']) ?></td>
                                <td class="reg-req-middle"><?= $middleName !== '' ? e($middleName) : '<span class="muted">—</span>' ?></td>
                                <td class="reg-req-usn"><?= e($request['student_no']) ?></td>
                                <td class="reg-req-email">
                                    <span class="reg-req-email-line">
                                        <span class="reg-req-email-text"><?= e($request['email']) ?></span>
                                        <?php if ($verifiedAt !== ''): ?>
                                            <svg class="reg-req-email-check" viewBox="0 0 16 16" aria-label="Email verified" role="img">
                                                <circle cx="8" cy="8" r="8" fill="currentColor" opacity="0.18"/>
                                                <path d="M4.5 8.25 6.75 10.5 11.5 5.75" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="reg-req-course"><?= $courseLabel !== '—' ? e($courseLabel) : '<span class="muted">—</span>' ?></td>
                                <td class="reg-req-submitted"><?= e($submittedAt) ?></td>
                                <td class="reg-req-col-action">
                                    <div class="reg-req-row-actions">
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
                    <h2>Review Student Account Request</h2>
                    <p>Verify student details and assign a coordinator before approving.</p>
                </div>
            </div>
            <button class="reg-req-review-close" type="button" data-reg-req-close aria-label="Close review panel">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="reg-req-review-body">
            <div class="reg-req-review-info">
                <h3>
                    <span class="reg-req-section-icon reg-req-section-icon--student" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                    </span>
                    Student Information
                </h3>
                <ul class="reg-req-info-list">
                    <li class="reg-req-info-row">
                        <span class="reg-req-info-label">Last Name</span>
                        <span class="reg-req-info-sep" aria-hidden="true">:</span>
                        <span class="reg-req-info-value" data-reg-field="last-name">—</span>
                    </li>
                    <li class="reg-req-info-row">
                        <span class="reg-req-info-label">First Name</span>
                        <span class="reg-req-info-sep" aria-hidden="true">:</span>
                        <span class="reg-req-info-value" data-reg-field="first-name">—</span>
                    </li>
                    <li class="reg-req-info-row">
                        <span class="reg-req-info-label">Middle Name</span>
                        <span class="reg-req-info-sep" aria-hidden="true">:</span>
                        <span class="reg-req-info-value" data-reg-field="middle-name">—</span>
                    </li>
                    <li class="reg-req-info-row">
                        <span class="reg-req-info-label">USN</span>
                        <span class="reg-req-info-sep" aria-hidden="true">:</span>
                        <span class="reg-req-info-value" data-reg-field="student-no">—</span>
                    </li>
                    <li class="reg-req-info-row">
                        <span class="reg-req-info-label">Email</span>
                        <span class="reg-req-info-sep" aria-hidden="true">:</span>
                        <span class="reg-req-info-value reg-req-info-email" data-reg-field="email">—</span>
                    </li>
                    <li class="reg-req-info-row">
                        <span class="reg-req-info-label">Program / Course</span>
                        <span class="reg-req-info-sep" aria-hidden="true">:</span>
                        <span class="reg-req-info-value" data-reg-field="course">—</span>
                    </li>
                    <li class="reg-req-info-row">
                        <span class="reg-req-info-label">Date Submitted</span>
                        <span class="reg-req-info-sep" aria-hidden="true">:</span>
                        <span class="reg-req-info-value" data-reg-field="submitted-at">—</span>
                    </li>
                </ul>

                <div class="reg-req-document-block">
                    <div class="reg-req-document-copy">
                        <h4>
                            <span class="reg-req-section-icon reg-req-section-icon--doc" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                            </span>
                            Document
                        </h4>
                        <p>Certificate of Registration (COR)</p>
                    </div>
                    <a class="reg-req-doc-btn" href="#" target="_blank" rel="noopener noreferrer" data-reg-cor-link hidden>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                        View COR
                    </a>
                    <span class="reg-req-doc-missing" data-reg-cor-missing hidden>No COR uploaded</span>
                </div>
            </div>

            <div class="reg-req-review-actions">
                <div class="reg-req-approval-head">
                    <h3>
                        <span class="reg-req-section-icon reg-req-section-icon--approval" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg>
                        </span>
                        Approval &amp; Assignment
                    </h3>
                </div>

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
                            <li>The selected coordinator will be assigned</li>
                            <li>A notification email will be sent to the student and coordinator</li>
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
