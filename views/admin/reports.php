<div class="reports-hub">
    <header class="reports-hub-lead">
        <p class="reports-hub-eyebrow">Monitoring &amp; accomplishment</p>
        <p class="reports-hub-desc">Pick a category below to open a report. Each list opens a searchable table you can review and export from.</p>
    </header>

    <div class="reports-hub-grid">
        <?php foreach ($categories as $category): ?>
        <?php $itemCount = count($category['items']); ?>
        <section class="reports-panel" id="reports-<?= e($category['id']) ?>">
            <header class="reports-panel-head">
                <span class="reports-panel-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="<?= e($category['icon']) ?>"/></svg>
                </span>
                <div class="reports-panel-titles">
                    <h3><?= e($category['title']) ?></h3>
                    <p class="reports-panel-meta"><?= $itemCount ?> report<?= $itemCount === 1 ? '' : 's' ?></p>
                </div>
            </header>
            <ul class="reports-panel-list">
                <?php foreach ($category['items'] as $item): ?>
                <li>
                    <a class="reports-panel-link" href="<?= e(route_url('admin.report', ['slug' => $item['slug']])) ?>">
                        <span><?= e($item['label']) ?></span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endforeach; ?>
    </div>
</div>
