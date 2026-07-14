<?php
$formatLogLabel = static fn(string $value): string => ucwords(str_replace('_', ' ', $value));
$formatFilterDate = static function (?string $value): string {
    if (!$value || strtotime($value) === false) {
        return 'mm/dd/yyyy';
    }

    return date('m/d/Y', strtotime($value));
};

$logs = $logs ?? [];
$totalLogs = count($logs);
$sentLogs = 0;
$failedLogs = 0;
foreach ($logs as $logRow) {
    if (strtolower(trim((string)($logRow['status'] ?? ''))) === 'failed') {
        $failedLogs++;
    } else {
        $sentLogs++;
    }
}

$now = new DateTimeImmutable('now');
$yesterday = $now->modify('-1 day');
$grouped = [];

foreach ($logs as $log) {
    $sentRaw = (string)($log['sent_at'] ?? '');
    $dayKey = 'unknown';
    $dayLabel = 'Other';
    $sortKey = '0000-00-00';

    if ($sentRaw !== '' && strtotime($sentRaw) !== false) {
        try {
            $then = new DateTimeImmutable($sentRaw);
            $sortKey = $then->format('Y-m-d');
            if ($then->format('Y-m-d') === $now->format('Y-m-d')) {
                $dayKey = 'today';
                $dayLabel = 'Today';
            } elseif ($then->format('Y-m-d') === $yesterday->format('Y-m-d')) {
                $dayKey = 'yesterday';
                $dayLabel = 'Yesterday';
            } else {
                $dayKey = $sortKey;
                $dayLabel = $then->format('M j, Y');
            }
        } catch (Throwable) {
            // keep defaults
        }
    }

    if (!isset($grouped[$dayKey])) {
        $grouped[$dayKey] = [
            'label' => $dayLabel,
            'sort' => $sortKey,
            'items' => [],
        ];
    }
    $grouped[$dayKey]['items'][] = $log;
}

uasort($grouped, static fn(array $a, array $b): int => strcmp($b['sort'], $a['sort']));
?>
<div class="admin-email-logs-v2" data-email-logs-feed data-per-page="12">
    <div class="ael-metrics">
        <div class="ael-metric">
            <span class="ael-metric-label">Total Logs</span>
            <strong class="ael-metric-value"><?= (int)$totalLogs ?></strong>
        </div>
        <div class="ael-metric">
            <span class="ael-metric-label">Delivered</span>
            <strong class="ael-metric-value ael-metric-value--ok"><?= (int)$sentLogs ?></strong>
        </div>
        <div class="ael-metric">
            <span class="ael-metric-label">Failed</span>
            <strong class="ael-metric-value ael-metric-value--bad"><?= (int)$failedLogs ?></strong>
        </div>
        <div class="ael-metric">
            <span class="ael-metric-label">Audit</span>
            <strong class="ael-metric-value">Live</strong>
        </div>
    </div>

    <section class="ael-shell">
        <header class="ael-toolbar">
            <form method="get" class="ael-filters">
                <input type="hidden" name="r" value="admin_email_logs">
                <label class="ael-field">
                    <span class="ael-field-label">Type</span>
                    <span class="filter-select-wrap">
                        <select name="type">
                            <option value="">All Types</option>
                            <?php foreach (($types ?? ['student_enrollment', 'company_deployment', 'password_reset']) as $type): ?>
                                <option value="<?= e($type) ?>" <?= ($filters['type'] ?? '') === $type ? 'selected' : '' ?>><?= e($formatLogLabel($type)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </span>
                </label>
                <label class="ael-field">
                    <span class="ael-field-label">Status</span>
                    <span class="filter-select-wrap">
                        <select name="status">
                            <option value="">All Statuses</option>
                            <?php foreach (['sent', 'failed'] as $status): ?>
                                <option value="<?= e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </span>
                </label>
                <label class="ael-field">
                    <span class="ael-field-label">From</span>
                    <span class="filter-date-picker<?= empty($filters['date_from']) ? ' is-placeholder' : '' ?>">
                        <input type="hidden" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>">
                        <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select start date">
                            <span class="filter-date-value"><?= e($formatFilterDate($filters['date_from'] ?? '')) ?></span>
                            <span class="filter-date-trigger-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg>
                            </span>
                        </button>
                        <div class="filter-date-panel" hidden></div>
                    </span>
                </label>
                <label class="ael-field">
                    <span class="ael-field-label">To</span>
                    <span class="filter-date-picker<?= empty($filters['date_to']) ? ' is-placeholder' : '' ?>">
                        <input type="hidden" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>">
                        <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select end date">
                            <span class="filter-date-value"><?= e($formatFilterDate($filters['date_to'] ?? '')) ?></span>
                            <span class="filter-date-trigger-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg>
                            </span>
                        </button>
                        <div class="filter-date-panel" hidden></div>
                    </span>
                </label>
                <div class="ael-filter-actions">
                    <button class="btn btn-primary ael-btn-apply" type="submit">Apply</button>
                    <a class="ael-btn-reset" href="index.php?r=admin_email_logs">Reset</a>
                </div>
            </form>

            <label class="ael-search">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5Zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14Z"/></svg>
                <input type="search" data-email-search placeholder="Search recipient, type, subject..." autocomplete="off">
            </label>
        </header>

        <div class="ael-feed" data-email-feed>
            <?php if ($logs === []): ?>
                <div class="ael-empty">
                    <div class="ael-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2Zm0 4-8 5-8-5V6l8 5 8-5v2Z"/></svg>
                    </div>
                    <h3>No email logs found</h3>
                    <p>Try adjusting filters or wait for the next outbound message.</p>
                </div>
            <?php else: ?>
                <?php foreach ($grouped as $group): ?>
                    <section class="ael-day" data-email-day>
                        <h3 class="ael-day-label"><?= e($group['label']) ?></h3>
                        <ul class="ael-list">
                            <?php foreach ($group['items'] as $log): ?>
                                <?php
                                $status = strtolower(trim((string)($log['status'] ?? '')));
                                $isFailed = $status === 'failed';
                                $statusClass = $isFailed ? 'failed' : 'sent';
                                $typeLabel = $formatLogLabel((string)($log['type'] ?? ''));
                                $sentRaw = (string)($log['sent_at'] ?? '');
                                $sentTime = $sentRaw !== '' && strtotime($sentRaw) ? date('g:i A', strtotime($sentRaw)) : '';
                                $recipient = (string)($log['recipient_email'] ?? '');
                                $subject = (string)($log['subject'] ?? 'No subject');
                                $searchBlob = strtolower(trim($recipient . ' ' . $typeLabel . ' ' . $subject . ' ' . $status . ' ' . $sentRaw));
                                ?>
                                <li class="ael-item" data-email-item data-search="<?= e($searchBlob) ?>">
                                    <div class="ael-item-rail" aria-hidden="true">
                                        <span class="ael-item-icon ael-item-icon--<?= e($statusClass) ?>">
                                            <?php if ($isFailed): ?>
                                                <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm5 11H7v-2h10v2Z"/></svg>
                                            <?php else: ?>
                                                <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2Zm0 4-8 5-8-5V6l8 5 8-5v2Z"/></svg>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="ael-item-body">
                                        <div class="ael-item-top">
                                            <strong class="ael-item-title"><?= e($recipient !== '' ? $recipient : 'Unknown recipient') ?></strong>
                                            <span class="ael-item-badge ael-item-badge--<?= e($statusClass) ?>"><?= e(ucfirst($status !== '' ? $status : 'sent')) ?></span>
                                        </div>
                                        <p class="ael-item-subject"><?= e($subject) ?></p>
                                        <div class="ael-item-meta">
                                            <span><?= e($typeLabel) ?></span>
                                            <?php if ($sentTime !== ''): ?>
                                                <span><?= e($sentTime) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="ael-item-actions">
                                        <button
                                            class="ael-view-btn email-log-view"
                                            type="button"
                                            data-sent-at="<?= e($sentRaw) ?>"
                                            data-recipient="<?= e($recipient) ?>"
                                            data-subject="<?= e($subject) ?>"
                                            data-type="<?= e((string)$log['type']) ?>"
                                            data-status="<?= e((string)$log['status']) ?>"
                                            data-error="<?= e($log['error_message'] ?: 'No error message') ?>"
                                        >
                                            View
                                        </button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endforeach; ?>

                <div class="ael-empty ael-empty--filtered is-hidden" data-email-empty>
                    <div class="ael-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5Zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14Z"/></svg>
                    </div>
                    <h3>No matching logs</h3>
                    <p>Try a different search term.</p>
                </div>
            <?php endif; ?>
        </div>

        <footer class="ael-pagination" data-email-pagination></footer>
    </section>
</div>
