<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Student Registration - AMA Practicum System</title>
    <link rel="icon" type="image/jpeg" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=20260703-register">
    <link rel="stylesheet" href="<?= e(asset('assets/css/register.css')) ?>?v=20260703-register-v8">
</head>
<body class="register-page" data-app-base="<?= e(app_base_path()) ?>">
    <div class="register-shell">
        <main class="register-main">
            <div class="register-card">
                <aside class="register-aside">
                    <a
                        class="register-back-link"
                        href="<?= e(route_url('student.login')) ?>"
                        data-register-close-login
                        data-login-url="<?= e(route_url('student.login')) ?>"
                    >
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m11.5 5-5 5 5 5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>Back to Login</span>
                    </a>

                    <div class="register-aside-brand">
                        <img src="<?= e(asset('assets/image/main/logo/amalogo.png')) ?>" alt="AMA Logo" class="register-logo">
                        <p class="register-eyebrow">Computer College &mdash; Davao</p>
                        <h1 class="register-title">Student Registration</h1>
                        <p class="register-aside-lead">Join the OJT practicum portal and track your deployment journey with AMA Davao.</p>
                    </div>

                    <ol class="register-steps" aria-label="Registration process">
                        <li class="register-step is-active">
                            <span class="register-step-num">1</span>
                            <span class="register-step-text">Fill in your details and upload COR</span>
                        </li>
                        <li class="register-step">
                            <span class="register-step-num">2</span>
                            <span class="register-step-text">Verify your email within 12 hours</span>
                        </li>
                        <li class="register-step">
                            <span class="register-step-num">3</span>
                            <span class="register-step-text">Wait for admin approval, then sign in</span>
                        </li>
                    </ol>

                    <p class="register-aside-footer">&copy; <?= date('Y') ?> AMA Computer College</p>
                </aside>

                <div class="register-body">
                    <?php
                    $successMessage = flash('success');
                    $isSuccess = !empty($submitted) || $successMessage;
                    if ($isSuccess):
                    ?>
                        <div
                            class="register-success-panel"
                            data-register-success
                            data-redirect-url="<?= e(route_url('student.login')) ?>"
                            data-countdown-seconds="10"
                        >
                            <div class="register-success-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <h2 class="register-success-heading">Check Your Email</h2>
                            <p class="register-success-text"><?= e($successMessage ?: 'Your registration has been submitted. Please verify your email within 12 hours to activate your account. After verification, you can sign in while waiting for administrator approval.') ?></p>
                            <p class="register-countdown" data-register-countdown aria-live="polite">
                                Closing this page in <strong data-register-countdown-value>10</strong> seconds&hellip;
                            </p>
                            <a class="btn btn-primary register-success-btn" href="<?= e(route_url('student.login')) ?>" data-register-close-login data-login-url="<?= e(route_url('student.login')) ?>">Go to Student Login Now</a>
                        </div>
                    <?php else: ?>
                        <?php if ($error = flash('error')): ?>
                            <div class="alert danger register-alert"><?= e($error) ?></div>
                        <?php endif; ?>

                        <form method="post" action="register.php" enctype="multipart/form-data" class="form js-validate register-form" id="studentRegisterForm">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                            <div class="register-form-grid">
                                <label class="register-field register-field--span-4">
                                    <span class="register-field-label">First Name</span>
                                    <input required type="text" name="first_name" autocomplete="given-name" placeholder="First name" value="<?= e($_POST['first_name'] ?? '') ?>">
                                </label>
                                <label class="register-field register-field--span-4">
                                    <span class="register-field-label">Middle <span class="register-field-optional"></span></span>
                                    <input type="text" name="middle_name" autocomplete="additional-name" placeholder="Middle" value="<?= e($_POST['middle_name'] ?? '') ?>">
                                </label>
                                <label class="register-field register-field--span-4">
                                    <span class="register-field-label">Last Name</span>
                                    <input required type="text" name="last_name" autocomplete="family-name" placeholder="Last name" value="<?= e($_POST['last_name'] ?? '') ?>">
                                </label>

                                <label class="register-field register-field--span-6">
                                    <span class="register-field-label">Student ID / USN</span>
                                    <input required type="text" name="student_no" inputmode="numeric" autocomplete="off" placeholder="Numbers only" data-student-no-check data-registration-check value="<?= e($_POST['student_no'] ?? '') ?>">
                                    <span class="field-check-message" data-student-no-message aria-live="polite"></span>
                                </label>
                                <label class="register-field register-field--span-6">
                                    <span class="register-field-label">Email Address</span>
                                    <input required type="email" name="email" autocomplete="email" placeholder="you@example.com" data-email-check data-registration-check value="<?= e($_POST['email'] ?? '') ?>">
                                    <span class="field-check-message" data-email-message aria-live="polite"></span>
                                </label>

                                <div class="register-field register-field--span-6 register-password-col">
                                    <label class="register-field-inner">
                                        <span class="register-field-label">Password</span>
                                        <input required minlength="8" type="password" name="password" autocomplete="new-password" placeholder="Min. 8 characters" data-register-password>
                                    </label>
                                    <div class="password-gate-strength register-password-strength" data-register-password-strength aria-live="polite" hidden>
                                        <div class="password-gate-strength__track" aria-hidden="true">
                                            <span class="password-gate-strength__fill"></span>
                                        </div>
                                        <span class="password-gate-strength__label" data-register-strength-label></span>
                                    </div>
                                </div>
                                <div class="register-field register-field--span-6 register-password-col">
                                    <label class="register-field-inner">
                                        <span class="register-field-label">Confirm Password</span>
                                        <input required minlength="8" type="password" name="confirm_password" autocomplete="new-password" placeholder="Re-enter password" data-register-confirm-password>
                                    </label>
                                    <p class="password-gate-match register-password-match" data-register-password-match aria-live="polite" hidden></p>
                                </div>

                                <label class="register-field register-field--span-6 register-file-field register-file-field--compact">
                                    <span class="register-field-label">Certificate of Registration (COR)</span>
                                    <input required type="file" name="cor_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                                    <span class="field-check-message register-file-hint">PDF, JPG, or PNG &middot; Max 5MB</span>
                                </label>
                            </div>

                            <div class="register-form-actions">
                                <button class="btn btn-primary register-submit" type="submit">
                                    <span class="btn-text">Submit Registration</span>
                                    <span class="spinner"></span>
                                </button>
                                <p class="register-signin-link">Already have an account? <a href="<?= e(route_url('student.login')) ?>" data-register-close-login data-login-url="<?= e(route_url('student.login')) ?>">Sign in</a></p>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
<script src="<?= e(asset('assets/js/main.js')) ?>?v=20260703-register-v4"></script>
</body>
</html>
