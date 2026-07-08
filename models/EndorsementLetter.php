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

        // Use a writable temp/font directory inside the project so embedded
        // images render on shared hosting where the system temp dir is locked down.
        $projectRoot = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
        $tempDir = $projectRoot . '/uploads/dompdf_temp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $options = new \Dompdf\Options();
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('chroot', $projectRoot);
        if (is_dir($tempDir) && is_writable($tempDir)) {
            $options->set('tempDir', $tempDir);
            $options->set('fontDir', $tempDir);
            $options->set('fontCache', $tempDir);
        }

        // Generate PDF using Dompdf
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->getOptions()->setIsFontSubsettingEnabled(true);
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
                c.department coordinator_dept,
                c.signature_file coordinator_signature
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
    <title>Endorsement Letter</title>
    <style>
        @page {
            margin: 0;
        }
        * {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #000;
            padding: 60px 70px 50px 70px;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        .header-table {
            margin: 0 auto;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0;
        }
        .header-logo {
            padding-right: 10px;
        }
        .header-logo img {
            width: 50px;
            height: auto;
        }
        .header-text {
            text-align: center;
        }
        .header-text h1 {
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .header-text h2 {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .header-text p {
            font-size: 9pt;
            color: #333;
        }
        .date-section {
            text-align: right;
            margin-bottom: 10px;
            font-style: italic;
        }
        .recipient {
            margin-bottom: 10px;
            line-height: 1.5;
        }
        .salutation {
            margin-bottom: 8px;
            font-weight: bold;
        }
        .body-content p {
            margin-bottom: 5px;
            text-align: justify;
        }
        .req-list {
            list-style: disc;
            margin: 4px 0 4px 25px;
        }
        .req-list li {
            margin-bottom: 2px;
            line-height: 1.25;
            font-size: 9pt;
        }
        .closing {
            margin-top: 12px;
        }
        .sig-block {
            margin-top: 20px;
        }
        .sig-block .signature {
            display: block;
            height: 80px;
            width: auto;
            margin-bottom: -25px;
        }
        .sig-block .name {
            font-weight: bold;
        }
        .sig-block .email {
            font-size: 9pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    <img src="{{ LOGO_BASE64 }}" alt="AMA Logo">
                </td>
                <td class="header-text">
                    <h1>AMA COMPUTER COLLEGE</h1>
                    <h2>Recommendation / Endorsement Letter</h2>
                    <p>Office of the OJT Department</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="date-section">{{ CURRENT_DATE }}</div>

    <div class="recipient">
        {{ CONTACT_PERSON }}<br>
        {{ COMPANY_NAME }}<br>
        {{ COMPANY_ADDRESS }}
    </div>

    <div class="salutation">Dear {{ CONTACT_PERSON }},</div>

    <div class="body-content">
        <p>This is to formally endorse <strong>{{ STUDENT_NAME }}</strong>, Student ID <strong>{{ STUDENT_NO }}</strong>, from <strong>{{ STUDENT_COURSE }}</strong> ({{ STUDENT_YEAR_LEVEL }}), for On-the-Job Training deployment at <strong>{{ COMPANY_NAME }}</strong>.</p>

        <p>The student is enrolled for <strong>{{ ACADEMIC_TERM }}</strong>{% if TERM_DATES %} ({{ TERM_DATES }}){% endif %} and is required to complete <strong>{{ REQUIRED_HOURS }} hours</strong> of On-the-Job Training. The official OJT start date and projected end date will be confirmed by your company after the student's orientation.</p>

        <p>In line with our objective of providing our students with a holistic, quality and relevant computer-based education in all disciplines, we strongly emphasized a discipline-based training environment appropriate to the rank of each and are given the best training after having finished the theoretical requirements in school.</p>

        <p>It is in this context that the <strong>AMA COMPUTER COLLEGE</strong> hereby endorses <strong>{{ STUDENT_NAME }}</strong>, student of <strong>{{ STUDENT_COURSE }}</strong> program to complete the required hours in your company in view of completing the curriculum.</p>

        <p>In support of this collaboration, and to enable the student to maximize their time and learning with your company and ensure their safety as well, may we request that the student:</p>

        <ul class="req-list">
            <li>be assigned to areas or given work assignments that are meaningful and will make them gain practical experience in their field of specialization</li>
            <li>not be given personal and menial tasks that are unrelated to the discipline</li>
            <li>not be exposed to dangerous work assignments or risk, and be fully compliant with OSHA standards and all safety regulations of your company</li>
            <li>be treated in a professional manner and all interactions between the school and your company be conducted professionally and ethically</li>
        </ul>

        <p>Thank you and we look forward to our continuing partnership in the development of our students and future professionals.</p>
    </div>

    <div class="closing">
        <p>Respectfully submitted,</p>
        <div class="sig-block">
            {{ SIGNATURE_IMG }}
            <p class="name">{{ COORDINATOR_NAME }}</p>
            <p>{{ COORDINATOR_TITLE }}</p>
            <p class="email">{{ COORDINATOR_EMAIL }}</p>
        </div>
    </div>
</body>
</html>
HTML;

        $logoPath = __DIR__ . '/../assets/image/main/logo/image.png';
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        // Embed the coordinator's signature image, auto-trimming whitespace
        $signatureImg = '';
        $signatureRelPath = trim((string)($data['coordinator_signature'] ?? ''));
        if ($signatureRelPath !== '') {
            $signatureFullPath = __DIR__ . '/../' . ltrim($signatureRelPath, '/');
            if (file_exists($signatureFullPath)) {
                $trimmedPng = $this->trimSignatureWhitespace($signatureFullPath);
                if ($trimmedPng !== null) {
                    $signatureData = 'data:image/png;base64,' . base64_encode($trimmedPng);
                    $signatureImg = '<img class="signature" src="' . $signatureData . '" alt="Signature">';
                }
            }
        }

        // Replace all placeholders with actual data
        $replacements = [
            '{{ LOGO_BASE64 }}' => $logoBase64,
            '{{ SIGNATURE_IMG }}' => $signatureImg,
            '{{ CURRENT_DATE }}' => $currentDate,
            '{{ CONTACT_PERSON }}' => $safe($data['contact_person'] ?? 'Host Training Establishment'),
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

    /**
     * Loads a signature image and crops away surrounding whitespace
     * so it sits tightly above the coordinator's name in the PDF.
     */
    private function trimSignatureWhitespace(string $path): ?string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $src = $ext === 'png' ? @imagecreatefrompng($path) : @imagecreatefromjpeg($path);
        if (!$src) {
            return file_get_contents($path) ?: null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $bg = imagecolorat($src, 0, 0);
        $threshold = 30;
        $bgR = ($bg >> 16) & 0xFF;
        $bgG = ($bg >> 8) & 0xFF;
        $bgB = $bg & 0xFF;

        $isBackground = function (int $x, int $y) use ($src, $bgR, $bgG, $bgB, $threshold): bool {
            $c = imagecolorat($src, $x, $y);
            return abs((($c >> 16) & 0xFF) - $bgR) <= $threshold
                && abs((($c >> 8) & 0xFF) - $bgG) <= $threshold
                && abs(($c & 0xFF) - $bgB) <= $threshold;
        };

        $top = 0;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if (!$isBackground($x, $y)) { $top = $y; goto foundTop; }
            }
        }
        foundTop:

        $bottom = $h - 1;
        for ($y = $h - 1; $y >= $top; $y--) {
            for ($x = 0; $x < $w; $x++) {
                if (!$isBackground($x, $y)) { $bottom = $y; goto foundBottom; }
            }
        }
        foundBottom:

        $left = 0;
        for ($x = 0; $x < $w; $x++) {
            for ($y = $top; $y <= $bottom; $y++) {
                if (!$isBackground($x, $y)) { $left = $x; goto foundLeft; }
            }
        }
        foundLeft:

        $right = $w - 1;
        for ($x = $w - 1; $x >= $left; $x--) {
            for ($y = $top; $y <= $bottom; $y++) {
                if (!$isBackground($x, $y)) { $right = $x; goto foundRight; }
            }
        }
        foundRight:

        $cropW = $right - $left + 1;
        $cropH = $bottom - $top + 1;
        if ($cropW < 1 || $cropH < 1) {
            imagedestroy($src);
            return file_get_contents($path) ?: null;
        }

        $cropped = imagecreatetruecolor($cropW, $cropH);
        imagesavealpha($cropped, true);
        imagefill($cropped, 0, 0, imagecolorallocatealpha($cropped, 255, 255, 255, 127));
        imagecopy($cropped, $src, 0, 0, $left, $top, $cropW, $cropH);
        imagedestroy($src);

        ob_start();
        imagepng($cropped);
        $pngData = ob_get_clean();
        imagedestroy($cropped);

        return $pngData ?: null;
    }
}
