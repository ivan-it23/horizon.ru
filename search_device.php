<?php
session_start();
include 'db_connection.php'; // Подключение к базе данных

// Устанавливаем заголовок для ответа в формате JSON
header('Content-Type: application/json');

// Проверяем, инициализирована ли переменная $conn
if (!isset($conn)) {
    die(json_encode(['error' => 'Ошибка: переменная $conn не инициализирована.']));
}

// Получаем данные из POST-запроса
$serialNumber = isset($_POST['serial_number']) ? $_POST['serial_number'] : null;
$caseNumber = isset($_POST['case_number']) ? $_POST['case_number'] : null;

// Подготовка SQL-запроса
$sql = "SELECT * FROM device_identification WHERE 1=1";
if ($serialNumber) {
    $sql .= " AND serial_number = ?";
}
if ($caseNumber) {
    $sql .= " AND case_number = ?";
}

// Подготавливаем и выполняем запрос
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die(json_encode(['error' => 'Ошибка подготовки запроса: ' . $conn->error]));
}

// Привязываем параметры
if ($serialNumber && $caseNumber) {
    $stmt->bind_param('ss', $serialNumber, $caseNumber);
} elseif ($serialNumber) {
    $stmt->bind_param('s', $serialNumber);
} elseif ($caseNumber) {
    $stmt->bind_param('s', $caseNumber);
}

// Выполняем запрос
$stmt->execute();
$result = $stmt->get_result();
$devices = $result->fetch_all(MYSQLI_ASSOC);

if ($devices) {
    echo json_encode($devices);
} else {
    echo json_encode(['message' => 'Прибор не найден']);
}

// Закрываем соединение
$stmt->close();
$conn->close();
?>
