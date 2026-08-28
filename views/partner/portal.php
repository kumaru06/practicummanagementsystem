<?php
$selectedId = (int)($selected['id'] ?? 0);
$renderedHours = array_reduce($dtrs ?? [], static function ($total, $dtr) {
    if (($dtr['verification_status'] ?? '') !== 'approved') {
        return $total;
    }
    return $total + (float)($dtr['hours'] ?? 0);
}, 0.0);
$requiredHours = (float)($selected['required_hours'] ?? 0);
$evaluationUnlocked = $selected
    && $requiredHours > 0
    && $renderedHours >= $requiredHours
    && in_array((string)($selected['status'] ?? ''), ['active', 'completed'], true)
    && (
        ($selected['predeployment_status'] ?? '') === 'orientation_completed'
        || ($selected['status'] ?? '') === 'completed'
    );

$statusMeta = static function (?string $status): array {
    $status = strtolower(trim((string)$status));
    return match ($status) {
        'forwarded' => ['label' => 'Docs Forwarded', 'chip' => 'pp-chip--forwarded', 'action' => true, 'hint' => 'Review & accept deployment'],
        'accepted' => ['label' => 'Accepted', 'chip' => 'pp-chip--accepted', 'action' => true, 'hint' => 'Schedule orientation'],
        'orientation_scheduled' => ['label' => 'Orientation Set', 'chip' => 'pp-chip--scheduled', 'action' => true, 'hint' => 'Complete orientation'],
        'orientation_completed' => ['label' => 'OJT Active', 'chip' => 'pp-chip--active', 'action' => false, 'hint' => 'Tracking hours'],
        'completed' => ['label' => 'Completed', 'chip' => 'pp-chip--done', 'action' => false, 'hint' => 'OJT finished'],
        default => ['label' => ucwords(str_replace('_', ' ', $status ?: 'pending')), 'chip' => 'pp-chip--default', 'action' => false, 'hint' => 'Awaiting update'],
    };
};

$needsAction = static function (array $student) use ($statusMeta): bool {
    return ($statusMeta($student['predeployment_status'] ?? '')['action'] ?? false);
};

$actionStudents = array_values(array_filter($students ?? [], $needsAction));
$forwardedStudents = array_values(array_filter($students ?? [], static fn ($s) => ($s['predeployment_status'] ?? '') === 'forwarded'));
$activeStudents = array_values(array_filter($students ?? [], static fn ($s) => partner_enrollment_is_active_ojt($s)));

$pipelineLabels = ['Documents', 'Accept', 'Orient', 'OJT', 'Done'];
$currentPipeline = $selected ? partner_enrollment_pipeline_step($selected, $evaluation ?? null) : -1;
?>
<div class="partner-portal-v2<?= $selected ? ' has-selection' : '' ?>">
    <div class="pp-layout">
        <aside class="pp-roster" aria-label="Student roster">
            <div class="pp-roster-head">
                <div>
                    <h2>Deployed Students</h2>
                    <p><?= count($students ?? []) ?> assigned to your organization</p>
                </div>
                <?php if (!empty($company['moa_mou_file']) && upload_file_exists((string)$company['moa_mou_file'])): ?>
                    <a class="pp-roster-moa" target="_blank" href="<?= e(asset($company['moa_mou_file'])) ?>">MOA/MOU</a>
                <?php elseif (!empty($company['moa_mou_file'])): ?>
                    <span class="pp-roster-moa pp-roster-moa--missing" title="MOA/MOU record exists but the file is missing on the server">MOA missing</span>
                <?php endif; ?>
            </div>

            <div class="pp-roster-filters" role="tablist" aria-label="Filter students">
                <button type="button" class="pp-filter is-active" data-pp-filter="all" role="tab" aria-selected="true">All <em><?= count($students ?? []) ?></em></button>
                <button type="button" class="pp-filter" data-pp-filter="action" role="tab" aria-selected="false">Needs Action <em><?= count($actionStudents) ?></em></button>
                <button type="button" class="pp-filter" data-pp-filter="forwarded" role="tab" aria-selected="false">Forwarded <em><?= count($forwardedStudents) ?></em></button>
                <button type="button" class="pp-filter" data-pp-filter="active" role="tab" aria-selected="false">Active OJT <em><?= count($activeStudents) ?></em></button>
            </div>

            <label class="pp-roster-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" class="pp-search-input" placeholder="Search name, ID, or email..." aria-label="Search students">
            </label>

            <div class="pp-roster-list">
                <?php if (empty($students)): ?>
                    <div class="pp-roster-empty">
                        <p>No students assigned yet</p>
                        <small>Students appear here once the coordinator forwards approved documents.</small>
                    </div>
                <?php endif; ?>
                <?php foreach ($students as $s): ?>
                    <?php
                    $meta = $statusMeta($s['predeployment_status'] ?? '');
                    $isSelected = (int)$s['id'] === $selectedId;
                    $filterGroup = 'all';
                    if ($needsAction($s)) {
                        $filterGroup .= ' action';
                    }
                    if (($s['predeployment_status'] ?? '') === 'forwarded') {
                        $filterGroup .= ' forwarded';
                    }
                    if (partner_enrollment_is_active_ojt($s)) {
                        $filterGroup .= ' active';
                    }
                    $searchBlob = strtolower(implode(' ', [
                        $s['student_name'] ?? '',
                        $s['student_email'] ?? '',
                        $s['student_no'] ?? '',
                        $s['course'] ?? '',
                        $meta['label'],
                    ]));
                    $initial = strtoupper(substr($s['student_name'] ?? 'S', 0, 1));
                    ?>
                    <a
                        class="pp-roster-card<?= $isSelected ? ' is-active' : '' ?><?= $meta['action'] ? ' needs-action' : '' ?>"
                        href="<?= e(route_url('partner.portal', ['enrollment' => (int)$s['id']])) ?>#student-workspace"
                        data-pp-groups="<?= e(trim($filterGroup)) ?>"
                        data-pp-search="<?= e($searchBlob) ?>"
                    >
                        <?php if ($meta['action']): ?><span class="pp-roster-pulse" aria-label="Action required"></span><?php endif; ?>
                        <span class="pp-roster-avatar" aria-hidden="true"><?= e($initial) ?></span>
                        <span class="pp-roster-body">
                            <strong><?= e($s['student_name']) ?></strong>
                            <small><?= e(trim(($s['course'] ?? '') . '  ·  ' . ($s['student_no'] ?? ''))) ?></small>
                            <span class="pp-chip <?= e($meta['chip']) ?>"><?= e($meta['label']) ?></span>
                        </span>
                        <svg class="pp-roster-chevron" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <main class="pp-workspace" id="student-workspace">
            <?php if ($selected): ?>
                <?php $selMeta = $statusMeta($selected['predeployment_status'] ?? ''); ?>
                <?php $selInitial = strtoupper(substr($selected['student_name'] ?? 'S', 0, 1)); ?>

                <header class="pp-student-banner">
                    <a class="pp-back-link" href="<?= e(route_url('partner.portal')) ?>">
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m12.5 15-5-5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        All Students
                    </a>
                    <div class="pp-student-top">
                        <span class="pp-student-avatar" aria-hidden="true"><?= e($selInitial) ?></span>
                        <div class="pp-student-meta">
                            <div class="pp-student-title-row">
                                <h2><?= e($selected['student_name']) ?></h2>
                                <span class="pp-chip <?= e($selMeta['chip']) ?> pp-chip--lg"><?= e($selMeta['label']) ?></span>
                            </div>
                            <p><?= e($selected['course'] . ' ' . $selected['year_level']) ?> · <?= e($selected['student_no']) ?> · <?= e($selected['student_email']) ?></p>
                            <?php $partnerStudentAddress = student_display_address($selected); ?>
                            <?php if (!empty($selected['contact_number']) || $partnerStudentAddress !== ''): ?>
                                <p class="pp-student-contact">
                                    <?php if (!empty($selected['contact_number'])): ?><span><?= e($selected['contact_number']) ?></span><?php endif; ?>
                                    <?php if ($partnerStudentAddress !== ''): ?><span><?= e($partnerStudentAddress) ?></span><?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($selMeta['action']): ?>
                        <div class="pp-action-banner">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                            <span><strong>Action required:</strong> <?= e($selMeta['hint']) ?></span>
                        </div>
                    <?php endif; ?>
                    <nav class="pp-pipeline" aria-label="Student progress">
                        <?php foreach ($pipelineLabels as $i => $label): ?>
                            <?php $state = $i < $currentPipeline ? 'is-done' : ($i === $currentPipeline ? 'is-current' : ''); ?>
                            <div class="pp-pipeline-step <?= e($state) ?>">
                                <span class="pp-pipeline-dot">
                                    <?php if ($i < $currentPipeline): ?>
                                        <svg class="pp-pipeline-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    <?php else: ?>
                                        <?= $i + 1 ?>
                                    <?php endif; ?>
                                </span>
                                <span class="pp-pipeline-label"><?= e($label) ?></span>
                            </div>
                            <?php if ($i < count($pipelineLabels) - 1): ?>
                                <div class="pp-pipeline-line<?= $i < $currentPipeline ? ' is-done' : '' ?>" aria-hidden="true"></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </nav>
                </header>

                <div class="pp-workspace-sections">
                    <section class="pp-panel pp-panel--docs">
                        <div class="pp-panel-head">
                            <div class="pp-panel-icon pp-panel-icon--docs" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                            </div>
                            <div>
                                <h3>Forwarded Documents</h3>
                                <p>Endorsement letter and approved pre-deployment files from the coordinator.</p>
                            </div>
                        </div>

                        <div class="pp-endorsement-card">
                            <span class="pp-endorsement-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/></svg>
                            </span>
                            <div>
                                <strong>Endorsement Letter</strong>
                                <small>Official letter from the OJT Coordinator</small>
                            </div>
                            <a class="pp-doc-btn" target="_blank" href="<?= e(route_url('partner.view_endorsement', ['enrollment' => (int)$selected['id']])) ?>">View Letter</a>
                        </div>

                        <?php
                        $approvedRequirementCount = 0;
                        foreach ($requirements as $reqCheck) {
                            if (($reqCheck['status'] ?? '') === 'approved') {
                                $approvedRequirementCount++;
                            }
                        }
                        $allRequirementsApproved = !empty($requirements) && $approvedRequirementCount === count($requirements);
                        ?>
                        <div class="pp-docs-block">
                            <div class="pp-docs-block-head">
                                <span><?= count($requirements) ?> Pre-Deployment Requirements</span>
                                <span class="pp-chip <?= $allRequirementsApproved ? 'pp-chip--approved' : 'pp-chip--default' ?>">
                                    <?= $allRequirementsApproved ? 'All Approved' : ($approvedRequirementCount . ' / ' . count($requirements) . ' Approved') ?>
                                </span>
                            </div>
                            <?php foreach ($requirements as $req): ?>
                                <?php $reqStatus = (string)($req['status'] ?? 'pending'); ?>
                                <div class="pp-docs-row">
                                    <span class="pp-docs-icon"><?= requirement_card_icon((string)$req['requirement_key']) ?></span>
                                    <span class="pp-docs-label"><?= e($req['requirement_name']) ?></span>
                                    <?php if ($reqStatus === 'approved'): ?>
                                        <span class="pp-docs-approved" aria-label="Approved">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                        </span>
                                    <?php else: ?>
                                        <span class="pp-chip pp-chip--default"><?= e(ucfirst(str_replace('_', ' ', $reqStatus))) ?></span>
                                    <?php endif; ?>
                                    <?= !empty($req['file_path']) && $reqStatus === 'approved'
                                        ? (requirement_is_form_path((string)$req['file_path'])
                                            ? '<a class="pp-doc-btn pp-doc-btn--ghost" href="' . e(route_url('partner.view_requirement_form', ['student_id' => (int)$selected['student_id'], 'key' => (string)$req['requirement_key']])) . '">View</a>'
                                            : '<a class="pp-doc-btn pp-doc-btn--ghost" target="_blank" href="' . e(asset($req['file_path'])) . '">View</a>')
                                        : '<span class="pp-muted">—</span>' ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($selected['predeployment_status'] === 'forwarded'): ?>
                            <form method="post" class="pp-accept-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="partner_accept_deployment">
                                <input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
                                <button class="btn btn-primary pp-accept-btn" type="submit">Accept Deployment</button>
                            </form>
                        <?php endif; ?>
                    </section>

                    <div class="pp-workspace-duo">
                        <section class="pp-panel pp-panel--orient orientation-card">
                            <div class="pp-panel-head">
                                <div class="pp-panel-icon pp-panel-icon--orient" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
                                </div>
                                <div>
                                    <h3>Orientation &amp; OJT Start</h3>
                                    <p>Set orientation schedule and notes for the student.</p>
                                </div>
                            </div>

                            <?php if ($selected['predeployment_status'] === 'accepted'): ?>
                                <form method="post" class="form js-validate ip-form-panel orientation-action-card orientation-action-card-primary">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="partner_schedule_orientation">
                                    <input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
                                    <?php
                                    $allowPastOrientation = temporary_orientation_past_dates_allowed();
                                    $orientationMinAttr = $allowPastOrientation ? '' : date('Y-m-d');
                                    ?>
                                    <label class="no-floating-label required-label orientation-field"><span class="field-title">Orientation Date/Time <span class="req">*</span></span>
                                        <span class="filter-date-picker form-date-picker form-datetime-picker is-placeholder" data-datetime-picker="1" data-date-required="1"<?= $allowPastOrientation ? ' data-allow-past-dates="1"' : '' ?><?= $orientationMinAttr !== '' ? ' data-date-min="' . e($orientationMinAttr) . '"' : '' ?>>
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
                            <?php elseif ($selected['predeployment_status'] === 'orientation_scheduled'): ?>
                                <div class="pp-orient-locked">
                                    <div class="pp-orient-badge">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                        Orientation Scheduled
                                    </div>
                                    <div class="pp-orient-field">
                                        <span class="field-title">Orientation Date/Time</span>
                                        <div class="pp-orient-value"><?php
                                            if (!empty($selected['orientation_datetime'])) {
                                                $dt = new DateTime($selected['orientation_datetime']);
                                                echo e($dt->format('F j, Y \a\t g:i A'));
                                            } else {
                                                echo ' - ';
                                            }
                                        ?></div>
                                    </div>
                                    <div class="pp-orient-field">
                                        <span class="field-title">Notes</span>
                                        <div class="pp-orient-value"><?= e($selected['orientation_notes'] ?? ' - ') ?></div>
                                    </div>
                                    <details class="pp-orient-reschedule">
                                        <summary class="btn btn-small">Reschedule Orientation</summary>
                                        <form method="post" class="form js-validate ip-form-panel orientation-action-card" style="margin-top: 0.85rem;">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="partner_schedule_orientation">
                                            <input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
                                            <?php
                                            $scheduledValue = !empty($selected['orientation_datetime'])
                                                ? (new DateTime($selected['orientation_datetime']))->format('Y-m-d\TH:i')
                                                : '';
                                            $scheduledLabel = !empty($selected['orientation_datetime'])
                                                ? (new DateTime($selected['orientation_datetime']))->format('M j, Y g:i A')
                                                : 'Select date & time';
                                            $allowPastOrientation = temporary_orientation_past_dates_allowed();
                                            $orientationMinAttr = $allowPastOrientation ? '' : date('Y-m-d');
                                            ?>
                                            <label class="no-floating-label required-label orientation-field"><span class="field-title">New Orientation Date/Time <span class="req">*</span></span>
                                                <span class="filter-date-picker form-date-picker form-datetime-picker<?= $scheduledValue !== '' ? '' : ' is-placeholder' ?>" data-datetime-picker="1" data-date-required="1"<?= $allowPastOrientation ? ' data-allow-past-dates="1"' : '' ?><?= $orientationMinAttr !== '' ? ' data-date-min="' . e($orientationMinAttr) . '"' : '' ?>>
                                                    <input type="hidden" name="orientation_datetime" value="<?= e($scheduledValue) ?>">
                                                    <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select orientation date and time">
                                                        <span class="filter-date-value"><?= e($scheduledLabel) ?></span>
                                                        <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                                                    </button>
                                                </span>
                                            </label>
                                            <label class="no-floating-label required-label orientation-field"><span class="field-title">Notes <span class="req">*</span></span><textarea required name="orientation_notes" maxlength="500" placeholder="Add meeting link, venue, attire, documents to bring, and contact person."><?= e($selected['orientation_notes'] ?? '') ?></textarea></label>
                                            <button class="btn btn-primary" type="submit">Save Reschedule</button>
                                        </form>
                                    </details>
                                </div>
                            <?php elseif ($selected['predeployment_status'] === 'orientation_completed'): ?>
                                <div class="ojt-completed-block">
                                    <div class="ojt-completed-header">
                                        <div class="ojt-completed-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></div>
                                        <div>
                                            <strong>OJT is Active</strong>
                                            <small>Orientation completed - hours are now being tracked</small>
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
                            <?php else: ?>
                                <div class="pp-orient-waiting">
                                    <p>Orientation unlocks after you accept the forwarded deployment documents.</p>
                                </div>
                            <?php endif; ?>
                        </section>

                        <?php if ($selected['predeployment_status'] === 'orientation_scheduled'): ?>
                            <section class="pp-panel pp-panel--start orientation-card ojt-start-card">
                                <div class="pp-panel-head">
                                    <div class="pp-panel-icon pp-panel-icon--start" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                                    </div>
                                    <div>
                                        <h3>Complete Orientation &amp; Begin OJT</h3>
                                        <p>Set the official OJT start date to begin tracking hours.</p>
                                    </div>
                                </div>
                                <form method="post" class="form js-validate" data-orientation-complete-form data-required-hours="<?= (int)($selected['required_hours'] ?? 0) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="partner_complete_orientation">
                                    <input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
                                    <?php
                                    $orientationDate = !empty($selected['orientation_datetime']) ? (new DateTime($selected['orientation_datetime']))->format('Y-m-d') : date('Y-m-d');
                                    $officialStartMinAttr = temporary_official_start_past_dates_allowed() ? '' : $orientationDate;
                                    ?>
                                    <label class="no-floating-label required-label orientation-field"><span class="field-title">Official OJT Start Date <span class="req">*</span></span>
                                        <span class="filter-date-picker form-date-picker is-placeholder" data-ojt-start-picker data-date-required="1"<?= $officialStartMinAttr !== '' ? ' data-date-min="' . e($officialStartMinAttr) . '"' : '' ?>>
                                            <input type="hidden" name="official_start_date" value="">
                                            <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select OJT start date"><span class="filter-date-value">mm/dd/yyyy</span><span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span></button>
                                        </span>
                                    </label>
                                    <label class="no-floating-label orientation-field"><span class="field-title">Projected End Date</span>
                                        <span class="filter-date-picker form-date-picker is-placeholder" data-ojt-end-picker>
                                            <input type="hidden" name="projected_end_date" value="">
                                            <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select projected end date"><span class="filter-date-value">mm/dd/yyyy</span><span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span></button>
                                        </span>
                                    </label>
                                    <button class="btn btn-primary" type="submit">Mark Orientation Completed</button>
                                </form>
                            </section>
                        <?php endif; ?>
                    </div>

                    <div class="pp-workspace-bottom">
                    <?php
                    $approvedDtrCount = 0;
                    $approvedWeeklyCount = 0;
                    foreach ($dtrs ?? [] as $dtrRow) {
                        if (($dtrRow['verification_status'] ?? '') === 'approved') {
                            $approvedDtrCount++;
                        }
                    }
                    foreach ($weeklies ?? [] as $weeklyRow) {
                        if (($weeklyRow['verification_status'] ?? '') === 'approved') {
                            $approvedWeeklyCount++;
                        }
                    }
                    $pendingSubmissionTotal = (int)($pendingDtrCount ?? 0) + (int)($pendingWeeklyCount ?? 0);
                    ?>
                    <section class="pp-panel pp-panel--records">
                        <div class="pp-panel-head pp-panel-head--split">
                            <div>
                                <h3>Student Submissions</h3>
                                <p class="muted">Daily time records and weekly reports for <?= e($selected['student_name']) ?>.</p>
                            </div>
                            <?php if (!empty($reportsUnlocked)): ?>
                                <a class="pp-link-btn" href="<?= e(route_url('partner.submissions', ['student_id' => (int)$selected['student_id']])) ?>">
                                    <?= $pendingSubmissionTotal > 0 ? 'Review now (' . $pendingSubmissionTotal . ' pending)' : 'Open submissions' ?>
                                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($reportsUnlocked)): ?>
                            <div class="pp-submissions-locked">
                                <div class="pp-submissions-locked-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                </div>
                                <p><?= e(enrollment_report_lock_message($selected)) ?></p>
                            </div>
                        <?php else: ?>
                            <div class="pp-submissions-summary">
                                <a class="pp-submission-stat" href="<?= e(route_url('partner.submissions', ['student_id' => (int)$selected['student_id'], 'tab' => 'dtr'])) ?>">
                                    <span class="pp-submission-stat-label">Daily Time Records</span>
                                    <span class="pp-submission-stat-values">
                                        <?php if ((int)($pendingDtrCount ?? 0) > 0): ?><em class="pp-submission-stat-pending"><?= (int)$pendingDtrCount ?> pending</em><?php endif; ?>
                                        <span><?= $approvedDtrCount ?> approved · <?= count($dtrs ?? []) ?> total</span>
                                    </span>
                                </a>
                                <a class="pp-submission-stat" href="<?= e(route_url('partner.submissions', ['student_id' => (int)$selected['student_id'], 'tab' => 'weekly'])) ?>">
                                    <span class="pp-submission-stat-label">Weekly Reports</span>
                                    <span class="pp-submission-stat-values">
                                        <?php if ((int)($pendingWeeklyCount ?? 0) > 0): ?><em class="pp-submission-stat-pending"><?= (int)$pendingWeeklyCount ?> pending</em><?php endif; ?>
                                        <span><?= $approvedWeeklyCount ?> approved · <?= count($weeklies ?? []) ?> total</span>
                                    </span>
                                </a>
                            </div>
                            <div class="table-wrap">
                                <table class="data-table compact-table" data-no-enhance="1">
                                    <thead><tr><th>Date</th><th>Schedule</th><th>Hours</th><th>Status</th><th>Tasks</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($dtrs)): ?>
                                            <tr><td colspan="5" class="pp-muted">No time records yet.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach (array_slice($dtrs ?? [], 0, 5) as $d): ?>
                                            <?php
                                            $dtrStatus = strtolower(trim((string)($d['verification_status'] ?? 'pending')));
                                            if (!in_array($dtrStatus, ['pending', 'approved', 'rejected'], true)) {
                                                $dtrStatus = 'pending';
                                            }
                                            $dtrChip = match ($dtrStatus) {
                                                'approved' => 'pp-chip--approved',
                                                'rejected' => 'pp-chip--default',
                                                default => 'pp-chip--forwarded',
                                            };
                                            ?>
                                            <tr>
                                                <td><?= e($d['work_date']) ?></td>
                                                <td><?= e(format_dtr_schedule($d)) ?></td>
                                                <td><?= e($d['hours']) ?></td>
                                                <td><span class="pp-chip <?= e($dtrChip) ?>"><?= e(ucfirst($dtrStatus)) ?></span></td>
                                                <td><?= e($d['tasks_done']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($dtrs ?? []) > 5): ?>
                                <p class="pp-submissions-more muted">Showing latest 5 records. <a href="<?= e(route_url('partner.submissions', ['student_id' => (int)$selected['student_id']])) ?>">View all in Submissions</a></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </section>

                    <?php if (!empty($studentEvalSubmitted)): ?>
                        <section class="pp-panel pp-panel--feedback">
                            <div class="pp-panel-head pp-panel-head--split">
                                <div>
                                    <h3>Student Feedback</h3>
                                    <p class="muted">This student submitted an evaluation of your organization.</p>
                                </div>
                                <a class="pp-link-btn" href="<?= e(route_url('partner.student_evaluation', ['student_id' => (int)$selected['student_id']])) ?>">
                                    View evaluation
                                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                            <div class="pp-feedback-summary">
                                <strong><?= e(number_format((float)($studentEvaluation['partner_grade'] ?? 0), 2)) ?>%</strong>
                                <span>Overall rating from student</span>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section class="pp-panel pp-panel--eval">
                        <div class="pp-panel-head">
                            <h3>Final Evaluation</h3>
                        </div>
                        <?php if ($evaluationUnlocked): ?>
                            <?php if (!empty($evaluation)): ?>
                                <div class="eval-summary">
                                    <div class="eval-summary-grade">
                                        <span class="eval-summary-grade-value"><?= e(number_format((float)($evaluation['final_grade'] ?? 0), 2)) ?>%</span>
                                        <span class="eval-summary-grade-label">Final Grade</span>
                                    </div>
                                    <p class="muted">Evaluation submitted<?= !empty($evaluation['submitted_at']) ? ' on ' . e(date('M j, Y', strtotime($evaluation['submitted_at']))) : '' ?>.</p>
                                    <?php if (!empty($evaluation['certificate_file'])): ?>
                                        <a class="btn btn-small" target="_blank" href="<?= e(asset($evaluation['certificate_file'])) ?>">View Certificate</a>
                                    <?php endif; ?>
                                </div>
                                <a class="btn btn-primary" href="<?= e(route_url('partner.evaluate', ['enrollment' => (int)$selected['id']])) ?>">Edit Evaluation</a>
                            <?php else: ?>
                                <p class="muted">The student has completed the required approved hours. Start the final evaluation.</p>
                                <a class="btn btn-primary" href="<?= e(route_url('partner.evaluate', ['enrollment' => (int)$selected['id']])) ?>">Start Evaluation</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="pp-eval-locked">
                                <div class="pp-eval-progress">
                                    <span style="width: <?= $requiredHours > 0 ? min(100, round(($renderedHours / $requiredHours) * 100)) : 0 ?>%"></span>
                                </div>
                                <p class="muted"><?= e(number_format($renderedHours, 2)) ?> / <?= e(number_format($requiredHours, 2)) ?> approved hours</p>
                                <button class="btn btn-primary" type="button" disabled>Evaluation Locked</button>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
                </div>

            <?php else: ?>
                <div class="pp-workspace-empty">
                    <div class="pp-workspace-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h2>Select a student</h2>
                    <p>Choose a student from the list to review documents, manage orientation, and track OJT progress.</p>

                    <?php if (!empty($actionStudents)): ?>
                        <div class="pp-priority-list">
                            <h3>Needs your attention</h3>
                            <?php foreach ($actionStudents as $s): ?>
                                <?php $meta = $statusMeta($s['predeployment_status'] ?? ''); ?>
                                <a class="pp-priority-card" href="<?= e(route_url('partner.portal', ['enrollment' => (int)$s['id']])) ?>#student-workspace">
                                    <span class="pp-roster-avatar" aria-hidden="true"><?= e(strtoupper(substr($s['student_name'] ?? 'S', 0, 1))) ?></span>
                                    <span>
                                        <strong><?= e($s['student_name']) ?></strong>
                                        <small><?= e($meta['hint']) ?></small>
                                    </span>
                                    <span class="pp-chip <?= e($meta['chip']) ?>"><?= e($meta['label']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
