<?php
include 'db_connection.php';

// Устанавливаем заголовок Content-Type, чтобы браузер ожидал JSON
header('Content-Type: application/json');

$response = array(); // Массив для формирования JSON ответа

// Проверяем, были ли переданы серийный номер и номер корпуса
if (!isset($_POST['serial_number']) || !isset($_POST['case_number'])) {
    $response['error'] = "Отсутствуют серийный номер или номер корпуса.";
    echo json_encode($response);
    // Убедитесь, что $conn существует перед закрытием
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    exit();
}

$serial_number = mysqli_real_escape_string($conn, $_POST['serial_number']);
$case_number = mysqli_real_escape_string($conn, $_POST['case_number']);

// Используем подготовленные выражения для безопасного запроса
$sql = "SELECT * FROM repair_data
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

    // Возвращаем данные истории в виде массива
    echo json_encode($history_data); // Возвращаем сам массив, а не объект с ключом 'history'

    $stmt->close();

} else {
    $response['error'] = "Ошибка при подготовке запроса: " . $conn->error;
    error_log("Ошибка при подготовке запроса для fetch_repair_data_history: " . $conn->error);
    echo json_encode($response); // Возвращаем ошибку в JSON формате
}

// Убедитесь, что $conn существует перед закрытием
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
