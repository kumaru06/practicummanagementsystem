<section class="card student-list-card">
    <div class="section-head section-head-split">
        <div><h2>My Students</h2><p class="muted">Track pre-deployment, final requirements, and evaluations in one place.</p></div>
        <div class="toolbar-inline"><input class="table-search" type="search" placeholder="Search by name or ID…" aria-label="Search students"><button class="btn btn-small view-toggle" type="button" data-target="studentsView">Card View</button></div>
    </div>
    <div id="studentsView" class="student-view-wrapper">
        <div class="student-cards-grid">
            <?php foreach ($students as $s): ?>
                <?php
                    $required = (float)($s['required_hours'] ?? 0);
                    $rendered = (float)($s['rendered_hours'] ?? 0);
                    $percent = $required > 0 ? min(100, round(($rendered / $required) * 100)) : 0;
                    $cardPhotoUrl = student_profile_photo_url($s);
                    $cardInitial = strtoupper(substr((string)($s['name'] ?? 'S'), 0, 1));
                ?>
                <article class="student-card" data-search="<?= e(strtolower($s['name'] . ' ' . $s['student_no'] . ' ' . $s['course'] . ' ' . ($s['company_name'] ?? ''))) ?>">
                    <?php if ($cardPhotoUrl !== ''): ?>
                        <span class="student-card-avatar student-card-avatar--photo"><img src="<?= e($cardPhotoUrl) ?>" alt="<?= e($s['name']) ?> profile photo"></span>
                    <?php else: ?>
                        <span class="student-card-avatar"><?= e($cardInitial) ?></span>
                    <?php endif; ?>
                    <div class="mini-ring" style="--percent: <?= $percent ?>"><span><?= $percent ?>%</span></div>
                    <div><h3><?= e($s['name']) ?></h3><p><?= e($s['student_no']) ?> · <?= e($s['course'] . ' ' . $s['year_level']) ?></p><span class="badge <?= e($s['deployment_status'] ?? 'pending') ?>"><?= e($s['deployment_status'] ?? 'pending') ?></span></div>
                    <small><?= e($s['company_name'] ?? 'No company assigned') ?></small>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="table-wrap coord-students-table-wrap"><table class="data-table coord-students-table no-row-details" data-no-tools><thead><tr><th data-sort>Student</th><th data-sort>Student ID</th><th>Pre-Deployment</th><th>Final &amp; Evaluations</th><th>Actions</th></tr></thead><tbody>
            <?php foreach ($students as $s): ?>
                <?php
                    $required = (float)($s['required_hours'] ?? 0);
                    $rendered = (float)($s['rendered_hours'] ?? 0);
                    $percent = $required > 0 ? min(100, round(($rendered / $required) * 100)) : 0;
                    $initial = strtoupper(substr((string)($s['name'] ?? 'S'), 0, 1));
                    $studentPhotoUrl = student_profile_photo_url($s);
                    $studentRequirements = $requirementsByStudent[(int)$s['id']] ?? [];
                    $finalRow = $finalRequirementsByStudent[(int)$s['id']] ?? [];
                    $finalSummary = (new FinalRequirement(db()))->overallSummary($finalRow);
                    $finalPct = $finalSummary['total'] > 0 ? round(($finalSummary['submitted'] / $finalSummary['total']) * 100) : 0;
                    $evalRow = $studentEvaluationsByStudent[(int)$s['id']] ?? [];
                    $partnerEvalStatus = StudentEvaluation::statusFor($evalRow, 'industry_partner');
                    $coordEvalStatus = StudentEvaluation::statusFor($evalRow, 'coordinator');
                    $evalDone = ($partnerEvalStatus === 'submitted' ? 1 : 0) + ($coordEvalStatus === 'submitted' ? 1 : 0);
                ?>
                <tr>
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
                        <div class="coord-status-block">
                            <span class="coord-status-pill <?= e($s['predeployment_status'] ?? 'not_submitted') ?>"><?= e(str_replace('_', ' ', $s['predeployment_status'] ?? 'not_submitted')) ?></span>
                            <?php if (in_array($s['predeployment_status'] ?? '', ['submitted', 'approved'], true)): ?>
                                <button class="coord-inline-action requirement-review-launch" type="button" data-review-modal="reviewModal-<?= (int)$s['id'] ?>">
                                    Review <?= count($studentRequirements) ?> file<?= count($studentRequirements) === 1 ? '' : 's' ?>
                                </button>
                            <?php endif; ?>
                            <?php if (($s['predeployment_status'] ?? '') === 'approved' && !empty($s['enrollment_id'])): ?>
                                <span class="coord-status-hint">Ready to forward</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="coord-final-panel">
                            <div class="coord-final-row">
                                <div class="coord-final-row-head">
                                    <span>Final Requirements</span>
                                    <strong><?= e($finalSummary['label']) ?></strong>
                                </div>
                                <div class="coord-progress-track"><span style="width: <?= (int)$finalPct ?>%"></span></div>
                            </div>
                            <div class="coord-final-row">
                                <div class="coord-final-row-head">
                                    <span>Student Evaluations</span>
                                    <strong><?= (int)$evalDone ?>/2 done</strong>
                                </div>
                                <div class="coord-eval-chips">
                                    <span class="coord-eval-chip <?= $partnerEvalStatus === 'submitted' ? 'is-done' : 'is-pending' ?>">Partner</span>
                                    <span class="coord-eval-chip <?= $coordEvalStatus === 'submitted' ? 'is-done' : 'is-pending' ?>">Coordinator</span>
                                </div>
                            </div>
                            <a class="coord-open-final" href="index.php?r=coordinator_student_final&amp;student_id=<?= (int)$s['id'] ?>">Open final section</a>
                        </div>
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
        </tbody></table></div><div class="pagination"></div>
    </div>
</section>

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
