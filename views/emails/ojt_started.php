<h2>Official OJT Start Notice</h2>
<p>Hello <?= e($student['student_name'] ?? $student['name'] ?? 'Student') ?>,</p>
<p>Your orientation has been marked completed and your OJT has officially started.</p>
<ul>
    <li><strong>Official Start Date:</strong> <?= e($officialStartDate ?? '') ?></li>
    <li><strong>Projected End Date:</strong> <?= e($projectedEndDate ?? '') ?></li>
    <li><strong>Required Hours:</strong> <?= e((string)($requiredHours ?? '')) ?></li>
</ul>
<p>Please keep your daily time records updated until you complete all required hours.</p>
