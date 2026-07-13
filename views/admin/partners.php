<?php
$totalPartners = count($partners);
$activePartners = count(array_filter($partners, static fn ($partner) => (int)($partner['is_active'] ?? 0) === 1));
$inactivePartners = $totalPartners - $activePartners;
$totalPrograms = count($programs);
?>

<form
    id="create-partner-form"
    method="post"
    enctype="multipart/form-data"
    class="form js-validate"
    data-require-checkbox-group="program_ids[]"
    data-require-checkbox-message="Select at least one accepted program/course."
>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="admin_create_company">
</form>

<div class="partner-page">

    <div class="partner-stats-strip">
        <div class="partner-stat-card partner-stat-total">
            <div class="partner-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
            </div>
            <div class="partner-stat-body">
                <span>Host Training Establishments</span>
                <strong><?= (int)$totalPartners ?></strong>
            </div>
        </div>
        <div class="partner-stat-card partner-stat-active">
            <div class="partner-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            </div>
            <div class="partner-stat-body">
                <span>Active</span>
                <strong><?= (int)$activePartners ?></strong>
            </div>
        </div>
        <div class="partner-stat-card partner-stat-inactive">
            <div class="partner-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8 0-1.85.63-3.55 1.69-4.9L16.9 18.31C15.55 19.37 13.85 20 12 20zm6.31-3.1L7.1 5.69C8.45 4.63 10.15 4 12 4c4.42 0 8 3.58 8 8 0 1.85-.63 3.55-1.69 4.9z"/></svg>
            </div>
            <div class="partner-stat-body">
                <span>Inactive</span>
                <strong><?= (int)$inactivePartners ?></strong>
            </div>
        </div>
        <div class="partner-stat-card partner-stat-programs">
            <div class="partner-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3 1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
            </div>
            <div class="partner-stat-body">
                <span>Programs</span>
                <strong><?= (int)$totalPrograms ?></strong>
            </div>
        </div>
    </div>

    <div class="partner-admin-layout">

        <section class="partner-panel partner-form-card">
            <div class="partner-panel-head">
                <div class="partner-icon-wrap partner-icon-wrap--add" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                </div>
                <div class="partner-panel-head-text">
                    <h2>Add Host Training Establishment</h2>
                </div>
            </div>

            <div class="partner-form-section">
                <div class="partner-form-section-head">Company Info</div>

                <div class="partner-form-fields">
                    <label class="partner-field">
                        <span class="partner-field-label">Company Name <em>*</em></span>
                        <input form="create-partner-form" required name="company_name" placeholder="e.g. TechLog Dev Inc." autocomplete="organization">
                    </label>
                    <label class="partner-field">
                        <span class="partner-field-label">Contact Person <em>*</em></span>
                        <input form="create-partner-form" required name="contact_person" placeholder="Full name" autocomplete="name">
                    </label>
                    <label class="partner-field">
                        <span class="partner-field-label">Email Address <em>*</em></span>
                        <input form="create-partner-form" required type="email" name="contact_email" placeholder="partner@company.com" autocomplete="email"
                            data-partner-email-check>
                        <span class="field-check-message" data-partner-email-message hidden aria-live="polite"></span>
                    </label>
                    <label class="partner-field">
                        <span class="partner-field-label">Contact Number <em>*</em></span>
                        <input form="create-partner-form" required name="contact_number" placeholder="+63 951 192 5735" inputmode="numeric" autocomplete="tel-national" maxlength="16" data-phone-format="ph" pattern="\+63\s9\d{2}\s\d{3}\s\d{4}" title="Use format +63 951 192 5735">
                    </label>
                </div>

                <label class="partner-field partner-field--full">
                    <span class="partner-field-label">Company Address <em>*</em></span>
                    <textarea form="create-partner-form" required name="address" maxlength="500" rows="3" placeholder="Street, city, province"></textarea>
                </label>

                <div class="partner-credential-strip">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    <div>
                        <strong>Auto credential delivery</strong>
                        <span>A temporary password is generated and emailed to the partner on creation.</span>
                    </div>
                </div>

                <div class="partner-id-preview">
                    <span class="partner-id-preview-label">HTE ID (Auto-Generated)</span>
                    <strong class="partner-id-preview-value"><?= e($nextPartnerId ?? 'HTE-' . date('Y') . '-0001') ?></strong>
                    <small>Assigned on save and included in the welcome email.</small>
                </div>
            </div>
        </section>

    </div>

    <section class="card asu-directory-card partner-hte-directory admin-partners-page" data-admin-partners-directory>
        <div class="asu-directory-head">
            <div class="asu-directory-copy">
                <span class="asu-eyebrow">HTE Directory</span>
                <h2>Host Training Establishments</h2>
            </div>
            <div class="asu-directory-badge" aria-live="polite">
                <strong><?= (int)$totalPartners ?></strong>
                <span>Listed</span>
            </div>
        </div>

        <?php if (!$partners): ?>
            <div class="asu-empty">
                <div class="asu-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
                </div>
                <p class="asu-empty-title">No Host Training Establishments yet</p>
                <p class="asu-empty-sub">Use the form above to add your first Host Training Establishment.</p>
            </div>
        <?php else: ?>
            <div class="asu-toolbar">
                <div class="asu-search-wrap">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    <input class="table-search asu-table-search" type="search" placeholder="Search establishments..." autocomplete="off">
                </div>
                <div class="asu-toolbar-actions">
                    <label class="filter-select-wrap asu-filter-select">
                        <select data-asu-partner-status-filter data-select-label="Status" aria-label="Filter by status">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="table-wrap asu-table-wrap">
                <table class="data-table no-row-details asu-students-table asu-partners-table" data-no-tools data-per-page="10">
                    <thead>
                        <tr>
                            <th data-sort>Company</th>
                            <th data-sort>Contact</th>
                            <th data-sort>Email</th>
                            <th data-sort>HTE ID</th>
                            <th data-sort>Phone</th>
                            <th>Programs</th>
                            <th>Status</th>
                            <th class="asu-col-action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($partners as $u): ?>
                            <?php
                                $selectedProgramIds = array_values(array_filter(array_map('intval', explode(',', (string)($u['accepted_program_ids'] ?? '')))));
                                $programCodes = array_values(array_filter(array_map('trim', explode(',', (string)($u['accepted_programs'] ?? '')))));
                                $programDetails = [];
                                foreach ($programs as $program) {
                                    if (!in_array((int)$program['id'], $selectedProgramIds, true)) {
                                        continue;
                                    }
                                    $programDetails[] = [
                                        'code' => (string)($program['code'] ?? ''),
                                        'name' => (string)($program['name'] ?? ''),
                                        'hours' => (int)($program['required_hours'] ?? 0),
                                    ];
                                }
                                if (!$programDetails && $programCodes) {
                                    foreach ($programCodes as $programCode) {
                                        $programDetails[] = [
                                            'code' => $programCode,
                                            'name' => '',
                                            'hours' => 0,
                                        ];
                                    }
                                }
                                $programCount = count($programDetails);
                                $isActive = !empty($u['is_active']);
                                $initial = strtoupper(mb_substr((string)($u['name'] ?? 'H'), 0, 1));
                                $searchHaystack = strtolower(trim(
                                    ($u['name'] ?? '') . ' ' .
                                    ($u['contact_person'] ?? '') . ' ' .
                                    ($u['email'] ?? '') . ' ' .
                                    ($u['partner_id'] ?? '') . ' ' .
                                    ($u['contact_number'] ?? '') . ' ' .
                                    ($u['accepted_programs'] ?? '')
                                ));
                            ?>
                            <tr data-partner-status="<?= $isActive ? 'active' : 'inactive' ?>"
                                data-search="<?= e($searchHaystack) ?>">
                                <td class="asu-name-cell">
                                    <div class="asu-student-cell">
                                        <span class="asu-student-avatar aco-avatar-tone--<?= (abs((int)($u['user_id'] ?? $u['id'] ?? 0)) % 6) + 1 ?>">
                                            <?= e($initial) ?>
                                        </span>
                                        <span title="<?= e($u['name'] ?? '') ?>"><?= e($u['name'] ?? '—') ?></span>
                                    </div>
                                </td>
                                <td class="asu-name-cell"><?= e($u['contact_person'] ?? '—') ?></td>
                                <td>
                                    <?php if (!empty($u['email'])): ?>
                                        <a class="asu-email-link" href="mailto:<?= e($u['email']) ?>"><?= e($u['email']) ?></a>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="center-cell">
                                    <?php if (!empty($u['partner_id'])): ?>
                                        <span class="asu-usn-badge"><?= e($u['partner_id']) ?></span>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($u['contact_number'] ?: '—') ?></td>
                                <td class="center-cell asu-partners-programs-cell">
                                    <?php if ($programCount > 0): ?>
                                        <button
                                            type="button"
                                            class="asu-partner-programs-btn"
                                            data-asu-view-programs
                                            data-company="<?= e($u['name'] ?? 'Host Training Establishment') ?>"
                                            data-programs="<?= e(json_encode($programDetails, JSON_UNESCAPED_UNICODE)) ?>"
                                        >
                                            <span class="asu-partner-programs-count"><?= (int)$programCount ?></span>
                                            <span>View</span>
                                        </button>
                                    <?php else: ?>
                                        <span class="muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td class="center-cell">
                                    <span class="asu-status-pill <?= $isActive ? 'is-active' : 'is-inactive' ?>">
                                        <?= $isActive ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="admin-users-action-cell asu-col-action">
                                    <details class="admin-user-action-menu">
                                        <summary class="admin-user-action-trigger" aria-label="Establishment actions">
                                            <svg class="admin-user-action-trigger-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm0 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                                            <span>Actions</span>
                                            <svg class="admin-user-action-trigger-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                                        </summary>
                                        <div class="admin-user-action-panel">
                                            <?php if (!empty($u['moa_mou_file'])): ?>
                                                <a class="admin-user-action-item" target="_blank" rel="noopener noreferrer" href="index.php?r=admin_partner_document&amp;company_id=<?= (int)$u['id'] ?>">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                                                    View MOA / MOU
                                                </a>
                                            <?php endif; ?>

                                            <button
                                                type="button"
                                                class="admin-user-action-item"
                                                data-asu-edit-programs
                                                data-company-id="<?= (int)$u['id'] ?>"
                                                data-company="<?= e($u['name'] ?? 'Host Training Establishment') ?>"
                                                data-selected-ids="<?= e(json_encode($selectedProgramIds)) ?>"
                                            >
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                                Edit Programs
                                            </button>

                                            <form method="post" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="admin_toggle_user">
                                                <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                                <input type="hidden" name="active" value="<?= $isActive ? 0 : 1 ?>">
                                                <input type="hidden" name="redirect" value="admin_partners">
                                                <button class="admin-user-action-item <?= $isActive ? 'admin-user-action-item--danger' : 'admin-user-action-item--success' ?>" type="submit">
                                                    <?php if ($isActive): ?>
                                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z"/></svg>
                                                        Deactivate
                                                    <?php else: ?>
                                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                                        Activate
                                                    <?php endif; ?>
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

    <section class="partner-panel partner-full-card">
        <div class="partner-panel-head">
            <div class="partner-icon-wrap partner-icon-wrap--programs" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3 1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
            </div>
            <div class="partner-panel-head-text">
                <h2>Accepted Programs <span class="field-required">*</span></h2>
            </div>
        </div>

        <?php if (empty($programs)): ?>
            <div class="partner-empty-state partner-empty-state--compact">
                <div class="partner-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                </div>
                <strong>No programs available yet</strong>
                <span>Create programs first in Degree Program before adding Host Training Establishments.</span>
            </div>
        <?php else: ?>
            <div class="partner-programs-grid">
                <?php foreach ($programs as $program): ?>
                    <label class="partner-program-option partner-program-option--card" form="create-partner-form">
                        <input form="create-partner-form" type="checkbox" name="program_ids[]" value="<?= (int)$program['id'] ?>">
                        <span class="partner-program-copy">
                            <strong><?= e($program['code']) ?></strong>
                            <span><?= e($program['name']) ?></span>
                        </span>
                        <em><?= (int)$program['required_hours'] ?> hrs</em>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="partner-programs-footer">
            <label class="partner-upload-field">
                <span class="partner-upload-title">Upload MOA/MOU <span class="field-required">*</span></span>
                <input form="create-partner-form" required type="file" name="moa_mou_file" accept=".pdf,.jpg,.jpeg,.png">
                <small class="muted partner-upload-note">Required. PDF, JPG, or PNG up to 8MB.</small>
            </label>
            <button form="create-partner-form" class="btn partner-create-btn" type="submit">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                <span class="btn-text">Create Host Training Establishment</span>
                <span class="spinner"></span>
            </button>
        </div>
    </section>

</div>

<div class="asu-partner-programs-overlay" id="asuPartnerProgramsOverlay" aria-hidden="true">
    <div class="asu-partner-programs-modal" role="dialog" aria-modal="true" aria-labelledby="asuPartnerProgramsTitle">
        <div class="asu-partner-programs-modal-head">
            <div>
                <span class="asu-eyebrow">Accepted Programs</span>
                <h2 id="asuPartnerProgramsTitle">Host Training Establishment</h2>
                <p class="asu-partner-programs-modal-sub" data-asu-programs-count></p>
            </div>
            <button type="button" class="asu-partner-programs-close" data-asu-programs-close aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="asu-partner-programs-modal-body" data-asu-programs-list></div>
    </div>
</div>

<div class="asu-partner-programs-overlay" id="asuPartnerEditProgramsOverlay" aria-hidden="true">
    <div class="asu-partner-programs-modal asu-partner-edit-programs-modal" role="dialog" aria-modal="true" aria-labelledby="asuPartnerEditProgramsTitle">
        <div class="asu-partner-programs-modal-head">
            <div>
                <span class="asu-eyebrow">Edit Programs</span>
                <h2 id="asuPartnerEditProgramsTitle">Host Training Establishment</h2>
                <p class="asu-partner-programs-modal-sub">Select the programs this establishment can accept.</p>
            </div>
            <button type="button" class="asu-partner-programs-close" data-asu-edit-programs-close aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form method="post" class="asu-partner-edit-programs-form" id="asuPartnerEditProgramsForm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="admin_update_company_programs">
            <input type="hidden" name="company_id" value="" data-asu-edit-company-id>
            <div class="asu-partner-programs-modal-body asu-partner-edit-programs-body" data-asu-edit-programs-list></div>
            <div class="asu-partner-edit-programs-footer">
                <button type="button" class="btn btn-small" data-asu-edit-programs-close>Cancel</button>
                <button type="submit" class="btn btn-small btn-primary">Save Programs</button>
            </div>
        </form>
    </div>
</div>

<script>
window.asuPartnerProgramCatalog = <?= json_encode(array_map(static function ($program) {
    return [
        'id' => (int)$program['id'],
        'code' => (string)($program['code'] ?? ''),
        'name' => (string)($program['name'] ?? ''),
        'hours' => (int)($program['required_hours'] ?? 0),
    ];
}, $programs ?? []), JSON_UNESCAPED_UNICODE) ?>;
</script>
