<?php
// Подключение к базе данных
include 'db_connection.php';

// Установка заголовка для ответа в формате JSON
header('Content-Type: application/json');

// Получение данных из POST-запроса и их экранирование
$termination_date = isset($_POST['termination_date']) ? mysqli_real_escape_string($conn, $_POST['termination_date']) : '';
$termination_reason = isset($_POST['termination_reason']) ? mysqli_real_escape_string($conn, $_POST['termination_reason']) : '';
$creator_surname = isset($_POST['creator_surname']) ? mysqli_real_escape_string($conn, $_POST['creator_surname']) : '';
// device_id не используется в INSERT, но может быть полезен для логирования или будущих связей
$device_id = isset($_POST['device_id']) ? mysqli_real_escape_string($conn, $_POST['device_id']) : '';
$serial_number = isset($_POST['serial_number']) ? mysqli_real_escape_string($conn, $_POST['serial_number']) : '';
$case_number = isset($_POST['case_number']) ? mysqli_real_escape_string($conn, $_POST['case_number']) : '';

// Проверка наличия обязательных данных (серийный номер и номер корпуса)
if (empty($serial_number) || empty($case_number)) {
    // Если обязательные данные отсутствуют, возвращаем ошибку в JSON
    echo json_encode([
        "success" => false,
        "message" => "Ошибка: Не указан серийный номер или номер корпуса."
    ]);
    $conn->close();
    exit(); // Прерываем выполнение скрипта
}


// SQL-запрос для вставки данных
// Обратите внимание: device_id не включен в INSERT, так как его нет в структуре таблицы termination_data
$sql = "INSERT INTO termination_data (termination_date, termination_reason, creator_surname, serial_number, case_number)
        VALUES (?, ?, ?, ?, ?)";

// Подготовленное выражение для безопасной вставки данных
$stmt = $conn->prepare($sql);

if ($stmt) {
    // Привязка параметров
    $stmt->bind_param("sssss", $termination_date, $termination_reason, $creator_surname, $serial_number, $case_number);

    // Выполнение запроса
    if ($stmt->execute()) {
        // Успешная вставка, возвращаем JSON с success: true
        echo json_encode([
            "success" => true,
            "message" => "Данные успешно сохранены!"
        ]);
    } else {
        // Ошибка выполнения запроса, возвращаем JSON с success: false и текстом ошибки
        echo json_encode([
            "success" => false,
            "message" => "Ошибка при сохранении данных: " . $stmt->error
        ]);
    }

    // Закрытие выражения
    $stmt->close();
} else {
    // Ошибка подготовки запроса
    echo json_encode([
        "success" => false,
        "message" => "Ошибка подготовки SQL запроса: " . $conn->error
    ]);
}


// Закрытие соединения с базой данных
$conn->close();
?>
