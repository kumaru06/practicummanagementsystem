<?php
$roleOptions = $roleOptions ?? [
    'student' => 'Student',
    'coordinator' => 'OJT Coordinator',
    'partner' => 'Industry Partner',
];
$selectedRole = $role ?? '';
$identifierLabels = [
    'student' => 'USN (Student ID)',
    'coordinator' => 'Coordinator ID',
    'partner' => 'Partner ID',
];
$identifierLabel = $selectedRole ? ($identifierLabels[$selectedRole] ?? 'Account ID') : 'Account ID';
$roleLabels = [
    'student' => 'Student',
    'coordinator' => 'OJT Coordinator',
    'partner' => 'Industry Partner',
];
$flashSuccess = $flashSuccess ?? flash('success');
$flashError = $flashError ?? flash('error');
$portalPartialUrl = $selectedRole
    ? route_url('login', ['portal' => $selectedRole])
    : route_url('login', ['partial' => 'portal']);
$forgotFormAction = route_url('forgot.password');
$leadMessages = [
    'student' => 'Enter your registered email and USN (Student ID). An admin will review your request.',
    'coordinator' => 'Enter your registered email and Coordinator ID. An admin will review your request.',
    'partner' => 'Enter your registered email and Partner ID. An admin will review your request.',
];
$leadMessage = $selectedRole
    ? ($leadMessages[$selectedRole] ?? 'Enter your registered email and account ID. An admin will review your request.')
    : 'Enter your account type, registered email, and account ID. An admin will review your request.';
$backLabel = $selectedRole ? 'Back to login' : 'Back to portal selection';
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
            <span><?= e($backLabel) ?></span>
        </a>

        <div class="forgot-recovery-head">
            <span class="portal-eyebrow">Account recovery</span>
            <h1 class="portal-heading">Forgot Password</h1>
            <?php if ($selectedRole): ?>
                <span class="forgot-recovery-role" aria-label="Account type">
                    <span class="forgot-recovery-role-dot" aria-hidden="true"></span>
                    <?= e($roleLabels[$selectedRole] ?? ucfirst($selectedRole)) ?> account
                </span>
            <?php endif; ?>
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
            <?php if ($embeddedInPortal): ?>
                <input type="hidden" name="partial" value="view">
            <?php endif; ?>

            <div class="alert danger js-forgot-alert<?= $flashError ? '' : ' is-hidden' ?>"><?= e($flashError ?: '') ?></div>

            <div class="forgot-recovery-panel">
            <?php if ($selectedRole): ?>
                <input type="hidden" name="role" value="<?= e($selectedRole) ?>">
            <?php else: ?>
                <label class="portal-field">
                    <span class="portal-field-label">Account Type</span>
                    <span class="portal-select-wrap">
                        <select name="role" required data-forgot-role data-select-label="Select account type">
                            <option value="">Select account type</option>
                            <?php foreach ($roleOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $selectedRole === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </span>
                </label>
            <?php endif; ?>

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
