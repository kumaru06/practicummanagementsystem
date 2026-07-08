<?php
class AdminReport
{
    public function __construct(private PDO $db) {}

    public function generate(string $slug): array
    {
        return match ($slug) {
            'active_students' => $this->studentStatusReport('active', 'Students currently on active OJT deployment.'),
            'completed_ojt_students' => $this->studentStatusReport('completed', 'Students who have completed their OJT requirements.'),
            'pending_students' => $this->studentStatusReport('pending', 'Students enrolled in OJT but not yet active or completed.'),
            'student_attendance_summary' => $this->studentAttendanceSummary(),
            'partner_company_list' => $this->partnerCompanyList(),
            'students_per_company' => $this->studentsPerCompany(),
            'company_evaluation_results' => $this->companyEvaluationResults(),
            'daily_attendance' => $this->dailyAttendance(),
            'weekly_attendance' => $this->weeklyAttendance(),
            'monthly_attendance' => $this->monthlyAttendance(),
            'hours_rendered' => $this->hoursRendered(),
            'industry_supervisor_evaluation' => $this->industrySupervisorEvaluations(),
            'student_self_evaluation' => $this->studentSelfEvaluations(),
            'coordinator_evaluation' => $this->coordinatorEvaluations(),
            'final_evaluation_summary' => $this->finalEvaluationSummary(),
            'submitted_requirements' => $this->requirementsReport('submitted'),
            'missing_requirements' => $this->requirementsReport('missing'),
            'approved_documents' => $this->requirementsReport('approved'),
            'rejected_documents' => $this->requirementsReport('rejected'),
            'completion_rate_by_course' => $this->completionRateByCourse(),
            'completion_rate_by_company' => $this->completionRateByCompany(),
            'graduated_ojt_students' => $this->studentStatusReport('completed', 'Students who have graduated from the OJT program.'),
            default => [
                'description' => 'This report is not available yet.',
                'columns' => [],
                'rows' => [],
                'ready' => false,
            ],
        };
    }

    private function studentStatusReport(string $status, string $description): array
    {
        $stmt = $this->db->prepare('
            SELECT
                u.name AS student_name,
                s.student_no,
                COALESCE(p.code, s.course) AS course,
                pc.name AS company_name,
                coord.name AS coordinator_name,
                COALESCE(e.official_start_date, e.start_date) AS start_date,
                COALESCE(e.projected_end_date, e.end_date) AS end_date,
                e.required_hours,
                COALESCE(SUM(CASE WHEN d.verification_status = "approved" THEN d.hours ELSE 0 END), 0) AS rendered_hours,
                e.status
            FROM ojt_enrollments e
            JOIN students s ON s.id = e.student_id
            JOIN users u ON u.id = s.user_id
            LEFT JOIN programs p ON p.id = s.program_id
            JOIN partner_companies pc ON pc.id = e.company_id
            LEFT JOIN users coord ON coord.id = s.coordinator_id
            LEFT JOIN daily_time_records d ON d.student_id = s.id
            WHERE e.status = ?
            GROUP BY e.id, s.id, u.id, p.id, pc.id, coord.id
            ORDER BY u.name
        ');
        $stmt->execute([$status]);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                $row['student_name'],
                $row['student_no'],
                $row['course'],
                $row['company_name'],
                $row['coordinator_name'] ?: 'â€”',
                $this->formatDate($row['start_date'] ?? null),
                $this->formatDate($row['end_date'] ?? null),
                number_format((float)$row['rendered_hours'], 1) . ' / ' . (int)$row['required_hours'] . ' hrs',
                ucfirst((string)$row['status']),
            ];
        }

        return [
            'description' => $description,
            'columns' => ['Student', 'Student ID', 'Course', 'Company', 'Coordinator', 'Start Date', 'End Date', 'Hours', 'Status'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function studentAttendanceSummary(): array
    {
        $stmt = $this->db->query('
            SELECT
                u.name AS student_name,
                s.student_no,
                COALESCE(p.code, s.course) AS course,
                pc.name AS company_name,
                e.required_hours,
                COALESCE(SUM(CASE WHEN d.verification_status = "approved" THEN d.hours ELSE 0 END), 0) AS approved_hours,
                COALESCE(SUM(CASE WHEN d.verification_status = "pending" THEN d.hours ELSE 0 END), 0) AS pending_hours,
                COUNT(d.id) AS dtr_entries
            FROM ojt_enrollments e
            JOIN students s ON s.id = e.student_id
            JOIN users u ON u.id = s.user_id
            LEFT JOIN programs p ON p.id = s.program_id
            JOIN partner_companies pc ON pc.id = e.company_id
            LEFT JOIN daily_time_records d ON d.student_id = s.id
            GROUP BY e.id, s.id, u.id, p.id, pc.id
            ORDER BY u.name
        ');
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $required = max(1, (int)$row['required_hours']);
            $pct = min(100, round(((float)$row['approved_hours'] / $required) * 100, 1));
            $rows[] = [
                $row['student_name'],
                $row['student_no'],
                $row['course'],
                $row['company_name'],
                number_format((float)$row['approved_hours'], 1) . ' hrs',
                number_format((float)$row['pending_hours'], 1) . ' hrs',
                (int)$row['dtr_entries'],
                $pct . '%',
            ];
        }

        return [
            'description' => 'Attendance and rendered hours summary for all enrolled students.',
            'columns' => ['Student', 'Student ID', 'Course', 'Company', 'Approved Hours', 'Pending Hours', 'DTR Entries', 'Progress'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function partnerCompanyList(): array
    {
        $companies = (new Company($this->db))->all();
        $rows = [];
        foreach ($companies as $company) {
            $rows[] = [
                $company['name'],
                $company['contact_person'] ?? 'â€”',
                $company['contact_email'] ?? 'â€”',
                $company['contact_number'] ?: 'â€”',
                $company['accepted_programs'] ?: 'â€”',
                !empty($company['is_active']) ? 'Active' : 'Inactive',
            ];
        }

        return [
            'description' => 'List of all registered host training establishment companies.',
            'columns' => ['Company', 'Contact Person', 'Email', 'Contact No.', 'Accepted Programs', 'Account Status'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function studentsPerCompany(): array
    {
        $stmt = $this->db->query('
            SELECT
                pc.name AS company_name,
                COUNT(e.id) AS total_students,
                SUM(CASE WHEN e.status = "active" THEN 1 ELSE 0 END) AS active_students,
                SUM(CASE WHEN e.status = "completed" THEN 1 ELSE 0 END) AS completed_students,
                SUM(CASE WHEN e.status = "pending" THEN 1 ELSE 0 END) AS pending_students
            FROM partner_companies pc
            LEFT JOIN ojt_enrollments e ON e.company_id = pc.id
            GROUP BY pc.id, pc.name
            ORDER BY total_students DESC, pc.name
        ');
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                $row['company_name'],
                (int)$row['total_students'],
                (int)$row['active_students'],
                (int)$row['completed_students'],
                (int)$row['pending_students'],
            ];
        }

        return [
            'description' => 'Number of students assigned to each host training establishment.',
            'columns' => ['Company', 'Total Students', 'Active', 'Completed', 'Pending'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function companyEvaluationResults(): array
    {
        if (!$this->tableExists('evaluations')) {
            return $this->emptyReport('Final evaluation results submitted by host training establishments.');
        }

        $stmt = $this->db->query('
            SELECT
                pc.name AS company_name,
                u.name AS student_name,
                s.student_no,
                ev.final_grade,
                ev.rating,
                ev.submitted_at
            FROM evaluations ev
            JOIN ojt_enrollments en ON en.id = ev.enrollment_id
            JOIN partner_companies pc ON pc.id = ev.company_id
            JOIN students s ON s.id = en.student_id
            JOIN users u ON u.id = s.user_id
            ORDER BY ev.submitted_at DESC
        ');
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $grade = $row['final_grade'] !== null
                ? number_format((float)$row['final_grade'], 2) . '%'
                : ((int)$row['rating'] . ' / 5');
            $rows[] = [
                $row['company_name'],
                $row['student_name'],
                $row['student_no'],
                $grade,
                $this->formatDate($row['submitted_at'] ?? null, true),
            ];
        }

        return [
            'description' => 'Final evaluation results submitted by host training establishments.',
            'columns' => ['Company', 'Student', 'Student ID', 'Final Grade', 'Submitted'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function dailyAttendance(): array
    {
        $stmt = $this->db->query('
            SELECT
                u.name AS student_name,
                s.student_no,
                d.work_date,
                d.day_type,
                d.hours,
                d.verification_status,
                d.submitted_at
            FROM daily_time_records d
            JOIN students s ON s.id = d.student_id
            JOIN users u ON u.id = s.user_id
            ORDER BY d.work_date DESC, u.name
            LIMIT 500
        ');
        return $this->attendanceRows($stmt->fetchAll(), 'Daily time records submitted by students.');
    }

    private function weeklyAttendance(): array
    {
        $stmt = $this->db->query('
            SELECT
                u.name AS student_name,
                s.student_no,
                w.week_start,
                w.week_end,
                w.verification_status,
                w.submitted_at
            FROM weekly_reports w
            JOIN students s ON s.id = w.student_id
            JOIN users u ON u.id = s.user_id
            ORDER BY w.week_start DESC, u.name
            LIMIT 500
        ');
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                $row['student_name'],
                $row['student_no'],
                $this->formatDate($row['week_start'] ?? null),
                $this->formatDate($row['week_end'] ?? null),
                ucfirst((string)($row['verification_status'] ?? 'pending')),
                $this->formatDate($row['submitted_at'] ?? null, true),
            ];
        }

        return [
            'description' => 'Weekly reports submitted by students.',
            'columns' => ['Student', 'Student ID', 'Week Start', 'Week End', 'Status', 'Submitted'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function monthlyAttendance(): array
    {
        $stmt = $this->db->query('
            SELECT
                u.name AS student_name,
                s.student_no,
                DATE_FORMAT(d.work_date, "%Y-%m") AS month_label,
                COUNT(d.id) AS days_logged,
                COALESCE(SUM(CASE WHEN d.verification_status = "approved" THEN d.hours ELSE 0 END), 0) AS approved_hours
            FROM daily_time_records d
            JOIN students s ON s.id = d.student_id
            JOIN users u ON u.id = s.user_id
            GROUP BY s.id, u.id, DATE_FORMAT(d.work_date, "%Y-%m")
            ORDER BY month_label DESC, u.name
        ');
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                $row['student_name'],
                $row['student_no'],
                $row['month_label'],
                (int)$row['days_logged'],
                number_format((float)$row['approved_hours'], 1) . ' hrs',
            ];
        }

        return [
            'description' => 'Monthly attendance summary based on daily time records.',
            'columns' => ['Student', 'Student ID', 'Month', 'Days Logged', 'Approved Hours'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function hoursRendered(): array
    {
        $stmt = $this->db->query('
            SELECT
                u.name AS student_name,
                s.student_no,
                COALESCE(p.code, s.course) AS course,
                pc.name AS company_name,
                e.required_hours,
                COALESCE(SUM(CASE WHEN d.verification_status = "approved" THEN d.hours ELSE 0 END), 0) AS approved_hours,
                COALESCE(SUM(d.hours), 0) AS total_logged_hours
            FROM ojt_enrollments e
            JOIN students s ON s.id = e.student_id
            JOIN users u ON u.id = s.user_id
            LEFT JOIN programs p ON p.id = s.program_id
            JOIN partner_companies pc ON pc.id = e.company_id
            LEFT JOIN daily_time_records d ON d.student_id = s.id
            GROUP BY e.id, s.id, u.id, p.id, pc.id
            ORDER BY approved_hours DESC, u.name
        ');
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                $row['student_name'],
                $row['student_no'],
                $row['course'],
                $row['company_name'],
                number_format((float)$row['approved_hours'], 1) . ' hrs',
                number_format((float)$row['total_logged_hours'], 1) . ' hrs',
                (int)$row['required_hours'] . ' hrs',
            ];
        }

        return [
            'description' => 'Total approved and logged hours rendered per student.',
            'columns' => ['Student', 'Student ID', 'Course', 'Company', 'Approved Hours', 'Total Logged', 'Required Hours'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function industrySupervisorEvaluations(): array
    {
        return $this->companyEvaluationResults();
    }

    private function studentSelfEvaluations(): array
    {
        if (!$this->tableExists('student_final_requirements')) {
            return $this->emptyReport('Students who submitted their personal observation (self-reflection) report.');
        }

        $stmt = $this->db->query('
            SELECT
                u.name AS student_name,
                s.student_no,
                COALESCE(p.code, s.course) AS course,
                fr.personal_observation_status,
                fr.updated_at
            FROM students s
            JOIN users u ON u.id = s.user_id
            LEFT JOIN programs p ON p.id = s.program_id
            LEFT JOIN student_final_requirements fr ON fr.student_id = s.id
            WHERE fr.personal_observation IS NOT NULL AND TRIM(fr.personal_observation) <> ""
            ORDER BY fr.updated_at DESC, u.name
        ');
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                $row['student_name'],
                $row['student_no'],
                $row['course'],
                ucfirst((string)($row['personal_observation_status'] ?? 'pending')),
                $this->formatDate($row['updated_at'] ?? null, true),
            ];
        }

        return [
            'description' => 'Students who submitted their personal observation (self-reflection) report.',
            'columns' => ['Student', 'Student ID', 'Course', 'Status', 'Last Updated'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function coordinatorEvaluations(): array
    {
        if (!$this->tableExists('student_evaluations')) {
            return $this->emptyReport('Student evaluations of their OJT coordinators.');
        }

        $stmt = $this->db->query('
            SELECT
                u.name AS student_name,
                s.student_no,
                coord.name AS coordinator_name,
                se.coordinator_grade,
                se.coordinator_status,
                se.updated_at
            FROM student_evaluations se
            JOIN students s ON s.id = se.student_id
            JOIN users u ON u.id = s.user_id
            LEFT JOIN users coord ON coord.id = s.coordinator_id
            WHERE se.coordinator_status = "completed"
            ORDER BY se.updated_at DESC, u.name
        ');
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                $row['student_name'],
                $row['student_no'],
                $row['coordinator_name'] ?: 'â€”',
                $row['coordinator_grade'] !== null ? number_format((float)$row['coordinator_grade'], 2) . '%' : 'â€”',
                ucfirst((string)$row['coordinator_status']),
                $this->formatDate($row['updated_at'] ?? null, true),
            ];
        }

        return [
            'description' => 'Student evaluations of their OJT coordinators.',
            'columns' => ['Student', 'Student ID', 'Coordinator', 'Grade', 'Status', 'Submitted'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function finalEvaluationSummary(): array
    {
        $stmt = $this->db->query('
            SELECT
                u.name AS student_name,
                s.student_no,
                COALESCE(p.code, s.course) AS course,
                pc.name AS company_name,
                ev.final_grade,
                ev.rating,
                ev.comments,
                ev.submitted_at
            FROM evaluations ev
            JOIN ojt_enrollments en ON en.id = ev.enrollment_id
            JOIN students s ON s.id = en.student_id
            JOIN users u ON u.id = s.user_id
            LEFT JOIN programs p ON p.id = s.program_id
            JOIN partner_companies pc ON pc.id = ev.company_id
            ORDER BY ev.submitted_at DESC
        ');
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $grade = $row['final_grade'] !== null
                ? number_format((float)$row['final_grade'], 2) . '%'
                : ((int)$row['rating'] . ' / 5');
            $rows[] = [
                $row['student_name'],
                $row['student_no'],
                $row['course'],
                $row['company_name'],
                $grade,
                $this->formatDate($row['submitted_at'] ?? null, true),
            ];
        }

        return [
            'description' => 'Summary of all final OJT evaluations submitted by host training establishments.',
            'columns' => ['Student', 'Student ID', 'Course', 'Company', 'Final Grade', 'Submitted'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function requirementsReport(string $mode): array
    {
        if ($mode === 'missing') {
            $studentModel = new Student($this->db);
            $stmt = $this->db->query('
                SELECT s.id, u.name AS student_name, s.student_no
                FROM students s
                JOIN users u ON u.id = s.user_id
                JOIN ojt_enrollments e ON e.student_id = s.id
                ORDER BY u.name
            ');
            $rows = [];
            foreach ($stmt->fetchAll() as $student) {
                foreach ($studentModel->requirements((int)$student['id']) as $req) {
                    if (!empty($req['file_path'])) {
                        continue;
                    }
                    $rows[] = [
                        $student['student_name'],
                        $student['student_no'],
                        $req['requirement_name'] ?? 'Requirement',
                        'Missing',
                        'â€”',
                        'â€”',
                    ];
                }
            }

            return [
                'description' => 'Required documents that have not been uploaded yet.',
                'columns' => ['Student', 'Student ID', 'Requirement', 'Status', 'Uploaded', 'Reviewed'],
                'rows' => $rows,
                'ready' => true,
            ];
        }

        $sql = '
            SELECT
                u.name AS student_name,
                s.student_no,
                sr.requirement_name,
                sr.status,
                sr.uploaded_at,
                sr.reviewed_at
            FROM student_requirements sr
            JOIN students s ON s.id = sr.student_id
            JOIN users u ON u.id = s.user_id
            WHERE 1=1
        ';
        if ($mode === 'submitted') {
            $sql .= ' AND sr.file_path IS NOT NULL';
        } elseif ($mode === 'approved') {
            $sql .= ' AND sr.status = "approved"';
        } elseif ($mode === 'rejected') {
            $sql .= ' AND sr.status = "rejected"';
        }
        $sql .= ' ORDER BY u.name, sr.requirement_name';

        $stmt = $this->db->query($sql);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                $row['student_name'],
                $row['student_no'],
                $row['requirement_name'],
                ucfirst((string)($row['status'] ?? 'pending')),
                $this->formatDate($row['uploaded_at'] ?? null, true),
                $this->formatDate($row['reviewed_at'] ?? null, true),
            ];
        }

        $descriptions = [
            'submitted' => 'Pre-deployment requirements uploaded by students.',
            'missing' => 'Required documents that have not been uploaded yet.',
            'approved' => 'Documents approved by coordinators.',
            'rejected' => 'Documents rejected and needing revision.',
        ];

        return [
            'description' => $descriptions[$mode] ?? '',
            'columns' => ['Student', 'Student ID', 'Requirement', 'Status', 'Uploaded', 'Reviewed'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function completionRateByCourse(): array
    {
        $rows = [];
        foreach ((new Enrollment($this->db))->completionRatesByCourse() as $row) {
            $rows[] = [
                $row['label'],
                (int)$row['total'],
                number_format((float)$row['value'], 2) . '%',
            ];
        }

        return [
            'description' => 'Average OJT completion progress grouped by course.',
            'columns' => ['Course', 'Enrolled Students', 'Avg. Completion'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function completionRateByCompany(): array
    {
        $stmt = $this->db->query('
            SELECT
                pc.name AS company_name,
                COUNT(e.id) AS total_students,
                ROUND(AVG(
                    LEAST(
                        COALESCE(
                            (SELECT SUM(d.hours) FROM daily_time_records d WHERE d.student_id = e.student_id AND d.verification_status = "approved"),
                            0
                        ) / NULLIF(e.required_hours, 0) * 100,
                        100
                    )
                ), 2) AS completion_rate
            FROM ojt_enrollments e
            JOIN partner_companies pc ON pc.id = e.company_id
            GROUP BY pc.id, pc.name
            ORDER BY completion_rate DESC, pc.name
        ');
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                $row['company_name'],
                (int)$row['total_students'],
                number_format((float)$row['completion_rate'], 2) . '%',
            ];
        }

        return [
            'description' => 'Average OJT completion progress grouped by host training establishment.',
            'columns' => ['Company', 'Students', 'Avg. Completion'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function attendanceRows(array $records, string $description): array
    {
        $rows = [];
        foreach ($records as $row) {
            $rows[] = [
                $row['student_name'],
                $row['student_no'],
                $this->formatDate($row['work_date'] ?? null),
                ucfirst(str_replace('_', ' ', (string)($row['day_type'] ?? 'full'))),
                number_format((float)($row['hours'] ?? 0), 1) . ' hrs',
                ucfirst((string)($row['verification_status'] ?? 'pending')),
                $this->formatDate($row['submitted_at'] ?? null, true),
            ];
        }

        return [
            'description' => $description,
            'columns' => ['Student', 'Student ID', 'Date', 'Day Type', 'Hours', 'Status', 'Submitted'],
            'rows' => $rows,
            'ready' => true,
        ];
    }

    private function formatDate(?string $date, bool $includeTime = false): string
    {
        if (!$date || strtotime($date) === false) {
            return 'â€”';
        }

        return $includeTime
            ? date('M j, Y g:i A', strtotime($date))
            : date('M j, Y', strtotime($date));
    }

    private function emptyReport(string $description): array
    {
        return [
            'description' => $description,
            'columns' => [],
            'rows' => [],
            'ready' => true,
        ];
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->db->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$table]);
            return (bool)$stmt->fetch();
        } catch (Throwable) {
            return false;
        }
    }
}
