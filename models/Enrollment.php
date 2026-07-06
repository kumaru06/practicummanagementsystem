<?php
class Enrollment
{
    public function __construct(private PDO $db) {}

    public function create(int $studentId, int $companyId, ?string $startDate, ?string $endDate, int $requiredHours, string $academicTerm = '', string $termStartDate = '', string $termEndDate = ''): int
    {
        $stmt = $this->db->prepare('INSERT INTO ojt_enrollments (student_id, company_id, academic_term, term_start_date, term_end_date, start_date, end_date, required_hours, status, predeployment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pending", "not_submitted") ON DUPLICATE KEY UPDATE company_id = VALUES(company_id), academic_term = VALUES(academic_term), term_start_date = VALUES(term_start_date), term_end_date = VALUES(term_end_date), start_date = VALUES(start_date), end_date = VALUES(end_date), required_hours = VALUES(required_hours)');
        $stmt->execute([$studentId, $companyId, $academicTerm, $termStartDate ?: null, $termEndDate ?: null, $startDate ?: null, $endDate ?: null, $requiredHours]);
        return (int)$this->db->lastInsertId();
    }

    public function activeCount(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM ojt_enrollments WHERE status = "active"')->fetchColumn();
    }

    public function syncCompletion(int $studentId): void
    {
        $stmt = $this->db->prepare('SELECT e.id, e.required_hours, COALESCE(SUM(CASE WHEN d.verification_status = "approved" THEN d.hours ELSE 0 END), 0) rendered_hours FROM ojt_enrollments e LEFT JOIN daily_time_records d ON d.student_id = e.student_id WHERE e.student_id = ? AND e.status = "active" GROUP BY e.id, e.required_hours');
        $stmt->execute([$studentId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            if ((float)$row['rendered_hours'] >= (float)$row['required_hours']) {
                $update = $this->db->prepare('UPDATE ojt_enrollments SET status = "completed" WHERE id = ?');
                $update->execute([$row['id']]);
            }
        }
    }

    public function statusDistribution(): array
    {
        return $this->db->query('SELECT status label, COUNT(*) value FROM ojt_enrollments GROUP BY status ORDER BY status')->fetchAll();
    }

    public function completionRatesByCourse(): array
    {
        return $this->db->query('
            SELECT
                s.course AS label,
                COUNT(e.id) AS total,
                ROUND(
                    AVG(
                        LEAST(
                            COALESCE(
                                (SELECT SUM(d.hours) FROM daily_time_records d WHERE d.student_id = e.student_id),
                                0
                            ) / NULLIF(e.required_hours, 0) * 100,
                            100
                        )
                    ), 2
                ) AS value
            FROM ojt_enrollments e
            JOIN students s ON s.id = e.student_id
            GROUP BY s.course
            ORDER BY s.course
        ')->fetchAll();
    }

    public function studentProgressByCourse(): array
    {
        $rows = $this->db->query('
            SELECT s.course, s.student_no, u.name,
                COALESCE((SELECT SUM(d.hours) FROM daily_time_records d WHERE d.student_id = e.student_id), 0) AS logged_hours,
                e.required_hours,
                LEAST(ROUND(
                    COALESCE((SELECT SUM(d.hours) FROM daily_time_records d WHERE d.student_id = e.student_id), 0)
                    / NULLIF(e.required_hours, 0) * 100, 1
                ), 100) AS pct
            FROM ojt_enrollments e
            JOIN students s ON s.id = e.student_id
            JOIN users u ON u.id = s.user_id
            ORDER BY s.course, pct DESC
        ')->fetchAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['course']][] = [
                'name'       => $row['name'],
                'student_no' => $row['student_no'],
                'logged'     => (float)$row['logged_hours'],
                'required'   => (int)$row['required_hours'],
                'pct'        => (float)$row['pct'],
            ];
        }
        return $grouped;
    }

    public function monthlyEnrollmentTrends(): array
    {
        return $this->db->query('SELECT DATE_FORMAT(created_at, "%Y-%m") label, COUNT(*) value FROM ojt_enrollments GROUP BY DATE_FORMAT(created_at, "%Y-%m") ORDER BY label')->fetchAll();
    }

    public function countByCoordinator(int $coordinatorUserId, ?string $status = null): int
    {
        $sql = 'SELECT COUNT(*) FROM ojt_enrollments e JOIN students s ON s.id = e.student_id WHERE s.coordinator_id = ?';
        $params = [$coordinatorUserId];
        if ($status) {
            $sql .= ' AND e.status = ?';
            $params[] = $status;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function statusDistributionByCoordinator(int $coordinatorUserId): array
    {
        $stmt = $this->db->prepare('SELECT e.status label, COUNT(*) value FROM ojt_enrollments e JOIN students s ON s.id = e.student_id WHERE s.coordinator_id = ? GROUP BY e.status ORDER BY e.status');
        $stmt->execute([$coordinatorUserId]);
        return $stmt->fetchAll();
    }

    public function completionRatesByCourseByCoordinator(int $coordinatorUserId): array
    {
        $stmt = $this->db->prepare('
            SELECT
                s.course AS label,
                COUNT(e.id) AS total,
                ROUND(
                    AVG(
                        LEAST(
                            COALESCE(
                                (SELECT SUM(d.hours) FROM daily_time_records d WHERE d.student_id = e.student_id),
                                0
                            ) / NULLIF(e.required_hours, 0) * 100,
                            100
                        )
                    ), 2
                ) AS value
            FROM ojt_enrollments e
            JOIN students s ON s.id = e.student_id
            WHERE s.coordinator_id = ?
            GROUP BY s.course
            ORDER BY s.course
        ');
        $stmt->execute([$coordinatorUserId]);
        return $stmt->fetchAll();
    }

    public function monthlyEnrollmentTrendsByCoordinator(int $coordinatorUserId): array
    {
        $stmt = $this->db->prepare('SELECT DATE_FORMAT(e.created_at, "%Y-%m") label, COUNT(*) value FROM ojt_enrollments e JOIN students s ON s.id = e.student_id WHERE s.coordinator_id = ? GROUP BY DATE_FORMAT(e.created_at, "%Y-%m") ORDER BY label');
        $stmt->execute([$coordinatorUserId]);
        return $stmt->fetchAll();
    }

    public function detailsByStudent(int $studentId): ?array
    {
        $stmt = $this->db->prepare('SELECT e.*, pc.name company_name, pc.address company_address, pc.contact_person, pc.contact_email FROM ojt_enrollments e JOIN partner_companies pc ON pc.id = e.company_id WHERE e.student_id = ?');
        $stmt->execute([$studentId]);
        $enrollment = $stmt->fetch() ?: null;
        if ($enrollment) {
            $enrollment['predeployment_status'] = (new Student($this->db))->effectivePredeploymentStatus($studentId, $enrollment['predeployment_status'] ?? null);
        }
        return $enrollment;
    }

    public function allowsReports(?array $enrollment): bool
    {
        return enrollment_allows_reports($enrollment);
    }

    public function reportLockMessage(?array $enrollment): string
    {
        return enrollment_report_lock_message($enrollment);
    }

    public function deployedByCompany(int $companyId): array
    {
        $stmt = $this->db->prepare('SELECT e.*, s.student_no, s.course, s.year_level, u.name student_name, u.email student_email FROM ojt_enrollments e JOIN students s ON s.id = e.student_id JOIN users u ON u.id = s.user_id WHERE e.company_id = ? ORDER BY CASE e.predeployment_status WHEN "orientation_completed" THEN 1 WHEN "orientation_scheduled" THEN 2 WHEN "accepted" THEN 3 WHEN "forwarded" THEN 4 WHEN "approved" THEN 5 WHEN "submitted" THEN 6 WHEN "needs_revision" THEN 7 WHEN "not_submitted" THEN 8 ELSE 9 END, COALESCE(e.forwarded_at, e.created_at) DESC');
        $stmt->execute([$companyId]);
        $rows = $stmt->fetchAll();
        $studentModel = new Student($this->db);
        foreach ($rows as &$row) {
            $row['predeployment_status'] = $studentModel->effectivePredeploymentStatus((int)$row['student_id'], $row['predeployment_status'] ?? null);
        }
        unset($row);
        return $rows;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT e.*, s.student_no, s.course, s.year_level, s.cor_file, u.name student_name, u.email student_email FROM ojt_enrollments e JOIN students s ON s.id = e.student_id JOIN users u ON u.id = s.user_id WHERE e.id = ?');
        $stmt->execute([$id]);
        $enrollment = $stmt->fetch() ?: null;
        if ($enrollment) {
            $enrollment['predeployment_status'] = (new Student($this->db))->effectivePredeploymentStatus((int)$enrollment['student_id'], $enrollment['predeployment_status'] ?? null);
        }
        return $enrollment;
    }

    /**
     * Fetches comprehensive endorsement letter data for a specific enrollment.
     * 
     * This method performs a single optimized query to retrieve all data needed
     * for generating an endorsement letter PDF, including student, company, program,
     * and coordinator information.
     * 
     * @param int $enrollmentId The OJT enrollment ID
     * @return ?array Complete endorsement data or null if not found
     */
    public function findForEndorsement(int $enrollmentId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT 
                s.id student_id,
                s.student_no,
                s.course,
                s.year_level,
                u.name student_name,
                u.email student_email,
                p.name program_name,
                p.required_hours program_required_hours,
                pc.id company_id,
                pc.name company_name,
                pc.address company_address,
                pc.contact_person,
                pc.contact_email company_email,
                pc.contact_number company_phone,
                oe.id enrollment_id,
                oe.academic_term,
                oe.term_start_date,
                oe.term_end_date,
                oe.start_date,
                oe.end_date,
                oe.required_hours enrollment_required_hours,
                coord_u.name coordinator_name,
                coord_u.email coordinator_email,
                c.department coordinator_dept
            FROM ojt_enrollments oe
            JOIN students s ON s.id = oe.student_id
            JOIN users u ON u.id = s.user_id
            LEFT JOIN programs p ON p.id = s.program_id
            JOIN partner_companies pc ON pc.id = oe.company_id
            LEFT JOIN users coord_u ON coord_u.id = s.coordinator_id
            LEFT JOIN coordinators c ON c.user_id = coord_u.id
            WHERE oe.id = ?
            LIMIT 1
        ');
        
        $stmt->execute([$enrollmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function setPredeploymentStatus(int $studentId, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE ojt_enrollments SET predeployment_status = ? WHERE student_id = ?');
        $stmt->execute([$status, $studentId]);
    }

    public function setPredeploymentStatusByEnrollment(int $enrollmentId, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE ojt_enrollments SET predeployment_status = ? WHERE id = ?');
        $stmt->execute([$status, $enrollmentId]);
    }

    public function byStudent(int $studentId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM ojt_enrollments WHERE student_id = ? LIMIT 1');
        $stmt->execute([$studentId]);
        return $stmt->fetch() ?: null;
    }

    public function allPlacements(): array
    {
        return $this->db->query('
            SELECT
                e.id,
                e.status,
                COALESCE(e.official_start_date, e.start_date) AS placement_start,
                COALESCE(e.projected_end_date, e.end_date) AS placement_end,
                u.name AS student_name,
                s.student_no,
                COALESCE(p.code, s.course) AS course,
                pc.name AS company_name,
                fr.position_held
            FROM ojt_enrollments e
            JOIN students s ON s.id = e.student_id
            JOIN users u ON u.id = s.user_id
            LEFT JOIN programs p ON p.id = s.program_id
            JOIN partner_companies pc ON pc.id = e.company_id
            LEFT JOIN student_final_requirements fr ON fr.student_id = s.id
            ORDER BY u.name
        ')->fetchAll();
    }

    public function approveAndForward(int $enrollmentId, string $endorsementFile): void
    {
        $stmt = $this->db->prepare('UPDATE ojt_enrollments SET predeployment_status = "forwarded", endorsement_file = ?, forwarded_at = NOW() WHERE id = ?');
        $stmt->execute([$endorsementFile, $enrollmentId]);
    }

    public function acceptDeployment(int $enrollmentId): void
    {
        $stmt = $this->db->prepare('UPDATE ojt_enrollments SET predeployment_status = "accepted", accepted_at = NOW() WHERE id = ?');
        $stmt->execute([$enrollmentId]);
    }

    public function scheduleOrientation(int $enrollmentId, string $dateTime, string $notes): void
    {
        assert_orientation_datetime($dateTime);
        $stmt = $this->db->prepare('UPDATE ojt_enrollments SET predeployment_status = "orientation_scheduled", orientation_datetime = ?, orientation_notes = ? WHERE id = ?');
        $stmt->execute([$dateTime, $notes, $enrollmentId]);
    }

    public function completeOrientation(int $enrollmentId, string $officialStart, string $projectedEnd): void
    {
        $enrollment = $this->find($enrollmentId);
        if (!$enrollment) {
            throw new RuntimeException('Enrollment not found.');
        }
        assert_official_start_date($enrollment, $officialStart, $projectedEnd);
        $stmt = $this->db->prepare('UPDATE ojt_enrollments SET predeployment_status = "orientation_completed", status = "active", official_start_date = ?, projected_end_date = ?, start_date = ?, end_date = ? WHERE id = ?');
        $stmt->execute([$officialStart, $projectedEnd, $officialStart, $projectedEnd, $enrollmentId]);
    }
}
