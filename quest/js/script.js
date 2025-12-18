var goodSong2 = new Audio("./song/2.mp3");
goodSong2.volume = 0.3;
var goodSong1 = new Audio("./song/3.mp3");
goodSong1.volume = 0.3;
var goodSong3 = new Audio("./song/1.mp3");
goodSong3.volume = 0.3;
var neytralSong = new Audio("./song/neytral.mp3");
neytralSong.volume = 0.3;
var badSong1 = new Audio("./song/bad2.mp3");
badSong1.volume = 0.3;
var badSong2 = new Audio("./song/bad.mp3");
badSong2.volume = 0.3;
var song = true;

function Music(){
    if (song){
        song = false;
        document.getElementById('muz').innerHTML = "Music:OFF";
    } else{
        song = true;
        document.getElementById('muz').innerHTML = "Music:ON";
    }
}

function GoodEnd1(){
    goodSong1.preload = "auto";
    document.body.style.backgroundImage = "url('./img/good_fone.jpg')";
    document.getElementById('content-main').style.backgroundColor = "rgba(0,0,0,0.75)";
    document.getElementById('yandex_rtb_R-A-5178800-1').style.backgroundColor = "rgba(0,0,0,0.75)";
    if (song){
        goodSong1.play();
    } else {
        goodSong1.pause();
    }
}

function GoodEnd2(){
    goodSong2.preload = "auto";
    document.body.style.backgroundImage = "url('./img/good_fone.jpg')";
    document.getElementById('content-main').style.backgroundColor = "rgba(0,0,0,0.75)";
    document.getElementById('yandex_rtb_R-A-5178800-1').style.backgroundColor = "rgba(0,0,0,0.75)";
    if (song){
        goodSong2.play();
    } else {
        goodSong2.pause();
    }
}

function GoodEnd3(){
    goodSong3.preload = "auto";
    document.body.style.backgroundImage = "url('./img/good_fone.jpg')";
    document.getElementById('content-main').style.backgroundColor = "rgba(0,0,0,0.75)";
    document.getElementById('yandex_rtb_R-A-5178800-1').style.backgroundColor = "rgba(0,0,0,0.75)";
    if (song){
        goodSong3.play();
    } else {
        goodSong3.pause();
    }
}

function NeytralEnd(){
    neytralSong.preload = "auto";
    if (song){
        neytralSong.play();
    } else {
        neytralSong.pause();
    }
}

function BadEnd1(){
    badSong1.preload = "auto";
    document.body.style.backgroundImage = "url('./img/bad_fone.jpg')";
    document.getElementById('content-main').style.backgroundColor = "rgba(0,0,0,0.75)";
    document.getElementById('yandex_rtb_R-A-5178800-1').style.backgroundColor = "rgba(0,0,0,0.75)";
    if (song){
        badSong1.play();
    } else {
        badSong1.pause();
    }
}

function BadEnd2(){
    badSong2.preload = "auto";
    document.getElementById('content-main').style.backgroundColor = "rgba(0,0,0,0.75)";
    document.getElementById('yandex_rtb_R-A-5178800-1').style.backgroundColor = "rgba(0,0,0,0.75)";
    if (song){
        badSong2.play();
    } else {
        badSong2.pause();
    }
}

answ1_1.onclick = function(){
    document.getElementById("answ1_2").style.display = "none";
    document.getElementById("end1").style.display = "block";
    GoodEnd2();
};
answ1_2.onclick = function(){
    document.getElementById("answ1_1").style.display = "none";
    document.getElementById("quest2").style.display = "block";
};
answ2_1.onclick = function(){
    document.getElementById("answ2_2").style.display = "none";
    document.getElementById("answ2_3").style.display = "none";
    document.getElementById("end2_1").style.display = "block";
    NeytralEnd();
};
answ2_3.onclick = function(){
    document.getElementById("answ2_2").style.display = "none";
    document.getElementById("answ2_1").style.display = "none";
    document.getElementById("quest2_1").style.display = "block";
};
answ2_4.onclick = function(){
    document.getElementById("answ2_5").style.display = "none";
    document.getElementById("answ2_6").style.display = "none";
    document.getElementById("end2_2").style.display = "block";
    NeytralEnd();
};
answ2_5.onclick = function(){
    document.getElementById("answ2_6").style.display = "none";
    document.getElementById("answ2_4").style.display = "none";
    document.getElementById("end2_2").style.display = "block";
    NeytralEnd();
};
answ2_6.onclick = function(){
    document.getElementById("answ2_5").style.display = "none";
    document.getElementById("answ2_4").style.display = "none";
    document.getElementById("end2_3").style.display = "block";
    GoodEnd1();
};
answ2_2.onclick = function(){
    document.getElementById("answ2_3").style.display = "none";
    document.getElementById("answ2_1").style.display = "none";
    document.getElementById("quest3").style.display = "block";
};
answ3_1.onclick = function(){
    document.getElementById("answ3_2").style.display = "none";
    document.getElementById("answ3_3").style.display = "none";
    document.getElementById("end3").style.display = "block";
    BadEnd1();
};
answ3_2.onclick = function(){
    document.getElementById("answ3_1").style.display = "none";
    document.getElementById("answ3_3").style.display = "none";
    document.getElementById("quest3_1").style.display = "block";
};
answ3_3.onclick = function(){
    document.getElementById("answ3_1").style.display = "none";
    document.getElementById("answ3_2").style.display = "none";
    document.getElementById("quest4").style.display = "block";
};
answ3_4.onclick = function(){
    document.getElementById("answ3_5").style.display = "none";
    document.getElementById("end3_1").style.display = "block";
    GoodEnd1();
    
};
answ3_5.onclick = function(){
    document.getElementById("answ3_4").style.display = "none";
    document.getElementById("end3_1").style.display = "block";
    GoodEnd1();
};
answ4_1.onclick = function(){
    document.getElementById("answ4_2").style.display = "none";
    document.getElementById("end4").style.display = "block";
    document.body.style.backgroundImage = "url('./img/bad_fone.jpg')";
    BadEnd2();
};
answ4_2.onclick = function(){
    document.body.style.backgroundImage = "url('./img/cristal.jpg')";
    document.getElementById("answ4_1").style.display = "none";
    document.getElementById("quest5").style.display = "block";
};
answ5.onclick = function(){
    result = prompt("Вы задумываетесь о вашем желании... Какое же оно на самом деле? Что вы действительно хотите приподнести Широ?", "желание");
    if (result.toLowerCase() != "счастье" && result.toLowerCase() != "счастья" && result.toLowerCase() != "радость" && result.toLowerCase() != "радости") {
        document.getElementById("answ5").style.display = "none";
        document.getElementById("end5").style.display = "inline";
        BadEnd2();
    } else {
        document.getElementById("answ5").style.display = "none";
        document.getElementById("quest6").style.display = "block";
    }
};
answ6.onclick = function(){
    document.getElementById("end6").style.display = "block";
    GoodEnd3();
};