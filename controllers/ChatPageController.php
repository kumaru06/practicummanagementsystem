<?php
class ChatPageController extends BaseController
{
    public function interface(): void
    {
        require_login();

        $chat = new ChatController($this->db);
        if (!$chat->isAuthenticated()) {
            http_response_code(403);
            exit('Forbidden');
        }

        $allPartners = $chat->getChatPartners();
        $partnerGroups = $chat->getPartnerGroups();

        $selectedPartnerId = (int)($_GET['partner_id'] ?? ($allPartners[0]['user_id'] ?? 0));
        $selectedPartnerRole = (string)($_GET['partner_role'] ?? ($allPartners[0]['role'] ?? ''));

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

        $initialMessages = [];
        if ($selectedPartner) {
            try {
                $initialMessages = $chat->getMessages($selectedPartnerId, $selectedPartnerRole);
            } catch (Throwable) {
                $initialMessages = [];
            }
        }

        $this->render('chat/interface', [
            'title' => 'Live Chat',
            'chat' => $chat,
            'partnerGroups' => $partnerGroups,
            'allPartners' => $allPartners,
            'selectedPartner' => $selectedPartner,
            'selectedPartnerId' => $selectedPartnerId,
            'selectedPartnerRole' => $selectedPartnerRole,
            'initialMessages' => $initialMessages,
            'chatEndpoint' => app_base_path() . '/api/async_chat.php',
            'chatJsPath' => asset('assets/js/chat.js') . '?v=20260617-chat-switch-fix',
            'csrfToken' => csrf_token(),
        ]);
    }
}
