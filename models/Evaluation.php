<?php
class Evaluation
{
    private ?bool $tableReady = null;
    private ?bool $detailReady = null;

    public function __construct(private PDO $db) {}

    /**
     * Single source of truth for the evaluation criteria, grouped by section.
     * Each criterion has a unique key, a label, and a percentage weight.
     * Total weight across all criteria is 100%.
     */
    public static function criteria(): array
    {
        return [
            'A. Work Performance' => [
                'wp1' => ['label' => 'Knowledge of work (able to grasp as instructed)', 'weight' => 10],
                'wp2' => ['label' => 'Quality of work (can cope with the demand of additional unexpected work load in a limited time)', 'weight' => 10],
                'wp3' => ['label' => 'Quality of work/performs an assigned job efficiently as possible', 'weight' => 10],
                'wp4' => ['label' => 'Attendance (follows assigned work schedule)', 'weight' => 10],
                'wp5' => ['label' => 'Punctuality (reports to work management on time)', 'weight' => 10],
            ],
            'B. Personality Traits' => [
                'pt1' => ['label' => 'Physical appearance (personally well groomed and always wears appropriate dress)', 'weight' => 5],
                'pt2' => ['label' => 'Attitude towards work (always shows enthusiasm and interest)', 'weight' => 5],
                'pt3' => ['label' => 'Courtesy (shows respect for authority at all times)', 'weight' => 5],
                'pt4' => ['label' => 'Conduct (observes rules and regulations of establishment)', 'weight' => 5],
                'pt5' => ['label' => 'Perseverance and industriousness (shows initiative and interests in work over & above what is assigned)', 'weight' => 5],
                'pt6' => ['label' => 'Drives & Leadership (inquisitive and aggressive)', 'weight' => 5],
                'pt7' => ['label' => 'Mental maturity (effective & calm under pressure)', 'weight' => 5],
                'pt8' => ['label' => 'Sociability (can work harmoniously with other employees)', 'weight' => 5],
                'pt9' => ['label' => 'Reliability (trusted to be left alone to see or operate office equipment)', 'weight' => 5],
                'pt10' => ['label' => 'Possession of traits necessary for employment in this kind of work', 'weight' => 5],
            ],
        ];
    }

    /**
     * Flattened map of criterion key => criterion definition (label + weight).
     */
    public static function criteriaFlat(): array
    {
        $flat = [];
        foreach (self::criteria() as $items) {
            foreach ($items as $key => $def) {
                $flat[$key] = $def;
            }
        }
        return $flat;
    }

    /**
     * Computes the final 0-100% grade from per-criterion star ratings (1-5).
     * Each criterion contributes (stars / 5) * weight.
     */
    public static function computeGrade(array $ratings): float
    {
        $total = 0.0;
        foreach (self::criteriaFlat() as $key => $def) {
            $stars = (int)($ratings[$key] ?? 0);
            if ($stars < 1) {
                continue;
            }
            $stars = min(5, $stars);
            $total += ($stars / 5) * $def['weight'];
        }
        return round($total, 2);
    }

    /**
     * Ensures the evaluations table exists (older deployments may be missing it).
     */
    private function ensureTable(): void
    {
        if ($this->tableReady === true) {
            return;
        }

        $this->db->exec('CREATE TABLE IF NOT EXISTS evaluations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            enrollment_id INT NOT NULL UNIQUE,
            company_id INT NOT NULL,
            rating TINYINT NOT NULL,
            criteria_ratings TEXT NULL,
            final_grade DECIMAL(5,2) NULL,
            comments TEXT NOT NULL,
            certificate_file VARCHAR(255) NULL,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_eval_enrollment FOREIGN KEY (enrollment_id) REFERENCES ojt_enrollments(id) ON DELETE CASCADE,
            CONSTRAINT fk_eval_company FOREIGN KEY (company_id) REFERENCES partner_companies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB');

        $this->tableReady = true;
    }

    /**
     * Ensures the evaluations table has the extended columns for the
     * detailed criteria ratings, computed grade, and certificate file.
     */
    public function ensureDetailSupport(): void
    {
        if ($this->detailReady === true) {
            return;
        }

        $this->ensureTable();

        $columns = [
            'criteria_ratings' => 'ALTER TABLE evaluations ADD COLUMN criteria_ratings TEXT NULL AFTER rating',
            'final_grade' => 'ALTER TABLE evaluations ADD COLUMN final_grade DECIMAL(5,2) NULL AFTER criteria_ratings',
            'certificate_file' => 'ALTER TABLE evaluations ADD COLUMN certificate_file VARCHAR(255) NULL AFTER comments',
        ];

        foreach ($columns as $name => $sql) {
            $stmt = $this->db->query("SHOW COLUMNS FROM evaluations LIKE " . $this->db->quote($name));
            if (!$stmt->fetch()) {
                $this->db->exec($sql);
            }
        }

        $this->detailReady = true;
    }

    /**
     * Saves a detailed evaluation: per-criterion ratings, computed grade,
     * comments, and certificate of completion file path.
     */
    public function submitDetailed(int $enrollmentId, int $companyId, array $ratings, string $comments, ?string $certificateFile): void
    {
        $this->ensureDetailSupport();

        foreach (self::criteriaFlat() as $key => $def) {
            $stars = (int)($ratings[$key] ?? 0);
            if ($stars < 1 || $stars > 5) {
                throw new RuntimeException('Please rate every item from 1 to 5 stars.');
            }
        }
        if (!trim($comments)) {
            throw new RuntimeException('Evaluation comments are required.');
        }

        $grade = self::computeGrade($ratings);
        $overallStars = (int)round($grade / 20);
        $overallStars = max(1, min(5, $overallStars));
        $ratingsJson = json_encode($ratings);

        $existing = $this->byEnrollment($enrollmentId);
        $certToStore = $certificateFile ?? ($existing['certificate_file'] ?? null);
        if ($certToStore === null) {
            throw new RuntimeException('Certificate of completion is required.');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO evaluations (enrollment_id, company_id, rating, criteria_ratings, final_grade, comments, certificate_file)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), criteria_ratings = VALUES(criteria_ratings),
                final_grade = VALUES(final_grade), comments = VALUES(comments), certificate_file = VALUES(certificate_file),
                submitted_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$enrollmentId, $companyId, $overallStars, $ratingsJson, $grade, $comments, $certToStore]);
    }

    public function byEnrollment(int $enrollmentId): ?array
    {
        $this->ensureDetailSupport();
        $stmt = $this->db->prepare('SELECT * FROM evaluations WHERE enrollment_id = ?');
        $stmt->execute([$enrollmentId]);
        return $stmt->fetch() ?: null;
    }

    public function allWithDetails(): array
    {
        $this->ensureDetailSupport();
        $stmt = $this->db->query(
            'SELECT e.*, u.name AS student_name, s.student_no, s.course, s.year_level,
                    c.name AS company_name, en.start_date, en.end_date
             FROM evaluations e
             JOIN ojt_enrollments en ON en.id = e.enrollment_id
             JOIN students s ON s.id = en.student_id
             JOIN users u ON u.id = s.user_id
             JOIN partner_companies c ON c.id = e.company_id
             ORDER BY e.submitted_at DESC'
        );
        return $stmt->fetchAll();
    }

    public function byCoordinator(int $coordinatorId): array
    {
        $this->ensureDetailSupport();
        $stmt = $this->db->prepare(
            'SELECT e.*, u.name AS student_name, s.student_no, s.course, s.year_level,
                    c.name AS company_name, en.start_date, en.end_date
             FROM evaluations e
             JOIN ojt_enrollments en ON en.id = e.enrollment_id
             JOIN students s ON s.id = en.student_id
             JOIN users u ON u.id = s.user_id
             JOIN partner_companies c ON c.id = e.company_id
             WHERE s.coordinator_id = ?
             ORDER BY e.submitted_at DESC'
        );
        $stmt->execute([$coordinatorId]);
        return $stmt->fetchAll();
    }

    /**
     * Decodes the stored criteria_ratings JSON into an array.
     */
    public static function decodeRatings(?string $json): array
    {
        if (!$json) {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
