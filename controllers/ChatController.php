<?php
class ChatController
{
    private const ALLOWED_ROLES = ['admin', 'coordinator', 'student', 'partner'];
    private const MAX_MESSAGE_LENGTH = 2000;
    private const TYPING_TTL_SECONDS = 5;
    private const PAGE_SIZE = 30;
    private const MAX_IMAGES = 3;
    private const MAX_IMAGE_BYTES = 5242880;
    private const HTE_LOOP_STATUSES = ['forwarded', 'accepted', 'orientation_scheduled', 'orientation_completed', 'approved'];
    private const IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'webp'];
    private const REACTION_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '🙏'];

    private PDO $db;
    private int $userId;
    private string $role;
    private bool $schemaReady = false;
    /** @var list<array<string, mixed>>|null */
    private ?array $chatPartnersCache = null;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
        $this->syncSessionContext();
        $this->userId = (int)($_SESSION['user_id'] ?? 0);
        $this->role = strtolower(trim((string)($_SESSION['role'] ?? '')));
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

    public function assertCanChatWith(int $partnerId, string $partnerRole): void
    {
        if (!$this->canChatWith($partnerId, $partnerRole)) {
            throw new RuntimeException('You are not allowed to view this conversation.');
        }
    }

    public function assertCanSendTo(int $partnerId, string $partnerRole): void
    {
        if (!$this->canSendTo($partnerId, $partnerRole)) {
            throw new RuntimeException($this->sendBlockReason($partnerId, $partnerRole) ?: 'You are not allowed to chat with this user.');
        }
    }

    /**
     * @param list<array<string, mixed>> $uploadedFiles
     * @return array<string, mixed>
     */
    public function sendMessage(
        int $receiverId,
        string $receiverRole,
        string $text,
        ?string $clientMessageId = null,
        ?int $replyToId = null,
        array $uploadedFiles = []
    ): array {
        $this->requireAuth();
        $this->ensureSchema();

        $receiverRole = strtolower(trim($receiverRole));
        $text = $this->sanitizeMessage($text);
        $clientMessageId = $this->normalizeClientMessageId($clientMessageId);
        if ($clientMessageId === null) {
            $clientMessageId = 's' . bin2hex(random_bytes(12));
        }

        $existing = $this->findByClientMessageId($clientMessageId);
        if ($existing) {
            return $existing;
        }

        $this->assertCanSendTo($receiverId, $receiverRole);

        $uploadedFiles = array_values(array_filter(
            $uploadedFiles,
            static fn(array $file): bool => (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
                && trim((string)($file['name'] ?? '')) !== ''
        ));

        if ($text === '' && $uploadedFiles === []) {
            throw new InvalidArgumentException('Message cannot be empty.');
        }

        if ($replyToId !== null && $replyToId > 0 && !$this->messageBelongsToConversation($replyToId, $receiverId, $receiverRole)) {
            throw new RuntimeException('You are not allowed to reply to this message.');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO messages (sender_id, sender_role, receiver_id, receiver_role, message_text, client_message_id, reply_to_id, delivery_status, is_read)
                 VALUES (?, ?, ?, ?, ?, ?, ?, "sent", 0)'
            );
            $stmt->execute([
                $this->userId,
                $this->role,
                $receiverId,
                $receiverRole,
                $text,
                $clientMessageId,
                $replyToId ?: null,
            ]);
            $messageId = (int)$this->db->lastInsertId();
            $this->storeAttachments($messageId, $uploadedFiles);
            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            if ($clientMessageId !== null && $this->isDuplicateClientKey($e)) {
                $existing = $this->findByClientMessageId($clientMessageId);
                if ($existing) {
                    return $existing;
                }
            }
            throw $e;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        $this->chatPartnersCache = null;
        $hydrated = $this->hydrateMessages([$this->fetchMessageRow($messageId)]);

        return $hydrated[0] ?? [];
    }

    /** @return array<string, mixed> */
    public function reactToMessage(int $partnerId, string $partnerRole, int $messageId, string $emoji): array
    {
        $this->requireAuth();
        $this->ensureSchema();
        $partnerRole = strtolower(trim($partnerRole));
        $this->assertCanChatWith($partnerId, $partnerRole);

        if (!in_array($emoji, self::REACTION_EMOJIS, true)) {
            throw new InvalidArgumentException('That reaction is not allowed.');
        }

        $row = $this->requireConversationMessage($messageId, $partnerId, $partnerRole);
        if (!empty($row['deleted_at'])) {
            throw new RuntimeException('This message was removed.');
        }

        $existing = $this->db->prepare(
            'SELECT emoji FROM chat_message_reactions
             WHERE message_id = ? AND user_id = ? AND user_role = ? LIMIT 1'
        );
        $existing->execute([$messageId, $this->userId, $this->role]);
        $current = $existing->fetchColumn();

        if ($current === $emoji) {
            $del = $this->db->prepare(
                'DELETE FROM chat_message_reactions
                 WHERE message_id = ? AND user_id = ? AND user_role = ?'
            );
            $del->execute([$messageId, $this->userId, $this->role]);
        } else {
            $upsert = $this->db->prepare(
                'INSERT INTO chat_message_reactions (message_id, user_id, user_role, emoji)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE emoji = VALUES(emoji), created_at = CURRENT_TIMESTAMP'
            );
            $upsert->execute([$messageId, $this->userId, $this->role, $emoji]);
        }

        $hydrated = $this->hydrateMessages([$this->fetchMessageRow($messageId)]);

        return $hydrated[0] ?? [];
    }

    /** @return array<string, mixed> */
    public function togglePinMessage(int $partnerId, string $partnerRole, int $messageId): array
    {
        $this->requireAuth();
        $this->ensureSchema();
        $partnerRole = strtolower(trim($partnerRole));
        $this->assertCanChatWith($partnerId, $partnerRole);

        $row = $this->requireConversationMessage($messageId, $partnerId, $partnerRole);
        if (!empty($row['deleted_at'])) {
            throw new RuntimeException('This message was removed.');
        }

        $next = ((int)($row['is_pinned'] ?? 0) === 1) ? 0 : 1;
        $stmt = $this->db->prepare('UPDATE messages SET is_pinned = ? WHERE id = ?');
        $stmt->execute([$next, $messageId]);

        $hydrated = $this->hydrateMessages([$this->fetchMessageRow($messageId)]);

        return $hydrated[0] ?? [];
    }

    /** @return array<string, mixed> */
    public function removeMessage(int $partnerId, string $partnerRole, int $messageId): array
    {
        $this->requireAuth();
        $this->ensureSchema();
        $partnerRole = strtolower(trim($partnerRole));
        $this->assertCanChatWith($partnerId, $partnerRole);

        $row = $this->requireConversationMessage($messageId, $partnerId, $partnerRole);
        if ((int)$row['sender_id'] !== $this->userId || (string)$row['sender_role'] !== $this->role) {
            throw new RuntimeException('You can only remove your own messages.');
        }

        $stmt = $this->db->prepare(
            'UPDATE messages SET deleted_at = CURRENT_TIMESTAMP, is_pinned = 0, message_text = "" WHERE id = ?'
        );
        $stmt->execute([$messageId]);
        $this->db->prepare('DELETE FROM chat_message_reactions WHERE message_id = ?')->execute([$messageId]);
        $this->chatPartnersCache = null;

        $hydrated = $this->hydrateMessages([$this->fetchMessageRow($messageId)]);

        return $hydrated[0] ?? [];
    }

    /**
     * @return array{messages: list<array<string, mixed>>, has_more: bool, can_send: bool, send_block_reason: string}
     */
    public function getMessages(int $chatPartnerId, string $chatPartnerRole, ?int $beforeId = null, int $limit = self::PAGE_SIZE): array
    {
        $this->requireAuth();
        $this->ensureSchema();

        $chatPartnerRole = strtolower(trim($chatPartnerRole));
        $this->assertCanChatWith($chatPartnerId, $chatPartnerRole);

        $limit = max(1, min(50, $limit));
        $params = [
            $this->userId, $this->role, $chatPartnerId, $chatPartnerRole,
            $chatPartnerId, $chatPartnerRole, $this->userId, $this->role,
        ];
        $beforeSql = '';
        if ($beforeId !== null && $beforeId > 0) {
            $beforeSql = ' AND m.id < ?';
            $params[] = $beforeId;
        }

        $stmt = $this->db->prepare(
            'SELECT m.id, m.sender_id, m.sender_role, m.receiver_id, m.receiver_role,
                    m.message_text, m.client_message_id, m.reply_to_id, m.delivery_status,
                    m.is_read, m.is_pinned, m.deleted_at, m.created_at, u.name AS sender_name
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE (
                 m.sender_id = ? AND m.sender_role = ? AND m.receiver_id = ? AND m.receiver_role = ?
             ) OR (
                 m.sender_id = ? AND m.sender_role = ? AND m.receiver_id = ? AND m.receiver_role = ?
             )' . $beforeSql . '
             ORDER BY m.id DESC
             LIMIT ' . ($limit + 1)
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];
        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            array_pop($rows);
        }
        $rows = array_reverse($rows);

        if ($beforeId === null) {
            $this->markConversationAsRead($chatPartnerId, $chatPartnerRole);
        }

        return [
            'messages' => $this->hydrateMessages($rows),
            'has_more' => $hasMore,
            'can_send' => $this->canSendTo($chatPartnerId, $chatPartnerRole),
            'send_block_reason' => $this->sendBlockReason($chatPartnerId, $chatPartnerRole),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function searchMessages(int $chatPartnerId, string $chatPartnerRole, string $query): array
    {
        $this->requireAuth();
        $this->ensureSchema();
        $chatPartnerRole = strtolower(trim($chatPartnerRole));
        $this->assertCanChatWith($chatPartnerId, $chatPartnerRole);

        $query = trim(strip_tags($query));
        if (mb_strlen($query) < 2) {
            return [];
        }

        $like = '%' . $query . '%';
        $stmt = $this->db->prepare(
            'SELECT m.id, m.sender_id, m.sender_role, m.receiver_id, m.receiver_role,
                    m.message_text, m.client_message_id, m.reply_to_id, m.delivery_status,
                    m.is_read, m.is_pinned, m.deleted_at, m.created_at, u.name AS sender_name
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE ((
                 m.sender_id = ? AND m.sender_role = ? AND m.receiver_id = ? AND m.receiver_role = ?
             ) OR (
                 m.sender_id = ? AND m.sender_role = ? AND m.receiver_id = ? AND m.receiver_role = ?
             )) AND m.message_text LIKE ?
               AND m.deleted_at IS NULL
             ORDER BY m.id DESC
             LIMIT 40'
        );
        $stmt->execute([
            $this->userId, $this->role, $chatPartnerId, $chatPartnerRole,
            $chatPartnerId, $chatPartnerRole, $this->userId, $this->role,
            $like,
        ]);

        return $this->hydrateMessages($stmt->fetchAll() ?: []);
    }

    public function setTyping(int $partnerId, string $partnerRole, bool $isTyping): void
    {
        $this->requireAuth();
        $this->ensureSchema();

        $partnerRole = strtolower(trim($partnerRole));
        $this->assertCanChatWith($partnerId, $partnerRole);

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
        $this->ensureSchema();

        $partnerRole = strtolower(trim($partnerRole));
        if (!$this->canChatWith($partnerId, $partnerRole)) {
            return ['is_typing' => false, 'name' => null];
        }

        $stmt = $this->db->prepare(
            'SELECT u.name
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

        return $row
            ? ['is_typing' => true, 'name' => (string)$row['name']]
            : ['is_typing' => false, 'name' => null];
    }

    /** @return list<array<string, mixed>> */
    public function getChatPartners(): array
    {
        if ($this->chatPartnersCache !== null) {
            return $this->chatPartnersCache;
        }

        $this->requireAuth();
        $this->ensureSchema();

        $partners = $this->dedupeChatPartners(match ($this->role) {
            'admin' => $this->partnersForAdmin(),
            'coordinator' => $this->partnersForCoordinator(),
            'student' => $this->partnersForStudent(),
            'partner' => $this->partnersForPartner(),
            default => [],
        });
        $this->chatPartnersCache = $partners;
        $decorated = $this->decoratePartners($partners);

        return $this->chatPartnersCache = $this->hideLockedStudentHtes($decorated);
    }

    public function getUnreadTotal(): int
    {
        $this->requireAuth();
        $this->ensureSchema();
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM messages
             WHERE receiver_id = ? AND receiver_role = ? AND is_read = 0 AND deleted_at IS NULL'
        );
        $stmt->execute([$this->userId, $this->role]);

        return (int)$stmt->fetchColumn();
    }

    /** @return list<array{user_id: int, role: string, unread_count: int}> */
    public function getUnreadBadges(): array
    {
        $this->requireAuth();
        $this->ensureSchema();
        $stmt = $this->db->prepare(
            'SELECT sender_id AS user_id, sender_role AS role, COUNT(*) AS unread_count
             FROM messages
             WHERE receiver_id = ? AND receiver_role = ? AND is_read = 0 AND deleted_at IS NULL
             GROUP BY sender_id, sender_role'
        );
        $stmt->execute([$this->userId, $this->role]);
        $badges = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $badges[] = [
                'user_id' => (int)$row['user_id'],
                'role' => (string)$row['role'],
                'unread_count' => (int)$row['unread_count'],
            ];
        }

        return $badges;
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

    public function canSendTo(int $partnerId, string $partnerRole): bool
    {
        if (!$this->canChatWith($partnerId, $partnerRole)) {
            return false;
        }

        $pair = $this->studentPartnerPair($partnerId, $partnerRole);
        if ($pair === null) {
            return true;
        }

        return $this->enrollmentAllowsSend($pair['student_user_id'], $pair['partner_user_id']);
    }

    public function sendBlockReason(int $partnerId, string $partnerRole): string
    {
        if ($this->canSendTo($partnerId, $partnerRole)) {
            return '';
        }
        if (!$this->canChatWith($partnerId, $partnerRole)) {
            return 'You are not allowed to chat with this user.';
        }

        $pair = $this->studentPartnerPair($partnerId, $partnerRole);
        if ($pair === null) {
            return 'Messaging is not available.';
        }

        $state = $this->enrollmentState($pair['student_user_id'], $pair['partner_user_id']);
        if (($state['reassigned'] ?? false) === true) {
            return 'This student was reassigned. Conversation is read-only.';
        }
        if (($state['status'] ?? '') === 'completed') {
            return 'This placement is completed. Conversation is read-only.';
        }

        return 'Messaging unlocks after the coordinator forwards deployment documents.';
    }

    public function contactHint(): string
    {
        return match ($this->role) {
            'admin' => 'Message coordinators and host training establishments.',
            'coordinator' => 'Connect with students, training partners, and your administrator.',
            'student' => 'Message your coordinator and host training establishment.',
            'partner' => 'Message your students, their coordinators, and the administrator.',
            default => 'Select a contact to start a conversation.',
        };
    }

    /** @param list<array<string, mixed>> $partners */
    private function dedupeChatPartners(array $partners): array
    {
        $map = [];
        foreach ($partners as $partner) {
            $userId = (int)($partner['user_id'] ?? 0);
            $role = (string)($partner['role'] ?? '');
            if ($userId <= 0 || $role === '') {
                continue;
            }
            $key = $userId . ':' . $role;
            if (!isset($map[$key])) {
                $map[$key] = $partner;
                continue;
            }
            $map[$key]['unread_count'] = max(
                (int)($map[$key]['unread_count'] ?? 0),
                (int)($partner['unread_count'] ?? 0)
            );
        }

        return array_values($map);
    }

    /** @param list<array<string, mixed>> $partners */
    private function decoratePartners(array $partners): array
    {
        foreach ($partners as &$partner) {
            $userId = (int)$partner['user_id'];
            $role = (string)$partner['role'];
            $partner['unread_count'] = (int)($partner['unread_count'] ?? 0);
            $partner['can_send'] = $this->canSendTo($userId, $role);
            $partner['send_block_reason'] = $partner['can_send'] ? '' : $this->sendBlockReason($userId, $role);
            $partner['subtitle'] = $this->partnerSubtitle($partner);
            $preview = $this->lastMessagePreview($userId, $role);
            $partner['last_message'] = $preview['text'];
            $partner['last_message_at'] = $preview['created_at'];
            $partner['last_message_is_photo'] = $preview['is_photo'];
        }
        unset($partner);

        usort($partners, static function (array $a, array $b): int {
            $aAt = (string)($a['last_message_at'] ?? '');
            $bAt = (string)($b['last_message_at'] ?? '');
            if ($aAt !== '' && $bAt !== '') {
                return $bAt <=> $aAt;
            }
            if ($aAt !== '') {
                return -1;
            }
            if ($bAt !== '') {
                return 1;
            }

            return strcasecmp((string)$a['name'], (string)$b['name']);
        });

        return $partners;
    }

    /**
     * Students only see an HTE once messaging is allowed, or when history already exists.
     *
     * @param list<array<string, mixed>> $partners
     * @return list<array<string, mixed>>
     */
    private function hideLockedStudentHtes(array $partners): array
    {
        if ($this->role !== 'student') {
            return $partners;
        }

        return array_values(array_filter($partners, static function (array $partner): bool {
            if ((string)($partner['role'] ?? '') !== 'partner') {
                return true;
            }
            if (!empty($partner['can_send'])) {
                return true;
            }
            if (trim((string)($partner['last_message'] ?? '')) !== '' || !empty($partner['last_message_is_photo'])) {
                return true;
            }

            return (int)($partner['unread_count'] ?? 0) > 0;
        }));
    }

    /** @return array{text: string, created_at: string, is_photo: bool} */
    private function lastMessagePreview(int $partnerId, string $partnerRole): array
    {
        $stmt = $this->db->prepare(
            'SELECT m.id, m.message_text, m.created_at
             FROM messages m
             WHERE ((
                 m.sender_id = ? AND m.sender_role = ? AND m.receiver_id = ? AND m.receiver_role = ?
             ) OR (
                 m.sender_id = ? AND m.sender_role = ? AND m.receiver_id = ? AND m.receiver_role = ?
             )) AND m.deleted_at IS NULL
             ORDER BY m.id DESC
             LIMIT 1'
        );
        $stmt->execute([
            $this->userId, $this->role, $partnerId, $partnerRole,
            $partnerId, $partnerRole, $this->userId, $this->role,
        ]);
        $row = $stmt->fetch();
        if (!$row) {
            return ['text' => '', 'created_at' => '', 'is_photo' => false];
        }

        $hasFile = false;
        try {
            $count = $this->db->prepare('SELECT COUNT(*) FROM chat_attachments WHERE message_id = ?');
            $count->execute([(int)$row['id']]);
            $hasFile = (int)$count->fetchColumn() > 0;
        } catch (Throwable) {
            $hasFile = false;
        }

        $text = trim((string)$row['message_text']);

        return [
            'text' => $text,
            'created_at' => (string)$row['created_at'],
            'is_photo' => $hasFile && $text === '',
        ];
    }

    /** @param array<string, mixed> $partner */
    private function partnerSubtitle(array $partner): string
    {
        $role = (string)($partner['role'] ?? '');
        if ($role === 'student' && trim((string)($partner['course'] ?? '')) !== '') {
            return 'Student · ' . trim((string)$partner['course']);
        }
        if ($role === 'partner') {
            return 'Host Training Establishment';
        }

        return match ($role) {
            'admin' => 'Administrator',
            'coordinator' => 'Coordinator',
            'student' => 'Student',
            default => ucfirst($role),
        };
    }

    /** @return array{student_user_id: int, partner_user_id: int}|null */
    private function studentPartnerPair(int $partnerId, string $partnerRole): ?array
    {
        if ($this->role === 'student' && $partnerRole === 'partner') {
            return ['student_user_id' => $this->userId, 'partner_user_id' => $partnerId];
        }
        if ($this->role === 'partner' && $partnerRole === 'student') {
            return ['student_user_id' => $partnerId, 'partner_user_id' => $this->userId];
        }

        return null;
    }

    private function enrollmentAllowsSend(int $studentUserId, int $partnerUserId): bool
    {
        $state = $this->enrollmentState($studentUserId, $partnerUserId);
        if ($state === null || !empty($state['reassigned'])) {
            return false;
        }
        if (($state['status'] ?? '') === 'completed') {
            return false;
        }
        if (($state['status'] ?? '') === 'active') {
            return true;
        }

        return in_array((string)($state['predeployment_status'] ?? ''), self::HTE_LOOP_STATUSES, true);
    }

    /** @return array{status: string, predeployment_status: string, reassigned: bool}|null */
    private function enrollmentState(int $studentUserId, int $partnerUserId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT e.id, e.status, e.predeployment_status, pc.user_id AS company_user_id
             FROM ojt_enrollments e
             JOIN students s ON s.id = e.student_id
             JOIN partner_companies pc ON pc.id = e.company_id
             WHERE s.user_id = ?
             ORDER BY e.id DESC'
        );
        $stmt->execute([$studentUserId]);
        $rows = $stmt->fetchAll() ?: [];
        if (!$rows) {
            return null;
        }

        $latest = $rows[0];
        $withThisHte = null;
        foreach ($rows as $row) {
            if ((int)$row['company_user_id'] === $partnerUserId) {
                $withThisHte = $row;
                break;
            }
        }
        if ($withThisHte === null) {
            return null;
        }

        $reassigned = (int)$latest['company_user_id'] !== $partnerUserId;

        return [
            'status' => (string)$withThisHte['status'],
            'predeployment_status' => (string)$withThisHte['predeployment_status'],
            'reassigned' => $reassigned,
        ];
    }

    private function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            throw new RuntimeException('Authentication required.');
        }
    }

    private function sanitizeMessage(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", strip_tags($text));
        $text = trim($text);
        $text = preg_replace("/[ \t]+/u", ' ', $text) ?? '';
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        if (mb_strlen($text) > self::MAX_MESSAGE_LENGTH) {
            $text = mb_substr($text, 0, self::MAX_MESSAGE_LENGTH);
        }

        return $text;
    }

    private function normalizeClientMessageId(?string $clientMessageId): ?string
    {
        $clientMessageId = trim((string)$clientMessageId);
        if ($clientMessageId === '') {
            return null;
        }
        if (!preg_match('/^[A-Za-z0-9._-]{8,64}$/', $clientMessageId)) {
            throw new InvalidArgumentException('Invalid message key.');
        }

        return $clientMessageId;
    }

    private function markConversationAsRead(int $partnerId, string $partnerRole): void
    {
        $stmt = $this->db->prepare(
            'UPDATE messages
             SET is_read = 1
             WHERE receiver_id = ? AND receiver_role = ?
               AND sender_id = ?
               AND is_read = 0'
        );
        $stmt->execute([$this->userId, $this->role, $partnerId]);
        $this->chatPartnersCache = null;
    }

    private function syncSessionContext(): void
    {
        if (!isset($_SESSION['user_id'], $_SESSION['role']) && isset($_SESSION['user'])) {
            $_SESSION['user_id'] = (int)($_SESSION['user']['id'] ?? 0);
            $_SESSION['role'] = (string)($_SESSION['user']['role'] ?? '');
        }
    }

    private function ensureSchema(): void
    {
        if ($this->schemaReady) {
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
        $this->migrateMessagesSchema();
        $this->schemaReady = true;
    }

    private function migrateMessagesSchema(): void
    {
        $this->addColumnIfMissing('messages', 'client_message_id', 'VARCHAR(64) NULL');
        $this->addColumnIfMissing('messages', 'reply_to_id', 'INT UNSIGNED NULL');
        $this->addColumnIfMissing('messages', 'delivery_status', "ENUM('sent') NOT NULL DEFAULT 'sent'");
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS chat_attachments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                message_id INT UNSIGNED NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                original_name VARCHAR(180) NOT NULL,
                mime VARCHAR(80) NOT NULL,
                byte_size INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_chat_attachments_message (message_id),
                INDEX idx_chat_attachments_path (file_path)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $this->addIndexIfMissing('messages', 'uq_messages_client', 'UNIQUE KEY uq_messages_client (sender_id, client_message_id)');
        $this->addColumnIfMissing('messages', 'is_pinned', 'TINYINT(1) NOT NULL DEFAULT 0');
        $this->addColumnIfMissing('messages', 'deleted_at', 'TIMESTAMP NULL DEFAULT NULL');
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS chat_message_reactions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                message_id INT UNSIGNED NOT NULL,
                user_id INT NOT NULL,
                user_role ENUM("admin","coordinator","student","partner") NOT NULL,
                emoji VARCHAR(16) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_chat_reaction (message_id, user_id, user_role),
                INDEX idx_chat_reaction_message (message_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        if ((int)$stmt->fetchColumn() === 0) {
            $this->db->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
        }
    }

    private function addIndexIfMissing(string $table, string $index, string $definition): void
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
        );
        $stmt->execute([$table, $index]);
        if ((int)$stmt->fetchColumn() === 0) {
            try {
                $this->db->exec('ALTER TABLE `' . $table . '` ADD ' . $definition);
            } catch (Throwable) {
            }
        }
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

    private function unreadSubquery(): string
    {
        return '(
            SELECT COUNT(*)
            FROM messages m
            WHERE m.receiver_id = ?
              AND m.receiver_role = ?
              AND m.sender_id = u.id
              AND m.sender_role = u.role
              AND m.is_read = 0
              AND m.deleted_at IS NULL
        ) AS unread_count';
    }

    /** @return list<array<string, mixed>> */
    private function partnersForAdmin(): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id AS user_id, u.name, u.email, u.role, "" AS course, ' . $this->unreadSubquery() . '
             FROM users u
             WHERE u.is_active = 1
               AND u.id != ?
               AND u.role IN ("coordinator", "partner")
             ORDER BY u.role, u.name'
        );
        $stmt->execute([$this->userId, $this->role, $this->userId]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    private function partnersForCoordinator(): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id AS user_id, u.name, u.email, u.role, u.course, ' . $this->unreadSubquery() . '
             FROM (
                 SELECT su.id, su.name, su.email, su.role, s.course
                 FROM students s
                 JOIN users su ON su.id = s.user_id
                 WHERE s.coordinator_id = ? AND su.is_active = 1

                 UNION

                 SELECT au.id, au.name, au.email, au.role, "" AS course
                 FROM users au
                 WHERE au.role = "admin" AND au.is_active = 1

                 UNION

                 SELECT pu.id, pu.name, pu.email, pu.role, "" AS course
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
            'SELECT u.id AS user_id, u.name, u.email, u.role, "" AS course, ' . $this->unreadSubquery() . '
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
            'SELECT u.id AS user_id, u.name, u.email, u.role, u.course, ' . $this->unreadSubquery() . '
             FROM (
                 SELECT su.id, su.name, su.email, su.role, s.course
                 FROM partner_companies pc
                 JOIN ojt_enrollments e ON e.company_id = pc.id
                 JOIN students s ON s.id = e.student_id
                 JOIN users su ON su.id = s.user_id
                 WHERE pc.user_id = ? AND su.is_active = 1

                 UNION

                 SELECT cu.id, cu.name, cu.email, cu.role, "" AS course
                 FROM partner_companies pc
                 JOIN ojt_enrollments e ON e.company_id = pc.id
                 JOIN students s ON s.id = e.student_id
                 JOIN users cu ON cu.id = s.coordinator_id
                 WHERE pc.user_id = ? AND cu.is_active = 1

                 UNION

                 SELECT au.id, au.name, au.email, au.role, "" AS course
                 FROM users au
                 WHERE au.role = "admin" AND au.is_active = 1
             ) u
             ORDER BY u.role, u.name'
        );
        $stmt->execute([$this->userId, $this->role, $this->userId, $this->userId]);

        return $stmt->fetchAll() ?: [];
    }

    /** @param list<array<string, mixed>> $rows */
    private function hydrateMessages(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn(array $row): int => (int)$row['id'], $rows);
        $attachments = $this->attachmentsForMessages($ids);
        $replies = $this->replyPreviews($rows);
        $reactions = $this->reactionsForMessages($ids);

        foreach ($rows as &$row) {
            $id = (int)$row['id'];
            $isDeleted = !empty($row['deleted_at']);
            $row['id'] = $id;
            $row['sender_id'] = (int)$row['sender_id'];
            $row['receiver_id'] = (int)$row['receiver_id'];
            $row['is_read'] = (int)$row['is_read'];
            $row['is_pinned'] = (int)($row['is_pinned'] ?? 0) === 1;
            $row['is_deleted'] = $isDeleted;
            $row['can_remove'] = !$isDeleted
                && (int)$row['sender_id'] === $this->userId
                && (string)$row['sender_role'] === $this->role;
            $row['delivery_status'] = (string)($row['delivery_status'] ?? 'sent');
            $row['attachments'] = $isDeleted ? [] : ($attachments[$id] ?? []);
            $row['reply'] = $isDeleted ? null : ($replies[(int)($row['reply_to_id'] ?? 0)] ?? null);
            $row['reactions'] = $isDeleted ? [] : ($reactions[$id] ?? []);
            if ($isDeleted) {
                $row['message_text'] = '';
            }
        }
        unset($row);

        return $rows;
    }

    /** @param list<int> $ids */
    private function attachmentsForMessages(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare(
                'SELECT id, message_id, file_path, original_name, mime, byte_size
                 FROM chat_attachments WHERE message_id IN (' . $placeholders . ')'
            );
            $stmt->execute($ids);
            $map = [];
            foreach ($stmt->fetchAll() ?: [] as $file) {
                $messageId = (int)$file['message_id'];
                $stored = ltrim(str_replace('\\', '/', (string)$file['file_path']), '/');
                if (str_starts_with($stored, 'uploads/')) {
                    $stored = substr($stored, strlen('uploads/'));
                }
                $map[$messageId][] = [
                    'id' => (int)$file['id'],
                    'url' => 'serve.php?f=' . rawurlencode($stored),
                    'name' => (string)$file['original_name'],
                    'mime' => (string)$file['mime'],
                    'size' => (int)$file['byte_size'],
                ];
            }

            return $map;
        } catch (Throwable) {
            return [];
        }
    }

    /** @param list<array<string, mixed>> $rows */
    private function replyPreviews(array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $replyId = (int)($row['reply_to_id'] ?? 0);
            if ($replyId > 0) {
                $ids[] = $replyId;
            }
        }
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            'SELECT m.id, m.message_text, m.deleted_at, u.name AS sender_name
             FROM messages m JOIN users u ON u.id = m.sender_id
             WHERE m.id IN (' . $placeholders . ')'
        );
        $stmt->execute($ids);
        $map = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $text = !empty($row['deleted_at']) ? 'Message removed' : trim((string)$row['message_text']);
            $map[(int)$row['id']] = [
                'id' => (int)$row['id'],
                'sender_name' => (string)$row['sender_name'],
                'text' => $text !== '' ? $text : 'Photo',
            ];
        }

        return $map;
    }

    /** @param list<int> $ids */
    private function reactionsForMessages(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare(
                'SELECT message_id, emoji, user_id, user_role
                 FROM chat_message_reactions
                 WHERE message_id IN (' . $placeholders . ')'
            );
            $stmt->execute($ids);
            $grouped = [];
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $messageId = (int)$row['message_id'];
                $emoji = (string)$row['emoji'];
                if (!isset($grouped[$messageId][$emoji])) {
                    $grouped[$messageId][$emoji] = ['emoji' => $emoji, 'count' => 0, 'mine' => false];
                }
                $grouped[$messageId][$emoji]['count']++;
                if ((int)$row['user_id'] === $this->userId && (string)$row['user_role'] === $this->role) {
                    $grouped[$messageId][$emoji]['mine'] = true;
                }
            }
            $map = [];
            foreach ($grouped as $messageId => $emojis) {
                $map[$messageId] = array_values($emojis);
            }

            return $map;
        } catch (Throwable) {
            return [];
        }
    }

    /** @return array<string, mixed> */
    private function requireConversationMessage(int $messageId, int $partnerId, string $partnerRole): array
    {
        if (!$this->messageBelongsToConversation($messageId, $partnerId, $partnerRole)) {
            throw new RuntimeException('You are not allowed to change this message.');
        }
        $row = $this->fetchMessageRow($messageId);
        if (!$row) {
            throw new RuntimeException('Message not found.');
        }

        return $row;
    }

    /** @return array<string, mixed>|null */
    private function fetchMessageRow(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT m.id, m.sender_id, m.sender_role, m.receiver_id, m.receiver_role,
                    m.message_text, m.client_message_id, m.reply_to_id, m.delivery_status,
                    m.is_read, m.is_pinned, m.deleted_at, m.created_at, u.name AS sender_name
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE m.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    private function findByClientMessageId(string $clientMessageId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT m.id FROM messages m
             WHERE m.sender_id = ? AND m.client_message_id = ?
             LIMIT 1'
        );
        $stmt->execute([$this->userId, $clientMessageId]);
        $id = (int)$stmt->fetchColumn();
        if ($id <= 0) {
            return null;
        }
        $hydrated = $this->hydrateMessages([$this->fetchMessageRow($id)]);

        return $hydrated[0] ?? null;
    }

    private function messageBelongsToConversation(int $messageId, int $partnerId, string $partnerRole): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM messages
             WHERE id = ? AND ((
                 sender_id = ? AND sender_role = ? AND receiver_id = ? AND receiver_role = ?
             ) OR (
                 sender_id = ? AND sender_role = ? AND receiver_id = ? AND receiver_role = ?
             )) LIMIT 1'
        );
        $stmt->execute([
            $messageId,
            $this->userId, $this->role, $partnerId, $partnerRole,
            $partnerId, $partnerRole, $this->userId, $this->role,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    /** @param list<array<string, mixed>> $uploadedFiles */
    private function storeAttachments(int $messageId, array $uploadedFiles): void
    {
        $uploadedFiles = array_values(array_filter(
            $uploadedFiles,
            static fn(array $file): bool => (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
                && trim((string)($file['name'] ?? '')) !== ''
        ));
        if ($uploadedFiles === []) {
            return;
        }
        if (count($uploadedFiles) > self::MAX_IMAGES) {
            throw new InvalidArgumentException('Maximum of 3 images per message.');
        }

        $dir = dirname(__DIR__) . '/uploads/chat/' . date('Y');
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to store the image.');
        }

        $insert = $this->db->prepare(
            'INSERT INTO chat_attachments (message_id, file_path, original_name, mime, byte_size)
             VALUES (?, ?, ?, ?, ?)'
        );

        foreach ($uploadedFiles as $file) {
            $validated = $this->validateImageUpload($file);
            $name = bin2hex(random_bytes(16)) . '.' . $validated['ext'];
            $absolute = $dir . '/' . $name;
            if (!move_uploaded_file($validated['tmp'], $absolute) && !rename($validated['tmp'], $absolute)) {
                if (!copy($validated['tmp'], $absolute)) {
                    throw new RuntimeException('Unable to store the image.');
                }
            }
            $insert->execute([
                $messageId,
                'uploads/chat/' . date('Y') . '/' . $name,
                $validated['original'],
                $validated['mime'],
                $validated['size'],
            ]);
        }
    }

    /** @param array<string, mixed> $file */
    private function validateImageUpload(array $file): array
    {
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Image upload failed.');
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        $size = (int)($file['size'] ?? 0);
        $original = basename((string)($file['name'] ?? 'image'));
        if ($tmp === '' || !is_file($tmp)) {
            throw new InvalidArgumentException('Image upload failed.');
        }
        if ($size <= 0 || $size > self::MAX_IMAGE_BYTES) {
            throw new InvalidArgumentException('Each image must be 5 MB or smaller.');
        }

        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if (!in_array($ext, self::IMAGE_EXTS, true)) {
            throw new InvalidArgumentException('Only JPG, PNG, and WebP images are allowed.');
        }

        $mime = (string)(mime_content_type($tmp) ?: '');
        $allowedMimes = [
            'jpg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
        ];
        if (!in_array($mime, $allowedMimes[$ext] ?? [], true)) {
            throw new InvalidArgumentException('The uploaded file is not a valid image.');
        }

        $header = (string)file_get_contents($tmp, false, null, 0, 16);
        $validMagic = match ($ext) {
            'jpg' => str_starts_with($header, "\xFF\xD8\xFF"),
            'png' => str_starts_with($header, "\x89PNG\r\n\x1A\n"),
            'webp' => str_starts_with($header, 'RIFF') && str_contains($header, 'WEBP'),
            default => false,
        };
        if (!$validMagic) {
            throw new InvalidArgumentException('The uploaded file is not a valid image.');
        }

        return [
            'tmp' => $tmp,
            'ext' => $ext,
            'mime' => $mime,
            'size' => $size,
            'original' => mb_substr($original, 0, 180),
        ];
    }

    private function isDuplicateClientKey(PDOException $exception): bool
    {
        return str_contains($exception->getMessage(), 'uq_messages_client')
            || (int)$exception->getCode() === 23000;
    }
}
