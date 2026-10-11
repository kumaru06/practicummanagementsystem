<?php if (empty($__submissionsHelpersLoaded)) {
    require __DIR__ . '/_submissions_helpers.php';
} ?>

<?php if (!$selectedStudent): ?>
    <section class="ps-v2-empty-card">
        <div class="ps-v2-empty-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
        </div>
        <p class="ps-v2-kicker">Review desk</p>
        <h3>Select a student</h3>
        <p class="muted">Choose a student from the list to review pending, approved, and rejected submissions.</p>
    </section>
<?php else: ?>
    <?php if (empty($selectedStudent['reports_unlocked'])): ?>
        <section class="ps-v2-empty-card ps-v2-locked-card">
            <div class="ps-v2-empty-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            </div>
            <p class="ps-v2-kicker">Unavailable</p>
            <h3>Submissions locked</h3>
            <p class="muted"><?= e($selectedStudent['reports_lock_message'] ?? 'DTR and weekly reports are not available for review yet.') ?></p>
            <p class="muted">You can still manage deployment and orientation for this student in the <a href="<?= e(route_url('partner.portal', ['enrollment' => (int)($selectedStudent['enrollment_id'] ?? 0)])) ?>">Portal</a>.</p>
        </section>
    <?php else: ?>
    <?php
    $activeCounts = $activeTab === 'weekly' ? $weeklyCounts : $dtrCounts;
    $recordNoun = $activeTab === 'weekly' ? 'weekly reports' : 'daily time records';
    ?>
    <section class="ps-v2-desk">
        <header class="ps-v2-student-banner">
            <div class="ps-v2-student-banner-copy">
                <h2><?= e($selectedStudent['student_name']) ?></h2>
                <p class="ps-v2-student-program"><?= e($selectedStudent['course']) ?></p>
                <div class="ps-v2-student-facts">
                    <span class="ps-v2-fact">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 9h6M7 13h4"/></svg>
                        <?= e($selectedStudent['student_no']) ?>
                    </span>
                    <span class="ps-v2-fact">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c2 1.5 4.5 2 6 2s4-.5 6-2v-5"/></svg>
                        <?= e($selectedStudent['year_level']) ?>
                    </span>
                </div>
            </div>
            <div class="ps-v2-status-pills" aria-label="Submission totals">
                <div class="ps-v2-status-pill ps-v2-status-pill--pending">
                    <strong><?= (int)$statusCounts['pending'] ?></strong>
                    <span>Pending</span>
                </div>
                <div class="ps-v2-status-pill ps-v2-status-pill--approved">
                    <strong><?= (int)$statusCounts['approved'] ?></strong>
                    <span>Approved</span>
                </div>
                <div class="ps-v2-status-pill ps-v2-status-pill--rejected">
                    <strong><?= (int)$statusCounts['rejected'] ?></strong>
                    <span>Rejected</span>
                </div>
            </div>
        </header>

        <div class="ps-v2-tabs" role="tablist" aria-label="Submission type">
            <a class="ps-v2-tab <?= $activeTab === 'dtr' ? 'is-active' : '' ?>"
               role="tab"
               aria-selected="<?= $activeTab === 'dtr' ? 'true' : 'false' ?>"
               data-ps-ajax
               data-ps-tab="dtr"
               href="<?= e($submissionUrl(['student_id' => (int)$selectedStudent['student_id'], 'tab' => 'dtr'])) ?>">
                <span>Daily Time Records</span>
                <span class="ps-v2-tab-count"><?= (int)$dtrCounts['pending'] ?></span>
            </a>
            <a class="ps-v2-tab <?= $activeTab === 'weekly' ? 'is-active' : '' ?>"
               role="tab"
               aria-selected="<?= $activeTab === 'weekly' ? 'true' : 'false' ?>"
               data-ps-ajax
               data-ps-tab="weekly"
               href="<?= e($submissionUrl(['student_id' => (int)$selectedStudent['student_id'], 'tab' => 'weekly'])) ?>">
                <span>Weekly Reports</span>
                <span class="ps-v2-tab-count"><?= (int)$weeklyCounts['pending'] ?></span>
            </a>
        </div>

        <?php $renderStatusFilter($activeCounts); ?>

        <div class="ps-v2-panel-body">
            <?php if (empty($activeRecords)): ?>
                <p class="muted ps-v2-empty">No <?= e($recordNoun) ?> submitted yet.</p>
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

        <?php if ($statusCounts['pending'] > 0 && in_array($statusFilter, ['pending', 'all'], true)): ?>
            <div class="ps-v2-bulk-bar">
                <p class="ps-v2-bulk-count"><strong><?= (int)$statusCounts['pending'] ?></strong> pending <?= e($recordNoun) ?></p>
                <form method="post" class="ps-v2-bulk-approve-form" onsubmit="return confirm('Approve all pending <?= e($recordNoun) ?> for this student?');">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $activeTab === 'weekly' ? 'partner_bulk_review_weekly' : 'partner_bulk_review_dtr' ?>">
                    <input type="hidden" name="student_id" value="<?= (int)$selectedStudent['student_id'] ?>">
                    <input type="hidden" name="decision" value="approved">
                    <button class="btn btn-small btn-approve ps-v2-bulk-approve" type="submit">Approve all</button>
                </form>
                <form method="post" class="ps-v2-bulk-reject-form" data-ps-bulk-reject-form onsubmit="return window.partnerBulkRejectConfirm ? window.partnerBulkRejectConfirm(this) : confirm('Reject all pending <?= e($recordNoun) ?> for this student?');">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $activeTab === 'weekly' ? 'partner_bulk_review_weekly' : 'partner_bulk_review_dtr' ?>">
                    <input type="hidden" name="student_id" value="<?= (int)$selectedStudent['student_id'] ?>">
                    <input type="hidden" name="decision" value="rejected">
                    <input type="text" name="notes" class="ps-v2-bulk-notes" placeholder="Rejection notes, required" maxlength="500" required data-ps-bulk-notes>
                    <button class="btn btn-small btn-reject ps-v2-bulk-reject" type="submit">Reject all</button>
                </form>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
<?php endif; ?>
