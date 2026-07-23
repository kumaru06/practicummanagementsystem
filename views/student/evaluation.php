<?php
$criteria = Evaluation::criteria();
$ratings = Evaluation::decodeRatings($evaluation['criteria_ratings'] ?? null);
$hasEvaluation = !empty($evaluation) && (!empty($evaluation['criteria_ratings']) || !empty($evaluation['final_grade']));
$grade = (float)($evaluation['final_grade'] ?? 0);
$companyName = $enrollment['company_name'] ?? null;
$submittedAt = !empty($evaluation['submitted_at']) ? date('F j, Y', strtotime($evaluation['submitted_at'])) : null;
$comments = trim((string)($evaluation['comments'] ?? ''));
$certificate = $evaluation['certificate_file'] ?? null;

$gradeLabel = match (true) {
    $grade >= 90 => 'Outstanding',
    $grade >= 85 => 'Excellent',
    $grade >= 80 => 'Very Satisfactory',
    $grade >= 75 => 'Satisfactory',
    $grade > 0 => 'Needs Improvement',
    default => 'Pending',
};

$ringR = 54;
$ringC = 2 * M_PI * $ringR;
$ringOffset = $ringC * (1 - max(0, min(100, $grade)) / 100);

$ratedCount = 0;
$starSum = 0;
$sectionScores = [];
foreach ($criteria as $sectionName => $items) {
    $sectionWeight = 0.0;
    $sectionScore = 0.0;
    foreach ($items as $key => $def) {
        $r = (int)($ratings[$key] ?? 0);
        if ($r > 0) {
            $ratedCount++;
            $starSum += $r;
        }
        $w = (float)$def['weight'];
        $sectionWeight += $w;
        $sectionScore += ($r / 5) * $w;
    }
    $sectionScores[$sectionName] = $sectionWeight > 0 ? round(($sectionScore / $sectionWeight) * 100, 1) : 0;
}
$avgStars = $ratedCount > 0 ? round($starSum / $ratedCount, 1) : 0;

$starSvg = static function (int $filled): string {
    $filled = max(0, min(5, $filled));
    $html = '<span class="ev2-stars" aria-label="' . $filled . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        $on = $i <= $filled ? ' is-on' : '';
        $html .= '<svg class="ev2-star' . $on . '" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.77l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94L12 2.5z"/></svg>';
    }
    return $html . '</span>';
};
?>
<div class="ev2<?= $hasEvaluation ? ' ev2--ready' : ' ev2--empty' ?>">
    <?php if (!$hasEvaluation): ?>
        <section class="ev2-empty card">
            <div class="ev2-empty-visual" aria-hidden="true">
                <div class="ev2-empty-ring"></div>
                <svg viewBox="0 0 24 24" width="36" height="36" fill="currentColor">
                    <path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17Z"/>
                </svg>
            </div>
            <h2>No Evaluation Yet</h2>
            <p>Your Host Training Establishment has not submitted your final evaluation yet. It will appear here once completed.</p>
        </section>
    <?php else: ?>
        <section class="ev2-hero">
            <div class="ev2-hero-glow ev2-hero-glow--a" aria-hidden="true"></div>
            <div class="ev2-hero-glow ev2-hero-glow--b" aria-hidden="true"></div>
            <div class="ev2-hero-pattern" aria-hidden="true"></div>

            <div class="ev2-hero-copy">
                <span class="ev2-kicker">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 3 7.5 12 12l7.5-3.75V16.5H21V7.5L12 3Z"/><path d="M6 11.25v4.5c1.8 2.4 10.2 2.4 12 0v-4.5"/></svg>
                    Final OJT Evaluation
                </span>
                <h2>Your Performance Rating</h2>
                <p class="ev2-hero-sub">
                    Submitted by your Host Training Establishment<?= $submittedAt ? ' on <strong>' . e($submittedAt) . '</strong>' : '' ?>.
                    <?php if ($companyName): ?>
                        <span class="ev2-hero-company"><?= e($companyName) ?></span>
                    <?php endif; ?>
                </p>
            </div>

            <aside class="ev2-score" aria-label="Final grade <?= e(number_format($grade, 2)) ?> percent">
                <div class="ev2-score-ring">
                    <svg viewBox="0 0 128 128" aria-hidden="true">
                        <circle class="ev2-ring-track" cx="64" cy="64" r="<?= $ringR ?>"></circle>
                        <circle class="ev2-ring-value" cx="64" cy="64" r="<?= $ringR ?>"
                            style="stroke-dasharray: <?= $ringC ?>; stroke-dashoffset: <?= $ringOffset ?>"></circle>
                    </svg>
                    <div class="ev2-score-center">
                        <span class="ev2-score-value"><?= e(number_format($grade, 1)) ?></span>
                        <span class="ev2-score-unit">%</span>
                        <span class="ev2-score-caption">Final Grade</span>
                    </div>
                </div>
            </aside>

            <div class="ev2-meta">
                <div class="ev2-meta-chip">
                    <span class="ev2-meta-label">Standing</span>
                    <strong><?= e($gradeLabel) ?></strong>
                </div>
                <div class="ev2-meta-chip">
                    <span class="ev2-meta-label">Avg. Rating</span>
                    <strong><?= e(number_format($avgStars, 1)) ?> / 5</strong>
                </div>
                <div class="ev2-meta-chip">
                    <span class="ev2-meta-label">Criteria</span>
                    <strong><?= (int)$ratedCount ?> rated</strong>
                </div>
            </div>
        </section>

        <div class="ev2-section-summary">
            <?php foreach ($sectionScores as $sectionName => $pct): ?>
                <div class="ev2-summary-card">
                    <div class="ev2-summary-top">
                        <span class="ev2-summary-title"><?= e(preg_replace('/^[A-Z]\.\s*/', '', $sectionName)) ?></span>
                        <strong><?= e(number_format($pct, 1)) ?>%</strong>
                    </div>
                    <div class="ev2-summary-bar" aria-hidden="true">
                        <span style="width: <?= max(0, min(100, $pct)) ?>%"></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php $rowIndex = 0; foreach ($criteria as $sectionName => $items): ?>
            <?php
            $letter = substr($sectionName, 0, 1);
            $sectionTitle = preg_replace('/^[A-Z]\.\s*/', '', $sectionName);
            $sectionPct = $sectionScores[$sectionName] ?? 0;
            ?>
            <section class="ev2-panel">
                <header class="ev2-panel-head">
                    <div class="ev2-panel-badge" aria-hidden="true"><?= e($letter) ?></div>
                    <div class="ev2-panel-titles">
                        <h3><?= e($sectionTitle) ?></h3>
                        <p><?= count($items) ?> criteria · section score <?= e(number_format($sectionPct, 1)) ?>%</p>
                    </div>
                    <div class="ev2-panel-cols" aria-hidden="true">
                        <span>Weight</span>
                        <span>Rating</span>
                    </div>
                </header>

                <div class="ev2-rows">
                    <?php foreach ($items as $key => $def):
                        $rowIndex++;
                        $r = (int)($ratings[$key] ?? 0);
                        $fill = $r > 0 ? ($r / 5) * 100 : 0;
                    ?>
                        <article class="ev2-row">
                            <span class="ev2-row-num"><?= str_pad((string)$rowIndex, 2, '0', STR_PAD_LEFT) ?></span>
                            <div class="ev2-row-main">
                                <div class="ev2-row-label-row">
                                    <p class="ev2-row-label"><?= e($def['label']) ?></p>
                                    <span class="ev2-row-weight-pill"><?= (int)$def['weight'] ?>%</span>
                                </div>
                                <div class="ev2-row-track" aria-hidden="true"><span style="width: <?= $fill ?>%"></span></div>
                            </div>
                            <span class="ev2-row-weight"><?= (int)$def['weight'] ?>%</span>
                            <div class="ev2-row-rating">
                                <?= $starSvg($r) ?>
                                <span class="ev2-row-score"><?= $r ?: '—' ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <section class="ev2-footer-grid">
            <div class="ev2-total">
                <div>
                    <span class="ev2-kicker ev2-kicker--dark">Overall</span>
                    <h3>Total Rating</h3>
                    <p>Weighted across all evaluation criteria</p>
                </div>
                <div class="ev2-total-value">
                    <strong><?= e(number_format($grade, 2)) ?>%</strong>
                    <span>out of 100%</span>
                </div>
            </div>

            <div class="ev2-comments">
                <span class="ev2-kicker ev2-kicker--dark">Host Feedback</span>
                <h3>Comments from HTE</h3>
                <?php if ($comments !== ''): ?>
                    <blockquote><?= nl2br(e($comments)) ?></blockquote>
                <?php else: ?>
                    <p class="ev2-muted">No comments were provided.</p>
                <?php endif; ?>
            </div>

            <?php if (!empty($certificate)): ?>
                <div class="ev2-cert">
                    <div class="ev2-cert-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/>
                            <path d="M14 3v5h5M9 13h6M9 17h4"/>
                        </svg>
                    </div>
                    <div>
                        <h3>Certificate of Completion</h3>
                        <p>Download the official certificate issued with your evaluation.</p>
                    </div>
                    <a class="btn btn-primary ev2-cert-btn" target="_blank" href="<?= e(asset($certificate)) ?>">View / Download</a>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<style>
.ev2 {
    --ev2-ink: #172033;
    --ev2-muted: #667085;
    --ev2-line: rgba(219, 228, 240, 0.95);
    --ev2-brand: #8B1A1A;
    --ev2-brand-deep: #5a1010;
    --ev2-brand-soft: #fff1f2;
    --ev2-gold: #c9a227;
    max-width: 1080px;
    margin: 0 auto;
    display: grid;
    gap: 18px;
}

.ev2-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255, 244, 244, 0.82);
}
.ev2-kicker svg { width: 16px; height: 16px; }
.ev2-kicker--dark { color: #9f1239; }

.ev2-empty {
    text-align: center;
    padding: 64px 28px;
    border: 1px solid var(--ev2-line);
    box-shadow: var(--shadow-soft);
}
.ev2-empty-visual {
    position: relative;
    width: 88px;
    height: 88px;
    margin: 0 auto 18px;
    display: grid;
    place-items: center;
    color: var(--ev2-brand);
    background: linear-gradient(145deg, #fff, #fff1f2);
    border: 1px solid rgba(139, 26, 26, 0.12);
    border-radius: 5px;
}
.ev2-empty-ring {
    position: absolute;
    inset: -8px;
    border-radius: 5px;
    border: 1px dashed rgba(139, 26, 26, 0.22);
    animation: ev2-spin 18s linear infinite;
}
.ev2-empty h2 {
    margin: 0 0 8px;
    font-size: 1.45rem;
    letter-spacing: -0.03em;
    color: var(--ev2-ink);
}
.ev2-empty p {
    margin: 0 auto;
    max-width: 420px;
    color: var(--ev2-muted);
    line-height: 1.6;
}

.ev2-hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    grid-template-areas:
        "copy score"
        "meta score";
    gap: 18px 28px;
    align-items: center;
    padding: 28px 30px;
    border-radius: 5px;
    color: #fff;
    background:
        radial-gradient(120% 140% at 0% 0%, rgba(211, 178, 82, 0.18), transparent 42%),
        linear-gradient(135deg, #5a1010 0%, #8B1A1A 48%, #254180 120%);
    box-shadow: 0 24px 50px rgba(90, 16, 16, 0.28);
    animation: ev2-rise 0.55s ease both;
}
.ev2-hero-copy { grid-area: copy; }
.ev2-score { grid-area: score; align-self: center; }
.ev2-meta { grid-area: meta; }
.ev2-hero-glow {
    position: absolute;
    border-radius: 5px;
    filter: blur(40px);
    pointer-events: none;
}
.ev2-hero-glow--a {
    width: 220px; height: 220px;
    top: -80px; right: 18%;
    background: rgba(211, 178, 82, 0.28);
}
.ev2-hero-glow--b {
    width: 260px; height: 260px;
    bottom: -120px; left: -40px;
    background: rgba(37, 65, 128, 0.35);
}
.ev2-hero-pattern {
    position: absolute;
    inset: 0;
    opacity: 0.18;
    background-image:
        linear-gradient(rgba(255,255,255,0.08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.08) 1px, transparent 1px);
    background-size: 28px 28px;
    mask-image: linear-gradient(180deg, #000, transparent 90%);
    pointer-events: none;
}
.ev2-hero-copy,
.ev2-score,
.ev2-meta { position: relative; z-index: 1; }
.ev2-hero-copy h2 {
    margin: 10px 0 8px;
    font-size: clamp(1.55rem, 2.4vw, 2rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    line-height: 1.15;
}
.ev2-hero-sub {
    margin: 0;
    max-width: 520px;
    color: rgba(255, 255, 255, 0.78);
    line-height: 1.55;
}
.ev2-hero-sub strong { color: #fff; font-weight: 700; }
.ev2-hero-company {
    display: inline-block;
    margin-top: 6px;
    padding: 4px 10px;
    border-radius: 5px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.16);
    font-size: 0.82rem;
    font-weight: 600;
    color: #fff;
}

.ev2-meta {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}
.ev2-meta-chip {
    min-width: 0;
    padding: 10px 14px;
    border-radius: 5px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.14);
    backdrop-filter: blur(8px);
}
.ev2-meta-label {
    display: block;
    margin-bottom: 2px;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.62);
}
.ev2-meta-chip strong {
    font-size: 0.95rem;
    font-weight: 750;
    color: #fff;
}

.ev2-score-ring {
    position: relative;
    width: 148px;
    height: 148px;
}
.ev2-score-ring svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}
.ev2-ring-track {
    fill: none;
    stroke: rgba(255,255,255,0.14);
    stroke-width: 10;
}
.ev2-ring-value {
    fill: none;
    stroke: url(#none);
    stroke: #d3b252;
    stroke-width: 10;
    stroke-linecap: round;
    transition: stroke-dashoffset 1s cubic-bezier(.22,1,.36,1);
    animation: ev2-ring 1.1s cubic-bezier(.22,1,.36,1) both;
}
.ev2-score-center {
    position: absolute;
    inset: 0;
    display: grid;
    place-content: center;
    text-align: center;
}
.ev2-score-value {
    font-size: 2.05rem;
    font-weight: 800;
    letter-spacing: -0.05em;
    line-height: 1;
}
.ev2-score-unit {
    font-size: 0.85rem;
    font-weight: 700;
    color: rgba(255,255,255,0.75);
}
.ev2-score-caption {
    margin-top: 4px;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.58);
}

.ev2-section-summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    animation: ev2-rise 0.55s ease 0.08s both;
}
.ev2-summary-card {
    padding: 16px 18px;
    border-radius: 5px;
    background: #fff;
    border: 1px solid var(--ev2-line);
    box-shadow: var(--shadow-soft);
}
.ev2-summary-top {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}
.ev2-summary-title {
    font-size: 0.92rem;
    font-weight: 750;
    color: var(--ev2-ink);
}
.ev2-summary-top strong {
    color: var(--ev2-brand);
    font-size: 1.05rem;
    letter-spacing: -0.03em;
}
.ev2-summary-bar {
    height: 7px;
    border-radius: 5px;
    background: #f1f4f9;
    overflow: hidden;
}
.ev2-summary-bar span {
    display: block;
    height: 100%;
    border-radius: 5px;
    background: linear-gradient(90deg, #8B1A1A, #d3b252);
    animation: ev2-fill 0.9s ease both;
}

.ev2-panel {
    background: #fff;
    border: 1px solid var(--ev2-line);
    border-radius: 5px;
    box-shadow: var(--shadow-soft);
    overflow: hidden;
    animation: ev2-rise 0.55s ease 0.14s both;
}
.ev2-panel-head {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 14px;
    align-items: center;
    padding: 16px 18px;
    background:
        linear-gradient(180deg, #fffafa, #fff),
        #fff;
    border-bottom: 1px solid var(--ev2-line);
}
.ev2-panel-badge {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 5px;
    font-weight: 800;
    color: #fff;
    background: linear-gradient(145deg, #8B1A1A, #5a1010);
    box-shadow: 0 10px 20px rgba(139, 26, 26, 0.22);
}
.ev2-panel-titles h3 {
    margin: 0;
    font-size: 1.05rem;
    letter-spacing: -0.03em;
    color: var(--ev2-ink);
}
.ev2-panel-titles p {
    margin: 3px 0 0;
    font-size: 0.82rem;
    color: var(--ev2-muted);
}
.ev2-panel-cols {
    display: grid;
    grid-template-columns: 72px 150px;
    gap: 8px;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #9aa3b5;
    text-align: center;
}

.ev2-rows { padding: 4px 8px 10px; }
.ev2-row {
    display: grid;
    grid-template-columns: 40px minmax(0, 1fr) 72px 150px;
    gap: 8px;
    align-items: center;
    padding: 14px 10px;
    border-radius: 5px;
    transition: background 0.18s ease;
}
.ev2-row:hover { background: #fafbfd; }
.ev2-row + .ev2-row { border-top: 1px solid #f0f3f8; }
.ev2-row-num {
    font-size: 0.78rem;
    font-weight: 750;
    color: #98a2b3;
    font-variant-numeric: tabular-nums;
}
.ev2-row-label-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 8px;
}
.ev2-row-label {
    margin: 0;
    flex: 1;
    min-width: 0;
    font-size: 0.92rem;
    color: var(--ev2-ink);
    line-height: 1.45;
}
.ev2-row-weight-pill {
    display: none;
    flex-shrink: 0;
    margin-top: 1px;
    padding: 3px 8px;
    border-radius: 5px;
    background: #fff1f2;
    color: var(--ev2-brand);
    font-size: 0.72rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
}
.ev2-row-track {
    height: 5px;
    border-radius: 5px;
    background: #eef2f7;
    overflow: hidden;
}
.ev2-row-track span {
    display: block;
    height: 100%;
    border-radius: 5px;
    background: linear-gradient(90deg, rgba(139,26,26,0.85), rgba(211,178,82,0.95));
}
.ev2-row-weight {
    text-align: center;
    font-weight: 750;
    color: #475467;
    font-variant-numeric: tabular-nums;
}
.ev2-row-rating {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.ev2-stars { display: inline-flex; gap: 2px; }
.ev2-star {
    width: 14px;
    height: 14px;
    fill: #e4e7ec;
}
.ev2-star.is-on { fill: #d3b252; }
.ev2-row-score {
    min-width: 18px;
    font-weight: 800;
    color: var(--ev2-brand-deep);
    font-variant-numeric: tabular-nums;
}

.ev2-footer-grid {
    display: grid;
    grid-template-columns: 0.9fr 1.1fr;
    gap: 14px;
    animation: ev2-rise 0.55s ease 0.2s both;
}
.ev2-total,
.ev2-comments,
.ev2-cert {
    padding: 22px 22px;
    border-radius: 5px;
    background: #fff;
    border: 1px solid var(--ev2-line);
    box-shadow: var(--shadow-soft);
}
.ev2-total {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 18px;
    background:
        linear-gradient(160deg, #fff 0%, #fff8f8 100%);
}
.ev2-total h3,
.ev2-comments h3,
.ev2-cert h3 {
    margin: 8px 0 4px;
    font-size: 1.15rem;
    letter-spacing: -0.03em;
    color: var(--ev2-ink);
}
.ev2-total p,
.ev2-comments p,
.ev2-cert p {
    margin: 0;
    color: var(--ev2-muted);
    line-height: 1.55;
}
.ev2-total-value strong {
    display: block;
    font-size: 2.2rem;
    font-weight: 800;
    letter-spacing: -0.05em;
    color: var(--ev2-brand);
    line-height: 1;
}
.ev2-total-value span {
    font-size: 0.82rem;
    color: var(--ev2-muted);
    font-weight: 600;
}
.ev2-comments blockquote {
    margin: 14px 0 0;
    padding: 14px 16px;
    border-left: 3px solid #d3b252;
    border-radius: 5px;
    background: #fafbfd;
    color: #344054;
    line-height: 1.65;
    font-size: 0.95rem;
}
.ev2-muted { color: var(--ev2-muted); margin-top: 12px !important; }

.ev2-cert {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 16px;
    align-items: center;
}
.ev2-cert-icon {
    width: 52px;
    height: 52px;
    display: grid;
    place-items: center;
    border-radius: 5px;
    color: var(--ev2-brand);
    background: linear-gradient(145deg, #fee2e2, #fff1f2);
}
.ev2-cert-icon svg { width: 24px; height: 24px; }
.ev2-cert-btn { white-space: nowrap; }

@keyframes ev2-rise {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes ev2-fill {
    from { width: 0 !important; }
}
@keyframes ev2-ring {
    from { stroke-dashoffset: <?= $ringC ?>; }
}
@keyframes ev2-spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 980px) {
    .ev2-hero {
        grid-template-columns: 1fr;
        grid-template-areas:
            "copy"
            "score"
            "meta";
        justify-items: stretch;
        gap: 16px;
        padding: 22px 20px 20px;
        text-align: center;
    }
    .ev2-hero-copy { text-align: center; }
    .ev2-hero-sub { margin-left: auto; margin-right: auto; }
    .ev2-hero-company { margin-top: 8px; }
    .ev2-kicker { justify-content: center; }
    .ev2-score { justify-self: center; }
    .ev2-meta {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        width: 100%;
    }
    .ev2-meta-chip { text-align: left; }
    .ev2-footer-grid { grid-template-columns: 1fr; }
    .ev2-panel-cols { display: none; }
    .ev2-row {
        grid-template-columns: 32px minmax(0, 1fr);
        grid-template-areas:
            "num main"
            ". rating";
        gap: 8px 10px;
        padding: 14px 8px;
    }
    .ev2-row-num { grid-area: num; align-self: start; padding-top: 2px; }
    .ev2-row-main { grid-area: main; }
    .ev2-row-weight { display: none; }
    .ev2-row-weight-pill { display: inline-flex; }
    .ev2-row-rating {
        grid-area: rating;
        justify-content: flex-start;
    }
    .ev2-cert {
        grid-template-columns: auto minmax(0, 1fr);
    }
    .ev2-cert-btn {
        grid-column: 1 / -1;
        justify-self: stretch;
        text-align: center;
    }
}

@media (max-width: 640px) {
    .ev2 {
        gap: 14px;
        padding-bottom: 8px;
    }
    .ev2-hero {
        padding: 18px 14px 16px;
        border-radius: 5px;
        gap: 14px;
    }
    .ev2-hero-copy h2 {
        font-size: 1.35rem;
        margin: 8px 0 6px;
    }
    .ev2-hero-sub {
        font-size: 0.88rem;
        line-height: 1.5;
    }
    .ev2-score-ring {
        width: 132px;
        height: 132px;
    }
    .ev2-score-value { font-size: 1.85rem; }
    .ev2-meta {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    .ev2-meta-chip {
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        gap: 8px;
        padding: 11px 14px;
    }
    .ev2-meta-label {
        margin: 0;
        font-size: 0.7rem;
    }
    .ev2-meta-chip strong {
        font-size: 0.92rem;
        text-align: right;
    }
    .ev2-section-summary {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .ev2-summary-card { padding: 14px 14px; }
    .ev2-panel { border-radius: 5px; }
    .ev2-panel-head {
        grid-template-columns: auto minmax(0, 1fr);
        padding: 14px;
        gap: 12px;
    }
    .ev2-panel-badge {
        width: 38px;
        height: 38px;
        border-radius: 5px;
    }
    .ev2-panel-titles h3 { font-size: 0.98rem; }
    .ev2-panel-titles p { font-size: 0.78rem; }
    .ev2-rows { padding: 2px 6px 8px; }
    .ev2-row-label { font-size: 0.88rem; }
    .ev2-star { width: 13px; height: 13px; }
    .ev2-total,
    .ev2-comments,
    .ev2-cert {
        padding: 18px 16px;
        border-radius: 5px;
    }
    .ev2-total-value strong { font-size: 1.85rem; }
    .ev2-comments blockquote {
        font-size: 0.9rem;
        padding: 12px 14px;
    }
    .ev2-empty { padding: 48px 20px; }
}

@media (max-width: 380px) {
    .ev2-hero-copy h2 { font-size: 1.22rem; }
    .ev2-score-ring {
        width: 118px;
        height: 118px;
    }
    .ev2-score-value { font-size: 1.65rem; }
    .ev2-row {
        grid-template-columns: minmax(0, 1fr);
        grid-template-areas:
            "main"
            "rating";
        gap: 8px;
    }
    .ev2-row-num { display: none; }
}
</style>
