<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Account Pending Approval - AMA Practicum System</title>
    <link rel="icon" type="image/jpeg" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=20260706-pending">
    <link rel="stylesheet" href="<?= e(asset('assets/css/login.css')) ?>?v=20260706-pending">
</head>
<body class="student-pending-page">
    <div class="student-pending-bg" aria-hidden="true">
        <img
            src="<?= e(asset('assets/image/login/campus2.webp')) ?>"
            alt=""
            class="student-pending-bg-img"
        >
        <div class="student-pending-bg-overlay"></div>
    </div>

    <main class="student-pending-shell">
        <div class="student-pending-card">
            <img src="<?= e(asset('assets/image/main/logo/amalogo.png')) ?>" alt="AMA Logo" class="student-pending-logo">

            <span class="student-pending-pill">Pending Review</span>

            <h1>Account Pending Approval</h1>
            <p class="student-pending-lead">You don&rsquo;t have access to the student dashboard yet.</p>

            <ol class="student-pending-steps">
                <li class="student-pending-step is-done">
                    <span class="student-pending-step-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div class="student-pending-step-copy">
                        <strong>Email Verified</strong>
                        <span>Your email address is confirmed</span>
                    </div>
                </li>
                <li class="student-pending-step is-current">
                    <span class="student-pending-step-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 6v6h4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div class="student-pending-step-copy">
                        <strong>Administrator Review</strong>
                        <span>Your application is being reviewed</span>
                    </div>
                </li>
                <li class="student-pending-step">
                    <span class="student-pending-step-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div class="student-pending-step-copy">
                        <strong>Dashboard Access</strong>
                        <span>Full portal access once approved</span>
                    </div>
                </li>
            </ol>

            <?php if ($success = flash('success')): ?>
                <div class="student-pending-alert" role="status">
                    <span class="student-pending-alert-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="currentColor"/><path d="M8 12.5 10.5 15 16 9" stroke="#fff" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <p><?= e($success) ?></p>
                </div>
            <?php endif; ?>

            <p class="student-pending-note">You will receive full access once your registration is approved. If you have questions, please contact the OJT office.</p>

            <a class="student-pending-logout" href="logout.php">Log Out</a>
        </div>
    </main>
</body>
</html>
