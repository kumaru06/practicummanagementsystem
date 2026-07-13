<?php
$totalPrograms = count($programs);
$activePrograms = count(array_filter($programs, static fn ($program) => (int)($program['is_active'] ?? 0) === 1));
$inactivePrograms = $totalPrograms - $activePrograms;
$totalTerms = count($terms ?? []);
?>

<div class="programs-page">

    <div class="programs-page-intro">
        <div class="programs-page-intro-copy">
            <p class="programs-page-eyebrow">Academic Setup</p>
            <p class="programs-page-desc">Configure OJT programs and required hours. Academic terms are managed separately for coordinator enrollment.</p>
        </div>
    </div>

    <div class="programs-stats-strip">
        <div class="programs-stat-card programs-stat-total">
            <div class="programs-stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            </div>
            <div class="programs-stat-body">
                <span>Total Programs</span>
                <strong><?= (int)$totalPrograms ?></strong>
            </div>
        </div>
        <div class="programs-stat-card programs-stat-active">
            <div class="programs-stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="programs-stat-body">
                <span>Active</span>
                <strong><?= (int)$activePrograms ?></strong>
            </div>
        </div>
        <div class="programs-stat-card programs-stat-inactive">
            <div class="programs-stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            </div>
            <div class="programs-stat-body">
                <span>Inactive</span>
                <strong><?= (int)$inactivePrograms ?></strong>
            </div>
        </div>
        <div class="programs-stat-card programs-stat-terms">
            <div class="programs-stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="programs-stat-body">
                <span>Terms</span>
                <strong><?= (int)$totalTerms ?></strong>
            </div>
        </div>
    </div>

    <div class="programs-top-row">

        <section class="programs-panel programs-add-card">
            <div class="programs-panel-head">
                <div class="programs-icon-wrap programs-icon-wrap--term">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                </div>
                <div class="programs-panel-head-text">
                    <div class="programs-panel-title-row">
                        <h2>Add Program / Course</h2>
                        <span class="programs-count-badge"><?= (int)$totalPrograms ?> listed</span>
                    </div>
                    <p>Programs must be prepared first before Host Training Establishments can choose them.</p>
                </div>
            </div>

            <form method="post" class="form js-validate programs-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="admin_save_program">

                <div class="programs-form-section">
                    <div class="programs-form-section-head">
                        <span>Program Details</span>
                    </div>

                    <label class="programs-field">
                        <span class="programs-field-label">Program Code <em>*</em></span>
                        <input required name="code" placeholder="e.g. BSIT" autocomplete="off">
                    </label>

                    <label class="programs-field">
                        <span class="programs-field-label">Program Name <em>*</em></span>
                        <input required name="name" placeholder="e.g. Bachelor of Science in Information Technology" autocomplete="off">
                    </label>

                    <label class="programs-field">
                        <span class="programs-field-label">Required OJT Hours <em>*</em></span>
                        <input required type="number" min="1" name="required_hours" placeholder="486" inputmode="numeric">
                    </label>
                </div>

                <button class="btn programs-add-btn programs-add-btn--secondary" type="submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    <span class="btn-text">Add Program</span>
                    <span class="spinner"></span>
                </button>
            </form>
        </section>

        <section class="programs-panel programs-term-card">
            <div class="programs-panel-head">
                <div class="programs-icon-wrap programs-icon-wrap--term">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7h8M8 12h8M8 17h5"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg>
                </div>
                <div class="programs-panel-head-text">
                    <div class="programs-panel-title-row">
                        <h2>Academic Terms</h2>
                        <span class="programs-count-badge"><?= (int)$totalTerms ?> saved</span>
                    </div>
                    <p>Create terms and date ranges coordinators use when enrolling students.</p>
                </div>
            </div>

            <form method="post" class="form js-validate programs-term-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="admin_save_term">
                <label class="programs-field">
                    <span class="programs-field-label">New Term <em>*</em></span>
                    <input
                        required
                        name="term_label"
                        placeholder="2523 (2nd Tri) - SY 2025-2026"
                        maxlength="120"
                    >
                </label>
                <div class="programs-term-date-row">
                    <label class="programs-field">
                        <span class="programs-field-label">Term Start Date <em>*</em></span>
                        <span class="filter-date-picker form-date-picker is-placeholder" data-date-required="1">
                            <input type="hidden" name="term_start_date" value="">
                            <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select term start date">
                                <span class="filter-date-value">mm/dd/yyyy</span>
                                <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                            </button>
                        </span>
                    </label>
                    <label class="programs-field">
                        <span class="programs-field-label">Term End Date <em>*</em></span>
                        <span class="filter-date-picker form-date-picker is-placeholder" data-date-required="1">
                            <input type="hidden" name="term_end_date" value="">
                            <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select term end date">
                                <span class="filter-date-value">mm/dd/yyyy</span>
                                <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                            </button>
                        </span>
                    </label>
                </div>
                <button class="btn programs-add-btn programs-add-btn--secondary" type="submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <span class="btn-text">Save Term</span>
                    <span class="spinner"></span>
                </button>
            </form>

            <div class="programs-term-list-wrap">
                <div class="programs-term-list-head">
                    <span>Saved Terms</span>
                </div>
                <div class="programs-term-list">
                    <?php if (empty($terms)): ?>
                        <div class="programs-term-empty">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <p>No terms added yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($terms as $term): ?>
                            <?php
                            $termFormId = 'term-form-' . (int)$term['id'];
                            $termStart = trim((string)($term['term_start_date'] ?? ''));
                            $termEnd = trim((string)($term['term_end_date'] ?? ''));
                            $hasDates = $termStart !== '' && $termEnd !== '';
                            $displayStart = $hasDates ? date('M j, Y', strtotime($termStart)) : '';
                            $displayEnd = $hasDates ? date('M j, Y', strtotime($termEnd)) : '';
                            ?>
                            <article class="programs-term-item<?= $hasDates ? '' : ' programs-term-item--incomplete' ?>">
                                <form method="post" id="<?= e($termFormId) ?>" class="programs-term-edit-form">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="admin_save_term">
                                    <input type="hidden" name="term_id" value="<?= (int)$term['id'] ?>">
                                    <input type="hidden" name="term_label" value="<?= e($term['term_label']) ?>">
                                </form>
                                <div class="programs-term-item-head">
                                    <div class="programs-term-chip">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        <div class="programs-term-copy">
                                            <span class="programs-term-label-text"><?= e($term['term_label']) ?></span>
                                            <?php if ($hasDates): ?>
                                                <small class="programs-term-range"><?= e($displayStart) ?> - <?= e($displayEnd) ?></small>
                                            <?php else: ?>
                                                <small class="programs-term-range programs-term-range--warn">Dates not set - add below</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <form method="post" class="programs-term-delete-form" onsubmit="return confirm('Delete this term? Coordinators will no longer see it when enrolling students.')">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="admin_delete_term">
                                        <input type="hidden" name="term_id" value="<?= (int)$term['id'] ?>">
                                        <button class="programs-term-delete-btn" type="submit" title="Delete term" aria-label="Delete term">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                        </button>
                                    </form>
                                </div>
                                <div class="programs-term-date-edit">
                                    <label class="programs-field programs-field--compact">
                                        <span class="programs-field-label">Start</span>
                                        <span class="filter-date-picker form-date-picker <?= $termStart === '' ? 'is-placeholder' : '' ?>" data-date-required="1">
                                            <input form="<?= e($termFormId) ?>" type="hidden" name="term_start_date" value="<?= e($termStart) ?>">
                                            <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select term start date">
                                                <span class="filter-date-value"><?= $termStart !== '' ? e(date('m/d/Y', strtotime($termStart))) : 'mm/dd/yyyy' ?></span>
                                                <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                                            </button>
                                        </span>
                                    </label>
                                    <label class="programs-field programs-field--compact">
                                        <span class="programs-field-label">End</span>
                                        <span class="filter-date-picker form-date-picker <?= $termEnd === '' ? 'is-placeholder' : '' ?>" data-date-required="1">
                                            <input form="<?= e($termFormId) ?>" type="hidden" name="term_end_date" value="<?= e($termEnd) ?>">
                                            <button class="filter-date-trigger" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Select term end date">
                                                <span class="filter-date-value"><?= $termEnd !== '' ? e(date('m/d/Y', strtotime($termEnd))) : 'mm/dd/yyyy' ?></span>
                                                <span class="filter-date-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg></span>
                                            </button>
                                        </span>
                                    </label>
                                    <button form="<?= e($termFormId) ?>" class="programs-term-save-btn" type="submit">Save</button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

    </div>

    <section class="card asu-directory-card programs-list-card" data-admin-programs-directory>
        <div class="asu-directory-head">
            <div class="asu-directory-copy">
                <span class="asu-eyebrow">Academic Setup</span>
                <h2>Program List</h2>
                <p>Edit hours, rename programs, or change their status below.</p>
            </div>
            <div class="asu-directory-badge" aria-live="polite">
                <strong><?= (int)$totalPrograms ?></strong>
                <span>Listed</span>
            </div>
        </div>

        <?php if (empty($programs)): ?>
            <div class="asu-empty">
                <div class="asu-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Zm0-8h5v2H8V9Z"/></svg>
                </div>
                <p class="asu-empty-title">No programs yet</p>
                <p class="asu-empty-sub">Add your first program using the form above.</p>
            </div>
        <?php else: ?>
            <div class="asu-toolbar programs-list-toolbar">
                <div class="asu-search-wrap">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    <input class="table-search asu-table-search" type="search" placeholder="Search programs..." autocomplete="off">
                </div>
                <div class="asu-toolbar-actions">
                    <label class="filter-select-wrap asu-filter-select programs-status-filter">
                        <select data-asu-program-status-filter data-select-label="Status" aria-label="Filter by status">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="table-wrap asu-table-wrap programs-table-wrap">
                <table class="data-table no-row-details asu-students-table asu-programs-table" data-no-tools data-per-page="10">
                    <thead>
                        <tr>
                            <th data-sort>Code</th>
                            <th data-sort>Program Name</th>
                            <th data-sort>Hours</th>
                            <th>Status</th>
                            <th class="asu-col-action"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($programs as $p): ?>
                            <?php
                                $formId = 'program-form-' . (int)$p['id'];
                                $isActive = (bool)$p['is_active'];
                            ?>
                            <tr class="asu-program-row"
                                data-program-status="<?= $isActive ? 'active' : 'inactive' ?>"
                                data-search="<?= e(strtolower(trim(($p['code'] ?? '') . ' ' . ($p['name'] ?? '') . ' ' . ($isActive ? 'active' : 'inactive')))) ?>">
                                <td class="asu-program-code-cell">
                                    <form method="post" id="<?= e($formId) ?>" class="program-row-form">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="admin_save_program">
                                        <input type="hidden" name="program_id" value="<?= (int)$p['id'] ?>">
                                    </form>
                                    <input form="<?= e($formId) ?>" class="asu-program-code-input" name="code" value="<?= e($p['code']) ?>" placeholder="Code" required aria-label="Program code" data-program-field>
                                </td>
                                <td class="asu-program-name-cell">
                                    <input form="<?= e($formId) ?>" class="asu-program-name-input" name="name" value="<?= e($p['name']) ?>" placeholder="Program Name" required aria-label="Program name" data-program-field>
                                </td>
                                <td class="asu-program-hours-cell">
                                    <div class="asu-program-hours-wrap">
                                        <input form="<?= e($formId) ?>" class="asu-program-hours-input" type="number" min="1" name="required_hours" value="<?= (int)$p['required_hours'] ?>" placeholder="0" required aria-label="Required hours" data-program-field>
                                        <span class="asu-program-hours-suffix">hrs</span>
                                    </div>
                                </td>
                                <td class="asu-program-status-cell">
                                    <label class="filter-select-wrap asu-program-status-wrap">
                                        <select form="<?= e($formId) ?>" class="asu-program-status-select" name="is_active" data-select-label="Status" aria-label="Status" data-program-field data-program-status-field>
                                            <option value="1" <?= $isActive ? 'selected' : '' ?>>Active</option>
                                            <option value="0" <?= !$isActive ? 'selected' : '' ?>>Inactive</option>
                                        </select>
                                    </label>
                                </td>
                                <td class="asu-col-action asu-program-action-cell">
                                    <details class="admin-user-action-menu asu-program-action-menu">
                                        <summary class="admin-user-action-trigger asu-program-action-trigger" aria-label="Program actions">
                                            <svg class="admin-user-action-trigger-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm0 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                                        </summary>
                                        <div class="admin-user-action-panel">
                                            <button form="<?= e($formId) ?>" class="admin-user-action-item asu-program-save-item" type="submit">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4Zm-5 16a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm3-10H5V5h10v4Z"/></svg>
                                                Save changes
                                            </button>
                                            <form method="post" class="asu-program-delete-form" data-program-delete>
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="admin_delete_program">
                                                <input type="hidden" name="program_id" value="<?= (int)$p['id'] ?>">
                                                <button class="admin-user-action-item admin-user-action-item--danger" type="submit">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>
                                                    Delete program
                                                </button>
                                            </form>
                                        </div>
                                    </details>
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
