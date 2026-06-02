<?php

use Dompdf\Dompdf;

/**
 * EndorsementLetter class handles the generation of student endorsement letters as PDFs
 * using dynamic data from the database without saving physical files to the server.
 * 
 * The generated PDF can be streamed directly to email attachments or displayed in the browser.
 */
class EndorsementLetter
{
    public function __construct(private PDO $db) {}

    /**
     * Generates a complete endorsement letter PDF as a string buffer (not saved to disk).
     * 
     * @param int $studentId The student ID
     * @param int $enrollmentId The OJT enrollment ID
     * @return string PDF content as binary string
     * @throws Exception If data cannot be fetched or PDF generation fails
     */
    public function generatePdfBuffer(int $studentId, int $enrollmentId): string
    {
        // Fetch all required data with a single comprehensive query
        $data = $this->fetchEndorsementData($studentId, $enrollmentId);
        
        if (!$data) {
            throw new Exception('Student enrollment data not found.');
        }

        // Build the HTML template
        $html = $this->buildHtmlTemplate($data);

        // Generate PDF using Dompdf
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Fetches all necessary data for the endorsement letter using a single optimized query.
     * 
     * Data includes:
     * - Student info (name, student number, course, year level)
     * - Company/Partner info (name, address, contact person, contact email)
     * - Program info (required hours, term)
     * - Coordinator/Signatory info (name, email, title)
     * - Enrollment info (academic term, dates, required hours)
     * 
     * @param int $studentId The student ID
     * @param int $enrollmentId The OJT enrollment ID
     * @return ?array Associative array with all endorsement data
     */
    private function fetchEndorsementData(int $studentId, int $enrollmentId): ?array
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
            WHERE oe.id = ? AND oe.student_id = ?
            LIMIT 1
        ');
        
        $stmt->execute([$enrollmentId, $studentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Builds the complete HTML template for the endorsement letter.
     * 
     * The template matches the official layout with:
     * - School header
     * - Current date
     * - Company contact information
     * - Student and enrollment details
     * - Professional closing with signatory
     * 
     * @param array $data The endorsement data from database
     * @return string Complete HTML string ready for PDF conversion
     */
    private function buildHtmlTemplate(array $data): string
    {
        // Safe HTML escaping function
        $safe = static fn ($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

        // Format dates in professional format
        $currentDate = date('F d, Y');
        $academicTerm = $safe($data['academic_term'] ?? '');
        $termDatesLabel = '';
        
        if (!empty($data['term_start_date']) && !empty($data['term_end_date'])) {
            $startDate = DateTime::createFromFormat('Y-m-d', $data['term_start_date']);
            $endDate = DateTime::createFromFormat('Y-m-d', $data['term_end_date']);
            if ($startDate && $endDate) {
                $termDatesLabel = $startDate->format('F d, Y') . ' to ' . $endDate->format('F d, Y');
            }
        }

        // Calculate required hours or use program default
        $requiredHours = (int)($data['enrollment_required_hours'] ?? $data['program_required_hours'] ?? 0);

        // HTML template with Times New Roman font matching the official template
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Endorsement Letter</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            padding: 40px 60px;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        
        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 5px;
            color: #000;
            letter-spacing: 0.5px;
        }
        
        .header h2 {
            font-size: 12pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 11pt;
            color: #333;
        }
        
        .date-section {
            text-align: right;
            margin-bottom: 20px;
            font-size: 11pt;
            font-style: italic;
        }
        
        .recipient {
            margin-bottom: 20px;
            font-size: 11pt;
            line-height: 1.4;
        }
        
        .recipient-name {
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .recipient-details {
            margin: 2px 0;
        }
        
        .salutation {
            margin-bottom: 12px;
            font-weight: bold;
            font-size: 11pt;
        }
        
        .body-content {
            line-height: 1.6;
            font-size: 11pt;
        }
        
        .body-content p {
            margin-bottom: 10px;
            text-align: justify;
        }
        
        .body-content strong {
            font-weight: bold;
            color: #000;
        }
        
        .requirements-list {
            list-style: disc;
            margin-left: 40px;
            margin-top: 10px;
            margin-bottom: 12px;
        }
        
        .requirements-list li {
            margin-bottom: 6px;
            line-height: 1.5;
            font-size: 11pt;
        }
        
        .closing-text {
            margin-top: 20px;
            margin-bottom: 30px;
            font-size: 11pt;
        }
        
        .signature-block {
            margin-top: 40px;
            font-size: 11pt;
        }
        
        .signature-block p {
            margin-bottom: 2px;
        }
        
        .coordinator-name {
            font-weight: bold;
            margin-top: 5px;
            margin-bottom: 2px;
        }
        
        .coordinator-title {
            font-size: 11pt;
        }
        
        .coordinator-email {
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>AMA COMPUTER COLLEGE</h1>
        <h2>Recommendation / Endorsement Letter</h2>
        <p>Office of the OJT Department</p>
    </div>
    
    <div class="date-section">
        {{ CURRENT_DATE }}
    </div>
    
    <div class="recipient">
        <div class="recipient-name">{{ CONTACT_PERSON }}</div>
        <div class="recipient-details">{{ COMPANY_POSITION }}</div>
        <div class="recipient-details">{{ COMPANY_NAME }}</div>
        <div class="recipient-details">{{ COMPANY_ADDRESS }}</div>
    </div>
    
    <div class="salutation">Dear {{ CONTACT_PERSON }},</div>
    
    <div class="body-content">
        <p>This is to formally endorse <strong>{{ STUDENT_NAME }}</strong>, Student ID <strong>{{ STUDENT_NO }}</strong>, 
        from <strong>{{ STUDENT_COURSE }}</strong> ({{ STUDENT_YEAR_LEVEL }}), for On-the-Job Training deployment 
        at <strong>{{ COMPANY_NAME }}</strong>.</p>
        
        <p>The student is enrolled for <strong>{{ ACADEMIC_TERM }}</strong>{% if TERM_DATES %} ({{ TERM_DATES }}){% endif %} 
        and is required to complete <strong>{{ REQUIRED_HOURS }} hours</strong> of On-the-Job Training. 
        The official OJT start date and projected end date will be confirmed by your company after the student's orientation.</p>
        
        <p>In line with our objective of providing our students with a holistic, quality and relevant computer-based education in all disciplines, we strongly emphasized a discipline-based training environment appropriate to the rank of each and are given the best training after having finished the theoretical requirements in school.</p>
        
        <p>It is in this context that the <strong>AMA COMPUTER COLLEGE</strong> hereby endorse <strong>{{ STUDENT_NAME }}</strong>, student of <strong>{{ STUDENT_COURSE }}</strong> program to complete the required hours in your company in view to complete his curriculum.</p>
        
        <p>In support of this collaboration, and to enable the student to maximize his time and learning with your company and ensure his safety as well, may we request that the student:</p>
        
        <ul class="requirements-list">
            <li>be assigned to areas or given work assignments that are meaningful and will make him gain practical experience in his field of specialization</li>
            <li>not be given personal and mental tasks that are unrelated to the discipline</li>
            <li>not be exposed to work assignments that are dangerous or will expose him to risk or harm by allowing him to observe and be fully compliant with the OSHA standards and all safety regulations of your company</li>
            <li>be treated with in a professional manner and all transactions and interactions between the school and your company be conducted in a professional and ethical manner</li>
            <li>work dealings and engagements</li>
        </ul>
        
        <p>Thank you and we look forward to our continuing partnership in the development of our students and once-to-be professionals.</p>
    </div>
    
    <div class="closing">
        <p class="closing-text">Respectfully submitted,</p>
        
        <div class="closing-text">Respectfully submitted,</div>
        
        <div class="signature-block">
            <p class="coordinator-name">{{ COORDINATOR_NAME }}</p>
            <p class="coordinator-title">{{ COORDINATOR_TITLE }}</p>
            <p class="coordinator-email">{{ COORDINATOR_EMAIL }}</p>
        </div>
    </div>
</body>
</html>
HTML;

        // Replace all placeholders with actual data
        $replacements = [
            '{{ CURRENT_DATE }}' => $currentDate,
            '{{ CONTACT_PERSON }}' => $safe($data['contact_person'] ?? 'Industry Partner'),
            '{{ COMPANY_POSITION }}' => $safe(ucfirst(strtolower($data['contact_person'] ?? 'Contact Person'))),
            '{{ COMPANY_NAME }}' => $safe($data['company_name'] ?? ''),
            '{{ COMPANY_ADDRESS }}' => $safe($data['company_address'] ?? ''),
            '{{ STUDENT_NAME }}' => $safe($data['student_name'] ?? ''),
            '{{ STUDENT_NO }}' => $safe($data['student_no'] ?? ''),
            '{{ STUDENT_COURSE }}' => $safe($data['course'] ?? ''),
            '{{ STUDENT_YEAR_LEVEL }}' => $safe($data['year_level'] ?? ''),
            '{{ ACADEMIC_TERM }}' => $academicTerm,
            '{% if TERM_DATES %} ({{ TERM_DATES }}){% endif %}' => !empty($termDatesLabel) ? " ({$termDatesLabel})" : '',
            '{{ REQUIRED_HOURS }}' => $requiredHours,
            '{{ COORDINATOR_NAME }}' => $safe($data['coordinator_name'] ?? 'OJT Coordinator'),
            '{{ COORDINATOR_TITLE }}' => 'OJT Coordinator',
            '{{ COORDINATOR_EMAIL }}' => $safe($data['coordinator_email'] ?? ''),
        ];

        foreach ($replacements as $placeholder => $value) {
            $html = str_replace($placeholder, $value, $html);
        }

        return $html;
    }
}
