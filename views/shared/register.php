<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Student Registration - AMA Practicum System</title>
    <link rel="icon" type="image/jpeg" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,600;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=20260703-register">
    <link rel="stylesheet" href="<?= e(asset('assets/css/register.css')) ?>?v=20260706-register-v19">
</head>
<body class="register-page" data-app-base="<?= e(app_base_path()) ?>">
    <div class="register-bg" aria-hidden="true">
        <img
            src="<?= e(asset('assets/image/login/campus2.webp')) ?>"
            alt=""
            class="register-bg-img"
        >
        <div class="register-bg-overlay"></div>
        <div class="register-bg-glow register-bg-glow--one"></div>
        <div class="register-bg-glow register-bg-glow--two"></div>
    </div>

    <div class="register-shell">
        <main class="register-main">
            <div class="register-card">
                <aside class="register-aside">
                    <div class="register-aside-top">
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
                    </div>

                    <ol class="register-steps" aria-label="Registration process">
                        <li class="register-step is-active">
                            <span class="register-step-num" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg>
                            </span>
                            <span class="register-step-copy">
                                <strong>Your details</strong>
                                <span>Fill in your profile and upload COR</span>
                            </span>
                        </li>
                        <li class="register-step">
                            <span class="register-step-num" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                            </span>
                            <span class="register-step-copy">
                                <strong>Email verification</strong>
                                <span>Confirm within 12 hours</span>
                            </span>
                        </li>
                        <li class="register-step">
                            <span class="register-step-num" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            </span>
                            <span class="register-step-copy">
                                <strong>Admin approval</strong>
                                <span>Sign in once approved</span>
                            </span>
                        </li>
                    </ol>

                    <div class="register-aside-trust">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                        <span>Your information is securely stored and reviewed by AMA Davao staff only.</span>
                    </div>

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
                                <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="24" cy="24" r="24" fill="currentColor"/>
                                    <path d="M15 24.5 21 30.5 33 18" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <h2 class="register-success-heading">Check Your Email</h2>
                            <p class="register-success-text"><?= e($successMessage ?: 'Your registration has been submitted. Please verify your email within 12 hours to activate your account. After verification, you can sign in while waiting for administrator approval.') ?></p>
                            <p class="register-countdown" data-register-countdown aria-live="polite">
                                Closing this page in <strong data-register-countdown-value>10</strong> seconds&hellip;
                            </p>
                            <a class="btn btn-primary register-success-btn" href="<?= e(route_url('student.login')) ?>" data-register-close-login data-login-url="<?= e(route_url('student.login')) ?>">Go to Student Login Now</a>
                        </div>
                    <?php else: ?>
                        <header class="register-body-head">
                            <span class="register-body-badge">OJT Practicum Portal</span>
                            <h2 class="register-body-title">Create your student account</h2>
                            <p class="register-body-lead">Complete the form below. All fields marked with <span class="register-required" aria-hidden="true">*</span> are required.</p>
                        </header>

                        <?php if ($error = flash('error')): ?>
                            <div class="alert danger register-alert"><?= e($error) ?></div>
                        <?php endif; ?>

                        <form method="post" action="register.php" enctype="multipart/form-data" class="form js-validate register-form" id="studentRegisterForm">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                            <section class="register-form-section" aria-labelledby="regSectionPersonal">
                                <div class="register-section-head">
                                    <span class="register-section-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                    </span>
                                    <div>
                                        <h3 id="regSectionPersonal" class="register-section-title">Personal Information</h3>
                                        <p class="register-section-desc">Use your official school records</p>
                                    </div>
                                </div>

                                <div class="register-form-grid">
                                    <label class="register-field register-field--span-4">
                                        <span class="register-field-label">First Name <span class="register-required" aria-hidden="true">*</span></span>
                                        <span class="register-input-wrap">
                                            <input required type="text" name="first_name" autocomplete="given-name" placeholder="Enter your first name" value="<?= e($_POST['first_name'] ?? '') ?>">
                                        </span>
                                    </label>
                                    <label class="register-field register-field--span-4">
                                        <span class="register-field-label">Middle Name</span>
                                        <span class="register-input-wrap">
                                            <input type="text" name="middle_name" autocomplete="additional-name" placeholder="Enter your middle name" value="<?= e($_POST['middle_name'] ?? '') ?>">
                                        </span>
                                    </label>
                                    <label class="register-field register-field--span-4">
                                        <span class="register-field-label">Last Name <span class="register-required" aria-hidden="true">*</span></span>
                                        <span class="register-input-wrap">
                                            <input required type="text" name="last_name" autocomplete="family-name" placeholder="Rezep" value="<?= e($_POST['last_name'] ?? '') ?>">
                                        </span>
                                    </label>

                                    <label class="register-field register-field--span-6">
                                        <span class="register-field-label">Student ID / USN <span class="register-required" aria-hidden="true">*</span></span>
                                        <span class="register-input-wrap">
                                            <span class="register-input-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24"><path d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm3-7.5h.008v.008H7.5V12Zm0 3h.008v.008H7.5V15Zm0 3h.008v.008H7.5V18Z"/></svg>
                                            </span>
                                            <input required type="text" name="student_no" inputmode="numeric" autocomplete="off" placeholder="Enter your USN" data-student-no-check data-registration-check value="<?= e($_POST['student_no'] ?? '') ?>">
                                        </span>
                                        <span class="field-check-message" data-student-no-message aria-live="polite"></span>
                                    </label>
                                    <label class="register-field register-field--span-6">
                                        <span class="register-field-label">Email Address <span class="register-required" aria-hidden="true">*</span></span>
                                        <span class="register-input-wrap">
                                            <span class="register-input-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24"><path d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                                            </span>
                                            <input required type="email" name="email" autocomplete="email" placeholder="test@test.com" data-email-check data-registration-check value="<?= e($_POST['email'] ?? '') ?>">
                                        </span>
                                        <span class="field-check-message" data-email-message aria-live="polite"></span>
                                    </label>

                                    <label class="register-field register-field--span-6">
                                        <span class="register-field-label">Program <span class="register-required" aria-hidden="true">*</span></span>
                                        <span class="register-input-wrap register-input-wrap--select">
                                            <span class="register-input-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24"><path d="M4.5 7.5h15M4.5 7.5A2.25 2.25 0 0 1 6.75 5.25h10.5A2.25 2.25 0 0 1 19.5 7.5v9A2.25 2.25 0 0 1 17.25 18.75H6.75A2.25 2.25 0 0 1 4.5 16.5v-9Z"/><path d="M8.25 10.5h7.5M8.25 13.5h4.5"/></svg>
                                            </span>
                                            <select required name="program_id" data-select-label="Program">
                                                <option value="">Select your program</option>
                                                <?php foreach ($programs ?? [] as $program): ?>
                                                    <option value="<?= (int)$program['id'] ?>" <?= (int)($_POST['program_id'] ?? 0) === (int)$program['id'] ? 'selected' : '' ?>><?= e($program['code'] . ' — ' . $program['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </span>
                                    </label>
                                    <label class="register-field register-field--span-6">
                                        <span class="register-field-label">Year Level <span class="register-required" aria-hidden="true">*</span></span>
                                        <span class="register-input-wrap register-input-wrap--select">
                                            <span class="register-input-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24"><path d="M12 3 2 8l10 5 8-4v6h2V8L12 3Zm-6 9v4c2 3 10 3 12 0v-4l-6 3-6-3Z"/></svg>
                                            </span>
                                            <select required name="year_level" data-select-label="Year Level">
                                                <option value="">Select year level</option>
                                                <option value="3rd Year" <?= ($_POST['year_level'] ?? '') === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                                                <option value="4th Year" <?= ($_POST['year_level'] ?? '') === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                                            </select>
                                        </span>
                                    </label>
                                </div>
                            </section>

                            <section class="register-form-section" aria-labelledby="regSectionSecurity">
                                <div class="register-section-head">
                                    <span class="register-section-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><path d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                    </span>
                                    <div>
                                        <h3 id="regSectionSecurity" class="register-section-title">Account Security</h3>
                                        <p class="register-section-desc">Minimum 8 characters for your password</p>
                                    </div>
                                </div>

                                <div class="register-form-grid">
                                    <div class="register-field register-field--span-6 register-password-col">
                                        <label class="register-field-inner">
                                            <span class="register-field-label">Password <span class="register-required" aria-hidden="true">*</span></span>
                                            <span class="register-input-wrap">
                                                <span class="register-input-icon" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24"><path d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.098-1.169.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.528-1 .43-1.563A6 6 0 1 1 21.75 8.25Z"/></svg>
                                                </span>
                                                <input required minlength="8" type="password" name="password" autocomplete="new-password" placeholder="Min. 8 characters" data-register-password>
                                            </span>
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
                                            <span class="register-field-label">Confirm Password <span class="register-required" aria-hidden="true">*</span></span>
                                            <span class="register-input-wrap">
                                                <span class="register-input-icon" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                                </span>
                                                <input required minlength="8" type="password" name="confirm_password" autocomplete="new-password" placeholder="Re-enter password" data-register-confirm-password>
                                            </span>
                                        </label>
                                        <p class="password-gate-match register-password-match" data-register-password-match aria-live="polite" hidden></p>
                                    </div>
                                </div>
                            </section>

                            <section class="register-form-section" aria-labelledby="regSectionDocs">
                                <div class="register-section-head">
                                    <span class="register-section-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                    </span>
                                    <div>
                                        <h3 id="regSectionDocs" class="register-section-title">Certificate of Registration <span class="register-required" aria-hidden="true">*</span></h3>
                                        <p class="register-section-desc">Upload your current COR document</p>
                                    </div>
                                </div>

                                <label class="register-field register-file-field">
                                    <div class="register-cor-dropzone" data-cor-dropzone>
                                        <input required type="file" name="cor_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" id="registerCorInput">
                                        <div class="register-cor-dropzone-inner">
                                            <span class="register-cor-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24"><path d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                            </span>
                                            <div class="register-cor-copy">
                                                <strong>Drag &amp; drop your COR here</strong>
                                                <span class="register-cor-filename" data-cor-filename>No file chosen</span>
                                            </div>
                                            <button type="button" class="register-cor-browse" data-cor-browse>Browse files</button>
                                            <button type="button" class="register-cor-clear" data-cor-clear hidden aria-label="Remove file">&times;</button>
                                        </div>
                                    </div>
                                    <span class="field-check-message register-file-hint">PDF, JPG, or PNG &middot; Max 5MB</span>
                                </label>
                            </section>

                            <div class="register-form-actions">
                                <button class="btn btn-primary register-submit" type="submit">
                                    <span class="btn-text">Submit Registration</span>
                                    <svg class="register-submit-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
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
<script src="<?= e(asset('assets/js/main.js')) ?>?v=20260706-register-v11"></script>
</body>
</html>
