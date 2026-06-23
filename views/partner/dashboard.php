<?php
$stats = $stats ?? ['total' => 0, 'pending' => 0, 'active' => 0, 'orientation' => 0, 'completed' => 0];
$recentStudents = array_slice($students ?? [], 0, 5);
?>
<div class="industry-dashboard">
    <section class="card ip-dashboard-hero">
        <div>
            <span class="eyebrow">Industry Partner Workspace</span>
            <h2><?= e($company['name'] ?? 'Industry Partner') ?></h2>
            <p class="muted">A professional overview of assigned OJT students, orientation progress, and evaluation work.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(route_url('partner.portal')) ?>">Open Industry Partner Portal</a>
    </section>

    <section class="ip-kpi-grid">
        <article class="card ip-kpi-card"><span>Total Students</span><strong><?= (int)$stats['total'] ?></strong><small class="muted">Assigned to your organization</small></article>
        <article class="card ip-kpi-card"><span>Pending</span><strong><?= (int)$stats['pending'] ?></strong><small class="muted">Awaiting next action</small></article>
        <article class="card ip-kpi-card"><span>Orientation</span><strong><?= (int)$stats['orientation'] ?></strong><small class="muted">Accepted or scheduled</small></article>
        <article class="card ip-kpi-card"><span>Active OJT</span><strong><?= (int)$stats['active'] ?></strong><small class="muted">Currently rendering hours</small></article>
    </section>

    <div class="grid two ip-dashboard-grid">
        <section class="card">
            <div class="section-head section-head-split">
                <div>
                    <h2>Recent Assigned Students</h2>
                    <p class="muted">Quick access to student records in the portal.</p>
                </div>
                <a class="btn btn-small" href="<?= e(route_url('partner.portal')) ?>">View All</a>
            </div>
            <div class="ip-student-list">
                <?php if (empty($recentStudents)): ?>
                    <p class="muted">No students assigned yet.</p>
                <?php endif; ?>
                <?php foreach ($recentStudents as $student): ?>
                    <a class="ip-student-row" href="<?= e(route_url('partner.portal', ['enrollment' => (int)$student['id']])) ?>#student-workspace">
                        <span class="user-avatar"><?= e(strtoupper(substr($student['student_name'] ?? 'S', 0, 1))) ?></span>
                        <span><strong><?= e($student['student_name']) ?></strong><small><?= e($student['course'] . ' ' . $student['year_level']) ?></small></span>
                        <em class="badge <?= e($student['status'] ?? 'pending') ?>"><?= e($student['status'] ?? 'pending') ?></em>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card ip-guide-card">
            <div class="section-head">
                <h2>Portal Workflow</h2>
                <p class="muted">Use the Industry Partner Portal for daily operational tasks.</p>
            </div>
            <ol class="ip-workflow-list">
                <li><strong>Review forwarded documents</strong><span>Open each student and verify their endorsement and requirements.</span></li>
                <li><strong>Send orientation instructions</strong><span>Email instructions first if the schedule is not finalized.</span></li>
                <li><strong>Schedule orientation</strong><span>Orientation date/time and notes are required before saving.</span></li>
                <li><strong>Complete OJT start and evaluation</strong><span>Set official dates, then submit the final evaluation when OJT ends.</span></li>
            </ol>
        </section>
    </div>
</div>
