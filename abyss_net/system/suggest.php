<?php
require_once '../../template/conn.php';

$conn = mysqli_connect($host, $log, $password_sql, $database);

if (!$conn) {
    error_log("Ошибка подключения к базе данных: " . mysqli_connect_error());
    echo json_encode([]);
    exit;
}

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

$suggestions = [];
$searchTerm = $term . '%';
$limit = 10;
$totalLimit = $limit;

//SQL-запрос для извлечения слов из title, keywords и search_log.
//Используется UNION для объединения результатов.
//Если нужно больше слов, в строках CROSS JOIN ( увеличиваем кол-во SELECT n UNION ALL.
$sql = "(SELECT DISTINCT word, 0 as cnt FROM (
    SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(title, ' ', n.n), ' ', -1) AS word
    FROM url
    CROSS JOIN (
        SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
    ) n
    WHERE CHAR_LENGTH(title) > 0 AND n.n <= CHAR_LENGTH(title) - CHAR_LENGTH(REPLACE(title, ' ', '')) + 1
) title_words
WHERE word LIKE ?
LIMIT ?
)
UNION
(SELECT DISTINCT word, 0 as cnt FROM (
    SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(keywords, ',', n.n), ',', -1)) AS word
    FROM url
    CROSS JOIN (
        SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
        ) n
        WHERE CHAR_LENGTH(keywords) > 0 AND n.n <= CHAR_LENGTH(keywords) - CHAR_LENGTH(REPLACE(keywords, ',', '')) + 1
) keyword_words
WHERE word LIKE ?
LIMIT ?
)
UNION
(SELECT word, COUNT(*) as cnt FROM (
    SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(search_log.search, ' ', n.n), ' ', -1) AS word
    FROM search_log
    CROSS JOIN (
        SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
    ) n
    WHERE CHAR_LENGTH(search_log.search) > 0 AND n.n <= CHAR_LENGTH(search_log.search) - CHAR_LENGTH(REPLACE(search_log.search, ' ', '')) + 1
) log_words
WHERE word LIKE ?
GROUP BY word
HAVING cnt > 1
)
ORDER BY cnt DESC, word ASC
LIMIT ?";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'sssssi', $searchTerm, $limit, $searchTerm, $limit, $searchTerm, $totalLimit);

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            // Проверка, что слово не пустое (на всякий случай, вдруг ёбнет).
            if (!empty($row['word'])) {
                if (!in_array($row['word'], $suggestions))
                $suggestions[] = $row['word'];
            }
        }
    } else {
        error_log("Ошибка выполнения запроса: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Ошибка подготовки запроса: " . mysqli_error($conn));
}

header('Content-Type: application/json; charset=utf-8');

echo json_encode(array_slice($suggestions, 0, $limit), JSON_UNESCAPED_UNICODE);

mysqli_close($conn);