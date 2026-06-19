<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class Email
{
    public function __construct(private PDO $db) {}

    private function mailDebugLog(string $hypothesisId, string $location, string $message, array $data = []): void
    {
        // #region agent log
        $entry = json_encode([
            'sessionId' => '824cc8',
            'runId' => 'mail',
            'hypothesisId' => $hypothesisId,
            'location' => $location,
            'message' => $message,
            'data' => $data,
            'timestamp' => (int) round(microtime(true) * 1000),
        ], JSON_UNESCAPED_SLASHES);
        @file_put_contents(dirname(__DIR__) . '/uploads/debug-824cc8.log', $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
        // #endregion
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
        $this->mailDebugLog('A', 'Email.php:send:start', 'Send requested', [
            'recipient_domain' => substr(strrchr($recipient, '@') ?: '', 1),
            'type' => $type,
            'smtp_host' => SMTP_HOST,
            'smtp_port' => SMTP_PORT,
            'smtp_secure' => SMTP_SECURE,
        ]);

        try {
            if (!class_exists(PHPMailer::class)) {
                throw new RuntimeException(
                    'PHPMailer is not installed. Upload config/mail.php from your PC to public_html/config/ (auto-installs on next page load).'
                );
            }
            $body = $this->renderTemplate($template, $data);
            $mail = new PHPMailer(true);
            $this->configureMailer($mail);
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($recipient);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body)));
            foreach ($attachments as $attachment) {
                // Support for string attachments (e.g., dynamically generated PDFs)
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
            $mail->send();
            $status = 'sent';
            $this->mailDebugLog('B', 'Email.php:send:success', 'PHPMailer send OK', [
                'type' => $type,
                'status' => $status,
            ]);
            return true;
        } catch (Throwable $e) {
            $error = trim($e->getMessage());
            if (isset($mail) && $mail instanceof PHPMailer && $mail->ErrorInfo !== '') {
                $error = trim($mail->ErrorInfo);
            }
            $this->mailDebugLog('C', 'Email.php:send:fail', 'PHPMailer send failed', [
                'type' => $type,
                'error' => $error,
                'host' => SMTP_HOST,
                'port' => SMTP_PORT,
            ]);
            return false;
        } finally {
            $stmt = $this->db->prepare('INSERT INTO email_logs (recipient_email, subject, type, sent_at, status, error_message) VALUES (?, ?, ?, NOW(), ?, ?)');
            $stmt->execute([$recipient, $subject, $type, $status, $error]);
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
