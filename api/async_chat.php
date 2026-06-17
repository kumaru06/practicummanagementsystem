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
        $action = $_GET['action'] ?? 'messages';

        if ($action === 'partners') {
            echo json_encode([
                'success' => true,
                'partners' => $chat->getChatPartners(),
                'groups' => $chat->getPartnerGroups(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $partnerId = (int)($_GET['partner_id'] ?? 0);
        $partnerRole = (string)($_GET['partner_role'] ?? '');

        if ($partnerId <= 0 || $partnerRole === '') {
            throw new InvalidArgumentException('Chat partner is required.');
        }

        echo json_encode([
            'success' => true,
            'messages' => $chat->getMessages($partnerId, $partnerRole),
            'partner_id' => $partnerId,
            'partner_role' => $partnerRole,
            'typing' => $chat->getPartnerTypingStatus($partnerId, $partnerRole),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($payload)) {
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

        $text = (string)($payload['message_text'] ?? $payload['message'] ?? $payload['text'] ?? '');

        $message = $chat->sendMessage($partnerId, $partnerRole, $text);

        echo json_encode([
            'success' => true,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
} catch (Throwable $e) {
    $code = $e instanceof InvalidArgumentException ? 422 : 403;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
