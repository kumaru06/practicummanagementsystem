<?php
$selectedRole = $role ?? '';
$identifierLabels = [
    'student' => 'USN (Student ID)',
    'coordinator' => 'Coordinator ID',
    'partner' => 'HTE ID',
];
$identifierLabel = $identifierLabels[$selectedRole] ?? 'Account ID';
$roleLabels = [
    'student' => 'Student',
    'coordinator' => 'OJT Coordinator',
    'partner' => 'Host Training Establishment',
];
if (!isset($flashSuccess)) {
    $flashSuccess = flash('success');
}
if (!isset($flashError)) {
    $flashError = flash('error');
}
$portalPartialUrl = route_url('login', ['portal' => $selectedRole]);
$forgotFormAction = route_url('forgot.password');
$leadMessages = [
    'student' => 'Enter your registered email and USN (Student ID). An admin will review your request.',
    'coordinator' => 'Enter your registered email and Coordinator ID. An admin will review your request.',
    'partner' => 'Enter your registered email and HTE ID. An admin will review your request.',
];
$leadMessage = $leadMessages[$selectedRole] ?? 'Enter your registered email and account ID. An admin will review your request.';
$embeddedInPortal = !empty($embeddedInPortal);
?>
<?php if (!empty($submitted) && $flashSuccess): ?>
    <div class="forgot-recovery forgot-recovery--success">
        <div class="alert success"><?= e($flashSuccess) ?></div>
        <div class="forgot-recovery-success-actions">
            <button type="button" class="btn btn-primary js-forgot-back" data-portal-role="<?= e($selectedRole) ?>" data-portal-fetch="<?= e($portalPartialUrl) ?>">Back to Login</button>
        </div>
    </div>
<?php else: ?>
    <div class="forgot-recovery">
        <a class="portal-back-link portal-back-link--compact js-forgot-back" href="#" role="button" data-portal-role="<?= e($selectedRole) ?>" data-portal-fetch="<?= e($portalPartialUrl) ?>">
            <span class="portal-back-link-icon" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none"><path d="m11.5 5-5 5 5 5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span>Back to login</span>
        </a>

        <div class="forgot-recovery-head">
            <span class="portal-eyebrow">Account recovery</span>
            <h1 class="portal-heading">Forgot Password</h1>
            <span class="forgot-recovery-role" aria-label="Account type">
                <span class="forgot-recovery-role-dot" aria-hidden="true"></span>
                <?= e($roleLabels[$selectedRole] ?? ucfirst($selectedRole)) ?> account
            </span>
            <p class="forgot-recovery-lead"><?= e($leadMessage) ?></p>
        </div>

        <form
            method="post"
            action="<?= e($forgotFormAction) ?>"
            class="form portal-login-form forgot-recovery-form is-active js-forgot-password-form"
            data-forgot-password-form
            data-forgot-ajax="<?= $embeddedInPortal ? '1' : '0' ?>"
            data-forgot-role-fixed="<?= e($selectedRole) ?>"
        >
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="role" value="<?= e($selectedRole) ?>">
            <?php if ($embeddedInPortal): ?>
                <input type="hidden" name="partial" value="view">
            <?php endif; ?>

            <div class="alert danger js-forgot-alert<?= $flashError ? '' : ' is-hidden' ?>"><?= e($flashError ?: '') ?></div>

            <div class="forgot-recovery-panel">
            <label class="portal-field">
                <span class="portal-field-label">Registered Email</span>
                <input required type="email" name="email" autocomplete="username" placeholder="Enter your registered email" value="<?= e($_POST['email'] ?? '') ?>">
            </label>

            <label class="portal-field">
                <span class="portal-field-label" data-forgot-identifier-label><?= e($identifierLabel) ?></span>
                <input required type="text" name="identifier" autocomplete="off" placeholder="Enter your account ID" value="<?= e($_POST['identifier'] ?? '') ?>">
            </label>
            </div>

            <div class="forgot-recovery-submit">
                <button class="btn btn-primary" type="submit"><span class="btn-text">Submit Reset Request</span><span class="spinner"></span></button>
            </div>
        </form>
    </div>
<?php endif; ?>
