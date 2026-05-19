<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($portalLabel ?? 'Login') ?> - AMA Practicum System</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=20260519-login-usn-support">
</head>
<body class="login-page">
    <div class="login-split">
        <!-- Left: Image panel -->
        <div class="login-image-panel">
            <img src="<?= e(asset('assets/image/main/image.png')) ?>" alt="Practicum" class="login-hero-img">
            <div class="login-image-overlay">
                <div class="login-image-text">
                    <h1>AMA Practicum<br>Management System</h1>
                    <p>Track your OJT journey — from deployment to completion.</p>
                </div>
            </div>
        </div>
        <!-- Right: Form panel -->
        <div class="login-form-panel">
            <div class="login-card portal-login-card">
                <?php if (!empty($portalRole)): ?>
                    <a class="portal-back-link" href="<?= e(route_url('login')) ?>">
                        <span class="portal-back-link-icon" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="none"><path d="m11.5 5-5 5 5 5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span>Back to portal selection</span>
                    </a>
                <?php endif; ?>
                <div class="brand login-brand">
                    <img src="<?= e(asset('assets/image/main/logo/amalogo.png')) ?>" alt="AMA Logo" class="login-logo">
                    <div><strong>Computer College</strong></div>
                </div>
                <?php if (!empty($portalRole)): ?>
                    <div class="portal-badge"><span class="dot"></span><?= e($portalLabel ?? 'Portal') ?></div>
                <?php else: ?>
                    <div class="portal-copy">
                        <h1 class="portal-heading">Choose your login portal</h1>
                        <p class="portal-sub">Select the portal that matches your account role. Other account types will be blocked.</p>
                    </div>
                <?php endif; ?>
                <?php if ($m = flash('error')): ?><div class="alert danger"><?= e($m) ?></div><?php endif; ?>
                <?php if (empty($portalRole)): ?>
                    <?php
                    $portalMeta = [
                        'student' => [
                            'desc' => 'OJT student account',
                            'icon' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4 3 8.5 12 13l7.2-3.6V15h1.8V8.5L12 4Z" fill="currentColor" opacity=".92"/><path d="M7 11.3v3.4c0 .45.24.87.63 1.09C8.8 16.47 10.35 17 12 17s3.2-.53 4.37-1.21c.39-.22.63-.64.63-1.09v-3.4L12 13.9 7 11.3Z" fill="currentColor" opacity=".68"/><path d="M9.75 18.35c.7.23 1.45.35 2.25.35s1.55-.12 2.25-.35V20h-4.5v-1.65Z" fill="currentColor" opacity=".5"/></svg>',
                        ],
                        'coordinator' => [
                            'desc' => 'OJT coordinator account',
                            'icon' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" fill="currentColor" opacity=".92"/><path d="M4.75 18.5c0-2.07 1.68-3.75 3.75-3.75S12.25 16.43 12.25 18.5V19H4.75v-.5Zm7 0c0-1.8 1.45-3.25 3.25-3.25s3.25 1.45 3.25 3.25V19h-6.5v-.5Z" fill="currentColor" opacity=".68"/><path d="M11 13.2c.43-.2.9-.3 1.4-.3 1.9 0 3.44 1.54 3.44 3.44V17H13.8c-.18-1.6-1.19-2.96-2.8-3.8Z" fill="currentColor" opacity=".48"/></svg>',
                        ],
                        'partner' => [
                            'desc' => 'Industry Partner account',
                            'icon' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 5.75A1.75 1.75 0 0 1 6.75 4h7.5A1.75 1.75 0 0 1 16 5.75V20H5V5.75Z" fill="currentColor" opacity=".9"/><path d="M16 8.75A1.75 1.75 0 0 1 17.75 7h.5A1.75 1.75 0 0 1 20 8.75V20h-4V8.75Z" fill="currentColor" opacity=".62"/><path d="M8 7.5h2v2H8v-2Zm0 4h2v2H8v-2Zm0 4h2v2H8v-2Zm4-8h2v2h-2v-2Zm0 4h2v2h-2v-2Zm0 4h2v2h-2v-2Z" fill="white" opacity=".92"/></svg>',
                        ],
                    ];
                    $portalArrowIcon = '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                    ?>
                    <div class="portal-grid">
                        <?php foreach (($portals ?? []) as $role => $portal): ?>
                            <a class="portal-link portal-link--<?= e($role) ?>" href="<?= e($portal['route']) ?>" style="color:#8B1A1A;">
                                <span class="portal-link-icon" aria-hidden="true" style="color:#8B1A1A;"><?= $portalMeta[$role]['icon'] ?? '' ?></span>
                                <span class="portal-link-text">
                                    <span class="portal-link-title" style="display:block;color:#8B1A1A;"><?= e($portal['label']) ?></span>
                                    <span class="portal-link-desc" style="display:block;color:#667085;"><?= e($portalMeta[$role]['desc'] ?? '') ?></span>
                                </span>
                                <span class="portal-link-arrow" aria-hidden="true" style="color:#8B1A1A;"><?= $portalArrowIcon ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= e(route_url($portalRole . '.login.post')) ?>" class="form js-validate">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <label><?= $portalRole === 'student' ? 'Email or USN' : 'Email' ?><input required type="<?= $portalRole === 'student' ? 'text' : 'email' ?>" name="email" autocomplete="username"></label>
                        <label>Password<input required type="password" name="password" autocomplete="current-password"></label>
                        <button class="btn btn-primary" type="submit"><span class="btn-text">Sign in</span><span class="spinner"></span></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<script src="<?= e(asset('assets/js/main.js')) ?>?v=20260509-native-login"></script>
</body>
</html>
