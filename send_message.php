<?php
// send_message.php
declare(strict_types=1);
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');

include 'db_connection.php';

// Проверяем авторизацию
if (empty($_SESSION['user']['id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Неавторизован'], JSON_UNESCAPED_UNICODE);
    exit;
}

$from_id = (int)$_SESSION['user']['id'];
$to_id   = isset($_POST['to_id'])    ? (int)$_POST['to_id']    : 0;
$msg     = isset($_POST['message'])  ? trim($_POST['message']) : '';

if ($to_id <= 0 || $msg === '') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Некорректные данные'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Вставляем сообщение
$sql  = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt->bind_param('iis', $from_id, $to_id, $msg);

if ($stmt->execute()) {
    ob_clean();
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
} else {
    ob_clean();
    echo json_encode(['success' => false, 'error' => $stmt->error], JSON_UNESCAPED_UNICODE);
}
