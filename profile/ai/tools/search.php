<?php
session_start();
require_once __DIR__ . '/../../../template/auth.php';
auth_sync_session_from_token();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Требуется авторизация']);
    exit;
}

require_once '../../../template/conn.php';
$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подключения к базе данных']);
    exit;
}

$query = $_GET['q'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$safe = isset($_GET['safe']) ? (bool)$_GET['safe'] : true;

if (empty(trim($query))) {
    echo json_encode(['error' => 'Пустой поисковый запрос']);
    exit;
}

$resultsPerPage = 5;
$offset = ($page - 1) * $resultsPerPage;

$keywords = preg_split('/\s+/', trim($query));
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

if ($safe) {
    $whereClause = "($whereClause) AND (nsfw = 0 OR nsfw IS NULL)";
}

$baseRelevanceExpression = implode(' + ', $relevanceParts);
$fullRelevanceExpression = "($baseRelevanceExpression + IF(in_top = 1, 50, 0) + (10 * LOG10(IFNULL(clicking, 0) + 1))) AS relevance";

$sql = "SELECT *, in_top, $fullRelevanceExpression FROM url WHERE $whereClause ORDER BY relevance DESC, date_add DESC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подготовки запроса: ' . mysqli_error($conn)]);
    exit;
}

$allParamsForQuery = array_merge($params, $params);
$allParamsForQuery[] = $resultsPerPage;
$allParamsForQuery[] = $offset;
$finalTypes = str_repeat($types, 2) . 'ii';

mysqli_stmt_bind_param($stmt, $finalTypes, ...$allParamsForQuery);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$results = [];
while ($row = mysqli_fetch_assoc($result)) {
    $results[] = [
        'title' => $row['title'],
        'url' => $row['url'],
        'description' => $row['description'],
        'keywords' => $row['keywords'] ?? '',
        'date_add' => date('d.m.Y H:i', strtotime($row['date_add'])),
        'is_sponsor' => (bool)$row['in_top']
    ];
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

if (empty($results)) {
    echo json_encode([
        'error' => 'Ничего не найдено',
        'query' => $query,
        'results' => []
    ]);
} else {
    echo json_encode([
        'query' => $query,
        'count' => count($results),
        'results' => $results
    ]);
}