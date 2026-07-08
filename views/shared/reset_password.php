<?php
$flashError = $tokenError ?? flash('error');
$token = trim((string)($request['reset_token'] ?? $_GET['token'] ?? $_POST['token'] ?? ''));
$forgotRole = $tokenRole ?? '';
$showResetForm = $token !== '' && empty($tokenError);
$forgotUrl = $forgotRole !== ''
    ? route_url('forgot.password', ['role' => $forgotRole])
    : route_url('login');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Reset Password - AMA Practicum System</title>
    <link rel="icon" type="image/jpeg" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=20260707-reset">
    <link rel="stylesheet" href="<?= e(asset('assets/css/login.css')) ?>?v=20260708-reset-fix">
</head>
<body class="login-page">
    <div class="login-split">
        <?php require __DIR__ . '/partials/login-info-panel.php'; ?>
        <?php require __DIR__ . '/partials/login-hero-open.php'; ?>
                <div class="login-card portal-login-card forgot-password-card is-revealed">
                    <div class="portal-login-card-inner">
                        <div class="brand login-brand">
                            <img src="<?= e(asset('assets/image/main/logo/amalogo.png')) ?>" alt="AMA Logo" class="login-logo" draggable="false">
                            <div class="login-brand-copy">
                                <strong>Computer College &mdash; Davao</strong>
                                <span>Practicum Management System</span>
                            </div>
                        </div>

                        <div class="portal-copy">
                            <span class="portal-eyebrow">Secure reset</span>
                            <h1 class="portal-heading">Create New Password</h1>
                            <p class="portal-sub">Choose a strong password with at least 8 characters.</p>
                        </div>

                        <div class="alert danger<?= $flashError ? '' : ' is-hidden' ?>"><?= e($flashError ?: '') ?></div>

                        <?php if ($showResetForm): ?>
                        <form method="post" class="form portal-login-form is-active">
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

                            <button class="btn btn-primary" type="submit"><span class="btn-text">Save New Password</span><span class="spinner"></span></button>
                        </form>
                        <?php elseif ($token !== ''): ?>
                        <div class="portal-form-footer-links">
                            <p class="portal-register-link"><a href="<?= e($forgotUrl) ?>">Request a new reset link</a></p>
                        </div>
                        <?php endif; ?>

                        <p class="portal-register-link"><a href="<?= e(route_url('login')) ?>">Back to Login</a></p>
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
});
</script>
</body>
</html>
