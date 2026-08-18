<?php
class StudentEvaluation
{
    public function __construct(private PDO $db) {}

    /**
     * Host Training Establishment evaluation criteria
     */
    public static function industryPartnerCriteria(): array
    {
        return [
            'A. HOST TRAINING ESTABLISHMENT EVALUATION' => [
                'ip1' => ['label' => 'Provided a safe and conducive environment for OJT', 'weight' => 10],
                'ip2' => ['label' => 'Provided the necessary materials, tools, and equipment', 'weight' => 10],
                'ip3' => ['label' => 'Allowed me to apply my knowledge and skills in real work situations', 'weight' => 10],
                'ip4' => ['label' => 'Communicated effectively with students, advisers, and procedures clearly', 'weight' => 10],
                'ip5' => ['label' => 'Supported and guided me throughout the OJT duration', 'weight' => 10],
                'ip6' => ['label' => 'Treated me with respect and professionalism', 'weight' => 10],
                'ip7' => ['label' => 'Provided opportunities to learn and grow', 'weight' => 10],
                'ip8' => ['label' => 'Overall satisfaction with the Host Training Establishment', 'weight' => 10],
            ],
            'B. OJT SUPERVISOR EVALUATION' => [
                'os1' => ['label' => 'Clearly explained tasks and responsibilities', 'weight' => 2.5],
                'os2' => ['label' => 'Provided adequate supervision and guidance', 'weight' => 2.5],
                'os3' => ['label' => 'Gave constructive feedback on my performance', 'weight' => 2.5],
                'os4' => ['label' => 'Was approachable and willing to assist', 'weight' => 2.5],
                'os5' => ['label' => 'Encouraged me to learn and improve', 'weight' => 2.5],
                'os6' => ['label' => 'Recognized my efforts and accomplishments', 'weight' => 2.5],
                'os7' => ['label' => 'Demonstrated professionalism and expertise', 'weight' => 2.5],
                'os8' => ['label' => 'Overall satisfaction with my OJT Supervisor', 'weight' => 2.5],
            ],
        ];
    }

    /**
     * OJT Coordinator evaluation criteria
     */
    public static function coordinatorCriteria(): array
    {
        return [
            'C. OJT COORDINATOR EVALUATION' => [
                'oc1' => ['label' => 'Provided clear orientation and guidelines', 'weight' => 12.5],
                'oc2' => ['label' => 'Was accessible for consultations and support', 'weight' => 12.5],
                'oc3' => ['label' => 'Conducted regular monitoring and check-ins', 'weight' => 12.5],
                'oc4' => ['label' => 'Responded promptly to concerns and issues', 'weight' => 12.5],
                'oc5' => ['label' => 'Facilitated effective communication with host training establishments', 'weight' => 12.5],
                'oc6' => ['label' => 'Provided adequate support throughout the OJT', 'weight' => 12.5],
                'oc7' => ['label' => 'Demonstrated professionalism and competence', 'weight' => 12.5],
                'oc8' => ['label' => 'Overall satisfaction with the OJT Coordinator', 'weight' => 12.5],
            ],
        ];
    }

    public function getByStudent(int $studentId): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT * FROM student_evaluations WHERE student_id = ?');
        $stmt->execute([$studentId]);
        return $stmt->fetch() ?: [];
    }

    /**
     * Batch fetch to avoid N+1 queries. Returns rows keyed by student_id.
     *
     * @param int[] $studentIds
     * @return array<int, array<string, mixed>>
     */
    public function getByStudents(array $studentIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $studentIds)));
        if (!$ids) {
            return [];
        }
        $this->ensureTable();
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT * FROM student_evaluations WHERE student_id IN ($placeholders)");
        $stmt->execute($ids);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[(int)$row['student_id']] = $row;
        }
        return $rows;
    }

    public static function statusColumn(string $type): string
    {
        return match ($type) {
            'industry_partner' => 'partner_status',
            'coordinator' => 'coordinator_status',
            default => $type . '_status',
        };
    }

    public static function statusFor(array $row, string $type): string
    {
        $column = self::statusColumn($type);
        $status = strtolower(trim((string)($row[$column] ?? 'pending')));
        return $status !== '' ? $status : 'pending';
    }

    public function saveIndustryPartnerEvaluation(int $studentId, array $ratings, string $comments): void
    {
        $allCriteria = array_merge(...array_values(self::industryPartnerCriteria()));
        $totalWeight = 0;
        $totalScore = 0;
        $cleanRatings = [];

        foreach (self::industryPartnerCriteria() as $section => $criteria) {
            foreach ($criteria as $key => $def) {
                $rating = (int)($ratings[$key] ?? 0);
                if ($rating < 1 || $rating > 5) {
                    throw new RuntimeException('Please rate every item from 1 to 5 stars.');
                }
                $cleanRatings[$key] = $rating;
                $totalWeight += $def['weight'];
                $totalScore += ($rating / 5) * $def['weight'];
            }
        }

        $grade = ($totalWeight > 0) ? round(($totalScore / $totalWeight) * 100, 2) : 0;

        $this->upsert($studentId, [
            'partner_ratings' => json_encode($cleanRatings, JSON_THROW_ON_ERROR),
            'partner_grade' => $grade,
            'partner_comments' => mb_substr(trim($comments), 0, 2000),
            'partner_status' => 'submitted',
        ]);
    }

    public function saveCoordinatorEvaluation(int $studentId, array $ratings, string $comments): void
    {
        $totalWeight = 0;
        $totalScore = 0;
        $cleanRatings = [];

        foreach (self::coordinatorCriteria() as $section => $criteria) {
            foreach ($criteria as $key => $def) {
                $rating = (int)($ratings[$key] ?? 0);
                if ($rating < 1 || $rating > 5) {
                    throw new RuntimeException('Please rate every item from 1 to 5 stars.');
                }
                $cleanRatings[$key] = $rating;
                $totalWeight += $def['weight'];
                $totalScore += ($rating / 5) * $def['weight'];
            }
        }

        $grade = ($totalWeight > 0) ? round(($totalScore / $totalWeight) * 100, 2) : 0;

        $this->upsert($studentId, [
            'coordinator_ratings' => json_encode($cleanRatings, JSON_THROW_ON_ERROR),
            'coordinator_grade' => $grade,
            'coordinator_comments' => mb_substr(trim($comments), 0, 2000),
            'coordinator_status' => 'submitted',
        ]);
    }

    /**
     * @param array<string,string|int|float> $fields
     */
    private function upsert(int $studentId, array $fields): void
    {
        $this->ensureTable();
        $columns = array_keys($fields);
        $insertCols = array_merge(['student_id'], $columns);
        $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
        $updates = implode(', ', array_map(static fn ($c) => "$c = VALUES($c)", $columns));
        $sql = 'INSERT INTO student_evaluations (' . implode(', ', $insertCols) . ', updated_at) '
            . 'VALUES (' . $placeholders . ', NOW()) '
            . 'ON DUPLICATE KEY UPDATE ' . $updates . ', updated_at = NOW()';
        $values = array_merge([$studentId], array_values($fields));
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
    }

    /** @return array<int, array<string, mixed>> */
    public function submittedPartnerEvaluationsByCompany(int $companyId): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare(
            'SELECT se.*, u.name AS student_name, s.student_no, s.course, s.year_level, e.id AS enrollment_id
             FROM student_evaluations se
             JOIN students s ON s.id = se.student_id
             JOIN users u ON u.id = s.user_id
             JOIN ojt_enrollments e ON e.student_id = s.id AND e.company_id = ?
             WHERE se.partner_status = "submitted"
             ORDER BY se.updated_at DESC'
        );
        $stmt->execute([$companyId]);

        return $stmt->fetchAll();
    }

    private function ensureTable(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS student_evaluations (
            student_id INT NOT NULL PRIMARY KEY,
            partner_ratings TEXT NULL,
            partner_grade DECIMAL(5,2) NULL,
            partner_comments TEXT NULL,
            partner_status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            coordinator_ratings TEXT NULL,
            coordinator_grade DECIMAL(5,2) NULL,
            coordinator_comments TEXT NULL,
            coordinator_status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_student_eval_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        ) ENGINE=InnoDB');
    }
}