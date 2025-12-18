<!DOCTYPE html>
<html>
<head>
    <title>Рассказы бездны</title>
    <link rel="icon" href="../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel = "stylesheet" href = "../style/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./css/main.css">
</head>
<body>
<div class="navbar">
    <a href="../legend">Back</a>
    <a href="./redact">Redactor</a>
</div>
<div class="content-main">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="header">
                    <h3>Рассказы бездны</h3>
                </div>

                <div class="stories-container">
                    <?php
                    require_once '../template/conn.php';
                    $conn = mysqli_connect($host, $log, $password_sql, $database);
                    if (!$conn) {
                        echo "Ошибка соединения: " . mysqli_connect_error();
                        exit;
                    }
                    $sql = "SELECT s.*, u.username
                            FROM story s
                            JOIN users u ON s.id_user = u.id";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $id = $row['id'];
                            $title = htmlspecialchars($row['title']);
                            $author = htmlspecialchars($row['username']);
                            $cover = $row['icon'] ? htmlspecialchars($row['icon']) : '../img/default-cover.jpg';
                            $description = htmlspecialchars($row['description']);
                            $ageLimit = (int)$row['age_limit'];
                            $ageClass = "age-$ageLimit";
                            $ageText = "Без ограничений";
                            if ($ageLimit === 18) $ageText = "18+";
                            if ($ageLimit === 16) $ageText = "16+";
                            if ($ageLimit === 12) $ageText = "12+";
                            echo "
                            <div class='story-card' data-id='$id'>
                                <img src='$cover' alt='Обложка: $title' class='story-cover'>
                                <div class='story-info'>
                                    <div class='story-title'>$title</div>
                                    <div class='story-author'>
                                        <span>$author</span>
                                    </div>
                                </div>
                            </div>
                            <div class='story-modal' id='modal-$id'>
                                <div class='modal-content'>
                                    <div class='modal-header'>
                                        <h2 class='modal-title'>$title</h2>
                                        <div class='modal-close'>&times;</div>
                                    </div>
                                    <div class='modal-body'>
                                        <img src='$cover' alt='Обложка: $title' class='modal-cover'>
                                        <div class='modal-details'>
                                            <div class='modal-author'>
                                                <span class='modal-author-name'>Автор: $author</span>
                                            </div>
                                            <div class='modal-description'>
                                                $description
                                            </div>
                                            <div class='modal-age $ageClass'>$ageText</div><br>
                                            <a href='story?id=$id' class='link'>Читать</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            ";
                        }
                    } else {
                        echo "<div class='story-card'><p>Пока нет опубликованных рассказов. Будьте первым!</p></div>";
                    }
                    $conn->close();
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../js/jquery-3.7.1.min.js"></script>
<script>
    $(document).ready(function() {
        $('.story-card').on('click', function() {
            const storyId = $(this).data('id');
            const modal = $('#modal-' + storyId);
            if (modal.length) {
                modal.fadeIn(300);
                $('body').css('overflow', 'hidden');
            }
        });
        $('.modal-close').on('click', function() {
            const modal = $(this).closest('.story-modal');
            modal.fadeOut(300);
            $('body').css('overflow', 'auto');
        });
        $(document).on('click', function(event) {
            if ($(event.target).hasClass('story-modal')) {
                $(event.target).fadeOut(300);
                $('body').css('overflow', 'auto');
            }
        });
        $(document).on('keydown', function(event) {
            if (event.key === "Escape") {
                $('.story-modal:visible').fadeOut(300);
                $('body').css('overflow', 'auto');
            }
        });
    });
</script>
</body>
</html>