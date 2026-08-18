<?php if (empty($__submissionsHelpersLoaded)) {
    require __DIR__ . '/_submissions_helpers.php';
} ?>

<?php if (!$selectedStudent): ?>
    <section class="ps-v2-empty-card ps-v2-empty-card--center">
        <div class="ps-v2-empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4Zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4Z"/></svg>
        </div>
        <h3>Select a student</h3>
        <p class="muted">Choose a student from the list to review pending, approved, and rejected submissions.</p>
    </section>
<?php else: ?>
    <?php if (empty($selectedStudent['reports_unlocked'])): ?>
        <section class="ps-v2-empty-card ps-v2-empty-card--center ps-v2-locked-card">
            <div class="ps-v2-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <h3>Submissions locked</h3>
            <p class="muted"><?= e($selectedStudent['reports_lock_message'] ?? 'DTR and weekly reports are not available for review yet.') ?></p>
            <p class="muted">You can still manage deployment and orientation for this student in the <a href="<?= e(route_url('partner.portal', ['enrollment' => (int)($selectedStudent['enrollment_id'] ?? 0)])) ?>">Portal</a>.</p>
        </section>
    <?php else: ?>
    <section class="ps-v2-student-banner">
        <div class="ps-v2-student-banner-copy">
            <span class="ps-v2-eyebrow">Reviewing</span>
            <h2><?= e($selectedStudent['student_name']) ?></h2>
            <p class="muted"><?= e($selectedStudent['student_no']) ?> · <?= e($selectedStudent['course']) ?> · <?= e($selectedStudent['year_level']) ?></p>
        </div>
        <div class="ps-v2-status-pills">
            <div class="ps-v2-status-pill ps-v2-status-pill--pending">
                <strong><?= $statusCounts['pending'] ?></strong>
                <span>Pending</span>
            </div>
            <div class="ps-v2-status-pill ps-v2-status-pill--approved">
                <strong><?= $statusCounts['approved'] ?></strong>
                <span>Approved</span>
            </div>
            <div class="ps-v2-status-pill ps-v2-status-pill--rejected">
                <strong><?= $statusCounts['rejected'] ?></strong>
                <span>Rejected</span>
            </div>
        </div>
    </section>

    <section class="ps-v2-panel">
        <div class="ps-v2-tabs" role="tablist" aria-label="Submission type">
            <div class="ps-v2-tab-wrap <?= $activeTab === 'dtr' ? 'is-active' : '' ?>">
                <a class="ps-v2-tab <?= $activeTab === 'dtr' ? 'is-active' : '' ?>"
                   data-ps-ajax
                   data-ps-tab="dtr"
                   href="<?= e($submissionUrl(['student_id' => (int)$selectedStudent['student_id'], 'tab' => 'dtr'])) ?>">
                    <span>Daily Time Records</span>
                    <?php $renderTabMeta($dtrCounts, $activeTab === 'dtr'); ?>
                </a>
                <?php if ($activeTab === 'dtr'): ?>
                    <?php $renderStatusFilter($dtrCounts); ?>
                <?php endif; ?>
            </div>
            <div class="ps-v2-tab-wrap <?= $activeTab === 'weekly' ? 'is-active' : '' ?>">
                <a class="ps-v2-tab <?= $activeTab === 'weekly' ? 'is-active' : '' ?>"
                   data-ps-ajax
                   data-ps-tab="weekly"
                   href="<?= e($submissionUrl(['student_id' => (int)$selectedStudent['student_id'], 'tab' => 'weekly'])) ?>">
                    <span>Weekly Reports</span>
                    <?php $renderTabMeta($weeklyCounts, $activeTab === 'weekly'); ?>
                </a>
                <?php if ($activeTab === 'weekly'): ?>
                    <?php $renderStatusFilter($weeklyCounts); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="ps-v2-panel-body">
            <?php if ($statusCounts['pending'] > 0 && in_array($statusFilter, ['pending', 'all'], true)): ?>
                <div class="ps-v2-bulk-bar">
                    <p class="muted">Review all <?= (int)$statusCounts['pending'] ?> pending <?= $activeTab === 'weekly' ? 'weekly reports' : 'daily time records' ?> at once.</p>
                    <div class="ps-v2-bulk-actions">
                        <form method="post" class="inline" onsubmit="return confirm('Approve all pending <?= $activeTab === 'weekly' ? 'weekly reports' : 'daily time records' ?> for this student?');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="<?= $activeTab === 'weekly' ? 'partner_bulk_review_weekly' : 'partner_bulk_review_dtr' ?>">
                            <input type="hidden" name="student_id" value="<?= (int)$selectedStudent['student_id'] ?>">
                            <input type="hidden" name="decision" value="approved">
                            <button class="btn btn-small btn-approve" type="submit">Approve All Pending</button>
                        </form>
                        <form method="post" class="inline ps-v2-bulk-reject-form" data-ps-bulk-reject-form onsubmit="return window.partnerBulkRejectConfirm ? window.partnerBulkRejectConfirm(this) : confirm('Reject all pending <?= $activeTab === 'weekly' ? 'weekly reports' : 'daily time records' ?> for this student?');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="<?= $activeTab === 'weekly' ? 'partner_bulk_review_weekly' : 'partner_bulk_review_dtr' ?>">
                            <input type="hidden" name="student_id" value="<?= (int)$selectedStudent['student_id'] ?>">
                            <input type="hidden" name="decision" value="rejected">
                            <input type="text" name="notes" class="ps-v2-bulk-notes" placeholder="Required rejection notes..." maxlength="500" required data-ps-bulk-notes>
                            <button class="btn btn-small btn-reject" type="submit">Reject All Pending</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (empty($activeRecords)): ?>
                <p class="muted ps-v2-empty">No <?= $activeTab === 'weekly' ? 'weekly reports' : 'daily time records' ?> submitted yet.</p>
            <?php elseif (empty($filteredRecords)): ?>
                <p class="muted ps-v2-empty"><?= e($emptyFilterMessage) ?></p>
            <?php else: ?>
                <div class="ps-v2-records-panel ps-v2-records-panel--<?= e($statusFilter) ?>">
                    <div class="ps-v2-status-scroll">
                        <div class="ps-v2-record-list">
                            <?php
                            $recordRenderer = $activeTab === 'weekly' ? $renderWeeklyRecord : $renderDtrRecord;
                            foreach ($filteredRecords as $record) {
                                $recordRenderer($record, $selectedStudent);
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
<?php endif; ?>
