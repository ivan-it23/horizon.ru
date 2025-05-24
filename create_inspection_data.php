<?php
// Устанавливаем заголовок Content-Type, чтобы браузер ожидал JSON
header('Content-Type: application/json');
include 'db_connection.php';

$response = array(); // Массив для формирования JSON ответа

// Получение данных из POST-запроса
// Рекомендуется использовать mysqli_real_escape_string при непосредственной вставке в SQL,
// но лучше использовать подготовленные выражения.
$inspection_date = isset($_POST['inspection_date']) ? $_POST['inspection_date'] : null;
$inspection_result = isset($_POST['inspection_result']) ? $_POST['inspection_result'] : null;
$creator_surname = isset($_POST['creator_surname']) ? $_POST['creator_surname'] : null;
$serial_number = isset($_POST['serial_number']) ? $_POST['serial_number'] : null;
$case_number = isset($_POST['case_number']) ? $_POST['case_number'] : null;
// $device_id не используется в вашем SQL INSERT и отсутствует в структуре таблицы inspection_data

// Простая валидация на стороне сервера (можно расширить)
if (empty($inspection_date) || empty($inspection_result) || empty($creator_surname) || empty($serial_number) || empty($case_number)) {
    $response['success'] = false;
    $response['message'] = "Ошибка: Не все обязательные поля были заполнены.";
    echo json_encode($response);
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    exit();
}

// SQL-запрос для вставки данных с использованием подготовленных выражений для безопасности
$sql = "INSERT INTO inspection_data (inspection_date, inspection_result, creator_surname, serial_number, case_number)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Экранируем данные перед привязкой, если это необходимо (хотя bind_param уже защищает от SQL-инъекций)
    $esc_inspection_date = mysqli_real_escape_string($conn, $inspection_date);
    $esc_inspection_result = mysqli_real_escape_string($conn, $inspection_result);
    $esc_creator_surname = mysqli_real_escape_string($conn, $creator_surname);
    $esc_serial_number = mysqli_real_escape_string($conn, $serial_number);
    $esc_case_number = mysqli_real_escape_string($conn, $case_number);

    $stmt->bind_param('sssss', $esc_inspection_date, $esc_inspection_result, $esc_creator_surname, $esc_serial_number, $esc_case_number);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Данные успешно сохранены!";
    } else {
        $response['success'] = false;
        $response['message'] = "Ошибка при сохранении данных: " . $stmt->error;
        // Для отладки можно добавить: error_log("SQL Error in create_inspection_data: " . $stmt->error);
    }
    $stmt->close();
} else {
    $response['success'] = false;
    $response['message'] = "Ошибка при подготовке запроса: " . $conn->error;
    // Для отладки можно добавить: error_log("SQL Prepare Error in create_inspection_data: " . $conn->error);
}

// Закрытие соединения с базой данных
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Отправка JSON-ответа
echo json_encode($response);
?>