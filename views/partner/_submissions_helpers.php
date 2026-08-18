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
    return '<span class="ps-v2-badge ' . $cls . '">' . strtoupper($status) . '</span>';
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
        <div class="ps-v2-record-rail" aria-hidden="true">
            <span class="ps-v2-record-dot"></span>
            <span class="ps-v2-record-line"></span>
        </div>
        <div class="ps-v2-record-card">
            <header class="ps-v2-record-head">
                <div class="ps-v2-record-meta">
                    <span class="dtr-day-type-pill"><?= e(format_dtr_day_type_label($d['day_type'] ?? 'full')) ?></span>
                    <strong><?= e(date('M d, Y', strtotime($d['work_date']))) ?></strong>
                    <small class="muted"><?= e(format_dtr_schedule($d)) ?> · <?= e((string)$d['hours']) ?> hrs</small>
                </div>
                <?= $statusBadge($d['verification_status']) ?>
            </header>
            <p class="ps-v2-record-body"><?= nl2br(e($d['tasks_done'])) ?></p>
            <?php if (!empty($d['verification_notes'])): ?>
                <p class="ps-v2-record-notes"><strong>Notes:</strong> <?= e($d['verification_notes']) ?></p>
            <?php endif; ?>
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
        <div class="ps-v2-record-rail" aria-hidden="true">
            <span class="ps-v2-record-dot"></span>
            <span class="ps-v2-record-line"></span>
        </div>
        <div class="ps-v2-record-card">
            <header class="ps-v2-record-head">
                <div class="ps-v2-record-meta">
                    <strong>Week <?= (int)$w['week_no'] ?></strong>
                    <?php if (!empty($w['date_covered_start']) || !empty($w['date_covered_end'])): ?>
                        <small class="muted"><?= e(date('M d', strtotime($w['date_covered_start'] ?: $w['submitted_at']))) ?> – <?= e(date('M d, Y', strtotime($w['date_covered_end'] ?: $w['submitted_at']))) ?></small>
                    <?php endif; ?>
                </div>
                <?= $statusBadge($w['verification_status']) ?>
            </header>
            <?php if (!empty($w['accomplishments'])): ?>
                <p class="ps-v2-record-body"><?= nl2br(e($w['accomplishments'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($w['proof_files'])): ?>
                <div class="ps-proof-files">
                    <?php foreach ($w['proof_files'] as $f): ?>
                        <a href="<?= e(asset($f['file_path'])) ?>" target="_blank" class="ps-proof-chip" title="<?= e($f['file_name']) ?>">
                            <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 1.5L18.5 8H14V3.5Z"/></svg>
                            <span><?= e(strlen($f['file_name']) > 18 ? substr($f['file_name'], 0, 16) . '..' : $f['file_name']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($w['file_path'])): ?>
                <p class="ps-record-pdf"><a href="<?= e(asset($w['file_path'])) ?>" target="_blank" class="btn btn-small">View PDF Report</a></p>
            <?php endif; ?>
            <?php if (!empty($w['verification_notes'])): ?>
                <p class="ps-v2-record-notes"><strong>Notes:</strong> <?= e($w['verification_notes']) ?></p>
            <?php endif; ?>
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

$tabMetaForFilter = static function (array $counts, string $filter): array {
    $totalAll = (int)$counts['pending'] + (int)$counts['approved'] + (int)$counts['rejected'];
    return match ($filter) {
        'approved' => [
            'tone' => 'approved',
            'label' => (int)$counts['approved'] . ' approved',
            'count' => (int)$counts['approved'],
        ],
        'rejected' => [
            'tone' => 'rejected',
            'label' => (int)$counts['rejected'] . ' rejected',
            'count' => (int)$counts['rejected'],
        ],
        'pending' => [
            'tone' => 'pending',
            'label' => (int)$counts['pending'] . ' pending',
            'count' => (int)$counts['pending'],
        ],
        default => [
            'tone' => 'all',
            'label' => $totalAll . ' total',
            'count' => $totalAll,
        ],
    };
};

$renderTabMeta = static function (array $counts, bool $isActiveTab) use ($statusFilter, $tabMetaForFilter): void {
    if ($isActiveTab) {
        $meta = $tabMetaForFilter($counts, $statusFilter);
        ?>
        <span class="ps-v2-tab-meta">
            <em class="ps-v2-tab-filter-label ps-v2-tab-filter-label--<?= e($meta['tone']) ?>"><?= e($meta['label']) ?></em>
            <span class="ps-v2-tab-total ps-v2-tab-total--<?= e($meta['tone']) ?>"><?= (int)$meta['count'] ?></span>
        </span>
        <?php
        return;
    }
    ?>
    <span class="ps-v2-tab-meta">
        <em class="ps-v2-tab-filter-label ps-v2-tab-filter-label--pending"><?= (int)$counts['pending'] ?> pending</em>
        <span class="ps-v2-tab-total"><?= (int)$counts['pending'] + (int)$counts['approved'] + (int)$counts['rejected'] ?></span>
    </span>
    <?php
};

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
    <details class="ps-v2-filter-dropdown">
        <summary class="ps-v2-filter-trigger" aria-label="Filter by status">
            <span class="ps-v2-filter-dot ps-v2-filter-dot--<?= e($statusFilter) ?>" aria-hidden="true"></span>
            <span class="ps-v2-filter-label"><?= e($filterLabels[$statusFilter] ?? 'Pending') ?></span>
            <svg class="ps-v2-filter-chevron" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </summary>
        <div class="ps-v2-filter-menu" role="menu">
            <?php foreach ($filterLabels as $key => $label): ?>
                <a class="ps-v2-filter-option <?= $statusFilter === $key ? 'is-active' : '' ?>"
                   role="menuitem"
                   data-ps-ajax
                   data-ps-filter="<?= e($key) ?>"
                   href="<?= e($submissionUrl([
                       'student_id' => (int)$selectedStudent['student_id'],
                       'tab' => $activeTab,
                       'status' => $key,
                   ])) ?>">
                    <span class="ps-v2-filter-dot ps-v2-filter-dot--<?= e($key) ?>" aria-hidden="true"></span>
                    <span><?= e($label) ?></span>
                    <em><?= $countMap[$key] ?></em>
                </a>
            <?php endforeach; ?>
        </div>
    </details>
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
