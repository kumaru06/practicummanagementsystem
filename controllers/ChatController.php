<?php
class ChatController
{
    private const ALLOWED_ROLES = ['admin', 'coordinator', 'student', 'partner'];
    private const MAX_MESSAGE_LENGTH = 2000;
    private const TYPING_TTL_SECONDS = 5;

    private PDO $db;
    private int $userId;
    private string $role;
    private bool $messagesTableReady = false;
    private bool $typingTableReady = false;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
        $this->syncSessionContext();
        $this->userId = (int)($_SESSION['user_id'] ?? 0);
        $this->role = (string)($_SESSION['role'] ?? '');
    }

    public function isAuthenticated(): bool
    {
        return $this->userId > 0 && in_array($this->role, self::ALLOWED_ROLES, true);
    }

    public function currentUserId(): int
    {
        return $this->userId;
    }

    public function currentRole(): string
    {
        return $this->role;
    }

    /**
     * Sanitizes input and inserts a message using PDO prepared statements.
     */
    public function sendMessage(int $receiverId, string $receiverRole, string $text): array
    {
        $this->requireAuth();
        $this->ensureMessagesTable();

        $receiverRole = strtolower(trim($receiverRole));
        $text = $this->sanitizeMessage($text);

        if ($text === '') {
            throw new InvalidArgumentException('Message cannot be empty.');
        }

        if (!$this->canChatWith($receiverId, $receiverRole)) {
            throw new RuntimeException('You are not allowed to chat with this user.');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO messages (sender_id, sender_role, receiver_id, receiver_role, message_text, is_read)
             VALUES (?, ?, ?, ?, ?, 0)'
        );
        $stmt->execute([$this->userId, $this->role, $receiverId, $receiverRole, $text]);

        return [
            'id' => (int)$this->db->lastInsertId(),
            'sender_id' => $this->userId,
            'sender_role' => $this->role,
            'receiver_id' => $receiverId,
            'receiver_role' => $receiverRole,
            'message_text' => $text,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Fetches chat history between the logged-in user and the selected partner.
     */
    public function getMessages(int $chatPartnerId, string $chatPartnerRole): array
    {
        $this->requireAuth();
        $this->ensureMessagesTable();

        $chatPartnerRole = strtolower(trim($chatPartnerRole));

        if (!$this->canChatWith($chatPartnerId, $chatPartnerRole)) {
            throw new RuntimeException('You are not allowed to view this conversation.');
        }

        $stmt = $this->db->prepare(
            'SELECT m.id, m.sender_id, m.sender_role, m.receiver_id, m.receiver_role,
                    m.message_text, m.is_read, m.created_at, u.name AS sender_name
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE (
                 m.sender_id = ? AND m.sender_role = ? AND m.receiver_id = ? AND m.receiver_role = ?
             ) OR (
                 m.sender_id = ? AND m.sender_role = ? AND m.receiver_id = ? AND m.receiver_role = ?
             )
             ORDER BY m.created_at ASC, m.id ASC'
        );
        $stmt->execute([
            $this->userId,
            $this->role,
            $chatPartnerId,
            $chatPartnerRole,
            $chatPartnerId,
            $chatPartnerRole,
            $this->userId,
            $this->role,
        ]);

        $messages = $stmt->fetchAll() ?: [];
        $this->markConversationAsRead($chatPartnerId, $chatPartnerRole);

        return $messages;
    }

    public function setTyping(int $partnerId, string $partnerRole, bool $isTyping): void
    {
        $this->requireAuth();
        $this->ensureTypingTable();

        $partnerRole = strtolower(trim($partnerRole));
        if (!$this->canChatWith($partnerId, $partnerRole)) {
            throw new RuntimeException('You are not allowed to chat with this user.');
        }

        if (!$isTyping) {
            $stmt = $this->db->prepare(
                'DELETE FROM chat_typing
                 WHERE user_id = ? AND user_role = ?
                   AND partner_id = ? AND partner_role = ?'
            );
            $stmt->execute([$this->userId, $this->role, $partnerId, $partnerRole]);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO chat_typing (user_id, user_role, partner_id, partner_role, updated_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE updated_at = NOW()'
        );
        $stmt->execute([$this->userId, $this->role, $partnerId, $partnerRole]);
    }

    /** @return array{is_typing: bool, name: string|null} */
    public function getPartnerTypingStatus(int $partnerId, string $partnerRole): array
    {
        $this->requireAuth();
        $this->ensureTypingTable();

        $partnerRole = strtolower(trim($partnerRole));
        if (!$this->canChatWith($partnerId, $partnerRole)) {
            return ['is_typing' => false, 'name' => null];
        }

        $stmt = $this->db->prepare(
            'SELECT u.name, ct.updated_at
             FROM chat_typing ct
             JOIN users u ON u.id = ct.user_id
             WHERE ct.user_id = ?
               AND ct.user_role = ?
               AND ct.partner_id = ?
               AND ct.partner_role = ?
               AND ct.updated_at >= (NOW() - INTERVAL ' . self::TYPING_TTL_SECONDS . ' SECOND)
             LIMIT 1'
        );
        $stmt->execute([$partnerId, $partnerRole, $this->userId, $this->role]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['is_typing' => false, 'name' => null];
        }

        return [
            'is_typing' => true,
            'name' => (string)$row['name'],
        ];
    }

    /** @return list<array<string, mixed>> */
    public function getChatPartners(): array
    {
        $this->requireAuth();
        $this->ensureMessagesTable();

        return match ($this->role) {
            'admin' => $this->partnersForAdmin(),
            'coordinator' => $this->partnersForCoordinator(),
            'student' => $this->partnersForStudent(),
            'partner' => $this->partnersForPartner(),
            default => [],
        };
    }

    public function canChatWith(int $partnerId, string $partnerRole): bool
    {
        if ($partnerId <= 0 || $partnerId === $this->userId) {
            return false;
        }

        $partnerRole = strtolower(trim($partnerRole));
        if (!in_array($partnerRole, self::ALLOWED_ROLES, true)) {
            return false;
        }

        foreach ($this->getChatPartners() as $partner) {
            if ((int)$partner['user_id'] === $partnerId && (string)$partner['role'] === $partnerRole) {
                return true;
            }
        }

        return false;
    }

    /** @return list<array<string, mixed>> */
    public function getPartnerGroups(): array
    {
        $groups = [];
        foreach ($this->getChatPartners() as $partner) {
            $role = (string)$partner['role'];
            $groups[$role]['role'] = $role;
            $groups[$role]['label'] = $this->roleLabel($role);
            $groups[$role]['partners'][] = $partner;
        }

        $order = ['coordinator', 'student', 'partner', 'admin'];
        uksort($groups, static function (string $a, string $b) use ($order): int {
            return array_search($a, $order, true) <=> array_search($b, $order, true);
        });

        return array_values($groups);
    }

    private function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            throw new RuntimeException('Authentication required.');
        }
    }

    private function sanitizeMessage(string $text): string
    {
        $text = trim(strip_tags($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        if (mb_strlen($text) > self::MAX_MESSAGE_LENGTH) {
            $text = mb_substr($text, 0, self::MAX_MESSAGE_LENGTH);
        }

        return $text;
    }

    private function markConversationAsRead(int $partnerId, string $partnerRole): void
    {
        $stmt = $this->db->prepare(
            'UPDATE messages
             SET is_read = 1
             WHERE receiver_id = ? AND receiver_role = ?
               AND sender_id = ? AND sender_role = ?
               AND is_read = 0'
        );
        $stmt->execute([$this->userId, $this->role, $partnerId, $partnerRole]);
    }

    private function syncSessionContext(): void
    {
        if (!isset($_SESSION['user_id'], $_SESSION['role']) && isset($_SESSION['user'])) {
            $_SESSION['user_id'] = (int)($_SESSION['user']['id'] ?? 0);
            $_SESSION['role'] = (string)($_SESSION['user']['role'] ?? '');
        }
    }

    private function ensureMessagesTable(): void
    {
        if ($this->messagesTableReady) {
            return;
        }

        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sender_id INT NOT NULL,
                sender_role ENUM("admin","coordinator","student","partner") NOT NULL,
                receiver_id INT NOT NULL,
                receiver_role ENUM("admin","coordinator","student","partner") NOT NULL,
                message_text TEXT NOT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_messages_conversation (sender_id, sender_role, receiver_id, receiver_role, created_at),
                INDEX idx_messages_inbox (receiver_id, receiver_role, is_read, created_at),
                CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->messagesTableReady = true;
    }

    private function ensureTypingTable(): void
    {
        if ($this->typingTableReady) {
            return;
        }

        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS chat_typing (
                user_id INT NOT NULL,
                user_role ENUM("admin","coordinator","student","partner") NOT NULL,
                partner_id INT NOT NULL,
                partner_role ENUM("admin","coordinator","student","partner") NOT NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, user_role, partner_id, partner_role),
                INDEX idx_chat_typing_partner (partner_id, partner_role, updated_at),
                CONSTRAINT fk_chat_typing_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_chat_typing_partner_user FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->typingTableReady = true;
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'Administrators',
            'coordinator' => 'Coordinators',
            'student' => 'Students',
            'partner' => 'Host Training Establishments',
            default => ucfirst($role),
        };
    }

    /** @return list<array<string, mixed>> */
    private function partnersForAdmin(): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id AS user_id, u.name, u.email, u.role,
                    (
                        SELECT COUNT(*)
                        FROM messages m
                        WHERE m.receiver_id = ?
                          AND m.receiver_role = ?
                          AND m.sender_id = u.id
                          AND m.sender_role = u.role
                          AND m.is_read = 0
                    ) AS unread_count
             FROM users u
             WHERE u.is_active = 1
               AND u.id != ?
               AND u.role IN ("coordinator", "student", "partner")
             ORDER BY u.role, u.name'
        );
        $stmt->execute([$this->userId, $this->role, $this->userId]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    private function partnersForCoordinator(): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id AS user_id, u.name, u.email, u.role,
                    (
                        SELECT COUNT(*)
                        FROM messages m
                        WHERE m.receiver_id = ?
                          AND m.receiver_role = ?
                          AND m.sender_id = u.id
                          AND m.sender_role = u.role
                          AND m.is_read = 0
                    ) AS unread_count
             FROM (
                 SELECT su.id, su.name, su.email, su.role
                 FROM students s
                 JOIN users su ON su.id = s.user_id
                 WHERE s.coordinator_id = ? AND su.is_active = 1

                 UNION

                 SELECT au.id, au.name, au.email, au.role
                 FROM users au
                 WHERE au.role = "admin" AND au.is_active = 1

                 UNION

                 SELECT pu.id, pu.name, pu.email, pu.role
                 FROM students s
                 JOIN ojt_enrollments e ON e.student_id = s.id
                 JOIN partner_companies pc ON pc.id = e.company_id
                 JOIN users pu ON pu.id = pc.user_id
                 WHERE s.coordinator_id = ? AND pu.is_active = 1
             ) u
             ORDER BY u.role, u.name'
        );
        $stmt->execute([$this->userId, $this->role, $this->userId, $this->userId]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    private function partnersForStudent(): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id AS user_id, u.name, u.email, u.role,
                    (
                        SELECT COUNT(*)
                        FROM messages m
                        WHERE m.receiver_id = ?
                          AND m.receiver_role = ?
                          AND m.sender_id = u.id
                          AND m.sender_role = u.role
                          AND m.is_read = 0
                    ) AS unread_count
             FROM (
                 SELECT cu.id, cu.name, cu.email, cu.role
                 FROM students s
                 JOIN users cu ON cu.id = s.coordinator_id
                 WHERE s.user_id = ? AND cu.is_active = 1

                 UNION

                 SELECT pu.id, pu.name, pu.email, pu.role
                 FROM students s
                 JOIN ojt_enrollments e ON e.student_id = s.id
                 JOIN partner_companies pc ON pc.id = e.company_id
                 JOIN users pu ON pu.id = pc.user_id
                 WHERE s.user_id = ? AND pu.is_active = 1
             ) u
             ORDER BY u.role, u.name'
        );
        $stmt->execute([$this->userId, $this->role, $this->userId, $this->userId]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    private function partnersForPartner(): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id AS user_id, u.name, u.email, u.role,
                    (
                        SELECT COUNT(*)
                        FROM messages m
                        WHERE m.receiver_id = ?
                          AND m.receiver_role = ?
                          AND m.sender_id = u.id
                          AND m.sender_role = u.role
                          AND m.is_read = 0
                    ) AS unread_count
             FROM (
                 SELECT su.id, su.name, su.email, su.role
                 FROM partner_companies pc
                 JOIN ojt_enrollments e ON e.company_id = pc.id
                 JOIN students s ON s.id = e.student_id
                 JOIN users su ON su.id = s.user_id
                 WHERE pc.user_id = ? AND su.is_active = 1

                 UNION

                 SELECT cu.id, cu.name, cu.email, cu.role
                 FROM partner_companies pc
                 JOIN ojt_enrollments e ON e.company_id = pc.id
                 JOIN students s ON s.id = e.student_id
                 JOIN users cu ON cu.id = s.coordinator_id
                 WHERE pc.user_id = ? AND cu.is_active = 1
             ) u
             ORDER BY u.role, u.name'
        );
        $stmt->execute([$this->userId, $this->role, $this->userId, $this->userId]);

        return $stmt->fetchAll() ?: [];
    }
}
