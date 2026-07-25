<?php
$required = (float)($enrollment['required_hours'] ?? 0);
$rendered = (float)($hours ?? 0);
$approvedHours = (float)($approvedHours ?? 0);
$remaining = max(0, $required - $rendered);
$hoursComplete = $remaining <= 0 && $required > 0;
$percent = $required > 0 ? min(100, round(($rendered / $required) * 100, 1)) : 0;
$progressRingRadius = 50;
$progressRingCircumference = 2 * M_PI * $progressRingRadius;
$ringOffset = $progressRingCircumference * (1 - ($percent / 100));
$predeployment = $enrollment['predeployment_status'] ?? 'not_submitted';
$uploadedRequirements = count(array_filter($requirements ?? [], static fn ($req) => !empty($req['file_path'])));
$approvedRequirements = count(array_filter($requirements ?? [], static fn ($req) => ($req['status'] ?? '') === 'approved'));
$totalRequirements = max(5, count($requirements ?? []));
$officialStart = $enrollment['official_start_date'] ?? $enrollment['start_date'] ?? null;
$projectedEnd = $enrollment['projected_end_date'] ?? $enrollment['end_date'] ?? null;
$todayDtr = null;
foreach (($dtrs ?? []) as $dtr) {
    if (($dtr['work_date'] ?? '') === date('Y-m-d')) {
        $todayDtr = $dtr;
        break;
    }
}
$latestWeekly = ($weeklyReports ?? [])[0] ?? null;
$profileComplete = !empty($student['profile_completed']);
$deploymentComplete = $predeployment === 'orientation_completed';
$reportCount = count($dtrs ?? []) + count($weeklyReports ?? []);
$ojtCompletion = $ojtCompletion ?? student_ojt_completion_status([
    'enrollment' => $enrollment,
    'approvedHours' => $approvedHours,
    'finalRequirement' => $finalRequirement ?? [],
    'studentEvaluation' => $studentEvaluation ?? [],
    'hteEvaluation' => $hteEvaluation ?? null,
]);
$actionAlerts = $actionAlerts ?? [];
$canAccessFinalRequirements = (bool)($canAccessFinalRequirements ?? false);
$finalDocsDone = true;
foreach (array_keys(FinalRequirement::SECTIONS) as $sectionKey) {
    if ((string)(($finalRequirement ?? [])[$sectionKey . '_status'] ?? 'pending') !== 'submitted') {
        $finalDocsDone = false;
        break;
    }
}
$selfEvalDone = true;
foreach (array_keys(FinalRequirement::EVALUATION_SECTIONS) as $evalKey) {
    if (StudentEvaluation::statusFor($studentEvaluation ?? [], $evalKey) !== 'submitted') {
        $selfEvalDone = false;
        break;
    }
}
$finalRequirementsDone = $finalDocsDone && $selfEvalDone;
$ojtCleared = ($ojtCompletion['status'] ?? '') === 'cleared';
$rejectedDtrCount = count(array_filter($dtrs ?? [], static fn ($row) => strtolower((string)($row['verification_status'] ?? '')) === 'rejected'));
$rejectedWeeklyCount = count(array_filter($weeklyReports ?? [], static fn ($row) => strtolower((string)($row['verification_status'] ?? '')) === 'rejected'));
$rejectedRequirementCount = count(array_filter($requirements ?? [], static fn ($req) => ($req['status'] ?? '') === 'rejected'));
$orientationDateTime = $enrollment['orientation_datetime'] ?? null;
$orientationFormatted = $orientationDateTime && strtotime((string)$orientationDateTime) !== false
    ? date('M d, Y g:i A', strtotime((string)$orientationDateTime))
    : null;
$certificateFile = $hteEvaluation['certificate_file'] ?? null;
$nextAction = match (true) {
    !$enrollment => ['title' => 'Awaiting Enrollment', 'message' => 'Your coordinator has not enrolled you in OJT yet.', 'route' => route_url('student.documents'), 'label' => 'View documents', 'icon' => 'enrollment'],
    $rejectedRequirementCount > 0 || $predeployment === 'needs_revision' => ['title' => 'Fix Rejected Documents', 'message' => 'One or more pre-deployment documents were rejected. Upload corrected files to continue.', 'route' => route_url('student.documents'), 'label' => 'Fix documents', 'icon' => 'requirements'],
    $rejectedDtrCount > 0 => ['title' => 'Correct Rejected DTR', 'message' => 'Your Host Training Establishment rejected ' . $rejectedDtrCount . ' daily time record(s). Update and resubmit them.', 'route' => route_url('student.records'), 'label' => 'Fix DTR', 'icon' => 'records'],
    $rejectedWeeklyCount > 0 => ['title' => 'Correct Rejected Weekly Report', 'message' => 'Your Host Training Establishment rejected ' . $rejectedWeeklyCount . ' weekly report(s). Update and resubmit them.', 'route' => route_url('student.records'), 'label' => 'Fix weekly report', 'icon' => 'records'],
    in_array($predeployment, ['not_submitted', 'needs_revision'], true) => ['title' => 'Prepare Requirements', 'message' => 'Upload all required pre-deployment documents and submit them for review.', 'route' => route_url('student.documents'), 'label' => 'Go to Documents', 'icon' => 'requirements'],
    $predeployment === 'submitted' => ['title' => 'Under Review', 'message' => 'Your coordinator is reviewing your submitted requirements.', 'route' => route_url('student.documents'), 'label' => 'Check Status', 'icon' => 'review'],
    in_array($predeployment, ['approved', 'forwarded', 'accepted', 'orientation_scheduled'], true) => ['title' => 'Deployment Processing', 'message' => 'Wait for company acceptance and orientation completion before submitting reports.', 'route' => route_url('student.documents'), 'label' => 'View Deployment', 'icon' => 'deployment'],
    $ojtCleared => ['title' => 'OJT Cleared', 'message' => 'Congratulations. Your OJT requirements and evaluations are complete.', 'route' => route_url('student.evaluation'), 'label' => 'View evaluation', 'icon' => 'review'],
    $canAccessFinalRequirements && !$finalRequirementsDone => ['title' => 'Complete Final Requirements', 'message' => 'Your practicum hours are complete or your end date has arrived. Submit your final documents and self-evaluations.', 'route' => route_url('student.documents.final'), 'label' => 'Open final requirements', 'icon' => 'requirements'],
    ($canSubmitReports ?? false) => ['title' => 'Submit OJT Records', 'message' => 'Your OJT records are unlocked. Submit DTR and weekly reports on time.', 'route' => route_url('student.records'), 'label' => 'Submit Record', 'icon' => 'records'],
    default => ['title' => 'Reports Locked', 'message' => $reportLockMessage ?? 'Reports are not available yet.', 'route' => route_url('student.records'), 'label' => 'View Records', 'icon' => 'locked'],
};
$sdIconAttrs = 'viewBox="0 0 24 24" aria-hidden="true"';
$nextActionIcons = [
    'enrollment' => '<svg class="sd-next-icon-svg" ' . $sdIconAttrs . '><path fill="currentColor" d="M12 3 2 8l10 5 8-4v6h2V8L12 3Zm-6 9v4c2 3 10 3 12 0v-4l-6 3-6-3Z"/></svg>',
    'requirements' => '<svg class="sd-next-icon-svg" ' . $sdIconAttrs . '><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg>',
    'review' => '<svg class="sd-next-icon-svg" ' . $sdIconAttrs . '><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>',
    'deployment' => '<svg class="sd-next-icon-svg" ' . $sdIconAttrs . '><path fill="currentColor" d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg>',
    'records' => '<svg class="sd-next-icon-svg" ' . $sdIconAttrs . '><path fill="currentColor" d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm0 16H5V9h14v10Zm-9-8H7v3h3v3h3v-3h3v-3h-3V8h-3v3Z"/></svg>',
    'locked' => '<svg class="sd-next-icon-svg" ' . $sdIconAttrs . '><path fill="currentColor" d="M18 8h-1V6a5 5 0 0 0-10 0v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2Zm-7 8.5V17h2v-2.5a1.25 1.25 0 1 0-2.5 0ZM9 8V6a3 3 0 0 1 6 0v2H9Z"/></svg>',
];
$nextActionIcon = $nextActionIcons[$nextAction['icon'] ?? 'records'] ?? $nextActionIcons['records'];
$journeySteps = [
    ['label' => 'Profile', 'done' => $profileComplete],
    ['label' => 'Documents', 'done' => in_array($predeployment, ['approved', 'forwarded', 'accepted', 'orientation_scheduled', 'orientation_completed'], true)],
    ['label' => 'Deployment', 'done' => in_array($predeployment, ['accepted', 'orientation_scheduled', 'orientation_completed'], true)],
    ['label' => 'Orientation', 'done' => $deploymentComplete],
    ['label' => 'OJT', 'done' => ($canSubmitReports ?? false) || $hoursComplete],
    ['label' => 'Finals', 'done' => $finalRequirementsDone],
    ['label' => 'Cleared', 'done' => $ojtCleared],
];
$currentJourneyIndex = count($journeySteps) - 1;
foreach ($journeySteps as $i => $step) {
    if (!$step['done']) {
        $currentJourneyIndex = $i;
        break;
    }
}
?>

<div class="student-dashboard-page student-dash-v2">
    <nav class="sd-journey" aria-label="OJT journey progress">
        <?php foreach ($journeySteps as $i => $step): ?>
            <div class="sd-journey-step<?= $step['done'] ? ' is-done' : '' ?><?= $i === $currentJourneyIndex ? ' is-current' : '' ?>">
                <span class="sd-journey-dot" aria-hidden="true">
                    <?php if ($step['done']): ?>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                    <?php else: ?>
                        <span><?= $i + 1 ?></span>
                    <?php endif; ?>
                </span>
                <span class="sd-journey-label"><?= e($step['label']) ?></span>
            </div>
            <?php if ($i < count($journeySteps) - 1): ?>
                <span class="sd-journey-line<?= $step['done'] ? ' is-done' : '' ?>" aria-hidden="true"></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <?php if (!empty($actionAlerts)): ?>
    <section class="sd-alerts" aria-label="Action required">
        <?php foreach ($actionAlerts as $alert): ?>
            <article class="sd-alert sd-alert--<?= e($alert['type']) ?>">
                <div class="sd-alert-copy">
                    <strong><?= e($alert['title']) ?></strong>
                    <p><?= e($alert['message']) ?></p>
                </div>
                <a class="btn btn-small sd-alert-btn" href="<?= e($alert['route']) ?>"><?= e($alert['label']) ?></a>
            </article>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <section class="student-next-card sd-next-card">
        <div class="sd-next-glow" aria-hidden="true"></div>
        <div class="student-next-icon sd-next-icon sd-next-icon--<?= e($nextAction['icon'] ?? 'records') ?>" aria-hidden="true">
            <?= $nextActionIcon ?>
        </div>
        <div class="sd-next-copy">
            <span class="student-section-label">Recommended next step</span>
            <h3><?= e($nextAction['title']) ?></h3>
            <p><?= e($nextAction['message']) ?></p>
        </div>
        <a class="btn btn-primary sd-next-btn" href="<?= e($nextAction['route']) ?>"><?= e($nextAction['label']) ?></a>
    </section>

    <div class="student-stat-grid sd-stat-grid">
        <article class="student-stat-card sd-stat-card sd-stat-card--company">
            <span class="student-stat-icon sd-stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg></span>
            <div><span>Company</span><strong><?= e($enrollment['company_name'] ?? 'Not enrolled') ?></strong></div>
        </article>
        <article class="student-stat-card sd-stat-card sd-stat-card--schedule">
            <span class="student-stat-icon sd-stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 16H5V10h14v10Zm-7-2h5v-5h-5v5ZM7 12h5V7H7v5Z"/></svg></span>
            <div><span>Official Schedule</span><strong class="sd-stat-value-sm"><?= e(($officialStart ?: ' - ') . ' -> ' . ($projectedEnd ?: ' - ')) ?></strong></div>
        </article>
        <article class="student-stat-card sd-stat-card sd-stat-card--hours">
            <span class="student-stat-icon sd-stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2ZM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8Zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7Z"/></svg></span>
            <div><span>Remaining Hours</span><strong class="<?= $hoursComplete ? 'is-positive' : '' ?>"><?= $hoursComplete ? 'Complete' : number_format($remaining, 2) ?></strong></div>
        </article>
        <article class="student-stat-card sd-stat-card sd-stat-card--records">
            <span class="student-stat-icon sd-stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg></span>
            <div><span>Submitted Records</span><strong><?= (int)$reportCount ?></strong></div>
        </article>
    </div>

    <div class="sd-bento">
        <section class="student-progress-card sd-progress-card sd-completion-card sd-completion-card--<?= e($ojtCompletion['status']) ?>">
            <div class="student-progress-copy">
                <span class="student-section-label">OJT Clearance</span>
                <h3><?= e($ojtCompletion['label']) ?></h3>
                <p><?= e($ojtCompletion['message']) ?></p>
                <div class="student-progress-bar sd-progress-bar sd-clearance-bar" aria-hidden="true"><span style="width: <?= (int)$ojtCompletion['percent'] ?>%"></span></div>
                <ul class="sd-clearance-list">
                    <?php foreach ($ojtCompletion['checklist'] as $item): ?>
                        <li class="<?= !empty($item['done']) ? 'is-done' : '' ?>">
                            <span class="sd-clearance-dot" aria-hidden="true"></span>
                            <?= e($item['label']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (!empty($certificateFile)): ?>
                    <a class="student-mini-link sd-cert-link" target="_blank" href="<?= e(asset($certificateFile)) ?>">Download completion certificate</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="student-progress-card sd-progress-card">
            <div class="progress-ring-wrap sd-ring-wrap">
                <svg class="progress-ring" viewBox="0 0 120 120" aria-label="OJT progress">
                    <defs>
                        <linearGradient id="sdRingGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#8B1A1A"/>
                            <stop offset="100%" stop-color="#f97316"/>
                        </linearGradient>
                    </defs>
                    <circle class="ring-bg" cx="60" cy="60" r="50"></circle>
                    <circle class="ring-value sd-progress-ring-value" cx="60" cy="60" r="50" style="stroke-dashoffset: <?= $ringOffset ?>"></circle>
                </svg>
                <div class="ring-label"><strong><?= $percent ?>%</strong><span>Complete</span></div>
            </div>
            <div class="student-progress-copy">
                <span class="student-section-label">OJT Progress</span>
                <h3><?= number_format($rendered, 2) ?> / <?= number_format($required, 2) ?> hours rendered</h3>
                <p>DTR and weekly submissions unlock after your Host Training Establishment completes orientation.</p>
                <div class="student-progress-bar sd-progress-bar" aria-hidden="true"><span style="width: <?= $percent ?>%"></span></div>
                <span class="badge <?= e($enrollment['status'] ?? 'pending') ?>"><?= e($enrollment['status'] ?? 'pending') ?></span>
            </div>
        </section>

        <section class="student-requirement-card sd-requirement-card">
            <div class="student-card-head">
                <div>
                    <span class="student-section-label">Pre-deployment</span>
                    <h3>Requirements Snapshot</h3>
                </div>
                <span class="badge <?= e($predeployment) ?>"><?= e(str_replace('_', ' ', $predeployment)) ?></span>
            </div>
            <div class="student-check-list sd-check-list">
                <div class="sd-check-item"><span>Uploaded</span><strong><?= $uploadedRequirements ?>/<?= $totalRequirements ?></strong></div>
                <div class="sd-check-item"><span>Approved</span><strong><?= $approvedRequirements ?>/<?= $totalRequirements ?></strong></div>
                <div class="sd-check-item"><span>Weekly Reports</span><strong><?= count($weeklyReports ?? []) ?></strong></div>
            </div>
            <a class="btn btn-small sd-manage-btn" href="<?= e(route_url('student.documents')) ?>">Manage Documents</a>
        </section>

        <div class="student-status-grid sd-status-grid">
            <article class="student-status-card sd-status-card sd-status-card--profile">
                <span class="student-status-icon student-status-icon--profile" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg>
                </span>
                <div class="student-status-copy"><span class="student-status-label">Profile</span><strong class="<?= $profileComplete ? 'is-positive' : 'is-warning' ?>"><?= $profileComplete ? 'Complete' : 'Incomplete' ?></strong></div>
            </article>
            <article class="student-status-card sd-status-card sd-status-card--docs">
                <span class="student-status-icon student-status-icon--docs" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                </span>
                <div class="student-status-copy"><span class="student-status-label">Approved Docs</span><strong class="<?= $approvedRequirements >= $totalRequirements ? 'is-positive' : '' ?>"><?= $approvedRequirements ?>/<?= $totalRequirements ?></strong></div>
            </article>
            <article class="student-status-card sd-status-card sd-status-card--deployment">
                <span class="student-status-icon student-status-icon--deployment" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 2h4a2 2 0 0 1 2 2v1h4a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h4V4a2 2 0 0 1 2-2Zm0 4V4h-4v2h4Zm-2 8v2h4v-2H8Z"/></svg>
                </span>
                <div class="student-status-copy"><span class="student-status-label">Deployment</span><strong class="<?= $deploymentComplete ? 'is-positive' : '' ?>"><?= $deploymentComplete ? 'Started' : ucwords(str_replace('_', ' ', $predeployment)) ?></strong></div>
            </article>
            <article class="student-status-card sd-status-card sd-status-card--reports">
                <span class="student-status-icon student-status-icon--reports" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M5 3h9l5 5v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm8 1.5V8h3.5L13 4.5ZM8 13h2v5H8v-5Zm3.5-3h2v8h-2v-8ZM15 15h2v3h-2v-3Z"/></svg>
                </span>
                <div class="student-status-copy"><span class="student-status-label">Reports</span><strong><?= (int)$reportCount ?></strong></div>
            </article>
        </div>
    </div>

    <div class="sd-info-grid sd-info-grid--support">
        <section class="student-mini-card sd-mini-card sd-mini-card--deploy">
            <div class="student-mini-card-head">
                <span class="student-mini-card-icon student-mini-card-icon--actions" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg>
                </span>
                <div class="student-mini-card-head-copy">
                    <h3>Deployment Contacts</h3>
                    <span class="student-mini-card-meta">Company and coordinator details</span>
                </div>
            </div>
            <div class="student-mini-card-body sd-contact-list">
                <div class="sd-contact-item">
                    <span>Host Training Establishment</span>
                    <strong><?= e($enrollment['company_name'] ?? 'Not assigned') ?></strong>
                    <?php if (!empty($enrollment['contact_person'])): ?><small><?= e($enrollment['contact_person']) ?></small><?php endif; ?>
                    <?php if (!empty($enrollment['contact_email'])): ?><a class="student-mini-link" href="mailto:<?= e($enrollment['contact_email']) ?>"><?= e($enrollment['contact_email']) ?></a><?php endif; ?>
                    <?php if (!empty($enrollment['company_phone'])): ?><small><?= e($enrollment['company_phone']) ?></small><?php endif; ?>
                </div>
                <div class="sd-contact-item">
                    <span>OJT Coordinator</span>
                    <strong><?= e($student['coordinator_name'] ?? $enrollment['coordinator_name'] ?? 'Not assigned') ?></strong>
                    <?php if (!empty($student['coordinator_email'] ?? $enrollment['coordinator_email'] ?? null)): ?>
                        <a class="student-mini-link" href="mailto:<?= e($student['coordinator_email'] ?? $enrollment['coordinator_email']) ?>"><?= e($student['coordinator_email'] ?? $enrollment['coordinator_email']) ?></a>
                    <?php endif; ?>
                    <?php if (!empty($enrollment['coordinator_department'])): ?><small><?= e($enrollment['coordinator_department']) ?></small><?php endif; ?>
                </div>
            </div>
        </section>

        <section class="student-mini-card sd-mini-card sd-mini-card--orientation">
            <div class="student-mini-card-head">
                <span class="student-mini-card-icon student-mini-card-icon--timeline" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 16H5V10h14v10Zm-7-2h5v-5h-5v5ZM7 12h5V7H7v5Z"/></svg>
                </span>
                <div class="student-mini-card-head-copy">
                    <h3>Orientation & Schedule</h3>
                    <span class="student-mini-card-meta">Important deployment dates</span>
                </div>
            </div>
            <div class="student-mini-card-body sd-schedule-list">
                <?php if ($orientationFormatted): ?>
                    <div class="sd-schedule-item">
                        <span>Orientation</span>
                        <strong><?= e($orientationFormatted) ?></strong>
                        <?php if (!empty($enrollment['orientation_notes'])): ?><p class="student-mini-note"><?= e($enrollment['orientation_notes']) ?></p><?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="student-mini-empty">Orientation schedule will appear here once your Host Training Establishment sets it.</p>
                <?php endif; ?>
                <?php if ($officialStart || $projectedEnd): ?>
                    <div class="sd-schedule-item">
                        <span>Official OJT Period</span>
                        <strong><?= e(($officialStart ? date('M d, Y', strtotime((string)$officialStart)) : ' - ') . ' -> ' . ($projectedEnd ? date('M d, Y', strtotime((string)$projectedEnd)) : ' - ')) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="student-mini-card sd-mini-card sd-mini-card--deadlines">
            <div class="student-mini-card-head">
                <span class="student-mini-card-icon student-mini-card-icon--report" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2ZM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8Zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7Z"/></svg>
                </span>
                <div class="student-mini-card-head-copy">
                    <h3>Reminders</h3>
                    <span class="student-mini-card-meta">Submission deadlines</span>
                </div>
            </div>
            <div class="student-mini-card-body sd-deadline-list">
                <?php if (empty($upcomingDeadlines ?? [])): ?>
                    <p class="student-mini-empty">No active reminders yet.</p>
                <?php else: ?>
                    <?php foreach ($upcomingDeadlines as $deadline): ?>
                        <div class="sd-deadline-item">
                            <strong><?= e($deadline['label']) ?></strong>
                            <?php if (!empty($deadline['date'])): ?><small><?= e($deadline['date']) ?></small><?php endif; ?>
                            <p class="student-mini-note"><?= e($deadline['note']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="student-mini-card sd-mini-card sd-mini-card--updates">
            <div class="student-mini-card-head">
                <span class="student-mini-card-icon student-mini-card-icon--actions" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 22a2 2 0 0 0 2-2h-2v2Zm-6-2h12v-2H6v2Zm6-16a6 6 0 0 0-6 6v4.17l-1.59 1.59A1 1 0 0 0 2 17h20a1 1 0 0 0 .59-1.76L21 13.17V8a6 6 0 0 0-6-6Z"/></svg>
                </span>
                <div class="student-mini-card-head-copy">
                    <h3>Recent Updates</h3>
                    <span class="student-mini-card-meta">System notifications</span>
                </div>
            </div>
            <div class="student-mini-card-body sd-updates-list">
                <?php if (empty($recentNotifications ?? [])): ?>
                    <p class="student-mini-empty">No recent updates yet.</p>
                <?php else: ?>
                    <?php foreach ($recentNotifications as $notice): ?>
                        <a class="sd-update-item<?= empty($notice['is_read']) ? ' is-unread' : '' ?>" href="index.php?action=read_notification&amp;id=<?= (int)$notice['id'] ?>&amp;redirect=<?= urlencode((string)($notice['link'] ?: 'index.php?r=student')) ?>">
                            <strong><?= e($notice['title']) ?></strong>
                            <small><?= e(date('M d, Y g:i A', strtotime((string)$notice['created_at']))) ?></small>
                            <p><?= e($notice['message']) ?></p>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="student-info-grid sd-info-grid">
        <section class="student-mini-card sd-mini-card student-mini-card--dtr">
            <div class="student-mini-card-head">
                <span class="student-mini-card-icon student-mini-card-icon--dtr" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm0 16H5V9h14v10Zm-7 4h2v-2h-2v2Zm-2-4h2v-2H8v2Zm4 0h2v-2h-2v2Zm4 0h2v-2h-2v2Z"/></svg>
                </span>
                <div class="student-mini-card-head-copy">
                    <h3>Today's DTR</h3>
                    <span class="student-mini-card-meta"><?= e(date('M d, Y')) ?></span>
                </div>
                <span class="student-mini-badge <?= $todayDtr ? 'student-mini-badge--success' : 'student-mini-badge--pending' ?>"><?= $todayDtr ? 'Logged' : 'Pending' ?></span>
            </div>
            <div class="student-mini-card-body">
                <?php if ($todayDtr): ?>
                    <div class="student-mini-highlight"><?= e(format_dtr_schedule($todayDtr)) ?></div>
                    <div class="student-mini-stats">
                        <span><strong><?= e($todayDtr['hours']) ?></strong> hours</span>
                        <span class="student-mini-divider" aria-hidden="true"> · </span>
                        <span><?= e($todayDtr['tasks_done']) ?></span>
                    </div>
                <?php else: ?>
                    <p class="student-mini-empty"><?= ($canSubmitReports ?? false) ? 'No DTR submitted for today yet.' : e($reportLockMessage ?? 'DTR is locked.') ?></p>
                <?php endif; ?>
            </div>
        </section>
        <section class="student-mini-card sd-mini-card student-mini-card--report">
            <div class="student-mini-card-head">
                <span class="student-mini-card-icon student-mini-card-icon--report" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg>
                </span>
                <div class="student-mini-card-head-copy">
                    <h3>Latest Weekly Report</h3>
                    <span class="student-mini-card-meta">Narrative PDF</span>
                </div>
                <span class="student-mini-badge student-mini-badge--pdf">PDF</span>
            </div>
            <div class="student-mini-card-body">
                <?php if ($latestWeekly): ?>
                    <div class="student-mini-highlight">Week <?= (int)$latestWeekly['week_no'] ?></div>
                    <p class="student-mini-note"><?= e($latestWeekly['report_text'] ?: 'PDF report submitted.') ?></p>
                    <?php if (!empty($latestWeekly['file_path'])): ?>
                        <a class="student-mini-link" target="_blank" href="<?= e($latestWeekly['file_path']) ?>">View PDF</a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="student-mini-empty">No weekly report submitted yet.</p>
                <?php endif; ?>
            </div>
        </section>
        <section class="student-mini-card sd-mini-card student-actions-card">
            <div class="student-mini-card-head">
                <span class="student-mini-card-icon student-mini-card-icon--actions" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 8h4V4H4v4Zm6 12h4v-4h-4v4Zm-6 0h4v-4H4v4Zm0-6h4v-4H4v4Zm6 0h4v-4h-4v4Zm6 10h4v-4h-4v4Zm0-6h4v-4h-4v4Zm0-6h4V4h-4v4Zm6 6h4v-4h-4v4Zm0 6h4v-4h-4v4Z"/></svg>
                </span>
                <div class="student-mini-card-head-copy">
                    <h3>Quick Actions</h3>
                    <span class="student-mini-card-meta">Shortcuts</span>
                </div>
            </div>
            <div class="student-mini-card-body student-mini-card-body--actions sd-quick-actions">
                <a class="student-mini-action" href="<?= e(route_url('student.records')) ?>">
                    <span class="student-mini-action-main">
                        <span class="student-mini-action-icon student-mini-action-icon--records" aria-hidden="true"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm0 16H5V9h14v10Zm-9-8H7v3h3v3h3v-3h3v-3h-3V8h-3v3Z"/></svg></span>
                        <span>Submit OJT Records</span>
                    </span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                </a>
                <a class="student-mini-action" href="<?= e(route_url('student.documents.final')) ?>">
                    <span class="student-mini-action-main">
                        <span class="student-mini-action-icon student-mini-action-icon--records" aria-hidden="true"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg></span>
                        <span>Final Requirements</span>
                    </span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                </a>
                <a class="student-mini-action" href="<?= e(route_url('student.evaluation')) ?>">
                    <span class="student-mini-action-main">
                        <span class="student-mini-action-icon student-mini-action-icon--timeline" aria-hidden="true"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg></span>
                        <span>My Evaluation</span>
                    </span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                </a>
                <a class="student-mini-action" href="<?= e(route_url('student.timeline')) ?>">
                    <span class="student-mini-action-main">
                        <span class="student-mini-action-icon student-mini-action-icon--timeline" aria-hidden="true"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M7 3a2 2 0 0 1 2 2v1h6V5a2 2 0 1 1 4 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm0 5v11h10V8H7Zm2 2h6v2H9v-2Zm0 4h4v2H9v-2Z"/></svg></span>
                        <span>Activity Timeline</span>
                    </span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                </a>
                <a class="student-mini-action" href="<?= e(route_url('student.settings')) ?>">
                    <span class="student-mini-action-main">
                        <span class="student-mini-action-icon student-mini-action-icon--settings" aria-hidden="true"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.49.49 0 0 0-.59-.22l-2.39.96a7.03 7.03 0 0 0-1.63-.94l-.36-2.54A.49.49 0 0 0 14 2h-4a.49.49 0 0 0-.49.42l-.36 2.54c-.58.22-1.13.53-1.63.94l-2.39-.96a.49.49 0 0 0-.59.22L2.71 8.04a.49.49 0 0 0 .12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.13.22.39.3.59.22l2.39-.96c.5.41 1.05.72 1.63.94l.36 2.54A.49.49 0 0 0 10 22h4c.24 0 .45-.17.49-.42l.36-2.54c.58-.22 1.13-.53 1.63-.94l2.39.96c.2.08.46 0 .59-.22l1.92-3.32a.49.49 0 0 0-.12-.61l-2.03-1.58ZM12 15.5A3.5 3.5 0 1 1 15.5 12 3.5 3.5 0 0 1 12 15.5Z"/></svg></span>
                        <span>Settings</span>
                    </span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                </a>
            </div>
        </section>
    </div>

    <section class="student-activity-card sd-activity-card">
        <div class="student-card-head sd-activity-head">
            <div>
                <span class="student-section-label">Activity</span>
                <h3>Recent DTR Entries</h3>
            </div>
            <a class="sd-activity-link" href="<?= e(route_url('student.records')) ?>">View all -></a>
        </div>
        <div class="timeline sd-timeline<?= empty($dtrs ?? []) ? ' is-empty' : '' ?>">
            <?php if (empty($dtrs ?? [])): ?>
                <div class="sd-timeline-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <p>No daily time records submitted yet.</p>
                </div>
            <?php endif; ?>
            <?php foreach (array_slice($dtrs ?? [], 0, 4) as $d): ?>
                <article class="timeline-item sd-timeline-item">
                    <span class="timeline-dot sd-timeline-dot"></span>
                    <div class="timeline-card sd-timeline-card">
                        <div class="sd-timeline-card-top">
                            <strong><?= e($d['work_date']) ?></strong>
                            <span class="sd-timeline-hours"><?= e($d['hours']) ?> hrs</span>
                        </div>
                        <small><?= e(format_dtr_schedule($d)) ?></small>
                        <p><?= e($d['tasks_done']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
