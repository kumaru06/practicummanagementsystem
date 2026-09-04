<?php
$roleLabelFor = static function (string $role, ?string $subtitle = null): string {
    if ($subtitle) {
        return $subtitle;
    }
    return match ($role) {
        'partner' => 'Host Training Establishment',
        'admin' => 'Administrator',
        'coordinator' => 'Coordinator',
        'student' => 'Student',
        default => ucwords(str_replace('_', ' ', $role)),
    };
};
$formatWhen = static function (?string $datetime): string {
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }
    if (date('Y-m-d', $ts) === date('Y-m-d')) {
        return date('g:i A', $ts);
    }
    if (date('Y-m-d', $ts) === date('Y-m-d', strtotime('-1 day'))) {
        return 'Yesterday';
    }
    return date('M j', $ts);
};
$initialsFor = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $parts = array_values(array_filter($parts, static fn(string $part): bool => $part !== ''));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }

    return strtoupper(substr($name !== '' ? $name : 'C', 0, 2));
};
?>
<div class="chat-app" id="chatApp"
     data-endpoint="<?= e($chatEndpoint) ?>"
     data-csrf="<?= e($csrfToken) ?>"
     data-user-id="<?= (int)$chat->currentUserId() ?>"
     data-user-role="<?= e($chat->currentRole()) ?>"
     data-partner-id="<?= (int)$selectedPartnerId ?>"
     data-partner-role="<?= e($selectedPartnerRole) ?>"
     data-can-send="<?= !empty($canSend) ? '1' : '0' ?>"
     data-has-more="<?= !empty($initialHasMore) ? '1' : '0' ?>"
     data-unread-total="<?= (int)($unreadTotal ?? 0) ?>">

    <div class="chat-shell<?= $selectedPartner ? ' has-conversation' : '' ?>" id="chatShell">
        <aside class="chat-sidebar" aria-label="Conversations">
            <div class="chat-sidebar__head">
                <div>
                    <h3>Messages</h3>
                </div>
                <div class="chat-app__hero-meta" id="chatConnectionStatus" data-state="connected" title="Connection status">
                    <span class="chat-app__status-dot" aria-hidden="true"></span>
                    <span class="chat-app__status-label">
                        <strong id="chatConnectionLabel">Connected</strong>
                    </span>
                </div>
            </div>

            <div class="chat-sidebar__search">
                <label class="chat-sidebar__search-field" for="chatPartnerSearch">
                    <svg class="chat-stroke-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input type="search" id="chatPartnerSearch" placeholder="Search contacts..." autocomplete="off">
                </label>
                <div class="chat-sidebar__filters" role="tablist">
                    <button type="button" class="chat-filter is-active" data-chat-filter="all">All</button>
                    <button type="button" class="chat-filter" data-chat-filter="unread">Unread</button>
                </div>
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
                    <section class="chat-group" data-role-group="<?= e((string)$group['role']) ?>">
                        <h4><?= e($group['label']) ?></h4>
                        <?php foreach ($group['partners'] as $partner): ?>
                            <?php
                            $isActive = (int)$partner['user_id'] === $selectedPartnerId
                                && (string)$partner['role'] === $selectedPartnerRole;
                            $initial = $initialsFor((string)$partner['name']);
                            $unread = (int)($partner['unread_count'] ?? 0);
                            $avatarRole = preg_replace('/[^a-z]/', '', strtolower((string)$partner['role'])) ?: 'user';
                            $preview = !empty($partner['last_message_is_photo'])
                                ? 'Photo'
                                : (string)($partner['last_message'] ?? '');
                            $when = $formatWhen($partner['last_message_at'] ?? null);
                            ?>
                            <button type="button"
                                    class="chat-partner<?= $isActive ? ' is-active' : '' ?><?= $unread > 0 ? ' has-unread' : '' ?>"
                                    data-partner-id="<?= (int)$partner['user_id'] ?>"
                                    data-partner-role="<?= e((string)$partner['role']) ?>"
                                    data-partner-name="<?= e((string)$partner['name']) ?>"
                                    data-partner-email="<?= e((string)$partner['email']) ?>"
                                    data-partner-subtitle="<?= e((string)($partner['subtitle'] ?? '')) ?>"
                                    data-can-send="<?= !empty($partner['can_send']) ? '1' : '0' ?>"
                                    data-send-block="<?= e((string)($partner['send_block_reason'] ?? '')) ?>">
                                <span class="chat-partner__avatar is-<?= e($avatarRole) ?>" aria-hidden="true"><?= e($initial) ?></span>
                                <span class="chat-partner__copy">
                                    <span class="chat-partner__row">
                                        <strong><?= e((string)$partner['name']) ?></strong>
                                        <?php if ($when !== ''): ?><time><?= e($when) ?></time><?php endif; ?>
                                    </span>
                                    <small class="chat-partner__preview"><?= e($preview !== '' ? $preview : $roleLabelFor((string)$partner['role'], $partner['subtitle'] ?? null)) ?></small>
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
                $activeRole = (string)$selectedPartner['role'];
                $activeSubtitle = (string)($selectedPartner['subtitle'] ?? $roleLabelFor($activeRole));
                $activeInitial = $initialsFor((string)$selectedPartner['name']);
                $activeAvatarRole = preg_replace('/[^a-z]/', '', strtolower($activeRole)) ?: 'user';
                ?>
                <header class="chat-window__head">
                    <button type="button" class="chat-window__back" id="chatBackBtn" aria-label="Back to contacts">
                        <svg class="chat-stroke-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <div class="chat-window__partner">
                        <span class="chat-window__avatar-wrap">
                            <span class="chat-window__avatar is-<?= e($activeAvatarRole) ?>" id="chatActiveAvatar"><?= e($activeInitial) ?></span>
                        </span>
                        <div class="chat-window__partner-copy">
                            <strong id="chatActiveName"><?= e((string)$selectedPartner['name']) ?></strong>
                            <small id="chatActiveMeta"><?= e($activeSubtitle) ?></small>
                        </div>
                    </div>
                    <div class="chat-window__head-actions">
                        <span class="chat-window__tag" id="chatActiveTag"><?= e(strtoupper($activeRole === 'partner' ? 'HTE' : $activeRole)) ?></span>
                        <button type="button" class="chat-icon-btn" id="chatThreadSearchBtn" aria-label="Search this conversation">
                            <svg class="chat-stroke-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        </button>
                    </div>
                </header>
                <div class="chat-pin-bar" id="chatPinBar" hidden>
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2Z"/></svg>
                    <span id="chatPinText">Pinned message</span>
                    <button type="button" id="chatPinJump">View</button>
                </div>
                <div class="chat-thread-search" id="chatThreadSearch" hidden>
                    <input type="search" id="chatThreadSearchInput" placeholder="Search this conversation..." autocomplete="off">
                    <div class="chat-search-hits" id="chatSearchHits" hidden></div>
                </div>

                <div class="chat-window__messages" id="chatMessages" aria-live="polite">
                    <?php if (!$initialMessages): ?>
                        <div class="chat-empty-state chat-empty-state--inline" id="chatEmptyState">
                            <div class="chat-empty-state__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v10H7l-3 3V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                            </div>
                            <p>No messages yet. Send a message to <strong><?= e((string)$selectedPartner['name']) ?></strong>.</p>
                        </div>
                    <?php else: ?>
                        <?php
                        $lastDateKey = null;
                        $prevMine = null;
                        $lastMineReceiptId = 0;
                        foreach ($initialMessages as $probe) {
                            $probeMine = (int)$probe['sender_id'] === (int)$chat->currentUserId()
                                && (string)$probe['sender_role'] === (string)$chat->currentRole();
                            if ($probeMine && empty($probe['is_deleted'])) {
                                $lastMineReceiptId = (int)$probe['id'];
                            }
                        }
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
                            <article class="chat-message<?= $isMine ? ' is-mine' : ' is-theirs' ?><?= $isGrouped ? ' is-grouped' : '' ?><?= $showAvatar ? ' has-avatar' : '' ?><?= !empty($message['is_pinned']) ? ' is-pinned' : '' ?><?= !empty($message['is_deleted']) ? ' is-deleted' : '' ?>"
                                     data-message-id="<?= (int)$message['id'] ?>"
                                     data-is-read="<?= (int)($message['is_read'] ?? 0) ?>"
                                     data-pinned="<?= !empty($message['is_pinned']) ? '1' : '0' ?>">
                                <?php if (!$isMine): ?>
                                    <?php if ($showAvatar): ?>
                                        <span class="chat-message__avatar is-<?= e($activeAvatarRole) ?>" aria-hidden="true"><?= e($activeInitial) ?></span>
                                    <?php else: ?>
                                        <span class="chat-message__avatar-spacer" aria-hidden="true"></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="chat-message__stack">
                                    <div class="chat-message__main">
                                    <?php if (!empty($message['is_deleted'])): ?>
                                        <div class="chat-message__removed">Message removed</div>
                                    <?php else: ?>
                                    <?php if (!empty($message['is_pinned'])): ?>
                                        <span class="chat-message__pin-flag">Pinned</span>
                                    <?php endif; ?>
                                    <?php if (!empty($message['reply'])): ?>
                                        <div class="chat-message__quote">
                                            <strong><?= e((string)$message['reply']['sender_name']) ?></strong>
                                            <span><?= e((string)$message['reply']['text']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($message['attachments'])): ?>
                                        <div class="chat-media-list">
                                            <?php foreach ($message['attachments'] as $file): ?>
                                                <button type="button" class="chat-media" data-chat-lightbox="<?= e((string)$file['url']) ?>" data-chat-name="<?= e((string)$file['name']) ?>">
                                                    <img src="<?= e((string)$file['url']) ?>" alt="<?= e((string)$file['name']) ?>">
                                                    <span><?= e((string)$file['name']) ?></span>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (trim((string)($message['message_text'] ?? '')) !== ''): ?>
                                        <div class="chat-message__bubble">
                                            <p><?= e((string)$message['message_text']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php
                                    $replyText = trim((string)($message['message_text'] ?? '')) !== ''
                                        ? (string)$message['message_text']
                                        : 'Photo';
                                    ?>
                                    <div class="chat-message__tools">
                                        <button type="button" class="chat-tool-btn" data-chat-react="<?= (int)$message['id'] ?>" aria-label="Add reaction">
                                            <svg class="chat-stroke-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>
                                        </button>
                                        <button type="button"
                                                class="chat-tool-btn"
                                                data-chat-more="<?= (int)$message['id'] ?>"
                                                data-reply-name="<?= e((string)($message['sender_name'] ?? '')) ?>"
                                                data-reply-text="<?= e($replyText) ?>"
                                                data-reply-self="<?= $isMine ? '1' : '0' ?>"
                                                data-can-remove="<?= !empty($message['can_remove']) ? '1' : '0' ?>"
                                                data-pinned="<?= !empty($message['is_pinned']) ? '1' : '0' ?>"
                                                aria-label="More actions">
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    </div>
                                    <?php if (!empty($message['reactions'])): ?>
                                        <div class="chat-reaction-row">
                                            <?php foreach ($message['reactions'] as $reaction): ?>
                                                <button type="button"
                                                        class="chat-reaction-chip<?= !empty($reaction['mine']) ? ' is-mine' : '' ?>"
                                                        data-chat-emoji="<?= e((string)$reaction['emoji']) ?>"
                                                        data-message-id="<?= (int)$message['id'] ?>">
                                                    <?= e((string)$reaction['emoji']) ?>
                                                    <span><?= (int)$reaction['count'] ?></span>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <time datetime="<?= e($createdAt) ?>"><?= e($ts ? date('g:i A', $ts) : $createdAt) ?></time>
                                    <?php if ($isMine && empty($message['is_deleted']) && (int)$message['id'] === $lastMineReceiptId): ?>
                                        <span class="chat-message__receipt"><?= !empty($message['is_read']) ? 'Seen' : 'Sent' ?></span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php
                            $prevMine = $isMine;
                        endforeach;
                        ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="chat-jump" id="chatJumpBtn" hidden>New messages ↓</button>

                <div class="chat-typing-indicator" id="chatTypingIndicator" hidden aria-live="polite">
                    <span class="chat-typing-indicator__bubble" aria-hidden="true">
                        <span class="chat-typing-indicator__dots"><span></span><span></span><span></span></span>
                    </span>
                    <span id="chatTypingLabel"><?= e((string)$selectedPartner['name']) ?> is typing...</span>
                </div>

                <form class="chat-window__composer" id="chatComposer" action="#" method="post" onsubmit="return false;">
                    <div class="chat-reply-bar" id="chatReplyBar" hidden>
                        <div class="chat-reply-bar__copy">
                            <strong id="chatReplyName">Replying to</strong>
                            <span id="chatReplyText"></span>
                        </div>
                        <button type="button" class="chat-reply-bar__close" id="chatReplyClear" aria-label="Cancel reply">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </button>
                    </div>
                    <div class="chat-attach-list" id="chatAttachList" hidden></div>
                    <?php if (empty($canSend)): ?>
                        <p class="chat-readonly" id="chatReadonlyNote"><?= e($sendBlockReason !== '' ? $sendBlockReason : 'This conversation is read-only.') ?></p>
                    <?php else: ?>
                        <p class="chat-readonly" id="chatReadonlyNote" hidden></p>
                    <?php endif; ?>
                    <input type="file" id="chatFileInput" accept="image/jpeg,image/png,image/webp" multiple hidden>
                    <div class="chat-window__composer-row">
                        <button type="button" class="chat-attach-btn" id="chatAttachBtn" aria-label="Attach image" <?= empty($canSend) ? 'disabled' : '' ?>>
                            <svg class="chat-stroke-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                        </button>
                        <label class="chat-window__composer-field" for="chatMessageInput">
                            <textarea id="chatMessageInput"
                                      rows="1"
                                      maxlength="2000"
                                      placeholder="Add a message..."
                                      <?= empty($canSend) ? 'disabled' : '' ?>></textarea>
                        </label>
                        <button type="submit" class="chat-send-btn" id="chatSendBtn" <?= empty($canSend) ? 'disabled' : '' ?> aria-label="Send message">
                            <span>Send</span>
                            <svg class="chat-stroke-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                        </button>
                    </div>
                    <div class="chat-composer__meta">
                        <small>Enter to send · Shift + Enter for a new line</small>
                        <span class="char-counter" id="chatCharCount">0 / 2000</span>
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
<div class="chat-lightbox" id="chatLightbox" hidden>
    <button type="button" class="chat-lightbox__close" id="chatLightboxClose" aria-label="Close">×</button>
    <img id="chatLightboxImage" alt="">
    <a class="chat-lightbox__download" id="chatLightboxDownload" href="#" download>Download</a>
</div>
<div class="chat-react-pop" id="chatReactPop" hidden>
    <button type="button" data-emoji="👍" aria-label="Thumbs up">👍</button>
    <button type="button" data-emoji="❤️" aria-label="Heart">❤️</button>
    <button type="button" data-emoji="😂" aria-label="Laugh">😂</button>
    <button type="button" data-emoji="😮" aria-label="Wow">😮</button>
    <button type="button" data-emoji="😢" aria-label="Sad">😢</button>
    <button type="button" data-emoji="🙏" aria-label="Pray">🙏</button>
</div>
<div class="chat-more-pop" id="chatMorePop" hidden>
    <button type="button" data-chat-act="reply">Reply</button>
    <button type="button" data-chat-act="pin" id="chatMorePin">Pin</button>
    <button type="button" data-chat-act="remove" id="chatMoreRemove">Remove</button>
</div>
