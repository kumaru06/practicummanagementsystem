<?php
$csrfToken = $csrfToken ?? csrf_token();
$isFirstLogin = !empty($isFirstLogin);
$verifyTitle = $isFirstLogin ? 'Verify your temporary password' : 'Verify your identity';
$verifyDescription = $isFirstLogin
    ? 'Enter the temporary password from your credentials email before setting a new one.'
    : 'Enter your current password to continue updating your account password.';
$currentPasswordLabel = $isFirstLogin ? 'Temporary Password' : 'Current Password';
$currentPasswordPlaceholder = $isFirstLogin
    ? 'Enter your temporary password'
    : 'Enter your current password';
$changeTitle = $isFirstLogin ? 'Set a new password' : 'Create a new password';
$changeDescription = $isFirstLogin
    ? 'For account security, Host Training Establishments must set a new password before accessing the portal.'
    : 'Your new password will take effect immediately after saving.';
$pageTitle = $isFirstLogin ? 'Change Temporary Password' : 'Change Password';
$pageSubtitle = $isFirstLogin
    ? 'Replace your temporary password to unlock the Host Training Establishment portal.'
    : 'Use a strong password with at least 8 characters to keep your account secure.';
?>
<div class="hte-settings hte-password" data-partner-password-flow data-is-first-login="<?= $isFirstLogin ? '1' : '0' ?>" data-success-redirect="<?= e(route_url('partner.settings')) ?>">
    <header class="hte-settings-page-head">
        <?php if (!$isFirstLogin): ?>
            <a class="spf-back-link" href="<?= e(route_url('partner.settings')) ?>">
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Back to Settings</span>
            </a>
        <?php endif; ?>
        <div class="hte-settings-page-head__copy">
            <span class="spf-eyebrow">Security</span>
            <h1><?= e($pageTitle) ?></h1>
            <p><?= e($pageSubtitle) ?></p>
        </div>
    </header>

    <section class="card hte-password-card hte-password-card--verify" data-password-step="verify">
        <div class="hte-password-card__icon hte-password-card__icon--verify" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 4 7v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V7l-8-4Z"/><path d="m9 12 2 2 4-4"/></svg>
        </div>
        <div class="hte-password-card__body">
            <h2><?= e($verifyTitle) ?></h2>
            <p class="muted"><?= e($verifyDescription) ?></p>

            <form class="form hte-password-form" data-partner-verify-password novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="partner_verify_current_password">

                <label class="no-floating-label hte-field">
                    <span class="ip-field__label"><?= e($currentPasswordLabel) ?></span>
                    <input required type="password" name="current_password" autocomplete="current-password" placeholder="<?= e($currentPasswordPlaceholder) ?>" data-partner-current-password>
                </label>

                <p class="hte-password-feedback" data-password-feedback hidden></p>

                <div class="hte-profile-actions hte-profile-actions--single">
                    <button class="btn btn-primary" type="submit"><span class="btn-text">Continue</span><span class="spinner"></span></button>
                </div>
            </form>
        </div>
    </section>

    <section class="card hte-password-card hte-password-card--change" data-password-step="change" hidden>
        <div class="hte-password-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3"/></svg>
        </div>
        <div class="hte-password-card__body">
            <div class="hte-password-verified-pill">
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z" stroke="currentColor" stroke-width="1.6"/><path d="m6.5 10 2 2 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Identity verified</span>
            </div>
            <h2><?= e($changeTitle) ?></h2>
            <p class="muted"><?= e($changeDescription) ?></p>

            <form class="form hte-password-form" data-partner-change-password novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="partner_change_password">
                <input type="hidden" name="reauth_token" value="" data-partner-reauth-token>

                <label class="no-floating-label hte-field">
                    <span class="ip-field__label">New Password</span>
                    <input required minlength="8" type="password" name="password" autocomplete="new-password" placeholder="Enter new password" data-partner-new-password>
                </label>
                <div class="password-gate-strength" data-partner-password-strength aria-live="polite" hidden>
                    <div class="password-gate-strength__track" aria-hidden="true">
                        <span class="password-gate-strength__fill" data-partner-strength-fill></span>
                    </div>
                    <span class="password-gate-strength__label" data-partner-strength-label></span>
                </div>
                <label class="no-floating-label hte-field">
                    <span class="ip-field__label">Confirm New Password</span>
                    <input required minlength="8" type="password" name="confirm_password" autocomplete="new-password" placeholder="Re-enter new password" data-partner-confirm-password>
                </label>
                <p class="password-gate-match" data-partner-password-match aria-live="polite" hidden></p>

                <ul class="hte-password-tips">
                    <li>At least 8 characters long</li>
                    <li>Mix letters, numbers, and symbols for stronger security</li>
                </ul>

                <p class="hte-password-feedback" data-password-feedback hidden></p>

                <div class="hte-profile-actions hte-profile-actions--single">
                    <button class="btn btn-primary hte-password-submit" type="submit"><span class="btn-text">Save New Password</span><span class="spinner"></span></button>
                </div>
            </form>
        </div>
    </section>
</div>
