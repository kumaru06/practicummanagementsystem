<?php
$studentSummaries = $studentSummaries ?? [];
$selectedStudent = $selectedStudent ?? null;
$studentDtrs = $studentDtrs ?? [];
$studentWeeklies = $studentWeeklies ?? [];
$activeTab = $activeTab ?? 'dtr';
$statusFilter = $statusFilter ?? 'pending';
$submissionUrl = static function (array $params = []) use ($statusFilter): string {
    if (!array_key_exists('status', $params)) {
        if ($statusFilter !== 'pending') {
            $params['status'] = $statusFilter;
        }
    } elseif (($params['status'] ?? '') === 'pending') {
        unset($params['status']);
    }
    return route_url('partner.submissions', $params);
};

$statusBadge = static function (?string $status): string {
    $status = strtolower(trim((string)$status));
    if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $status = 'pending';
    }
    $cls = match ($status) {
        'approved' => 'ps-v2-badge--success',
        'rejected' => 'ps-v2-badge--danger',
        default => 'ps-v2-badge--warn',
    };
    $label = match ($status) {
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        default => 'Pending',
    };
    return '<span class="ps-v2-badge ' . $cls . '"><i class="ps-v2-badge-dot" aria-hidden="true"></i>' . $label . '</span>';
};

$partitionByStatus = static function (array $records): array {
    $pending = $approved = $rejected = [];
    foreach ($records as $record) {
        $status = strtolower(trim((string)($record['verification_status'] ?? 'pending')));
        match ($status) {
            'approved' => $approved[] = $record,
            'rejected' => $rejected[] = $record,
            default => $pending[] = $record,
        };
    }
    return compact('pending', 'approved', 'rejected');
};

$countByStatus = static function (array $records) use ($partitionByStatus): array {
    $parts = $partitionByStatus($records);
    return [
        'pending' => count($parts['pending']),
        'approved' => count($parts['approved']),
        'rejected' => count($parts['rejected']),
    ];
};

$renderDtrRecord = static function (array $d, ?array $selectedStudent) use ($statusBadge): void {
    if (!$selectedStudent) {
        return;
    }
    ?>
    <article class="ps-v2-record">
        <div class="ps-v2-record-card ps-v2-record-card--dtr">
            <div class="ps-v2-dtr-top">
                <div class="ps-v2-dtr-title">
                    <strong><?= e(date('M d, Y', strtotime($d['work_date']))) ?></strong>
                    <?= $statusBadge($d['verification_status']) ?>
                </div>
                <div class="ps-v2-dtr-meta">
                    <span class="ps-v2-dtr-chip"><?= e(format_dtr_day_type_label($d['day_type'] ?? 'full')) ?></span>
                    <span class="ps-v2-dtr-chip"><?= e(format_dtr_schedule($d)) ?></span>
                    <span class="ps-v2-dtr-chip ps-v2-dtr-chip--hours"><?= e((string)$d['hours']) ?> hrs</span>
                </div>
            </div>
            <div class="ps-v2-record-main">
                <p class="ps-v2-record-body"><?= nl2br(e($d['tasks_done'])) ?></p>
                <?php if (!empty($d['verification_notes'])): ?>
                    <p class="ps-v2-record-notes"><strong>Notes:</strong> <?= e($d['verification_notes']) ?></p>
                <?php endif; ?>
            </div>
            <?php if (($d['verification_status'] ?? '') === 'pending'): ?>
                <form method="post" class="ps-v2-review-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="partner_review_dtr">
                    <input type="hidden" name="dtr_id" value="<?= (int)$d['id'] ?>">
                    <input type="hidden" name="student_id" value="<?= (int)$selectedStudent['student_id'] ?>">
                    <input type="text" name="notes" placeholder="Required when rejecting — explain what to correct..." maxlength="500" data-ps-review-notes>
                    <button class="btn btn-small btn-approve" type="submit" name="decision" value="approved">Approve</button>
                    <button class="btn btn-small btn-reject" type="submit" name="decision" value="rejected" data-ps-reject-btn>Reject</button>
                </form>
            <?php endif; ?>
        </div>
    </article>
    <?php
};

$renderWeeklyRecord = static function (array $w, ?array $selectedStudent) use ($statusBadge): void {
    if (!$selectedStudent) {
        return;
    }
    ?>
    <article class="ps-v2-record">
        <div class="ps-v2-record-card ps-v2-record-card--weekly">
            <div class="ps-v2-week-head">
                <div class="ps-v2-week-id">
                    <strong>Week <?= (int)$w['week_no'] ?></strong>
                    <?php if (!empty($w['date_covered_start']) || !empty($w['date_covered_end'])): ?>
                        <span class="ps-v2-week-date"><?= e(date('M j', strtotime($w['date_covered_start'] ?: $w['submitted_at']))) ?> – <?= e(date('M j, Y', strtotime($w['date_covered_end'] ?: $w['submitted_at']))) ?></span>
                    <?php endif; ?>
                </div>
                <?= $statusBadge($w['verification_status']) ?>
            </div>
            <div class="ps-v2-record-main">
                <?php if (!empty($w['accomplishments'])): ?>
                    <p class="ps-v2-record-body"><?= nl2br(e($w['accomplishments'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($w['proof_files'])): ?>
                    <div class="ps-proof-files">
                        <?php foreach ($w['proof_files'] as $f): ?>
                            <?php $proofExt = strtolower(pathinfo((string)$f['file_name'], PATHINFO_EXTENSION)); ?>
                            <?php $proofIsImage = in_array($proofExt, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true); ?>
                            <a href="<?= e(asset($f['file_path'])) ?>" target="_blank" class="ps-proof-chip <?= $proofIsImage ? 'ps-proof-chip--image' : 'ps-proof-chip--doc' ?>" title="<?= e($f['file_name']) ?>">
                                <span class="ps-proof-icon" aria-hidden="true">
                                    <?php if ($proofIsImage): ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.4"/><path d="m21 15-5-5L5 19"/></svg>
                                    <?php else: ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/></svg>
                                    <?php endif; ?>
                                </span>
                                <span class="ps-proof-name"><?= e(strlen($f['file_name']) > 36 ? substr($f['file_name'], 0, 34) . '..' : $f['file_name']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($w['file_path'])): ?>
                    <p class="ps-record-pdf"><a href="<?= e(asset($w['file_path'])) ?>" target="_blank" class="ps-v2-file-link">View PDF report</a></p>
                <?php endif; ?>
                <?php if (!empty($w['verification_notes'])): ?>
                    <p class="ps-v2-record-notes"><strong>Notes:</strong> <?= e($w['verification_notes']) ?></p>
                <?php endif; ?>
            </div>
            <?php if (($w['verification_status'] ?? '') === 'pending'): ?>
                <form method="post" class="ps-v2-review-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="partner_review_weekly">
                    <input type="hidden" name="weekly_id" value="<?= (int)$w['id'] ?>">
                    <input type="hidden" name="student_id" value="<?= (int)$selectedStudent['student_id'] ?>">
                    <input type="text" name="notes" placeholder="Required when rejecting — explain what to correct..." maxlength="500" data-ps-review-notes>
                    <button class="btn btn-small btn-approve" type="submit" name="decision" value="approved">Approve</button>
                    <button class="btn btn-small btn-reject" type="submit" name="decision" value="rejected" data-ps-reject-btn>Reject</button>
                </form>
            <?php endif; ?>
        </div>
    </article>
    <?php
};

$filterLabels = [
    'all' => 'All',
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
];

$renderStatusFilter = static function (array $counts) use ($statusFilter, $filterLabels, $selectedStudent, $activeTab, $submissionUrl): void {
    if (!$selectedStudent) {
        return;
    }
    $totalAll = (int)$counts['pending'] + (int)$counts['approved'] + (int)$counts['rejected'];
    $countMap = [
        'all' => $totalAll,
        'pending' => (int)$counts['pending'],
        'approved' => (int)$counts['approved'],
        'rejected' => (int)$counts['rejected'],
    ];
    ?>
    <div class="ps-v2-segments" role="group" aria-label="Filter by status">
        <?php foreach ($filterLabels as $key => $label): ?>
            <a class="ps-v2-segment <?= $statusFilter === $key ? 'is-active' : '' ?>"
               <?php if ($statusFilter === $key): ?>aria-current="page"<?php endif; ?>
               data-ps-ajax
               data-ps-filter="<?= e($key) ?>"
               href="<?= e($submissionUrl([
                   'student_id' => (int)$selectedStudent['student_id'],
                   'tab' => $activeTab,
                   'status' => $key,
               ])) ?>">
                <span class="ps-v2-filter-dot ps-v2-filter-dot--<?= e($key) ?>" aria-hidden="true"></span>
                <span><?= e($label) ?></span>
                <em><?= (int)$countMap[$key] ?></em>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
};

$activeRecords = $activeTab === 'weekly' ? $studentWeeklies : $studentDtrs;
$statusCounts = $countByStatus($activeRecords);
$dtrCounts = $countByStatus($studentDtrs);
$weeklyCounts = $countByStatus($studentWeeklies);
$recordParts = $partitionByStatus($activeRecords);
$filteredRecords = match ($statusFilter) {
    'approved' => $recordParts['approved'],
    'rejected' => $recordParts['rejected'],
    'pending' => $recordParts['pending'],
    default => $activeRecords,
};
$emptyFilterMessage = match ($statusFilter) {
    'approved' => 'No approved ' . ($activeTab === 'weekly' ? 'weekly reports' : 'daily time records') . ' yet.',
    'rejected' => 'No rejected ' . ($activeTab === 'weekly' ? 'weekly reports' : 'daily time records') . ' yet.',
    'pending' => 'No pending ' . ($activeTab === 'weekly' ? 'weekly reports' : 'daily time records') . ' to review.',
    default => 'No ' . ($activeTab === 'weekly' ? 'weekly reports' : 'daily time records') . ' submitted yet.',
};

$__submissionsHelpersLoaded = true;
