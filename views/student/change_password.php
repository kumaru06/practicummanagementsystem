<?php
$csrfToken = $csrfToken ?? csrf_token();
$isFirstLogin = !empty($isFirstLogin);
$verifyTitle = $isFirstLogin ? 'Verify your temporary password' : 'Verify your identity';
$verifyDescription = $isFirstLogin
    ? 'Enter the temporary password you used to sign in before setting a new one.'
    : 'Enter your current password to continue updating your account password.';
$currentPasswordPlaceholder = $isFirstLogin
    ? 'Enter your temporary password'
    : 'Enter your current password';
$changeTitle = $isFirstLogin ? 'Change your temporary password' : 'Create a new password';
$changeDescription = $isFirstLogin
    ? 'For account security, students must set a new password before accessing the OJT portal.'
    : 'Your new password will take effect immediately after saving.';
?>
<div class="password-gate-flow" data-student-password-flow data-is-first-login="<?= $isFirstLogin ? '1' : '0' ?>">
    <section class="card password-gate password-gate-step" data-password-step="verify">
        <div class="gate-icon gate-icon--verify" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 4 7v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V7l-8-4Z"/><path d="m9 12 2 2 4-4"/></svg>
        </div>
        <h2><?= e($verifyTitle) ?></h2>
        <p class="muted"><?= e($verifyDescription) ?></p>
        <form class="form narrow-form" data-student-verify-password novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="student_verify_current_password">
            <label><?= e($currentPasswordPlaceholder) ?>
                <input required type="password" name="current_password" autocomplete="current-password" data-student-current-password>
            </label>
            <p class="password-gate-feedback" data-password-feedback hidden></p>
            <button class="btn btn-primary" type="submit"><span class="btn-text">Continue</span><span class="spinner"></span></button>
        </form>
    </section>

    <section class="card password-gate password-gate-step" data-password-step="change" hidden>
        <div class="gate-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M17 8V7a5 5 0 0 0-10 0v1H5v14h14V8h-2ZM9 7a3 3 0 0 1 6 0v1H9V7Zm4 9.7V19h-2v-2.3a2 2 0 1 1 2 0Z"/></svg>
        </div>
        <div class="password-gate-verified-pill">
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z" stroke="currentColor" stroke-width="1.6"/><path d="m6.5 10 2 2 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>Identity verified</span>
        </div>
        <h2><?= e($changeTitle) ?></h2>
        <p class="muted"><?= e($changeDescription) ?></p>
        <form class="form narrow-form" data-student-change-password novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="student_change_password">
            <label>Enter new password
                <input required minlength="8" type="password" name="password" autocomplete="new-password" data-student-new-password>
            </label>
            <div class="password-gate-strength" data-student-password-strength aria-live="polite" hidden>
                <div class="password-gate-strength__track" aria-hidden="true">
                    <span class="password-gate-strength__fill" data-student-strength-fill></span>
                </div>
                <span class="password-gate-strength__label" data-student-strength-label></span>
            </div>
            <label>Re-enter new password
                <input required minlength="8" type="password" name="confirm_password" autocomplete="new-password" data-student-confirm-password>
            </label>
            <p class="password-gate-match" data-student-password-match aria-live="polite" hidden></p>
            <p class="password-gate-feedback" data-password-feedback hidden></p>
            <button class="btn btn-primary password-gate-submit" type="submit"><span class="btn-text">Save New Password</span><span class="spinner"></span></button>
        </form>
    </section>
</div>
