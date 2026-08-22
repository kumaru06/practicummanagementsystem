<?php
$timelineEntries = $timelineEntries ?? build_student_timeline_entries($dtrs ?? [], $weeklyReports ?? []);
$timelineStats = $timelineStats ?? [
    'dtrCount' => count($dtrs ?? []),
    'weeklyCount' => count($weeklyReports ?? []),
    'totalHours' => array_sum(array_map(static fn(array $d): float => (float)($d['hours'] ?? 0), $dtrs ?? [])),
];
$totalEntries = count($timelineEntries);
$isEmpty = $totalEntries === 0;
?>

<style>
/* Timeline page layout overrides (desktop chip only) */
@media (min-width: 721px) {
    body.role-student .topbar-toolbar,
    body.role-student .user-chip,
    body.role-student .user-chip-link {
        border-radius: 0 !important;
    }
    body.role-student .top-actions {
        flex: 0 1 auto !important;
        min-width: 0 !important;
        max-width: min(520px, 72%) !important;
    }
    body.role-student .app-user-identity--chip .app-user-identity__meta {
        max-width: 220px !important;
        min-width: 0 !important;
        overflow: hidden !important;
    }
    body.role-student .app-user-identity--chip .app-user-identity__meta strong,
    body.role-student .app-user-identity--chip .app-user-identity__meta small {
        display: block !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        word-break: normal !important;
    }
}
</style>

<style>
.student-timeline-page,
.student-timeline-page * {
    box-sizing: border-box;
}

.student-timeline-page .st-stats {
    display: grid !important;
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 12px !important;
    margin-bottom: 14px !important;
}

.student-timeline-page .st-stat-card {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    min-width: 0 !important;
    padding: 14px 16px !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 5px !important;
    background: #fff !important;
    box-shadow: none !important;
}

.student-timeline-page .st-stat-card .st-stat-icon {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 40px !important;
    height: 40px !important;
    min-width: 40px !important;
    min-height: 40px !important;
    margin: 0 !important;
    padding: 0 !important;
    border-radius: 5px !important;
    flex-shrink: 0 !important;
    overflow: hidden !important;
    line-height: 0 !important;
    font-size: 0 !important;
    font-weight: 400 !important;
    color: inherit !important;
}

.student-timeline-page .st-stat-card .st-stat-icon svg {
    width: 18px !important;
    height: 18px !important;
    max-width: 18px !important;
    max-height: 18px !important;
    margin: 0 !important;
    display: block !important;
    flex-shrink: 0 !important;
}

.student-timeline-page .st-stat-icon--entries { background: #fff1f2 !important; color: #be123c !important; }
.student-timeline-page .st-stat-icon--dtr { background: #eff6ff !important; color: #1d4ed8 !important; }
.student-timeline-page .st-stat-icon--weekly { background: #f5f3ff !important; color: #6d28d9 !important; }
.student-timeline-page .st-stat-icon--hours { background: #ecfdf5 !important; color: #047857 !important; }

.student-timeline-page .st-stat-card strong {
    display: block !important;
    font-size: 1.3rem !important;
    line-height: 1.1 !important;
    color: #0f172a !important;
}
.student-timeline-page .st-stat-card > div > span {
    display: block !important;
    margin-top: 2px !important;
    color: #64748b !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
}

.student-timeline-page .st-timeline-box {
    display: flex !important;
    flex-direction: column !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 5px !important;
    background: #fff !important;
    box-shadow: none !important;
    overflow: hidden !important;
    transform: none !important;
}

.student-timeline-page .st-timeline-box:hover {
    transform: none !important;
    box-shadow: none !important;
}

.student-timeline-page .st-box-head {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 12px !important;
    padding: 16px 18px !important;
    border-bottom: 1px solid #eef2f6 !important;
    background: #f8fafc !important;
}

.student-timeline-page .st-box-head h2 {
    margin: 0 0 2px !important;
    font-size: 1.05rem !important;
    color: #0f172a !important;
}

.student-timeline-page .st-box-head .muted {
    display: block !important;
    font-size: 0.82rem !important;
}

.student-timeline-page .st-filters {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 8px !important;
}

.student-timeline-page .st-filter {
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 8px 12px !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 5px !important;
    background: #fff !important;
    color: #475569 !important;
    font: inherit !important;
    font-size: 0.84rem !important;
    font-weight: 700 !important;
    cursor: pointer !important;
}

.student-timeline-page .st-filter.is-active {
    border-color: #8B1A1A !important;
    background: #8B1A1A !important;
    color: #fff !important;
    box-shadow: none !important;
}

.student-timeline-page .st-filter-count {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-width: 22px !important;
    height: 20px !important;
    padding: 0 6px !important;
    border-radius: 5px !important;
    background: rgba(15, 23, 42, 0.08) !important;
    font-size: 0.72rem !important;
    line-height: 1 !important;
}

.student-timeline-page .st-filter.is-active .st-filter-count {
    background: rgba(255, 255, 255, 0.2) !important;
}

.student-timeline-page .st-timeline-scroll {
    max-height: min(58vh, 560px) !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    padding: 16px 18px !important;
    background: #f8fafc !important;
    border-top: 0 !important;
}

.student-timeline-page .st-timeline-scroll::-webkit-scrollbar {
    width: 8px !important;
}
.student-timeline-page .st-timeline-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1 !important;
    border-radius: 5px !important;
}

.student-timeline-page .st-timeline {
    display: grid !important;
    gap: 14px !important;
    margin: 0 !important;
    position: relative !important;
}

.student-timeline-page .st-entry-card,
.student-timeline-page .st-type-badge,
.student-timeline-page .st-hours-pill,
.student-timeline-page .st-week-pill,
.student-timeline-page .st-month-divider span,
.student-timeline-page .st-timeline-empty {
    border-radius: 5px !important;
}

.student-timeline-page .st-entry-card {
    padding: 14px 16px !important;
    border: 1px solid #cbd5e1 !important;
    background: #fff !important;
    box-shadow: none !important;
    transform: none !important;
}

.student-timeline-page .st-timeline-item:hover .st-entry-card {
    border-color: #8B1A1A !important;
    box-shadow: none !important;
    transform: none !important;
}

@media (max-width: 980px) {
    .student-timeline-page .st-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
}
@media (max-width: 720px) {
    .student-timeline-page {
        gap: 12px !important;
    }
    .student-timeline-page .st-stats {
        gap: 10px !important;
        margin-bottom: 10px !important;
    }
    .student-timeline-page .st-stat-card {
        padding: 12px 14px !important;
    }
    .student-timeline-page .st-box-head,
    .student-timeline-page .st-filter-row {
        flex-wrap: wrap !important;
        gap: 10px !important;
    }
    .student-timeline-page .st-filter,
    .student-timeline-page .st-filter-btn {
        min-height: 40px;
    }
}
@media (max-width: 560px) {
    .student-timeline-page .st-stats {
        grid-template-columns: 1fr !important;
    }
    body.role-student .top-actions {
        max-width: none !important;
    }
}
</style>

<div class="student-timeline-page">
    <div class="st-stats" aria-label="Timeline summary">
        <article class="st-stat-card">
            <div class="st-stat-icon st-stat-icon--entries" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M7 3a2 2 0 0 1 2 2v1h6V5a2 2 0 1 1 4 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm0 5v11h10V8H7Zm2 2h6v2H9v-2Zm0 4h4v2H9v-2Z"/></svg>
            </div>
            <div>
                <strong><?= (int)$totalEntries ?></strong>
                <span>Total entries</span>
            </div>
        </article>
        <article class="st-stat-card">
            <div class="st-stat-icon st-stat-icon--dtr" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2Zm0 16H5V8h14v11ZM7 10h5v5H7v-5Z"/></svg>
            </div>
            <div>
                <strong><?= (int)$timelineStats['dtrCount'] ?></strong>
                <span>Daily records</span>
            </div>
        </article>
        <article class="st-stat-card">
            <div class="st-stat-icon st-stat-icon--weekly" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6Zm2 16H8v-2h8v2Zm0-4H8v-2h8v2Zm-3-5V3.5L18.5 9H13Z"/></svg>
            </div>
            <div>
                <strong><?= (int)$timelineStats['weeklyCount'] ?></strong>
                <span>Weekly reports</span>
            </div>
        </article>
        <article class="st-stat-card">
            <div class="st-stat-icon st-stat-icon--hours" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2Zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8Zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7Z"/></svg>
            </div>
            <div>
                <strong><?= e(number_format((float)$timelineStats['totalHours'], 1)) ?></strong>
                <span>Total hours</span>
            </div>
        </article>
    </div>

    <section class="st-timeline-box">
        <div class="st-box-head">
            <div>
                <h2>Activity Timeline</h2>
                <span class="muted">Daily time records and weekly submissions</span>
            </div>
            <?php if (!$isEmpty): ?>
            <div class="st-filters" role="tablist" aria-label="Filter timeline">
                <button class="st-filter is-active" type="button" role="tab" aria-selected="true" data-st-filter="all">All <span class="st-filter-count"><?= (int)$totalEntries ?></span></button>
                <button class="st-filter" type="button" role="tab" aria-selected="false" data-st-filter="dtr">Daily Records <span class="st-filter-count"><?= (int)$timelineStats['dtrCount'] ?></span></button>
                <button class="st-filter" type="button" role="tab" aria-selected="false" data-st-filter="weekly">Weekly Reports <span class="st-filter-count"><?= (int)$timelineStats['weeklyCount'] ?></span></button>
            </div>
            <?php endif; ?>
        </div>

        <div class="st-timeline-scroll">
            <div class="timeline st-timeline<?= $isEmpty ? ' is-empty' : '' ?>" data-st-timeline>
                <?php if ($isEmpty): ?>
                    <div class="st-timeline-empty">
                        <svg viewBox="0 0 24 24" width="40" height="40" aria-hidden="true"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm12 8H5v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Zm3 6h4v2H8v-2Zm6 0h2v2h-2v-2Zm-6 4h8v2H8v-2Z"/></svg>
                        <p>No practicum activity submitted yet.</p>
                        <a class="btn btn-small" href="<?= e(route_url('student.records')) ?>">Submit a record</a>
                    </div>
                <?php else: ?>
                    <?php $currentMonth = ''; ?>
                    <?php foreach ($timelineEntries as $entry): ?>
                        <?php
                        $monthKey = '';
                        $sortDate = trim((string)($entry['sort_date'] ?? ''));
                        if ($sortDate !== '') {
                            try {
                                $monthDt = new DateTimeImmutable($sortDate);
                                if ((int)$monthDt->format('Y') >= 1990) {
                                    $monthKey = $monthDt->format('F Y');
                                }
                            } catch (Throwable) {
                                $monthKey = '';
                            }
                        }
                        ?>
                        <?php if ($monthKey !== '' && $monthKey !== $currentMonth): ?>
                            <?php $currentMonth = $monthKey; ?>
                            <div class="st-month-divider" data-st-month><span><?= e($monthKey) ?></span></div>
                        <?php endif; ?>

                        <?php if ($entry['type'] === 'dtr'): ?>
                            <?php $d = $entry['data']; ?>
                            <article
                                class="timeline-item st-timeline-item"
                                data-type="dtr"
                                data-detail="<?= e($d['work_date'] . '|' . format_dtr_schedule($d) . '|' . $d['hours'] . ' hours|' . $d['tasks_done']) ?>"
                            >
                                <span class="timeline-dot st-timeline-dot st-timeline-dot--dtr" aria-hidden="true"></span>
                                <div class="timeline-card st-entry-card">
                                    <div class="st-entry-card-head">
                                        <span class="st-type-badge st-type-badge--dtr">Daily Record</span>
                                        <span class="st-hours-pill"><?= e((string)$d['hours']) ?> hrs</span>
                                    </div>
                                    <strong><?= e(format_timeline_date($d['work_date'])) ?></strong>
                                    <small><?= e(format_dtr_schedule($d)) ?></small>
                                    <p><?= e($d['tasks_done']) ?></p>
                                </div>
                            </article>
                        <?php else: ?>
                            <?php
                            $r = $entry['data'];
                            $weekNo = (int)($r['week_no'] ?? 0);
                            $weeklyWhen = timeline_usable_date(
                                (string)($r['submitted_at'] ?? ''),
                                (string)($r['created_at'] ?? ''),
                                (string)($r['date_covered_start'] ?? '')
                            );
                            $dateCovered = '';
                            if (!empty($r['date_covered_start']) && !empty($r['date_covered_end'])) {
                                $dateCovered = date('M d', strtotime((string)$r['date_covered_start']))
                                    . ' – '
                                    . date('M d, Y', strtotime((string)$r['date_covered_end']));
                            }
                            $reportSummary = $r['file_path']
                                ? 'PDF attachment available'
                                : (string)($r['report_text'] ?? $r['accomplishments'] ?? '');
                            ?>
                            <article
                                class="timeline-item st-timeline-item"
                                data-type="weekly"
                                data-detail="<?= e('Week ' . $weekNo . '|' . ($dateCovered !== '' ? $dateCovered : ($weeklyWhen !== '' ? format_timeline_date($weeklyWhen) : '')) . '|Weekly Report|' . $reportSummary) ?>"
                            >
                                <span class="timeline-dot st-timeline-dot st-timeline-dot--weekly" aria-hidden="true"></span>
                                <div class="timeline-card st-entry-card st-entry-card--weekly">
                                    <div class="st-entry-card-head">
                                        <span class="st-type-badge st-type-badge--weekly">Weekly Report</span>
                                        <span class="st-week-pill">Week <?= $weekNo ?></span>
                                    </div>
                                    <strong>Weekly Report <?= $weekNo ?></strong>
                                    <?php if ($dateCovered !== ''): ?>
                                        <small><?= e($dateCovered) ?></small>
                                    <?php elseif ($weeklyWhen !== ''): ?>
                                        <small>Submitted <?= e(format_timeline_date($weeklyWhen)) ?></small>
                                    <?php endif; ?>
                                    <p>
                                        <?php if (!empty($r['file_path'])): ?>
                                            <a class="btn btn-small st-view-pdf" target="_blank" href="<?= e(asset($r['file_path'])) ?>">View PDF</a>
                                        <?php else: ?>
                                            <?= e($r['report_text'] ?? $r['accomplishments'] ?? '') ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </article>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
