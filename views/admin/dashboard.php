<div class="grid cards">
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8c.8-4 3.8-6 8-6s7.2 2 8 6H4Z"/></svg><div><strong><?= (int)$stats['coordinators'] ?></strong><span>Coordinators</span></div></div>
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="M3 21V7l6-4 6 4v14H3Zm14 0V9h4v12h-4Z"/></svg><div><strong><?= (int)$stats['companies'] ?></strong><span>Partner Companies</span></div></div>
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="M12 3 2 8l10 5 10-5-10-5Zm-6 9v4c2 3 10 3 12 0v-4l-6 3-6-3Z"/></svg><div><strong><?= (int)$stats['students'] ?></strong><span>Students</span></div></div>
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="m9 16.2-3.5-3.5L4 14.2 9 19l11-11-1.5-1.5L9 16.2Z"/></svg><div><strong><?= (int)$stats['active'] ?></strong><span>Active OJT</span></div></div>
</div>

<div class="grid chart-grid">
    <section class="card chart-card">
        <div class="chart-header">
            <h2 class="chart-title">Monthly Enrollment Trends</h2>
        </div>
        <canvas id="monthlyChart"></canvas>
    </section>
    <section class="card chart-card">
        <div class="chart-header" style="justify-content:center">
            <h2 class="chart-title" style="text-align:center">OJT Status Distribution</h2>
        </div>
        <canvas id="statusChart"></canvas>
    </section>
</div>
<section class="card chart-card">
    <div class="chart-header">
        <h2 class="chart-title">Completion Rate by Course</h2>
    </div>
    <canvas id="courseChart"></canvas>
</section>
<script>window.dashboardCharts = <?= json_encode($charts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;</script>

