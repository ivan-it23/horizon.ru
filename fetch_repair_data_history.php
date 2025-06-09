<?php
// Файл: fetch_repair_data_history.php (ОБНОВЛЕННЫЙ)
// (Этот файл теперь используется для получения списка всех отдельных ремонтов)
include 'db_connection.php';
header('Content-Type: application/json');
$response = [];

if (!isset($_POST['serial_number']) || !isset($_POST['case_number'])) {
    $response['error'] = "Отсутствуют серийный номер или номер корпуса.";
    echo json_encode($response);
    exit();
}

$serial_number = $_POST['serial_number'];
$case_number = $_POST['case_number'];

// Запрос остается прежним, но теперь он возвращает список уникальных ремонтов
$sql = "SELECT * FROM repair_data
        WHERE serial_number = ? AND case_number = ?
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('ss', $serial_number, $case_number);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
    $stmt->close();
} else {
    $response['error'] = "Ошибка при подготовке запроса: " . $conn->error;
}
$conn->close();
echo json_encode($response);
?>