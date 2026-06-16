// Audio Player v1.5
const PlayerConfig = {
    storageKeys: {
        volume: 'playerVolume',
        visualizerEnabled: 'visualizerEnabled',
        visualizerQuality: 'visualizerQuality',
        repeatQueue: 'repeatQueueEnabled'
    },
    visualizer: {
        fftSizes: { low: 32, medium: 64, high: 128 },
        colors: { bar: 'rgba(153, 102, 204, 0.6)', fullscreen: 'rgba(255, 255, 255, 0.4)' }
    },
    quotes: [
        "Пока ты слушаешь — Бездна слушает тебя.", "Каждая песня — это заклинание для уставшего сердца.",
        "Внутри тишины звучишь ты.", "Музыка — это форма магии, которую мы ещё не забыли.",
        "Песни — это письма без адресатов.", "Пока ты слушаешь, мир замедляется вокруг."
    ],
    quoteInterval: 15000
};

const PlayerState = {
    queue: [], currentTrackIndex: -1, isRepeat: false, isRepeatQueue: false,
    visualizerEnabled: true, visualizerQuality: 'medium', isFullscreenOpen: false,
    drake: null, audioCtx: null, analyser: null, source: null,
    dataArray: null, visualStarted: false, isAudioConnectedToContext: false
};

const DOM = {};

function initDOM() {
    DOM.audio = document.getElementById('audio');
    DOM.playerBar = {
        currentTime: document.getElementById('currentTime'),
        totalTime: document.getElementById('totalTime'),
        nowPlaying: $('#now-playing'), play: $('#play'), stop: $('#stop'),
        next: $('#next'), prev: $('#prev'), repeat: $('#repeat'),
        progress: $('#progress'), volume: $('#volume'), openFullscreen: $('#openFullscreenBtn')
    };
    DOM.queue = {
        modal: $('#queueModal'), openBtn: $('.openQueueModalBtn'), closeBtn: $('#closeQueueModalBtn'),
        list: $('#queue-list'), clearBtn: $('#clearQueueBtn')
    };
    DOM.fullscreen = {
        player: $('#fullscreen-player'), play: $('#fs-play'), next: $('#fs-next'),
        prev: $('#fs-prev'), repeat: $('#fs-repeat'), title: $('#fs-title'),
        artist: $('#fs-artist'), uploader: $('#fs-uploader'), progress: $('#fs-progress'),
        volume: $('#fs-volume'), current: document.getElementById('fs-current'), total: document.getElementById('fs-total'),
        cover: $('#player-cover'), visualizer: $('#fs-visualizer'),
        close: $('#closeFullscreenBtn')
    };
    DOM.settings = {
        modal: $('#visualizerSettingsModal'), openBtn: $('.setting'), closeBtn: $('#closeVisualizerSettings'),
        toggleVisualizer: $('#toggle-visualizer'), qualitySelect: $('#quality-select'),
        toggleRepeatQueue: $('#toggle-repeat-queue'), saveBtn: $('#saveVisualizerSettings')
    };
    DOM.visualizer = {
        canvas: $('<canvas id="akihiro-visualizer" height="35"></canvas>'), ctx: null
    };
}

const Utils = {
    formatTime(seconds) {
        if (!seconds || isNaN(seconds)) return '00:00';
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return `${m}:${s < 10 ? '0' + s : s}`;
    },
    findQueueIndexBySrc(src) { return PlayerState.queue.findIndex(t => t.src === src); },
    getTrackFromButton($button) {
        return {
            src: $button.data('src'),
            title: $button.data('title') || $button.data('src').split('/').pop().replace(/\.(mp3|wav|ogg|flac|aac|m4a)$/i, ''),
            artist: $button.data('artist'), uploader: $button.data('uploader'), cover: $button.data('cover')
        };
    }
};

const Visualizer = {
    init() {
        DOM.visualizer.canvas.css({ width: '50%', position: 'fixed', bottom: '55px', left: '50%', transform: 'translateX(-50%)', zIndex: 999, pointerEvents: 'none' });
        $('body').append(DOM.visualizer.canvas);
        DOM.visualizer.ctx = DOM.visualizer.canvas[0].getContext('2d');
        PlayerState.visualizerEnabled ? DOM.visualizer.canvas.show() : DOM.visualizer.canvas.hide();
    },
    start() {
        if (!PlayerState.visualizerEnabled || PlayerState.isAudioConnectedToContext) return;
        try {
            if (!PlayerState.audioCtx) PlayerState.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            PlayerState.analyser = PlayerState.audioCtx.createAnalyser();
            PlayerState.analyser.fftSize = PlayerConfig.visualizer.fftSizes[PlayerState.visualizerQuality];
            PlayerState.source = PlayerState.audioCtx.createMediaElementSource(DOM.audio);
            PlayerState.source.connect(PlayerState.analyser);
            PlayerState.analyser.connect(PlayerState.audioCtx.destination);
            PlayerState.isAudioConnectedToContext = true;
            PlayerState.dataArray = new Uint8Array(PlayerState.analyser.frequencyBinCount);
            this.draw();
        } catch (error) { console.error("Ошибка инициализации визуализатора:", error); this.cleanup(); }
    },
    draw() {
        if (!PlayerState.visualizerEnabled || !PlayerState.analyser || !PlayerState.dataArray) return;
        requestAnimationFrame(() => this.draw());
        try {
            PlayerState.analyser.getByteFrequencyData(PlayerState.dataArray);
            const canvas = DOM.visualizer.canvas[0], ctx = DOM.visualizer.ctx;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            const barWidth = canvas.width / PlayerState.dataArray.length;
            for (let i = 0; i < PlayerState.dataArray.length; i++) {
                const barHeight = PlayerState.dataArray[i] / 2;
                ctx.fillStyle = PlayerConfig.visualizer.colors.bar;
                ctx.fillRect(i * barWidth, canvas.height - barHeight, barWidth - 1, barHeight);
            }
        } catch (error) { console.error("Ошибка отрисовки визуализатора:", error); }
    },
    drawFullscreen() {
        if (!PlayerState.analyser || !PlayerState.dataArray) return;
        requestAnimationFrame(() => this.drawFullscreen());
        const ctx = DOM.fullscreen.visualizer.getContext('2d');
        PlayerState.analyser.getByteFrequencyData(PlayerState.dataArray);
        ctx.clearRect(0, 0, DOM.fullscreen.visualizer.width, DOM.fullscreen.visualizer.height);
        const barWidth = DOM.fullscreen.visualizer.width / PlayerState.dataArray.length;
        for (let i = 0; i < PlayerState.dataArray.length; i++) {
            ctx.fillStyle = PlayerConfig.visualizer.colors.fullscreen;
            ctx.fillRect(i * barWidth, DOM.fullscreen.visualizer.height - (PlayerState.dataArray[i] / 1.8), barWidth - 1, PlayerState.dataArray[i] / 1.8);
        }
    },
    cleanup() {
        if (PlayerState.source && PlayerState.analyser && PlayerState.isAudioConnectedToContext) {
            try {
                PlayerState.source.disconnect(); PlayerState.analyser.disconnect();
                PlayerState.source.connect(PlayerState.audioCtx.destination);
                PlayerState.isAudioConnectedToContext = false;
            } catch (e) { console.warn("Ошибка отключения визуализатора:", e); }
        }
    },
    toggle(enabled) {
        PlayerState.visualizerEnabled = enabled;
        localStorage.setItem(PlayerConfig.storageKeys.visualizerEnabled, enabled);
        enabled ? (DOM.visualizer.canvas.show(), !PlayerState.audioCtx && this.start()) : (DOM.visualizer.canvas.hide(), this.cleanup());
    },
    setQuality(quality) {
        PlayerState.visualizerQuality = quality;
        localStorage.setItem(PlayerConfig.storageKeys.visualizerQuality, quality);
        if (PlayerState.analyser) {
            PlayerState.analyser.fftSize = PlayerConfig.visualizer.fftSizes[quality];
            PlayerState.dataArray = new Uint8Array(PlayerState.analyser.frequencyBinCount);
        }
    }
};

const QuoteDisplay = {
    index: 0, element: null,
    init() {
        this.element = $('<div>').attr('id', 'akihiro-quote').text(PlayerConfig.quotes[0]).css({ color: '#ccc', fontStyle: 'italic', textAlign: 'center', marginTop: '5px', fontSize: '0.9em', opacity: 0.7 });
        $('#add_text').append(this.element);
        this.startRotation();
    },
    startRotation() {
        setInterval(() => {
            this.index = (this.index + 1) % PlayerConfig.quotes.length;
            this.element.fadeOut(400, () => { this.element.text(PlayerConfig.quotes[this.index]).fadeIn(400); });
        }, PlayerConfig.quoteInterval);
    }
};

const Player = {
    init() { this.loadSettings(); this.setupEventListeners(); this.setupAudioEvents(); },
    loadSettings() {
        PlayerState.visualizerEnabled = localStorage.getItem(PlayerConfig.storageKeys.visualizerEnabled) !== 'false';
        PlayerState.visualizerQuality = localStorage.getItem(PlayerConfig.storageKeys.visualizerQuality) || 'medium';
        PlayerState.isRepeatQueue = localStorage.getItem(PlayerConfig.storageKeys.repeatQueue) === 'true';
        const savedVolume = localStorage.getItem(PlayerConfig.storageKeys.volume);
        if (savedVolume !== null) { DOM.audio.volume = parseFloat(savedVolume); DOM.playerBar.volume.val(savedVolume); }
        DOM.settings.toggleVisualizer.prop('checked', PlayerState.visualizerEnabled);
        DOM.settings.qualitySelect.val(PlayerState.visualizerQuality);
        DOM.settings.toggleRepeatQueue.prop('checked', PlayerState.isRepeatQueue);
    },
    setupEventListeners() {
        DOM.playerBar.play.on('click', () => this.togglePlay());
        DOM.playerBar.stop.on('click', () => this.stop());
        DOM.playerBar.next.on('click', () => this.playNext());
        DOM.playerBar.prev.on('click', () => this.playPrev());
        DOM.playerBar.repeat.on('click', () => this.toggleRepeat());
        DOM.playerBar.progress.on('input', function() { if (DOM.audio.readyState >= 2) DOM.audio.currentTime = this.value; });
        DOM.playerBar.volume.on('input', (e) => this.setVolume(e.target.value));
        DOM.queue.openBtn.on('click', (e) => { e.preventDefault(); this.renderQueue(); DOM.queue.modal.css('display', 'block'); });
        DOM.queue.closeBtn.on('click', () => DOM.queue.modal.css('display', 'none'));
        DOM.queue.modal.on('click', (e) => { if (e.target === DOM.queue.modal[0]) DOM.queue.modal.css('display', 'none'); });
        DOM.queue.clearBtn.on('click', () => this.stop());
        DOM.fullscreen.close.on('click', () => this.closeFullscreen());
        DOM.fullscreen.play.on('click', () => this.togglePlay());
        DOM.fullscreen.next.on('click', () => this.playNext());
        DOM.fullscreen.prev.on('click', () => { if (DOM.audio.currentTime > 5) DOM.audio.currentTime = 0; else if (PlayerState.currentTrackIndex > 0) this.playTrack(PlayerState.currentTrackIndex - 1); });
        DOM.fullscreen.repeat.on('click', () => this.toggleRepeat());
        DOM.fullscreen.progress.on('input', function() { DOM.audio.currentTime = this.value; });
        DOM.fullscreen.volume.on('input', (e) => this.setVolume(e.target.value));
        DOM.playerBar.openFullscreen.on('click', () => { if (PlayerState.queue[PlayerState.currentTrackIndex]) this.openFullscreen(PlayerState.queue[PlayerState.currentTrackIndex]); });
        DOM.settings.openBtn.on('click', () => DOM.settings.modal.css('display', 'block'));
        DOM.settings.closeBtn.on('click', () => DOM.settings.modal.css('display', 'none'));
        DOM.settings.modal.on('click', (e) => { if (e.target === DOM.settings.modal[0]) DOM.settings.modal.css('display', 'none'); });
        DOM.settings.saveBtn.on('click', () => this.saveSettings());
        $(document).on('click', '.play-btn', (e) => { this.stop(); this.addToQueue(Utils.getTrackFromButton($(e.currentTarget))); });
        $(document).on('click', '.queue-btn', (e) => { this.addToQueue(Utils.getTrackFromButton($(e.currentTarget))); });
        $(document).on('click', '.share-btn', (e) => {
            const id = $(e.currentTarget).data('id');
            const shareUrl = `${window.location.origin}${window.location.pathname}?track_id=${encodeURIComponent(id)}`;
            this.shareTrack(shareUrl);
        });
        $(window).on('keydown', (e) => { if (e.key === 'Escape') { DOM.fullscreen.player.addClass('hidden'); DOM.queue.modal.css('display', 'none'); DOM.settings.modal.css('display', 'none'); } });
        $(document).ready(() => {
            PlayerState.drake = dragula([document.getElementById('queue-list')]).on('drop', () => this.updateQueueOrder());
        });
    },
    setupAudioEvents() {
        DOM.audio.addEventListener('timeupdate', () => this.onTimeUpdate());
        DOM.audio.addEventListener('loadedmetadata', () => this.onLoadedMetadata());
        DOM.audio.addEventListener('play', () => this.onPlay());
        DOM.audio.addEventListener('pause', () => this.onPause());
        DOM.audio.addEventListener('ended', () => this.onEnded());
    },
    togglePlay() {
        if (DOM.audio.src && DOM.audio.readyState >= 1) { DOM.audio.paused ? DOM.audio.play() : DOM.audio.pause(); }
        else if (PlayerState.queue.length > 0) { this.playTrack(PlayerState.currentTrackIndex === -1 ? 0 : PlayerState.currentTrackIndex); }
    },
    playTrack(index) {
        if (index < 0 || index >= PlayerState.queue.length) return;
        PlayerState.currentTrackIndex = index;
        const track = PlayerState.queue[index];
        DOM.audio.src = track.src; DOM.audio.load(); DOM.audio.play().catch(e => console.error("Playback failed:", e));
        DOM.playerBar.nowPlaying.text(track.title).attr('title', track.title);
        this.updateFullscreenInfo(track); DOM.audio.loop = PlayerState.isRepeat;
        this.renderQueue(); this.updateMediaSession(track);
    },
    playNext() { if (PlayerState.currentTrackIndex + 1 < PlayerState.queue.length) this.playTrack(PlayerState.currentTrackIndex + 1); else this.stop(); },
    playPrev() { if (DOM.audio.currentTime > 5 || PlayerState.currentTrackIndex === 0) DOM.audio.currentTime = 0; else if (PlayerState.currentTrackIndex > 0) this.playTrack(PlayerState.currentTrackIndex - 1); },
    stop() {
        DOM.audio.pause(); DOM.audio.src = ''; PlayerState.currentTrackIndex = -1; PlayerState.queue = [];
        DOM.playerBar.nowPlaying.text('Очередь пуста...').attr('title', 'Очередь пуста...');
        DOM.playerBar.currentTime.textContent = '00:00'; DOM.playerBar.totalTime.textContent = '00:00';
        DOM.playerBar.progress.val(0).attr('max', 0);
        DOM.playerBar.play.html('<img src="./icon/play.svg">');
        $('#player-bar button, .fs-controls button').removeClass('akihiro-pulse');
        this.renderQueue();
    },
    addToQueue(track) {
        PlayerState.queue.push(track);
        if (PlayerState.currentTrackIndex === -1 && PlayerState.queue.length === 1) this.playTrack(0);
        this.renderQueue();
    },
    removeFromQueue(index) {
        if (index < 0 || index >= PlayerState.queue.length) return;
        const isCurrentlyPlaying = index === PlayerState.currentTrackIndex;
        PlayerState.queue.splice(index, 1);
        if (isCurrentlyPlaying) {
            DOM.audio.pause(); DOM.audio.src = '';
            if (PlayerState.queue.length > 0) this.playTrack(Math.min(index, PlayerState.queue.length - 1));
            else this.stop();
        } else {
            if (index < PlayerState.currentTrackIndex) PlayerState.currentTrackIndex--;
            this.renderQueue();
        }
    },
    renderQueue() {
        DOM.queue.list.empty();
        if (PlayerState.queue.length === 0) { DOM.queue.list.append('<li>Очередь пуста</li>'); return; }
        PlayerState.queue.forEach((track, index) => {
            const $item = $('<li>').attr('data-index', index).attr('data-src', track.src);
            if (index === PlayerState.currentTrackIndex) $item.addClass('playing');
            $item.append($('<span>').text(track.title || 'Без названия'));
            const $controls = $('<div>').addClass('queue-item-controls');
            $controls.append(
                $('<button>').html('<img src="./icon/play.svg">').attr('title', 'Воспроизвести').on('click', (e) => { e.stopPropagation(); this.playTrack(index); }),
                $('<button>').html('&times;').attr('title', 'Удалить').on('click', (e) => { e.stopPropagation(); this.removeFromQueue(index); })
            );
            $item.append($controls); DOM.queue.list.append($item);
        });
    },
    updateQueueOrder() {
        const newQueue = []; let newPlayingIndex = -1;
        DOM.queue.list.find('li').each((newIndex) => {
            const src = $(this).data('src');
            const originalTrack = PlayerState.queue.find(t => t.src === src);
            if (originalTrack) {
                newQueue.push(originalTrack);
                if ($(this).hasClass('playing') || (PlayerState.currentTrackIndex !== -1 && PlayerState.queue[PlayerState.currentTrackIndex]?.src === src)) newPlayingIndex = newIndex;
            }
        });
        PlayerState.queue = newQueue; PlayerState.currentTrackIndex = newPlayingIndex;
        this.renderQueue();
    },
    setVolume(value) { DOM.audio.volume = value; localStorage.setItem(PlayerConfig.storageKeys.volume, value); DOM.playerBar.volume.val(value); DOM.fullscreen.volume.val(value); },
    toggleRepeat() {
        PlayerState.isRepeat = !PlayerState.isRepeat; DOM.audio.loop = PlayerState.isRepeat;
        DOM.playerBar.repeat.toggleClass('active', PlayerState.isRepeat);
        DOM.playerBar.repeat.attr('title', PlayerState.isRepeat ? 'Повтор включен' : 'Повтор выключен');
        if (PlayerState.isRepeat) DOM.fullscreen.repeat.css({ backgroundColor: '#7a3dcc', boxShadow: 'inset 0 0 5px rgba(255,255,255,0.5)' });
        else DOM.fullscreen.repeat.removeAttr('style');
    },
    saveSettings() {
        const visualizerEnabled = DOM.settings.toggleVisualizer.prop('checked');
        const quality = DOM.settings.qualitySelect.val();
        const repeatQueue = DOM.settings.toggleRepeatQueue.prop('checked');
        localStorage.setItem(PlayerConfig.storageKeys.visualizerEnabled, visualizerEnabled);
        localStorage.setItem(PlayerConfig.storageKeys.visualizerQuality, quality);
        localStorage.setItem(PlayerConfig.storageKeys.repeatQueue, repeatQueue);
        PlayerState.visualizerEnabled = visualizerEnabled; PlayerState.visualizerQuality = quality; PlayerState.isRepeatQueue = repeatQueue;
        Visualizer.toggle(visualizerEnabled); Visualizer.setQuality(quality);
        DOM.settings.modal.css('display', 'none');
    },
    openFullscreen(track) {
        DOM.fullscreen.title.text(track.title); DOM.fullscreen.artist.text(track.artist || 'Неизвестный');
        DOM.fullscreen.uploader.text(`Загрузил: ${track.uploader || '???'}`);
        DOM.fullscreen.cover.css('background-image', `url('${track.cover}')`);
        DOM.fullscreen.progress.attr('max', DOM.audio.duration || 0);
        DOM.fullscreen.player.removeClass('hidden'); PlayerState.isFullscreenOpen = true;
    },
    closeFullscreen() { DOM.fullscreen.player.addClass('hidden'); PlayerState.isFullscreenOpen = false; },
    updateFullscreenInfo(track) {
        DOM.fullscreen.title.text(track.title); DOM.fullscreen.artist.text(track.artist || 'Неизвестный');
        DOM.fullscreen.uploader.text(`Загрузил: ${track.uploader || '???'}`);
        DOM.fullscreen.cover.css('background-image', `url('${track.cover}')`);
    },
    onTimeUpdate() {
        if (!DOM.audio.duration) return;
        DOM.playerBar.progress.val(DOM.audio.currentTime);
        DOM.playerBar.currentTime.textContent = Utils.formatTime(DOM.audio.currentTime);
        DOM.fullscreen.progress.val(DOM.audio.currentTime);
        DOM.fullscreen.current.textContent = Utils.formatTime(DOM.audio.currentTime);
        DOM.fullscreen.total.textContent = Utils.formatTime(DOM.audio.duration);
    },
    onLoadedMetadata() {
        DOM.playerBar.progress.attr('max', DOM.audio.duration); DOM.fullscreen.progress.attr('max', DOM.audio.duration);
        DOM.playerBar.totalTime.textContent = Utils.formatTime(Math.floor(DOM.audio.duration));
    },
    onPlay() {
        DOM.playerBar.play.html('<img src="./icon/pause.svg">'); DOM.fullscreen.play.html('<img src="./icon/pause.svg">');
        if (PlayerState.visualizerEnabled && !PlayerState.visualStarted) { Visualizer.start(); PlayerState.visualStarted = true; }
        $("#player-bar button, .fs-controls button").addClass("akihiro-pulse");
    },
    onPause() {
        DOM.playerBar.play.html('<img src="./icon/play.svg">'); DOM.fullscreen.play.html('<img src="./icon/play.svg">');
        $("#player-bar button, .fs-controls button").removeClass("akihiro-pulse");
    },
    onEnded() {
        if (!PlayerState.isRepeat) {
            if (PlayerState.isRepeatQueue && PlayerState.currentTrackIndex + 1 >= PlayerState.queue.length) this.playTrack(0);
            else this.playNext();
        } else { DOM.audio.currentTime = 0; DOM.audio.play(); }
    },
    shareTrack(shareUrl) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(shareUrl).then(() => alert('Ссылка скопирована!')).catch(() => this.fallbackCopy(shareUrl));
        } else this.fallbackCopy(shareUrl);
    },
    fallbackCopy(shareUrl) {
        const textArea = document.createElement("textarea"); textArea.value = shareUrl;
        textArea.style.position = "fixed"; textArea.style.opacity = "0";
        document.body.appendChild(textArea); textArea.focus(); textArea.select();
        document.execCommand('copy'); document.body.removeChild(textArea);
        alert('Ссылка скопирована!');
    },
    updateMediaSession(track) {
        if ('mediaSession' in navigator) {
            navigator.mediaSession.metadata = new MediaMetadata({ title: track.title || 'Unknown Title', artist: track.artist || 'Unknown Artist' });
            navigator.mediaSession.setActionHandler('play', () => this.togglePlay());
            navigator.mediaSession.setActionHandler('pause', () => this.togglePlay());
            navigator.mediaSession.setActionHandler('stop', () => this.stop());
            navigator.mediaSession.setActionHandler('previoustrack', () => DOM.playerBar.prev.click());
            navigator.mediaSession.setActionHandler('nexttrack', () => DOM.playerBar.next.click());
        }
    }
};

$(document).ready(() => {
    initDOM(); Visualizer.init(); QuoteDisplay.init(); Player.init();
    DOM.fullscreen.visualizer.width = window.innerWidth;
    $(window).on('resize', () => { DOM.fullscreen.visualizer.width = window.innerWidth; });
    Visualizer.drawFullscreen();
});

window.queue = PlayerState.queue;
window.playTrack = function(index) { Player.playTrack(index); };
window.PlayerState = PlayerState;
window.Player = Player;