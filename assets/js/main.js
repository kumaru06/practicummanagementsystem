document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initStudentNavReveal();
    initTextMarquees();
    initStudentMobileNav();
    initStudentDashboardJourney();
    initStudentDocsStepperScroll();
    initToasts();
    initFloatingLabels();
    initCustomFilterSelects();
    initDateTimePickers();
    initCustomDatePickers();
    initPhoneInputs();
    initCharacterCounters();
    initDtrTimeLocks();
    initForms();
    initStudentRegistrationAvailability();
    initStudentRegistrationPasswordIndicators();
    initRegisterCourseSelect();
    initRegistrationBackLink();
    initRegistrationSuccessCountdown();
    initCoordinatorAvailability();
    initCoordinatorDirectory();
    initPartnerAvailability();
    initCounters();
    initWizards();
    initEnrollmentAutomation();
    initEnrollmentDirectory();
    initEnrollmentWizardModal();
    initEnrollmentWizardSelectPortals();
    initEnrollmentCorUpload();
    initViewToggles();
    initTimelineDetails();
    initStudentTimelineFilters();
    initEmailLogViews();
    initRequirementReviewModals();
    initRegistrationRequestsReview();
    initPasswordResetRequests();
    initNotifications();
    try { initWeeklyReportUpload(); } catch (err) { console.warn('Weekly report upload init failed:', err); }
    try { initWeeklyReportResubmitDateRange(); } catch (err) { console.warn('Weekly report date range init failed:', err); }
    initMoaLibrary();
    initCoordinatorCardAlignment();
    initCapitalizeWordInputs();
    initConfirmActions();
    initStudentMobileTapProxy();
    initStudentMobileInputZoomFix();
    initStudentProfilePhotoPreview();
    initPartnerPasswordChange();
    initStudentPasswordChange();
    initPartnerPortalRoster();
    initPartnerSubmissions();
    initPartnerSubmissionsPopstate();
    initAppAjaxNav();
    document.querySelectorAll('.data-table').forEach(table => enhanceTable(table));
    initEnrollmentFilters();
    initMyStudentsDirectory();
    initAdminStudentsDirectory();
    initAdminProgramsDirectory();
    initAdminPartnersDirectory();
    initAdminOjtPlacementDirectory();
    initPartnerCreateAccordion();
    initPartnerCreateReviewConfirm();
    initAdminCreateStudentModal();
    initAdminTermsPage();
    initAdminActivitiesFeed();
    initEmailLogsFeed();
    document.querySelector('#modal .modal-close')?.addEventListener('click', closeSlidePanel);
    document.addEventListener('click', handleOutsideMenus);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeSlidePanel(); closeAdminActionMenus(); closeNotifications(); closeRequirementReviewModals(); closeRegistrationRequestsReview(); closeCustomSelects(); closeCustomDatePickers(); closeDtrTimePicker(); } });
    initStudentModal();
    initAdminUserActions();
    renderDashboardCharts();
    initLiveChat();
});

function initStudentProfilePhotoPreview() {
    const form = document.querySelector('[data-student-profile-form]');
    const input = form?.querySelector('[data-profile-photo-input]') || document.querySelector('[data-profile-photo-input]');
    const preview = document.querySelector('[data-profile-photo-preview]');
    const fallback = document.querySelector('[data-profile-photo-fallback]');
    const inlinePreview = document.querySelector('[data-profile-photo-preview-inline]');
    const inlineFallback = document.querySelector('[data-profile-initial-inline]');
    const inlineAvatar = document.querySelector('[data-profile-inline-avatar]');
    const cropEnabled = !!form?.hasAttribute('data-profile-photo-crop');
    const genderSelect = form?.querySelector('[data-profile-gender-select]');
    const genderPreview = form?.querySelector('[data-profile-gender-preview]');

    const setInlinePhoto = url => {
        if (!inlinePreview) return;
        if (!url) {
            inlinePreview.classList.add('is-hidden');
            inlinePreview.removeAttribute('src');
            inlineFallback?.classList.remove('is-hidden');
            inlineAvatar?.classList.remove('app-user-identity__avatar--photo');
            return;
        }
        inlinePreview.src = url;
        inlinePreview.classList.remove('is-hidden');
        inlineFallback?.classList.add('is-hidden');
        inlineAvatar?.classList.add('app-user-identity__avatar--photo');
    };

    const setSidebarPhoto = url => {
        if (!preview || !fallback) return;
        if (!url) {
            preview.classList.add('is-hidden');
            preview.removeAttribute('src');
            fallback.classList.remove('is-hidden');
            return;
        }
        preview.src = url;
        preview.classList.remove('is-hidden');
        fallback.classList.add('is-hidden');
    };

    const showPhoto = url => {
        setSidebarPhoto(url);
        setInlinePhoto(url);
    };

    const showFallback = () => showPhoto('');

    const assignInputFile = file => {
        if (!input || !file) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
    };

    if (preview && fallback) {
        const previewSrc = (preview.getAttribute('src') || '').trim();
        if (!previewSrc) {
            setSidebarPhoto('');
        } else {
            preview.addEventListener('error', () => setSidebarPhoto(''), { once: true });
        }
    }

    const inlineSrc = (inlinePreview?.getAttribute('src') || '').trim();
    if (inlineSrc) {
        setInlinePhoto(inlineSrc);
        inlinePreview?.addEventListener('error', () => setInlinePhoto(''), { once: true });
    } else {
        setInlinePhoto('');
    }

    genderSelect?.addEventListener('change', () => {
        if (!genderPreview) return;
        const value = (genderSelect.value || '').trim();
        genderPreview.textContent = value !== '' ? value : '—';
    });

    if (!input) return;

    if (!cropEnabled) {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file || !file.type.startsWith('image/')) {
                if (file) {
                    input.value = '';
                    pushAppToast('Profile photo must be a JPG or PNG image.', 'error');
                }
                showFallback();
                return;
            }
            showPhoto(URL.createObjectURL(file));
        });
        return;
    }

    initProfilePhotoCropModal({
        input,
        showPhoto,
        showFallback,
        assignInputFile,
    });
}

function initProfilePhotoCropModal({ input, showPhoto, showFallback, assignInputFile }) {
    const overlay = document.querySelector('[data-profile-crop-overlay]');
    const stage = overlay?.querySelector('[data-profile-crop-stage]');
    const cropImage = overlay?.querySelector('[data-profile-crop-image]');
    const zoomInput = overlay?.querySelector('[data-profile-crop-zoom]');
    const applyBtn = overlay?.querySelector('[data-profile-crop-apply]');
    const cancelBtns = overlay?.querySelectorAll('[data-profile-crop-cancel]') || [];

    if (!overlay || !stage || !cropImage || !zoomInput || !applyBtn) return;

    const viewportSize = 320;
    const outputSize = 640;
    let objectUrl = '';
    let pendingFile = null;
    let baseScale = 1;
    let zoomFactor = 1;
    let offsetX = 0;
    let offsetY = 0;
    let dragging = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let dragOriginX = 0;
    let dragOriginY = 0;

    const clampOffsets = () => {
        const scale = baseScale * zoomFactor;
        const displayW = cropImage.naturalWidth * scale;
        const displayH = cropImage.naturalHeight * scale;
        const maxX = Math.max(0, (displayW - viewportSize) / 2);
        const maxY = Math.max(0, (displayH - viewportSize) / 2);
        offsetX = Math.min(maxX, Math.max(-maxX, offsetX));
        offsetY = Math.min(maxY, Math.max(-maxY, offsetY));
    };

    const renderCrop = () => {
        const scale = baseScale * zoomFactor;
        cropImage.style.transform = `translate(calc(-50% + ${offsetX}px), calc(-50% + ${offsetY}px)) scale(${scale})`;
    };

    const resetCropState = () => {
        zoomFactor = 1;
        zoomInput.value = '1';
        offsetX = 0;
        offsetY = 0;
        if (cropImage.naturalWidth > 0 && cropImage.naturalHeight > 0) {
            baseScale = Math.max(viewportSize / cropImage.naturalWidth, viewportSize / cropImage.naturalHeight);
        }
        clampOffsets();
        renderCrop();
    };

    const closeCropModal = (clearInput = false) => {
        overlay.hidden = true;
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-profile-crop-open');
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = '';
        }
        pendingFile = null;
        cropImage.removeAttribute('src');
        dragging = false;
        if (clearInput) {
            input.value = '';
        }
    };

    const openCropModal = file => {
        pendingFile = file;
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        cropImage.onload = () => {
            resetCropState();
        };
        cropImage.src = objectUrl;
        overlay.hidden = false;
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-profile-crop-open');
    };

    const getCropMetrics = () => {
        const scale = baseScale * zoomFactor;
        const displayW = cropImage.naturalWidth * scale;
        const displayH = cropImage.naturalHeight * scale;
        const imageLeft = (viewportSize - displayW) / 2 + offsetX;
        const imageTop = (viewportSize - displayH) / 2 + offsetY;
        const sourceX = Math.max(0, (0 - imageLeft) / scale);
        const sourceY = Math.max(0, (0 - imageTop) / scale);
        const sourceSize = viewportSize / scale;
        const maxX = Math.max(0, cropImage.naturalWidth - sourceSize);
        const maxY = Math.max(0, cropImage.naturalHeight - sourceSize);
        return {
            sourceX: Math.min(maxX, sourceX),
            sourceY: Math.min(maxY, sourceY),
            sourceSize: Math.min(sourceSize, cropImage.naturalWidth, cropImage.naturalHeight),
        };
    };

    const applyCrop = () => {
        if (!pendingFile || cropImage.naturalWidth <= 0) return;
        const { sourceX, sourceY, sourceSize } = getCropMetrics();
        const canvas = document.createElement('canvas');
        canvas.width = outputSize;
        canvas.height = outputSize;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        ctx.drawImage(
            cropImage,
            sourceX,
            sourceY,
            sourceSize,
            sourceSize,
            0,
            0,
            outputSize,
            outputSize,
        );

        canvas.toBlob(blob => {
            if (!blob) {
                pushAppToast('Unable to crop profile photo.', 'error');
                return;
            }
            const ext = pendingFile.type === 'image/png' ? 'png' : 'jpg';
            const croppedFile = new File([blob], `profile-photo.${ext}`, {
                type: pendingFile.type === 'image/png' ? 'image/png' : 'image/jpeg',
                lastModified: Date.now(),
            });
            assignInputFile(croppedFile);
            showPhoto(URL.createObjectURL(croppedFile));
            closeCropModal(false);
        }, pendingFile.type === 'image/png' ? 'image/png' : 'image/jpeg', 0.92);
    };

    input.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file || !file.type.startsWith('image/')) {
            if (file) {
                input.value = '';
                pushAppToast('Profile photo must be a JPG or PNG image.', 'error');
            }
            showFallback();
            return;
        }
        openCropModal(file);
    });

    zoomInput.addEventListener('input', () => {
        zoomFactor = Number.parseFloat(zoomInput.value) || 1;
        clampOffsets();
        renderCrop();
    });

    const startDrag = (clientX, clientY) => {
        dragging = true;
        dragStartX = clientX;
        dragStartY = clientY;
        dragOriginX = offsetX;
        dragOriginY = offsetY;
        stage.classList.add('is-dragging');
    };

    const moveDrag = (clientX, clientY) => {
        if (!dragging) return;
        offsetX = dragOriginX + (clientX - dragStartX);
        offsetY = dragOriginY + (clientY - dragStartY);
        clampOffsets();
        renderCrop();
    };

    const endDrag = () => {
        dragging = false;
        stage.classList.remove('is-dragging');
    };

    stage.addEventListener('mousedown', event => {
        event.preventDefault();
        startDrag(event.clientX, event.clientY);
    });
    window.addEventListener('mousemove', event => moveDrag(event.clientX, event.clientY));
    window.addEventListener('mouseup', endDrag);

    stage.addEventListener('touchstart', event => {
        if (!event.touches[0]) return;
        startDrag(event.touches[0].clientX, event.touches[0].clientY);
    }, { passive: true });
    stage.addEventListener('touchmove', event => {
        if (!event.touches[0]) return;
        moveDrag(event.touches[0].clientX, event.touches[0].clientY);
    }, { passive: true });
    stage.addEventListener('touchend', endDrag);

    applyBtn.addEventListener('click', applyCrop);
    cancelBtns.forEach(btn => {
        btn.addEventListener('click', () => closeCropModal(true));
    });
    overlay.addEventListener('click', event => {
        if (event.target === overlay) closeCropModal(true);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && overlay.classList.contains('is-open')) {
            closeCropModal(true);
        }
    });
}

function initConfirmActions() {
    document.querySelectorAll('[data-confirm]').forEach(element => {
        if (element.dataset.confirmReady === '1') return;
        element.dataset.confirmReady = '1';
        element.addEventListener('click', async event => {
            event.preventDefault();
            event.stopPropagation();
            const confirmed = await showConfirmModal(element.dataset.confirm || 'Are you sure?', {
                title: element.dataset.confirmTitle || 'Confirm action',
                confirmText: element.dataset.confirmOk || 'Continue',
                cancelText: element.dataset.confirmCancel || 'Cancel',
            });
            if (!confirmed) return;
            if (element.tagName === 'A' && element.href) {
                window.location.href = element.href;
                return;
            }
            element.closest('form')?.requestSubmit();
        });
    });
}

function buildConfirmCardHtml({
    variant = 'confirm',
    title = '',
    titleId = 'app-confirm-title',
    message = '',
    messageHtml = '',
    iconSvg = '',
    actionsHtml = '',
    centered = false,
    busy = false,
    role = 'dialog',
}) {
    const processingDots = variant === 'processing'
        ? '<div class="app-confirm-dots" aria-hidden="true"><span></span><span></span><span></span></div>'
        : '';

    const iconMap = {
        alert: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v5"/><path d="M12 16h.01"/></svg>',
        confirm: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><path d="M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v0a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/><path d="m9 14 2 2 4-4"/></svg>',
        review: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><path d="M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v0a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/><path d="M9 12h6"/><path d="M9 16h4"/></svg>',
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>',
        processing: '',
    };

    let bodyHtml = '';
    const resolvedIcon = iconSvg || iconMap[variant] || iconMap.confirm;

    if (centered) {
        const markHtml = variant === 'processing'
            ? '<span class="app-confirm-spinner" aria-hidden="true"></span>'
            : resolvedIcon;

        bodyHtml = `
            <div class="app-confirm-stage app-confirm-stage--center">
                <div class="app-confirm-mark app-confirm-mark--${variant}" aria-hidden="true">${markHtml}</div>
                <h2 id="${titleId}">${escapeHtml(title)}</h2>
                ${messageHtml || (message ? `<p class="app-confirm-message">${escapeHtml(message)}</p>` : '')}
                ${processingDots}
            </div>
        `;
    } else {
        const iconHtml = variant === 'processing'
            ? '<span class="app-confirm-spinner" aria-hidden="true"></span>'
            : resolvedIcon;

        const contentHtml = messageHtml
            || (message ? `<div class="app-confirm-message-box"><p class="app-confirm-message">${escapeHtml(message)}</p></div>` : '');

        bodyHtml = `
            <div class="app-confirm-stage">
                <div class="app-confirm-icon app-confirm-icon--${variant}" aria-hidden="true">${iconHtml}</div>
                <h2 id="${titleId}">${escapeHtml(title)}</h2>
                ${contentHtml}
                ${processingDots}
            </div>
        `;
    }

    const footerHtml = actionsHtml ? `<div class="app-confirm-footer">${actionsHtml}</div>` : '';

    return `
        <div class="app-confirm-card app-confirm-card--${variant}" role="${role}" aria-modal="true" aria-labelledby="${titleId}"${busy ? ' aria-busy="true"' : ''}>
            ${bodyHtml}
            ${footerHtml}
        </div>
    `;
}

function showConfirmModal(message, options = {}) {
    return new Promise(resolve => {
        const existing = document.querySelector('.app-confirm-overlay');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.className = 'app-confirm-overlay';
        overlay.innerHTML = buildConfirmCardHtml({
            variant: options.variant || 'confirm',
            title: options.title || 'Confirm action',
            titleId: 'app-confirm-title',
            message: options.messageHtml ? '' : message,
            messageHtml: options.messageHtml || '',
            iconSvg: options.iconSvg || '',
            actionsHtml: `
                <div class="app-confirm-actions app-confirm-actions--stacked">
                    <button class="app-confirm-btn app-confirm-btn--primary app-confirm-ok" type="button">${escapeHtml(options.confirmText || 'Continue')}</button>
                    <button class="app-confirm-btn app-confirm-btn--ghost app-confirm-cancel" type="button">${escapeHtml(options.cancelText || 'Cancel')}</button>
                </div>
            `,
        });
        if (options.preLine) {
            overlay.querySelector('.app-confirm-message')?.classList.add('app-confirm-message--preline');
        }

        const close = value => {
            overlay.classList.remove('is-open');
            document.body.classList.remove('is-confirm-open');
            document.removeEventListener('keydown', onKeydown);
            setTimeout(() => overlay.remove(), 160);
            resolve(value);
        };
        const onKeydown = event => {
            if (event.key === 'Escape') close(false);
        };

        overlay.addEventListener('click', event => {
            if (event.target === overlay) close(false);
        });
        overlay.querySelector('.app-confirm-cancel')?.addEventListener('click', () => close(false));
        overlay.querySelector('.app-confirm-ok')?.addEventListener('click', () => {
            if (options.persistOnConfirm) {
                document.removeEventListener('keydown', onKeydown);
                resolve({ confirmed: true, overlay });
                return;
            }
            close(true);
        });
        document.addEventListener('keydown', onKeydown);
        document.body.classList.add('is-confirm-open');
        document.body.appendChild(overlay);
        requestAnimationFrame(() => {
            overlay.classList.add('is-open');
            overlay.querySelector('.app-confirm-ok')?.focus();
        });
    });
}

function ensureConfirmOverlay() {
    let overlay = document.querySelector('.app-confirm-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'app-confirm-overlay';
        document.body.appendChild(overlay);
    }
    return overlay;
}

function replaceConfirmOverlay(overlay) {
    const freshOverlay = document.createElement('div');
    freshOverlay.className = overlay.className;
    overlay.replaceWith(freshOverlay);
    return freshOverlay;
}

function openConfirmOverlay(overlay) {
    document.body.classList.add('is-confirm-open');
    requestAnimationFrame(() => overlay.classList.add('is-open'));
}

function setConfirmOverlayCard(overlay, html) {
    overlay.innerHTML = html;
    openConfirmOverlay(overlay);
}

function dismissConfirmOverlay(overlay) {
    return new Promise(resolve => {
        overlay.classList.remove('is-open');
        document.body.classList.remove('is-confirm-open');
        setTimeout(() => {
            overlay.remove();
            resolve();
        }, 160);
    });
}

function showProcessingModal(overlay, options = {}) {
    const target = replaceConfirmOverlay(overlay);
    setConfirmOverlayCard(target, buildConfirmCardHtml({
        variant: 'processing',
        title: options.title || 'Processing...',
        message: options.message || 'Please wait while we complete your request.',
        centered: true,
        busy: true,
    }));
    return target;
}

function showSuccessModal(overlay, message, options = {}) {
    const canMorph = Boolean(
        overlay?.querySelector('.app-confirm-card--processing .app-confirm-mark--processing')
    );
    if (canMorph) {
        return morphProcessingToSuccess(overlay, message, options);
    }

    const target = replaceConfirmOverlay(overlay);
    return new Promise(resolve => {
        setConfirmOverlayCard(target, buildConfirmCardHtml({
            variant: 'success',
            title: options.title || 'Success',
            message,
            centered: true,
            role: 'alertdialog',
            actionsHtml: `
                <div class="app-confirm-actions app-confirm-actions--single">
                    <button class="app-confirm-btn app-confirm-btn--primary app-confirm-success-ok" type="button">${escapeHtml(options.confirmText || 'Done')}</button>
                </div>
            `,
        }));
        const finish = () => {
            dismissConfirmOverlay(target).then(() => {
                if (options.redirect) {
                    window.location.href = options.redirect;
                }
                resolve();
            });
        };
        target.querySelector('.app-confirm-success-ok')?.addEventListener('click', finish);
        target.querySelector('.app-confirm-success-ok')?.focus();
    });
}

function morphProcessingToSuccess(overlay, message, options = {}) {
    return new Promise(resolve => {
        const card = overlay.querySelector('.app-confirm-card');
        const stage = overlay.querySelector('.app-confirm-stage');
        const mark = overlay.querySelector('.app-confirm-mark');
        const title = overlay.querySelector('h2');
        const messageEl = overlay.querySelector('.app-confirm-message');
        const dots = overlay.querySelector('.app-confirm-dots');
        if (!card || !mark || !stage) {
            showSuccessModal(replaceConfirmOverlay(overlay), message, options).then(resolve);
            return;
        }

        dots?.classList.add('is-fading-out');
        mark.classList.add('is-morphing');

        window.setTimeout(() => {
            dots?.remove();
            card.classList.remove('app-confirm-card--processing');
            card.classList.add('app-confirm-card--success');
            card.removeAttribute('aria-busy');
            card.setAttribute('role', 'alertdialog');

            mark.classList.remove('app-confirm-mark--processing');
            mark.classList.add('app-confirm-mark--success', 'is-check-in');
            mark.innerHTML = `
                <svg class="app-confirm-check" viewBox="0 0 24 24" aria-hidden="true" fill="none">
                    <circle class="app-confirm-check-ring" cx="12" cy="12" r="10"></circle>
                    <path class="app-confirm-check-path" d="M7.2 12.4l3.1 3.1 6.5-6.6"></path>
                </svg>
            `;

            if (title) title.textContent = options.title || 'Success';
            if (messageEl) {
                messageEl.textContent = message || '';
            } else if (message) {
                const p = document.createElement('p');
                p.className = 'app-confirm-message';
                p.textContent = message;
                stage.appendChild(p);
            }

            let footer = overlay.querySelector('.app-confirm-footer');
            if (!footer) {
                footer = document.createElement('div');
                footer.className = 'app-confirm-footer';
                footer.innerHTML = `
                    <div class="app-confirm-actions app-confirm-actions--single">
                        <button class="app-confirm-btn app-confirm-btn--primary app-confirm-success-ok" type="button">${escapeHtml(options.confirmText || 'Done')}</button>
                    </div>
                `;
                card.appendChild(footer);
            }

            const finish = () => {
                dismissConfirmOverlay(overlay).then(() => {
                    if (options.redirect) {
                        window.location.href = options.redirect;
                    }
                    resolve();
                });
            };
            const okBtn = footer.querySelector('.app-confirm-success-ok');
            okBtn?.addEventListener('click', finish);
            window.setTimeout(() => okBtn?.focus(), 280);
        }, 180);
    });
}

async function submitFormWithStatusModals(form, overlay = null, options = {}) {
    if (!overlay) {
        overlay = ensureConfirmOverlay();
    }
    overlay = showProcessingModal(overlay, {
        title: form.dataset.submitProcessingTitle || form.dataset.confirmProcessingTitle || 'Processing...',
        message: form.dataset.submitProcessingMessage || form.dataset.confirmProcessingMessage || 'Please wait while we complete your request.',
    });

    const showSuccess = options.showSuccess ?? (form.dataset.submitAsyncSuccess === '1' || form.dataset.confirmAsync === '1');

    try {
        const response = await fetch(form.getAttribute('action') || window.location.href, {
            method: (form.getAttribute('method') || 'POST').toUpperCase(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
        });
        const data = await response.json().catch(() => null);
        if (!response.ok || !data?.ok) {
            await dismissConfirmOverlay(overlay);
            await showAlertModal(data?.message || 'Something went wrong. Please try again.', {
                title: form.dataset.submitErrorTitle || form.dataset.confirmErrorTitle || 'Unable to complete',
            });
            return;
        }
        if (showSuccess) {
            await showSuccessModal(overlay, data.message || 'Completed successfully.', {
                title: form.dataset.submitSuccessTitle || form.dataset.confirmSuccessTitle || 'Success',
                confirmText: form.dataset.submitSuccessOk || form.dataset.confirmSuccessOk || 'Done',
                redirect: data.redirect || form.dataset.submitSuccessRedirect || form.dataset.confirmSuccessRedirect || window.location.href,
            });
            return;
        }
        window.location.href = data.redirect || window.location.href;
    } catch (err) {
        await dismissConfirmOverlay(overlay);
        await showAlertModal(err?.message || 'Network error. Please try again.', {
            title: form.dataset.submitErrorTitle || form.dataset.confirmErrorTitle || 'Unable to complete',
        });
    }
}

async function submitFormWithAsyncConfirm(form, overlay) {
    await submitFormWithStatusModals(form, overlay, { showSuccess: true });
}

function showAlertModal(message, options = {}) {
    return new Promise(resolve => {
        const existing = document.querySelector('.app-confirm-overlay');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.className = 'app-confirm-overlay';
        overlay.innerHTML = buildConfirmCardHtml({
            variant: 'alert',
            title: options.title || 'Notice',
            titleId: 'app-alert-title',
            message,
            role: 'alertdialog',
            actionsHtml: `
                <div class="app-confirm-actions app-confirm-actions--single">
                    <button class="app-confirm-btn app-confirm-btn--primary app-alert-ok" type="button">${escapeHtml(options.confirmText || 'OK')}</button>
                </div>
            `,
        });

        const close = () => {
            overlay.classList.remove('is-open');
            document.body.classList.remove('is-confirm-open');
            document.removeEventListener('keydown', onKeydown);
            setTimeout(() => overlay.remove(), 160);
            resolve();
        };
        const onKeydown = event => {
            if (event.key === 'Escape' || event.key === 'Enter') close();
        };

        overlay.addEventListener('click', event => {
            if (event.target === overlay) close();
        });
        overlay.querySelector('.app-alert-ok')?.addEventListener('click', close);
        document.addEventListener('keydown', onKeydown);
        document.body.classList.add('is-confirm-open');
        document.body.appendChild(overlay);
        requestAnimationFrame(() => {
            overlay.classList.add('is-open');
            overlay.querySelector('.app-alert-ok')?.focus();
        });
    });
}

function initStudentMobileTapProxy() {
    if (!document.body.classList.contains('role-student')) return;

    let proxying = false;
    let suppressUntil = 0;
    let touchGesture = null;
    let blockClickUntil = 0;
    const isMobileStudentLayout = () => window.matchMedia('(max-width: 720px)').matches;
    const clickableSelector = 'button,a,input:not([type="hidden"]),textarea,select,[data-time-lock-toggle],[data-time-picker-trigger],.filter-date-trigger';
    const skipSelector = '.notif-panel,.topbar,.sidebar,.student-bottom-nav-root,.global-cal-panel,.global-datetime-panel,.dtr-time-panel,.student-nav-sheet-backdrop,.nav-group-items,[data-docs-sheet="1"],.student-docs-sheet-item,.chat-app,.app-confirm-overlay,#modal';
    const TAP_MOVE_THRESHOLD_PX = 12;
    const TAP_MAX_DURATION_MS = 450;

    const findContentControl = (x, y) => {
        const content = document.querySelector('.role-student .content');
        if (!content) return null;

        const elements = document.elementsFromPoint(x, y);
        for (const element of elements) {
            if (!element || element === document.documentElement || element === document.body) continue;
            if (element.closest('.app-confirm-overlay, #modal')) continue;
            if (element.closest(skipSelector)) continue;
            if (!content.contains(element)) continue;

            const control = element.matches(clickableSelector) ? element : element.closest(clickableSelector);
            if (control && content.contains(control)) return control;
        }
        return null;
    };

    const isScrollLikeGesture = () => {
        if (!touchGesture) return false;
        if (touchGesture.moved) return true;
        if (Math.abs(window.scrollY - touchGesture.scrollY) > 2) return true;
        if (Date.now() - touchGesture.time > TAP_MAX_DURATION_MS) return true;
        return false;
    };

    const resetTouchGesture = () => {
        touchGesture = null;
    };

    document.addEventListener('touchstart', event => {
        if (!isMobileStudentLayout()) return;
        const point = event.touches?.[0];
        if (!point) return;

        touchGesture = {
            x: point.clientX,
            y: point.clientY,
            scrollY: window.scrollY,
            time: Date.now(),
            moved: false,
        };
    }, { capture: true, passive: true });

    document.addEventListener('touchmove', event => {
        if (!touchGesture) return;
        const point = event.touches?.[0];
        if (!point) return;

        const deltaX = Math.abs(point.clientX - touchGesture.x);
        const deltaY = Math.abs(point.clientY - touchGesture.y);
        if (deltaX > TAP_MOVE_THRESHOLD_PX || deltaY > TAP_MOVE_THRESHOLD_PX) {
            touchGesture.moved = true;
        }
    }, { capture: true, passive: true });

    document.addEventListener('touchcancel', resetTouchGesture, { capture: true, passive: true });

    const forwardTap = event => {
        if (proxying || !isMobileStudentLayout()) return;
        if (document.querySelector('.app-confirm-overlay.is-open, #modal.open')) return;
        if (event.target instanceof Element && event.target.closest(skipSelector)) return;
        if (Date.now() < suppressUntil) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }
        if (Date.now() < blockClickUntil) {
            event.preventDefault();
            event.stopImmediatePropagation();
            resetTouchGesture();
            return;
        }

        const isTouchEnd = event.type === 'touchend';
        if (isTouchEnd && isScrollLikeGesture()) {
            blockClickUntil = Date.now() + 350;
            resetTouchGesture();
            return;
        }

        const point = event.changedTouches?.[0] || event.touches?.[0] || event;
        if (typeof point.clientX !== 'number' || typeof point.clientY !== 'number') return;

        const topbar = document.querySelector('.role-student .topbar')?.getBoundingClientRect();
        const sidebar = document.querySelector('.role-student .sidebar')?.getBoundingClientRect();
        if (topbar && point.clientY <= topbar.bottom) return;
        if (sidebar && point.clientY >= sidebar.top - 4) return;

        const control = findContentControl(point.clientX, point.clientY);
        if (!control) {
            if (isTouchEnd) resetTouchGesture();
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        proxying = true;
        suppressUntil = Date.now() + 450;
        try {
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(control.tagName) && control.type !== 'button' && control.type !== 'submit') {
                control.focus();
                if (control.type === 'file') control.click();
            } else {
                control.click();
            }
        } finally {
            setTimeout(() => { proxying = false; }, 0);
            if (isTouchEnd) resetTouchGesture();
        }
    };

    document.addEventListener('touchend', forwardTap, { capture: true, passive: false });
    document.addEventListener('click', forwardTap, { capture: true });
}

function initStudentMobileInputZoomFix() {
    if (!document.body.classList.contains('role-student')) return;

    const mq = window.matchMedia('(max-width: 720px)');
    const viewport = document.querySelector('meta[name="viewport"]');
    if (!viewport) return;

    const baseViewport = viewport.getAttribute('content')
        || 'width=device-width, initial-scale=1, viewport-fit=cover';
    let resetTimer = null;

    const resetIosZoom = () => {
        if (!mq.matches) return;
        // Nudge Safari back to 1x after keyboard dismiss (when inputs used sub-16px fonts).
        viewport.setAttribute('content', `${baseViewport}, maximum-scale=1`);
        requestAnimationFrame(() => viewport.setAttribute('content', baseViewport));
    };

    document.addEventListener('focusout', event => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (!['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return;
        clearTimeout(resetTimer);
        resetTimer = window.setTimeout(resetIosZoom, 120);
    }, true);

    mq.addEventListener('change', () => {
        if (!mq.matches) resetIosZoom();
    });
}

function initMoaLibrary() {
    const library = document.querySelector('[data-cdoc-library]');
    if (!library) return;

    const searchInput = library.querySelector('[data-cdoc-search]');
    const filters = Array.from(library.querySelectorAll('[data-cdoc-filter]'));
    const cards = Array.from(library.querySelectorAll('[data-cdoc-card]'));
    const count = library.querySelector('[data-cdoc-visible-count]');
    const noResults = library.querySelector('[data-cdoc-no-results]');
    let activeFilter = 'all';

    const applyFilters = () => {
        const query = (searchInput?.value || '').trim().toLowerCase();
        let visible = 0;

        cards.forEach(card => {
            const status = card.dataset.status || '';
            const searchable = card.dataset.search || '';
            const matchesStatus = activeFilter === 'all' || status === activeFilter;
            const matchesSearch = query === '' || searchable.includes(query);
            const shouldShow = matchesStatus && matchesSearch;

            card.hidden = !shouldShow;
            if (shouldShow) visible += 1;
        });

        if (count) count.textContent = String(visible);
        if (noResults) noResults.hidden = visible !== 0;
    };

    searchInput?.addEventListener('input', applyFilters);
    filters.forEach(button => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.cdocFilter || 'all';
            filters.forEach(item => item.classList.toggle('is-active', item === button));
            applyFilters();
        });
    });

    applyFilters();
}

function formatPersonNameValue(value) {
    return value
        .replace(/[^A-Za-z\s\-\.]/g, '')
        .replace(/\b[a-z]/g, char => char.toUpperCase());
}

function initCapitalizeWordInputs() {
    document.querySelectorAll('[data-capitalize-words]').forEach(input => {
        input.addEventListener('input', () => {
            const cursor = input.selectionStart;
            const formatted = formatPersonNameValue(input.value);
            if (formatted === input.value) return;
            input.value = formatted;
            if (cursor !== null) {
                input.setSelectionRange(cursor, cursor);
            }
        });
    });
}

function initCoordinatorCardAlignment() {
    // Legacy no-op: coordinator page now uses stacked layout (form top, table below).
}

function initAdminDirectoryActions() {
    document.querySelectorAll('.admin-users-page, .admin-coordinators-page, [data-admin-programs-directory], [data-admin-partners-directory]').forEach(page => {
        bindAdminDirectoryActions(page);
    });
}

/* -- Global shared calendar panel (escapes all overflow/transform ancestors) -- */
let _globalCalPanel = null;
let _globalCalActivePicker = null;
let _globalCalState = null;

function getGlobalCalPanel() {
    if (_globalCalPanel) return _globalCalPanel;
    _globalCalPanel = document.createElement('div');
    _globalCalPanel.className = 'global-cal-panel';
    _globalCalPanel.hidden = true;
    document.body.appendChild(_globalCalPanel);

    _globalCalPanel.addEventListener('mousedown', e => e.stopPropagation());
    _globalCalPanel.addEventListener('click', e => {
        e.stopPropagation();
        const nav = e.target.closest('[data-date-nav]');
        if (nav && _globalCalState) {
            const delta = Number(nav.dataset.dateNav || 0);
            _globalCalState.view = new Date(_globalCalState.view.getFullYear(), _globalCalState.view.getMonth() + delta, 1);
            _globalCalPanel.innerHTML = buildCustomDatePanel(_globalCalState);
            return;
        }
        const action = e.target.closest('[data-date-action]');
        if (action && _globalCalActivePicker) {
            const { input, state, sync } = _globalCalActivePicker;
            if (action.dataset.dateAction === 'clear') {
                state.selected = null; input.value = ''; sync();
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (action.dataset.dateAction === 'today') {
                const today = stripTime(new Date());
                state.selected = today; state.view = new Date(today.getFullYear(), today.getMonth(), 1);
                input.value = formatCustomDateValue(today); sync();
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
            closeGlobalCalPanel();
            return;
        }
        const day = e.target.closest('[data-date-value]');
        if (day && _globalCalActivePicker) {
            const { input, state, sync, picker } = _globalCalActivePicker;
            const selectedDate = parseCustomDateValue(day.dataset.dateValue || '');
            if (!selectedDate) return;
            state.selected = selectedDate;
            state.view = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
            input.value = formatCustomDateValue(selectedDate);
            picker.classList.remove('date-required-error');
            sync();
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.dispatchEvent(new Event('input', { bubbles: true }));
            closeGlobalCalPanel();
        }
    });
    _globalCalPanel.addEventListener('change', e => {
        const yearSelect = e.target.closest('[data-date-year]');
        if (!yearSelect || !_globalCalState) return;
        const selectedYear = Number(yearSelect.value || 0);
        if (!selectedYear) return;
        _globalCalState.view = new Date(selectedYear, _globalCalState.view.getMonth(), 1);
        _globalCalPanel.innerHTML = buildCustomDatePanel(_globalCalState);
    });
    return _globalCalPanel;
}

function positionGlobalCalPanel(trigger) {
    const panel = getGlobalCalPanel();
    const rect = trigger.getBoundingClientRect();
    const panelW = 308;
    const panelH = 360;
    const vpW = window.innerWidth;
    const vpH = window.innerHeight;
    let left = rect.left;
    if (left + panelW > vpW - 8) left = vpW - panelW - 8;
    if (left < 8) left = 8;
    let top;
    if (rect.bottom + 6 + panelH <= vpH - 8) {
        top = rect.bottom + 6;
    } else {
        top = rect.top - 6 - panelH;
        if (top < 8) top = 8;
    }
    panel.style.top = top + 'px';
    panel.style.left = left + 'px';
}

function openGlobalCalPanel(pickerCtx) {
    _globalCalActivePicker = pickerCtx;
    _globalCalState = pickerCtx.state;
    const panel = getGlobalCalPanel();
    panel.innerHTML = buildCustomDatePanel(_globalCalState);
    positionGlobalCalPanel(pickerCtx.trigger);
    panel.hidden = false;
    requestAnimationFrame(() => panel.classList.add('is-open'));
}

function closeGlobalCalPanel() {
    if (!_globalCalPanel) return;
    _globalCalPanel.classList.remove('is-open');
    _globalCalPanel.hidden = true;
    if (_globalCalActivePicker) {
        _globalCalActivePicker.picker.classList.remove('is-open');
        _globalCalActivePicker.picker.querySelector('.filter-date-trigger')?.setAttribute('aria-expanded', 'false');
        _globalCalActivePicker = null;
    }
    _globalCalState = null;
}

/* -- Global DateTime Picker (calendar + time) -- */
let _globalDtPanel = null;
let _globalDtActivePicker = null;
let _globalDtState = null;

function getGlobalDtPanel() {
    if (_globalDtPanel) return _globalDtPanel;
    _globalDtPanel = document.createElement('div');
    _globalDtPanel.className = 'global-datetime-panel';
    _globalDtPanel.hidden = true;
    document.body.appendChild(_globalDtPanel);

    _globalDtPanel.addEventListener('mousedown', e => e.stopPropagation());
    _globalDtPanel.addEventListener('click', e => {
        e.stopPropagation();
        if (!_globalDtState || !_globalDtActivePicker) return;
        const { input, state, sync, picker } = _globalDtActivePicker;

        const nav = e.target.closest('[data-date-nav]');
        if (nav) {
            state.view = new Date(state.view.getFullYear(), state.view.getMonth() + Number(nav.dataset.dateNav || 0), 1);
            renderDtPanel(); return;
        }
        const action = e.target.closest('[data-date-action]');
        if (action) {
            if (action.dataset.dateAction === 'clear') {
                state.selected = null; state.hour = 9; state.minute = 0; state.period = 'AM';
                input.value = ''; sync();
            }
            if (action.dataset.dateAction === 'today') {
                const today = stripTime(new Date());
                state.selected = today; state.view = new Date(today.getFullYear(), today.getMonth(), 1);
            }
            renderDtPanel(); return;
        }
        const day = e.target.closest('[data-date-value]');
        if (day) {
            const d = parseCustomDateValue(day.dataset.dateValue || '');
            if (!d) return;
            state.selected = d;
            state.view = new Date(d.getFullYear(), d.getMonth(), 1);
            picker.classList.remove('date-required-error');
            commitDtValue(); renderDtPanel(); return;
        }
        const hour = e.target.closest('[data-dt-hour]');
        if (hour) { state.hour = Number(hour.dataset.dtHour); commitDtValue(); renderDtPanel(); scrollDtCol(hour); return; }
        const minute = e.target.closest('[data-dt-minute]');
        if (minute) { state.minute = Number(minute.dataset.dtMinute); commitDtValue(); renderDtPanel(); scrollDtCol(minute); return; }
        const period = e.target.closest('[data-dt-period]');
        if (period) { state.period = period.dataset.dtPeriod; commitDtValue(); renderDtPanel(); return; }
        const confirm = e.target.closest('[data-dt-confirm]');
        if (confirm) { closeGlobalDtPanel(); return; }
    });
    _globalDtPanel.addEventListener('change', e => {
        const yearSelect = e.target.closest('[data-date-year]');
        if (!yearSelect || !_globalDtState) return;
        _globalDtState.view = new Date(Number(yearSelect.value), _globalDtState.view.getMonth(), 1);
        renderDtPanel();
    });
    return _globalDtPanel;
}

function scrollDtCol(btn) {
    const col = btn.closest('.datetime-time-col');
    if (col) btn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

function commitDtValue() {
    if (!_globalDtActivePicker || !_globalDtState) return;
    const { input, sync, state } = _globalDtActivePicker;
    if (!state.selected) return;
    let h24 = state.hour % 12;
    if (state.period === 'PM') h24 += 12;
    if (state.period === 'AM' && state.hour === 12) h24 = 0;
    const ds = formatCustomDateValue(state.selected);
    const ts = String(h24).padStart(2, '0') + ':' + String(state.minute).padStart(2, '0');
    input.value = ds + 'T' + ts;
    sync();
}

function renderDtPanel() {
    if (!_globalDtPanel || !_globalDtState) return;
    _globalDtPanel.innerHTML = buildDateTimePanel(_globalDtState);
    const panel = _globalDtPanel;
    requestAnimationFrame(() => {
        panel.querySelectorAll('.datetime-time-col').forEach(col => {
            const sel = col.querySelector('.is-selected');
            if (sel) sel.scrollIntoView({ block: 'center', behavior: 'instant' });
        });
    });
}

function buildDateTimePanel(state) {
    const calendarHtml = buildCustomDatePanel(state);
    const hours = [];
    for (let h = 1; h <= 12; h++) {
        hours.push(`<button class="datetime-time-item${state.hour === h ? ' is-selected' : ''}" type="button" data-dt-hour="${h}">${h}</button>`);
    }
    const minutes = [];
    for (let m = 0; m < 60; m += 5) {
        minutes.push(`<button class="datetime-time-item${state.minute === m ? ' is-selected' : ''}" type="button" data-dt-minute="${m}">${String(m).padStart(2, '0')}</button>`);
    }
    const periods = ['AM', 'PM'].map(p =>
        `<button class="datetime-time-item${state.period === p ? ' is-selected' : ''}" type="button" data-dt-period="${p}">${p}</button>`
    ).join('');

    const previewTime = state.selected
        ? `${state.hour}:${String(state.minute).padStart(2, '0')} ${state.period}`
        : '--:--';

    return `<div class="datetime-panel-layout">${calendarHtml}<div class="datetime-time-picker"><div class="datetime-time-header">Time  ?  ${previewTime}</div><div class="datetime-time-cols"><div class="datetime-time-col">${hours.join('')}</div><div class="datetime-time-col">${minutes.join('')}</div><div class="datetime-time-period-col">${periods}</div></div><div class="datetime-confirm-row"><button class="btn btn-small" type="button" data-dt-confirm>Done</button></div></div></div>`;
}

function positionGlobalDtPanel(trigger) {
    const panel = getGlobalDtPanel();
    const rect = trigger.getBoundingClientRect();
    const isMobile = window.innerWidth <= 520;
    const panelW = isMobile ? Math.min(308, window.innerWidth - 16) : 470;
    const panelH = isMobile ? 520 : 400;
    const vpW = window.innerWidth;
    const vpH = window.innerHeight;
    let left = rect.left;
    if (left + panelW > vpW - 8) left = vpW - panelW - 8;
    if (left < 8) left = 8;
    let top = rect.bottom + 6;
    if (top + panelH > vpH - 8) top = rect.top - 6 - panelH;
    if (top < 8) top = 8;
    panel.style.top = top + 'px';
    panel.style.left = left + 'px';
}

function openGlobalDtPanel(pickerCtx) {
    _globalDtActivePicker = pickerCtx;
    _globalDtState = pickerCtx.state;
    const panel = getGlobalDtPanel();
    renderDtPanel();
    positionGlobalDtPanel(pickerCtx.trigger);
    panel.hidden = false;
    requestAnimationFrame(() => panel.classList.add('is-open'));
}

function closeGlobalDtPanel() {
    if (!_globalDtPanel) return;
    _globalDtPanel.classList.remove('is-open');
    _globalDtPanel.hidden = true;
    if (_globalDtActivePicker) {
        _globalDtActivePicker.picker.classList.remove('is-open');
        _globalDtActivePicker.picker.querySelector('.filter-date-trigger')?.setAttribute('aria-expanded', 'false');
        _globalDtActivePicker = null;
    }
    _globalDtState = null;
}

function initDateTimePickers() {
    document.querySelectorAll('.form-datetime-picker').forEach(picker => {
        if (picker.dataset.enhanced === '1') return;
        picker.dataset.enhanced = '1';

        const input = picker.querySelector('input[type="hidden"]');
        const trigger = picker.querySelector('.filter-date-trigger');
        const value = picker.querySelector('.filter-date-value');
        if (!input || !trigger || !value) return;

        let initialDate = null;
        let initialHour = 9, initialMinute = 0, initialPeriod = 'AM';
        if (input.value) {
            const parts = input.value.split('T');
            initialDate = parseCustomDateValue(parts[0]);
            if (parts[1]) {
                const [h, m] = parts[1].split(':').map(Number);
                initialPeriod = h >= 12 ? 'PM' : 'AM';
                initialHour = h % 12 || 12;
                initialMinute = Math.round(m / 5) * 5;
                if (initialMinute >= 60) initialMinute = 55;
            }
        }

        const state = {
            selected: initialDate,
            view: initialDate ? new Date(initialDate.getFullYear(), initialDate.getMonth(), 1) : new Date(new Date().getFullYear(), new Date().getMonth(), 1),
            max: null,
            hour: initialHour,
            minute: initialMinute,
            period: initialPeriod,
        };

        const formatDisplay = () => {
            if (!state.selected) return 'Select date & time';
            const d = formatCustomDateDisplay(state.selected);
            return `${d} ${state.hour}:${String(state.minute).padStart(2, '0')} ${state.period}`;
        };

        const sync = () => {
            value.textContent = formatDisplay();
            picker.classList.toggle('is-placeholder', !state.selected);
        };

        trigger.addEventListener('click', e => {
            e.stopPropagation();
            const isOpen = picker.classList.contains('is-open');
            closeCustomSelects();
            closeGlobalCalPanel();
            closeGlobalDtPanel();
            if (!isOpen) {
                picker.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                openGlobalDtPanel({ picker, trigger, input, state, sync });
            }
        });

        sync();
    });
}

function initCustomDatePickers() {
    document.querySelectorAll('.filter-date-picker').forEach(picker => {
        if (picker.dataset.enhanced === '1') return;
        if (picker.dataset.dateReadonly === '1') {
            picker.dataset.enhanced = '1';
            picker.classList.add('is-readonly');
            return;
        }
        picker.dataset.enhanced = '1';

        const input = picker.querySelector('input[type="hidden"]');
        const trigger = picker.querySelector('.filter-date-trigger');
        const value = picker.querySelector('.filter-date-value');
        const isFormPicker = picker.classList.contains('form-date-picker');

        // For non-form pickers keep the inline panel approach
        const panel = !isFormPicker ? picker.querySelector('.filter-date-panel') : null;
        if (!input || !trigger || !value) return;
        if (!isFormPicker && !panel) return;

        const initialDate = parseCustomDateValue(input.value);
        const maxDate = parseCustomDateValue(picker.dataset.dateMax || '');
        const minDate = parseCustomDateValue(picker.dataset.dateMin || '');
        const state = {
            selected: initialDate,
            view: initialDate ? new Date(initialDate.getFullYear(), initialDate.getMonth(), 1) : (minDate ? new Date(minDate.getFullYear(), minDate.getMonth(), 1) : (maxDate ? new Date(maxDate.getFullYear(), maxDate.getMonth(), 1) : new Date(new Date().getFullYear(), new Date().getMonth(), 1))),
            max: maxDate || null,
            min: minDate || null,
        };

        const sync = () => {
            value.textContent = state.selected ? formatCustomDateDisplay(state.selected) : 'mm/dd/yyyy';
            picker.classList.toggle('is-placeholder', !state.selected);
        };

        if (isFormPicker) {
            // Remove the inline placeholder panel - all rendering is via global panel
            picker.querySelector('.filter-date-panel')?.remove();

            trigger.addEventListener('click', event => {
                event.stopPropagation();
                const isOpen = picker.classList.contains('is-open');
                closeCustomSelects();
                closeGlobalCalPanel();
                if (!isOpen) {
                    state.max = parseCustomDateValue(picker.dataset.dateMax || '') || null;
                    state.min = parseCustomDateValue(picker.dataset.dateMin || '') || null;
                    picker.classList.add('is-open');
                    trigger.setAttribute('aria-expanded', 'true');
                    openGlobalCalPanel({ picker, trigger, input, state, sync });
                }
            });
        } else {
            // Original inline panel logic for filter date pickers (email logs)
            const render = () => { panel.innerHTML = buildCustomDatePanel(state); };

            trigger.addEventListener('click', event => {
                event.stopPropagation();
                const opening = !picker.classList.contains('is-open');
                closeCustomSelects();
                closeCustomDatePickers(opening ? picker : null);
                picker.classList.toggle('is-open', opening);
                panel.hidden = !opening;
                trigger.setAttribute('aria-expanded', String(opening));
                if (opening) render();
            });

            panel.addEventListener('click', event => {
                const nav = event.target.closest('[data-date-nav]');
                if (nav) {
                    const delta = Number(nav.dataset.dateNav || 0);
                    state.view = new Date(state.view.getFullYear(), state.view.getMonth() + delta, 1);
                    render(); return;
                }
                const action = event.target.closest('[data-date-action]');
                if (action) {
                    if (action.dataset.dateAction === 'clear') { state.selected = null; input.value = ''; sync(); closeCustomDatePickers(); input.dispatchEvent(new Event('change', { bubbles: true })); input.dispatchEvent(new Event('input', { bubbles: true })); }
                    if (action.dataset.dateAction === 'today') {
                        const today = stripTime(new Date());
                        state.selected = today; state.view = new Date(today.getFullYear(), today.getMonth(), 1);
                        input.value = formatCustomDateValue(today); sync(); closeCustomDatePickers();
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    return;
                }
                const day = event.target.closest('[data-date-value]');
                if (!day) return;
                const selectedDate = parseCustomDateValue(day.dataset.dateValue || '');
                if (!selectedDate) return;
                state.selected = selectedDate;
                state.view = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
                input.value = formatCustomDateValue(selectedDate);
                picker.classList.remove('date-required-error');
                sync(); closeCustomDatePickers();
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });

            panel.addEventListener('change', event => {
                const yearSelect = event.target.closest('[data-date-year]');
                if (!yearSelect) return;
                const selectedYear = Number(yearSelect.value || 0);
                if (!selectedYear) return;
                state.view = new Date(selectedYear, state.view.getMonth(), 1);
                render();
            });

            render();
        }

        sync();
    });
}

function buildCustomDatePanel(state) {
    const today = stripTime(new Date());
    const monthLabel = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(state.view);
    const weekDays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
    const firstDay = new Date(state.view.getFullYear(), state.view.getMonth(), 1);
    const start = new Date(firstDay);
    start.setDate(firstDay.getDate() - firstDay.getDay());
    const currentYear = today.getFullYear();
    const maxYear = state.max ? state.max.getFullYear() : currentYear + 10;
    const minYearBase = state.min ? state.min.getFullYear() : maxYear - 100;
    const minYear = Math.min(minYearBase, state.selected?.getFullYear() ?? minYearBase, state.view.getFullYear());
    const years = [];
    for (let year = maxYear; year >= minYear; year -= 1) {
        years.push(`<option value="${year}" ${year === state.view.getFullYear() ? 'selected' : ''}>${year}</option>`);
    }

    const cells = [];
    for (let index = 0; index < 42; index += 1) {
        const date = new Date(start);
        date.setDate(start.getDate() + index);

        const classes = ['filter-date-day'];
        if (date.getMonth() !== state.view.getMonth()) classes.push('is-outside');
        if (isSameCustomDate(date, today)) classes.push('is-today');
        if (state.selected && isSameCustomDate(date, state.selected)) classes.push('is-selected');
        const isDisabled = (state.max && date > state.max) || (state.min && date < state.min);
        if (isDisabled) classes.push('is-disabled');

        cells.push(`
            <button
                class="${classes.join(' ')}"
                type="button"
                ${isDisabled ? 'disabled aria-disabled="true"' : `data-date-value="${formatCustomDateValue(date)}"`}
                aria-pressed="${state.selected && isSameCustomDate(date, state.selected) ? 'true' : 'false'}"
            >${date.getDate()}</button>
        `);
    }

    return `
        <div class="filter-date-calendar" role="dialog" aria-label="Calendar picker">
            <div class="filter-date-calendar-header">
                <button class="filter-date-nav" type="button" data-date-nav="-1" aria-label="Previous month"></button>
                <div class="filter-date-title-wrap">
                    <div class="filter-date-title">${monthLabel}</div>
                    <label class="filter-date-year-wrap" aria-label="Select year">
                        <select class="filter-date-year-select" data-date-year>
                            ${years.join('')}
                        </select>
                    </label>
                </div>
                <button class="filter-date-nav" type="button" data-date-nav="1" aria-label="Next month"></button>
            </div>
            <div class="filter-date-weekdays">${weekDays.map(day => `<span>${day}</span>`).join('')}</div>
            <div class="filter-date-grid">${cells.join('')}</div>
            <div class="filter-date-actions">
                <button class="filter-date-action" type="button" data-date-action="clear">Clear</button>
                ${(!state.max || today <= state.max) && (!state.min || today >= state.min) ? `<button class="filter-date-action" type="button" data-date-action="today">Today</button>` : ''}
            </div>
        </div>
    `;
}

function parseCustomDateValue(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(String(value || ''))) return null;
    const [year, month, day] = value.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return null;
    return date;
}

function formatCustomDateValue(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function formatCustomDateDisplay(date) {
    return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}/${date.getFullYear()}`;
}

function setFormDatePickerValue(picker, isoDate) {
    if (!picker) return;
    const input = picker.querySelector('input[type="hidden"]');
    const valueEl = picker.querySelector('.filter-date-value');
    if (!input || !valueEl) return;
    if (!isoDate) {
        input.value = '';
        valueEl.textContent = picker.dataset.emptyLabel || 'mm/dd/yyyy';
        picker.classList.add('is-placeholder');
        picker.classList.remove('date-required-error');
        return;
    }
    const date = parseCustomDateValue(isoDate);
    if (!date) return;
    input.value = formatCustomDateValue(date);
    valueEl.textContent = formatCustomDateDisplay(date);
    picker.classList.remove('is-placeholder', 'date-required-error');
    input.dispatchEvent(new Event('change', { bubbles: true }));
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

function syncAcademicTermDates(form) {
    const termSelect = form?.querySelector('[name="academic_term"][data-term-autofill]');
    if (!termSelect) return;
    const selected = termSelect.selectedOptions[0];
    const startPicker = form.querySelector('[data-term-start-picker]');
    const endPicker = form.querySelector('[data-term-end-picker]');
    const start = selected?.dataset.termStart || '';
    const end = selected?.dataset.termEnd || '';
    if (!selected?.value || !start || !end) {
        setFormDatePickerValue(startPicker, '');
        setFormDatePickerValue(endPicker, '');
        if (startPicker) {
            startPicker.querySelector('.filter-date-value').textContent = 'Select academic term first';
            startPicker.classList.add('is-placeholder');
        }
        if (endPicker) {
            endPicker.querySelector('.filter-date-value').textContent = 'Select academic term first';
            endPicker.classList.add('is-placeholder');
        }
        return;
    }
    setFormDatePickerValue(startPicker, start);
    setFormDatePickerValue(endPicker, end);
}

function stripTime(date) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function isSameCustomDate(first, second) {
    return first.getFullYear() === second.getFullYear()
        && first.getMonth() === second.getMonth()
        && first.getDate() === second.getDate();
}

function initCustomFilterSelects() {
    document.querySelectorAll('select').forEach((select, index) => {
        if (select.dataset.enhanced === '1' || select.classList.contains('filter-date-year-select') || select.multiple || select.dataset.nativeSelect === '1') return;

        const wrap = select.closest('.filter-select-wrap') || select.parentElement;
        if (!wrap) return;

        select.dataset.enhanced = '1';
        wrap.classList.add('is-enhanced', 'select-enhanced-wrap');

        const fieldLabel = wrap.closest('.filter-control')?.querySelector('.filter-label')?.textContent?.trim()
            || select.dataset.selectLabel
            || wrap.querySelector('span')?.textContent?.replace('*', '').trim()
            || select.getAttribute('aria-label')
            || 'Select';
        const custom = document.createElement('div');
        custom.className = 'custom-select';
        if (wrap.classList.contains('register-input-wrap--select')) {
            custom.classList.add('register-course-custom-select');
        }

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'custom-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-label', fieldLabel);
        trigger.setAttribute('aria-controls', `custom-select-menu-${index}`);

        const copy = document.createElement('span');
        copy.className = 'custom-select-copy';

        const value = document.createElement('span');
        value.className = 'custom-select-value';

        copy.append(value);

        const caret = document.createElement('span');
        caret.className = 'custom-select-caret';
        caret.setAttribute('aria-hidden', 'true');

        const menu = document.createElement('div');
        menu.className = 'custom-select-menu';
        menu.id = `custom-select-menu-${index}`;
        menu.setAttribute('role', 'listbox');
        menu.setAttribute('aria-label', fieldLabel);

        trigger.append(copy, caret);
        custom.append(trigger, menu);
        wrap.appendChild(custom);

        let optionSignature = '';

        const renderOptions = () => {
            const nextSignature = [...select.options].map(option => [option.value, option.textContent, option.hidden, option.disabled].join('|')).join('::');
            if (nextSignature === optionSignature && menu.children.length) return;
            optionSignature = nextSignature;
            menu.innerHTML = '';
            [...select.options].forEach((option, optionIndex) => {
                if (option.hidden) return;
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'custom-select-option';
                item.setAttribute('role', 'option');
                item.dataset.value = option.value;
                item.dataset.index = String(optionIndex);
                item.disabled = option.disabled;
                item.innerHTML = `
                    <span class="custom-select-option-dot" aria-hidden="true"></span>
                    <span class="custom-select-option-label">${escapeHtml(option.textContent.trim())}</span>
                `;
                item.addEventListener('click', () => {
                    if (option.disabled) return;
                    select.selectedIndex = optionIndex;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    closeCustomSelects();
                    trigger.focus();
                });
                item.addEventListener('keydown', event => handleCustomSelectOptionKeys(event, custom));
                menu.appendChild(item);
            });
        };

        const syncState = () => {
            renderOptions();
            const selectedOption = select.selectedOptions[0] || select.options[0];
            const hasValue = !!(selectedOption?.value || '').trim();
            value.textContent = selectedOption?.textContent?.trim() || fieldLabel;
            custom.classList.toggle('is-placeholder', !hasValue);
            custom.classList.toggle('is-disabled', select.disabled);
            trigger.disabled = select.disabled;
            [...menu.querySelectorAll('.custom-select-option')].forEach(item => {
                const selected = item.dataset.index === String(selectedOption?.index ?? select.selectedIndex);
                item.classList.toggle('is-selected', selected);
                item.setAttribute('aria-selected', String(selected));
                item.tabIndex = selected && !item.disabled ? 0 : -1;
            });
        };

        const setOpen = open => {
            custom.classList.toggle('is-open', open);
            trigger.setAttribute('aria-expanded', String(open));
        };

        trigger.addEventListener('click', event => {
            event.stopPropagation();
            syncState();
            const opening = !custom.classList.contains('is-open');
            closeCustomSelects(opening ? custom : null);
            setOpen(opening);
            if (opening) focusCustomSelectOption(custom);
        });

        trigger.addEventListener('keydown', event => {
            if (!['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) return;
            event.preventDefault();
            if (!custom.classList.contains('is-open')) {
                closeCustomSelects(custom);
                setOpen(true);
            }
            focusCustomSelectOption(custom, event.key === 'ArrowUp' ? 'last' : 'selected');
        });

        select.addEventListener('change', syncState);
        select._syncCustomSelect = syncState;
        syncState();
    });
}

function focusCustomSelectOption(custom, mode = 'selected') {
    const items = [...custom.querySelectorAll('.custom-select-option')];
    if (!items.length) return;
    const target = mode === 'last'
        ? items[items.length - 1]
        : items.find(item => item.classList.contains('is-selected')) || items[0];
    target.focus();
}

function handleCustomSelectOptionKeys(event, custom) {
    const items = [...custom.querySelectorAll('.custom-select-option')];
    const index = items.indexOf(event.currentTarget);
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        items[(index + 1) % items.length]?.focus();
    }
    if (event.key === 'ArrowUp') {
        event.preventDefault();
        items[(index - 1 + items.length) % items.length]?.focus();
    }
    if (event.key === 'Escape') {
        event.preventDefault();
        closeCustomSelects();
        custom.querySelector('.custom-select-trigger')?.focus();
    }
}

function closeCustomSelects(except = null) {
    document.querySelectorAll('.custom-select.is-open').forEach(custom => {
        if (except && custom === except) return;
        custom.classList.remove('is-open');
        custom.querySelector('.custom-select-trigger')?.setAttribute('aria-expanded', 'false');
    });
    // Portaled menus usually sync via MutationObserver; force-hide when everything closes
    if (!except) {
        document.querySelectorAll('.enr-wizard-select-menu.is-open').forEach(menu => {
            menu.classList.remove('is-open');
            menu.hidden = true;
        });
    }
}

function closeCustomDatePickers(except = null) {
    if (!except) closeGlobalCalPanel();
    document.querySelectorAll('.filter-date-picker.is-open').forEach(picker => {
        if (except && picker === except) return;
        picker.classList.remove('is-open');
        const floatingPanel = picker._floatingPanel;
        if (floatingPanel) {
            floatingPanel.hidden = true;
            floatingPanel.classList.remove('is-open');
        } else {
            picker.querySelector('.filter-date-panel')?.setAttribute('hidden', 'hidden');
        }
        picker.querySelector('.filter-date-trigger')?.setAttribute('aria-expanded', 'false');
        if (picker._parentCard) { picker._parentCard.classList.remove('picker-open'); picker._parentCard = null; }
    });
}

function initNotifications() {
    const menu  = document.getElementById('notifMenu');
    const btn   = document.getElementById('notifBtn');
    const panel = document.getElementById('notifPanel');
    if (!menu || !btn || !panel) return;

    function positionPanel() {
        const rect = btn.getBoundingClientRect();
        const panelWidth = Math.min(370, window.innerWidth - 32);
        const left = Math.max(16, Math.min(rect.right - panelWidth, window.innerWidth - panelWidth - 16));
        panel.style.top = (rect.bottom + 12) + 'px';
        panel.style.left = left + 'px';
        panel.style.width = panelWidth + 'px';
        panel.style.setProperty('--caret-left', Math.round(rect.left + (rect.width / 2) - left - 7) + 'px');
    }

    btn.addEventListener('click', e => {
        e.stopPropagation();
        const opening = !panel.classList.contains('is-open');
        if (opening) {
            panel.hidden = false;
            positionPanel();
        }
        panel.classList.toggle('is-open', opening);
        menu.classList.toggle('is-open', opening);   // for button active styling
        btn.setAttribute('aria-expanded', String(opening));
        if (!opening) panel.hidden = true;
    });

    window.addEventListener('resize', () => {
        if (panel.classList.contains('is-open')) positionPanel();
    });
}

function closeNotifications() {
    const panel = document.getElementById('notifPanel');
    const menu  = document.getElementById('notifMenu');
    const btn   = document.getElementById('notifBtn');
    if (!panel) return;
    panel.classList.remove('is-open');
    panel.hidden = true;
    menu?.classList.remove('is-open');
    btn?.setAttribute('aria-expanded', 'false');
}

function getTextMarqueeElements() {
    const selectors = [
        '[data-marquee]',
    ];
    const nodes = new Set();
    selectors.forEach(selector => {
        document.querySelectorAll(selector).forEach(el => nodes.add(el));
    });
    return [...nodes];
}

function refreshTextMarquees() {
    getTextMarqueeElements().forEach(refreshTextMarquee);
}

function refreshTextMarquee(el) {
    if (!el) return;
    const track = el.querySelector('.text-marquee__track');
    const primary = track?.querySelector('.text-marquee__text:not(.text-marquee__clone)');
    if (!track || !primary) return;

    const overflowing = el.clientWidth > 0 && primary.scrollWidth > el.clientWidth + 1;
    el.classList.toggle('is-active', overflowing);
    if (overflowing) {
        const seconds = Math.max(5, Math.min(18, primary.scrollWidth / 35));
        el.style.setProperty('--marquee-duration', `${seconds}s`);
    } else {
        el.style.removeProperty('--marquee-duration');
    }
}

function isSidebarLabelMarquee(el) {
    if (!el?.closest('.sidebar')) return false;
    return !!el.closest('.brand > div');
}

function setupTextMarquee(el) {
    if (!el) return;
    if (el.dataset.marqueeReady === '1') {
        refreshTextMarquee(el);
        return;
    }

    if (document.body.classList.contains('sidebar-collapsed') && isSidebarLabelMarquee(el)) {
        return;
    }

    const text = el.textContent.trim();
    if (!text || el.clientWidth === 0 || getComputedStyle(el).display === 'none') return;

    el.classList.add('text-marquee');
    el.setAttribute('title', text);

    const track = document.createElement('span');
    track.className = 'text-marquee__track';

    const primary = document.createElement('span');
    primary.className = 'text-marquee__text';
    primary.textContent = text;

    const clone = document.createElement('span');
    clone.className = 'text-marquee__text text-marquee__clone';
    clone.setAttribute('aria-hidden', 'true');
    clone.textContent = text;

    track.append(primary, clone);
    el.textContent = '';
    el.append(track);
    el.dataset.marqueeReady = '1';

    refreshTextMarquee(el);
    new ResizeObserver(() => refreshTextMarquee(el)).observe(el);
}

function isDesktopSidebarMode() {
    return window.matchMedia('(min-width: 721px)').matches;
}

function readSidebarCollapsedPreference() {
    try {
        if (localStorage.getItem('sidebarCollapsed') === '1') return true;
    } catch (e) {}
    if (document.body?.dataset.sidebarCollapsed === '1') return true;
    if (document.cookie.split(';').some(part => part.trim() === 'sidebarCollapsed=1')) return true;
    return document.body?.classList.contains('sidebar-collapsed') === true;
}

function persistSidebarCollapsed(collapsed) {
    const value = collapsed ? '1' : '0';
    try { localStorage.setItem('sidebarCollapsed', value); } catch (e) {}
    document.cookie = `sidebarCollapsed=${value}; path=/; max-age=31536000; SameSite=Lax`;
    if (document.body) document.body.dataset.sidebarCollapsed = value;
}

function syncSidebarCollapseForViewport() {
    const wantsCollapsed = readSidebarCollapsedPreference();
    const desktop = isDesktopSidebarMode();
    document.documentElement.classList.remove('is-sidebar-collapsed-init');
    if (desktop && wantsCollapsed) {
        document.body.classList.add('sidebar-collapsed');
        persistSidebarCollapsed(true);
    } else if (!wantsCollapsed) {
        document.body.classList.remove('sidebar-collapsed');
        persistSidebarCollapsed(false);
    } else {
        document.body.classList.remove('sidebar-collapsed');
    }
}

function initSidebarMarqueesIfNeeded() {
    if (document.body.classList.contains('sidebar-collapsed')) return;
    getTextMarqueeElements().forEach(el => {
        if (isSidebarLabelMarquee(el)) setupTextMarquee(el);
    });
    refreshTextMarquees();
}

function initTextMarquees() {
    requestAnimationFrame(() => {
        getTextMarqueeElements().forEach(setupTextMarquee);
    });

    let resizeTimer;
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(refreshTextMarquees, 150);
    });
}

function initStudentNavReveal() {
    if (!document.body.classList.contains('student-nav-reveal')) return;

    window.setTimeout(() => {
        document.body.classList.remove('student-nav-reveal');
        document.querySelector('.sidebar')?.classList.remove('sidebar--nav-reveal');
        refreshTextMarquees();
    }, 1300);
}

function initSidebar() {
    if (document.body.classList.contains('student-onboarding')) {
        document.body.classList.remove('sidebar-collapsed');
        document.documentElement.classList.remove('is-sidebar-collapsed-init');
    }
    syncSidebarCollapseForViewport();
    document.querySelector('.sidebar-toggle')?.addEventListener('click', () => {
        if (!isDesktopSidebarMode()) return;
        const willCollapse = !document.body.classList.contains('sidebar-collapsed');
        document.body.classList.toggle('sidebar-collapsed');
        persistSidebarCollapsed(document.body.classList.contains('sidebar-collapsed'));
        if (!willCollapse) {
            initSidebarMarqueesIfNeeded();
        }
        window.setTimeout(refreshTextMarquees, 300);
    });
    window.addEventListener('resize', () => {
        syncSidebarCollapseForViewport();
        if (!document.body.classList.contains('sidebar-collapsed')) {
            initSidebarMarqueesIfNeeded();
        }
        window.setTimeout(refreshTextMarquees, 150);
    });
    // Collapsible nav groups
    document.querySelectorAll('.nav-group-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const group = btn.closest('.nav-group');
            if (!group) return;
            if (group.classList.contains('nav-group--student-docs') && window.matchMedia('(max-width: 720px)').matches) {
                window.dispatchEvent(new CustomEvent('student-nav-docs-toggle', { detail: { group, open: !group.classList.contains('open') } }));
                return;
            }
            group.classList.toggle('open');
            btn.setAttribute('aria-expanded', group.classList.contains('open') ? 'true' : 'false');
        });
    });
}

function scrollHorizontalContainerToChild(container, child, options = {}) {
    if (!container || !child) return;

    const { align = 'center', behavior = 'auto' } = options;
    const maxScroll = Math.max(0, container.scrollWidth - container.clientWidth);
    if (maxScroll <= 0) return;

    const containerRect = container.getBoundingClientRect();
    const childRect = child.getBoundingClientRect();
    const relativeLeft = childRect.left - containerRect.left + container.scrollLeft;
    let target = align === 'start'
        ? relativeLeft
        : relativeLeft - ((container.clientWidth - childRect.width) / 2);
    target = Math.max(0, Math.min(maxScroll, target));

    if (typeof container.scrollTo === 'function') {
        container.scrollTo({ left: target, behavior });
    } else {
        container.scrollLeft = target;
    }
}

function initStudentDashboardJourney() {
    if (!document.body.classList.contains('role-student')) return;

    const journey = document.querySelector('.student-dash-v3 .sd3-journey');
    if (!journey || journey.dataset.journeyInit === '1') return;
    journey.dataset.journeyInit = '1';

    const current = journey.querySelector('.sd3-journey-step.is-current');
    if (!current) return;

    const centerCurrent = () => {
        if (!window.matchMedia('(max-width: 720px)').matches) return;
        scrollHorizontalContainerToChild(journey, current, {
            align: 'center',
            behavior: journey.dataset.journeyScrolled === '1' ? 'auto' : 'smooth',
        });
        journey.dataset.journeyScrolled = '1';
    };

    requestAnimationFrame(() => requestAnimationFrame(centerCurrent));

    let lastWidth = window.innerWidth;
    window.addEventListener('resize', () => {
        const nextWidth = window.innerWidth;
        // Ignore Safari toolbar height changes — only re-center on real width/orientation changes.
        if (Math.abs(nextWidth - lastWidth) < 8) return;
        lastWidth = nextWidth;
        window.clearTimeout(journey._centerTimer);
        journey._centerTimer = window.setTimeout(centerCurrent, 120);
    }, { passive: true });
}

function initStudentDocsStepperScroll() {
    if (!document.body.classList.contains('role-student')) return;
    const stepper = document.querySelector('.docs-stepper');
    if (!stepper) return;
    const current = stepper.querySelector('.docs-step.is-active, .docs-step.is-current, .docs-step[aria-current="page"]');
    if (!current) return;
    if (window.matchMedia('(max-width: 760px)').matches) {
        requestAnimationFrame(() => {
            scrollHorizontalContainerToChild(stepper, current, { align: 'start', behavior: 'smooth' });
        });
    }
}

function isStudentMobileNav() {
    return document.body.classList.contains('role-student')
        && window.matchMedia('(max-width: 720px)').matches;
}

function syncStudentDocsGroupExpanded() {
    const docGroup = document.querySelector('.nav-group--student-docs');
    if (!docGroup) return;

    if (isStudentMobileNav()) {
        docGroup.classList.remove('open');
        return;
    }

    docGroup.classList.toggle('open', docGroup.classList.contains('nav-group--active'));
}

function initStudentMobileNav() {
    if (!document.body.classList.contains('role-student')) return;

    const sidebar = document.querySelector('.sidebar');
    const docsGroup = document.querySelector('.nav-group--student-docs');
    const mq = window.matchMedia('(max-width: 720px)');
    const toggle = docsGroup?.querySelector('.nav-group-toggle');
    let backdrop = document.querySelector('.student-nav-sheet-backdrop');
    let navRoot = document.getElementById('student-bottom-nav-root');
    let sidebarAnchor = sidebar ? { parent: sidebar.parentElement, next: sidebar.nextSibling } : null;

    const ensureNavRoot = () => {
        if (navRoot) return navRoot;
        navRoot = document.createElement('div');
        navRoot.id = 'student-bottom-nav-root';
        navRoot.className = 'student-bottom-nav-root';
        document.body.appendChild(navRoot);
        return navRoot;
    };

    const mountBottomNav = () => {
        if (!sidebar || !mq.matches) return;
        const root = ensureNavRoot();
        if (sidebar.parentElement !== root) {
            sidebarAnchor = { parent: sidebar.parentElement, next: sidebar.nextSibling };
            root.appendChild(sidebar);
        }
        syncNavRootPosition();
        attachNavStripListener();
    };

    const unmountBottomNav = () => {
        if (!sidebar || mq.matches) return;
        if (sidebarAnchor?.parent && sidebar.parentElement !== sidebarAnchor.parent) {
            sidebarAnchor.parent.insertBefore(sidebar, sidebarAnchor.next);
        }
        navRoot?.remove();
        navRoot = null;
        syncNavRootPosition();
    };

    const syncBottomNavMount = () => {
        if (mq.matches) mountBottomNav();
        else unmountBottomNav();
    };

    const syncNavRootPosition = () => {
        if (!mq.matches) return;

        const root = document.getElementById('student-bottom-nav-root');
        if (!root) return;

        root.style.left = '0';
        root.style.width = '100%';
        root.style.maxWidth = '100%';
        root.style.bottom = '0';
        root.style.transform = 'none';
    };

    const clearDocsSheetPosition = (items) => {
        if (!items) return;
        items.style.removeProperty('left');
        items.style.removeProperty('right');
        items.style.removeProperty('width');
        items.style.removeProperty('--docs-sheet-tail-left');
        delete items.dataset.docsSheetPositioned;
    };

    const positionDocsSheet = () => {
        if (!mq.matches || !docsGroup?.classList.contains('open')) return;

        const items = document.querySelector('.nav-group-items[data-docs-sheet="1"]');
        if (!items || !toggle) return;

        const toggleRect = toggle.getBoundingClientRect();
        const viewportWidth = window.visualViewport?.width || window.innerWidth;
        const margin = 12;
        const sheetWidth = Math.min(280, viewportWidth - margin * 2);
        const toggleCenterX = toggleRect.left + (toggleRect.width / 2);

        let left = toggleCenterX - (sheetWidth / 2);
        left = Math.max(margin, Math.min(left, viewportWidth - sheetWidth - margin));

        items.style.right = 'auto';
        items.style.width = `${sheetWidth}px`;
        items.style.left = `${left}px`;
        items.style.setProperty('--docs-sheet-tail-left', `${toggleCenterX - left}px`);
        items.dataset.docsSheetPositioned = '1';
    };

    const syncDocsSheetLayout = () => {
        positionDocsSheet();
    };

    let docsSheetScrollBound = false;
    const bindDocsSheetScrollSync = () => {
        if (docsSheetScrollBound) return;
        docsSheetScrollBound = true;
        window.addEventListener('scroll', syncDocsSheetLayout, { passive: true, capture: true });
        window.visualViewport?.addEventListener('scroll', syncDocsSheetLayout);
    };
    const unbindDocsSheetScrollSync = () => {
        if (!docsSheetScrollBound) return;
        docsSheetScrollBound = false;
        window.removeEventListener('scroll', syncDocsSheetLayout, { capture: true });
        window.visualViewport?.removeEventListener('scroll', syncDocsSheetLayout);
    };

    const attachNavStripListener = () => {
        const navEl = document.querySelector('.student-bottom-nav-root .nav');
        if (!navEl || navEl.dataset.scrollBound === '1') return;
        navEl.dataset.scrollBound = '1';
        navEl.addEventListener('scroll', syncDocsSheetLayout, { passive: true });
    };

    syncBottomNavMount();
    mq.addEventListener('change', syncBottomNavMount);
    const onViewportResize = () => {
        positionDocsSheet();
    };
    window.addEventListener('resize', onViewportResize, { passive: true });
    window.visualViewport?.addEventListener('resize', onViewportResize);
    requestAnimationFrame(syncNavRootPosition);

    if (!docsGroup) return;

    if (!backdrop) {
        backdrop = document.createElement('button');
        backdrop.type = 'button';
        backdrop.className = 'student-nav-sheet-backdrop';
        backdrop.setAttribute('aria-label', 'Close documents menu');
        document.body.appendChild(backdrop);
    }

    const docsItemsEl = docsGroup.querySelector('.nav-group-items');
    let docsItemsAnchor = docsItemsEl
        ? { parent: docsItemsEl.parentElement, next: docsItemsEl.nextSibling }
        : null;

    const portalDocsItems = () => {
        const items = docsGroup.querySelector('.nav-group-items')
            || document.querySelector('.nav-group-items[data-docs-sheet="1"]');
        if (!items || items.dataset.docsSheet === '1') return;
        docsItemsAnchor = { parent: items.parentElement, next: items.nextSibling };
        items.dataset.docsSheet = '1';
        items.classList.add('nav-group-items--sheet-open');
        document.body.appendChild(items);
    };

    const unportalDocsItems = () => {
        const items = document.querySelector('.nav-group-items[data-docs-sheet="1"]');
        if (!items) return;
        clearDocsSheetPosition(items);
        items.classList.remove('nav-group-items--sheet-open');
        delete items.dataset.docsSheet;
        if (docsItemsAnchor?.parent) {
            docsItemsAnchor.parent.insertBefore(items, docsItemsAnchor.next);
        } else {
            docsGroup.appendChild(items);
        }
    };

    const closeDocsSheet = (restoreNav = true) => {
        unbindDocsSheetScrollSync();
        unportalDocsItems();
        docsGroup.classList.remove('open');
        toggle?.setAttribute('aria-expanded', 'false');
        backdrop.classList.remove('is-visible');
        if (restoreNav) {
            updateAppSidebarActive(parseAppRoute(window.location.href), window.location.href);
        }
    };

    const openDocsSheet = () => {
        document.querySelectorAll('.student-bottom-nav-root .nav-link.active').forEach(link => {
            link.classList.remove('active');
        });
        portalDocsItems();
        docsGroup.classList.add('open');
        toggle?.setAttribute('aria-expanded', 'true');
        positionDocsSheet();
        bindDocsSheetScrollSync();
        backdrop.classList.add('is-visible');
    };

    const syncMobileDocsState = () => {
        if (mq.matches) {
            unportalDocsItems();
            docsGroup.classList.remove('open');
            toggle?.setAttribute('aria-expanded', 'false');
            backdrop.classList.remove('is-visible');
            return;
        }
        closeDocsSheet();
    };

    syncMobileDocsState();
    syncStudentDocsGroupExpanded();
    mq.addEventListener('change', () => {
        syncMobileDocsState();
        syncStudentDocsGroupExpanded();
    });

    window.addEventListener('student-nav-docs-toggle', event => {
        if (!mq.matches) return;
        const { group, open } = event.detail || {};
        if (group !== docsGroup) return;
        if (open) openDocsSheet();
        else closeDocsSheet();
    });

    backdrop.addEventListener('click', closeDocsSheet);
    docsGroup.querySelectorAll('.nav-group-items .nav-link').forEach(link => {
        link.addEventListener('click', () => {
            if (mq.matches) closeDocsSheet(false);
        });
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && docsGroup.classList.contains('open') && mq.matches) closeDocsSheet();
    });

    window.__closeStudentDocsSheet = closeDocsSheet;
}

function initToasts() {
    document.querySelectorAll('.toast').forEach((toast, i) => {
        setTimeout(() => toast.classList.add('show'), 80 + i * 120);
        setTimeout(() => toast.classList.remove('show'), 4200 + i * 150);
    });
}

function pushAppToast(message, type = 'success') {
    const stack = document.querySelector('.toast-stack');
    if (!stack || !message) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type === 'error' ? 'danger' : 'success'}`;
    toast.textContent = message;
    stack.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 220);
    }, 4200);
}

function setPartnerPasswordFeedback(form, message, isError = true) {
    const feedback = form?.querySelector('[data-password-feedback]');
    if (!feedback) return;
    if (!message) {
        feedback.hidden = true;
        feedback.textContent = '';
        feedback.classList.remove('is-error', 'is-success');
        return;
    }
    feedback.hidden = false;
    feedback.textContent = message;
    feedback.classList.toggle('is-error', isError);
    feedback.classList.toggle('is-success', !isError);
}

async function parseJsonResponse(response, fallbackMessage = 'Something went wrong. Please try again.') {
    const raw = await response.text();
    try {
        return JSON.parse(raw);
    } catch {
        throw new Error(fallbackMessage);
    }
}

function initPartnerPasswordChange() {
    const root = document.querySelector('[data-partner-password-flow]');
    if (!root) return;

    const verifyStep = root.querySelector('[data-password-step="verify"]');
    const changeStep = root.querySelector('[data-password-step="change"]');
    const verifyForm = root.querySelector('[data-partner-verify-password]');
    const changeForm = root.querySelector('[data-partner-change-password]');
    const passwordInput = changeForm?.querySelector('[data-partner-new-password]');
    const confirmInput = changeForm?.querySelector('[data-partner-confirm-password]');
    const strengthIndicator = changeForm?.querySelector('[data-partner-password-strength]');
    const strengthLabel = changeForm?.querySelector('[data-partner-strength-label]');
    const matchIndicator = changeForm?.querySelector('[data-partner-password-match]');
    const isFirstLogin = root.dataset.isFirstLogin === '1';
    const passwordLabel = isFirstLogin ? 'temporary password' : 'current password';
    let verifiedReauthToken = '';

    const resetPasswordIndicators = () => {
        strengthIndicator?.setAttribute('hidden', '');
        strengthIndicator?.removeAttribute('data-level');
        if (strengthLabel) strengthLabel.textContent = '';
        matchIndicator?.setAttribute('hidden', '');
        matchIndicator?.classList.remove('is-match', 'is-mismatch');
        if (matchIndicator) matchIndicator.textContent = '';
    };

    const updatePasswordStrength = () => {
        const password = passwordInput?.value || '';
        const { level, label } = getPasswordStrength(password);
        if (!password) {
            strengthIndicator?.setAttribute('hidden', '');
            strengthIndicator?.removeAttribute('data-level');
            if (strengthLabel) strengthLabel.textContent = '';
            return;
        }
        strengthIndicator?.removeAttribute('hidden');
        strengthIndicator?.setAttribute('data-level', String(level));
        if (strengthLabel) strengthLabel.textContent = label;
    };

    const updatePasswordMatch = () => {
        const password = passwordInput?.value || '';
        const confirmPassword = confirmInput?.value || '';
        if (!confirmPassword) {
            matchIndicator?.setAttribute('hidden', '');
            matchIndicator?.classList.remove('is-match', 'is-mismatch');
            if (matchIndicator) matchIndicator.textContent = '';
            return;
        }
        matchIndicator?.removeAttribute('hidden');
        const matches = password === confirmPassword;
        matchIndicator?.classList.toggle('is-match', matches);
        matchIndicator?.classList.toggle('is-mismatch', !matches);
        if (matchIndicator) {
            matchIndicator.textContent = matches ? 'Passwords match' : 'Passwords do not match';
        }
    };

    const showVerifyStep = () => {
        verifiedReauthToken = '';
        const tokenInput = changeForm?.querySelector('[data-partner-reauth-token]');
        if (tokenInput) tokenInput.value = '';
        changeStep?.classList.remove('is-visible');
        changeStep?.setAttribute('hidden', '');
        verifyStep?.removeAttribute('hidden');
        requestAnimationFrame(() => verifyStep?.classList.add('is-visible'));
        changeForm?.reset();
        if (tokenInput) tokenInput.value = '';
        resetPasswordIndicators();
        setPartnerPasswordFeedback(changeForm, '');
        verifyForm?.querySelector('[data-partner-current-password]')?.focus();
    };

    const showChangeStep = () => {
        verifyStep?.classList.remove('is-visible');
        verifyStep?.setAttribute('hidden', '');
        changeStep?.removeAttribute('hidden');
        requestAnimationFrame(() => {
            changeStep?.classList.add('is-visible');
            changeForm?.querySelector('input[name="password"]')?.focus();
        });
        setPartnerPasswordFeedback(verifyForm, '');
    };

    verifyStep?.classList.add('is-visible');

    passwordInput?.addEventListener('input', () => {
        updatePasswordStrength();
        updatePasswordMatch();
    });
    confirmInput?.addEventListener('input', updatePasswordMatch);

    verifyForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submitBtn = verifyForm.querySelector('button[type="submit"]');
        const currentInput = verifyForm.querySelector('[data-partner-current-password]');
        const currentPassword = currentInput?.value || '';
        if (!currentPassword) {
            currentInput?.focus();
            setPartnerPasswordFeedback(verifyForm, `Enter your ${passwordLabel}.`);
            return;
        }

        submitBtn?.classList.add('loading');
        submitBtn && (submitBtn.disabled = true);
        setPartnerPasswordFeedback(verifyForm, '');

        try {
            const response = await fetch('index.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(verifyForm),
            });
            const data = await parseJsonResponse(response, 'Unable to verify your password. Please try again.');
            if (!response.ok || !data.ok) {
                throw new Error(data.message || `${passwordLabel.charAt(0).toUpperCase()}${passwordLabel.slice(1)} is incorrect.`);
            }
            verifiedReauthToken = data.reauth_token || '';
            if (!verifiedReauthToken) {
                throw new Error('Unable to continue password change. Please try again.');
            }
            const tokenInput = changeForm?.querySelector('[data-partner-reauth-token]');
            if (tokenInput) tokenInput.value = verifiedReauthToken;
            showChangeStep();
        } catch (error) {
            setPartnerPasswordFeedback(verifyForm, error.message || 'Unable to verify your password.');
            currentInput?.focus();
            currentInput?.select();
        } finally {
            submitBtn?.classList.remove('loading');
            if (submitBtn) submitBtn.disabled = false;
        }
    });

    changeForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submitBtn = changeForm.querySelector('button[type="submit"]');
        const password = changeForm.querySelector('input[name="password"]')?.value || '';
        const confirmPassword = changeForm.querySelector('input[name="confirm_password"]')?.value || '';

        if (!verifiedReauthToken) {
            showVerifyStep();
            setPartnerPasswordFeedback(verifyForm, `Please verify your ${passwordLabel} first.`);
            return;
        }
        if (password.length < 8) {
            setPartnerPasswordFeedback(changeForm, 'Password must be at least 8 characters.');
            return;
        }
        if (password !== confirmPassword) {
            setPartnerPasswordFeedback(changeForm, 'Passwords do not match.');
            return;
        }

        submitBtn?.classList.add('loading');
        submitBtn && (submitBtn.disabled = true);
        setPartnerPasswordFeedback(changeForm, '');

        const formData = new FormData(changeForm);
        formData.set('reauth_token', verifiedReauthToken);

        try {
            const response = await fetch('index.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });
            const data = await parseJsonResponse(response, 'Unable to change your password. Please try again.');
            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Unable to change your password.');
            }
            pushAppToast(data.message || 'Password changed successfully.');
            const redirectTo = data.redirect || root?.dataset.successRedirect || '';
            setTimeout(() => {
                window.location.href = redirectTo;
            }, 700);
        } catch (error) {
            const message = (error.message || '').toLowerCase();
            if (message.includes('current password') || message.includes('temporary password')) {
                showVerifyStep();
                setPartnerPasswordFeedback(verifyForm, error.message);
            } else {
                setPartnerPasswordFeedback(changeForm, error.message || 'Unable to change your password.');
            }
        } finally {
            submitBtn?.classList.remove('loading');
            if (submitBtn) submitBtn.disabled = false;
        }
    });

}

function getPasswordStrength(password) {
    if (!password) {
        return { level: 0, label: '' };
    }

    let score = 0;
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;

    if (password.length < 8) {
        return { level: 1, label: 'Weak' };
    }
    if (score <= 2) {
        return { level: 2, label: 'Fair' };
    }
    if (score <= 3) {
        return { level: 3, label: 'Good' };
    }
    return { level: 4, label: 'Strong' };
}

function initStudentPasswordChange() {
    const root = document.querySelector('[data-student-password-flow]');
    if (!root) return;

    const isFirstLogin = root.dataset.isFirstLogin === '1';
    const passwordLabel = isFirstLogin ? 'temporary password' : 'current password';
    const verifyStep = root.querySelector('[data-password-step="verify"]');
    const changeStep = root.querySelector('[data-password-step="change"]');
    const verifyForm = root.querySelector('[data-student-verify-password]');
    const changeForm = root.querySelector('[data-student-change-password]');
    const passwordInput = changeForm?.querySelector('[data-student-new-password]');
    const confirmInput = changeForm?.querySelector('[data-student-confirm-password]');
    const strengthIndicator = changeForm?.querySelector('[data-student-password-strength]');
    const strengthLabel = changeForm?.querySelector('[data-student-strength-label]');
    const matchIndicator = changeForm?.querySelector('[data-student-password-match]');
    let verifiedCurrentPassword = '';

    const resetPasswordIndicators = () => {
        strengthIndicator?.setAttribute('hidden', '');
        strengthIndicator?.removeAttribute('data-level');
        if (strengthLabel) strengthLabel.textContent = '';
        matchIndicator?.setAttribute('hidden', '');
        matchIndicator?.classList.remove('is-match', 'is-mismatch');
        if (matchIndicator) matchIndicator.textContent = '';
    };

    const updatePasswordStrength = () => {
        const password = passwordInput?.value || '';
        const { level, label } = getPasswordStrength(password);
        if (!password) {
            strengthIndicator?.setAttribute('hidden', '');
            strengthIndicator?.removeAttribute('data-level');
            if (strengthLabel) strengthLabel.textContent = '';
            return;
        }
        strengthIndicator?.removeAttribute('hidden');
        strengthIndicator?.setAttribute('data-level', String(level));
        if (strengthLabel) strengthLabel.textContent = label;
    };

    const updatePasswordMatch = () => {
        const password = passwordInput?.value || '';
        const confirmPassword = confirmInput?.value || '';
        if (!confirmPassword) {
            matchIndicator?.setAttribute('hidden', '');
            matchIndicator?.classList.remove('is-match', 'is-mismatch');
            if (matchIndicator) matchIndicator.textContent = '';
            return;
        }
        matchIndicator?.removeAttribute('hidden');
        const matches = password === confirmPassword;
        matchIndicator?.classList.toggle('is-match', matches);
        matchIndicator?.classList.toggle('is-mismatch', !matches);
        if (matchIndicator) {
            matchIndicator.textContent = matches ? 'Passwords match' : 'Passwords do not match';
        }
    };

    const showVerifyStep = () => {
        verifiedCurrentPassword = '';
        changeStep?.classList.remove('is-visible');
        changeStep?.setAttribute('hidden', '');
        verifyStep?.removeAttribute('hidden');
        requestAnimationFrame(() => verifyStep?.classList.add('is-visible'));
        changeForm?.reset();
        resetPasswordIndicators();
        setPartnerPasswordFeedback(changeForm, '');
        verifyForm?.querySelector('[data-student-current-password]')?.focus();
    };

    const showChangeStep = () => {
        verifyStep?.classList.remove('is-visible');
        verifyStep?.setAttribute('hidden', '');
        changeStep?.removeAttribute('hidden');
        requestAnimationFrame(() => {
            changeStep?.classList.add('is-visible');
            changeForm?.querySelector('input[name="password"]')?.focus();
        });
        setPartnerPasswordFeedback(verifyForm, '');
    };

    verifyStep?.classList.add('is-visible');

    passwordInput?.addEventListener('input', () => {
        updatePasswordStrength();
        updatePasswordMatch();
    });
    confirmInput?.addEventListener('input', updatePasswordMatch);

    verifyForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submitBtn = verifyForm.querySelector('button[type="submit"]');
        const currentInput = verifyForm.querySelector('[data-student-current-password]');
        const currentPassword = currentInput?.value || '';
        if (!currentPassword) {
            currentInput?.focus();
            setPartnerPasswordFeedback(verifyForm, `Enter your ${passwordLabel}.`);
            return;
        }

        submitBtn?.classList.add('loading');
        if (submitBtn) submitBtn.disabled = true;
        setPartnerPasswordFeedback(verifyForm, '');

        try {
            const response = await fetch('index.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(verifyForm),
            });
            const data = await parseJsonResponse(response, 'Unable to verify your password. Please try again.');
            if (!response.ok || !data.ok) {
                throw new Error(data.message || `${passwordLabel.charAt(0).toUpperCase()}${passwordLabel.slice(1)} is incorrect.`);
            }
            verifiedCurrentPassword = currentPassword;
            showChangeStep();
        } catch (error) {
            setPartnerPasswordFeedback(verifyForm, error.message || 'Unable to verify your password.');
            currentInput?.focus();
            currentInput?.select();
        } finally {
            submitBtn?.classList.remove('loading');
            if (submitBtn) submitBtn.disabled = false;
        }
    });

    changeForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submitBtn = changeForm.querySelector('button[type="submit"]');
        const password = changeForm.querySelector('input[name="password"]')?.value || '';
        const confirmPassword = changeForm.querySelector('input[name="confirm_password"]')?.value || '';

        if (!verifiedCurrentPassword) {
            showVerifyStep();
            setPartnerPasswordFeedback(verifyForm, `Please verify your ${passwordLabel} first.`);
            return;
        }
        if (!password) {
            setPartnerPasswordFeedback(changeForm, 'New password is required.');
            return;
        }
        if (password.length < 8) {
            setPartnerPasswordFeedback(changeForm, 'Password must be at least 8 characters.');
            return;
        }
        if (!confirmPassword) {
            setPartnerPasswordFeedback(changeForm, 'Please confirm your new password.');
            return;
        }
        if (password !== confirmPassword) {
            setPartnerPasswordFeedback(changeForm, 'Passwords do not match.');
            return;
        }

        submitBtn?.classList.add('loading');
        if (submitBtn) submitBtn.disabled = true;
        setPartnerPasswordFeedback(changeForm, '');

        const formData = new FormData(changeForm);
        formData.set('current_password', verifiedCurrentPassword);

        try {
            const response = await fetch('index.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });
            const data = await parseJsonResponse(response, 'Unable to change your password. Please try again.');
            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Unable to change your password.');
            }
            pushAppToast(data.message || 'Password changed successfully.');
            const redirectTo = data.redirect || 'index.php?r=student';
            setTimeout(() => {
                window.location.href = redirectTo;
            }, 700);
        } catch (error) {
            if ((error.message || '').toLowerCase().includes('password')) {
                showVerifyStep();
                setPartnerPasswordFeedback(verifyForm, error.message);
            } else {
                setPartnerPasswordFeedback(changeForm, error.message || 'Unable to change your password.');
            }
        } finally {
            submitBtn?.classList.remove('loading');
            if (submitBtn) submitBtn.disabled = false;
        }
    });

}

function initPartnerPortalRoster() {
    const root = document.querySelector('.partner-portal-v2');
    if (!root) return;

    const filters = root.querySelectorAll('.pp-filter');
    const searchInput = root.querySelector('.pp-search-input');
    const cards = [...root.querySelectorAll('.pp-roster-card')];
    let activeFilter = 'all';

    const applyRosterFilter = () => {
        const q = (searchInput?.value || '').trim().toLowerCase();
        cards.forEach(card => {
            const groups = (card.dataset.ppGroups || '').split(/\s+/);
            const matchesFilter = activeFilter === 'all' || groups.includes(activeFilter);
            const matchesSearch = !q || (card.dataset.ppSearch || '').includes(q);
            card.classList.toggle('is-hidden', !(matchesFilter && matchesSearch));
        });
    };

    filters.forEach(btn => {
        btn.addEventListener('click', () => {
            activeFilter = btn.dataset.ppFilter || 'all';
            filters.forEach(b => {
                const isActive = b === btn;
                b.classList.toggle('is-active', isActive);
                b.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            applyRosterFilter();
        });
    });

    searchInput?.addEventListener('input', applyRosterFilter);

    if (location.hash === '#student-workspace') {
        const workspace = document.getElementById('student-workspace');
        workspace?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function initPartnerSubmissions() {
    const root = document.querySelector('[data-ps-submissions]');
    const detailHost = root?.querySelector('[data-ps-v2-detail]');
    if (!root || !detailHost) return;

    let loading = false;

    const searchInput = root.querySelector('[data-ps-student-search]');
    const studentList = root.querySelector('.ps-v2-student-list');
    const searchEmpty = root.querySelector('[data-ps-student-search-empty]');
    const filterStudents = () => {
        const q = (searchInput?.value || '').trim().toLowerCase();
        const items = root.querySelectorAll('[data-ps-student-item]');
        let visible = 0;
        items.forEach(item => {
            const match = q === '' || (item.dataset.search || '').includes(q);
            item.hidden = !match;
            if (match) visible++;
        });
        const showNotFound = q !== '' && visible === 0;
        if (studentList) {
            studentList.hidden = showNotFound;
        }
        if (searchEmpty) {
            searchEmpty.hidden = !showNotFound;
        }
    };
    searchInput?.addEventListener('input', filterStudents);

    const setupMetaMarquees = () => {
        root.querySelectorAll('[data-ps-meta-marquee]').forEach(marquee => {
            const track = marquee.querySelector('.ps-v2-student-meta-track');
            const first = track?.querySelector('small:first-child');
            if (!track || !first) return;

            marquee.classList.remove('is-overflow');
            track.style.removeProperty('--ps-marquee-distance');
            track.style.removeProperty('--ps-marquee-duration');

            if (first.scrollWidth > marquee.clientWidth + 2) {
                const gap = 32;
                const distance = first.scrollWidth + gap;
                marquee.classList.add('is-overflow');
                track.style.setProperty('--ps-marquee-distance', `-${distance}px`);
                track.style.setProperty('--ps-marquee-duration', `${Math.max(7, distance / 24)}s`);
            }
        });
    };
    setupMetaMarquees();
    window.addEventListener('resize', setupMetaMarquees);

    const setSelectedStudent = studentId => {
        root.querySelectorAll('[data-student-id]').forEach(card => {
            card.classList.toggle('is-selected', card.dataset.studentId === String(studentId));
        });
    };

    const loadDetail = async (url, { pushState = true, studentId = null } = {}) => {
        if (loading) return;
        loading = true;
        detailHost.classList.add('is-loading');
        try {
            const requestUrl = new URL(url, window.location.href);
            if (!requestUrl.searchParams.get('partial')) {
                requestUrl.searchParams.set('partial', 'detail');
            }
            const response = await fetch(requestUrl.toString(), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
            });
            if (!response.ok) {
                throw new Error('Failed to load submissions.');
            }
            detailHost.innerHTML = await response.text();
            if (studentId !== null) {
                setSelectedStudent(studentId);
            }
            if (pushState) {
                const cleanUrl = new URL(url, window.location.href);
                cleanUrl.searchParams.delete('partial');
                history.pushState({ psSubmissions: true }, '', cleanUrl.toString());
            }
        } catch {
            window.location.assign(url);
        } finally {
            loading = false;
            detailHost.classList.remove('is-loading');
        }
    };

    root.addEventListener('click', event => {
        const link = event.target.closest('[data-ps-ajax]');
        if (!link || !root.contains(link)) return;
        event.preventDefault();
        link.closest('details')?.removeAttribute('open');
        const studentId = link.dataset.studentId || null;
        loadDetail(link.href, { studentId });
    });
}

function initPartnerSubmissionsPopstate() {
    if (window.__psPopstateBound) return;
    window.__psPopstateBound = true;

    window.addEventListener('popstate', async () => {
        const root = document.querySelector('[data-ps-submissions]');
        const detailHost = root?.querySelector('[data-ps-v2-detail]');
        if (!root || !detailHost) return;

        const params = new URLSearchParams(window.location.search);
        const studentId = params.get('student_id');
        const url = window.location.href;

        if (!studentId) {
            detailHost.classList.add('is-loading');
            try {
                const listUrl = new URL(url, window.location.href);
                listUrl.searchParams.delete('student_id');
                listUrl.searchParams.set('partial', 'detail');
                const response = await fetch(listUrl.toString(), {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store',
                });
                if (!response.ok) {
                    throw new Error('Failed to load submissions.');
                }
                detailHost.innerHTML = await response.text();
                root.querySelectorAll('[data-student-id]').forEach(card => card.classList.remove('is-selected'));
            } catch {
                window.location.assign(url);
            } finally {
                detailHost.classList.remove('is-loading');
            }
            return;
        }

        detailHost.classList.add('is-loading');
        try {
            const detailUrl = new URL(url, window.location.href);
            detailUrl.searchParams.set('partial', 'detail');
            const response = await fetch(detailUrl.toString(), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
            });
            if (!response.ok) {
                throw new Error('Failed to load submissions.');
            }
            detailHost.innerHTML = await response.text();
            root.querySelectorAll('[data-student-id]').forEach(card => {
                card.classList.toggle('is-selected', card.dataset.studentId === String(studentId));
            });
        } catch {
            window.location.assign(url);
        } finally {
            detailHost.classList.remove('is-loading');
        }
    });
}

function initFloatingLabels() {
    document.querySelectorAll('.form label, .filter-bar label, .partner-form-fields label, .partner-form-section > label').forEach(label => {
        if (label.classList.contains('no-floating-label') || label.querySelector('.label-text')) return;
        const text = [...label.childNodes].find(n => n.nodeType === Node.TEXT_NODE && n.textContent.trim());
        const field = label.querySelector('input,select,textarea');
        if (!text || !field || field.type === 'hidden' || field.type === 'file') return;
        const span = document.createElement('span');
        span.className = 'label-text';
        span.textContent = text.textContent.trim();
        text.textContent = '';
        label.insertBefore(span, field);
        label.classList.add('floating-label');
        if (field.tagName === 'TEXTAREA') label.classList.add('floating-textarea');
        const sync = () => label.classList.toggle('has-value', !!field.value);
        field.addEventListener('input', sync);
        field.addEventListener('change', sync);
        sync();
    });
}

function formatPhilippineMobile(value) {
    let digits = String(value || '').replace(/\D/g, '');
    if (digits.startsWith('63')) digits = digits.slice(2);
    if (digits.startsWith('0')) digits = digits.slice(1);
    digits = digits.slice(0, 10);
    if (!digits) return '';
    const parts = [digits.slice(0, 3), digits.slice(3, 6), digits.slice(6, 10)].filter(Boolean);
    return `+63 ${parts.join(' ')}`.trim();
}

function initPhoneInputs() {
    document.querySelectorAll('input[data-phone-format="ph"]').forEach(input => {
        const sync = () => {
            input.value = formatPhilippineMobile(input.value);
            input.closest('label')?.classList.toggle('has-value', !!input.value);
        };
        input.addEventListener('input', sync);
        input.addEventListener('paste', () => requestAnimationFrame(sync));
        input.addEventListener('blur', sync);
        sync();
    });
}

function initCharacterCounters() {
    document.querySelectorAll('textarea').forEach(textarea => {
        if (!textarea.maxLength || textarea.maxLength < 0) textarea.maxLength = 500;
        const counter = document.createElement('small');
        counter.className = 'char-counter';
        textarea.insertAdjacentElement('afterend', counter);
        const update = () => {
            const remaining = textarea.maxLength - textarea.value.length;
            counter.textContent = `${remaining} characters remaining`;
            counter.classList.toggle('warning', remaining <= Math.min(50, textarea.maxLength * 0.1));
        };
        textarea.addEventListener('input', update);
        update();
    });
}

let _dtrTimePanel = null;
let _dtrTimeContext = null;
let _dtrTimeState = null;

function hasDtrTimeValue(value) {
    return /^\d{1,2}:\d{2}/.test(String(value || '').trim());
}

function formatDtrTimeDisplay(value) {
    const match = String(value || '').match(/^(\d{1,2}):(\d{2})/);
    if (!match) return '--:-- --';
    const hour24 = Number(match[1]);
    const minute = match[2];
    const suffix = hour24 >= 12 ? 'PM' : 'AM';
    const hour12 = hour24 % 12 || 12;
    return `${String(hour12).padStart(2, '0')}:${minute} ${suffix}`;
}

function toDtrTimeValue(hour, minute, period) {
    let hour24 = Number(hour) % 12;
    if (period === 'PM') hour24 += 12;
    return `${String(hour24).padStart(2, '0')}:${String(Number(minute) || 0).padStart(2, '0')}`;
}

function parseDtrTimeValue(value) {
    const match = String(value || '').match(/^(\d{1,2}):(\d{2})/);
    const fallback = new Date();
    const hour24 = match ? Number(match[1]) : fallback.getHours();
    const minute = match ? Number(match[2]) : fallback.getMinutes();
    return {
        hour: hour24 % 12 || 12,
        minute: Math.max(0, Math.min(59, minute)),
        period: hour24 >= 12 ? 'PM' : 'AM',
    };
}

function getDtrTimePanel() {
    if (_dtrTimePanel) return _dtrTimePanel;
    _dtrTimePanel = document.createElement('div');
    _dtrTimePanel.className = 'dtr-time-panel';
    _dtrTimePanel.hidden = true;
    document.body.appendChild(_dtrTimePanel);

    _dtrTimePanel.addEventListener('mousedown', e => e.stopPropagation());
    _dtrTimePanel.addEventListener('click', e => {
        e.stopPropagation();
        if (!_dtrTimeState || !_dtrTimeContext) return;
        const hour = e.target.closest('[data-dtr-hour]');
        if (hour) {
            _dtrTimeState.hour = Number(hour.dataset.dtrHour || 12);
            renderDtrTimePanel();
            return;
        }
        const period = e.target.closest('[data-dtr-period]');
        if (period) {
            _dtrTimeState.period = period.dataset.dtrPeriod || 'AM';
            renderDtrTimePanel();
            return;
        }
        const now = e.target.closest('[data-dtr-now]');
        if (now) {
            _dtrTimeState = parseDtrTimeValue(`${String(new Date().getHours()).padStart(2, '0')}:${String(new Date().getMinutes()).padStart(2, '0')}`);
            renderDtrTimePanel();
            return;
        }
        const set = e.target.closest('[data-dtr-set-time]');
        if (set) {
            const minuteInput = _dtrTimePanel.querySelector('[data-dtr-minute]');
            _dtrTimeState.minute = Math.max(0, Math.min(59, Number(minuteInput?.value || 0)));
            _dtrTimeContext.input.value = toDtrTimeValue(_dtrTimeState.hour, _dtrTimeState.minute, _dtrTimeState.period);
            _dtrTimeContext.sync();
            closeDtrTimePicker();
            _dtrTimeContext.saveButton?.focus();
        }
    });
    _dtrTimePanel.addEventListener('input', e => {
        const minuteInput = e.target.closest('[data-dtr-minute]');
        if (!minuteInput || !_dtrTimeState) return;
        minuteInput.value = String(Math.max(0, Math.min(59, Number(minuteInput.value || 0)))).padStart(2, '0');
        _dtrTimeState.minute = Number(minuteInput.value || 0);
    });
    return _dtrTimePanel;
}

function renderDtrTimePanel() {
    const panel = getDtrTimePanel();
    const hours = Array.from({ length: 12 }, (_, i) => i + 1).map(hour => `<button type="button" class="dtr-time-chip ${hour === _dtrTimeState.hour ? 'is-active' : ''}" data-dtr-hour="${hour}">${String(hour).padStart(2, '0')}</button>`).join('');
    panel.innerHTML = `<div class="dtr-time-panel-head"><strong>Choose Time</strong><button type="button" data-dtr-now>Use Now</button></div><div class="dtr-time-preview">${formatDtrTimeDisplay(toDtrTimeValue(_dtrTimeState.hour, _dtrTimeState.minute, _dtrTimeState.period))}</div><div class="dtr-time-hours">${hours}</div><div class="dtr-time-bottom"><label>Minute<input type="number" min="0" max="59" value="${String(_dtrTimeState.minute).padStart(2, '0')}" data-dtr-minute></label><div class="dtr-time-periods"><button type="button" class="${_dtrTimeState.period === 'AM' ? 'is-active' : ''}" data-dtr-period="AM">AM</button><button type="button" class="${_dtrTimeState.period === 'PM' ? 'is-active' : ''}" data-dtr-period="PM">PM</button></div></div><button type="button" class="btn btn-primary dtr-time-set" data-dtr-set-time>Set Time</button>`;
}

function openDtrTimePicker(context) {
    closeDtrTimePicker();
    _dtrTimeContext = context;
    _dtrTimeState = parseDtrTimeValue(context.input.value);
    const panel = getDtrTimePanel();
    renderDtrTimePanel();
    const rect = context.trigger.getBoundingClientRect();
    const viewportPadding = 10;
    const width = Math.min(320, window.innerWidth - (viewportPadding * 2));
    const panelHeight = Math.min(panel.getBoundingClientRect().height || 380, window.innerHeight - (viewportPadding * 2));
    const belowTop = rect.bottom + 8;
    const aboveTop = rect.top - panelHeight - 8;
    const hasRoomBelow = belowTop + panelHeight <= window.innerHeight - viewportPadding;
    panel.style.width = `${width}px`;
    panel.style.left = `${Math.max(viewportPadding, Math.min(rect.left, window.innerWidth - width - viewportPadding))}px`;
    panel.style.top = `${Math.max(viewportPadding, hasRoomBelow ? belowTop : aboveTop)}px`;
    panel.hidden = false;
    requestAnimationFrame(() => panel.classList.add('is-open'));
}

function closeDtrTimePicker() {
    if (!_dtrTimePanel) return;
    _dtrTimePanel.classList.remove('is-open');
    _dtrTimePanel.hidden = true;
    _dtrTimeContext = null;
    _dtrTimeState = null;
}

function initDtrTimeLocks() {
    const dayTypeConfig = {
        full: {
            morning: true,
            afternoon: true,
            needsTimes: true,
            requiredIndices: [0, 1, 2, 3],
            intro: 'Record your morning and afternoon attendance, then describe the tasks you completed today.',
            tasksLabel: 'Tasks Done',
            tasksHint: 'Summarize your practicum activities for this work day.',
            tasksPlaceholder: 'Describe the tasks you completed today...',
            confirmTitle: 'Submit DTR',
            confirmMessage: 'Please verify the work date, session times, and tasks before submitting.',
            summaryLabel: 'Whole Day',
        },
        half_am: {
            morning: true,
            afternoon: false,
            needsTimes: true,
            requiredIndices: [0, 1],
            intro: 'Log your morning session only, then describe what you accomplished.',
            tasksLabel: 'Tasks Done',
            tasksHint: 'Summarize what you completed during your morning practicum session.',
            tasksPlaceholder: 'Describe the tasks you completed this morning...',
            confirmTitle: 'Confirm Half Day (Morning) DTR',
            confirmMessage: 'You are submitting a half-day morning DTR only. Afternoon attendance will not be recorded.',
            summaryLabel: 'Half Day ? Morning Only',
        },
        half_pm: {
            morning: false,
            afternoon: true,
            needsTimes: true,
            requiredIndices: [2, 3],
            intro: 'Log your afternoon session only, then describe what you accomplished.',
            tasksLabel: 'Tasks Done',
            tasksHint: 'Summarize what you completed during your afternoon practicum session.',
            tasksPlaceholder: 'Describe the tasks you completed this afternoon...',
            confirmTitle: 'Confirm Half Day (Afternoon) DTR',
            confirmMessage: 'You are submitting a half-day afternoon DTR only. Morning attendance will not be recorded.',
            summaryLabel: 'Half Day ? Afternoon Only',
        },
        sick: {
            morning: false,
            afternoon: false,
            needsTimes: false,
            requiredIndices: [],
            intro: 'No time log is required. Briefly explain your sick leave for partner review.',
            tasksLabel: 'Reason for Sick Leave',
            tasksHint: 'Provide a brief explanation of your absence (e.g. medical reason).',
            tasksPlaceholder: 'Explain why you were on sick leave today...',
            confirmTitle: 'Confirm Sick Leave DTR',
            confirmMessage: 'You are submitting a sick leave record with 0 hours. This will not count toward rendered OJT hours.',
            summaryLabel: 'Sick Leave',
        },
        absent: {
            morning: false,
            afternoon: false,
            needsTimes: false,
            requiredIndices: [],
            intro: 'No time log is required. Briefly explain your absence for partner review.',
            tasksLabel: 'Reason for Absence',
            tasksHint: 'Provide a brief explanation of why you did not report today.',
            tasksPlaceholder: 'Explain why you were absent today...',
            confirmTitle: 'Confirm Absence DTR',
            confirmMessage: 'You are submitting an absence record with 0 hours. This will not count toward rendered OJT hours.',
            summaryLabel: 'Absent',
        },
    };

    document.querySelectorAll('[data-dtr-lock-flow]').forEach(form => {
        const groups = [...form.querySelectorAll('[data-time-lock-group]')].map(group => ({
            group,
            input: group.querySelector('[data-lockable-time]'),
            trigger: group.querySelector('[data-time-picker-trigger]'),
            display: group.querySelector('[data-time-display]'),
            button: group.querySelector('[data-time-lock-toggle]'),
            locked: group.dataset.savedLocked === '1' && hasDtrTimeValue(group.querySelector('[data-lockable-time]')?.value),
        })).filter(item => item.input && item.button && item.trigger && item.display);
        const tasks = form.querySelector('[data-dtr-tasks]');
        const submit = form.querySelector('[data-dtr-submit]');
        const dayTypeSelect = form.querySelector('[data-dtr-day-type]');
        const morningSession = form.querySelector('[data-dtr-session="morning"]');
        const afternoonSession = form.querySelector('[data-dtr-session="afternoon"]');
        const sessionsWrap = form.querySelector('[data-dtr-sessions]');
        const formIntro = form.querySelector('[data-dtr-form-intro]');
        const tasksLabel = form.querySelector('[data-dtr-tasks-label]');
        const tasksHint = form.querySelector('[data-dtr-tasks-hint]');
        const submitHint = form.querySelector('[data-dtr-submit-hint]');
        const submitSummary = form.querySelector('[data-dtr-submit-summary]');
        const datePicker = form.querySelector('.dtr-date-section .filter-date-picker');
        const workDateInput = form.querySelector('input[name="work_date"]');
        const useTodayBtn = form.querySelector('[data-dtr-date-today]');
        if (!groups.length || !tasks || !submit || !dayTypeSelect) return;

        const getDayType = () => dayTypeConfig[dayTypeSelect.value] ? dayTypeSelect.value : 'full';
        const getConfig = () => dayTypeConfig[getDayType()] || dayTypeConfig.full;

        const formatWorkDateDisplay = value => {
            const parsed = parseCustomDateValue(value);
            return parsed ? formatCustomDateDisplay(parsed) : '';
        };

        const buildScheduleSummary = () => {
            const dayType = getDayType();
            const amIn = groups[0]?.input?.value || '';
            const amOut = groups[1]?.input?.value || '';
            const pmIn = groups[2]?.input?.value || '';
            const pmOut = groups[3]?.input?.value || '';

            if (dayType === 'half_am') {
                return `Morning: ${formatDtrTimeDisplay(amIn)} ? ${formatDtrTimeDisplay(amOut)} ? Afternoon: not included`;
            }
            if (dayType === 'half_pm') {
                return `Morning: not included ? Afternoon: ${formatDtrTimeDisplay(pmIn)} ? ${formatDtrTimeDisplay(pmOut)}`;
            }
            if (dayType === 'sick' || dayType === 'absent') {
                return 'No attendance times ? 0 hours';
            }
            return `Morning: ${formatDtrTimeDisplay(amIn)} ? ${formatDtrTimeDisplay(amOut)} ? Afternoon: ${formatDtrTimeDisplay(pmIn)} ? ${formatDtrTimeDisplay(pmOut)}`;
        };

        const setWorkDate = date => {
            if (!workDateInput) return;
            workDateInput.value = formatCustomDateValue(date);
            const pickerValue = form.querySelector('.filter-date-value');
            if (pickerValue) pickerValue.textContent = formatCustomDateDisplay(date);
            datePicker?.classList.remove('is-placeholder', 'date-required-error');
            workDateInput.dispatchEvent(new Event('change', { bubbles: true }));
            workDateInput.dispatchEvent(new Event('input', { bubbles: true }));
        };

        if (useTodayBtn) {
            useTodayBtn.addEventListener('click', () => {
                setWorkDate(stripTime(new Date()));
                saveDraft();
            });
        }

        const saveDraft = () => {
            const body = new URLSearchParams();
            body.set('csrf_token', form.querySelector('input[name="csrf_token"]')?.value || '');
            body.set('action', 'student_save_dtr_draft');
            body.set('work_date', workDateInput?.value || '');
            body.set('day_type', getDayType());
            groups.forEach(item => {
                const fieldName = item.input.name;
                if (!fieldName) return;
                body.set(fieldName, item.input.value || '');
                body.set(`${fieldName}_locked`, item.locked && hasDtrTimeValue(item.input.value) ? '1' : '0');
            });
            return fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body,
                credentials: 'same-origin',
                keepalive: true,
            }).then(async response => {
                if (response.ok) {
                    // Try to read JSON body if available to check server 'ok' flag
                    try {
                        const json = await response.json();
                        if (json && (json.ok === true || json.ok === 'true')) return true;
                    } catch (e) {
                        // ignore parse error and fallthrough to returning true
                    }
                    return true;
                }
                const text = await response.text().catch(() => '');
                console.error('DTR draft save failed', response.status, text);
                return false;
            }).catch(err => {
                console.error('Error saving DTR draft', err);
                return false;
            });
        };

        const clearGroup = (item, resetTasks = false) => {
            item.input.value = '';
            item.locked = false;
            item.group.classList.remove('is-locked', 'needs-time');
        };

        const resetInactiveSessions = () => {
            const config = getConfig();
            groups.forEach((item, index) => {
                if (!config.requiredIndices.includes(index)) {
                    clearGroup(item);
                }
            });
        };

        const sync = () => {
            const config = getConfig();
            const required = config.requiredIndices;

            if (formIntro) formIntro.textContent = config.intro;
            if (tasksLabel) tasksLabel.textContent = config.tasksLabel;
            if (tasksHint) tasksHint.textContent = config.tasksHint;
            if (tasks.dataset.dtrTasksPlaceholder) {
                tasks.placeholder = config.tasksPlaceholder;
            }

            morningSession?.toggleAttribute('hidden', !config.morning);
            afternoonSession?.toggleAttribute('hidden', !config.afternoon);
            sessionsWrap?.toggleAttribute('hidden', !config.needsTimes);
            form.classList.toggle('dtr-leave-mode', !config.needsTimes);

            groups.forEach((item, index) => {
                if (!hasDtrTimeValue(item.input.value)) {
                    item.locked = false;
                }

                const isRequired = required.includes(index);
                const reqPos = required.indexOf(index);
                const mustWait = isRequired && reqPos > 0 && !groups[required[reqPos - 1]]?.locked;
                const isSaved = item.locked && hasDtrTimeValue(item.input.value);

                item.group.toggleAttribute('hidden', !isRequired);
                item.input.disabled = false;
                item.trigger.disabled = !isRequired;
                item.button.disabled = !isRequired;

                if (!isRequired) {
                    item.trigger.setAttribute('aria-disabled', 'true');
                    item.button.setAttribute('aria-disabled', 'true');
                    item.group.classList.remove('is-locked', 'is-waiting', 'has-time', 'needs-time');
                    item.group.querySelector('[data-time-lock-badge]')?.setAttribute('hidden', '');
                    return;
                }

                item.trigger.setAttribute('aria-disabled', String(mustWait || isSaved));
                item.button.setAttribute('aria-disabled', String(mustWait));
                item.display.textContent = formatDtrTimeDisplay(item.input.value);
                item.group.classList.toggle('is-locked', isSaved);
                item.group.classList.toggle('is-waiting', mustWait);
                item.group.classList.toggle('has-time', hasDtrTimeValue(item.input.value));
                const labelSpan = item.button.querySelector('.dtr-time-lock-btn-text');
                const nextLabel = isSaved ? item.button.dataset.editLabel : item.button.dataset.applyLabel;
                if (labelSpan) labelSpan.textContent = nextLabel;
                else item.button.textContent = nextLabel;
                item.button.classList.toggle('is-edit-mode', isSaved);
                item.button.setAttribute('aria-label', nextLabel);
                item.group.querySelector('[data-time-lock-badge]')?.toggleAttribute('hidden', !isSaved);
            });

            const timesReady = !config.needsTimes || required.every(index => groups[index]?.locked && hasDtrTimeValue(groups[index]?.input?.value));
            const dateReady = !!workDateInput?.value?.trim();
            const tasksReady = !!tasks.value.trim();
            const canSubmit = dateReady && tasksReady && timesReady;

            tasks.disabled = false;
            submit.disabled = !canSubmit;
            form.classList.toggle('dtr-ready-for-tasks', config.needsTimes ? timesReady : true);

            datePicker?.classList.toggle('date-required-error', !dateReady);

            if (canSubmit) {
                form.dataset.confirmTitle = config.confirmTitle || 'Submit DTR';
                form.dataset.confirmSubmit = `${config.confirmMessage || 'Please verify before submitting.'} Work date: ${formatWorkDateDisplay(workDateInput?.value) || '?'}. ${buildScheduleSummary()}.`;
                form.dataset.confirmOk = 'Yes, submit DTR';
            }

            if (submitSummary) {
                if (canSubmit && getDayType() !== 'full') {
                    submitSummary.innerHTML = `
                        <strong>${escapeHtml(config.summaryLabel || 'Ready to submit')}</strong>
                        <span>Work date: ${escapeHtml(formatWorkDateDisplay(workDateInput?.value) || '?')}</span>
                        <span>${escapeHtml(buildScheduleSummary())}</span>
                        <span class="dtr-submit-summary-note">Please review the summary above before submitting.</span>
                    `;
                    submitSummary.hidden = false;
                } else {
                    submitSummary.hidden = true;
                    submitSummary.innerHTML = '';
                }
            }

            if (submitHint) {
                if (canSubmit) {
                    submitHint.textContent = '';
                    submitHint.hidden = true;
                } else {
                    const missing = [];
                    if (!dateReady) missing.push('work date');
                    if (config.needsTimes && !timesReady) {
                        missing.push(config.requiredIndices.length === 4
                            ? 'all required time entries (save each one)'
                            : 'required session times (save each one)');
                    }
                    if (!tasksReady) {
                        missing.push(config.needsTimes ? 'tasks done' : 'reason for absence');
                    }
                    submitHint.textContent = `To submit: ${missing.join(', ')}.`;
                    submitHint.hidden = false;
                }
            }
        };

        const unlockFrom = startIndex => {
            groups.slice(startIndex).forEach(item => {
                item.locked = false;
                item.group.classList.remove('is-locked');
            });
        };

        const clearFrom = startIndex => {
            groups.slice(startIndex).forEach(item => clearGroup(item));
            if (startIndex <= groups.length - 1) {
                tasks.value = '';
                tasks.dispatchEvent(new Event('input', { bubbles: true }));
            }
        };

        dayTypeSelect.addEventListener('change', () => {
            resetInactiveSessions();
            sync();
            saveDraft();
        });

        workDateInput?.addEventListener('change', sync);
        workDateInput?.addEventListener('input', sync);
        tasks.addEventListener('input', sync);

        groups.forEach((item, index) => {
            let lastTap = 0;
            const runOnce = (event, handler) => {
                const now = Date.now();
                if (event.type !== 'keydown' && now - lastTap < 350) return;
                lastTap = now;
                event.preventDefault();
                event.stopPropagation();
                handler();
            };

            const openPicker = () => {
                if (item.trigger.getAttribute('aria-disabled') === 'true') return;
                openDtrTimePicker({ input: item.input, trigger: item.trigger, saveButton: item.button, sync });
            };

            const toggleLock = async () => {
                sync();
                if (item.button.getAttribute('aria-disabled') === 'true') return;
                if (item.locked) {
                    unlockFrom(index);
                    clearFrom(index);
                    sync();
                    await saveDraft();
                    item.trigger.focus();
                    return;
                }
                if (!hasDtrTimeValue(item.input.value)) {
                    item.group.classList.add('needs-time');
                    openPicker();
                    return;
                }
                item.group.classList.remove('needs-time');
                item.locked = true;
                sync();
                const saved = await saveDraft();
                if (!saved) {
                    item.locked = false;
                    sync();
                    console.error('Failed to save DTR draft.');
                    return;
                }
                const config = getConfig();
                const required = config.requiredIndices;
                const reqPos = required.indexOf(index);
                const nextRequiredIndex = reqPos >= 0 ? required[reqPos + 1] : undefined;
                const nextInput = nextRequiredIndex !== undefined ? groups[nextRequiredIndex]?.input : tasks;
                const nextTrigger = nextRequiredIndex !== undefined ? groups[nextRequiredIndex]?.trigger : null;
                (nextTrigger || nextInput)?.focus();
            };

            item.input.addEventListener('input', sync);
            item.input.addEventListener('change', sync);
            item.input.addEventListener('blur', sync);
            item.input.addEventListener('keyup', sync);
            ['pointerup', 'touchend', 'click'].forEach(eventName => {
                item.trigger.addEventListener(eventName, event => runOnce(event, openPicker), { passive: false });
                item.button.addEventListener(eventName, event => runOnce(event, toggleLock), { passive: false });
            });
            item.trigger.addEventListener('keydown', event => {
                if (!['Enter', ' '].includes(event.key)) return;
                runOnce(event, openPicker);
            });
            item.button.addEventListener('keydown', event => {
                if (!['Enter', ' '].includes(event.key)) return;
                runOnce(event, toggleLock);
            });
        });

        if (!workDateInput?.value?.trim()) {
            setWorkDate(stripTime(new Date()));
            saveDraft();
        }

        resetInactiveSessions();
        sync();
    });
}

function sortControlsByDocumentOrder(controls) {
    return [...controls].sort((a, b) => {
        if (a === b) return 0;
        const position = a.compareDocumentPosition(b);
        if (position & Node.DOCUMENT_POSITION_FOLLOWING) return -1;
        if (position & Node.DOCUMENT_POSITION_PRECEDING) return 1;
        return 0;
    });
}

function getValidationScrollTarget(control) {
    if (!control) return null;

    if (control.type === 'file') {
        return control.closest('.spf-photo-upload, .profile-photo-input, .spf-photo-stack, label') || control;
    }

    return control.closest('.spf-section, .spf-field, .spf-verified-group, label') || control;
}

function scrollToFirstInvalidControl(form, controls) {
    const ordered = sortControlsByDocumentOrder(controls);

    for (const control of ordered) {
        if (control.disabled || !control.willValidate) continue;
        if (control.checkValidity()) continue;

        const scrollTarget = getValidationScrollTarget(control);
        scrollTarget?.classList.add('field-validation-focus');
        scrollTarget?.scrollIntoView({ behavior: 'smooth', block: 'center' });

        window.setTimeout(() => {
            try {
                if (control.type === 'file') {
                    control.closest('.spf-photo-upload, .profile-photo-input')?.focus({ preventScroll: true });
                } else {
                    control.focus({ preventScroll: true });
                }
            } catch (_) {}
            control.reportValidity();
        }, 280);

        window.setTimeout(() => {
            scrollTarget?.classList.remove('field-validation-focus');
        }, 2600);

        return control;
    }

    if (!form.checkValidity()) {
        form.reportValidity();
    }

    return null;
}

function initForms() {
    const getAssociatedControls = form => {
        const selector = form.id
            ? `input[form="${form.id}"], select[form="${form.id}"], textarea[form="${form.id}"]`
            : '';
        const localControls = [...form.querySelectorAll('input,select,textarea')];
        const externalControls = selector ? [...document.querySelectorAll(selector)] : [];

        return sortControlsByDocumentOrder([...new Set([...localControls, ...externalControls])]);
    };

    const getAssociatedSubmitButtons = form => {
        const selector = form.id
            ? `button[type="submit"][form="${form.id}"], input[type="submit"][form="${form.id}"]`
            : '';
        const localButtons = [...form.querySelectorAll('button[type="submit"],input[type="submit"]')];
        const externalButtons = selector ? [...document.querySelectorAll(selector)] : [];

        return [...new Set([...localButtons, ...externalButtons])];
    };

    const validateRequiredCheckboxGroup = form => {
        const groupName = form.dataset.requireCheckboxGroup;
        if (!groupName) return true;

        const selector = form.id
            ? `input[type="checkbox"][name="${groupName}"][form="${form.id}"]`
            : `input[type="checkbox"][name="${groupName}"]`;
        const checkboxes = [...document.querySelectorAll(selector)].filter(checkbox => !checkbox.disabled);
        if (!checkboxes.length) return true;

        const hasSelection = checkboxes.some(checkbox => checkbox.checked);
        const message = hasSelection ? '' : (form.dataset.requireCheckboxMessage || 'Please select at least one option.');

        checkboxes.forEach(checkbox => checkbox.setCustomValidity(''));
        checkboxes[0].setCustomValidity(message);

        return hasSelection;
    };

    document.querySelectorAll('.js-validate').forEach(form => {
        const controls = getAssociatedControls(form);
        const submitButtons = getAssociatedSubmitButtons(form);
        const markTouched = () => controls.forEach(el => el.classList.add('touched'));

        controls.forEach(el => {
            const eventName = ['checkbox', 'radio'].includes(el.type) ? 'change' : 'blur';
            el.addEventListener(eventName, () => {
                el.classList.add('touched');
                validateRequiredCheckboxGroup(form);
            });
        });

        form.addEventListener('submit', e => {
            const hasValidCheckboxGroup = validateRequiredCheckboxGroup(form);
            const reqDatePickers = [...form.querySelectorAll('.filter-date-picker[data-date-required]')];
            const missingDates = reqDatePickers.filter(p => !p.querySelector('input[type="hidden"]')?.value);
            missingDates.forEach(p => p.classList.add('date-required-error'));
            if (!hasValidCheckboxGroup || !form.checkValidity() || missingDates.length) {
                e.preventDefault();
                markTouched();

                if (missingDates.length) {
                    missingDates[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    missingDates[0].classList.add('field-validation-focus');
                    window.setTimeout(() => missingDates[0].classList.remove('field-validation-focus'), 2600);
                    return;
                }

                if (!hasValidCheckboxGroup) {
                    const groupName = form.dataset.requireCheckboxGroup;
                    const selector = form.id
                        ? `input[type="checkbox"][name="${groupName}"][form="${form.id}"]`
                        : `input[type="checkbox"][name="${groupName}"]`;
                    const firstCheckbox = document.querySelector(selector);
                    const scrollTarget = firstCheckbox?.closest('label, .spf-field, fieldset') || firstCheckbox;
                    scrollTarget?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    scrollTarget?.classList.add('field-validation-focus');
                    firstCheckbox?.focus({ preventScroll: true });
                    window.setTimeout(() => scrollTarget?.classList.remove('field-validation-focus'), 2600);
                    form.reportValidity();
                    return;
                }

                scrollToFirstInvalidControl(form, controls);
                return;
            }

            if (form.dataset.confirmSubmit && form.dataset.confirmedSubmit !== '1') {
                e.preventDefault();
                const btn = e.submitter || submitButtons[0] || null;
                const isAsyncConfirm = form.dataset.confirmAsync === '1';
                showConfirmModal(form.dataset.confirmSubmit, {
                    title: form.dataset.confirmTitle || 'Confirm submission',
                    confirmText: form.dataset.confirmOk || 'Submit',
                    cancelText: form.dataset.confirmCancel || 'Review again',
                    persistOnConfirm: isAsyncConfirm,
                    preLine: form.dataset.confirmPreline === '1',
                    variant: form.dataset.confirmVariant || 'confirm',
                    messageHtml: form._confirmMessageHtml || '',
                    iconSvg: form._confirmIconSvg || '',
                }).then(result => {
                    const confirmed = result === true || result?.confirmed === true;
                    if (!confirmed) return;
                    if (isAsyncConfirm && result?.overlay) {
                        submitFormWithAsyncConfirm(form, result.overlay);
                        return;
                    }
                    form.dataset.confirmedSubmit = '1';
                    if (btn && typeof form.requestSubmit === 'function') {
                        form.requestSubmit(btn);
                    } else if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
                return;
            }
            delete form.dataset.confirmedSubmit;

            if (form.dataset.submitAsync === '1') {
                e.preventDefault();
                const btn = e.submitter || submitButtons[0] || null;
                if (btn) {
                    btn.classList.add('loading');
                    btn.disabled = true;
                }
                submitFormWithStatusModals(form);
                return;
            }

            const btn = e.submitter || submitButtons[0] || null;
            if (btn) { btn.classList.add('loading'); btn.disabled = true; }
        });
    });
}

function buildAppRouteUrl(route, params = {}) {
    const base = (document.body?.dataset?.appBase || '').replace(/\/$/, '');
    const pathname = `${base}/index.php`.replace(/\/{2,}/g, '/');
    const url = new URL(pathname, window.location.origin);
    url.searchParams.set('r', route);
    Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            url.searchParams.set(key, value);
        }
    });
    return url;
}

function buildRegisterCheckUrl(action, paramName, value) {
    const base = (document.body?.dataset?.appBase || '').replace(/\/$/, '');
    const pathname = `${base}/register.php`.replace(/\/{2,}/g, '/');
    const url = new URL(pathname, window.location.origin);
    url.searchParams.set('action', action);
    url.searchParams.set(paramName, value);
    return url;
}

function initLiveFieldAvailability({
    input,
    route,
    paramName,
    messageSelector,
    sanitize = value => value.trim(),
    canCheck = () => true,
    takenMessage,
    availableMessage,
    endpointBuilder = null,
}) {
    if (!input) return;

    const messageEl = input.closest('label')?.querySelector(messageSelector);
    let debounceTimer = null;
    let requestId = 0;

    const setMessage = (text, state) => {
        if (!messageEl) return;
        messageEl.textContent = text;
        messageEl.hidden = !text;
        messageEl.classList.remove('is-checking', 'is-error', 'is-success');
        if (state) messageEl.classList.add(`is-${state}`);
    };

    const checkAvailability = async value => {
        const currentRequest = ++requestId;

        if (!value || !canCheck(value)) {
            input.setCustomValidity('');
            setMessage('', '');
            return;
        }

        setMessage('Checking availability...', 'checking');

        try {
            const url = endpointBuilder
                ? endpointBuilder(paramName, value)
                : buildAppRouteUrl(route, { [paramName]: value });

            const response = await fetch(url.toString(), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });
            const responseText = await response.text();

            let data;
            try {
                data = JSON.parse(responseText);
            } catch {
                if (currentRequest !== requestId) return;
                setMessage('Unable to verify right now. Refresh and try again.', 'error');
                return;
            }

            if (currentRequest !== requestId) return;

            if (!response.ok || !data.ok) {
                setMessage('Unable to verify right now. Refresh and try again.', 'error');
                return;
            }

            if (data.exists) {
                input.setCustomValidity(takenMessage);
                input.classList.add('touched');
                setMessage(takenMessage, 'error');
            } else {
                input.setCustomValidity('');
                setMessage(availableMessage, 'success');
            }
        } catch {
            if (currentRequest !== requestId) return;
            setMessage('Unable to verify right now. Refresh and try again.', 'error');
        }
    };

    const queueCheck = () => {
        input.setCustomValidity('');
        clearTimeout(debounceTimer);

        const value = sanitize(input.value);
        if (input.value !== value) input.value = value;

        if (!value) {
            setMessage('', '');
            return;
        }

        debounceTimer = setTimeout(() => checkAvailability(value), 400);
    };

    input.addEventListener('input', queueCheck);

    input.addEventListener('blur', () => {
        clearTimeout(debounceTimer);
        const value = sanitize(input.value);
        if (input.value !== value) input.value = value;
        if (value) checkAvailability(value);
    });
}

function initAdminCreateStudentAvailability() {
    const form = document.getElementById('asuCreateStudentForm');
    if (!form) return;

    initLiveFieldAvailability({
        input: form.querySelector('input[name="student_no"][data-admin-student-no-check]'),
        route: 'admin_check_student_no',
        paramName: 'student_no',
        messageSelector: '[data-admin-student-no-message]',
        sanitize: value => value.replace(/\D/g, ''),
        canCheck: value => /^\d+$/.test(value),
        takenMessage: 'This Student ID/USN is already registered.',
        availableMessage: 'Student ID/USN is available.',
    });

    initLiveFieldAvailability({
        input: form.querySelector('input[name="email"][data-admin-student-email-check]'),
        route: 'admin_check_student_email',
        paramName: 'email',
        messageSelector: '[data-admin-student-email-message]',
        sanitize: value => value.trim().toLowerCase(),
        canCheck: value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
        takenMessage: 'This email address is already registered.',
        availableMessage: 'Email address is available.',
    });
}

function initAdminCreateStudentModal() {
    const overlay = document.getElementById('asuCreateStudentOverlay');
    const openBtn = document.querySelector('[data-asu-create-student]');
    const closeBtn = document.getElementById('asuCreateStudentClose');
    const cancelBtn = document.getElementById('asuCreateStudentCancel');
    const form = document.getElementById('asuCreateStudentForm');
    if (!overlay || !openBtn || !closeBtn || !cancelBtn || !form) return;
    if (overlay.dataset.ready === '1') return;
    overlay.dataset.ready = '1';

    const MODAL_ANIM_MS = 300;
    let closeTimer = null;

    const dropzone = form.querySelector('[data-asu-cor-dropzone]');
    const fileInput = form.querySelector('#asuCorFileInput');
    const browseBtn = form.querySelector('[data-asu-cor-browse]');
    const fileName = form.querySelector('[data-asu-cor-filename]');
    const clearBtn = form.querySelector('[data-asu-cor-clear]');

    const syncFileLabel = () => {
        const file = fileInput?.files?.[0];
        if (fileName) fileName.textContent = file ? file.name : 'No file chosen';
        if (clearBtn) clearBtn.hidden = !file;
    };

    browseBtn?.addEventListener('click', () => fileInput?.click());
    clearBtn?.addEventListener('click', () => {
        if (!fileInput) return;
        fileInput.value = '';
        syncFileLabel();
    });
    fileInput?.addEventListener('change', syncFileLabel);
    dropzone?.addEventListener('dragover', e => {
        e.preventDefault();
        dropzone.classList.add('is-dragover');
    });
    dropzone?.addEventListener('dragleave', () => dropzone.classList.remove('is-dragover'));
    dropzone?.addEventListener('drop', e => {
        e.preventDefault();
        dropzone.classList.remove('is-dragover');
        if (!fileInput || !e.dataTransfer?.files?.length) return;
        fileInput.files = e.dataTransfer.files;
        syncFileLabel();
    });

    const finishClose = () => {
        overlay.classList.remove('is-closing');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-asu-create-open');
        closeTimer = null;
    };

    const closeModal = () => {
        if (!overlay.classList.contains('open') || overlay.classList.contains('is-closing')) return;
        if (closeTimer) clearTimeout(closeTimer);
        overlay.classList.add('is-closing');
        overlay.classList.remove('open');
        closeTimer = window.setTimeout(finishClose, MODAL_ANIM_MS);
    };

    const openModal = () => {
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }
        overlay.classList.remove('is-closing');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-asu-create-open');
        requestAnimationFrame(() => {
            requestAnimationFrame(() => overlay.classList.add('open'));
        });
    };

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeModal();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
    });

    initAdminCreateStudentAvailability();
    initCustomFilterSelects();
    initCustomDatePickers();
}

function initAdminTermsPage() {
    const page = document.querySelector('[data-admin-terms-page]');
    if (!page || page.dataset.termsReady === '1') return;
    page.dataset.termsReady = '1';

    const directory = page.querySelector('[data-admin-terms-directory]');
    const table = directory?.querySelector('.atm-terms-table');
    const statusFilter = directory?.querySelector('[data-atm-status-filter]');
    const search = directory?.querySelector('.table-search');
    let statusValue = 'all';

    const applyFilters = () => {
        if (!table) return;
        table._applyRowFilter = statusValue === 'all'
            ? null
            : row => row.dataset.termStatus === statusValue;
        search?.dispatchEvent(new Event('input'));
    };

    if (table) {
        table._resetDirectoryFilters = () => {
            statusValue = 'all';
            if (statusFilter) {
                statusFilter.value = 'all';
                statusFilter._syncCustomSelect?.();
            }
            table._applyRowFilter = null;
        };
        statusFilter?.addEventListener('change', () => {
            statusValue = statusFilter.value || 'all';
            applyFilters();
        });
        requestAnimationFrame(applyFilters);
    }

    page.addEventListener('submit', async event => {
        const deleteForm = event.target.closest('[data-atm-delete]');
        if (!deleteForm || !page.contains(deleteForm)) return;

        event.preventDefault();
        const termLabel = deleteForm.dataset.termLabel?.trim() || 'this term';
        const confirmed = await showConfirmModal(
            `Delete "${termLabel}"? Coordinators will no longer see it when enrolling students.`,
            {
                title: 'Delete academic term',
                confirmText: 'Delete term',
                cancelText: 'Keep term',
                variant: 'alert',
                iconSvg: '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>',
            }
        );
        if (confirmed) deleteForm.submit();
    });

    const overlay = document.getElementById('atmTermOverlay');
    const form = document.getElementById('atmTermForm');
    const closeBtn = document.getElementById('atmTermModalClose');
    const cancelBtn = document.getElementById('atmTermModalCancel');
    const titleEl = document.getElementById('atmTermModalTitle');
    const subEl = overlay?.querySelector('[data-atm-modal-sub]');
    const submitLabel = overlay?.querySelector('[data-atm-submit-label]');
    const termIdInput = document.getElementById('atmTermId');
    const termLabelInput = document.getElementById('atmTermLabel');
    const startPicker = document.getElementById('atmStartPicker');
    const endPicker = document.getElementById('atmEndPicker');
    if (!overlay || !form || !closeBtn || !cancelBtn || !termIdInput || !termLabelInput) return;

    const MODAL_ANIM_MS = 300;
    let closeTimer = null;

    const finishClose = () => {
        overlay.classList.remove('is-closing');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-asu-create-open');
        closeTimer = null;
    };

    const closeModal = () => {
        if (!overlay.classList.contains('open') || overlay.classList.contains('is-closing')) return;
        if (closeTimer) clearTimeout(closeTimer);
        overlay.classList.add('is-closing');
        overlay.classList.remove('open');
        closeTimer = window.setTimeout(finishClose, MODAL_ANIM_MS);
    };

    const statusSelect = document.getElementById('atmTermStatus');

    const resetForm = () => {
        form.reset();
        termIdInput.value = '';
        termLabelInput.value = '';
        setFormDatePickerValue(startPicker, '');
        setFormDatePickerValue(endPicker, '');
        if (statusSelect) statusSelect.value = '1';
        form.querySelectorAll('.is-invalid, .date-required-error').forEach(el => {
            el.classList.remove('is-invalid', 'date-required-error');
        });
    };

    const setModalMode = (mode) => {
        const isEdit = mode === 'edit';
        if (titleEl) titleEl.textContent = isEdit ? 'Edit Academic Term' : 'Add Academic Term';
        if (subEl) {
            subEl.textContent = isEdit
                ? 'Update the term label and date range used for enrollment.'
                : 'Create a term and date range for coordinator enrollment.';
        }
        if (submitLabel) submitLabel.textContent = isEdit ? 'Update Term' : 'Save Term';
    };

    const openModal = (mode = 'add', data = null) => {
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }
        resetForm();
        setModalMode(mode);
        if (mode === 'edit' && data) {
            termIdInput.value = data.id || '';
            termLabelInput.value = data.label || '';
            setFormDatePickerValue(startPicker, data.start || '');
            setFormDatePickerValue(endPicker, data.end || '');
            if (statusSelect) statusSelect.value = data.active ?? '1';
        }
        overlay.classList.remove('is-closing');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-asu-create-open');
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                overlay.classList.add('open');
                termLabelInput.focus();
            });
        });
    };

    page.querySelectorAll('[data-atm-open-modal]').forEach(btn => {
        btn.addEventListener('click', () => openModal('add'));
    });

    page.addEventListener('click', e => {
        const editBtn = e.target.closest('[data-atm-edit]');
        if (!editBtn || !page.contains(editBtn)) return;
        openModal('edit', {
            id: editBtn.dataset.termId || '',
            label: editBtn.dataset.termLabel || '',
            start: editBtn.dataset.termStart || '',
            end: editBtn.dataset.termEnd || '',
            active: editBtn.dataset.termActive ?? '1',
        });
    });

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeModal();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
    });

    initCustomFilterSelects();
    initCustomDatePickers();
}

function initStudentRegistrationPasswordIndicators() {
    const form = document.getElementById('studentRegisterForm');
    if (!form) return;

    const passwordInput = form.querySelector('[data-register-password]');
    const confirmInput = form.querySelector('[data-register-confirm-password]');
    const strengthIndicator = form.querySelector('[data-register-password-strength]');
    const strengthLabel = form.querySelector('[data-register-strength-label]');
    const matchIndicator = form.querySelector('[data-register-password-match]');

    const updatePasswordStrength = () => {
        const password = passwordInput?.value || '';
        const { level, label } = getPasswordStrength(password);
        if (!password) {
            strengthIndicator?.setAttribute('hidden', '');
            strengthIndicator?.removeAttribute('data-level');
            if (strengthLabel) strengthLabel.textContent = '';
            return;
        }
        strengthIndicator?.removeAttribute('hidden');
        strengthIndicator?.setAttribute('data-level', String(level));
        if (strengthLabel) strengthLabel.textContent = label;
    };

    const updatePasswordMatch = () => {
        const password = passwordInput?.value || '';
        const confirmPassword = confirmInput?.value || '';
        if (!confirmPassword) {
            confirmInput?.setCustomValidity('');
            matchIndicator?.setAttribute('hidden', '');
            matchIndicator?.classList.remove('is-match', 'is-mismatch');
            if (matchIndicator) matchIndicator.textContent = '';
            return;
        }
        matchIndicator?.removeAttribute('hidden');
        const matches = password === confirmPassword;
        matchIndicator?.classList.toggle('is-match', matches);
        matchIndicator?.classList.toggle('is-mismatch', !matches);
        if (matchIndicator) {
            matchIndicator.textContent = matches ? 'Passwords match' : 'Passwords do not match';
        }
        confirmInput?.setCustomValidity(matches ? '' : 'Passwords do not match.');
    };

    passwordInput?.addEventListener('input', () => {
        updatePasswordStrength();
        updatePasswordMatch();
    });
    confirmInput?.addEventListener('input', updatePasswordMatch);
}

function closeRegistrationAndReturnToLogin(loginUrl) {
    try {
        if (window.opener && !window.opener.closed) {
            window.opener.focus();
        }
    } catch {
        // Cross-origin opener ? ignore.
    }

    window.close();

    window.setTimeout(() => {
        if (!window.closed) {
            window.location.href = loginUrl;
        }
    }, 120);
}

function initRegistrationBackLink() {
    document.querySelectorAll('[data-register-close-login]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const loginUrl = link.dataset.loginUrl || link.getAttribute('href') || '/';
            closeRegistrationAndReturnToLogin(loginUrl);
        });
    });
}

function initRegistrationSuccessCountdown() {
    const panel = document.querySelector('[data-register-success]');
    if (!panel) return;

    const redirectUrl = panel.dataset.redirectUrl || '/';
    const countdownEl = panel.querySelector('[data-register-countdown-value]');
    const configuredSeconds = parseInt(panel.dataset.countdownSeconds ?? '10', 10);
    if (!Number.isFinite(configuredSeconds) || configuredSeconds <= 0) {
        return;
    }

    let remaining = Math.max(3, configuredSeconds);

    if (countdownEl) countdownEl.textContent = String(remaining);

    const finish = () => {
        closeRegistrationAndReturnToLogin(redirectUrl);
    };

    const timer = window.setInterval(() => {
        remaining -= 1;
        if (countdownEl) countdownEl.textContent = String(Math.max(remaining, 0));
        if (remaining <= 0) {
            window.clearInterval(timer);
            finish();
        }
    }, 1000);
}

function initRegisterCourseSelect() {
    document.querySelectorAll('#studentRegisterForm select[name="program_id"], #studentRegisterForm select[name="year_level"]').forEach(select => {
        initRegisterPortalSelect(select);
    });
}

function initRegisterPortalSelect(select) {
    if (!select || select.dataset.enhanced !== '1' || select.dataset.registerPortalReady === '1') return;

    const wrap = select.closest('.register-input-wrap--select');
    const custom = wrap?.querySelector('.register-course-custom-select');
    const trigger = custom?.querySelector('.custom-select-trigger');
    const menu = custom?.querySelector('.custom-select-menu');
    if (!wrap || !custom || !trigger || !menu) return;

    select.dataset.registerPortalReady = '1';
    const isProgramSelect = select.name === 'program_id';

    wrap.classList.add('register-input-wrap--enhanced');
    menu.classList.add('register-course-custom-select-menu');
    document.body.appendChild(menu);
    menu.hidden = true;

    const syncInvalid = () => {
        custom.classList.toggle('is-invalid', select.classList.contains('touched') && !select.checkValidity());
    };

    const upgradeOptions = () => {
        menu.querySelectorAll('.custom-select-option').forEach(item => {
            const label = item.querySelector('.custom-select-option-label');
            if (!label || label.dataset.registerUpgraded === '1') return;
            const text = label.textContent.trim();
            if (item.dataset.value === '') {
                label.dataset.registerUpgraded = '1';
                label.innerHTML = `<span class="register-course-option-placeholder">${escapeHtml(text)}</span>`;
                return;
            }
            if (!isProgramSelect) {
                label.dataset.registerUpgraded = '1';
                return;
            }
            const match = text.match(/^(.+?)\s*[?-]\s*(.+)$/);
            if (!match) return;
            label.dataset.registerUpgraded = '1';
            label.innerHTML = `<span class="register-course-option-code">${escapeHtml(match[1].trim())}</span><span class="register-course-option-name">${escapeHtml(match[2].trim())}</span>`;
        });
    };

    const syncMenu = () => {
        upgradeOptions();
        const open = custom.classList.contains('is-open');
        menu.classList.toggle('is-open', open);
        if (!open) {
            menu.hidden = true;
            return;
        }

        menu.hidden = false;
        const rect = trigger.getBoundingClientRect();
        const maxMenuHeight = isProgramSelect ? 280 : 160;
        const spaceBelow = window.innerHeight - rect.bottom - 12;
        const spaceAbove = rect.top - 12;
        const openUp = spaceBelow < 120 && spaceAbove > spaceBelow;

        menu.style.position = 'fixed';
        menu.style.left = `${Math.round(rect.left)}px`;
        menu.style.width = `${Math.round(rect.width)}px`;
        menu.style.zIndex = '10050';
        menu.style.maxHeight = `${maxMenuHeight}px`;

        if (openUp) {
            menu.style.top = 'auto';
            menu.style.bottom = `${Math.round(window.innerHeight - rect.top + 6)}px`;
        } else {
            menu.style.bottom = 'auto';
            menu.style.top = `${Math.round(rect.bottom + 6)}px`;
        }
    };

    const observer = new MutationObserver(() => requestAnimationFrame(syncMenu));
    observer.observe(custom, { attributes: true, attributeFilter: ['class'] });

    trigger.addEventListener('click', () => requestAnimationFrame(syncMenu));
    window.addEventListener('resize', syncMenu);
    window.addEventListener('scroll', syncMenu, true);
    trigger.addEventListener('blur', () => {
        select.classList.add('touched');
        syncInvalid();
    });
    select.addEventListener('change', () => {
        syncInvalid();
        requestAnimationFrame(upgradeOptions);
    });

    const originalSync = select._syncCustomSelect;
    if (typeof originalSync === 'function') {
        select._syncCustomSelect = () => {
            originalSync();
            requestAnimationFrame(upgradeOptions);
        };
    }

    select._syncCustomSelect?.();
    syncMenu();
    syncInvalid();
}

function initStudentRegistrationAvailability() {
    const form = document.getElementById('studentRegisterForm');
    if (!form) return;

    initLiveFieldAvailability({
        input: form.querySelector('input[name="student_no"][data-student-no-check]'),
        paramName: 'student_no',
        messageSelector: '[data-student-no-message]',
        sanitize: value => value.replace(/\D/g, ''),
        canCheck: value => /^\d+$/.test(value),
        takenMessage: 'This Student ID/USN is already registered.',
        availableMessage: 'Student ID/USN is available.',
        endpointBuilder: (_paramName, value) => buildRegisterCheckUrl('check_student_no', 'student_no', value),
    });

    initLiveFieldAvailability({
        input: form.querySelector('input[name="email"][data-email-check]'),
        paramName: 'email',
        messageSelector: '[data-email-message]',
        sanitize: value => value.trim().toLowerCase(),
        canCheck: value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
        takenMessage: 'This email address is already registered.',
        availableMessage: 'Email address is available.',
        endpointBuilder: (_paramName, value) => buildRegisterCheckUrl('check_email', 'email', value),
    });
}

function initCoordinatorAvailability() {
    const createForm = document.querySelector('.coordinator-create-form, .aco-create-form');
    if (!createForm) return;

    const idInput = createForm.querySelector('input[name="id_number"][data-coordinator-id-check]');
    const emailInput = createForm.querySelector('input[name="email"][data-coordinator-email-check]');

    initLiveFieldAvailability({
        input: idInput,
        route: 'admin_check_coordinator_id',
        paramName: 'id_number',
        messageSelector: '[data-coordinator-id-message]',
        sanitize: value => value.replace(/\D/g, ''),
        canCheck: value => /^\d+$/.test(value),
        takenMessage: 'This ID number is already registered.',
        availableMessage: 'ID number is available.',
    });

    initLiveFieldAvailability({
        input: emailInput,
        route: 'admin_check_coordinator_email',
        paramName: 'email',
        messageSelector: '[data-coordinator-email-message]',
        sanitize: value => value.trim().toLowerCase(),
        canCheck: value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
        takenMessage: 'This email address is already registered.',
        availableMessage: 'Email address is available.',
    });
}

function initPartnerAvailability() {
    const createForm = document.getElementById('create-partner-form');
    if (!createForm) return;

    initLiveFieldAvailability({
        input: createForm.querySelector('input[name="contact_email"][data-partner-email-check]')
            || document.querySelector('input[name="contact_email"][data-partner-email-check]'),
        route: 'admin_check_partner_email',
        paramName: 'email',
        messageSelector: '[data-partner-email-message]',
        sanitize: value => value.trim().toLowerCase(),
        canCheck: value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
        takenMessage: 'This email address is already registered.',
        availableMessage: 'Email address is available.',
    });
}

function initCounters() {
    document.querySelectorAll('.metric strong').forEach(el => {
        const raw = el.textContent.replace(/,/g, '').trim();
        if (!/^\d+(\.\d+)?$/.test(raw)) return;
        const target = Number(raw);
        const duration = 900;
        const start = performance.now();
        const decimals = raw.includes('.') ? 2 : 0;
        const tick = now => {
            const pct = Math.min(1, (now - start) / duration);
            const value = target * (1 - Math.pow(1 - pct, 3));
            el.textContent = value.toLocaleString(undefined, { maximumFractionDigits: decimals, minimumFractionDigits: decimals });
            if (pct < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    });
}

function initWizards() {
    document.querySelectorAll('[data-wizard]').forEach(form => {
        if (form.dataset.wizardReady === '1') return;
        form.dataset.wizardReady = '1';

        let index = 0;
        const panels = [...form.querySelectorAll('.wizard-step')];
        const stepIndicators = [...form.querySelectorAll('[data-wizard-step-indicator]')];
        const connectors = [...form.querySelectorAll('.enrollment-wizard-connector')];
        const legacySteps = [...form.querySelectorAll('.wizard-steps > span:not([data-wizard-step-indicator])')];
        const show = next => {
            index = Math.max(0, Math.min(next, panels.length - 1));
            panels.forEach((p, i) => p.classList.toggle('active', i === index));
            stepIndicators.forEach((s, i) => {
                s.classList.toggle('is-done', i < index);
                s.classList.toggle('is-current', i === index);
            });
            connectors.forEach((c, i) => c.classList.toggle('is-done', i < index));
            legacySteps.forEach((s, i) => s.classList.toggle('active', i <= index));
            updateWizardSummary(form);
        };
        form._wizardGoTo = show;
        form._wizardReset = () => {
            form.querySelectorAll('.touched').forEach(field => field.classList.remove('touched'));
            form.querySelectorAll('.date-required-error').forEach(picker => picker.classList.remove('date-required-error'));
            show(0);
        };
        form.querySelectorAll('.wizard-next').forEach(btn => btn.addEventListener('click', () => {
            const studentSelect = form.querySelector('[name="student_id"]');
            const selectedStudent = studentSelect?.selectedOptions?.[0];
            if (selectedStudent?.dataset.isEnrolled === '1') {
                showAlertModal('This student is already enrolled. Please try again.', {
                    title: 'Student already enrolled',
                    confirmText: 'OK'
                });
                studentSelect.value = '';
                studentSelect._syncCustomSelect?.();
                updateWizardSummary(form);
                return;
            }
            const fields = [...panels[index].querySelectorAll('input,select,textarea')];
            const reqDatePickers = [...panels[index].querySelectorAll('.filter-date-picker[data-date-required]')];
            const missingDates = reqDatePickers.filter(p => !p.querySelector('input[type="hidden"]')?.value);
            if (fields.some(field => !field.checkValidity()) || missingDates.length) {
                fields.forEach(field => field.classList.add('touched'));
                missingDates.forEach(p => p.classList.add('date-required-error'));
                return;
            }
            reqDatePickers.forEach(p => p.classList.remove('date-required-error'));
            show(index + 1);
        }));
        form.querySelectorAll('.wizard-prev').forEach(btn => btn.addEventListener('click', () => show(index - 1)));
        show(0);
    });
}

function formatWizardDateRange(start, end) {
    const formatDate = (value) => {
        if (!value || value === '-') return null;
        const parsed = new Date(`${value}T00:00:00`);
        if (Number.isNaN(parsed.getTime())) return value;
        return parsed.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    };
    const startLabel = formatDate(start);
    const endLabel = formatDate(end);
    if (!startLabel && !endLabel) return '—';
    if (!startLabel) return escapeHtml(endLabel);
    if (!endLabel) return escapeHtml(startLabel);
    return `<span class="confirm-schedule-range"><span>${escapeHtml(startLabel)}</span><span class="confirm-schedule-sep" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span>${escapeHtml(endLabel)}</span></span>`;
}

function updateWizardSummary(form) {
    const box = form.querySelector('.confirm-box');
    if (!box) return;
    const student = form.querySelector('[name="student_id"]')?.selectedOptions[0]?.textContent || '—';
    const company = form.querySelector('[name="company_id"]')?.selectedOptions[0]?.textContent || '—';
    const term = form.querySelector('[name="academic_term"]')?.selectedOptions[0]?.textContent || '—';
    const start = form.querySelector('[name="term_start_date"]')?.value || '';
    const end = form.querySelector('[name="term_end_date"]')?.value || '';
    const hours = form.querySelector('[name="required_hours"]')?.value || '—';
    const schedule = formatWizardDateRange(start, end);
    box.innerHTML = `
        <div class="enr-confirm-head">
            <span class="enr-confirm-head-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <div>
                <h3>Review & confirm</h3>
                <p>Double-check the placement details before enrolling the student.</p>
            </div>
        </div>
        <div class="enr-confirm-grid">
            <div class="enr-confirm-item">
                <span class="enr-confirm-item-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" fill="none" stroke="currentColor" stroke-width="2"/></svg></span>
                <div><span class="enr-confirm-item-label">Student</span><span class="enr-confirm-item-value">${escapeHtml(student)}</span></div>
            </div>
            <div class="enr-confirm-item">
                <span class="enr-confirm-item-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l7-4 7 4v14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 10h6M9 14h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                <div><span class="enr-confirm-item-label">Host</span><span class="enr-confirm-item-value">${escapeHtml(company)}</span></div>
            </div>
            <div class="enr-confirm-item">
                <span class="enr-confirm-item-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" fill="none" stroke="currentColor" stroke-width="2"/></svg></span>
                <div><span class="enr-confirm-item-label">Term</span><span class="enr-confirm-item-value">${escapeHtml(term)}</span></div>
            </div>
            <div class="enr-confirm-item">
                <span class="enr-confirm-item-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 2v4M8 2v4M3 10h18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                <div><span class="enr-confirm-item-label">Schedule</span><span class="enr-confirm-item-value enr-confirm-item-value--schedule">${schedule}</span></div>
            </div>
            <div class="enr-confirm-item enr-confirm-item--wide">
                <span class="enr-confirm-item-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                <div><span class="enr-confirm-item-label">Required Hours</span><span class="enr-confirm-item-value">${escapeHtml(hours)} hrs</span></div>
            </div>
        </div>
        <div class="enr-confirm-alert">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4M12 17h.01" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>This will enroll the student and email their enrollment details.</span>
        </div>`;
}

function initEnrollmentAutomation() {
    document.querySelectorAll('form [name="student_id"]').forEach(studentSelect => {
        const form = studentSelect.closest('form');
        if (!form || form.dataset.enrollAutoReady === '1') return;

        const companySelect = form.querySelector('[name="company_id"]');
        const hoursInput = form.querySelector('[name="required_hours"]');
        const companyDocPreview = form.querySelector('[data-company-doc-preview]');
        const companyDocLink = form.querySelector('[data-company-doc-link]');
        if (!companySelect || !hoursInput) return;
        form.dataset.enrollAutoReady = '1';
        const clearSelection = () => {
            studentSelect.value = '';
            hoursInput.value = '';
            studentSelect._syncCustomSelect?.();
        };
        const resetCompanies = () => {
            [...companySelect.options].forEach(option => {
                option.hidden = false;
                option.disabled = false;
            });
            companySelect.value = '';
            companySelect._syncCustomSelect?.();
        };
        const filterCompaniesForProgram = (programId) => {
            const selectedProgramId = String(programId || '');
            [...companySelect.options].forEach(option => {
                if (!option.value) {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }
                const acceptedIds = String(option.dataset.programIds || '')
                    .split(',')
                    .map(value => value.trim())
                    .filter(Boolean);
                const matches = selectedProgramId !== '' && acceptedIds.includes(selectedProgramId);
                option.hidden = !matches;
                option.disabled = !matches;
            });
            const current = companySelect.selectedOptions[0];
            if (current && (current.hidden || current.disabled)) {
                companySelect.value = '';
            }
            companySelect._syncCustomSelect?.();
        };
        const sync = () => {
            const selected = studentSelect.selectedOptions[0];
            if (selected?.dataset.isEnrolled === '1') {
                clearSelection();
                resetCompanies();
                syncCompanyDocument();
                updateWizardSummary(form);
                showAlertModal('This student is already enrolled. Please try again.', {
                    title: 'Student already enrolled',
                    confirmText: 'OK'
                });
                return;
            }
            hoursInput.value = selected?.dataset.requiredHours || '';
            filterCompaniesForProgram(selected?.dataset.programId || '');
            updateWizardSummary(form);
        };
        form._resetEnrollmentFields = (studentId = '') => {
            const termSelect = form.querySelector('[name="academic_term"]');
            if (studentId) {
                studentSelect.value = String(studentId);
            } else {
                clearSelection();
            }
            studentSelect._syncCustomSelect?.();
            if (studentId) {
                filterCompaniesForProgram(studentSelect.selectedOptions[0]?.dataset.programId || '');
            } else {
                resetCompanies();
            }
            if (termSelect) {
                termSelect.value = '';
                termSelect._syncCustomSelect?.();
            }
            syncAcademicTermDates(form);
            if (studentId) {
                hoursInput.value = studentSelect.selectedOptions[0]?.dataset.requiredHours || '';
            }
            syncCompanyDocument();
            updateWizardSummary(form);
        };
        const syncCompanyDocument = () => {
            if (!companyDocPreview || !companyDocLink) return;
            const selectedCompany = companySelect.selectedOptions[0];
            const docUrl = selectedCompany?.dataset.moaMou || '';
            if (docUrl) {
                companyDocLink.href = docUrl;
                companyDocPreview.hidden = false;
            } else {
                companyDocLink.href = '#';
                companyDocPreview.hidden = true;
            }
        };
        studentSelect.addEventListener('change', sync);
        companySelect.addEventListener('change', () => {
            updateWizardSummary(form);
            syncCompanyDocument();
        });
        const termSelect = form.querySelector('[name="academic_term"][data-term-autofill]');
        termSelect?.addEventListener('change', () => {
            syncAcademicTermDates(form);
            updateWizardSummary(form);
        });
        if (studentSelect.selectedOptions[0]?.dataset.isEnrolled === '1') {
            clearSelection();
            resetCompanies();
            syncCompanyDocument();
            updateWizardSummary(form);
            return;
        }
        sync();
        syncCompanyDocument();
        syncAcademicTermDates(form);
    });
}

function initViewToggles() {
    document.querySelectorAll('.view-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const wrapper = document.getElementById(btn.dataset.target);
            wrapper?.classList.toggle('cards-mode');
            btn.textContent = wrapper?.classList.contains('cards-mode') ? 'Table View' : 'Card View';
        });
    });
    document.querySelectorAll('.student-list-card .table-search').forEach(search => {
        search.addEventListener('input', () => {
            const q = search.value.toLowerCase();
            document.querySelectorAll('.student-card').forEach(card => card.style.display = card.dataset.search.includes(q) ? '' : 'none');
        });
    });
    document.querySelectorAll('.student-card').forEach(card => {
        card.addEventListener('click', () => {
            const title = card.querySelector('h3')?.textContent || 'Student';
            const body = card.querySelector('p')?.textContent || '';
            const status = card.querySelector('.badge')?.textContent || '';
            const company = card.querySelector('small')?.textContent || '';
            openSlidePanel(`<h2>${escapeHtml(title)}</h2><div class="detail-row"><span>Student</span><strong>${escapeHtml(body)}</strong></div><div class="detail-row"><span>Status</span><strong>${escapeHtml(status)}</strong></div><div class="detail-row"><span>Company</span><strong>${escapeHtml(company)}</strong></div>`);
        });
    });
}

function ensureTableSearchEmpty(wrap, onReset) {
    if (!wrap) return null;
    let el = wrap.querySelector('[data-table-search-empty]');
    if (el) return el;
    el = document.createElement('div');
    el.className = 'table-search-empty';
    el.setAttribute('data-table-search-empty', '');
    el.hidden = true;
    el.innerHTML = `
        <div class="table-search-empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
                <path d="m9 9 4 4M13 9l-4 4"></path>
            </svg>
        </div>
        <h3 class="table-search-empty-title">No result found</h3>
        <p class="table-search-empty-sub">We can't find any item matching your search.</p>
        <button class="btn btn-small table-search-empty-reset" type="button" data-table-reset-filters>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 2.64-6.36M3 4v5h5"/></svg>
            Reset filter
        </button>
    `;
    el.querySelector('[data-table-reset-filters]')?.addEventListener('click', onReset);
    wrap.appendChild(el);
    return el;
}

function resetTableFilters(table, search) {
    if (search) search.value = '';
    if (typeof table._resetDirectoryFilters === 'function') {
        table._resetDirectoryFilters();
    } else {
        table._applyRowFilter = null;
        table.closest('[data-enrollment-directory]')?.querySelector('[data-enrollment-filter="all"]')?.click();
    }
}

function enhanceTable(table) {
    if (table.hasAttribute('data-no-enhance')) return;
    const card = table.closest('.card');
    const directory = table.closest('[data-my-students-directory]') || table.closest('[data-enrollment-directory]');
    const search = card?.querySelector('.table-search') || directory?.querySelector('.table-search');
    const tbody = table.tBodies[0];
    if (!tbody) return;
    if (!table.hasAttribute('data-no-tools')) {
        addTableTools(table);
    }
    let rows = [...tbody.rows];
    let page = 1;
    let perPage = parseInt(table.dataset.perPage, 10) || 10;
    const wrap = table.closest('.table-wrap');
    const paginationInfo = directory?.querySelector('[data-pagination-info]') || card?.querySelector('[data-pagination-info]');
    const pager = directory?.querySelector('[data-pagination-nav]') || card?.querySelector('.pagination');
    const tableFooter = directory?.querySelector('.ms-table-footer');
    const emptyState = table.hasAttribute('data-no-search-empty')
        ? null
        : ensureTableSearchEmpty(wrap, () => {
            resetTableFilters(table, search);
            page = 1;
            render();
        });
    const filtered = () => {
        let base = rows;
        if (typeof table._applyRowFilter === 'function') {
            base = base.filter(table._applyRowFilter);
        }
        const q = (search?.value || '').toLowerCase().trim();
        return base.filter(r => {
            if (!q) return true;
            const haystack = (r.dataset.search || r.innerText).toLowerCase();
            return haystack.includes(q);
        });
    };
    const render = () => {
        const list = filtered();
        const showEmpty = list.length === 0 && rows.length > 0;
        tbody.innerHTML = '';
        if (!showEmpty) {
            list.slice((page - 1) * perPage, page * perPage).forEach(r => tbody.appendChild(r));
        }
        if (emptyState) {
            emptyState.hidden = !showEmpty;
        }
        if (wrap) {
            wrap.classList.toggle('is-search-empty', showEmpty);
        }
        card?.classList.toggle('is-table-search-empty', showEmpty);
        if (tableFooter) {
            tableFooter.hidden = showEmpty;
        }
        if (paginationInfo) {
            const total = list.length;
            const start = total === 0 ? 0 : (page - 1) * perPage + 1;
            const end = Math.min(page * perPage, total);
            paginationInfo.textContent = `Showing ${start} to ${end} of ${total} entries`;
        }
        if (pager) {
            pager.innerHTML = '';
            if (showEmpty) {
                attachRowDetails(table);
                applyHiddenColumns(table);
                return;
            }
            const pages = Math.max(1, Math.ceil(list.length / perPage));
            const useNav = table.hasAttribute('data-ms-students-table');
            if (useNav) {
                const prev = document.createElement('button');
                prev.type = 'button';
                prev.className = 'ms-page-btn ms-page-prev';
                prev.setAttribute('aria-label', 'Previous page');
                prev.textContent = '?';
                prev.disabled = page <= 1;
                prev.onclick = () => { if (page > 1) { page--; render(); } };
                pager.appendChild(prev);
            }
            for (let i = 1; i <= pages; i++) {
                const b = document.createElement('button');
                b.textContent = String(i);
                b.className = i === page ? 'active' : '';
                b.type = 'button';
                b.onclick = () => { page = i; render(); };
                pager.appendChild(b);
            }
            if (useNav) {
                const next = document.createElement('button');
                next.type = 'button';
                next.className = 'ms-page-btn ms-page-next';
                next.setAttribute('aria-label', 'Next page');
                next.textContent = '?';
                next.disabled = page >= pages;
                next.onclick = () => { if (page < pages) { page++; render(); } };
                pager.appendChild(next);
            }
        }
        attachRowDetails(table);
        applyHiddenColumns(table);
    };
    search?.addEventListener('input', () => { page = 1; render(); });
    table.querySelectorAll('th[data-sort]').forEach((th, i) => {
        let asc = true;
        th.addEventListener('click', () => {
            rows.sort((a, b) => asc ? a.cells[i].innerText.localeCompare(b.cells[i].innerText) : b.cells[i].innerText.localeCompare(a.cells[i].innerText));
            asc = !asc;
            render();
        });
    });
    table._getFilteredRows = filtered;
    table._rerender = render;
    table._setPerPage = (n) => {
        perPage = Math.max(1, parseInt(n, 10) || 10);
        table.dataset.perPage = String(perPage);
        page = 1;
        render();
    };
    render();
}

function addTableTools(table) {
    const wrap = table.closest('.table-wrap');
    if (!wrap || wrap.previousElementSibling?.classList.contains('table-tools')) return;
    const tools = document.createElement('div');
    tools.className = 'table-tools';
    const exportPdf = table.dataset.export === 'pdf';
    const exportBtn = exportPdf
        ? '<button class="btn btn-small export-pdf" type="button">Export PDF</button>'
        : '<button class="btn btn-small export-csv" type="button">Export CSV</button>';
    const columnMenu = table.hasAttribute('data-hide-column-toggle')
        ? ''
        : '<div class="column-menu"><button class="btn btn-small column-toggle" type="button">Columns</button><div class="column-options"></div></div>';
    tools.innerHTML = exportBtn + columnMenu;
    wrap.insertAdjacentElement('beforebegin', tools);
    if (exportPdf) {
        tools.querySelector('.export-pdf').addEventListener('click', () => {
            const url = table.dataset.exportUrl;
            if (url) window.open(url, '_blank');
        });
    } else {
        tools.querySelector('.export-csv').addEventListener('click', () => exportCsv(table));
    }
    if (!table.hasAttribute('data-hide-column-toggle')) {
        const options = tools.querySelector('.column-options');
        [...table.tHead.rows[0].cells].forEach((th, i) => {
            const label = document.createElement('label');
            label.innerHTML = `<input type="checkbox" checked data-col="${i}"> ${escapeHtml(th.innerText || 'Column ' + (i + 1))}`;
            options.appendChild(label);
            label.querySelector('input').addEventListener('change', e => setColumnVisible(table, i, e.target.checked));
        });
        tools.querySelector('.column-toggle').addEventListener('click', () => options.classList.toggle('open'));
    }
}

function handleOutsideMenus(event) {
    document.querySelectorAll('.column-options.open').forEach(menu => {
        if (!menu.parentElement.contains(event.target)) menu.classList.remove('open');
    });
    if (!event.target.closest('.custom-select') && !event.target.closest('.asu-program-filter-menu') && !event.target.closest('.enr-wizard-select-menu')) closeCustomSelects();
    if (!event.target.closest('.filter-date-picker') && !event.target.closest('.global-cal-panel')) closeCustomDatePickers();
    if (!event.target.closest('.form-datetime-picker') && !event.target.closest('.global-datetime-panel')) closeGlobalDtPanel();
    if (!event.target.closest('.dtr-time-panel') && !event.target.closest('[data-time-picker-trigger]')) closeDtrTimePicker();
    const panel = document.querySelector('.notif-panel');
    const btn   = document.getElementById('notifBtn');
    if (panel && panel.classList.contains('is-open') && !panel.contains(event.target) && event.target !== btn && !btn?.contains(event.target)) {
        closeNotifications();
    }
    const docsGroup = document.querySelector('.role-student .nav-group--student-docs.open');
    if (docsGroup && window.matchMedia('(max-width: 720px)').matches) {
        const backdrop = document.querySelector('.student-nav-sheet-backdrop');
        const portaledItems = document.querySelector('.nav-group-items[data-docs-sheet="1"]');
        const clickedInside = docsGroup.contains(event.target)
            || portaledItems?.contains(event.target)
            || event.target === backdrop;
        if (!clickedInside) {
            window.dispatchEvent(new CustomEvent('student-nav-docs-toggle', {
                detail: { group: docsGroup, open: false },
            }));
        }
    }
}

function setColumnVisible(table, index, visible) {
    table._hiddenCols = table._hiddenCols || new Set();
    if (visible) table._hiddenCols.delete(index); else table._hiddenCols.add(index);
    applyHiddenColumns(table);
}

function applyHiddenColumns(table) {
    const hidden = table._hiddenCols || new Set();
    [...table.rows].forEach(row => [...row.cells].forEach((cell, i) => { cell.style.display = hidden.has(i) ? 'none' : ''; }));
}

function exportCsv(table) {
    const rows = [table.tHead.rows[0], ...table.tBodies[0].rows];
    const csv = rows.map(row => [...row.cells].filter(cell => cell.style.display !== 'none').map(cell => `"${cell.innerText.replace(/"/g, '""').trim()}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'table-export.csv';
    a.click();
    URL.revokeObjectURL(a.href);
}

function attachRowDetails(table) {
    if (table.classList.contains('no-row-details')) return;
    [...table.tBodies[0].rows].forEach(row => {
        if (row.dataset.detailReady) return;
        row.dataset.detailReady = '1';
        row.addEventListener('click', e => {
            if (e.target.closest('a,button,form,input,select,textarea,summary,details,.admin-user-action-menu,.admin-user-action-panel')) return;
            const headers = [...table.tHead.rows[0].cells].map(th => th.innerText.trim());
            let prependFields = [];
            let appendFields = [];
            if (row.dataset.idNumber) {
                prependFields.push({
                    label: 'ID Number',
                    value: row.dataset.idNumber,
                });
            }
            if (row.dataset.detailFields) {
                try {
                    const parsed = JSON.parse(row.dataset.detailFields);
                    if (Array.isArray(parsed)) appendFields = parsed;
                } catch {
                    appendFields = [];
                }
            }
            const fieldHtml = (fields) => fields.map(field => `<div class="detail-row"><span>${escapeHtml(field?.label || 'Field')}</span><strong>${escapeHtml(field?.value || '-')}</strong></div>`).join('');
            const cellsHtml = [...row.cells].map((cell, i) => `<div class="detail-row"><span>${escapeHtml(headers[i] || 'Field')}</span><strong>${escapeHtml(cell.innerText.trim())}</strong></div>`).join('');
            openSlidePanel('<h2>Record Details</h2>' + fieldHtml(prependFields) + cellsHtml + fieldHtml(appendFields));
        });
    });
}

function initTimelineDetails() {
    document.querySelectorAll('.timeline-item[data-detail]').forEach(item => {
        item.addEventListener('click', (event) => {
            if (event.target.closest('a, button')) return;
            const parts = (item.dataset.detail || '').split('|');
            const labels = item.dataset.type === 'weekly'
                ? ['Week', 'Submitted', 'Type', 'Summary']
                : ['Date', 'Schedule', 'Hours', 'Tasks'];
            openSlidePanel(`<h2>Activity Details</h2>${parts.map((p, i) => `<div class="detail-row"><span>${labels[i] || 'Info'}</span><strong>${escapeHtml(p)}</strong></div>`).join('')}`);
        });
    });
}

function initStudentTimelineFilters() {
    const timeline = document.querySelector('[data-st-timeline]');
    if (!timeline) return;

    const filters = document.querySelectorAll('[data-st-filter]');
    const items = timeline.querySelectorAll('.st-timeline-item');
    const monthDividers = timeline.querySelectorAll('[data-st-month]');
    if (!filters.length || !items.length) return;

    filters.forEach(filterBtn => {
        filterBtn.addEventListener('click', () => {
            const filter = filterBtn.dataset.stFilter || 'all';

            filters.forEach(btn => {
                const active = btn === filterBtn;
                btn.classList.toggle('is-active', active);
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            items.forEach(item => {
                const type = item.dataset.type || '';
                const show = filter === 'all' || type === filter;
                item.classList.toggle('is-hidden', !show);
            });

            monthDividers.forEach(divider => {
                let visible = false;
                let node = divider.nextElementSibling;
                while (node && !node.matches('[data-st-month]')) {
                    if (node.matches('.st-timeline-item') && !node.classList.contains('is-hidden')) {
                        visible = true;
                        break;
                    }
                    node = node.nextElementSibling;
                }
                divider.classList.toggle('is-hidden', !visible);
            });
        });
    });
}

function openSlidePanel(html) {
    const modal = document.getElementById('modal');
    const body  = document.getElementById('modal-body');
    if (!modal || !body) return;
    body.innerHTML = html;
    if (modal._closeTimer) {
        clearTimeout(modal._closeTimer);
        modal._closeTimer = null;
    }
    modal.classList.remove('is-closing');
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            modal.classList.add('open');
        });
    });
    if (!modal.dataset.closeBound) {
        modal.dataset.closeBound = '1';
        modal.addEventListener('click', e => {
            if (e.target === modal) closeSlidePanel();
        });
    }
}

function closeSlidePanel() {
    const modal = document.getElementById('modal');
    if (modal && (modal.classList.contains('open') || modal.classList.contains('is-closing'))) {
        if (!modal.classList.contains('is-closing')) {
            const ANIM_MS = 280;
            if (modal._closeTimer) clearTimeout(modal._closeTimer);
            modal.classList.add('is-closing');
            modal.classList.remove('open');
            modal._closeTimer = window.setTimeout(() => {
                modal.classList.remove('is-closing');
                modal._closeTimer = null;
            }, ANIM_MS);
        }
    }
    closeStudentModal();
}

function closeStudentModal() {
    const modal = document.getElementById('studentModal');
    if (!modal) return;
    if (!modal.classList.contains('open') && !modal.classList.contains('is-closing')) return;
    if (modal.classList.contains('is-closing')) return;

    const ANIM_MS = 280;
    if (modal._closeTimer) clearTimeout(modal._closeTimer);
    modal.classList.add('is-closing');
    modal.classList.remove('open');
    modal._closeTimer = window.setTimeout(() => {
        modal.classList.remove('is-closing');
        modal._closeTimer = null;
    }, ANIM_MS);
}

function openStudentModal() {
    const modal = document.getElementById('studentModal');
    if (!modal) return;
    if (modal._closeTimer) {
        clearTimeout(modal._closeTimer);
        modal._closeTimer = null;
    }
    modal.classList.remove('is-closing');
    requestAnimationFrame(() => {
        requestAnimationFrame(() => modal.classList.add('open'));
    });
}

function closeAdminActionMenus() {
    document.querySelectorAll('.admin-user-action-menu[open], .admin-user-action-submenu[open], .admin-user-action-other[open]').forEach(menu => {
        menu.removeAttribute('open');
    });
    document.querySelectorAll('.admin-user-action-panel.is-fixed-panel').forEach(panel => {
        const host = panel._actionMenuHost;
        if (panel.dataset.portaled === '1' && host) {
            host.appendChild(panel);
            delete panel.dataset.portaled;
        }
        panel.classList.remove('is-fixed-panel');
        panel.style.top = '';
        panel.style.left = '';
        panel.style.right = '';
        panel.style.bottom = '';
        panel.style.width = '';
        panel.style.visibility = '';
        panel.style.pointerEvents = '';
    });
}
function escapeHtml(value) {
    return String(value).replace(/[&<>'"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[c]));
}

function formatLabel(value) {
    return String(value || '')
        .replaceAll('_', ' ')
        .replace(/\b\w/g, char => char.toUpperCase());
}

function initEmailLogViews() {
    document.querySelectorAll('.email-log-view').forEach(button => {
        if (button.dataset.emailLogBound === '1') return;
        button.dataset.emailLogBound = '1';
        button.addEventListener('click', () => {
            const data = button.dataset;
            const statusRaw = String(data.status || 'sent').toLowerCase();
            const isFailed = statusRaw === 'failed' || statusRaw === 'fail';
            const statusClass = isFailed ? 'failed' : 'sent';
            const statusLabel = isFailed ? 'Failed' : 'Sent';
            const errorText = String(data.error || '').trim();
            const showError = isFailed && errorText && errorText.toLowerCase() !== 'no error message';

            const iconSvg = isFailed
                ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm3.5 13.5L12 12l-3.5 3.5-1.5-1.5L10.5 10.5 7 7l1.5-1.5L12 9.5l3.5-3.5L17 7l-3.5 3.5L17 14l-1.5 1.5Z"/></svg>'
                : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1.5 14.5-4-4L8 11l2.5 2.5L16 8l1.5 1.5-7 7Z"/></svg>';

            openSlidePanel(`
                <div class="email-log-detail">
                    <div class="email-log-detail__hero">
                        <span class="email-log-detail__icon email-log-detail__icon--${statusClass}" aria-hidden="true">${iconSvg}</span>
                        <div class="email-log-detail__intro">
                            <p class="email-log-detail__eyebrow">Delivery record</p>
                            <h2 class="email-log-detail__title">Email Log Details</h2>
                            <span class="email-log-detail__status email-log-detail__status--${statusClass}">${escapeHtml(statusLabel)}</span>
                        </div>
                    </div>
                    <dl class="email-log-detail__facts">
                        <div class="email-log-detail__fact">
                            <dt>Sent At</dt>
                            <dd>${escapeHtml(data.sentAt || '?')}</dd>
                        </div>
                        <div class="email-log-detail__fact">
                            <dt>Recipient</dt>
                            <dd>${escapeHtml(data.recipient || '?')}</dd>
                        </div>
                        <div class="email-log-detail__fact">
                            <dt>Subject</dt>
                            <dd>${escapeHtml(data.subject || '?')}</dd>
                        </div>
                        <div class="email-log-detail__fact">
                            <dt>Type</dt>
                            <dd>${escapeHtml(formatLabel(data.type || '') || '?')}</dd>
                        </div>
                    </dl>
                    ${showError ? `
                    <div class="email-log-detail__alert">
                        <div class="email-log-detail__alert-head">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 21h22L12 2 1 21Zm12-3h-2v-2h2v2Zm0-4h-2v-4h2v4Z"/></svg>
                            <span>Error Message</span>
                        </div>
                        <p>${escapeHtml(errorText)}</p>
                    </div>` : `
                    <div class="email-log-detail__note">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1.5 14.5-4-4L8 11l2.5 2.5L16 8l1.5 1.5-7 7Z"/></svg>
                        <p>Message was accepted by the mail server with no recorded delivery error.</p>
                    </div>`}
                </div>
            `);
        });
    });
}

function initEmailLogsFeed() {
    document.querySelectorAll('[data-email-logs-feed]').forEach(root => {
        if (root.dataset.emailFeedReady === '1') return;
        root.dataset.emailFeedReady = '1';

        const items = [...root.querySelectorAll('[data-email-item]')];
        if (!items.length) return;

        const searchInput = root.querySelector('[data-email-search]');
        const empty = root.querySelector('[data-email-empty]');
        const pager = root.querySelector('[data-email-pagination]');
        const perPage = Math.max(1, parseInt(root.dataset.perPage || '12', 10) || 12);

        let query = '';
        let page = 1;

        const matches = item => {
            const hay = (item.dataset.search || '').toLowerCase();
            return !query || hay.includes(query);
        };

        const syncDaySections = () => {
            root.querySelectorAll('[data-email-day]').forEach(day => {
                const visible = [...day.querySelectorAll('[data-email-item]')].some(
                    item => !item.classList.contains('is-hidden')
                );
                day.classList.toggle('is-hidden', !visible);
            });
        };

        const renderPager = totalPages => {
            if (!pager) return;
            pager.innerHTML = '';
            if (totalPages <= 1) return;

            const addBtn = (label, targetPage, opts = {}) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ael-page-btn' + (opts.active ? ' is-active' : '');
                btn.textContent = label;
                btn.disabled = !!opts.disabled;
                btn.addEventListener('click', () => {
                    page = targetPage;
                    apply();
                });
                pager.appendChild(btn);
            };

            addBtn('?', Math.max(1, page - 1), { disabled: page <= 1 });
            for (let i = 1; i <= totalPages; i += 1) {
                if (totalPages > 7 && Math.abs(i - page) > 2 && i !== 1 && i !== totalPages) {
                    if (i === 2 || i === totalPages - 1) {
                        const dots = document.createElement('span');
                        dots.className = 'ael-page-btn';
                        dots.style.border = 'none';
                        dots.style.background = 'transparent';
                        dots.style.cursor = 'default';
                        dots.textContent = '?';
                        pager.appendChild(dots);
                    }
                    continue;
                }
                addBtn(String(i), i, { active: i === page });
            }
            addBtn('?', Math.min(totalPages, page + 1), { disabled: page >= totalPages });
        };

        const apply = () => {
            const matched = items.filter(matches);
            const totalPages = Math.max(1, Math.ceil(matched.length / perPage));
            if (page > totalPages) page = totalPages;

            const start = (page - 1) * perPage;
            const end = start + perPage;
            const pageSet = new Set(matched.slice(start, end));

            items.forEach(item => {
                item.classList.toggle('is-hidden', !pageSet.has(item));
            });

            syncDaySections();

            const showEmpty = matched.length === 0;
            empty?.classList.toggle('is-hidden', !showEmpty);
            renderPager(showEmpty ? 0 : totalPages);
        };

        searchInput?.addEventListener('input', () => {
            query = (searchInput.value || '').trim().toLowerCase();
            page = 1;
            apply();
        });

        apply();
    });
}

function renderDashboardCharts() {
    if (!window.dashboardCharts) return;

    function drawAll() {
        if (document.getElementById('monthlyChart')) {
            drawBars('monthlyChart', window.dashboardCharts.monthlyTrends || [], '', false, true);
        }
        if (document.getElementById('statusChart')) {
            drawStatusPie('statusChart', window.dashboardCharts.statusDistribution || []);
        }
        drawCourseRateChart('courseChart', window.dashboardCharts.completionRates || []);
    }

    drawAll();

    // Redraw on window resize (covers zoom changes too)
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(drawAll, 80);
    });

    // ResizeObserver for container size changes (more reliable)
    if (window.ResizeObserver) {
        const ro = new ResizeObserver(() => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(drawAll, 80);
        });
        ['monthlyChart', 'statusChart', 'courseChart'].forEach(id => {
            const el = document.getElementById(id);
            if (el && el.parentElement) ro.observe(el.parentElement);
        });
    }
}
// --- Chart helpers ------------------------------------------------------------
const CHART_COLORS = ['#642525', '#4b2030', '#261c4b', '#1b2a63', '#8B1A1A', '#c0392b', '#64748b'];
const CHART_BAR_COLOR = '#4b2030';
const CHART_BAR_GRADIENT = ['#4b2030', '#261c4b', '#642525'];

function chartBarGradient(ctx, x1, y1, x2, y2) {
    const grad = ctx.createLinearGradient(x1, y1, x2, y2);
    grad.addColorStop(0, CHART_BAR_GRADIENT[0]);
    grad.addColorStop(0.5, CHART_BAR_GRADIENT[1]);
    grad.addColorStop(1, CHART_BAR_GRADIENT[2]);
    return grad;
}

function prepCanvas(id) {
    const c = document.getElementById(id);
    if (!c) return null;
    const dpr = window.devicePixelRatio || 1;
    // Clear fixed size so browser can recalculate responsive dimensions
    c.style.width  = '100%';
    c.style.height = '';
    c.removeAttribute('width');
    c.removeAttribute('height');
    const cssW = c.offsetWidth || c.parentElement.clientWidth;
    const cssH = c.parentElement?.classList.contains('chart-card--status') ? 360 : 320;
    c.width  = cssW * dpr;
    c.height = cssH * dpr;
    c.style.width  = cssW + 'px';
    c.style.height = cssH + 'px';
    const ctx = c.getContext('2d');
    ctx.scale(dpr, dpr);
    ctx.clearRect(0, 0, cssW, cssH);
    return { c, ctx, w: cssW, h: cssH };
}

function chartFont(ctx, size = 12, weight = '500') {
    ctx.font = `${weight} ${size}px "Plus Jakarta Sans", system-ui, sans-serif`;
}

function drawEmpty(ctx, w, h) {
    chartFont(ctx, 13);
    ctx.fillStyle = '#94a3b8';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('No data available', w / 2, h / 2);
}

function niceMax(val) {
    if (val <= 0) return 10;
    const exp = Math.pow(10, Math.floor(Math.log10(val)));
    return Math.ceil(val / exp) * exp;
}

function roundRect(ctx, x, y, w, h, r) {
    if (h <= 0) return;
    r = Math.min(r, h / 2, w / 2);
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + r);
    ctx.lineTo(x + w, y + h);
    ctx.lineTo(x, y + h);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
}

// --- Donut / Pie -------------------------------------------------------------
const STATUS_PIE_SERIES = [
    { key: 'active', label: 'Active', color: '#16a34a' },
    { key: 'pending', label: 'Pending Requirements', color: '#f59e0b' },
    { key: 'completed', label: 'Completed', color: '#dc2626' },
];

function normalizeStatusPieData(data) {
    const counts = {};
    (data || []).forEach(d => {
        counts[String(d.label || '').toLowerCase()] = Number(d.value || 0);
    });
    return STATUS_PIE_SERIES.map(item => ({
        label: item.label,
        value: counts[item.key] || 0,
        color: item.color,
    }));
}

function formatPiePercent(value, total) {
    if (!total) return '0%';
    const pct = (value / total) * 100;
    return Number.isInteger(pct) ? `${pct}%` : `${pct.toFixed(1)}%`;
}

function drawStatusPie(id, data) {
    const items = normalizeStatusPieData(data);
    const p = prepCanvas(id);
    if (!p) return;
    const { ctx, w, h } = p;
    const total = items.reduce((s, d) => s + d.value, 0);
    if (!total) { drawEmpty(ctx, w, h); return; }

    const legendRowH = 34;
    const legendH = items.length * legendRowH + 20;
    const donutSpace = h - legendH;
    const r = Math.min(w * 0.34, donutSpace / 2 * 0.9);
    const cx = w / 2;
    const cy = donutSpace / 2;
    const inner = r * 0.58;
    let start = -Math.PI / 2;

    items.forEach(d => {
        const val = d.value;
        if (!val) return;
        const sweep = (val / total) * Math.PI * 2;
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, r, start, start + sweep);
        ctx.closePath();
        ctx.fillStyle = d.color;
        ctx.fill();
        start += sweep;
    });

    ctx.beginPath();
    ctx.arc(cx, cy, inner, 0, Math.PI * 2);
    ctx.fillStyle = '#fff';
    ctx.fill();

    chartFont(ctx, 22, '800');
    ctx.fillStyle = '#172033';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(String(total), cx, cy - 8);
    chartFont(ctx, 11, '600');
    ctx.fillStyle = '#64748b';
    ctx.fillText('Total', cx, cy + 12);

    const legendTop = donutSpace + 4;
    const padX = 4;
    const swatch = 12;

    items.forEach((d, i) => {
        const y = legendTop + i * legendRowH + 10;
        const pctText = formatPiePercent(d.value, total);

        ctx.fillStyle = d.color;
        roundRect(ctx, padX, y, swatch, swatch, 3);
        ctx.fill();

        chartFont(ctx, 13, '600');
        ctx.fillStyle = '#172033';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'middle';
        ctx.fillText(d.label, padX + swatch + 10, y + swatch / 2);

        chartFont(ctx, 13, '700');
        ctx.fillStyle = '#64748b';
        ctx.textAlign = 'right';
        ctx.fillText(`${d.value} (${pctText})`, w - padX, y + swatch / 2);
    });
}

function drawPie(id, data) {
    const p = prepCanvas(id);
    if (!p) return;
    const { ctx, w, h } = p;
    const total = data.reduce((s, d) => s + Number(d.value || 0), 0);
    if (!total) { drawEmpty(ctx, w, h); return; }

    // layout: calculate exact space for donut, center it above legend
    const legendRowH  = 44;
    const legendRows  = data.length;
    const legendH     = legendRows * legendRowH + 8;
    const donutSpace  = h - legendH;          // pixels available for the circle
    const r   = Math.min(w * 0.38, donutSpace / 2 * 0.88);
    const cx  = w / 2;
    const cy  = donutSpace / 2;              // true vertical centre of donut area
    const inner = r * 0.55;
    let start = -Math.PI / 2;

    // slices
    data.forEach((d, i) => {
        const val = Number(d.value || 0);
        const sweep = (val / total) * Math.PI * 2;
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, r, start, start + sweep);
        ctx.closePath();
        ctx.fillStyle = CHART_COLORS[i % CHART_COLORS.length];
        ctx.fill();
        start += sweep;
    });

    // donut hole
    ctx.beginPath();
    ctx.arc(cx, cy, inner, 0, Math.PI * 2);
    ctx.fillStyle = '#fff';
    ctx.fill();

    // centre label
    chartFont(ctx, 14, '700');
    ctx.fillStyle = '#172033';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(total, cx, cy - 9);
    chartFont(ctx, 10, '500');
    ctx.fillStyle = '#64748b';
    ctx.fillText('Total', cx, cy + 10);

    // legend below donut - centred horizontally
    const legendTop = donutSpace + 8;
    const swatchW   = 12;
    const colW      = 110; // fixed column width for centering
    const totalLegW = data.length * colW;
    const legendStartX = (w - totalLegW) / 2;

    data.forEach((d, i) => {
        const val = Number(d.value || 0);
        const pct = Math.round((val / total) * 100);
        const x   = legendStartX + i * colW;
        const y   = legendTop + 12;

        // swatch
        ctx.fillStyle = CHART_COLORS[i % CHART_COLORS.length];
        roundRect(ctx, x, y, swatchW, swatchW, 3);
        ctx.fill();

        // label
        chartFont(ctx, 12, '600');
        ctx.fillStyle = '#172033';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'top';
        ctx.fillText(String(d.label).charAt(0).toUpperCase() + String(d.label).slice(1), x + swatchW + 6, y);

        // value
        chartFont(ctx, 11, '700');
        ctx.fillStyle = '#64748b';
        ctx.fillText(`${val}  (${pct}%)`, x + swatchW + 6, y + 17);
    });
}

// --- Bar chart (auto horizontal when many bars) -----------------------------
const MONTH_NAMES = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
function fmtBarLabel(lbl) {
    // "2025-11" -> "Nov '25"
    const m = String(lbl).match(/^(\d{4})-(\d{2})$/);
    if (m) return MONTH_NAMES[parseInt(m[2], 10) - 1] + ' \'' + m[1].slice(2);
    return String(lbl);
}
function drawBars(id, data, suffix = '', forceHorizontal = false, forceVertical = false) {
    const horizontal = !forceVertical && (forceHorizontal || data.length > 5);
    if (horizontal) { drawHBars(id, data, suffix); return; }

    const p = prepCanvas(id);
    if (!p) return;
    const { ctx, w, h } = p;
    if (!data.length) { drawEmpty(ctx, w, h); return; }

    const pad = { top: 30, right: 24, bottom: 66, left: 52 };
    const gW = w - pad.left - pad.right;
    const gH = h - pad.top - pad.bottom;
    const maxVal = niceMax(Math.max(...data.map(d => Number(d.value || 0)), 1));
    const ticks = 5;

    ctx.strokeStyle = '#e8edf5';
    ctx.lineWidth = 1;
    chartFont(ctx, 11, '500');
    ctx.fillStyle = '#94a3b8';
    ctx.textAlign = 'right';
    ctx.textBaseline = 'middle';
    for (let i = 0; i <= ticks; i++) {
        const val = (maxVal / ticks) * i;
        const y = pad.top + gH - (val / maxVal) * gH;
        ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(pad.left + gW, y); ctx.stroke();
        ctx.fillText(Math.round(val) + suffix, pad.left - 8, y);
    }

    ctx.strokeStyle = '#cbd5e1'; ctx.lineWidth = 1.5;
    ctx.beginPath(); ctx.moveTo(pad.left, pad.top); ctx.lineTo(pad.left, pad.top + gH); ctx.lineTo(pad.left + gW, pad.top + gH); ctx.stroke();

    const gap = 0.82;
    const barW = Math.max(4, (gW / data.length) * (1 - gap));
    const step = gW / data.length;
    data.forEach((d, i) => {
        const val = Number(d.value || 0);
        const bH  = (val / maxVal) * gH;
        const x   = pad.left + i * step + (step - barW) / 2;
        const y   = pad.top + gH - bH;
        const grad = chartBarGradient(ctx, x, y, x, pad.top + gH);
        ctx.fillStyle = val > 0 ? grad : '#e2e8f0';
        roundRect(ctx, x, y, barW, bH || 2, 6); ctx.fill();
        if (val > 0) {
            chartFont(ctx, 11, '700'); ctx.fillStyle = '#172033'; ctx.textAlign = 'center'; ctx.textBaseline = 'bottom';
            ctx.fillText(val + suffix, x + barW / 2, y - 4);
        }
        chartFont(ctx, 11, '500'); ctx.fillStyle = '#64748b';
        const labelStr = fmtBarLabel(d.label);
        ctx.save(); ctx.translate(x + barW / 2, pad.top + gH + 10); ctx.rotate(-Math.PI / 4);
        ctx.textAlign = 'right'; ctx.textBaseline = 'middle';
        ctx.fillText(labelStr, 0, 0); ctx.restore();
    });
}

function roundRectAll(ctx, x, y, w, h, r) {
    if (w <= 0 || h <= 0) return;
    r = Math.min(r, h / 2, w / 2);
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}

function formatCoursePct(val) {
    const n = Number(val || 0);
    if (!Number.isFinite(n)) return '0%';
    if (Number.isInteger(n)) return `${n}%`;
    return `${n.toFixed(1)}%`;
}

function courseBarGradient(ctx, x, y, w, h) {
    const grad = ctx.createLinearGradient(x, y, x + Math.max(w, 1), y);
    grad.addColorStop(0, '#6b1515');
    grad.addColorStop(0.55, '#8B1A1A');
    grad.addColorStop(1, '#c9a227');
    return grad;
}

function drawCourseRateChart(id, data) {
    const c = document.getElementById(id);
    if (!c) return;
    if (!data.length) {
        const p = prepCanvas(id);
        if (p) drawEmpty(p.ctx, p.w, p.h);
        return;
    }

    const rows = data.map(d => ({
        label: String(d.label || 'Untitled'),
        value: Math.max(0, Math.min(100, Number(d.value || 0))),
    }));

    const padL = 8;
    const padR = 64;
    const padTop = 28;
    const padBot = 36;
    const labelH = 18;
    const barH = 12;
    const rowGap = 28;
    const rowH = labelH + 10 + barH;
    const totalH = padTop + rows.length * rowH + (rows.length - 1) * rowGap + padBot;

    const dpr = window.devicePixelRatio || 1;
    c.style.width = '100%';
    c.removeAttribute('width');
    c.removeAttribute('height');
    const cssW = c.offsetWidth || c.parentElement.clientWidth || 700;
    c.width = cssW * dpr;
    c.height = totalH * dpr;
    c.style.setProperty('width', cssW + 'px', 'important');
    c.style.setProperty('height', totalH + 'px', 'important');

    const ctx = c.getContext('2d');
    ctx.scale(dpr, dpr);
    ctx.clearRect(0, 0, cssW, totalH);

    const trackX = padL;
    const trackW = cssW - padL - padR;
    const ticks = [0, 25, 50, 75, 100];

    // Soft plot background
    roundRectAll(ctx, trackX - 4, padTop - 14, trackW + padR - 8, totalH - padTop - padBot + 22, 8);
    ctx.fillStyle = '#fbf8f8';
    ctx.fill();

    // Grid + top tick labels
    chartFont(ctx, 10, '600');
    ctx.fillStyle = '#9a8585';
    ctx.textBaseline = 'bottom';
    ticks.forEach((tick, i) => {
        const x = trackX + (tick / 100) * trackW;
        ctx.strokeStyle = tick === 0 || tick === 100 ? '#eadfdf' : '#f0e6e6';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(x, padTop - 6);
        ctx.lineTo(x, totalH - padBot + 4);
        ctx.stroke();

        ctx.textAlign = i === 0 ? 'left' : (i === ticks.length - 1 ? 'right' : 'center');
        ctx.fillText(`${tick}%`, x, padTop - 8);
    });

    const hitRegions = [];
    rows.forEach((row, i) => {
        const y = padTop + i * (rowH + rowGap);
        const barY = y + labelH + 10;
        const fillW = (row.value / 100) * trackW;

        hitRegions.push({ y, h: rowH, label: row.label, val: row.value });

        // Course label (full, no mid-word cut)
        chartFont(ctx, 12.5, '700');
        ctx.fillStyle = '#1a1212';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'top';
        let label = row.label;
        const maxLabelW = trackW + padR - 16;
        while (label.length > 3 && ctx.measureText(label).width > maxLabelW) {
            label = label.slice(0, -1);
        }
        if (label !== row.label) label = `${label.slice(0, -1)}\u2026`;
        ctx.fillText(label, trackX, y);

        // Track
        roundRectAll(ctx, trackX, barY, trackW, barH, 999);
        ctx.fillStyle = '#efe4e4';
        ctx.fill();

        // Fill
        if (row.value > 0) {
            const drawW = Math.max(fillW, row.value > 0 ? 8 : 0);
            roundRectAll(ctx, trackX, barY, drawW, barH, 999);
            ctx.fillStyle = courseBarGradient(ctx, trackX, barY, drawW, barH);
            ctx.fill();

            // Soft highlight on top edge
            ctx.save();
            roundRectAll(ctx, trackX, barY, drawW, barH, 999);
            ctx.clip();
            const shine = ctx.createLinearGradient(trackX, barY, trackX, barY + barH);
            shine.addColorStop(0, 'rgba(255,255,255,0.28)');
            shine.addColorStop(0.55, 'rgba(255,255,255,0)');
            ctx.fillStyle = shine;
            ctx.fillRect(trackX, barY, drawW, barH);
            ctx.restore();
        }

        // Value
        chartFont(ctx, 12, '800');
        ctx.fillStyle = row.value > 0 ? '#8B1A1A' : '#9a8585';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'middle';
        ctx.fillText(formatCoursePct(row.value), trackX + trackW + 10, barY + barH / 2);
    });

    window._courseHitRegions = hitRegions;
    window._courseChartPadL = padL;
    window._courseTotalH = totalH;
    attachCourseChartInteraction();
}

function drawHBars(id, data, suffix = '') {
    if (!data.length) return;
    const barH    = 36;
    const gapH    = 14;
    const padR    = 56;
    const padTop  = 16;
    const padBot  = 40;  // enough room for tick labels below the chart area
    const totalH  = padTop + data.length * (barH + gapH) - gapH + padBot;

    const c = document.getElementById(id);
    if (!c) return;
    const dpr  = window.devicePixelRatio || 1;
    c.style.width = '100%';
    c.removeAttribute('width');
    c.removeAttribute('height');
    const cssW = c.offsetWidth || c.parentElement.clientWidth || 700;

    // Measure max label width dynamically so labels never get clipped
    const measureCtx = document.createElement('canvas').getContext('2d');
    measureCtx.font = '600 12px "Plus Jakarta Sans", system-ui, sans-serif';
    const maxTextW = Math.max(...data.map(d => measureCtx.measureText(String(d.label)).width));
    const padL = Math.min(Math.ceil(maxTextW) + 20, 280);
    c.width  = cssW * dpr;
    c.height = totalH * dpr;
    c.style.setProperty('width',  cssW   + 'px', 'important');
    c.style.setProperty('height', totalH + 'px', 'important');
    const ctx = c.getContext('2d');
    ctx.scale(dpr, dpr);
    ctx.clearRect(0, 0, cssW, totalH);

    const gW     = cssW - padL - padR;
    const maxVal = niceMax(Math.max(...data.map(d => Number(d.value || 0)), 1));

    // faint vertical grid lines
    const ticks = 4;
    ctx.strokeStyle = '#e8edf5'; ctx.lineWidth = 1;
    chartFont(ctx, 10, '500'); ctx.fillStyle = '#94a3b8'; ctx.textAlign = 'center'; ctx.textBaseline = 'top';
    for (let i = 0; i <= ticks; i++) {
        const x = padL + (i / ticks) * gW;
        ctx.beginPath(); ctx.moveTo(x, padTop); ctx.lineTo(x, padTop + totalH - padBot); ctx.stroke();
        ctx.fillText(Math.round((maxVal / ticks) * i) + suffix, x, padTop + totalH - padBot + 4);
    }

    const hitRegions = [];
    data.forEach((d, i) => {
        const val  = Number(d.value || 0);
        const bW   = (val / maxVal) * gW;
        const y    = padTop + i * (barH + gapH);
        const x    = padL;
        hitRegions.push({ y, h: barH, label: d.label, val });

        // label - truncate by pixel width, not character count
        chartFont(ctx, 12, '600');
        ctx.fillStyle = '#172033';
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        const labelStr = String(d.label);
        const maxLabelW = padL - 14;
        let truncated = labelStr;
        while (truncated.length > 1 && ctx.measureText(truncated).width > maxLabelW) {
            truncated = truncated.slice(0, -1);
        }
        if (truncated !== labelStr) truncated = truncated.slice(0, -1) + '\u2026';
        ctx.fillText(truncated, padL - 10, y + barH / 2);

        // bar
        const grad = chartBarGradient(ctx, x, y, x + bW, y);
        ctx.fillStyle = val > 0 ? grad : '#e2e8f0';
        roundRect(ctx, x, y, Math.max(bW, 4), barH, 8);
        ctx.fill();

        // value label
        chartFont(ctx, 11, '700');
        ctx.fillStyle = val > 0 && bW > 40 ? '#fff' : '#172033';
        ctx.textAlign = val > 0 && bW > 40 ? 'right' : 'left';
        ctx.textBaseline = 'middle';
        if (val > 0 && bW > 40) {
            ctx.fillText(val + suffix, x + bW - 10, y + barH / 2);
        } else {
            ctx.fillText(val + suffix, x + bW + 8, y + barH / 2);
        }
    });
    if (id === 'courseChart') {
        window._courseHitRegions = hitRegions;
        window._courseChartPadL  = padL;
        window._courseTotalH     = totalH;
        attachCourseChartInteraction();
    }
}

function attachCourseChartInteraction() {
    const canvas = document.getElementById('courseChart');
    if (!canvas || canvas._interactionAttached) return;
    canvas._interactionAttached = true;

    // -- Tooltip element ------------------------------------------------------
    let tip = document.getElementById('_courseTooltip');
    if (!tip) {
        tip = document.createElement('div');
        tip.id = '_courseTooltip';
        tip.className = 'course-chart-tip';
        document.body.appendChild(tip);
    }

    function getHoveredBar(e) {
        const rect = canvas.getBoundingClientRect();
        const logH = window._courseTotalH || rect.height;
        const rawY = (e.clientY - rect.top) * (logH / rect.height);
        return (window._courseHitRegions || []).find(r => rawY >= r.y && rawY <= r.y + r.h) || null;
    }

    canvas.addEventListener('mousemove', e => {
        const bar = getHoveredBar(e);
        if (!bar) { tip.classList.remove('visible'); canvas.style.cursor = ''; return; }
        const students = (window.dashboardCharts.courseStudents || {})[bar.label] || [];
        canvas.style.cursor = students.length ? 'pointer' : 'default';
        const names = students.slice(0, 5).map(s => `<span>${escapeHtml(s.name)}<em>${s.pct}%</em></span>`).join('');
        const more  = students.length > 5 ? `<span class="tip-more">+${students.length - 5} more</span>` : '';
        tip.innerHTML = `<strong>${escapeHtml(bar.label)}</strong>${names}${more}${students.length ? '<small>Click to see details</small>' : ''}`;
        tip.classList.add('visible');
        const x = Math.min(e.clientX + 14, window.innerWidth - tip.offsetWidth - 12);
        const y = Math.max(e.clientY - tip.offsetHeight / 2, 8);
        tip.style.left = x + 'px';
        tip.style.top  = y + 'px';
    });

    canvas.addEventListener('mouseleave', () => {
        tip.classList.remove('visible');
        canvas.style.cursor = '';
    });

    canvas.addEventListener('click', e => {
        const bar      = getHoveredBar(e);
        if (!bar) return;
        const students = (window.dashboardCharts.courseStudents || {})[bar.label] || [];
        if (!students.length) return;
        tip.classList.remove('visible');

        const rows = students.map(s => {
            const pct   = Math.min(s.pct, 100);
            const color = pct >= 100 ? '#16a34a' : pct >= 50 ? '#f59e0b' : '#8B1A1A';
            return `
                <div class="cprogress-row">
                    <div class="cprogress-header">
                        <div>
                            <span class="cprogress-name">${escapeHtml(s.name)}</span>
                            <span class="cprogress-id">${escapeHtml(s.student_no || '')}</span>
                        </div>
                        <span class="cprogress-pct" style="color:${color}">${pct}%</span>
                    </div>
                    <div class="cprogress-track">
                        <div class="cprogress-fill" style="width:${pct}%;background:${color}"></div>
                    </div>
                    <div class="cprogress-sub">${s.logged}h logged / ${s.required}h required</div>
                </div>`;
        }).join('');

        openSlidePanel(`
            <h2>${escapeHtml(bar.label)}</h2>
            <p class="cprogress-meta">${students.length} student${students.length !== 1 ? 's' : ''} &mdash; avg ${bar.val}% completion</p>
            <div class="cprogress-list">${rows}</div>
        `);
    });
}

// --- Line chart --------------------------------------------------------------
function drawLine(id, data) {
    const p = prepCanvas(id);
    if (!p) return;
    const { ctx, w, h } = p;
    if (!data.length) { drawEmpty(ctx, w, h); return; }

    const pad = { top: 30, right: 24, bottom: 62, left: 52 };
    const gW = w - pad.left - pad.right;
    const gH = h - pad.top - pad.bottom;
    const maxVal = niceMax(Math.max(...data.map(d => Number(d.value || 0)), 1));
    const ticks = 5;

    // grid + y labels
    ctx.strokeStyle = '#e8edf5';
    ctx.lineWidth = 1;
    chartFont(ctx, 11, '500');
    ctx.fillStyle = '#94a3b8';
    ctx.textAlign = 'right';
    ctx.textBaseline = 'middle';
    for (let i = 0; i <= ticks; i++) {
        const val = (maxVal / ticks) * i;
        const y = pad.top + gH - (val / maxVal) * gH;
        ctx.beginPath();
        ctx.moveTo(pad.left, y);
        ctx.lineTo(pad.left + gW, y);
        ctx.stroke();
        ctx.fillText(Math.round(val), pad.left - 8, y);
    }

    // axes
    ctx.strokeStyle = '#cbd5e1';
    ctx.lineWidth = 1.5;
    ctx.beginPath();
    ctx.moveTo(pad.left, pad.top);
    ctx.lineTo(pad.left, pad.top + gH);
    ctx.lineTo(pad.left + gW, pad.top + gH);
    ctx.stroke();

    const pts = data.map((d, i) => ({
        x: pad.left + (data.length === 1 ? gW / 2 : i * (gW / (data.length - 1))),
        y: pad.top + gH - (Number(d.value || 0) / maxVal) * gH,
        d
    }));

    // area fill
    const areaGrad = ctx.createLinearGradient(0, pad.top, 0, pad.top + gH);
    areaGrad.addColorStop(0, 'rgba(139,26,26,0.18)');
    areaGrad.addColorStop(1, 'rgba(139,26,26,0)');
    ctx.beginPath();
    pts.forEach((pt, i) => i === 0 ? ctx.moveTo(pt.x, pt.y) : ctx.lineTo(pt.x, pt.y));
    ctx.lineTo(pts[pts.length - 1].x, pad.top + gH);
    ctx.lineTo(pts[0].x, pad.top + gH);
    ctx.closePath();
    ctx.fillStyle = areaGrad;
    ctx.fill();

    // line
    ctx.strokeStyle = '#8B1A1A';
    ctx.lineWidth = 3;
    ctx.lineJoin = 'round';
    ctx.beginPath();
    pts.forEach((pt, i) => i === 0 ? ctx.moveTo(pt.x, pt.y) : ctx.lineTo(pt.x, pt.y));
    ctx.stroke();

    // dots + labels
    pts.forEach(pt => {
        // outer ring
        ctx.beginPath();
        ctx.arc(pt.x, pt.y, 7, 0, Math.PI * 2);
        ctx.fillStyle = '#fff';
        ctx.fill();
        ctx.strokeStyle = '#8B1A1A';
        ctx.lineWidth = 2.5;
        ctx.stroke();
        // inner dot
        ctx.beginPath();
        ctx.arc(pt.x, pt.y, 3.5, 0, Math.PI * 2);
        ctx.fillStyle = '#8B1A1A';
        ctx.fill();

        // value above dot
        chartFont(ctx, 11, '700');
        ctx.fillStyle = '#172033';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'bottom';
        ctx.fillText(pt.d.value, pt.x, pt.y - 12);

        // x label - draw after dots so we can do rotation outside the per-dot loop
    });

    // x-axis labels: skip any that would overlap, rotate -35?
    chartFont(ctx, 11, '500');
    ctx.fillStyle = '#64748b';
    const labelY = pad.top + gH + 10;
    const minGap = 52; // minimum px between label centres before skipping
    let lastDrawnX = -Infinity;
    // decide step: draw every Nth label so neighbours are ? minGap apart
    const step = Math.ceil(minGap / (pts.length > 1 ? gW / (pts.length - 1) : 1));
    pts.forEach((pt, i) => {
        if (i % step !== 0 && i !== pts.length - 1) return;
        if (pt.x - lastDrawnX < minGap && i !== pts.length - 1) return;
        lastDrawnX = pt.x;
        ctx.save();
        ctx.translate(pt.x, labelY);
        ctx.rotate(-Math.PI / 5); // -36?
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        ctx.fillText(String(pt.d.label), 0, 0);
        ctx.restore();
    });}

function closeRequirementReviewModals(instant = false) {
    document.querySelectorAll('.requirement-review-modal').forEach(modal => {
        if (!modal.classList.contains('open') && !modal.classList.contains('is-closing')) return;
        if (instant) {
            if (modal._closeTimer) {
                clearTimeout(modal._closeTimer);
                modal._closeTimer = null;
            }
            modal.classList.remove('open', 'is-closing');
            return;
        }
        closeRequirementReviewModal(modal);
    });
}

function openRequirementReviewModal(modal) {
    if (!modal) return;
    if (modal._closeTimer) {
        clearTimeout(modal._closeTimer);
        modal._closeTimer = null;
    }
    modal.classList.remove('is-closing');
    requestAnimationFrame(() => {
        requestAnimationFrame(() => modal.classList.add('open'));
    });
}

function closeRequirementReviewModal(modal) {
    if (!modal) return;
    if (!modal.classList.contains('open') && !modal.classList.contains('is-closing')) return;
    if (modal.classList.contains('is-closing')) return;

    const ANIM_MS = 280;
    if (modal._closeTimer) clearTimeout(modal._closeTimer);
    modal.classList.add('is-closing');
    modal.classList.remove('open');
    modal._closeTimer = window.setTimeout(() => {
        modal.classList.remove('is-closing');
        modal._closeTimer = null;
    }, ANIM_MS);
}

function closeRegistrationRequestsReview() {
    const panel = document.getElementById('regReqReviewPanel');
    if (!panel || panel.hidden || panel.classList.contains('is-closing')) return;

    const finishClose = () => {
        panel.hidden = true;
        panel.classList.remove('is-opening', 'is-closing');
        document.querySelectorAll('.reg-req-row.is-selected-row').forEach(row => row.classList.remove('is-selected-row'));
    };

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion) {
        finishClose();
        return;
    }

    panel.classList.remove('is-opening');
    panel.classList.add('is-closing');

    const onAnimEnd = (event) => {
        if (event.target !== panel || event.animationName !== 'regReqSlideUp') return;
        panel.removeEventListener('animationend', onAnimEnd);
        finishClose();
    };

    panel.addEventListener('animationend', onAnimEnd);
}

function initRegistrationRequestsReview() {
    const root = document.querySelector('.reg-req-v2');
    if (!root) return;

    const panel = document.getElementById('regReqReviewPanel');
    const table = root.querySelector('.reg-req-table');
    const exportBtn = root.querySelector('[data-reg-req-export]');
    const approveForm = document.getElementById('regReqApproveForm');
    const declineForm = document.getElementById('regReqDeclineForm');
    const coordinatorSelect = document.getElementById('regReqCoordinator');
    const coordinatorError = panel?.querySelector('[data-reg-coordinator-error]');

    const setCoordinatorError = (show) => {
        if (!coordinatorSelect || !coordinatorError) return;
        coordinatorError.hidden = !show;
        coordinatorSelect.classList.toggle('is-invalid', show);
        coordinatorSelect.setAttribute('aria-invalid', show ? 'true' : 'false');
    };

    exportBtn?.addEventListener('click', () => {
        if (table) exportCsv(table);
    });

    coordinatorSelect?.addEventListener('change', () => {
        if (coordinatorSelect.value) setCoordinatorError(false);
    });

    approveForm?.addEventListener('submit', e => {
        if (!coordinatorSelect?.value) {
            e.preventDefault();
            setCoordinatorError(true);
            coordinatorSelect.focus({ preventScroll: true });
            coordinatorSelect.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return;
        }
        setCoordinatorError(false);
    });

    root.querySelector('[data-reg-decline]')?.addEventListener('click', async e => {
        e.preventDefault();
        const confirmed = await showConfirmModal('Decline this student account request?', {
            title: 'Decline registration',
            confirmText: 'Yes, decline',
            cancelText: 'Cancel',
        });
        if (confirmed) declineForm?.requestSubmit();
    });

    root.querySelectorAll('[data-reg-incomplete-delete]').forEach(form => {
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const confirmed = await showConfirmModal(
                'Delete this stuck registration? The student will be able to register again with the same email or USN.',
                {
                    title: 'Delete stuck registration',
                    confirmText: 'Yes, delete',
                    cancelText: 'Cancel',
                }
            );
            if (confirmed) form.submit();
        });
    });

    const fieldMap = {
        'last-name': 'lastName',
        'first-name': 'firstName',
        'middle-name': 'middleName',
        'student-no': 'studentNo',
        email: 'email',
        course: 'course',
        'submitted-at': 'submittedAt',
    };

    const emailCheckSvg = `
        <svg class="reg-req-email-check" viewBox="0 0 16 16" aria-label="Email verified" role="img">
            <circle cx="8" cy="8" r="8" fill="currentColor" opacity="0.18"/>
            <path d="M4.5 8.25 6.75 10.5 11.5 5.75" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    `;

    const renderEmailField = (container, email, verifiedAt) => {
        if (!container) return;
        const trimmedEmail = (email || '').trim();
        const isVerified = (verifiedAt || '').trim() !== '';
        if (trimmedEmail === '') {
            container.textContent = '?';
            return;
        }
        container.innerHTML = isVerified
            ? `<span class="reg-req-email-line"><span class="reg-req-email-text">${escapeHtml(trimmedEmail)}</span>${emailCheckSvg}</span>`
            : escapeHtml(trimmedEmail);
    };

    table?.querySelectorAll('th[data-sort]').forEach((th) => {
        const colIndex = th.cellIndex;
        let asc = true;
        th.addEventListener('click', () => {
            const tbody = table.tBodies[0];
            if (!tbody) return;
            const rows = [...tbody.rows];
            rows.sort((a, b) => {
                const aText = (a.cells[colIndex]?.innerText || '').trim();
                const bText = (b.cells[colIndex]?.innerText || '').trim();
                return asc ? aText.localeCompare(bText) : bText.localeCompare(aText);
            });
            asc = !asc;
            rows.forEach(row => tbody.appendChild(row));
        });
    });

    const openReview = (row) => {
        if (!panel || !row || panel.classList.contains('is-closing')) return;

        document.querySelectorAll('.reg-req-row.is-selected-row').forEach(r => r.classList.remove('is-selected-row'));
        row.classList.add('is-selected-row');

        const requestId = row.dataset.requestId || '';
        panel.querySelectorAll('[data-reg-request-id]').forEach(input => {
            input.value = requestId;
        });

        Object.entries(fieldMap).forEach(([field, dataKey]) => {
            const el = panel.querySelector(`[data-reg-field="${field}"]`);
            if (!el || field === 'email') return;
            const value = (row.dataset[dataKey] || '').trim();
            el.textContent = value !== '' ? value : '?';
        });

        renderEmailField(
            panel.querySelector('[data-reg-field="email"]'),
            row.dataset.email || '',
            row.dataset.verifiedAt || ''
        );

        const corLink = panel.querySelector('[data-reg-cor-link]');
        const corMissing = panel.querySelector('[data-reg-cor-missing]');
        const corUrl = (row.dataset.corUrl || '').trim();
        if (corLink && corMissing) {
            if (corUrl) {
                corLink.href = corUrl;
                corLink.hidden = false;
                corMissing.hidden = true;
            } else {
                corLink.hidden = true;
                corMissing.hidden = false;
            }
        }

        if (coordinatorSelect) {
            coordinatorSelect.value = '';
            setCoordinatorError(false);
        }

        const wasHidden = panel.hidden;
        panel.classList.remove('is-closing');
        panel.hidden = false;

        if (wasHidden) {
            panel.classList.remove('is-opening');
            // Restart slide-down animation when opening from hidden.
            void panel.offsetWidth;
            panel.classList.add('is-opening');
        }

        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    root.querySelectorAll('[data-reg-req-review]').forEach(button => {
        button.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            const row = button.closest('.reg-req-row');
            openReview(row);
        });
    });

    panel?.querySelector('[data-reg-req-close]')?.addEventListener('click', closeRegistrationRequestsReview);
}

function initPasswordResetRequests() {
    const root = document.querySelector('[data-pwd-reset-requests]');
    if (!root) return;

    const badgeEl = root.querySelector('[data-pwd-reset-badge]');
    const countEl = root.querySelector('[data-pwd-reset-count]');
    const emptyEl = root.querySelector('[data-pwd-reset-empty]');
    const tableWrap = root.querySelector('[data-pwd-reset-table-wrap]');
    const tbody = root.querySelector('[data-pwd-reset-tbody]');
    const loadingLabels = {
        approve: 'Sending reset link...',
        reject: 'Rejecting request...',
    };
    const successLabels = {
        approve: 'Reset link sent successfully.',
        reject: 'Password reset request rejected.',
    };

    let floatEl = document.querySelector('[data-pwd-reset-float]');
    if (!floatEl) {
        floatEl = document.createElement('div');
        floatEl.className = 'pwd-reset-float';
        floatEl.setAttribute('data-pwd-reset-float', '');
        floatEl.setAttribute('aria-live', 'polite');
        floatEl.setAttribute('aria-busy', 'false');
        floatEl.hidden = true;
        floatEl.innerHTML = `
            <div class="pwd-reset-float-box" role="status">
                <div class="pwd-reset-float-icon pwd-reset-float-icon--loading" data-pwd-reset-float-icon aria-hidden="true">
                    <span class="pwd-reset-float-spinner"></span>
                </div>
                <p class="pwd-reset-float-msg" data-pwd-reset-float-msg></p>
            </div>
        `;
        document.body.appendChild(floatEl);
    }

    const floatMsgEl = floatEl.querySelector('[data-pwd-reset-float-msg]');
    const floatIconEl = floatEl.querySelector('[data-pwd-reset-float-icon]');
    let floatHideTimer = null;

    const wait = ms => new Promise(resolve => window.setTimeout(resolve, ms));

    const hideFloat = async (delayMs = 0) => {
        if (floatHideTimer) {
            window.clearTimeout(floatHideTimer);
            floatHideTimer = null;
        }
        if (delayMs > 0) {
            await wait(delayMs);
        }
        floatEl.classList.remove('is-visible');
        floatEl.classList.add('is-fading-out');
        floatEl.setAttribute('aria-busy', 'false');
        await wait(320);
        floatEl.hidden = true;
        floatEl.classList.remove('is-fading-out', 'is-success', 'is-error');
        floatIconEl?.classList.remove('pwd-reset-float-icon--success', 'pwd-reset-float-icon--error');
    };

    const showFloat = async (state, message) => {
        if (floatHideTimer) {
            window.clearTimeout(floatHideTimer);
            floatHideTimer = null;
        }

        floatEl.hidden = false;
        floatEl.classList.remove('is-fading-out', 'is-success', 'is-error');
        floatIconEl?.classList.remove('pwd-reset-float-icon--success', 'pwd-reset-float-icon--error');

        if (floatMsgEl) floatMsgEl.textContent = message;
        floatEl.setAttribute('aria-busy', state === 'loading' ? 'true' : 'false');

        if (state === 'success') {
            floatEl.classList.add('is-success');
            floatIconEl?.classList.add('pwd-reset-float-icon--success');
        } else if (state === 'error') {
            floatEl.classList.add('is-error');
            floatIconEl?.classList.add('pwd-reset-float-icon--error');
        }

        await wait(16);
        floatEl.classList.add('is-visible');

        if (state === 'success') {
            floatHideTimer = window.setTimeout(() => {
                hideFloat();
            }, 1800);
        } else if (state === 'error') {
            floatHideTimer = window.setTimeout(() => {
                hideFloat();
            }, 2800);
        }
    };

    const updatePendingCount = () => {
        const count = tbody?.querySelectorAll('[data-pwd-reset-row]').length || 0;
        if (countEl) countEl.textContent = String(count);
        badgeEl?.classList.toggle('is-hidden', count === 0);
        emptyEl?.classList.toggle('is-hidden', count > 0);
        tableWrap?.classList.toggle('is-hidden', count === 0);
    };

    const setFormLoading = (form, loading) => {
        const button = form.querySelector('[data-pwd-reset-submit]');
        const row = form.closest('[data-pwd-reset-row]');
        const decision = form.dataset.pwdResetDecision || 'approve';
        const textEl = button?.querySelector('.btn-text');
        const defaultLabel = textEl?.dataset.defaultLabel || textEl?.textContent || '';
        const disableSelector = 'button, input:not([type="hidden"]), textarea, select';

        if (loading) {
            if (textEl && !textEl.dataset.defaultLabel) {
                textEl.dataset.defaultLabel = defaultLabel;
            }
            button?.classList.add('loading');
            if (button) button.disabled = true;
            if (textEl) textEl.textContent = loadingLabels[decision] || 'Processing...';
            row?.classList.add('is-processing');
            form.querySelectorAll(disableSelector).forEach(el => {
                el.disabled = true;
            });
            return;
        }

        button?.classList.remove('loading');
        if (button) button.disabled = false;
        if (textEl) textEl.textContent = textEl.dataset.defaultLabel || defaultLabel;
        row?.classList.remove('is-processing');
        form.querySelectorAll(disableSelector).forEach(el => {
            el.disabled = false;
        });
    };

    root.querySelectorAll('[data-pwd-reset-form]').forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const submitBtn = form.querySelector('[data-pwd-reset-submit]');
            if (!submitBtn || submitBtn.disabled) return;

            const decision = form.dataset.pwdResetDecision || 'approve';
            const formData = new FormData(form);

            await showFloat('loading', loadingLabels[decision] || 'Processing...');
            setFormLoading(form, true);

            try {
                const endpoint = form.getAttribute('action') || window.location.pathname || 'index.php';
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                    body: formData,
                });

                const raw = await response.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch {
                    const snippet = raw.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
                    throw new Error(snippet.slice(0, 180) || 'Unable to process this password reset request.');
                }

                if (!response.ok || !data.ok) {
                    throw new Error(data.message || 'Unable to process this password reset request.');
                }

                form.closest('[data-pwd-reset-row]')?.remove();
                updatePendingCount();

                const details = form.closest('details.reg-req-decline');
                if (details) details.open = false;

                setFormLoading(form, false);
                await showFloat('success', data.message || successLabels[decision] || 'Done.');
            } catch (error) {
                setFormLoading(form, false);
                await showFloat('error', error.message || 'Unable to process this password reset request.');
            }
        });
    });
}

function initRequirementReviewModals() {
    document.querySelectorAll('[data-review-modal]').forEach(button => {
        button.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            const modal = document.getElementById(button.dataset.reviewModal || '');
            if (!modal) return;
            closeStudentModal();
            closeRequirementReviewModals(true);
            openRequirementReviewModal(modal);
        });
    });

    document.querySelectorAll('.requirement-review-modal').forEach(modal => {
        modal.querySelector('.requirement-review-modal-close')?.addEventListener('click', () => closeRequirementReviewModal(modal));
        modal.addEventListener('click', e => { if (e.target === modal) closeRequirementReviewModal(modal); });
    });

    document.querySelectorAll('.js-review-form').forEach(form => {
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            if (!btn || btn.disabled) return;
            const status = form.dataset.reviewStatus;
            if (status === 'rejected') {
                const note = form.querySelector('.requirement-review-note');
                if (note && !note.value.trim()) {
                    note.focus();
                    note.classList.add('touched');
                    return;
                }
            }
            const origText = btn.textContent;
            btn.disabled = true;
            btn.textContent = status === 'approved' ? 'Approving...' : 'Rejecting...';
            const allBtns = form.closest('[data-review-actions]')?.querySelectorAll('button');
            allBtns?.forEach(b => b.disabled = true);
            try {
                const resp = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                });
                const data = await resp.json();
                if (!data.ok) throw new Error(data.message || 'Review failed.');
                const modal = form.closest('.requirement-review-modal');
                const article = form.closest('.requirement-review-item');
                if (article) {
                    article.className = `requirement-review-item status-${data.requirement_status}`;
                    const badge = article.querySelector('[data-req-status-badge]');
                    if (badge) {
                        badge.className = `badge ${data.requirement_status}`;
                        badge.textContent = data.requirement_status;
                    }
                    const subtitle = article.querySelector('.requirement-review-head small');
                    if (subtitle) {
                        subtitle.textContent = data.requirement_status === 'approved'
                            ? 'Reviewed and approved'
                            : 'Rejected - needs revision';
                    }
                    const actions = article.querySelector('[data-review-actions]');
                    if (actions) {
                        const isApproved = data.requirement_status === 'approved';
                        const rejectionNote = form.querySelector('.requirement-review-note')?.value || '';
                        const icon = isApproved
                            ? '<svg viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>'
                            : '<svg viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/></svg>';
                        const label = isApproved ? 'Approved successfully' : 'Rejected';
                        const noteHtml = !isApproved && rejectionNote
                            ? `<span class="result-note">${escapeHtml(rejectionNote)}</span>`
                            : '';
                        actions.innerHTML = `<div class="requirement-review-result result-${data.requirement_status}">${icon}<span>${label}${noteHtml}</span></div>`;
                    }
                }
                if (modal) {
                    const modalBadge = modal.querySelector('[data-modal-status-badge]');
                    if (modalBadge) {
                        const ps = data.predeployment_status;
                        modalBadge.className = `badge ${ps}`;
                        modalBadge.textContent = ps.replace(/_/g, ' ');
                    }
                    const forwardBox = modal.querySelector('[data-forward-box]');
                    if (forwardBox) {
                        forwardBox.style.display = data.predeployment_status === 'approved' ? '' : 'none';
                    }
                }
                const studentId = modal?.dataset.studentId;
                if (studentId) {
                    document.querySelectorAll('tr .student-predeployment-cell').forEach(cell => {
                        const reviewBtn = cell.querySelector(`[data-review-modal="reviewModal-${studentId}"]`);
                        if (!reviewBtn) return;
                        const badge = cell.querySelector('.badge');
                        if (badge) {
                            badge.className = `badge ${data.predeployment_status}`;
                            badge.textContent = data.predeployment_status.replace(/_/g, ' ');
                        }
                    });
                }
            } catch (err) {
                allBtns?.forEach(b => b.disabled = false);
                btn.textContent = origText;
                showAlertModal(err.message || 'Something went wrong. Please try again.', {
                    title: 'Review failed',
                    confirmText: 'OK',
                });
            }
        });
    });
}

function initStudentModal() {
    const modal = document.getElementById('studentModal');
    if (!modal) return;

    const formatDateTime = value => {
        if (!value) return '\u2014';
        const normalized = String(value).replace(' ', 'T');
        const date = new Date(normalized);
        return Number.isNaN(date.getTime()) ? value : date.toLocaleString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    };

    const formatDate = value => {
        if (!value) return '\u2014';
        const date = new Date(`${value}T00:00:00`);
        return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    };

    // Close handlers
    document.getElementById('studentModalClose')?.addEventListener('click', closeStudentModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeStudentModal(); });

    // Open handler - event delegation on table body
    document.addEventListener('click', e => {
        const btn = e.target.closest('.student-view-btn');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        closeSlidePanel();
        closeAdminActionMenus();
        closeRequirementReviewModals();

        const d = btn.dataset;
        const photoEl = document.getElementById('sm-photo');
        const initialEl = document.getElementById('sm-initial');
        const avatarWrap = document.getElementById('sm-avatar-wrap');
        const photoUrl = (d.photoUrl || '').trim();
        if (photoEl && initialEl && avatarWrap) {
            if (photoUrl) {
                photoEl.src = photoUrl;
                photoEl.alt = `${d.name || 'Student'} profile photo`;
                photoEl.classList.remove('is-hidden');
                initialEl.classList.add('is-hidden');
                avatarWrap.classList.add('has-photo');
            } else {
                photoEl.classList.add('is-hidden');
                photoEl.removeAttribute('src');
                initialEl.textContent = d.initial || (d.name || 'S').charAt(0).toUpperCase();
                initialEl.classList.remove('is-hidden');
                avatarWrap.classList.remove('has-photo');
            }
        }
        document.getElementById('sm-name').textContent = d.name || '';
        document.getElementById('sm-email').textContent = d.email || '';
        document.getElementById('sm-chip-id').textContent = d.studentNo ? `ID ${d.studentNo}` : 'ID \u2014';
        document.getElementById('sm-chip-year').textContent = d.yearLevel ? `${d.yearLevel}` : 'Year \u2014';
        const statusChip = document.getElementById('sm-chip-status');
        statusChip.textContent = formatLabel(d.status || 'pending');
        statusChip.className = `student-panel-chip student-panel-chip-status is-${(d.status || 'pending').replaceAll('_', '-')}`;

        document.getElementById('sm-course').textContent = d.course || '\u2014';
        document.getElementById('sm-year-level').textContent = d.yearLevel || '\u2014';
        const bdRaw = d.birthdate || '';
        document.getElementById('sm-birthdate').textContent = bdRaw ? new Date(bdRaw + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '\u2014';
        document.getElementById('sm-company').textContent = d.company || '\u2014';

        const percent = Math.max(0, Math.min(100, Number.parseInt(d.percent, 10) || 0));
        document.getElementById('sm-progress-text').textContent = `${d.rendered} / ${d.required} hrs (${percent}%)`;
        const progressBar = document.getElementById('sm-progress-bar');
        if (progressBar) progressBar.style.width = `${percent}%`;

        const predeployEl = document.getElementById('sm-predeployment');
        const predeployKey = String(d.predeploymentStatus || 'not submitted').toLowerCase().replaceAll(' ', '_');
        predeployEl.innerHTML = `<span class="coord-status-pill ${escapeHtml(predeployKey)}">${escapeHtml(formatLabel(d.predeploymentStatus || 'Not submitted'))}</span>`;

        document.getElementById('sm-orientation-datetime').textContent = formatDateTime(d.orientationDatetime || '');
        document.getElementById('sm-official-start').textContent = formatDate(d.officialStartDate || '');
        document.getElementById('sm-projected-end').textContent = formatDate(d.projectedEndDate || '');
        document.getElementById('sm-orientation-notes').textContent = d.orientationNotes || 'No orientation instructions recorded yet.';

        const finalLink = document.getElementById('sm-final-link');
        if (finalLink && d.finalUrl) finalLink.href = d.finalUrl;

        const coordinatorEl = document.getElementById('sm-coordinator');
        const coordinatorField = document.querySelector('.admin-only-profile-field');
        if (coordinatorEl && d.coordinator) {
            coordinatorEl.textContent = d.coordinator;
            coordinatorField?.classList.remove('is-hidden');
        } else {
            coordinatorField?.classList.add('is-hidden');
        }

        const adminProfileFooter = document.querySelector('.admin-users-profile-footer');
        if (adminProfileFooter) {
            adminProfileFooter.classList.toggle('is-hidden', !document.querySelector('.admin-users-page'));
        }

        const corLink = document.getElementById('sm-cor-link');
        if (corLink) {
            if (d.cor && d.cor.trim() !== '') {
                corLink.href = d.cor;
                corLink.classList.remove('is-hidden');
            } else {
                corLink.classList.add('is-hidden');
            }
        }

        const moaLink = document.getElementById('sm-moa-link');
        if (moaLink) {
            if (d.moaMou && d.moaMou.trim() !== '') {
                moaLink.href = d.moaMou;
                moaLink.classList.remove('is-hidden');
            } else {
                moaLink.classList.add('is-hidden');
            }
        }

        // Edit email form
        const emailCsrf = document.getElementById('sm-email-csrf');
        if (emailCsrf) emailCsrf.value = d.csrf || '';
        const emailUserId = document.getElementById('sm-email-user-id');
        if (emailUserId) emailUserId.value = d.userId || '';
        const emailInput = document.getElementById('sm-email-input');
        if (emailInput) emailInput.value = d.email || '';

        openStudentModal();
    });

    const params = new URLSearchParams(window.location.search);
    const focusStudent = params.get('focus_student');
    if (focusStudent) {
        const escapedFocusStudent = window.CSS?.escape ? CSS.escape(focusStudent) : focusStudent.replace(/"/g, '\\"');
        const targetButton = document.querySelector(`.student-view-btn[data-student-id="${escapedFocusStudent}"]`);
        if (targetButton) {
            targetButton.closest('tr')?.classList.add('is-selected-row');
            setTimeout(() => targetButton.click(), 120);
        }
    }
}

function initAdminUserActions() {
    initAdminDirectoryActions();
}

function bindAdminDirectoryActions(page) {
    if (!page || page.dataset.adminActionsBound === '1') return;
    page.dataset.adminActionsBound = '1';

    const resetActionPanel = panel => {
        if (!panel) return;
        const host = panel._actionMenuHost;
        if (panel.dataset.portaled === '1' && host) {
            host.appendChild(panel);
            delete panel.dataset.portaled;
        }
        panel.classList.remove('is-fixed-panel');
        panel.style.top = '';
        panel.style.left = '';
        panel.style.right = '';
        panel.style.bottom = '';
        panel.style.width = '';
        panel.style.visibility = '';
        panel.style.pointerEvents = '';
    };

    const getActionPanel = menu => {
        if (!menu) return null;
        if (menu._actionPanel && document.contains(menu._actionPanel)) {
            return menu._actionPanel;
        }
        const panel = menu.querySelector(':scope > .admin-user-action-panel');
        if (panel) {
            menu._actionPanel = panel;
            panel._actionMenuHost = menu;
        }
        return panel || null;
    };

    const positionActionPanel = menu => {
        if (!menu?.classList.contains('admin-user-action-menu')) return;
        const panel = getActionPanel(menu);
        const trigger = menu.querySelector('.admin-user-action-trigger');
        if (!panel || !trigger) return;

        if (!menu.open) {
            resetActionPanel(panel);
            return;
        }

        if (panel.parentElement !== document.body) {
            document.body.appendChild(panel);
            panel.dataset.portaled = '1';
            panel._actionMenuHost = menu;
            menu._actionPanel = panel;
        }

        panel.classList.add('is-fixed-panel');
        panel.style.visibility = 'hidden';
        panel.style.pointerEvents = 'none';
        const panelWidth = panel.offsetWidth || 210;
        const panelHeight = panel.offsetHeight || 1;
        const rect = trigger.getBoundingClientRect();
        let left = rect.right - panelWidth;
        left = Math.max(8, Math.min(left, window.innerWidth - panelWidth - 8));
        let top = rect.bottom + 6;
        if (top + panelHeight > window.innerHeight - 8) {
            top = Math.max(8, rect.top - panelHeight - 6);
        }
        panel.style.left = `${left}px`;
        panel.style.top = `${top}px`;
        panel.style.right = 'auto';
        panel.style.visibility = '';
        panel.style.pointerEvents = '';
    };

    const repositionOpenMenus = () => {
        page.querySelectorAll('.admin-user-action-menu[open]').forEach(positionActionPanel);
    };

    const resetNestedMenus = root => {
        root.querySelectorAll('.admin-user-action-submenu[open], .admin-user-action-other[open]').forEach(menu => {
            menu.removeAttribute('open');
        });
    };

    const closeAllActionMenus = except => {
        page.querySelectorAll('.admin-user-action-menu').forEach(menu => {
            if (menu !== except) {
                menu.removeAttribute('open');
                resetActionPanel(getActionPanel(menu));
            }
            resetNestedMenus(menu);
            const panel = getActionPanel(menu);
            if (panel) resetNestedMenus(panel);
        });
    };

    page.addEventListener('toggle', event => {
        const menu = event.target.closest('.admin-user-action-menu, .admin-user-action-submenu, .admin-user-action-other');
        if (!menu) return;

        if (!menu.open) {
            if (menu.classList.contains('admin-user-action-menu')) {
                resetActionPanel(getActionPanel(menu));
            }
            resetNestedMenus(menu);
            return;
        }

        if (menu.classList.contains('admin-user-action-menu')) {
            closeAllActionMenus(menu);
            resetNestedMenus(menu);
            positionActionPanel(menu);
            requestAnimationFrame(() => positionActionPanel(menu));
            return;
        }

        const panel = menu.closest('.admin-user-action-panel');
        if (panel) {
            panel.querySelectorAll('.admin-user-action-submenu[open], .admin-user-action-other[open]').forEach(sibling => {
                if (sibling !== menu) sibling.removeAttribute('open');
            });
        }
    }, true);

    window.addEventListener('resize', repositionOpenMenus);
    window.addEventListener('scroll', repositionOpenMenus, true);

    page.addEventListener('click', event => {
        if (event.target.closest('.pagination button, .pagination a')) {
            closeAllActionMenus();
        }
    });

    const directoryTable = page.querySelector('.aco-coordinators-table, .asu-students-table, .asu-programs-table, .asu-partners-table');
    if (directoryTable?._rerender && !directoryTable.dataset.actionMenusHooked) {
        directoryTable.dataset.actionMenusHooked = '1';
        const originalRerender = directoryTable._rerender;
        directoryTable._rerender = function rerenderWithActionReset(...args) {
            closeAllActionMenus();
            return originalRerender.apply(this, args);
        };
    }

    document.addEventListener('click', event => {
        if (event.target.closest('.student-view-btn')) {
            return;
        }
        const inMenu = event.target.closest('.admin-user-action-menu');
        const inPortaledPanel = event.target.closest('.admin-user-action-panel.is-fixed-panel');
        if (!inMenu && !inPortaledPanel) {
            closeAllActionMenus();
        }
    });

    page.addEventListener('submit', async event => {
        const form = event.target.closest('.admin-deactivate-form, .admin-activate-form');
        if (!form) return;
        event.preventDefault();

        const isDeactivate = form.classList.contains('admin-deactivate-form');
        const submitBtn = form.querySelector('[type="submit"]');
        const reasonLabel = submitBtn?.dataset.reasonLabel
            || form.querySelector('[name="reason"]')?.value
            || 'this student';
        const studentRow = form.closest('tr');
        const studentName = studentRow?.querySelector('.user-name-cell span:last-child')?.textContent?.trim() || 'this student';
        const notes = form.querySelector('[name="notes"]')?.value?.trim() || '';

        let message = isDeactivate
            ? `Deactivate ${studentName}'s account?\n\nReason: ${reasonLabel}${notes ? `\nDetails: ${notes}` : ''}\n\nThe student will be notified by email.`
            : `Reactivate ${studentName}'s account? They will be able to sign in again.`;

        const confirmed = await showConfirmModal(message, {
            title: isDeactivate ? 'Deactivate student' : 'Activate student',
            confirmText: isDeactivate ? 'Deactivate' : 'Activate',
            variant: isDeactivate ? 'danger' : 'default',
        });

        if (confirmed) form.submit();
    });
}

function initCoordinatorDirectory() {
    document.querySelectorAll('[data-coordinator-directory]').forEach(directory => {
        const table = directory.querySelector('.aco-coordinators-table');
        const pills = directory.querySelectorAll('[data-coordinator-filter]');
        const search = directory.querySelector('.table-search');
        if (!table || !pills.length) return;

        let statusFilter = 'all';
        const applyFilter = () => {
            table._applyRowFilter = row => {
                if (statusFilter === 'all') return true;
                return row.dataset.coordinatorStatus === statusFilter;
            };
            search?.dispatchEvent(new Event('input'));
        };

        table._resetDirectoryFilters = () => {
            statusFilter = 'all';
            pills.forEach(p => p.classList.toggle('is-active', (p.dataset.coordinatorFilter || 'all') === 'all'));
            table._applyRowFilter = null;
        };

        pills.forEach(pill => {
            pill.addEventListener('click', () => {
                statusFilter = pill.dataset.coordinatorFilter || 'all';
                pills.forEach(p => p.classList.toggle('is-active', p === pill));
                applyFilter();
            });
        });

        requestAnimationFrame(applyFilter);
    });
}

function initEnrollmentDirectory() {
    document.querySelectorAll('[data-enrollment-directory]').forEach(directory => {
        if (directory.dataset.enrDirReady === '1') return;
        directory.dataset.enrDirReady = '1';

        const table = directory.querySelector('[data-enrollment-directory-table]');
        const wizardForm = document.querySelector('.enr-wizard-overlay [data-wizard], [data-wizard]');
        const openBtn = directory.querySelector('[data-enr-open-wizard]');
        const exportBtn = directory.querySelector('[data-enr-export]');
        if (!table) return;

        exportBtn?.addEventListener('click', () => exportCsv(table));

        const openWizardForStudent = studentId => {
            if (!wizardForm) return;
            wizardForm._wizardReset?.();
            wizardForm._resetEnrollmentFields?.(studentId || '');
            window.openEnrollmentWizardModal?.();
        };

        openBtn?.addEventListener('click', () => openWizardForStudent(''));

        directory.querySelectorAll('[data-enr-enroll-student]').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                e.stopPropagation();
                openWizardForStudent(btn.dataset.enrEnrollStudent || '');
            });
        });

        table.tBodies[0]?.addEventListener('click', event => {
            if (event.target.closest('[data-enr-enroll-student]')) return;
            const row = event.target.closest('.enrollment-directory-row');
            if (!row) return;

            const isEnrolled = row.dataset.studentEnrolled === '1';
            const studentId = row.dataset.studentId || '';
            document.querySelectorAll('.enrollment-directory-row.is-selected-row').forEach(activeRow => activeRow.classList.remove('is-selected-row'));
            row.classList.add('is-selected-row');

            if (isEnrolled) {
                showAlertModal('This student is already enrolled. Please try again.', {
                    title: 'Student already enrolled',
                    confirmText: 'OK'
                });
                return;
            }

            openWizardForStudent(studentId);
        });
    });
}

function initEnrollmentWizardSelectPortal(select) {
    if (!select || select.dataset.enhanced !== '1' || select.dataset.enrMenuPortal === '1') return;

    const wrap = select.closest('label') || select.parentElement;
    const custom = wrap?.querySelector('.custom-select');
    const trigger = custom?.querySelector('.custom-select-trigger');
    const menu = custom?.querySelector('.custom-select-menu');
    if (!custom || !trigger || !menu) return;

    select.dataset.enrMenuPortal = '1';
    document.body.appendChild(menu);
    menu.classList.add('enr-wizard-select-menu');
    menu.hidden = true;

    const syncMenu = () => {
        const open = custom.classList.contains('is-open');
        menu.classList.toggle('is-open', open);
        if (!open) {
            menu.hidden = true;
            menu.style.width = '';
            menu.style.left = '';
            menu.style.top = '';
            menu.style.bottom = '';
            menu.style.maxHeight = '';
            return;
        }

        menu.hidden = false;
        const rect = trigger.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom - 16;
        const spaceAbove = rect.top - 16;
        const openUp = spaceBelow < 160 && spaceAbove > spaceBelow;
        const maxHeight = Math.max(140, Math.min(320, openUp ? spaceAbove : spaceBelow));

        menu.style.position = 'fixed';
        menu.style.left = `${Math.round(rect.left)}px`;
        menu.style.width = `${Math.round(rect.width)}px`;
        menu.style.zIndex = '10060';
        menu.style.maxHeight = `${maxHeight}px`;
        menu.style.overflowY = 'auto';

        if (openUp) {
            menu.style.top = 'auto';
            menu.style.bottom = `${Math.round(window.innerHeight - rect.top + 6)}px`;
        } else {
            menu.style.bottom = 'auto';
            menu.style.top = `${Math.round(rect.bottom + 6)}px`;
        }
    };

    const observer = new MutationObserver(() => requestAnimationFrame(syncMenu));
    observer.observe(custom, { attributes: true, attributeFilter: ['class'] });
    window.addEventListener('resize', syncMenu);
    window.addEventListener('scroll', syncMenu, true);
    requestAnimationFrame(syncMenu);
}

function initEnrollmentWizardSelectPortals() {
    const overlay = document.getElementById('enrWizardOverlay');
    if (!overlay) return;
    overlay.querySelectorAll('select').forEach(select => initEnrollmentWizardSelectPortal(select));
}

function initEnrollmentWizardModal() {
    const overlay = document.getElementById('enrWizardOverlay');
    const closeBtn = document.getElementById('enrWizardClose');
    if (!overlay || !closeBtn) return;
    if (overlay.dataset.ready === '1') return;
    overlay.dataset.ready = '1';

    const MODAL_ANIM_MS = 300;
    let closeTimer = null;

    const finishClose = () => {
        overlay.classList.remove('is-closing');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-enr-wizard-open');
        closeCustomSelects();
        const wizardForm = overlay.querySelector('[data-wizard]');
        wizardForm?._wizardReset?.();
        wizardForm?._resetEnrollmentFields?.('');
        closeTimer = null;
    };

    const closeModal = () => {
        if (!overlay.classList.contains('open') || overlay.classList.contains('is-closing')) return;
        if (closeTimer) clearTimeout(closeTimer);
        closeCustomSelects();
        overlay.classList.add('is-closing');
        overlay.classList.remove('open');
        closeTimer = window.setTimeout(finishClose, MODAL_ANIM_MS);
    };

    const openModal = () => {
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }
        overlay.classList.remove('is-closing');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-enr-wizard-open');
        initEnrollmentWizardSelectPortals();
        requestAnimationFrame(() => {
            requestAnimationFrame(() => overlay.classList.add('open'));
        });
    };

    window.openEnrollmentWizardModal = openModal;
    window.closeEnrollmentWizardModal = closeModal;

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeModal();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
    });
}

function initEnrollmentCorUpload() {
    document.querySelectorAll('[data-cor-dropzone]').forEach(row => {
        const fileInput = row.querySelector('input[type="file"]');
        const browseBtn = row.querySelector('[data-cor-browse]');
        const filenameEl = row.querySelector('[data-cor-filename]');
        const clearBtn = row.querySelector('[data-cor-clear]');
        if (!fileInput) return;

        const showFile = file => {
            if (!file) return;
            row.classList.add('has-file');
            if (filenameEl) filenameEl.textContent = file.name;
            if (clearBtn) clearBtn.hidden = false;
        };

        const clearFile = () => {
            fileInput.value = '';
            row.classList.remove('has-file');
            if (filenameEl) filenameEl.textContent = 'No file chosen';
            if (clearBtn) clearBtn.hidden = true;
        };

        browseBtn?.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            fileInput.click();
        });

        clearBtn?.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            clearFile();
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files?.[0]) showFile(fileInput.files[0]);
        });

        row.addEventListener('dragover', e => {
            e.preventDefault();
            row.classList.add('is-dragover');
        });
        row.addEventListener('dragleave', () => row.classList.remove('is-dragover'));
        row.addEventListener('drop', e => {
            e.preventDefault();
            row.classList.remove('is-dragover');
            const file = e.dataTransfer?.files?.[0];
            if (!file) return;
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            showFile(file);
        });
    });
}

function initEnrollmentFilters() {
    document.querySelectorAll('[data-enrollment-directory]').forEach(directory => {
        const table = directory.querySelector('[data-enrollment-directory-table]');
        const pills = directory.querySelectorAll('[data-enrollment-filter]');
        const search = directory.querySelector('.table-search');
        if (!table || !pills.length) return;

        let statusFilter = 'all';
        const applyFilter = () => {
            table._applyRowFilter = row => {
                if (statusFilter === 'all') return true;
                if (statusFilter === 'enrolled') return row.dataset.studentEnrolled === '1';
                return row.dataset.studentEnrolled === '0';
            };
            search?.dispatchEvent(new Event('input'));
        };

        table._resetDirectoryFilters = () => {
            statusFilter = 'all';
            pills.forEach(p => p.classList.toggle('is-active', (p.dataset.enrollmentFilter || 'all') === 'all'));
            table._applyRowFilter = null;
        };

        pills.forEach(pill => {
            pill.addEventListener('click', () => {
                statusFilter = pill.dataset.enrollmentFilter || 'all';
                pills.forEach(p => p.classList.toggle('is-active', p === pill));
                applyFilter();
            });
        });

        requestAnimationFrame(applyFilter);
    });
}

function initMyStudentsDirectory() {
    document.querySelectorAll('[data-my-students-directory]').forEach(directory => {
        const table = directory.querySelector('[data-ms-students-table]');
        const ojtFilter = directory.querySelector('[data-ms-ojt-filter]');
        const termFilter = directory.querySelector('[data-ms-term-filter]');
        const perPageFilter = directory.querySelector('[data-ms-per-page]');
        const exportBtn = directory.querySelector('[data-ms-export]');
        const search = directory.querySelector('.table-search');
        if (!table) return;

        let ojtStatus = 'all';
        let term = 'all';

        const applyFilters = () => {
            table._applyRowFilter = row => {
                if (ojtStatus !== 'all' && row.dataset.ojtStatus !== ojtStatus) return false;
                if (term !== 'all' && row.dataset.academicTerm !== term) return false;
                return true;
            };
            search?.dispatchEvent(new Event('input'));
        };

        table._resetDirectoryFilters = () => {
            ojtStatus = 'all';
            term = 'all';
            if (ojtFilter) {
                ojtFilter.value = 'all';
                ojtFilter._syncCustomSelect?.();
            }
            if (termFilter) {
                termFilter.value = 'all';
                termFilter._syncCustomSelect?.();
            }
            table._applyRowFilter = null;
        };

        ojtFilter?.addEventListener('change', () => {
            ojtStatus = ojtFilter.value || 'all';
            applyFilters();
        });

        termFilter?.addEventListener('change', () => {
            term = termFilter.value || 'all';
            applyFilters();
        });

        perPageFilter?.addEventListener('change', () => {
            const perPage = parseInt(perPageFilter.value, 10) || 10;
            if (typeof table._setPerPage === 'function') {
                table._setPerPage(perPage);
            } else {
                table.dataset.perPage = String(perPage);
                table._rerender?.();
            }
        });

        exportBtn?.addEventListener('click', () => exportCsv(table));

        requestAnimationFrame(() => {
            applyFilters();
        });
    });
}

function initAdminStudentsProgramFilterPortal(programFilter) {
    if (!programFilter || programFilter.dataset.asuMenuPortal === '1') return;

    const wrap = programFilter.closest('.asu-filter-select');
    const custom = wrap?.querySelector('.custom-select');
    const trigger = custom?.querySelector('.custom-select-trigger');
    const menu = custom?.querySelector('.custom-select-menu');
    if (!custom || !trigger || !menu) return;

    programFilter.dataset.asuMenuPortal = '1';
    document.body.appendChild(menu);
    menu.classList.add('asu-program-filter-menu');
    menu.hidden = true;

    const syncMenu = () => {
        const open = custom.classList.contains('is-open');
        menu.classList.toggle('is-open', open);
        if (!open) {
            menu.hidden = true;
            menu.style.width = '';
            menu.style.left = '';
            menu.style.maxHeight = '';
            menu.style.maxWidth = '';
            return;
        }

        menu.hidden = false;
        const rect = trigger.getBoundingClientRect();
        const viewportPadding = 16;
        const maxViewportWidth = window.innerWidth - viewportPadding * 2;

        menu.style.visibility = 'hidden';
        menu.style.display = 'grid';
        menu.style.width = 'max-content';
        menu.style.maxWidth = `${maxViewportWidth}px`;
        menu.style.left = '0';
        menu.style.top = '0';
        const contentWidth = menu.scrollWidth;
        const menuWidth = Math.min(
            maxViewportWidth,
            Math.max(Math.round(rect.width), contentWidth, 320)
        );

        let left = Math.round(rect.left);
        if (left + menuWidth > window.innerWidth - viewportPadding) {
            left = Math.max(viewportPadding, window.innerWidth - viewportPadding - menuWidth);
        }

        const spaceBelow = window.innerHeight - rect.bottom - 12;
        const openUp = spaceBelow < 120 && rect.top > spaceBelow;
        const maxHeight = openUp
            ? Math.min(320, rect.top - 18)
            : Math.min(320, window.innerHeight - rect.bottom - 18);

        menu.style.position = 'fixed';
        menu.style.left = `${left}px`;
        menu.style.width = `${menuWidth}px`;
        menu.style.maxWidth = `${maxViewportWidth}px`;
        menu.style.zIndex = '10050';
        menu.style.maxHeight = `${Math.max(120, maxHeight)}px`;
        menu.style.visibility = '';

        if (openUp) {
            menu.style.top = 'auto';
            menu.style.bottom = `${Math.round(window.innerHeight - rect.top + 6)}px`;
        } else {
            menu.style.bottom = 'auto';
            menu.style.top = `${Math.round(rect.bottom + 6)}px`;
        }
    };

    const observer = new MutationObserver(() => requestAnimationFrame(syncMenu));
    observer.observe(custom, { attributes: true, attributeFilter: ['class'] });
    window.addEventListener('resize', syncMenu);
    window.addEventListener('scroll', syncMenu, true);
    requestAnimationFrame(syncMenu);
}

function buildPartnerCreateReviewHtml(form) {
    const valueOf = name => {
        const el = form.elements.namedItem(name)
            || document.querySelector(`[name="${name}"][form="${form.id}"]`);
        if (!el) return '';
        if (el instanceof RadioNodeList) return (el.value || '').trim();
        return String(el.value || '').trim();
    };

    const rows = [
        ['Company', valueOf('company_name') || '?'],
        ['Contact', valueOf('contact_person') || '?'],
        ['Email', valueOf('contact_email') || '?'],
        ['Phone', valueOf('contact_number') || '?'],
        ['Address', valueOf('address') || '?'],
        ['Programs', (() => {
            const programs = [...document.querySelectorAll(`input[type="checkbox"][name="program_ids[]"][form="${form.id}"]:checked`)]
                .map(input => input.closest('label')?.querySelector('.partner-program-copy strong')?.textContent?.trim() || input.value)
                .filter(Boolean);
            return programs.length ? programs.join(', ') : 'None selected';
        })()],
        ['MOA/MOU', document.querySelector(`input[type="file"][name="moa_mou_file"][form="${form.id}"]`)?.files?.[0]?.name || 'No file selected'],
    ];

    const rowsHtml = rows.map(([label, value]) => `
        <div class="app-confirm-review-row">
            <dt>${escapeHtml(label)}</dt>
            <dd title="${escapeHtml(value)}">${escapeHtml(value)}</dd>
        </div>
    `).join('');

    return `
        <div class="app-confirm-review">
            <p class="app-confirm-review-intro">Please review the details below before creating this account.</p>
            <dl class="app-confirm-review-list">${rowsHtml}</dl>
            <p class="app-confirm-review-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="14" x="3" y="5" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                <span>Login credentials will be emailed automatically after creation.</span>
            </p>
        </div>
    `;
}

const PARTNER_CREATE_REVIEW_ICON = `
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
        <path d="M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v0a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/>
        <path d="m9 14 2 2 4-4"/>
    </svg>
`;

function initPartnerCreateReviewConfirm() {
    const form = document.getElementById('create-partner-form');
    if (!form || form.dataset.reviewConfirmReady === '1') return;
    form.dataset.reviewConfirmReady = '1';
    form.dataset.confirmVariant = 'review';
    form.dataset.confirmPreline = '0';

    form.addEventListener('submit', () => {
        form._confirmMessageHtml = buildPartnerCreateReviewHtml(form);
        form._confirmIconSvg = PARTNER_CREATE_REVIEW_ICON;
        form.dataset.confirmSubmit = 'review';
    }, true);
}

function initPartnerCreateAccordion() {
    document.querySelectorAll('[data-partner-create-accordion]').forEach(card => {
        if (card.dataset.accordionReady === '1') return;
        card.dataset.accordionReady = '1';

        const toggle = card.querySelector('[data-partner-create-toggle]');
        if (!toggle) return;

        const setOpen = (open, { scroll = false } = {}) => {
            card.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open && scroll) {
                window.setTimeout(() => {
                    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 40);
            }
        };

        const shouldStartOpen = Boolean(
            document.querySelector('.content .alert.danger, .content .alert.error, .flash.danger')
        );
        setOpen(shouldStartOpen);

        toggle.addEventListener('click', () => {
            const willOpen = !card.classList.contains('is-open');
            setOpen(willOpen, { scroll: willOpen });
        });

        card.addEventListener('invalid', event => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) return;
            const formId = target.getAttribute('form') || target.closest('form')?.id;
            if (formId !== 'create-partner-form') return;
            setOpen(true, { scroll: true });
        }, true);
    });
}

function initAdminPartnersDirectory() {
    document.querySelectorAll('[data-admin-partners-directory]').forEach(directory => {
        bindAdminDirectoryActions(directory);

        if (directory.dataset.partnersDirReady === '1') return;
        directory.dataset.partnersDirReady = '1';

        const table = directory.querySelector('.asu-partners-table');
        const statusFilter = directory.querySelector('[data-asu-partner-status-filter]');
        const search = directory.querySelector('.table-search');
        if (!table) return;

        let statusValue = 'all';

        const applyFilters = () => {
            if (!statusFilter) {
                table._applyRowFilter = null;
                search?.dispatchEvent(new Event('input'));
                return;
            }
            table._applyRowFilter = row => {
                if (statusValue === 'all') return true;
                return row.dataset.partnerStatus === statusValue;
            };
            search?.dispatchEvent(new Event('input'));
        };

        table._resetDirectoryFilters = () => {
            statusValue = 'all';
            if (statusFilter) {
                statusFilter.value = 'all';
                statusFilter._syncCustomSelect?.();
            }
            table._applyRowFilter = null;
        };

        statusFilter?.addEventListener('change', () => {
            statusValue = statusFilter.value || 'all';
            applyFilters();
        });

        requestAnimationFrame(applyFilters);
    });

    initAdminPartnerProgramsModal();
    initAdminPartnerEditDetailsModal();
}

function initAdminOjtPlacementDirectory() {
    document.querySelectorAll('[data-ojt-placement-directory]').forEach(directory => {
        if (directory.dataset.ojtPlacementReady === '1') return;
        directory.dataset.ojtPlacementReady = '1';

        const table = directory.querySelector('.data-table');
        const statusFilter = directory.querySelector('[data-ojt-placement-status-filter]');
        const search = directory.querySelector('.table-search');
        if (!table || !statusFilter) return;

        let statusValue = statusFilter.value || 'active';

        const applyFilters = () => {
            table._applyRowFilter = row => {
                if (statusValue === 'all') return true;
                return row.dataset.placementStatus === statusValue;
            };
            search?.dispatchEvent(new Event('input'));
        };

        table._resetDirectoryFilters = () => {
            statusValue = 'active';
            statusFilter.value = 'active';
            statusFilter._syncCustomSelect?.();
            applyFilters();
        };

        statusFilter.addEventListener('change', () => {
            statusValue = statusFilter.value || 'active';
            applyFilters();
        });

        requestAnimationFrame(applyFilters);
    });
}

function initAdminPartnerProgramsModal() {
    const overlay = document.getElementById('asuPartnerProgramsOverlay');
    const editOverlay = document.getElementById('asuPartnerEditProgramsOverlay');
    if (overlay && overlay.dataset.ready !== '1') {
        overlay.dataset.ready = '1';

        const titleEl = document.getElementById('asuPartnerProgramsTitle');
        const countEl = overlay.querySelector('[data-asu-programs-count]');
        const listEl = overlay.querySelector('[data-asu-programs-list]');

        const closeModal = () => {
            overlay.classList.remove('is-open');
            overlay.setAttribute('aria-hidden', 'true');
        };

        const openModal = (companyName, programs) => {
            if (titleEl) titleEl.textContent = companyName || 'Host Training Establishment';
            if (countEl) {
                const total = programs.length;
                countEl.textContent = `${total} program${total === 1 ? '' : 's'} accepted`;
            }
            if (listEl) {
                listEl.innerHTML = programs.map(program => {
                    const code = escapeHtml(program.code || '?');
                    const name = escapeHtml(program.name || 'Program');
                    const hours = Number(program.hours || 0);
                    const hoursLabel = hours > 0 ? `${hours} hrs` : '';
                    return `<article class="asu-partner-program-row">
                        <strong>${code}</strong>
                        <span>${name}</span>
                        <em>${escapeHtml(hoursLabel)}</em>
                    </article>`;
                }).join('');
            }
            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden', 'false');
        };

        document.addEventListener('click', event => {
            const trigger = event.target.closest('[data-asu-view-programs]');
            if (trigger) {
                event.preventDefault();
                event.stopPropagation();
                closeAdminActionMenus();
                let programs = [];
                try {
                    programs = JSON.parse(trigger.dataset.programs || '[]');
                } catch (error) {
                    programs = [];
                }
                if (!Array.isArray(programs)) programs = [];
                openModal(trigger.dataset.company || 'Host Training Establishment', programs);
                return;
            }

            if (event.target === overlay || event.target.closest('[data-asu-programs-close]')) {
                closeModal();
            }
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && overlay.classList.contains('is-open')) {
                closeModal();
            }
        });
    }

    if (!editOverlay || editOverlay.dataset.ready === '1') return;
    editOverlay.dataset.ready = '1';

    const editTitleEl = document.getElementById('asuPartnerEditProgramsTitle');
    const editListEl = editOverlay.querySelector('[data-asu-edit-programs-list]');
    const editCompanyIdInput = editOverlay.querySelector('[data-asu-edit-company-id]');

    const closeEditModal = () => {
        editOverlay.classList.remove('is-open');
        editOverlay.setAttribute('aria-hidden', 'true');
    };

    const openEditModal = (companyName, companyId, selectedIds) => {
        const catalog = Array.isArray(window.asuPartnerProgramCatalog) ? window.asuPartnerProgramCatalog : [];
        if (editTitleEl) editTitleEl.textContent = companyName || 'Host Training Establishment';
        if (editCompanyIdInput) editCompanyIdInput.value = String(companyId || '');
        const selected = new Set((selectedIds || []).map(id => String(id)));
        if (editListEl) {
            if (!catalog.length) {
                editListEl.innerHTML = '<p class="muted">No programs available yet.</p>';
            } else {
                editListEl.innerHTML = catalog.map(program => {
                    const id = String(program.id);
                    const checked = selected.has(id) ? ' checked' : '';
                    const hours = Number(program.hours || 0);
                    return `<label class="partner-program-option asu-partner-edit-option">
                        <input type="checkbox" name="program_ids[]" value="${escapeHtml(id)}"${checked}>
                        <span class="partner-program-copy">
                            <strong>${escapeHtml(program.code || '')}</strong>
                            <span>${escapeHtml(program.name || '')}</span>
                        </span>
                        <em>${hours > 0 ? `${hours} hrs` : ''}</em>
                    </label>`;
                }).join('');
            }
        }
        closeAdminActionMenus();
        editOverlay.classList.add('is-open');
        editOverlay.setAttribute('aria-hidden', 'false');
    };

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-asu-edit-programs]');
        if (trigger) {
            event.preventDefault();
            event.stopPropagation();
            let selectedIds = [];
            try {
                selectedIds = JSON.parse(trigger.dataset.selectedIds || '[]');
            } catch (error) {
                selectedIds = [];
            }
            if (!Array.isArray(selectedIds)) selectedIds = [];
            openEditModal(
                trigger.dataset.company || 'Host Training Establishment',
                trigger.dataset.companyId || '',
                selectedIds
            );
            return;
        }

        if (event.target === editOverlay || event.target.closest('[data-asu-edit-programs-close]')) {
            closeEditModal();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && editOverlay.classList.contains('is-open')) {
            closeEditModal();
        }
    });
}

function initAdminPartnerEditDetailsModal() {
    const overlay = document.getElementById('asuPartnerEditDetailsOverlay');
    if (!overlay || overlay.dataset.ready === '1') return;
    overlay.dataset.ready = '1';

    const titleEl = document.getElementById('asuPartnerEditDetailsTitle');
    const companyIdInput = overlay.querySelector('[data-asu-edit-partner-id]');
    const nameInput = overlay.querySelector('[data-asu-edit-partner-name]');
    const contactInput = overlay.querySelector('[data-asu-edit-partner-contact]');
    const emailInput = overlay.querySelector('[data-asu-edit-partner-email]');
    const phoneInput = overlay.querySelector('[data-asu-edit-partner-phone]');
    const addressInput = overlay.querySelector('[data-asu-edit-partner-address]');
    const moaHint = overlay.querySelector('[data-asu-edit-partner-moa-hint]');

    const closeModal = () => {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
    };

    const openModal = (trigger) => {
        if (typeof closeAdminActionMenus === 'function') {
            closeAdminActionMenus();
        }
        if (titleEl) titleEl.textContent = trigger.dataset.company || 'Host Training Establishment';
        if (companyIdInput) companyIdInput.value = trigger.dataset.companyId || '';
        if (nameInput) nameInput.value = trigger.dataset.company || '';
        if (contactInput) contactInput.value = trigger.dataset.contactPerson || '';
        if (emailInput) emailInput.value = trigger.dataset.email || '';
        if (phoneInput) phoneInput.value = trigger.dataset.contactNumber || '';
        if (addressInput) addressInput.value = trigger.dataset.address || '';
        if (moaHint) {
            moaHint.textContent = trigger.dataset.hasMoa === '1'
                ? 'Optional. Upload a file only if you need to replace the current MOA/MOU.'
                : 'Optional. Upload the establishment MOA/MOU document.';
        }
        const fileInput = overlay.querySelector('input[type="file"][name="moa_mou_file"]');
        if (fileInput) fileInput.value = '';
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        nameInput?.focus();
    };

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-asu-edit-partner]');
        if (trigger) {
            event.preventDefault();
            event.stopPropagation();
            openModal(trigger);
            return;
        }
        if (event.target === overlay || event.target.closest('[data-asu-edit-partner-close]')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && overlay.classList.contains('is-open')) {
            closeModal();
        }
    });
}

function initAdminProgramsDirectory() {
    document.querySelectorAll('[data-admin-programs-directory]').forEach(directory => {
        if (directory.dataset.programsDirReady === '1') return;
        directory.dataset.programsDirReady = '1';

        const table = directory.querySelector('.asu-programs-table');
        const statusFilter = directory.querySelector('[data-asu-program-status-filter]');
        const search = directory.querySelector('.table-search');
        if (!table) return;

        let statusValue = 'all';

        const snapshotRow = row => {
            const fields = [...row.querySelectorAll('[data-program-field]')];
            row._programSnapshot = fields.map(field => field.value);
        };

        const syncDirtyState = row => {
            const fields = [...row.querySelectorAll('[data-program-field]')];
            const snapshot = row._programSnapshot || [];
            const dirty = fields.some((field, index) => field.value !== snapshot[index]);
            row.classList.toggle('is-dirty', dirty);
        };

        const applyFilters = () => {
            if (!statusFilter) {
                table._applyRowFilter = null;
                search?.dispatchEvent(new Event('input'));
                return;
            }
            table._applyRowFilter = row => {
                if (statusValue === 'all') return true;
                return row.dataset.programStatus === statusValue;
            };
            search?.dispatchEvent(new Event('input'));
        };

        table._resetDirectoryFilters = () => {
            statusValue = 'all';
            if (statusFilter) {
                statusFilter.value = 'all';
                statusFilter._syncCustomSelect?.();
            }
            table._applyRowFilter = null;
        };

        statusFilter?.addEventListener('change', () => {
            statusValue = statusFilter.value || 'all';
            applyFilters();
        });

        table.querySelectorAll('tr.asu-program-row').forEach(snapshotRow);

        table.addEventListener('input', event => {
            const field = event.target.closest('[data-program-field]');
            if (!field) return;
            const row = field.closest('tr.asu-program-row');
            if (row) syncDirtyState(row);
        });

        table.addEventListener('change', event => {
            const field = event.target.closest('[data-program-field]');
            if (!field) return;
            const row = field.closest('tr.asu-program-row');
            if (!row) return;

            if (field.matches('[data-program-status-field]')) {
                row.dataset.programStatus = field.value === '1' ? 'active' : 'inactive';
                applyFilters();
            }
            syncDirtyState(row);
        });

        directory.addEventListener('submit', async event => {
            const deleteForm = event.target.closest('[data-program-delete]');
            if (!deleteForm || !directory.contains(deleteForm)) return;

            event.preventDefault();
            const row = deleteForm.closest('tr');
            const code = row?.querySelector('.asu-program-code-input')?.value?.trim() || 'this program';
            const confirmed = await showConfirmModal(`Delete ${code}? This cannot be undone.`, {
                title: 'Delete program',
                confirmText: 'Delete',
                variant: 'danger',
            });
            if (confirmed) deleteForm.submit();
        });

        requestAnimationFrame(applyFilters);
    });
}

function initAdminActivitiesFeed() {
    document.querySelectorAll('[data-activities-feed]').forEach(root => {
        if (root.dataset.activitiesReady === '1') return;
        root.dataset.activitiesReady = '1';

        const items = [...root.querySelectorAll('[data-activities-item]')];
        if (!items.length) return;

        const searchInput = root.querySelector('[data-activities-search]');
        const chips = [...root.querySelectorAll('[data-activities-chip]')];
        const empty = root.querySelector('[data-activities-empty]');
        const feed = root.querySelector('[data-activities-list]');
        const pager = root.querySelector('[data-activities-pagination]');
        const exportBtn = root.querySelector('[data-activities-export]');
        const resetBtn = root.querySelector('[data-activities-reset]');
        const perPage = Math.max(1, parseInt(root.dataset.perPage || '20', 10) || 20);

        let filter = 'all';
        let query = '';
        let page = 1;

        const matches = item => {
            const category = (item.dataset.category || '').toLowerCase();
            const hay = (item.dataset.search || '').toLowerCase();
            const chipOk = filter === 'all' || category === filter.toLowerCase();
            const searchOk = !query || hay.includes(query);
            return chipOk && searchOk;
        };

        const syncDaySections = () => {
            root.querySelectorAll('[data-activities-day]').forEach(day => {
                const visible = [...day.querySelectorAll('[data-activities-item]')].some(
                    item => !item.classList.contains('is-hidden')
                );
                day.classList.toggle('is-hidden', !visible);
            });
        };

        const renderPager = totalPages => {
            if (!pager) return;
            pager.innerHTML = '';
            if (totalPages <= 1) return;

            const addBtn = (label, targetPage, opts = {}) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'aa-page-btn' + (opts.active ? ' is-active' : '');
                btn.textContent = label;
                btn.disabled = !!opts.disabled;
                btn.addEventListener('click', () => {
                    page = targetPage;
                    apply();
                });
                pager.appendChild(btn);
            };

            addBtn('?', Math.max(1, page - 1), { disabled: page <= 1 });
            for (let i = 1; i <= totalPages; i += 1) {
                if (totalPages > 7 && Math.abs(i - page) > 2 && i !== 1 && i !== totalPages) {
                    if (i === 2 || i === totalPages - 1) {
                        const dots = document.createElement('span');
                        dots.className = 'aa-page-btn';
                        dots.style.border = 'none';
                        dots.style.background = 'transparent';
                        dots.style.cursor = 'default';
                        dots.textContent = '?';
                        pager.appendChild(dots);
                    }
                    continue;
                }
                addBtn(String(i), i, { active: i === page });
            }
            addBtn('?', Math.min(totalPages, page + 1), { disabled: page >= totalPages });
        };

        const apply = () => {
            const matched = items.filter(matches);
            const totalPages = Math.max(1, Math.ceil(matched.length / perPage));
            if (page > totalPages) page = totalPages;

            const start = (page - 1) * perPage;
            const end = start + perPage;
            const pageSet = new Set(matched.slice(start, end));

            items.forEach(item => {
                item.classList.toggle('is-hidden', !pageSet.has(item));
            });

            syncDaySections();

            const showEmpty = matched.length === 0;
            empty?.classList.toggle('is-hidden', !showEmpty);
            feed?.classList.toggle('is-hidden', showEmpty);
            renderPager(showEmpty ? 0 : totalPages);
        };

        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                filter = chip.dataset.filter || 'all';
                page = 1;
                chips.forEach(c => {
                    const active = c === chip;
                    c.classList.toggle('is-active', active);
                    c.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                apply();
            });
        });

        searchInput?.addEventListener('input', () => {
            query = (searchInput.value || '').trim().toLowerCase();
            page = 1;
            apply();
        });

        resetBtn?.addEventListener('click', () => {
            filter = 'all';
            query = '';
            page = 1;
            if (searchInput) searchInput.value = '';
            chips.forEach(c => {
                const active = (c.dataset.filter || '') === 'all';
                c.classList.toggle('is-active', active);
                c.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            apply();
        });

        exportBtn?.addEventListener('click', () => {
            const matched = items.filter(matches);
            const rows = [
                ['Activity', 'Category', 'Detail', 'When'],
                ...matched.map(item => [
                    item.dataset.title || '',
                    item.dataset.category || '',
                    item.dataset.detail || '',
                    item.dataset.when || '',
                ]),
            ];
            const csv = rows
                .map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
                .join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'recent-activities.csv';
            a.click();
            URL.revokeObjectURL(a.href);
        });

        apply();
    });
}

function initAdminStudentsDirectory() {
    document.querySelectorAll('[data-admin-students-directory]').forEach(directory => {
        const table = directory.querySelector('.asu-students-table');
        const programFilter = directory.querySelector('[data-asu-program-filter]');
        const exportBtn = directory.querySelector('[data-asu-export]');
        const search = directory.querySelector('.table-search');
        if (!table) return;

        let programId = 'all';

        const applyFilters = () => {
            if (!programFilter) {
                table._applyRowFilter = null;
                search?.dispatchEvent(new Event('input'));
                return;
            }
            table._applyRowFilter = row => {
                if (programId === 'all') return true;
                return row.dataset.programId === programId;
            };
            search?.dispatchEvent(new Event('input'));
        };

        table._resetDirectoryFilters = () => {
            programId = 'all';
            if (programFilter) {
                programFilter.value = 'all';
                programFilter._syncCustomSelect?.();
            }
            table._applyRowFilter = null;
        };

        programFilter?.addEventListener('change', () => {
            programId = programFilter.value || 'all';
            applyFilters();
        });

        initAdminStudentsProgramFilterPortal(programFilter);

        exportBtn?.addEventListener('click', () => exportCsv(table));

        requestAnimationFrame(applyFilters);
    });
}

function initWeeklyReportUpload() {
    const form = document.getElementById('weeklyReportForm');
    if (!form) return;

    initWeeklyReportDateRange(form);

    const dropzone = document.getElementById('wrDropzone');
    const fileInput = document.getElementById('wrFileInput');
    const browseLink = document.getElementById('wrBrowseLink');
    const previewRow = document.getElementById('wrPreviewRow');
    if (!dropzone || !fileInput || !browseLink || !previewRow) return;
    const textarea = form.querySelector('textarea[name="accomplishments"]');
    const charCurrent = form.querySelector('[data-char-current]');
    const MAX_SIZE = 10 * 1024 * 1024;
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];
    const ALLOWED_EXTS = ['.jpg', '.jpeg', '.png', '.pdf'];
    let proofFiles = [];

    if (textarea && charCurrent) {
        textarea.addEventListener('input', () => {
            charCurrent.textContent = textarea.value.length;
        });
    }

    browseLink.addEventListener('click', () => fileInput.click());
    dropzone.addEventListener('click', e => {
        if (e.target.closest('#wrBrowseLink')) return;
        fileInput.click();
    });

    dropzone.addEventListener('dragover', e => {
        e.preventDefault();
        dropzone.classList.add('is-dragover');
    });
    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('is-dragover');
    });
    dropzone.addEventListener('drop', e => {
        e.preventDefault();
        dropzone.classList.remove('is-dragover');
        addFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', () => {
        addFiles(fileInput.files);
        fileInput.value = '';
    });

    function addFiles(fileList) {
        for (const file of fileList) {
            const ext = '.' + file.name.split('.').pop().toLowerCase();
            if (!ALLOWED_TYPES.includes(file.type) && !ALLOWED_EXTS.includes(ext)) {
                alert(file.name + ' is not a supported file type. Please upload JPG, PNG, or PDF files.');
                continue;
            }
            if (file.size > MAX_SIZE) {
                alert(file.name + ' exceeds the 10MB file size limit.');
                continue;
            }
            proofFiles.push(file);
            renderPreview(file, proofFiles.length - 1);
        }
    }

    function renderPreview(file, index) {
        const item = document.createElement('div');
        item.className = 'wr-preview-item';
        item.dataset.index = index;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'wr-preview-remove';
        removeBtn.innerHTML = '&times;';
        removeBtn.addEventListener('click', () => removeFile(index));
        item.appendChild(removeBtn);

        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.alt = file.name;
            const reader = new FileReader();
            reader.onload = e => { img.src = e.target.result; };
            reader.readAsDataURL(file);
            item.appendChild(img);
        } else {
            const icon = document.createElement('div');
            icon.className = 'wr-file-icon';
            icon.innerHTML = '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M6 2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6H6Zm7 1.5L18.5 9H13V3.5ZM8 12h8v2H8v-2Zm0 4h5v2H8v-2Z"/></svg>'
                + '<span>' + (file.name.length > 12 ? file.name.slice(0, 10) + '...' : file.name) + '</span>';
            item.appendChild(icon);
        }

        previewRow.appendChild(item);
    }

    function removeFile(index) {
        proofFiles[index] = null;
        const el = previewRow.querySelector('[data-index="' + index + '"]');
        if (el) el.remove();
    }

    form.addEventListener('submit', e => {
        const activeFiles = proofFiles.filter(f => f !== null);
        if (activeFiles.length > 0) {
            const dt = new DataTransfer();
            activeFiles.forEach(f => dt.items.add(f));
            const dynamicInput = document.createElement('input');
            dynamicInput.type = 'file';
            dynamicInput.name = 'proof_files[]';
            dynamicInput.multiple = true;
            dynamicInput.hidden = true;
            dynamicInput.files = dt.files;
            form.appendChild(dynamicInput);
        }
    });
}

function initWeeklyReportDateRange(form) {
    const range = form.querySelector('[data-wr-date-range]');
    if (!range) return;

    const startPicker = range.querySelector('[data-wr-date="start"]');
    const endPicker = range.querySelector('[data-wr-date="end"]');
    if (!startPicker || !endPicker) return;

    const syncConstraints = () => {
        const startVal = startPicker.querySelector('input[type="hidden"]')?.value || '';
        const endVal = endPicker.querySelector('input[type="hidden"]')?.value || '';
        if (startVal) {
            endPicker.dataset.dateMin = startVal;
        } else {
            delete endPicker.dataset.dateMin;
        }
        if (endVal) {
            startPicker.dataset.dateMax = endVal;
        } else {
            delete startPicker.dataset.dateMax;
        }
    };

    startPicker.querySelector('input[type="hidden"]')?.addEventListener('change', syncConstraints);
    endPicker.querySelector('input[type="hidden"]')?.addEventListener('change', syncConstraints);
    syncConstraints();
}

function initWeeklyReportResubmitDateRange() {
    document.querySelectorAll('.records-resubmit-form [data-wr-date-range]').forEach(range => {
        const wrapper = range.closest('form');
        if (!wrapper) return;
        initWeeklyReportDateRange(wrapper);
    });
}

const APP_AJAX_ROLES = new Set(['admin', 'coordinator', 'student', 'partner']);

const ROLE_AJAX_ROUTES = {
    admin: new Set([
        'admin',
        'admin_users',
        'admin_registration_requests',
        'admin_password_reset_requests',
        'admin_coordinators',
        'admin_partners',
        'admin_email_logs',
        'admin_evaluations',
        'admin_ojt_placement',
        'admin_programs',
        'admin_terms',
        'admin_reports',
        'admin_report',
        'admin_recent_activities',
        'chat',
    ]),
    coordinator: new Set([
        'coordinator',
        'coordinator_manage',
        'coordinator_students',
        'coordinator_moa_mou',
        'coordinator_evaluations',
        'coordinator_student_final',
        'chat',
    ]),
    student: new Set([
        'student',
        'student_records',
        'student_reports',
        'student_timeline',
        'student_documents',
        'student_documents_final',
        'student_documents_other',
        'student_evaluation',
        'student_settings',
        'student_password',
        'student_profile',
        'chat',
    ]),
    partner: new Set([
        'partner',
        'partner_portal',
        'partner_submissions',
        'partner_settings',
        'partner_password',
        'partner_profile',
        'partner_evaluate',
        'chat',
    ]),
};

const ADMIN_USER_ROUTES = new Set([
    'admin_users',
    'admin_registration_requests',
    'admin_password_reset_requests',
    'admin_coordinators',
    'admin_partners',
]);

const STUDENT_DOC_ROUTES = new Set([
    'student_documents',
    'student_documents_final',
    'student_documents_other',
]);

const SIDEBAR_HOME_ROUTES = {
    admin: 'admin',
    coordinator: 'coordinator',
    student: 'student',
    partner: 'partner',
};

const SIDEBAR_ROUTE_ALIASES = {
    admin_reports: ['admin_reports', 'admin_report'],
    student_settings: ['student_settings', 'student_password', 'student_profile'],
    partner_settings: ['partner_settings', 'partner_password', 'partner_profile'],
};

function getAppRole() {
    for (const className of document.body.classList) {
        if (className.startsWith('role-')) {
            return className.slice(5);
        }
    }
    return '';
}

function getRoleAjaxRoutes(role = getAppRole()) {
    return ROLE_AJAX_ROUTES[role] || new Set();
}

function parseAppRoute(href) {
    if (!href) return '';
    try {
        const url = new URL(href, window.location.href);
        return url.searchParams.get('r') || '';
    } catch {
        return '';
    }
}

function parseAppStage(href) {
    if (!href) return '';
    try {
        const url = new URL(href, window.location.href);
        return url.searchParams.get('stage') || '';
    } catch {
        return '';
    }
}

function isAppAjaxNavLink(link) {
    if (!link || link.target === '_blank' || link.hasAttribute('download')) return false;
    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return false;
    if (href.includes('logout.php')) return false;

    const role = getAppRole();
    if (!APP_AJAX_ROLES.has(role)) return false;

    const route = parseAppRoute(href);
    return getRoleAjaxRoutes(role).has(route);
}

function buildAppAjaxUrl(href) {
    const url = new URL(href, window.location.href);
    url.searchParams.set('partial', 'content');
    return url.toString();
}

function runInjectedScripts(container) {
    if (!container) return;

    container.querySelectorAll('script').forEach(oldScript => {
        const code = oldScript.textContent || '';
        if (oldScript.src) {
            const script = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => script.setAttribute(attr.name, attr.value));
            oldScript.replaceWith(script);
            return;
        }

        const domReadyMatch = code.match(
            /document\.addEventListener\s*\(\s*['"]DOMContentLoaded['"]\s*,\s*function\s*\(\)\s*\{([\s\S]*)\}\s*\)\s*;?\s*$/
        );

        try {
            if (domReadyMatch) {
                new Function(domReadyMatch[1])();
            } else if (code.trim()) {
                new Function(code)();
            }
        } catch (error) {
            console.error('Failed to run injected script:', error);
        }

        oldScript.remove();
    });
}

function routeMatchesNavLink(route, linkRoute, homeRoute) {
    if (route === homeRoute && linkRoute === homeRoute) return true;

    for (const [navRoute, aliases] of Object.entries(SIDEBAR_ROUTE_ALIASES)) {
        if (aliases.includes(route) && linkRoute === navRoute) return true;
    }

    return linkRoute === route;
}

function updateAppSidebarActive(route, currentHref = window.location.href) {
    const sidebar = document.querySelector('.sidebar');
    const role = getAppRole();
    const homeRoute = SIDEBAR_HOME_ROUTES[role];
    if (!sidebar || !homeRoute) return;

    document.querySelectorAll('.sidebar .nav-link, .student-docs-sheet-item').forEach(link => link.classList.remove('active'));

    if (role === 'admin') {
        const userGroup = sidebar.querySelector('.nav-group:not(.nav-group--student-docs)');
        userGroup?.classList.toggle('open', ADMIN_USER_ROUTES.has(route));
    }

    if (role === 'student') {
        const docGroup = sidebar.querySelector('.nav-group--student-docs');
        const isDocRoute = STUDENT_DOC_ROUTES.has(route);
        docGroup?.classList.toggle('nav-group--active', isDocRoute);
        if (isStudentMobileNav()) {
            window.__closeStudentDocsSheet?.(false);
        } else {
            docGroup?.classList.toggle('open', isDocRoute);
        }
    }

    const currentStage = parseAppStage(currentHref) || (route === 'student_documents' ? '1' : '');
    document.querySelectorAll('.sidebar .nav-link[href], .nav-group-items .nav-link[href]').forEach(link => {
        const href = link.href || link.getAttribute('href') || '';
        const linkRoute = parseAppRoute(href);
        if (!linkRoute) return;

        if (!routeMatchesNavLink(route, linkRoute, homeRoute)) return;

        // Stage links share r=student_documents — only the matching stage stays active.
        const linkStage = parseAppStage(href);
        if (linkRoute === 'student_documents' && linkStage && linkStage !== currentStage) {
            return;
        }

        link.classList.add('active');
    });
}

function updateAppTopbar(title) {
    const heading = document.querySelector('.topbar h1');
    if (heading && title) {
        heading.textContent = title;
    }
    if (title) {
        document.title = title;
    }
}

function destroyLiveChatIfNeeded() {
    if (typeof window.__liveChatCleanup === 'function') {
        window.__liveChatCleanup();
        window.__liveChatCleanup = null;
    }
}

function reinitAppPageContent() {
    initToasts();
    initFloatingLabels();
    initCustomFilterSelects();
    initCustomDatePickers();
    initDateTimePickers();
    initPhoneInputs();
    initCharacterCounters();
    initDtrTimeLocks();
    initConfirmActions();
    initViewToggles();
    initTimelineDetails();
    initStudentTimelineFilters();
    initEmailLogViews();
    initEmailLogsFeed();
    initRequirementReviewModals();
    initRegistrationRequestsReview();
    initPasswordResetRequests();
    initAdminStudentsDirectory();
    initAdminProgramsDirectory();
    initAdminPartnersDirectory();
    initAdminOjtPlacementDirectory();
    initPartnerCreateAccordion();
    initPartnerCreateReviewConfirm();
    initAdminCreateStudentModal();
    initAdminTermsPage();
    initAdminActivitiesFeed();
    initCoordinatorAvailability();
    initPartnerAvailability();
    initWizards();
    initEnrollmentAutomation();
    initEnrollmentDirectory();
    initEnrollmentWizardModal();
    initEnrollmentWizardSelectPortals();
    initEnrollmentCorUpload();
    initEnrollmentFilters();
    initMyStudentsDirectory();
    initMoaLibrary();
    initCoordinatorCardAlignment();
    initCapitalizeWordInputs();
    initStudentProfilePhotoPreview();
    initPartnerPasswordChange();
    initStudentPasswordChange();
    initPartnerPortalRoster();
    initPartnerSubmissions();
    initStudentModal();
    try { initWeeklyReportUpload(); } catch (err) { console.warn('Weekly report upload init failed:', err); }
    try { initWeeklyReportResubmitDateRange(); } catch (err) { console.warn('Weekly report date range init failed:', err); }
    document.querySelectorAll('.content .data-table').forEach(table => enhanceTable(table));
    // Bind action menus after tables are enhanced so pagination hooks attach correctly.
    initAdminUserActions();

    // Only clear chart data when no dashboard chart canvases remain (admin no longer has monthlyChart).
    if (!document.getElementById('monthlyChart')
        && !document.getElementById('statusChart')
        && !document.getElementById('courseChart')) {
        window.dashboardCharts = null;
    }
    renderDashboardCharts();
    initLiveChat();
    initTextMarquees();
}

function initAppAjaxNav() {
    const role = getAppRole();
    if (!APP_AJAX_ROLES.has(role)) return;

    const content = document.querySelector('section.content');
    if (!content) return;

    let loading = false;

    const loadPage = async (href, { pushState = true } = {}) => {
        if (loading) return;

        const route = parseAppRoute(href);
        if (!getRoleAjaxRoutes(role).has(route)) {
            window.location.assign(href);
            return;
        }

        loading = true;
        content.classList.add('is-ajax-loading');

        try {
            destroyLiveChatIfNeeded();

            const response = await fetch(buildAppAjaxUrl(href), {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error('Failed to load page.');
            }

            const html = await response.text();
            content.innerHTML = html;

            const pageRoot = content.querySelector('[data-ajax-page]');
            const pageTitle = pageRoot?.dataset.pageTitle || '';
            const pageRoute = pageRoot?.dataset.route || route;

            runInjectedScripts(content);
            updateAppTopbar(pageTitle);
            updateAppSidebarActive(pageRoute, href);
            reinitAppPageContent();

            if (pushState) {
                history.pushState({ appAjaxNav: true, route: pageRoute, role }, '', href);
            }

            content.scrollTop = 0;
            window.scrollTo({ top: 0, behavior: 'auto' });
        } catch {
            window.location.assign(href);
        } finally {
            loading = false;
            content.classList.remove('is-ajax-loading');
        }
    };

    document.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link || !isAppAjaxNavLink(link)) return;

        const inSidebar = link.closest('.sidebar');
        const inContent = link.closest('section.content');
        if (!inSidebar && !inContent) return;

        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return;

        event.preventDefault();
        loadPage(link.href);
    });

    window.addEventListener('popstate', () => {
        if (!APP_AJAX_ROLES.has(getAppRole())) return;

        const route = parseAppRoute(window.location.href);
        if (route === 'partner_submissions' && document.querySelector('[data-ps-submissions]')) {
            return;
        }

        loadPage(window.location.href, { pushState: false });
    });
}

/**
 * AMA Practicum Live Chat
 * Vanilla JS + Fetch API with 3-second polling (no WebSockets).
 */
function initLiveChat() {
    'use strict';

    const app = document.getElementById('chatApp');
    if (!app) return;

    const endpoint = app.dataset.endpoint || 'index.php?r=chat_api';
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

    function parseMessageDate(dateString) {
        const date = new Date(String(dateString || '').replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? null : date;
    }

    function formatTime(dateString) {
        const date = parseMessageDate(dateString);
        if (!date) return dateString;
        return date.toLocaleTimeString(undefined, {
            hour: 'numeric',
            minute: '2-digit',
        });
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
        return date.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    }

    function dateKey(dateString) {
        const date = parseMessageDate(dateString);
        if (!date) return String(dateString || '');
        return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
    }

    function formatRoleLabel(role) {
        return String(role || '')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, function (char) { return char.toUpperCase(); });
    }

    function partnerInitial() {
        return String(partnerName || 'C').charAt(0).toUpperCase();
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

    function buildDayDivider(label) {
        const divider = document.createElement('div');
        divider.className = 'chat-day-divider';
        divider.setAttribute('role', 'separator');
        divider.innerHTML = '<span>' + escapeHtml(label) + '</span>';
        return divider;
    }

    function buildMessageNode(message, options) {
        options = options || {};
        const isMine = Number(message.sender_id) === currentUserId
            && String(message.sender_role) === currentUserRole;
        const isGrouped = Boolean(options.isGrouped);
        const showAvatar = Boolean(options.showAvatar);

        const article = document.createElement('article');
        article.className = 'chat-message'
            + (isMine ? ' is-mine' : ' is-theirs')
            + (isGrouped ? ' is-grouped' : '')
            + (showAvatar ? ' has-avatar' : '');
        article.dataset.messageId = String(message.id || '');

        let avatarHtml = '';
        if (!isMine) {
            avatarHtml = showAvatar
                ? '<span class="chat-message__avatar" aria-hidden="true">' + escapeHtml(partnerInitial()) + '</span>'
                : '<span class="chat-message__avatar-spacer" aria-hidden="true"></span>';
        }

        article.innerHTML =
            avatarHtml +
            '<div class="chat-message__stack">' +
            '<div class="chat-message__bubble"><p>' + escapeHtml(message.message_text || '') + '</p></div>' +
            '<time datetime="' + escapeHtml(message.created_at || '') + '">' +
            escapeHtml(formatTime(message.created_at || '')) +
            '</time>' +
            '</div>';

        return article;
    }

    function emptyStateHtml(messageHtml) {
        return '' +
            '<div class="chat-empty-state__icon" aria-hidden="true">' +
            '<svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v10H7l-3 3V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>' +
            '</div>' +
            '<p>' + messageHtml + '</p>';
    }

    function clearMessagesPanel(message) {
        if (!messagesEl) return;

        lastMessageSignature = '';
        lastMessageCount = 0;
        messagesEl.innerHTML =
            '<div class="chat-empty-state chat-empty-state--inline" id="chatEmptyState">' +
            emptyStateHtml(escapeHtml(message || ('Loading conversation with ' + partnerName + '...'))) +
            '</div>';
    }

    function isMineMessage(message) {
        return Number(message.sender_id) === currentUserId
            && String(message.sender_role) === currentUserRole;
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
            empty.innerHTML = emptyStateHtml('Start the conversation with <strong>' + escapeHtml(partnerName) + '</strong>.');
            messagesEl.appendChild(empty);
            lastMessageCount = 0;
            return;
        }

        let lastKey = null;
        let prevMine = null;
        messages.forEach(function (message, index) {
            const key = dateKey(message.created_at || '');
            const mine = isMineMessage(message);
            const next = messages[index + 1] || null;
            const nextMine = next ? isMineMessage(next) : null;
            const nextKey = next ? dateKey(next.created_at || '') : null;
            const showAvatar = !mine && (next === null || nextMine === true || nextKey !== key);
            let isGrouped = prevMine !== null && prevMine === mine && key === lastKey;

            if (key !== lastKey) {
                lastKey = key;
                isGrouped = false;
                messagesEl.appendChild(buildDayDivider(formatDayLabel(message.created_at || '')));
            }

            messagesEl.appendChild(buildMessageNode(message, {
                isGrouped: isGrouped,
                showAvatar: showAvatar,
            }));
            prevMine = mine;
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

        let response;
        try {
            response = await fetch(endpoint, {
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
        } catch (error) {
            throw new Error('Unable to reach the chat server. Please refresh and try again.');
        }

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
        button.classList.remove('has-unread');

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

    function resizeComposer() {
        if (!inputEl) return;
        inputEl.style.height = 'auto';
        inputEl.style.height = Math.min(inputEl.scrollHeight, 110) + 'px';
    }

    function relocateCharCounter() {
        if (!composerEl) return;
        const counter = composerEl.querySelector('.char-counter');
        if (counter && counter.parentElement !== composerEl) {
            composerEl.appendChild(counter);
        }
    }

    inputEl?.addEventListener('input', resizeComposer);
    resizeComposer();
    window.setTimeout(relocateCharCounter, 0);
    window.setTimeout(relocateCharCounter, 250);

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
}




