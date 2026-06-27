<section class="card reports-intro">
    <div class="section-head">
        <div>
            <h2>Reports</h2>
            <p class="muted reports-purpose">Generate monitoring and accomplishment reports.</p>
        </div>
    </div>
</section>

<div class="reports-hub-grid">
    <?php foreach ($categories as $index => $category): ?>
    <section class="card reports-category-card" id="reports-<?= e($category['id']) ?>">
        <div class="reports-category-head">
            <span class="reports-category-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="<?= e($category['icon']) ?>"/></svg>
            </span>
            <div>
                <span class="reports-category-number"><?= (int)$index + 1 ?></span>
                <h3><?= e($category['title']) ?></h3>
            </div>
        </div>
        <ul class="reports-category-list">
            <?php foreach ($category['items'] as $item): ?>
            <li>
                <a class="reports-category-link" href="<?= e(route_url('admin.report', ['slug' => $item['slug']])) ?>">
                    <span><?= e($item['label']) ?></span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endforeach; ?>
</div>
