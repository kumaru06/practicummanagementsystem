<?php
$companies = $companies ?? [];
$documentCount  = count($companies);
$activeCount    = count(array_filter($companies, static fn(array $c): bool => (int)($c['is_active'] ?? 0) === 1));
$inactiveCount  = max(0, $documentCount - $activeCount);
$uniquePrograms = [];

foreach ($companies as $company) {
    foreach (array_filter(array_map('trim', explode(',', (string)($company['accepted_programs'] ?? '')))) as $programCode) {
        $uniquePrograms[$programCode] = true;
    }
}

$programCoverage = count($uniquePrograms);
?>

<div class="cdoc-page" data-cdoc-library>
    <section class="cdoc-hero" aria-labelledby="moa-library-title">
        <div class="cdoc-hero-bg" aria-hidden="true">
            <span class="cdoc-hero-orb cdoc-hero-orb--one"></span>
            <span class="cdoc-hero-orb cdoc-hero-orb--two"></span>
            <span class="cdoc-hero-orb cdoc-hero-orb--three"></span>
            <span class="cdoc-hero-grid"></span>
        </div>
        <div class="cdoc-hero-content">
            <div class="cdoc-hero-kicker">
                <span class="cdoc-hero-kicker-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M14 2H7a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V8l-6-6Zm0 2.8L17.2 8H14V4.8ZM8 13h8v1.8H8V13Zm0 4h8v1.8H8V17Zm0-8h4v1.8H8V9Z"/></svg>
                </span>
                Host Training Establishment Agreement Center
            </div>
            <h1 id="moa-library-title">MOA / MOU Library</h1>
            <p>Review official Host Training Establishment agreements in a clean document workspace before assigning students to deployment organizations.</p>
            <div class="cdoc-hero-actions">
                <a class="cdoc-primary-action" href="#company-agreements">
                    Browse documents
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16.5 5.5 10l1.42-1.42L12 13.66l5.08-5.08L18.5 10 12 16.5Z"/></svg>
                </a>
                <span class="cdoc-hero-note">Only companies with uploaded MOA/MOU files are shown.</span>
            </div>
        </div>
    </section>

    <div class="cdoc-stats-strip" aria-label="Document summary">
        <article class="cdoc-stat-card cdoc-stat-total">
            <div class="cdoc-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5Z"/></svg>
            </div>
            <div class="cdoc-stat-body">
                <span>Total Documents</span>
                <strong><?= (int)$documentCount ?></strong>
            </div>
        </article>
        <article class="cdoc-stat-card cdoc-stat-active">
            <div class="cdoc-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 1 3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 11-2-2 1.41-1.41L10 11.17l3.59-3.59L15 9l-5 5z"/></svg>
            </div>
            <div class="cdoc-stat-body">
                <span>Active Partners</span>
                <strong><?= (int)$activeCount ?></strong>
            </div>
        </article>
        <article class="cdoc-stat-card cdoc-stat-inactive">
            <div class="cdoc-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            </div>
            <div class="cdoc-stat-body">
                <span>Inactive</span>
                <strong><?= (int)$inactiveCount ?></strong>
            </div>
        </article>
        <article class="cdoc-stat-card cdoc-stat-programs">
            <div class="cdoc-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3 1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
            </div>
            <div class="cdoc-stat-body">
                <span>Programs Covered</span>
                <strong><?= (int)$programCoverage ?></strong>
            </div>
        </article>
    </div>

    <section class="card cdoc-registry-card" id="company-agreements" aria-labelledby="company-agreements-title">
        <header class="cdoc-registry-head">
            <div class="cdoc-registry-copy">
                <span class="cdoc-section-eyebrow">Document Registry</span>
                <h2 id="company-agreements-title">Company Agreements</h2>
            </div>
            <?php if ($documentCount > 0): ?>
            <div class="cdoc-live-count" aria-live="polite">
                <strong data-cdoc-visible-count><?= (int)$documentCount ?></strong>
                <span>shown</span>
            </div>
            <?php endif; ?>
        </header>

        <?php if (!empty($companies)): ?>
        <div class="cdoc-toolbar" role="region" aria-label="Agreement filters">
            <label class="cdoc-search" for="cdoc-search-input">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4a6 6 0 0 1 4.74 9.67l4.3 4.29-1.42 1.42-4.29-4.3A6 6 0 1 1 10 4Zm0 2a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/></svg>
                <input id="cdoc-search-input" type="search" placeholder="Search company, contact, email, program, or address..." data-cdoc-search autocomplete="off">
            </label>
            <div class="cdoc-filter-group" aria-label="Filter by status">
                <button class="cdoc-filter is-active" type="button" data-cdoc-filter="all">All</button>
                <button class="cdoc-filter" type="button" data-cdoc-filter="active">Active</button>
                <button class="cdoc-filter" type="button" data-cdoc-filter="inactive">Inactive</button>
            </div>
        </div>

        <div class="cdoc-grid">
            <?php foreach ($companies as $company): ?>
            <?php
                $programCodes = array_values(array_filter(array_map('trim', explode(',', (string)($company['accepted_programs'] ?? '')))));
                $programCount = count($programCodes);
                $name         = (string)($company['name'] ?? 'Host Training Establishment');
                $initial      = strtoupper(substr($name, 0, 1));
                $isActive     = !empty($company['is_active']);
                $email        = $company['email'] ?? ($company['contact_email'] ?? '');
                $phone        = $company['contact_number'] ?? '';
                $contact      = $company['contact_person'] ?? '';
                $address      = $company['address'] ?? '';
                $documentPath = (string)($company['moa_mou_file'] ?? '');
                $documentName = basename($documentPath) ?: 'MOA / MOU Document';
                $documentExt  = strtoupper(pathinfo($documentName, PATHINFO_EXTENSION) ?: 'FILE');
                $absoluteDocumentPath = $documentPath !== '' ? realpath(__DIR__ . '/../../' . ltrim($documentPath, '/\\')) : false;
                $documentSize = ($absoluteDocumentPath && is_file($absoluteDocumentPath)) ? filesize($absoluteDocumentPath) : null;
                $documentSizeLabel = $documentSize !== null ? number_format(max(1, $documentSize / 1024), 0) . ' KB' : 'Ready to view';
                $searchText = strtolower(trim($name . ' ' . $contact . ' ' . $email . ' ' . $phone . ' ' . $address . ' ' . implode(' ', $programCodes) . ' ' . $documentName));
            ?>
            <article class="cdoc-card<?= $isActive ? '' : ' cdoc-card--inactive' ?>" data-cdoc-card data-status="<?= $isActive ? 'active' : 'inactive' ?>" data-search="<?= e($searchText) ?>">
                <div class="cdoc-card-accent" aria-hidden="true"></div>
                <header class="cdoc-card-header">
                    <div class="cdoc-avatar" aria-hidden="true"><?= e($initial) ?></div>
                    <div class="cdoc-card-identity">
                        <span class="cdoc-card-label">Host Training Establishment</span>
                        <h3 class="cdoc-card-name" title="<?= e($name) ?>"><?= e($name) ?></h3>
                    </div>
                    <span class="cdoc-status-badge <?= $isActive ? 'cdoc-status-badge--active' : 'cdoc-status-badge--inactive' ?>">
                        <span class="cdoc-status-dot"></span>
                        <?= $isActive ? 'Active' : 'Inactive' ?>
                    </span>
                </header>

                <div class="cdoc-document-strip">
                    <div class="cdoc-document-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 13h8v2H8v-2Zm0 4h6v2H8v-2Z"/></svg>
                    </div>
                    <div class="cdoc-document-copy">
                        <strong><?= e($documentName) ?></strong>
                        <span><?= e($documentExt) ?> - <?= e($documentSizeLabel) ?></span>
                    </div>
                </div>

                <div class="cdoc-card-body">
                    <?php if ($contact): ?>
                    <div class="cdoc-info-row">
                        <span class="cdoc-info-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg></span>
                        <div><span>Contact Person</span><strong><?= e($contact) ?></strong></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($email): ?>
                    <div class="cdoc-info-row">
                        <span class="cdoc-info-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4-8 5-8-5V6l8 5 8-5v2Z"/></svg></span>
                        <div><span>Email Address</span><strong><?= e($email) ?></strong></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($phone): ?>
                    <div class="cdoc-info-row">
                        <span class="cdoc-info-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.02-.24 11.36 11.36 0 0 0 3.57.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.36 11.36 0 0 0 .57 3.57 1 1 0 0 1-.24 1.02l-2.21 2.2Z"/></svg></span>
                        <div><span>Phone Number</span><strong><?= e($phone) ?></strong></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($address): ?>
                    <div class="cdoc-info-row">
                        <span class="cdoc-info-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8Zm0 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg></span>
                        <div><span>Address</span><strong><?= e($address) ?></strong></div>
                    </div>
                    <?php endif; ?>
                </div>

                <footer class="cdoc-card-footer">
                    <div class="cdoc-programs">
                        <div class="cdoc-programs-head">
                            <span><?= (int)$programCount ?></span>
                            Program<?= $programCount === 1 ? '' : 's' ?> Accepted
                        </div>
                        <div class="cdoc-footer-tags">
                            <?php foreach (array_slice($programCodes, 0, 4) as $code): ?>
                                <span class="cdoc-tag"><?= e($code) ?></span>
                            <?php endforeach; ?>
                            <?php if ($programCount > 4): ?>
                                <span class="cdoc-tag cdoc-tag--more">+<?= $programCount - 4 ?> more</span>
                            <?php endif; ?>
                            <?php if ($programCount === 0): ?>
                                <span class="cdoc-tag cdoc-tag--empty">No programs assigned</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a class="cdoc-open-btn" href="index.php?r=coordinator_partner_document&amp;company_id=<?= (int)$company['id'] ?>" target="_blank" rel="noopener noreferrer">
                        Open Document
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3h7v7h-2V6.41l-9.3 9.3-1.4-1.42 9.29-9.29H14V3ZM5 5h6v2H5v12h12v-6h2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/></svg>
                    </a>
                </footer>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="cdoc-no-results" data-cdoc-no-results hidden>
            <div class="cdoc-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M10 4a6 6 0 0 1 4.74 9.67l4.3 4.29-1.42 1.42-4.29-4.3A6 6 0 1 1 10 4Zm0 2a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/></svg></div>
            <p class="cdoc-empty-title">No matching agreements</p>
            <p class="cdoc-empty-sub">Try another keyword or switch the status filter back to all documents.</p>
        </div>

        <?php else: ?>
        <div class="cdoc-empty">
            <div class="cdoc-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM8 13h8v2H8v-2Zm0 4h6v2H8v-2Z"/></svg>
            </div>
            <p class="cdoc-empty-title">No documents uploaded yet</p>
            <p class="cdoc-empty-sub">Host Training Establishment MOA/MOU agreements will appear here once they have been uploaded by an administrator.</p>
        </div>
        <?php endif; ?>
    </section>
</div>
