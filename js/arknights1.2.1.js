// jshint maxerr:1000
const musicBtn = document.getElementById("musicBtn");
const modal = document.getElementById("musicModal");
const closeBtn = document.getElementsByClassName("close")[0];
const acheinpulseBtn = document.getElementById("ache_in_pulse");
const renegateBtn = document.getElementById("renegate");
const endLikeBtn = document.getElementById('end_like_this');
const boilingbloodBtn = document.getElementById('boiling_blood');
const lastofmeBtn = document.getElementById('last_of_me');
const heartforestBtn = document.getElementById('heart_forest');
const requiemBtn = document.getElementById('requiem');
const muteMusicBtn = document.getElementById("muteMusic");
const playMusicBtn = document.getElementById("playMusic");
const volumeSlider = document.getElementById("volumeSlider");
const seekSlider = document.getElementById("seekSlider");
const currentTimeElement = document.getElementById('currentTime');
const totalTimeElement = document.getElementById('totalTime');
const modal_song = document.getElementById('content_music');
const title = document.getElementById('title');
const special = document.getElementById('special');

const audio = new Audio();
audio.src = '../song/arknights.mp3';

let count = 0;

let colorStyle;
let bg ;

function openModal() {
  modal.style.display = "block";
}

function closeModal() {
  modal.style.display = "none";
}

audio.addEventListener('ended', function(){
  onblure();
  title.innerHTML = 'Настройки музыки';

  bg = "none";
  colorStyle = 'white';

  modal_song.style.backgroundImage = bg;
  modal_song.style.color = colorStyle;

  audio.src = '../song/arknights.mp3';
});

function blure() {
  acheinpulseBtn.classList.add('btn-playing');
  renegateBtn.classList.add('btn-playing');
  endLikeBtn.classList.add('btn-playing');
  boilingbloodBtn.classList.add('btn-playing');
  lastofmeBtn.classList.add('btn-playing');
  playMusicBtn.classList.add('btn-playing');
  heartforestBtn.classList.add('btn-playing');
  requiemBtn.classList.add('btn-playing');
  muteMusicBtn.style.opacity = '0.5';
  title.style.opacity = '0.5';
}

function onblure() {
  acheinpulseBtn.classList.remove('btn-playing');
  renegateBtn.classList.remove('btn-playing');
  endLikeBtn.classList.remove('btn-playing');
  boilingbloodBtn.classList.remove('btn-playing');
  lastofmeBtn.classList.remove('btn-playing');
  playMusicBtn.classList.remove('btn-playing');
  heartforestBtn.classList.remove('btn-playing');
  requiemBtn.classList.remove('btn-playing');
  muteMusicBtn.style.opacity = '1';
  title.style.opacity = '1';
}

function joinInRodos(){
    if (count == 6) { 
        const resultDiv = document.getElementById('result');
        $.ajax({
            url: '../achievement_core',
            type: 'POST',
            data: {achievement: 'arknights' },
            success: function(response) {
                resultDiv.textContent = response;
            },
            error: function(xhr, status, error) {
                console.error('Ошибка AJAX: ' + error);
            }
        });
    } else {
        return;
    }
}

musicBtn.addEventListener("click", openModal);
closeBtn.addEventListener("click", closeModal);
window.addEventListener("click", (event) => {
  if (event.target === modal) {
    closeModal();
  }
});

acheinpulseBtn.addEventListener("click", () => {
  audio.src = "../song/ACHE_IN_PULSE.mp3";
  audio.play();

  bg = "url('../img/ache_in_pulse.png')";
  colorStyle = 'black';

  modal_song.style.backgroundImage = bg;
  modal_song.style.color = colorStyle;
  blure();
  title.innerHTML = 'Сейчас играет: ACHE IN PULSE';
  
  count += 1;
  
  if (count == 6) {
    special.innerHTML = '<a id="rodos" onclick="joinInRodos()">Join in Rodos</a>';
  } else if (count >= 7) {
    special.innerHTML = '';
    count = 0;
  }
});

renegateBtn.addEventListener("click", () => {
  audio.src = "../song/renegade.mp3";
  audio.play();

  bg = "url('../img/Renegade.png')";
  colorStyle = 'white';

  modal_song.style.backgroundImage = bg;
  modal_song.style.color = colorStyle;
  blure();
  title.innerHTML = 'Сейчас играет: Renegade'; 
  
  special.innerHTML = '';
  count = 0;
});

boilingbloodBtn.addEventListener("click", () =>{
  audio.src = "../song/Boiling_Blood.mp3";
  audio.play();

  bg = "url('../img/boiling_blood.jpg')";
  colorStyle = 'black';

  modal_song.style.backgroundImage = bg;
  modal_song.style.color = colorStyle;
  blure();
  title.innerHTML = 'Сейчас играет: Boiling Blood'; 
  
  special.innerHTML = '';
  count = 0;
});

endLikeBtn.addEventListener("click", () => {
  audio.src = "../song/End_Like_This.mp3";
  audio.play();

  bg = "url('../img/rikroll8.jpeg')";
  colorStyle = 'black';

  modal_song.style.backgroundImage = bg;
  modal_song.style.color = colorStyle;
  blure();
  title.innerHTML = 'Сейчас играет: End Like This'; 
  
  special.innerHTML = '';
  count = 0;
});

lastofmeBtn.addEventListener("click", () => {
  audio.src = "../song/Last_Of_Me.mp3";
  audio.play();

  bg = "url('../img/last_of_me.png')";
  colorStyle = 'black';

  modal_song.style.backgroundImage = bg;
  modal_song.style.color = colorStyle;
  blure();
  title.innerHTML = 'Сейчас играет: Last Of Me'; 
  
  special.innerHTML = '';
  count = 0;
});

heartforestBtn.addEventListener("click", () => {
  audio.src = "../song/Heart_Forest.mp3";
  audio.play();

  bg = "url('../img/heart_forest.jpg')";
  colorStyle = 'black';

  modal_song.style.backgroundImage = bg;
  modal_song.style.color = colorStyle;
  blure();
  title.innerHTML = 'Сейчас играет: Heart Forest'; 
  
  special.innerHTML = '';
  count = 0;
});

requiemBtn.addEventListener("click", () => {
  audio.src = "../song/Requiem.mp3";
  audio.play();

  bg = "url('../img/requiem.jpeg')";
  colorStyle = 'black';

  modal_song.style.backgroundImage = bg;
  modal_song.style.color = colorStyle;
  blure();
  title.innerHTML = 'Сейчас играет: Requiem'; 
  
  special.innerHTML = '';
  count = 0;
});

muteMusicBtn.addEventListener("click", () => {
  audio.pause();

  modal_song.style.backgroundImage = "none";
  modal_song.style.color = 'white';
  onblure();
});

playMusicBtn.addEventListener("click", () => {
  audio.play();

  modal_song.style.backgroundImage = bg;
  modal_song.style.color = colorStyle;
  blure();
});

volumeSlider.addEventListener("input", () => {
  audio.volume = volumeSlider.value / 100;
});

audio.addEventListener('loadedmetadata', () => {
  const totalSeconds = Math.floor(audio.duration);
  const totalMinutes = Math.floor(totalSeconds / 60);
  const remainingSeconds = totalSeconds % 60;
  totalTimeElement.textContent = `${totalMinutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
  seekSlider.max = totalSeconds;
});

audio.addEventListener('timeupdate', () => {
  const currentSeconds = Math.floor(audio.currentTime);
  const currentMinutes = Math.floor(currentSeconds / 60);
  const remainingSeconds = currentSeconds % 60;
  currentTimeElement.textContent = `${currentMinutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
  seekSlider.value = currentSeconds;
});

seekSlider.addEventListener('input', () => {
  audio.currentTime = seekSlider.value;
});