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
$flashSuccess = $flashSuccess ?? flash('success');
$flashError = $flashError ?? flash('error');
$portalPartialUrl = route_url('login', ['partial' => 'portal']);
$forgotFormAction = route_url('forgot.password');
?>
<?php if (!empty($submitted) && $flashSuccess): ?>
    <div class="forgot-recovery forgot-recovery--success">
        <div class="alert success"><?= e($flashSuccess) ?></div>
        <div class="forgot-recovery-success-actions">
            <button type="button" class="btn btn-primary js-forgot-back" data-portal-fetch="<?= e($portalPartialUrl) ?>">Back to Login</button>
        </div>
    </div>
<?php else: ?>
    <div class="forgot-recovery">
        <a class="portal-back-link portal-back-link--compact js-forgot-back" href="#" role="button" data-portal-fetch="<?= e($portalPartialUrl) ?>">
            <span class="portal-back-link-icon" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none"><path d="m11.5 5-5 5 5 5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span>Back to portal selection</span>
        </a>

        <div class="forgot-recovery-head">
            <span class="portal-eyebrow">Account recovery</span>
            <h1 class="portal-heading">Forgot Password</h1>
            <p class="forgot-recovery-lead">Enter your account type, registered email, and account ID. An admin will review your request.</p>
        </div>

        <form
            method="post"
            action="<?= e($forgotFormAction) ?>"
            class="form portal-login-form forgot-recovery-form is-active js-forgot-password-form"
            data-forgot-password-form
        >
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="alert danger js-forgot-alert<?= $flashError ? '' : ' is-hidden' ?>"><?= e($flashError ?: '') ?></div>

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

            <label class="portal-field">
                <span class="portal-field-label">Registered Email</span>
                <input required type="email" name="email" autocomplete="username" placeholder="Enter your registered email" value="<?= e($_POST['email'] ?? '') ?>">
            </label>

            <label class="portal-field">
                <span class="portal-field-label" data-forgot-identifier-label><?= e($identifierLabel) ?></span>
                <input required type="text" name="identifier" autocomplete="off" placeholder="Enter your account ID" value="<?= e($_POST['identifier'] ?? '') ?>">
            </label>

            <div class="forgot-recovery-submit">
                <button class="btn btn-primary" type="submit"><span class="btn-text">Submit Reset Request</span><span class="spinner"></span></button>
            </div>
        </form>
    </div>
<?php endif; ?>
