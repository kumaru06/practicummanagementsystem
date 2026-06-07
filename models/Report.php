<?php
class Report
{
    public function __construct(private PDO $db) {}

    public function addDtr(int $studentId, string $date, string $timeIn, string $timeOut, string $tasks): void
    {
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
        $tsIn  = strtotime($timeIn);
        $tsOut = strtotime($timeOut);
        if ($tsIn === false || $tsOut === false) {
            throw new RuntimeException('Invalid time-in or time-out values.');
        }
        // Handle overnight shifts (e.g. time-in 23:00, time-out 01:00)
        if ($tsOut <= $tsIn) {
            $tsOut += 86400;
        }
        $hours = ($tsOut - $tsIn) / 3600;
        $stmt = $this->db->prepare('INSERT INTO daily_time_records (student_id, work_date, time_in, time_out, hours, tasks_done) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$studentId, date('Y-m-d', strtotime($date)), $timeIn, $timeOut, $hours, trim($tasks)]);
    }

    public function dtrDraftByStudent(int $studentId): array
    {
        $this->ensureDtrDraftTable();
        $stmt = $this->db->prepare('SELECT work_date, time_in, time_out, time_in_locked, time_out_locked FROM dtr_drafts WHERE student_id = ?');
        $stmt->execute([$studentId]);
        $draft = $stmt->fetch() ?: [];
        if (!empty($draft['time_in'])) {
            $draft['time_in'] = substr((string)$draft['time_in'], 0, 5);
        }
        if (!empty($draft['time_out'])) {
            $draft['time_out'] = substr((string)$draft['time_out'], 0, 5);
        }
        return $draft;
    }

    public function saveDtrDraft(int $studentId, ?string $workDate, ?string $timeIn, ?string $timeOut, bool $timeInLocked, bool $timeOutLocked): void
    {
        $this->ensureDtrDraftTable();
        $date = $workDate && strtotime($workDate) !== false ? date('Y-m-d', strtotime($workDate)) : null;
        $stmt = $this->db->prepare('INSERT INTO dtr_drafts (student_id, work_date, time_in, time_out, time_in_locked, time_out_locked, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE work_date = VALUES(work_date), time_in = VALUES(time_in), time_out = VALUES(time_out), time_in_locked = VALUES(time_in_locked), time_out_locked = VALUES(time_out_locked), updated_at = NOW()');
        $stmt->execute([$studentId, $date, $this->normalizeDraftTime($timeIn), $this->normalizeDraftTime($timeOut), $timeInLocked ? 1 : 0, $timeOutLocked ? 1 : 0]);
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
        $stmt->execute([$studentId, $weekNo, $dateCoveredStart, $dateCoveredEnd, $text, $accomplishments, $filePath]);
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
    }
}
