<div class="toast-stack" aria-live="polite">
    <?php if ($m = flash('success')): ?><div class="toast success"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = flash('error')): ?><div class="toast danger"><?= e($m) ?></div><?php endif; ?>
</div>
<div data-ajax-page data-page-title="<?= e($ajaxTitle) ?>" data-route="<?= e($ajaxRoute) ?>">
<?= $pageHtml ?>
</div>
