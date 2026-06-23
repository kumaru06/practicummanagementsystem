<?php
class Student
{
    public function __construct(private PDO $db) {}

    public function create(int $userId, string $studentNo, string $course, string $yearLevel, string $corFile, int $coordinatorId, ?int $programId = null, string $section = '', ?string $birthdate = null): int
    {
        $stmt = $this->db->prepare('INSERT INTO students (user_id, student_no, program_id, course, year_level, section, birthdate, cor_file, coordinator_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $studentNo, $programId, $course, $yearLevel, $section, $birthdate, $corFile, $coordinatorId]);
        return (int)$this->db->lastInsertId();
    }

    public function allByCoordinator(int $coordinatorUserId): array
    {
        (new Company($this->db))->ensureMoaMouSupport();
        $stmt = $this->db->prepare('SELECT s.*, u.name, u.email, u.is_active, p.code program_code, p.required_hours program_required_hours, e.id enrollment_id, e.status deployment_status, e.predeployment_status, e.required_hours, e.orientation_datetime, e.orientation_notes, e.official_start_date, e.projected_end_date, COALESCE(SUM(CASE WHEN d.verification_status = "approved" THEN d.hours ELSE 0 END), 0) rendered_hours, pc.id company_id, pc.name company_name, pc.moa_mou_file company_moa_mou_file FROM students s JOIN users u ON u.id = s.user_id LEFT JOIN programs p ON p.id = s.program_id LEFT JOIN ojt_enrollments e ON e.student_id = s.id LEFT JOIN daily_time_records d ON d.student_id = s.id LEFT JOIN partner_companies pc ON pc.id = e.company_id WHERE s.coordinator_id = ? GROUP BY s.id, u.id, p.id, e.id, pc.id ORDER BY u.name');
        $stmt->execute([$coordinatorUserId]);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query('SELECT s.*, u.name, u.email, c.name coordinator_name FROM students s JOIN users u ON u.id = s.user_id LEFT JOIN users c ON c.id = s.coordinator_id ORDER BY u.name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT s.*, u.name, u.email, u.is_active, c.name coordinator_name, c.email coordinator_email FROM students s JOIN users u ON u.id = s.user_id LEFT JOIN users c ON c.id = s.coordinator_id WHERE s.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function existsByStudentNo(string $studentNo): bool
    {
        $studentNo = trim($studentNo);
        if ($studentNo === '') {
            return false;
        }

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM students WHERE student_no = ?');
        $stmt->execute([$studentNo]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function belongsToCoordinator(int $studentId, int $coordinatorUserId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM students WHERE id = ? AND coordinator_id = ?');
        $stmt->execute([$studentId, $coordinatorUserId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function findByUser(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT s.*, u.name, u.email, c.name coordinator_name, c.email coordinator_email FROM students s JOIN users u ON u.id = s.user_id LEFT JOIN users c ON c.id = s.coordinator_id WHERE s.user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function countByCoordinator(int $coordinatorUserId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM students WHERE coordinator_id = ?');
        $stmt->execute([$coordinatorUserId]);
        return (int)$stmt->fetchColumn();
    }

    public function updateProfile(int $studentId, array $data, ?string $photoFile): void
    {
        $stmt = $this->db->prepare('UPDATE students SET address = ?, contact_number = ?, emergency_contact_name = ?, emergency_contact_number = ?, guardian_name = ?, guardian_contact = ?, year_level = ?, section = ?, photo_file = COALESCE(?, photo_file), profile_completed = 1 WHERE id = ?');
        $stmt->execute([
            trim($data['address'] ?? ''),
            trim($data['contact_number'] ?? ''),
            trim($data['emergency_contact_name'] ?? ''),
            trim($data['emergency_contact_number'] ?? ''),
            trim($data['guardian_name'] ?? ''),
            trim($data['guardian_contact'] ?? ''),
            trim($data['year_level'] ?? ''),
            trim($data['section'] ?? ''),
            $photoFile,
            $studentId,
        ]);
    }

    public function requirementDefinitions(): array
    {
        return [
            'guardian_consent' => ['name' => 'Parent/Guardian Consent Form', 'notes' => 'Download the template, then submit the notarized file with the guardian’s valid ID.'],
            'philhealth' => ['name' => 'PhilHealth Card / Document', 'notes' => 'Upload scan or photo.'],
            'vaccine_card' => ['name' => 'Vaccine Card', 'notes' => 'Upload scan or photo.'],
            'guardian_id' => ['name' => "Guardian's Valid ID", 'notes' => 'Upload scan or photo.'],
            'cor' => ['name' => 'Certificate of Registration (COR)', 'notes' => 'Upload current term COR.'],
        ];
    }

    public function normalizePredeploymentStatus(?string $status): string
    {
        $status = trim((string)$status);
        return in_array($status, ['not_submitted', 'submitted', 'approved', 'needs_revision', 'forwarded', 'accepted', 'orientation_scheduled', 'orientation_completed'], true)
            ? $status
            : 'not_submitted';
    }

    public function effectivePredeploymentStatus(int $studentId, ?string $currentStatus = null, ?array $requirements = null): string
    {
        $currentStatus = $this->normalizePredeploymentStatus($currentStatus);
        if (in_array($currentStatus, ['forwarded', 'accepted', 'orientation_scheduled', 'orientation_completed'], true)) {
            return $currentStatus;
        }

        $requirements ??= $this->requirements($studentId);
        $hasRejected = false;
        $allApproved = true;
        foreach ($requirements as $requirement) {
            $hasFile = !empty($requirement['file_path']);
            $status = trim((string)($requirement['status'] ?? 'pending'));
            if ($hasFile && $status === 'rejected') {
                $hasRejected = true;
            }
            if (!$hasFile || $status !== 'approved') {
                $allApproved = false;
            }
        }
        if ($allApproved) {
            return 'approved';
        }
        if ($hasRejected) {
            return 'needs_revision';
        }
        return $currentStatus;
    }

    public function requirements(int $studentId): array
    {
        $defs = $this->requirementDefinitions();
        $stmt = $this->db->prepare('SELECT * FROM student_requirements WHERE student_id = ?');
        $stmt->execute([$studentId]);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[$row['requirement_key']] = $row;
        }
        foreach ($defs as $key => $def) {
            if (!isset($rows[$key])) {
                $rows[$key] = ['requirement_key' => $key, 'requirement_name' => $def['name'], 'notes' => $def['notes'], 'file_path' => null, 'status' => 'pending'];
            } else {
                $rows[$key]['review_notes'] = $rows[$key]['notes'] ?? '';
                $rows[$key]['notes'] = $def['notes'];
            }
        }
        return $rows;
    }

    public function saveRequirement(int $studentId, string $key, string $filePath): void
    {
        $defs = $this->requirementDefinitions();
        if (!isset($defs[$key])) {
            throw new RuntimeException('Invalid requirement.');
        }
        $existingStmt = $this->db->prepare('SELECT status FROM student_requirements WHERE student_id = ? AND requirement_key = ? LIMIT 1');
        $existingStmt->execute([$studentId, $key]);
        $existing = $existingStmt->fetch() ?: [];
        $stmt = $this->db->prepare('INSERT INTO student_requirements (student_id, requirement_key, requirement_name, file_path, status, uploaded_at) VALUES (?, ?, ?, ?, "uploaded", NOW()) ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), status = "uploaded", uploaded_at = NOW()');
        $stmt->execute([$studentId, $key, $defs[$key]['name'], $filePath]);
        $enrollment = (new Enrollment($this->db))->detailsByStudent($studentId);
        if (($existing['status'] ?? '') === 'rejected' || ($enrollment['predeployment_status'] ?? '') === 'needs_revision') {
            $nextStatus = $this->hasRejectedRequirements($studentId)
                ? 'needs_revision'
                : ($this->hasCompleteRequirements($studentId) ? 'submitted' : 'not_submitted');
            (new Enrollment($this->db))->setPredeploymentStatus($studentId, $nextStatus);
        }
    }

    public function reviewRequirement(int $studentId, string $key, string $status, string $notes = ''): void
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new RuntimeException('Invalid review status.');
        }
        $defs = $this->requirementDefinitions();
        if (!isset($defs[$key])) {
            throw new RuntimeException('Invalid requirement.');
        }
        $enrollment = (new Enrollment($this->db))->detailsByStudent($studentId);
        $predeploymentStatus = $this->normalizePredeploymentStatus($enrollment['predeployment_status'] ?? 'not_submitted');
        if (!in_array($predeploymentStatus, ['submitted', 'needs_revision', 'approved'], true)) {
            throw new RuntimeException('Student must submit requirements for review before coordinator approval or rejection.');
        }
        $stmt = $this->db->prepare('UPDATE student_requirements SET status = ?, notes = ?, reviewed_at = NOW() WHERE student_id = ? AND requirement_key = ? AND file_path IS NOT NULL');
        $stmt->execute([$status, $notes, $studentId, $key]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Requirement file is not available for review.');
        }
    }

    public function hasCompleteRequirements(int $studentId): bool
    {
        foreach ($this->requirements($studentId) as $req) {
            if (empty($req['file_path'])) {
                return false;
            }
        }
        return true;
    }

    public function hasRejectedRequirements(int $studentId): bool
    {
        foreach ($this->requirements($studentId) as $req) {
            if (!empty($req['file_path']) && ($req['status'] ?? '') === 'rejected') {
                return true;
            }
        }
        return false;
    }

    public function canUploadRequirement(int $studentId, string $key): bool
    {
        $requirements = $this->requirements($studentId);
        if (!isset($requirements[$key])) {
            return false;
        }
        $requirement = $requirements[$key];
        $status = $requirement['status'] ?? 'pending';
        $hasFile = !empty($requirement['file_path']);
        $enrollment = (new Enrollment($this->db))->detailsByStudent($studentId);
        if (!$enrollment) {
            return false;
        }
        $predeploymentStatus = $this->normalizePredeploymentStatus($enrollment['predeployment_status'] ?? 'not_submitted');
        if (in_array($predeploymentStatus, ['approved', 'forwarded', 'accepted', 'orientation_scheduled', 'orientation_completed'], true)) {
            return false;
        }
        if ($status === 'rejected') {
            return true;
        }
        if ($hasFile || $predeploymentStatus === 'submitted') {
            return false;
        }
        return true;
    }

    public function requirementUploadMessage(int $studentId, string $key): string
    {
        $requirements = $this->requirements($studentId);
        $requirement = $requirements[$key] ?? [];
        $status = $requirement['status'] ?? 'pending';
        $hasFile = !empty($requirement['file_path']);
        $enrollment = (new Enrollment($this->db))->detailsByStudent($studentId);
        if (!$enrollment) return 'Enrollment required';
        $predeploymentStatus = $this->normalizePredeploymentStatus($enrollment['predeployment_status'] ?? 'not_submitted');
        if ($status === 'approved') return 'Approved';
        if ($status === 'uploaded' && $hasFile) return 'Awaiting review';
        if ($status === 'rejected') return 'Replace the rejected file';
        if (in_array($predeploymentStatus, ['approved', 'forwarded', 'accepted', 'orientation_scheduled', 'orientation_completed'], true)) return 'Locked';
        if ($predeploymentStatus === 'submitted') return 'Under review';
        if ($hasFile) return 'Already uploaded';
        return 'Ready to upload';
    }

    public function hasApprovedRequirements(int $studentId): bool
    {
        foreach ($this->requirements($studentId) as $req) {
            if (empty($req['file_path']) || ($req['status'] ?? '') !== 'approved') {
                return false;
            }
        }
        return true;
    }

    public function requirementFilePaths(int $studentId): array
    {
        return array_values(array_filter(array_map(static fn ($req) => $req['file_path'] ?? null, $this->requirements($studentId))));
    }
}
