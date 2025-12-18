// jshint maxerr:1000
$(document).ready(function() {
  var coin = 0;
  var coinMultiplier = 0.3;
  var coinUpgradePrice = 35;

  var xp = 0;
  var xpMultiplier = 0.20;
  var xpUpgradePrice = 25;

  var gem = 0;
  var gemMultiplier = 0.05;
  
  var clickDelay = 1400;
  var clickDelayUpgradePrice = 50;

  var isWindowFocused = true;
  var isClickEnabled = true;

  function updateValues() {
    $("#coins").text(coin.toFixed(2));
    $("#xp").text(xp.toFixed(2));
    $("#gems").text(gem.toFixed(2));
    $("#click-delay").text(`Задержка: ${clickDelay.toFixed(0)}ms`);
    updateUpgradeUI();
  }

  function updateUpgradeUI() {
    $("#coin-upgrade").text(`Монеты (${coinMultiplier.toFixed(2)})`);
    $("#coin-upgrade-price").text(`Цена: ${coinUpgradePrice} монет`);
    $("#xp-upgrade").text(`Опыт (${xpMultiplier.toFixed(2)})`);
    $("#xp-upgrade-price").text(`Цена: ${xpUpgradePrice} опыта`);
    $("#gem-upgrade").text(`Кристаллы (${gemMultiplier.toFixed(2)})`);
    $("#click-delay-upgrade").text(`Задержка (${clickDelay})`);
    $("#click-delay-upgrade-price").text(`Цена: ${clickDelayUpgradePrice} монет и опыта`);
  }

  $("#flower").click(function() {
    if (!isClickEnabled) return;

    // Анимация цветка при нажатии.
    $(this).addClass('animate__animated animate__pulse');
    setTimeout(function() {
      $('#flower').removeClass('animate__animated animate__pulse');
    }, 1000);

    var randomValue = Math.random();
    if (randomValue < 0.45) {
      coin += coinMultiplier;
      updateValues();
    } else if (randomValue < 0.90) {
      xp += xpMultiplier;
      updateValues();
    } else {
      gem += gemMultiplier;
      updateValues();
    }

    isClickEnabled = false;
    setTimeout(function() {
      isClickEnabled = true;
    }, clickDelay);
  });

  $("#coin-upgrade-btn").click(function() {
    if (coin >= coinUpgradePrice) {
        if (coinMultiplier < 20) {
            coin -= coinUpgradePrice;
            coinMultiplier += 0.20;
            coinUpgradePrice += 15;
            updateValues();
        }
    }
  });

  $("#xp-upgrade-btn").click(function() {
    if (xp >= xpUpgradePrice) {
        if (xpMultiplier < 10) {
            xp -= xpUpgradePrice;
            xpMultiplier += 0.15;
            xpUpgradePrice += 10;
            updateValues();
        }
    }
  });
  
  $("#click-delay-upgrade-btn").click(function() {
    if (coin >= clickDelayUpgradePrice && xp >= clickDelayUpgradePrice) {
        if (clickDelay > 100) {
            coin -= clickDelayUpgradePrice;
            xp -= clickDelayUpgradePrice;
            clickDelay -= 100;
            clickDelayUpgradePrice += 15;
            updateValues();
        }
    }
  });

  $("#send").click(function() {
    if (coin >= 150 && xp >= 100) {
      send();
      //resetValues();
    } else {
      $('#result').text("Ошибка: Недостаточно монет, опыта или кристаллов. Необходимо: 150 монет и 100 опыта.");
    }
  });

  function send() {
    $.ajax({
      url: "items_core",
      type: "POST",
      data: {
        action: "group_add",
        coins: coin,
        xp: xp,
        gems: gem,
      },
      success: function(response) {
        $('#result').text(response);
      },
      error: function(xhr, status, error) {
        $('#result').text("Ошибка отправки: " + error);
      }
    });
  }

  function resetValues() {
    coin = 0;
    xp = 0;
    gem = 0;
    updateValues();
  }

  $(window).focus(function() {
    isWindowFocused = true;
  });

  $(window).blur(function() {
    isWindowFocused = false;
    resetValues();
  });

  // Перехват контекстного меню.

  $(document).on('contextmenu', function(e) {
    e.preventDefault();
    showContextMenu(e.clientX, e.clientY);
  });

  // Скрыть контекстное меню при клике вне него.
  $(document).on('click', function(e) {
    if (!$(e.target).closest('.context-menu').length) {
      hideContextMenu();
    }
  });

  function showContextMenu(x, y) {
    $('#custom-context-menu').css({
      display: 'block',
      left: x + 'px',
      top: y + 'px'
    });
  }

  function hideContextMenu() {
    $('#custom-context-menu').hide();
  }

  // Gеревод на 403 при комбинации клавиш Ctrl + Shift + I или F12.
  $(document).keydown(function(e) {
    if ((e.ctrlKey && e.shiftKey && e.which === 73) || e.which === 123) {
      location.href = './403';
    }
  });
});