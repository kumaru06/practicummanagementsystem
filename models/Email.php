<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class Email
{
    public function __construct(private PDO $db) {}

    public function send(string $recipient, string $subject, string $type, string $template, array $data, array $attachments = []): bool
    {
        $status = 'failed';
        $error = null;
        try {
            if (!class_exists(PHPMailer::class)) {
                throw new RuntimeException('PHPMailer is not installed. Run composer install.');
            }
            $body = $this->renderTemplate($template, $data);
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($recipient);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body)));
            foreach ($attachments as $attachment) {
                $path = is_array($attachment) ? ($attachment['path'] ?? '') : (string)$attachment;
                $name = is_array($attachment) ? ($attachment['name'] ?? '') : '';
                $fullPath = str_starts_with($path, __DIR__) ? $path : dirname(__DIR__) . '/' . ltrim($path, '/\\');
                if ($path && is_file($fullPath)) {
                    $mail->addAttachment($fullPath, $name ?: basename($fullPath));
                }
            }
            $mail->send();
            $status = 'sent';
            return true;
        } catch (Throwable $e) {
            $error = $e->getMessage();
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
