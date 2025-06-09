<?php
// Файл: delete_repair_record.php (НОВЫЙ)
session_start();
include 'db_connection.php';
header('Content-Type: application/json');
$response = [];

// Проверяем, что пользователь авторизован и является администратором
if (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin']) {
    $response['success'] = false;
    $response['message'] = "Ошибка: У вас нет прав для выполнения этого действия.";
    echo json_encode($response);
    exit();
}

if (!isset($_POST['id'])) {
    $response['success'] = false;
    $response['message'] = "Отсутствует ID записи для удаления.";
    echo json_encode($response);
    exit();
}

$repair_id = (int)$_POST['id'];

// Внешний ключ с ON DELETE CASCADE позаботится об удалении записей из repair_data_history
$sql = "DELETE FROM repair_data WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('i', $repair_id);
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Запись о ремонте и вся ее история были удалены.";
    } else {
        $response['success'] = false;
        $response['message'] = "Ошибка при удалении: " . $stmt->error;
    }
    $stmt->close();
} else {
    $response['success'] = false;
    $response['message'] = "Ошибка подготовки запроса: " . $conn->error;
}

$conn->close();
echo json_encode($response);
?>