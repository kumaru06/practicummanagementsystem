<?php
$selectedRole = $role ?? '';
$identifierLabels = [
    'student' => 'USN (Student ID)',
    'coordinator' => 'Coordinator ID',
    'partner' => 'HTE ID',
];
$identifierPlaceholders = [
    'student' => 'Enter your USN (Student ID)',
    'coordinator' => 'Enter your Coordinator ID',
    'partner' => 'Enter your HTE ID',
];
$identifierLabel = $identifierLabels[$selectedRole] ?? 'Account ID';
$identifierPlaceholder = $identifierPlaceholders[$selectedRole] ?? 'Enter your account ID';
$roleLabels = [
    'student' => 'Student',
    'coordinator' => 'OJT Coordinator',
    'partner' => 'Host Training Establishment',
];
$roleBadgeLabel = $roleLabels[$selectedRole] ?? '';
if (!isset($flashSuccess)) {
    $flashSuccess = flash('success');
}
if (!isset($flashError)) {
    $flashError = flash('error');
}
$portalPartialParams = ['partial' => 'portal'];
if ($selectedRole !== '') {
    $portalPartialParams['portal'] = $selectedRole;
}
$portalPartialUrl = route_url('login', $portalPartialParams);
$forgotFormAction = route_url('forgot.password');
$leadMessages = [
    'student' => 'Enter your registered email and USN (Student ID). An admin will review your request.',
    'coordinator' => 'Enter your registered email and Coordinator ID. An admin will review your request.',
    'partner' => 'Enter your registered email and HTE ID. An admin will review your request.',
];
$leadMessage = $leadMessages[$selectedRole] ?? 'Enter your registered email and account ID. An admin will review your request.';
$embeddedInPortal = !empty($embeddedInPortal);
$postedEmail = (string)($_POST['email'] ?? '');
$postedIdentifier = (string)($_POST['identifier'] ?? '');
$showFailed = $flashError && empty($submitted);
?>
<?php if (!empty($submitted) && $flashSuccess): ?>
    <div class="forgot-recovery forgot-recovery--success" data-forgot-success>
        <div class="forgot-success-hero">
            <div class="forgot-ios-status forgot-ios-status--success" aria-hidden="true">
                <svg class="forgot-ios-status-svg" viewBox="0 0 52 52" fill="none">
                    <circle class="forgot-ios-status-track" cx="26" cy="26" r="22"></circle>
                    <circle class="forgot-ios-status-circle" cx="26" cy="26" r="22"></circle>
                    <path class="forgot-ios-status-check" d="M15.5 26.8 22.4 33.5 36.5 18.5"></path>
                </svg>
            </div>
            <div class="forgot-success-copy">
                <span class="forgot-success-eyebrow">Request submitted</span>
                <h2 class="forgot-success-title">You're all set for now</h2>
                <p class="forgot-success-lead"><?= e($flashSuccess) ?></p>
            </div>
        </div>
        <div class="forgot-success-note">
            <span class="forgot-success-note-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.75" fill="none"></rect>
                    <path d="m4.5 7.5 7.5 5.5 7.5-5.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" fill="none"></path>
                </svg>
            </span>
            <p>Watch your registered email for the secure reset link after an admin approves your request.</p>
        </div>
        <div class="forgot-recovery-success-actions">
            <button type="button" class="btn btn-primary js-forgot-back" data-portal-role="<?= e($selectedRole) ?>" data-portal-fetch="<?= e($portalPartialUrl) ?>">Back to Login</button>
        </div>
    </div>
<?php elseif ($showFailed): ?>
    <div class="forgot-recovery forgot-recovery--failed" data-forgot-failed>
        <div class="forgot-failed-hero">
            <div class="forgot-ios-status forgot-ios-status--failed" aria-hidden="true">
                <svg class="forgot-ios-status-svg" viewBox="0 0 52 52" fill="none">
                    <circle class="forgot-ios-status-track" cx="26" cy="26" r="22"></circle>
                    <circle class="forgot-ios-status-circle forgot-ios-status-circle--failed" cx="26" cy="26" r="22"></circle>
                    <path class="forgot-ios-status-cross forgot-ios-status-cross-a" d="M18 18 34 34"></path>
                    <path class="forgot-ios-status-cross forgot-ios-status-cross-b" d="M34 18 18 34"></path>
                </svg>
            </div>
            <div class="forgot-failed-copy">
                <span class="forgot-failed-eyebrow">Request failed</span>
                <h2 class="forgot-failed-title">We couldn't verify that</h2>
                <p class="forgot-failed-lead"><?= e($flashError) ?></p>
            </div>
        </div>
        <div class="forgot-failed-note">
            <p>Double-check your registered email and account ID, then try again.</p>
        </div>
        <div class="forgot-recovery-failed-actions">
            <button type="button" class="btn btn-primary js-forgot-try-again">Try again</button>
            <button type="button" class="btn btn-secondary js-forgot-back" data-portal-role="<?= e($selectedRole) ?>" data-portal-fetch="<?= e($portalPartialUrl) ?>">Back to Login</button>
        </div>
    </div>

    <div class="forgot-recovery is-hidden" data-forgot-retry-form hidden>
        <a class="portal-back-link portal-back-link--compact js-forgot-back" href="#" role="button" data-portal-role="<?= e($selectedRole) ?>" data-portal-fetch="<?= e($portalPartialUrl) ?>">
            <span class="portal-back-link-icon" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none"><path d="m11.5 5-5 5 5 5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span>Back to login</span>
        </a>

        <div class="forgot-recovery-head">
            <span class="portal-eyebrow">Account recovery</span>
            <h1 class="portal-heading">Forgot Password</h1>
            <?php if ($roleBadgeLabel !== ''): ?>
                <span class="forgot-recovery-role forgot-recovery-role--<?= e($selectedRole) ?>" aria-label="Account type">
                    <span class="forgot-recovery-role-dot" aria-hidden="true"></span>
                    <?= e($roleBadgeLabel) ?>
                </span>
            <?php endif; ?>
            <p class="forgot-recovery-lead"><?= e($leadMessage) ?></p>
        </div>

        <form
            method="post"
            action="<?= e($forgotFormAction) ?>"
            class="form portal-login-form forgot-recovery-form is-active js-forgot-password-form"
            data-forgot-password-form
            data-forgot-ajax="1"
            data-forgot-role-fixed="<?= e($selectedRole) ?>"
        >
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="role" value="<?= e($selectedRole) ?>">
            <input type="hidden" name="partial" value="view">

            <div class="forgot-recovery-panel">
                <label class="portal-field">
                    <span class="portal-field-label">Registered Email</span>
                    <input required type="email" name="email" autocomplete="username" placeholder="Enter your registered email" value="<?= e($postedEmail) ?>">
                </label>

                <label class="portal-field">
                    <span class="portal-field-label" data-forgot-identifier-label><?= e($identifierLabel) ?></span>
                    <input required type="text" name="identifier" autocomplete="off" placeholder="<?= e($identifierPlaceholder) ?>" value="<?= e($postedIdentifier) ?>">
                </label>
            </div>

            <div class="forgot-recovery-submit">
                <button class="btn btn-primary" type="submit"><span class="btn-text">Submit Reset Request</span><span class="spinner"></span></button>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="forgot-recovery" data-forgot-active-role="<?= e($selectedRole) ?>">
        <a class="portal-back-link portal-back-link--compact js-forgot-back" href="#" role="button" data-portal-role="<?= e($selectedRole) ?>" data-portal-fetch="<?= e($portalPartialUrl) ?>">
            <span class="portal-back-link-icon" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none"><path d="m11.5 5-5 5 5 5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span>Back to login</span>
        </a>

        <div class="forgot-recovery-head">
            <span class="portal-eyebrow">Account recovery</span>
            <h1 class="portal-heading">Forgot Password</h1>
            <?php if ($roleBadgeLabel !== ''): ?>
                <span class="forgot-recovery-role forgot-recovery-role--<?= e($selectedRole) ?>" aria-label="Account type">
                    <span class="forgot-recovery-role-dot" aria-hidden="true"></span>
                    <?= e($roleBadgeLabel) ?>
                </span>
            <?php endif; ?>
            <p class="forgot-recovery-lead"><?= e($leadMessage) ?></p>
        </div>

        <form
            method="post"
            action="<?= e($forgotFormAction) ?>"
            class="form portal-login-form forgot-recovery-form is-active js-forgot-password-form"
            data-forgot-password-form
            data-forgot-ajax="1"
            data-forgot-role-fixed="<?= e($selectedRole) ?>"
        >
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="role" value="<?= e($selectedRole) ?>">
            <input type="hidden" name="partial" value="view">

            <div class="forgot-recovery-panel">
                <label class="portal-field">
                    <span class="portal-field-label">Registered Email</span>
                    <input required type="email" name="email" autocomplete="username" placeholder="Enter your registered email" value="<?= e($postedEmail) ?>">
                </label>

                <label class="portal-field">
                    <span class="portal-field-label" data-forgot-identifier-label><?= e($identifierLabel) ?></span>
                    <input required type="text" name="identifier" autocomplete="off" placeholder="<?= e($identifierPlaceholder) ?>" value="<?= e($postedIdentifier) ?>">
                </label>
            </div>

            <div class="forgot-recovery-submit">
                <button class="btn btn-primary" type="submit"><span class="btn-text">Submit Reset Request</span><span class="spinner"></span></button>
            </div>
        </form>
    </div>
<?php endif; ?>
