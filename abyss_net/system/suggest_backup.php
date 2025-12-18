<?php
require_once '../../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);

if (!$conn) {
    echo json_encode([]);
    exit;
}

$term = isset($_GET['term']) ? trim($_GET['term']) : '';
if (strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

$suggestions = [];
$term = $term . '%';
$limit = 10;

$sql = "(
    SELECT DISTINCT word FROM (
        SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(title, ' ', n), ' ', -1) AS word
        FROM url, 
        (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) numbers
        WHERE n <= CHAR_LENGTH(title) - CHAR_LENGTH(REPLACE(title, ' ', '')) + 1
    ) title_words
    WHERE word LIKE ?
    LIMIT ?
) UNION (
    SELECT DISTINCT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(keywords, ',', n), ',', -1)) AS word
    FROM url, 
    (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) numbers
    WHERE n <= CHAR_LENGTH(keywords) - CHAR_LENGTH(REPLACE(keywords, ',', '')) + 1
        AND TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(keywords, ',', n), ',', -1)) LIKE ?
    LIMIT ?
) LIMIT ?";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    $likeTerm = $term;
    $limitParam = $limit;
    mysqli_stmt_bind_param($stmt, 'sisii', $likeTerm, $limitParam, $likeTerm, $limitParam, $limitParam);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['word'])) {
            $suggestions[] = $row['word'];
        }
    }
    mysqli_stmt_close($stmt);
}

header('Content-Type: application/json');
echo json_encode(array_slice(array_unique($suggestions), 0, $limit));

mysqli_close($conn);