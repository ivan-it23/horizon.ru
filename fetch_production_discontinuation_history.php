<?php
session_start();
include 'db_connection.php';

// Устанавливаем заголовок Content-Type, чтобы браузер ожидал JSON
header('Content-Type: application/json');

$response = array(); // Массив для формирования JSON ответа

// Проверяем, были ли переданы серийный номер и номер корпуса
if (!isset($_POST['serial_number']) || !isset($_POST['case_number'])) {
    $response['error'] = "Отсутствуют серийный номер или номер корпуса.";
    echo json_encode($response);
    $conn->close();
    exit();
}

$serial_number = mysqli_real_escape_string($conn, $_POST['serial_number']);
$case_number = mysqli_real_escape_string($conn, $_POST['case_number']);

// Используем подготовленные выражения для безопасного запроса
$sql = "SELECT * FROM production_discontinuation
        WHERE serial_number = ?
        AND case_number = ?
        ORDER BY created_at DESC"; // Сортируем по дате создания записи (новейшие первыми)

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param('ss', $serial_number, $case_number);
    $stmt->execute();
    $result = $stmt->get_result();

    $history_data = array();

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $history_data[] = $row;
        }
    }

    // Возвращаем данные истории в виде массива под ключом 'history'
    $response['history'] = $history_data;

    $stmt->close();

} else {
    $response['error'] = "Ошибка при подготовке запроса: " . $conn->error;
    error_log("Ошибка при подготовке запроса для fetch_production_discontinuation_history: " . $conn->error);
}

$conn->close();

// Выводим JSON ответ
echo json_encode($response);
?>
