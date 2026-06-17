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
    !$enrollment => ['title' => 'Awaiting Enrollment', 'message' => 'Your coordinator has not enrolled you in OJT yet.', 'route' => route_url('student.documents'), 'label' => 'View documents'],
    in_array($predeployment, ['not_submitted', 'needs_revision'], true) => ['title' => 'Prepare Requirements', 'message' => 'Upload all required pre-deployment documents and submit them for review.', 'route' => route_url('student.documents'), 'label' => 'Go to Documents'],
    $predeployment === 'submitted' => ['title' => 'Under Review', 'message' => 'Your coordinator is reviewing your submitted requirements.', 'route' => route_url('student.documents'), 'label' => 'Check Status'],
    in_array($predeployment, ['approved', 'forwarded', 'accepted', 'orientation_scheduled'], true) => ['title' => 'Deployment Processing', 'message' => 'Wait for company acceptance and orientation completion before submitting reports.', 'route' => route_url('student.documents'), 'label' => 'View Deployment'],
    ($canSubmitReports ?? false) => ['title' => 'Submit OJT Records', 'message' => 'Your OJT records are unlocked. Submit DTR and weekly reports on time.', 'route' => route_url('student.records'), 'label' => 'Submit Record'],
    default => ['title' => 'Reports Locked', 'message' => $reportLockMessage ?? 'Reports are not available yet.', 'route' => route_url('student.records'), 'label' => 'View Records'],
};
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
                        <svg viewBox="0 0 16 16" fill="none"><path d="M3.5 8.5 6.5 11.5 12.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
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
        <div class="student-next-icon sd-next-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
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
            <span class="student-stat-icon sd-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 5.75A1.75 1.75 0 0 1 6.75 4h7.5A1.75 1.75 0 0 1 16 5.75V20H5V5.75Z"/><path d="M16 8.75A1.75 1.75 0 0 1 17.75 7h.5A1.75 1.75 0 0 1 20 8.75V20h-4V8.75Z"/></svg></span>
            <div><span>Company</span><strong><?= e($enrollment['company_name'] ?? 'Not enrolled') ?></strong></div>
        </article>
        <article class="student-stat-card sd-stat-card sd-stat-card--schedule">
            <span class="student-stat-icon sd-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg></span>
            <div><span>Official Schedule</span><strong class="sd-stat-value-sm"><?= e(($officialStart ?: '—') . ' → ' . ($projectedEnd ?: '—')) ?></strong></div>
        </article>
        <article class="student-stat-card sd-stat-card sd-stat-card--hours">
            <span class="student-stat-icon sd-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
            <div><span>Remaining Hours</span><strong class="<?= $hoursComplete ? 'is-positive' : '' ?>"><?= $hoursComplete ? 'Complete ✓' : number_format($remaining, 2) ?></strong></div>
        </article>
        <article class="student-stat-card sd-stat-card sd-stat-card--records">
            <span class="student-stat-icon sd-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 4h8l3 3v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h2"/><path d="M16 4v4h4"/></svg></span>
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
                    <svg class="dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M6 21v-1a6 6 0 0 1 12 0v1"/></svg>
                </span>
                <div class="student-status-copy"><span class="student-status-label">Profile</span><strong class="<?= $profileComplete ? 'is-positive' : 'is-warning' ?>"><?= $profileComplete ? 'Complete' : 'Incomplete' ?></strong></div>
            </article>
            <article class="student-status-card sd-status-card sd-status-card--docs">
                <span class="student-status-icon student-status-icon--docs" aria-hidden="true">
                    <svg class="dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 4h8l3 3v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h2"/><path d="m9 13 2 2 4-4"/></svg>
                </span>
                <div class="student-status-copy"><span class="student-status-label">Approved Docs</span><strong class="<?= $approvedRequirements >= $totalRequirements ? 'is-positive' : '' ?>"><?= $approvedRequirements ?>/<?= $totalRequirements ?></strong></div>
            </article>
            <article class="student-status-card sd-status-card sd-status-card--deployment">
                <span class="student-status-icon student-status-icon--deployment" aria-hidden="true">
                    <svg class="dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 7V5a2 2 0 0 1 4 0v2"/><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M4 12h16"/></svg>
                </span>
                <div class="student-status-copy"><span class="student-status-label">Deployment</span><strong class="<?= $deploymentComplete ? 'is-positive' : '' ?>"><?= $deploymentComplete ? 'Started' : ucwords(str_replace('_', ' ', $predeployment)) ?></strong></div>
            </article>
            <article class="student-status-card sd-status-card sd-status-card--reports">
                <span class="student-status-icon student-status-icon--reports" aria-hidden="true">
                    <svg class="dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9"/><path d="M8 3v6h8"/><path d="M8 13h2v6M12 10h2v9M16 15h2v4"/></svg>
                </span>
                <div class="student-status-copy"><span class="student-status-label">Reports</span><strong><?= (int)$reportCount ?></strong></div>
            </article>
        </div>
    </div>

    <div class="student-info-grid sd-info-grid">
        <section class="student-mini-card sd-mini-card student-mini-card--dtr">
            <div class="student-mini-card-head">
                <span class="student-mini-card-icon student-mini-card-icon--dtr" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M10 13h4M10 17h4"/></svg>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                </span>
                <div class="student-mini-card-head-copy">
                    <h3>Quick Actions</h3>
                    <span class="student-mini-card-meta">Shortcuts</span>
                </div>
            </div>
            <div class="student-mini-card-body student-mini-card-body--actions sd-quick-actions">
                <a class="student-mini-action" href="<?= e(route_url('student.records')) ?>"><span>Submit Record</span><svg viewBox="0 0 20 20" fill="none"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></a>
                <a class="student-mini-action" href="<?= e(route_url('student.timeline')) ?>"><span>Activity Timeline</span><svg viewBox="0 0 20 20" fill="none"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></a>
                <a class="student-mini-action" href="<?= e(route_url('student.settings')) ?>"><span>Settings</span><svg viewBox="0 0 20 20" fill="none"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></a>
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
