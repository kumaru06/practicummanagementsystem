<?php
$companyName = trim((string)($company['name'] ?? current_user()['name'] ?? 'Organization'));
$companyInitial = strtoupper(substr($companyName, 0, 1));
$contactEmail = trim((string)($company['contact_email'] ?? current_user()['email'] ?? ''));
$profilePhotoUrl = partner_profile_photo_url($company ?? null);
?>
<div class="ip-settings">
    <header class="ip-settings-hero card">
        <div class="ip-settings-hero__copy">
            <span class="eyebrow">Account</span>
            <h2>Settings &amp; Security</h2>
            <p class="muted">Manage your organization profile, login email, and account password.</p>
        </div>
        <div class="ip-settings-hero__badge<?= $profilePhotoUrl !== '' ? ' ip-settings-hero__badge--photo' : '' ?>" aria-hidden="true">
            <?php if ($profilePhotoUrl !== ''): ?>
                <img src="<?= e($profilePhotoUrl) ?>" alt="">
            <?php else: ?>
                <span><?= e($companyInitial) ?></span>
            <?php endif; ?>
        </div>
    </header>

    <div class="ip-settings-grid">
        <a class="ip-settings-action card" href="<?= e(route_url('partner.profile')) ?>">
            <span class="ip-settings-action__icon ip-settings-action__icon--profile">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4"/></svg>
            </span>
            <span class="ip-settings-action__copy">
                <strong>Edit Profile</strong>
                <small>Update company name, contact person, email, phone, address, and profile photo.</small>
            </span>
            <span class="ip-settings-action__arrow" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none"><path d="M7.5 5 12.5 10l-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
        </a>

        <a class="ip-settings-action card" href="<?= e(route_url('partner.password.edit')) ?>">
            <span class="ip-settings-action__icon ip-settings-action__icon--password">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3"/></svg>
            </span>
            <span class="ip-settings-action__copy">
                <strong>Change Password</strong>
                <small>Set a new secure password for your Industry Partner account.</small>
            </span>
            <span class="ip-settings-action__arrow" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none"><path d="M7.5 5 12.5 10l-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
        </a>
    </div>

    <section class="card ip-settings-overview">
        <div class="ip-settings-overview__head">
            <div>
                <h2>Organization Profile</h2>
                <p class="muted">Your current organization details on file.</p>
            </div>
            <a class="btn btn-small" href="<?= e(route_url('partner.profile')) ?>">Edit details</a>
        </div>

        <?php if ($company): ?>
            <div class="ip-settings-overview__body">
                <div class="ip-settings-org-card">
                    <?php if ($profilePhotoUrl !== ''): ?>
                        <span class="ip-settings-org-card__avatar ip-settings-org-card__avatar--photo"><img src="<?= e($profilePhotoUrl) ?>" alt=""></span>
                    <?php else: ?>
                        <span class="ip-settings-org-card__avatar"><?= e($companyInitial) ?></span>
                    <?php endif; ?>
                    <div>
                        <strong><?= e($company['name'] ?? '') ?></strong>
                        <small><?= e($company['contact_person'] ?? '') ?></small>
                    </div>
                </div>

                <dl class="ip-settings-meta">
                    <div class="ip-settings-meta__item">
                        <dt>Partner ID</dt>
                        <dd><?= e($company['partner_id'] ?? '—') ?></dd>
                    </div>
                    <div class="ip-settings-meta__item">
                        <dt>Email</dt>
                        <dd><?= e($contactEmail !== '' ? $contactEmail : '—') ?></dd>
                    </div>
                    <div class="ip-settings-meta__item">
                        <dt>Contact Number</dt>
                        <dd><?= e($company['contact_number'] ?? '—') ?></dd>
                    </div>
                    <div class="ip-settings-meta__item ip-settings-meta__item--full">
                        <dt>Address</dt>
                        <dd><?= e($company['address'] ?? '—') ?></dd>
                    </div>
                </dl>
            </div>
        <?php else: ?>
            <div class="ip-empty-state">
                <strong>Profile not configured</strong>
                <p class="muted">Your organization profile is not set up yet. Contact your administrator for assistance.</p>
            </div>
        <?php endif; ?>
    </section>
</div>
