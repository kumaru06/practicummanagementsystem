<?php
class Report
{
    public function __construct(private PDO $db) {}

    public function addDtr(
        int $studentId,
        string $date,
        string $dayType,
        string $morningIn,
        string $morningOut,
        string $afternoonIn,
        string $afternoonOut,
        string $tasks
    ): void {
        if (!$date || strtotime($date) === false) {
            throw new RuntimeException('Invalid work date.');
        }
        if (date('Y-m-d', strtotime($date)) > date('Y-m-d')) {
            throw new RuntimeException('Daily time record date cannot be in the future.');
        }
        if (!trim($tasks)) {
            throw new RuntimeException('Tasks done is required.');
        }
        $existing = $this->db->prepare('SELECT COUNT(*) FROM daily_time_records WHERE student_id = ? AND work_date = ?');
        $existing->execute([$studentId, date('Y-m-d', strtotime($date))]);
        if ((int)$existing->fetchColumn() > 0) {
            throw new RuntimeException('A daily time record already exists for this date.');
        }

        $this->ensureDtrSessionColumns();
        $this->ensureDtrDayTypeColumn();
        $dayType = normalize_dtr_day_type($dayType);
        $times = $this->validateDtrTimes($dayType, $morningIn, $morningOut, $afternoonIn, $afternoonOut);

        $stmt = $this->db->prepare(
            'INSERT INTO daily_time_records (
                student_id, work_date, day_type, time_in, time_out,
                morning_time_in, morning_time_out, afternoon_time_in, afternoon_time_out,
                hours, tasks_done
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        try {
            $stmt->execute([
                $studentId,
                date('Y-m-d', strtotime($date)),
                $dayType,
                $times['time_in'],
                $times['time_out'],
                $times['morning_in'],
                $times['morning_out'],
                $times['afternoon_in'],
                $times['afternoon_out'],
                $times['hours'],
                trim($tasks),
            ]);
        } catch (PDOException $e) {
            // Unique-key race: a second concurrent submit for the same day.
            if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                throw new RuntimeException('A daily time record already exists for this date.');
            }
            throw $e;
        }
    }

    public function dtrDraftByStudent(int $studentId): array
    {
        $this->ensureDtrDraftTable();
        $stmt = $this->db->prepare(
            'SELECT work_date, day_type,
                morning_time_in, morning_time_out, afternoon_time_in, afternoon_time_out,
                morning_time_in_locked, morning_time_out_locked, afternoon_time_in_locked, afternoon_time_out_locked,
                time_in, time_out, time_in_locked, time_out_locked
             FROM dtr_drafts WHERE student_id = ?'
        );
        $stmt->execute([$studentId]);
        $draft = $stmt->fetch() ?: [];

        foreach (['morning_time_in', 'morning_time_out', 'afternoon_time_in', 'afternoon_time_out', 'time_in', 'time_out'] as $field) {
            if (!empty($draft[$field])) {
                $draft[$field] = substr((string)$draft[$field], 0, 5);
            }
        }

        if (empty($draft['morning_time_in']) && !empty($draft['time_in'])) {
            $draft['morning_time_in'] = $draft['time_in'];
        }
        if (empty($draft['morning_time_out']) && !empty($draft['time_out'])) {
            $draft['morning_time_out'] = $draft['time_out'];
        }
        if (!isset($draft['morning_time_in_locked']) && isset($draft['time_in_locked'])) {
            $draft['morning_time_in_locked'] = $draft['time_in_locked'];
        }
        if (!isset($draft['morning_time_out_locked']) && isset($draft['time_out_locked'])) {
            $draft['morning_time_out_locked'] = $draft['time_out_locked'];
        }

        foreach (['morning_time_in', 'morning_time_out', 'afternoon_time_in', 'afternoon_time_out'] as $field) {
            if (!dtr_time_has_value($draft[$field] ?? null)) {
                $draft[$field] = '';
                $draft[$field . '_locked'] = 0;
            }
        }

        $draft['day_type'] = normalize_dtr_day_type($draft['day_type'] ?? 'full');

        return $draft;
    }

    public function saveDtrDraft(
        int $studentId,
        ?string $workDate,
        string $dayType,
        ?string $morningIn,
        ?string $morningOut,
        ?string $afternoonIn,
        ?string $afternoonOut,
        bool $morningInLocked,
        bool $morningOutLocked,
        bool $afternoonInLocked,
        bool $afternoonOutLocked
    ): void {
        $this->ensureDtrDraftTable();
        $date = $workDate && strtotime($workDate) !== false ? date('Y-m-d', strtotime($workDate)) : null;
        $dayType = normalize_dtr_day_type($dayType);
        $morningInNorm = $this->normalizeDraftTime($morningIn);
        $morningOutNorm = $this->normalizeDraftTime($morningOut);
        $afternoonInNorm = $this->normalizeDraftTime($afternoonIn);
        $afternoonOutNorm = $this->normalizeDraftTime($afternoonOut);
        $morningInLocked = $morningInLocked && $morningInNorm !== null;
        $morningOutLocked = $morningOutLocked && $morningOutNorm !== null;
        $afternoonInLocked = $afternoonInLocked && $afternoonInNorm !== null;
        $afternoonOutLocked = $afternoonOutLocked && $afternoonOutNorm !== null;

        $stmt = $this->db->prepare(
            'INSERT INTO dtr_drafts (
                student_id, work_date, day_type,
                time_in, time_out, time_in_locked, time_out_locked,
                morning_time_in, morning_time_out, afternoon_time_in, afternoon_time_out,
                morning_time_in_locked, morning_time_out_locked, afternoon_time_in_locked, afternoon_time_out_locked,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                work_date = VALUES(work_date),
                day_type = VALUES(day_type),
                time_in = VALUES(time_in),
                time_out = VALUES(time_out),
                time_in_locked = VALUES(time_in_locked),
                time_out_locked = VALUES(time_out_locked),
                morning_time_in = VALUES(morning_time_in),
                morning_time_out = VALUES(morning_time_out),
                afternoon_time_in = VALUES(afternoon_time_in),
                afternoon_time_out = VALUES(afternoon_time_out),
                morning_time_in_locked = VALUES(morning_time_in_locked),
                morning_time_out_locked = VALUES(morning_time_out_locked),
                afternoon_time_in_locked = VALUES(afternoon_time_in_locked),
                afternoon_time_out_locked = VALUES(afternoon_time_out_locked),
                updated_at = NOW()'
        );
        $stmt->execute([
            $studentId,
            $date,
            $dayType,
            $morningInNorm,
            $afternoonOutNorm ?: $morningOutNorm,
            $morningInLocked ? 1 : 0,
            $afternoonOutLocked ? 1 : 0,
            $morningInNorm,
            $morningOutNorm,
            $afternoonInNorm,
            $afternoonOutNorm,
            $morningInLocked ? 1 : 0,
            $morningOutLocked ? 1 : 0,
            $afternoonInLocked ? 1 : 0,
            $afternoonOutLocked ? 1 : 0,
        ]);
    }

    public function clearDtrDraft(int $studentId): void
    {
        $this->ensureDtrDraftTable();
        $stmt = $this->db->prepare('DELETE FROM dtr_drafts WHERE student_id = ?');
        $stmt->execute([$studentId]);
    }

    public function dtrByStudent(int $studentId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM daily_time_records WHERE student_id = ? ORDER BY work_date DESC');
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    public function totalHours(int $studentId, bool $approvedOnly = false): float
    {
        $sql = 'SELECT COALESCE(SUM(hours),0) FROM daily_time_records WHERE student_id = ?';
        if ($approvedOnly) {
            $sql .= " AND verification_status = 'approved'";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$studentId]);
        return (float)$stmt->fetchColumn();
    }

    /**
     * Pending DTR records by company. Joins through students → ojt_enrollments.
     */
    public function pendingDtrByCompany(int $companyId): array
    {
        $stmt = $this->db->prepare(
            'SELECT d.*, s.id student_id, u.name student_name, s.student_no
             FROM daily_time_records d
             JOIN students s ON s.id = d.student_id
             JOIN users u ON u.id = s.user_id
             JOIN ojt_enrollments e ON e.student_id = s.id
             WHERE e.company_id = ? AND d.verification_status = "pending"
             ORDER BY d.work_date DESC'
        );
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }

    public function pendingWeeklyByCompany(int $companyId): array
    {
        $stmt = $this->db->prepare(
            'SELECT w.*, s.id student_id, u.name student_name, s.student_no
             FROM weekly_reports w
             JOIN students s ON s.id = w.student_id
             JOIN users u ON u.id = s.user_id
             JOIN ojt_enrollments e ON e.student_id = s.id
             WHERE e.company_id = ? AND w.verification_status = "pending"
             ORDER BY w.week_no DESC'
        );
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }

    /**
     * Aggregated counts of pending submissions per student for a company.
     */
    public function submissionSummaryByCompany(int $companyId): array
    {
        $stmt = $this->db->prepare(
            'SELECT s.id student_id, u.name student_name, s.student_no, s.course, s.year_level,
                (SELECT COUNT(*) FROM daily_time_records d WHERE d.student_id = s.id AND d.verification_status = "pending") pending_dtr,
                (SELECT COUNT(*) FROM weekly_reports w WHERE w.student_id = s.id AND w.verification_status = "pending") pending_weekly,
                (SELECT COUNT(*) FROM daily_time_records d WHERE d.student_id = s.id) total_dtr,
                (SELECT COUNT(*) FROM weekly_reports w WHERE w.student_id = s.id) total_weekly
             FROM ojt_enrollments e
             JOIN students s ON s.id = e.student_id
             JOIN users u ON u.id = s.user_id
             WHERE e.company_id = ?
             ORDER BY (pending_dtr + pending_weekly) DESC, u.name ASC'
        );
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }

    public function findDtr(int $dtrId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM daily_time_records WHERE id = ?');
        $stmt->execute([$dtrId]);
        return $stmt->fetch() ?: null;
    }

    public function findWeekly(int $weeklyId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM weekly_reports WHERE id = ?');
        $stmt->execute([$weeklyId]);
        return $stmt->fetch() ?: null;
    }

    public function setDtrVerification(int $dtrId, string $status, int $verifierUserId, ?string $notes = null): void
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new RuntimeException('Invalid verification status.');
        }
        $stmt = $this->db->prepare('UPDATE daily_time_records SET verification_status = ?, verified_by = ?, verified_at = NOW(), verification_notes = ? WHERE id = ?');
        $stmt->execute([$status, $verifierUserId, $notes, $dtrId]);
    }

    public function setWeeklyVerification(int $weeklyId, string $status, int $verifierUserId, ?string $notes = null): void
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new RuntimeException('Invalid verification status.');
        }
        $stmt = $this->db->prepare('UPDATE weekly_reports SET verification_status = ?, verified_by = ?, verified_at = NOW(), verification_notes = ? WHERE id = ?');
        $stmt->execute([$status, $verifierUserId, $notes, $weeklyId]);
    }

    public function resubmitDtr(
        int $dtrId,
        int $studentId,
        string $dayType,
        string $morningIn,
        string $morningOut,
        string $afternoonIn,
        string $afternoonOut,
        string $tasks
    ): void {
        $row = $this->findDtr($dtrId);
        if (!$row || (int)$row['student_id'] !== $studentId) {
            throw new RuntimeException('Daily time record not found.');
        }
        if (strtolower((string)($row['verification_status'] ?? '')) !== 'rejected') {
            throw new RuntimeException('Only rejected daily time records can be corrected and resubmitted.');
        }
        if (!trim($tasks)) {
            throw new RuntimeException('Tasks done is required.');
        }

        $this->ensureDtrSessionColumns();
        $this->ensureDtrDayTypeColumn();
        $dayType = normalize_dtr_day_type($dayType);
        $times = $this->validateDtrTimes($dayType, $morningIn, $morningOut, $afternoonIn, $afternoonOut);

        $stmt = $this->db->prepare(
            'UPDATE daily_time_records SET
                day_type = ?, time_in = ?, time_out = ?,
                morning_time_in = ?, morning_time_out = ?, afternoon_time_in = ?, afternoon_time_out = ?,
                hours = ?, tasks_done = ?,
                verification_status = "pending", verified_by = NULL, verified_at = NULL, verification_notes = NULL
             WHERE id = ? AND student_id = ?'
        );
        $stmt->execute([
            $dayType,
            $times['time_in'],
            $times['time_out'],
            $times['morning_in'],
            $times['morning_out'],
            $times['afternoon_in'],
            $times['afternoon_out'],
            $times['hours'],
            trim($tasks),
            $dtrId,
            $studentId,
        ]);
    }

    public function resubmitWeekly(
        int $weeklyId,
        int $studentId,
        ?string $accomplishments,
        ?string $dateCoveredStart,
        ?string $dateCoveredEnd,
        ?string $reportText = null
    ): void {
        $row = $this->findWeekly($weeklyId);
        if (!$row || (int)$row['student_id'] !== $studentId) {
            throw new RuntimeException('Weekly report not found.');
        }
        if (strtolower((string)($row['verification_status'] ?? '')) !== 'rejected') {
            throw new RuntimeException('Only rejected weekly reports can be corrected and resubmitted.');
        }

        $accomplishments = trim((string)$accomplishments);
        if ($accomplishments === '') {
            throw new RuntimeException('Weekly accomplishments are required.');
        }
        if (mb_strlen($accomplishments) > 2000) {
            $accomplishments = mb_substr($accomplishments, 0, 2000);
        }

        $dateStart = null;
        $dateEnd = null;
        if ($dateCoveredStart && strtotime($dateCoveredStart) !== false) {
            $dateStart = date('Y-m-d', strtotime($dateCoveredStart));
        }
        if ($dateCoveredEnd && strtotime($dateCoveredEnd) !== false) {
            $dateEnd = date('Y-m-d', strtotime($dateCoveredEnd));
        }
        if (!$dateStart || !$dateEnd) {
            throw new RuntimeException('Date covered is required.');
        }

        $stmt = $this->db->prepare(
            'UPDATE weekly_reports SET
                date_covered_start = ?, date_covered_end = ?, report_text = ?, accomplishments = ?,
                verification_status = "pending", verified_by = NULL, verified_at = NULL, verification_notes = NULL
             WHERE id = ? AND student_id = ?'
        );
        $stmt->execute([
            $dateStart,
            $dateEnd,
            trim((string)$reportText),
            $accomplishments,
            $weeklyId,
            $studentId,
        ]);
    }

    public function clearWeeklyProofFiles(int $weeklyReportId): void
    {
        $this->ensureWeeklyReportFilesTable();
        $stmt = $this->db->prepare('DELETE FROM weekly_report_files WHERE weekly_report_id = ?');
        $stmt->execute([$weeklyReportId]);
    }

    public function addWeekly(int $studentId, int $weekNo, ?string $text, ?string $filePath, ?string $accomplishments = null, ?string $dateCoveredStart = null, ?string $dateCoveredEnd = null): int
    {
        if ($weekNo < 1) {
            throw new RuntimeException('Week number must be at least 1.');
        }
        $existing = $this->db->prepare('SELECT COUNT(*) FROM weekly_reports WHERE student_id = ? AND week_no = ?');
        $existing->execute([$studentId, $weekNo]);
        if ((int)$existing->fetchColumn() > 0) {
            throw new RuntimeException('A weekly report already exists for this week.');
        }
        $stmt = $this->db->prepare('INSERT INTO weekly_reports (student_id, week_no, date_covered_start, date_covered_end, report_text, accomplishments, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
        try {
            $stmt->execute([$studentId, $weekNo, $dateCoveredStart, $dateCoveredEnd, $text, $accomplishments, $filePath]);
        } catch (PDOException $e) {
            // Unique-key race: a second concurrent submit for the same week.
            if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                throw new RuntimeException('A weekly report already exists for this week.');
            }
            throw $e;
        }
        return (int)$this->db->lastInsertId();
    }

    public function addWeeklyProofFile(int $weeklyReportId, string $filePath, string $fileName, string $fileType, int $fileSize): void
    {
        $this->ensureWeeklyReportFilesTable();
        $stmt = $this->db->prepare('INSERT INTO weekly_report_files (weekly_report_id, file_path, file_name, file_type, file_size) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$weeklyReportId, $filePath, $fileName, $fileType, $fileSize]);
    }

    public function proofFilesByReport(int $weeklyReportId): array
    {
        $this->ensureWeeklyReportFilesTable();
        $stmt = $this->db->prepare('SELECT * FROM weekly_report_files WHERE weekly_report_id = ? ORDER BY id');
        $stmt->execute([$weeklyReportId]);
        return $stmt->fetchAll();
    }

    private function ensureWeeklyReportFilesTable(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS weekly_report_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            weekly_report_id INT NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_type VARCHAR(20) NOT NULL,
            file_size INT NOT NULL DEFAULT 0,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_wrf_report FOREIGN KEY (weekly_report_id) REFERENCES weekly_reports(id) ON DELETE CASCADE
        ) ENGINE=InnoDB');
    }

    public function weeklyByStudent(int $studentId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM weekly_reports WHERE student_id = ? ORDER BY week_no DESC');
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    private function normalizeDraftTime(?string $time): ?string
    {
        $time = trim((string)$time);
        if ($time === '') return null;
        $timestamp = strtotime($time);
        return $timestamp === false ? null : date('H:i', $timestamp);
    }

    private function ensureDtrDraftTable(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS dtr_drafts (
            student_id INT NOT NULL PRIMARY KEY,
            work_date DATE NULL,
            time_in VARCHAR(5) NULL,
            time_out VARCHAR(5) NULL,
            time_in_locked TINYINT(1) NOT NULL DEFAULT 0,
            time_out_locked TINYINT(1) NOT NULL DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_dtr_drafts_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        ) ENGINE=InnoDB');
        $this->ensureDtrDraftSessionColumns();
        $this->ensureDtrDraftDayTypeColumn();
    }

    private function ensureDtrSessionColumns(): void
    {
        $columns = [
            'morning_time_in' => 'TIME NULL AFTER time_out',
            'morning_time_out' => 'TIME NULL AFTER morning_time_in',
            'afternoon_time_in' => 'TIME NULL AFTER morning_time_out',
            'afternoon_time_out' => 'TIME NULL AFTER afternoon_time_in',
        ];
        foreach ($columns as $column => $definition) {
            if ($this->columnExists('daily_time_records', $column)) {
                continue;
            }
            $this->db->exec("ALTER TABLE daily_time_records ADD COLUMN $column $definition");
        }
    }

    private function ensureDtrDraftSessionColumns(): void
    {
        $columns = [
            'morning_time_in' => 'VARCHAR(5) NULL',
            'morning_time_out' => 'VARCHAR(5) NULL',
            'afternoon_time_in' => 'VARCHAR(5) NULL',
            'afternoon_time_out' => 'VARCHAR(5) NULL',
            'morning_time_in_locked' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'morning_time_out_locked' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'afternoon_time_in_locked' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'afternoon_time_out_locked' => 'TINYINT(1) NOT NULL DEFAULT 0',
        ];
        foreach ($columns as $column => $definition) {
            if ($this->columnExists('dtr_drafts', $column)) {
                continue;
            }
            $this->db->exec("ALTER TABLE dtr_drafts ADD COLUMN $column $definition");
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function ensureDtrDayTypeColumn(): void
    {
        if (!$this->columnExists('daily_time_records', 'day_type')) {
            $this->db->exec(
                "ALTER TABLE daily_time_records ADD COLUMN day_type ENUM('full','half_am','half_pm','sick','absent') NOT NULL DEFAULT 'full' AFTER work_date"
            );
        }
    }

    private function ensureDtrDraftDayTypeColumn(): void
    {
        if (!$this->columnExists('dtr_drafts', 'day_type')) {
            $this->db->exec(
                "ALTER TABLE dtr_drafts ADD COLUMN day_type VARCHAR(20) NOT NULL DEFAULT 'full' AFTER work_date"
            );
        }
    }

    private function validateDtrTimes(
        string $dayType,
        string $morningIn,
        string $morningOut,
        string $afternoonIn,
        string $afternoonOut
    ): array {
        $dayType = normalize_dtr_day_type($dayType);

        if (in_array($dayType, ['sick', 'absent'], true)) {
            return [
                'morning_in' => null,
                'morning_out' => null,
                'afternoon_in' => null,
                'afternoon_out' => null,
                'time_in' => '00:00:00',
                'time_out' => '00:00:00',
                'hours' => 0,
            ];
        }

        $morningStart = 8 * 60;
        $morningEnd = 12 * 60;
        $afternoonStart = 13 * 60;
        $afternoonEnd = 17 * 60;
        $needsMorning = in_array($dayType, ['full', 'half_am'], true);
        $needsAfternoon = in_array($dayType, ['full', 'half_pm'], true);

        $morningInVal = null;
        $morningOutVal = null;
        $afternoonInVal = null;
        $afternoonOutVal = null;
        $hours = 0.0;

        if ($needsMorning) {
            if (!trim($morningIn) || !trim($morningOut)) {
                throw new RuntimeException('Morning time in and time out are required for this day type.');
            }
            $tsMorningIn = $this->parseDtrTime($morningIn);
            $tsMorningOut = $this->parseDtrTime($morningOut);
            $this->assertTimeWindow($tsMorningIn, 'Morning time in', $morningStart, $morningEnd, '8:00 AM and 12:00 NN');
            $this->assertTimeWindow($tsMorningOut, 'Morning time out', $morningStart, $morningEnd, '8:00 AM and 12:00 NN');
            if ($tsMorningOut <= $tsMorningIn) {
                throw new RuntimeException('Morning time out must be after morning time in.');
            }
            $morningInVal = date('H:i:s', $tsMorningIn);
            $morningOutVal = date('H:i:s', $tsMorningOut);
            $hours += ($tsMorningOut - $tsMorningIn) / 3600;
        }

        if ($needsAfternoon) {
            if (!trim($afternoonIn) || !trim($afternoonOut)) {
                throw new RuntimeException('Afternoon time in and time out are required for this day type.');
            }
            $tsAfternoonIn = $this->parseDtrTime($afternoonIn);
            $tsAfternoonOut = $this->parseDtrTime($afternoonOut);
            $this->assertTimeWindow($tsAfternoonIn, 'Afternoon time in', $afternoonStart, $afternoonEnd, '1:00 PM and 5:00 PM');
            $this->assertTimeWindow($tsAfternoonOut, 'Afternoon time out', $afternoonStart, $afternoonEnd, '1:00 PM and 5:00 PM');
            if ($tsAfternoonOut <= $tsAfternoonIn) {
                throw new RuntimeException('Afternoon time out must be after afternoon time in.');
            }
            $afternoonInVal = date('H:i:s', $tsAfternoonIn);
            $afternoonOutVal = date('H:i:s', $tsAfternoonOut);
            $hours += ($tsAfternoonOut - $tsAfternoonIn) / 3600;
        }

        return [
            'morning_in' => $morningInVal,
            'morning_out' => $morningOutVal,
            'afternoon_in' => $afternoonInVal,
            'afternoon_out' => $afternoonOutVal,
            'time_in' => $morningInVal ?? $afternoonInVal ?? '00:00:00',
            'time_out' => $afternoonOutVal ?? $morningOutVal ?? '00:00:00',
            'hours' => round($hours, 2),
        ];
    }

    private function assertTimeWindow(int $timestamp, string $label, int $min, int $max, string $window): void
    {
        $minutes = $this->minutesOfDay($timestamp);
        if ($minutes < $min || $minutes > $max) {
            throw new RuntimeException("$label must be between $window.");
        }
    }

    private function parseDtrTime(string $time): int
    {
        $timestamp = strtotime(trim($time));
        if ($timestamp === false) {
            throw new RuntimeException('Invalid time value.');
        }
        return $timestamp;
    }

    private function minutesOfDay(int $timestamp): int
    {
        return (int)date('G', $timestamp) * 60 + (int)date('i', $timestamp);
    }
}
