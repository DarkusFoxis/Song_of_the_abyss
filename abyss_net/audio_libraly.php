<?php
session_start();
require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
if (isset($_GET['refreshTracks']) && $_GET['refreshTracks'] == 'true') {
    $sql = "SELECT a.*, u.username, u.avatar 
            FROM audio a 
            JOIN users u ON a.user_id = u.id";
    $result = $conn->query($sql);
    $track_cards = '';
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $track_cards .= '
            <div class="col-12 col-md-6 mb-4 track_class_data"> 
            <div class="track-card">
            <img src="../profile/avatars/' . $row['avatar'] . '" alt="Аватар ' . $row['username'] . '" class="track-card_avatar">
            <div class="track-card_info">
            <div class="track-title">' . $row['name'] . '</div>
            <div class="track-uploader">Загрузил: ' . $row['username'] . '</div>
            <div class="track-artist">Исполнитель: ' . $row['author_name'] . '</div>
            </div>
            <div class="track-buttons">
            <button title="Играть сейчас" class="play-btn" data-src="./media/audio/' . $row['path'] . '" data-title="' . $row['name'] . '" data-artist="' . $row['author_name'] . '", data-uploader="' . $row['username'] . '", data-cover="./icon/' . $row['cover_patch'] . '"><img src="./icon/play.svg" alt="Играть сейчас"></button>
            <button class="queue-btn" title="Добавить в очередь" data-src="./media/audio/' . $row['path'] . '" data-title="' . $row['name'] . '" data-artist="' . $row['author_name'] . '", data-uploader="' . $row['username'] . '", data-cover="./icon/' . $row['cover_patch'] . '"><img src="./icon/add_queue.svg" alt="Добавить в очередь"></button>
            <button class="share-btn" title="Поделиться" data-id="' . $row['audio_id'] . '"><img src="./icon/share.svg" alt="Поделиться"></button>
            </div>
            </div>
            </div>';
        }
    } else {
        $track_cards = '<p>Музыки пока нет. :(</p>';
    }
    echo $track_cards;
    exit();
} else {
    $sql = "SELECT a.*, u.username, u.avatar 
            FROM audio a 
            JOIN users u ON a.user_id = u.id";
    $result = $conn->query($sql);
    setlocale(LC_TIME, 'ru_RU.UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ru" prefix="og:http://ogp.me/ns#">
<head>
    <title>Музыкальная библиотека</title>
    <link rel = "icon" href = "../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel = "stylesheet" href = "../style/style.css">
    <link rel = "stylesheet" href = "./style/audioLibraly1.2.4.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="./js/dragula.min.js"></script>
    <style>
    .track-card {
        height: 100%;
    }
    
    @media (max-width: 767px) {
        .col-md-6 {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="main">Back</a>
        <a href="#" class="toggleUploadFormBtnNavbar">Upload</a>
        <a href="#" class="setting">Настройки</a>
        <a href="#" class="openQueueModalBtn">Очередь</a>
        <a href="#" class="refreshTracksBtn">Update list</a>
    </div>
    <nav class="mobile-nav">
        <button class="nav-toggle" id="toggleNav">
            <img src="./icon/menu.svg" alt="Меню" width="24">
        </button>
        <div class="nav-panel" id="navPanel">
            <a href="main">Back</a>
            <a href="#" class="toggleUploadFormBtnNavbar">Upload</a>
            <a href="#" class="setting">Настройки</a>
            <a href="#" class="openQueueModalBtn">Очередь</a>
            <a href="#" class="refreshTracksBtn">Update list</a>
        </div>
    </nav>
    <div class="content-main">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="header">
                            <h2 id="title_site">Музыкальная библиотека</h2>
                            <input type="text" id="trackSearch" placeholder="Поиск трека по названию..." style="margin-bottom: 10px;">
                            <p></p>
						</div>
						 <div class="row" id="tracks-container">
						<?php if ($result->num_rows > 0) : ?>
                            <?php while ($row = $result->fetch_assoc()) : ?>
                            <div class="col-12 col-md-6 mb-4 track_class_data">
                                <div class="track-card">
                                    <img src="../profile/avatars/<?php echo $row['avatar']; ?>" alt="Аватар <?php echo $row['username']; ?>" class="track-card_avatar"> <div class="track-card_info">
                                        <div class="track-title"><?php echo $row['name']; ?></div> <div class="track-uploader">Загрузил: <?php echo $row['username']; ?></div> <div class="track-artist">Исполнитель: <?php echo $row['author_name']; ?></div> </div>
                                    <div class="track-buttons">
                                        <button title="Играть сейчас" class="play-btn" data-src="./media/audio/<?php echo $row['path']; ?>" data-title="<?php echo $row['name']; ?>" data-artist="<?php echo $row['author_name']; ?>", data-uploader="<?php echo $row['username']; ?>", data-cover="./icon/<?php echo $row['cover_patch']; ?>"><img src="./icon/play.svg" alt="Играть сейчас"></button> <button class="queue-btn" title="Добавить в очередь" data-src="./media/audio/<?php echo $row['path']; ?>" data-title="<?php echo $row['name']; ?>" data-artist="<?php echo $row['author_name']; ?>", data-uploader="<?php echo $row['username']; ?>", data-cover="./icon/<?php echo $row['cover_patch']; ?>"><img src="./icon/add_queue.svg" alt="Добавить в очередь"></button> <button class="share-btn" title="Поделиться" data-id="<?php echo $row['audio_id']; ?>"><img src="./icon/share.svg" alt="Поделиться"></button>
                                    </div>
                                </div>
                            </div>
                             <?php endwhile; ?>
                        <?php else : ?>
                        <div class="col-12">
                            <p>Музыки пока нет. :(</p>
                        </div>
                        <?php endif; ?>
                        </div>
                        <div id="player-bar">
                            <span id="now-playing" title="Очередь пуста...">Очередь пуста...</span>
                            <div class="controls">
                                <button id="prev" title="Предыдущий трек"><img src="./icon/back.svg" alt="Back"></button><button id="play" title="Играть/Пауза"><img src="./icon/play.svg" alt="Play"></button><button id="stop" title="Остановить и очистить очередь"><img src="./icon/stop.svg" alt="Stop"></button><button id="repeat" title="Повтор трека"><img src="./icon/repeat.svg" alt="Repeat"></button><button id="next" title="Следующий трек"><img src="./icon/next.svg" alt="Next"></button>
                                <button id="openFullscreenBtn" title="Открыть плеер на весь экран"><img src="./icon/fullscreen.svg" alt="Full Screen"></button>
                            </div>
                            <div class="time_data"><span id="currentTime">00:00</span>/<span id="totalTime">00:00</span></div> <div class="progress-volume">
                                <input type="range" id="progress" min="0" value="0" title="Прогресс"><img src="./icon/volume.svg" alt="Volume" style="width: 30px; height: 30px; background-color: #9966cc; padding: 3px; border-radius: 5px; margin-right: 3px;"><input type="range" id="volume" min="0" max="1" step="0.01" value="1" title="Громкость">
                            </div>
                            <div id="add_text"></div>
                        </div>
                        <audio id="audio"></audio> </div>
                </div>
            </div>
        </div>
    <div id="queueModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeQueueModalBtn">&times;</span>
            <h2>Очередь воспроизведения</h2>
            <ul id="queue-list">
            </ul>
             <button id="clearQueueBtn" style="margin-top: 15px; padding: 8px 15px; background-color: #c0392b; color: white; border: none; border-radius: 5px; cursor: pointer;">Очистить очередь</button>
        </div>
    </div>
    <div class="modal" id="visualizerSettingsModal">
        <div class="modal-content">
            <span class="close" id="closeVisualizerSettings">&times;</span>
            <h2>Настройки визуализатора</h2>
            <label class="custom-checkbox">
                <input type="checkbox" id="toggle-visualizer">
                Включить визуализатор
                <span class="checkmark"></span>
            </label>

            <label for="quality-select" style="display: block; margin-bottom: 10px;">Качество визуализации:</label>
            <select id="quality-select" class="form-control">
                <option value="low">Низкое (для слабых устройств)</option>
                <option value="medium">Среднее</option>
                <option value="high">Высокое</option>
            </select>

            <label class="custom-checkbox">
                <input type="checkbox" id="toggle-repeat-queue">
                Повторять очередь воспроизведения
                <span class="checkmark"></span>
            </label>
            <p style="font-size: 0.85em; color: #888; margin-top: -10px; margin-bottom: 25px;">
                <em>Изменения применяются немедленно. Выключение визуализатора не останавливает воспроизведение.</em>
            </p>
            <br><br>
            <button type="button" id="saveVisualizerSettings">Сохранить</button>
        </div>
    </div>
    <div id="fullscreen-player" class="fullscreen-player hidden">
        <div class="player-background" id="player-cover"></div>
            <div class="fullscreen-content">
                <div class="close-btn" id="closeFullscreenBtn">×</div>
                <div class="track-info">
                    <h2 id="fs-title">Название трека</h2>
                    <p id="fs-artist">Исполнитель</p>
                    <p id="fs-uploader">Загрузил: ???</p>
                </div>
                <canvas id="fs-visualizer"></canvas>
                <div class="fs-controls">
                    <button id="fs-prev"><img src="./icon/back.svg" alt="Back"></button>
                    <button id="fs-play"><img src="./icon/play.svg" alt="Play"></button>
                    <button id="fs-next"><img src="./icon/next.svg" alt="Next"></button>
                    <button id="fs-repeat"><img src="./icon/repeat.svg" alt="Repeat"></button>
                </div>
            <div class="fs-progress">
                <span id="fs-current">00:00</span>
                <input type="range" id="fs-progress" min="0" value="0">
                <span id="fs-total">00:00</span>
            </div>
            <input type="range" id="fs-volume" min="0" max="1" step="0.01" value="1">
        </div>
    </div>
    <div id="uploadIframeContainer" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(5px);">
        <div style="background-color: #fff; padding: 0; border-radius: 10px; max-width: 550px; width: 90%; max-height: 90vh; overflow: hidden; position: relative; box-shadow: 0 5px 25px rgba(0,0,0,0.2); display: flex; flex-direction: column;">
            <button id="closeUploadIframeBtn" style="position: absolute; top: 10px; right: 15px; background: transparent; border: none; font-size: 24px; cursor: pointer; color: #888; z-index:10;">&times;</button>
            <iframe id="uploadIframe" src="./system/core.php" style="width: 100%; height: 500px; border: none; display: block; flex-grow: 1;"></iframe>
        </div>
    </div>
    <script src="./js/audioPlayer1.3.1.js"></script>
    <?php 
        if (!empty($_GET['track_id'])) {
            $track_URL = $_GET['track_id'];
            $sql = "SELECT a.*, u.username, u.avatar 
                    FROM audio a 
                    JOIN users u ON a.user_id = u.id
                    WHERE a.audio_id = '$track_URL'";
            $result = $conn->query($sql);
            $track = $result->fetch_assoc();
        }
    ?>
    <?php if (!empty($_GET['track_id'])) : ?>
        <script>
            $(document).ready(function() {
                const trackToPlay = './media/audio/<?php echo $track['path']; ?>';
                const trackTitle = '<?php echo $track['name']; ?>' || 'Track';
                const trackArtist = '<?php echo $track['author_name']; ?>';
                const trackUploader = '<?php echo $track['username']; ?>';
                const trackCover = './icon/<?php echo $track['cover_patch']; ?>';
                if (trackToPlay) {
                    queue = [{src: trackToPlay, title: trackTitle, artist: trackArtist, uploader: trackUploader, cover: trackCover}];
                    playTrack(0);
                }
            });
        </script>
    <?php endif; ?>
    <script src="./js/menu.js"></script>
    <script src="./js/search.js"></script>
    <script src="./js/update1_2.js"></script>
    <script>
        $(document).ready(function() {
            const uploadIframeContainer = $('#uploadIframeContainer');
            const uploadIframe = $('#uploadIframe');
            const toggleUploadFormBtnNavbar = $('.toggleUploadFormBtnNavbar');
            const closeUploadIframeBtn = $('#closeUploadIframeBtn');
            function openUploadIframe() {
                uploadIframe.attr('src', './system/core.php?' + new Date().getTime());
                uploadIframeContainer.css('display', 'flex');
                $('body').css('overflow', 'hidden');
            }
            function closeUploadIframe() {
                uploadIframeContainer.hide();
                $('body').css('overflow', '');
            }
            toggleUploadFormBtnNavbar.on('click', function(e) {
                e.preventDefault();
                openUploadIframe();
            });
            closeUploadIframeBtn.on('click', function() {
                closeUploadIframe();
            });
            $(uploadIframeContainer).on('click', function(e) {
                if (e.target === this) {
                    closeUploadIframe();
                }
            });
            $(document).on('keydown', function(e) {
                if (e.key === "Escape" && uploadIframeContainer.is(':visible')) {
                    closeUploadIframe();
                }
            });
        });
    </script>
</body>
</html>
<?php 
session_write_close();
?>