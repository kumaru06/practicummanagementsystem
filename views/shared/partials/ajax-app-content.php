<div class="toast-stack" aria-live="polite">
    <?= render_flash_toasts() ?>
</div>
<div data-ajax-page data-page-title="<?= e($ajaxTitle) ?>" data-page-hint="<?= e((string)($topbarHint ?? '')) ?>" data-route="<?= e($ajaxRoute) ?>">
<?= $pageHtml ?>
</div>
