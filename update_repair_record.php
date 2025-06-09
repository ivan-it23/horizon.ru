<?php
// Файл: update_repair_record.php (НОВЫЙ, для редактирования)
session_start();
include 'db_connection.php';
header('Content-Type: application/json');
$response = [];

// Проверка авторизации и наличия данных
if (!isset($_SESSION['user']['id'])) {
    $response['success'] = false; $response['message'] = "Доступ запрещен."; echo json_encode($response); exit();
}
if (!isset($_POST['id'])) {
    $response['success'] = false; $response['message'] = "Отсутствует ID записи."; echo json_encode($response); exit();
}

$repair_id = (int)$_POST['id'];
$user_id = (int)$_SESSION['user']['id'];
$new_data = [
    'repair_reason' => isset($_POST['repair_reason']) ? $_POST['repair_reason'] : null,
    'failure_reason' => isset($_POST['failure_reason']) ? $_POST['failure_reason'] : null,
    'admission_date' => !empty($_POST['admission_date']) ? $_POST['admission_date'] : null,
    'release_date' => !empty($_POST['release_date']) ? $_POST['release_date'] : null,
    'actions_description' => isset($_POST['actions_description']) ? $_POST['actions_description'] : null,
];

$conn->begin_transaction();
try {
    // 1. Получаем текущую запись, чтобы сохранить ее в историю
    $sql_select = "SELECT repair_reason, failure_reason, admission_date, release_date, actions_description FROM repair_data WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param('i', $repair_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $old_data = $result->fetch_assoc();
    $stmt_select->close();

    if (!$old_data) {
        throw new Exception("Запись не найдена.");
    }

    // 2. Сохраняем старую версию в историю
    $sql_history = "INSERT INTO repair_data_history (repair_data_id, repair_reason, failure_reason, admission_date, release_date, actions_description, changed_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_history = $conn->prepare($sql_history);
    $stmt_history->bind_param('isssssi', $repair_id, $old_data['repair_reason'], $old_data['failure_reason'], $old_data['admission_date'], $old_data['release_date'], $old_data['actions_description'], $user_id);
    $stmt_history->execute();
    $stmt_history->close();

    // 3. Обновляем основную запись
    $sql_update = "UPDATE repair_data SET repair_reason = ?, failure_reason = ?, admission_date = ?, release_date = ?, actions_description = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param('sssssi', $new_data['repair_reason'], $new_data['failure_reason'], $new_data['admission_date'], $new_data['release_date'], $new_data['actions_description'], $repair_id);
    $stmt_update->execute();
    $stmt_update->close();

    $conn->commit();
    $response['success'] = true;
    $response['message'] = "Изменения успешно сохранены!";

} catch (Exception $e) {
    $conn->rollback();
    $response['success'] = false;
    $response['message'] = "Ошибка транзакции: " . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>