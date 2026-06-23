<section class="email-filter-bar">
    <?php
    $formatLogLabel = static fn(string $value): string => ucwords(str_replace('_', ' ', $value));
    $formatFilterDate = static function (?string $value): string {
        if (!$value || strtotime($value) === false) {
            return 'mm/dd/yyyy';
        }

        return date('m/d/Y', strtotime($value));
    };
    ?>
    <div class="section-head">
        <div>
            <h2>Filter Email Activity</h2>
            <p class="muted">Review real PHPMailer SMTP delivery attempts, failures, and errors.</p>
        </div>
        <span class="hero-pill soft">Real-time audit trail</span>
    </div>
    <form method="get" class="filter-bar email-filter-bare">
        <input type="hidden" name="r" value="admin_email_logs">
        <label class="filter-control">
            <span class="filter-label">Email Type</span>
            <span class="filter-select-wrap">
                <select name="type">
                    <option value="">All Types</option>
                    <?php foreach (($types ?? ['student_enrollment','company_deployment','password_reset']) as $type): ?>
                    <option value="<?= e($type) ?>" <?= ($filters['type'] ?? '') === $type ? 'selected' : '' ?>><?= e($formatLogLabel($type)) ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
        </label>
        <label class="filter-control">
            <span class="filter-label">Delivery Status</span>
            <span class="filter-select-wrap">
                <select name="status">
                    <option value="">All Statuses</option>
                    <?php foreach (['sent','failed'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
        </label>
        <label class="filter-control">
            <span class="filter-label">Date From</span>
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
        <label class="filter-control">
            <span class="filter-label">Date To</span>
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
        <button class="btn btn-primary" type="submit">Apply Filters</button>
        <a class="btn btn-small" href="index.php?r=admin_email_logs">Reset</a>
    </form>
</section>

<section class="card">
    <div class="section-head section-head-split"><div><h2>Delivery History</h2><p class="muted">Search, export, and inspect every sent or failed message.</p></div><input class="table-search table-search-wide" placeholder="Search logs..."></div>
    <div class="table-wrap"><table class="data-table no-row-details"><thead><tr><th data-sort>Sent At</th><th data-sort>Recipient</th><th data-sort>Type</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= e($log['sent_at']) ?></td>
                <td><?= e($log['recipient_email']) ?></td>
                <td><span class="email-log-type"><?= e($formatLogLabel($log['type'])) ?></span></td>
                <td class="table-actions">
                    <button
                        class="btn btn-small btn-ghost email-log-view"
                        type="button"
                        data-sent-at="<?= e($log['sent_at']) ?>"
                        data-recipient="<?= e($log['recipient_email']) ?>"
                        data-subject="<?= e($log['subject']) ?>"
                        data-type="<?= e($log['type']) ?>"
                        data-status="<?= e($log['status']) ?>"
                        data-error="<?= e($log['error_message'] ?: 'No error message') ?>"
                    >
                        View
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody></table></div><div class="pagination"></div>
</section>
