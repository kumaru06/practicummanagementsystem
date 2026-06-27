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
