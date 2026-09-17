<?php
$kind = (string)($kind ?? '');
$evalType = (string)($evalType ?? '');
$emptyMessage = (string)($emptyMessage ?? '');
$title = (string)($title ?? 'Document');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body class="sp-doc-embed">
<?php if ($emptyMessage !== ''): ?>
    <p class="sp-doc-embed-empty"><?= e($emptyMessage) ?></p>
<?php elseif ($kind === 'form'): ?>
    <h1 class="sp-doc-embed-title"><?= e($requirement['requirement_name'] ?? $title) ?></h1>
    <?php require __DIR__ . '/requirement_form_readonly.php'; ?>
<?php elseif ($kind === 'evaluation' && $evalType === 'coordinator'): ?>
    <?php require __DIR__ . '/../coordinator/evaluations/coordinator.php'; ?>
<?php elseif ($kind === 'evaluation' && $evalType === 'industry_partner'): ?>
    <?php require __DIR__ . '/../coordinator/evaluations/industry_partner.php'; ?>
<?php else: ?>
    <p class="sp-doc-embed-empty">No content to display.</p>
<?php endif; ?>
</body>
</html>
