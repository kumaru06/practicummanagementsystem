<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class Email
{
    public function __construct(private PDO $db) {}

    private function archiveLocalDevCopy(string $recipient, string $subject, string $body): ?string
    {
        if (!defined('APP_IS_LOCAL') || !APP_IS_LOCAL) {
            return null;
        }

        $dir = dirname(__DIR__) . '/uploads/dev-mail';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        $safeRecipient = preg_replace('/[^a-z0-9@._-]+/i', '_', $recipient) ?: 'recipient';
        $file = $dir . '/' . date('Ymd-His') . '-' . $safeRecipient . '.html';
        $html = '<!-- To: ' . htmlspecialchars($recipient) . ' | Subject: ' . htmlspecialchars($subject) . " -->\n" . $body;
        return file_put_contents($file, $html) !== false ? $file : null;
    }

    private function configureMailer(PHPMailer $mail): void
    {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = false;
        if (defined('MAIL_FROM_EMAIL') && str_contains(MAIL_FROM_EMAIL, '@')) {
            $fromDomain = substr(MAIL_FROM_EMAIL, strrpos(MAIL_FROM_EMAIL, '@') + 1);
            // Local .test hosts otherwise produce Message-IDs like @amaccmanagementsystem.test, which Gmail drops.
            $mail->Hostname = $fromDomain;
            $mail->Helo = $fromDomain;
        }
        // Shared hosts (Hostinger) sometimes need relaxed SSL for outbound SMTP.
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
    }

    public function send(string $recipient, string $subject, string $type, string $template, array $data, array $attachments = []): bool
    {
        $status = 'failed';
        $error = null;
        $logType = $type;

        try {
            if (!class_exists(PHPMailer::class)) {
                throw new RuntimeException('PHPMailer is not installed on this server.');
            }
            $body = $this->renderTemplate($template, $data);

            if (defined('MAIL_DRIVER') && MAIL_DRIVER === 'file') {
                $dir = defined('MAIL_FILE_DIR') ? MAIL_FILE_DIR : dirname(__DIR__) . '/uploads/dev-mail';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $safeType = preg_replace('/[^a-z0-9_-]+/i', '_', $type) ?: 'mail';
                $file = $dir . '/' . date('Ymd-His') . '-' . $safeType . '.html';
                $html = '<!-- To: ' . htmlspecialchars($recipient) . ' | Subject: ' . htmlspecialchars($subject) . " -->\n" . $body;
                if (file_put_contents($file, $html) === false) {
                    throw new RuntimeException('Could not write dev mail file.');
                }
                $status = 'sent';
                return true;
            }

            $mail = new PHPMailer(true);
            $this->configureMailer($mail);
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addReplyTo(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->XMailer = ' ';
        $mail->Priority = 3;
            $mail->addAddress($recipient);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body)));
            $this->archiveLocalDevCopy($recipient, $subject, $body);

            foreach ($attachments as $attachment) {
                // Support for string attachments (e.g., dynamically generated PDFs)
                if (is_array($attachment) && isset($attachment['string'])) {
                    $content = $attachment['string'];
                    $name = $attachment['name'] ?? 'attachment';
                    $attachmentMime = $attachment['type'] ?? 'application/octet-stream';
                    $mail->addStringAttachment($content, $name, PHPMailer::ENCODING_BASE64, $attachmentMime);
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
            $mail->send();
            $status = 'sent';
            return true;
        } catch (Throwable $e) {
            $error = trim($e->getMessage());
            if (isset($mail) && $mail instanceof PHPMailer && $mail->ErrorInfo !== '') {
                $error = trim($mail->ErrorInfo);
            }
            $error = mail_error_hint($error);
            return false;
        } finally {
            $stmt = $this->db->prepare('INSERT INTO email_logs (recipient_email, subject, type, sent_at, status, error_message) VALUES (?, ?, ?, NOW(), ?, ?)');
            $stmt->execute([$recipient, $subject, $logType, $status, $error]);
        }
    }

    private function renderTemplate(string $template, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require __DIR__ . '/../views/emails/' . $template . '.php';
        return ob_get_clean();
    }

    public function recent(): array
    {
        return $this->db->query('SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT 100')->fetchAll();
    }

    public function filtered(array $filters = []): array
    {
        $sql = 'SELECT * FROM email_logs WHERE 1=1';
        $params = [];
        if (!empty($filters['type'])) {
            $sql .= ' AND type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND DATE(sent_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND DATE(sent_at) <= ?';
            $params[] = $filters['date_to'];
        }
        $sql .= ' ORDER BY sent_at DESC LIMIT 300';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function types(): array
    {
        return $this->db->query('SELECT DISTINCT type FROM email_logs ORDER BY type')->fetchAll(PDO::FETCH_COLUMN);
    }
}
