<?php
// get_users.php
declare(strict_types=1);
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');

// подключаем ваше соединение
include 'db_connection.php';

// если нет авторизации или ID — возвращаем пустой массив
if (empty($_SESSION['user']['id'])) {
    ob_clean();
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$currentId = (int)$_SESSION['user']['id'];

$sql  = "SELECT id, firstname, lastname, middlename
         FROM users
         WHERE id <> ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $currentId);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

ob_clean();
echo json_encode($users, JSON_UNESCAPED_UNICODE);
