// jshint maxerr:700
let queue = [];
let currentTrackIndex = -1;
let isRepeat = false;
let drake = null;
let visualizerEnabled = false;
let visualizerQuality = 'medium';
let fftSize = 64;
let audioCtx, analyser, source, dataArray;
let visualStarted = false;
let isAudioConnectedToContext = false;
let isRepeatQueue = false;


const audio = document.getElementById('audio');
const currentTimeDisplay = document.getElementById('currentTime');
const totalTimeDisplay = document.getElementById('totalTime');
const nowPlaying = $('#now-playing');
const playBtn = $('#play');
const stopBtn = $('#stop');
const nextBtn = $('#next');
const prevBtn = $('#prev');
const repeatBtn = $('#repeat');
const progress = $('#progress');
const volume = $('#volume');
const queueModal = $('#queueModal');
const openQueueModalBtn = $('.openQueueModalBtn');
const closeQueueModalBtn = $('#closeQueueModalBtn');
const queueList = $('#queue-list');
const clearQueueBtn = $('#clearQueueBtn');
const fsPlayer = $('#fullscreen-player');
const fsPlay = $('#fs-play');
const fsNext = $('#fs-next');
const fsPrev = $('#fs-prev');
const fsRepeat = $('#fs-repeat');
const fsTitle = $('#fs-title');
const fsArtist = $('#fs-artist');
const fsUploader = $('#fs-uploader');
const fsProgress = $('#fs-progress');
const fsVolume = $('#fs-volume');
const fsCurrent = $('#fs-current');
const fsTotal = $('#fs-total');
const fsCover = $('#player-cover');
const fsVisualizer = document.getElementById('fs-visualizer');
const fsClose = $('#closeFullscreenBtn');

const setting = $('#visualizerSettingsModal');
const openSetting = $('.setting');
const closeSetting = $('#closeVisualizerSettings');
const toggleVisualizer = $('#toggle-visualizer');
const qualitySelect = $('#quality-select');
const save = $('#saveVisualizerSettings');

openSetting.on('click', function() {
    setting.css('display', 'block');
});

closeSetting.on('click', function() {
    setting.css('display', 'none');
});

window.onclick = (e) => { if (e.target == setting) setting.css('display', 'none')};

save.on("click", function() {
    localStorage.setItem('visualizerEnabled', toggleVisualizer.prop('checked'));
    localStorage.setItem('visualizerQuality', qualitySelect.val());
    localStorage.setItem('repeatQueueEnabled', $('#toggle-repeat-queue').prop('checked'));
    console.log(`Новые данные: Визуализатор: ${toggleVisualizer.prop('checked')}, Качество: ${qualitySelect.val()}, Повтор очереди: ${$('#toggle-repeat-queue').prop('checked')}`);
    setting.css('display', 'none');
    applyVisualizerSettings();
});

function formatTime(seconds) {
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${s < 10 ? '0' + s : s}`;
}

function openFullscreenPlayer(track) {
    fsTitle.text(track.title);
    fsArtist.text(track.artist || 'Неизвестный');
    fsUploader.text(`Загрузил: ${track.uploader || '???'}`);
    fsCover.css('background-image', `url('${track.cover}')`);
    fsProgress.attr('max', audio.duration);
    fsPlayer.removeClass('hidden');
}

fsClose.on('click', () => fsPlayer.addClass('hidden'));
fsPlay.on('click', () => audio.paused ? audio.play() : audio.pause());
fsNext.on('click', () => { if (currentTrackIndex + 1 < queue.length) playTrack(currentTrackIndex + 1); });
fsPrev.on('click', () => {
    if (audio.currentTime > 5) audio.currentTime = 0;
    else if (currentTrackIndex > 0) playTrack(currentTrackIndex - 1);
});
fsRepeat.on('click', () => {
    switchRepeat();
});

fsProgress.on('input', function() { audio.currentTime = this.value; });
fsVolume.on('input', function() { volumeSet(this.value); });

audio.addEventListener('timeupdate', () => {
    fsProgress.val(audio.currentTime);
    fsCurrent.text(formatTime(audio.currentTime));
    fsTotal.text(formatTime(audio.duration || 0));
});

window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fsPlayer.addClass('hidden');
});

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

function applyVisualizerSettings() {
    visualizerEnabled = localStorage.getItem('visualizerEnabled') === 'true';
    visualizerQuality = localStorage.getItem('visualizerQuality') || 'medium';
    isRepeatQueue = localStorage.getItem('repeatQueueEnabled') === 'true';
    fftSize = 64;
    if (visualizerQuality === 'low') fftSize = 32;
    if (visualizerQuality === 'high') fftSize = 128;
    toggleVisualizer.prop('checked', visualizerEnabled);
    qualitySelect.val(visualizerQuality);
    $('#toggle-repeat-queue').prop('checked', isRepeatQueue);

    if (visualizerEnabled) {
        canvas.show();
        if (!audioCtx) {
            initVisualizer();
        } else {
            analyser.fftSize = fftSize;
            dataArray = new Uint8Array(analyser.frequencyBinCount);
        }
    } else {
        canvas.hide();
        if (audioCtx && source && analyser && isAudioConnectedToContext) {
            try {
                source.disconnect();
                analyser.disconnect();
                source.connect(audioCtx.destination);
                isAudioConnectedToContext = false;
            } catch (e) {
                console.warn("Ошибка отключения визуализатора:", e);
            }
        }
    }
}

const canvas = $('<canvas id="akihiro-visualizer" height="35"></canvas>').css({
  width: '50%',
  position: 'fixed',
  bottom: '55px',
  left: '50%',
  transform: 'translateX(-50%)',
  zIndex: 999,
  pointerEvents: 'none'
});

$('body').append(canvas);
canvas.hide();

if (visualizerEnabled) {
  $('#player-bar').before(canvas);
}

const ctx = canvas[0].getContext('2d');

function initVisualizer() {
    if (!visualizerEnabled || isAudioConnectedToContext) return;
    try {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        analyser = audioCtx.createAnalyser();
        analyser.fftSize = fftSize;
        source = audioCtx.createMediaElementSource(audio);
        source.connect(analyser);
        analyser.connect(audioCtx.destination);
        isAudioConnectedToContext = true;
        const bufferLength = analyser.frequencyBinCount;
        dataArray = new Uint8Array(bufferLength);
        drawVisualizer();
    } catch (error) {
        console.error("Ошибка инициализации визуализатора:", error);
        cleanupVisualizer();
    }
}

function drawVisualizer() {
    if (!visualizerEnabled || !analyser || !dataArray) {
        return;
    }
    requestAnimationFrame(drawVisualizer);
    try {
        analyser.getByteFrequencyData(dataArray);
        ctx.clearRect(0, 0, canvas[0].width, canvas[0].height);
        const barWidth = canvas[0].width / dataArray.length;
        
        for (let i = 0; i < dataArray.length; i++) {
            const barHeight = dataArray[i] / 2;
            const x = i * barWidth;
            ctx.fillStyle = 'rgba(153, 102, 204, 0.6)';
            ctx.fillRect(x, canvas[0].height - barHeight, barWidth - 1, barHeight);
        }
    } catch (error) {
        console.error("Ошибка в drawVisualizer:", error);
        cleanupVisualizer();
    }
}

const style = document.createElement('style'); 
style.innerHTML = `
    .akihiro-pulse{
        animation: pulseAkihiro 1.5s infinite ease-in-out;
    } 
    @keyframes pulseAkihiro { 
        0% { box-shadow: 0 0 5px #9966cc; } 
        50% { box-shadow: 0 0 15px #9966cc; } 
        100% { box-shadow: 0 0 5px #9966cc; } 
    }`; 
document.head.appendChild(style);

const fsCtx = fsVisualizer.getContext('2d');
function drawFsVisualizer() {
    requestAnimationFrame(drawFsVisualizer);
    if (!analyser) return;
    analyser.getByteFrequencyData(dataArray);
    fsCtx.clearRect(0, 0, fsVisualizer.width, fsVisualizer.height);
    const barWidth = (fsVisualizer.width / dataArray.length);
    for (let i = 0; i < dataArray.length; i++) {
        const barHeight = dataArray[i] / 1.8;
        const x = i * barWidth;
        fsCtx.fillStyle = `rgba(255, 255, 255, 0.4)`;
        fsCtx.fillRect(x, fsVisualizer.height - barHeight, barWidth - 1, barHeight);
    }
}
fsVisualizer.width = window.innerWidth;
window.addEventListener('resize', () => fsVisualizer.width = window.innerWidth);
drawFsVisualizer();

$(document).ready(function() {
    const savedVolume = localStorage.getItem('playerVolume');
    if (savedVolume !== null) {
        audio.volume = parseFloat(savedVolume);
        volume.val(savedVolume);
    } else {
        audio.volume = 1;
        volume.val(1);
    }
    drake = dragula([document.getElementById('queue-list')])
        .on('drop', function (el, target, source, sibling) {
            updateQueueOrder();
        });
});

playBtn.on('click', function() {
    if (audio.src && audio.readyState >= 1) {
        if (audio.paused) {
            audio.play();
        } else {
            audio.pause();
        }
    } else if (queue.length > 0 && currentTrackIndex === -1) {
        playTrack(0);
    } else if (queue.length > 0 && currentTrackIndex !== -1) {
        audio.play();
    }
}); 

stopBtn.on('click', function() {
    stop();
});

nextBtn.on('click', function() {
    playNextTrack();
});

prevBtn.on('click', function() {
    if (audio.currentTime > 5 || currentTrackIndex === 0) {
        audio.currentTime = 0;
    } else if (currentTrackIndex > 0) {
        playTrack(currentTrackIndex - 1);
    }
});

repeatBtn.on('click', function() {
    switchRepeat();
});

$(document).on('click', '.play-btn', function() {
    stop();
    const src = $(this).data('src');
    const title = $(this).data('title') || src.split('/').pop().replace(/\.(mp3|wav|ogg|flac|aac|m4a)$/i, '');
    const artist = $(this).data('artist');
    const uploader = $(this).data('uploader');
    const cover = $(this).data("cover");
    addTrackToQueue({ src: src, title: title, artist: artist, uploader: uploader, cover: cover });
});

$(document).on('click', '.queue-btn', function() {
    const src = $(this).data('src');
    const title = $(this).data('title') || src.split('/').pop().replace(/\.(mp3|wav|ogg|flac|aac|m4a)$/i, '');
    const artist = $(this).data('artist');
    const uploader = $(this).data('uploader');
    const cover = $(this).data("cover");
    addTrackToQueue({ src: src, title: title, artist: artist, uploader: uploader, cover: cover });
});

$(document).on('click', '.share-btn', function() {
    const id = $(this).data('id');
    const shareUrl = `${window.location.origin}${window.location.pathname}?track_id=${encodeURIComponent(id)}`;

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(shareUrl).then(() => {
            alert('Ссылка на трек скопирована!');
        }).catch(err => {
            console.error('Could not copy text: ', err);
            prompt('Не удалось скопировать автоматически. Нажмите Ctrl+C, Enter:', shareUrl);
        });
    } else {
        try {
            const textArea = document.createElement("textarea");
            textArea.value = shareUrl;
            textArea.style.position = "fixed";
            textArea.style.opacity = "0";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Ссылка на трек скопирована!');
        } catch (err) {
            console.error('Fallback copy failed: ', err);
            prompt('Не удалось скопировать автоматически. Нажмите Ctrl+C, Enter:', shareUrl);
         }
    }
});

audio.addEventListener('play', () => {
    playBtn.html('<img src="./icon/pause.svg">');
    fsPlay.html('<img src="./icon/pause.svg">');
    if (visualizerEnabled && !visualStarted) {
        initVisualizer();
        visualStarted = true;
    }
    $("#player-bar button").addClass("akihiro-pulse");
    $('.fs-controls button').addClass('akihiro-pulse');
});

audio.addEventListener('pause', () => {
    playBtn.html('<img src="./icon/play.svg">');
    fsPlay.html('<img src="./icon/play.svg">');
    $("#player-bar button").removeClass("akihiro-pulse");
    $('.fs-controls button').removeClass('akihiro-pulse');
});

$('#openFullscreenBtn').on('click', function() {
   if(queue[currentTrackIndex]) {
       openFullscreenPlayer(queue[currentTrackIndex]);
   } 
});

audio.addEventListener('loadedmetadata', () => {
    const totalSeconds = Math.floor(audio.duration);
    progress.attr('max', audio.duration);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    totalTimeDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

audio.addEventListener('timeupdate', () => {
    if (!audio.duration) return;
    progress.val(audio.currentTime);
    const currentSeconds = Math.floor(audio.currentTime);
    const minutes = Math.floor(currentSeconds / 60);
    const seconds = currentSeconds % 60;
    currentTimeDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

audio.addEventListener('ended', () => {
    if (!isRepeat) {
        if (isRepeatQueue) {
            if (currentTrackIndex + 1 < queue.length) {
                playNextTrack();
            } else {
                playTrack(0);
            }
        } else {
            playNextTrack();
        }
    } else {
        audio.currentTime = 0;
        audio.play();
    }
});

progress.on('input', function() {
     if(audio.readyState >= 2) {
        audio.currentTime = this.value;
     }
});

function volumeSet(data) {
    audio.volume = data;
    localStorage.setItem('playerVolume', data);
    
    volume.val(data);
    fsVolume.val(data);
}

volume.on('input', function() {
    volumeSet(this.value);
});

openQueueModalBtn.on('click', function(e) {
    e.preventDefault();
    renderQueueList();
    queueModal.css('display', 'block');
});

closeQueueModalBtn.on('click', function() {
    queueModal.css('display', 'none');
});

$(window).on('click', function(event) {
    if (event.target == queueModal[0]) {
        queueModal.css('display', 'none');
    }
});

clearQueueBtn.on('click', function() {
    stopBtn.click();
    renderQueueList();
});
function stop() {
    audio.pause();
    audio.src = null;
    currentTrackIndex = -1;
    queue = [];
    nowPlaying.text('Очередь пуста...').attr('title', 'Очередь пуста...');
    currentTimeDisplay.textContent = '00:00';
    totalTimeDisplay.textContent = '00:00';
    progress.val(0).attr('max', 0);
    playBtn.html('<img src="./icon/play.svg">');
    $('#player-bar button').removeClass('akihiro-pulse');
    $('.fs-controls button').removeClass('akihiro-pulse');
    renderQueueList();
}
function addTrackToQueue(track) {
    queue.push(track);
    if (currentTrackIndex === -1 && queue.length === 1) {
        playTrack(0);
    }
    renderQueueList();
    
}
function playTrack(index) {
    if (index >= 0 && index < queue.length) {
        currentTrackIndex = index;
        const track = queue[index];
        audio.src = track.src;
        audio.load();
        audio.play().catch(e => console.error("Playback failed:", e));
        nowPlaying.text(track.title).attr('title', track.title);

        fsTitle.text(track.title);
        fsArtist.text(track.artist || 'Неизвестный');
        fsUploader.text(`Загрузил: ${track.uploader || '???'}`);
        fsCover.css('background-image', `url('${track.cover}')`);

        audio.loop = isRepeat;
        renderQueueList();
        updateMediaSession(track);
    } else {
        stopBtn.click();
        console.log("playTrack: Invalid index or empty queue", index);
    }
}
function playNextTrack() {
    if (currentTrackIndex + 1 < queue.length) {
        playTrack(currentTrackIndex + 1);
    } else {
        stopBtn.click();
    }
}

function switchRepeat() {
    isRepeat = !isRepeat;
    audio.loop = isRepeat;
    if (isRepeat) {
        fsRepeat.css({
            backgroundColor: '#7a3dcc',
            boxShadow: 'inset 0 0 5px rgba(255,255,255,0.5)'
        });
    } else {
        fsRepeat.removeAttr('style');
    }
    $('#repeat').toggleClass('active', isRepeat);
    $('#repeat').attr('title', isRepeat ? 'Повтор включен' : 'Повтор выключен');
}

function removeTrackFromQueue(indexToRemove) {
    if (indexToRemove < 0 || indexToRemove >= queue.length) return;
    const removedSrc = queue[indexToRemove].src;
    const isCurrentlyPlaying = (indexToRemove === currentTrackIndex);
    queue.splice(indexToRemove, 1);

     if (isCurrentlyPlaying) {
        audio.pause();
        audio.src = '';

         if (queue.length > 0) {
            const nextIndex = indexToRemove;
             if (nextIndex < queue.length) {
                playTrack(nextIndex);
             } else {
                 if (queue.length > 0) {
                    playTrack(queue.length - 1);
                 } else {
                    stopBtn.click();
                 }
             }
         } else {
            stopBtn.click();
         }
    } else {
         if (indexToRemove < currentTrackIndex) {
            currentTrackIndex--;
         }
        renderQueueList();
    }
}
 function renderQueueList() {
    queueList.empty();
    if (queue.length === 0) {
        queueList.append('<li>Очередь пуста</li>');
    } else {
         queue.forEach((track, index) => {
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
                        playTrack(findQueueIndexBySrc(track.src));
                    });
             controlsDiv.append(playItemBtn);

             const deleteBtn = $('<button>')
                 .html('&times;')
                 .attr('title', 'Удалить из очереди')
                 .on('click', (e) => {
                      e.stopPropagation();
                      removeTrackFromQueue(findQueueIndexBySrc(track.src));
                 });
             controlsDiv.append(deleteBtn);
             listItem.append(controlsDiv);
             if (index === currentTrackIndex) {
                 listItem.addClass('playing');
             }
             queueList.append(listItem);
         });
     }
}
function updateQueueOrder() {
    const newQueue = [];
    const listItems = queueList.find('li');
    let newPlayingIndex = -1;
    listItems.each(function(newIndex) {
        const src = $(this).data('src');
        const originalTrack = queue.find(track => track.src === src);
         if (originalTrack) {
            newQueue.push(originalTrack);
            if ($(this).hasClass('playing') || (currentTrackIndex !== -1 && queue[currentTrackIndex]?.src === src)) {
                newPlayingIndex = newIndex;
            }
        }
    });
    queue = newQueue;
    currentTrackIndex = newPlayingIndex;
    renderQueueList();
}
function findQueueIndexBySrc(src) {
    return queue.findIndex(track => track.src === src);
}
 function updateMediaSession(track) {
    if ('mediaSession' in navigator) {
        navigator.mediaSession.metadata = new MediaMetadata({
            title: track.title || 'Unknown Title',
            artist: track.artist || 'Unknown Artist',
        });
        navigator.mediaSession.setActionHandler('play', () => { playBtn.click(); });
        navigator.mediaSession.setActionHandler('pause', () => { playBtn.click(); });
        navigator.mediaSession.setActionHandler('stop', () => { stopBtn.click(); });
        navigator.mediaSession.setActionHandler('previoustrack', () => { prevBtn.click(); });
        navigator.mediaSession.setActionHandler('nexttrack', () => { nextBtn.click(); });
     }
}
window.addEventListener('DOMContentLoaded', () => {
    const savedEnabled = localStorage.getItem('visualizerEnabled');
    const savedQuality = localStorage.getItem('visualizerQuality') || 'medium';
    const savedRepeatQueue = localStorage.getItem('repeatQueueEnabled');
    toggleVisualizer.prop('checked', savedEnabled === null ? true : savedEnabled === 'true');
    qualitySelect.val(savedQuality);
    $('#toggle-repeat-queue').prop('checked', savedRepeatQueue === null ? false : savedRepeatQueue === 'true');
    applyVisualizerSettings();
});