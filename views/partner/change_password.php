<?php $csrfToken = $csrfToken ?? csrf_token(); ?>
<div class="ip-settings ip-password" data-partner-password-flow>
    <header class="ip-settings-page-head">
        <a class="spf-back-link" href="<?= e(route_url('partner.settings')) ?>">
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12.5 15 7.5 10l5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>Back to Settings</span>
        </a>
        <div class="ip-settings-page-head__copy">
            <span class="spf-eyebrow">Security</span>
            <h1>Change Password</h1>
            <p>Use a strong password with at least 8 characters to keep your account secure.</p>
        </div>
    </header>

    <section class="card ip-password-card ip-password-card--verify" data-password-step="verify">
        <div class="ip-password-card__icon ip-password-card__icon--verify" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 4 7v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V7l-8-4Z"/><path d="m9 12 2 2 4-4"/></svg>
        </div>
        <div class="ip-password-card__body">
            <h2>Verify your identity</h2>
            <p class="muted">Enter your current password to continue updating your account password.</p>

            <form class="form ip-password-form" data-partner-verify-password novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="partner_verify_current_password">

                <label class="no-floating-label ip-field">
                    <span class="ip-field__label">Current Password</span>
                    <input required type="password" name="current_password" autocomplete="current-password" placeholder="Enter your current password" data-partner-current-password>
                </label>

                <p class="ip-password-feedback" data-password-feedback hidden></p>

                <div class="ip-profile-actions ip-profile-actions--single">
                    <button class="btn btn-primary" type="submit"><span class="btn-text">Continue</span><span class="spinner"></span></button>
                </div>
            </form>
        </div>
    </section>

    <section class="card ip-password-card ip-password-card--change" data-password-step="change" hidden>
        <div class="ip-password-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3"/></svg>
        </div>
        <div class="ip-password-card__body">
            <div class="ip-password-verified-pill">
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z" stroke="currentColor" stroke-width="1.6"/><path d="m6.5 10 2 2 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Identity verified</span>
            </div>
            <h2>Create a new password</h2>
            <p class="muted">Your new password will take effect immediately after saving.</p>

            <form class="form ip-password-form" data-partner-change-password novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="partner_change_password">

                <label class="no-floating-label ip-field">
                    <span class="ip-field__label">New Password</span>
                    <input required minlength="8" type="password" name="password" autocomplete="new-password" placeholder="Enter new password">
                </label>
                <label class="no-floating-label ip-field">
                    <span class="ip-field__label">Confirm New Password</span>
                    <input required minlength="8" type="password" name="confirm_password" autocomplete="new-password" placeholder="Re-enter new password">
                </label>

                <ul class="ip-password-tips">
                    <li>At least 8 characters long</li>
                    <li>Mix letters, numbers, and symbols for stronger security</li>
                </ul>

                <p class="ip-password-feedback" data-password-feedback hidden></p>

                <div class="ip-profile-actions ip-profile-actions--single">
                    <button class="btn btn-ghost" type="button" data-partner-password-back>Re-enter current password</button>
                    <button class="btn btn-primary" type="submit"><span class="btn-text">Save New Password</span><span class="spinner"></span></button>
                </div>
            </form>
        </div>
    </section>
</div>
