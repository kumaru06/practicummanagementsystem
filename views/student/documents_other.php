<section class="card documents-collection-card">
    <div class="section-head">
        <div>
            <h2>Other Documents</h2>
            <p class="muted">Your Certificate of Registration and submitted weekly reports.</p>
        </div>
    </div>

    <div class="doc-collection">
        <div class="doc-collection-block">
            <h3 class="doc-collection-title">Certificate of Registration</h3>
            <?php if ($student && !empty($student['cor_file'])): ?>
                <a class="requirement-file-chip" target="_blank" href="<?= e(asset($student['cor_file'])) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                    View COR
                </a>
            <?php else: ?>
                <p class="doc-empty">No COR uploaded yet.</p>
            <?php endif; ?>
        </div>

        <div class="doc-collection-block">
            <h3 class="doc-collection-title">Weekly Reports</h3>
            <?php if (empty($weeklyReports)): ?>
                <p class="doc-empty">No weekly reports uploaded yet.</p>
            <?php else: ?>
                <div class="weekly-report-grid">
                    <?php foreach ($weeklyReports as $r): ?>
                        <div class="weekly-report-chip">
                            <span class="weekly-report-week">Week <?= (int)$r['week_no'] ?></span>
                            <?php if (!empty($r['file_path'])): ?>
                                <a class="weekly-report-link" target="_blank" href="<?= e(asset($r['file_path'])) ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                    View PDF
                                </a>
                            <?php else: ?>
                                <span class="muted">No file</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
