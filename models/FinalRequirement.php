<?php
class FinalRequirement
{
    public function __construct(private PDO $db) {}

    /**
     * Document sections that make up the post-OJT final requirement.
     */
    public const SECTIONS = [
        'job_description' => [
            'name' => 'Job Description',
            'description' => 'Detailed explanation of your tasks and responsibilities.',
        ],
        'company_profile' => [
            'name' => 'Company Profile',
            'description' => 'Background information about the company.',
        ],
        'personal_observation' => [
            'name' => 'Personal Observation',
            'description' => 'Your learnings, insights and observations during OJT.',
        ],
    ];

    /**
     * Evaluation sections that students can complete.
     */
    public const EVALUATION_SECTIONS = [
        'industry_partner' => [
            'name' => 'Host Training Establishment Evaluation',
            'description' => 'Evaluate your OJT experience by rating your Host Training Establishment and OJT Supervisor.',
        ],
        'coordinator' => [
            'name' => 'OJT Coordinator Evaluation', 
            'description' => 'Evaluate the support and guidance provided by your OJT Coordinator.',
        ],
    ];

    /**
     * Sub-fields that make up the Personal Observation document.
     * Column name => [label, description, placeholder].
     */
    public const PERSONAL_OBSERVATION_FIELDS = [
        'po_facilities' => ['Facilities', 'Describe the facilities of the Company', 'Write your observations here...'],
        'po_services' => ['Services', 'Describe the services offered by the Company', 'Write your observations here...'],
        'po_employee' => ['Employee', 'Describe the employees of the Company', 'Write your observations here...'],
        'po_management' => ['Management', 'Describe the management of the Company', 'Write your observations here...'],
        'po_organization' => ['Organization', 'Describe the Company as an organization', 'Write your observations here...'],
        'po_recommendation' => ['Recommendation', 'The recommendation focused on how the OJT Program was implemented by the company.', 'Write your recommendation here...'],
    ];

    public function getByStudent(int $studentId): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT * FROM student_final_requirements WHERE student_id = ?');
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
        $stmt = $this->db->prepare("SELECT * FROM student_final_requirements WHERE student_id IN ($placeholders)");
        $stmt->execute($ids);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[(int)$row['student_id']] = $row;
        }
        return $rows;
    }

    public function statusFor(int $studentId, string $section): string
    {
        $row = $this->getByStudent($studentId);
        $column = $section . '_status';
        $status = (string)($row[$column] ?? 'pending');
        return $status !== '' ? $status : 'pending';
    }

    /**
     * @return array{submitted:int,total:int,label:string,class:string}
     */
    public function overallSummary(array $row): array
    {
        $total = count(self::SECTIONS);
        $submitted = 0;
        foreach (array_keys(self::SECTIONS) as $section) {
            if (($row[$section . '_status'] ?? 'pending') === 'submitted') {
                $submitted++;
            }
        }
        if ($submitted === 0) {
            return ['submitted' => 0, 'total' => $total, 'label' => 'Not started', 'class' => 'not_submitted'];
        }
        if ($submitted < $total) {
            return ['submitted' => $submitted, 'total' => $total, 'label' => $submitted . '/' . $total . ' submitted', 'class' => 'pending'];
        }
        return ['submitted' => $submitted, 'total' => $total, 'label' => 'Complete', 'class' => 'submitted'];
    }

    public function saveJobDescription(int $studentId, string $positionHeld, string $jobDescription): void
    {
        $positionHeld = trim($positionHeld);
        $jobDescription = trim($jobDescription);
        if ($positionHeld === '') {
            throw new RuntimeException('Position held is required.');
        }
        if ($jobDescription === '') {
            throw new RuntimeException('Job description is required.');
        }
        $this->upsert($studentId, [
            'position_held' => mb_substr($positionHeld, 0, 255),
            'job_description' => mb_substr($jobDescription, 0, 2000),
            'job_description_status' => 'submitted',
        ]);
    }

    public function saveCompanyProfile(int $studentId, string $history, string $description, string $mission, string $vision): void
    {
        $history = trim($history);
        $description = trim($description);
        $mission = trim($mission);
        $vision = trim($vision);
        if ($history === '' || $description === '' || $mission === '' || $vision === '') {
            throw new RuntimeException('All company profile fields are required.');
        }
        $this->upsert($studentId, [
            'company_history' => mb_substr($history, 0, 2000),
            'company_description' => mb_substr($description, 0, 2000),
            'company_mission' => mb_substr($mission, 0, 2000),
            'company_vision' => mb_substr($vision, 0, 2000),
            'company_profile_status' => 'submitted',
        ]);
    }

    /**
     * @param array<string,string> $fields keyed by personal observation column name
     */
    public function savePersonalObservation(int $studentId, array $fields): void
    {
        $clean = [];
        foreach (array_keys(self::PERSONAL_OBSERVATION_FIELDS) as $column) {
            $value = trim((string)($fields[$column] ?? ''));
            if ($value === '') {
                throw new RuntimeException('All personal observation fields are required.');
            }
            $clean[$column] = mb_substr($value, 0, 2000);
        }
        $clean['personal_observation_status'] = 'submitted';
        $this->upsert($studentId, $clean);
    }

    /**
     * Insert or update the given columns for a student row.
     *
     * @param array<string,string> $fields
     */
    private function upsert(int $studentId, array $fields): void
    {
        $this->ensureTable();
        $columns = array_keys($fields);
        $insertCols = array_merge(['student_id'], $columns);
        $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
        $updates = implode(', ', array_map(static fn ($c) => "$c = VALUES($c)", $columns));
        $sql = 'INSERT INTO student_final_requirements (' . implode(', ', $insertCols) . ', updated_at) '
            . 'VALUES (' . $placeholders . ', NOW()) '
            . 'ON DUPLICATE KEY UPDATE ' . $updates . ', updated_at = NOW()';
        $values = array_merge([$studentId], array_values($fields));
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
    }

    private function ensureTable(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS student_final_requirements (
            student_id INT NOT NULL PRIMARY KEY,
            position_held VARCHAR(255) NULL,
            job_description TEXT NULL,
            job_description_status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            company_history TEXT NULL,
            company_description TEXT NULL,
            company_mission TEXT NULL,
            company_vision TEXT NULL,
            company_profile_status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            personal_observation TEXT NULL,
            po_facilities TEXT NULL,
            po_services TEXT NULL,
            po_employee TEXT NULL,
            po_management TEXT NULL,
            po_organization TEXT NULL,
            po_recommendation TEXT NULL,
            personal_observation_status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_final_req_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        ) ENGINE=InnoDB');
        $this->ensureColumns();
    }

    /**
     * Add any newer columns to tables created before they existed.
     */
    private function ensureColumns(): void
    {
        try {
            $stmt = $this->db->query('SHOW COLUMNS FROM student_final_requirements');
            $existing = array_column($stmt->fetchAll(), 'Field');
        } catch (Throwable) {
            return;
        }
        $needed = [
            'po_facilities' => 'TEXT NULL',
            'po_services' => 'TEXT NULL',
            'po_employee' => 'TEXT NULL',
            'po_management' => 'TEXT NULL',
            'po_organization' => 'TEXT NULL',
            'po_recommendation' => 'TEXT NULL',
        ];
        foreach ($needed as $column => $definition) {
            if (!in_array($column, $existing, true)) {
                $this->db->exec("ALTER TABLE student_final_requirements ADD COLUMN $column $definition");
            }
        }
    }
}
