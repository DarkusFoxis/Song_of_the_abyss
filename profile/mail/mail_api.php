<?php
require_once("./system/config.php");
require_once("./system/tools_check.php");

$API_KEY = "ABYSS2445$#@-5211XRTW";

$headers = getallheaders();
if (($headers['X-API-KEY'] ?? '') !== $API_KEY) {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$action = $_GET['action'] ?? '';

if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $to = $data['to'] ?? null;
    $subject = $data['subject'] ?? '';
    $body = $data['body'] ?? '';
    if (!$to || !$subject || !$body) {
        http_response_code(400);
        echo json_encode(["error" => "Missing fields"]);
        exit;
    }
    if (is_numeric($to)) {
        $stmt = $pdo->prepare("SELECT id, user_id, public_key FROM mail_user WHERE user_id = ?");
        $stmt->execute([$to]);
    } else {
        $stmt = $pdo->prepare("SELECT id, user_id, public_key FROM mail_user WHERE username = ?");
        $stmt->execute([$to]);
    }
    $recipient = $stmt->fetch();
    if (!$recipient || empty($recipient['public_key'])) {
        http_response_code(404);
        echo json_encode(["error" => "Recipient not found"]);
        exit;
    }
    $system_sender = [
        'id' => 0,
        'user_id' => 0,
        'public_key' => file_get_contents(ADMIN_PUBLIC_KEY)
    ];
    $aes_key = openssl_random_pseudo_bytes(32);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted_body = openssl_encrypt($body, 'AES-256-CBC', $aes_key, 0, $iv);
    $encrypted_body .= '::' . base64_encode($iv);
    openssl_public_encrypt($aes_key, $key_for_user, $recipient['public_key']);
    openssl_public_encrypt($aes_key, $key_for_sender, $system_sender['public_key']);
    openssl_public_encrypt($aes_key, $key_for_admin, file_get_contents(ADMIN_PUBLIC_KEY));
    $stmt = $pdo->prepare("INSERT INTO mail (sender_id, recipient_id, subject, body, key_user, key_sender, key_admin) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $system_sender['id'],
        $recipient['id'],
        $subject,
        $encrypted_body,
        base64_encode($key_for_user),
        base64_encode($key_for_sender),
        base64_encode($key_for_admin)
    ]);
    echo json_encode(["success" => true, "mail_id" => $pdo->lastInsertId()]);
    exit;
}

if ($action === 'send_mass' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $subject = $data['subject'] ?? '';
    $body = $data['body'] ?? '';
    if (!$subject || !$body) {
        http_response_code(400);
        echo json_encode(["error" => "Missing fields"]);
        exit;
    }
    $stmt = $pdo->query("SELECT id, user_id, public_key FROM mail_user");
    $users = $stmt->fetchAll();
    $system_sender = [
        'id' => 0,
        'user_id' => 0,
        'public_key' => file_get_contents(ADMIN_PUBLIC_KEY)
    ];
    $sent = 0;
    foreach ($users as $recipient) {
        if (empty($recipient['public_key'])) continue;
        $aes_key = openssl_random_pseudo_bytes(32);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted_body = openssl_encrypt($body, 'AES-256-CBC', $aes_key, 0, $iv);
        $encrypted_body .= '::' . base64_encode($iv);
        openssl_public_encrypt($aes_key, $key_for_user, $recipient['public_key']);
        openssl_public_encrypt($aes_key, $key_for_sender, $system_sender['public_key']);
        openssl_public_encrypt($aes_key, $key_for_admin, file_get_contents(ADMIN_PUBLIC_KEY));
        $stmt2 = $pdo->prepare("INSERT INTO mail (sender_id, recipient_id, subject, body, key_user, key_sender, key_admin) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt2->execute([
            $system_sender['id'],
            $recipient['id'],
            $subject,
            $encrypted_body,
            base64_encode($key_for_user),
            base64_encode($key_for_sender),
            base64_encode($key_for_admin)
        ]);
        $sent++;
    }
    echo json_encode(["success" => true, "sent" => $sent]);
    exit;
}

if ($action === 'inbox' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = intval($_GET['user_id'] ?? 0);
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(["error" => "Missing user_id"]);
        exit;
    }
    $stmt = $pdo->prepare("SELECT mu.id FROM mail_user mu WHERE mu.user_id = ?");
    $stmt->execute([$user_id]);
    $mail_user = $stmt->fetch();
    if (!$mail_user) {
        http_response_code(404);
        echo json_encode(["error" => "Mail profile not found"]);
        exit;
    }
    $my_mail_id = $mail_user['id'];
    $stmt = $pdo->prepare("SELECT m.id, m.subject, m.body, m.sender_id, m.recipient_id, m.sent_at FROM mail m WHERE m.recipient_id = ? ORDER BY m.sent_at DESC LIMIT 20");
    $stmt->execute([$my_mail_id]);
    $messages = $stmt->fetchAll();
    echo json_encode(["messages" => $messages]);
    exit;
}

http_response_code(400);
echo json_encode(["error" => "Unknown action"]);
exit;