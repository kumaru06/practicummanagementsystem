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

    public function totalHours(int $studentId): float
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(hours),0) FROM daily_time_records WHERE student_id = ?');
        $stmt->execute([$studentId]);
        return (float)$stmt->fetchColumn();
    }

    public function addWeekly(int $studentId, int $weekNo, ?string $text, ?string $filePath): void
    {
        if ($weekNo < 1) {
            throw new RuntimeException('Week number must be at least 1.');
        }
        $existing = $this->db->prepare('SELECT COUNT(*) FROM weekly_reports WHERE student_id = ? AND week_no = ?');
        $existing->execute([$studentId, $weekNo]);
        if ((int)$existing->fetchColumn() > 0) {
            throw new RuntimeException('A weekly report already exists for this week.');
        }
        $stmt = $this->db->prepare('INSERT INTO weekly_reports (student_id, week_no, report_text, file_path) VALUES (?, ?, ?, ?)');
        $stmt->execute([$studentId, $weekNo, $text, $filePath]);
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
