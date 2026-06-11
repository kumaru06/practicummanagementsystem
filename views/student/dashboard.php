<?php
$required = (float)($enrollment['required_hours'] ?? 0);
$rendered = (float)($hours ?? 0);
$remaining = max(0, $required - $rendered);
$hoursComplete = $remaining <= 0 && $required > 0;
$percent = $required > 0 ? min(100, round(($rendered / $required) * 100, 1)) : 0;
$ringOffset = 314 - (314 * $percent / 100);
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
?>

<div class="student-dashboard-page">
    <section class="student-dashboard-hero">
        <div class="student-hero-glow student-hero-glow--one" aria-hidden="true"></div>
        <div class="student-hero-glow student-hero-glow--two" aria-hidden="true"></div>
        <div class="student-hero-pattern" aria-hidden="true"></div>
        <div class="student-hero-copy">
            <span class="student-hero-kicker">
                <svg class="dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 2 8l10 5 8-4v6h2V8L12 3Zm-6 9v4c2 3 10 3 12 0v-4l-6 3-6-3Z"/></svg>
                Student Portal
            </span>
            <h2>
                <span class="student-hero-greeting">Welcome back,</span>
                <span class="student-hero-name"><?= e($user['name'] ?? 'Student') ?></span>
            </h2>
            <p>Monitor your OJT progress, requirements, deployment, and reports from one focused dashboard.</p>
            <div class="student-hero-actions">
                <a class="btn btn-primary student-hero-btn-primary" href="<?= e($nextAction['route']) ?>"><?= e($nextAction['label']) ?></a>
                <a class="btn btn-small student-ghost-btn" href="<?= e(route_url('student.timeline')) ?>">View Timeline</a>
            </div>
        </div>
        <aside class="student-hero-panel" aria-label="Deployment summary">
            <div class="student-hero-panel-top">
                <span class="student-hero-panel-icon" aria-hidden="true">
                    <svg class="dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 7V5a2 2 0 0 1 4 0v2"/><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M4 12h16"/></svg>
                </span>
                <span class="student-panel-label">Deployment Company</span>
            </div>
            <strong><?= e($enrollment['company_name'] ?? 'Awaiting deployment') ?></strong>
            <div class="student-panel-meta">
                <span class="student-panel-pill student-panel-pill--status"><?= e($enrollment['status'] ?? 'pending') ?></span>
                <?php if ($hoursComplete): ?>
                    <span class="student-panel-pill student-panel-pill--complete">Hours complete</span>
                <?php else: ?>
                    <span class="student-panel-pill student-panel-pill--hours"><?= number_format($remaining, 2) ?> hrs left</span>
                <?php endif; ?>
            </div>
            <?php if ($required > 0): ?>
                <div class="student-hero-panel-progress">
                    <div class="student-hero-panel-progress-head">
                        <span>OJT Progress</span>
                        <strong><?= $percent ?>%</strong>
                    </div>
                    <div class="student-hero-panel-progress-track"><span style="width: <?= $percent ?>%"></span></div>
                </div>
            <?php endif; ?>
        </aside>
    </section>

    <section class="student-next-card">
        <div class="student-next-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5v5l4 2-.8 1.8L11 13V7h2Z"/></svg></div>
        <div>
            <span class="student-section-label">Recommended next step</span>
            <h3><?= e($nextAction['title']) ?></h3>
            <p><?= e($nextAction['message']) ?></p>
        </div>
        <a class="btn btn-primary" href="<?= e($nextAction['route']) ?>"><?= e($nextAction['label']) ?></a>
    </section>

    <div class="student-stat-grid">
        <article class="student-stat-card">
            <span class="student-stat-icon"><svg viewBox="0 0 24 24"><path d="M3 5h18v14H3V5Zm2 2v10h14V7H5Z"/></svg></span>
            <div><span>Company</span><strong><?= e($enrollment['company_name'] ?? 'Not enrolled') ?></strong></div>
        </article>
        <article class="student-stat-card">
            <span class="student-stat-icon"><svg viewBox="0 0 24 24"><path d="M7 2h2v2h6V2h2v2h3v18H4V4h3V2Zm11 8H6v10h12V10Z"/></svg></span>
            <div><span>Official Schedule</span><strong><?= e(($officialStart ?: '-') . ' to ' . ($projectedEnd ?: '-')) ?></strong></div>
        </article>
        <article class="student-stat-card">
            <span class="student-stat-icon"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5v5l4 2-.8 1.8L11 13V7h2Z"/></svg></span>
            <div><span>Remaining Hours</span><strong><?= $hoursComplete ? 'Complete' : number_format($remaining, 2) ?></strong></div>
        </article>
        <article class="student-stat-card">
            <span class="student-stat-icon"><svg viewBox="0 0 24 24"><path d="M4 6h16v2H4V6Zm0 5h16v2H4v-2Zm0 5h10v2H4v-2Z"/></svg></span>
            <div><span>Submitted Records</span><strong><?= (int)$reportCount ?></strong></div>
        </article>
    </div>

    <div class="student-dashboard-main">
        <section class="student-progress-card">
            <div class="progress-ring-wrap">
                <svg class="progress-ring" viewBox="0 0 120 120" aria-label="OJT progress">
                    <circle class="ring-bg" cx="60" cy="60" r="50"></circle>
                    <circle class="ring-value" cx="60" cy="60" r="50" style="stroke-dashoffset: <?= $ringOffset ?>"></circle>
                </svg>
                <div class="ring-label"><strong><?= $percent ?>%</strong><span>Complete</span></div>
            </div>
            <div class="student-progress-copy">
                <span class="student-section-label">OJT Progress</span>
                <h3><?= number_format($rendered, 2) ?> / <?= number_format($required, 2) ?> hours rendered</h3>
                <p>DTR and weekly submissions unlock after your Industry Partner completes orientation.</p>
                <div class="student-progress-bar" aria-hidden="true"><span style="width: <?= $percent ?>%"></span></div>
                <span class="badge <?= e($enrollment['status'] ?? 'pending') ?>"><?= e($enrollment['status'] ?? 'pending') ?></span>
            </div>
        </section>

        <section class="student-requirement-card">
            <div class="student-card-head">
                <div>
                    <span class="student-section-label">Pre-deployment</span>
                    <h3>Requirements Snapshot</h3>
                </div>
                <span class="badge <?= e($predeployment) ?>"><?= e(str_replace('_', ' ', $predeployment)) ?></span>
            </div>
            <div class="student-check-list">
                <div><span>Uploaded</span><strong><?= $uploadedRequirements ?>/<?= $totalRequirements ?></strong></div>
                <div><span>Approved</span><strong><?= $approvedRequirements ?>/<?= $totalRequirements ?></strong></div>
                <div><span>Weekly Reports</span><strong><?= count($weeklyReports ?? []) ?></strong></div>
            </div>
            <a class="btn btn-small" href="<?= e(route_url('student.documents')) ?>">Manage Documents</a>
        </section>
    </div>

    <div class="student-status-grid">
        <article class="student-status-card student-status-card--profile">
            <span class="student-status-icon student-status-icon--profile" aria-hidden="true">
                <svg class="dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M6 21v-1a6 6 0 0 1 12 0v1"/><path d="m16 10 1.5 1.5L21 8"/></svg>
            </span>
            <div class="student-status-copy">
                <span class="student-status-label">Profile</span>
                <strong class="<?= $profileComplete ? 'is-positive' : 'is-warning' ?>"><?= $profileComplete ? 'Complete' : 'Incomplete' ?></strong>
            </div>
        </article>
        <article class="student-status-card student-status-card--docs">
            <span class="student-status-icon student-status-icon--docs" aria-hidden="true">
                <svg class="dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4h8l3 3v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h2"/><path d="M16 4v4h4"/><path d="M9 13l2 2 4-4"/></svg>
            </span>
            <div class="student-status-copy">
                <span class="student-status-label">Approved Documents</span>
                <strong class="<?= $approvedRequirements >= $totalRequirements ? 'is-positive' : '' ?>"><?= $approvedRequirements ?>/<?= $totalRequirements ?></strong>
            </div>
        </article>
        <article class="student-status-card student-status-card--deployment">
            <span class="student-status-icon student-status-icon--deployment" aria-hidden="true">
                <svg class="dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 7V5a2 2 0 0 1 4 0v2"/><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M4 12h16"/><path d="M12 12v4"/></svg>
            </span>
            <div class="student-status-copy">
                <span class="student-status-label">Deployment</span>
                <strong class="<?= $deploymentComplete ? 'is-positive' : '' ?>"><?= $deploymentComplete ? 'Started' : ucwords(str_replace('_', ' ', $predeployment)) ?></strong>
            </div>
        </article>
        <article class="student-status-card student-status-card--reports">
            <span class="student-status-icon student-status-icon--reports" aria-hidden="true">
                <svg class="dashboard-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9"/><path d="M8 3v6h8"/><path d="M8 13h2v6H8v-6Zm4-3h2v9h-2v-9Zm4 5h2v4h-2v-4Z"/></svg>
            </span>
            <div class="student-status-copy">
                <span class="student-status-label">Reports</span>
                <strong><?= (int)$reportCount ?></strong>
            </div>
        </article>
    </div>

    <div class="student-info-grid">
        <section class="student-mini-card student-mini-card--dtr">
            <div class="student-mini-card-head">
                <span class="student-mini-card-icon student-mini-card-icon--dtr" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </span>
                <div class="student-mini-card-head-copy">
                    <h3>Today’s DTR</h3>
                    <span class="student-mini-card-meta"><?= e(date('M d, Y')) ?></span>
                </div>
                <span class="student-mini-badge <?= $todayDtr ? 'student-mini-badge--success' : 'student-mini-badge--pending' ?>"><?= $todayDtr ? 'Logged' : 'Pending' ?></span>
            </div>
            <div class="student-mini-card-body">
                <?php if ($todayDtr): ?>
                    <div class="student-mini-highlight"><?= e($todayDtr['time_in']) ?> – <?= e($todayDtr['time_out']) ?></div>
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
        <section class="student-mini-card student-mini-card--report">
            <div class="student-mini-card-head">
                <span class="student-mini-card-icon student-mini-card-icon--report" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M10 13h4M10 17h4"/></svg>
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
        <section class="student-mini-card student-actions-card">
            <div class="student-mini-card-head">
                <span class="student-mini-card-icon student-mini-card-icon--actions" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                </span>
                <div class="student-mini-card-head-copy">
                    <h3>Quick Actions</h3>
                    <span class="student-mini-card-meta">Shortcuts</span>
                </div>
            </div>
            <div class="student-mini-card-body student-mini-card-body--actions">
                <a class="student-mini-action" href="<?= e(route_url('student.records')) ?>">Submit Record</a>
                <a class="student-mini-action" href="<?= e(route_url('student.timeline')) ?>">Activity Timeline</a>
                <a class="student-mini-action" href="<?= e(route_url('student.settings')) ?>">Settings</a>
            </div>
        </section>
    </div>

    <section class="student-activity-card">
        <div class="student-card-head"><h3>Recent Activity</h3><span>Latest DTR entries</span></div>
        <div class="timeline<?= empty($dtrs ?? []) ? ' is-empty' : '' ?>">
            <?php if (empty($dtrs ?? [])): ?><p class="muted">No daily time records submitted yet.</p><?php endif; ?>
            <?php foreach (array_slice($dtrs ?? [], 0, 4) as $d): ?>
                <article class="timeline-item">
                    <span class="timeline-dot"></span>
                    <div class="timeline-card"><strong><?= e($d['work_date']) ?></strong><small><?= e($d['time_in']) ?> - <?= e($d['time_out']) ?> · <?= e($d['hours']) ?> hours</small><p><?= e($d['tasks_done']) ?></p></div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
