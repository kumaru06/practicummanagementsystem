<?php
    $activePanel = $activePanel ?? '';
    $finalRequirement = $finalRequirement ?? [];
    $canEditForm = !empty($canEditForm);
    $formRequirementStatus = (string)($formRequirementStatus ?? 'pending');
    $panelTitles = [
        'company_profile' => 'Company Profile',
        'job_description' => 'Job Description',
    ];
    $panelTitlesJson = json_encode($panelTitles, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<div
    class="js-final-req-shell"
    data-active-panel="<?= e($activePanel) ?>"
    data-active-kind="doc"
    data-base-url="<?= e(route_url('student.documents', ['stage' => 3])) ?>"
    data-page-title="Documents"
    data-wide-panels=""
    data-panel-titles="<?= e($panelTitlesJson ?: '{}') ?>"
>
    <div class="final-req-view final-req-view--form is-active" data-final-view="form">
        <div class="final-form-page final-form-v2 js-final-req-form-wrap">
            <?php require __DIR__ . '/final_form_back.php'; ?>
            <?php if (!$canEditForm && in_array($formRequirementStatus, ['uploaded', 'approved'], true)): ?>
                <p class="muted final-form-readonly-note">
                    <?= $formRequirementStatus === 'approved'
                        ? 'This form was approved. Contact your coordinator if you need changes.'
                        : 'This form is awaiting coordinator review. You can view your answers below.' ?>
                </p>
            <?php endif; ?>
            <div class="final-req-panels">
                <div class="final-req-panel<?= $activePanel === 'company_profile' ? ' is-active' : '' ?>" data-final-panel="company_profile">
                    <?php
                        $formReadOnly = !$canEditForm;
                        require __DIR__ . '/../final/company_profile.php';
                    ?>
                </div>
                <div class="final-req-panel<?= $activePanel === 'job_description' ? ' is-active' : '' ?>" data-final-panel="job_description">
                    <?php
                        $formReadOnly = !$canEditForm;
                        require __DIR__ . '/../final/job_description.php';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?= e(asset_url('assets/js/final-req-nav.js')) ?>"></script>
