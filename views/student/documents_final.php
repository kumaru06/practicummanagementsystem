<?php
    $finalRequirement = $finalRequirement ?? [];
    $finalSections = $finalSections ?? [];
    $studentEvaluation = $studentEvaluation ?? [];
    $evaluationSections = $evaluationSections ?? [];

    $docSubmitted = 0;
    $docTotal = count($finalSections);
    foreach (array_keys($finalSections) as $sectionKey) {
        if ((string)($finalRequirement[$sectionKey . '_status'] ?? 'pending') === 'submitted') {
            $docSubmitted++;
        }
    }
    $docPct = $docTotal > 0 ? (int)round(($docSubmitted / $docTotal) * 100) : 0;
    $allDocsSubmitted = $docSubmitted === $docTotal && $docTotal > 0;

    $coordinatorStatus = StudentEvaluation::statusFor($studentEvaluation, 'coordinator');
    $partnerStatus = StudentEvaluation::statusFor($studentEvaluation, 'industry_partner');

    $svgAttrs = 'class="final-req-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
    $infoIcon = '<svg ' . $svgAttrs . '><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>';
    $successIcon = '<svg ' . $svgAttrs . '><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>';
    $sectionDocsIcon = '<svg ' . $svgAttrs . '><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><path d="M12 11v6M9 14h6"/></svg>';
    $sectionEvalIcon = '<svg ' . $svgAttrs . '><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 12h6M9 16h4M9 8h6"/></svg>';
    $summaryChartIcon = '<svg ' . $svgAttrs . '><path d="M3 3v18h18"/><path d="m7 16 4-5 4 3 5-7"/></svg>';
    $docRowIcons = [
        'job_description' => [
            'class' => 'final-req-row-icon final-req-row-icon--job',
            'svg' => '<svg ' . $svgAttrs . '><path d="M10 7V5a2 2 0 0 1 4 0v2"/><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M3 12h18"/><path d="M12 12v4"/></svg>',
        ],
        'company_profile' => [
            'class' => 'final-req-row-icon final-req-row-icon--company',
            'svg' => '<svg ' . $svgAttrs . '><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12h12"/><path d="M10 8h.01M14 8h.01M10 16h.01M14 16h.01"/></svg>',
        ],
        'personal_observation' => [
            'class' => 'final-req-row-icon final-req-row-icon--observation',
            'svg' => '<svg ' . $svgAttrs . '><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/><path d="m15 5 3 3"/></svg>',
        ],
    ];
    $evalRowIcons = [
        'coordinator' => [
            'class' => 'final-req-row-icon final-req-row-icon--eval',
            'svg' => '<svg ' . $svgAttrs . '><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/></svg>',
        ],
        'industry_partner' => [
            'class' => 'final-req-row-icon final-req-row-icon--partner',
            'svg' => '<svg ' . $svgAttrs . '><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01"/></svg>',
        ],
    ];

    $activePanel = $activePanel ?? '';
    $activeKind = $activeKind ?? '';
    $showFormView = $activePanel !== '';
    $widePanels = ['industry_partner', 'coordinator'];
    $panelTitles = [];
    foreach ($finalSections as $key => $section) {
        $panelTitles[$key] = $section['name'];
    }
    foreach ($evaluationSections as $key => $section) {
        $panelTitles[$key] = $section['name'];
    }
    $panelTitlesJson = json_encode($panelTitles, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $formWrapWide = in_array($activePanel, $widePanels, true);
?>
<div
    class="js-final-req-shell"
    data-active-panel="<?= e($activePanel) ?>"
    data-active-kind="<?= e($activeKind) ?>"
    data-base-url="index.php?r=student_documents_final"
    data-page-title="Final Requirements"
    data-wide-panels="<?= e(implode(',', $widePanels)) ?>"
    data-panel-titles="<?= e($panelTitlesJson ?: '{}') ?>"
>
<div class="final-req-stage">
<div class="final-req-view final-req-view--list<?= $showFormView ? '' : ' is-active' ?>" data-final-view="list">
<div class="final-req-page">
    <nav class="final-req-breadcrumb" aria-label="Breadcrumb">
        <a href="index.php?r=student_documents">Documents</a>
        <span class="final-req-breadcrumb-sep" aria-hidden="true">›</span>
        <span aria-current="page">Final Requirements</span>
    </nav>

    <section class="card final-req-section">
        <header class="final-req-section-head">
            <span class="final-req-section-icon final-req-section-icon--docs"><?= $sectionDocsIcon ?></span>
            <div class="final-req-section-copy">
                <h2><span class="final-req-section-step">1.</span> Student — Submit the Following</h2>
                <p class="muted">Please input the required information below.</p>
            </div>
        </header>

        <div class="final-req-banner info">
            <span class="final-req-banner-icon"><?= $infoIcon ?></span>
            <p>Complete all document sections below. You can edit any entry after submission.</p>
        </div>

        <div class="final-req-list">
            <?php foreach ($finalSections as $key => $section): ?>
                <?php
                    $status = (string)($finalRequirement[$key . '_status'] ?? 'pending');
                    $status = $status !== '' ? $status : 'pending';
                    $statusLabel = $status === 'submitted' ? 'Submitted' : 'Pending';
                    $actionLabel = $status === 'submitted' ? 'Edit' : 'Input';
                    $actionClass = $status === 'submitted' ? 'final-req-action final-req-action--edit' : 'final-req-action final-req-action--primary';
                    $rowIconMeta = $docRowIcons[$key] ?? ['class' => 'final-req-row-icon final-req-row-icon--company', 'svg' => $docRowIcons['company_profile']['svg']];
                ?>
                <article class="final-req-row">
                    <div class="final-req-row-main">
                        <span class="<?= e($rowIconMeta['class']) ?>"><?= $rowIconMeta['svg'] ?></span>
                        <div class="final-req-row-copy">
                            <strong><?= e($section['name']) ?></strong>
                            <span><?= e($section['description']) ?></span>
                        </div>
                    </div>
                    <div class="final-req-row-meta">
                        <span class="badge <?= e($status) ?>"><?= e($statusLabel) ?></span>
                        <a class="<?= e($actionClass) ?> js-final-req-open" href="index.php?r=student_documents_final&amp;doc=<?= e($key) ?>" data-final-panel="<?= e($key) ?>" data-final-kind="doc"><?= e($actionLabel) ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="final-req-banner <?= $allDocsSubmitted ? 'success' : 'info' ?>">
            <span class="final-req-banner-icon"><?= $allDocsSubmitted ? $successIcon : $infoIcon ?></span>
            <div>
                <?php if ($allDocsSubmitted): ?>
                    <strong>All final requirements submitted</strong>
                    <p>You have completed all the required documents. You may still edit any entry.</p>
                <?php else: ?>
                    <strong>Action needed</strong>
                    <p>Please input all required information to complete your final requirements.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="card final-req-section">
        <header class="final-req-section-head">
            <span class="final-req-section-icon final-req-section-icon--eval"><?= $sectionEvalIcon ?></span>
            <div class="final-req-section-copy">
                <h2><span class="final-req-section-step">2.</span> Evaluations</h2>
                <p class="muted">Evaluate your overall performance and OJT experience.</p>
            </div>
        </header>

        <div class="final-req-list">
            <?php foreach ($evaluationSections as $key => $section): ?>
                <?php
                    $status = StudentEvaluation::statusFor($studentEvaluation, $key);
                    $statusLabel = $status === 'submitted' ? 'Completed' : 'Pending';
                    $actionLabel = $status === 'submitted' ? 'Edit' : 'Evaluate';
                    $actionClass = $status === 'submitted' ? 'final-req-action final-req-action--edit' : 'final-req-action final-req-action--primary';
                    $rowIconMeta = $evalRowIcons[$key] ?? $evalRowIcons['coordinator'];
                ?>
                <article class="final-req-row">
                    <div class="final-req-row-main">
                        <span class="<?= e($rowIconMeta['class']) ?>"><?= $rowIconMeta['svg'] ?></span>
                        <div class="final-req-row-copy">
                            <strong><?= e($section['name']) ?></strong>
                            <span><?= e($section['description']) ?></span>
                        </div>
                    </div>
                    <div class="final-req-row-meta">
                        <span class="badge <?= e($status) ?>"><?= e($statusLabel) ?></span>
                        <a class="<?= e($actionClass) ?> js-final-req-open" href="index.php?r=student_documents_final&amp;eval=<?= e($key) ?>" data-final-panel="<?= e($key) ?>" data-final-kind="eval"><?= e($actionLabel) ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card final-req-summary">
        <header class="final-req-summary-head">
            <span class="final-req-summary-icon"><?= $summaryChartIcon ?></span>
            <h2>Submission Summary</h2>
        </header>

        <div class="final-req-summary-grid">
            <article class="final-req-summary-card">
                <div class="final-req-summary-card-top">
                    <span class="final-req-summary-card-icon final-req-summary-card-icon--docs"><?= $sectionDocsIcon ?></span>
                    <div>
                        <span class="final-req-summary-label">Documents Submitted</span>
                        <strong class="final-req-summary-value"><?= (int)$docSubmitted ?> / <?= (int)$docTotal ?></strong>
                    </div>
                </div>
                <div class="final-req-summary-progress">
                    <div class="final-req-summary-progress-track"><span style="width: <?= $docPct ?>%"></span></div>
                    <span><?= $docPct ?>%</span>
                </div>
            </article>

            <article class="final-req-summary-card">
                <div class="final-req-summary-card-top">
                    <span class="final-req-summary-card-icon final-req-summary-card-icon--eval"><?= $evalRowIcons['coordinator']['svg'] ?></span>
                    <div>
                        <span class="final-req-summary-label">OJT Coordinator Evaluation</span>
                        <strong class="final-req-summary-value"><?= $coordinatorStatus === 'submitted' ? 'Completed' : 'Pending' ?></strong>
                    </div>
                </div>
                <p class="final-req-summary-note"><?= $coordinatorStatus === 'submitted' ? 'Evaluation submitted' : 'Not started' ?></p>
            </article>

            <article class="final-req-summary-card">
                <div class="final-req-summary-card-top">
                    <span class="final-req-summary-card-icon final-req-summary-card-icon--partner"><?= $evalRowIcons['industry_partner']['svg'] ?></span>
                    <div>
                        <span class="final-req-summary-label">Industry Partner Evaluation</span>
                        <strong class="final-req-summary-value"><?= $partnerStatus === 'submitted' ? 'Completed' : 'Pending' ?></strong>
                    </div>
                </div>
                <p class="final-req-summary-note"><?= $partnerStatus === 'submitted' ? 'Evaluation submitted' : 'Not started' ?></p>
            </article>
        </div>
    </section>
</div>
</div>

<div class="final-req-view final-req-view--form<?= $showFormView ? ' is-active' : '' ?>" data-final-view="form">
    <div class="final-form-page js-final-req-form-wrap<?= $formWrapWide ? ' final-form-page--wide' : '' ?>">
        <?php require __DIR__ . '/partials/final_form_back.php'; ?>
        <div class="final-req-panels">
            <?php
            $finalPanels = [
                'job_description' => __DIR__ . '/final/job_description.php',
                'company_profile' => __DIR__ . '/final/company_profile.php',
                'personal_observation' => __DIR__ . '/final/personal_observation.php',
                'industry_partner' => __DIR__ . '/evaluations/industry_partner.php',
                'coordinator' => __DIR__ . '/evaluations/coordinator.php',
            ];
            foreach ($finalPanels as $panelKey => $panelPath):
            ?>
                <div class="final-req-panel<?= $activePanel === $panelKey ? ' is-active' : '' ?>" data-final-panel="<?= e($panelKey) ?>">
                    <?php require $panelPath; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</div>
</div>
<script src="<?= e(asset('assets/js/final-req-nav.js')) ?>?v=20260611-final-req-spa"></script>
