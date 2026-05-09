<section class="card student-list-card">
    <div class="section-head section-head-split">
        <div><h2>My Students</h2><p class="muted">Switch between table and card view for a cleaner student overview.</p></div>
        <div class="toolbar-inline"><input class="table-search" placeholder="Search students..."><button class="btn btn-small view-toggle" type="button" data-target="studentsView">Card View</button></div>
    </div>
    <div id="studentsView" class="student-view-wrapper">
        <div class="student-cards-grid">
            <?php foreach ($students as $s): ?>
                <?php $required = (float)($s['required_hours'] ?? 0); $rendered = (float)($s['rendered_hours'] ?? 0); $percent = $required > 0 ? min(100, round(($rendered / $required) * 100)) : 0; ?>
                <article class="student-card" data-search="<?= e(strtolower($s['name'] . ' ' . $s['student_no'] . ' ' . $s['course'] . ' ' . ($s['company_name'] ?? ''))) ?>">
                    <div class="mini-ring" style="--percent: <?= $percent ?>"><span><?= $percent ?>%</span></div>
                    <div><h3><?= e($s['name']) ?></h3><p><?= e($s['student_no']) ?> · <?= e($s['course'] . ' ' . $s['year_level']) ?></p><span class="badge <?= e($s['deployment_status'] ?? 'pending') ?>"><?= e($s['deployment_status'] ?? 'pending') ?></span></div>
                    <small><?= e($s['company_name'] ?? 'No company assigned') ?></small>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="table-wrap"><table class="data-table no-row-details"><thead><tr><th data-sort>Name</th><th data-sort>Student No.</th><th>Pre-Deployment</th><th>Details</th></tr></thead><tbody>
            <?php foreach ($students as $s): ?>
                <?php $required = (float)($s['required_hours'] ?? 0); $rendered = (float)($s['rendered_hours'] ?? 0); $percent = $required > 0 ? min(100, round(($rendered / $required) * 100)) : 0; ?>
                <tr>
                    <td><?= e($s['name']) ?><br><small><?= e($s['email']) ?></small></td>
                    <td><?= e($s['student_no']) ?></td>
                    <td class="student-predeployment-cell">
                        <span class="badge <?= e($s['predeployment_status'] ?? 'not_submitted') ?>"><?= e(str_replace('_', ' ', $s['predeployment_status'] ?? 'not_submitted')) ?></span>
                        <?php $studentRequirements = $requirementsByStudent[(int)$s['id']] ?? []; ?>
                        <?php if (in_array($s['predeployment_status'] ?? '', ['submitted', 'approved'], true)): ?>
                            <button class="btn btn-small requirement-review-launch" type="button" data-review-modal="reviewModal-<?= (int)$s['id'] ?>">
                                Review Documents
                            </button>
                            <small class="requirement-review-count"><?= count($studentRequirements) ?> file<?= count($studentRequirements) === 1 ? '' : 's' ?> ready for checking</small>
                        <?php endif; ?>
                        <?php if (($s['predeployment_status'] ?? '') === 'approved' && !empty($s['enrollment_id'])): ?>
                            <small class="requirement-review-count requirement-review-ready">Ready for forwarding after final check</small>
                        <?php endif; ?>
                    </td>
                    <td><button class="btn btn-small student-view-btn"
                            data-name="<?= e($s['name']) ?>"
                            data-email="<?= e($s['email']) ?>"
                            data-student-no="<?= e($s['student_no']) ?>"
                            data-course="<?= e($s['course'] . ' ' . $s['year_level']) ?>"
                            data-company="<?= e($s['company_name'] ?? '-') ?>"
                            data-status="<?= e($s['deployment_status'] ?? 'pending') ?>"
                            data-rendered="<?= number_format($rendered, 2) ?>"
                            data-required="<?= number_format($required, 2) ?>"
                            data-percent="<?= $percent ?>"
                            data-cor="<?= e($s['cor_file'] ?? '') ?>"
                            data-student-id="<?= (int)$s['id'] ?>"
                            data-csrf="<?= e(csrf_token()) ?>"
                            type="button">View</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody></table></div><div class="pagination"></div>
    </div>
</section>

<div class="modal" id="studentModal">
    <div class="modal-card student-panel-modal">
        <button class="modal-close" id="studentModalClose">&times;</button>
        <div class="student-panel-header">
            <div>
                <h2 id="sm-name"></h2>
                <p id="sm-email" class="muted"></p>
            </div>
        </div>
        <div class="sm-details-grid student-panel-grid">
            <div class="student-panel-item"><span class="sm-label">Student No.</span><strong id="sm-student-no"></strong></div>
            <div class="student-panel-item"><span class="sm-label">Course</span><strong id="sm-course"></strong></div>
            <div class="student-panel-item"><span class="sm-label">Company</span><strong id="sm-company"></strong></div>
            <div class="student-panel-item"><span class="sm-label">Status</span><div id="sm-status"></div></div>
            <div class="student-panel-item student-panel-item-wide"><span class="sm-label">OJT Progress</span><strong id="sm-progress"></strong></div>
        </div>
        <div id="sm-cor-wrap" class="student-panel-actions" style="display:none">
            <a id="sm-cor-link" class="btn btn-small" target="_blank" href="#">View COR</a>
        </div>
        <details class="student-panel-reset">
            <summary>Reset Password</summary>
            <form method="post" class="student-panel-reset-form">
                <input type="hidden" name="csrf_token" id="sm-csrf">
                <input type="hidden" name="action" value="coordinator_reset_password">
                <input type="hidden" name="student_id" id="sm-student-id">
                <button class="btn btn-small" type="submit">Send Reset Email</button>
            </form>
        </details>
    </div>
</div>

<?php foreach ($students as $s): ?>
    <?php $studentRequirements = $requirementsByStudent[(int)$s['id']] ?? []; ?>
    <?php if (in_array($s['predeployment_status'] ?? '', ['submitted', 'approved'], true)): ?>
        <div class="modal requirement-review-modal" id="reviewModal-<?= (int)$s['id'] ?>">
            <div class="modal-card requirement-review-modal-card">
                <button class="modal-close requirement-review-modal-close" type="button" aria-label="Close review panel">&times;</button>
                <div class="requirement-review-modal-header">
                    <div>
                        <h2>Review Documents</h2>
                        <p><?= e($s['name']) ?> • <?= e($s['student_no']) ?></p>
                    </div>
                    <span class="badge <?= e($s['predeployment_status'] ?? 'not_submitted') ?>"><?= e(str_replace('_', ' ', $s['predeployment_status'] ?? 'not_submitted')) ?></span>
                </div>
                <div class="requirement-review-modal-body">
                    <div class="requirement-review-modal-summary">
                        <span><?= count($studentRequirements) ?> requirement<?= count($studentRequirements) === 1 ? '' : 's' ?></span>
                        <strong>Review each uploaded file below</strong>
                    </div>
                    <div class="requirement-review-modal-grid">
                        <?php foreach ($studentRequirements as $req): ?>
                            <article class="requirement-review-item status-<?= e($req['status'] ?? 'pending') ?>">
                                <div class="requirement-review-head">
                                    <div>
                                        <strong class="requirement-review-title"><?= e($req['requirement_name']) ?></strong>
                                        <small class="muted"><?= !empty($req['file_path']) ? 'Uploaded file ready for review' : 'No file uploaded yet' ?></small>
                                    </div>
                                    <span class="badge <?= e($req['status'] ?? 'pending') ?>"><?= e($req['status'] ?? 'pending') ?></span>
                                </div>
                                <div class="requirement-review-file-row"><?= !empty($req['file_path']) ? '<a class="btn btn-small requirement-review-file" target="_blank" href="' . e($req['file_path']) . '">View File</a>' : '<span class="requirement-review-empty">No file uploaded</span>' ?></div>
                                <?php if (!empty($req['file_path']) && ($s['predeployment_status'] ?? '') === 'submitted'): ?>
                                    <div class="requirement-review-actions">
                                        <form method="post" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="coordinator_review_requirement">
                                            <input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>">
                                            <input type="hidden" name="requirement_key" value="<?= e($req['requirement_key']) ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button class="btn btn-small" type="submit">Approve</button>
                                        </form>
                                        <form method="post" class="inline requirement-review-reject-form">
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
                    <?php if (($s['predeployment_status'] ?? '') === 'approved' && !empty($s['enrollment_id'])): ?>
                        <div class="requirement-forward-box">
                            <div>
                                <strong>Ready to forward deployment</strong>
                                <small>Attach the endorsement letter and send the approved documents to the partner.</small>
                            </div>
                            <form method="post" enctype="multipart/form-data" class="form requirement-forward-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="coordinator_forward_deployment">
                                <input type="hidden" name="enrollment_id" value="<?= (int)$s['enrollment_id'] ?>">
                                <label>Endorsement Letter<input required type="file" name="endorsement_file" accept=".pdf,.jpg,.jpeg,.png"></label>
                                <button class="btn btn-small" type="submit">Approve &amp; Forward</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
