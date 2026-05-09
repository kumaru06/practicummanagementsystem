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

<section class="hero-banner compact student-hero">
    <div>
        <span class="eyebrow">Student Portal</span>
        <h2>Welcome back, <?= e($user['name'] ?? 'Student') ?>.</h2>
        <p class="muted">Track your OJT progress, deployment status, documents, and report submissions from one overview.</p>
    </div>
    <div class="hero-actions"><span class="hero-pill"><?= e($enrollment['company_name'] ?? 'Awaiting deployment') ?></span></div>
</section>

<section class="card dashboard-alert">
    <div>
        <span class="eyebrow">Next step</span>
        <h2><?= e($nextAction['title']) ?></h2>
        <p class="muted"><?= e($nextAction['message']) ?></p>
    </div>
    <a class="btn btn-primary" href="<?= e($nextAction['route']) ?>"><?= e($nextAction['label']) ?></a>
</section>

<div class="grid cards">
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="M3 5h18v14H3V5Zm2 2v10h14V7H5Z"/></svg><div><strong><?= e($enrollment['company_name'] ?? 'Not enrolled') ?></strong><span>Company</span></div></div>
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="M7 2h2v2h6V2h2v2h3v18H4V4h3V2Zm11 8H6v10h12V10Z"/></svg><div><strong><?= e(($officialStart ?: '-') . ' to ' . ($projectedEnd ?: '-')) ?></strong><span>Official Schedule</span></div></div>
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5v5l4 2-.8 1.8L11 13V7h2Z"/></svg><div><strong><?= number_format($remaining, 2) ?></strong><span>Remaining Hours</span></div></div>
</div>

<section class="card progress-card ring-progress-card">
    <div class="progress-ring-wrap">
        <svg class="progress-ring" viewBox="0 0 120 120" aria-label="OJT progress">
            <circle class="ring-bg" cx="60" cy="60" r="50"></circle>
            <circle class="ring-value" cx="60" cy="60" r="50" style="stroke-dashoffset: <?= $ringOffset ?>"></circle>
        </svg>
        <div class="ring-label"><strong><?= $percent ?>%</strong><span>Complete</span></div>
    </div>
    <div>
        <div class="section-head"><div><h2>OJT Progress Tracker</h2><p class="muted"><?= number_format($rendered, 2) ?> rendered hours out of <?= number_format($required, 2) ?> required hours</p></div><span class="badge <?= e($enrollment['status'] ?? 'pending') ?>"><?= e($enrollment['status'] ?? 'pending') ?></span></div>
        <p class="muted">DTR and weekly submissions are handled on the Submit Record page after your partner company completes orientation.</p>
    </div>
</section>

<div class="grid two">
    <section class="card">
        <div class="section-head"><div><h2>Pre-deployment Snapshot</h2><p class="muted">Requirement status migrated from the Laravel workflow.</p></div><span class="badge <?= e($predeployment) ?>"><?= e(str_replace('_', ' ', $predeployment)) ?></span></div>
        <div class="progress-list">
            <div><strong><?= $uploadedRequirements ?>/<?= $totalRequirements ?></strong><span>Uploaded requirements</span></div>
            <div><strong><?= $approvedRequirements ?>/<?= $totalRequirements ?></strong><span>Approved requirements</span></div>
            <div><strong><?= count($weeklyReports ?? []) ?></strong><span>Weekly reports submitted</span></div>
        </div>
        <a class="btn btn-small" href="<?= e(route_url('student.documents')) ?>">Manage Documents</a>
    </section>
    <section class="card">
        <div class="section-head"><div><h2>Quick Actions</h2><p class="muted">Use the dedicated pages for each task.</p></div></div>
        <div class="quick-actions">
            <a class="btn btn-small" href="<?= e(route_url('student.records')) ?>">Submit Record</a>
            <a class="btn btn-small" href="<?= e(route_url('student.timeline')) ?>">Activity Timeline</a>
            <a class="btn btn-small" href="<?= e(route_url('student.settings')) ?>">Settings</a>
        </div>
        <?php if (!($canSubmitReports ?? false)): ?><p class="muted" style="margin-top:14px"><?= e($reportLockMessage ?? '') ?></p><?php endif; ?>
    </section>
</div>

<section class="card">
    <div class="section-head"><div><h2>Progress Categories</h2><p class="muted">A quick Laravel-style snapshot of your practicum readiness.</p></div></div>
    <div class="grid cards">
        <div class="card metric"><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg><div><strong><?= $profileComplete ? 'Complete' : 'Incomplete' ?></strong><span>Profile</span></div></div>
        <div class="card metric"><svg viewBox="0 0 24 24"><path d="M7 2h10v20H7V2Zm2 4h6v2H9V6Zm0 4h6v2H9v-2Zm0 4h4v2H9v-2Z"/></svg><div><strong><?= $approvedRequirements ?>/<?= $totalRequirements ?></strong><span>Approved Documents</span></div></div>
        <div class="card metric"><svg viewBox="0 0 24 24"><path d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg><div><strong><?= $deploymentComplete ? 'Started' : ucwords(str_replace('_', ' ', $predeployment)) ?></strong><span>Deployment</span></div></div>
        <div class="card metric"><svg viewBox="0 0 24 24"><path d="M4 6h16v2H4V6Zm0 5h16v2H4v-2Zm0 5h10v2H4v-2Z"/></svg><div><strong><?= $reportCount ?></strong><span>Submitted Records</span></div></div>
    </div>
</section>

<div class="grid two">
    <section class="card">
        <div class="section-head"><h2>Today’s DTR</h2><span class="muted"><?= e(date('M d, Y')) ?></span></div>
        <?php if ($todayDtr): ?>
            <p><strong><?= e($todayDtr['time_in']) ?> - <?= e($todayDtr['time_out']) ?></strong></p>
            <p class="muted"><?= e($todayDtr['hours']) ?> hours · <?= e($todayDtr['tasks_done']) ?></p>
        <?php else: ?>
            <p class="muted"><?= ($canSubmitReports ?? false) ? 'No DTR submitted for today yet.' : e($reportLockMessage ?? 'DTR is locked.') ?></p>
        <?php endif; ?>
    </section>
    <section class="card">
        <div class="section-head"><h2>Latest Weekly Report</h2><span class="muted">Narrative PDF</span></div>
        <?php if ($latestWeekly): ?>
            <p><strong>Week <?= (int)$latestWeekly['week_no'] ?></strong></p>
            <p class="muted"><?= e($latestWeekly['report_text'] ?: 'PDF report submitted.') ?></p>
            <?php if (!empty($latestWeekly['file_path'])): ?><a class="btn btn-small" target="_blank" href="<?= e($latestWeekly['file_path']) ?>">View PDF</a><?php endif; ?>
        <?php else: ?>
            <p class="muted">No weekly report submitted yet.</p>
        <?php endif; ?>
    </section>
</div>

<section class="card">
    <div class="section-head"><h2>Recent Activity</h2><span class="muted">Latest DTR entries</span></div>
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
