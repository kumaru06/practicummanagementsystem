<section class="card"><div class="section-head section-head-split"><div><h2>Deployed Students</h2><p class="muted">Students currently assigned to your company.</p></div><input class="table-search table-search-wide" placeholder="Search students..."></div><div class="table-wrap"><table class="data-table"><thead><tr><th data-sort>Name</th><th data-sort>Student No.</th><th>Course/Year</th><th>Schedule</th><th>Status</th><th>Details</th></tr></thead><tbody><?php foreach ($students as $s): ?><tr><td><?= e($s['student_name']) ?><br><small><?= e($s['student_email']) ?></small></td><td><?= e($s['student_no']) ?></td><td><?= e($s['course'] . ' ' . $s['year_level']) ?></td><td><?= e($s['start_date'] . ' to ' . $s['end_date']) ?></td><td><span class="badge <?= e($s['status']) ?>"><?= e($s['status']) ?></span></td><td><a class="btn btn-small" href="index.php?r=partner&enrollment=<?= (int)$s['id'] ?>">Open</a></td></tr><?php endforeach; ?></tbody></table></div><div class="pagination"></div></section>

<?php if ($selected): ?>
<div class="grid two">
	<section class="card">
		<div class="section-head"><h2><?= e($selected['student_name']) ?> - Forwarded Documents</h2><span class="badge <?= e($selected['predeployment_status']) ?>"><?= e(str_replace('_', ' ', $selected['predeployment_status'])) ?></span></div>
		<p><strong>Endorsement Letter:</strong> <?= $selected['endorsement_file'] ? '<a class="btn btn-small" target="_blank" href="' . e($selected['endorsement_file']) . '">View</a>' : '<span class="muted">Not yet forwarded</span>' ?></p>
		<div class="table-wrap"><table class="data-table"><thead><tr><th>Requirement</th><th>File</th></tr></thead><tbody>
			<?php foreach ($requirements as $req): ?><tr><td><?= e($req['requirement_name']) ?></td><td><?= !empty($req['file_path']) ? '<a class="btn btn-small" target="_blank" href="' . e($req['file_path']) . '">View</a>' : '-' ?></td></tr><?php endforeach; ?>
		</tbody></table></div>
		<?php if ($selected['predeployment_status'] === 'forwarded'): ?>
			<form method="post" style="margin-top:14px">
				<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
				<input type="hidden" name="action" value="partner_accept_deployment">
				<input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
				<button class="btn btn-primary" type="submit">Accept Deployment</button>
			</form>
		<?php endif; ?>
	</section>
	<section class="card">
		<h2>Orientation & OJT Start</h2>
		<?php if (in_array($selected['predeployment_status'], ['accepted','orientation_scheduled'], true)): ?>
			<form method="post" class="form js-validate" style="margin-bottom:18px">
				<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
				<input type="hidden" name="action" value="partner_send_orientation_email">
				<input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
				<label class="no-floating-label">Orientation Email / Instructions<textarea required name="orientation_notes" placeholder="Send orientation instructions without setting a system date/time yet."></textarea></label>
				<button class="btn btn-small" type="submit">Send Orientation Email Only</button>
			</form>
			<form method="post" class="form js-validate">
				<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
				<input type="hidden" name="action" value="partner_schedule_orientation">
				<input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
				<label>Orientation Date/Time<input required type="datetime-local" name="orientation_datetime" value="<?= e($selected['orientation_datetime'] ? str_replace(' ', 'T', substr($selected['orientation_datetime'], 0, 16)) : '') ?>"></label>
				<label>Notes<textarea name="orientation_notes"><?= e($selected['orientation_notes'] ?? '') ?></textarea></label>
				<button class="btn btn-primary" type="submit">Schedule Orientation</button>
			</form>
		<?php endif; ?>
		<?php if ($selected['predeployment_status'] === 'orientation_scheduled'): ?>
			<form method="post" class="form js-validate" style="margin-top:18px">
				<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
				<input type="hidden" name="action" value="partner_complete_orientation">
				<input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>">
				<label>Official OJT Start Date<input required type="date" name="official_start_date"></label>
				<label>Projected End Date<input type="date" name="projected_end_date"><small class="muted">Leave blank to calculate automatically from <?= (int)$selected['required_hours'] ?> required hours at 8 hours/day, weekdays only.</small></label>
				<button class="btn btn-primary" type="submit">Mark Orientation Completed</button>
			</form>
		<?php endif; ?>
		<?php if ($selected['predeployment_status'] === 'orientation_completed'): ?>
			<p class="muted">OJT officially started on <?= e($selected['official_start_date']) ?>. Projected end date: <?= e($selected['projected_end_date']) ?>.</p>
		<?php endif; ?>
	</section>
</div>
<div class="grid two"><section class="card"><h2><?= e($selected['student_name']) ?> - Time Records</h2><div class="table-wrap"><table class="data-table"><thead><tr><th>Date</th><th>In</th><th>Out</th><th>Hours</th><th>Tasks</th></tr></thead><tbody><?php foreach ($dtrs as $d): ?><tr><td><?= e($d['work_date']) ?></td><td><?= e($d['time_in']) ?></td><td><?= e($d['time_out']) ?></td><td><?= e($d['hours']) ?></td><td><?= e($d['tasks_done']) ?></td></tr><?php endforeach; ?></tbody></table></div></section><section class="card"><h2>Final Evaluation</h2><form method="post" class="form js-validate"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="partner_submit_evaluation"><input type="hidden" name="enrollment_id" value="<?= (int)$selected['id'] ?>"><label>Rating (1-5)<input required type="number" min="1" max="5" name="rating" value="<?= e($evaluation['rating'] ?? '') ?>"></label><label>Comments<textarea required name="comments"><?= e($evaluation['comments'] ?? '') ?></textarea></label><button class="btn btn-primary" type="submit"><span class="btn-text">Submit Evaluation</span><span class="spinner"></span></button></form></section></div>
<?php endif; ?>
