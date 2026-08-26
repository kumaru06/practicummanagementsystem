<?php
$roleOptions = [
    'student' => 'Student',
    'coordinator' => 'OJT Coordinator',
    'partner' => 'Host Training Establishment',
];
$selectedRole = $role ?? '';
$identifierLabels = [
    'student' => 'USN (Student ID)',
    'coordinator' => 'Coordinator ID',
    'partner' => 'HTE ID',
];
$identifierLabel = $selectedRole ? ($identifierLabels[$selectedRole] ?? 'Account ID') : 'Account ID';
if (!isset($flashSuccess)) {
    $flashSuccess = flash('success');
}
if (!isset($flashError)) {
    $flashError = flash('error');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Forgot Password - AMA Practicum System</title>
    <link rel="icon" type="image/jpeg" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=20260707-forgot">
    <link rel="stylesheet" href="<?= e(asset('assets/css/login.css')) ?>?v=20260826-back-accent">
</head>
<body class="login-page">
    <div class="login-split">
        <?php require __DIR__ . '/partials/login-info-panel.php'; ?>
        <?php require __DIR__ . '/partials/login-hero-open.php'; ?>
                <?php require __DIR__ . '/partials/forgot-password-card.php'; ?>
        <?php require __DIR__ . '/partials/login-hero-close.php'; ?>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
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

    if (typeof window.initForgotPasswordShell === 'function') {
        window.initForgotPasswordShell(document);
    }
});
</script>
<script src="<?= e(asset('assets/js/login-custom-select.js')) ?>?v=20260707-forgot-select"></script>
<script src="<?= e(asset('assets/js/login-portal.js')) ?>?v=20260826-shell-partial"></script>
</body>
</html>
