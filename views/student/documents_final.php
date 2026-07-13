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

    $svgAttrs = 'class="final-req-icon" viewBox="0 0 24 24" aria-hidden="true"';
    $infoIcon = '<svg ' . $svgAttrs . '><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>';
    $successIcon = '<svg ' . $svgAttrs . '><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>';
    $sectionDocsIcon = '<svg ' . $svgAttrs . '><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg>';
    $sectionEvalIcon = '<svg ' . $svgAttrs . '><path fill="currentColor" d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>';
    $summaryChartIcon = '<svg ' . $svgAttrs . '><path fill="currentColor" d="M5 3h9l5 5v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm8 1.5V8h3.5L13 4.5ZM8 13h2v5H8v-5Zm3.5-3h2v8h-2v-8ZM15 15h2v3h-2v-3Z"/></svg>';
    $docRowIcons = [
        'job_description' => [
            'class' => 'final-req-row-icon final-req-row-icon--job',
            'svg' => '<svg ' . $svgAttrs . '><path fill="currentColor" d="M10 2h4a2 2 0 0 1 2 2v1h4a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h4V4a2 2 0 0 1 2-2Zm0 4V4h-4v2h4Zm-2 8v2h4v-2H8Z"/></svg>',
        ],
        'company_profile' => [
            'class' => 'final-req-row-icon final-req-row-icon--company',
            'svg' => '<svg ' . $svgAttrs . '><path fill="currentColor" d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg>',
        ],
        'personal_observation' => [
            'class' => 'final-req-row-icon final-req-row-icon--observation',
            'svg' => '<svg ' . $svgAttrs . '><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25ZM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83Z"/></svg>',
        ],
    ];
    $evalRowIcons = [
        'coordinator' => [
            'class' => 'final-req-row-icon final-req-row-icon--eval',
            'svg' => '<svg ' . $svgAttrs . '><path fill="currentColor" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg>',
        ],
        'industry_partner' => [
            'class' => 'final-req-row-icon final-req-row-icon--partner',
            'svg' => '<svg ' . $svgAttrs . '><path fill="currentColor" d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg>',
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
    $evalSubmitted = 0;
    foreach (array_keys($evaluationSections) as $evalKey) {
        if (StudentEvaluation::statusFor($studentEvaluation, $evalKey) === 'submitted') {
            $evalSubmitted++;
        }
    }
    $evalTotal = count($evaluationSections);
    $evalPct = $evalTotal > 0 ? (int)round(($evalSubmitted / $evalTotal) * 100) : 0;
    $overallDone = $docSubmitted + $evalSubmitted;
    $overallTotal = $docTotal + $evalTotal;
    $overallPct = $overallTotal > 0 ? (int)round(($overallDone / $overallTotal) * 100) : 0;
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
<div class="final-req-page final-req-v2">
    <nav class="final-req-breadcrumb" aria-label="Breadcrumb">
        <a href="index.php?r=student_documents">Documents</a>
        <span class="final-req-breadcrumb-sep" aria-hidden="true">></span>
        <span aria-current="page">Final Requirements</span>
    </nav>

    <section class="fr-hero" aria-label="Final requirements overview">
        <div class="fr-hero-copy">
            <span class="fr-hero-kicker">Completion Tracker</span>
            <h1 class="fr-hero-title">Final Requirements</h1>
            <p>Submit your final documents and complete evaluations before OJT completion.</p>
        </div>
        <div class="fr-hero-stats">
            <div class="fr-hero-stat">
                <span>Overall</span>
                <strong><?= (int)$overallDone ?>/<?= (int)$overallTotal ?></strong>
            </div>
            <div class="fr-hero-stat">
                <span>Documents</span>
                <strong><?= (int)$docSubmitted ?>/<?= (int)$docTotal ?></strong>
            </div>
            <div class="fr-hero-stat">
                <span>Evaluations</span>
                <strong><?= (int)$evalSubmitted ?>/<?= (int)$evalTotal ?></strong>
            </div>
        </div>
        <div class="fr-hero-progress">
            <div class="fr-hero-progress-head">
                <span>Overall progress</span>
                <strong><?= (int)$overallPct ?>%</strong>
            </div>
            <div class="fr-hero-progress-track"><span style="width: <?= (int)$overallPct ?>%"></span></div>
        </div>
    </section>

    <section class="card final-req-section final-req-section--docs">
        <header class="final-req-section-head">
            <span class="final-req-section-icon final-req-section-icon--docs"><?= $sectionDocsIcon ?></span>
            <div class="final-req-section-copy">
                <span class="fr-section-eyebrow">Step 1</span>
                <h2>Student - Submit the Following</h2>
                <p class="muted">Please input the required information below.</p>
            </div>
            <span class="fr-section-chip"><?= (int)$docSubmitted ?>/<?= (int)$docTotal ?> done</span>
        </header>

        <div class="fr-section-progress" aria-hidden="true">
            <div class="fr-section-progress-track"><span style="width: <?= (int)$docPct ?>%"></span></div>
        </div>

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
                    $rowStateClass = $status === 'submitted' ? 'is-complete' : 'is-pending';
                ?>
                <article class="final-req-row <?= e($rowStateClass) ?>">
                    <div class="final-req-row-main">
                        <span class="<?= e($rowIconMeta['class']) ?>"><?= $rowIconMeta['svg'] ?></span>
                        <div class="final-req-row-copy">
                            <strong><?= e($section['name']) ?></strong>
                            <span><?= e($section['description']) ?></span>
                        </div>
                    </div>
                    <div class="final-req-row-meta">
                        <span class="fr-status-pill fr-status-pill--<?= e($status === 'submitted' ? 'submitted' : 'pending') ?>"><?= e($statusLabel) ?></span>
                        <a class="<?= e($actionClass) ?> js-final-req-open" href="index.php?r=student_documents_final&amp;doc=<?= e($key) ?>" data-final-panel="<?= e($key) ?>" data-final-kind="doc">
                            <span><?= e($actionLabel) ?></span>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                        </a>
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

    <section class="card final-req-section final-req-section--eval">
        <header class="final-req-section-head">
            <span class="final-req-section-icon final-req-section-icon--eval"><?= $sectionEvalIcon ?></span>
            <div class="final-req-section-copy">
                <span class="fr-section-eyebrow">Step 2</span>
                <h2>Evaluations</h2>
                <p class="muted">Evaluate your overall performance and OJT experience.</p>
            </div>
            <span class="fr-section-chip"><?= (int)$evalSubmitted ?>/<?= (int)$evalTotal ?> done</span>
        </header>

        <div class="fr-section-progress" aria-hidden="true">
            <div class="fr-section-progress-track"><span style="width: <?= (int)$evalPct ?>%"></span></div>
        </div>

        <div class="final-req-list">
            <?php foreach ($evaluationSections as $key => $section): ?>
                <?php
                    $status = StudentEvaluation::statusFor($studentEvaluation, $key);
                    $statusLabel = $status === 'submitted' ? 'Completed' : 'Pending';
                    $actionLabel = $status === 'submitted' ? 'Edit' : 'Evaluate';
                    $actionClass = $status === 'submitted' ? 'final-req-action final-req-action--edit' : 'final-req-action final-req-action--primary';
                    $rowIconMeta = $evalRowIcons[$key] ?? $evalRowIcons['coordinator'];
                    $rowStateClass = $status === 'submitted' ? 'is-complete' : 'is-pending';
                ?>
                <article class="final-req-row <?= e($rowStateClass) ?>">
                    <div class="final-req-row-main">
                        <span class="<?= e($rowIconMeta['class']) ?>"><?= $rowIconMeta['svg'] ?></span>
                        <div class="final-req-row-copy">
                            <strong><?= e($section['name']) ?></strong>
                            <span><?= e($section['description']) ?></span>
                        </div>
                    </div>
                    <div class="final-req-row-meta">
                        <span class="fr-status-pill fr-status-pill--<?= e($status === 'submitted' ? 'submitted' : 'pending') ?>"><?= e($statusLabel) ?></span>
                        <a class="<?= e($actionClass) ?> js-final-req-open" href="index.php?r=student_documents_final&amp;eval=<?= e($key) ?>" data-final-panel="<?= e($key) ?>" data-final-kind="eval">
                            <span><?= e($actionLabel) ?></span>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                        </a>
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
                        <span class="final-req-summary-label">Host Training Establishment Evaluation</span>
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
    <div class="final-form-page final-form-v2 js-final-req-form-wrap<?= $formWrapWide ? ' final-form-page--wide' : '' ?>">
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
<script src="<?= e(asset_url('assets/js/final-req-nav.js')) ?>"></script>
