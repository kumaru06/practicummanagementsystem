<div class="grid two">
    <?php if (!($canSubmitReports ?? false)): ?>
    <section class="card locked-card"><h2>Submit Daily Time Record</h2><p class="muted"><?= e($reportLockMessage ?? 'DTR is locked until your OJT deployment starts.') ?></p><button class="btn btn-primary" type="button" disabled>Submit DTR Locked</button></section>
    <section class="card locked-card"><h2>Submit Weekly Report</h2><p class="muted"><?= e($reportLockMessage ?? 'Weekly reports are locked until your OJT deployment starts.') ?></p><button class="btn btn-primary" type="button" disabled>Weekly Report Locked</button></section>
    <?php else: ?>
    <?php $dtrDraft = $dtrDraft ?? []; ?>
    <section class="card"><h2>Submit Daily Time Record</h2><form method="post" class="form js-validate" data-dtr-lock-flow data-confirm-submit="Submit this DTR? Please verify the date, time in, time out, and tasks before submitting." data-confirm-title="Submit DTR" data-confirm-ok="Submit DTR"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="student_add_dtr"><label>Date
        <span class="filter-date-picker form-date-picker is-placeholder" data-date-required="1">
            <input type="hidden" name="work_date" value="<?= e($dtrDraft['work_date'] ?? '') ?>">
            <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select work date">
                <span class="filter-date-value">mm/dd/yyyy</span>
                <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
            </button>
            <div class="filter-date-panel" hidden></div>
        </span>
    </label><div class="dtr-time-lock" data-time-lock-group data-saved-locked="<?= !empty($dtrDraft['time_in_locked']) ? '1' : '0' ?>"><label>Time In<input required type="hidden" name="time_in" value="<?= e($dtrDraft['time_in'] ?? '') ?>" data-lockable-time><button class="dtr-time-picker-trigger" type="button" data-time-picker-trigger><span data-time-display>--:-- --</span><span class="dtr-time-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 1 0 20a10 10 0 0 1 0-20Zm0 2a8 8 0 1 0 0 16a8 8 0 0 0 0-16Zm0 2.75a1 1 0 0 1 1 1v3.67l2.28 2.28a1 1 0 1 1-1.42 1.42l-2.57-2.58A1 1 0 0 1 11 11.83V7.75a1 1 0 0 1 1-1Z"/></svg></span></button></label><button class="btn btn-small dtr-time-lock-btn" type="button" data-time-lock-toggle data-apply-label="Save Time In" data-edit-label="Undo Time In">Save Time In</button></div><div class="dtr-time-lock" data-time-lock-group data-saved-locked="<?= !empty($dtrDraft['time_out_locked']) ? '1' : '0' ?>"><label>Time Out<input required type="hidden" name="time_out" value="<?= e($dtrDraft['time_out'] ?? '') ?>" data-lockable-time><button class="dtr-time-picker-trigger" type="button" data-time-picker-trigger><span data-time-display>--:-- --</span><span class="dtr-time-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 1 0 20a10 10 0 0 1 0-20Zm0 2a8 8 0 1 0 0 16a8 8 0 0 0 0-16Zm0 2.75a1 1 0 0 1 1 1v3.67l2.28 2.28a1 1 0 1 1-1.42 1.42l-2.57-2.58A1 1 0 0 1 11 11.83V7.75a1 1 0 0 1 1-1Z"/></svg></span></button></label><button class="btn btn-small dtr-time-lock-btn" type="button" data-time-lock-toggle data-apply-label="Save Time Out" data-edit-label="Undo Time Out">Save Time Out</button></div><label>Tasks Done<textarea required maxlength="500" name="tasks_done" data-dtr-tasks></textarea></label><button class="btn btn-primary" type="submit" data-dtr-submit><span class="btn-text">Submit DTR</span><span class="spinner"></span></button></form></section>
    <section class="card weekly-report-card">
        <h2>Weekly Task Report</h2>
        <p class="muted">Submit your weekly accomplishments and proof of work</p>
        <form method="post" enctype="multipart/form-data" class="form js-validate" id="weeklyReportForm"
              data-confirm-submit="Submit this weekly report? Please verify all fields before submitting."
              data-confirm-title="Submit weekly report" data-confirm-ok="Submit report">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="student_add_weekly">

            <div class="wr-section">
                <h3 class="wr-section-title">Week Information</h3>
                <div class="wr-week-row">
                    <div>
                        <span class="wr-field-label">Week Number</span>
                        <select required name="week_no">
                            <option value="">Select week</option>
                            <?php for ($w = 1; $w <= 18; $w++): ?>
                                <option value="<?= $w ?>">Week <?= $w ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <span class="wr-field-label">Date Covered</span>
                        <div class="wr-date-range">
                            <input type="date" name="date_covered_start" required>
                            <span class="wr-date-sep">-</span>
                            <input type="date" name="date_covered_end" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wr-section">
                <h3 class="wr-section-title">Weekly Accomplishments</h3>
                <p class="muted">What tasks did you accomplish this week?</p>
                <div class="wr-textarea-wrap">
                    <textarea name="accomplishments" maxlength="2000" rows="5" placeholder="Write your weekly accomplishments here..."></textarea>
                    <span class="wr-char-count"><span data-char-current>0</span> / 2000 characters</span>
                </div>
            </div>

            <div class="wr-section">
                <h3 class="wr-section-title">Upload Proof of Work</h3>
                <p class="muted">Upload pictures, screenshots or files as proof of your accomplishments.</p>
                <div class="wr-dropzone" id="wrDropzone">
                    <div class="wr-dropzone-inner">
                        <svg class="wr-dropzone-icon" viewBox="0 0 24 24" width="36" height="36"><path fill="currentColor" d="M11 14.414V20h2v-5.586l2.293 2.293 1.414-1.414L12 10.586l-4.707 4.707 1.414 1.414L11 14.414ZM4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4v-2h4V6H4v12h4v2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg>
                        <p><strong>Drag &amp; drop files here</strong></p>
                        <p class="muted">or <button type="button" class="wr-browse-btn" id="wrBrowseLink">click to browse</button></p>
                        <p class="muted small">You can upload multiple images (JPG, PNG) or PDF files.</p>
                    </div>
                    <input type="file" id="wrFileInput" multiple accept=".jpg,.jpeg,.png,.pdf" hidden>
                </div>
                <div class="wr-preview-row" id="wrPreviewRow"></div>
                <p class="wr-file-limits"><span class="muted">JPG, PNG, PDF</span> <span class="muted">Max file size: 10MB each</span></p>
            </div>

            <button class="btn btn-primary btn-weekly-submit" type="submit">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z"/></svg>
                <span class="btn-text">Submit Weekly Report</span>
                <span class="spinner"></span>
            </button>
        </form>
    </section>
    <?php endif; ?>
</div>

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
        <table class="data-table">
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
        <table class="data-table">
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
