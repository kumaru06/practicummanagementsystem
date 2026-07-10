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
                    <span class="partner-id-preview-label">Partner ID (Auto-Generated)</span>
                    <strong class="partner-id-preview-value"><?= e($nextPartnerId ?? 'IP-' . date('Y') . '-0001') ?></strong>
                    <small>Assigned on save and included in the welcome email.</small>
                </div>
            </div>
        </section>

        <section class="partner-panel partner-directory-card">
            <div class="partner-panel-head partner-panel-head--split">
                <div class="partner-panel-head-main">
                    <div class="partner-icon-wrap partner-icon-wrap--directory" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
                    </div>
                    <div class="partner-panel-head-text">
                        <div class="partner-panel-title-row">
                            <h2>Host Training Establishment Directory</h2>
                            <span class="partner-count-badge"><?= (int)$totalPartners ?> listed</span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($partners): ?>
                <div class="partner-directory-scroll">
                    <div class="partner-company-grid">
                        <?php foreach ($partners as $u): ?>
                            <?php $selectedProgramIds = array_values(array_filter(array_map('intval', explode(',', (string)($u['accepted_program_ids'] ?? ''))))); ?>
                            <article class="partner-company-card">
                                <div class="partner-company-top">
                                    <div class="partner-company-brand">
                                        <span class="partner-company-avatar"><?= e(strtoupper(substr($u['name'] ?? 'P', 0, 1))) ?></span>
                                        <div class="partner-company-brand-copy">
                                            <h3 class="partner-company-name" title="<?= e($u['name']) ?>"><?= e($u['name']) ?></h3>
                                            <p><?= e($u['contact_person'] ?? '—') ?></p>
                                        </div>
                                    </div>
                                    <span class="badge partner-company-status <?= $u['is_active'] ? 'active' : 'inactive' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span>
                                </div>

                                <div class="partner-company-meta">
                                    <div class="partner-meta-item">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                                        <div>
                                            <span>Partner ID</span>
                                            <strong><?= e($u['partner_id'] ?? '—') ?></strong>
                                        </div>
                                    </div>
                                    <div class="partner-meta-item">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                        <div>
                                            <span>Email</span>
                                            <strong title="<?= e($u['email']) ?>"><?= e($u['email']) ?></strong>
                                        </div>
                                    </div>
                                    <div class="partner-meta-item">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                                        <div>
                                            <span>Phone</span>
                                            <strong><?= e($u['contact_number'] ?: '—') ?></strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-company-doc-row">
                                    <div class="partner-company-doc-copy">
                                        <span class="partner-company-doc-label">MOA / MOU</span>
                                        <?php if (empty($u['moa_mou_file'])): ?>
                                            <span class="partner-doc-missing">Not uploaded</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($u['moa_mou_file'])): ?>
                                        <a class="btn btn-small partner-company-doc-btn" target="_blank" rel="noopener noreferrer" href="index.php?r=admin_partner_document&amp;company_id=<?= (int)$u['id'] ?>">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                                            View Document
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="partner-program-tags">
                                    <?php foreach (array_filter(array_map('trim', explode(',', (string)($u['accepted_programs'] ?? '')))) as $programCode): ?>
                                        <span><?= e($programCode) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (empty(trim((string)($u['accepted_programs'] ?? '')))): ?>
                                        <span class="partner-tag-empty">Not set</span>
                                    <?php endif; ?>
                                </div>

                                <div class="partner-company-footer">
                                    <details class="partner-company-action partner-company-edit">
                                        <summary class="btn btn-small partner-action-btn">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                            Edit Programs
                                        </summary>
                                        <form method="post" class="partner-company-edit-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="admin_update_company_programs">
                                            <input type="hidden" name="company_id" value="<?= (int)$u['id'] ?>">

                                            <div class="partner-program-picker">
                                                <?php foreach ($programs as $program): ?>
                                                    <label class="partner-program-option">
                                                        <input
                                                            type="checkbox"
                                                            name="program_ids[]"
                                                            value="<?= (int)$program['id'] ?>"
                                                            <?= in_array((int)$program['id'], $selectedProgramIds, true) ? 'checked' : '' ?>
                                                        >
                                                        <span class="partner-program-copy">
                                                            <strong><?= e($program['code']) ?></strong>
                                                            <span><?= e($program['name']) ?></span>
                                                        </span>
                                                        <em><?= (int)$program['required_hours'] ?> hrs</em>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>

                                            <button class="btn btn-small btn-primary" type="submit">Save Programs</button>
                                        </form>
                                    </details>
                                    <form method="post" class="inline partner-company-action">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="admin_toggle_user">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <input type="hidden" name="active" value="<?= $u['is_active'] ? 0 : 1 ?>">
                                        <input type="hidden" name="redirect" value="admin_partners">
                                        <button class="btn btn-small partner-action-btn <?= $u['is_active'] ? 'partner-action-btn--danger' : 'btn-primary' ?>" type="submit">
                                            <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="partner-empty-state">
                    <div class="partner-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
                    </div>
                    <strong>No Host Training Establishments yet</strong>
                    <span>Use the form on the left to add your first Host Training Establishment.</span>
                </div>
            <?php endif; ?>
        </section>

    </div>

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
