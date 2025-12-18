let audio = null;
let currentTrackTitle = null;
let currentTrackPath = null;
const musicModal = document.getElementById('musicModal');
const closeButton = document.getElementById('close');
const playButton = document.getElementById('playMusic');
const seekSlider = document.getElementById('seekSlider');
const volumeSlider = document.getElementById('volumeSlider');
const currentTimeDisplay = document.getElementById('currentTime');
const totalTimeDisplay = document.getElementById('totalTime');
const muteButton = document.getElementById('muteMusic');
const volumeFill = document.getElementById('volumeFill');
const seekFill = document.getElementById('seekFill');
let isDragging = false;

function openModal() {
    musicModal.style.display = "flex";
}

function closeModal() {
    musicModal.style.display = "none";
}

closeButton.onclick = closeModal;
window.onclick = function(event) {
    if (event.target == musicModal) {
      closeModal();
    }
};

function playButtonStart(title, path) {
    if(currentTrackPath !== path){
        if (audio) {
            audio.pause();
            audio.currentTime = 0;
        }
        currentTrackPath = path;
        currentTrackTitle = title;
        audio = new Audio(path);
        playAudio();
    }else{
        playAudio();
    }
    document.getElementById('title').innerHTML = currentTrackTitle;
    openModal();
}


function playAudio(){
    if(audio){
         if (audio.paused) {
            audio.play();
            playButton.innerHTML = '⏸️';
            audio.addEventListener('timeupdate', updateSeekSlider);
            updateTotalTime();
        } else {
            audio.pause();
            playButton.innerHTML = '▶️';
        }
        updateVolume();
        
        audio.onended = function() {
            playButton.innerHTML = '▶️';
             seekSlider.value = 0;
        };
    }
}

function updateTotalTime(){
    audio.addEventListener('loadedmetadata', () => {
        const totalSeconds = Math.floor(audio.duration);
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        totalTimeDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    });
}

function updateSeekSlider() {
    if(!isDragging){
        const progress = (audio.currentTime / audio.duration) * 100;
        seekSlider.value = progress;
        seekFill.style.width = `${progress}%`;
        updateCurrentTime();
    }
}

function updateCurrentTime() {
    const currentSeconds = Math.floor(audio.currentTime);
    const minutes = Math.floor(currentSeconds / 60);
    const seconds = currentSeconds % 60;
    currentTimeDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

seekSlider.addEventListener('input', function() {
    isDragging = true;
     const seekTime = (seekSlider.value / 100) * audio.duration;
    audio.currentTime = seekTime;
    seekFill.style.width = `${seekSlider.value}%`;
});

seekSlider.addEventListener('change', function() {
    isDragging = false;
});

volumeSlider.addEventListener('input', updateVolume);

function updateVolume() {
    if(audio){
         audio.volume = volumeSlider.value / 100;
         volumeFill.style.width = `${volumeSlider.value}%`;
    }
}

playButton.addEventListener('click', playAudio);
muteButton.addEventListener('click', function() {
     if(audio){
        audio.pause();
        audio.currentTime = 0;
        seekSlider.value = 0;
        playButton.innerHTML = '▶️';
    }
});