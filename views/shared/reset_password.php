<?php
$flashError = $tokenError ?? flash('error');
$token = trim((string)($request['reset_token'] ?? $_GET['token'] ?? $_POST['token'] ?? ''));
$forgotRole = $tokenRole ?? ($_GET['role'] ?? '');
$isUpdated = isset($_GET['updated']);
$showResetForm = !$isUpdated && $token !== '' && empty($tokenError);
$loginUrl = in_array($forgotRole, ['student', 'coordinator', 'partner'], true)
    ? route_url('login', ['portal' => $forgotRole])
    : route_url('login');
$forgotUrl = in_array($forgotRole, ['student', 'coordinator', 'partner'], true)
    ? route_url('forgot.password', ['role' => $forgotRole])
    : route_url('login');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= $isUpdated ? 'Password Updated' : 'Reset Password' ?> - AMA Practicum System</title>
    <link rel="icon" type="image/jpeg" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=20260707-reset">
    <link rel="stylesheet" href="<?= e(asset('assets/css/login.css')) ?>?v=20260826-reset-success">
</head>
<body class="login-page">
    <div class="login-split">
        <?php require __DIR__ . '/partials/login-info-panel.php'; ?>
        <?php require __DIR__ . '/partials/login-hero-open.php'; ?>
                <div class="login-card portal-login-card forgot-password-card reset-password-card is-revealed">
                    <div class="portal-login-card-inner">
                        <div class="brand login-brand">
                            <img src="<?= e(asset('assets/image/main/logo/amalogo.png')) ?>" alt="AMA Logo" class="login-logo" draggable="false">
                            <div class="login-brand-copy">
                                <strong>Computer College &mdash; Davao</strong>
                                <span>Practicum Management System</span>
                            </div>
                        </div>

                        <?php if ($isUpdated): ?>
                            <div class="reset-success" data-reset-success data-login-url="<?= e($loginUrl) ?>">
                                <div class="reset-success-hero">
                                    <div class="forgot-ios-status forgot-ios-status--success" aria-hidden="true">
                                        <svg class="forgot-ios-status-svg" viewBox="0 0 52 52" fill="none">
                                            <circle class="forgot-ios-status-track" cx="26" cy="26" r="22"></circle>
                                            <circle class="forgot-ios-status-circle" cx="26" cy="26" r="22"></circle>
                                            <path class="forgot-ios-status-check" d="M15.5 26.8 22.4 33.5 36.5 18.5"></path>
                                        </svg>
                                    </div>
                                    <div class="reset-success-copy">
                                        <span class="forgot-success-eyebrow">Password updated</span>
                                        <h1 class="forgot-success-title">You're all set</h1>
                                        <p class="forgot-success-lead">Your password has been updated. You can now sign in with your new password.</p>
                                    </div>
                                </div>
                                <div class="forgot-success-note">
                                    <p>Use your updated password on the next sign-in. For security, keep it private.</p>
                                </div>
                                <div class="reset-success-actions">
                                    <a class="btn btn-primary" href="<?= e($loginUrl) ?>">Continue to Login</a>
                                    <p class="reset-success-countdown" data-reset-countdown aria-live="polite">
                                        Redirecting in <strong data-reset-countdown-value>8</strong>s&hellip;
                                    </p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="portal-copy">
                                <span class="portal-eyebrow">Secure reset</span>
                                <h1 class="portal-heading">Create New Password</h1>
                                <p class="portal-sub">Choose a strong password with at least 8 characters.</p>
                            </div>

                            <div class="alert danger<?= $flashError ? '' : ' is-hidden' ?>"><?= e($flashError ?: '') ?></div>

                            <?php if ($showResetForm): ?>
                            <form method="post" class="form portal-login-form is-active" data-reset-password-form>
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="token" value="<?= e($token) ?>">

                                <label class="portal-field">
                                    <span class="portal-field-label">New Password</span>
                                    <input required minlength="8" type="password" name="password" autocomplete="new-password" placeholder="Enter new password">
                                </label>

                                <label class="portal-field">
                                    <span class="portal-field-label">Confirm New Password</span>
                                    <input required minlength="8" type="password" name="confirm_password" autocomplete="new-password" placeholder="Re-enter new password">
                                </label>

                                <button class="btn btn-primary" type="submit" data-reset-submit>
                                    <span class="btn-text">Save New Password</span>
                                    <span class="spinner"></span>
                                </button>
                            </form>
                            <?php elseif ($token !== ''): ?>
                            <div class="portal-form-footer-links">
                                <p class="portal-register-link"><a href="<?= e($forgotUrl) ?>">Request a new reset link</a></p>
                            </div>
                            <?php endif; ?>

                            <p class="portal-register-link reset-password-back"><a href="<?= e($loginUrl) ?>">Back to Login</a></p>
                        <?php endif; ?>
                    </div>
                </div>
        <?php require __DIR__ . '/partials/login-hero-close.php'; ?>
    </div>
<script>
document.addEventListener('dragstart', function (event) {
    if (event.target instanceof HTMLImageElement && event.target.closest('.login-page')) {
        event.preventDefault();
    }
}, true);
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.login-page img').forEach(function (img) {
        img.setAttribute('draggable', 'false');
    });
    document.querySelectorAll('.login-info-slideshow').forEach(function (slideshow) {
        var slides = slideshow.querySelectorAll('.login-info-slide');
        if (slides.length < 2) return;
        var index = 0;
        window.setInterval(function () {
            slides[index].classList.remove('is-active');
            index = (index + 1) % slides.length;
            slides[index].classList.add('is-active');
        }, 5000);
    });

    var success = document.querySelector('[data-reset-success]');
    if (success) {
        success.classList.remove('is-revealed');
        void success.offsetWidth;
        requestAnimationFrame(function () {
            success.classList.add('is-revealed');
        });

        var loginUrl = success.getAttribute('data-login-url') || 'auth.php';
        var countdownEl = success.querySelector('[data-reset-countdown-value]');
        var remaining = 8;
        var tick = function () {
            if (countdownEl) countdownEl.textContent = String(remaining);
            if (remaining <= 0) {
                window.location.href = loginUrl;
                return;
            }
            remaining -= 1;
            window.setTimeout(tick, 1000);
        };
        window.setTimeout(tick, 900);
    }

    var form = document.querySelector('[data-reset-password-form]');
    var submitBtn = document.querySelector('[data-reset-submit]');
    if (form && submitBtn) {
        form.addEventListener('submit', function (event) {
            if (event.defaultPrevented) return;
            if (typeof form.reportValidity === 'function' && !form.reportValidity()) return;
            form.classList.add('is-submitting');
            submitBtn.classList.add('loading');
            submitBtn.setAttribute('aria-busy', 'true');
            window.setTimeout(function () {
                submitBtn.disabled = true;
            }, 0);
        });
    }
});
</script>
</body>
</html>
