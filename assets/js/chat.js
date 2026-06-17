/**
 * AMA Practicum Live Chat
 * Vanilla JS + Fetch API with 3-second polling (no WebSockets).
 */
(function () {
    'use strict';

    const app = document.getElementById('chatApp');
    if (!app) return;

    const endpoint = app.dataset.endpoint || 'api/async_chat.php';
    const csrfToken = app.dataset.csrf || '';
    const currentUserId = Number(app.dataset.userId || 0);
    const currentUserRole = app.dataset.userRole || '';

    let partnerId = Number(app.dataset.partnerId || 0);
    let partnerRole = app.dataset.partnerRole || '';
    let partnerName = document.getElementById('chatActiveName')?.textContent?.trim() || 'Contact';

    const messagesEl = document.getElementById('chatMessages');
    const composerEl = document.getElementById('chatComposer');
    const inputEl = document.getElementById('chatMessageInput');
    const sendBtn = document.getElementById('chatSendBtn');
    const partnerListEl = document.getElementById('chatPartnerList');
    const searchEl = document.getElementById('chatPartnerSearch');
    const activeNameEl = document.getElementById('chatActiveName');
    const activeMetaEl = document.getElementById('chatActiveMeta');
    const activeAvatarEl = document.getElementById('chatActiveAvatar');
    const activeTagEl = document.getElementById('chatActiveTag');
    const typingIndicatorEl = document.getElementById('chatTypingIndicator');
    const typingLabelEl = document.getElementById('chatTypingLabel');

    let pollTimer = null;
    let typingPulseTimer = null;
    let messageFetchController = null;
    let isSending = false;
    let isTypingActive = false;
    let lastMessageCount = messagesEl ? messagesEl.querySelectorAll('.chat-message').length : 0;
    let lastMessageSignature = '';

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatTime(dateString) {
        const date = new Date(dateString.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return dateString;
        return date.toLocaleString(undefined, {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    }

    function formatRoleLabel(role) {
        return String(role || '')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, function (char) { return char.toUpperCase(); });
    }

    function isCurrentConversation(id, role) {
        return partnerId === Number(id) && partnerRole === String(role);
    }

    function scrollToBottom(force) {
        if (!messagesEl) return;
        const distanceFromBottom = messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight;
        if (force || distanceFromBottom < 120) {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }
    }

    function buildMessageNode(message) {
        const isMine = Number(message.sender_id) === currentUserId
            && String(message.sender_role) === currentUserRole;

        const article = document.createElement('article');
        article.className = 'chat-message' + (isMine ? ' is-mine' : ' is-theirs');
        article.dataset.messageId = String(message.id || '');

        article.innerHTML =
            '<div class="chat-message__bubble"><p>' + escapeHtml(message.message_text || '') + '</p></div>' +
            '<time datetime="' + escapeHtml(message.created_at || '') + '">' +
            escapeHtml(formatTime(message.created_at || '')) +
            '</time>';

        return article;
    }

    function clearMessagesPanel(message) {
        if (!messagesEl) return;

        lastMessageSignature = '';
        lastMessageCount = 0;
        messagesEl.innerHTML =
            '<div class="chat-empty-state chat-empty-state--inline" id="chatEmptyState">' +
            '<p>' + escapeHtml(message || ('Loading conversation with ' + partnerName + '...')) + '</p>' +
            '</div>';
    }

    function renderMessages(messages, force) {
        if (!messagesEl) return;

        const signature = messages.map(function (message) {
            return String(message.id || '') + ':' + String(message.created_at || '');
        }).join('|');

        if (!force && signature === lastMessageSignature) {
            return;
        }

        lastMessageSignature = signature;
        messagesEl.innerHTML = '';

        if (!messages.length) {
            const empty = document.createElement('div');
            empty.className = 'chat-empty-state chat-empty-state--inline';
            empty.id = 'chatEmptyState';
            empty.innerHTML = '<p>Start the conversation with ' + escapeHtml(partnerName) + '.</p>';
            messagesEl.appendChild(empty);
            lastMessageCount = 0;
            return;
        }

        messages.forEach(function (message) {
            messagesEl.appendChild(buildMessageNode(message));
        });

        if (force || messages.length !== lastMessageCount) {
            scrollToBottom(true);
        }
        lastMessageCount = messages.length;
    }

    function updateTypingIndicator(typing) {
        if (!typingIndicatorEl || !typingLabelEl) return;

        const isTyping = Boolean(typing && typing.is_typing);
        if (!isTyping) {
            typingIndicatorEl.hidden = true;
            return;
        }

        const name = typing.name || partnerName || 'Contact';
        typingLabelEl.textContent = name + ' is typing...';
        typingIndicatorEl.hidden = false;
        scrollToBottom(false);
    }

    function abortPendingMessageFetch() {
        if (messageFetchController) {
            messageFetchController.abort();
            messageFetchController = null;
        }
    }

    async function sendTypingStatus(isTyping) {
        if (!partnerId || !partnerRole) return;

        await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
            },
            body: JSON.stringify({
                action: 'typing',
                csrf_token: csrfToken,
                partner_id: partnerId,
                partner_role: partnerRole,
                is_typing: isTyping,
            }),
        });
    }

    function pulseTypingStatus() {
        if (!inputEl || !partnerId || !partnerRole) return;

        const hasText = inputEl.value.trim().length > 0;
        if (!hasText) {
            if (isTypingActive) {
                isTypingActive = false;
                sendTypingStatus(false).catch(function (error) {
                    console.error(error);
                });
            }
            return;
        }

        if (!isTypingActive) {
            isTypingActive = true;
        }

        sendTypingStatus(true).catch(function (error) {
            console.error(error);
        });
    }

    function startTypingPulse() {
        stopTypingPulse();
        typingPulseTimer = window.setInterval(pulseTypingStatus, 2000);
    }

    function stopTypingPulse() {
        if (typingPulseTimer) {
            window.clearInterval(typingPulseTimer);
            typingPulseTimer = null;
        }
    }

    function clearTypingStatus() {
        if (!isTypingActive || !partnerId || !partnerRole) {
            isTypingActive = false;
            return;
        }

        isTypingActive = false;
        sendTypingStatus(false).catch(function (error) {
            console.error(error);
        });
    }

    async function fetchMessages(force) {
        if (!partnerId || !partnerRole) return;

        abortPendingMessageFetch();
        messageFetchController = new AbortController();

        const requestPartnerId = partnerId;
        const requestPartnerRole = partnerRole;
        const signal = messageFetchController.signal;

        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('partner_id', String(requestPartnerId));
        url.searchParams.set('partner_role', requestPartnerRole);

        try {
            const response = await fetch(url.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
                signal: signal,
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Unable to fetch messages.');
            }

            if (!isCurrentConversation(requestPartnerId, requestPartnerRole)) {
                return;
            }

            renderMessages(data.messages || [], Boolean(force));
            updateTypingIndicator(data.typing || null);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            throw error;
        } finally {
            if (messageFetchController && messageFetchController.signal === signal) {
                messageFetchController = null;
            }
        }
    }

    async function sendMessage(text) {
        const requestPartnerId = partnerId;
        const requestPartnerRole = partnerRole;

        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
            },
            body: JSON.stringify({
                csrf_token: csrfToken,
                partner_id: requestPartnerId,
                partner_role: requestPartnerRole,
                message_text: text,
            }),
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Unable to send message.');
        }

        if (!isCurrentConversation(requestPartnerId, requestPartnerRole)) {
            return;
        }

        await fetchMessages(true);
        clearTypingStatus();
    }

    function updateConversationHeader(button) {
        partnerName = button.dataset.partnerName || 'Contact';
        const partnerEmail = button.dataset.partnerEmail || '';
        const roleLabel = formatRoleLabel(button.dataset.partnerRole || partnerRole);

        if (activeNameEl) activeNameEl.textContent = partnerName;
        if (activeMetaEl) activeMetaEl.textContent = roleLabel + ' · ' + partnerEmail;
        if (activeAvatarEl) activeAvatarEl.textContent = partnerName.charAt(0).toUpperCase();
        if (activeTagEl) activeTagEl.textContent = String(button.dataset.partnerRole || partnerRole).toUpperCase();
        if (typingLabelEl) typingLabelEl.textContent = partnerName + ' is typing...';
    }

    function setActivePartner(button) {
        if (button.classList.contains('is-active')) {
            return;
        }

        partnerListEl?.querySelectorAll('.chat-partner.is-active').forEach(function (el) {
            el.classList.remove('is-active');
        });
        button.classList.add('is-active');

        clearTypingStatus();
        abortPendingMessageFetch();

        partnerId = Number(button.dataset.partnerId || 0);
        partnerRole = button.dataset.partnerRole || '';

        app.dataset.partnerId = String(partnerId);
        app.dataset.partnerRole = partnerRole;

        updateConversationHeader(button);

        const badge = button.querySelector('.chat-partner__badge');
        if (badge) badge.remove();

        if (typingIndicatorEl) typingIndicatorEl.hidden = true;
        if (inputEl) inputEl.value = '';

        clearMessagesPanel('Loading conversation with ' + partnerName + '...');

        fetchMessages(true).catch(function (error) {
            console.error(error);
            if (isCurrentConversation(partnerId, partnerRole)) {
                clearMessagesPanel('Unable to load this conversation. Please try again.');
            }
        });

        startTypingPulse();
    }

    function startPolling() {
        if (pollTimer) {
            window.clearInterval(pollTimer);
            pollTimer = null;
        }

        if (!partnerId || !partnerRole) return;

        startTypingPulse();

        pollTimer = window.setInterval(function () {
            fetchMessages(false).catch(function (error) {
                console.error(error);
            });
        }, 3000);
    }

    function stopPolling() {
        if (pollTimer) {
            window.clearInterval(pollTimer);
            pollTimer = null;
        }
        abortPendingMessageFetch();
        stopTypingPulse();
        clearTypingStatus();
    }

    composerEl?.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (isSending || !inputEl) return;

        const text = inputEl.value.trim();
        if (!text) return;

        isSending = true;
        sendBtn?.setAttribute('disabled', 'disabled');

        try {
            await sendMessage(text);
            inputEl.value = '';
            inputEl.focus();
        } catch (error) {
            window.alert(error.message || 'Failed to send message.');
        } finally {
            isSending = false;
            sendBtn?.removeAttribute('disabled');
        }
    });

    inputEl?.addEventListener('input', pulseTypingStatus);

    inputEl?.addEventListener('blur', function () {
        clearTypingStatus();
    });

    inputEl?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            composerEl?.requestSubmit();
        }
    });

    partnerListEl?.addEventListener('click', function (event) {
        const button = event.target.closest('.chat-partner');
        if (!button) return;
        setActivePartner(button);
    });

    searchEl?.addEventListener('input', function () {
        const query = searchEl.value.trim().toLowerCase();
        partnerListEl?.querySelectorAll('.chat-partner').forEach(function (button) {
            const haystack = (
                (button.dataset.partnerName || '') + ' ' +
                (button.dataset.partnerRole || '') + ' ' +
                (button.dataset.partnerEmail || '')
            ).toLowerCase();
            button.style.display = haystack.includes(query) ? '' : 'none';
        });
    });

    window.addEventListener('beforeunload', stopPolling);

    if (messagesEl) {
        scrollToBottom(true);
    }

    if (partnerId && partnerRole) {
        startPolling();
    }
})();
