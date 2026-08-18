<?php
$stats = $stats ?? ['total' => 0, 'pending' => 0, 'active' => 0, 'orientation' => 0, 'completed' => 0];
$submissionStats = $submissionStats ?? ['pending_dtr' => 0, 'pending_weekly' => 0, 'pending_total' => 0, 'students_with_pending' => 0];
$pendingReviewStudents = $pendingReviewStudents ?? [];
$recentStudents = array_slice($students ?? [], 0, 5);

$statusBadge = static function (?string $predeploymentStatus, ?string $enrollmentStatus): array {
    if (strtolower(trim((string)$enrollmentStatus)) === 'completed') {
        return ['label' => 'Completed', 'class' => 'pd-badge--done'];
    }
    return match (strtolower(trim((string)$predeploymentStatus))) {
        'forwarded' => ['label' => 'Docs Forwarded', 'class' => 'pd-badge--pending'],
        'accepted' => ['label' => 'Accepted', 'class' => 'pd-badge--pending'],
        'orientation_scheduled' => ['label' => 'Orientation Set', 'class' => 'pd-badge--pending'],
        'orientation_completed' => ['label' => 'OJT Active', 'class' => 'pd-badge--active'],
        default => ['label' => 'Pending', 'class' => 'pd-badge--pending'],
    };
};

$workflowSteps = [
  ['num' => 1, 'title' => 'Review documents', 'desc' => 'Verify endorsement and requirements after the coordinator forwards them.'],
  ['num' => 2, 'title' => 'Accept deployment', 'desc' => 'Accept forwarded documents to unlock orientation scheduling.'],
  ['num' => 3, 'title' => 'Schedule orientation', 'desc' => 'Set date, time, and notes; the student is emailed automatically.'],
  ['num' => 4, 'title' => 'Start OJT & evaluate', 'desc' => 'Complete orientation, track hours, then submit the final evaluation.'],
];
?>
<div class="partner-dash-v2">
    <section class="pd-kpi-grid" aria-label="Key metrics">
        <article class="pd-stat-card pd-stat-card--total">
            <div class="pd-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="pd-stat-body">
                <span class="pd-stat-label">Total Students</span>
                <strong class="pd-stat-value"><?= (int)$stats['total'] ?></strong>
                <small>Assigned to your organization</small>
            </div>
        </article>
        <article class="pd-stat-card pd-stat-card--pending">
            <div class="pd-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 2.5"/></svg>
            </div>
            <div class="pd-stat-body">
                <span class="pd-stat-label">Pending</span>
                <strong class="pd-stat-value"><?= (int)$stats['pending'] ?></strong>
                <small>Awaiting acceptance</small>
            </div>
        </article>
        <article class="pd-stat-card pd-stat-card--orientation">
            <div class="pd-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
            <div class="pd-stat-body">
                <span class="pd-stat-label">Orientation</span>
                <strong class="pd-stat-value"><?= (int)$stats['orientation'] ?></strong>
                <small>Accepted or scheduled</small>
            </div>
        </article>
        <article class="pd-stat-card pd-stat-card--active">
            <div class="pd-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
            </div>
            <div class="pd-stat-body">
                <span class="pd-stat-label">Active OJT</span>
                <strong class="pd-stat-value"><?= (int)$stats['active'] ?></strong>
                <small>Currently rendering hours</small>
            </div>
        </article>
        <article class="pd-stat-card pd-stat-card--reviews">
            <div class="pd-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
            </div>
            <div class="pd-stat-body">
                <span class="pd-stat-label">Pending Reviews</span>
                <strong class="pd-stat-value"><?= (int)$submissionStats['pending_total'] ?></strong>
                <small><?= (int)$submissionStats['pending_dtr'] ?> DTR · <?= (int)$submissionStats['pending_weekly'] ?> weekly</small>
            </div>
        </article>
        <article class="pd-stat-card pd-stat-card--completed">
            <div class="pd-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><circle cx="12" cy="12" r="10"/></svg>
            </div>
            <div class="pd-stat-body">
                <span class="pd-stat-label">Completed</span>
                <strong class="pd-stat-value"><?= (int)$stats['completed'] ?></strong>
                <small>Finished OJT program</small>
            </div>
        </article>
    </section>

    <?php if ((int)$submissionStats['pending_total'] > 0): ?>
        <section class="pd-attention-banner" aria-label="Pending submission reviews">
            <div class="pd-attention-copy">
                <strong><?= (int)$submissionStats['pending_total'] ?> submission<?= (int)$submissionStats['pending_total'] === 1 ? '' : 's' ?> waiting for review</strong>
                <p>Across <?= (int)$submissionStats['students_with_pending'] ?> student<?= (int)$submissionStats['students_with_pending'] === 1 ? '' : 's' ?> — review DTR entries and weekly reports.</p>
            </div>
            <a class="pd-attention-cta" href="<?= e(route_url('partner.submissions')) ?>">
                Review now
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <?php if (!empty($pendingReviewStudents)): ?>
                <ul class="pd-attention-list">
                    <?php foreach (array_slice($pendingReviewStudents, 0, 4) as $row): ?>
                        <?php $rowPending = (int)$row['pending_dtr'] + (int)$row['pending_weekly']; ?>
                        <li>
                            <a href="<?= e(route_url('partner.submissions', ['student_id' => (int)$row['student_id']])) ?>">
                                <span><?= e($row['student_name']) ?></span>
                                <em><?= $rowPending ?> pending</em>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <div class="pd-main-grid">
        <section class="pd-card pd-students-card">
            <div class="pd-card-head">
                <div>
                    <h2>Recent Assigned Students</h2>
                    <p>Quick access to student records in the portal.</p>
                </div>
                <a class="pd-link-btn" href="<?= e(route_url('partner.portal')) ?>">
                    View All
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>

            <div class="pd-student-list">
                <?php if (empty($recentStudents)): ?>
                    <div class="pd-empty-state">
                        <div class="pd-empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        </div>
                        <p>No students assigned yet</p>
                        <small>Students will appear here once deployed to your organization.</small>
                    </div>
                <?php endif; ?>
                <?php foreach ($recentStudents as $student): ?>
                    <?php
                    $initial = strtoupper(substr($student['student_name'] ?? 'S', 0, 1));
                    $badge = $statusBadge($student['predeployment_status'] ?? null, $student['status'] ?? null);
                    ?>
                    <a class="pd-student-row" href="<?= e(route_url('partner.portal', ['enrollment' => (int)$student['id']])) ?>#student-workspace">
                        <span class="pd-student-avatar" aria-hidden="true"><?= e($initial) ?></span>
                        <span class="pd-student-info">
                            <strong><?= e($student['student_name']) ?></strong>
                            <small><?= e(trim(($student['course'] ?? '') . ' ' . ($student['year_level'] ?? ''))) ?></small>
                        </span>
                        <span class="pd-badge <?= e($badge['class']) ?>"><?= e($badge['label']) ?></span>
                        <svg class="pd-student-chevron" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="pd-card pd-workflow-card">
            <div class="pd-card-head">
                <div>
                    <h2>Portal Workflow</h2>
                    <p>Use the Host Training Establishment Portal for daily operational tasks.</p>
                </div>
            </div>

            <ol class="pd-workflow-steps">
                <?php foreach ($workflowSteps as $step): ?>
                    <li class="pd-workflow-step">
                        <span class="pd-workflow-num" aria-hidden="true"><?= (int)$step['num'] ?></span>
                        <div class="pd-workflow-content">
                            <strong><?= e($step['title']) ?></strong>
                            <span><?= e($step['desc']) ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>

            <a class="pd-workflow-cta" href="<?= e(route_url('partner.portal')) ?>">
                <span>Go to Portal</span>
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </section>
    </div>

    <section class="pd-quick-grid" aria-label="Quick actions">
        <a class="pd-quick-card" href="<?= e(route_url('partner.portal')) ?>">
            <span class="pd-quick-icon pd-quick-icon--portal" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/><path d="M9 9h.01M14 9h.01M9 13h.01M14 13h.01"/></svg>
            </span>
            <span class="pd-quick-text">
                <strong>Host Training Establishment Portal</strong>
                <small>Manage students and deployment</small>
            </span>
            <svg class="pd-quick-arrow" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <a class="pd-quick-card" href="<?= e(route_url('partner.submissions')) ?>">
            <span class="pd-quick-icon pd-quick-icon--submissions" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
            </span>
            <span class="pd-quick-text">
                <strong>Student Submissions</strong>
                <small>Review DTR and weekly reports</small>
            </span>
            <svg class="pd-quick-arrow" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <a class="pd-quick-card" href="<?= e(route_url('chat')) ?>">
            <span class="pd-quick-icon pd-quick-icon--chat" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </span>
            <span class="pd-quick-text">
                <strong>Live Chat</strong>
                <small>Connect with coordinators and students</small>
            </span>
            <svg class="pd-quick-arrow" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </section>
</div>
