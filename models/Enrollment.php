<?php
class Enrollment
{
    public function __construct(private PDO $db) {}

    public function create(int $studentId, int $companyId, ?string $startDate, ?string $endDate, int $requiredHours, string $academicTerm = '', string $termStartDate = '', string $termEndDate = ''): int
    {
        if ($this->byStudent($studentId)) {
            throw new RuntimeException('This student is already enrolled.');
        }
        $stmt = $this->db->prepare('INSERT INTO ojt_enrollments (student_id, company_id, academic_term, term_start_date, term_end_date, start_date, end_date, required_hours, status, predeployment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pending", "not_submitted")');
        $stmt->execute([$studentId, $companyId, $academicTerm, $termStartDate ?: null, $termEndDate ?: null, $startDate ?: null, $endDate ?: null, $requiredHours]);
        return (int)$this->db->lastInsertId();
    }

    public function activeCount(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM ojt_enrollments WHERE status = "active"')->fetchColumn();
    }

    public function countByPredeploymentStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM ojt_enrollments WHERE predeployment_status = ?');
        $stmt->execute([$status]);
        return (int)$stmt->fetchColumn();
    }

    public function countActiveStartsInLastDays(int $days): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM ojt_enrollments
             WHERE official_start_date IS NOT NULL
               AND official_start_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)'
        );
        $stmt->execute([max(1, $days)]);
        return (int)$stmt->fetchColumn();
    }

    public function countActiveStartsBetweenDays(int $olderThanDays, int $newerThanDays): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM ojt_enrollments
             WHERE official_start_date IS NOT NULL
               AND official_start_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
               AND official_start_date < DATE_SUB(CURDATE(), INTERVAL ? DAY)'
        );
        $stmt->execute([max(1, $olderThanDays), max(0, $newerThanDays)]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Marks active enrollments completed once approved hours meet the requirement.
     * Partner portal "Done" also considers a submitted final evaluation.
     */
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
                                (SELECT SUM(d.hours) FROM daily_time_records d WHERE d.student_id = e.student_id AND d.verification_status = "approved"),
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
                COALESCE((SELECT SUM(d.hours) FROM daily_time_records d WHERE d.student_id = e.student_id AND d.verification_status = "approved"), 0) AS logged_hours,
                e.required_hours,
                LEAST(ROUND(
                    COALESCE((SELECT SUM(d.hours) FROM daily_time_records d WHERE d.student_id = e.student_id AND d.verification_status = "approved"), 0)
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

    public function studentProgressByCourseByCoordinator(int $coordinatorUserId): array
    {
        $stmt = $this->db->prepare('
            SELECT s.course, s.student_no, u.name,
                COALESCE((SELECT SUM(d.hours) FROM daily_time_records d WHERE d.student_id = e.student_id AND d.verification_status = "approved"), 0) AS logged_hours,
                e.required_hours,
                LEAST(ROUND(
                    COALESCE((SELECT SUM(d.hours) FROM daily_time_records d WHERE d.student_id = e.student_id AND d.verification_status = "approved"), 0)
                    / NULLIF(e.required_hours, 0) * 100, 1
                ), 100) AS pct
            FROM ojt_enrollments e
            JOIN students s ON s.id = e.student_id
            JOIN users u ON u.id = s.user_id
            WHERE s.coordinator_id = ?
            ORDER BY s.course, pct DESC
        ');
        $stmt->execute([$coordinatorUserId]);
        $rows = $stmt->fetchAll();
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
                                (SELECT SUM(d.hours) FROM daily_time_records d WHERE d.student_id = e.student_id AND d.verification_status = "approved"),
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
        $stmt = $this->db->prepare('SELECT e.*, pc.name company_name, pc.address company_address, pc.contact_person, pc.contact_email, pc.contact_number company_phone, coord_u.name coordinator_name, coord_u.email coordinator_email, c.department coordinator_department FROM ojt_enrollments e JOIN partner_companies pc ON pc.id = e.company_id LEFT JOIN students s ON s.id = e.student_id LEFT JOIN users coord_u ON coord_u.id = s.coordinator_id LEFT JOIN coordinators c ON c.user_id = coord_u.id WHERE e.student_id = ?');
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

        // Partners only see students after the coordinator forwards approved documents.
        return array_values(array_filter($rows, static function (array $row) use ($studentModel): bool {
            return $studentModel->isPredeploymentPipelineAdvanced($row['predeployment_status'] ?? null)
                || ($row['status'] ?? '') === 'completed';
        }));
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

    public function acceptDeployment(int $enrollmentId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ojt_enrollments
             SET predeployment_status = "accepted", accepted_at = NOW()
             WHERE id = ? AND predeployment_status = "forwarded"'
        );
        $stmt->execute([$enrollmentId]);

        return $stmt->rowCount() > 0;
    }

    public function scheduleOrientation(int $enrollmentId, string $dateTime, string $notes): void
    {
        assert_orientation_datetime($dateTime);
        $stmt = $this->db->prepare('UPDATE ojt_enrollments SET predeployment_status = "orientation_scheduled", orientation_datetime = ?, orientation_notes = ? WHERE id = ?');
        $stmt->execute([$dateTime, $notes, $enrollmentId]);
    }

    public function completeOrientation(int $enrollmentId, string $officialStart, string $projectedEnd): bool
    {
        $enrollment = $this->find($enrollmentId);
        if (!$enrollment) {
            throw new RuntimeException('Enrollment not found.');
        }
        assert_official_start_date($enrollment, $officialStart, $projectedEnd);
        $stmt = $this->db->prepare(
            'UPDATE ojt_enrollments
             SET predeployment_status = "orientation_completed", status = "active",
                 official_start_date = ?, projected_end_date = ?, start_date = ?, end_date = ?
             WHERE id = ? AND predeployment_status = "orientation_scheduled"'
        );
        $stmt->execute([$officialStart, $projectedEnd, $officialStart, $projectedEnd, $enrollmentId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Finished OJT students in a term, split by projected end date.
     *
     * @return array{
     *   term: string,
     *   total: int,
     *   before: array{count: int, pct: int},
     *   ontime: array{count: int, pct: int},
     *   beyond: array{count: int, pct: int},
     *   programs: list<array{
     *     name: string,
     *     code: string,
     *     before: int,
     *     ontime: int,
     *     beyond: int,
     *     total: int,
     *     before_pct: int,
     *     ontime_pct: int,
     *     beyond_pct: int
     *   }>
     * }
     */
    public function completionTimingByTerm(string $academicTerm): array
    {
        $academicTerm = trim($academicTerm);
        $empty = $this->emptyCompletionTiming($academicTerm);
        if ($academicTerm === '') {
            return $empty;
        }

        $stmt = $this->db->prepare(
            'SELECT
                e.student_id,
                e.status,
                e.required_hours,
                e.projected_end_date,
                e.end_date,
                COALESCE(NULLIF(TRIM(p.name), ""), NULLIF(TRIM(s.course), ""), "Unspecified Program") AS program_name,
                COALESCE(NULLIF(TRIM(p.code), ""), "") AS program_code
             FROM ojt_enrollments e
             JOIN students s ON s.id = e.student_id
             LEFT JOIN programs p ON p.id = s.program_id
             WHERE TRIM(e.academic_term) = ?'
        );
        $stmt->execute([$academicTerm]);
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$enrollments) {
            return $empty;
        }

        $studentIds = array_values(array_unique(array_map(static fn ($row) => (int)$row['student_id'], $enrollments)));
        $dtrsByStudent = [];
        if ($studentIds) {
            $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
            $dtrStmt = $this->db->prepare(
                "SELECT student_id, work_date, hours
                 FROM daily_time_records
                 WHERE verification_status = 'approved'
                   AND student_id IN ($placeholders)
                 ORDER BY student_id, work_date, id"
            );
            $dtrStmt->execute($studentIds);
            foreach ($dtrStmt->fetchAll(PDO::FETCH_ASSOC) as $dtr) {
                $dtrsByStudent[(int)$dtr['student_id']][] = $dtr;
            }
        }

        $buckets = ['before' => 0, 'ontime' => 0, 'beyond' => 0];
        $programs = [];

        foreach ($enrollments as $row) {
            $projected = trim((string)($row['projected_end_date'] ?? ''));
            if ($projected === '' || strtotime($projected) === false) {
                continue;
            }
            $projected = date('Y-m-d', strtotime($projected));
            $studentId = (int)$row['student_id'];
            $required = (float)$row['required_hours'];
            $records = $dtrsByStudent[$studentId] ?? [];
            $approvedHours = 0.0;
            $finishDate = null;
            foreach ($records as $dtr) {
                $approvedHours += (float)$dtr['hours'];
                if ($finishDate === null && $required > 0 && $approvedHours >= $required) {
                    $workDate = trim((string)($dtr['work_date'] ?? ''));
                    if ($workDate !== '' && strtotime($workDate) !== false) {
                        $finishDate = date('Y-m-d', strtotime($workDate));
                    }
                }
            }

            $finished = $approvedHours >= $required || ($row['status'] ?? '') === 'completed';
            if (!$finished) {
                continue;
            }
            if ($finishDate === null) {
                $fallback = trim((string)($row['end_date'] ?? ''));
                if ($fallback !== '' && strtotime($fallback) !== false) {
                    $finishDate = date('Y-m-d', strtotime($fallback));
                }
            }
            if ($finishDate === null) {
                continue;
            }

            if ($finishDate < $projected) {
                $bucket = 'before';
            } elseif ($finishDate === $projected) {
                $bucket = 'ontime';
            } else {
                $bucket = 'beyond';
            }

            $programName = (string)$row['program_name'];
            $programCode = (string)$row['program_code'];
            $key = strtolower($programCode !== '' ? $programCode : $programName);
            if (!isset($programs[$key])) {
                $programs[$key] = [
                    'name' => $programName,
                    'code' => $programCode,
                    'before' => 0,
                    'ontime' => 0,
                    'beyond' => 0,
                    'total' => 0,
                ];
            }
            $programs[$key][$bucket]++;
            $programs[$key]['total']++;
            $buckets[$bucket]++;
        }

        $total = $buckets['before'] + $buckets['ontime'] + $buckets['beyond'];
        $overallPct = $this->sharePercents([$buckets['before'], $buckets['ontime'], $buckets['beyond']], $total);

        uasort($programs, static fn ($a, $b) => strcasecmp((string)$a['name'], (string)$b['name']));
        $programRows = [];
        foreach ($programs as $program) {
            $shares = $this->sharePercents(
                [(int)$program['before'], (int)$program['ontime'], (int)$program['beyond']],
                (int)$program['total']
            );
            $programRows[] = [
                'name' => $program['name'],
                'code' => $program['code'],
                'before' => (int)$program['before'],
                'ontime' => (int)$program['ontime'],
                'beyond' => (int)$program['beyond'],
                'total' => (int)$program['total'],
                'before_pct' => $shares[0],
                'ontime_pct' => $shares[1],
                'beyond_pct' => $shares[2],
            ];
        }

        return [
            'term' => $academicTerm,
            'total' => $total,
            'before' => ['count' => $buckets['before'], 'pct' => $overallPct[0]],
            'ontime' => ['count' => $buckets['ontime'], 'pct' => $overallPct[1]],
            'beyond' => ['count' => $buckets['beyond'], 'pct' => $overallPct[2]],
            'programs' => $programRows,
        ];
    }

    /**
     * @return array{term: string, total: int, before: array{count: int, pct: int}, ontime: array{count: int, pct: int}, beyond: array{count: int, pct: int}, programs: list}
     */
    public function emptyCompletionTiming(string $academicTerm = ''): array
    {
        return [
            'term' => $academicTerm,
            'total' => 0,
            'before' => ['count' => 0, 'pct' => 0],
            'ontime' => ['count' => 0, 'pct' => 0],
            'beyond' => ['count' => 0, 'pct' => 0],
            'programs' => [],
        ];
    }

    /**
     * Keep existing totals, but show every active program in the table.
     *
     * @param array{
     *   term: string,
     *   total: int,
     *   before: array{count: int, pct: int},
     *   ontime: array{count: int, pct: int},
     *   beyond: array{count: int, pct: int},
     *   programs: list<array<string, mixed>>
     * } $report
     * @param list<array<string, mixed>> $programs
     * @return array{
     *   term: string,
     *   total: int,
     *   before: array{count: int, pct: int},
     *   ontime: array{count: int, pct: int},
     *   beyond: array{count: int, pct: int},
     *   programs: list<array<string, mixed>>
     * }
     */
    public function fillCompletionPrograms(array $report, array $programs): array
    {
        $rows = [];
        foreach ($report['programs'] as $row) {
            $code = strtoupper(trim((string)($row['code'] ?? '')));
            $key = $code !== '' ? $code : strtolower(trim((string)($row['name'] ?? '')));
            $rows[$key] = $row;
        }

        foreach ($programs as $program) {
            $code = strtoupper(trim((string)($program['code'] ?? '')));
            $name = trim((string)($program['name'] ?? ''));
            $key = $code !== '' ? $code : strtolower($name);
            if ($key === '' || isset($rows[$key])) {
                continue;
            }
            $rows[$key] = [
                'name' => $name !== '' ? $name : $code,
                'code' => $code,
                'before' => 0,
                'ontime' => 0,
                'beyond' => 0,
                'total' => 0,
                'before_pct' => 0,
                'ontime_pct' => 0,
                'beyond_pct' => 0,
            ];
        }

        uasort($rows, static fn ($a, $b) => strcasecmp((string)$a['name'], (string)$b['name']));
        $report['programs'] = array_values($rows);
        return $report;
    }

    /**
     * @param list<int> $counts
     * @return list<int>
     */
    private function sharePercents(array $counts, int $total): array
    {
        if ($total <= 0) {
            return array_fill(0, count($counts), 0);
        }

        $raw = [];
        $remainders = [];
        $used = 0;
        foreach ($counts as $i => $count) {
            $exact = ($count / $total) * 100;
            $floor = (int)floor($exact);
            $raw[$i] = $floor;
            $remainders[$i] = $exact - $floor;
            $used += $floor;
        }

        $leftover = 100 - $used;
        arsort($remainders);
        foreach (array_keys($remainders) as $i) {
            if ($leftover <= 0) {
                break;
            }
            if ((int)$counts[$i] <= 0) {
                continue;
            }
            $raw[$i]++;
            $leftover--;
        }

        ksort($raw);
        return array_values($raw);
    }
}
