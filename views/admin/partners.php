<?php
$totalPartners = count($partners);
$activePartners = count(array_filter($partners, static fn ($partner) => (int)($partner['is_active'] ?? 0) === 1));
$inactivePartners = $totalPartners - $activePartners;
$totalPrograms = count($programs);
?>

<!-- Hidden create-partner form (for cross-section field association) -->
<form
    id="create-partner-form"
    method="post"
    class="form js-validate"
    data-require-checkbox-group="program_ids[]"
    data-require-checkbox-message="Select at least one accepted program/course."
>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="admin_create_company">
</form>

<div class="partner-page">

    <!-- ① Stats Strip -->
    <div class="partner-stats-strip">
        <div class="partner-stat-card">
            <div class="partner-stat-icon">
                <svg viewBox="0 0 24 24"><path d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg>
            </div>
            <div class="partner-stat-body">
                <strong><?= (int)$totalPartners ?></strong>
                <span>Total Partners</span>
            </div>
        </div>
        <div class="partner-stat-card">
            <div class="partner-stat-icon">
                <svg viewBox="0 0 24 24"><path d="m9 16.2-3.5-3.5L4 14.2 9 19l11-11-1.5-1.5L9 16.2Z"/></svg>
            </div>
            <div class="partner-stat-body">
                <strong><?= (int)$activePartners ?></strong>
                <span>Active</span>
            </div>
        </div>
        <div class="partner-stat-card">
            <div class="partner-stat-icon">
                <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm5 11H7v-2h10v2Z"/></svg>
            </div>
            <div class="partner-stat-body">
                <strong><?= (int)$inactivePartners ?></strong>
                <span>Inactive</span>
            </div>
        </div>
        <div class="partner-stat-card">
            <div class="partner-stat-icon">
                <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4V5Zm2 2v10h12V7H6Zm2 2h8v2H8V9Zm0 4h6v2H8v-2Z"/></svg>
            </div>
            <div class="partner-stat-body">
                <strong><?= (int)$totalPrograms ?></strong>
                <span>Programs</span>
            </div>
        </div>
    </div>

    <!-- ② 2-Col: Add Partner (left) | Partner Directory (right) -->
    <div class="partner-admin-layout">

        <section class="partner-form-card">
            <div class="partner-form-head">
                <h2>Add Partner Company</h2>
                <p>Fill in the company details below. Choose accepted programs in the section below.</p>
            </div>

            <div class="partner-form-section">
                <div class="partner-section-title">
                    <h3>Company Info</h3>
                    <p>Basic identity and contact details.</p>
                </div>
                <div class="partner-form-fields">
                    <label>Company Name<input form="create-partner-form" required name="company_name"></label>
                    <label>Name<input form="create-partner-form" required name="contact_person" autocomplete="name"></label>
                    <label>Email Address<input form="create-partner-form" required type="email" name="contact_email"></label>
                    <label>Contact Number<input form="create-partner-form" required name="contact_number" inputmode="numeric" autocomplete="tel-national" maxlength="16" data-phone-format="ph" pattern="\+63\s9\d{2}\s\d{3}\s\d{4}" title="Use format +63 951 192 5735"></label>
                </div>
                <label>
                    Company Address
                    <textarea form="create-partner-form" required name="address" maxlength="500"></textarea>
                </label>
            </div>

            <div class="partner-credential-strip">
                <svg viewBox="0 0 24 24"><path d="M12 2a5 5 0 0 1 5 5v1h1a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h1V7a5 5 0 0 1 5-5Zm3 6V7a3 3 0 1 0-6 0v1h6Z"/></svg>
                <div>
                    <strong>Auto credential delivery</strong>
                    <span>A temporary password is generated and emailed to the partner on creation.</span>
                </div>
            </div>
        </section>

        <section class="partner-directory-card">
            <div class="partner-directory-head">
                <div>
                    <h2>Partner Directory</h2>
                    <p>All registered partner companies and their details.</p>
                </div>
                <span class="partner-count-pill"><?= (int)$totalPartners ?> listed</span>
            </div>

            <?php if ($partners): ?>
                <div class="partner-directory-scroll">
                <div class="partner-company-grid">
                    <?php foreach ($partners as $u): ?>
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
                                <div><span>Email</span><strong><?= e($u['email']) ?></strong></div>
                                <div><span>Phone</span><strong><?= e($u['contact_number'] ?: '—') ?></strong></div>
                            </div>
                            <div class="partner-program-tags">
                                <?php foreach (array_filter(array_map('trim', explode(',', (string)($u['accepted_programs'] ?? '')))) as $programCode): ?>
                                    <span><?= e($programCode) ?></span>
                                <?php endforeach; ?>
                                <?php if (empty(trim((string)($u['accepted_programs'] ?? '')))): ?><span style="background:#f3f4f6;color:#9ca3af;border-color:#e5e7eb;">Not set</span><?php endif; ?>
                            </div>
                            <div class="partner-company-footer">
                                <form method="post" class="inline partner-company-action">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="admin_resend_company_credentials">
                                    <input type="hidden" name="company_id" value="<?= (int)$u['id'] ?>">
                                    <button class="btn btn-small" type="submit">Resend Credentials</button>
                                </form>
                                <form method="post" class="inline partner-company-action">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="admin_toggle_user">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                    <input type="hidden" name="active" value="<?= $u['is_active'] ? 0 : 1 ?>">
                                    <input type="hidden" name="redirect" value="admin_partners">
                                    <button class="btn btn-small <?= $u['is_active'] ? '' : 'btn-primary' ?>" type="submit"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                </div>
            <?php else: ?>
                <div class="partner-empty-state">
                    <strong>No partner companies yet.</strong>
                    <span>Use the form on the left to add your first partner.</span>
                </div>
            <?php endif; ?>
        </section>

    </div>

    <!-- ④ Full-width: Accepted Programs -->
    <section class="partner-full-card">
        <div class="partner-full-head">
            <div>
                <h2>Accepted Programs</h2>
                <p class="muted">Select which programs the new partner company can accept students from.</p>
            </div>
        </div>

        <?php if (empty($programs)): ?>
            <div class="partner-empty-state">
                <strong>No programs available yet.</strong>
                <span>Create programs first in Programs / Courses before adding partners.</span>
            </div>
        <?php else: ?>
            <div class="partner-programs-grid">
                <?php foreach ($programs as $program): ?>
                    <label class="partner-program-option" form="create-partner-form">
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
            <button form="create-partner-form" class="btn btn-primary" type="submit">
                <span class="btn-text">Create Partner Company</span>
                <span class="spinner"></span>
            </button>
        </div>
    </section>

</div>
