<?php
class ChatPageController extends BaseController
{
    public function interface(): void
    {
        require_login();

        try {
            $chat = new ChatController($this->db);
            if (!$chat->isAuthenticated()) {
                http_response_code(403);
                exit('Forbidden');
            }

            $allPartners = $chat->getChatPartners();
            $partnerGroups = $chat->getPartnerGroups();
        } catch (Throwable $e) {
            error_log('Chat page failed to load: ' . $e->getMessage());
            flash('error', 'Live Chat could not load. Please try again in a moment.');
            redirect('index.php?r=' . current_user()['role']);
        }

        $firstPartner = $allPartners[0] ?? null;
        $selectedPartnerId = (int)($_GET['partner_id'] ?? ($firstPartner['user_id'] ?? 0));
        $selectedPartnerRole = (string)($_GET['partner_role'] ?? ($firstPartner['role'] ?? ''));

        $selectedPartner = null;
        foreach ($allPartners as $partner) {
            if ((int)$partner['user_id'] === $selectedPartnerId && (string)$partner['role'] === $selectedPartnerRole) {
                $selectedPartner = $partner;
                break;
            }
        }

        if (!$selectedPartner && $allPartners) {
            $selectedPartner = $allPartners[0];
            $selectedPartnerId = (int)$selectedPartner['user_id'];
            $selectedPartnerRole = (string)$selectedPartner['role'];
        }

        $initialPage = [
            'messages' => [],
            'has_more' => false,
            'can_send' => false,
            'send_block_reason' => '',
        ];
        if ($selectedPartner) {
            try {
                $initialPage = $chat->getMessages($selectedPartnerId, $selectedPartnerRole);
            } catch (Throwable) {
            }
        }

        $allPartners = $chat->getChatPartners();
        $partnerGroups = $chat->getPartnerGroups();

        $this->renderAppPage('chat/interface', [
            'title' => 'Live Chat',
            'chat' => $chat,
            'partnerGroups' => $partnerGroups,
            'allPartners' => $allPartners,
            'selectedPartner' => $selectedPartner,
            'selectedPartnerId' => $selectedPartnerId,
            'selectedPartnerRole' => $selectedPartnerRole,
            'initialMessages' => $initialPage['messages'],
            'initialHasMore' => !empty($initialPage['has_more']),
            'canSend' => !empty($initialPage['can_send']),
            'sendBlockReason' => (string)($initialPage['send_block_reason'] ?? ''),
            'unreadTotal' => $chat->getUnreadTotal(),
            'chatEndpoint' => 'index.php?r=chat_api',
            'csrfToken' => csrf_token(),
            'topbarHint' => $chat->contactHint(),
        ]);
    }
}
