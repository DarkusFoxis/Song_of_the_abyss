<?php 
require_once '../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo "Error connection.";
    exit;
}
$countResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM url");
$totalLinks = mysqli_fetch_assoc($countResult)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abyss Search</title>
    <link rel="icon" href="../img/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
    <script src="../js/jquery-3.7.1.min.js"></script>
    <style>
        .search-hero {
            padding: 1.5rem 0;
            text-align: center;
        }
        .search-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 20px;
            box-shadow: 0 0 50px rgba(147, 112, 219, 0.3);
            border: 1px solid rgba(229, 36, 255, 0.5);
        }
        .search-input {
            background: rgba(0, 0, 0, 0.8) !important;
            color: #fff !important;
            border: 2px solid #9370DB !important;
            padding: 12px 18px !important;
            font-size: 16px !important;
            border-radius: 50px !important;
            box-shadow: 0 0 20px rgba(147, 112, 219, 0.4) inset;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            border-color: rgba(229, 36, 255, 1) !important;
            box-shadow: 0 0 30px rgba(229, 36, 255, 0.5) inset !important;
        }
        .search-btn {
            padding: 12px 35px !important;
            border-radius: 50px !important;
            font-weight: bold;
            font-size: 16px;
            margin-top: 15px;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.9) 0%, rgba(29, 29, 29, 0.9) 100%) !important;
            border: 2px solid rgba(229, 36, 255, 1) !important;
        }
        .results-container {
            margin-top: 25px;
            text-align: left;
        }
        .result-card {
            background: rgba(30, 30, 30, 0.7);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative; /* Добавлено для правильного позиционирования бейджа */
        }
        .sponsor-badge {
            position: absolute;
            top: -10px;
            right: 10px;
            background: linear-gradient(to right, #ff8a00, #da1b60);
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 10;
        }
        .result-card.sponsor {
            border: 2px solid rgba(229, 36, 255, 1);
            background: rgba(40, 30, 60, 0.75);
        }
        .result-card:not(.sponsor) {
            border-left: 3px solid rgba(147, 112, 219, 0.8);
        }
        .result-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(147, 112, 219, 0.3);
        }
        .result-title {
            color: #BA55D3;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }
        .result-url {
            color: #9370DB;
            font-size: 0.85rem;
            word-break: break-all;
            margin-bottom: 5px;
        }
        .result-description {
            color: #FFE4E1;
            margin: 7px 0;
            font-size: 0.95rem;
        }
        .result-keywords {
            color: #FFA500;
            font-style: italic;
            font-size: 0.85rem;
            margin-bottom: 3px;
        }
        .result-date {
            color: #aaa;
            font-size: 0.8rem;
        }
        .no-results {
            text-align: center;
            padding: 25px;
            color: #FFE4E1;
            font-size: 1.1rem;
        }
        .search-stats {
            color: #BA55D3;
            text-align: center;
            margin: 15px 0;
            font-size: 1rem;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin: 20px 0 10px;
        }
        .pagination a {
            color: #BA55D3;
            padding: 6px 12px;
            text-decoration: none;
            border: 1px solid rgba(147, 112, 219, 0.3);
            margin: 0 3px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .pagination a:hover {
            background: rgba(147, 112, 219, 0.2);
        }
        .pagination a.active {
            background: rgba(147, 112, 219, 0.3);
            color: white;
        }
        #suggestions {
            position: absolute;
            background: rgba(30, 30, 30, 0.95);
            border: 1px solid #9370DB;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
            width: calc(100% - 40px);
            margin-top: 5px;
            border-radius: 5px;
        }
        #suggestions div {
            padding: 8px 15px;
            cursor: pointer;
            color: #FFE4E1;
        }
        #suggestions div:hover {
            background: rgba(147, 112, 219, 0.3);
        }
        mark {
            background-color: rgba(229, 36, 255, 0.3);
            color: white;
            padding: 0 2px;
            border-radius: 2px;
        }
        .result-title mark {
            background-color: rgba(229, 36, 255, 0.5);
            font-weight: bold;
        }
        .result-keywords mark {
            background-color: rgba(255, 165, 0, 0.3);
        }
    </style>
</head>
<body>
<div class="navbar">
    <a href="./main">Main</a>
    <a href="#">Total link: <? echo $totalLinks; ?></a>
    <a href="./add_link">Добавить ссылку</a>
</div>
<div class="content-main">
    <div class="search-hero">
        <div class="header">
            <h1>Abyss Search</h1>
            <h3>Ищи новые миры в нашей библиотеке...</h3>
        </div>
        <div class="search-container">
            <form id="searchForm" method="GET" action="">
                <div class="form-group" style="position:relative;">
                    <input type="text" name="q" class="form-control search-input" placeholder="Введите ваш запрос..." value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>" autocomplete="off" autofocus>
                    <div id="suggestions"></div>
                </div>
                <button type="submit" class="button search-btn">Поиск</button>
            </form>
            <div class="results-container">
                <?php
                function highlightSnippet($text, $keywords, $context = 60) {
                    if (empty($keywords)) return htmlspecialchars($text);
                    $text = strip_tags($text);
                    $word = preg_quote($keywords[0], '/');
                    if (!preg_match("/($word)/iu", $text, $m, PREG_OFFSET_CAPTURE)) {
                        return htmlspecialchars(mb_substr($text, 0, $context * 2)) . '…';
                    }
                    $pos = $m[1][1];
                    $start = max(0, $pos - $context);
                    $snippet = mb_substr($text, $start, $context * 2);
                    $snippet = preg_replace("/($word)/iu", '<mark>$1</mark>', $snippet);
                    return ($start > 0 ? '…' : '') . $snippet . '…';
                }
                function highlightKeywords($text, $keywords) {
                    foreach ($keywords as $keyword) {
                        $text = preg_replace("/($keyword)/iu", '<mark>$1</mark>', $text);
                    }
                    return $text;
                }
                if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
                    mysqli_set_charset($conn, "utf8mb4");
                    $searchQueryRaw = $_GET['q'];
                    $logStmt = mysqli_prepare($conn, "INSERT INTO `search_log` (`search`, `time`) VALUES (?, NOW())");
                    if ($logStmt) {
                        mysqli_stmt_bind_param($logStmt, "s", $searchQueryRaw);
                        mysqli_stmt_execute($logStmt);
                        mysqli_stmt_close($logStmt);
                    } else {
                       error_log("Ошибка подготовки запроса логирования: " . mysqli_error($conn));
                    }
                    $searchQuery = trim($_GET['q']);
                    $keywords = preg_split('/\s+/', $searchQuery);
                    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                    $resultsPerPage = 20;
                    $offset = ($page - 1) * $resultsPerPage;
                    
                    $whereConditions = [];
                    $relevanceParts = [];
                    $params = [];
                    $types = '';

                    foreach ($keywords as $keyword) {
                        $whereConditions[] = "(title LIKE ? OR description LIKE ? OR keywords LIKE ?)";

                        $relevanceParts[] = "(IF(title LIKE ?, 5, 0) + IF(keywords LIKE ?, 3, 0) + IF(description LIKE ?, 1, 0))";
                        $params = array_merge($params, ["%$keyword%", "%$keyword%", "%$keyword%"]);
                        $types .= 'sss';
                    }

                    $whereClause = implode(' OR ', $whereConditions);

                    // Объединяем части релевантности в одно выражение.
                    $baseRelevanceExpression = implode(' + ', $relevanceParts);

                    //коэффициент кликов: 10.
                    //бонус за спонсорство: 50.
                    //LOG10 для смягчения влияния большого числа кликов.
                    //IFNULL(clicking, 0) на случай, если в БД есть NULL.
                    $fullRelevanceExpression = "($baseRelevanceExpression + IF(in_top = 1, 50, 0) + (10 * LOG10(IFNULL(clicking, 0) + 1))) AS relevance";

                    $countSql = "SELECT COUNT(*) AS total FROM url WHERE $whereClause";
                    $stmtCount = mysqli_prepare($conn, $countSql);
                    if ($stmtCount) {
                        if (!empty($params)) {
                            mysqli_stmt_bind_param($stmtCount, str_repeat('s', count($params)), ...$params);
                        }
                        mysqli_stmt_execute($stmtCount);
                        $countResult = mysqli_stmt_get_result($stmtCount);
                        $totalCount = mysqli_fetch_assoc($countResult)['total'];
                        mysqli_stmt_close($stmtCount);
                    } else {
                        error_log("Ошибка подготовки запроса подсчета: " . mysqli_error($conn));
                        $totalCount = 0;
                    }

                    $totalPages = ceil($totalCount / $resultsPerPage);

                    $sql = "SELECT *, in_top, $fullRelevanceExpression FROM url WHERE $whereClause ORDER BY relevance DESC, date_add DESC LIMIT ? OFFSET ?";

                    $stmt = mysqli_prepare($conn, $sql);
                    if ($stmt) {
                        $allParamsForQuery = array_merge($params, $params);
                        $allParamsForQuery[] = $resultsPerPage;
                        $allParamsForQuery[] = $offset;
                        $finalTypes = str_repeat($types, 2) . 'ii';

                        mysqli_stmt_bind_param($stmt, $finalTypes, ...$allParamsForQuery);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        $numResults = mysqli_num_rows($result);

                        echo '<div class="search-stats">Найдено результатов: ' . $totalCount . '</div>';

                        if ($numResults > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $cardClass = 'result-card';
                                if ($row['in_top']) {
                                    $cardClass .= ' sponsor';
                                }
                                echo '<div class="' . $cardClass . '">';
                                if ($row['in_top']) {
                                    echo '<span class="sponsor-badge">Спонсорская</span>';
                                }
                                echo '<a href="./system/redirect?to='.htmlspecialchars($row['id']).'" class="link" target="_blank"><h3 class="result-title">'.highlightKeywords(htmlspecialchars($row['title']), $keywords).'</h3></a>';
                                echo '<a href="./system/redirect?to='.htmlspecialchars($row['id']).'" class="link" target="_blank"><div class="result-url">'.htmlspecialchars($row['url']).'</div></a>';
                                // echo '<div class="result-clicks">Кликов: ' . (int)$row['clicking'] . '</div>';
                                echo '<div class="result-description">'.highlightSnippet($row['description'], $keywords).'</div>';
                                if (!empty($row['keywords'])) {
                                    echo '<div class="result-keywords">Теги: '.highlightKeywords(htmlspecialchars($row['keywords']), $keywords).'</div>';
                                }
                                echo '<div class="result-date">Добавлено: ' . date('d.m.Y H:i', strtotime($row['date_add'])) . '</div>';
                                echo '</div>';
                            }
                            if ($totalPages > 1) {
                                echo '<div class="pagination">';
                                if ($page > 1) {
                                    echo '<a href="javascript:loadPage('.($page-1).')">← Назад</a>';
                                }
                                if ($page < $totalPages) {
                                    echo '<a href="javascript:loadPage('.($page+1).')">Вперёд →</a>';
                                }
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="no-results">';
                            echo '<h3>Ничего не найдено</h3>';
                            echo '<p>Попробуйте изменить запрос или <a href="./add_link" class="link">добавьте новую ссылку</a> в нашу базу.</p>';
                            echo '</div>';
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                         error_log("Ошибка подготовки основного запроса: " . mysqli_error($conn));
                        echo '<div class="no-results">Ошибка выполнения запроса</div>';
                    }
                } else {
                    echo '<div class="no-results">';
                    echo '<h3>Добро пожаловать в вашу бездну знаний!</h3>';
                    echo '<p>Введите поисковый запрос, чтобы найти миры или <a href="./add_link" class="link">добавьте свои миры</a>.</p>';
                    echo '<p>Сейчас в базе: ' . $totalLinks . ' ссылок</p>';
                    echo '</div>';
                }
                mysqli_close($conn);
                ?>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    const searchInput = $('.search-input');
    if (searchInput.length && searchInput.val()) {
        searchInput[0].setSelectionRange(searchInput.val().length, searchInput.val().length);
    }
    searchInput.focus();
    function loadResults(query, page) {
        $.ajax({
            url: 'search',
            method: 'GET',
            data: { q: query, page: page },
            success: function(data) {
                const $temp = $('<div>').html(data);
                const resultsHtml = $temp.find('.results-container').html();
                $('.results-container').html(resultsHtml);
                const params = new URLSearchParams(window.location.search);
                let newUrl = 'search?q=' + encodeURIComponent(query);
                if (page > 1) newUrl += '&page=' + page;
                history.pushState({ q: query, page: page }, '', newUrl);
            }
        });
    }
    $('#searchForm').submit(function(e) {
        e.preventDefault();
        const query = $('.search-input').val().trim();
        if (query) {
            loadResults(query, 1);
        }
    });
    window.loadPage = function(page) {
        const query = $('.search-input').val().trim();
        loadResults(query, page);
    };
    let suggestTimer;
    $('.search-input').on('input', function() {
        clearTimeout(suggestTimer);
        const input = $(this).val().trim();
        if (input.length < 2) {
            $('#suggestions').empty().hide();
            return;
        }
        suggestTimer = setTimeout(function() {
            $.get('./system/suggest', { term: input }, function(data) {
                const $suggestions = $('#suggestions');
                $suggestions.empty();
                if (data.length > 0) {
                    $.each(data, function(i, word) {
                        $('<div>').text(word).appendTo($suggestions);
                    });
                    $suggestions.show();
                } else {
                    $suggestions.hide();
                }
            }, 'json');
        }, 300);
    });
    $(document).on('click', '#suggestions div', function() {
        $('.search-input').val($(this).text());
        $('#suggestions').hide();
        $('#searchForm').submit();
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#suggestions').length && 
            !$(e.target).is('.search-input')) {
            $('#suggestions').hide();
        }
    });
    window.addEventListener('popstate', function() {
        const params = new URLSearchParams(window.location.search);
        const query = params.get('q') || '';
        const page = parseInt(params.get('page')) || 1;
        $('.search-input').val(query);
        loadResults(query, page);
    });
});
</script>
</body>
</html>
<?php 
session_write_close();
?>