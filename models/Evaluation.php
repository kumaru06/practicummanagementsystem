<?php
class Evaluation
{
    public function __construct(private PDO $db) {}

    public function submit(int $enrollmentId, int $companyId, int $rating, string $comments): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new RuntimeException('Evaluation rating must be from 1 to 5.');
        }
        if (!trim($comments)) {
            throw new RuntimeException('Evaluation comments are required.');
        }
        $stmt = $this->db->prepare('INSERT INTO evaluations (enrollment_id, company_id, rating, comments) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), comments = VALUES(comments), submitted_at = CURRENT_TIMESTAMP');
        $stmt->execute([$enrollmentId, $companyId, $rating, $comments]);
    }

    public function byEnrollment(int $enrollmentId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM evaluations WHERE enrollment_id = ?');
        $stmt->execute([$enrollmentId]);
        return $stmt->fetch() ?: null;
    }

    public function allWithDetails(): array
    {
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
}
