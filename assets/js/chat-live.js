/**
 * AMA Practicum Live Chat client: polling, drafts, attachments, search, replies.
 */
(function () {
    'use strict';

    const UNREAD_POLL_MS = 25000;
    const MESSAGE_POLL_MS = 5000;
    const PARTNER_POLL_MS = 20000;
    const MAX_CHARS = 2000;
    const MAX_IMAGES = 3;
    const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
    const ALLOWED_EXTS = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'rtf'];
    const IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'webp'];
    const FILE_PICKER_ACCEPT = ALLOWED_EXTS.map(function (ext) { return '.' + ext; }).join(',');
    const ALLOWED_TYPES = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/csv', 'application/csv', 'text/plain', 'application/rtf', 'text/rtf',
    ];

    function fileExtension(file) {
        const name = String((file && (file.name || (file.file && file.file.name))) || '').toLowerCase();
        const dot = name.lastIndexOf('.');
        return dot === -1 ? '' : name.slice(dot + 1);
    }

    function isPdfAttachment(file) {
        const mime = String((file && (file.mime || file.type)) || '').toLowerCase();
        return mime.indexOf('pdf') !== -1 || fileExtension(file) === 'pdf';
    }

    function isImageAttachment(file) {
        const mime = String((file && (file.mime || file.type)) || '').toLowerCase();
        if (IMAGE_EXTS.indexOf(fileExtension(file)) !== -1) return true;
        return mime.indexOf('image/') === 0 && mime.indexOf('svg') === -1;
    }

    function fileBadge(file) {
        const ext = fileExtension(file);
        if (ext === 'pdf' || isPdfAttachment(file)) return { label: 'PDF', kind: 'pdf' };
        if (ext === 'doc' || ext === 'docx') return { label: 'DOC', kind: 'doc' };
        if (ext === 'xls' || ext === 'xlsx' || ext === 'csv') return { label: 'XLS', kind: 'xls' };
        if (ext === 'ppt' || ext === 'pptx') return { label: 'PPT', kind: 'ppt' };
        if (ext === 'txt' || ext === 'rtf') return { label: 'TXT', kind: 'txt' };
        if (ext) return { label: ext.toUpperCase().slice(0, 4), kind: 'file' };
        return { label: 'FILE', kind: 'file' };
    }

    function fileChipHtml(file, url) {
        const name = file.name || 'File';
        const mime = file.mime || file.type || '';
        const badge = fileBadge(file);
        const icon = '<span class="chat-file__icon chat-file__icon--' + badge.kind + '" aria-hidden="true">' + escapeHtml(badge.label) + '</span>' +
            '<span>' + escapeHtml(name) + '</span>';
        if (url) {
            return '<a class="chat-file" href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer" data-chat-name="' + escapeHtml(name) + '" data-chat-mime="' + escapeHtml(mime) + '">' +
                icon +
                '</a>';
        }
        return '<span class="chat-file" data-chat-name="' + escapeHtml(name) + '" data-chat-mime="' + escapeHtml(mime) + '">' + icon + '</span>';
    }

    function isAllowedChatFile(file) {
        return ALLOWED_EXTS.indexOf(fileExtension(file)) !== -1;
    }

    let unreadTimer = null;

    function apiUrl(endpoint, params) {
        const url = new URL(endpoint || 'index.php?r=chat_api', window.location.href);
        Object.keys(params || {}).forEach(function (key) {
            if (params[key] !== undefined && params[key] !== null && params[key] !== '') {
                url.searchParams.set(key, String(params[key]));
            }
        });
        return url.toString();
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatUnread(count) {
        const n = Number(count) || 0;
        if (n <= 0) return '0';
        return n > 99 ? '99+' : String(n);
    }

    function applyNavUnread(total) {
        const count = Number(total) || 0;
        document.querySelectorAll('[data-chat-unread]').forEach(function (link) {
            let badge = link.querySelector('[data-chat-unread-count]');
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'nav-link-badge';
                badge.setAttribute('data-chat-unread-count', '');
                link.appendChild(badge);
            }
            if (count > 0) {
                badge.hidden = false;
                badge.textContent = formatUnread(count);
                badge.setAttribute('aria-label', count + ' unread chat messages');
            } else {
                badge.hidden = true;
                badge.textContent = '0';
            }
        });
    }

    async function fetchUnreadTotal() {
        try {
            const response = await fetch(apiUrl('index.php?r=chat_api', { action: 'unread_total' }), {
                method: 'GET',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });
            const data = await response.json();
            if (response.ok && data.success) {
                applyNavUnread(data.unread_total);
            }
        } catch (error) {
            // Stay quiet off the chat page; connection UI lives in the thread client.
        }
    }

    window.initChatUnreadNav = function initChatUnreadNav() {
        if (!document.querySelector('[data-chat-unread]')) {
            return;
        }
        fetchUnreadTotal();
        if (unreadTimer) {
            return;
        }
        unreadTimer = window.setInterval(function () {
            if (document.hidden) {
                return;
            }
            fetchUnreadTotal();
        }, UNREAD_POLL_MS);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                fetchUnreadTotal();
            }
        });
    };

    window.initLiveChatPage = function initLiveChatPage() {
        const app = document.getElementById('chatApp');
        if (!app || app.dataset.chatBound === '1') {
            return;
        }
        app.dataset.chatBound = '1';

        const endpoint = app.dataset.endpoint || 'index.php?r=chat_api';
        const csrfToken = app.dataset.csrf || '';
        const currentUserId = Number(app.dataset.userId || 0);
        const currentUserRole = app.dataset.userRole || '';

        let partnerId = Number(app.dataset.partnerId || 0);
        let partnerRole = app.dataset.partnerRole || '';
        let partnerName = document.getElementById('chatActiveName')?.textContent?.trim() || 'Contact';
        let partnerPhotoUrl = app.dataset.partnerPhoto || '';
        let canSend = app.dataset.canSend !== '0';
        let hasMore = app.dataset.hasMore === '1';
        let thread = [];
        let replyTo = null;
        let pendingFiles = [];
        let pendingByClient = {};
        let partnerFilter = 'all';
        let pollTimer = null;
        let partnerTimer = null;
        let typingPulseTimer = null;
        let messageFetchController = null;
        let isSending = false;
        let isTypingActive = false;
        let loadingOlder = false;
        let unseenCount = 0;
        let lastPaintKey = '';
        let lastPaintKeys = [];

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
        const shellEl = document.getElementById('chatShell');
        const backBtn = document.getElementById('chatBackBtn');
        const statusEl = document.getElementById('chatConnectionStatus');
        const statusLabelEl = document.getElementById('chatConnectionLabel');
        const charCountEl = document.getElementById('chatCharCount');
        const attachBtn = document.getElementById('chatAttachBtn');
        const fileInput = document.getElementById('chatFileInput');
        const attachListEl = document.getElementById('chatAttachList');
        const readonlyEl = document.getElementById('chatReadonlyNote');
        const replyBar = document.getElementById('chatReplyBar');
        const replyNameEl = document.getElementById('chatReplyName');
        const replyTextEl = document.getElementById('chatReplyText');
        const jumpBtn = document.getElementById('chatJumpBtn');
        const threadSearchWrap = document.getElementById('chatThreadSearch');
        const threadSearchInput = document.getElementById('chatThreadSearchInput');
        const threadSearchBtn = document.getElementById('chatThreadSearchBtn');
        const threadSearchClear = document.getElementById('chatThreadSearchClear');
        const threadSearchBackdrop = document.getElementById('chatThreadSearchBackdrop');
        const threadSearchHits = document.getElementById('chatSearchHits');
        const lightbox = document.getElementById('chatLightbox');
        const lightboxImage = document.getElementById('chatLightboxImage');
        const lightboxFrame = document.getElementById('chatLightboxFrame');
        const lightboxDownload = document.getElementById('chatLightboxDownload');
        let lightboxBlobUrl = '';
        const reactPop = document.getElementById('chatReactPop');
        const morePop = document.getElementById('chatMorePop');
        const pinBar = document.getElementById('chatPinBar');
        const pinTextEl = document.getElementById('chatPinText');
        const pinLabelEl = document.getElementById('chatPinLabel');
        const pinCountEl = document.getElementById('chatPinCount');
        const pinJumpBtn = document.getElementById('chatPinJump');
        const pinPop = document.getElementById('chatPinPop');
        const pinPopList = document.getElementById('chatPinPopList');
        const pinPopMeta = document.getElementById('chatPinPopMeta');
        const pinPopClose = document.getElementById('chatPinPopClose');
        const pinPopBackdrop = document.getElementById('chatPinPopBackdrop');
        const morePinBtn = document.getElementById('chatMorePin');
        const moreRemoveBtn = document.getElementById('chatMoreRemove');
        let popoverMessageId = 0;

        function draftKey(id, role) {
            return 'chat-draft:' + String(id) + ':' + String(role);
        }

        function saveDraft() {
            if (!partnerId || !partnerRole || !inputEl) {
                return;
            }
            const text = inputEl.value;
            if (text) {
                sessionStorage.setItem(draftKey(partnerId, partnerRole), text);
            } else {
                sessionStorage.removeItem(draftKey(partnerId, partnerRole));
            }
        }

        function restoreDraft() {
            if (!inputEl) {
                return;
            }
            inputEl.value = partnerId && partnerRole
                ? (sessionStorage.getItem(draftKey(partnerId, partnerRole)) || '')
                : '';
            resizeComposer();
            updateCharCount();
        }

        function setConnection(state) {
            if (!statusEl || !statusLabelEl) {
                return;
            }
            const labels = {
                connected: 'Connected',
                reconnecting: 'Reconnecting…',
                offline: 'Offline',
            };
            statusEl.dataset.state = state;
            statusLabelEl.textContent = labels[state] || 'Connected';
        }

        async function chatFetch(url, options) {
            if (!navigator.onLine) {
                setConnection('offline');
                throw new Error('You are offline.');
            }
            try {
                const response = await fetch(url, options);
                setConnection('connected');
                return response;
            } catch (error) {
                if (error && error.name === 'AbortError') {
                    throw error;
                }
                setConnection(navigator.onLine ? 'reconnecting' : 'offline');
                throw error;
            }
        }

        function parseMessageDate(dateString) {
            const date = new Date(String(dateString || '').replace(' ', 'T'));
            return Number.isNaN(date.getTime()) ? null : date;
        }

        function formatTime(dateString) {
            const date = parseMessageDate(dateString);
            if (!date) return dateString || '';
            return date.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
        }

        function formatDayLabel(dateString) {
            const date = parseMessageDate(dateString);
            if (!date) return 'Earlier';
            const today = new Date();
            const yesterday = new Date();
            yesterday.setDate(today.getDate() - 1);
            const sameDay = function (a, b) {
                return a.getFullYear() === b.getFullYear()
                    && a.getMonth() === b.getMonth()
                    && a.getDate() === b.getDate();
            };
            if (sameDay(date, today)) return 'Today';
            if (sameDay(date, yesterday)) return 'Yesterday';
            return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function dateKey(dateString) {
            const date = parseMessageDate(dateString);
            if (!date) return String(dateString || '');
            return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
        }

        function formatWhen(dateString) {
            const date = parseMessageDate(dateString);
            if (!date) return '';
            const today = new Date();
            const yesterday = new Date();
            yesterday.setDate(today.getDate() - 1);
            if (date.toDateString() === today.toDateString()) {
                return date.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
            }
            if (date.toDateString() === yesterday.toDateString()) {
                return 'Yesterday';
            }
            return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        }

        function formatRoleLabel(role) {
            const normalized = String(role || '').toLowerCase();
            if (normalized === 'partner') return 'Host Training Establishment';
            return normalized.replace(/_/g, ' ').replace(/\b\w/g, function (char) {
                return char.toUpperCase();
            });
        }

        function roleTag(role) {
            return String(role || '').toLowerCase() === 'partner' ? 'HTE' : String(role || '').toUpperCase();
        }

        function initialsFromName(name) {
            const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
            if (parts.length >= 2) {
                return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
            }
            return String(name || 'C').slice(0, 2).toUpperCase();
        }

        function avatarRoleClass(role) {
            return String(role || 'user').toLowerCase().replace(/[^a-z]/g, '') || 'user';
        }

        function fillAvatar(el, extraClass, role, initial, photoUrl) {
            if (!el) return;
            const roleClass = avatarRoleClass(role);
            el.className = extraClass + ' is-' + roleClass + (photoUrl ? ' ' + extraClass + '--photo' : '');
            if (photoUrl) {
                el.innerHTML = '<img src="' + escapeHtml(photoUrl) + '" alt="">';
            } else {
                el.textContent = initial;
            }
        }

        function avatarMarkup(extraClass, role, initial, photoUrl) {
            const roleClass = avatarRoleClass(role);
            if (photoUrl) {
                return '<span class="' + extraClass + ' ' + extraClass + '--photo is-' + escapeHtml(roleClass) + '" aria-hidden="true"><img src="' + escapeHtml(photoUrl) + '" alt=""></span>';
            }
            return '<span class="' + extraClass + ' is-' + escapeHtml(roleClass) + '" aria-hidden="true">' + escapeHtml(initial) + '</span>';
        }

        function partnerInitial() {
            return initialsFromName(partnerName);
        }

        function isCurrentConversation(id, role) {
            return partnerId === Number(id) && partnerRole === String(role);
        }

        function isMineMessage(message) {
            return Number(message.sender_id) === currentUserId
                && String(message.sender_role) === currentUserRole;
        }

        function nearBottom() {
            if (!messagesEl) return true;
            return messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 120;
        }

        function scrollToBottom(force) {
            if (!messagesEl) return;
            if (force || nearBottom()) {
                messagesEl.scrollTop = messagesEl.scrollHeight;
            }
        }

        function newClientId() {
            return ('c' + Date.now().toString(36) + Math.random().toString(36).slice(2, 12)).slice(0, 64);
        }

        function setCanSend(next, reason) {
            canSend = Boolean(next);
            if (inputEl) inputEl.disabled = !canSend;
            if (attachBtn) attachBtn.disabled = !canSend;
            if (readonlyEl) {
                if (!canSend) {
                    readonlyEl.hidden = false;
                    readonlyEl.textContent = reason || 'This conversation is read-only.';
                } else {
                    readonlyEl.hidden = true;
                    readonlyEl.textContent = '';
                }
            }
            updateSendEnabled();
        }

        function composerHasContent() {
            return (inputEl?.value || '').trim() !== '' || pendingFiles.length > 0;
        }

        function updateSendEnabled() {
            if (!sendBtn) return;
            sendBtn.disabled = !canSend || isSending || !composerHasContent();
        }

        function formatBytes(bytes) {
            const size = Number(bytes) || 0;
            if (size < 1024) return size + ' B';
            if (size < 1024 * 1024) return (size / 1024).toFixed(1).replace(/\.0$/, '') + ' KB';
            return (size / (1024 * 1024)).toFixed(1).replace(/\.0$/, '') + ' MB';
        }

        function emptyStateHtml(messageHtml) {
            return '' +
                '<div class="chat-empty-state__icon" aria-hidden="true">' +
                '<svg class="chat-stroke-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="M8 12h.01"/><path d="M12 12h.01"/><path d="M16 12h.01"/></svg>' +
                '</div>' +
                '<p>' + messageHtml + '</p>';
        }

        function attachmentHtml(files) {
            const cards = (files || []).map(function (file) {
                const url = file.url || file.previewUrl || '';
                const name = file.name || 'File';
                if (isImageAttachment(file)) {
                    if (!url) return '';
                    return '<button type="button" class="chat-media" data-chat-lightbox="' + escapeHtml(url) + '" data-chat-name="' + escapeHtml(name) + '" data-chat-mime="' + escapeHtml(file.mime || file.type || '') + '">' +
                        '<img src="' + escapeHtml(url) + '" alt="' + escapeHtml(name) + '" loading="lazy" onerror="this.closest(\'.chat-media\')?.classList.add(\'is-broken\')">' +
                        '<span>' + escapeHtml(name) + '</span>' +
                        '</button>';
                }
                return fileChipHtml(file, url);
            }).join('');
            return cards ? '<div class="chat-media-list">' + cards + '</div>' : '';
        }

        function reactionRowHtml(message) {
            const chips = (message.reactions || []).map(function (reaction) {
                return '<button type="button" class="chat-reaction-chip' + (reaction.mine ? ' is-mine' : '') + '"' +
                    ' data-chat-emoji="' + escapeHtml(reaction.emoji) + '"' +
                    ' data-message-id="' + Number(message.id) + '">' +
                    escapeHtml(reaction.emoji) + '<span>' + Number(reaction.count || 1) + '</span></button>';
            }).join('');
            return chips ? '<div class="chat-reaction-row">' + chips + '</div>' : '';
        }

        function messageToolsHtml(message, replyText) {
            if (message._pending || message.is_deleted || !message.id) {
                return '';
            }
            return '<div class="chat-message__tools">' +
                '<button type="button" class="chat-tool-btn" data-chat-react="' + Number(message.id) + '" aria-label="Add reaction">' +
                '<svg class="chat-stroke-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>' +
                '</button>' +
                '<button type="button" class="chat-tool-btn" data-chat-more="' + Number(message.id) + '"' +
                ' data-reply-name="' + escapeHtml(message.sender_name || (isMineMessage(message) ? 'You' : partnerName)) + '"' +
                ' data-reply-text="' + escapeHtml(replyText) + '"' +
                ' data-reply-self="' + (isMineMessage(message) ? '1' : '0') + '"' +
                ' data-can-remove="' + (message.can_remove ? '1' : '0') + '"' +
                ' data-pinned="' + (message.is_pinned ? '1' : '0') + '"' +
                ' aria-label="More actions">' +
                '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>' +
                '</button>' +
                '</div>';
        }

        function closeMessagePops() {
            if (reactPop) reactPop.hidden = true;
            if (morePop) morePop.hidden = true;
            popoverMessageId = 0;
            messagesEl?.querySelectorAll('.chat-message.is-open').forEach(function (el) {
                el.classList.remove('is-open');
            });
        }

        function openMessageTools(messageId) {
            messagesEl?.querySelectorAll('.chat-message.is-open').forEach(function (el) {
                el.classList.remove('is-open');
            });
            const article = messagesEl?.querySelector('[data-message-id="' + String(messageId) + '"]');
            if (article) {
                article.classList.add('is-open');
            }
        }

        function onDocumentClick(event) {
            if (event.target.closest('.chat-react-pop, .chat-more-pop, .chat-message__tools, [data-chat-react], [data-chat-more], .chat-message')) {
                return;
            }
            closeMessagePops();
        }

        function placePopover(el, anchor) {
            if (!el || !anchor) return;
            el.hidden = false;
            const rect = anchor.getBoundingClientRect();
            const width = el.offsetWidth;
            const height = el.offsetHeight;
            let left = rect.left + (rect.width / 2) - (width / 2);
            left = Math.max(8, Math.min(left, window.innerWidth - width - 8));
            let top = rect.top - height - 8;
            if (top < 8) {
                top = rect.bottom + 8;
            }
            el.style.left = left + 'px';
            el.style.top = top + 'px';
        }

        function applyMessageUpdate(updated) {
            if (!updated || !updated.id) return;
            thread = thread.map(function (message) {
                return Number(message.id) === Number(updated.id) ? Object.assign({}, message, updated, { _pending: false }) : message;
            });
            const article = messagesEl?.querySelector('[data-message-id="' + String(updated.id) + '"]');
            if (article && !updated.is_deleted) {
                const html = reactionRowHtml(updated);
                const current = article.querySelector('.chat-reaction-row');
                if (html) {
                    const wrap = document.createElement('div');
                    wrap.innerHTML = html.trim();
                    const next = wrap.firstElementChild;
                    if (current && next) {
                        current.replaceWith(next);
                    } else if (next) {
                        const main = article.querySelector('.chat-message__main');
                        if (main) {
                            main.insertAdjacentElement('afterend', next);
                        } else {
                            article.querySelector('.chat-message__stack')?.appendChild(next);
                        }
                    }
                } else if (current) {
                    current.remove();
                }
                article.classList.toggle('is-pinned', Boolean(updated.is_pinned));
                article.dataset.pinned = updated.is_pinned ? '1' : '0';
                rememberPaintKeys(thread.map(messageIdentity));
                updatePinBar();
                return;
            }
            paintThread({ force: true });
        }

        async function postThreadAction(action, extra) {
            const response = await chatFetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(Object.assign({
                    csrf_token: csrfToken,
                    action: action,
                    partner_id: partnerId,
                    partner_role: partnerRole,
                }, extra || {})),
            });
            const data = await response.json();
            if (!response.ok || !data || !data.success) {
                throw new Error((data && data.error) || 'Unable to update this message.');
            }
            return data.message;
        }

        function pinnedMessages() {
            return thread.filter(function (message) {
                return message.is_pinned && !message.is_deleted && Number(message.id) > 0;
            });
        }

        function pinPreview(message) {
            const text = String((message && message.message_text) || '').trim();
            if (text) return text;
            const files = (message && message.attachments) || [];
            if (!files.length) return 'Pinned message';
            return files[0].name || (files.some(isImageAttachment) ? 'Photo' : 'File');
        }

        function closeSearchPanel() {
            if (!threadSearchWrap) return;
            threadSearchWrap.hidden = true;
            threadSearchBtn?.classList.remove('is-active');
            threadSearchBtn?.setAttribute('aria-expanded', 'false');
            if (threadSearchInput) {
                threadSearchInput.value = '';
            }
            if (threadSearchClear) {
                threadSearchClear.hidden = true;
            }
            if (threadSearchHits) {
                threadSearchHits.hidden = true;
                threadSearchHits.innerHTML = '';
            }
        }

        function openSearchPanel() {
            closePinPop();
            if (!threadSearchWrap) return;
            threadSearchWrap.hidden = false;
            threadSearchBtn?.classList.add('is-active');
            threadSearchBtn?.setAttribute('aria-expanded', 'true');
            runThreadSearch(threadSearchInput?.value || '');
            threadSearchInput?.focus();
        }

        function closePinPop() {
            if (pinPop) pinPop.hidden = true;
        }

        function renderPinPop() {
            if (!pinPopList) return;
            const pinned = pinnedMessages();
            if (pinPopMeta) {
                pinPopMeta.textContent = pinned.length === 1
                    ? '1 pinned message'
                    : pinned.length + ' pinned messages';
            }
            if (!pinned.length) {
                pinPopList.innerHTML = '<p class="chat-float__empty">No pinned messages yet.</p>';
                return;
            }
            pinPopList.innerHTML = pinned.slice().reverse().map(function (message) {
                const preview = pinPreview(message);
                const name = message.sender_name || (isMineMessage(message) ? 'You' : partnerName);
                return '<button type="button" class="chat-pin-item" data-jump-id="' + Number(message.id) + '">' +
                    '<span class="chat-pin-item__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2Z"/></svg></span>' +
                    '<span class="chat-pin-item__copy">' +
                    '<strong>' + escapeHtml(name) + '</strong>' +
                    '<span>' + escapeHtml(preview) + '</span>' +
                    '</span></button>';
            }).join('');
        }

        function openPinPop() {
            closeSearchPanel();
            if (!pinPop) return;
            renderPinPop();
            pinPop.hidden = false;
        }

        function updatePinBar() {
            if (!pinBar) return;
            const pinned = pinnedMessages();
            if (!pinned.length) {
                pinBar.hidden = true;
                pinBar.dataset.messageId = '';
                closePinPop();
                return;
            }
            const latest = pinned[pinned.length - 1];
            const preview = pinPreview(latest);
            pinBar.hidden = false;
            pinBar.dataset.messageId = String(latest.id);
            if (pinLabelEl) {
                pinLabelEl.textContent = pinned.length > 1 ? 'Pinned messages' : 'Pinned message';
            }
            if (pinTextEl) {
                pinTextEl.textContent = preview.length > 72 ? preview.slice(0, 72) + '…' : preview;
            }
            if (pinCountEl) {
                pinCountEl.hidden = pinned.length < 2;
                pinCountEl.textContent = String(pinned.length);
            }
            if (pinJumpBtn) {
                pinJumpBtn.textContent = pinned.length > 1 ? 'View all' : 'View';
            }
            if (pinPop && !pinPop.hidden) {
                renderPinPop();
            }
        }

        function jumpToMessage(id) {
            const target = messagesEl?.querySelector('[data-message-id="' + String(id) + '"]');
            if (!target) return;
            if (threadSearchHits) {
                threadSearchHits.hidden = true;
            }
            target.scrollIntoView({ block: 'center', behavior: 'smooth' });
            target.classList.add('is-search-hit');
            window.setTimeout(function () { target.classList.remove('is-search-hit'); }, 1800);
        }

        function receiptHtml(message) {
            if (!isMineMessage(message) || message.is_deleted) return '';
            if (message._pending) {
                return message._failed
                    ? '<button type="button" class="chat-retry-btn" data-retry="' + escapeHtml(message.client_message_id || '') + '">Failed · Retry</button>'
                    : '<span class="chat-message__receipt">Sending…</span>';
            }
            return '<span class="chat-message__receipt">' + (Number(message.is_read) ? 'Seen' : 'Sent') + '</span>';
        }

        function lastMineReceiptKey() {
            for (let i = thread.length - 1; i >= 0; i -= 1) {
                if (isMineMessage(thread[i]) && !thread[i].is_deleted) {
                    if (thread[i].id) return 'id:' + thread[i].id;
                    return 'c:' + (thread[i].client_message_id || '');
                }
            }
            return '';
        }

        function buildDayDivider(label) {
            const divider = document.createElement('div');
            divider.className = 'chat-day-divider';
            divider.setAttribute('role', 'separator');
            divider.innerHTML = '<span>' + escapeHtml(label) + '</span>';
            return divider;
        }

        function buildMessageNode(message, options) {
            options = options || {};
            const isMine = isMineMessage(message);
            const article = document.createElement('article');
            article.className = 'chat-message'
                + (isMine ? ' is-mine' : ' is-theirs')
                + (options.isGrouped ? ' is-grouped' : '')
                + (options.showAvatar ? ' has-avatar' : '')
                + (message._failed ? ' is-failed' : '')
                + (message.is_pinned ? ' is-pinned' : '')
                + (message.is_deleted ? ' is-deleted' : '')
                + (options.isFresh ? ' is-fresh' : '');
            article.dataset.messageId = String(message.id || message.client_message_id || '');
            article.dataset.pinned = message.is_pinned ? '1' : '0';
            if (message.client_message_id) {
                article.dataset.clientId = message.client_message_id;
            }

            let avatarHtml = '';
            if (!isMine) {
                avatarHtml = options.showAvatar
                    ? avatarMarkup('chat-message__avatar', partnerRole, partnerInitial(), partnerPhotoUrl)
                    : '<span class="chat-message__avatar-spacer" aria-hidden="true"></span>';
            }

            const quote = !message.is_deleted && message.reply
                ? '<div class="chat-message__quote"><strong>' + escapeHtml(message.reply.sender_name || '') + '</strong><span>' + escapeHtml(message.reply.text || '') + '</span></div>'
                : '';
            const text = message.is_deleted ? '' : String(message.message_text || '').trim();
            const replyText = text !== '' ? text : ((message.attachments || []).length ? 'Photo' : '');
            const contentHtml = message.is_deleted
                ? '<div class="chat-message__removed">Message removed</div>'
                : (attachmentHtml(message.attachments) +
                    (text !== '' ? '<div class="chat-message__bubble"><p>' + escapeHtml(text) + '</p></div>' : ''));
            const toolsHtml = messageToolsHtml(message, replyText);
            const bodyHtml = message.is_deleted
                ? contentHtml
                : ((message.is_pinned ? '<span class="chat-message__pin-flag">Pinned</span>' : '') +
                    quote +
                    '<div class="chat-message__row">' +
                    '<div class="chat-message__body">' + contentHtml + '</div>' +
                    toolsHtml +
                    '</div>');

            article.innerHTML =
                avatarHtml +
                '<div class="chat-message__stack">' +
                '<div class="chat-message__main">' +
                bodyHtml +
                '</div>' +
                (message.is_deleted ? '' : reactionRowHtml(message)) +
                '<time datetime="' + escapeHtml(message.created_at || '') + '">' +
                escapeHtml(formatTime(message.created_at || new Date().toISOString())) +
                '</time>' +
                (options.showReceipt ? receiptHtml(message) : '') +
                '</div>';

            return article;
        }

        function messageIdentity(message) {
            return [
                Number(message.id) || message.client_message_id || '',
                message.is_read,
                message._pending,
                message._failed,
                message.message_text,
                message.is_pinned ? '1' : '0',
                message.is_deleted ? '1' : '0',
                JSON.stringify(message.reactions || []),
            ].join(':');
        }

        function rememberPaintKeys(keys) {
            lastPaintKeys = keys.slice();
            lastPaintKey = keys.join('|');
        }

        function lastMessageArticle() {
            if (!messagesEl) return null;
            const rows = messagesEl.querySelectorAll('.chat-message');
            return rows.length ? rows[rows.length - 1] : null;
        }

        function settleArticle(article, message) {
            if (!article || !message) return;
            article.dataset.messageId = String(message.id || message.client_message_id || '');
            if (message.client_message_id) {
                article.dataset.clientId = message.client_message_id;
            }
            article.dataset.isRead = message.is_read ? '1' : '0';
            article.dataset.pinned = message.is_pinned ? '1' : '0';
            article.classList.toggle('is-failed', Boolean(message._failed));
            article.classList.toggle('is-pinned', Boolean(message.is_pinned));
            article.classList.toggle('is-deleted', Boolean(message.is_deleted));
            article.classList.remove('is-fresh');

            if (message.id) {
                article.querySelectorAll('[data-chat-react]').forEach(function (btn) {
                    btn.setAttribute('data-chat-react', String(message.id));
                });
                article.querySelectorAll('[data-chat-more]').forEach(function (btn) {
                    btn.setAttribute('data-chat-more', String(message.id));
                });
            }

            const html = receiptHtml(message);
            const current = article.querySelector('.chat-message__receipt, .chat-retry-btn');
            if (html) {
                const wrap = document.createElement('div');
                wrap.innerHTML = html.trim();
                const next = wrap.firstElementChild;
                if (next && current) {
                    current.replaceWith(next);
                } else if (next) {
                    article.querySelector('.chat-message__stack')?.appendChild(next);
                }
            } else if (current) {
                current.remove();
            }

            (message.attachments || []).forEach(function (file, index) {
                const media = article.querySelectorAll('.chat-media, .chat-file')[index];
                if (!media || !file || !file.url) return;
                if (media.tagName === 'SPAN') {
                    media.outerHTML = fileChipHtml(file, file.url);
                    return;
                }
                if (media.tagName === 'A') {
                    media.setAttribute('href', file.url);
                    media.setAttribute('target', '_blank');
                    media.setAttribute('rel', 'noopener noreferrer');
                    media.removeAttribute('download');
                }
                if (media.hasAttribute('data-chat-lightbox') || media.classList.contains('chat-media')) {
                    media.setAttribute('data-chat-lightbox', file.url);
                }
                const img = media.querySelector('img');
                if (img) img.src = file.url;
            });
        }

        function appendRenderedMessage(message, index, isFresh) {
            const prev = index > 0 ? thread[index - 1] : null;
            const next = thread[index + 1] || null;
            const key = dateKey(message.created_at || '');
            const prevKey = prev ? dateKey(prev.created_at || '') : null;
            const mine = isMineMessage(message);
            const isGrouped = Boolean(prev && isMineMessage(prev) === mine && prevKey === key);

            if (!mine && prev && !isMineMessage(prev) && prevKey === key) {
                const previousArticle = lastMessageArticle();
                if (previousArticle) {
                    previousArticle.classList.remove('has-avatar');
                    const avatar = previousArticle.querySelector('.chat-message__avatar');
                    if (avatar) {
                        const spacer = document.createElement('span');
                        spacer.className = 'chat-message__avatar-spacer';
                        spacer.setAttribute('aria-hidden', 'true');
                        avatar.replaceWith(spacer);
                    }
                }
            }

            if (!prev || prevKey !== key) {
                messagesEl.appendChild(buildDayDivider(formatDayLabel(message.created_at || '')));
            }

            if (mine) {
                messagesEl.querySelectorAll('.chat-message.is-mine .chat-message__receipt').forEach(function (el) {
                    el.remove();
                });
            }

            const nextMine = next ? isMineMessage(next) : null;
            const nextKey = next ? dateKey(next.created_at || '') : null;
            const showAvatar = !mine && (next === null || nextMine === true || nextKey !== key);
            const receiptKey = lastMineReceiptKey();
            const messageKey = message.id ? 'id:' + message.id : 'c:' + (message.client_message_id || '');
            messagesEl.appendChild(buildMessageNode(message, {
                isGrouped: isGrouped,
                showAvatar: showAvatar,
                showReceipt: messageKey !== '' && messageKey === receiptKey,
                isFresh: Boolean(isFresh),
            }));
        }

        function paintThread(options) {
            options = options || {};
            if (!messagesEl) return;

            const keys = thread.map(messageIdentity);
            const paintKey = keys.join('|');
            if (!options.force && paintKey === lastPaintKey) {
                return;
            }

            const rendered = messagesEl.querySelectorAll('.chat-message').length;
            const canAppend = !options.force && !options.prepend
                && rendered === lastPaintKeys.length
                && keys.length > lastPaintKeys.length
                && lastPaintKeys.every(function (key, index) { return keys[index] === key; });
            const canPatchTail = !options.force && !options.prepend
                && rendered === lastPaintKeys.length
                && keys.length === lastPaintKeys.length
                && keys.length > 0
                && lastPaintKeys.slice(0, -1).every(function (key, index) { return keys[index] === key; });

            if (canAppend) {
                messagesEl.querySelectorAll('.chat-empty-state').forEach(function (el) { el.remove(); });
                thread.slice(lastPaintKeys.length).forEach(function (message, offset) {
                    appendRenderedMessage(message, lastPaintKeys.length + offset, true);
                });
                rememberPaintKeys(keys);
                if (options.stickBottom || nearBottom()) {
                    scrollToBottom(true);
                    unseenCount = 0;
                    if (jumpBtn) jumpBtn.hidden = true;
                }
                updatePinBar();
                return;
            }

            if (canPatchTail) {
                settleArticle(lastMessageArticle(), thread[thread.length - 1]);
                rememberPaintKeys(keys);
                if (options.stickBottom) {
                    scrollToBottom(true);
                }
                updatePinBar();
                return;
            }

            rememberPaintKeys(keys);

            const previousHeight = messagesEl.scrollHeight;
            const previousTop = messagesEl.scrollTop;
            const stickBottom = options.stickBottom || (!options.prepend && nearBottom());

            messagesEl.innerHTML = '';
            if (!thread.length) {
                const empty = document.createElement('div');
                empty.className = 'chat-empty-state chat-empty-state--inline';
                empty.id = 'chatEmptyState';
                empty.innerHTML = emptyStateHtml('No messages yet. Send a message to <strong>' + escapeHtml(partnerName) + '</strong>.');
                messagesEl.appendChild(empty);
                updatePinBar();
                return;
            }

            let lastKey = null;
            let prevMine = null;
            const receiptKey = lastMineReceiptKey();
            thread.forEach(function (message, index) {
                const key = dateKey(message.created_at || '');
                const mine = isMineMessage(message);
                const next = thread[index + 1] || null;
                const nextMine = next ? isMineMessage(next) : null;
                const nextKey = next ? dateKey(next.created_at || '') : null;
                const showAvatar = !mine && (next === null || nextMine === true || nextKey !== key);
                const messageKey = message.id ? 'id:' + message.id : 'c:' + (message.client_message_id || '');
                let isGrouped = prevMine !== null && prevMine === mine && key === lastKey;
                if (key !== lastKey) {
                    lastKey = key;
                    isGrouped = false;
                    messagesEl.appendChild(buildDayDivider(formatDayLabel(message.created_at || '')));
                }
                messagesEl.appendChild(buildMessageNode(message, {
                    isGrouped: isGrouped,
                    showAvatar: showAvatar,
                    showReceipt: messageKey !== '' && messageKey === receiptKey,
                }));
                prevMine = mine;
            });

            if (options.prepend) {
                messagesEl.scrollTop = messagesEl.scrollHeight - previousHeight + previousTop;
            } else if (stickBottom) {
                scrollToBottom(true);
                unseenCount = 0;
                if (jumpBtn) jumpBtn.hidden = true;
            } else {
                messagesEl.scrollTop = previousTop;
            }
            updatePinBar();
        }

        function mergeIncoming(incoming, mode) {
            const byKey = {};
            thread.forEach(function (message) {
                const key = message.id ? 'id:' + message.id : 'c:' + message.client_message_id;
                byKey[key] = message;
            });
            (incoming || []).forEach(function (message) {
                const key = message.id ? 'id:' + message.id : 'c:' + message.client_message_id;
                if (message.client_message_id && byKey['c:' + message.client_message_id]) {
                    delete byKey['c:' + message.client_message_id];
                }
                byKey[key] = Object.assign({}, byKey[key] || {}, message, { _pending: false, _failed: false });
            });
            const next = Object.keys(byKey).map(function (key) { return byKey[key]; });
            next.sort(function (a, b) {
                const aId = Number(a.id) || 0;
                const bId = Number(b.id) || 0;
                if (aId && bId) return aId - bId;
                return String(a.created_at || '').localeCompare(String(b.created_at || ''));
            });
            const previousCount = thread.filter(function (message) { return message.id; }).length;
            thread = next;
            const added = thread.filter(function (message) { return message.id; }).length - previousCount;
            if (mode !== 'prepend' && added > 0 && !nearBottom()) {
                unseenCount += added;
                if (jumpBtn) {
                    jumpBtn.hidden = false;
                    jumpBtn.textContent = unseenCount + ' new message' + (unseenCount === 1 ? '' : 's') + ' ↓';
                }
            }
        }

        function hydrateFromDom() {
            if (!messagesEl) return;
            const rows = [];
            messagesEl.querySelectorAll('.chat-message[data-message-id]').forEach(function (article) {
                const id = Number(article.dataset.messageId || 0);
                if (id <= 0) return;
                const quote = article.querySelector('.chat-message__quote');
                const attachments = [];
                article.querySelectorAll('.chat-media, .chat-file, .chat-thumb').forEach(function (thumb) {
                    attachments.push({
                        url: thumb.getAttribute('data-chat-lightbox') || thumb.getAttribute('href') || '',
                        name: thumb.getAttribute('data-chat-name') || 'File',
                        mime: thumb.getAttribute('data-chat-mime') || '',
                    });
                });
                rows.push({
                    id: id,
                    sender_id: article.classList.contains('is-mine') ? currentUserId : partnerId,
                    sender_role: article.classList.contains('is-mine') ? currentUserRole : partnerRole,
                    sender_name: article.classList.contains('is-mine') ? 'You' : partnerName,
                    message_text: article.querySelector('.chat-message__bubble p')?.textContent || '',
                    created_at: article.querySelector('time')?.getAttribute('datetime') || '',
                    is_read: Number(article.dataset.isRead || 0),
                    is_pinned: article.dataset.pinned === '1' || article.classList.contains('is-pinned'),
                    is_deleted: article.classList.contains('is-deleted'),
                    can_remove: article.classList.contains('is-mine'),
                    attachments: attachments,
                    reactions: Array.from(article.querySelectorAll('.chat-reaction-chip')).map(function (chip) {
                        return {
                            emoji: chip.getAttribute('data-chat-emoji') || '',
                            count: Number(chip.querySelector('span')?.textContent || 1),
                            mine: chip.classList.contains('is-mine'),
                        };
                    }),
                    reply: quote ? {
                        sender_name: quote.querySelector('strong')?.textContent || '',
                        text: quote.querySelector('span')?.textContent || '',
                    } : null,
                });
            });
            thread = rows;
            rememberPaintKeys(thread.map(messageIdentity));
            markBrokenMedia();
        }

        function markBrokenMedia(root) {
            (root || messagesEl)?.querySelectorAll('.chat-media img').forEach(function (img) {
                const card = img.closest('.chat-media');
                if (!card) return;
                if (img.complete && img.naturalWidth === 0) {
                    card.classList.add('is-broken');
                }
            });
        }

        function updateTypingIndicator(typing) {
            if (!typingIndicatorEl || !typingLabelEl) return;
            if (!typing || !typing.is_typing) {
                typingIndicatorEl.hidden = true;
                return;
            }
            typingLabelEl.textContent = (typing.name || partnerName || 'Contact') + ' is typing...';
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
            await chatFetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
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
                    sendTypingStatus(false).catch(function () {});
                }
                return;
            }
            isTypingActive = true;
            sendTypingStatus(true).catch(function () {});
        }

        function startTypingPulse() {
            stopTypingPulse();
            typingPulseTimer = window.setInterval(pulseTypingStatus, 3000);
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
            sendTypingStatus(false).catch(function () {});
        }

        function navUnreadFromList() {
            let remaining = 0;
            partnerListEl?.querySelectorAll('.chat-partner.has-unread').forEach(function (button) {
                const badge = button.querySelector('.chat-partner__badge');
                remaining += Math.max(1, Number((badge && badge.textContent) || 1) || 1);
            });
            applyNavUnread(remaining);
        }

        function applyUnreadBadges(unreads, unreadTotal) {
            if (!partnerListEl || !Array.isArray(unreads)) {
                if (unreadTotal !== undefined && unreadTotal !== null) {
                    applyNavUnread(unreadTotal);
                } else {
                    navUnreadFromList();
                }
                return;
            }
            const map = {};
            unreads.forEach(function (row) {
                map[String(row.user_id) + ':' + String(row.role)] = Number(row.unread_count || 0);
            });
            partnerListEl.querySelectorAll('.chat-partner').forEach(function (button) {
                const key = String(button.dataset.partnerId || '') + ':' + String(button.dataset.partnerRole || '');
                let count = map[key] || 0;
                if (isCurrentConversation(button.dataset.partnerId, button.dataset.partnerRole)) {
                    count = 0;
                }
                let badge = button.querySelector('.chat-partner__badge');
                if (count > 0) {
                    button.classList.add('has-unread');
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'chat-partner__badge';
                        button.appendChild(badge);
                    }
                    badge.textContent = String(count);
                } else {
                    button.classList.remove('has-unread');
                    if (badge) badge.remove();
                }
            });
            applyPartnerFilter();
            if (unreadTotal !== undefined && unreadTotal !== null) {
                applyNavUnread(unreadTotal);
            } else {
                navUnreadFromList();
            }
        }

        function applyPartnerFilter() {
            const query = (searchEl?.value || '').trim().toLowerCase();
            partnerListEl?.querySelectorAll('.chat-group').forEach(function (group) {
                let visible = 0;
                group.querySelectorAll('.chat-partner').forEach(function (button) {
                    const haystack = (
                        (button.dataset.partnerName || '') + ' ' +
                        (button.dataset.partnerRole || '') + ' ' +
                        (button.dataset.partnerEmail || '')
                    ).toLowerCase();
                    const matchesQuery = query === '' || haystack.includes(query);
                    const matchesFilter = partnerFilter !== 'unread' || button.classList.contains('has-unread');
                    const show = matchesQuery && matchesFilter;
                    button.hidden = !show;
                    if (show) visible += 1;
                });
                group.hidden = visible === 0;
            });
        }

        function partnerButtonHtml(partner, isActive) {
            const unread = Number(partner.unread_count || 0);
            const initial = initialsFromName(partner.name || 'C');
            const preview = partner.last_message_is_photo
                ? 'Photo'
                : String(partner.last_message || partner.subtitle || formatRoleLabel(partner.role));
            const when = formatWhen(partner.last_message_at);
            const photoUrl = String(partner.photo_url || '');
            return '<button type="button" class="chat-partner' + (isActive ? ' is-active' : '') + (unread > 0 && !isActive ? ' has-unread' : '') + '"' +
                ' title="' + escapeHtml(partner.name || '') + '"' +
                ' data-partner-id="' + Number(partner.user_id) + '"' +
                ' data-partner-role="' + escapeHtml(partner.role || '') + '"' +
                ' data-partner-name="' + escapeHtml(partner.name || '') + '"' +
                ' data-partner-email="' + escapeHtml(partner.email || '') + '"' +
                ' data-partner-subtitle="' + escapeHtml(partner.subtitle || '') + '"' +
                ' data-partner-photo="' + escapeHtml(photoUrl) + '"' +
                ' data-can-send="' + (partner.can_send ? '1' : '0') + '"' +
                ' data-send-block="' + escapeHtml(partner.send_block_reason || '') + '">' +
                avatarMarkup('chat-partner__avatar', partner.role, initial, photoUrl) +
                '<span class="chat-partner__copy"><span class="chat-partner__row"><strong>' + escapeHtml(partner.name || '') + '</strong>' +
                (when ? '<time>' + escapeHtml(when) + '</time>' : '') +
                '</span><small class="chat-partner__preview">' + escapeHtml(preview) + '</small></span>' +
                (unread > 0 && !isActive ? '<span class="chat-partner__badge">' + unread + '</span>' : '') +
                '</button>';
        }

        function renderPartnerGroups(groups) {
            if (!partnerListEl || !Array.isArray(groups)) return;
            if (!groups.length) return;
            partnerListEl.innerHTML = groups.map(function (group) {
                return '<section class="chat-group" data-role-group="' + escapeHtml(group.role || '') + '">' +
                    '<h4>' + escapeHtml(group.label || formatRoleLabel(group.role)) + '</h4>' +
                    (group.partners || []).map(function (partner) {
                        const active = isCurrentConversation(partner.user_id, partner.role);
                        return partnerButtonHtml(partner, active);
                    }).join('') +
                    '</section>';
            }).join('');
            const countEl = document.getElementById('chatContactCount');
            const total = groups.reduce(function (sum, group) {
                return sum + (group.partners || []).length;
            }, 0);
            if (countEl) countEl.textContent = total + ' contacts';
            applyPartnerFilter();
            navUnreadFromList();
        }

        function updateLocalPreview(text, isPhoto) {
            const button = partnerListEl?.querySelector('.chat-partner.is-active');
            if (!button) return;
            const preview = button.querySelector('.chat-partner__preview');
            const row = button.querySelector('.chat-partner__row');
            if (preview) preview.textContent = isPhoto && !text ? 'Photo' : text;
            if (row) {
                let timeEl = row.querySelector('time');
                if (!timeEl) {
                    timeEl = document.createElement('time');
                    row.appendChild(timeEl);
                }
                timeEl.textContent = formatWhen(new Date().toISOString());
            }
        }

        function syncChatUrl() {
            if (!partnerId || !partnerRole) return;
            const url = new URL(window.location.href);
            url.searchParams.set('partner_id', String(partnerId));
            url.searchParams.set('partner_role', partnerRole);
            history.replaceState(history.state, '', url.toString());
        }

        function clearChatUrl() {
            const url = new URL(window.location.href);
            url.searchParams.delete('partner_id');
            url.searchParams.delete('partner_role');
            history.replaceState(history.state, '', url.toString());
        }

        function syncConversationChrome() {
            const open = !!shellEl?.classList.contains('has-conversation');
            document.body.classList.toggle('chat-conversation-open', open);
        }

        function showConversationPane() {
            const emptyEl = document.getElementById('chatWindowEmpty');
            const threadEl = document.getElementById('chatWindowThread');
            if (emptyEl) emptyEl.hidden = true;
            if (threadEl) threadEl.hidden = false;
            syncConversationChrome();
        }

        function showContactsPane() {
            shellEl?.classList.remove('has-conversation');
            clearChatUrl();
            syncConversationChrome();
            // Desktop: return to the empty "select a conversation" pane.
            if (window.matchMedia('(min-width: 981px)').matches) {
                const emptyEl = document.getElementById('chatWindowEmpty');
                const threadEl = document.getElementById('chatWindowThread');
                if (emptyEl) emptyEl.hidden = false;
                if (threadEl) threadEl.hidden = true;
                partnerListEl?.querySelectorAll('.chat-partner.is-active').forEach(function (el) {
                    el.classList.remove('is-active');
                });
                partnerId = 0;
                partnerRole = '';
                app.dataset.partnerId = '0';
                app.dataset.partnerRole = '';
                stopPolling();
            }
        }

        async function fetchMessages(force, beforeId) {
            if (!partnerId || !partnerRole) return;
            if (!beforeId) {
                abortPendingMessageFetch();
            }
            const controller = new AbortController();
            if (!beforeId) {
                messageFetchController = controller;
            }
            const requestPartnerId = partnerId;
            const requestPartnerRole = partnerRole;
            const url = apiUrl(endpoint, {
                partner_id: requestPartnerId,
                partner_role: requestPartnerRole,
                before_id: beforeId || '',
            });
            try {
                const response = await chatFetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Unable to fetch messages.');
                }
                if (!isCurrentConversation(requestPartnerId, requestPartnerRole)) {
                    return;
                }
                if (beforeId) {
                    hasMore = Boolean(data.has_more);
                    mergeIncoming(data.messages || [], 'prepend');
                    paintThread({ prepend: true, force: true });
                } else {
                    hasMore = Boolean(data.has_more);
                    setCanSend(data.can_send, data.send_block_reason);
                    const stick = nearBottom() || force;
                    mergeIncoming(data.messages || [], 'poll');
                    paintThread({ force: Boolean(force), stickBottom: stick });
                    updateTypingIndicator(data.typing || null);
                    applyUnreadBadges(data.unreads || [], data.unread_total);
                }
            } catch (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }
                throw error;
            } finally {
                if (messageFetchController && messageFetchController.signal === controller.signal) {
                    messageFetchController = null;
                }
            }
        }

        async function loadOlder() {
            if (loadingOlder || !hasMore || !thread.length) return;
            const oldest = thread.find(function (message) { return Number(message.id) > 0; });
            if (!oldest) return;
            loadingOlder = true;
            try {
                await fetchMessages(false, oldest.id);
            } catch (error) {
                console.error(error);
            } finally {
                loadingOlder = false;
            }
        }

        async function refreshPartners() {
            try {
                const response = await chatFetch(apiUrl(endpoint, { action: 'partners' }), {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                const data = await response.json();
                if (!response.ok || !data.success) return;
                renderPartnerGroups(data.groups || []);
                applyNavUnread(data.unread_total);
            } catch (error) {
                // Connection badge already updated by chatFetch.
            }
        }

        function clearPendingFiles() {
            pendingFiles.forEach(function (item) {
                if (item.previewUrl) URL.revokeObjectURL(item.previewUrl);
            });
            pendingFiles = [];
            renderAttachList();
        }

        let uploadProgress = null;

        function renderAttachList() {
            if (!attachListEl) return;
            const files = uploadProgress ? uploadProgress.files : pendingFiles;
            const uploading = Boolean(uploadProgress);
            if (!files.length) {
                attachListEl.hidden = true;
                attachListEl.innerHTML = '';
                return;
            }
            attachListEl.hidden = false;
            const percent = uploading ? Math.max(0, Math.min(100, Number(uploadProgress.percent) || 0)) : 0;
            attachListEl.innerHTML = files.map(function (item, index) {
                const source = item.file || item;
                const image = isImageAttachment(source) && item.previewUrl;
                const badge = fileBadge(source);
                const preview = image
                    ? '<img src="' + escapeHtml(item.previewUrl) + '" alt="">'
                    : '<span class="chat-attach-chip__file chat-attach-chip__file--' + badge.kind + '" aria-hidden="true">' + escapeHtml(badge.label) + '</span>';
                return '<div class="chat-attach-chip">' +
                    preview +
                    '<span class="chat-attach-chip__meta">' +
                    '<strong>' + escapeHtml(item.file.name) + '</strong>' +
                    '<small>' + escapeHtml(formatBytes(item.file.size)) + (uploading ? ' · ' + percent + '%' : '') + '</small>' +
                    (uploading
                        ? '<span class="chat-attach-progress" aria-hidden="true"><span style="width:' + percent + '%"></span></span>'
                        : '') +
                    '</span>' +
                    (uploading ? '' : '<button type="button" class="chat-icon-btn" data-remove-file="' + index + '" aria-label="Remove file">×</button>') +
                    '</div>';
            }).join('');
        }

        function addFiles(fileList) {
            Array.from(fileList || []).forEach(function (file) {
                if (pendingFiles.length >= MAX_IMAGES) {
                    window.alert('You can attach up to 3 files.');
                    return;
                }
                if (!isAllowedChatFile(file)) {
                    window.alert('Only images, PDF, Word, Excel, PowerPoint, CSV, and text files are allowed.');
                    return;
                }
                if (file.size > MAX_IMAGE_BYTES) {
                    window.alert('Each file must be 5 MB or smaller.');
                    return;
                }
                pendingFiles.push({
                    file: file,
                    previewUrl: isImageAttachment(file) ? URL.createObjectURL(file) : '',
                });
            });
            renderAttachList();
            updateSendEnabled();
        }

        function normalizeClipboardImage(file) {
            if (!file) return null;
            const type = file.type || 'image/png';
            if (ALLOWED_TYPES.indexOf(type) === -1 && type !== 'image/jpg') {
                return null;
            }
            const mime = type === 'image/jpg' ? 'image/jpeg' : type;
            const ext = mime === 'image/jpeg' ? 'jpg' : (mime === 'image/webp' ? 'webp' : 'png');
            const name = (file.name && /\.(jpe?g|png|webp)$/i.test(file.name))
                ? file.name
                : ('screenshot-' + Date.now() + '.' + ext);
            return new File([file], name, { type: mime, lastModified: Date.now() });
        }

        function filesFromClipboard(event) {
            const data = event.clipboardData;
            if (!data) return [];
            const seen = [];
            const pushFile = function (file) {
                const normalized = normalizeClipboardImage(file);
                if (normalized) seen.push(normalized);
            };
            Array.from(data.files || []).forEach(pushFile);
            if (!seen.length) {
                Array.from(data.items || []).forEach(function (item) {
                    if (item.kind === 'file' && String(item.type || '').indexOf('image/') === 0) {
                        pushFile(item.getAsFile());
                    }
                });
            }
            return seen;
        }

        function onChatPaste(event) {
            if (!canSend) return;
            const target = event.target;
            if (target && target.closest && target.closest('#chatPartnerSearch, #chatThreadSearchInput')) {
                return;
            }
            const images = filesFromClipboard(event);
            if (!images.length) return;
            event.preventDefault();
            addFiles(images);
        }

        function setReply(id, name, text, isSelf) {
            replyTo = id ? { id: Number(id), name: name || '', text: text || '' } : null;
            if (!replyBar) return;
            if (!replyTo) {
                replyBar.hidden = true;
                replyBar.setAttribute('hidden', '');
                return;
            }
            const ownName = document.querySelector('.topbar .app-user-identity__meta strong')?.textContent?.trim() || '';
            const selfReply = isSelf === true || isSelf === '1' || !replyTo.name || replyTo.name === 'You' || (ownName !== '' && replyTo.name === ownName);
            if (replyNameEl) replyNameEl.textContent = selfReply ? 'Replying to yourself' : 'Replying to ' + replyTo.name;
            if (replyTextEl) replyTextEl.textContent = replyTo.text || 'Photo';
            replyBar.hidden = false;
            replyBar.removeAttribute('hidden');
        }

        function parseJsonResponse(text) {
            try {
                return JSON.parse(text);
            } catch (error) {
                return null;
            }
        }

        function postFormWithProgress(body, onProgress) {
            return new Promise(function (resolve, reject) {
                if (!navigator.onLine) {
                    setConnection('offline');
                    reject(new Error('You are offline.'));
                    return;
                }
                const xhr = new XMLHttpRequest();
                xhr.open('POST', endpoint);
                xhr.withCredentials = true;
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-CSRF-Token', csrfToken);
                if (xhr.upload && typeof onProgress === 'function') {
                    xhr.upload.onprogress = function (event) {
                        if (event.lengthComputable) {
                            onProgress(Math.round((event.loaded / event.total) * 100));
                        }
                    };
                }
                xhr.onload = function () {
                    setConnection('connected');
                    resolve({ ok: xhr.status >= 200 && xhr.status < 300, status: xhr.status, text: xhr.responseText });
                };
                xhr.onerror = function () {
                    setConnection(navigator.onLine ? 'reconnecting' : 'offline');
                    reject(new Error('Unable to reach the chat server. Please try again.'));
                };
                xhr.send(body);
            });
        }

        async function postMessage(payload) {
            const requestPartnerId = payload.partnerId;
            const requestPartnerRole = payload.partnerRole;
            let ok = false;
            let raw = '';
            if (payload.files && payload.files.length) {
                const body = new FormData();
                body.append('csrf_token', csrfToken);
                body.append('action', 'send');
                body.append('partner_id', String(requestPartnerId));
                body.append('partner_role', requestPartnerRole);
                body.append('message_text', payload.text);
                body.append('client_message_id', payload.clientId);
                if (payload.replyToId) body.append('reply_to_id', String(payload.replyToId));
                payload.files.forEach(function (item) {
                    body.append('images[]', item.file, item.file.name);
                });
                uploadProgress = { files: payload.files, percent: 0 };
                renderAttachList();
                const posted = await postFormWithProgress(body, function (percent) {
                    if (uploadProgress) {
                        uploadProgress.percent = percent;
                        renderAttachList();
                    }
                });
                ok = posted.ok;
                raw = posted.text;
                uploadProgress = null;
                renderAttachList();
            } else {
                const response = await chatFetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                    },
                    body: JSON.stringify({
                        csrf_token: csrfToken,
                        partner_id: requestPartnerId,
                        partner_role: requestPartnerRole,
                        message_text: payload.text,
                        client_message_id: payload.clientId,
                        reply_to_id: payload.replyToId || null,
                    }),
                });
                ok = response.ok;
                raw = await response.text();
            }
            const data = parseJsonResponse(raw);
            if (!ok || !data || !data.success) {
                throw new Error((data && data.error) || 'Unable to send message.');
            }
            return data.message;
        }

        function markFailed(clientId) {
            thread = thread.map(function (message) {
                if (message.client_message_id === clientId && message._pending) {
                    return Object.assign({}, message, { _failed: true });
                }
                return message;
            });
            paintThread({ stickBottom: true });
        }

        async function sendPayload(payload, isRetry) {
            if (!isRetry) {
                const optimistic = {
                    id: 0,
                    client_message_id: payload.clientId,
                    sender_id: currentUserId,
                    sender_role: currentUserRole,
                    sender_name: 'You',
                    message_text: payload.text,
                    created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
                    is_read: 0,
                    attachments: (payload.files || []).map(function (item) {
                        return { url: item.previewUrl, name: item.file.name, mime: item.file.type || '' };
                    }),
                    reply: payload.replyToId ? { sender_name: payload.replyName, text: payload.replyText } : null,
                    _pending: true,
                    _failed: false,
                };
                thread.push(optimistic);
                paintThread({ stickBottom: true });
            } else {
                thread = thread.map(function (message) {
                    if (message.client_message_id === payload.clientId) {
                        return Object.assign({}, message, { _failed: false, _pending: true });
                    }
                    return message;
                });
                paintThread({ stickBottom: true });
            }

            try {
                const saved = await postMessage(payload);
                delete pendingByClient[payload.clientId];
                (payload.files || []).forEach(function (item) {
                    if (item.previewUrl) URL.revokeObjectURL(item.previewUrl);
                });
                thread = thread.filter(function (message) {
                    return message.client_message_id !== payload.clientId || Number(message.id) > 0;
                });
                mergeIncoming([saved], 'poll');
                paintThread({ stickBottom: true });
                updateLocalPreview(payload.text, (payload.files || []).length > 0 && payload.text === '');
                clearTypingStatus();
            } catch (error) {
                markFailed(payload.clientId);
                if (payload.text) {
                    sessionStorage.setItem(draftKey(payload.partnerId, payload.partnerRole), payload.text);
                }
                throw error;
            } finally {
                uploadProgress = null;
                renderAttachList();
            }
        }

        async function submitComposer() {
            if (isSending || !canSend) return;
            const text = (inputEl?.value || '').trim();
            if (!text && pendingFiles.length === 0) return;
            if (text.length > MAX_CHARS) return;

            const payload = {
                partnerId: partnerId,
                partnerRole: partnerRole,
                text: text,
                clientId: newClientId(),
                replyToId: replyTo ? replyTo.id : null,
                replyName: replyTo ? replyTo.name : '',
                replyText: replyTo ? replyTo.text : '',
                files: pendingFiles.slice(),
            };
            pendingByClient[payload.clientId] = payload;
            pendingFiles = [];
            if (inputEl) inputEl.value = '';
            setReply(null);
            resizeComposer();
            updateCharCount();
            updateSendEnabled();

            isSending = true;
            updateSendEnabled();
            try {
                await sendPayload(payload, false);
                sessionStorage.removeItem(draftKey(partnerId, partnerRole));
            } catch (error) {
                console.error(error);
            } finally {
                isSending = false;
                updateSendEnabled();
                inputEl?.focus();
            }
        }

        async function retryMessage(clientId) {
            const payload = pendingByClient[clientId];
            if (!payload) return;
            try {
                await sendPayload(payload, true);
            } catch (error) {
                console.error(error);
            }
        }

        function updateConversationHeader(button) {
            partnerName = button.dataset.partnerName || 'Contact';
            partnerPhotoUrl = button.dataset.partnerPhoto || '';
            app.dataset.partnerPhoto = partnerPhotoUrl;
            const subtitle = button.dataset.partnerSubtitle || formatRoleLabel(button.dataset.partnerRole || partnerRole);
            if (activeNameEl) activeNameEl.textContent = partnerName;
            if (activeMetaEl) activeMetaEl.textContent = subtitle;
            fillAvatar(activeAvatarEl, 'chat-window__avatar', button.dataset.partnerRole || partnerRole, initialsFromName(partnerName), partnerPhotoUrl);
            if (activeTagEl) activeTagEl.textContent = roleTag(button.dataset.partnerRole || partnerRole);
            if (typingLabelEl) typingLabelEl.textContent = partnerName + ' is typing...';
        }

        function setActivePartner(button, force) {
            if (!button) return;
            const alreadyOpen = button.classList.contains('is-active') && shellEl?.classList.contains('has-conversation');
            if (!force && alreadyOpen) {
                return;
            }

            saveDraft();
            closeMessagePops();
            closeSearchPanel();
            closePinPop();
            partnerListEl?.querySelectorAll('.chat-partner.is-active').forEach(function (el) {
                el.classList.remove('is-active');
            });
            button.classList.add('is-active');
            shellEl?.classList.add('has-conversation');
            showConversationPane();

            clearTypingStatus();
            abortPendingMessageFetch();
            clearPendingFiles();
            setReply(null);

            partnerId = Number(button.dataset.partnerId || 0);
            partnerRole = button.dataset.partnerRole || '';
            app.dataset.partnerId = String(partnerId);
            app.dataset.partnerRole = partnerRole;
            hasMore = false;
            unseenCount = 0;
            if (jumpBtn) jumpBtn.hidden = true;

            updateConversationHeader(button);
            setCanSend(button.dataset.canSend !== '0', button.dataset.sendBlock || '');
            const badge = button.querySelector('.chat-partner__badge');
            if (badge) badge.remove();
            button.classList.remove('has-unread');
            markActiveConversationRead();
            if (typingIndicatorEl) typingIndicatorEl.hidden = true;
            restoreDraft();

            thread = [];
            lastPaintKey = '';
            lastPaintKeys = [];
            if (messagesEl) {
                messagesEl.innerHTML = '<div class="chat-empty-state chat-empty-state--inline" id="chatEmptyState">' +
                    emptyStateHtml('Loading conversation with <strong>' + escapeHtml(partnerName) + '</strong>...') +
                    '</div>';
            }

            fetchMessages(true).catch(function (error) {
                console.error(error);
                if (isCurrentConversation(partnerId, partnerRole) && messagesEl) {
                    messagesEl.innerHTML = '<div class="chat-empty-state chat-empty-state--inline">' +
                        emptyStateHtml('Unable to load this conversation. Please try again.') +
                        '</div>';
                }
            });
            startTypingPulse();
            startPolling();
            syncChatUrl();
        }

        async function markActiveConversationRead() {
            if (!partnerId || !partnerRole) {
                return;
            }
            const requestPartnerId = partnerId;
            const requestPartnerRole = partnerRole;
            try {
                const response = await chatFetch(apiUrl(endpoint), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                    },
                    body: JSON.stringify({
                        action: 'mark_read',
                        partner_id: requestPartnerId,
                        partner_role: requestPartnerRole,
                        csrf_token: csrfToken,
                    }),
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    return;
                }
                if (!isCurrentConversation(requestPartnerId, requestPartnerRole)) {
                    return;
                }
                applyNavUnread(data.unread_total);
            } catch (error) {
                // Message poll will still refresh unread state.
            }
        }

        function startPolling() {
            if (pollTimer) {
                window.clearInterval(pollTimer);
                pollTimer = null;
            }
            if (partnerTimer) {
                window.clearInterval(partnerTimer);
                partnerTimer = null;
            }
            if (!partnerId || !partnerRole) return;
            startTypingPulse();
            pollTimer = window.setInterval(function () {
                if (document.hidden) return;
                fetchMessages(false).catch(function (error) {
                    console.error(error);
                });
            }, MESSAGE_POLL_MS);
            partnerTimer = window.setInterval(function () {
                if (document.hidden) return;
                refreshPartners();
            }, PARTNER_POLL_MS);
        }

        function stopPolling() {
            if (pollTimer) {
                window.clearInterval(pollTimer);
                pollTimer = null;
            }
            if (partnerTimer) {
                window.clearInterval(partnerTimer);
                partnerTimer = null;
            }
            abortPendingMessageFetch();
            stopTypingPulse();
            clearTypingStatus();
            saveDraft();
        }

        function resizeComposer() {
            if (!inputEl) return;
            inputEl.style.height = 'auto';
            inputEl.style.height = Math.min(inputEl.scrollHeight, 88) + 'px';
        }

        function updateCharCount() {
            if (!charCountEl || !inputEl) return;
            const length = inputEl.value.length;
            charCountEl.textContent = length + ' / ' + MAX_CHARS;
            charCountEl.classList.toggle('warning', length >= MAX_CHARS - 120);
            charCountEl.classList.add('is-visible');
        }

        function revokeLightboxBlob() {
            if (lightboxBlobUrl) {
                URL.revokeObjectURL(lightboxBlobUrl);
                lightboxBlobUrl = '';
            }
        }

        function isPdfPreview(name, mime, url) {
            const type = String(mime || '').toLowerCase();
            const fileName = String(name || '').toLowerCase();
            const href = String(url || '').toLowerCase();
            return type.indexOf('pdf') !== -1 || fileName.endsWith('.pdf') || href.indexOf('.pdf') !== -1;
        }

        function loadPdfFrame(url) {
            if (!lightboxFrame) return;
            fetch(url, { credentials: 'same-origin', cache: 'no-store' })
                .then(function (res) {
                    if (!res.ok) throw new Error('pdf');
                    return res.arrayBuffer();
                })
                .then(function (buf) {
                    revokeLightboxBlob();
                    lightboxBlobUrl = URL.createObjectURL(new Blob([buf], { type: 'application/pdf' }));
                    lightboxFrame.src = lightboxBlobUrl;
                })
                .catch(function () {
                    lightboxFrame.src = url;
                });
        }

        function openLightbox(url, name, mime) {
            if (!url) return;

            // Non-image files always open in a new tab/page (no in-app iframe).
            if (!isImageAttachment({ name: name || '', mime: mime || '', type: mime || '' }) || isPdfPreview(name, mime, url)) {
                window.open(url, '_blank', 'noopener,noreferrer');
                return;
            }

            if (!lightbox) return;
            revokeLightboxBlob();
            lightbox.classList.remove('is-pdf');
            if (lightboxDownload) {
                lightboxDownload.href = url;
                lightboxDownload.setAttribute('download', name || 'chat-image');
                lightboxDownload.hidden = false;
            }
            if (lightboxFrame) {
                lightboxFrame.hidden = true;
                lightboxFrame.removeAttribute('src');
            }
            if (lightboxImage) {
                lightboxImage.hidden = false;
                lightboxImage.removeAttribute('hidden');
                lightboxImage.onload = function () {
                    lightboxImage.classList.add('is-ready');
                };
                lightboxImage.onerror = function () {
                    lightboxImage.classList.remove('is-ready');
                    closeLightbox();
                    window.open(url, '_blank', 'noopener,noreferrer');
                };
                lightboxImage.classList.remove('is-ready');
                lightboxImage.src = url;
                lightboxImage.alt = name || 'Photo';
            }
            lightbox.hidden = false;
        }

        function closeLightbox() {
            if (!lightbox) return;
            lightbox.hidden = true;
            lightbox.classList.remove('is-pdf');
            revokeLightboxBlob();
            if (lightboxImage) {
                lightboxImage.hidden = false;
                lightboxImage.classList.remove('is-ready');
                lightboxImage.onload = null;
                lightboxImage.onerror = null;
                lightboxImage.src = '';
            }
            if (lightboxFrame) {
                lightboxFrame.hidden = true;
                lightboxFrame.removeAttribute('src');
            }
        }

        let searchTimer = null;
        async function runThreadSearch(query) {
            const hits = document.getElementById('chatSearchHits');
            if (!hits) return;
            query = String(query || '').trim();
            if (threadSearchClear) {
                threadSearchClear.hidden = query.length === 0;
            }
            if (query.length < 2) {
                hits.hidden = true;
                hits.innerHTML = '';
                return;
            }
            try {
                const response = await chatFetch(apiUrl(endpoint, {
                    action: 'search',
                    partner_id: partnerId,
                    partner_role: partnerRole,
                    q: query,
                }), {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                const data = await response.json();
                const results = data.results || [];
                if (!results.length) {
                    hits.hidden = false;
                    hits.innerHTML = '<p class="chat-float__empty">No matches in this conversation.</p>';
                    return;
                }
                hits.hidden = false;
                hits.innerHTML = '<p class="chat-search-hits__meta">' + results.length + ' match' + (results.length === 1 ? '' : 'es') + '</p>' +
                    results.map(function (row) {
                        return '<button type="button" class="chat-search-hit" data-jump-id="' + Number(row.id) + '">' +
                            '<strong>' + escapeHtml(row.sender_name || '') + '</strong>' +
                            '<span>' + escapeHtml(row.message_text || '') + '</span></button>';
                    }).join('');
            } catch (error) {
                hits.hidden = true;
            }
        }

        composerEl?.addEventListener('submit', function (event) {
            event.preventDefault();
            submitComposer();
        });

        inputEl?.addEventListener('input', function () {
            pulseTypingStatus();
            resizeComposer();
            updateCharCount();
            saveDraft();
            updateSendEnabled();
        });

        inputEl?.addEventListener('blur', clearTypingStatus);

        inputEl?.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                submitComposer();
            }
        });

        partnerListEl?.addEventListener('click', function (event) {
            const button = event.target.closest('.chat-partner');
            if (!button) return;
            setActivePartner(button);
        });

        searchEl?.addEventListener('input', applyPartnerFilter);

        document.querySelectorAll('[data-chat-filter]').forEach(function (button) {
            button.addEventListener('click', function () {
                partnerFilter = button.dataset.chatFilter || 'all';
                document.querySelectorAll('[data-chat-filter]').forEach(function (el) {
                    el.classList.toggle('is-active', el === button);
                });
                applyPartnerFilter();
            });
        });

        backBtn?.addEventListener('click', function () {
            showContactsPane();
        });

        attachBtn?.addEventListener('click', function () {
            if (!canSend) return;
            if (fileInput) {
                fileInput.setAttribute('accept', FILE_PICKER_ACCEPT);
                try { fileInput.accept = FILE_PICKER_ACCEPT; } catch (err) {}
            }
            fileInput?.click();
        });

        fileInput?.addEventListener('change', function () {
            addFiles(fileInput.files);
            fileInput.value = '';
        });

        app?.addEventListener('paste', onChatPaste);

        attachListEl?.addEventListener('click', function (event) {
            const remove = event.target.closest('[data-remove-file]');
            if (!remove) return;
            const index = Number(remove.getAttribute('data-remove-file'));
            const item = pendingFiles[index];
            if (item?.previewUrl) URL.revokeObjectURL(item.previewUrl);
            pendingFiles.splice(index, 1);
            renderAttachList();
            updateSendEnabled();
        });

        messagesEl?.addEventListener('click', function (event) {
            const retry = event.target.closest('[data-retry]');
            if (retry) {
                retryMessage(retry.getAttribute('data-retry'));
                return;
            }
            const chip = event.target.closest('[data-chat-emoji]');
            if (chip) {
                postThreadAction('react', {
                    message_id: Number(chip.getAttribute('data-message-id') || 0),
                    emoji: chip.getAttribute('data-chat-emoji') || '',
                }).then(applyMessageUpdate).catch(function (error) { console.error(error); });
                return;
            }
            const reactBtn = event.target.closest('[data-chat-react]');
            if (reactBtn) {
                popoverMessageId = Number(reactBtn.getAttribute('data-chat-react') || 0);
                openMessageTools(popoverMessageId);
                if (morePop) morePop.hidden = true;
                placePopover(reactPop, reactBtn);
                return;
            }
            const moreBtn = event.target.closest('[data-chat-more]');
            if (moreBtn) {
                popoverMessageId = Number(moreBtn.getAttribute('data-chat-more') || 0);
                openMessageTools(popoverMessageId);
                if (reactPop) reactPop.hidden = true;
                if (morePinBtn) morePinBtn.textContent = moreBtn.getAttribute('data-pinned') === '1' ? 'Unpin' : 'Pin';
                if (moreRemoveBtn) moreRemoveBtn.hidden = moreBtn.getAttribute('data-can-remove') !== '1';
                morePop.dataset.replyName = moreBtn.getAttribute('data-reply-name') || '';
                morePop.dataset.replyText = moreBtn.getAttribute('data-reply-text') || '';
                morePop.dataset.replySelf = moreBtn.getAttribute('data-reply-self') || '0';
                placePopover(morePop, moreBtn);
                return;
            }
            const thumb = event.target.closest('[data-chat-lightbox]');
            if (thumb) {
                if (thumb.classList.contains('is-broken')) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                closeMessagePops();
                const url = thumb.getAttribute('data-chat-lightbox') || thumb.getAttribute('href') || '';
                const name = thumb.getAttribute('data-chat-name') || '';
                const mime = thumb.getAttribute('data-chat-mime') || '';
                // Only images use lightbox; any file chip with lightbox attr opens in a new page.
                if (thumb.classList.contains('chat-file') || isPdfPreview(name, mime, url)) {
                    if (url) window.open(url, '_blank', 'noopener,noreferrer');
                    return;
                }
                openLightbox(url, name, mime);
                return;
            }
            const fileChip = event.target.closest('a.chat-file');
            if (fileChip) {
                event.stopPropagation();
                // Let the browser follow target="_blank".
                return;
            }

            // Mobile/touch: tap bubble to reveal action tools (side dock gets clipped on long messages).
            if (isCoarseChatUi()) {
                const article = event.target.closest('.chat-message');
                if (article && !article.classList.contains('is-deleted') && article.querySelector('.chat-message__tools')) {
                    const wasOpen = article.classList.contains('is-open');
                    closeMessagePops();
                    if (!wasOpen) {
                        article.classList.add('is-open');
                        ensureToolsInView(article);
                    }
                    return;
                }
            }
        });

        function isCoarseChatUi() {
            return window.matchMedia('(hover: none)').matches
                || window.matchMedia('(pointer: coarse)').matches
                || window.matchMedia('(max-width: 980px)').matches;
        }

        function ensureToolsInView(article) {
            const tools = article?.querySelector('.chat-message__tools');
            if (!tools || !messagesEl) return;
            const toolsRect = tools.getBoundingClientRect();
            const paneRect = messagesEl.getBoundingClientRect();
            if (toolsRect.top < paneRect.top + 8) {
                messagesEl.scrollTop -= (paneRect.top + 8) - toolsRect.top;
            }
        }

        let pressTimer = null;
        let pressMoved = false;
        let pressArticle = null;

        function clearPressTimer() {
            if (pressTimer) {
                window.clearTimeout(pressTimer);
                pressTimer = null;
            }
            pressArticle = null;
            pressMoved = false;
        }

        messagesEl?.addEventListener('pointerdown', function (event) {
            if (!isCoarseChatUi()) return;
            if (event.pointerType === 'mouse' && !window.matchMedia('(max-width: 980px)').matches) return;
            const article = event.target.closest('.chat-message');
            if (!article || article.classList.contains('is-deleted')) return;
            if (event.target.closest('a, button, .chat-message__tools, [data-chat-lightbox], .chat-file')) return;
            pressArticle = article;
            pressMoved = false;
            pressTimer = window.setTimeout(function () {
                if (!pressArticle || pressMoved) return;
                const moreBtn = pressArticle.querySelector('[data-chat-more]');
                if (!moreBtn) return;
                openMessageTools(Number(moreBtn.getAttribute('data-chat-more') || 0));
                ensureToolsInView(pressArticle);
                if (reactPop) reactPop.hidden = true;
                if (morePinBtn) morePinBtn.textContent = moreBtn.getAttribute('data-pinned') === '1' ? 'Unpin' : 'Pin';
                if (moreRemoveBtn) moreRemoveBtn.hidden = moreBtn.getAttribute('data-can-remove') !== '1';
                morePop.dataset.replyName = moreBtn.getAttribute('data-reply-name') || '';
                morePop.dataset.replyText = moreBtn.getAttribute('data-reply-text') || '';
                morePop.dataset.replySelf = moreBtn.getAttribute('data-reply-self') || '0';
                placePopover(morePop, moreBtn);
            }, 420);
        });

        messagesEl?.addEventListener('pointermove', function (event) {
            if (!pressTimer) return;
            if (Math.abs(event.movementX) + Math.abs(event.movementY) > 6) {
                pressMoved = true;
                clearPressTimer();
            }
        });

        messagesEl?.addEventListener('pointerup', clearPressTimer);
        messagesEl?.addEventListener('pointercancel', clearPressTimer);

        messagesEl?.addEventListener('scroll', function () {
            closeMessagePops();
            if (messagesEl.scrollTop < 48) {
                loadOlder();
            }
            if (nearBottom()) {
                unseenCount = 0;
                if (jumpBtn) jumpBtn.hidden = true;
            }
        });

        pinJumpBtn?.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (pinPop && !pinPop.hidden) {
                closePinPop();
                return;
            }
            openPinPop();
        });

        pinPopClose?.addEventListener('click', closePinPop);
        pinPopBackdrop?.addEventListener('click', closePinPop);
        pinPopList?.addEventListener('click', function (event) {
            const jump = event.target.closest('[data-jump-id]');
            if (!jump) return;
            closePinPop();
            jumpToMessage(jump.getAttribute('data-jump-id') || '');
        });

        reactPop?.addEventListener('click', function (event) {
            const emojiBtn = event.target.closest('[data-emoji]');
            if (!emojiBtn || !popoverMessageId) return;
            const emoji = emojiBtn.getAttribute('data-emoji') || '';
            const messageId = popoverMessageId;
            closeMessagePops();
            postThreadAction('react', { message_id: messageId, emoji: emoji })
                .then(applyMessageUpdate)
                .catch(function (error) { console.error(error); });
        });

        morePop?.addEventListener('click', function (event) {
            const act = event.target.closest('[data-chat-act]');
            if (!act || !popoverMessageId) return;
            const action = act.getAttribute('data-chat-act');
            const messageId = popoverMessageId;
            const replyName = morePop.dataset.replyName || '';
            const replyText = morePop.dataset.replyText || '';
            closeMessagePops();
            if (action === 'reply') {
                setReply(String(messageId), replyName, replyText, morePop.dataset.replySelf === '1');
                inputEl?.focus();
                return;
            }
            if (action === 'pin') {
                postThreadAction('pin', { message_id: messageId })
                    .then(applyMessageUpdate)
                    .catch(function (error) { console.error(error); });
                return;
            }
            if (action === 'remove') {
                const confirmRemove = typeof showConfirmModal === 'function'
                    ? showConfirmModal('This message will be removed from the conversation.', {
                        title: 'Remove message',
                        confirmText: 'Remove',
                        cancelText: 'Cancel',
                    })
                    : Promise.resolve(window.confirm('Remove this message?'));
                confirmRemove.then(function (ok) {
                    if (!ok) return;
                    postThreadAction('remove', { message_id: messageId })
                        .then(applyMessageUpdate)
                        .catch(function (error) { console.error(error); });
                });
            }
        });

        document.addEventListener('click', onDocumentClick);

        jumpBtn?.addEventListener('click', function () {
            scrollToBottom(true);
            unseenCount = 0;
            jumpBtn.hidden = true;
        });

        composerEl?.addEventListener('click', function (event) {
            if (event.target.closest('#chatReplyClear')) {
                event.preventDefault();
                event.stopPropagation();
                setReply(null);
            }
        });

        document.getElementById('chatReplyClear')?.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            setReply(null);
        });

        threadSearchBtn?.addEventListener('click', function () {
            if (!threadSearchWrap) return;
            if (threadSearchWrap.hidden) {
                openSearchPanel();
            } else {
                closeSearchPanel();
            }
        });

        threadSearchBackdrop?.addEventListener('click', closeSearchPanel);
        threadSearchClear?.addEventListener('click', function () {
            if (!threadSearchInput) return;
            threadSearchInput.value = '';
            threadSearchInput.focus();
            runThreadSearch('');
        });

        threadSearchInput?.addEventListener('input', function () {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(function () {
                runThreadSearch(threadSearchInput.value);
            }, 250);
        });

        threadSearchWrap?.addEventListener('click', function (event) {
            const jump = event.target.closest('[data-jump-id]');
            if (!jump) return;
            jumpToMessage(jump.getAttribute('data-jump-id') || '');
        });

        document.getElementById('chatLightboxClose')?.addEventListener('click', closeLightbox);
        lightbox?.addEventListener('click', function (event) {
            if (event.target === lightbox) closeLightbox();
        });

        window.addEventListener('online', function () { setConnection('connected'); fetchMessages(false).catch(function () {}); });
        window.addEventListener('offline', function () { setConnection('offline'); });

        function onKeydown(event) {
            if (event.key === 'Escape') {
                closeLightbox();
                closeMessagePops();
                closeSearchPanel();
                closePinPop();
            }
        }
        window.addEventListener('keydown', onKeydown);
        window.addEventListener('beforeunload', stopPolling);
        window.__liveChatCleanup = function () {
            stopPolling();
            closeMessagePops();
            closeSearchPanel();
            closePinPop();
            clearPendingFiles();
            window.removeEventListener('beforeunload', stopPolling);
            window.removeEventListener('keydown', onKeydown);
            document.removeEventListener('click', onDocumentClick);
            window.__liveChatCleanup = null;
        };

        hydrateFromDom();
        updatePinBar();
        resizeComposer();
        updateCharCount();
        restoreDraft();
        updateSendEnabled();
        if (messagesEl) scrollToBottom(true);
        navUnreadFromList();
        syncConversationChrome();
        if (partnerId && partnerRole) {
            showConversationPane();
            shellEl?.classList.add('has-conversation');
            syncConversationChrome();
            fetchMessages(true).catch(function () {});
            startPolling();
            syncChatUrl();
        } else if (window.matchMedia('(min-width: 981px)').matches) {
            // Desktop split view: open the first contact when landing on /chat.
            const firstPartner = partnerListEl?.querySelector('.chat-partner');
            if (firstPartner) {
                setActivePartner(firstPartner, true);
            }
        }
        applyPartnerFilter();
        setConnection(navigator.onLine ? 'connected' : 'offline');
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            window.initChatUnreadNav();
        });
    } else {
        window.initChatUnreadNav();
    }
})();
