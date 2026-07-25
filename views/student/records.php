<div class="grid two records-page">
    <?php
    $formatDtrTimeValue = static function (?string $time): string {
        if (!$time) {
            return '';
        }
        return substr((string)$time, 0, 5);
    };
    $rejectedDtrs = array_values(array_filter($dtrs ?? [], static fn ($row) => strtolower((string)($row['verification_status'] ?? '')) === 'rejected'));
    $rejectedWeekly = array_values(array_filter($weeklyReports ?? [], static fn ($row) => strtolower((string)($row['verification_status'] ?? '')) === 'rejected'));
    $resubmitDtrId = (int)($_GET['resubmit_dtr'] ?? 0);
    $resubmitWeeklyId = (int)($_GET['resubmit_weekly'] ?? 0);
    ?>
    <?php if (!empty($rejectedDtrs) || !empty($rejectedWeekly)): ?>
    <section class="card records-action-card" style="grid-column: 1 / -1;">
        <div class="section-head">
            <div>
                <h2>Correct Rejected Submissions</h2>
                <p class="muted">Review the rejection reason, update your entry, and resubmit for Host Training Establishment approval.</p>
            </div>
        </div>
        <div class="records-reject-grid">
            <?php foreach ($rejectedDtrs as $rejectedDtr): ?>
                <?php
                    $dtrId = (int)$rejectedDtr['id'];
                    $isOpen = $resubmitDtrId === $dtrId || ($resubmitDtrId === 0 && count($rejectedDtrs) === 1);
                    $dayType = normalize_dtr_day_type($rejectedDtr['day_type'] ?? 'full');
                ?>
                <article class="records-reject-card<?= $isOpen ? ' is-open' : '' ?>" id="resubmit-dtr-<?= $dtrId ?>">
                    <header class="records-reject-head">
                        <div>
                            <strong>DTR · <?= e($rejectedDtr['work_date']) ?></strong>
                            <span class="rec-status-badge rec-badge-danger">REJECTED</span>
                        </div>
                        <a class="btn btn-small" href="index.php?r=student_records&amp;resubmit_dtr=<?= $dtrId ?>#resubmit-dtr-<?= $dtrId ?>">Correct entry</a>
                    </header>
                    <?php if (!empty($rejectedDtr['verification_notes'])): ?>
                        <div class="rec-reject-reason"><strong>Reason:</strong> <?= e($rejectedDtr['verification_notes']) ?></div>
                    <?php endif; ?>
                    <?php if ($isOpen): ?>
                        <form method="post" class="form records-resubmit-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="student_resubmit_dtr">
                            <input type="hidden" name="dtr_id" value="<?= $dtrId ?>">
                            <div class="records-resubmit-meta">
                                <span><strong>Work date:</strong> <?= e($rejectedDtr['work_date']) ?></span>
                            </div>
                            <label>
                                <span>Day Type</span>
                                <select name="day_type" required>
                                    <?php foreach (dtr_day_types() as $value => $label): ?>
                                        <option value="<?= e($value) ?>"<?= $dayType === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <div class="records-resubmit-times">
                                <label><span>Morning In</span><input type="time" name="morning_time_in" value="<?= e($formatDtrTimeValue($rejectedDtr['morning_time_in'] ?? $rejectedDtr['time_in'] ?? '')) ?>"></label>
                                <label><span>Morning Out</span><input type="time" name="morning_time_out" value="<?= e($formatDtrTimeValue($rejectedDtr['morning_time_out'] ?? '')) ?>"></label>
                                <label><span>Afternoon In</span><input type="time" name="afternoon_time_in" value="<?= e($formatDtrTimeValue($rejectedDtr['afternoon_time_in'] ?? '')) ?>"></label>
                                <label><span>Afternoon Out</span><input type="time" name="afternoon_time_out" value="<?= e($formatDtrTimeValue($rejectedDtr['afternoon_time_out'] ?? $rejectedDtr['time_out'] ?? '')) ?>"></label>
                            </div>
                            <label>
                                <span>Tasks Done</span>
                                <textarea required maxlength="500" name="tasks_done" rows="4"><?= e($rejectedDtr['tasks_done'] ?? '') ?></textarea>
                            </label>
                            <button class="btn btn-primary" type="submit">Resubmit DTR</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>

            <?php foreach ($rejectedWeekly as $rejectedReport): ?>
                <?php
                    $weeklyId = (int)$rejectedReport['id'];
                    $isOpen = $resubmitWeeklyId === $weeklyId || ($resubmitWeeklyId === 0 && empty($rejectedDtrs) && count($rejectedWeekly) === 1);
                ?>
                <article class="records-reject-card<?= $isOpen ? ' is-open' : '' ?>" id="resubmit-weekly-<?= $weeklyId ?>">
                    <header class="records-reject-head">
                        <div>
                            <strong>Weekly Report · Week <?= (int)$rejectedReport['week_no'] ?></strong>
                            <span class="rec-status-badge rec-badge-danger">REJECTED</span>
                        </div>
                        <a class="btn btn-small" href="index.php?r=student_records&amp;resubmit_weekly=<?= $weeklyId ?>#resubmit-weekly-<?= $weeklyId ?>">Correct entry</a>
                    </header>
                    <?php if (!empty($rejectedReport['verification_notes'])): ?>
                        <div class="rec-reject-reason"><strong>Reason:</strong> <?= e($rejectedReport['verification_notes']) ?></div>
                    <?php endif; ?>
                    <?php if ($isOpen): ?>
                        <form method="post" enctype="multipart/form-data" class="form records-resubmit-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="student_resubmit_weekly">
                            <input type="hidden" name="weekly_id" value="<?= $weeklyId ?>">
                            <div class="records-resubmit-meta">
                                <span><strong>Week:</strong> <?= (int)$rejectedReport['week_no'] ?></span>
                            </div>
                            <div class="records-resubmit-times wr-date-range" data-wr-date-range>
                                <div class="wr-date-field">
                                    <span class="wr-date-field-label">Start date</span>
                                    <?php render_form_date_picker('date_covered_start', (string)($rejectedReport['date_covered_start'] ?? ''), ['data-wr-date' => 'start']); ?>
                                </div>
                                <span class="wr-date-sep" aria-hidden="true">to</span>
                                <div class="wr-date-field">
                                    <span class="wr-date-field-label">End date</span>
                                    <?php render_form_date_picker('date_covered_end', (string)($rejectedReport['date_covered_end'] ?? ''), ['data-wr-date' => 'end']); ?>
                                </div>
                            </div>
                            <label>
                                <span>Weekly accomplishments</span>
                                <textarea required maxlength="2000" name="accomplishments" rows="5"><?= e(trim((string)($rejectedReport['accomplishments'] ?? $rejectedReport['report_text'] ?? ''))) ?></textarea>
                            </label>
                            <label>
                                <span>Replace proof files (optional)</span>
                                <input type="file" name="proof_files[]" multiple accept=".jpg,.jpeg,.png,.pdf">
                            </label>
                            <button class="btn btn-primary" type="submit">Resubmit weekly report</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    <?php if (!($canSubmitReports ?? false)): ?>
    <section class="card locked-card"><h2>Submit Daily Time Record</h2><p class="muted"><?= e($reportLockMessage ?? 'DTR is locked until your OJT deployment starts.') ?></p><button class="btn btn-primary" type="button" disabled>Submit DTR Locked</button></section>
    <section class="card locked-card"><h2>Submit Weekly Report</h2><p class="muted"><?= e($reportLockMessage ?? 'Weekly reports are locked until your OJT deployment starts.') ?></p><button class="btn btn-primary" type="button" disabled>Weekly Report Locked</button></section>
    <?php else: ?>
    <?php $dtrDraft = $dtrDraft ?? []; ?>
    <?php $dtrDayType = normalize_dtr_day_type($dtrDraft['day_type'] ?? 'full'); ?>
    <?php
    $renderDtrTimeLock = static function (
        string $label,
        string $name,
        ?string $value,
        bool $locked,
        string $applyLabel,
        string $editLabel
    ): void {
        ?>
        <div class="dtr-time-lock" data-time-lock-group data-saved-locked="<?= $locked ? '1' : '0' ?>">
            <div class="dtr-time-lock-top">
                <span class="dtr-time-lock-label"><?= e($label) ?></span>
                <span class="dtr-time-lock-badge" data-time-lock-badge <?= $locked ? '' : 'hidden' ?>>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z"/></svg>
                    Saved
                </span>
            </div>
            <div class="dtr-time-field-group">
                <input type="hidden" name="<?= e($name) ?>" value="<?= e($value ?? '') ?>" data-lockable-time>
                <button class="dtr-time-picker-trigger" type="button" data-time-picker-trigger aria-label="<?= e($label) ?>">
                    <span class="dtr-time-display" data-time-display>--:-- --</span>
                    <span class="dtr-time-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 1 0 20a10 10 0 0 1 0-20Zm0 2a8 8 0 1 0 0 16a8 8 0 0 0 0-16Zm0 2.75a1 1 0 0 1 1 1v3.67l2.28 2.28a1 1 0 1 1-1.42 1.42l-2.57-2.58A1 1 0 0 1 11 11.83V7.75a1 1 0 0 1 1-1Z"/></svg>
                    </span>
                </button>
                <button class="dtr-time-lock-btn" type="button" data-time-lock-toggle data-apply-label="<?= e($applyLabel) ?>" data-edit-label="<?= e($editLabel) ?>" aria-label="<?= e($applyLabel) ?>">
                    <span class="dtr-time-lock-btn-icon dtr-time-lock-btn-icon--save" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z"/></svg>
                    </span>
                    <span class="dtr-time-lock-btn-icon dtr-time-lock-btn-icon--undo" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12.5 8c-2.65 0-5.05 1.04-6.9 2.9L2 7v9h9l-3.62-3.62c1.39-1.39 3.3-2.26 5.42-2.26 4.14 0 7.5 3.36 7.5 7.5h2c0-5.24-4.26-9.5-9.5-9.5Z"/></svg>
                    </span>
                    <span class="dtr-time-lock-btn-text"><?= e($applyLabel) ?></span>
                </button>
            </div>
        </div>
        <?php
    };
    ?>
    <section class="card dtr-form-card">
        <div class="dtr-form-head">
            <div>
                <h2>Submit Daily Time Record</h2>
                <p class="muted" data-dtr-form-intro>Record your morning and afternoon attendance, then describe the tasks you completed today.</p>
            </div>
            <span class="dtr-form-badge">OJT Attendance</span>
        </div>
        <form method="post" class="form js-validate dtr-form" data-dtr-lock-flow data-confirm-submit="Submit this DTR? Please verify the date, session times, and tasks before submitting." data-confirm-title="Submit DTR" data-confirm-ok="Submit DTR">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="student_add_dtr">

            <div class="dtr-date-section">
                <span class="dtr-field-label">Work Date</span>
                <div class="dtr-date-row">
                    <span class="filter-date-picker form-date-picker is-placeholder" data-date-required="1">
                        <input type="hidden" name="work_date" value="<?= e($dtrDraft['work_date'] ?? '') ?>">
                        <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select work date">
                            <span class="filter-date-value">mm/dd/yyyy</span>
                            <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                        </button>
                        <div class="filter-date-panel" hidden></div>
                    </span>
                    <button class="btn btn-ghost dtr-date-today-btn" type="button" data-dtr-date-today>Use today</button>
                </div>
            </div>

            <div class="dtr-day-type-section">
                <span class="dtr-field-label">Day Type</span>
                <p class="muted dtr-day-type-hint">Choose what kind of work day this is. Only the required time sessions will be shown.</p>
                <select name="day_type" class="dtr-day-type-select" data-dtr-day-type required>
                    <?php foreach (dtr_day_types() as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= $dtrDayType === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="dtr-sessions" data-dtr-sessions>
                <section class="dtr-session dtr-session--morning" data-dtr-session="morning">
                    <header class="dtr-session-head">
                        <div class="dtr-session-title-wrap">
                            <span class="dtr-session-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 9c1.65 0 3 1.35 3 3s-1.35 3-3 3-3-1.35-3-3 1.35-3 3-3m0-2c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2v-2H2v2zm18 0h2v-2h-2v2zM11 2v2h2V2h-2zm0 18v2h2v-2h-2zM5.99 4.58 7.4 3.17 4.58.76 3.17 2.17l1.41 1.41zm12.37 12.37 1.41-1.41-1.41-1.41-1.41 1.41 1.41 1.41zM5.99 19.42l-1.41 1.41 1.41 1.41 1.41-1.41-1.41-1.41zm12.37-12.37-1.41-1.41-1.41 1.41 1.41 1.41 1.41-1.41z"/></svg>
                            </span>
                            <div>
                                <h3 class="dtr-session-title">Morning</h3>
                                <p class="dtr-session-window">8:00 AM – 12:00 NN</p>
                            </div>
                        </div>
                        <span class="dtr-session-chip">AM</span>
                    </header>
                    <div class="dtr-session-fields">
                        <?php $renderDtrTimeLock(
                            'Time In',
                            'morning_time_in',
                            $dtrDraft['morning_time_in'] ?? '',
                            dtr_field_is_locked($dtrDraft, 'morning_time_in'),
                            'Save',
                            'Undo'
                        ); ?>
                        <?php $renderDtrTimeLock(
                            'Time Out',
                            'morning_time_out',
                            $dtrDraft['morning_time_out'] ?? '',
                            dtr_field_is_locked($dtrDraft, 'morning_time_out'),
                            'Save',
                            'Undo'
                        ); ?>
                    </div>
                </section>

                <section class="dtr-session dtr-session--afternoon" data-dtr-session="afternoon">
                    <header class="dtr-session-head">
                        <div class="dtr-session-title-wrap">
                            <span class="dtr-session-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19.35 10.04A7.49 7.49 0 0 0 12 4C9.11 4 6.6 5.64 5.35 8.04A5.994 5.994 0 0 0 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/></svg>
                            </span>
                            <div>
                                <h3 class="dtr-session-title">Afternoon</h3>
                                <p class="dtr-session-window">1:00 PM – 5:00 PM</p>
                            </div>
                        </div>
                        <span class="dtr-session-chip">PM</span>
                    </header>
                    <div class="dtr-session-fields">
                        <?php $renderDtrTimeLock(
                            'Time In',
                            'afternoon_time_in',
                            $dtrDraft['afternoon_time_in'] ?? '',
                            dtr_field_is_locked($dtrDraft, 'afternoon_time_in'),
                            'Save',
                            'Undo'
                        ); ?>
                        <?php $renderDtrTimeLock(
                            'Time Out',
                            'afternoon_time_out',
                            $dtrDraft['afternoon_time_out'] ?? '',
                            dtr_field_is_locked($dtrDraft, 'afternoon_time_out'),
                            'Save',
                            'Undo'
                        ); ?>
                    </div>
                </section>
            </div>

            <div class="dtr-tasks-section">
                <span class="dtr-field-label" data-dtr-tasks-label>Tasks Done</span>
                <p class="muted dtr-tasks-hint" data-dtr-tasks-hint>Summarize your practicum activities for this work day.</p>
                <div class="dtr-textarea-wrap">
                    <textarea required maxlength="500" name="tasks_done" rows="5" placeholder="Describe the tasks you completed today..." data-dtr-tasks data-dtr-tasks-placeholder="Describe the tasks you completed today..."></textarea>
                </div>
            </div>

            <div class="dtr-submit-summary" data-dtr-submit-summary hidden></div>
            <p class="muted dtr-submit-hint" data-dtr-submit-hint hidden>To submit: work date, required times, and tasks.</p>
            <button class="btn btn-primary btn-dtr-submit" type="submit" data-dtr-submit>
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z"/></svg>
                <span class="btn-text">Submit DTR</span>
                <span class="spinner"></span>
            </button>
        </form>
    </section>
    <section class="card weekly-report-card">
        <div class="wr-form-head">
            <div>
                <h2>Weekly Task Report</h2>
                <p class="muted">Submit your weekly accomplishments and proof of work.</p>
            </div>
            <span class="wr-form-badge">Weekly Report</span>
        </div>
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
                        <div class="wr-date-range" data-wr-date-range>
                            <div class="wr-date-field">
                                <span class="wr-date-field-label">Start date</span>
                                <?php render_form_date_picker('date_covered_start', '', ['data-wr-date' => 'start']); ?>
                            </div>
                            <span class="wr-date-sep" aria-hidden="true">to</span>
                            <div class="wr-date-field">
                                <span class="wr-date-field-label">End date</span>
                                <?php render_form_date_picker('date_covered_end', '', ['data-wr-date' => 'end']); ?>
                            </div>
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
