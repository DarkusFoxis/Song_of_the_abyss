<!DOCTYPE html>
<html>
<head>
    <link rel = "icon" href = "../img/food.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подземелье</title>
	<link rel="stylesheet" href="../style/bootstrap.min.css">
    <link rel="stylesheet" href="../style/dungeon_style1.2.css">
</head>
<body>
  <div class="content-main">
    <div class="container">
      <div class="row">
    	<div class="col-12">
            <div class="game-container">
              <h1 id="title">Подземелье</h1>
              <div class="game-info">
                <label for="game_inf">Комнат прошли:</label>
                <span id="game_inf">0</span>
                <div class="info-item">
                  <label for="health">Вера сакуры:</label>
                  <span id="health">100</span>
                </div>
                <div class="info-item">
                  <label for="coins">Монеты:</label>
                  <span id="coins">0</span>
                </div>
                <div class="info-item">
                  <label for="crystals">Кристаллы:</label>
                  <span id="crystals">0</span>
                </div>
                <div class="info-item">
                  <label for="experience">Бутыльков опыта:</label>
                  <span id="experience">0</span>
                </div>
              </div>
              <div id="effect"></div>
              <div id="output"></div>
              <div id="room-description">Вы проходите в темном подземелье. Судя по сказам народа, тут могут быть скрытые сокровища, поэтому не мудрено, что почти каждый второй желает сюда попасть. Вы готовы погрузиться в малоизвестные катакомбы?</div>
              <div class="game-buttons">
                <button id="trolley" style="display: none;">Пустить вагонетку</button>
                <button id="boxes" style="display: none;">Изучить ящики</button>
                <button id="button" style="display: none;">Нажать на кнопку</button>
                <button id="scream" style="display: none;">Крикнуть</button>

                <button id="firstWater" style="display: none;">Попить из первой реки</button>
                <button id="secondWater" style="display: none;">Попить из второй реки</button>
                
                <button id="pay" style="display: none;">Дать 10 монет</button>
                <button id="dont_pay" style="display: none;">Отказать</button>

                <button id="bootle_up" style="display: none;">Поднять бутылку</button>

                <button id="suspension" style="display: none;">Пройти по мосту</button>
                <button id="edge" style="display: none;">Пройти по краю</button>

                <button id="kick" style="display: none;">Пнуть</button>
                <button id="sneak" style="display: none;">Обойти</button>

                <button id="study" style="display: none;">Изучить пещеру</button>
                <button id="shoot_down" style="display: none;">Сбить кристаллы</button>
                <button id="scream_cristal" style="display: none;">Крикнуть</button>
                <button id="review" style="display: none;">Изучить кристалл</button>

                <button id="go-deeper" >Спуститься глубже</button>
                <button id="restart" style="display: none;">Перезапуск</button>
                <button id="eatEssential" style="display: none;">Съесть кусочек</button>
                <button id="exit">Выйти из подземелья</button>
              </div>
            </div>
          </div>
        </div>
        <div id="custom-context-menu" class="context-menu">
          <ul>
            <li>
              <div class="context-menu-title">Дневник советов:</div>
              <ul>
                <li>🧪: <span id='poison_time'></span>- отравление. Действует долго, но вредит не сильно.</li>
                <li>🧿: <span id='paranoia_time'></span>- паранойя. До сих пор неизвестно, как она влияет на сакуру в этом подземелье...</li>
                <li>🔥: <span id='fire_time'></span>- горение. Поджигает ненадолго, но сильно вредит сакуре...</li>
                <li>🌀: <span id='darknes_time'></span>- тьма... Действует совсем не долго, но крайне сильно вредит сакуре... Необходимо как можно сильнее избегать этого эффекта...</li>
                <li>🩸: <span id='bleeding_time'></span>- кровотечение. Действует недолго, и кровь немного вытекает. Если вы пытаетесь восстановить веру, то кровотечение не позволит этому случиться. Однако эфирная река позволит излечить все раны.</li>
                <li>💜: <span id='regen_time'></span>- защита сакуры. Если на вас есть такой эффект, значит сакура желает вашей жизни.</li>
                <li>Иногда наши действия могут влиять на будущие последствия, поэтому стоит изучать как можно больше уголков подземелья, но очень осторожно...</li>
                <li>Застывшие кусочки эссенции сакуры могут помочь вам в вашем прохождении. Они восстанавливают веру саруры в вас, и лечит от недугов! У вас их сейчас: <span id='count_essential'></span></li>
                <li>В этом подземелье известно <span id="total"></span> комнат, но точно ли они так хорошо известны?...</li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </div>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="../js/dungeon_script1.3.2.2.js"></script>
</body>
</html>