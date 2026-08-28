<?php
$required = (float)($enrollment['required_hours'] ?? 0);
$rendered = (float)($hours ?? 0);
$approvedHours = (float)($approvedHours ?? 0);
$remaining = max(0, $required - $rendered);
$hoursComplete = $remaining <= 0 && $required > 0;
$percent = $required > 0 ? min(100, round(($rendered / $required) * 100, 1)) : 0;
$progressRingRadius = 54;
$progressRingCircumference = 2 * M_PI * $progressRingRadius;
$ringOffset = $progressRingCircumference * (1 - ($percent / 100));
$predeployment = $enrollment['predeployment_status'] ?? 'not_submitted';
$uploadedRequirements = count(array_filter($requirements ?? [], static fn ($req) => !empty($req['file_path'])));
$approvedRequirements = count(array_filter($requirements ?? [], static fn ($req) => ($req['status'] ?? '') === 'approved'));
$totalRequirements = max(6, count($requirements ?? []));
$officialStart = $enrollment['official_start_date'] ?? $enrollment['start_date'] ?? null;
$projectedEnd = $enrollment['projected_end_date'] ?? $enrollment['end_date'] ?? null;
$todayDtr = $todayDtr ?? null;
if ($todayDtr === null) {
    foreach (($dtrs ?? []) as $dtr) {
        if (($dtr['work_date'] ?? '') === date('Y-m-d')) {
            $todayDtr = $dtr;
            break;
        }
    }
}
$latestWeekly = ($weeklyReports ?? [])[0] ?? null;
$profileComplete = !empty($student['profile_completed']);
$deploymentComplete = $predeployment === 'orientation_completed';
$dtrTotalCount = $dtrTotalCount ?? count($dtrs ?? []);
$weeklyTotalCount = $weeklyTotalCount ?? count($weeklyReports ?? []);
$reportCount = $dtrTotalCount + $weeklyTotalCount;
$ojtCompletion = $ojtCompletion ?? student_ojt_completion_status([
    'enrollment' => $enrollment,
    'approvedHours' => $approvedHours,
    'finalRequirement' => $finalRequirement ?? [],
    'studentEvaluation' => $studentEvaluation ?? [],
    'hteEvaluation' => $hteEvaluation ?? null,
]);
$actionAlerts = $actionAlerts ?? [];
$canAccessFinalRequirements = (bool)($canAccessFinalRequirements ?? false);
$studentId = (int)($student['id'] ?? 0);
$stage3DocProgress = student_stage3_upload_progress($studentId);
$finalDocsDone = $stage3DocProgress['done'];
$selfEvalDone = true;
foreach (array_keys(FinalRequirement::EVALUATION_SECTIONS) as $evalKey) {
    if (StudentEvaluation::statusFor($studentEvaluation ?? [], $evalKey) !== 'submitted') {
        $selfEvalDone = false;
        break;
    }
}
$finalRequirementsDone = $finalDocsDone && $selfEvalDone;
$ojtCleared = ($ojtCompletion['status'] ?? '') === 'cleared';
$rejectedDtrCount = $dtrRejectedCount ?? count(array_filter($dtrs ?? [], static fn ($row) => strtolower((string)($row['verification_status'] ?? '')) === 'rejected'));
$rejectedWeeklyCount = count(array_filter($weeklyReports ?? [], static fn ($row) => strtolower((string)($row['verification_status'] ?? '')) === 'rejected'));
$rejectedRequirementCount = count(array_filter($requirements ?? [], static fn ($req) => ($req['status'] ?? '') === 'rejected'));
$orientationDateTime = $enrollment['orientation_datetime'] ?? null;
$orientationFormatted = $orientationDateTime && strtotime((string)$orientationDateTime) !== false
    ? date('M d, Y g:i A', strtotime((string)$orientationDateTime))
    : null;
$certificateFile = $hteEvaluation['certificate_file'] ?? null;
$nextAction = match (true) {
    !$enrollment => ['title' => 'Awaiting Enrollment', 'message' => 'Your coordinator has not enrolled you in OJT yet.', 'route' => route_url('student.documents', ['stage' => 1]), 'label' => 'View documents', 'icon' => 'enrollment'],
    $rejectedRequirementCount > 0 || $predeployment === 'needs_revision' => ['title' => 'Fix Rejected Documents', 'message' => 'One or more pre-deployment documents were rejected. Upload corrected files to continue.', 'route' => route_url('student.documents', ['stage' => 1]), 'label' => 'Fix documents', 'icon' => 'requirements'],
    $rejectedDtrCount > 0 => ['title' => 'Correct Rejected DTR', 'message' => 'Your Host Training Establishment rejected ' . $rejectedDtrCount . ' daily time record(s). Update and resubmit them.', 'route' => route_url('student.records'), 'label' => 'Fix DTR', 'icon' => 'records'],
    $rejectedWeeklyCount > 0 => ['title' => 'Correct Rejected Weekly Report', 'message' => 'Your Host Training Establishment rejected ' . $rejectedWeeklyCount . ' weekly report(s). Update and resubmit them.', 'route' => route_url('student.records'), 'label' => 'Fix weekly report', 'icon' => 'records'],
    in_array($predeployment, ['not_submitted', 'needs_revision'], true) => ['title' => 'Prepare Requirements', 'message' => 'Upload all required pre-deployment documents and submit them for review.', 'route' => route_url('student.documents', ['stage' => 1]), 'label' => 'Go to Documents', 'icon' => 'requirements'],
    $predeployment === 'submitted' => ['title' => 'Under Review', 'message' => 'Your coordinator is reviewing your submitted requirements.', 'route' => route_url('student.documents', ['stage' => 1]), 'label' => 'Check Status', 'icon' => 'review'],
    in_array($predeployment, ['approved', 'forwarded', 'accepted', 'orientation_scheduled'], true) => ['title' => 'Deployment Processing', 'message' => 'Wait for company acceptance and orientation completion before submitting reports.', 'route' => route_url('student.documents', ['stage' => 2]), 'label' => 'View Deployment', 'icon' => 'deployment'],
    $ojtCleared => ['title' => 'OJT Cleared', 'message' => 'Congratulations. Your OJT requirements and evaluations are complete.', 'route' => route_url('student.evaluation'), 'label' => 'View evaluation', 'icon' => 'review'],
    $canAccessFinalRequirements && !$finalRequirementsDone => ['title' => 'Complete 3rd to Comply', 'message' => 'Your practicum hours are complete or your end date has arrived. Finish your remaining documents and self-evaluations.', 'route' => route_url('student.documents', ['stage' => 3]), 'label' => 'Open 3rd to Comply', 'icon' => 'requirements'],
    ($canSubmitReports ?? false) => ['title' => 'Submit OJT Records', 'message' => 'Your OJT records are unlocked. Submit DTR and weekly reports on time.', 'route' => route_url('student.records'), 'label' => 'Submit Record', 'icon' => 'records'],
    default => ['title' => 'Reports Locked', 'message' => $reportLockMessage ?? 'Reports are not available yet.', 'route' => route_url('student.records'), 'label' => 'View Records', 'icon' => 'locked'],
};
$showUrgentAction = $rejectedRequirementCount > 0
    || $rejectedDtrCount > 0
    || $rejectedWeeklyCount > 0
    || $predeployment === 'needs_revision';
$formatJourneyDate = static function (?string $date): ?string {
    if (!$date || strtotime($date) === false) {
        return null;
    }
    return date('M j, Y', strtotime($date));
};
$ojtStartLabel = $formatJourneyDate($officialStart ? (string)$officialStart : null);
$ojtEndLabel = $formatJourneyDate($projectedEnd ? (string)$projectedEnd : null);
if ($ojtStartLabel && $ojtEndLabel) {
    // Compact: "Aug 23 – Dec 15, 2026" (year once if same year)
    $startTs = strtotime((string)$officialStart);
    $endTs = strtotime((string)$projectedEnd);
    if (date('Y', $startTs) === date('Y', $endTs)) {
        $ojtStatusDetail = date('M j', $startTs) . ' – ' . date('M j, Y', $endTs);
    } else {
        $ojtStatusDetail = $ojtStartLabel . ' – ' . $ojtEndLabel;
    }
} elseif ($ojtStartLabel) {
    $ojtStatusDetail = 'Started ' . $ojtStartLabel;
} else {
    $ojtStatusDetail = 'In progress';
}

$journeySteps = [
    [
        'label' => 'Profile',
        'done' => $profileComplete,
        'status' => $profileComplete ? 'Completed' : 'Pending',
    ],
    [
        'label' => 'Documents',
        'done' => in_array($predeployment, ['approved', 'forwarded', 'accepted', 'orientation_scheduled', 'orientation_completed'], true),
        'status' => in_array($predeployment, ['approved', 'forwarded', 'accepted', 'orientation_scheduled', 'orientation_completed'], true)
            ? 'Completed'
            : ($uploadedRequirements > 0 ? $uploadedRequirements . '/' . $totalRequirements . ' uploaded' : 'Pending'),
    ],
    [
        'label' => 'Deployment',
        'done' => in_array($predeployment, ['accepted', 'orientation_scheduled', 'orientation_completed'], true),
        'status' => in_array($predeployment, ['accepted', 'orientation_scheduled', 'orientation_completed'], true)
            ? 'Completed'
            : (in_array($predeployment, ['forwarded', 'approved'], true) ? 'In review' : 'Pending'),
    ],
    [
        'label' => 'Orientation',
        'done' => $deploymentComplete,
        'status' => $deploymentComplete
            ? 'Completed'
            : ($predeployment === 'orientation_scheduled' ? 'Scheduled' : 'Pending'),
    ],
    [
        'label' => 'OJT',
        'done' => $hoursComplete || $ojtCleared,
        'status' => ($hoursComplete || $ojtCleared)
            ? 'Completed'
            : (($canSubmitReports ?? false) ? $ojtStatusDetail : 'Pending'),
    ],
    [
        'label' => 'Finals',
        'done' => $finalRequirementsDone,
        'status' => $finalRequirementsDone
            ? 'Completed'
            : ($canAccessFinalRequirements ? 'In progress' : 'Pending'),
    ],
    [
        'label' => 'Cleared',
        'done' => $ojtCleared,
        'status' => $ojtCleared ? 'Completed' : 'Pending',
    ],
];
$currentJourneyIndex = count($journeySteps) - 1;
foreach ($journeySteps as $i => $step) {
    if (!$step['done']) {
        $currentJourneyIndex = $i;
        break;
    }
}
if (!empty($journeySteps[$currentJourneyIndex]) && !$journeySteps[$currentJourneyIndex]['done']) {
    $currentLabel = (string)$journeySteps[$currentJourneyIndex]['label'];
    if ($currentLabel === 'OJT' && ($canSubmitReports ?? false)) {
        $journeySteps[$currentJourneyIndex]['status'] = $ojtStatusDetail;
    } elseif ($journeySteps[$currentJourneyIndex]['status'] === 'Pending') {
        $journeySteps[$currentJourneyIndex]['status'] = 'Current';
    }
}
$studentName = (string)($student['name'] ?? current_user()['name'] ?? 'Student');
$firstName = trim(explode(' ', $studentName)[0] ?: $studentName);
$scheduleLabel = ($officialStart ? date('M j, Y', strtotime((string)$officialStart)) : '—')
    . ' → '
    . ($projectedEnd ? date('M j, Y', strtotime((string)$projectedEnd)) : '—');
$companyName = (string)($enrollment['company_name'] ?? 'Not enrolled');
?>

<div class="student-dashboard-page student-dash-v2 student-dash-v3">
    <section class="sd3-intro" aria-label="Welcome">
        <div class="sd3-intro-backdrop" aria-hidden="true"></div>
        <div class="sd3-intro-copy">
            <div class="sd3-intro-meta">
                <p class="sd3-intro-kicker"><?= e(date('l, M j, Y')) ?></p>
                <span class="sd3-intro-badge"><?= e($nextAction['title']) ?></span>
            </div>
            <h2 class="sd3-intro-title">Welcome back, <em><?= e($firstName) ?></em></h2>
            <p class="sd3-intro-sub"><?= e($nextAction['message']) ?></p>
        </div>
        <a class="btn btn-primary sd3-intro-cta" href="<?= e($nextAction['route']) ?>">
            <span><?= e($nextAction['label']) ?></span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 6l6 6-6 6-1.4-1.4 4.6-4.6-4.6-4.6L10 6z"/></svg>
        </a>
    </section>

    <nav class="sd3-journey" aria-label="OJT journey progress">
        <?php foreach ($journeySteps as $i => $step): ?>
            <?php
            $isCurrent = $i === $currentJourneyIndex;
            $stepClass = ($step['done'] ? ' is-done' : '') . ($isCurrent ? ' is-current' : '');
            ?>
            <div class="sd3-journey-step<?= $stepClass ?>">
                <span class="sd3-journey-dot" aria-hidden="true">
                    <?php if ($step['done']): ?>
                        <svg class="sd3-journey-check" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M9.2 16.6 4.8 12.3l1.5-1.5 2.9 2.9 7.4-7.5 1.5 1.5z"/>
                        </svg>
                    <?php else: ?>
                        <span><?= $i + 1 ?></span>
                    <?php endif; ?>
                </span>
                <span class="sd3-journey-copy">
                    <span class="sd3-journey-label"><?= e($step['label']) ?></span>
                    <span class="sd3-journey-status"><?= e($step['status']) ?></span>
                </span>
            </div>
            <?php if ($i < count($journeySteps) - 1): ?>
                <span class="sd3-journey-line<?= $step['done'] ? ' is-done' : ($isCurrent ? ' is-current' : '') ?>" aria-hidden="true"></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <?php if (!empty($actionAlerts)): ?>
    <section class="sd3-alerts" aria-label="Action required">
        <?php foreach ($actionAlerts as $alert): ?>
            <article class="sd3-alert sd3-alert--<?= e($alert['type']) ?>">
                <div class="sd3-alert-copy">
                    <strong><?= e($alert['title']) ?></strong>
                    <p><?= e($alert['message']) ?></p>
                </div>
                <a class="btn btn-small sd3-alert-btn" href="<?= e($alert['route']) ?>"><?= e($alert['label']) ?></a>
            </article>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <?php if ($showUrgentAction): ?>
    <section class="sd3-urgent" aria-label="Urgent action">
        <div class="sd3-urgent-copy">
            <span class="sd3-eyebrow">Needs attention</span>
            <h3><?= e($nextAction['title']) ?></h3>
            <p><?= e($nextAction['message']) ?></p>
        </div>
        <a class="btn btn-primary" href="<?= e($nextAction['route']) ?>"><?= e($nextAction['label']) ?></a>
    </section>
    <?php endif; ?>

    <div class="sd3-metrics" aria-label="Key metrics">
        <article class="sd3-metric">
            <div class="sd3-metric-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
            </div>
            <div>
                <span>Company</span>
                <strong><?= e($companyName) ?></strong>
            </div>
        </article>
        <article class="sd3-metric">
            <div class="sd3-metric-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2zm-7 5h5v5h-5v-5z"/></svg>
            </div>
            <div>
                <span>Official schedule</span>
                <strong class="sd3-metric-sm"><?= e($scheduleLabel) ?></strong>
            </div>
        </article>
        <article class="sd3-metric">
            <div class="sd3-metric-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            </div>
            <div>
                <span>Remaining hours</span>
                <strong class="<?= $hoursComplete ? 'is-positive' : '' ?>"><?= $hoursComplete ? 'Complete' : number_format($remaining, 2) ?></strong>
            </div>
        </article>
        <article class="sd3-metric">
            <div class="sd3-metric-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
            </div>
            <div>
                <span>Submitted records</span>
                <strong><?= (int)$reportCount ?></strong>
            </div>
        </article>
    </div>

    <div class="sd3-dual">
        <section class="sd3-card sd3-panel sd3-progress-card">
            <header class="sd3-panel-head">
                <div>
                    <span class="sd3-eyebrow">OJT Progress</span>
                    <h3><?= number_format($rendered, 2) ?> / <?= number_format($required, 2) ?> hours</h3>
                </div>
                <span class="badge <?= e($enrollment['status'] ?? 'pending') ?>"><?= e($enrollment['status'] ?? 'pending') ?></span>
            </header>
            <div class="sd3-panel-body">
                <div class="sd3-progress-layout">
                    <div class="sd3-ring-wrap sd3-ring-wrap--light" aria-hidden="true">
                        <svg class="sd3-ring" viewBox="0 0 120 120">
                            <circle class="sd3-ring-bg" cx="60" cy="60" r="<?= $progressRingRadius ?>"></circle>
                            <circle
                                class="sd3-ring-value"
                                cx="60"
                                cy="60"
                                r="<?= $progressRingRadius ?>"
                                style="stroke-dasharray: <?= round($progressRingCircumference, 2) ?>; stroke-dashoffset: <?= round($ringOffset, 2) ?>;"
                            ></circle>
                        </svg>
                        <div class="sd3-ring-label">
                            <strong><?= $percent ?>%</strong>
                            <span>complete</span>
                        </div>
                    </div>
                    <div class="sd3-progress-body">
                        <p class="sd3-card-copy">DTR and weekly submissions unlock after your Host Training Establishment completes orientation.</p>
                        <div class="sd3-bar" aria-hidden="true"><span style="width: <?= $percent ?>%"></span></div>
                        <p class="sd3-card-copy"><?= $hoursComplete ? 'Required hours complete.' : number_format($remaining, 1) . ' hours remaining.' ?></p>
                        <div class="sd3-snapshot">
                            <div class="sd3-snapshot-item"><span>Uploaded docs</span><strong><?= $uploadedRequirements ?>/<?= $totalRequirements ?></strong></div>
                            <div class="sd3-snapshot-item"><span>Approved docs</span><strong><?= $approvedRequirements ?>/<?= $totalRequirements ?></strong></div>
                            <div class="sd3-snapshot-item"><span>Weekly reports</span><strong><?= count($weeklyReports ?? []) ?></strong></div>
                        </div>
                        <a class="btn btn-small" href="<?= e(route_url('student.documents', ['stage' => 1])) ?>">Manage documents</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="sd3-card sd3-panel sd3-clearance-card sd3-clearance-card--<?= e($ojtCompletion['status']) ?>">
            <header class="sd3-panel-head">
                <div>
                    <span class="sd3-eyebrow">OJT Clearance</span>
                    <h3><?= e($ojtCompletion['label']) ?></h3>
                </div>
            </header>
            <div class="sd3-panel-body">
                <p class="sd3-card-copy"><?= e($ojtCompletion['message']) ?></p>
                <div class="sd3-bar sd3-bar--clearance" aria-hidden="true"><span style="width: <?= (int)$ojtCompletion['percent'] ?>%"></span></div>
                <ul class="sd3-checklist">
                    <?php foreach ($ojtCompletion['checklist'] as $item): ?>
                        <li class="<?= !empty($item['done']) ? 'is-done' : '' ?>">
                            <span class="sd3-check-dot" aria-hidden="true"></span>
                            <?= e($item['label']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (!empty($certificateFile)): ?>
                    <a class="sd3-link" target="_blank" href="<?= e(asset($certificateFile)) ?>">Download completion certificate</a>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="sd3-activity">
        <section class="sd3-card sd3-panel">
            <header class="sd3-panel-head">
                <div>
                    <span class="sd3-eyebrow">Today</span>
                    <h3>Daily time record</h3>
                </div>
                <span class="sd3-status <?= $todayDtr ? 'is-done' : 'is-pending' ?>"><?= $todayDtr ? 'Logged' : 'Pending' ?></span>
            </header>
            <div class="sd3-panel-body">
                <?php if ($todayDtr): ?>
                    <p class="sd3-highlight"><?= e(format_dtr_schedule($todayDtr)) ?></p>
                    <p class="sd3-card-copy"><strong><?= e($todayDtr['hours']) ?> hrs</strong> · <?= e($todayDtr['tasks_done']) ?></p>
                <?php else: ?>
                    <p class="sd3-empty"><?= ($canSubmitReports ?? false) ? 'No DTR submitted for today yet.' : e($reportLockMessage ?? 'DTR is locked.') ?></p>
                <?php endif; ?>
                <a class="sd3-link" href="<?= e(route_url('student.records')) ?>">Open records</a>
            </div>
        </section>

        <section class="sd3-card sd3-panel">
            <header class="sd3-panel-head">
                <div>
                    <span class="sd3-eyebrow">Reports</span>
                    <h3>Latest weekly report</h3>
                </div>
                <span class="sd3-status">PDF</span>
            </header>
            <div class="sd3-panel-body">
                <?php if ($latestWeekly): ?>
                    <p class="sd3-highlight">Week <?= (int)$latestWeekly['week_no'] ?></p>
                    <p class="sd3-card-copy"><?= e($latestWeekly['report_text'] ?: 'PDF report submitted.') ?></p>
                    <?php if (!empty($latestWeekly['file_path'])): ?>
                        <a class="sd3-link" target="_blank" href="<?= e(asset($latestWeekly['file_path'])) ?>">View PDF</a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="sd3-empty">No weekly report submitted yet.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="sd3-card sd3-panel sd3-shortcuts">
            <header class="sd3-panel-head">
                <div>
                    <span class="sd3-eyebrow">Shortcuts</span>
                    <h3>Quick actions</h3>
                </div>
            </header>
            <div class="sd3-panel-body">
                <div class="sd3-shortcut-list">
                    <a href="<?= e(route_url('student.records')) ?>"><span>Submit OJT Records</span><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 6l6 6-6 6-1.4-1.4 4.6-4.6-4.6-4.6L10 6z"/></svg></a>
                    <a href="<?= e(route_url('student.documents', ['stage' => 3])) ?>"><span>3rd to Comply</span><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 6l6 6-6 6-1.4-1.4 4.6-4.6-4.6-4.6L10 6z"/></svg></a>
                    <a href="<?= e(route_url('student.evaluation')) ?>"><span>My Evaluation</span><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 6l6 6-6 6-1.4-1.4 4.6-4.6-4.6-4.6L10 6z"/></svg></a>
                    <a href="<?= e(route_url('student.timeline')) ?>"><span>Activity Timeline</span><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 6l6 6-6 6-1.4-1.4 4.6-4.6-4.6-4.6L10 6z"/></svg></a>
                    <a href="<?= e(route_url('student.settings')) ?>"><span>Settings</span><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 6l6 6-6 6-1.4-1.4 4.6-4.6-4.6-4.6L10 6z"/></svg></a>
                </div>
            </div>
        </section>
    </div>

    <section class="sd3-card sd3-recent">
        <div class="sd3-card-head">
            <div>
                <span class="sd3-eyebrow">Activity</span>
                <h3>Recent DTR entries</h3>
            </div>
            <a class="sd3-link" href="<?= e(route_url('student.records')) ?>">View all</a>
        </div>
        <div class="sd3-recent-list<?= empty($dtrs ?? []) ? ' is-empty' : '' ?>">
            <?php if (empty($dtrs ?? [])): ?>
                <p class="sd3-empty">No daily time records submitted yet.</p>
            <?php else: ?>
                <?php foreach (array_slice($dtrs ?? [], 0, 4) as $d): ?>
                    <article class="sd3-recent-item">
                        <div>
                            <strong><?= e(format_timeline_date($d['work_date'] ?? '')) ?></strong>
                            <small><?= e(format_dtr_schedule($d)) ?></small>
                            <p><?= e($d['tasks_done']) ?></p>
                        </div>
                        <span class="sd3-hours"><?= e($d['hours']) ?> hrs</span>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="sd3-support">
        <section class="sd3-card sd3-panel">
            <header class="sd3-panel-head">
                <span class="sd3-eyebrow">Schedule</span>
                <h3>Orientation &amp; dates</h3>
            </header>
            <div class="sd3-panel-body sd3-card-scroll">
                <?php if ($orientationFormatted): ?>
                    <div class="sd3-contact-item">
                        <span>Orientation</span>
                        <strong><?= e($orientationFormatted) ?></strong>
                        <?php if (!empty($enrollment['orientation_notes'])): ?><p class="sd3-card-copy"><?= e($enrollment['orientation_notes']) ?></p><?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="sd3-empty">Orientation schedule will appear once your Host Training Establishment sets it.</p>
                <?php endif; ?>
                <?php if ($officialStart || $projectedEnd): ?>
                    <div class="sd3-contact-item">
                        <span>Official OJT period</span>
                        <strong><?= e($scheduleLabel) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="sd3-card sd3-panel">
            <header class="sd3-panel-head">
                <span class="sd3-eyebrow">Reminders</span>
                <h3>Deadlines</h3>
            </header>
            <div class="sd3-panel-body sd3-card-scroll">
                <?php if (empty($upcomingDeadlines ?? [])): ?>
                    <p class="sd3-empty">No active reminders yet.</p>
                <?php else: ?>
                    <div class="sd3-deadline-list">
                        <?php foreach ($upcomingDeadlines as $deadline): ?>
                            <div class="sd3-deadline-item">
                                <strong><?= e($deadline['label']) ?></strong>
                                <?php if (!empty($deadline['date'])): ?><small><?= e($deadline['date']) ?></small><?php endif; ?>
                                <p><?= e($deadline['note']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="sd3-card sd3-panel sd3-card--updates">
            <header class="sd3-panel-head">
                <span class="sd3-eyebrow">Updates</span>
                <h3>Recent notifications</h3>
            </header>
            <div class="sd3-panel-body sd3-card-scroll">
                <?php if (empty($recentNotifications ?? [])): ?>
                    <p class="sd3-empty">No recent updates yet.</p>
                <?php else: ?>
                    <div class="sd3-updates-list">
                        <?php foreach ($recentNotifications as $notice): ?>
                            <a class="sd3-update-item<?= empty($notice['is_read']) ? ' is-unread' : '' ?>" href="index.php?action=read_notification&amp;id=<?= (int)$notice['id'] ?>&amp;redirect=<?= urlencode((string)($notice['link'] ?: 'index.php?r=student')) ?>">
                                <strong><?= e($notice['title']) ?></strong>
                                <small><?= e(date('M d, Y g:i A', strtotime((string)$notice['created_at']))) ?></small>
                                <p><?= e($notice['message']) ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
