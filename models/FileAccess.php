<?php

/**
 * Central authorization for serving uploaded files through serve.php.
 *
 * Access model (mirrors the controllers):
 *  - admin      : every uploaded file
 *  - student    : only files belonging to their own student record
 *  - coordinator: only files of students assigned to them (+ scoped MOA/MOU, own signature)
 *  - partner    : only files of students deployed to their company (+ own MOA/MOU)
 *  - profiles/  : any authenticated user (avatars are shown throughout the app)
 * Anything not explicitly matched is denied.
 */
class FileAccess
{
    public static function canView(string $relPath, ?array $user): bool
    {
        if (!$user) {
            return false;
        }
        $role = (string)($user['role'] ?? '');
        $uid = (int)($user['id'] ?? 0);
        if ($uid <= 0) {
            return false;
        }

        // Admin can view every uploaded file.
        if ($role === 'admin') {
            return true;
        }

        $rel = str_replace('\\', '/', $relPath);

        // Avatars/profile photos are rendered across the app for any signed-in user.
        if (str_starts_with($rel, 'uploads/profiles/')) {
            return true;
        }

        $db = db();

        // requirements/{studentId}/... and proof/{studentId}/... embed the student id in the path.
        if (preg_match('#^uploads/(?:requirements|proof)/(\d+)/#', $rel, $m)) {
            return self::userCanAccessStudent($db, (int)$m[1], $role, $uid);
        }

        // Files stored with a random name: resolve the owning student via the DB.
        $studentId = self::studentIdForPath($db, $rel);
        if ($studentId !== null) {
            return self::userCanAccessStudent($db, $studentId, $role, $uid);
        }

        // Coordinator signature image (owning coordinator only; admin handled above).
        $signatureOwner = self::scalar($db, 'SELECT user_id FROM coordinators WHERE signature_file = ? LIMIT 1', [$rel]);
        if ($signatureOwner !== null) {
            return $role === 'coordinator' && $signatureOwner === $uid;
        }

        // Company MOA/MOU: scoped to coordinator's students / enrollable partners; partner sees its own.
        $companyId = self::scalar($db, 'SELECT id FROM partner_companies WHERE moa_mou_file = ? LIMIT 1', [$rel]);
        if ($companyId !== null) {
            if ($role === 'coordinator') {
                return (new Company($db))->coordinatorCanAccessMoa($uid, (int)$companyId);
            }
            $companyOwner = self::scalar($db, 'SELECT user_id FROM partner_companies WHERE id = ? LIMIT 1', [(int)$companyId]);

            return $role === 'partner' && $companyOwner === $uid;
        }

        // Default deny.
        return false;
    }

    private static function userCanAccessStudent(PDO $db, int $studentId, string $role, int $uid): bool
    {
        if ($studentId <= 0) {
            return false;
        }
        return match ($role) {
            'student' => self::exists($db, 'SELECT 1 FROM students WHERE id = ? AND user_id = ? LIMIT 1', [$studentId, $uid]),
            'coordinator' => self::exists($db, 'SELECT 1 FROM students WHERE id = ? AND coordinator_id = ? LIMIT 1', [$studentId, $uid]),
            'partner' => self::exists(
                $db,
                'SELECT 1 FROM ojt_enrollments e
                 JOIN partner_companies pc ON pc.id = e.company_id
                 WHERE e.student_id = ? AND pc.user_id = ?
                   AND (
                     e.status = "completed"
                     OR e.predeployment_status IN ("forwarded", "accepted", "orientation_scheduled", "orientation_completed")
                   )
                 LIMIT 1',
                [$studentId, $uid]
            ),
            default => false,
        };
    }

    private static function studentIdForPath(PDO $db, string $rel): ?int
    {
        // Registration/enrollment COR stored on the student record.
        $id = self::scalar($db, 'SELECT id FROM students WHERE cor_file = ? LIMIT 1', [$rel]);
        if ($id !== null) {
            return $id;
        }
        // Weekly report PDF.
        $id = self::scalar($db, 'SELECT student_id FROM weekly_reports WHERE file_path = ? LIMIT 1', [$rel]);
        if ($id !== null) {
            return $id;
        }
        // Completion certificate attached to the final evaluation.
        $id = self::scalar(
            $db,
            'SELECT e.student_id FROM evaluations ev
             JOIN ojt_enrollments e ON e.id = ev.enrollment_id
             WHERE ev.certificate_file = ? LIMIT 1',
            [$rel]
        );
        return $id;
    }

    private static function exists(PDO $db, string $sql, array $params): bool
    {
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }

    private static function scalar(PDO $db, string $sql, array $params): ?int
    {
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $value = $stmt->fetchColumn();
            return $value === false ? null : (int)$value;
        } catch (Throwable) {
            return null;
        }
    }
}
