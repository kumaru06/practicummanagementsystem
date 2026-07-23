<div class="chat-app" id="chatApp"
     data-endpoint="<?= e($chatEndpoint) ?>"
     data-csrf="<?= e($csrfToken) ?>"
     data-user-id="<?= (int)$chat->currentUserId() ?>"
     data-user-role="<?= e($chat->currentRole()) ?>"
     data-partner-id="<?= (int)$selectedPartnerId ?>"
     data-partner-role="<?= e($selectedPartnerRole) ?>">

    <div class="chat-app__toolbar">
        <div class="chat-app__toolbar-copy">
            <span class="chat-app__eyebrow">Practicum Messaging</span>
            <p>Message coordinators, students, and host training establishments in real time.</p>
        </div>
        <div class="chat-app__toolbar-actions">
            <div class="chat-app__hero-meta" title="Messages refresh automatically">
                <span class="chat-app__status-dot" aria-hidden="true"></span>
                <span class="chat-app__status-label">
                    <strong>Live</strong>
                    <small>Every 3s</small>
                </span>
            </div>
        </div>
    </div>

    <div class="chat-shell">
        <aside class="chat-sidebar" aria-label="Conversations">
            <div class="chat-sidebar__head">
                <div>
                    <h3>Messages</h3>
                    <span class="chat-sidebar__count"><?= count($allPartners) ?> contacts</span>
                </div>
            </div>

            <div class="chat-sidebar__search">
                <label class="chat-sidebar__search-field" for="chatPartnerSearch">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/>
                        <path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <input type="search" id="chatPartnerSearch" placeholder="Search chats..." autocomplete="off">
                </label>
            </div>

            <div class="chat-sidebar__list" id="chatPartnerList">
                <?php if (!$partnerGroups): ?>
                    <div class="chat-empty-state">
                        <div class="chat-empty-state__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v10H7l-3 3V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                        </div>
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
                            $roleLabel = ucwords(str_replace('_', ' ', (string)$partner['role']));
                            $unread = (int)($partner['unread_count'] ?? 0);
                            $tone = (ord($initial) % 3) + 1;
                            ?>
                            <button type="button"
                                    class="chat-partner<?= $isActive ? ' is-active' : '' ?><?= $unread > 0 ? ' has-unread' : '' ?>"
                                    data-partner-id="<?= (int)$partner['user_id'] ?>"
                                    data-partner-role="<?= e((string)$partner['role']) ?>"
                                    data-partner-name="<?= e((string)$partner['name']) ?>"
                                    data-partner-email="<?= e((string)$partner['email']) ?>">
                                <span class="chat-partner__avatar chat-partner__avatar--tone-<?= $tone ?>" aria-hidden="true"><?= e($initial) ?></span>
                                <span class="chat-partner__copy">
                                    <span class="chat-partner__row">
                                        <strong><?= e((string)$partner['name']) ?></strong>
                                    </span>
                                    <small><?= e($roleLabel) ?></small>
                                </span>
                                <?php if ($unread > 0): ?>
                                    <span class="chat-partner__badge"><?= $unread ?></span>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </aside>

        <section class="chat-window" aria-label="Chat window">
            <?php if ($selectedPartner): ?>
                <?php
                $activeRoleLabel = ucwords(str_replace('_', ' ', (string)$selectedPartner['role']));
                $activeInitial = strtoupper(substr((string)$selectedPartner['name'], 0, 1));
                ?>
                <header class="chat-window__head">
                    <div class="chat-window__partner">
                        <span class="chat-window__avatar-wrap">
                            <span class="chat-window__avatar" id="chatActiveAvatar"><?= e($activeInitial) ?></span>
                            <span class="chat-window__presence" aria-hidden="true"></span>
                        </span>
                        <div class="chat-window__partner-copy">
                            <strong id="chatActiveName"><?= e((string)$selectedPartner['name']) ?></strong>
                            <small id="chatActiveMeta"><?= e($activeRoleLabel) ?> · <?= e((string)$selectedPartner['email']) ?></small>
                        </div>
                    </div>
                    <span class="chat-window__tag" id="chatActiveTag"><?= e(strtoupper((string)$selectedPartner['role'])) ?></span>
                </header>

                <div class="chat-window__messages" id="chatMessages" aria-live="polite">
                    <?php if (!$initialMessages): ?>
                        <div class="chat-empty-state chat-empty-state--inline" id="chatEmptyState">
                            <div class="chat-empty-state__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v10H7l-3 3V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                            </div>
                            <p>Start the conversation with <strong><?= e((string)$selectedPartner['name']) ?></strong>.</p>
                        </div>
                    <?php else: ?>
                        <?php
                        $lastDateKey = null;
                        $prevMine = null;
                        foreach ($initialMessages as $index => $message):
                            $isMine = (int)$message['sender_id'] === (int)$chat->currentUserId()
                                && (string)$message['sender_role'] === (string)$chat->currentRole();
                            $createdAt = (string)$message['created_at'];
                            $ts = strtotime($createdAt);
                            $dateKey = $ts ? date('Y-m-d', $ts) : $createdAt;
                            $next = $initialMessages[$index + 1] ?? null;
                            $nextMine = null;
                            $nextDateKey = null;
                            if ($next) {
                                $nextMine = (int)$next['sender_id'] === (int)$chat->currentUserId()
                                    && (string)$next['sender_role'] === (string)$chat->currentRole();
                                $nextTs = strtotime((string)$next['created_at']);
                                $nextDateKey = $nextTs ? date('Y-m-d', $nextTs) : (string)$next['created_at'];
                            }
                            $showAvatar = !$isMine && ($next === null || $nextMine === true || $nextDateKey !== $dateKey);
                            $isGrouped = $prevMine !== null && $prevMine === $isMine && $dateKey === $lastDateKey;
                            if ($dateKey !== $lastDateKey):
                                $lastDateKey = $dateKey;
                                $dayLabel = 'Today';
                                if ($ts) {
                                    $today = date('Y-m-d');
                                    $yesterday = date('Y-m-d', strtotime('-1 day'));
                                    if ($dateKey === $yesterday) {
                                        $dayLabel = 'Yesterday';
                                    } elseif ($dateKey !== $today) {
                                        $dayLabel = date('M j, Y', $ts);
                                    }
                                }
                                $isGrouped = false;
                        ?>
                            <div class="chat-day-divider" role="separator">
                                <span><?= e($dayLabel) ?></span>
                            </div>
                        <?php endif; ?>
                            <article class="chat-message<?= $isMine ? ' is-mine' : ' is-theirs' ?><?= $isGrouped ? ' is-grouped' : '' ?><?= $showAvatar ? ' has-avatar' : '' ?>">
                                <?php if (!$isMine): ?>
                                    <?php if ($showAvatar): ?>
                                        <span class="chat-message__avatar" aria-hidden="true"><?= e($activeInitial) ?></span>
                                    <?php else: ?>
                                        <span class="chat-message__avatar-spacer" aria-hidden="true"></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="chat-message__stack">
                                    <div class="chat-message__bubble">
                                        <p><?= e((string)$message['message_text']) ?></p>
                                    </div>
                                    <time datetime="<?= e($createdAt) ?>">
                                        <?= e($ts ? date('g:i A', $ts) : $createdAt) ?>
                                    </time>
                                </div>
                            </article>
                        <?php
                            $prevMine = $isMine;
                        endforeach;
                        ?>
                    <?php endif; ?>
                </div>

                <div class="chat-typing-indicator" id="chatTypingIndicator" hidden aria-live="polite">
                    <span class="chat-typing-indicator__bubble" aria-hidden="true">
                        <span class="chat-typing-indicator__dots"><span></span><span></span><span></span></span>
                    </span>
                    <span id="chatTypingLabel"><?= e((string)$selectedPartner['name']) ?> is typing...</span>
                </div>

                <form class="chat-window__composer" id="chatComposer" action="#" method="post" onsubmit="return false;">
                    <label class="sr-only" for="chatMessageInput">Message</label>
                    <div class="chat-window__composer-shell">
                        <div class="chat-window__composer-field">
                            <textarea id="chatMessageInput"
                                      rows="1"
                                      maxlength="2000"
                                      placeholder="Type a new message..."
                                      required></textarea>
                        </div>
                        <button type="submit" class="chat-send-btn" id="chatSendBtn" aria-label="Send message" title="Send">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.4 20.6 21 12 3.4 3.4 3.3 10l11.2 2L3.3 14z"/></svg>
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="chat-empty-state chat-empty-state--window">
                    <div class="chat-empty-state__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v10H7l-3 3V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    </div>
                    <h3>Select a conversation</h3>
                    <p>Choose a contact from the sidebar to begin chatting.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
