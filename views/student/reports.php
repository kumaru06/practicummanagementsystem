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
?>

<section class="card">
    <div class="section-head"><h2>Recent Daily Time Records</h2><span class="muted">Submitted DTR entries</span></div>
    <div class="table-wrap">
        <table class="data-table" data-export="pdf" data-export-url="index.php?r=student_report_pdf&amp;type=dtr">
            <thead><tr><th>Date</th><th>Time</th><th>Hours</th><th>Tasks</th><th>Approval Status</th></tr></thead>
            <tbody>
                <?php if (!$dtrs): ?>
                    <tr><td colspan="5" class="muted">No daily time records submitted yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($dtrs as $d): ?>
                    <tr>
                        <td><?= e($d['work_date']) ?></td>
                        <td><?= e($d['time_in']) ?> - <?= e($d['time_out']) ?></td>
                        <td><?= e((string)$d['hours']) ?></td>
                        <td><?= e($d['tasks_done']) ?></td>
                        <td><?= $statusBadge($d['verification_status'] ?? 'pending', $d['verification_notes'] ?? null) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <div class="section-head"><h2>Recent Weekly Reports</h2><span class="muted">Submitted weekly report entries</span></div>
    <div class="table-wrap">
        <table class="data-table" data-export="pdf" data-export-url="index.php?r=student_report_pdf&amp;type=weekly">
            <thead><tr><th>Week</th><th>Date Covered</th><th>Accomplishments</th><th>Files</th><th>Approval Status</th></tr></thead>
            <tbody>
                <?php if (empty($weeklyReports)): ?>
                    <tr><td colspan="5" class="muted">No weekly reports submitted yet.</td></tr>
                <?php endif; ?>
                <?php foreach (($weeklyReports ?? []) as $w): ?>
                    <?php
                        $dateRange = '';
                        if (!empty($w['date_covered_start']) && !empty($w['date_covered_end'])) {
                            $dateRange = date('M d', strtotime($w['date_covered_start'])) . ' - ' . date('M d, Y', strtotime($w['date_covered_end']));
                        }
                        $accomplishments = trim((string)($w['accomplishments'] ?? $w['report_text'] ?? ''));
                        $accomplishmentsShort = $accomplishments !== ''
                            ? (strlen($accomplishments) > 90 ? substr($accomplishments, 0, 88) . '...' : $accomplishments)
                            : '-';
                    ?>
                    <tr>
                        <td><strong>Week <?= (int)$w['week_no'] ?></strong></td>
                        <td><?= $dateRange !== '' ? e($dateRange) : '<span class="muted">-</span>' ?></td>
                        <td><?= e($accomplishmentsShort) ?></td>
                        <td><?= !empty($w['file_path']) ? '<a class="btn btn-small" target="_blank" href="' . e(asset($w['file_path'])) . '">View PDF</a>' : '<span class="muted">-</span>' ?></td>
                        <td><?= $statusBadge($w['verification_status'] ?? 'pending', $w['verification_notes'] ?? null) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
