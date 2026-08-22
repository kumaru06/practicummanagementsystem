<?php
$timelineEntries = $timelineEntries ?? [];
$timelineStats = $timelineStats ?? ['entryCount' => 0, 'milestoneCount' => 0, 'dtrCount' => 0, 'weeklyCount' => 0, 'approvedHours' => 0];
$selected = $selected ?? null;
$students = $students ?? [];
$isEmpty = empty($timelineEntries);
$requiredHours = (float)($selected['required_hours'] ?? 0);
$approvedHours = (float)($timelineStats['approvedHours'] ?? 0);
$hoursPct = $requiredHours > 0 ? min(100, (int)round(($approvedHours / $requiredHours) * 100)) : 0;
$enrollmentStatus = strtolower(trim((string)($selected['status'] ?? 'active')));
$statusCopy = $enrollmentStatus === 'completed' ? ['Completed', 'done'] : ['Active OJT', 'active'];

$studentInitials = static function (?array $row): string {
    $parts = preg_split('/\s+/', trim((string)($row['student_name'] ?? ''))) ?: [];
    $first = strtoupper(substr((string)($parts[0] ?? 'S'), 0, 1));
    $last = strtoupper(substr((string)($parts[count($parts) > 1 ? count($parts) - 1 : 0] ?? 'T'), 0, 1));
    return $first . $last;
};
$statusLabel = static function (?string $status): string {
    return ucfirst(strtolower(trim((string)$status ?: 'pending')));
};
$monthLabel = static function (string $date): string {
    if (trim($date) === '') {
        return '';
    }
    try {
        $dt = new DateTimeImmutable($date);
        if ((int)$dt->format('Y') < 1990) {
            return '';
        }
        return $dt->format('F Y');
    } catch (Throwable) {
        return '';
    }
};
?>
<div class="partner-timeline-page">
    <section class="pt-hero">
        <div class="pt-hero-bg" aria-hidden="true">
            <span class="pt-hero-glow pt-hero-glow--one"></span>
            <span class="pt-hero-glow pt-hero-glow--two"></span>
            <span class="pt-hero-mesh"></span>
        </div>
        <div class="pt-hero-copy">
            <div class="pt-hero-kicker">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v4l2.5 2.5"/><circle cx="12" cy="12" r="9"/></svg>
                Activity Timeline
            </div>
            <h2><?= e($selected['student_name'] ?? 'Select a student') ?></h2>
            <?php if ($selected): ?>
                <p class="pt-hero-sub"><?= e($selected['student_no'] ?? '') ?> · <?= e(trim(($selected['course'] ?? '') . ' ' . ($selected['year_level'] ?? ''))) ?></p>
            <?php else: ?>
                <p class="pt-hero-sub">Track deployment milestones, daily records, and weekly reports for assigned students.</p>
            <?php endif; ?>
            <?php if (!empty($students)): ?>
                <?php
                $selectedPickerLabel = $selected
                    ? ((string)$selected['student_name'] . ' · ' . (string)($selected['student_no'] ?? ''))
                    : 'Select a student';
                ?>
                <div class="pt-student-picker">
                    <span class="pt-student-picker-label">Switch student</span>
                    <details class="pt-student-menu">
                        <summary>
                            <span class="pt-student-menu-value"><?= e($selectedPickerLabel) ?></span>
                            <svg class="pt-student-menu-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="pt-student-menu-list" role="listbox">
                            <?php foreach ($students as $student): ?>
                                <?php $isSelectedStudent = $selected && (int)$selected['id'] === (int)$student['id']; ?>
                                <a
                                    class="pt-student-menu-option<?= $isSelectedStudent ? ' is-active' : '' ?>"
                                    href="<?= e(route_url('partner.timeline', ['enrollment' => (int)$student['id']])) ?>"
                                    role="option"
                                    aria-selected="<?= $isSelectedStudent ? 'true' : 'false' ?>"
                                >
                                    <?= e($student['student_name']) ?> · <?= e($student['student_no']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>
            <?php endif; ?>
        </div>
        <aside class="pt-hero-panel" aria-label="Student snapshot">
            <div class="pt-hero-identity">
                <span class="pt-avatar" aria-hidden="true"><?= e($selected ? $studentInitials($selected) : '—') ?></span>
                <div>
                    <span class="pt-panel-label">Current trainee</span>
                    <strong><?= e($selected['student_name'] ?? 'No student selected') ?></strong>
                    <small class="pt-status-pill pt-status-pill--<?= e($statusCopy[1]) ?>"><?= e($selected ? $statusCopy[0] : 'Waiting') ?></small>
                </div>
            </div>
            <div class="pt-hero-metrics">
                <div>
                    <span>Entries</span>
                    <strong><?= (int)$timelineStats['entryCount'] ?></strong>
                </div>
                <div>
                    <span>Hours</span>
                    <strong><?= e(number_format($approvedHours, 1)) ?></strong>
                </div>
                <div>
                    <span>Weekly</span>
                    <strong><?= (int)($timelineStats['weeklyCount'] ?? 0) ?></strong>
                </div>
            </div>
            <div class="pt-progress">
                <div class="pt-progress-head">
                    <span>Approved hours</span>
                    <strong><?= $requiredHours > 0 ? $hoursPct . '%' : '—' ?></strong>
                </div>
                <div class="pt-progress-track" aria-hidden="true"><span style="width: <?= $requiredHours > 0 ? $hoursPct : 0 ?>%"></span></div>
                <p><?= $requiredHours > 0 ? e(number_format($approvedHours, 1) . ' of ' . number_format($requiredHours, 0) . ' required hours') : 'Required hours will appear once OJT starts.' ?></p>
            </div>
        </aside>
    </section>

    <?php if ($selected): ?>
        <div class="pt-kpi-grid" aria-label="Timeline summary">
            <article class="pt-stat-card pt-stat-card--entries">
                <div class="pt-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg>
                </div>
                <div>
                    <span>Total entries</span>
                    <strong><?= (int)$timelineStats['entryCount'] ?></strong>
                    <small>Milestones, DTR, and weekly reports</small>
                </div>
            </article>
            <article class="pt-stat-card pt-stat-card--milestones">
                <div class="pt-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4.5 6.5v11L12 22l7.5-4.5v-11L12 2Z"/><path d="M12 22V12"/><path d="m4.5 6.5 7.5 5.5 7.5-5.5"/></svg>
                </div>
                <div>
                    <span>Milestones</span>
                    <strong><?= (int)$timelineStats['milestoneCount'] ?></strong>
                    <small>Deployment and evaluation events</small>
                </div>
            </article>
            <article class="pt-stat-card pt-stat-card--dtr">
                <div class="pt-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                </div>
                <div>
                    <span>Daily records</span>
                    <strong><?= (int)$timelineStats['dtrCount'] ?></strong>
                    <small><?= (int)($timelineStats['weeklyCount'] ?? 0) ?> weekly reports submitted</small>
                </div>
            </article>
            <article class="pt-stat-card pt-stat-card--hours">
                <div class="pt-stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </div>
                <div>
                    <span>Approved hours</span>
                    <strong><?= e(number_format($approvedHours, 1)) ?></strong>
                    <small><?= $requiredHours > 0 ? e(number_format($requiredHours, 0) . ' hours required') : 'Verified attendance only' ?></small>
                </div>
            </article>
        </div>
    <?php endif; ?>

    <section class="pt-stage">
        <div class="pt-stage-head">
            <div class="pt-stage-identity">
                <span class="pt-avatar pt-avatar--sm" aria-hidden="true"><?= e($selected ? $studentInitials($selected) : '—') ?></span>
                <div>
                    <h3><?= e($selected['student_name'] ?? 'Select a student') ?></h3>
                    <?php if ($selected): ?>
                        <p><?= e($selected['student_no'] ?? '') ?> · <?= e(trim(($selected['course'] ?? '') . ' ' . ($selected['year_level'] ?? ''))) ?></p>
                    <?php else: ?>
                        <p>Choose a trainee to view their activity feed.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!$isEmpty): ?>
                <div class="pt-filters" role="tablist" aria-label="Filter timeline">
                    <button class="pt-filter is-active" type="button" data-st-filter="all" aria-selected="true">All</button>
                    <button class="pt-filter" type="button" data-st-filter="milestone" aria-selected="false">Milestones</button>
                    <button class="pt-filter" type="button" data-st-filter="dtr" aria-selected="false">Daily Records</button>
                    <button class="pt-filter" type="button" data-st-filter="weekly" aria-selected="false">Weekly Reports</button>
                </div>
            <?php endif; ?>
        </div>

        <div class="pt-timeline-scroll">
            <?php if (empty($students)): ?>
                <div class="pt-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <strong>No students assigned yet</strong>
                    <p>Assigned trainees will appear here after the coordinator forwards their documents.</p>
                </div>
            <?php elseif ($isEmpty): ?>
                <div class="pt-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l2.5 2.5"/></svg>
                    <strong>No activity yet</strong>
                    <p>Milestones, daily records, and weekly reports will show here once this student starts logging work.</p>
                </div>
            <?php else: ?>
                <div class="timeline st-timeline" data-st-timeline>
                    <?php $lastMonth = ''; ?>
                    <?php foreach ($timelineEntries as $entry): ?>
                        <?php
                        $currentMonth = $monthLabel((string)($entry['sort_date'] ?? ''));
                        if ($currentMonth !== '' && $currentMonth !== $lastMonth):
                            $lastMonth = $currentMonth;
                        ?>
                            <div class="st-month-divider pt-month" data-st-month><span><?= e($currentMonth) ?></span></div>
                        <?php endif; ?>
                        <?php if (($entry['type'] ?? '') === 'milestone'): ?>
                            <?php $m = $entry['data']; ?>
                            <article class="timeline-item st-timeline-item pt-item" data-type="milestone">
                                <span class="timeline-dot st-timeline-dot pt-dot pt-dot--milestone pt-dot--<?= e($m['tone'] ?? 'default') ?>" aria-hidden="true"></span>
                                <div class="timeline-card st-entry-card pt-card pt-card--milestone">
                                    <div class="pt-card-head">
                                        <span class="pt-type-badge pt-type-badge--milestone">Milestone</span>
                                        <small><?= e(format_timeline_date($entry['sort_date'])) ?></small>
                                    </div>
                                    <strong><?= e($m['title'] ?? 'Update') ?></strong>
                                    <p><?= e($m['detail'] ?? '') ?></p>
                                </div>
                            </article>
                        <?php elseif (($entry['type'] ?? '') === 'dtr'): ?>
                            <?php $d = $entry['data']; ?>
                            <article class="timeline-item st-timeline-item pt-item" data-type="dtr">
                                <span class="timeline-dot st-timeline-dot pt-dot pt-dot--dtr" aria-hidden="true"></span>
                                <div class="timeline-card st-entry-card pt-card pt-card--dtr">
                                    <div class="pt-card-head">
                                        <span class="pt-type-badge pt-type-badge--dtr">Daily Record</span>
                                        <span class="pt-hours-pill"><?= e((string)$d['hours']) ?> hrs</span>
                                        <span class="pt-review-chip pt-review-chip--<?= e($d['review_status'] ?? 'pending') ?>"><?= e($statusLabel($d['review_status'] ?? 'pending')) ?></span>
                                    </div>
                                    <strong><?= e(format_timeline_date($d['work_date'])) ?></strong>
                                    <small class="pt-card-meta"><?= e(format_dtr_schedule($d)) ?></small>
                                    <p><?= e($d['tasks_done'] ?? '') ?></p>
                                </div>
                            </article>
                        <?php else: ?>
                            <?php $r = $entry['data']; ?>
                            <article class="timeline-item st-timeline-item pt-item" data-type="weekly">
                                <span class="timeline-dot st-timeline-dot pt-dot pt-dot--weekly" aria-hidden="true"></span>
                                <div class="timeline-card st-entry-card pt-card pt-card--weekly">
                                    <div class="pt-card-head">
                                        <span class="pt-type-badge pt-type-badge--weekly">Weekly Report</span>
                                        <span class="pt-week-pill">Week <?= (int)($r['week_no'] ?? 0) ?></span>
                                        <span class="pt-review-chip pt-review-chip--<?= e($r['review_status'] ?? 'pending') ?>"><?= e($statusLabel($r['review_status'] ?? 'pending')) ?></span>
                                    </div>
                                    <strong>Weekly Report <?= (int)($r['week_no'] ?? 0) ?></strong>
                                    <?php if (!empty($r['accomplishments'])): ?>
                                        <p><?= e($r['accomplishments']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
