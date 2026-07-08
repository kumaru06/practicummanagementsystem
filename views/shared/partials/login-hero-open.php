<?php
$campusPhoto = $campusPhoto ?? asset('assets/image/login/campus2.webp');
?>
<div class="login-hero-panel" style="background-image: url('<?= e($campusPhoto) ?>')">
    <img
        src="<?= e($campusPhoto) ?>"
        alt="AMA Computer College Davao Campus building"
        class="login-hero-bg"
        draggable="false"
    >
    <div class="login-hero-brand">
        <img src="<?= e(asset('assets/image/login/ama.webp')) ?>" alt="AMA Education System" class="login-hero-brand-logo" draggable="false">
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
