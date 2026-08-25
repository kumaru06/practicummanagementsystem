<?php
$terms = $terms ?? [];
$today = date('Y-m-d');

$enrichTerm = static function (array $term) use ($today): array {
    $label = trim((string)($term['term_label'] ?? ''));
    $start = trim((string)($term['term_start_date'] ?? ''));
    $end = trim((string)($term['term_end_date'] ?? ''));
    $hasDates = $start !== '' && $end !== '';
    $isActive = (int)($term['is_active'] ?? 1);

    $type = '—';
    if (preg_match('/\((1st|2nd|3rd)\s+Tri\)/i', $label, $m)) {
        $type = $m[1] . ' Tri';
    }

    if (!$hasDates) {
        $status = 'incomplete';
        $statusLabel = 'Incomplete';
    } elseif ($isActive === 1) {
        $status = 'active';
        $statusLabel = 'Active';
    } else {
        $status = 'inactive';
        $statusLabel = 'Inactive';
    }

    return [
        'id' => (int)($term['id'] ?? 0),
        'label' => $label,
        'type' => $type,
        'start' => $start,
        'end' => $end,
        'has_dates' => $hasDates,
        'is_active' => $isActive,
        'status' => $status,
        'status_label' => $statusLabel,
        'display_start' => $hasDates ? date('M j, Y', strtotime($start)) : '—',
        'display_end' => $hasDates ? date('M j, Y', strtotime($end)) : '—',
    ];
};

$termRows = array_map($enrichTerm, $terms);
$totalTerms = count($termRows);
$activeCount = count(array_filter($termRows, static fn ($t) => $t['status'] === 'active'));
$inactiveCount = count(array_filter($termRows, static fn ($t) => $t['status'] === 'inactive'));
$incompleteCount = count(array_filter($termRows, static fn ($t) => $t['status'] === 'incomplete'));
?>

<div class="admin-terms-page" data-admin-terms-page>
    <section class="atm-hero">
        <div class="atm-hero-main">
            <div class="atm-hero-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 0 0 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2Zm0 16H5V10h14v10Zm0-12H5V6h14v2Zm-7 5h5v5h-5v-5Z"/></svg>
            </div>
            <div class="atm-hero-copy">
                <h2>Academic Term</h2>
                <p>Manage academic terms for the system. Add, edit, and update academic terms.</p>
            </div>
        </div>
        <button class="btn btn-primary atm-add-btn" type="button" data-atm-open-modal>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Add Academic Term
        </button>
    </section>

    <div class="asu-stats-strip atm-stats">
        <article class="asu-stat-card asu-stat-total">
            <div class="asu-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <div class="asu-stat-body">
                <span>Total Terms</span>
                <strong><?= (int)$totalTerms ?></strong>
            </div>
        </article>
        <article class="asu-stat-card asu-stat-active">
            <div class="asu-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div class="asu-stat-body">
                <span>Active</span>
                <strong><?= (int)$activeCount ?></strong>
            </div>
        </article>
        <article class="asu-stat-card asu-stat-inactive">
            <div class="asu-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="10" y1="15" x2="10" y2="9"/>
                    <line x1="14" y1="15" x2="14" y2="9"/>
                </svg>
            </div>
            <div class="asu-stat-body">
                <span>Inactive</span>
                <strong><?= (int)$inactiveCount ?></strong>
            </div>
        </article>
        <article class="asu-stat-card atm-stat-incomplete">
            <div class="asu-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div class="asu-stat-body">
                <span>Incomplete</span>
                <strong><?= (int)$incompleteCount ?></strong>
            </div>
        </article>
    </div>

    <section class="card asu-directory-card atm-directory" data-admin-terms-directory>
        <div class="asu-directory-head">
            <div class="asu-directory-copy">
                <span class="asu-eyebrow">Term Directory</span>
                <h2>List of Academic Terms</h2>
            </div>
            <div class="asu-directory-badge" aria-live="polite">
                <strong><?= (int)$totalTerms ?></strong>
                <span>Listed</span>
            </div>
        </div>

        <div class="asu-toolbar atm-toolbar">
            <div class="asu-search-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                <input class="table-search asu-table-search" type="search" placeholder="Search terms..." autocomplete="off">
            </div>
            <label class="filter-select-wrap asu-filter-select">
                <select data-atm-status-filter data-select-label="Status" aria-label="Filter by status">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="incomplete">Incomplete</option>
                </select>
            </label>
        </div>

        <?php if ($totalTerms === 0): ?>
            <div class="asu-empty">
                <div class="asu-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path fill="currentColor" d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg>
                </div>
                <p class="asu-empty-title">No academic terms yet</p>
                <p class="asu-empty-sub">Add your first term so coordinators can enroll students.</p>
                <button class="btn btn-primary" type="button" data-atm-open-modal>Add Academic Term</button>
            </div>
        <?php else: ?>
            <div class="table-wrap asu-table-wrap">
                <table class="data-table no-row-details atm-terms-table" data-no-tools data-per-page="10">
                    <thead>
                        <tr>
                            <th data-sort>Term Name</th>
                            <th data-sort>Type</th>
                            <th data-sort>Start Date</th>
                            <th data-sort>End Date</th>
                            <th data-sort>Status</th>
                            <th class="atm-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($termRows as $row): ?>
                            <tr
                                data-term-status="<?= e($row['status']) ?>"
                                data-search="<?= e(strtolower(trim($row['label'] . ' ' . $row['type'] . ' ' . $row['status_label']))) ?>"
                            >
                                <td>
                                    <div class="atm-term-name">
                                        <span class="atm-term-name-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 0 0 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2Zm0 16H5V10h14v10Zm0-12H5V6h14v2Z"/></svg>
                                        </span>
                                        <span><?= e($row['label']) ?></span>
                                    </div>
                                </td>
                                <td><span class="atm-type-chip"><?= e($row['type']) ?></span></td>
                                <td><?= e($row['display_start']) ?></td>
                                <td><?= e($row['display_end']) ?></td>
                                <td>
                                    <span class="atm-status-pill is-<?= e($row['status']) ?>"><?= e($row['status_label']) ?></span>
                                </td>
                                <td class="atm-col-actions">
                                    <div class="atm-row-actions">
                                        <button
                                            class="atm-action-btn atm-action-btn--edit"
                                            type="button"
                                            title="Edit term"
                                            aria-label="Edit term"
                                            data-atm-edit
                                            data-term-id="<?= (int)$row['id'] ?>"
                                            data-term-label="<?= e($row['label']) ?>"
                                            data-term-start="<?= e($row['start']) ?>"
                                            data-term-end="<?= e($row['end']) ?>"
                                            data-term-active="<?= (int)$row['is_active'] ?>"
                                        >
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25ZM20.71 7.04a.996.996 0 0 0 0-1.41l-2.34-2.34a.996.996 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83Z"/></svg>
                                        </button>
                                        <form method="post" class="atm-delete-form" data-atm-delete data-term-label="<?= e($row['label']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="admin_delete_term">
                                            <input type="hidden" name="term_id" value="<?= (int)$row['id'] ?>">
                                            <button class="atm-action-btn atm-action-btn--delete" type="submit" title="Delete term" aria-label="Delete term">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <footer class="asu-table-footer">
                <div class="pagination"></div>
            </footer>
        <?php endif; ?>
    </section>

    <?php
    $completionReport = $completionReport ?? [
        'term' => '',
        'total' => 0,
        'before' => ['count' => 0, 'pct' => 0],
        'ontime' => ['count' => 0, 'pct' => 0],
        'beyond' => ['count' => 0, 'pct' => 0],
        'programs' => [],
    ];
    $completionTerm = (string)($completionTerm ?? ($completionReport['term'] ?? ''));
    $ocTotal = (int)($completionReport['total'] ?? 0);
    $ocBefore = (int)($completionReport['before']['count'] ?? 0);
    $ocOnTime = (int)($completionReport['ontime']['count'] ?? 0);
    $ocBeyond = (int)($completionReport['beyond']['count'] ?? 0);
    $ocBeforePct = (int)($completionReport['before']['pct'] ?? 0);
    $ocOnTimePct = (int)($completionReport['ontime']['pct'] ?? 0);
    $ocBeyondPct = (int)($completionReport['beyond']['pct'] ?? 0);
    $ocPrograms = $completionReport['programs'] ?? [];
    $ocStudentWord = static fn (int $count): string => $count === 1 ? 'Student' : 'Students';
    $ocDonutRadius = 40;
    $ocDonutCircumference = 2 * M_PI * $ocDonutRadius;
    $ocDonutOffset = 0;
    $ocDonutStartDeg = -90;
    $ocDonutSlices = [];
    foreach ([
        ['pct' => $ocBeforePct, 'color' => '#22c55e'],
        ['pct' => $ocOnTimePct, 'color' => '#3b82f6'],
        ['pct' => $ocBeyondPct, 'color' => '#ef4444'],
    ] as $slice) {
        $length = $ocTotal > 0 ? (($slice['pct'] / 100) * $ocDonutCircumference) : 0;
        $sweepDeg = $ocTotal > 0 ? (($slice['pct'] / 100) * 360) : 0;
        $midDeg = $ocDonutStartDeg + ($sweepDeg / 2);
        $midRad = deg2rad($midDeg);
        $ocDonutSlices[] = [
            'color' => $slice['color'],
            'pct' => (int)$slice['pct'],
            'length' => $length,
            'offset' => -$ocDonutOffset,
            'labelX' => 60 + $ocDonutRadius * cos($midRad),
            'labelY' => 60 + $ocDonutRadius * sin($midRad),
        ];
        $ocDonutOffset += $length;
        $ocDonutStartDeg += $sweepDeg;
    }
    ?>

    <section class="card atm-oc-card" data-atm-oc data-term="<?= e($completionTerm) ?>">
        <div class="atm-oc-toolbar">
            <form method="get" class="atm-oc-term-form" data-atm-oc-term-form>
                <input type="hidden" name="r" value="admin_terms">
                <label class="atm-oc-term-label">
                    <span>Academic Term</span>
                    <div class="atm-oc-term-control">
                        <span class="atm-oc-term-cal" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <rect x="4" y="5.5" width="16" height="14" rx="2" fill="none" stroke="#64748b" stroke-width="1.8"/>
                                <path d="M8 3.8v3.2M16 3.8v3.2M4 10.5h16" fill="none" stroke="#64748b" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span class="filter-select-wrap atm-oc-term-select">
                            <select name="completion_term" data-atm-oc-term data-select-label="Academic Term" aria-label="Academic Term" <?= $terms === [] ? 'disabled' : '' ?>>
                                <?php if ($terms === []): ?>
                                    <option value="">No terms yet</option>
                                <?php else: ?>
                                    <?php foreach ($terms as $termOption): ?>
                                        <?php $termLabel = (string)($termOption['term_label'] ?? ''); ?>
                                        <option value="<?= e($termLabel) ?>" <?= $termLabel === $completionTerm ? 'selected' : '' ?>><?= e($termLabel) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </span>
                    </div>
                </label>
            </form>
            <button class="atm-oc-export" type="button" data-atm-oc-export <?= $ocPrograms === [] ? 'disabled' : '' ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                Export Report
            </button>
        </div>

        <div class="atm-oc-head">
            <span class="asu-eyebrow">Overall Completion</span>
            <h2>Overall Completion (All Programs)</h2>
        </div>

        <div class="atm-oc-kpis">
            <article class="atm-oc-kpi atm-oc-kpi--before">
                <span class="atm-oc-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="8.25" fill="none" stroke="#fff" stroke-width="2"/>
                        <path d="M8.2 12.2 10.7 14.7 15.8 9.2" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="atm-oc-kpi-label">Finished Before Projected End</span>
                <strong><?= (int)$ocBeforePct ?>%</strong>
                <span class="atm-oc-kpi-sub"><?= (int)$ocBefore ?> <?= e($ocStudentWord($ocBefore)) ?></span>
            </article>
            <article class="atm-oc-kpi atm-oc-kpi--ontime">
                <span class="atm-oc-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="8.25" fill="none" stroke="#fff" stroke-width="2"/>
                        <path d="M12 8v4.2l2.8 1.7" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="atm-oc-kpi-label">Finished On Time</span>
                <strong><?= (int)$ocOnTimePct ?>%</strong>
                <span class="atm-oc-kpi-sub"><?= (int)$ocOnTime ?> <?= e($ocStudentWord($ocOnTime)) ?></span>
            </article>
            <article class="atm-oc-kpi atm-oc-kpi--beyond">
                <span class="atm-oc-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="4" y="5.5" width="16" height="14" rx="2" fill="none" stroke="#fff" stroke-width="2"/>
                        <path d="M8 3.8v3.2M16 3.8v3.2M4 10.5h16" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="atm-oc-kpi-label">Finished Beyond Schedule</span>
                <strong><?= (int)$ocBeyondPct ?>%</strong>
                <span class="atm-oc-kpi-sub"><?= (int)$ocBeyond ?> <?= e($ocStudentWord($ocBeyond)) ?></span>
            </article>
            <article class="atm-oc-kpi atm-oc-kpi--total">
                <span class="atm-oc-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M16.5 20.5v-1.6a3.4 3.4 0 0 0-3.4-3.4H7.4A3.4 3.4 0 0 0 4 18.9v1.6" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="10" cy="8" r="3.2" fill="none" stroke="#fff" stroke-width="2"/>
                        <path d="M20 20.5v-1.4a3 3 0 0 0-2.2-2.9" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                        <path d="M15.7 4.8a3.2 3.2 0 0 1 0 6.1" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="atm-oc-kpi-label">Total Students</span>
                <strong><?= (int)$ocTotal ?></strong>
                <span class="atm-oc-kpi-sub">All Programs</span>
            </article>
        </div>

        <div class="atm-oc-body">
            <div class="atm-oc-table-card">
                <h3>Completion Status by Program</h3>
                <?php if ($ocPrograms === []): ?>
                    <div class="atm-oc-empty">
                        <p>No programs to show yet.</p>
                        <span>Add degree programs to see completion by course.</span>
                    </div>
                <?php else: ?>
                    <div class="table-wrap atm-oc-table-wrap">
                        <table class="data-table no-row-details atm-oc-table" data-no-tools data-no-enhance data-atm-oc-table>
                            <thead>
                                <tr>
                                    <th>Program</th>
                                    <th class="atm-oc-col-before">Before Projected End</th>
                                    <th class="atm-oc-col-ontime">On Time</th>
                                    <th class="atm-oc-col-beyond">Beyond Schedule</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ocPrograms as $program): ?>
                                    <?php
                                    $programName = trim((string)($program['name'] ?? ''));
                                    $programCode = trim((string)($program['code'] ?? ''));
                                    if ($programName === '') {
                                        $programName = $programCode !== '' ? $programCode : 'Unspecified Program';
                                    }
                                    ?>
                                    <tr
                                        data-program="<?= e($programName) ?>"
                                        data-before="<?= (int)$program['before'] ?>"
                                        data-ontime="<?= (int)$program['ontime'] ?>"
                                        data-beyond="<?= (int)$program['beyond'] ?>"
                                        data-total="<?= (int)$program['total'] ?>"
                                    >
                                        <td class="atm-oc-program">
                                            <strong><?= e($programName) ?></strong>
                                            <?php if ($programCode !== ''): ?>
                                                <span class="atm-oc-code-pill"><?= e($programCode) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="atm-oc-metric atm-oc-metric--before">
                                            <strong><?= (int)$program['before_pct'] ?>%</strong>
                                            <span><?= (int)$program['before'] ?> <?= e($ocStudentWord((int)$program['before'])) ?></span>
                                            <span class="atm-oc-bar" aria-hidden="true"><i style="width: <?= (int)$program['before_pct'] ?>%"></i></span>
                                        </td>
                                        <td class="atm-oc-metric atm-oc-metric--ontime">
                                            <strong><?= (int)$program['ontime_pct'] ?>%</strong>
                                            <span><?= (int)$program['ontime'] ?> <?= e($ocStudentWord((int)$program['ontime'])) ?></span>
                                            <span class="atm-oc-bar" aria-hidden="true"><i style="width: <?= (int)$program['ontime_pct'] ?>%"></i></span>
                                        </td>
                                        <td class="atm-oc-metric atm-oc-metric--beyond">
                                            <strong><?= (int)$program['beyond_pct'] ?>%</strong>
                                            <span><?= (int)$program['beyond'] ?> <?= e($ocStudentWord((int)$program['beyond'])) ?></span>
                                            <span class="atm-oc-bar" aria-hidden="true"><i style="width: <?= (int)$program['beyond_pct'] ?>%"></i></span>
                                        </td>
                                        <td class="atm-oc-total-cell"><strong><?= (int)$program['total'] ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Overall</th>
                                    <th class="atm-oc-metric atm-oc-metric--before">
                                        <strong><?= (int)$ocBeforePct ?>%</strong>
                                        <span><?= (int)$ocBefore ?> <?= e($ocStudentWord($ocBefore)) ?></span>
                                    </th>
                                    <th class="atm-oc-metric atm-oc-metric--ontime">
                                        <strong><?= (int)$ocOnTimePct ?>%</strong>
                                        <span><?= (int)$ocOnTime ?> <?= e($ocStudentWord($ocOnTime)) ?></span>
                                    </th>
                                    <th class="atm-oc-metric atm-oc-metric--beyond">
                                        <strong><?= (int)$ocBeyondPct ?>%</strong>
                                        <span><?= (int)$ocBeyond ?> <?= e($ocStudentWord($ocBeyond)) ?></span>
                                    </th>
                                    <th class="atm-oc-total-cell"><strong><?= (int)$ocTotal ?></strong></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="atm-oc-chart-card">
                <h3>Chart Overview</h3>
                <div class="atm-oc-donut-wrap">
                    <svg class="atm-oc-donut" viewBox="0 0 120 120" role="img" aria-label="Completion breakdown for <?= e((string)$ocTotal) ?> students">
                        <g transform="rotate(-90 60 60)">
                            <circle class="atm-oc-donut-track" cx="60" cy="60" r="<?= $ocDonutRadius ?>"></circle>
                            <?php foreach ($ocDonutSlices as $slice): ?>
                                <?php if ($slice['length'] <= 0) continue; ?>
                                <circle
                                    class="atm-oc-donut-slice"
                                    cx="60"
                                    cy="60"
                                    r="<?= $ocDonutRadius ?>"
                                    stroke="<?= e($slice['color']) ?>"
                                    stroke-dasharray="<?= number_format($slice['length'], 2, '.', '') ?> <?= number_format($ocDonutCircumference, 2, '.', '') ?>"
                                    stroke-dashoffset="<?= number_format($slice['offset'], 2, '.', '') ?>"
                                ></circle>
                            <?php endforeach; ?>
                        </g>
                        <?php foreach ($ocDonutSlices as $slice): ?>
                            <?php if ((int)$slice['pct'] < 8) continue; ?>
                            <text
                                class="atm-oc-donut-label"
                                x="<?= number_format((float)$slice['labelX'], 2, '.', '') ?>"
                                y="<?= number_format((float)$slice['labelY'], 2, '.', '') ?>"
                                text-anchor="middle"
                                dominant-baseline="middle"
                            ><?= (int)$slice['pct'] ?>%</text>
                        <?php endforeach; ?>
                    </svg>
                    <div class="atm-oc-donut-hole">
                        <strong><?= (int)$ocTotal ?></strong>
                        <span>Total</span>
                    </div>
                </div>
                <ul class="atm-oc-legend">
                    <li>
                        <i class="atm-oc-dot atm-oc-dot--before"></i>
                        <span>Finished Before Projected End</span>
                        <strong><?= (int)$ocBeforePct ?>%</strong>
                        <small>(<?= (int)$ocBefore ?>)</small>
                    </li>
                    <li>
                        <i class="atm-oc-dot atm-oc-dot--ontime"></i>
                        <span>Finished On Time</span>
                        <strong><?= (int)$ocOnTimePct ?>%</strong>
                        <small>(<?= (int)$ocOnTime ?>)</small>
                    </li>
                    <li>
                        <i class="atm-oc-dot atm-oc-dot--beyond"></i>
                        <span>Finished Beyond Schedule</span>
                        <strong><?= (int)$ocBeyondPct ?>%</strong>
                        <small>(<?= (int)$ocBeyond ?>)</small>
                    </li>
                </ul>
                <p class="atm-oc-chart-note">Percentages are based on the total number of students in the selected academic term.</p>
            </aside>
        </div>

        <div class="atm-oc-note">
            <span class="atm-oc-note-icon" aria-hidden="true">i</span>
            <p><strong>Before Projected End</strong> finished required hours before the company projected end date. <strong>On Time</strong> finished on that date. <strong>Beyond Schedule</strong> finished after that date.</p>
        </div>
    </section>
</div>

<div class="asu-create-overlay" id="atmTermOverlay" aria-hidden="true">
    <div class="asu-create-modal atm-term-modal" role="dialog" aria-modal="true" aria-labelledby="atmTermModalTitle">
        <div class="asu-create-modal-head">
            <div class="asu-create-modal-head-main">
                <div class="asu-create-modal-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 0 0 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2Zm0 16H5V10h14v10Zm0-12H5V6h14v2Z"/></svg>
                </div>
                <div>
                    <h2 id="atmTermModalTitle">Add Academic Term</h2>
                    <p data-atm-modal-sub>Create a term and date range for coordinator enrollment.</p>
                </div>
            </div>
            <button type="button" class="asu-create-modal-close" id="atmTermModalClose" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <form method="post" class="form js-validate asu-create-form" id="atmTermForm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="admin_save_term">
            <input type="hidden" name="term_id" id="atmTermId" value="">

            <div class="asu-create-modal-body">
                <div class="asu-create-section">
                    <div class="asu-create-section-head">Term Details</div>
                    <div class="asu-create-fields">
                        <label class="asu-create-field asu-create-field--full">
                            <span>Term Name <em>*</em></span>
                            <input
                                required
                                name="term_label"
                                id="atmTermLabel"
                                placeholder="2523 (2nd Tri) - SY 2025-2026"
                                maxlength="120"
                                autocomplete="off"
                            >
                            <small class="muted">Format: 2523 (2nd Tri) - SY 2025-2026</small>
                        </label>
                        <label class="asu-create-field">
                            <span>Start Date <em>*</em></span>
                            <span class="filter-date-picker form-date-picker is-placeholder" data-date-required="1" id="atmStartPicker">
                                <input type="hidden" name="term_start_date" value="">
                                <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select term start date">
                                    <span class="filter-date-value">mm/dd/yyyy</span>
                                    <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                                </button>
                                <div class="filter-date-panel" hidden></div>
                            </span>
                        </label>
                        <label class="asu-create-field">
                            <span>End Date <em>*</em></span>
                            <span class="filter-date-picker form-date-picker is-placeholder" data-date-required="1" id="atmEndPicker">
                                <input type="hidden" name="term_end_date" value="">
                                <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select term end date">
                                    <span class="filter-date-value">mm/dd/yyyy</span>
                                    <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                                </button>
                                <div class="filter-date-panel" hidden></div>
                            </span>
                        </label>
                        <label class="asu-create-field asu-create-field--full">
                            <span>Status</span>
                            <select name="is_active" id="atmTermStatus" class="atm-status-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>

            <div class="asu-create-modal-actions">
                <button type="button" class="btn btn-small" id="atmTermModalCancel">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <span class="btn-text" data-atm-submit-label>Save Term</span>
                    <span class="spinner"></span>
                </button>
            </div>
        </form>
    </div>
</div>
