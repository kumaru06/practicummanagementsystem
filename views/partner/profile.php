<?php
$companyName = trim((string)($company['name'] ?? current_user()['name'] ?? 'Organization'));
$companyInitial = strtoupper(substr($companyName, 0, 1));
$contactEmail = trim((string)($company['contact_email'] ?? current_user()['email'] ?? ''));
$contactNumber = (string)($company['contact_number'] ?? '');
$contactNumberDigits = preg_replace('/\D+/', '', $contactNumber);
if (str_starts_with($contactNumberDigits, '63')) {
    $contactNumberDigits = substr($contactNumberDigits, 2);
}
if (str_starts_with($contactNumberDigits, '0')) {
    $contactNumberDigits = substr($contactNumberDigits, 1);
}
$contactNumberDisplay = strlen($contactNumberDigits) === 10 ? '0' . $contactNumberDigits : $contactNumber;
?>
<div class="ip-settings ip-profile">
    <header class="ip-settings-page-head">
        <a class="spf-back-link" href="<?= e(route_url('partner.settings')) ?>">
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>Back to Settings</span>
        </a>
        <div class="ip-settings-page-head__copy">
            <span class="spf-eyebrow">Organization</span>
            <h1>Edit Profile</h1>
            <p>Update your organization and contact information used across the OJT portal.</p>
        </div>
    </header>

    <div class="ip-profile-layout">
        <aside class="card ip-profile-aside">
            <span class="ip-profile-aside__avatar"><?= e($companyInitial) ?></span>
            <div class="ip-profile-aside__identity">
                <strong><?= e($companyName) ?></strong>
                <?php if ($contactEmail !== ''): ?><small><?= e($contactEmail) ?></small><?php endif; ?>
            </div>
            <div class="ip-profile-aside__note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 10v5M12 7h.01"/></svg>
                <p>Accepted programs and MOA/MOU documents are managed by your school administrator.</p>
            </div>
        </aside>

        <section class="card ip-profile-form-card">
            <div class="ip-profile-form-card__head">
                <h2>Contact Details</h2>
                <p class="muted">Keep your organization information accurate for coordinators and students.</p>
            </div>

            <form method="post" class="form js-validate ip-profile-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="partner_save_profile">

                <div class="ip-field-grid">
                    <label class="no-floating-label ip-field">
                        <span class="ip-field__label">Company Name</span>
                        <input required name="company_name" value="<?= e($company['name'] ?? '') ?>" autocomplete="organization" placeholder="Organization or company name">
                    </label>
                    <label class="no-floating-label ip-field">
                        <span class="ip-field__label">Contact Person</span>
                        <input required name="contact_person" value="<?= e($company['contact_person'] ?? '') ?>" autocomplete="name" placeholder="Primary contact full name">
                    </label>
                    <label class="no-floating-label ip-field">
                        <span class="ip-field__label">Email Address</span>
                        <input required type="email" name="contact_email" value="<?= e($contactEmail) ?>" autocomplete="email" placeholder="name@company.com">
                    </label>
                    <label class="no-floating-label ip-field">
                        <span class="ip-field__label">Contact Number</span>
                        <input required name="contact_number" value="<?= e($contactNumberDisplay) ?>" placeholder="09XX XXX XXXX" autocomplete="tel" data-phone-format="ph">
                    </label>
                    <label class="no-floating-label ip-field ip-field--full">
                        <span class="ip-field__label">Address</span>
                        <textarea required name="address" rows="4" maxlength="500" autocomplete="street-address" placeholder="Street, city, province"><?= e($company['address'] ?? '') ?></textarea>
                    </label>
                </div>

                <div class="ip-profile-actions">
                    <a class="btn spf-cancel-btn" href="<?= e(route_url('partner.settings')) ?>">Cancel</a>
                    <button class="btn btn-primary" type="submit"><span class="btn-text">Save Profile</span><span class="spinner"></span></button>
                </div>
            </form>
        </section>
    </div>
</div>
