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
            flash('error', 'Live Chat could not load. Please import the chat migration SQL files in phpMyAdmin on InfinityFree.');
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

        $initialMessages = [];
        if ($selectedPartner) {
            try {
                $initialMessages = $chat->getMessages($selectedPartnerId, $selectedPartnerRole);
            } catch (Throwable) {
                $initialMessages = [];
            }
        }

        $this->renderAppPage('chat/interface', [
            'title' => 'Live Chat',
            'chat' => $chat,
            'partnerGroups' => $partnerGroups,
            'allPartners' => $allPartners,
            'selectedPartner' => $selectedPartner,
            'selectedPartnerId' => $selectedPartnerId,
            'selectedPartnerRole' => $selectedPartnerRole,
            'initialMessages' => $initialMessages,
            'chatEndpoint' => 'index.php?r=chat_api',
            'chatJsPath' => asset('assets/js/chat.js') . '?v=20260617-chat-switch-fix',
            'csrfToken' => csrf_token(),
        ]);
    }
}
