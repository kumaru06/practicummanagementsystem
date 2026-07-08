<?php
$slideshowDir = __DIR__ . '/../../../assets/image/slideshow';
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
                        draggable="false"
                    >
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <img
                src="<?= e(asset('assets/image/login/students.webp')) ?>"
                alt="AMA Computer College students"
                class="login-info-photo-img"
                draggable="false"
                onerror="this.closest('.login-info-photo').classList.add('is-placeholder')"
            >
        <?php endif; ?>
    </div>
    <div class="login-info-mission">
        <h2 class="login-info-mission-title">Shaping Future Innovators Through Education and AI</h2>
        <p class="login-info-mission-text">AMA Computer College delivers quality education in advanced technology — including AI-driven learning — empowering students with digital skills and knowledge to become future-ready graduates and industry leaders.</p>
    </div>
</aside>
