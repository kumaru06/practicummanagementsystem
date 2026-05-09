<h2>OJT Orientation Notice</h2>
<p>Hello <?= e($student['student_name'] ?? $student['name'] ?? 'Student') ?>,</p>
<p>Your Industry Partner has sent OJT orientation information.</p>
<ul>
    <li><strong>Company:</strong> <?= e($company['name'] ?? '') ?></li>
    <?php if (!empty($orientationDateTime)): ?><li><strong>Orientation Date/Time:</strong> <?= e($orientationDateTime) ?></li><?php endif; ?>
    <li><strong>Notes:</strong> <?= e($notes ?? '') ?></li>
</ul>
<p>The OJT Coordinator has also been notified.</p>
