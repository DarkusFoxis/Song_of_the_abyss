// jshint maxerr:700
class QueueManager {
    constructor() {
        this.queue = [];
        this.currentTrackIndex = -1;
        this.isRepeat = false;
        this.drake = null;
        this.queueListElement = $('#queue-list');
        this._initializeDragAndDrop();
    }
    addTrack(track) {
        this.queue.push(track);
        this.render();
        if (this.currentTrackIndex === -1 && this.queue.length === 1) {
            return 0;
        }
        return -1;
    }
    removeTrack(indexToRemove) {
        if (indexToRemove < 0 || indexToRemove >= this.queue.length) return -2;
        const isCurrentlyPlaying = (indexToRemove === this.currentTrackIndex);
        this.queue.splice(indexToRemove, 1);
        if (isCurrentlyPlaying) {
            if (this.queue.length > 0) {
                const nextIndex = indexToRemove < this.queue.length ? indexToRemove : this.queue.length - 1;
                this.currentTrackIndex = nextIndex;
                this.render();
                return nextIndex;
            } else {
                this.currentTrackIndex = -1;
                this.render();
                return -1;
            }
        } else {
            if (indexToRemove < this.currentTrackIndex) {
                this.currentTrackIndex--;
            }
            this.render();
            return -2;
        }
    }
    getTrack(index) {
        if (index >= 0 && index < this.queue.length) {
            this.currentTrackIndex = index;
            return this.queue[index];
        }
        return null;
    }
    getNextTrack() {
        if (this.currentTrackIndex + 1 < this.queue.length) {
            this.currentTrackIndex++;
            return this.queue[this.currentTrackIndex];
        }
        this.currentTrackIndex = -1;
        return null;
    }
    getPreviousTrack() {
        if (this.currentTrackIndex > 0) {
            this.currentTrackIndex--;
            return this.queue[this.currentTrackIndex];
        }
        return null;
    }
    toggleRepeat() {
        this.isRepeat = !this.isRepeat;
        return this.isRepeat;
    }
    clear() {
        this.queue = [];
        this.currentTrackIndex = -1;
        this.render();
    }
    findIndexBySrc(src) {
        return this.queue.findIndex(track => track.src === src);
    }
    _updateOrder() {
        const newQueue = [];
        const listItems = this.queueListElement.find('li');
        let newPlayingIndex = -1;
        listItems.each((newIndex, item) => {
            const src = $(item).data('src');
            const originalTrack = this.queue.find(track => track.src === src);
             if (originalTrack) {
                newQueue.push(originalTrack);
                if ($(item).hasClass('playing') || (this.currentTrackIndex !== -1 && this.queue[this.currentTrackIndex]?.src === src)) {
                    newPlayingIndex = newIndex;
                }
            }
        });
        this.queue = newQueue;
        this.currentTrackIndex = newPlayingIndex;
        this.render();
    }
    _initializeDragAndDrop() {
        this.drake = dragula([document.getElementById('queue-list')])
            .on('drop', () => {
                this._updateOrder();
            });
    }
    render() {
        this.queueListElement.empty();
        if (this.queue.length === 0) {
            this.queueListElement.append('<li>Очередь пуста</li>');
        } else {
            this.queue.forEach((track, index) => {
                const listItem = $('<li>')
                    .attr('data-index', index)
                    .attr('data-src', track.src);
                const titleSpan = $('<span>').text(track.title || 'Без названия');
                listItem.append(titleSpan);
                const controlsDiv = $('<div>').addClass('queue-item-controls');
                const playItemBtn = $('<button>')
                        .html('<img src="./icon/play.svg">')
                        .attr('title', 'Воспроизвести этот трек')
                        .on('click', (e) => {
                            e.stopPropagation();
                            $(document).trigger('playTrackByIndex', [this.findIndexBySrc(track.src)]);
                        });
                controlsDiv.append(playItemBtn);
                const deleteBtn = $('<button>')
                    .html('&times;')
                    .attr('title', 'Удалить из очереди')
                    .on('click', (e) => {
                        e.stopPropagation();
                        $(document).trigger('removeTrackByIndex', [this.findIndexBySrc(track.src)]);
                    });
                controlsDiv.append(deleteBtn);
                listItem.append(controlsDiv);
                if (index === this.currentTrackIndex) {
                    listItem.addClass('playing');
                }
                this.queueListElement.append(listItem);
             });
        }
    }
}
class Visualizer {
    constructor(audioElement, canvasElement, fsCanvasElement) {
        this.audioElement = audioElement;
        this.canvas = $(canvasElement);
        this.fsCanvas = fsCanvasElement ? $(fsCanvasElement) : null;
        this.ctx = this.canvas[0]?.getContext('2d');
        this.fsCtx = this.fsCanvas ? this.fsCanvas[0].getContext('2d') : null;
        this.audioCtx = null;
        this.analyser = null;
        this.source = null;
        this.dataArray = null;
        this.isEnabled = localStorage.getItem('visualizerEnabled') === 'true' || localStorage.getItem('visualizerEnabled') === null;
        this.quality = localStorage.getItem('visualizerQuality') || 'medium';
        this.fftSize = this._getFftSize(this.quality);
        this.isInitialized = false;
        this.isAudioConnected = false;
        this.animationFrameId = null;
        this._setupFsCanvas();
        this.applySettings();
    }
    _getFftSize(quality) {
        switch (quality) {
            case 'low': return 32;
            case 'high': return 128;
            case 'medium':
            default: return 64;
        }
    }
    _setupFsCanvas() {
        if (!this.fsCanvas) return;
        const setWidth = () => this.fsCanvas[0].width = window.innerWidth;
        setWidth();
        window.addEventListener('resize', setWidth);
    }
    init() {
        if (!this.isEnabled || this.isInitialized) return;
        try {
            if (!this.audioCtx) {
                this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            this.analyser = this.audioCtx.createAnalyser();
            this.analyser.fftSize = this.fftSize;
            if (!this.source || !this.isAudioConnected) {
                 this.source = this.audioCtx.createMediaElementSource(this.audioElement);
                 this.source.connect(this.analyser);
                 this.analyser.connect(this.audioCtx.destination);
                 this.isAudioConnected = true;
            }
            const bufferLength = this.analyser.frequencyBinCount;
            this.dataArray = new Uint8Array(bufferLength);
            this.isInitialized = true;
            this.startDrawing();
        } catch (error) {
            console.error("Ошибка инициализации визуализатора:", error);
            this.cleanup();
            this.isEnabled = false;
            this.applySettings();
        }
    }
    startDrawing() {
        if (!this.isEnabled || !this.isInitialized || this.animationFrameId) return;
        const drawLoop = () => {
            this.draw();
            this.animationFrameId = requestAnimationFrame(drawLoop);
        };
        drawLoop();
    }
    stopDrawing() {
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
            this.animationFrameId = null;
        }
        if(this.ctx) this.ctx.clearRect(0, 0, this.canvas[0].width, this.canvas[0].height);
        if(this.fsCtx) this.fsCtx.clearRect(0, 0, this.fsCanvas[0].width, this.fsCanvas[0].height);
    }
    draw() {
        if (!this.isEnabled || !this.analyser || !this.dataArray) {
             this.stopDrawing();
             return;
        }
        try {
            this.analyser.getByteFrequencyData(this.dataArray);
            if (this.ctx) {
                this.ctx.clearRect(0, 0, this.canvas[0].width, this.canvas[0].height);
                const barWidth = this.canvas[0].width / this.dataArray.length;
                for (let i = 0; i < this.dataArray.length; i++) {
                    const barHeight = this.dataArray[i] / 2;
                    const x = i * barWidth;
                    this.ctx.fillStyle = 'rgba(153, 102, 204, 0.6)';
                    this.ctx.fillRect(x, this.canvas[0].height - barHeight, barWidth - 1, barHeight);
                }
            }
            if (this.fsCtx) {
                 this.fsCtx.clearRect(0, 0, this.fsCanvas[0].width, this.fsCanvas[0].height);
                 const fsBarWidth = (this.fsCanvas[0].width / this.dataArray.length);
                 for (let i = 0; i < this.dataArray.length; i++) {
                    const barHeight = this.dataArray[i] / 1.8;
                    const x = i * fsBarWidth;
                    this.fsCtx.fillStyle = `rgba(255, 255, 255, 0.4)`;
                    this.fsCtx.fillRect(x, this.fsCanvas[0].height - barHeight, fsBarWidth - 1, barHeight);
                }
            }
        } catch (error) {
            console.error("Ошибка в drawVisualizer:", error);
            this.cleanup();
            this.isEnabled = false;
            this.applySettings();
        }
    }
    applySettings(newEnabled = this.isEnabled, newQuality = this.quality) {
        this.isEnabled = newEnabled;
        this.quality = newQuality;
        this.fftSize = this._getFftSize(this.quality);
        localStorage.setItem('visualizerEnabled', this.isEnabled);
        localStorage.setItem('visualizerQuality', this.quality);
        if (this.analyser) {
            this.analyser.fftSize = this.fftSize;
             if (this.isInitialized) {
                 const bufferLength = this.analyser.frequencyBinCount;
                 this.dataArray = new Uint8Array(bufferLength);
            }
        }
        if (this.isEnabled) {
            this.canvas.show();
            if (!this.isInitialized) {
                this.init();
            } else {
                if (!this.isAudioConnected && this.source && this.analyser) {
                     try {
                         this.source.disconnect();
                         this.source.connect(this.analyser);
                         this.analyser.connect(this.audioCtx.destination);
                         this.isAudioConnected = true;
                     } catch (e) { console.warn("Ошибка переподключения визуализатора:", e); }
                 }
                this.startDrawing();
            }
        } else {
            this.canvas.hide();
            this.stopDrawing();
             if (this.isInitialized && this.source && this.analyser && this.isAudioConnected) {
                 try {
                    this.source.disconnect(this.analyser);
                    this.analyser.disconnect(this.audioCtx.destination);
                    this.source.connect(this.audioCtx.destination);
                    this.isAudioConnected = false;
                 } catch (e) {
                    console.warn("Ошибка отключения визуализатора:", e);
                 }
            }
        }
    }
    cleanup() {
         this.stopDrawing();
         if (this.source) {
            try { this.source.disconnect(); } catch (e) {}
            this.source = null;
        }
         if (this.analyser) {
            try { this.analyser.disconnect(); } catch (e) {}
            this.analyser = null;
        }
        this.isInitialized = false;
        this.isAudioConnected = false;
        this.dataArray = null;
    }
}
class UIManager {
    constructor(player) {
        this.player = player;
        this.playBtn = $('#play');
        this.stopBtn = $('#stop');
        this.nextBtn = $('#next');
        this.prevBtn = $('#prev');
        this.repeatBtn = $('#repeat');
        this.progress = $('#progress');
        this.volume = $('#volume');
        this.currentTimeDisplay = $('#currentTime');
        this.totalTimeDisplay = $('#totalTime');
        this.nowPlaying = $('#now-playing');
        this.openFullscreenBtn = $('#openFullscreenBtn');
        this.queueModal = $('#queueModal');
        this.openQueueModalBtn = $('.openQueueModalBtn');
        this.closeQueueModalBtn = $('#closeQueueModalBtn');
        this.clearQueueBtn = $('#clearQueueBtn');
        this.fsPlayer = $('#fullscreen-player');
        this.fsPlay = $('#fs-play');
        this.fsNext = $('#fs-next');
        this.fsPrev = $('#fs-prev');
        this.fsRepeat = $('#fs-repeat');
        this.fsTitle = $('#fs-title');
        this.fsArtist = $('#fs-artist');
        this.fsUploader = $('#fs-uploader');
        this.fsProgress = $('#fs-progress');
        this.fsVolume = $('#fs-volume');
        this.fsCurrent = $('#fs-current');
        this.fsTotal = $('#fs-total');
        this.fsCover = $('#player-cover');
        this.fsClose = $('#closeFullscreenBtn');
        this.settingsModal = $('#visualizerSettingsModal');
        this.openSettingsBtn = $('.setting');
        this.closeSettingsBtn = $('#closeVisualizerSettings');
        this.toggleVisualizer = $('#toggle-visualizer');
        this.qualitySelect = $('#quality-select');
        this.saveSettingsBtn = $('#saveVisualizerSettings');
        this.pagePlayBtns = $('.play-btn');
        this.pageQueueBtns = $('.queue-btn');
        this.pageShareBtns = $('.share-btn');
        this._bindEvents();
        this._setupVisualizerSettingsUI();
        this._setupEasterEggPhrases();
        this._addPulseAnimation();
    }
    _bindEvents() {
        this.playBtn.on('click', () => this.player.togglePlayPause());
        this.stopBtn.on('click', () => this.player.stop());
        this.nextBtn.on('click', () => this.player.playNext());
        this.prevBtn.on('click', () => this.player.playPrevious());
        this.repeatBtn.on('click', () => this.player.toggleRepeat());
        this.progress.on('input', (e) => this.player.seek(e.target.value));
        this.volume.on('input', (e) => this.player.setVolume(e.target.value));
        this.pagePlayBtns.on('click', (e) => this._handlePageButtonClick(e, 'play'));
        this.pageQueueBtns.on('click', (e) => this._handlePageButtonClick(e, 'queue'));
        this.pageShareBtns.on('click', (e) => this._handleShareClick(e));
        this.openQueueModalBtn.on('click', (e) => { e.preventDefault(); this.openQueueModal(); });
        this.closeQueueModalBtn.on('click', () => this.closeQueueModal());
        this.clearQueueBtn.on('click', () => this.player.clearQueue());
        $(window).on('click', (e) => { if (e.target == this.queueModal[0]) this.closeQueueModal(); });
        this.openFullscreenBtn.on('click', () => this.openFullscreenPlayer());
        this.fsClose.on('click', () => this.closeFullscreenPlayer());
        this.fsPlay.on('click', () => this.player.togglePlayPause());
        this.fsNext.on('click', () => this.player.playNext());
        this.fsPrev.on('click', () => this.player.playPrevious());
        this.fsRepeat.on('click', () => this.player.toggleRepeat());
        this.fsProgress.on('input', (e) => this.player.seek(e.target.value));
        this.fsVolume.on('input', (e) => this.player.setVolume(e.target.value));
        $(window).on('keydown', (e) => { if (e.key === 'Escape') this.closeFullscreenPlayer(); });
        this.openSettingsBtn.on('click', () => this.openSettingsModal());
        this.closeSettingsBtn.on('click', () => this.closeSettingsModal());
        this.saveSettingsBtn.on('click', () => this._saveVisualizerSettings());
        $(window).on('click', (e) => { if (e.target == this.settingsModal[0]) this.closeSettingsModal(); });
        $(document).on('playTrackByIndex', (e, index) => this.player.playTrack(index));
        $(document).on('removeTrackByIndex', (e, index) => this.player.removeTrack(index));
    }
    _handlePageButtonClick(event, action) {
        const button = $(event.currentTarget);
        const trackData = {
            src: button.data('src'),
            title: button.data('title') || button.data('src').split('/').pop().replace(/\.(mp3|wav|ogg|flac|aac|m4a)$/i, ''),
            artist: button.data('artist'),
            uploader: button.data('uploader'),
            cover: button.data('cover')
        };
        if (action === 'play') {
            this.player.playNow(trackData);
        } else if (action === 'queue') {
            this.player.addToQueue(trackData);
        }
    }
    _handleShareClick(event) {
        const button = $(event.currentTarget);
        const trackId = button.data('id');
        const shareUrl = `${window.location.origin}${window.location.pathname}?track_id=${encodeURIComponent(trackId)}`;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(shareUrl).then(() => {
                alert('Ссылка на трек скопирована!');
            }).catch(err => {
                console.error('Не удалось скопировать: ', err);
                prompt('Не удалось скопировать автоматически. Скопируйте вручную:', shareUrl);
            });
        } else {
            try {
                const textArea = document.createElement("textarea");
                textArea.value = shareUrl;
                textArea.style.position = "fixed";
                textArea.style.left = "-9999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('Ссылка на трек скопирована!');
            } catch (err) {
                console.error('Fallback: не удалось скопировать: ', err);
                prompt('Не удалось скопировать автоматически. Скопируйте вручную:', shareUrl);
            }
        }
    }
    openQueueModal() { this.player.queueManager.render(); this.queueModal.css('display', 'block'); }
    closeQueueModal() { this.queueModal.css('display', 'none'); }
    openSettingsModal() { this.settingsModal.css('display', 'block'); }
    closeSettingsModal() { this.settingsModal.css('display', 'none'); }
    openFullscreenPlayer() {
        const track = this.player.getCurrentTrack();
        if (!track) return;
        this.fsTitle.text(track.title);
        this.fsArtist.text(track.artist || 'Неизвестный');
        this.fsUploader.text(`Загрузил: ${track.uploader || '???'}`);
        this.fsCover.css('background-image', `url('${track.cover || ''}')`);
        if (this.player.audio.duration) {
             this.fsProgress.attr('max', this.player.audio.duration);
        } else {
             this.fsProgress.removeAttr('max');
        }
        this.fsPlayer.removeClass('hidden');
    }
    closeFullscreenPlayer() { this.fsPlayer.addClass('hidden'); }
    updatePlayPauseState(isPlaying) {
        const icon = isPlaying ? './icon/pause.svg' : './icon/play.svg';
        this.playBtn.html(`<img src="${icon}">`);
        this.fsPlay.html(`<img src="${icon}">`);
        if (isPlaying) {
            $("#player-bar button").addClass("akihiro-pulse"); 
            $('.fs-controls button').addClass('akihiro-pulse');
        } else {
            $("#player-bar button").removeClass("akihiro-pulse");
            $('.fs-controls button').removeClass('akihiro-pulse');
        }
    }
    updateRepeatState(isRepeat) {
        this.repeatBtn.toggleClass('active', isRepeat);
        this.repeatBtn.attr('title', isRepeat ? 'Повтор включен' : 'Повтор выключен');
        if (isRepeat) {
            this.fsRepeat.css({
                backgroundColor: '#7a3dcc',
                boxShadow: 'inset 0 0 5px rgba(255,255,255,0.5)'
            });
        } else {
            this.fsRepeat.removeAttr('style');
        }
    }
    updateTrackInfo(track) {
        const title = track ? track.title : 'Очередь пуста...';
        this.nowPlaying.text(title).attr('title', title);
        if (this.fsPlayer.is(':visible') && track) {
            this.fsTitle.text(track.title);
            this.fsArtist.text(track.artist || 'Неизвестный');
            this.fsUploader.text(`Загрузил: ${track.uploader || '???'}`);
            this.fsCover.css('background-image', `url('${track.cover || ''}')`);
        } else if (!track) {
            this.fsTitle.text('...');
            this.fsArtist.text('');
            this.fsUploader.text('');
            this.fsCover.css('background-image', 'none');
        }
    }
    updateTimeAndProgress(currentTime, duration) {
        const formattedCurrentTime = this._formatTime(currentTime);
        const formattedTotalTime = this._formatTime(duration);
        this.currentTimeDisplay.text(formattedCurrentTime);
        this.totalTimeDisplay.text(formattedTotalTime);
        this.progress.val(currentTime).attr('max', duration || 0);
        this.fsCurrent.text(formattedCurrentTime);
        this.fsTotal.text(formattedTotalTime);
        if (!isNaN(currentTime) && isFinite(currentTime)) {
             this.fsProgress.val(currentTime);
        }
        if (!isNaN(duration) && isFinite(duration) && duration > 0) {
             this.fsProgress.attr('max', duration);
        } else {
            this.fsProgress.removeAttr('max');
        }
    }
    updateVolume(volumeLevel) {
        this.volume.val(volumeLevel);
        this.fsVolume.val(volumeLevel);
    }
    resetUI() {
        this.updatePlayPauseState(false);
        this.updateTrackInfo(null);
        this.updateTimeAndProgress(0, 0); 
        this.closeFullscreenPlayer();
    }
    _formatTime(seconds) {
        if (isNaN(seconds) || !isFinite(seconds)) {
            return '00:00';
        }
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }
    _setupVisualizerSettingsUI() {
        const enabled = localStorage.getItem('visualizerEnabled');
        const quality = localStorage.getItem('visualizerQuality') || 'medium';
        this.toggleVisualizer.prop('checked', enabled === null ? true : enabled === 'true');
        this.qualitySelect.val(quality);
    }
    _saveVisualizerSettings() {
        const isEnabled = this.toggleVisualizer.prop('checked');
        const quality = this.qualitySelect.val();
        console.log(`Сохранение настроек: Визуализатор: ${isEnabled}, Качество: ${quality}`);
        this.player.visualizer.applySettings(isEnabled, quality);
        this.closeSettingsModal();
    }
    _setupEasterEggPhrases() {
        const phrases = [
            "Пока ты слушаешь — Бездна слушает тебя.",
            "Каждая песня — это заклинание для уставшего сердца.",
            "Внутри тишины звучишь ты.",
            "Музыка — это форма магии, которую мы ещё не забыли.",
            "Песни — это письма без адресатов.",
            "Пока ты слушаешь, мир замедляется вокруг."
        ];
        let phraseIndex = 0;
        const phraseDisplay = $('<div>').attr('id', 'akihiro-quote').text(phrases[0]).css({
            color: '#ccc',
            fontStyle: 'italic',
            textAlign: 'center',
            marginTop: '5px',
            fontSize: '0.9em',
            opacity: 0.7
        });
        $('#add_text').append(phraseDisplay);
        setInterval(() => {
            phraseIndex = (phraseIndex + 1) % phrases.length;
            phraseDisplay.fadeOut(400, () => {
                phraseDisplay.text(phrases[phraseIndex]).fadeIn(400);
            });
        }, 15000);
    }
    _addPulseAnimation() {
        const style = document.createElement('style');
        style.innerHTML = `
            .akihiro-pulse {
                animation: pulseAkihiro 1.5s infinite ease-in-out;
            }
            @keyframes pulseAkihiro {
                0% { box-shadow: 0 0 5px #9966cc; }
                50% { box-shadow: 0 0 15px #9966cc; }
                100% { box-shadow: 0 0 5px #9966cc; }
            }`;
        document.head.appendChild(style);
    }
}
class AudioPlayer {
    constructor() {
        this.audio = document.getElementById('audio');
        this.queueManager = new QueueManager();
        this.visualizer = new Visualizer(this.audio, '#akihiro-visualizer', '#fs-visualizer');
        this.uiManager = new UIManager(this);
        this._loadInitialSettings();
        this._bindAudioEvents();
        this._setupMediaSession();
    }
    _loadInitialSettings() {
        const savedVolume = localStorage.getItem('playerVolume');
        const initialVolume = savedVolume !== null ? parseFloat(savedVolume) : 1;
        this.setVolume(initialVolume);
    }
    _bindAudioEvents() {
        this.audio.addEventListener('play', () => {
            this.uiManager.updatePlayPauseState(true);
            this.visualizer.init();
        });
        this.audio.addEventListener('pause', () => {
             this.uiManager.updatePlayPauseState(false);
        });
        this.audio.addEventListener('ended', () => {
            if (!this.queueManager.isRepeat) {
                this.playNext();
            } else {
                 this.audio.currentTime = 0;
                 this.audio.play();
            }
        });
        this.audio.addEventListener('loadedmetadata', () => {
            this.uiManager.updateTimeAndProgress(this.audio.currentTime, this.audio.duration);
             if (this.uiManager.fsPlayer.is(':visible')) {
                 this.uiManager.fsProgress.attr('max', this.audio.duration || 0);
            }
        });
        this.audio.addEventListener('timeupdate', () => {
            this.uiManager.updateTimeAndProgress(this.audio.currentTime, this.audio.duration);
        });
        this.audio.addEventListener('volumechange', () => {
             this.uiManager.updateVolume(this.audio.volume);
        });
        this.audio.addEventListener('error', (e) => {
             console.error("Ошибка элемента Audio:", e);
             alert("Не удалось загрузить или воспроизвести трек.");
             if (this.audio.currentSrc) {
                 this.playNext();
             } else {
                this.stop();
            }
        });
    }
    _setupMediaSession() {
        if ('mediaSession' in navigator) {
            navigator.mediaSession.setActionHandler('play', () => this.togglePlayPause());
            navigator.mediaSession.setActionHandler('pause', () => this.togglePlayPause());
            navigator.mediaSession.setActionHandler('stop', () => this.stop());
            navigator.mediaSession.setActionHandler('previoustrack', () => this.playPrevious());
            navigator.mediaSession.setActionHandler('nexttrack', () => this.playNext());
        }
    }
    _updateMediaSessionMetadata(track) {
        if ('mediaSession' in navigator && track) {
            navigator.mediaSession.metadata = new MediaMetadata({
                title: track.title || 'Unknown Title',
                artist: track.artist || 'Unknown Artist',
                artwork: track.cover ? [ { src: track.cover, sizes: '512x512', type: 'image/jpeg' } ] : []
            });
            navigator.mediaSession.playbackState = this.audio.paused ? 'paused' : 'playing';
        } else if ('mediaSession' in navigator) {
            navigator.mediaSession.metadata = null;
            navigator.mediaSession.playbackState = 'none';
        }
    }
    addToQueue(trackData) {
        const startIndex = this.queueManager.addTrack(trackData);
        if (startIndex === 0) {
            this.playTrack(0);
        }
    }
    playNow(trackData) {
        this.stop();
        this.queueManager.addTrack(trackData);
        this.playTrack(0);
    }
    playTrack(index) {
        const track = this.queueManager.getTrack(index);
        if (track) {
            try {
                this.audio.src = track.src;
                this.audio.load();
                const playPromise = this.audio.play();
                if (playPromise !== undefined) {
                    playPromise.then(_ => {
                        this.uiManager.updateTrackInfo(track);
                        this._updateMediaSessionMetadata(track);
                        this.audio.loop = this.queueManager.isRepeat;
                        this.queueManager.render();
                    }).catch(error => {
                        console.error("Ошибка автовоспроизведения:", error);
                        this.uiManager.updatePlayPauseState(false);
                        alert("Браузер заблокировал автовоспроизведение. Нажмите Play.");
                    });
                } else {
                    this.uiManager.updateTrackInfo(track);
                    this._updateMediaSessionMetadata(track);
                    this.audio.loop = this.queueManager.isRepeat;
                    this.queueManager.render();
                }
            } catch (e) {
                console.error("Ошибка установки src или воспроизведения:", e);
                this.stop(); 
                alert("Произошла ошибка при попытке воспроизвести трек.");
            }
        } else {
            console.log("playTrack: Невалидный индекс или пустая очередь", index);
            this.stop();
        }
    }
    removeTrack(index) {
        const nextActionIndex = this.queueManager.removeTrack(index);
        if (nextActionIndex === -1) {
            this.stop();
        } else if (nextActionIndex >= 0) {
            this.playTrack(nextActionIndex);
        }
    }
    togglePlayPause() {
        if (!this.audio.src && this.queueManager.queue.length > 0) {
            this.playTrack(0);
        } else if (this.audio.src && this.audio.readyState >= 1) {
            if (this.audio.paused) {
                const playPromise = this.audio.play();
                if (playPromise !== undefined) {
                    playPromise.catch(error => {
                        console.error("Ошибка воспроизведения (togglePlayPause):", error);
                        this.uiManager.updatePlayPauseState(false);
                    });
                 }
            } else {
                this.audio.pause();
            }
        } else if (this.queueManager.queue.length > 0 && this.queueManager.currentTrackIndex !== -1) {
            this.playTrack(this.queueManager.currentTrackIndex);
        }
        this._updateMediaSessionMetadata(this.getCurrentTrack());
    }
    stop() {
        this.audio.pause(); 
        this.audio.src = '';
        this.queueManager.clear();
        this.uiManager.resetUI();
        this.visualizer.stopDrawing();
        this._updateMediaSessionMetadata(null);
    }
    playNext() {
        const nextTrack = this.queueManager.getNextTrack();
        if (nextTrack) {
            this.playTrack(this.queueManager.currentTrackIndex);
        } else {
            this.stop();
        }
    }
    playPrevious() {
        if (this.audio.currentTime > 5 || this.queueManager.currentTrackIndex === 0) {
            this.audio.currentTime = 0;
        } else {
             const prevTrack = this.queueManager.getPreviousTrack();
             if (prevTrack) {
                 this.playTrack(this.queueManager.currentTrackIndex);
            }
        }
    }
    toggleRepeat() {
        const isRepeat = this.queueManager.toggleRepeat();
        this.audio.loop = isRepeat;
        this.uiManager.updateRepeatState(isRepeat);
    }
    seek(time) {
        if (this.audio.readyState >= 2) {
            this.audio.currentTime = parseFloat(time);
        }
    }
    setVolume(level) {
        const volumeLevel = parseFloat(level);
        if (!isNaN(volumeLevel) && volumeLevel >= 0 && volumeLevel <= 1) {
             this.audio.volume = volumeLevel; 
             localStorage.setItem('playerVolume', volumeLevel);
        }
    }
    clearQueue() {
        this.stop();
    }
    getCurrentTrack() {
        return this.queueManager.getTrack(this.queueManager.currentTrackIndex);
    }
}
$(document).ready(function() {
    const player = new AudioPlayer();
    window.audioPlayer = player;
    const urlParams = new URLSearchParams(window.location.search);
    const trackIdToPlay = urlParams.get('track_id');
    if (trackIdToPlay) {
        const playButton = $(`.play-btn[data-id="${trackIdToPlay}"]`);
        if (playButton.length > 0) {
            console.log(`Воспроизведение трека по ссылке: ${trackIdToPlay}`);
            playButton.first().trigger('click');
        } else {
            console.warn(`Трек с ID ${trackIdToPlay} для deeplink не найден.`);
        }
    }
});