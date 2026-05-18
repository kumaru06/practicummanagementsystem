<?php
$required = (float)($enrollment['required_hours'] ?? 0);
$rendered = (float)($hours ?? 0);
$remaining = max(0, $required - $rendered);
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
        <div class="student-hero-copy">
            <span class="student-hero-kicker">Student Portal</span>
            <h2>Welcome back, <?= e($user['name'] ?? 'Student') ?>.</h2>
            <p>Monitor your OJT progress, requirements, deployment, and reports from one focused dashboard.</p>
            <div class="student-hero-actions">
                <a class="btn btn-primary" href="<?= e($nextAction['route']) ?>"><?= e($nextAction['label']) ?></a>
                <a class="btn btn-small student-ghost-btn" href="<?= e(route_url('student.timeline')) ?>">View Timeline</a>
            </div>
        </div>
        <aside class="student-hero-panel" aria-label="Deployment summary">
            <span class="student-panel-label">Deployment Company</span>
            <strong><?= e($enrollment['company_name'] ?? 'Awaiting deployment') ?></strong>
            <div class="student-panel-meta">
                <span><?= e($enrollment['status'] ?? 'pending') ?></span>
                <span><?= number_format($remaining, 2) ?> hrs left</span>
            </div>
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
            <div><span>Remaining Hours</span><strong><?= number_format($remaining, 2) ?></strong></div>
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
        <article class="student-status-card"><span>Profile</span><strong><?= $profileComplete ? 'Complete' : 'Incomplete' ?></strong></article>
        <article class="student-status-card"><span>Approved Documents</span><strong><?= $approvedRequirements ?>/<?= $totalRequirements ?></strong></article>
        <article class="student-status-card"><span>Deployment</span><strong><?= $deploymentComplete ? 'Started' : ucwords(str_replace('_', ' ', $predeployment)) ?></strong></article>
        <article class="student-status-card"><span>Reports</span><strong><?= (int)$reportCount ?></strong></article>
    </div>

    <div class="student-info-grid">
        <section class="student-mini-card">
            <div class="student-card-head"><h3>Today’s DTR</h3><span><?= e(date('M d, Y')) ?></span></div>
            <?php if ($todayDtr): ?>
                <strong><?= e($todayDtr['time_in']) ?> - <?= e($todayDtr['time_out']) ?></strong>
                <p><?= e($todayDtr['hours']) ?> hours · <?= e($todayDtr['tasks_done']) ?></p>
            <?php else: ?>
                <p><?= ($canSubmitReports ?? false) ? 'No DTR submitted for today yet.' : e($reportLockMessage ?? 'DTR is locked.') ?></p>
            <?php endif; ?>
        </section>
        <section class="student-mini-card">
            <div class="student-card-head"><h3>Latest Weekly Report</h3><span>Narrative PDF</span></div>
            <?php if ($latestWeekly): ?>
                <strong>Week <?= (int)$latestWeekly['week_no'] ?></strong>
                <p><?= e($latestWeekly['report_text'] ?: 'PDF report submitted.') ?></p>
                <?php if (!empty($latestWeekly['file_path'])): ?><a class="btn btn-small" target="_blank" href="<?= e($latestWeekly['file_path']) ?>">View PDF</a><?php endif; ?>
            <?php else: ?>
                <p>No weekly report submitted yet.</p>
            <?php endif; ?>
        </section>
        <section class="student-mini-card student-actions-card">
            <div class="student-card-head"><h3>Quick Actions</h3><span>Shortcuts</span></div>
            <a class="btn btn-small" href="<?= e(route_url('student.records')) ?>">Submit Record</a>
            <a class="btn btn-small" href="<?= e(route_url('student.timeline')) ?>">Activity Timeline</a>
            <a class="btn btn-small" href="<?= e(route_url('student.settings')) ?>">Settings</a>
        </section>
    </div>

    <section class="student-activity-card">
        <div class="student-card-head"><h3>Recent Activity</h3><span>Latest DTR entries</span></div>
        <div class="timeline">
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
