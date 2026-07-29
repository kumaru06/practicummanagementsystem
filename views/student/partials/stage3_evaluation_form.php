<?php
    $activePanel = $activePanel ?? '';
    $widePanels = ['industry_partner', 'coordinator'];
    $formWrapWide = in_array($activePanel, $widePanels, true);
    $evaluationSections = $evaluationSections ?? FinalRequirement::EVALUATION_SECTIONS;
    $panelTitles = [];
    foreach ($evaluationSections as $key => $section) {
        $panelTitles[$key] = $section['name'];
    }
    $panelTitlesJson = json_encode($panelTitles, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<div
    class="js-final-req-shell"
    data-active-panel="<?= e($activePanel) ?>"
    data-active-kind="eval"
    data-base-url="<?= e(route_url('student.documents', ['stage' => 3])) ?>"
    data-page-title="Documents"
    data-wide-panels="<?= e(implode(',', $widePanels)) ?>"
    data-panel-titles="<?= e($panelTitlesJson ?: '{}') ?>"
>
    <div class="final-req-view final-req-view--form is-active" data-final-view="form">
        <div class="final-form-page final-form-v2 js-final-req-form-wrap<?= $formWrapWide ? ' final-form-page--wide' : '' ?>">
            <?php require __DIR__ . '/final_form_back.php'; ?>
            <div class="final-req-panels">
                <?php foreach (['industry_partner' => __DIR__ . '/../evaluations/industry_partner.php', 'coordinator' => __DIR__ . '/../evaluations/coordinator.php'] as $panelKey => $panelPath): ?>
                    <div class="final-req-panel<?= $activePanel === $panelKey ? ' is-active' : '' ?>" data-final-panel="<?= e($panelKey) ?>">
                        <?php require $panelPath; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script src="<?= e(asset_url('assets/js/final-req-nav.js')) ?>"></script>
