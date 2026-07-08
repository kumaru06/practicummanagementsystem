<div class="chat-app" id="chatApp"
     data-endpoint="<?= e($chatEndpoint) ?>"
     data-csrf="<?= e($csrfToken) ?>"
     data-user-id="<?= (int)$chat->currentUserId() ?>"
     data-user-role="<?= e($chat->currentRole()) ?>"
     data-partner-id="<?= (int)$selectedPartnerId ?>"
     data-partner-role="<?= e($selectedPartnerRole) ?>">

    <header class="chat-app__hero">
        <div>
            <span class="chat-app__eyebrow">Practicum Messaging</span>
            <h2>Live Chat</h2>
            <p>Message your coordinator, students, or host training establishments in real time.</p>
        </div>
        <div class="chat-app__hero-meta">
            <span class="chat-app__status-dot" aria-hidden="true"></span>
            <span>Polling every 3 seconds</span>
        </div>
    </header>

    <div class="chat-shell">
        <aside class="chat-sidebar card-panel" aria-label="Conversations">
            <div class="chat-sidebar__head">
                <h3>Conversations</h3>
                <span class="chat-sidebar__count"><?= count($allPartners) ?> contacts</span>
            </div>

            <div class="chat-sidebar__search">
                <input type="search" id="chatPartnerSearch" placeholder="Search contacts..." autocomplete="off">
            </div>

            <div class="chat-sidebar__list" id="chatPartnerList">
                <?php if (!$partnerGroups): ?>
                    <div class="chat-empty-state">
                        <p>No chat contacts available for your role yet.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($partnerGroups as $group): ?>
                    <section class="chat-group">
                        <h4><?= e($group['label']) ?></h4>
                        <?php foreach ($group['partners'] as $partner): ?>
                            <?php
                            $isActive = (int)$partner['user_id'] === $selectedPartnerId
                                && (string)$partner['role'] === $selectedPartnerRole;
                            $initial = strtoupper(substr((string)$partner['name'], 0, 1));
                            ?>
                            <button type="button"
                                    class="chat-partner<?= $isActive ? ' is-active' : '' ?>"
                                    data-partner-id="<?= (int)$partner['user_id'] ?>"
                                    data-partner-role="<?= e((string)$partner['role']) ?>"
                                    data-partner-name="<?= e((string)$partner['name']) ?>"
                                    data-partner-email="<?= e((string)$partner['email']) ?>">
                                <span class="chat-partner__avatar"><?= e($initial) ?></span>
                                <span class="chat-partner__copy">
                                    <strong><?= e((string)$partner['name']) ?></strong>
                                    <small><?= e(ucwords(str_replace('_', ' ', (string)$partner['role']))) ?></small>
                                </span>
                                <?php if ((int)($partner['unread_count'] ?? 0) > 0): ?>
                                    <span class="chat-partner__badge"><?= (int)$partner['unread_count'] ?></span>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </aside>

        <section class="chat-window card-panel" aria-label="Chat window">
            <?php if ($selectedPartner): ?>
                <header class="chat-window__head">
                    <div class="chat-window__partner">
                        <span class="chat-window__avatar" id="chatActiveAvatar"><?= e(strtoupper(substr((string)$selectedPartner['name'], 0, 1))) ?></span>
                        <div>
                            <strong id="chatActiveName"><?= e((string)$selectedPartner['name']) ?></strong>
                            <small id="chatActiveMeta"><?= e(ucwords(str_replace('_', ' ', (string)$selectedPartner['role']))) ?> Â· <?= e((string)$selectedPartner['email']) ?></small>
                        </div>
                    </div>
                    <span class="chat-window__tag" id="chatActiveTag"><?= e(strtoupper((string)$selectedPartner['role'])) ?></span>
                </header>

                <div class="chat-window__messages" id="chatMessages" aria-live="polite">
                    <?php if (!$initialMessages): ?>
                        <div class="chat-empty-state chat-empty-state--inline" id="chatEmptyState">
                            <p>Start the conversation with <?= e((string)$selectedPartner['name']) ?>.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($initialMessages as $message): ?>
                        <?php
                        $isMine = (int)$message['sender_id'] === (int)$chat->currentUserId()
                            && (string)$message['sender_role'] === (string)$chat->currentRole();
                        ?>
                        <article class="chat-message<?= $isMine ? ' is-mine' : ' is-theirs' ?>">
                            <div class="chat-message__bubble">
                                <p><?= e((string)$message['message_text']) ?></p>
                            </div>
                            <time datetime="<?= e((string)$message['created_at']) ?>">
                                <?= e(date('M j, g:i A', strtotime((string)$message['created_at']))) ?>
                            </time>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="chat-typing-indicator" id="chatTypingIndicator" hidden aria-live="polite">
                    <span class="chat-typing-indicator__dots" aria-hidden="true"><span></span><span></span><span></span></span>
                    <span id="chatTypingLabel"><?= e((string)$selectedPartner['name']) ?> is typing...</span>
                </div>

                <form class="chat-window__composer" id="chatComposer" action="#" method="post" onsubmit="return false;">
                    <label class="sr-only" for="chatMessageInput">Message</label>
                    <textarea id="chatMessageInput"
                              rows="2"
                              maxlength="2000"
                              placeholder="Type your message..."
                              required></textarea>
                    <button type="submit" class="chat-send-btn" id="chatSendBtn">
                        <span>Send</span>
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 12 14-7-4 7 4 7-14-7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    </button>
                </form>
            <?php else: ?>
                <div class="chat-empty-state chat-empty-state--window">
                    <h3>Select a conversation</h3>
                    <p>Choose a contact from the sidebar to begin chatting.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
