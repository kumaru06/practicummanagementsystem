<?php
$studentSummaries = $studentSummaries ?? [];
$selectedStudent = $selectedStudent ?? null;
$studentDtrs = $studentDtrs ?? [];
$studentWeeklies = $studentWeeklies ?? [];
$activeTab = $activeTab ?? 'dtr';

$statusBadge = static function (?string $status): string {
    $status = strtolower(trim((string)$status));
    if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $status = 'pending';
    }
    $cls = match ($status) {
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
        default => 'badge-warn',
    };
    return '<span class="ps-badge ' . $cls . '">' . strtoupper($status) . '</span>';
};
?>

<div class="ps-page">
    <?php if (!$company): ?>
        <section class="card">
            <h2>No Industry Partner profile found.</h2>
            <p class="muted">Please contact the administrator to set up your account.</p>
        </section>
    <?php else: ?>

    <section class="card ps-hero-card">
        <div>
            <span class="eyebrow">Industry Partner</span>
            <h2><?= e($company['name']) ?></h2>
            <p class="muted">Review and approve student daily time records and weekly reports before they reflect in the OJT Coordinator's view.</p>
        </div>
    </section>

    <div class="ps-layout">
        <aside class="card ps-student-list">
            <div class="section-head">
                <h2>Students</h2>
                <span class="muted"><?= count($studentSummaries) ?> total</span>
            </div>
            <?php if (empty($studentSummaries)): ?>
                <p class="muted ps-empty">No students assigned to your organization yet.</p>
            <?php else: ?>
                <ul class="ps-student-items">
                    <?php foreach ($studentSummaries as $row): ?>
                        <?php $isSelected = $selectedStudent && (int)$selectedStudent['student_id'] === (int)$row['student_id']; ?>
                        <?php $pendingTotal = (int)$row['pending_dtr'] + (int)$row['pending_weekly']; ?>
                        <li>
                            <a class="ps-student-card <?= $isSelected ? 'is-selected' : '' ?>"
                               href="<?= e(route_url('partner.submissions', ['student_id' => (int)$row['student_id']])) ?>">
                                <div class="ps-student-avatar"><?= e(strtoupper(substr($row['student_name'], 0, 1))) ?></div>
                                <div class="ps-student-info">
                                    <strong><?= e($row['student_name']) ?></strong>
                                    <small><?= e($row['student_no']) ?> · <?= e($row['course']) ?></small>
                                </div>
                                <?php if ($pendingTotal > 0): ?>
                                    <span class="ps-pending-pill" title="Pending submissions"><?= $pendingTotal ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </aside>

        <main class="ps-detail">
            <?php if (!$selectedStudent): ?>
                <section class="card ps-placeholder">
                    <div class="ps-placeholder-inner">
                        <svg viewBox="0 0 24 24" width="56" height="56"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4Zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4Z"/></svg>
                        <h3>Select a student</h3>
                        <p class="muted">Choose a student from the list to review their Daily Time Records and Weekly Reports.</p>
                    </div>
                </section>
            <?php else: ?>
                <section class="card ps-student-header">
                    <div>
                        <span class="eyebrow">Reviewing</span>
                        <h2><?= e($selectedStudent['student_name']) ?></h2>
                        <p class="muted"><?= e($selectedStudent['student_no']) ?> · <?= e($selectedStudent['course']) ?> <?= e($selectedStudent['year_level']) ?></p>
                    </div>
                    <div class="ps-quick-stats">
                        <div class="ps-stat">
                            <strong><?= (int)$selectedStudent['pending_dtr'] ?></strong>
                            <span class="muted">Pending DTR</span>
                        </div>
                        <div class="ps-stat">
                            <strong><?= (int)$selectedStudent['pending_weekly'] ?></strong>
                            <span class="muted">Pending Weekly</span>
                        </div>
                    </div>
                </section>

                <section class="card ps-tabs-card">
                    <div class="ps-tabs">
                        <a class="ps-tab <?= $activeTab === 'dtr' ? 'is-active' : '' ?>"
                           href="<?= e(route_url('partner.submissions', ['student_id' => (int)$selectedStudent['student_id'], 'tab' => 'dtr'])) ?>">
                            Daily Time Records <span class="ps-tab-count"><?= count($studentDtrs) ?></span>
                        </a>
                        <a class="ps-tab <?= $activeTab === 'weekly' ? 'is-active' : '' ?>"
                           href="<?= e(route_url('partner.submissions', ['student_id' => (int)$selectedStudent['student_id'], 'tab' => 'weekly'])) ?>">
                            Weekly Reports <span class="ps-tab-count"><?= count($studentWeeklies) ?></span>
                        </a>
                    </div>

                    <?php if ($activeTab === 'dtr'): ?>
                        <?php if (empty($studentDtrs)): ?>
                            <p class="muted ps-empty">No daily time records submitted yet.</p>
                        <?php else: ?>
                            <div class="ps-record-list">
                                <?php foreach ($studentDtrs as $d): ?>
                                    <article class="ps-record-item">
                                        <header class="ps-record-head">
                                            <div>
                                                <strong><?= e(date('M d, Y', strtotime($d['work_date']))) ?></strong>
                                                <small class="muted"><?= e($d['time_in']) ?> - <?= e($d['time_out']) ?> · <?= e((string)$d['hours']) ?> hrs</small>
                                            </div>
                                            <?= $statusBadge($d['verification_status']) ?>
                                        </header>
                                        <p class="ps-record-tasks"><?= nl2br(e($d['tasks_done'])) ?></p>
                                        <?php if (!empty($d['verification_notes'])): ?>
                                            <p class="ps-record-notes muted"><strong>Notes:</strong> <?= e($d['verification_notes']) ?></p>
                                        <?php endif; ?>
                                        <?php if ($d['verification_status'] === 'pending'): ?>
                                            <form method="post" class="ps-review-form">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="partner_review_dtr">
                                                <input type="hidden" name="dtr_id" value="<?= (int)$d['id'] ?>">
                                                <input type="hidden" name="student_id" value="<?= (int)$selectedStudent['student_id'] ?>">
                                                <input type="text" name="notes" placeholder="Optional review notes..." maxlength="500">
                                                <button class="btn btn-small btn-approve" type="submit" name="decision" value="approved">Approve</button>
                                                <button class="btn btn-small btn-reject" type="submit" name="decision" value="rejected">Reject</button>
                                            </form>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (empty($studentWeeklies)): ?>
                            <p class="muted ps-empty">No weekly reports submitted yet.</p>
                        <?php else: ?>
                            <div class="ps-record-list">
                                <?php foreach ($studentWeeklies as $w): ?>
                                    <article class="ps-record-item">
                                        <header class="ps-record-head">
                                            <div>
                                                <strong>Week <?= (int)$w['week_no'] ?></strong>
                                                <?php if (!empty($w['date_covered_start']) || !empty($w['date_covered_end'])): ?>
                                                    <small class="muted"><?= e(date('M d', strtotime($w['date_covered_start'] ?: $w['submitted_at']))) ?> - <?= e(date('M d, Y', strtotime($w['date_covered_end'] ?: $w['submitted_at']))) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <?= $statusBadge($w['verification_status']) ?>
                                        </header>
                                        <?php if (!empty($w['accomplishments'])): ?>
                                            <p class="ps-record-tasks"><?= nl2br(e($w['accomplishments'])) ?></p>
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
                                            <p class="ps-record-notes muted"><strong>Notes:</strong> <?= e($w['verification_notes']) ?></p>
                                        <?php endif; ?>
                                        <?php if ($w['verification_status'] === 'pending'): ?>
                                            <form method="post" class="ps-review-form">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="partner_review_weekly">
                                                <input type="hidden" name="weekly_id" value="<?= (int)$w['id'] ?>">
                                                <input type="hidden" name="student_id" value="<?= (int)$selectedStudent['student_id'] ?>">
                                                <input type="text" name="notes" placeholder="Optional review notes..." maxlength="500">
                                                <button class="btn btn-small btn-approve" type="submit" name="decision" value="approved">Approve</button>
                                                <button class="btn btn-small btn-reject" type="submit" name="decision" value="rejected">Reject</button>
                                            </form>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
    <?php endif; ?>
</div>
