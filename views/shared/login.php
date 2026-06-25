<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($portalLabel ?? 'Login') ?> - AMA Practicum System</title>
    <link rel="icon" type="image/jpeg" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=20260611-portal-spa">
    <link rel="stylesheet" href="<?= e(asset('assets/css/login.css')) ?>?v=20260625-login-mobile-v4">
    <style>
        @media (min-width: 900px) {
            .login-page .login-mobile-hero {
                display: none !important;
                height: 0 !important;
                overflow: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="login-bg-decor" aria-hidden="true">
        <div class="login-orb login-orb--1"></div>
        <div class="login-orb login-orb--2"></div>
        <div class="login-orb login-orb--3"></div>
    </div>

    <div class="login-mobile-hero" id="js-login-mobile-hero" hidden>
        <div class="login-mobile-hero-inner">
            <img src="<?= e(asset('assets/image/main/logo/amalogo.png')) ?>" alt="AMA Computer College" class="login-mobile-hero-logo">
            <div class="login-mobile-hero-text">
                <span class="login-mobile-hero-badge">OJT Management Platform</span>
                <p class="login-mobile-hero-title">AMA Practicum Management System</p>
            </div>
        </div>
    </div>

    <div class="login-split">
        <div class="login-image-panel">
            <img src="<?= e(asset('assets/image/login/amamain.jpg')) ?>" alt="AMA University and Colleges" class="login-hero-img">
            <div class="login-image-vignette" aria-hidden="true"></div>
            <div class="login-image-overlay">
                <div class="login-image-content">
                    <div class="login-image-text">
                        <div class="login-hero-badge">
                            <span class="login-hero-badge-dot"></span>
                            OJT Management Platform
                        </div>
                        <h1>AMA Practicum<br>Management System</h1>
                        <p>Your complete OJT companion — from deployment to completion, guided every step of the way.</p>
                        <ul class="login-hero-stats" aria-label="Platform highlights">
                            <li class="login-hero-stat">
                                <span class="login-hero-stat-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                                </span>
                                <span class="login-hero-stat-copy">
                                    <strong>360°</strong>
                                    <span>OJT Tracking</span>
                                </span>
                            </li>
                            <li class="login-hero-stat">
                                <span class="login-hero-stat-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 13h3l2-5 4 10 2-5h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span class="login-hero-stat-copy">
                                    <strong>Real-time</strong>
                                    <span>Progress Updates</span>
                                </span>
                            </li>
                            <li class="login-hero-stat">
                                <span class="login-hero-stat-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 3 4 7v6c0 4.2 3.4 8.1 8 9.5 4.6-1.4 8-5.3 8-9.5V7l-8-4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span class="login-hero-stat-copy">
                                    <strong>Secure</strong>
                                    <span>Role-based Access</span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="login-panel-divider" aria-hidden="true"></div>
        </div>

        <div class="login-form-panel">
            <div class="login-form-shell">
            <?php
            $activePortal = $portalRole ?? '';
            $showFormView = !empty($activePortal);
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
                'admin' => [
                    'desc' => 'System administrator account',
                    'icon' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2 4 5.5v6.5c0 4.42 3.4 8.55 8 10 4.6-1.45 8-5.58 8-10V5.5L12 2Z" fill="currentColor" opacity=".9"/><path d="m9.5 12.5 1.75 1.75L14.75 10.5" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                ],
            ];
            $portalArrowIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            $mailIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>';
            $userIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
            $lockIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
            $loginPortals = $loginPortals ?? ($portals ?? []);
            $portalLabelsJson = json_encode(
                array_map(static fn ($portal) => ['label' => $portal['label']], $loginPortals),
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            );
            $flashError = flash('error');
            ?>
            <div
                class="login-card portal-login-card js-portal-shell"
                data-active-portal="<?= e($activePortal) ?>"
                data-portal-base="<?= e(route_url('login')) ?>"
                data-portals="<?= e($portalLabelsJson ?: '{}') ?>"
            >
                <div class="portal-login-card-inner">
                    <div class="brand login-brand">
                        <img src="<?= e(asset('assets/image/main/logo/amalogo.png')) ?>" alt="AMA Logo" class="login-logo">
                        <div class="login-brand-copy">
                            <strong>Computer College</strong>
                            <span>Practicum Management System</span>
                        </div>
                    </div>

                    <div class="alert danger js-portal-alert<?= $flashError ? '' : ' is-hidden' ?>"><?= e($flashError ?: '') ?></div>

                    <div class="portal-stage">
                        <div class="portal-view portal-view--select<?= $showFormView ? '' : ' is-active' ?>" data-portal-view="select">
                            <div class="portal-copy">
                                <span class="portal-eyebrow">Welcome back</span>
                                <h1 class="portal-heading">Choose your portal</h1>
                                <p class="portal-sub">Sign in with the account type that matches your role. We'll keep everything secure and on track.</p>
                            </div>
                            <p class="portal-section-label">Select your role</p>
                            <div class="portal-grid">
                                <?php foreach (($portals ?? []) as $role => $portal): ?>
                                    <a
                                        class="portal-link portal-link--<?= e($role) ?> js-portal-open"
                                        href="<?= e($portal['route']) ?>"
                                        data-portal="<?= e($role) ?>"
                                    >
                                        <span class="portal-link-icon" aria-hidden="true"><?= $portalMeta[$role]['icon'] ?? '' ?></span>
                                        <span class="portal-link-text">
                                            <span class="portal-link-title"><?= e($portal['label']) ?></span>
                                            <span class="portal-link-desc"><?= e($portalMeta[$role]['desc'] ?? '') ?></span>
                                        </span>
                                        <span class="portal-link-arrow" aria-hidden="true"><?= $portalArrowIcon ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <p class="portal-trust-note">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 4 7v6c0 4.2 3.4 8.1 8 9.5 4.6-1.4 8-5.3 8-9.5V7l-8-4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Secure, role-based access for students, coordinators, and partners.
                            </p>
                        </div>

                        <div class="portal-view portal-view--form<?= $showFormView ? ' is-active' : '' ?>" data-portal-view="form">
                            <a class="portal-back-link js-portal-back" href="<?= e(route_url('login')) ?>">
                                <span class="portal-back-link-icon" aria-hidden="true">
                                    <svg viewBox="0 0 20 20" fill="none"><path d="m11.5 5-5 5 5 5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span>Back to portal selection</span>
                            </a>

                            <div class="portal-badge"><span class="dot"></span><span class="js-portal-badge-label"><?= e($portalLabel ?? 'Portal') ?></span></div>

                            <?php foreach ($loginPortals as $role => $portal): ?>
                                <form
                                    method="post"
                                    action="<?= e(route_url($role . '.login.post')) ?>"
                                    class="form js-validate portal-login-form<?= $activePortal === $role ? ' is-active' : '' ?>"
                                    data-portal-form="<?= e($role) ?>"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <label class="portal-field">
                                        <span class="portal-field-label"><?= $role === 'student' ? 'Email or USN' : 'Email' ?></span>
                                        <span class="portal-field-wrap">
                                            <span class="portal-field-icon" aria-hidden="true"><?= $role === 'student' ? $userIcon : $mailIcon ?></span>
                                            <input required type="<?= $role === 'student' ? 'text' : 'email' ?>" name="email" autocomplete="username" placeholder="<?= $role === 'student' ? 'Enter email or USN' : 'Enter your email' ?>">
                                        </span>
                                    </label>
                                    <label class="portal-field">
                                        <span class="portal-field-label">Password</span>
                                        <span class="portal-field-wrap">
                                            <span class="portal-field-icon" aria-hidden="true"><?= $lockIcon ?></span>
                                            <input required type="password" name="password" autocomplete="current-password" placeholder="Enter your password">
                                        </span>
                                    </label>
                                    <button class="btn btn-primary" type="submit"><span class="btn-text">Sign in</span><span class="spinner"></span></button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="login-card-footer">
                    <p>&copy; <?= date('Y') ?> AMA Computer College &middot; Practicum Management System</p>
                </div>
            </div>
            </div>
        </div>
    </div>
<script src="<?= e(asset('assets/js/main.js')) ?>?v=20260509-native-login"></script>
<script src="<?= e(asset('assets/js/login-portal.js')) ?>?v=20260625-mobile-fix"></script>
<script>
(function () {
    const el = document.getElementById('js-login-mobile-hero');
    const mq = window.matchMedia('(max-width: 899px)');
    const syncMobileHero = function () {
        if (el) {
            el.hidden = !mq.matches;
        }
    };
    syncMobileHero();
    if (typeof mq.addEventListener === 'function') {
        mq.addEventListener('change', syncMobileHero);
    } else if (typeof mq.addListener === 'function') {
        mq.addListener(syncMobileHero);
    }
})();
</script>
</body>
</html>
