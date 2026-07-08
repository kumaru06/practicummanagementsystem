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

    <div class="partner-page-intro">
        <div class="partner-page-intro-copy">
            <p class="partner-page-eyebrow">Partner Management</p>
            <p class="partner-page-desc">Register host training establishments, assign accepted programs, and manage MOA/MOU agreements for OJT deployment.</p>
        </div>
    </div>

    <div class="partner-stats-strip">
        <div class="partner-stat-card partner-stat-total">
            <div class="partner-stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg>
            </div>
            <div class="partner-stat-body">
                <span>Host Training Establishments</span>
                <strong><?= (int)$totalPartners ?></strong>
            </div>
        </div>
        <div class="partner-stat-card partner-stat-active">
            <div class="partner-stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="partner-stat-body">
                <span>Active</span>
                <strong><?= (int)$activePartners ?></strong>
            </div>
        </div>
        <div class="partner-stat-card partner-stat-inactive">
            <div class="partner-stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            </div>
            <div class="partner-stat-body">
                <span>Inactive</span>
                <strong><?= (int)$inactivePartners ?></strong>
            </div>
        </div>
        <div class="partner-stat-card partner-stat-programs">
            <div class="partner-stat-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
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
                <div class="partner-icon-wrap partner-icon-wrap--add">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                </div>
                <div class="partner-panel-head-text">
                    <h2>Add Host Training Establishment</h2>
                    <p>Fill in company details below. Choose accepted programs in the section at the bottom.</p>
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
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
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
                    <div class="partner-icon-wrap partner-icon-wrap--directory">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg>
                    </div>
                    <div class="partner-panel-head-text">
                        <div class="partner-panel-title-row">
                            <h2>Host Training Establishment Directory</h2>
                            <span class="partner-count-badge"><?= (int)$totalPartners ?> listed</span>
                        </div>
                        <p>All registered partners and their deployment details.</p>
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
                                            <p><?= e($u['contact_person'] ?? 'â€”') ?></p>
                                        </div>
                                    </div>
                                    <span class="badge partner-company-status <?= $u['is_active'] ? 'active' : 'inactive' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span>
                                </div>

                                <div class="partner-company-meta">
                                    <div class="partner-meta-item">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M4 12h10M4 17h6"/></svg>
                                        <div>
                                            <span>Partner ID</span>
                                            <strong><?= e($u['partner_id'] ?? 'â€”') ?></strong>
                                        </div>
                                    </div>
                                    <div class="partner-meta-item">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                        <div>
                                            <span>Email</span>
                                            <strong title="<?= e($u['email']) ?>"><?= e($u['email']) ?></strong>
                                        </div>
                                    </div>
                                    <div class="partner-meta-item">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
                                        <div>
                                            <span>Phone</span>
                                            <strong><?= e($u['contact_number'] ?: 'â€”') ?></strong>
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
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"/><polyline points="14 2 14 8 20 8"/></svg>
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
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
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
                    <div class="partner-empty-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg>
                    </div>
                    <strong>No Host Training Establishments yet</strong>
                    <span>Use the form on the left to add your first Host Training Establishment.</span>
                </div>
            <?php endif; ?>
        </section>

    </div>

    <section class="partner-panel partner-full-card">
        <div class="partner-panel-head">
            <div class="partner-icon-wrap partner-icon-wrap--programs">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 10v6"/><path d="M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>
            </div>
            <div class="partner-panel-head-text">
                <h2>Accepted Programs <span class="field-required">*</span></h2>
                <p>Select which programs the new Host Training Establishment can accept students from.</p>
            </div>
        </div>

        <?php if (empty($programs)): ?>
            <div class="partner-empty-state partner-empty-state--compact">
                <div class="partner-empty-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
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
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                <span class="btn-text">Create Host Training Establishment</span>
                <span class="spinner"></span>
            </button>
        </div>
    </section>

</div>
