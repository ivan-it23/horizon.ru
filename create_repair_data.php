<?php
session_start(); // Добавляем session_start(), если он нужен для получения информации о пользователе
include 'db_connection.php';

// Устанавливаем заголовок Content-Type, чтобы браузер ожидал JSON
header('Content-Type: application/json');

$response = array(); // Массив для формирования JSON ответа

// Проверяем, были ли переданы необходимые данные
if (!isset($_POST['repair_type']) || !isset($_POST['repair_reason']) || !isset($_POST['admission_date']) || !isset($_POST['release_date']) || !isset($_POST['actions_description']) || !isset($_POST['repair_creator']) || !isset($_POST['serial_number']) || !isset($_POST['case_number'])) {
    $response['success'] = false;
    $response['message'] = "Отсутствуют необходимые данные для сохранения.";
    echo json_encode($response);
    // Убедитесь, что $conn существует перед закрытием
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    exit();
}

// Получаем данные из POST запроса
$repair_type = $_POST['repair_type'];
$repair_reason = $_POST['repair_reason'];
$failure_reason = isset($_POST['failure_reason']) ? $_POST['failure_reason'] : null; // failure_reason может быть пустым
$admission_date = $_POST['admission_date'];
$release_date = $_POST['release_date'];
$actions_description = $_POST['actions_description'];
$repair_creator = $_POST['repair_creator'];
$serial_number = $_POST['serial_number'];
$case_number = $_POST['case_number'];


// Используем подготовленные выражения для безопасной вставки данных
$sql = "INSERT INTO repair_data (repair_type, repair_reason, failure_reason, admission_date, release_date, actions_description, repair_creator, serial_number, case_number)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Привязываем параметры. 'sssssssss' означает 9 строковых параметров.
    $stmt->bind_param('sssssssss', $repair_type, $repair_reason, $failure_reason, $admission_date, $release_date, $actions_description, $repair_creator, $serial_number, $case_number);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Данные о ремонте успешно сохранены!";
    } else {
        $response['success'] = false;
        $response['message'] = "Ошибка при выполнении запроса: " . $stmt->error;
        error_log("Ошибка при выполнении запроса INSERT в repair_data: " . $stmt->error);
    }

    $stmt->close();

} else {
    $response['success'] = false;
    $response['message'] = "Ошибка при подготовке запроса: " . $conn->error;
    error_log("Ошибка при подготовке запроса INSERT в repair_data: " . $conn->error);
}

// Убедитесь, что $conn существует перед закрытием
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Выводим JSON ответ
echo json_encode($response);
?>
