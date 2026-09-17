<?php
$studentProfileModalShowFinal = $studentProfileModalShowFinal ?? false;
$chipIdIcon = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.75"/><circle cx="8.5" cy="12" r="1.75" fill="currentColor"/><path d="M13 10h5M13 14h3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';
$chipYearIcon = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 3 8.5 12 14l9-5.5L12 3Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M6.5 11.2v4.2c0 .4.22.77.58 1.02C8.3 17.3 10.05 18 12 18s3.7-.7 4.92-1.58c.36-.25.58-.62.58-1.02v-4.2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';
$tabOverviewIcon = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.75"/></svg>';
$tabDocsIcon = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M14 3v5h5" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg>';
$tabEvalIcon = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="1.75"/><rect x="9" y="3" width="6" height="4" rx="1" stroke="currentColor" stroke-width="1.75"/><path d="m9 13 2 2 4-4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$tabAccountIcon = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.75"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" stroke="currentColor" stroke-width="1.4"/></svg>';
$iconBuilding = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 21V6a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v15" stroke="currentColor" stroke-width="1.75"/><path d="M15 10h3a2 2 0 0 1 2 2v9" stroke="currentColor" stroke-width="1.75"/><path d="M8 8h2M8 12h2M8 16h2M16 21v-4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';
$iconCap = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 3 8.5 12 14l9-5.5L12 3Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M6.5 11.2v4.2c0 .4.22.77.58 1.02C8.3 17.3 10.05 18 12 18s3.7-.7 4.92-1.58c.36-.25.58-.62.58-1.02v-4.2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';
$iconNotes = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z" stroke="currentColor" stroke-width="1.75"/><path d="M14 3v5h5M9 13h6M9 17h4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';
$iconChart = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19V5M4 19h16" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><path d="M8 16v-5M12 16V8M16 16v-8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';
$iconCal = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="M8 3v4M16 3v4M3 10h18" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';
$iconPhone = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$iconPin = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z" stroke="currentColor" stroke-width="1.75"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.75"/></svg>';
$iconUser = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.75"/></svg>';
?>
<div class="modal" id="studentModal">
    <div class="modal-card student-panel-modal student-record-modal">
        <button class="modal-close student-panel-close" id="studentModalClose" type="button" aria-label="Close profile"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>

        <div class="student-panel-hero">
            <div class="student-panel-hero-content">
                <span class="student-panel-avatar" id="sm-avatar-wrap">
                    <img id="sm-photo" class="is-hidden" alt="">
                    <span id="sm-initial" class="student-panel-avatar-fallback is-hidden"></span>
                </span>
                <div class="student-panel-hero-copy">
                    <span class="student-panel-kicker">Student Profile</span>
                    <h2 id="sm-name" class="student-panel-name"></h2>
                    <p id="sm-email" class="student-panel-email"></p>
                    <div class="student-panel-chips">
                        <span class="student-panel-chip"><?= $chipIdIcon ?><span id="sm-chip-id"></span></span>
                        <span class="student-panel-chip"><?= $chipYearIcon ?><span id="sm-chip-year"></span></span>
                        <span class="student-panel-chip student-panel-chip-status" id="sm-chip-status"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="sp-tabs" role="tablist" aria-label="Student profile sections">
            <button class="sp-tab is-active" type="button" role="tab" aria-selected="true" data-sp-tab="overview"><?= $tabOverviewIcon ?><span>Overview</span></button>
            <button class="sp-tab" type="button" role="tab" aria-selected="false" data-sp-tab="documents"><?= $tabDocsIcon ?><span>Documents</span></button>
            <button class="sp-tab" type="button" role="tab" aria-selected="false" data-sp-tab="evaluations"><?= $tabEvalIcon ?><span>Evaluations</span></button>
            <button class="sp-tab" type="button" role="tab" aria-selected="false" data-sp-tab="account"><?= $tabAccountIcon ?><span>Account</span></button>
        </div>

        <div class="student-panel-body">
            <div class="sp-panel is-active" data-sp-panel="overview">
                <div class="sp-overview">
                    <article class="sp-card">
                        <header class="sp-card-head">
                            <span class="sp-card-icon"><?= $iconBuilding ?></span>
                            <div>
                                <h3>Deployment details</h3>
                                <p>Host Training Establishment</p>
                            </div>
                        </header>
                        <strong class="sp-card-company" id="sm-company"></strong>
                        <div class="sp-card-dates">
                            <div>
                                <span class="sp-mini-icon"><?= $iconCal ?></span>
                                <div>
                                    <span class="sm-label">Official OJT start</span>
                                    <strong id="sm-official-start"></strong>
                                </div>
                            </div>
                            <div>
                                <span class="sp-mini-icon"><?= $iconCal ?></span>
                                <div>
                                    <span class="sm-label">Projected end</span>
                                    <strong id="sm-projected-end"></strong>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="sp-card">
                        <header class="sp-card-head">
                            <span class="sp-card-icon"><?= $iconCap ?></span>
                            <div>
                                <h3>Orientation</h3>
                            </div>
                            <span class="sp-orient-badge" id="sm-orientation-badge">—</span>
                        </header>
                        <ol class="sp-timeline">
                            <li class="sp-timeline-item" id="sm-orient-step">
                                <span class="sp-timeline-dot"></span>
                                <div>
                                    <strong id="sm-orient-title">Orientation</strong>
                                    <span id="sm-orientation-datetime">—</span>
                                </div>
                            </li>
                            <li class="sp-timeline-item" id="sm-ojt-step">
                                <span class="sp-timeline-dot"></span>
                                <div>
                                    <strong>OJT start</strong>
                                    <span id="sm-ojt-start-copy">—</span>
                                </div>
                            </li>
                        </ol>
                        <div class="sp-orient-notes">
                            <span class="sm-label">Orientation notes</span>
                            <div class="student-panel-notes-box" id="sm-orientation-notes"></div>
                        </div>
                    </article>

                    <article class="sp-card">
                        <header class="sp-card-head">
                            <span class="sp-card-icon"><?= $tabDocsIcon ?></span>
                            <div>
                                <h3>Pre-deployment</h3>
                            </div>
                        </header>
                        <div class="sp-predeploy-body">
                            <span class="ms-predeploy-badge ms-predeploy-badge--not_submitted" id="sm-predeploy-status">Not Submitted</span>
                            <button type="button" class="btn btn-small btn-ghost is-hidden" id="sm-predeploy-review">Review documents</button>
                        </div>
                    </article>

                    <article class="sp-card">
                        <header class="sp-card-head">
                            <span class="sp-card-icon"><?= $iconChart ?></span>
                            <div>
                                <h3>Final &amp; evaluations</h3>
                            </div>
                            <span class="sp-eval-count" id="sm-ov-eval-count">0/2 evals</span>
                        </header>
                        <ul class="sp-eval-list">
                            <li>
                                <span>OJT Performance Evaluation (Faculty)</span>
                                <strong class="sp-eval-pill" id="sm-ov-eval-faculty">Not Submitted</strong>
                            </li>
                            <li>
                                <span>OJT Performance Evaluation (Employer)</span>
                                <strong class="sp-eval-pill" id="sm-ov-eval-employer">Not Submitted</strong>
                            </li>
                        </ul>
                    </article>
                </div>
            </div>

            <div class="sp-panel" data-sp-panel="documents" hidden>
                <div class="sp-documents-stack" id="sm-documents-lists">
                    <div class="sp-card sp-card--list">
                        <header class="sp-card-head">
                            <span class="sp-card-icon"><?= $tabDocsIcon ?></span>
                            <div>
                                <h3>Pre-deployment documents</h3>
                                <p>1st to Comply files submitted for this student</p>
                            </div>
                        </header>
                        <ul class="sp-doc-list" id="sm-documents-list"></ul>
                    </div>
                    <div class="sp-card sp-card--list" id="sm-documents-stage2-card">
                        <header class="sp-card-head">
                            <span class="sp-card-icon"><?= $tabDocsIcon ?></span>
                            <div>
                                <h3>2nd to Comply</h3>
                                <p>Endorsement letter and confidentiality agreement</p>
                            </div>
                        </header>
                        <ul class="sp-doc-list" id="sm-documents-stage2-list"></ul>
                    </div>
                    <div class="sp-card sp-card--list" id="sm-documents-stage3-card">
                        <header class="sp-card-head">
                            <span class="sp-card-icon"><?= $tabDocsIcon ?></span>
                            <div>
                                <h3>3rd to Comply</h3>
                                <p>Forms and evaluations submitted during OJT</p>
                            </div>
                        </header>
                        <ul class="sp-doc-list" id="sm-documents-stage3-list"></ul>
                    </div>
                </div>
                <div class="sp-card sp-doc-viewer is-hidden" id="sm-document-viewer" hidden>
                    <div class="sp-doc-viewer-bar">
                        <button type="button" class="btn btn-small btn-ghost" id="sm-document-viewer-back">Back to list</button>
                        <strong class="sp-doc-viewer-title" id="sm-document-viewer-title"></strong>
                        <a class="btn btn-small btn-ghost" id="sm-document-viewer-open" target="_blank" rel="noopener noreferrer" href="#">Open in new tab</a>
                    </div>
                    <div class="sp-doc-viewer-stage" id="sm-document-viewer-stage"></div>
                </div>
            </div>

            <div class="sp-panel" data-sp-panel="evaluations" hidden>
                <div class="sp-card sp-card--list">
                    <header class="sp-card-head">
                        <span class="sp-card-icon"><?= $iconChart ?></span>
                        <div>
                            <h3>Final evaluations</h3>
                        </div>
                        <span class="sp-eval-count" id="sm-eval-count">0 of 2 submitted</span>
                    </header>
                    <ul class="sp-eval-list sp-eval-list--roomy">
                        <li>
                            <span>OJT Performance Evaluation (Faculty)</span>
                            <strong class="sp-eval-pill" id="sm-eval-faculty">Not Submitted</strong>
                        </li>
                        <li>
                            <span>OJT Performance Evaluation (Employer)</span>
                            <strong class="sp-eval-pill" id="sm-eval-employer">Not Submitted</strong>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="sp-panel" data-sp-panel="account" hidden>
                <div class="sp-account-layout">
                    <article class="sp-card sp-card--account">
                        <header class="sp-card-head">
                            <span class="sp-card-icon"><?= $tabAccountIcon ?></span>
                            <div>
                                <h3>Student details</h3>
                            </div>
                        </header>
                        <dl class="sp-account-grid">
                            <div class="sp-account-item">
                                <span class="sp-account-icon" aria-hidden="true"><?= $iconCap ?></span>
                                <div class="sp-account-copy">
                                    <dt>Course</dt>
                                    <dd id="sm-course">—</dd>
                                </div>
                            </div>
                            <div class="sp-account-item">
                                <span class="sp-account-icon" aria-hidden="true"><?= $iconCal ?></span>
                                <div class="sp-account-copy">
                                    <dt>Birthdate</dt>
                                    <dd id="sm-birthdate">—</dd>
                                </div>
                            </div>
                            <div class="sp-account-item">
                                <span class="sp-account-icon" aria-hidden="true"><?= $chipYearIcon ?></span>
                                <div class="sp-account-copy">
                                    <dt>Year level</dt>
                                    <dd id="sm-year-level">—</dd>
                                </div>
                            </div>
                            <div class="sp-account-item">
                                <span class="sp-account-icon" aria-hidden="true"><?= $iconPhone ?></span>
                                <div class="sp-account-copy">
                                    <dt>Contact number</dt>
                                    <dd id="sm-contact-number">—</dd>
                                </div>
                            </div>
                            <div class="sp-account-item sp-account-wide">
                                <span class="sp-account-icon" aria-hidden="true"><?= $iconPin ?></span>
                                <div class="sp-account-copy">
                                    <dt>Home address</dt>
                                    <dd id="sm-address">—</dd>
                                </div>
                            </div>
                            <div class="sp-account-item sp-account-wide admin-only-profile-field is-hidden">
                                <span class="sp-account-icon" aria-hidden="true"><?= $iconUser ?></span>
                                <div class="sp-account-copy">
                                    <dt>Coordinator</dt>
                                    <dd id="sm-coordinator">—</dd>
                                </div>
                            </div>
                        </dl>
                    </article>
                </div>
            </div>
        </div>

        <?php if ($studentProfileModalShowFinal): ?>
        <div class="student-panel-footer">
            <div class="student-panel-doc-actions">
                <a id="sm-final-link" class="btn btn-small btn-primary sp-footer-primary" href="#"><?= $tabDocsIcon ?><span>Open final section</span></a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
