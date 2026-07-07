<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($portalLabel ?? 'Login') ?> - AMA Practicum System</title>
    <link rel="icon" type="image/jpeg" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=20260703-portal">
    <link rel="stylesheet" href="<?= e(asset('assets/css/login.css')) ?>?v=20260707-portal-scroll-fix">
</head>
<body class="login-page">
    <?php
    $campusPhoto = asset('assets/image/login/campus2.webp');
    $slideshowDir = __DIR__ . '/../../assets/image/slideshow';
    $slideshowImages = [];
    if (is_dir($slideshowDir)) {
        $files = glob($slideshowDir . '/*.{webp,jpg,jpeg,png}', GLOB_BRACE) ?: [];
        natsort($files);
        foreach ($files as $file) {
            if (is_file($file)) {
                $slideshowImages[] = asset('assets/image/slideshow/' . basename($file));
            }
        }
    }
    ?>
    <div class="login-split">
        <aside class="login-info-panel" aria-label="About AMA Computer College Davao">
            <div class="login-info-photo">
                <?php if ($slideshowImages): ?>
                    <div class="login-info-slideshow" data-slide-count="<?= count($slideshowImages) ?>">
                        <?php foreach ($slideshowImages as $index => $slideSrc): ?>
                            <img
                                src="<?= e($slideSrc) ?>"
                                alt="AMA Computer College campus life"
                                class="login-info-slide<?= $index === 0 ? ' is-active' : '' ?>"
                                loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"
                            >
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <img
                        src="<?= e(asset('assets/image/login/students.webp')) ?>"
                        alt="AMA Computer College students"
                        class="login-info-photo-img"
                        onerror="this.closest('.login-info-photo').classList.add('is-placeholder')"
                    >
                <?php endif; ?>
            </div>
            <div class="login-info-mission">
                <h2 class="login-info-mission-title">Shaping Future Innovators Through Education and AI</h2>
                <p class="login-info-mission-text">AMA Computer College delivers quality education in advanced technology — including AI-driven learning — empowering students with digital skills and knowledge to become future-ready graduates and industry leaders.</p>
            </div>
        </aside>

        <div class="login-hero-panel" style="background-image: url('<?= e($campusPhoto) ?>')">
            <img
                src="<?= e($campusPhoto) ?>"
                alt="AMA Computer College Davao Campus building"
                class="login-hero-bg"
            >
            <div class="login-hero-brand">
                <img src="<?= e(asset('assets/image/login/ama.webp')) ?>" alt="AMA Education System" class="login-hero-brand-logo">
            </div>

            <address class="login-hero-contact">
                <strong class="login-hero-contact-heading">CONTACT US</strong>
                <span>#123 General Malvar St., Davao City</span>
                <span>Tel# (082) 331-1608</span>
                <a href="mailto:ama_davao@amaes.edu.ph">ama_davao@amaes.edu.ph</a>
                <span>FB page: AMA Computer College of Davao</span>
            </address>

            <div class="login-form-panel">
                <div class="login-form-shell js-login-form-shell">
                <?php
                $flashError = flash('error');
                $showPortalImmediately = !empty($portalRole) || !empty($flashError);
                $portalPartialUrl = route_url('login') . '?partial=portal';
                ?>
                <?php if ($showPortalImmediately): ?>
                    <?php require __DIR__ . '/partials/login-portal-card.php'; ?>
                <?php else: ?>
                    <div class="login-portal-gate js-portal-gate">
                        <div class="login-portal-gate-card">
                            <img src="<?= e(asset('assets/image/main/logo/amalogo.png')) ?>" alt="AMA Logo" class="login-portal-gate-logo">
                            <p class="login-portal-gate-eyebrow">Practicum Management System</p>
                            <h2 class="login-portal-gate-title">AMA Computer College &mdash; Davao</h2>
                            <p class="login-portal-gate-text">Sign in to manage OJT practicum activities, track progress, and stay connected with your campus community.</p>
                            <button
                                type="button"
                                class="login-portal-gate-btn js-portal-gate-open"
                                data-portal-fetch="<?= e($portalPartialUrl) ?>"
                            >
                                <span class="login-portal-gate-btn-label">Click Portal</span>
                                <span class="login-portal-gate-btn-spinner" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                    <div class="login-portal-ajax-host js-portal-ajax-host" hidden></div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<script src="<?= e(asset('assets/js/main.js')) ?>?v=20260509-native-login"></script>
<script src="<?= e(asset('assets/js/login-custom-select.js')) ?>?v=20260707-forgot-select"></script>
    <script src="<?= e(asset('assets/js/login-portal.js')) ?>?v=20260707-forgot-reset"></script>
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
});
</script>
</body>
</html>
