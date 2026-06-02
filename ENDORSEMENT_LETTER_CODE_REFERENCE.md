# Quick Reference: Endorsement Letter Implementation

## Installation

```bash
cd c:\xampp\htdocs\amaccmanagementsystem
composer update
```

## Complete Code Examples

### 1. Database Query (Single Optimized Query)

**File:** `models/Enrollment.php` - Method `findForEndorsement()`

```sql
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
```

### 2. PDF Generation (EndorsementLetter Class)

**File:** `models/EndorsementLetter.php`

```php
<?php
use Dompdf\Dompdf;

class EndorsementLetter {
    public function __construct(private PDO $db) {}

    public function generatePdfBuffer(int $studentId, int $enrollmentId): string {
        $data = $this->fetchEndorsementData($studentId, $enrollmentId);
        if (!$data) throw new Exception('Student enrollment data not found.');
        
        $html = $this->buildHtmlTemplate($data);
        
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return $dompdf->output();
    }
    
    private function fetchEndorsementData(int $studentId, int $enrollmentId): ?array {
        $stmt = $this->db->prepare('
            SELECT 
                s.id student_id, s.student_no, s.course, s.year_level,
                u.name student_name, u.email student_email,
                p.name program_name, p.required_hours program_required_hours,
                pc.id company_id, pc.name company_name, pc.address company_address,
                pc.contact_person, pc.contact_email company_email, pc.contact_number company_phone,
                oe.id enrollment_id, oe.academic_term, oe.term_start_date, oe.term_end_date,
                oe.start_date, oe.end_date, oe.required_hours enrollment_required_hours,
                coord_u.name coordinator_name, coord_u.email coordinator_email, c.department coordinator_dept
            FROM ojt_enrollments oe
            JOIN students s ON s.id = oe.student_id
            JOIN users u ON u.id = s.user_id
            LEFT JOIN programs p ON p.id = s.program_id
            JOIN partner_companies pc ON pc.id = oe.company_id
            LEFT JOIN users coord_u ON coord_u.id = s.coordinator_id
            LEFT JOIN coordinators c ON c.user_id = coord_u.id
            WHERE oe.id = ? AND oe.student_id = ?
            LIMIT 1
        ');
        $stmt->execute([$enrollmentId, $studentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    private function buildHtmlTemplate(array $data): string {
        $safe = static fn ($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        
        $currentDate = date('F d, Y');
        $academicTerm = $safe($data['academic_term'] ?? '');
        $requiredHours = (int)($data['enrollment_required_hours'] ?? $data['program_required_hours'] ?? 0);
        
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; padding: 40px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1f2937; }
        .header h1 { font-size: 16px; font-weight: 700; margin-bottom: 5px; }
        .header h2 { font-size: 14px; font-weight: 600; color: #374151; }
        .date-section { text-align: right; margin-bottom: 20px; }
        .body-content { text-align: justify; line-height: 1.8; }
        .body-content p { margin-bottom: 12px; }
        .requirements-list { list-style: none; margin-left: 30px; }
        .requirements-list li { margin-bottom: 8px; padding-left: 20px; position: relative; }
        .requirements-list li:before { content: "•"; position: absolute; left: 0; font-weight: bold; }
        .signature-block { margin-top: 35px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>AMA COMPUTER COLLEGE</h1>
        <h2>Recommendation / Endorsement Letter</h2>
        <p>Office of the OJT Department</p>
    </div>
    
    <div class="date-section">{{ CURRENT_DATE }}</div>
    
    <div class="body-content">
        <p><strong>{{ CONTACT_PERSON }}</strong><br>
        {{ COMPANY_NAME }}<br>
        {{ COMPANY_ADDRESS }}</p>
        
        <p>Dear {{ CONTACT_PERSON }},</p>
        
        <p>This is to formally endorse <strong>{{ STUDENT_NAME }}</strong>, Student ID <strong>{{ STUDENT_NO }}</strong>, 
        from <strong>{{ STUDENT_COURSE }}</strong> (Year {{ STUDENT_YEAR_LEVEL }}), for On-the-Job Training deployment 
        at <strong>{{ COMPANY_NAME }}</strong>.</p>
        
        <p>The student is enrolled for <strong>{{ ACADEMIC_TERM }}</strong> and is required to complete 
        <strong>{{ REQUIRED_HOURS }} hours</strong> of On-the-Job Training.</p>
        
        <p>In line with the objectives of providing our students with holistic, quality and relevant computer 
        knowledge education, we trust that the school and your company can collaborate closely in the student's training.</p>
        
        <p>In support of this collaboration, we respectfully request that the student:</p>
        
        <ul class="requirements-list">
            <li>be assigned to areas or given work assignments that are meaningful and will make him gain practical experience</li>
            <li>not be given personal and mental tasks that are unrelated to the discipline</li>
            <li>not be exposed to work assignments that are dangerous or expose him to risk</li>
            <li>be treated with a professional manner and all transactions conducted professionally</li>
            <li>work in compliance with professional dealings and engagements</li>
        </ul>
        
        <p>Thank you and we look forward to our continuing partnership.</p>
        
        <div class="signature-block">
            <p>Respectfully submitted,</p>
            <p style="margin-top: 30px;"><strong>{{ COORDINATOR_NAME }}</strong><br>
            OJT Coordinator<br>
            {{ COORDINATOR_EMAIL }}</p>
        </div>
    </div>
</body>
</html>
HTML;
        
        $replacements = [
            '{{ CURRENT_DATE }}' => $currentDate,
            '{{ CONTACT_PERSON }}' => $safe($data['contact_person'] ?? ''),
            '{{ COMPANY_NAME }}' => $safe($data['company_name'] ?? ''),
            '{{ COMPANY_ADDRESS }}' => $safe($data['company_address'] ?? ''),
            '{{ STUDENT_NAME }}' => $safe($data['student_name'] ?? ''),
            '{{ STUDENT_NO }}' => $safe($data['student_no'] ?? ''),
            '{{ STUDENT_COURSE }}' => $safe($data['course'] ?? ''),
            '{{ STUDENT_YEAR_LEVEL }}' => $safe($data['year_level'] ?? ''),
            '{{ ACADEMIC_TERM }}' => $academicTerm,
            '{{ REQUIRED_HOURS }}' => $requiredHours,
            '{{ COORDINATOR_NAME }}' => $safe($data['coordinator_name'] ?? ''),
            '{{ COORDINATOR_EMAIL }}' => $safe($data['coordinator_email'] ?? ''),
        ];
        
        foreach ($replacements as $placeholder => $value) {
            $html = str_replace($placeholder, $value, $html);
        }
        
        return $html;
    }
}
```

### 3. Controller Integration (forwardDeployment)

**File:** `controllers/CoordinatorController.php`

```php
public function forwardDeployment(): void {
    require_role('coordinator');
    $p = $this->post();
    try {
        $enrollment = (new Enrollment($this->db))->find((int)$p['enrollment_id']);
        if (!$enrollment) throw new RuntimeException('Enrollment not found.');
        
        $student = (new Student($this->db))->find((int)$enrollment['student_id']);
        if (!$student || (int)$student['coordinator_id'] !== (int)current_user()['id']) {
            throw new RuntimeException('Student does not belong to your coordination.');
        }
        
        $studentModel = new Student($this->db);
        if (!$studentModel->hasApprovedRequirements((int)$student['id'])) {
            throw new RuntimeException('Approve all five requirements before forwarding.');
        }
        
        $company = (new Company($this->db))->find((int)$enrollment['company_id']);
        if (!$company) throw new RuntimeException('Industry Partner not found.');
        
        // Generate PDF dynamically
        $endorsementLetter = new EndorsementLetter($this->db);
        $pdfBuffer = $endorsementLetter->generatePdfBuffer((int)$student['id'], (int)$enrollment['id']);
        
        // Update enrollment status
        $stmt = $this->db->prepare('UPDATE ojt_enrollments SET predeployment_status = "forwarded", 
                                    endorsement_file = ?, forwarded_at = NOW() WHERE id = ?');
        $stmt->execute(['(generated-pdf)', (int)$enrollment['id']]);
        
        // Prepare attachments
        $attachments = array_map(
            static fn ($path) => ['path' => $path],
            $studentModel->requirementFilePaths((int)$student['id'])
        );
        
        // Add PDF as string attachment
        $attachments[] = [
            'string' => $pdfBuffer,
            'name' => 'Endorsement_Letter.pdf',
            'type' => 'application/pdf'
        ];
        
        // Send email with PDF attached
        (new Email($this->db))->send(
            $company['contact_email'],
            'Student Deployment Documents Forwarded',
            'deployment_forwarded',
            'company_deployment',
            [
                'student' => $student,
                'company' => $company,
                'academicTerm' => $enrollment['academic_term'] ?? '',
                'termStartDate' => $enrollment['term_start_date'] ?? '',
                'termEndDate' => $enrollment['term_end_date'] ?? '',
                'requiredHours' => (int)$enrollment['required_hours'],
                'coordinator' => current_user(),
            ],
            $attachments
        );
        
        flash('success', 'Documents approved and Endorsement Letter generated and forwarded.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('index.php?r=coordinator_students');
}
```

### 4. Email String Attachment Support

**File:** `models/Email.php` - `send()` method

```php
foreach ($attachments as $attachment) {
    // Support for string attachments (dynamically generated PDFs)
    if (is_array($attachment) && isset($attachment['string'])) {
        $content = $attachment['string'];
        $name = $attachment['name'] ?? 'attachment';
        $type = $attachment['type'] ?? 'application/octet-stream';
        $mail->addStringAttachment($content, $name, PHPMailer::ENCODING_BASE64, $type);
    }
    // Standard file attachment handling
    else {
        $path = is_array($attachment) ? ($attachment['path'] ?? '') : (string)$attachment;
        $name = is_array($attachment) ? ($attachment['name'] ?? '') : '';
        $fullPath = str_starts_with($path, __DIR__) ? $path : dirname(__DIR__) . '/' . ltrim($path, '/\\');
        if ($path && is_file($fullPath)) {
            $mail->addAttachment($fullPath, $name ?: basename($fullPath));
        }
    }
}
```

## Usage Pattern

```php
// Generate PDF on demand (never saved to disk)
$letter = new EndorsementLetter($pdo);
$pdf = $letter->generatePdfBuffer($studentId, $enrollmentId);

// Attachment format for Email class
$attachments = [
    ['string' => $pdf, 'name' => 'Endorsement_Letter.pdf', 'type' => 'application/pdf'],
    ['path' => 'uploads/requirements/file.pdf'] // Traditional file attachment still works
];

// Send email with mixed attachments
(new Email($pdo))->send($recipient, $subject, $type, $template, $data, $attachments);
```

## Database Schema

```sql
-- ojt_enrollments table fields used:
ALTER TABLE ojt_enrollments 
    MODIFY endorsement_file VARCHAR(255) DEFAULT '(generated-pdf)',
    ADD forwarded_at DATETIME;

-- Table relationships:
ojt_enrollments (enrollment_id) → students (student_id)
students (user_id) → users (id for student name/email)
students (coordinator_id) → users (id for coordinator)
students (program_id) → programs (id)
ojt_enrollments (company_id) → partner_companies (id)
```

## Testing

```php
// Test PDF generation
$letter = new EndorsementLetter($pdo);
try {
    $pdf = $letter->generatePdfBuffer(1, 1); // student_id=1, enrollment_id=1
    echo "PDF size: " . strlen($pdf) . " bytes";
    // Save for inspection (optional)
    file_put_contents('/tmp/test.pdf', $pdf);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Email Log Entry

```sql
-- Check email_logs after forwarding:
SELECT * FROM email_logs 
WHERE type = 'company_deployment' 
AND status = 'sent' 
ORDER BY sent_at DESC 
LIMIT 1;
```

## Files Modified

```
✅ composer.json                                      - Added dompdf
✅ models/EndorsementLetter.php                       - NEW file
✅ models/Enrollment.php                              - Added findForEndorsement()
✅ models/Email.php                                   - String attachment support
✅ controllers/CoordinatorController.php              - PDF generation flow
✅ views/coordinator/my_students.php                  - Removed file upload
✅ ENDORSEMENT_LETTER_IMPLEMENTATION.md               - Documentation
```

---

**Implementation Status:** ✅ COMPLETE  
**Ready for Testing:** YES  
**Requires Composer Update:** YES `composer update`
