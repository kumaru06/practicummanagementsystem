<?php
$totalPrograms = count($programs);
$activePrograms = count(array_filter($programs, static fn ($program) => (int)($program['is_active'] ?? 0) === 1));
$inactivePrograms = $totalPrograms - $activePrograms;
?>

<div class="programs-page">

    <div class="programs-page-intro">
        <div class="programs-page-intro-copy">
            <p class="programs-page-eyebrow">Academic Setup</p>
            <p class="programs-page-desc">Configure OJT programs and required hours for Host Training Establishments and student enrollment.</p>
        </div>
    </div>

    <div class="programs-stats-strip programs-stats-strip--3">
        <div class="programs-stat-card programs-stat-total">
            <div class="programs-stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3Zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9ZM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72Z"/></svg>
            </div>
            <div class="programs-stat-body">
                <span>Total Programs</span>
                <strong><?= (int)$totalPrograms ?></strong>
            </div>
        </div>
        <div class="programs-stat-card programs-stat-active">
            <div class="programs-stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1.5 14.5-4-4L8 11l2.5 2.5L16 8l1.5 1.5-7 7Z"/></svg>
            </div>
            <div class="programs-stat-body">
                <span>Active</span>
                <strong><?= (int)$activePrograms ?></strong>
            </div>
        </div>
        <div class="programs-stat-card programs-stat-inactive">
            <div class="programs-stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm5 11H7v-2h10v2Z"/></svg>
            </div>
            <div class="programs-stat-body">
                <span>Inactive</span>
                <strong><?= (int)$inactivePrograms ?></strong>
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

    </div>

    <section class="card asu-directory-card programs-list-card" data-admin-programs-directory>
        <div class="programs-directory-head programs-directory-head--in-card">
            <div class="programs-directory-copy">
                <h2>Program List</h2>
                <p>Manage hours, rename programs, or change their active status.</p>
            </div>
            <div class="programs-directory-actions">
                <div class="programs-search-box">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    <input class="table-search asu-table-search" type="search" placeholder="Search programs..." autocomplete="off">
                </div>
                <label class="filter-select-wrap asu-filter-select programs-status-filter">
                    <select data-asu-program-status-filter data-select-label="Status" aria-label="Filter by status">
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
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
            <div class="table-wrap asu-table-wrap programs-table-wrap">
                <table class="data-table no-row-details asu-students-table asu-programs-table" data-no-tools data-per-page="10">
                    <thead>
                        <tr>
                            <th data-sort>Code</th>
                            <th data-sort>Program Name</th>
                            <th data-sort>Hours</th>
                            <th>Status</th>
                            <th class="asu-col-action">Action</th>
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
