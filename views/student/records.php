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
    <section class="card"><h2>Submit Weekly Report</h2><form method="post" enctype="multipart/form-data" class="form js-validate" data-confirm-submit="Submit this weekly report? Please verify the week number and selected PDF before submitting." data-confirm-title="Submit weekly report" data-confirm-ok="Submit report"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="student_add_weekly"><label>Week Number<input required type="number" min="1" name="week_no"></label><label>PDF Report<input required type="file" name="report_file" accept=".pdf"></label><button class="btn btn-primary" type="submit"><span class="btn-text">Submit Weekly Report</span><span class="spinner"></span></button></form></section>
    <?php endif; ?>
</div>

<section class="card">
    <div class="section-head"><h2>Recent Daily Time Records</h2><span class="muted">Submitted DTR entries</span></div>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Date</th><th>Time</th><th>Hours</th><th>Tasks</th></tr></thead><tbody>
        <?php if (!$dtrs): ?><tr><td colspan="4" class="muted">No daily time records submitted yet.</td></tr><?php endif; ?>
        <?php foreach ($dtrs as $d): ?><tr><td><?= e($d['work_date']) ?></td><td><?= e($d['time_in']) ?> - <?= e($d['time_out']) ?></td><td><?= e((string)$d['hours']) ?></td><td><?= e($d['tasks_done']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
