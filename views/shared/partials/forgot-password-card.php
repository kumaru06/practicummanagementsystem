<?php
$selectedRole = $role ?? '';
$portalPartialUrl = $selectedRole
    ? route_url('login', ['portal' => $selectedRole])
    : route_url('login', ['partial' => 'portal']);
?>
<div
    class="login-card portal-login-card forgot-password-card is-revealed js-forgot-shell"
    data-portal-fetch="<?= e($portalPartialUrl) ?>"
>
    <div class="portal-login-card-inner">
        <div class="brand login-brand">
            <img src="<?= e(asset('assets/image/main/logo/amalogo.png')) ?>" alt="AMA Logo" class="login-logo">
            <div class="login-brand-copy">
                <strong>Computer College &mdash; Davao</strong>
                <span>Practicum Management System</span>
            </div>
        </div>

        <?php require __DIR__ . '/forgot-password-view.php'; ?>
    </div>

    <div class="login-card-footer">
        <p>&copy; <?= date('Y') ?> AMA Computer College &middot; Practicum Management System</p>
    </div>
</div>
