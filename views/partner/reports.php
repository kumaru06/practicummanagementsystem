<?php
$rows = $rows ?? [];
$totalApprovedHours = array_sum(array_map(static fn(array $row): float => (float)($row['approved_hours'] ?? 0), $rows));
$totalPending = array_sum(array_map(static fn(array $row): int => (int)($row['pending_dtr'] ?? 0) + (int)($row['pending_weekly'] ?? 0), $rows));
$studentInitials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = strtoupper(substr((string)($parts[0] ?? 'S'), 0, 1));
    $last = strtoupper(substr((string)($parts[count($parts) > 1 ? count($parts) - 1 : 0] ?? 'T'), 0, 1));
    return $first . $last;
};
$statusMeta = static function (array $row): array {
    if (strtolower((string)($row['enrollment_status'] ?? '')) === 'completed') {
        return ['Completed', 'done'];
    }
    $pre = strtolower(trim((string)($row['predeployment_status'] ?? '')));
    return match ($pre) {
        'orientation_completed' => ['Active OJT', 'active'],
        'orientation_scheduled' => ['Orientation set', 'scheduled'],
        'accepted' => ['Accepted', 'accepted'],
        'forwarded' => ['Forwarded', 'forwarded'],
        default => [ucwords(str_replace('_', ' ', $pre ?: 'pending')), 'pending'],
    };
};
?>
<div class="partner-reports-page">
    <div class="pr-kpi-grid" aria-label="Report summary">
        <article class="pr-stat-card pr-stat-card--students">
            <div class="pr-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <span>Students</span>
                <strong><?= count($rows) ?></strong>
                <small>Assigned to your organization</small>
            </div>
        </article>
        <article class="pr-stat-card pr-stat-card--hours">
            <div class="pr-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </div>
            <div>
                <span>Approved hours</span>
                <strong><?= e(number_format($totalApprovedHours, 1)) ?></strong>
                <small>Verified attendance only</small>
            </div>
        </article>
        <article class="pr-stat-card pr-stat-card--pending">
            <div class="pr-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/></svg>
            </div>
            <div>
                <span>Pending review</span>
                <strong><?= (int)$totalPending ?></strong>
                <small>DTR and weekly reports waiting</small>
            </div>
        </article>
    </div>

    <section class="pr-stage">
        <div class="pr-stage-head">
            <div>
                <h3>Student hours</h3>
                <p>Progress against required OJT hours.</p>
            </div>
            <?php if (!empty($rows)): ?>
                <a class="pr-export" href="<?= e(route_url('partner.export_reports')) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m8 11 4 4 4-4"/><path d="M5 19h14"/></svg>
                    Export CSV
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($rows)): ?>
            <div class="pr-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 20V4h16v16H4Zm2-2h12V6H6v12Zm2-8h2v6H8v-6Zm4-3h2v9h-2v-9Zm4 5h2v4h-2v-4Z"/></svg>
                <strong>No deployment data yet</strong>
                <p>Assigned students will appear here after the coordinator forwards their documents.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap pr-table-wrap">
                <table class="data-table no-row-details pr-table" data-no-tools data-hide-column-toggle>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>USN</th>
                            <th>Program</th>
                            <th>Year level</th>
                            <th>Status</th>
                            <th>Hours</th>
                            <th>Pending review</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $pendingDtr = (int)($row['pending_dtr'] ?? 0);
                            $pendingWeekly = (int)($row['pending_weekly'] ?? 0);
                            $pendingTotal = $pendingDtr + $pendingWeekly;
                            $required = (float)($row['required_hours'] ?? 0);
                            $approved = (float)($row['approved_hours'] ?? 0);
                            $progress = $required > 0 ? min(100, (int)round(($approved / $required) * 100)) : 0;
                            [$statusText, $statusTone] = $statusMeta($row);
                            $name = (string)($row['student_name'] ?? '');
                            $portalUrl = route_url('partner.portal', ['enrollment' => (int)$row['enrollment_id']]);
                            $reviewUrl = route_url('partner.submissions', ['student_id' => (int)$row['student_id']]);
                            ?>
                            <tr>
                                <td>
                                    <div class="pr-student">
                                        <span class="pr-avatar" aria-hidden="true"><?= e($studentInitials($name)) ?></span>
                                        <strong><?= e($name) ?></strong>
                                    </div>
                                </td>
                                <td class="pr-usn"><?= e($row['student_no']) ?></td>
                                <td><span class="pr-program"><?= e($row['course'] ?? '') ?></span></td>
                                <td class="pr-year-level"><?= e($row['year_level'] ?? '—') ?></td>
                                <td><span class="pr-status pr-status--<?= e($statusTone) ?>"><?= e($statusText) ?></span></td>
                                <td>
                                    <div class="pr-hours">
                                        <div class="pr-hours-copy">
                                            <strong><?= e(number_format($approved, 1)) ?></strong>
                                            <small><?= $required > 0 ? e(number_format($required, 0) . ' required') : 'No requirement' ?></small>
                                        </div>
                                        <div class="pr-hours-track" aria-label="<?= (int)$progress ?>% complete">
                                            <span style="width: <?= (int)$progress ?>%"></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($pendingTotal > 0): ?>
                                        <span class="pr-pending">
                                            <strong><?= (int)$pendingTotal ?></strong>
                                            <small><?= (int)$pendingDtr ?> DTR · <?= (int)$pendingWeekly ?> weekly</small>
                                        </span>
                                    <?php else: ?>
                                        <span class="pr-pending pr-pending--none">Clear</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pr-actions">
                                    <details class="admin-user-action-menu">
                                        <summary class="admin-user-action-trigger pr-action-trigger" aria-label="Student actions">
                                            <svg class="admin-user-action-trigger-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm0 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                                        </summary>
                                        <div class="admin-user-action-panel">
                                            <a class="admin-user-action-item" href="<?= e($portalUrl) ?>">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 21V7l6-4 6 4v14h-4v-5H7v5H3Zm14 0V9h4v12h-4ZM7 9h4v2H7V9Zm0 4h4v2H7v-2Z"/></svg>
                                                Open portal
                                            </a>
                                            <a class="admin-user-action-item" href="<?= e($reviewUrl) ?>">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm-9 14-4-4 1.4-1.4 2.6 2.6 5.6-5.6L17 10l-7 7Z"/></svg>
                                                Review submissions
                                            </a>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
