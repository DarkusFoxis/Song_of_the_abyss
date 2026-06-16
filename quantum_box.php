<!DOCTYPE html>
<html>
  <head>
    <title>Ящик квантовой запутанности</title>
    <link rel = "icon" href = "./img/icon.png">
	<link rel = "stylesheet" href = "./style/style.css">
	<link rel="stylesheet" href="./style/bootstrap.min.css">
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="description" content="Открой ящик, и перенесись туда, куда твой путь идёт..."/>
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
          margin: 0;
          padding: 0;
          height: 100vh;
        }
        
        #background {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: black;
          z-index: -1;
        }
        
        #background::before {
          content: "";
          position: absolute;
          width: 100%;
          height: 100%;
          background-image: radial-gradient(white 1px, transparent 1px);
          background-size: 40px 40px;
          opacity: 0.2;
        }
        
        #openButton {
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          padding: 10px 20px;
          font-size: 16px;
          background-color: #333;
          border-radius: 10px;
          color: #fff;
          border: none;
          cursor: pointer;
        }
        
        #popupContainer {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.5);
          z-index: 1;
        }
        
        .center{
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        #popup {
          background-color: rgba(0, 0, 0, 0.1);
          color: #fff;
          padding: 20px;
          border-radius: 5px;
          text-align: center;
          display: flex;
          justify-content: center;
          align-items: center;
        }
        #pulsingText {
          color: #fff;
          animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
          0% {
            transform: scale(1);
          }
          50% {
            transform: scale(1.2);
          }
          100% {
            transform: scale(1);
          }
        }
        .hidden {
          display: none;
        }
        .loader {
		  position: relative;
		  display: inline-block;
		  width: 50px;
		  height: 50px;
		}

		.loader:before , .loader:after{
		  content: '';
		  border-radius: 50%;
		  position: absolute;
		  inset: 0;
		  box-shadow: 0 0 10px 2px rgba(0, 0, 0, 0.3) inset;
		}
		.loader:after {
		  box-shadow: 0 2px 0 #6A5ACD inset;
		  animation: rotate 1s linear infinite;
		}

		@keyframes rotate {
		  0% {  transform: rotate(0)}
		  100% { transform: rotate(360deg)}
		}
    </style>
  </head>
  <body>
    <div id="background"></div>
    <button id="openButton">Открыть ящик</button>
    <div id="popupContainer" class="hidden center">
      <div id="popup"><!--<span id="pulsingText">Вас затягивает в...</span><br>--><span class="loader"></span></div>
    </div>
    <script src="./js/jquery-3.7.1.min.js"></script>
    <script>
        const openButton = document.getElementById('openButton');
        const popupContainer = document.getElementById('popupContainer');
        const popup = document.getElementById('popup');
        
        openButton.addEventListener('click', () => {
            <?php
                session_start();
require_once __DIR__ . '/./template/auth.php';
auth_sync_session_from_token();
                if(isset($_SESSION['user'])) {
                    echo "$.ajax({";
                    echo "url: 'achievement_core',";
                    echo "type: 'POST',";
                    echo "data: { achievement: 'quant' },";
                    echo "error: function(xhr, status, error) {";
                    echo "console.error('Ошибка AJAX: ' + error);";
                    echo "}";
                    echo "});";
                }
            ?>
            popupContainer.classList.remove('hidden');
            $.ajax({
                url: 'redirect_core',
                type: 'POST',
                success: function(response) {
                    let timer = setTimeout(() => {
                        popupContainer.classList.add('hidden');
                        window.location.href = response;
                    }, 2500);
                    $(window).blur(function() {
                        popupContainer.classList.add('hidden');
                        clearTimeout(timer);
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Ошибка AJAX: ' + error);
                }
            });
        });

        $(document).keydown(function(e) {
            if ((e.ctrlKey && e.shiftKey && e.which === 73) || e.which === 123) {
                e.preventDefault();
                location.href = './403';
            }
        });
        $(document).on('contextmenu', function(e) {
          e.preventDefault();
          return false;
        });
    </script>
  </body>
</html>


