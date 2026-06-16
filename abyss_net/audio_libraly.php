<?php
session_start();
require_once __DIR__ . '/../template/auth.php';
auth_sync_session_from_token();
require_once '../template/conn.php';

$conn = mysqli_connect($host, $log, $password_sql, $database);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

//Генерирует HTML для карточки трека.
function generateTrackCard($row) {
    $avatar = htmlspecialchars($row['avatar']);
    $username = htmlspecialchars($row['username']);
    $trackName = htmlspecialchars($row['name']);
    $authorName = htmlspecialchars($row['author_name']);
    $audioId = htmlspecialchars($row['audio_id']);
    $audioPath = htmlspecialchars($row['path']);
    $coverPath = htmlspecialchars($row['cover_patch']);
    
    $dataAttrs = sprintf('data-src="./media/audio/%s" data-title="%s" data-artist="%s" data-uploader="%s" data-cover="./icon/%s"',$audioPath, $trackName, $authorName, $username, $coverPath);
    return <<<HTML
    <div class="col-12 col-md-6 mb-4 track_class_data">
        <div class="track-card" data-search-title="{$trackName}" data-search-artist="{$authorName}" data-search-uploader="{$username}">
            <img src="../profile/avatars/{$avatar}" alt="Аватар {$username}" class="track-card_avatar">
            <div class="track-card_info">
                <div class="track-title">{$trackName}</div>
                <div class="track-uploader">Загрузил: {$username}</div>
                <div class="track-artist">Исполнитель: {$authorName}</div>
            </div>
            <div class="track-buttons">
                <button title="Играть сейчас" class="play-btn" {$dataAttrs}>
                    <img src="./icon/play.svg" alt="Играть сейчас">
                </button>
                <button class="queue-btn" title="Добавить в очередь" {$dataAttrs}>
                    <img src="./icon/add_queue.svg" alt="Добавить в очередь">
                </button>
                <button class="share-btn" title="Поделиться" data-id="{$audioId}">
                    <img src="./icon/share.svg" alt="Поделиться">
                </button>
            </div>
        </div>
    </div>
HTML;
}

//Получает и возвращает список треков.
function getTracksList($conn, $sortOrder = 'new_to_old') {
    $orderBy = '';
    switch ($sortOrder) {
        case 'old_to_new':
            $orderBy = 'ORDER BY a.data_upload ASC';
            break;
        case 'new_to_old':
            $orderBy = 'ORDER BY a.data_upload DESC';
            break;
        case 'random':
            $orderBy = 'ORDER BY RAND()';
            break;
        default:
            $orderBy = 'ORDER BY a.data_upload DESC';
    }

    $sql = "SELECT a.*, u.username, u.avatar FROM audio a JOIN users u ON a.user_id = u.id $orderBy";

    $result = $conn->query($sql);
    $trackCards = '';

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $trackCards .= generateTrackCard($row);
        }
    } else {
        $trackCards = '<div class="col-12"><p>Музыки пока нет. :(</p></div>';
    }
    return $trackCards;
}

if (isset($_GET['refreshTracks']) && $_GET['refreshTracks'] === 'true') {
    $sortOrder = isset($_GET['sortOrder']) ? $_GET['sortOrder'] : 'new_to_old';
    echo getTracksList($conn, $sortOrder);
    exit();
}

setlocale(LC_TIME, 'ru_RU.UTF-8');

$sortOrder = isset($_GET['sort']) ? $_GET['sort'] : 'new_to_old';
$trackCards = getTracksList($conn, $sortOrder);

$currentTrack = null;
if (!empty($_GET['track_id'])) {
    $trackId = mysqli_real_escape_string($conn, $_GET['track_id']);
    $sql = "SELECT a.*, u.username, u.avatar FROM audio a JOIN users u ON a.user_id = u.id WHERE a.audio_id = '$trackId'";
    $result = $conn->query($sql);
    $currentTrack = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="ru" prefix="og:http://ogp.me/ns#">
<head>
    <title>Музыкальная библиотека</title>
    <link rel="icon" href="../img/icon.png">
	<link rel="stylesheet" href="../style/bootstrap.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="./style/audioLibraly1.4.1.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="./js/dragula.min.js"></script>
    <script src="./js/audioPlayer1.5.js"></script>
    <script src="./js/search1.js"></script>
    <script src="./js/update1_2.js"></script>
    <style>
        .track-card { height: 100%; transition: transform 0.2s, box-shadow 0.2s; }
        .track-card:hover {transform: translateY(-3px); box-shadow: 0 8px 25px rgba(153, 102, 204, 0.3);}
        .sort-controls {display: flex; align-items: center; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;}
        .sort-controls label {color: #aaa; font-size: 14px; font-weight: 500;}
        .sort-controls select {background: rgba(30, 30, 50, 0.9); border: 1px solid rgba(100, 150, 255, 0.3); color: #fff; padding: 8px 15px; border-radius: 6px; font-size: 14px; cursor: pointer; outline: none; transition: all 0.3s;}
        .sort-controls select:hover {border-color: rgba(100, 150, 255, 0.6); box-shadow: 0 0 8px rgba(100, 150, 255, 0.2);}
        .sort-controls select:focus {border-color: rgba(100, 150, 255, 0.8); box-shadow: 0 0 15px rgba(100, 150, 255, 0.4);}
        .sort-badge {background: linear-gradient(135deg, rgba(153, 102, 204, 0.3), rgba(186, 20, 126, 0.3)); color: #d48aff; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-left: auto; border: 1px solid rgba(212, 134, 255, 0.3); text-shadow: 0 0 10px rgba(212, 134, 255, 0.5);}
        .header {margin-bottom: 20px; padding: 20px; background: rgba(30, 30, 50, 0.5); border-radius: 15px; border: 1px solid rgba(153, 102, 204, 0.2);}
        .header-controls {display: flex; align-items: center; gap: 15px; flex-wrap: wrap;}
        #title_site {background: linear-gradient(135deg, #9966cc, #ba147e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700;}
        #trackSearch {flex: 1; min-width: 100px; background: rgba(30, 30, 50, 0.9); border: 1px solid rgba(153, 102, 204, 0.3); color: #fff; padding: 10px 15px; border-radius: 8px; font-size: 14px; outline: none; transition: all 0.3s;}
        #trackSearch:focus {border-color: rgba(153, 102, 204, 0.6); box-shadow: 0 0 15px rgba(153, 102, 204, 0.3);}
        #trackSearch::placeholder {color: #666;}
        #tracks-container {transition: opacity 0.3s;}
    </style>
</head>
<body>
    <nav class="navbar navbar-desktop">
        <a href="main">Back</a>
        <a href="#" class="toggleUploadFormBtnNavbar">Upload</a>
        <a href="#" class="setting">Настройки</a>
        <a href="#" class="openQueueModalBtn">Очередь</a>
        <a href="#" class="refreshTracksBtn">Update list</a>
    </nav>
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
    <main class="content-main">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="header">
                        <div class="header-controls">
                            <h2 id="title_site" style="margin: 0;">Музыкальная библиотека</h2>
                            <span class="sort-badge" id="sortBadge">Сначала новые</span>
                        </div>
                        
                        <div class="sort-controls" style="margin-top: 20px;">
                            <label for="sortOrder">Сортировка треков:</label>
                            <select id="sortOrder" onchange="changeSortOrder()">
                                <option value="new_to_old" <?= $sortOrder === 'new_to_old' ? 'selected' : '' ?>>От новых к старым</option>
                                <option value="old_to_new" <?= $sortOrder === 'old_to_new' ? 'selected' : '' ?>>От старых к новым</option>
                                <option value="random" <?= $sortOrder === 'random' ? 'selected' : '' ?>>Мне повезёт!</option>
                            </select>
                            <button onclick="refreshTracks()" style="background: linear-gradient(135deg, rgba(153, 102, 204, 0.3), rgba(186, 20, 126, 0.3)); border: 1px solid rgba(153, 102, 204, 0.4); color: #d48aff; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='linear-gradient(135deg, rgba(153, 102, 204, 0.5), rgba(186, 20, 126, 0.5))'" onmouseout="this.style.background='linear-gradient(135deg, rgba(153, 102, 204, 0.3), rgba(186, 20, 126, 0.3))'">Обновить</button>
                            <input type="text" id="trackSearch" placeholder="Поиск трека по названию..." style="margin-left: auto;">
                        </div>
                    </div>

                    <div class="row" id="tracks-container">
                        <?= $trackCards ?>
                    </div>
                    <div id="player-bar">
                        <span id="now-playing" title="Очередь пуста...">Очередь пуста...</span>
                        <div class="controls">
                            <button id="prev" title="Предыдущий трек"><img src="./icon/back.svg" alt="Back"></button>
                            <button id="play" title="Играть/Пауза"><img src="./icon/play.svg" alt="Play"></button>
                            <button id="stop" title="Остановить и очистить очередь"><img src="./icon/stop.svg" alt="Stop"></button>
                            <button id="repeat" title="Повтор трека"><img src="./icon/repeat.svg" alt="Repeat"></button>
                            <button id="next" title="Следующий трек"><img src="./icon/next.svg" alt="Next"></button>
                            <button id="openFullscreenBtn" title="Открыть плеер на весь экран"><img src="./icon/fullscreen.svg" alt="Full Screen"></button>
                        </div>
                        <div class="time_data">
                            <span id="currentTime">00:00</span>/<span id="totalTime">00:00</span>
                        </div>
                        <div class="progress-volume">
                            <input type="range" id="progress" min="0" value="0" title="Прогресс">
                            <img src="./icon/volume.svg" alt="Volume" class="volume-icon">
                            <input type="range" id="volume" min="0" max="1" step="0.01" value="1" title="Громкость">
                        </div>
                        <div id="add_text"></div>
                    </div>
                    
                    <audio id="audio"></audio>
                </div>
            </div>
        </div>
    </main>

    <div id="queueModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeQueueModalBtn">&times;</span>
            <h2>Очередь воспроизведения</h2>
            <ul id="queue-list"></ul>
            <button id="clearQueueBtn">Очистить очередь</button>
        </div>
    </div>

    <div id="visualizerSettingsModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="closeVisualizerSettings">&times;</span>
            <h2>Настройки визуализатора</h2>
            <label class="custom-checkbox"><input type="checkbox" id="toggle-visualizer">Включить визуализатор<span class="checkmark"></span></label>

            <label for="quality-select">Качество визуализации:</label>
            <select id="quality-select" class="form-control">
                <option value="low">Низкое (для слабых устройств)</option>
                <option value="medium">Среднее</option>
                <option value="high">Высокое</option>
            </select>

            <label class="custom-checkbox"><input type="checkbox" id="toggle-repeat-queue">Повторять очередь воспроизведения<span class="checkmark"></span></label>
            <p class="info-note">Изменения применяются немедленно. Выключение визуализатора не останавливает воспроизведение.</p>
            <button type="button" id="saveVisualizerSettings">Сохранить</button>
        </div>
    </div>

    <div id="fullscreen-player" class="fullscreen-player hidden">
        <div class="player-background" id="player-cover"></div>
        <div class="fullscreen-content">
            <span class="close-btn" id="closeFullscreenBtn">&times;</span>
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

    <div id="uploadIframeContainer" class="upload-iframe-container">
        <div class="upload-iframe-content">
            <button id="closeUploadIframeBtn" class="close-iframe-btn">&times;</button>
            <iframe id="uploadIframe" src="./system/core.php"></iframe>
        </div>
    </div>
    <script src="./js/menu.js"></script>
    <?php if ($currentTrack): ?>
        <script>
            $(document).ready(function() {
                const trackToPlay = './media/audio/<?= htmlspecialchars($currentTrack['path']) ?>';
                const trackTitle = '<?= htmlspecialchars($currentTrack['name']) ?>' || 'Track';
                const trackArtist = '<?= htmlspecialchars($currentTrack['author_name']) ?>';
                const trackUploader = '<?= htmlspecialchars($currentTrack['username']) ?>';
                const trackCover = './icon/<?= htmlspecialchars($currentTrack['cover_patch']) ?>';

                if (trackToPlay && typeof Player !== 'undefined') {
                    PlayerState.queue = [{src: trackToPlay, title: trackTitle, artist: trackArtist, uploader: trackUploader, cover: trackCover}];
                    Player.playTrack(0);
                }
            });
        </script>
    <?php endif; ?>
    
    <script>
        function changeSortOrder() {
            const sortOrder = document.getElementById('sortOrder').value;
            const sortBadge = document.getElementById('sortBadge');
            const tracksContainer = document.getElementById('tracks-container');

            const badgeTexts = {
                'new_to_old': 'Сначала новые',
                'old_to_new': 'Сначала старые',
                'random': 'Случайный порядок'
            };
            sortBadge.textContent = badgeTexts[sortOrder] || 'Сначала новые';

            tracksContainer.style.opacity = '0.5';

            $.ajax({
                url: 'audio_libraly',
                method: 'GET',
                data: {
                    refreshTracks: 'true',
                    sortOrder: sortOrder
                },
                success: function(response) {
                    tracksContainer.innerHTML = response;
                    tracksContainer.style.opacity = '1';

                    reattachTrackHandlers();
                },
                error: function() {
                    tracksContainer.style.opacity = '1';
                    console.error('Ошибка загрузки треков');
                }
            });
        }

        function refreshTracks() {
            const sortOrder = document.getElementById('sortOrder').value;
            const tracksContainer = document.getElementById('tracks-container');

            tracksContainer.style.opacity = '0.5';

            $.ajax({
                url: 'audio_libraly',
                method: 'GET',
                data: {
                    refreshTracks: 'true',
                    sortOrder: sortOrder
                },
                success: function(response) {
                    tracksContainer.innerHTML = response;
                    tracksContainer.style.opacity = '1';

                    reattachTrackHandlers();
                },
                error: function() {
                    tracksContainer.style.opacity = '1';
                    console.error('Ошибка загрузки треков');
                }
            });
        }

        function reattachTrackHandlers() {}

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

            closeUploadIframeBtn.on('click', closeUploadIframe);

            uploadIframeContainer.on('click', function(e) {
                if (e.target === this) closeUploadIframe();
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