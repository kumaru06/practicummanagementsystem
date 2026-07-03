<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Student Registration - AMA Practicum System</title>
    <link rel="icon" type="image/jpeg" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=20260703-register">
    <link rel="stylesheet" href="<?= e(asset('assets/css/login.css')) ?>?v=20260703-middle-name">
</head>
<body class="login-page register-page" data-app-base="<?= e(app_base_path()) ?>">
    <div class="login-split register-split">
        <aside class="login-info-panel" aria-label="About AMA Computer College Davao">
            <div class="login-info-photo">
                <img
                    src="<?= e(asset('assets/image/login/students.webp')) ?>"
                    alt="AMA Computer College students"
                    class="login-info-photo-img"
                    onerror="this.closest('.login-info-photo').classList.add('is-placeholder')"
                >
            </div>
            <div class="login-info-mission">
                <h2 class="login-info-mission-title">Create Your Student Account</h2>
                <p class="login-info-mission-text">Register to join the OJT practicum portal. Verify your email within 12 hours, then wait for administrator approval before accessing the dashboard.</p>
            </div>
        </aside>

        <div class="login-hero-panel register-hero-panel" style="background-image: url('<?= e(asset('assets/image/login/campus2.webp')) ?>')">
            <div class="login-form-panel register-form-panel">
                <div class="login-card portal-login-card register-card">
                    <div class="portal-login-card-inner">
                        <div class="brand login-brand">
                            <img src="<?= e(asset('assets/image/main/logo/amalogo.png')) ?>" alt="AMA Logo" class="login-logo">
                            <div class="login-brand-copy">
                                <strong>Computer College &mdash; Davao</strong>
                                <span>Student Registration</span>
                            </div>
                        </div>

                        <?php
                        $successMessage = flash('success');
                        if (!empty($submitted) || $successMessage):
                        ?>
                            <div class="register-success-panel">
                                <div class="register-success-icon" aria-hidden="true">✓</div>
                                <h1>Check Your Email</h1>
                                <p><?= e($successMessage ?: 'Your registration has been submitted. Please verify your email within 12 hours to activate your account. After verification, you can sign in while waiting for administrator approval.') ?></p>
                                <a class="btn btn-primary" href="<?= e(route_url('student.login')) ?>">Back to Student Login</a>
                            </div>
                        <?php else: ?>
                            <?php if ($error = flash('error')): ?>
                                <div class="alert danger"><?= e($error) ?></div>
                            <?php endif; ?>

                            <form method="post" action="register.php" enctype="multipart/form-data" class="form js-validate register-form" id="studentRegisterForm">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                                <div class="register-name-grid">
                                    <label class="portal-field">
                                        <span class="portal-field-label">First Name</span>
                                        <input required type="text" name="first_name" autocomplete="given-name" placeholder="First name" value="<?= e($_POST['first_name'] ?? '') ?>">
                                    </label>
                                    <label class="portal-field">
                                        <span class="portal-field-label">Middle Name <span class="field-optional">(optional)</span></span>
                                        <input type="text" name="middle_name" autocomplete="additional-name" placeholder="Middle name" value="<?= e($_POST['middle_name'] ?? '') ?>">
                                    </label>
                                    <label class="portal-field">
                                        <span class="portal-field-label">Last Name</span>
                                        <input required type="text" name="last_name" autocomplete="family-name" placeholder="Last name" value="<?= e($_POST['last_name'] ?? '') ?>">
                                    </label>
                                </div>

                                <label class="portal-field">
                                    <span class="portal-field-label">Student ID / USN</span>
                                    <input required type="text" name="student_no" inputmode="numeric" autocomplete="off" placeholder="Numbers only" data-student-no-check data-registration-check value="<?= e($_POST['student_no'] ?? '') ?>">
                                    <span class="field-check-message" data-student-no-message aria-live="polite"></span>
                                </label>

                                <label class="portal-field">
                                    <span class="portal-field-label">Email Address</span>
                                    <input required type="email" name="email" autocomplete="email" placeholder="you@example.com" data-email-check data-registration-check value="<?= e($_POST['email'] ?? '') ?>">
                                    <span class="field-check-message" data-email-message aria-live="polite"></span>
                                </label>

                                <label class="portal-field">
                                    <span class="portal-field-label">Password</span>
                                    <input required minlength="8" type="password" name="password" autocomplete="new-password" placeholder="At least 8 characters">
                                </label>

                                <label class="portal-field">
                                    <span class="portal-field-label">Confirm Password</span>
                                    <input required minlength="8" type="password" name="confirm_password" autocomplete="new-password" placeholder="Re-enter password">
                                </label>

                                <label class="portal-field register-cor-field">
                                    <span class="portal-field-label">Certificate of Registration (COR)</span>
                                    <input required type="file" name="cor_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                                    <span class="field-check-message register-cor-hint">PDF, JPG, or PNG. Max 5MB.</span>
                                </label>

                                <button class="btn btn-primary" type="submit"><span class="btn-text">Submit Registration</span><span class="spinner"></span></button>
                            </form>

                            <p class="portal-register-link">Already have an account? <a href="<?= e(route_url('student.login')) ?>">Sign in</a></p>
                        <?php endif; ?>
                    </div>

                    <div class="login-card-footer">
                        <p>&copy; <?= date('Y') ?> AMA Computer College &middot; Practicum Management System</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="<?= e(asset('assets/js/main.js')) ?>?v=20260703-register"></script>
</body>
</html>
