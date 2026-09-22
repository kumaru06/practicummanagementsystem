<?php
    $studentEvaluation = $studentEvaluation ?? [];
    $evaluationSections = $evaluationSections ?? FinalRequirement::EVALUATION_SECTIONS;
    $stage2Requirements = $stage2Requirements ?? [];
    $stage3Requirements = $stage3Requirements ?? [];
    $stage3DocProgress = $stage3DocProgress ?? ['total' => 0, 'uploaded' => 0, 'approved' => 0, 'done' => false];
    $stage3FilesUnlocked = !empty($stage3FilesUnlocked);
    $studentEvaluationsUnlocked = !empty($studentEvaluationsUnlocked);
    $studentEvaluationsLockMessage = (string)($studentEvaluationsLockMessage ?? '');
    $enrollment = $enrollment ?? [];

    $stage2PendingReview = 0;
    foreach ($stage2Requirements as $stage2Req) {
        if (!empty($stage2Req['file_path']) && ($stage2Req['status'] ?? '') === 'uploaded') {
            $stage2PendingReview++;
        }
    }

    $docSubmitted = (int)$stage3DocProgress['approved'];
    $docTotal = (int)$stage3DocProgress['total'];
    $docPct = $docTotal > 0 ? (int)round(($docSubmitted / $docTotal) * 100) : 0;
    $allDocsSubmitted = !empty($stage3DocProgress['done']);
    $stage3PendingReview = 0;
    foreach ($stage3Requirements as $stage3Req) {
        if (!empty($stage3Req['file_path']) && ($stage3Req['status'] ?? '') === 'uploaded') {
            $stage3PendingReview++;
        }
    }

    $evalSubmitted = 0;
    foreach (array_keys($evaluationSections) as $evalKey) {
        if (StudentEvaluation::statusFor($studentEvaluation, $evalKey) === 'submitted') {
            $evalSubmitted++;
        }
    }
    $evalTotal = count($evaluationSections);
    $evalPct = $evalTotal > 0 ? (int)round(($evalSubmitted / $evalTotal) * 100) : 0;
    $allEvalsSubmitted = $evalSubmitted === $evalTotal && $evalTotal > 0;

    $studentId = (int)($student['id'] ?? 0);
    $studentName = (string)($student['name'] ?? 'Student');
    $studentNo = (string)($student['student_no'] ?? '');
    $studentEmail = trim((string)($student['email'] ?? ''));
    $yearLevel = trim((string)($student['year_level'] ?? ''));
    $photoUrl = student_profile_photo_url($student);
    $deployStatus = strtolower(trim((string)($enrollment['status'] ?? 'pending')));
    if ($deployStatus === '') {
        $deployStatus = 'pending';
    }
    $statusLabel = ucwords(str_replace('_', ' ', $deployStatus));
    $statusClass = str_replace('_', '-', $deployStatus);
    $chipIdIcon = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.75"/><circle cx="8.5" cy="12" r="1.75" fill="currentColor"/><path d="M13 10h5M13 14h3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';
    $chipYearIcon = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 3 8.5 12 14l9-5.5L12 3Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M6.5 11.2v4.2c0 .4.22.77.58 1.02C8.3 17.3 10.05 18 12 18s3.7-.7 4.92-1.58c.36-.25.58-.62.58-1.02v-4.2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';

    $svgAttrs = 'class="cfp-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
    $sectionDocsIcon = '<svg ' . $svgAttrs . '><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><path d="M12 11v6M9 14h6"/></svg>';
    $sectionEvalIcon = '<svg ' . $svgAttrs . '><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 12h6M9 16h4M9 8h6"/></svg>';
    $chevronIcon = '<svg class="cfp-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>';

    $docRowIcons = [
        'job_description_doc' => [
            'class' => 'cfp-item-icon cfp-item-icon--job',
            'svg' => '<svg ' . $svgAttrs . '><path d="M10 7V5a2 2 0 0 1 4 0v2"/><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M3 12h18"/><path d="M12 12v4"/></svg>',
        ],
        'company_profile_doc' => [
            'class' => 'cfp-item-icon cfp-item-icon--company',
            'svg' => '<svg ' . $svgAttrs . '><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12h12"/><path d="M10 8h.01M14 8h.01M10 16h.01M14 16h.01"/></svg>',
        ],
        'personal_observation_doc' => [
            'class' => 'cfp-item-icon cfp-item-icon--observation',
            'svg' => '<svg ' . $svgAttrs . '><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/><path d="m15 5 3 3"/></svg>',
        ],
    ];
    $defaultDocIcon = [
        'class' => 'cfp-item-icon cfp-item-icon--company',
        'svg' => '<svg ' . $svgAttrs . '><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5Z"/></svg>',
    ];
    $evalRowIcons = [
        'coordinator' => [
            'class' => 'cfp-item-icon cfp-item-icon--eval',
            'svg' => '<svg ' . $svgAttrs . '><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/></svg>',
        ],
        'industry_partner' => [
            'class' => 'cfp-item-icon cfp-item-icon--partner',
            'svg' => '<svg ' . $svgAttrs . '><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01"/></svg>',
        ],
    ];

    $initials = '';
    $nameParts = preg_split('/\s+/', trim($studentName), 2);
    if (!empty($nameParts[0])) {
        $initials .= strtoupper(substr($nameParts[0], 0, 1));
    }
    if (!empty($nameParts[1])) {
        $initials .= strtoupper(substr($nameParts[1], 0, 1));
    }
    if ($initials === '') {
        $initials = '?';
    }

    $docStatusLabel = $allDocsSubmitted ? 'Complete' : ($docSubmitted > 0 ? 'In progress' : 'Not started');
    $docStatusClass = $allDocsSubmitted ? 'is-complete' : ($docSubmitted > 0 ? 'is-progress' : 'is-empty');
    $evalStatusLabel = $allEvalsSubmitted ? 'Complete' : ($evalSubmitted > 0 ? 'In progress' : 'Not started');
    $evalStatusClass = $allEvalsSubmitted ? 'is-complete' : ($evalSubmitted > 0 ? 'is-progress' : 'is-empty');
?>

<div class="coord-final-page">
    <div class="cfp-toolbar">
        <nav class="cfp-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php?r=coordinator_students">My Students</a>
            <span class="cfp-breadcrumb-sep" aria-hidden="true">/</span>
            <span aria-current="page"><?= e($studentName) ?></span>
        </nav>
        <a class="cfp-back" href="index.php?r=coordinator_students">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            Back to My Students
        </a>
    </div>

    <header class="cfp-hero">
        <div class="cfp-hero-profile">
            <span class="cfp-avatar<?= $photoUrl !== '' ? ' has-photo' : '' ?>">
                <?php if ($photoUrl !== ''): ?>
                    <img src="<?= e($photoUrl) ?>" alt="<?= e($studentName) ?> profile photo">
                <?php else: ?>
                    <span class="cfp-avatar-fallback"><?= e($initials) ?></span>
                <?php endif; ?>
            </span>
            <div class="cfp-hero-copy">
                <p class="cfp-eyebrow">Student Profile</p>
                <h1><?= e($studentName !== '' ? $studentName : '—') ?></h1>
                <p class="cfp-meta"><?= $studentEmail !== '' ? e($studentEmail) : '—' ?></p>
                <div class="cfp-idchips">
                    <span class="cfp-idchip"><?= $chipIdIcon ?><span><?= $studentNo !== '' ? 'ID ' . e($studentNo) : 'ID —' ?></span></span>
                    <span class="cfp-idchip"><?= $chipYearIcon ?><span><?= $yearLevel !== '' ? e($yearLevel) : 'Year —' ?></span></span>
                    <span class="cfp-idchip cfp-idchip-status is-<?= e($statusClass) ?>"><?= e($statusLabel !== '' ? $statusLabel : '—') ?></span>
                </div>
            </div>
        </div>

        <div class="cfp-hero-stats">
            <article class="cfp-stat <?= e($docStatusClass) ?>">
                <div class="cfp-ring" style="--pct: <?= $docPct ?>">
                    <span><?= $docPct ?>%</span>
                </div>
                <div class="cfp-stat-copy">
                    <span class="cfp-stat-label">Documents</span>
                    <strong><?= $docSubmitted ?> <small>of <?= $docTotal ?></small></strong>
                    <span class="cfp-stat-status"><?= e($docStatusLabel) ?></span>
                </div>
            </article>
            <article class="cfp-stat <?= e($evalStatusClass) ?>">
                <div class="cfp-ring cfp-ring--eval" style="--pct: <?= $evalPct ?>">
                    <span><?= $evalPct ?>%</span>
                </div>
                <div class="cfp-stat-copy">
                    <span class="cfp-stat-label">Evaluations</span>
                    <strong><?= $evalSubmitted ?> <small>of <?= $evalTotal ?></small></strong>
                    <span class="cfp-stat-status"><?= e($evalStatusLabel) ?></span>
                </div>
            </article>
        </div>
    </header>

    <div class="cfp-grid">
        <?php if (!empty($stage2Requirements)): ?>
            <section class="cfp-panel">
                <header class="cfp-panel-head cfp-panel-head--stage2">
                    <div class="cfp-panel-title">
                        <span class="cfp-panel-icon cfp-panel-icon--docs"><?= $sectionDocsIcon ?></span>
                        <div>
                            <h2>2nd to Comply</h2>
                        </div>
                    </div>
                    <?php if ($stage2PendingReview > 0): ?>
                        <span class="cfp-chip cfp-chip--pending"><?= (int)$stage2PendingReview ?> pending review</span>
                    <?php endif; ?>
                </header>

                <ul class="cfp-checklist">
                    <?php foreach ($stage2Requirements as $key => $requirement): ?>
                        <?php
                            $status = (string)($requirement['status'] ?? 'pending');
                            $hasFile = !empty($requirement['file_path']);
                            $isApproved = $hasFile && $status === 'approved';
                            $rowIconMeta = $defaultDocIcon;
                            $itemUrl = 'index.php?r=coordinator_student_final&amp;student_id=' . $studentId . '&amp;doc=' . e($key);
                            $ctaLabel = ($hasFile && in_array($status, ['uploaded', 'approved'], true))
                                ? ($status === 'uploaded' ? 'Review' : 'View / Revoke')
                                : 'View';
                        ?>
                        <li class="cfp-item <?= $isApproved ? 'is-done' : ($hasFile ? 'is-progress' : 'is-pending') ?>">
                            <?php if ($hasFile): ?>
                                <a class="cfp-item-link" href="<?= $itemUrl ?>">
                            <?php else: ?>
                                <div class="cfp-item-link">
                            <?php endif; ?>
                                <span class="<?= e($rowIconMeta['class']) ?>"><?= $rowIconMeta['svg'] ?></span>
                                <div class="cfp-item-body">
                                    <strong><?= e($requirement['requirement_name'] ?? $key) ?></strong>
                                </div>
                                <span class="cfp-item-action">
                                    <?php if ($isApproved): ?>
                                        <span class="cfp-chip cfp-chip--done">Approved</span>
                                        <span class="cfp-item-cta"><?= e($ctaLabel) ?><?= $chevronIcon ?></span>
                                    <?php elseif ($hasFile): ?>
                                        <span class="cfp-chip cfp-chip--pending"><?= e(str_replace('_', ' ', $status)) ?></span>
                                        <span class="cfp-item-cta"><?= e($ctaLabel) ?><?= $chevronIcon ?></span>
                                    <?php else: ?>
                                        <span class="cfp-chip cfp-chip--pending">Not uploaded</span>
                                    <?php endif; ?>
                                </span>
                            <?php if ($hasFile): ?>
                                </a>
                            <?php else: ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <section class="cfp-panel">
            <header class="cfp-panel-head cfp-panel-head--stage3">
                <div class="cfp-panel-title">
                    <span class="cfp-panel-icon cfp-panel-icon--docs"><?= $sectionDocsIcon ?></span>
                        <div>
                            <h2>3rd to Comply Documents</h2>
                        </div>
                </div>
                <div class="cfp-panel-progress" role="progressbar" aria-valuenow="<?= $docPct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Documents progress">
                    <div class="cfp-panel-progress-track"><span style="width: <?= $docPct ?>%"></span></div>
                    <span class="cfp-panel-progress-label"><?= $docSubmitted ?>/<?= $docTotal ?></span>
                </div>
            </header>
            <?php if ($stage3PendingReview > 0): ?>
                <p class="cfp-note"><?= (int)$stage3PendingReview ?> document<?= $stage3PendingReview === 1 ? '' : 's' ?> waiting for review.</p>
            <?php endif; ?>

            <ul class="cfp-checklist">
                <?php foreach ($stage3Requirements as $key => $requirement): ?>
                    <?php
                        $status = (string)($requirement['status'] ?? 'pending');
                        $hasFile = !empty($requirement['file_path']);
                        $isApproved = $hasFile && $status === 'approved';
                        $rowIconMeta = $docRowIcons[$key] ?? $defaultDocIcon;
                        $itemUrl = 'index.php?r=coordinator_student_final&amp;student_id=' . $studentId . '&amp;doc=' . e($key);
                        $ctaLabel = ($hasFile && in_array($status, ['uploaded', 'approved'], true))
                            ? ($status === 'uploaded' ? 'Review' : 'View / Revoke')
                            : 'View';
                    ?>
                    <li class="cfp-item <?= $isApproved ? 'is-done' : ($hasFile ? 'is-progress' : 'is-pending') ?>">
                        <?php if ($hasFile): ?>
                            <a class="cfp-item-link" href="<?= $itemUrl ?>">
                        <?php else: ?>
                            <div class="cfp-item-link">
                        <?php endif; ?>
                            <span class="<?= e($rowIconMeta['class']) ?>"><?= $rowIconMeta['svg'] ?></span>
                            <div class="cfp-item-body">
                                <strong><?= e($requirement['requirement_name'] ?? $key) ?></strong>
                            </div>
                            <span class="cfp-item-action">
                                <?php if ($isApproved): ?>
                                    <span class="cfp-chip cfp-chip--done">Approved</span>
                                    <span class="cfp-item-cta">View<?= $chevronIcon ?></span>
                                <?php elseif ($hasFile): ?>
                                    <span class="cfp-chip cfp-chip--pending"><?= e(str_replace('_', ' ', $status)) ?></span>
                                    <span class="cfp-item-cta"><?= e($ctaLabel) ?><?= $chevronIcon ?></span>
                                <?php else: ?>
                                    <span class="cfp-chip cfp-chip--pending">Not uploaded</span>
                                <?php endif; ?>
                            </span>
                        <?php if ($hasFile): ?>
                            </a>
                        <?php else: ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="cfp-panel">
            <header class="cfp-panel-head cfp-panel-head--eval">
                <div class="cfp-panel-title">
                    <span class="cfp-panel-icon cfp-panel-icon--eval"><?= $sectionEvalIcon ?></span>
                        <div>
                            <h2>Student Evaluations</h2>
                        </div>
                </div>
                <div class="cfp-panel-progress" role="progressbar" aria-valuenow="<?= $evalPct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Evaluations progress">
                    <div class="cfp-panel-progress-track cfp-panel-progress-track--eval"><span style="width: <?= $evalPct ?>%"></span></div>
                    <span class="cfp-panel-progress-label"><?= $evalSubmitted ?>/<?= $evalTotal ?></span>
                </div>
            </header>

            <ul class="cfp-checklist">
                <?php foreach ($evaluationSections as $evalKey => $evalSection): ?>
                    <?php
                        $evalStatus = StudentEvaluation::statusFor($studentEvaluation, $evalKey);
                        $isSubmitted = $evalStatus === 'submitted';
                        $rowIconMeta = $evalRowIcons[$evalKey] ?? $evalRowIcons['coordinator'];
                        $canView = $isSubmitted && $evalKey !== 'coordinator';
                        $itemUrl = 'index.php?r=coordinator_student_final&amp;student_id=' . $studentId . '&amp;eval=' . e($evalKey);
                    ?>
                    <li class="cfp-item <?= $isSubmitted ? 'is-done' : 'is-pending' ?>">
                        <?php if ($canView): ?>
                            <a class="cfp-item-link" href="<?= $itemUrl ?>">
                        <?php else: ?>
                            <div class="cfp-item-link">
                        <?php endif; ?>
                            <span class="<?= e($rowIconMeta['class']) ?>"><?= $rowIconMeta['svg'] ?></span>
                            <div class="cfp-item-body">
                                <strong><?= e($evalSection['name']) ?></strong>
                            </div>
                            <span class="cfp-item-action">
                                <?php if ($isSubmitted && $evalKey === 'coordinator'): ?>
                                    <span class="cfp-chip cfp-chip--done">Submitted</span>
                                    <span class="cfp-item-cta muted">Private</span>
                                <?php elseif ($isSubmitted): ?>
                                    <span class="cfp-chip cfp-chip--done">Completed</span>
                                    <span class="cfp-item-cta">View<?= $chevronIcon ?></span>
                                <?php else: ?>
                                    <span class="cfp-chip cfp-chip--pending">Awaiting</span>
                                <?php endif; ?>
                            </span>
                        <?php if ($canView): ?>
                            </a>
                        <?php else: ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
</div>
