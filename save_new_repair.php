<?php
// Файл: save_new_repair.php (НОВЫЙ, для создания записи)
session_start();
include 'db_connection.php';
header('Content-Type: application/json');
$response = [];

// Проверяем, что пользователь авторизован
if (!isset($_SESSION['user']['id'])) {
    $response['success'] = false;
    $response['message'] = "Ошибка: Доступ запрещен. Пожалуйста, авторизуйтесь.";
    echo json_encode($response);
    exit();
}

// Проверяем наличие обязательных данных
if (!isset($_POST['serial_number'], $_POST['case_number'])) {
    $response['success'] = false;
    $response['message'] = "Отсутствуют идентификаторы прибора.";
    echo json_encode($response);
    exit();
}

// Получаем данные
$serial_number = $_POST['serial_number'];
$case_number = $_POST['case_number'];
$repair_reason = isset($_POST['repair_reason']) ? $_POST['repair_reason'] : null;
$failure_reason = isset($_POST['failure_reason']) ? $_POST['failure_reason'] : null;
$admission_date = !empty($_POST['admission_date']) ? $_POST['admission_date'] : null;
$release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : null;
$actions_description = isset($_POST['actions_description']) ? $_POST['actions_description'] : null;
$repair_creator = $_POST['repair_creator']; // Это имя, а не ID
$creator_id = $_SESSION['user']['id'];

// SQL запрос на вставку
$sql = "INSERT INTO repair_data (serial_number, case_number, repair_reason, failure_reason, admission_date, release_date, actions_description, repair_creator, creator_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param('ssssssssi', $serial_number, $case_number, $repair_reason, $failure_reason, $admission_date, $release_date, $actions_description, $repair_creator, $creator_id);
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Новая запись о ремонте успешно создана!";
    } else {
        $response['success'] = false;
        $response['message'] = "Ошибка при создании записи: " . $stmt->error;
    }
    $stmt->close();
} else {
    $response['success'] = false;
    $response['message'] = "Ошибка подготовки запроса: " . $conn->error;
}

$conn->close();
echo json_encode($response);
?>