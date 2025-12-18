const musicBtn = document.getElementById("musicBtn");
const modal = document.getElementById("musicModal");
const closeBtn = document.getElementsByClassName("close")[0];
const easterSongBtn = document.getElementById("easterSong");
const morshyParadiseBtn = document.getElementById("morshyParadise");
const endLikeBtn = document.getElementById('end_like_this');
const renaissanceBtn = document.getElementById('renaissance');
const muteMusicBtn = document.getElementById("muteMusic");
const volumeSlider = document.getElementById("volumeSlider");
const modal_song = document.getElementById('content_music');

const audio = new Audio();

function openModal() {
  modal.style.display = "block";
}

function closeModal() {
  modal.style.display = "none";
}

musicBtn.addEventListener("click", openModal);
closeBtn.addEventListener("click", closeModal);
window.addEventListener("click", (event) => {
  if (event.target === modal) {
    closeModal();
  }
});

easterSongBtn.addEventListener("click", () => {
  audio.src = "../song/easter1.mp3";
  audio.play();
});

morshyParadiseBtn.addEventListener("click", () => {
  audio.src = "../song/easter2.mp3";
  audio.play();
});

renaissanceBtn.addEventListener("click", () =>{
    audio.src = "https://cdn.discordapp.com/attachments/805523725814071336/1236421633942618192/Memories_of_the_good_in_the_bad_future2.wav?ex=6637f2e1&is=6636a161&hm=cf679386a7f28f607b3d8fdb3efb3c8d57261a5b9ef17c7db6dfc98fcf32324e&";
    audio.play();
    modal_song.style.backgroundImage = "url('https://cdn.discordapp.com/attachments/805523725814071336/1236425308924678164/generated_image_f9ebc7b90a0211ef8f7fae5494798a57.jpg?ex=6637f64d&is=6636a4cd&hm=52926b2ebcb0b0926a065283b188fbfa4beb4226926f10d79ea58a97a8fa8514&')";
    thank.innerHTML = "Bis bald, Саймон Уствид. Bis bald. Song by: @doomich71";
});

endLikeBtn.addEventListener("click", () => {
  audio.src = "../song/easter3.mp3";
  audio.play();
  modal_song.style.backgroundImage = "url('../img/rikroll8.jpeg')";
});

muteMusicBtn.addEventListener("click", () => {
  audio.pause();
});

volumeSlider.addEventListener("input", () => {
  audio.volume = volumeSlider.value / 100;
});