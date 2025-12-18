let currentLang = 'ru';
const quotes = {
  ru: [
    'В цифровых просторах я чувствую себя как дома.',
    'Новые технологии — это двери в миры, которых ещё не существует.',
    'Каждый проект — это маленькая вселенная.',
    'Мир неко — это уют и тепло, даже в холодном коде.',
    'Ограничения — лишь вызов для творчества.',
    '«Error 404: Worlds not found...» Но я создаю их снова — как бред, как код.',
    'Боль в их глазах — моя 「永遠の罰」.',
    'Бездна… Мир вечных войн, противостояний.',
    'Горит замок твой, король без лица!',
    'Я мстила за тех, кто ушёл в никуда...',
    'DarkOleFox, Китцунэ, бездны хранитель.',
    'Но Сакура корнями держит, не даёт упасть.',
    '«Answer me, fox spirit! 教えて...»'
  ],
  en: [
    'In the digital realms, I feel at home.',
    'New technologies are doors to worlds that don’t exist yet.',
    'Every project is its own little universe.',
    'The neko world is warmth and comfort, even in cold code.',
    'Restrictions are just challenges for creativity.',
    '«Error 404: Worlds not found...» Но я создаю их снова — как бред, как код.',
    'Боль в их глазах — моя 「永遠の罰」.',
    'Abyss... A world of eternal wars, confrontations.',
    'Your castle burns, faceless king!',
    'I avenged those who went nowhere...',
    'DarkOleFox, Kitsune, guardian of the abyss.',
    'But Sakura holds with roots, doesn\'t let fall.',
    '«Answer me, fox spirit! 教えて...»'
  ]
};

function toggleLang() {
  currentLang = currentLang === 'ru' ? 'en' : 'ru';
  document.querySelectorAll('[data-lang-ru]').forEach(el => el.hidden = currentLang !== 'ru');
  document.querySelectorAll('[data-lang-en]').forEach(el => el.hidden = currentLang !== 'en');
}

function randomQuote() {
  const quoteElement = document.getElementById('quote');
  const randomIndex = Math.floor(Math.random() * quotes[currentLang].length);
  quoteElement.textContent = quotes[currentLang][randomIndex];
  
  quoteElement.style.animation = 'none';
  setTimeout(() => {
    quoteElement.style.animation = 'fadeIn 0.5s ease';
  }, 10);
}

function showToast(card) {
  const toastContainer = document.getElementById('toast-container');
  const icons = {
    alive: 'fa-seedling',
    dead: 'fa-skull',
    sub: 'fa-code-branch'
  };
  
  const message = currentLang === 'ru' 
    ? card.getAttribute('data-toast-ru') 
    : card.getAttribute('data-toast-en');

  const type = card.getAttribute('data-type') || 'info';
  const icon = icons[type] || 'fa-info-circle';

  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;

  toastContainer.appendChild(toast);

  setTimeout(() => {
    toast.remove();
  }, 3000);
}

function togglePlay(button, playerId) {
  const player = document.getElementById(playerId);
  const icon = button.querySelector('i');
  
  if (player.paused) {
    player.play();
    icon.classList.remove('fa-play');
    icon.classList.add('fa-pause');
    button.closest('.track-card').classList.add('playing');
  } else {
    player.pause();
    icon.classList.remove('fa-pause');
    icon.classList.add('fa-play');
    button.closest('.track-card').classList.remove('playing');
  }
}

function initPlayer(playerId) {
  const player = document.getElementById(playerId);
  const duration = formatTime(player.duration);
  document.getElementById(`duration${playerId.slice(-1)}`).textContent = duration;
}

function updateProgress(playerId) {
  const player = document.getElementById(playerId);
  const progressBar = document.getElementById(`progress${playerId.slice(-1)}`);
  const currentTime = document.getElementById(`current${playerId.slice(-1)}`);
  
  const percent = (player.currentTime / player.duration) * 100;
  progressBar.style.width = `${percent}%`;
  
  currentTime.textContent = formatTime(player.currentTime);
}

function seek(event, playerId) {
  const player = document.getElementById(playerId);
  const progressContainer = event.currentTarget;
  const progressBar = document.getElementById(`progress${playerId.slice(-1)}`);
  
  const containerWidth = progressContainer.clientWidth;
  const clickPosition = event.offsetX;
  const percent = (clickPosition / containerWidth) * 100;
  
  player.currentTime = (percent / 100) * player.duration;
  progressBar.style.width = `${percent}%`;
}

function setVolume(event, playerId) {
  const player = document.getElementById(playerId);
  const volumeSlider = event.currentTarget;
  const volumeLevel = volumeSlider.querySelector('.volume-level');
  
  const containerWidth = volumeSlider.clientWidth;
  const clickPosition = event.offsetX;
  const percent = (clickPosition / containerWidth) * 100;
  
  player.volume = percent / 100;
  volumeLevel.style.width = `${percent}%`;
}

function formatTime(seconds) {
  if (isNaN(seconds)) return '0:00';
  
  const minutes = Math.floor(seconds / 60);
  seconds = Math.floor(seconds % 60);
  return `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
}

document.querySelectorAll('audio').forEach(audio => {
  audio.addEventListener('play', function() {
    document.querySelectorAll('audio').forEach(otherAudio => {
      if (otherAudio !== audio && !otherAudio.paused) {
        otherAudio.pause();
        const otherButton = otherAudio.closest('.track-card').querySelector('.play-btn');
        if (otherButton) {
          const icon = otherButton.querySelector('i');
          icon.classList.remove('fa-pause');
          icon.classList.add('fa-play');
          otherButton.closest('.track-card').classList.remove('playing');
        }
      }
    });
  });
});
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('audio').forEach(audio => {
    audio.volume = 0.8;
  });
});

document.querySelectorAll('.nav-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');

    document.querySelectorAll('.tab-content').forEach(content => {
      content.classList.remove('active');
    });

    const tabId = tab.getAttribute('data-tab');
    document.getElementById(tabId).classList.add('active');
  });
});

document.addEventListener('DOMContentLoaded', () => {
  toggleLang();
});