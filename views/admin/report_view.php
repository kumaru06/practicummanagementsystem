<section class="card">
    <div class="section-head section-head-split">
        <div>
            <p class="reports-breadcrumb muted"><a href="<?= e(route_url('admin.reports')) ?>">Reports</a> / <?= e($report['category']) ?></p>
            <h2><?= e($report['label']) ?></h2>
            <?php if (!empty($description)): ?>
                <p class="muted"><?= e($description) ?></p>
            <?php endif; ?>
        </div>
        <a class="btn btn-small" href="<?= e(route_url('admin.reports')) ?>">Back to Reports</a>
    </div>

    <?php if (empty($ready)): ?>
        <p class="muted" style="padding:24px 0">This report is not available yet.</p>
    <?php elseif (empty($rows)): ?>
        <p class="muted" style="padding:24px 0">No records found for this report yet.</p>
    <?php else: ?>
        <div class="reports-summary muted"><?= count($rows) ?> record<?= count($rows) === 1 ? '' : 's' ?></div>
        <input class="table-search table-search-wide" placeholder="Search report...">
        <div class="table-wrap"><table class="data-table"><thead><tr>
            <?php foreach ($columns as $column): ?>
            <th data-sort><?= e($column) ?></th>
            <?php endforeach; ?>
        </tr></thead><tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($row as $cell): ?>
                <td><?= e((string)$cell) ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody></table></div>
        <div class="pagination"></div>
    <?php endif; ?>
</section>
