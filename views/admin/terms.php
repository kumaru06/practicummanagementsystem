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
