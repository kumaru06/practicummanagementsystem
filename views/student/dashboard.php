<?php
$required = (float)($enrollment['required_hours'] ?? 0);
$rendered = (float)($hours ?? 0);
$remaining = max(0, $required - $rendered);
$hoursComplete = $remaining <= 0 && $required > 0;
$percent = $required > 0 ? min(100, round(($rendered / $required) * 100, 1)) : 0;
$progressRingRadius = 50;
$progressRingCircumference = 2 * M_PI * $progressRingRadius;
$ringOffset = $progressRingCircumference * (1 - ($percent / 100));
$heroRingRadius = 36;
$heroRingCircumference = 2 * M_PI * $heroRingRadius;
$heroRingOffset = $heroRingCircumference * (1 - ($percent / 100));
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
$nextAction = match (true) {
    !$enrollment => ['title' => 'Awaiting Enrollment', 'message' => 'Your coordinator has not enrolled you in OJT yet.', 'route' => route_url('student.documents'), 'label' => 'View documents', 'icon' => 'enrollment'],
    in_array($predeployment, ['not_submitted', 'needs_revision'], true) => ['title' => 'Prepare Requirements', 'message' => 'Upload all required pre-deployment documents and submit them for review.', 'route' => route_url('student.documents'), 'label' => 'Go to Documents', 'icon' => 'requirements'],
    $predeployment === 'submitted' => ['title' => 'Under Review', 'message' => 'Your coordinator is reviewing your submitted requirements.', 'route' => route_url('student.documents'), 'label' => 'Check Status', 'icon' => 'review'],
    in_array($predeployment, ['approved', 'forwarded', 'accepted', 'orientation_scheduled'], true) => ['title' => 'Deployment Processing', 'message' => 'Wait for company acceptance and orientation completion before submitting reports.', 'route' => route_url('student.documents'), 'label' => 'View Deployment', 'icon' => 'deployment'],
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
$hour = (int)date('G');
$greeting = match (true) {
    $hour < 12 => 'Good morning',
    $hour < 17 => 'Good afternoon',
    default => 'Good evening',
};
$journeySteps = [
    ['label' => 'Profile', 'done' => $profileComplete],
    ['label' => 'Documents', 'done' => in_array($predeployment, ['approved', 'forwarded', 'accepted', 'orientation_scheduled', 'orientation_completed'], true)],
    ['label' => 'Deployment', 'done' => in_array($predeployment, ['accepted', 'orientation_scheduled', 'orientation_completed'], true)],
    ['label' => 'Orientation', 'done' => $deploymentComplete],
    ['label' => 'OJT', 'done' => ($canSubmitReports ?? false)],
    ['label' => 'Done', 'done' => $hoursComplete],
];
$currentJourneyIndex = count($journeySteps) - 1;
foreach ($journeySteps as $i => $step) {
    if (!$step['done']) {
        $currentJourneyIndex = $i;
        break;
    }
}
$statusClass = strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string)($enrollment['status'] ?? 'pending')));
?>

<div class="student-dashboard-page student-dash-v2">
    <section class="student-dashboard-hero sd-hero">
        <div class="student-hero-glow student-hero-glow--one" aria-hidden="true"></div>
        <div class="student-hero-glow student-hero-glow--two" aria-hidden="true"></div>
        <div class="student-hero-glow sd-hero-glow--three" aria-hidden="true"></div>
        <div class="student-hero-pattern" aria-hidden="true"></div>
        <div class="sd-hero-mesh" aria-hidden="true"></div>

        <div class="student-hero-copy sd-hero-copy">
            <span class="student-hero-kicker sd-hero-kicker">
                <svg class="dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 2 8l10 5 8-4v6h2V8L12 3Zm-6 9v4c2 3 10 3 12 0v-4l-6 3-6-3Z"/></svg>
                Student Portal
            </span>
            <p class="sd-hero-date"><?= e(date('l, F j, Y')) ?></p>
            <h2>
                <span class="student-hero-greeting"><?= e($greeting) ?>,</span>
                <span class="student-hero-name"><?= e($user['name'] ?? 'Student') ?></span>
            </h2>
            <p class="sd-hero-sub">Track your OJT journey — deployment, records, and completion in one place.</p>
            <div class="student-hero-actions sd-hero-actions">
                <a class="btn btn-primary student-hero-btn-primary sd-hero-cta" href="<?= e($nextAction['route']) ?>">
                    <span><?= e($nextAction['label']) ?></span>
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <a class="btn btn-small student-ghost-btn" href="<?= e(route_url('student.timeline')) ?>">View Timeline</a>
            </div>
        </div>

        <aside class="student-hero-panel sd-hero-panel" aria-label="Deployment summary">
            <div class="sd-hero-panel-head">
                <div class="sd-hero-panel-brand">
                    <span class="student-hero-panel-icon" aria-hidden="true">
                        <svg class="dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 7V5a2 2 0 0 1 4 0v2"/><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M4 12h16"/></svg>
                    </span>
                    <div>
                        <span class="student-panel-label">Deployment Company</span>
                        <strong><?= e($enrollment['company_name'] ?? 'Awaiting deployment') ?></strong>
                    </div>
                </div>
                <?php if ($required > 0): ?>
                    <div class="sd-hero-ring" aria-hidden="true">
                        <svg viewBox="0 0 88 88">
                            <circle class="sd-ring-bg" cx="44" cy="44" r="36"></circle>
                            <circle class="sd-ring-value" cx="44" cy="44" r="36" style="stroke-dashoffset: <?= $heroRingOffset ?>"></circle>
                        </svg>
                        <span class="sd-hero-ring-label"><span class="sd-hero-ring-value"><?= $percent ?></span><span class="sd-hero-ring-unit">%</span></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="student-panel-meta sd-panel-meta">
                <span class="student-panel-pill student-panel-pill--status sd-status-pill sd-status-pill--<?= e($statusClass) ?>"><?= e($enrollment['status'] ?? 'pending') ?></span>
                <?php if ($hoursComplete): ?>
                    <span class="student-panel-pill student-panel-pill--complete">Hours complete</span>
                <?php else: ?>
                    <span class="student-panel-pill student-panel-pill--hours"><?= number_format($remaining, 2) ?> hrs left</span>
                <?php endif; ?>
            </div>
            <?php if ($required > 0): ?>
                <div class="student-hero-panel-progress sd-panel-progress">
                    <div class="student-hero-panel-progress-head">
                        <span>OJT Progress</span>
                        <strong><?= number_format($rendered, 1) ?> / <?= number_format($required, 0) ?> hrs</strong>
                    </div>
                    <div class="student-hero-panel-progress-track sd-progress-track"><span style="width: <?= $percent ?>%"></span></div>
                </div>
            <?php endif; ?>
        </aside>
    </section>

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
            <div><span>Official Schedule</span><strong class="sd-stat-value-sm"><?= e(($officialStart ?: '—') . ' → ' . ($projectedEnd ?: '—')) ?></strong></div>
        </article>
        <article class="student-stat-card sd-stat-card sd-stat-card--hours">
            <span class="student-stat-icon sd-stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2ZM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8Zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7Z"/></svg></span>
            <div><span>Remaining Hours</span><strong class="<?= $hoursComplete ? 'is-positive' : '' ?>"><?= $hoursComplete ? 'Complete ✓' : number_format($remaining, 2) ?></strong></div>
        </article>
        <article class="student-stat-card sd-stat-card sd-stat-card--records">
            <span class="student-stat-icon sd-stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg></span>
            <div><span>Submitted Records</span><strong><?= (int)$reportCount ?></strong></div>
        </article>
    </div>

    <div class="sd-bento">
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
                <p>DTR and weekly submissions unlock after your Industry Partner completes orientation.</p>
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
                        <span class="student-mini-divider" aria-hidden="true">·</span>
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
            <a class="sd-activity-link" href="<?= e(route_url('student.records')) ?>">View all →</a>
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
