<div class="grid cards">
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="M12 3 2 8l10 5 10-5-10-5Zm-6 9v4c2 3 10 3 12 0v-4l-6 3-6-3Z"/></svg><div><strong><?= (int)$stats['students'] ?></strong><span>Total Students</span></div></div>
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="m9 16.2-3.5-3.5L4 14.2 9 19l11-11-1.5-1.5L9 16.2Z"/></svg><div><strong><?= (int)$stats['enrolled'] ?></strong><span>Active OJT</span></div></div>
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1 14-4-4 1.4-1.4 2.6 2.6 5.6-5.6L18 9l-7 7Z"/></svg><div><strong><?= (int)$stats['completed'] ?></strong><span>Completed OJT</span></div></div>
    <div class="card metric"><svg viewBox="0 0 24 24"><path d="M12 2a5 5 0 1 0 0 10A5 5 0 0 0 12 2Zm0 12c-5.33 0-8 2.67-8 4v2h16v-2c0-1.33-2.67-4-8-4Z"/></svg><div><strong><?= (int)$stats['pending'] ?></strong><span>Pending</span></div></div>
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


