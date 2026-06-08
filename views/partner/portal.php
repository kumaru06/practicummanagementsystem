<?php
$selectedId = (int)($selected['id'] ?? 0);
$renderedHours = array_reduce($dtrs ?? [], static fn ($total, $dtr) => $total + (float)($dtr['hours'] ?? 0), 0.0);
$requiredHours = (float)($selected['required_hours'] ?? 0);
$evaluationUnlocked = $selected && $requiredHours > 0 && $renderedHours >= $requiredHours;
?>
<div class="industry-portal">
    <?php if ($company): ?>
        <section class="card ip-hero-card">
            <div class="ip-hero-copy">
                <span class="eyebrow">Industry Partner Portal</span>
                <h2><?= e($company['name']) ?></h2>
                <p class="muted">Review forwarded student documents, schedule OJT start activities, and submit final evaluations after required hours are completed.</p>
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
                        <th data-sort>Student ID</th>
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
                <section class="card ip-sub-card ip-docs-card">
                    <div class="section-head section-head-split">
                        <div>
                            <h2>Forwarded Documents</h2>
                            <p class="muted">Review the endorsement letter and approved pre-deployment files.</p>
                        </div>
                    </div>
                    <div class="ip-endorsement-block">
                        <div class="ip-endorsement-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 2 5 5h-5V4zM6 20V4h5v7h7v9H6z"/><path d="M8 13h8v1.5H8zm0 3h8v1.5H8zm0-6h3v1.5H8z"/></svg></div>
                        <div>
                            <strong>Endorsement Letter</strong>
                            <small class="muted">Official letter from the OJT Coordinator</small>
                        </div>
                        <a class="btn btn-small" target="_blank" href="<?= e(route_url('partner.view_endorsement', ['enrollment' => (int)$selected['id']])) ?>">View Letter</a>
                    </div>
                    <div class="ip-docs-list">
                        <div class="ip-docs-list-header">
                            <span><?= count($requirements) ?> Pre-Deployment Requirements</span>
                            <span class="badge approved">All Approved</span>
                        </div>
                        <?php foreach ($requirements as $req): ?>
                            <div class="ip-docs-list-row">
                                <span class="ip-docs-list-name"><svg viewBox="0 0 20 20" width="14" height="14" fill="#16a34a"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg> <?= e($req['requirement_name']) ?></span>
                                <?= !empty($req['file_path']) ? '<a class="btn btn-small" target="_blank" href="' . e($req['file_path']) . '">View</a>' : '<span class="muted">—</span>' ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($selected['predeployment_status'] === 'forwarded'): ?>
                        <form method="post" style="margin-top:auto; padding-top:18px;">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="partner_accept_deployment">
                            <input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
                            <button class="btn btn-primary" type="submit" style="width:100%">Accept Deployment</button>
                        </form>
                    <?php endif; ?>
                </section>

                <div class="ip-right-stack">
                    <section class="card ip-sub-card orientation-card">
                        <div class="section-head orientation-head">
                            <h2>Orientation &amp; OJT Start</h2>
                            <p class="muted">Select the orientation date and time, then add notes for the student before sending the schedule.</p>
                        </div>
                        <?php if ($selected['predeployment_status'] === 'accepted'): ?>
                            <div class="ip-form-stack">
                                <form method="post" class="form js-validate ip-form-panel orientation-action-card orientation-action-card-primary">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="partner_schedule_orientation">
                                    <input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
                                    <label class="no-floating-label required-label orientation-field"><span class="field-title">Orientation Date/Time <span class="req">*</span></span>
                                        <span class="filter-date-picker form-date-picker form-datetime-picker is-placeholder" data-datetime-picker="1" data-date-required="1">
                                            <input type="hidden" name="orientation_datetime" value="">
                                            <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select orientation date and time">
                                                <span class="filter-date-value">Select date &amp; time</span>
                                                <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                                            </button>
                                        </span>
                                    </label>
                                    <label class="no-floating-label required-label orientation-field"><span class="field-title">Notes <span class="req">*</span></span><textarea required name="orientation_notes" maxlength="500" placeholder="Add meeting link, venue, attire, documents to bring, and contact person."></textarea></label>
                                    <button class="btn btn-primary" type="submit">Send Schedule Orientation</button>
                                </form>
                            </div>
                        <?php elseif ($selected['predeployment_status'] === 'orientation_scheduled'): ?>
                            <div class="ip-form-stack">
                                <div class="ip-form-panel orientation-action-card orientation-action-card-primary orientation-locked">
                                    <div class="orientation-locked-badge"><svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg> Orientation Scheduled</div>
                                    <div class="orientation-field">
                                        <span class="field-title">Orientation Date/Time</span>
                                        <div class="orientation-locked-value"><?php
                                            if (!empty($selected['orientation_datetime'])) {
                                                $dt = new DateTime($selected['orientation_datetime']);
                                                echo e($dt->format('F j, Y \a\t g:i A'));
                                            } else {
                                                echo '—';
                                            }
                                        ?></div>
                                    </div>
                                    <div class="orientation-field">
                                        <span class="field-title">Notes</span>
                                        <div class="orientation-locked-value"><?= e($selected['orientation_notes'] ?? '—') ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($selected['predeployment_status'] === 'orientation_completed'): ?>
                            <div class="ojt-completed-block">
                                <div class="ojt-completed-header">
                                    <div class="ojt-completed-icon"><svg viewBox="0 0 20 20" width="20" height="20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg></div>
                                    <div>
                                        <strong>OJT is Active</strong>
                                        <small>Orientation completed — hours are now being tracked</small>
                                    </div>
                                </div>
                                <div class="ojt-completed-timeline">
                                    <?php if (!empty($selected['orientation_datetime'])): ?>
                                        <?php $dt = new DateTime($selected['orientation_datetime']); ?>
                                        <div class="ojt-timeline-step is-done">
                                            <div class="ojt-timeline-dot"></div>
                                            <div class="ojt-timeline-content">
                                                <span class="ojt-timeline-label">Orientation</span>
                                                <strong><?= e($dt->format('M j, Y')) ?></strong>
                                                <small><?= e($dt->format('g:i A')) ?></small>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($selected['official_start_date'])): ?>
                                        <?php $sd = new DateTime($selected['official_start_date']); ?>
                                        <div class="ojt-timeline-step is-done">
                                            <div class="ojt-timeline-dot"></div>
                                            <div class="ojt-timeline-content">
                                                <span class="ojt-timeline-label">OJT Started</span>
                                                <strong><?= e($sd->format('M j, Y')) ?></strong>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($selected['projected_end_date'])): ?>
                                        <?php $ed = new DateTime($selected['projected_end_date']); ?>
                                        <div class="ojt-timeline-step is-future">
                                            <div class="ojt-timeline-dot"></div>
                                            <div class="ojt-timeline-content">
                                                <span class="ojt-timeline-label">Projected End</span>
                                                <strong><?= e($ed->format('M j, Y')) ?></strong>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </section>

                    <?php if ($selected['predeployment_status'] === 'orientation_scheduled'): ?>
                        <section class="card ip-sub-card orientation-card ojt-start-card">
                            <div class="section-head orientation-head">
                                <h2>Complete Orientation &amp; Begin OJT</h2>
                                <p class="muted">Once the orientation is done, set the official OJT start date to begin tracking hours.</p>
                            </div>
                            <form method="post" class="form js-validate">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="partner_complete_orientation">
                                <input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
                                <?php $orientationDate = !empty($selected['orientation_datetime']) ? (new DateTime($selected['orientation_datetime']))->format('Y-m-d') : date('Y-m-d'); ?>
                                <label class="no-floating-label required-label orientation-field"><span class="field-title">Official OJT Start Date <span class="req">*</span></span>
                                    <span class="filter-date-picker form-date-picker is-placeholder" data-date-required="1" data-date-min="<?= e($orientationDate) ?>">
                                        <input type="hidden" name="official_start_date" value="">
                                        <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select OJT start date"><span class="filter-date-value">mm/dd/yyyy</span><span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span></button>
                                    </span>
                                </label>
                                <label class="no-floating-label orientation-field"><span class="field-title">Projected End Date</span>
                                    <span class="filter-date-picker form-date-picker is-placeholder">
                                        <input type="hidden" name="projected_end_date" value="">
                                        <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select projected end date"><span class="filter-date-value">mm/dd/yyyy</span><span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span></button>
                                    </span>
                                    <small class="muted">Leave blank to calculate automatically from <?= (int)$selected['required_hours'] ?> required hours at 8 hours/day, Monday to Saturday.</small>
                                </label>
                                <button class="btn btn-primary" type="submit">Mark Orientation Completed</button>
                            </form>
                        </section>
                    <?php endif; ?>
                </div>

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
                    <?php if ($evaluationUnlocked): ?>
                        <?php if (!empty($evaluation)): ?>
                            <div class="eval-summary">
                                <div class="eval-summary-grade">
                                    <span class="eval-summary-grade-value"><?= e(number_format((float)($evaluation['final_grade'] ?? 0), 2)) ?>%</span>
                                    <span class="eval-summary-grade-label">Final Grade</span>
                                </div>
                                <p class="muted">Evaluation submitted<?= !empty($evaluation['submitted_at']) ? ' on ' . e(date('M j, Y', strtotime($evaluation['submitted_at']))) : '' ?>.</p>
                                <?php if (!empty($evaluation['certificate_file'])): ?>
                                    <a class="btn btn-small" target="_blank" href="<?= e(asset($evaluation['certificate_file'])) ?>">View Certificate of Completion</a>
                                <?php endif; ?>
                            </div>
                            <a class="btn btn-primary" href="<?= e(route_url('partner.evaluate', ['enrollment' => (int)$selected['id']])) ?>">Edit Evaluation</a>
                        <?php else: ?>
                            <p class="muted">The student has completed the required hours. Start the final evaluation.</p>
                            <a class="btn btn-primary" href="<?= e(route_url('partner.evaluate', ['enrollment' => (int)$selected['id']])) ?>">Start Evaluation</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="muted">Final evaluation unlocks after the student completes the required OJT hours. Current progress: <?= e(number_format($renderedHours, 2)) ?> / <?= e(number_format($requiredHours, 2)) ?> hours.</p>
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
