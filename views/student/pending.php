<?php
$fullName = trim((string)(current_user()['name'] ?? ''));
$firstName = $fullName !== '' ? explode(' ', $fullName)[0] : 'Student';
$success = flash('success');
$justVerified = (bool)$success || (defined('APP_IS_LOCAL') && APP_IS_LOCAL && isset($_GET['celebrate']));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= $justVerified ? 'Email Verified' : 'Account Pending Approval' ?> - AMA Practicum System</title>
    <link rel="icon" type="image/jpeg" href="<?= e(asset('assets/image/main/favicon.jpg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=20260902-pending-celebrate">
    <link rel="stylesheet" href="<?= e(asset('assets/css/login.css')) ?>?v=20260902-pending-celebrate">
</head>
<body class="student-pending-page<?= $justVerified ? ' is-just-verified' : '' ?>">
    <div class="student-pending-bg" aria-hidden="true">
        <img
            src="<?= e(asset('assets/image/login/campus2.webp')) ?>"
            alt=""
            class="student-pending-bg-img"
        >
        <div class="student-pending-bg-overlay"></div>
        <div class="student-pending-bg-glow student-pending-bg-glow--one"></div>
        <div class="student-pending-bg-glow student-pending-bg-glow--two"></div>
    </div>

    <main class="student-pending-shell">
        <div class="student-pending-card<?= $justVerified ? ' has-celebrate' : '' ?>">
            <?php if ($justVerified): ?>
                <section class="pending-celebrate" data-pending-celebrate aria-live="polite">
                    <div class="pending-celebrate-burst" aria-hidden="true">
                        <span class="pending-celebrate-ring"></span>
                        <span class="pending-celebrate-ring pending-celebrate-ring--two"></span>
                        <?php
                        $confetti = [
                            ['-42px', '-8px', '#22c55e', '18deg'],
                            ['38px', '-14px', '#0a3d8f', '-22deg'],
                            ['-8px', '-46px', '#f59e0b', '8deg'],
                            ['22px', '40px', '#8b1a1a', '-12deg'],
                            ['-36px', '28px', '#38bdf8', '28deg'],
                            ['48px', '12px', '#16a34a', '-30deg'],
                            ['-18px', '44px', '#fbbf24', '14deg'],
                            ['12px', '-38px', '#1a56b8', '-8deg'],
                            ['-52px', '6px', '#4ade80', '36deg'],
                            ['54px', '-28px', '#ef4444', '-18deg'],
                            ['-28px', '-32px', '#0ea5e9', '22deg'],
                            ['32px', '32px', '#84cc16', '-26deg'],
                            ['0px', '52px', '#a855f7', '10deg'],
                            ['-46px', '-22px', '#f97316', '-34deg'],
                        ];
                        foreach ($confetti as $i => $piece):
                        ?>
                            <span
                                class="pending-confetti"
                                style="--i:<?= $i + 1 ?>;--x:<?= $piece[0] ?>;--y:<?= $piece[1] ?>;--c:<?= $piece[2] ?>;--r:<?= $piece[3] ?>;"
                            ></span>
                        <?php endforeach; ?>
                    </div>

                    <div class="pending-celebrate-mark" aria-hidden="true">
                        <svg class="pending-celebrate-svg" viewBox="0 0 52 52" fill="none">
                            <circle class="pending-celebrate-track" cx="26" cy="26" r="22"></circle>
                            <circle class="pending-celebrate-circle" cx="26" cy="26" r="22"></circle>
                            <path class="pending-celebrate-check" d="M15.5 26.8 22.4 33.5 36.5 18.5"></path>
                        </svg>
                    </div>

                    <div class="pending-celebrate-copy">
                        <span class="pending-celebrate-eyebrow">Email verified</span>
                        <h1>You&rsquo;re verified, <?= e($firstName) ?></h1>
                        <p>Your email is confirmed. An administrator will review your registration next — the dashboard unlocks after approval.</p>
                    </div>

                    <button class="pending-celebrate-continue" type="button" data-pending-continue>
                        See approval status
                    </button>
                </section>
            <?php endif; ?>

            <section class="pending-status" data-pending-status<?= $justVerified ? ' aria-hidden="true"' : '' ?>>
                <img src="<?= e(asset('assets/image/main/logo/amalogo.png')) ?>" alt="AMA Logo" class="student-pending-logo">

                <span class="student-pending-pill">
                    <span class="student-pending-pill-dot" aria-hidden="true"></span>
                    Pending review
                </span>

                <p class="student-pending-hello">Hi, <?= e($firstName) ?></p>
                <h1>Account pending approval</h1>
                <p class="student-pending-lead">Your email is confirmed. Your application is now with the OJT office.</p>

                <ol class="student-pending-steps">
                    <li class="student-pending-step is-done">
                        <span class="student-pending-step-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <div class="student-pending-step-copy">
                            <strong>Email verified</strong>
                            <span>Your address is confirmed</span>
                        </div>
                    </li>
                    <li class="student-pending-step is-current">
                        <span class="student-pending-step-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 6v6h4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <div class="student-pending-step-copy">
                            <strong>Administrator review</strong>
                            <span>Your application is in queue</span>
                        </div>
                    </li>
                    <li class="student-pending-step">
                        <span class="student-pending-step-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <div class="student-pending-step-copy">
                            <strong>Dashboard access</strong>
                            <span>Unlocks after approval</span>
                        </div>
                    </li>
                </ol>

                <p class="student-pending-note">You&rsquo;ll get full portal access once an administrator approves your registration. Questions? Contact the OJT office.</p>

                <a class="student-pending-logout" href="logout.php">Log out</a>
            </section>
        </div>
    </main>

    <script>
    (function () {
        const celebrate = document.querySelector('[data-pending-celebrate]');
        const status = document.querySelector('[data-pending-status]');
        const card = document.querySelector('.student-pending-card');
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!celebrate || !status) {
            card?.classList.add('is-in');
            return;
        }

        requestAnimationFrame(function () {
            celebrate.classList.add('is-revealed');
        });

        let settled = false;
        const settle = function () {
            if (settled) return;
            settled = true;
            celebrate.classList.add('is-leaving');
            document.body.classList.add('is-pending-settled');
            status.classList.add('is-in');
            status.removeAttribute('aria-hidden');
            card.classList.add('is-in');
            window.setTimeout(function () {
                celebrate.setAttribute('hidden', '');
                celebrate.setAttribute('aria-hidden', 'true');
            }, reduce ? 0 : 420);
        };

        celebrate.querySelector('[data-pending-continue]')?.addEventListener('click', settle);
        window.setTimeout(settle, reduce ? 400 : 3400);
    })();
    </script>
</body>
</html>
