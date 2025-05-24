<?php
session_start();
include 'db_connection.php';

// Устанавливаем заголовок Content-Type, чтобы браузер ожидал JSON
header('Content-Type: application/json');

$response = array(); // Массив для формирования JSON ответа

// Получаем данные из POST запроса.
// serial_number и case_number теперь ожидаются из JS, основанные на выбранном приборе.
$discontinuation_date = isset($_POST['discontinuation_date']) ? mysqli_real_escape_string($conn, $_POST['discontinuation_date']) : '';
$creator_surname = isset($_POST['creator_surname']) ? mysqli_real_escape_string($conn, $_POST['creator_surname']) : '';
$serial_number = isset($_POST['serial_number']) ? mysqli_real_escape_string($conn, $_POST['serial_number']) : '';
$case_number = isset($_POST['case_number']) ? mysqli_real_escape_string($conn, $_POST['case_number']) : '';


// Проверка наличия обязательных данных
if (empty($discontinuation_date) || empty($creator_surname) || empty($serial_number) || empty($case_number)) {
    $response['success'] = false;
    $response['message'] = "Отсутствуют обязательные данные (дата, создатель, серийный номер или номер корпуса).";
    echo json_encode($response);
    $conn->close();
    exit(); // Прерываем выполнение скрипта
}


// Используем подготовленные выражения для безопасной вставки данных
$sql = "INSERT INTO production_discontinuation (discontinuation_date, creator_surname, serial_number, case_number)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Привязываем параметры к подготовленному выражению
    $stmt->bind_param('ssss', $discontinuation_date, $creator_surname, $serial_number, $case_number);

    // Выполняем запрос
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Данные успешно сохранены!";
    } else {
        $response['success'] = false;
        // Включаем информацию об ошибке выполнения запроса
        $response['message'] = "Ошибка при выполнении запроса: " . $stmt->error;
        error_log("Ошибка при сохранении данных о выходе из производства: " . $stmt->error); // Логируем ошибку на сервере
    }
    $stmt->close();
} else {
    $response['success'] = false;
    // Включаем информацию об ошибке подготовки запроса
    $response['message'] = "Ошибка при подготовке запроса: " . $conn->error;
    error_log("Ошибка при подготовке запроса для create_production_discontinuation: " . $conn->error); // Логируем ошибку на сервере
}

$conn->close();

// Выводим JSON ответ
echo json_encode($response);
?>
