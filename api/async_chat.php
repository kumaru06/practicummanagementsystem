<?php
declare(strict_types=1);

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$chat = new ChatController();

if (!$chat->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        release_session_lock();
        $action = (string)($_GET['action'] ?? 'messages');

        if ($action === 'unread_total') {
            echo json_encode([
                'success' => true,
                'unread_total' => $chat->getUnreadTotal(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'partners') {
            echo json_encode([
                'success' => true,
                'partners' => $chat->getChatPartners(),
                'groups' => $chat->getPartnerGroups(),
                'unread_total' => $chat->getUnreadTotal(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $partnerId = (int)($_GET['partner_id'] ?? 0);
        $partnerRole = (string)($_GET['partner_role'] ?? '');

        if ($partnerId <= 0 || $partnerRole === '') {
            throw new InvalidArgumentException('Chat partner is required.');
        }

        if ($action === 'search') {
            echo json_encode([
                'success' => true,
                'results' => $chat->searchMessages($partnerId, $partnerRole, (string)($_GET['q'] ?? '')),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $beforeId = (int)($_GET['before_id'] ?? 0);
        $page = $chat->getMessages($partnerId, $partnerRole, $beforeId > 0 ? $beforeId : null);

        echo json_encode([
            'success' => true,
            'messages' => $page['messages'],
            'has_more' => $page['has_more'],
            'can_send' => $page['can_send'],
            'send_block_reason' => $page['send_block_reason'],
            'partner_id' => $partnerId,
            'partner_role' => $partnerRole,
            'typing' => $chat->getPartnerTypingStatus($partnerId, $partnerRole),
            'unreads' => $chat->getUnreadBadges(),
            'unread_total' => $chat->getUnreadTotal(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload) || isset($_FILES['images'])) {
            $payload = $_POST;
        } elseif (!empty($payload['csrf_token'])) {
            $_POST['csrf_token'] = (string)$payload['csrf_token'];
        }

        verify_csrf();

        $action = (string)($payload['action'] ?? 'send');
        $partnerId = (int)($payload['partner_id'] ?? $payload['receiver_id'] ?? 0);
        $partnerRole = (string)($payload['partner_role'] ?? $payload['receiver_role'] ?? '');

        if ($partnerId <= 0 || $partnerRole === '') {
            throw new InvalidArgumentException('Chat partner is required.');
        }

        if ($action === 'typing') {
            $chat->setTyping($partnerId, $partnerRole, (bool)($payload['is_typing'] ?? true));
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $messageId = (int)($payload['message_id'] ?? 0);

        if ($action === 'react') {
            $message = $chat->reactToMessage($partnerId, $partnerRole, $messageId, (string)($payload['emoji'] ?? ''));
            echo json_encode(['success' => true, 'message' => $message], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'pin') {
            $message = $chat->togglePinMessage($partnerId, $partnerRole, $messageId);
            echo json_encode(['success' => true, 'message' => $message], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'remove') {
            $message = $chat->removeMessage($partnerId, $partnerRole, $messageId);
            echo json_encode(['success' => true, 'message' => $message], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action !== 'send') {
            throw new InvalidArgumentException('Unknown chat action.');
        }

        $files = [];
        if (!empty($_FILES['images'])) {
            $bag = $_FILES['images'];
            if (is_array($bag['name'])) {
                foreach ($bag['name'] as $i => $name) {
                    if ((int)($bag['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $files[] = [
                        'name' => $name,
                        'type' => $bag['type'][$i] ?? '',
                        'tmp_name' => $bag['tmp_name'][$i] ?? '',
                        'error' => $bag['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $bag['size'][$i] ?? 0,
                    ];
                }
            } elseif ((int)($bag['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $files[] = $bag;
            }
        }

        $text = (string)($payload['message_text'] ?? $payload['message'] ?? $payload['text'] ?? '');
        $message = $chat->sendMessage(
            $partnerId,
            $partnerRole,
            $text,
            isset($payload['client_message_id']) ? (string)$payload['client_message_id'] : null,
            isset($payload['reply_to_id']) ? (int)$payload['reply_to_id'] : null,
            $files
        );

        echo json_encode([
            'success' => true,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
} catch (InvalidArgumentException $e) {
    error_log('[chat] ' . $e->getMessage());
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (RuntimeException $e) {
    error_log('[chat] ' . $e->getMessage());
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[chat] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to process this request.'], JSON_UNESCAPED_UNICODE);
}
