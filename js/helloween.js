var svuk = new Audio("../events/svuki/smeh_helloween.mp3"); //Получаем аудио.
svuk.volume = 0.3; //Устанавливаем громкость для определённого аудио.
var player = new Audio('../song/helloween.mp3');
player.volume = 0.4;
var wedma = new Audio("../events/svuki/witch.mp3");
var bu = new Audio("../events/svuki/bu.mp3");
var bats = new Audio("../events/svuki/bat.mp3");
function TrueSong(){ //Заваём функцию, в случае если пользователь примет предупреждение.
	player.preload = "auto"; //Предзагружаем аудио.
	player.addEventListener('ended', function(){ //Как только аудио прекратит звучание, запускаем его заного.
		player.play();
	});
	svuk.play(); //Воспроизводим аудио
	setTimeout(() => {player.play()},3500);//Вопроизводим следующее аудио с задержкой.
}
var song = confirm("Внимание! Продолжая приближаться к этому порталу, вы чувствуете странные ощущения, ни на что не похоже. Видимо, этот портал был совершенно иным. Вы уверены, что войдёте в него без защиты?");
if (song) { //Если пользователь нажмёт "ОК", то вводим следующее сообщение, иначе отправляем на главную.
	alert("Вы вошли в портал...");
} else {window.location.href="../index"}

function play(){ //Делаем баннер, который позволит автоматически воспроизводить аудио.
	TrueSong();
	setTimeout(() => {document.getElementById('skull').style.display = "none"},300); ///Убираем баннер с определённым id с задержкой.
}

function scrimer(){ //Просто воспроизводим все доступные звуки.
	svuk.play();
	wedma.play();
	bu.play();
	bats.play();
}
function witch(){ //Перемещение ведьмы, со звуковым сопровождением(её смехом).
	var wt = document.getElementById('witch'), pos = 0; //Получаем id картинки ведьмы, и создаём переменную перемещения со значением 0.
	wt.style.display = "block"; //Показываем ведьму.
	wedma.play();
	time = setInterval(function(){ //Создаём функцию перемещения по интервалам.
		pos += 5;
		wt.style.right = pos+"px"; //Перемещаем ведьму интервалами.
		if (pos == 1100) { //Как только ведьма достигает этого значения, скрываем её, и возвращаем на место.
			clearInterval(time);
			wt.style.display = "none";
			wt.style.right = "-170px";
		}
	}, 25);
}
function pumk(){ //Обычный тыквенный скример.
	var pum = document.getElementById("pumpkin"); //Получаем id картинки с тыквой.
	bu.play();
	setTimeout(() => {player.pause(); pum.style.display = "block";}, 500); //С задержкой останавливаем воспроизведение, и показываем картинку.
	setTimeout(() => {pum.style.display = 'none';}, 850); //Обратно прошлой, мы скрываем тыкву.
}
function skull(){ //Обычный скример черепка.
	var sk = document.getElementById("skull"); //Получаем id картинки с черепком.
	bu.play();
	setTimeout(() => {player.pause(); sk.style.display = "block";}, 500); //С задержкой останавливаем воспроизведение, и показываем картинку.
	setTimeout(() => {sk.style.display = 'none';}, 850); //Обратно прошлой, мы скрываем черепок.
}
function avrora(){ //Задаём на каждого персонажа вывод его текста, и вызываем дополнительные звуки.
	document.getElementById("avror").style.display = "block";
	witch();
}
function dark(){
	document.getElementById("dark").style.display = "block";
	bu.play();
}
function imr(){
	document.getElementById("imr").style.display = "block";
	skull();
}
function minam(){
	document.getElementById("minam").style.display = "block";
	witch();
	bat();
}
//Проверяем, играет ли сейчас музыка, и если да, то паузим её, иначе возобновляем.
var el = document.getElementById('play');
var playing = true; //Создаём переменную состояния музыки
el.addEventListener('click', playPause); //Отслеживаем клик по элементу.
function playPause() {
	if( playing) {
    	player.pause();
    	el.innerText = "Paused";
  	} else {
   		player.play();
    	el.innerText = "Playing";
  	}
  	playing = !playing;
}
function bat(){//Ведьм мало, надо + мышек.
	var bt = document.getElementById('bat'), pos = 0;
	bt.style.display = "block";
	bats.play(); //Пусть пищат.
	timeb = setInterval(function(){ //Создаём функцию перемещения по интервалам.
		pos -= 5;
		bt.style.marginTop = pos+"px"; //Перемещаем мышек интервалами.
		if (pos == -1000) { //Как только мыши долетели, скрываем их, и возвращаем на место.
			clearInterval(timeb);
			bt.style.display = "none";
			bt.style.marginTop = "0px";
		}
	}, 25);

}