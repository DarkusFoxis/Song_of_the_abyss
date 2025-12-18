<?php
session_start();

if (!isset($_SESSION['user'])) die(json_encode(['error' => 'Unauthorized']));

require_once '../../template/conn.php';

$conn = mysqli_connect($host, $log, $password_sql, $database);

if (!$conn) die(json_encode(['error' => 'DB connection failed']));

$login = $_SESSION['user'];
session_write_close();

$stmt = $conn->prepare("SELECT id FROM users WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) die(json_encode(['error' => 'User not found']));

$user_id = $user['id'];

$action = $_GET['action'] ?? '';

switch ($action) {
    
    case 'get_chats':
        $stmt = $conn->prepare("
            SELECT c.*, COUNT(m.id) as message_count
            FROM chats c
            LEFT JOIN messages m ON c.id = m.chat_id
            WHERE c.user_id = ?
            GROUP BY c.id
            ORDER BY c.updated_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $chats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['chats' => $chats]);
        break;
    
    case 'get_chat':
        $chat_id = intval($_GET['chat_id'] ?? 0);

        $stmt = $conn->prepare("SELECT * FROM chats WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $chat_id, $user_id);
        $stmt->execute();
        $chat = $stmt->get_result()->fetch_assoc();

        if (!$chat) die(json_encode(['error' => 'Chat not found']));

        $stmt = $conn->prepare("SELECT * FROM messages WHERE chat_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();
        $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $chat['messages'] = $messages;
        echo json_encode($chat);
        break;
    
    case 'create_chat':
        $input = json_decode(file_get_contents('php://input'), true);
        $title = $input['title'] ?? 'Новый чат';
        $character_id = $input['character_id'] ?? null;
        $model = $input['model'] ?? null;

        $stmt = $conn->prepare("INSERT INTO chats (user_id, title, character_id, model) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $title, $character_id, $model);
        $stmt->execute();

        echo json_encode(['chat_id' => $conn->insert_id, 'success' => true]);
        break;
    
    case 'create_chat_with_messages':
        $input = json_decode(file_get_contents('php://input'), true);
        $title = $input['title'] ?? 'Новый чат';
        $character_id = $input['character_id'] ?? null;
        $model = $input['model'] ?? null;
        $messages = $input['messages'] ?? [];

        if (count($messages) > 5000) {
            die(json_encode(['error' => 'Too many messages (max 5000)']));
        }

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("INSERT INTO chats (user_id, title, character_id, model) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $title, $character_id, $model);
            $stmt->execute();
            $chat_id = $conn->insert_id;

            if (count($messages) > 0) {
                $values = [];
                $params = [];
                $types = '';

                foreach ($messages as $msg) {
                    $values[] = "(?, ?, ?)";
                    $params[] = $chat_id;
                    $params[] = $msg['role'];
                    $params[] = $msg['content'];
                    $types .= 'iss';
                }

                $sql = "INSERT INTO messages (chat_id, role, content) VALUES " . implode(", ", $values);
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
            }

            $conn->commit();
            echo json_encode([
                'chat_id' => $chat_id,
                'messages_count' => count($messages),
                'success' => true
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            die(json_encode(['error' => 'Migration failed: ' . $e->getMessage()]));
        }

        break;
    
    case 'update_chat':
        $input = json_decode(file_get_contents('php://input'), true);
        $chat_id = intval($input['chat_id']);

        $stmt = $conn->prepare("SELECT id FROM chats WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $chat_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) die(json_encode(['error' => 'Access denied']));

        $updates = [];
        $params = [];
        $types = '';

        if (isset($input['title'])) {
            $updates[] = "title = ?";
            $params[] = $input['title'];
            $types .= 's';
        }

        if (isset($input['character_id'])) {
            $updates[] = "character_id = ?";
            $params[] = $input['character_id'];
            $types .= 's';
        }

        if (isset($input['model'])) {
            $updates[] = "model = ?";
            $params[] = $input['model'];
            $types .= 's';
        }

        if (count($updates) > 0) {
            $sql = "UPDATE chats SET " . implode(", ", $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $params[] = $chat_id;
            $types .= 'i';

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
        }

        echo json_encode(['success' => true]);
        break;
    
    case 'save_message':
        $input = json_decode(file_get_contents('php://input'), true);
        $chat_id = intval($input['chat_id']);
        $role = $input['role'];
        $content = $input['content'];

        $stmt = $conn->prepare("SELECT id FROM chats WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $chat_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) die(json_encode(['error' => 'Access denied']));

        $stmt = $conn->prepare("INSERT INTO messages (chat_id, role, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $chat_id, $role, $content);
        $stmt->execute();

        $idNewMessage = mysqli_insert_id($conn);

        $stmt = $conn->prepare("UPDATE chats SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();

        echo json_encode(['message_id' => $idNewMessage, 'success' => true]);
        break;
    
    case 'delete_chat':
        $chat_id = intval($_GET['chat_id'] ?? 0);

        $stmt = $conn->prepare("DELETE FROM chats WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $chat_id, $user_id);
        $stmt->execute();

        echo json_encode(['success' => true]);
        break;
    
    case 'update_message':
        $input = json_decode(file_get_contents('php://input'), true);
        $message_id = intval($input['message_id']);
        $chat_id = intval($input['chat_id']);
        $content = $input['content'];

        $stmt = $conn->prepare("SELECT id FROM chats WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $chat_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) die(json_encode(['error' => 'Access denied']));

        $stmt = $conn->prepare("UPDATE messages SET content = ? WHERE id = ? AND chat_id = ?");
        $stmt->bind_param("sii", $content, $message_id, $chat_id);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE chats SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();

        echo json_encode(['success' => true]);
        break;
    
    case 'check_last_messages':
        $chat_id = intval($_GET['chat_id'] ?? 0);

        $stmt = $conn->prepare("SELECT id FROM chats WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $chat_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) die(json_encode(['error' => 'Access denied']));

        $stmt = $conn->prepare("
            SELECT id, role FROM messages
            WHERE chat_id = ?
            ORDER BY created_at DESC
            LIMIT 2
        ");
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();
        $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['messages' => $messages]);
        break;
    
    case 'delete_message':
        $input = json_decode(file_get_contents('php://input'), true);
        $message_id = intval($input['message_id']);
        $chat_id = intval($input['chat_id']);

        $stmt = $conn->prepare("SELECT id FROM chats WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $chat_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) die(json_encode(['error' => 'Access denied']));

        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND chat_id = ?");
        $stmt->bind_param("ii", $message_id, $chat_id);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE chats SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();

        echo json_encode(['success' => true]);
        break;
    
    default:
        echo json_encode(['error' => 'Unknown action']);
}

$conn->close();