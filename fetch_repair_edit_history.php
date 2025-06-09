<?php
// Файл: fetch_repair_edit_history.php (НОВЫЙ)
session_start();
include 'db_connection.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID записи о ремонте не указан.']);
    exit;
}

$repair_id = (int)$_GET['id'];
$response = [];

// Получаем историю изменений, объединяя с таблицей пользователей для получения имени автора
$sql = "SELECT h.*, u.lastname, u.firstname, u.middlename
        FROM repair_data_history h
        LEFT JOIN users u ON h.changed_by_user_id = u.id
        WHERE h.repair_data_id = ?
        ORDER BY h.changed_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('i', $repair_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
    $stmt->close();
} else {
    $response = ['error' => 'Ошибка подготовки запроса.'];
}
$conn->close();
echo json_encode($response);
?>