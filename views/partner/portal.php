<?php $selectedId = (int)($selected['id'] ?? 0); ?>
<div class="industry-portal">
    <?php if ($company): ?>
        <section class="card ip-hero-card">
            <div class="ip-hero-copy">
                <span class="eyebrow">Industry Partner Portal</span>
                <h2><?= e($company['name']) ?></h2>
                <p class="muted">Review forwarded student documents, send orientation instructions, schedule OJT start activities, and submit final evaluations.</p>
            </div>
            <div class="ip-hero-actions">
                <?= !empty($company['moa_mou_file']) ? '<a class="btn btn-small" target="_blank" href="' . e(asset($company['moa_mou_file'])) . '">View MOA/MOU</a>' : '<span class="muted">No MOA/MOU uploaded</span>' ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="card ip-table-card">
        <div class="section-head section-head-split">
            <div>
                <h2>Deployed Students</h2>
                <p class="muted">Students currently assigned to your organization.</p>
            </div>
            <input class="table-search table-search-wide" placeholder="Search students...">
        </div>
        <div class="table-wrap">
            <table class="data-table no-row-details">
                <thead>
                    <tr>
                        <th data-sort>Name</th>
                        <th data-sort>Student No.</th>
                        <th>Course/Year</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr class="<?= (int)$s['id'] === $selectedId ? 'is-selected-row' : '' ?>">
                            <td><?= e($s['student_name']) ?><br><small><?= e($s['student_email']) ?></small></td>
                            <td><?= e($s['student_no']) ?></td>
                            <td><?= e($s['course'] . ' ' . $s['year_level']) ?></td>
                            <td><?= e(trim(($s['start_date'] ?? '') . ' to ' . ($s['end_date'] ?? ''))) ?></td>
                            <td><span class="badge <?= e($s['status']) ?>"><?= e($s['status']) ?></span></td>
                            <td><a class="btn btn-small" href="<?= e(route_url('partner.portal', ['enrollment' => (int)$s['id']])) ?>#student-workspace">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="pagination"></div>
    </section>

    <?php if ($selected): ?>
        <section class="ip-workspace" id="student-workspace">
            <div class="ip-workspace-head">
                <div>
                    <span class="eyebrow">Selected Student</span>
                    <h2><?= e($selected['student_name']) ?></h2>
                    <p class="muted"><?= e($selected['course'] . ' ' . $selected['year_level']) ?> · <?= e($selected['student_no']) ?></p>
                </div>
                <span class="badge <?= e($selected['predeployment_status']) ?>"><?= e(str_replace('_', ' ', $selected['predeployment_status'])) ?></span>
            </div>

            <div class="grid two ip-detail-grid">
                <section class="card ip-sub-card">
                    <div class="section-head section-head-split">
                        <div>
                            <h2>Forwarded Documents</h2>
                            <p class="muted">Review the endorsement letter and approved pre-deployment files.</p>
                        </div>
                    </div>
                    <p class="ip-inline-file"><strong>Endorsement Letter:</strong> <?= $selected['endorsement_file'] ? '<a class="btn btn-small" target="_blank" href="' . e($selected['endorsement_file']) . '">View</a>' : '<span class="muted">Not yet forwarded</span>' ?></p>
                    <div class="table-wrap">
                        <table class="data-table compact-table">
                            <thead><tr><th>Requirement</th><th>File</th></tr></thead>
                            <tbody>
                                <?php foreach ($requirements as $req): ?>
                                    <tr><td><?= e($req['requirement_name']) ?></td><td><?= !empty($req['file_path']) ? '<a class="btn btn-small" target="_blank" href="' . e($req['file_path']) . '">View</a>' : '-' ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($selected['predeployment_status'] === 'forwarded'): ?>
                        <form method="post" style="margin-top:14px">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="partner_accept_deployment">
                            <input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
                            <button class="btn btn-primary" type="submit">Accept Deployment</button>
                        </form>
                    <?php endif; ?>
                </section>

                <section class="card ip-sub-card orientation-card">
                    <div class="section-head orientation-head">
                        <h2>Orientation &amp; OJT Start</h2>
                        <p class="muted">Send instructions first, then schedule the official orientation when the date is ready.</p>
                    </div>
                    <?php if (in_array($selected['predeployment_status'], ['accepted','orientation_scheduled'], true)): ?>
                        <div class="ip-form-stack">
                            <form method="post" class="form js-validate ip-form-panel orientation-action-card">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="partner_send_orientation_email">
                                <input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
                                <label class="no-floating-label required-label orientation-field"><span class="field-title">Orientation Email / Instructions <span class="req">*</span></span><textarea required name="orientation_notes" maxlength="500" placeholder="Send orientation instructions without setting a system date/time yet."></textarea></label>
                                <button class="btn btn-small" type="submit">Send Orientation Email Only</button>
                            </form>
                            <form method="post" class="form js-validate ip-form-panel orientation-action-card orientation-action-card-primary">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="partner_schedule_orientation">
                                <input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
                                <label class="no-floating-label required-label orientation-field"><span class="field-title">Orientation Date/Time <span class="req">*</span></span><input required type="datetime-local" name="orientation_datetime" value="<?= e($selected['orientation_datetime'] ? str_replace(' ', 'T', substr($selected['orientation_datetime'], 0, 16)) : '') ?>"></label>
                                <label class="no-floating-label required-label orientation-field"><span class="field-title">Notes <span class="req">*</span></span><textarea required name="orientation_notes" maxlength="500" placeholder="Add meeting link, venue, attire, documents to bring, and contact person."><?= e($selected['orientation_notes'] ?? '') ?></textarea></label>
                                <button class="btn btn-primary" type="submit">Schedule Orientation</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <?php if ($selected['predeployment_status'] === 'orientation_scheduled'): ?>
                        <form method="post" class="form js-validate ip-form-panel orientation-action-card orientation-action-card-primary" style="margin-top:18px">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="partner_complete_orientation">
                            <input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
                            <label class="no-floating-label required-label orientation-field"><span class="field-title">Official OJT Start Date <span class="req">*</span></span>
                                <span class="filter-date-picker form-date-picker is-placeholder" data-date-required="1">
                                    <input type="hidden" name="official_start_date" value="">
                                    <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select OJT start date"><span class="filter-date-value">mm/dd/yyyy</span><span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span></button>
                                </span>
                            </label>
                            <label class="no-floating-label orientation-field"><span class="field-title">Projected End Date</span>
                                <span class="filter-date-picker form-date-picker is-placeholder">
                                    <input type="hidden" name="projected_end_date" value="">
                                    <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select projected end date"><span class="filter-date-value">mm/dd/yyyy</span><span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span></button>
                                </span>
                                <small class="muted">Leave blank to calculate automatically from <?= (int)$selected['required_hours'] ?> required hours at 8 hours/day, weekdays only.</small>
                            </label>
                            <button class="btn btn-primary" type="submit">Mark Orientation Completed</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($selected['predeployment_status'] === 'orientation_completed'): ?>
                        <p class="muted">OJT officially started on <?= e($selected['official_start_date']) ?>. Projected end date: <?= e($selected['projected_end_date']) ?>.</p>
                    <?php endif; ?>
                </section>
            </div>

            <div class="grid two ip-detail-grid">
                <section class="card ip-sub-card">
                    <h2><?= e($selected['student_name']) ?> - Time Records</h2>
                    <div class="table-wrap">
                        <table class="data-table compact-table">
                            <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Hours</th><th>Tasks</th></tr></thead>
                            <tbody><?php foreach ($dtrs as $d): ?><tr><td><?= e($d['work_date']) ?></td><td><?= e($d['time_in']) ?></td><td><?= e($d['time_out']) ?></td><td><?= e($d['hours']) ?></td><td><?= e($d['tasks_done']) ?></td></tr><?php endforeach; ?></tbody>
                        </table>
                    </div>
                </section>
                <section class="card ip-sub-card">
                    <h2>Final Evaluation</h2>
                    <?php if ($selected['predeployment_status'] === 'orientation_completed'): ?>
                        <form method="post" class="form js-validate">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="partner_submit_evaluation">
                            <input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
                            <label>Rating (1-5)<input required type="number" min="1" max="5" name="rating" value="<?= e($evaluation['rating'] ?? '') ?>"></label>
                            <label>Comments<textarea required name="comments"><?= e($evaluation['comments'] ?? '') ?></textarea></label>
                            <button class="btn btn-primary" type="submit"><span class="btn-text"><?= $evaluation ? 'Update Evaluation' : 'Submit Evaluation' ?></span><span class="spinner"></span></button>
                        </form>
                    <?php else: ?>
                        <p class="muted">Final evaluation unlocks after orientation is completed and the student officially starts OJT.</p>
                        <button class="btn btn-primary" type="button" disabled>Evaluation Locked</button>
                    <?php endif; ?>
                </section>
            </div>
        </section>
    <?php else: ?>
        <section class="card ip-empty-state">
            <h2>Select a student to start</h2>
            <p class="muted">Open a student from the table above to review documents, handle orientation, and manage evaluations.</p>
        </section>
    <?php endif; ?>
</div>
