<?php
$statusBadge = static function (?string $status, ?string $notes = null): string {
    $status = strtolower(trim((string)$status));
    if ($status === '' || !in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $status = 'pending';
    }
    $cls = match ($status) {
        'approved' => 'rec-badge-success',
        'rejected' => 'rec-badge-danger',
        default => 'rec-badge-warn',
    };
    $label = strtoupper($status);
    $html = '<span class="rec-status-badge ' . $cls . '">' . htmlspecialchars($label, ENT_QUOTES) . '</span>';
    if ($status === 'rejected' && !empty($notes)) {
        $html .= '<div class="rec-reject-reason"><strong>Reason:</strong> ' . htmlspecialchars($notes, ENT_QUOTES) . '</div>';
    }
    return $html;
};

$dtrRows = $dtrs ?? [];
$weeklyRows = $weeklyReports ?? [];

$countByStatus = static function (array $rows): array {
    $counts = ['approved' => 0, 'pending' => 0, 'rejected' => 0];
    foreach ($rows as $row) {
        $status = strtolower((string)($row['verification_status'] ?? 'pending'));
        if (isset($counts[$status])) {
            $counts[$status]++;
        }
    }
    return $counts;
};

$dtrStats = $countByStatus($dtrRows);
$weeklyStats = $countByStatus($weeklyRows);

$dayTypePill = static function (?string $dayType): string {
    $dayType = normalize_dtr_day_type($dayType ?? 'full');
    $class = match ($dayType) {
        'half_am', 'half_pm' => 'sr-day-pill--half',
        'sick' => 'sr-day-pill--sick',
        'absent' => 'sr-day-pill--absent',
        default => 'sr-day-pill--full',
    };
    return '<span class="sr-day-pill ' . $class . '">' . e(format_dtr_day_type_label($dayType)) . '</span>';
};

$formatReportDate = static function (string $date): string {
    $ts = strtotime($date);
    if ($ts === false) {
        return e($date);
    }
    return e(date('M j, Y', $ts));
};
?>

<div class="student-reports-v2">
<section class="sr-card sr-card--dtr">
    <header class="sr-card-head">
        <div class="sr-card-brand">
            <span class="sr-card-icon sr-card-icon--dtr" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <div>
                <span class="sr-card-kicker">OJT Attendance</span>
                <h2>Recent Daily Time Records</h2>
                <p>Track submitted DTR entries and approval status in one place.</p>
            </div>
        </div>
        <div class="sr-card-meta">
            <span class="sr-count-pill"><?= count($dtrRows) ?> entries</span>
        </div>
    </header>

    <div class="sr-stats-row" aria-label="DTR summary">
        <article class="sr-stat-chip">
            <span>Total</span>
            <strong><?= count($dtrRows) ?></strong>
        </article>
        <article class="sr-stat-chip sr-stat-chip--approved">
            <span>Approved</span>
            <strong><?= $dtrStats['approved'] ?></strong>
        </article>
        <article class="sr-stat-chip sr-stat-chip--pending">
            <span>Pending</span>
            <strong><?= $dtrStats['pending'] ?></strong>
        </article>
        <article class="sr-stat-chip sr-stat-chip--rejected">
            <span>Rejected</span>
            <strong><?= $dtrStats['rejected'] ?></strong>
        </article>
    </div>

    <div class="sr-table-shell">
        <div class="table-wrap sr-table-wrap">
            <table class="data-table reports-dtr-table sr-table" data-export="pdf" data-export-url="index.php?r=student_report_pdf&amp;type=dtr">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day Type</th>
                        <th>Schedule</th>
                        <th>Hours</th>
                        <th>Tasks Done</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dtrRows)): ?>
                        <tr>
                            <td colspan="6" class="sr-empty-cell muted">No daily time records submitted yet.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($dtrRows as $d): ?>
                        <?php $status = strtolower((string)($d['verification_status'] ?? 'pending')); ?>
                        <tr class="sr-row sr-row--<?= e($status) ?>">
                            <td>
                                <div class="sr-date-cell">
                                    <strong><?= $formatReportDate((string)$d['work_date']) ?></strong>
                                    <small><?= e($d['work_date']) ?></small>
                                </div>
                            </td>
                            <td><?= $dayTypePill($d['day_type'] ?? 'full') ?></td>
                            <td><span class="sr-schedule-text"><?= e(format_dtr_schedule($d)) ?></span></td>
                            <td><span class="sr-hours-pill"><?= e((string)$d['hours']) ?></span></td>
                            <td><p class="sr-task-text"><?= e($d['tasks_done']) ?></p></td>
                            <td class="sr-status-cell"><?= $statusBadge($d['verification_status'] ?? 'pending', $d['verification_notes'] ?? null) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="sr-card sr-card--weekly">
    <header class="sr-card-head">
        <div class="sr-card-brand">
            <span class="sr-card-icon sr-card-icon--weekly" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M10 13h4M10 17h4"/></svg>
            </span>
            <div>
                <span class="sr-card-kicker">Weekly Narrative</span>
                <h2>Recent Weekly Reports</h2>
                <p>Review submitted weekly accomplishments and uploaded files.</p>
            </div>
        </div>
        <div class="sr-card-meta">
            <span class="sr-count-pill"><?= count($weeklyRows) ?> entries</span>
        </div>
    </header>

    <div class="sr-stats-row" aria-label="Weekly report summary">
        <article class="sr-stat-chip">
            <span>Total</span>
            <strong><?= count($weeklyRows) ?></strong>
        </article>
        <article class="sr-stat-chip sr-stat-chip--approved">
            <span>Approved</span>
            <strong><?= $weeklyStats['approved'] ?></strong>
        </article>
        <article class="sr-stat-chip sr-stat-chip--pending">
            <span>Pending</span>
            <strong><?= $weeklyStats['pending'] ?></strong>
        </article>
        <article class="sr-stat-chip sr-stat-chip--rejected">
            <span>Rejected</span>
            <strong><?= $weeklyStats['rejected'] ?></strong>
        </article>
    </div>

    <div class="sr-table-shell">
        <div class="table-wrap sr-table-wrap">
            <table class="data-table reports-weekly-table sr-table" data-export="pdf" data-export-url="index.php?r=student_report_pdf&amp;type=weekly">
                <thead>
                    <tr>
                        <th>Week</th>
                        <th>Date Covered</th>
                        <th>Accomplishments</th>
                        <th>File</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($weeklyRows)): ?>
                        <tr>
                            <td colspan="5" class="sr-empty-cell muted">No weekly reports submitted yet.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($weeklyRows as $w): ?>
                        <?php
                            $dateRange = '';
                            if (!empty($w['date_covered_start']) && !empty($w['date_covered_end'])) {
                                $dateRange = date('M d', strtotime($w['date_covered_start'])) . ' - ' . date('M d, Y', strtotime($w['date_covered_end']));
                            }
                            $accomplishments = trim((string)($w['accomplishments'] ?? $w['report_text'] ?? ''));
                            $accomplishmentsShort = $accomplishments !== ''
                                ? (strlen($accomplishments) > 120 ? substr($accomplishments, 0, 118) . '...' : $accomplishments)
                                : '-';
                            $status = strtolower((string)($w['verification_status'] ?? 'pending'));
                        ?>
                        <tr class="sr-row sr-row--<?= e($status) ?>">
                            <td>
                                <div class="sr-week-cell">
                                    <strong>Week <?= (int)$w['week_no'] ?></strong>
                                    <small>Report</small>
                                </div>
                            </td>
                            <td><?= $dateRange !== '' ? e($dateRange) : '<span class="muted">-</span>' ?></td>
                            <td><p class="sr-task-text"><?= e($accomplishmentsShort) ?></p></td>
                            <td>
                                <?php if (!empty($w['file_path'])): ?>
                                    <a class="sr-file-btn" target="_blank" href="<?= e(asset($w['file_path'])) ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                        PDF
                                    </a>
                                <?php else: ?>
                                    <span class="muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="sr-status-cell"><?= $statusBadge($w['verification_status'] ?? 'pending', $w['verification_notes'] ?? null) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
</div>
