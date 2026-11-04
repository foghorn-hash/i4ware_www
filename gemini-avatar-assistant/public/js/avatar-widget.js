/**
 * Gemini AI Avatar Assistant Widget
 * Handles interactive avatar animations, Web Audio API amplitude analysis,
 * speech synthesis playback, REST API communication, and accessibility.
 *
 * @package Gemini_Avatar_Assistant
 */

/* global geminiAvatarConfig */
(function () {
    'use strict';

    if (typeof geminiAvatarConfig === 'undefined') {
        return;
    }

    const config = geminiAvatarConfig;
    let currentLang = config.currentLang || 'fi';
    let i18n = (config.languages && config.languages[currentLang]) ? config.languages[currentLang] : config.i18n;

    // Elements
    let widgetEl, launcherEl, drawerEl, messagesEl, inputEl, sendBtnEl;
    let soundBtnEl, resetBtnEl, closeBtnEl, micBtnEl, micStatusEl, micCancelEl;
    let starterChipsEl, charCountEl, headerStatusDot, headerStateText, launcherStatus;
    let portalOuterRing, mouthOverlay, vectorMouth, audioWavesEl, stopSpeechStageBtn;

    // Audio & Web Audio API state
    let audioCtx = null;
    let currentAudioSource = null;
    let analyserNode = null;
    let animFrameId = null;
    let isPlayingAudio = false;

    // Speech recognition state
    let speechRecognizer = null;
    let isRecordingVoice = false;

    // Widget state
    let isOpen = false;
    let isGenerating = false;
    let soundEnabled = config.enableSpeech;
    let conversationHistory = [];

    // Load sound preference from session
    try {
        const storedSound = sessionStorage.getItem('gemini_avatar_sound');
        if (storedSound !== null) {
            soundEnabled = storedSound === '1';
        }
    } catch (e) {
        // Ignore storage errors
    }

    /**
     * Initialize DOM elements and event listeners
     */
    function init() {
        widgetEl = document.getElementById('gemini-avatar-widget');
        launcherEl = document.getElementById('gemini-avatar-launcher');
        drawerEl = document.getElementById('gemini-avatar-drawer');
        messagesEl = document.getElementById('gemini-messages-list');
        inputEl = document.getElementById('gemini-chat-input');
        sendBtnEl = document.getElementById('gemini-btn-send');
        soundBtnEl = document.getElementById('gemini-btn-sound');
        resetBtnEl = document.getElementById('gemini-btn-reset');
        closeBtnEl = document.getElementById('gemini-btn-close');
        micBtnEl = document.getElementById('gemini-btn-mic');
        micStatusEl = document.getElementById('gemini-mic-status');
        micCancelEl = document.getElementById('gemini-mic-cancel');
        starterChipsEl = document.getElementById('gemini-starter-chips');
        charCountEl = document.getElementById('gemini-char-count');
        headerStatusDot = document.getElementById('gemini-header-status-dot');
        headerStateText = document.getElementById('gemini-header-state-text');
        launcherStatus = document.getElementById('gemini-launcher-status');
        portalOuterRing = document.getElementById('gemini-portal-ring-outer');
        mouthOverlay = document.getElementById('gemini-mouth-overlay');
        vectorMouth = document.getElementById('gemini-vector-mouth');
        audioWavesEl = document.getElementById('gemini-audio-waves');
        stopSpeechStageBtn = document.getElementById('gemini-stage-stop-btn');

        if (!widgetEl || !launcherEl || !drawerEl) {
            console.error('[Gemini Avatar] Required DOM elements missing');
            return;
        }

        // Ensure soundEnabled defaults to true if config flag missing
        if (typeof config.enableSpeech !== 'undefined') {
            soundEnabled = config.enableSpeech;
        } else {
            soundEnabled = true;
        }

        // Update sound UI
        updateSoundUI();

        // Bind events
        launcherEl.addEventListener('click', openDrawer);
        closeBtnEl.addEventListener('click', closeDrawer);
        if (soundBtnEl) soundBtnEl.addEventListener('click', toggleSound);
        if (resetBtnEl) resetBtnEl.addEventListener('click', resetConversation);
        if (stopSpeechStageBtn) {
            stopSpeechStageBtn.addEventListener('click', stopAudio);
        }

        const form = document.getElementById('gemini-chat-form');
        if (form) {
            form.addEventListener('submit', onFormSubmit);
        }

        if (inputEl) {
            inputEl.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    onFormSubmit(e);
                }
            });
            inputEl.addEventListener('input', onInputChange);
        }

        if (micBtnEl) {
            micBtnEl.addEventListener('click', toggleVoiceInput);
        }
        if (micCancelEl) {
            micCancelEl.addEventListener('click', stopVoiceInput);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen) {
                closeDrawer();
            }
        });

        const langPills = document.querySelectorAll('.gemini-lang-pill');
        langPills.forEach(function (pill) {
            pill.addEventListener('click', function (e) {
                e.preventDefault();
                const selected = pill.getAttribute('data-lang');
                if (selected && selected !== currentLang) {
                    setLanguage(selected);
                }
            });
        });

        // Render initial UI
        renderInitialState();
        setStatus('ready');
    }

    /**
     * Open the chat drawer
     */
    function openDrawer() {
        isOpen = true;
        widgetEl.classList.add('is-open');
        drawerEl.removeAttribute('hidden');
        launcherEl.setAttribute('aria-expanded', 'true');
        // Warm up Web Audio context on user interaction
        ensureAudioContext();
        // Focus input
        if (inputEl) {
            setTimeout(() => inputEl.focus(), 150);
        }
        scrollToBottom();
    }

    /**
     * Close the chat drawer
     */
    function closeDrawer() {
        isOpen = false;
        stopAudio();
        stopVoiceInput();
        widgetEl.classList.remove('is-open');
        drawerEl.setAttribute('hidden', '');
        launcherEl.setAttribute('aria-expanded', 'false');
        launcherEl.focus();
    }

    /**
     * Toggle speech synthesis on / off
     */
    function toggleSound() {
        soundEnabled = !soundEnabled;
        try {
            sessionStorage.setItem('gemini_avatar_sound', soundEnabled ? '1' : '0');
        } catch (e) { }
        updateSoundUI();
        if (!soundEnabled) {
            stopAudio();
        }
    }

    function updateSoundUI() {
        if (!soundBtnEl) return;
        if (soundEnabled) {
            soundBtnEl.classList.add('sound-on');
            soundBtnEl.classList.remove('sound-off');
            soundBtnEl.title = i18n.soundOn + ' (klikkaa mykistääksesi)';
            soundBtnEl.setAttribute('aria-label', i18n.soundOn);
        } else {
            soundBtnEl.classList.add('sound-off');
            soundBtnEl.classList.remove('sound-on');
            soundBtnEl.title = i18n.soundOff + ' (klikkaa kytkeäksesi puhe päälle)';
            soundBtnEl.setAttribute('aria-label', i18n.soundOff);
        }
    }

    /**
     * Reset conversation to initial state
     */
    function resetConversation() {
        stopAudio();
        stopVoiceInput();
        conversationHistory = [];
        messagesEl.innerHTML = '';
        renderInitialState();
        setStatus('ready');
        if (inputEl) {
            inputEl.value = '';
            onInputChange();
            inputEl.focus();
        }
    }

    /**
     * Set widget status: ready | thinking | speaking | error
     */
    function setStatus(state) {
        const portal = document.getElementById('gemini-photo-avatar-portal');
        const stage = document.getElementById('gemini-stage-visualizer');
        const speechCtrl = document.getElementById('gemini-stage-speech-ctrl');

        if (launcherStatus) {
            launcherStatus.className = 'gemini-status-indicator status-' + state;
        }
        if (headerStatusDot) {
            headerStatusDot.className = 'gemini-status-dot status-' + state;
        }
        if (portal) {
            portal.classList.toggle('is-speaking', state === 'speaking');
            portal.classList.toggle('is-thinking', state === 'thinking');
        }
        if (stage) {
            stage.classList.toggle('is-speaking', state === 'speaking');
            stage.classList.toggle('is-thinking', state === 'thinking');
        }
        if (speechCtrl) {
            speechCtrl.style.display = (state === 'speaking') ? 'block' : 'none';
        }
        if (headerStateText) {
            switch (state) {
                case 'thinking':
                    headerStateText.textContent = i18n.thinking;
                    headerStateText.style.color = 'var(--gemini-accent)';
                    break;
                case 'speaking':
                    headerStateText.textContent = i18n.speaking;
                    headerStateText.style.color = 'var(--gemini-pink)';
                    break;
                case 'error':
                    headerStateText.textContent = i18n.error;
                    headerStateText.style.color = 'var(--gemini-red)';
                    break;
                default:
                    headerStateText.textContent = i18n.ready;
                    headerStateText.style.color = 'var(--gemini-accent)';
                    break;
            }
        }
    }

    /**
     * Switch language
     */
    function setLanguage(newLang) {
        if (!config.languages || !config.languages[newLang]) return;
        currentLang = newLang;
        i18n = config.languages[currentLang];
        // Update pills
        document.querySelectorAll('.gemini-lang-pill').forEach(function (pill) {
            pill.classList.toggle('active', pill.getAttribute('data-lang') === currentLang);
        });
        // RTL handling
        if (drawerEl) {
            if (currentLang === 'ar') {
                drawerEl.setAttribute('dir', 'rtl');
                drawerEl.classList.add('is-rtl');
            } else {
                drawerEl.setAttribute('dir', 'ltr');
                drawerEl.classList.remove('is-rtl');
            }
        }
        // Update tooltip
        const launcherTooltip = document.getElementById('gemini-launcher-tooltip-text');
        if (launcherTooltip) {
            if (currentLang === 'ar') launcherTooltip.textContent = 'اسأل المساعد الذكي لـ i4ware!';
            else if (currentLang === 'en') launcherTooltip.textContent = 'Ask i4ware AI Assistant!';
            else launcherTooltip.textContent = 'Kysy i4waren AI-avustajalta!';
        }
        // Update privacy text
        const privacyText = document.getElementById('gemini-privacy-text');
        if (privacyText) {
            if (currentLang === 'ar') {
                privacyText.innerHTML = 'أنا المساعد الذكي لموقع i4ware Software. تتم معالجة الرسائل عبر خدمة Google Gemini. <a href="' + escapeHtml(config.privacyUrl) + '" target="_blank" rel="noopener">سياسة الخصوصية</a>';
            } else if (currentLang === 'en') {
                privacyText.innerHTML = 'I am i4ware Software’s AI assistant. Messages are processed via Google Gemini. <a href="' + escapeHtml(config.privacyUrl) + '" target="_blank" rel="noopener">Privacy Policy</a>';
            } else {
                privacyText.innerHTML = 'Olen i4ware Softwaren AI-avustaja. Viestit käsitellään Googlen Gemini -palvelussa. <a href="' + escapeHtml(config.privacyUrl) + '" target="_blank" rel="noopener">Tietosuojaseloste</a>';
            }
        }
        // Placeholder & mic text
        if (inputEl) {
            inputEl.placeholder = i18n.inputPlaceholder;
        }
        if (micStatusEl) {
            const micStatusText = micStatusEl.querySelector('.mic-status-text');
            if (micStatusText) micStatusText.textContent = i18n.listening;
        }
        if (stopSpeechStageBtn) {
            const span = stopSpeechStageBtn.querySelector('span');
            if (span) span.textContent = i18n.stopSpeech;
        }
        updateSoundUI();
        setStatus('ready');
        // Save language preference
        try {
            sessionStorage.setItem('gemini_avatar_lang', currentLang);
        } catch (e) { }
    }

    /**
     * Render initial greeting and starter chips
     */
    function renderInitialState() {
        const initialGreeting = (i18n && i18n.greeting) ? i18n.greeting : config.greeting;
        appendMessage('assistant', initialGreeting, []);
        renderStarterChips();
    }

    /**
     * Render starter chips
     */
    function renderStarterChips() {
        if (starterChipsEl && i18n.starters && i18n.starters.length) {
            starterChipsEl.innerHTML = '';
            i18n.starters.forEach(function (promptText) {
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'gemini-starter-chip';
                chip.innerHTML = '<span>' + escapeHtml(promptText) + '</span>' +
                    '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>';
                chip.addEventListener('click', function () {
                    if (isGenerating) return;
                    sendQuestion(promptText);
                });
                starterChipsEl.appendChild(chip);
            });
            starterChipsEl.style.display = 'flex';
        }
    }

    /**
     * Append a message
     */
    function appendMessage(role, text, sources, audioData, buttons) {
        const msgEl = document.createElement('div');
        msgEl.className = 'gemini-msg msg-' + role;
        const bubbleEl = document.createElement('div');
        bubbleEl.className = 'gemini-bubble';
        bubbleEl.innerHTML = formatMarkdown(text);
        msgEl.appendChild(bubbleEl);

        if (role === 'assistant' && sources && sources.length) {
            const sourcesEl = document.createElement('div');
            sourcesEl.className = 'gemini-msg-sources';
            sourcesEl.innerHTML = '<span class="gemini-sources-label">' + i18n.sources + '</span>';
            sources.forEach(function (s) {
                const tag = document.createElement('a');
                tag.className = 'gemini-source-tag';
                tag.href = s.url;
                tag.target = '_blank';
                tag.rel = 'noopener';
                tag.innerHTML = '<svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg> ' + escapeHtml(s.title);
                sourcesEl.appendChild(tag);
            });
            bubbleEl.appendChild(sourcesEl);
        }

        if (role === 'assistant' && buttons && buttons.length) {
            const btnsWrap = document.createElement('div');
            btnsWrap.className = 'gemini-msg-buttons';
            buttons.forEach(function (btn) {
                const btnEl = document.createElement('button');
                btnEl.type = 'button';
                btnEl.className = 'gemini-action-btn' + (btn.type ? ' action-' + btn.type : '');
                let iconSvg = '';
                if (btn.target && btn.target.indexOf('marketplace.atlassian.com') !== -1) {
                    iconSvg = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>';
                } else if (btn.target === '#paypal-advertising' || btn.type === 'modal') {
                    iconSvg = '<svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M20.007 6.467a3.003 3.003 0 0 0-3.003-2.92h-6.223L8.03 16.784a.5.5 0 0 0 .49.584h2.528l1.458-9.055a.5.5 0 0 1 .494-.42h4.524a1.002 1.002 0 0 1 .983 1.201l-1.458 9.055a.5.5 0 0 1-.494.42H11.53l.42 2.61a.5.5 0 0 0 .493.42H16.03a3.003 3.003 0 0 0 2.977-2.58l1.458-9.056a3.003 3.003 0 0 0-.458-2.072z"></path></svg>';
                } else {
                    iconSvg = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 16 16 12 12 8"></polyline><line x1="8" y1="12" x2="16" y2="12"></line></svg>';
                }
                btnEl.innerHTML = iconSvg + '<span>' + escapeHtml(btn.label) + '</span>';
                btnEl.addEventListener('click', function () {
                    handleActionButtonClick(btn);
                });
                btnsWrap.appendChild(btnEl);
            });
            msgEl.appendChild(btnsWrap);
        }

        if (role === 'assistant' && audioData) {
            const actionsEl = document.createElement('div');
            actionsEl.className = 'gemini-msg-audio-actions';
            const playBtn = document.createElement('button');
            playBtn.type = 'button';
            playBtn.className = 'gemini-msg-audio-btn';
            playBtn.innerHTML = '<svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg> <span>' + i18n.replay + '</span>';
            playBtn.addEventListener('click', function () {
                if (isPlayingAudio) {
                    stopAudio();
                } else {
                    playBase64Wav(audioData);
                }
            });
            actionsEl.appendChild(playBtn);
            msgEl.appendChild(actionsEl);
        }

        messagesEl.appendChild(msgEl);
        scrollToBottom();
        return msgEl;
    }

    /**
     * Handle action button clicks
     */
    function handleActionButtonClick(btn) {
        if (!btn || !btn.target) return;

        // PayPal / modal – direct navigation to the anchor
        if (btn.target === '#paypal-advertising' || btn.type === 'modal') {
            window.location.href = config.homeUrl + '#paypal-advertising';
            return;
        }

        // If a modal element exists, scroll to it
        const modalEl = document.getElementById('i4ware-paypal-modal') ||
            document.querySelector('.i4ware-support-table-wrapper') ||
            document.getElementById('paypal-advertising');
        if (modalEl) {
            modalEl.scrollIntoView({ behavior: 'smooth' });
            return;
        }

        // External links (full URLs)
        if (btn.target && (btn.target.startsWith('http://') || btn.target.startsWith('https://'))) {
            window.open(btn.target, '_blank', 'noopener,noreferrer');
            return;
        }

        // In‑page anchors
        if (btn.target && btn.target.startsWith('#')) {
            const el = document.querySelector(btn.target);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth' });
            } else {
                window.location.href = config.homeUrl + btn.target;
            }
            return;
        }

        // Fallback for normal relative links
        if (btn.target) {
            const target = btn.target.startsWith('/') ? btn.target : '/' + btn.target;
            window.location.href = config.homeUrl + target;
        }
    }

    /**
     * Show thinking indicator
     */
    function showThinkingIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'gemini-thinking-indicator';
        indicator.className = 'gemini-thinking-indicator';
        indicator.innerHTML = '<span class="gemini-thinking-dot"></span><span class="gemini-thinking-dot"></span><span class="gemini-thinking-dot"></span>';
        messagesEl.appendChild(indicator);
        scrollToBottom();
    }

    function removeThinkingIndicator() {
        const indicator = document.getElementById('gemini-thinking-indicator');
        if (indicator) indicator.remove();
    }

    /**
     * Form submit handler
     */
    function onFormSubmit(e) {
        e.preventDefault();
        if (isGenerating) return;
        const text = inputEl ? inputEl.value.trim() : '';
        if (!text) return;
        sendQuestion(text);
    }

    /**
     * Send question to backend
     */
    function sendQuestion(question) {
        if (isGenerating || !question) return;
        isGenerating = true;
        setStatus('thinking');
        // Hide starter chips
        if (starterChipsEl) starterChipsEl.style.display = 'none';
        // Append user message
        appendMessage('user', question);
        // Clear input
        if (inputEl) {
            inputEl.value = '';
            onInputChange();
        }
        if (sendBtnEl) sendBtnEl.disabled = true;
        showThinkingIndicator();
        if (soundEnabled) ensureAudioContext();
        fetch(config.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': config.nonce
            },
            body: JSON.stringify({
                question: question,
                generate_audio: soundEnabled,
                history: conversationHistory,
                lang: currentLang
            })
        })
            .then(res => res.json().then(data => ({ status: res.status, data })))
            .then(result => {
                removeThinkingIndicator();
                isGenerating = false;
                if (sendBtnEl) sendBtnEl.disabled = false;
                if (result.status === 200 && result.data && result.data.success) {
                    let answerText = result.data.text || result.data.answer || '';
                    if (!answerText || answerText.trim() === '') {
                        // Fallback placeholder when the AI returns no answer text.
                        answerText = (i18n && i18n.noAnswer) ? i18n.noAnswer : 'Ei vastausta.';
                    }
                    const sources = result.data.sources || [];
                    const audioData = result.data.audio_base64;
                    const buttons = result.data.buttons || [];
                    conversationHistory.push({ role: 'user', text: question });
                    conversationHistory.push({ role: 'model', text: answerText });
                    appendMessage('assistant', answerText, sources, audioData, buttons);
                    setStatus('ready');
                    if (soundEnabled && audioData) {
                        playBase64Wav(audioData);
                    }
                } else {
                    setStatus('error');
                    const errorMsg = (result.data && result.data.error) ? result.data.error : 'Pahoittelut, vastausta ei voitu noutaa. Yritä hetken kuluttua uudelleen.';
                    appendMessage('assistant', '⚠️ ' + errorMsg);
                }
            })
            .catch(err => {
                removeThinkingIndicator();
                isGenerating = false;
                if (sendBtnEl) sendBtnEl.disabled = false;
                setStatus('error');
                appendMessage('assistant', '⚠️ Verkkovirhe: ' + err.message);
            });
    }

    /**
     * Ensure AudioContext
     */
    function ensureAudioContext() {
        try {
            if (!audioCtx) {
                const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
                if (AudioCtxClass) audioCtx = new AudioCtxClass();
            }
            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
        } catch (e) {
            console.warn('[Gemini Avatar] AudioContext error:', e);
        }
    }

    /**
     * Play base64 WAV
     */
    function playBase64Wav(base64Data) {
        stopAudio();
        ensureAudioContext();
        if (!audioCtx) {
            console.warn('[Gemini Avatar] No AudioContext available.');
            return;
        }
        try {
            const binaryString = window.atob(base64Data);
            const len = binaryString.length;
            const bytes = new Uint8Array(len);
            for (let i = 0; i < len; i++) {
                bytes[i] = binaryString.charCodeAt(i);
            }
            audioCtx.decodeAudioData(bytes.buffer, function (audioBuffer) {
                startAudioPlayback(audioBuffer);
            }, function (err) {
                console.error('[Gemini Avatar] decodeAudioData error:', err);
                setStatus('ready');
            });
        } catch (e) {
            console.error('[Gemini Avatar] Audio processing error:', e);
            setStatus('ready');
        }
    }

    /**
     * Start audio playback
     */
    function startAudioPlayback(buffer) {
        if (!audioCtx) return;
        analyserNode = audioCtx.createAnalyser();
        analyserNode.fftSize = 64;
        analyserNode.smoothingTimeConstant = 0.75;
        currentAudioSource = audioCtx.createBufferSource();
        currentAudioSource.buffer = buffer;
        currentAudioSource.connect(analyserNode);
        analyserNode.connect(audioCtx.destination);
        isPlayingAudio = true;
        setStatus('speaking');
        currentAudioSource.onended = function () {
            stopAudio();
        };
        currentAudioSource.start(0);
        startAmplitudeAnimation();
    }

    /**
     * Animate amplitude
     */
    function startAmplitudeAnimation() {
        if (!analyserNode) return;
        const freqData = new Uint8Array(analyserNode.frequencyBinCount);
        const waveBars = audioWavesEl ? audioWavesEl.querySelectorAll('.wave-bar') : [];
        function renderFrame() {
            if (!isPlayingAudio || !analyserNode) return;
            analyserNode.getByteFrequencyData(freqData);
            let sum = 0;
            for (let i = 0; i < freqData.length; i++) {
                sum += freqData[i];
            }
            const average = sum / freqData.length;
            const amplitude = Math.min(1.0, average / 128.0);
            if (mouthOverlay) {
                const scaleY = Math.max(0.2, amplitude * 2.8);
                const scaleX = 1 + amplitude * 0.4;
                mouthOverlay.style.transform = 'translateX(-50%) scale(' + scaleX + ', ' + scaleY + ')';
                mouthOverlay.style.opacity = (amplitude > 0.05) ? String(0.3 + amplitude * 0.7) : '0';
            }
            if (vectorMouth) {
                const mouthOpen = 84 + amplitude * 14;
                const curve = 88 + amplitude * 18;
                vectorMouth.setAttribute('d', 'M48 84 Q60 ' + curve + ' 72 84 Q60 ' + mouthOpen + ' 48 84 Z');
            }
            if (portalOuterRing) {
                const ringScale = 1 + amplitude * 0.12;
                portalOuterRing.style.transform = 'scale(' + ringScale + ')';
            }
            if (waveBars && waveBars.length) {
                const step = Math.floor(freqData.length / waveBars.length);
                waveBars.forEach(function (bar, idx) {
                    const val = (freqData[idx * step] || 0) / 255;
                    const heightPx = Math.max(3, Math.round(val * 16));
                    bar.style.height = heightPx + 'px';
                });
            }
            animFrameId = requestAnimationFrame(renderFrame);
        }
        renderFrame();
    }

    /**
     * Stop audio playback
     */
    function stopAudio() {
        isPlayingAudio = false;
        if (animFrameId) {
            cancelAnimationFrame(animFrameId);
            animFrameId = null;
        }
        if (currentAudioSource) {
            try { currentAudioSource.stop(); } catch (e) { }
            try { currentAudioSource.disconnect(); } catch (e) { }
            currentAudioSource = null;
        }
        // Reset visuals
        if (mouthOverlay) {
            mouthOverlay.style.transform = 'translateX(-50%) scaleY(0)';
            mouthOverlay.style.opacity = '0';
        }
        if (vectorMouth) {
            vectorMouth.setAttribute('d', 'M48 84 Q60 88 72 84 Q60 90 48 84 Z');
        }
        if (portalOuterRing) {
            portalOuterRing.style.transform = 'scale(1)';
        }
        if (audioWavesEl) {
            const bars = audioWavesEl.querySelectorAll('.wave-bar');
            bars.forEach(function (bar) { bar.style.height = '3px'; });
        }
        if (!isGenerating) setStatus('ready');
    }

    /**
     * Voice input
     */
    function toggleVoiceInput() {
        if (isRecordingVoice) {
            stopVoiceInput();
        } else {
            startVoiceInput();
        }
    }

    function startVoiceInput() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            alert(currentLang === 'ar' ? 'متصفحك لا يدعم التعرف على الصوت.' : (currentLang === 'en' ? 'Your browser does not support speech recognition.' : 'Selaimesi ei tue Web Speech API -puheentunnistusta.'));
            return;
        }
        stopAudio();
        try {
            speechRecognizer = new SpeechRecognition();
            speechRecognizer.lang = (i18n && i18n.speechLang) ? i18n.speechLang : ((currentLang === 'ar') ? 'ar-SA' : ((currentLang === 'en') ? 'en-US' : 'fi-FI'));
            speechRecognizer.interimResults = true;
            speechRecognizer.continuous = false;
            speechRecognizer.onstart = function () {
                isRecordingVoice = true;
                if (micBtnEl) micBtnEl.classList.add('is-listening');
                if (micStatusEl) micStatusEl.style.display = 'flex';
            };
            speechRecognizer.onresult = function (event) {
                let transcript = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    transcript += event.results[i][0].transcript;
                }
                if (inputEl) {
                    inputEl.value = transcript;
                    onInputChange();
                }
            };
            speechRecognizer.onerror = function (event) {
                console.warn('[Gemini Avatar] Speech recognition error:', event.error);
                stopVoiceInput();
                if (event.error === 'not-allowed') {
                    alert(i18n.micPermission);
                }
            };
            speechRecognizer.onend = function () {
                stopVoiceInput();
            };
            speechRecognizer.start();
        } catch (e) {
            console.error('[Gemini Avatar] Voice input error:', e);
            stopVoiceInput();
        }
    }

    function stopVoiceInput() {
        isRecordingVoice = false;
        if (speechRecognizer) {
            try { speechRecognizer.stop(); } catch (e) { }
            speechRecognizer = null;
        }
        if (micBtnEl) micBtnEl.classList.remove('is-listening');
        if (micStatusEl) micStatusEl.style.display = 'none';
    }

    /**
     * Vector avatar blink
     */
    function initVectorAvatarBlink() {
        const eyes = drawerEl ? drawerEl.querySelectorAll('.gemini-eye') : [];
        if (!eyes || !eyes.length) return;
        function triggerBlink() {
            eyes.forEach(eye => eye.style.transform = 'scaleY(0.1)');
            setTimeout(() => {
                eyes.forEach(eye => eye.style.transform = 'scaleY(1)');
            }, 150);
            const nextBlink = 3000 + Math.random() * 3000;
            setTimeout(triggerBlink, nextBlink);
        }
        setTimeout(triggerBlink, 3500);
    }

    /**
     * Input change handling
     */
    function onInputChange() {
        if (!inputEl) return;
        inputEl.style.height = 'auto';
        inputEl.style.height = Math.min(100, Math.max(24, inputEl.scrollHeight)) + 'px';
        if (charCountEl) {
            charCountEl.textContent = inputEl.value.length + '/' + config.maxChars;
        }
    }

    function scrollToBottom() {
        const body = document.getElementById('gemini-drawer-body');
        if (body) body.scrollTop = body.scrollHeight;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function formatMarkdown(text) {
        let safe = escapeHtml(text);
        safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        safe = safe.replace(/\*(.*?)\*/g, '<em>$1</em>');
        safe = safe.replace(/`([^`]+)`/g, '<code style="background:rgba(255,255,255,0.1);padding:1px 4px;border-radius:4px;">$1</code>');
        const paragraphs = safe.split(/\n\n+/);
        return paragraphs.map(p => '<p>' + p.replace(/\n/g, '<br/>') + '</p>').join('');
    }

    // DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
