<?php require __DIR__ . '/_submissions_helpers.php'; ?>

<div class="ps-v2" data-ps-submissions>
    <div class="ps-v2-layout">
        <aside class="ps-v2-sidebar">
            <div class="ps-v2-sidebar-head">
                <h2>Students</h2>
                <span class="ps-v2-sidebar-count"><?= count($studentSummaries) ?></span>
            </div>
            <?php if (empty($studentSummaries)): ?>
                <p class="muted ps-v2-empty">No students assigned to your organization yet.</p>
            <?php else: ?>
                <div class="ps-v2-sidebar-search">
                    <label class="ps-v2-search-field">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input type="search"
                               class="ps-v2-student-search"
                               data-ps-student-search
                               placeholder="Search by name or student ID..."
                               aria-label="Search students by name or student ID">
                    </label>
                </div>
                <div class="ps-v2-student-list-panel">
                    <ul class="ps-v2-student-list">
                        <?php foreach ($studentSummaries as $row): ?>
                            <?php
                            $isSelected = $selectedStudent && (int)$selectedStudent['student_id'] === (int)$row['student_id'];
                            $isLocked = empty($row['reports_unlocked']);
                            $pendingTotal = $isLocked ? 0 : (int)$row['pending_dtr'] + (int)$row['pending_weekly'];
                            ?>
                            <li data-ps-student-item data-search="<?= e(strtolower($row['student_name'] . ' ' . $row['student_no'])) ?>"<?= $isLocked ? ' data-ps-student-locked="1"' : '' ?>>
                                <a class="ps-v2-student-card <?= $isSelected ? 'is-selected' : '' ?><?= $isLocked ? ' is-locked' : '' ?>"
                                   data-ps-ajax
                                   data-student-id="<?= (int)$row['student_id'] ?>"
                                   href="<?= e($submissionUrl(['student_id' => (int)$row['student_id']])) ?>">
                                    <span class="ps-v2-student-avatar"><?= e(strtoupper(substr($row['student_name'], 0, 1))) ?></span>
                                    <span class="ps-v2-student-info">
                                        <strong><?= e($row['student_name']) ?></strong>
                                        <?php $studentMeta = $row['student_no'] . '  ·  ' . $row['course']; ?>
                                        <span class="ps-v2-student-meta-marquee" data-ps-meta-marquee title="<?= e($studentMeta) ?>">
                                            <span class="ps-v2-student-meta-track">
                                                <small><?= e($studentMeta) ?></small>
                                                <small aria-hidden="true"><?= e($studentMeta) ?></small>
                                            </span>
                                        </span>
                                    </span>
                                    <?php if ($isLocked): ?>
                                        <span class="ps-v2-student-lock" title="Submissions locked">Locked</span>
                                    <?php elseif ($pendingTotal > 0): ?>
                                        <span class="ps-v2-student-pending" title="Pending submissions"><?= $pendingTotal ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="ps-v2-search-not-found" data-ps-student-search-empty hidden>
                        <strong>No result found</strong>
                        <p class="muted">Try a different name or student ID.</p>
                    </div>
                </div>
            <?php endif; ?>
        </aside>

        <main class="ps-v2-main" data-ps-v2-detail>
            <?php require __DIR__ . '/submissions_detail.php'; ?>
        </main>
    </div>
</div>
