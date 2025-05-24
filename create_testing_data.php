<?php
// Устанавливаем заголовок, чтобы клиент знал, что ожидать JSON
header('Content-Type: application/json');

// Включаем отображение ошибок для отладки (в продакшене лучше отключить)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php'; // Подключение к БД

// Проверка подключения
if ($conn->connect_error) {
    // Возвращаем JSON с ошибкой при неудачном подключении
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к базе данных: ' . $conn->connect_error]);
    exit(); // Прекращаем выполнение скрипта
}

// Получаем данные из тела POST-запроса (ожидаем JSON)
$input = file_get_contents('php://input');
$data = json_decode($input, true); // Декодируем JSON в ассоциативный массив

// Проверка, что данные успешно декодированы и не пусты
if ($data === null) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Неверный формат JSON данных.']);
    $conn->close();
    exit();
}

// Извлекаем данные с проверкой их наличия в декодированном массиве
// Используем оператор объединения с null (??) для безопасного доступа
$test_result = $data['test_result'] ?? null;
$serial_number = $data['serial_number'] ?? null;
$case_number = $data['case_number'] ?? null;
$creator = $data['creator_surname'] ?? null; // Используем creator_surname как имя создателя
$temperature_test = $data['temp_test'] ?? null;
$vibration_test = $data['vibration_data'] ?? null;

// Извлекаем числовые поля, преобразуя пустые строки в null
$noise_sko_x1_400 = $data['noise_sko_x1_400'] !== '' ? (float)$data['noise_sko_x1_400'] : null;
$noise_sko_y1_400 = $data['noise_sko_y1_400'] !== '' ? (float)$data['noise_sko_y1_400'] : null;
$noise_sko_z1_400 = $data['noise_sko_z1_400'] !== '' ? (float)$data['noise_sko_z1_400'] : null;
$noise_sko_x2_400 = $data['noise_sko_x2_400'] !== '' ? (float)$data['noise_sko_x2_400'] : null;
$noise_sko_y2_400 = $data['noise_sko_y2_400'] !== '' ? (float)$data['noise_sko_y2_400'] : null;
$noise_sko_z2_400 = $data['noise_sko_z2_400'] !== '' ? (float)$data['noise_sko_z2_400'] : null;

$noise_sko_x1_2000 = $data['noise_sko_x1_2000'] !== '' ? (float)$data['noise_sko_x1_2000'] : null;
$noise_sko_y1_2000 = $data['noise_sko_y1_2000'] !== '' ? (float)$data['noise_sko_y1_2000'] : null;
$noise_sko_z1_2000 = $data['noise_sko_z1_2000'] !== '' ? (float)$data['noise_sko_z1_2000'] : null;
$noise_sko_x2_2000 = $data['noise_sko_x2_2000'] !== '' ? (float)$data['noise_sko_x2_2000'] : null;
$noise_sko_y2_2000 = $data['noise_sko_y2_2000'] !== '' ? (float)$data['noise_sko_y2_2000'] : null;
$noise_sko_z2_2000 = $data['noise_sko_z2_2000'] !== '' ? (float)$data['noise_sko_z2_2000'] : null;

$mds_transmitter_1_400 = $data['mds_transmitter_1_400'] !== '' ? (float)$data['mds_transmitter_1_400'] : null;
$mds_transmitter_2_400 = $data['mds_transmitter_2_400'] !== '' ? (float)$data['mds_transmitter_2_400'] : null;
$mds_transmitter_3_400 = $data['mds_transmitter_3_400'] !== '' ? (float)$data['mds_transmitter_3_400'] : null;
$mds_transmitter_4_400 = $data['mds_transmitter_4_400'] !== '' ? (float)$data['mds_transmitter_4_400'] : null;
$mds_transmitter_5_400 = $data['mds_transmitter_5_400'] !== '' ? (float)$data['mds_transmitter_5_400'] : null;
$mds_transmitter_1_2000 = $data['mds_transmitter_1_2000'] !== '' ? (float)$data['mds_transmitter_1_2000'] : null;
$mds_transmitter_2_2000 = $data['mds_transmitter_2_2000'] !== '' ? (float)$data['mds_transmitter_2_2000'] : null;
$mds_transmitter_3_2000 = $data['mds_transmitter_3_2000'] !== '' ? (float)$data['mds_transmitter_3_2000'] : null;
$mds_transmitter_4_2000 = $data['mds_transmitter_4_2000'] !== '' ? (float)$data['mds_transmitter_4_2000'] : null;
$mds_transmitter_5_2000 = $data['mds_transmitter_5_2000'] !== '' ? (float)$data['mds_transmitter_5_2000'] : null;

$second_harmonic_1_400 = $data['second_harmonic_1_400'] !== '' ? (float)$data['second_harmonic_1_400'] : null;
$second_harmonic_2_400 = $data['second_harmonic_2_400'] !== '' ? (float)$data['second_harmonic_2_400'] : null;
$second_harmonic_3_400 = $data['second_harmonic_3_400'] !== '' ? (float)$data['second_harmonic_3_400'] : null;
$second_harmonic_4_400 = $data['second_harmonic_4_400'] !== '' ? (float)$data['second_harmonic_4_400'] : null;
$second_harmonic_5_400 = $data['second_harmonic_5_400'] !== '' ? (float)$data['second_harmonic_5_400'] : null;
$second_harmonic_1_2000 = $data['second_harmonic_1_2000'] !== '' ? (float)$data['second_harmonic_1_2000'] : null;
$second_harmonic_2_2000 = $data['second_harmonic_2_2000'] !== '' ? (float)$data['second_harmonic_2_2000'] : null;
$second_harmonic_3_2000 = $data['second_harmonic_3_2000'] !== '' ? (float)$data['second_harmonic_3_2000'] : null;
$second_harmonic_4_2000 = $data['second_harmonic_4_2000'] !== '' ? (float)$data['second_harmonic_4_2000'] : null;
$second_harmonic_5_2000 = $data['second_harmonic_5_2000'] !== '' ? (float)$data['second_harmonic_5_2000'] : null;

$third_harmonic_1_400 = $data['third_harmonic_1_400'] !== '' ? (float)$data['third_harmonic_1_400'] : null;
$third_harmonic_2_400 = $data['third_harmonic_2_400'] !== '' ? (float)$data['third_harmonic_2_400'] : null;
$third_harmonic_3_400 = $data['third_harmonic_3_400'] !== '' ? (float)$data['third_harmonic_3_400'] : null;
$third_harmonic_4_400 = $data['third_harmonic_4_400'] !== '' ? (float)$data['third_harmonic_4_400'] : null;
$third_harmonic_5_400 = $data['third_harmonic_5_400'] !== '' ? (float)$data['third_harmonic_5_400'] : null;
$third_harmonic_1_2000 = $data['third_harmonic_1_2000'] !== '' ? (float)$data['third_harmonic_1_2000'] : null;
$third_harmonic_2_2000 = $data['third_harmonic_2_2000'] !== '' ? (float)$data['third_harmonic_2_2000'] : null;
$third_harmonic_3_2000 = $data['third_harmonic_3_2000'] !== '' ? (float)$data['third_harmonic_3_2000'] : null;
$third_harmonic_4_2000 = $data['third_harmonic_4_2000'] !== '' ? (float)$data['third_harmonic_4_2000'] : null;
$third_harmonic_5_2000 = $data['third_harmonic_5_2000'] !== '' ? (float)$data['third_harmonic_5_2000'] : null;

$non_harmonic_components_1_400 = $data['non_harmonic_components_1_400'] !== '' ? (float)$data['non_harmonic_components_1_400'] : null;
$non_harmonic_components_2_400 = $data['non_harmonic_components_2_400'] !== '' ? (float)$data['non_harmonic_components_2_400'] : null;
$non_harmonic_components_3_400 = $data['non_harmonic_components_3_400'] !== '' ? (float)$data['non_harmonic_components_3_400'] : null;
$non_harmonic_components_4_400 = $data['non_harmonic_components_4_400'] !== '' ? (float)$data['non_harmonic_components_4_400'] : null;
$non_harmonic_components_5_400 = $data['non_harmonic_components_5_400'] !== '' ? (float)$data['non_harmonic_components_5_400'] : null;
$non_harmonic_components_1_2000 = $data['non_harmonic_components_1_2000'] !== '' ? (float)$data['non_harmonic_components_1_2000'] : null;
$non_harmonic_components_2_2000 = $data['non_harmonic_components_2_2000'] !== '' ? (float)$data['non_harmonic_components_2_2000'] : null;
$non_harmonic_components_3_2000 = $data['non_harmonic_components_3_2000'] !== '' ? (float)$data['non_harmonic_components_3_2000'] : null;
$non_harmonic_components_4_2000 = $data['non_harmonic_components_4_2000'] !== '' ? (float)$data['non_harmonic_components_4_2000'] : null;
$non_harmonic_components_5_2000 = $data['non_harmonic_components_5_2000'] !== '' ? (float)$data['non_harmonic_components_5_2000'] : null;

$phase_difference_drift_1_400 = $data['phase_difference_drift_1_400'] !== '' ? (float)$data['phase_difference_drift_1_400'] : null;
$phase_difference_drift_2_400 = $data['phase_difference_drift_2_400'] !== '' ? (float)$data['phase_difference_drift_2_400'] : null;
$phase_difference_drift_3_400 = $data['phase_difference_drift_3_400'] !== '' ? (float)$data['phase_difference_drift_3_400'] : null;
$phase_difference_drift_4_400 = $data['phase_difference_drift_4_400'] !== '' ? (float)$data['phase_difference_drift_4_400'] : null;
$phase_difference_drift_5_400 = $data['phase_difference_drift_5_400'] !== '' ? (float)$data['phase_difference_drift_5_400'] : null;
$phase_difference_drift_1_2000 = $data['phase_difference_drift_1_2000'] !== '' ? (float)$data['phase_difference_drift_1_2000'] : null;
$phase_difference_drift_2_2000 = $data['phase_difference_drift_2_2000'] !== '' ? (float)$data['phase_difference_drift_2_2000'] : null;
$phase_difference_drift_3_2000 = $data['phase_difference_drift_3_2000'] !== '' ? (float)$data['phase_difference_drift_3_2000'] : null;
$phase_difference_drift_4_2000 = $data['phase_difference_drift_4_2000'] !== '' ? (float)$data['phase_difference_drift_4_2000'] : null;
$phase_difference_drift_5_2000 = $data['phase_difference_drift_5_2000'] !== '' ? (float)$data['phase_difference_drift_5_2000'] : null;


// Подготовленный запрос для вставки данных
$sql = "INSERT INTO testing_data (
    test_result,
    serial_number,
    case_number,
    creator,
    temperature_test,
    vibration_test,
    noise_sko_x1_400,
    noise_sko_y1_400,
    noise_sko_z1_400,
    noise_sko_x2_400,
    noise_sko_y2_400,
    noise_sko_z2_400,
    noise_sko_x1_2000,
    noise_sko_y1_2000,
    noise_sko_z1_2000,
    noise_sko_x2_2000,
    noise_sko_y2_2000,
    noise_sko_z2_2000,
    mds_transmitter_1_400,
    mds_transmitter_2_400,
    mds_transmitter_3_400,
    mds_transmitter_4_400,
    mds_transmitter_5_400,
    mds_transmitter_1_2000,
    mds_transmitter_2_2000,
    mds_transmitter_3_2000,
    mds_transmitter_4_2000,
    mds_transmitter_5_2000,
    second_harmonic_1_400,
    second_harmonic_2_400,
    second_harmonic_3_400,
    second_harmonic_4_400,
    second_harmonic_5_400,
    second_harmonic_1_2000,
    second_harmonic_2_2000,
    second_harmonic_3_2000,
    second_harmonic_4_2000,
    second_harmonic_5_2000,
    third_harmonic_1_400,
    third_harmonic_2_400,
    third_harmonic_3_400,
    third_harmonic_4_400,
    third_harmonic_5_400,
    third_harmonic_1_2000,
    third_harmonic_2_2000,
    third_harmonic_3_2000,
    third_harmonic_4_2000,
    third_harmonic_5_2000,
    non_harmonic_components_1_400,
    non_harmonic_components_2_400,
    non_harmonic_components_3_400,
    non_harmonic_components_4_400,
    non_harmonic_components_5_400,
    non_harmonic_components_1_2000,
    non_harmonic_components_2_2000,
    non_harmonic_components_3_2000,
    non_harmonic_components_4_2000,
    non_harmonic_components_5_2000,
    phase_difference_drift_1_400,
    phase_difference_drift_2_400,
    phase_difference_drift_3_400,
    phase_difference_drift_4_400,
    phase_difference_drift_5_400,
    phase_difference_drift_1_2000,
    phase_difference_drift_2_2000,
    phase_difference_drift_3_2000,
    phase_difference_drift_4_2000,
    phase_difference_drift_5_2000
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка подготовки запроса: ' . $conn->error]);
    $conn->close();
    exit();
}

// Привязка параметров. Используем 's' для строк и 'd' для чисел с плавающей точкой.
// Учитываем, что некоторые поля могут быть NULL.
$stmt->bind_param(
    "sssssdddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd",
    $test_result,
    $serial_number,
    $case_number,
    $creator,
    $temperature_test,
    $vibration_test,
    $noise_sko_x1_400,
    $noise_sko_y1_400,
    $noise_sko_z1_400,
    $noise_sko_x2_400,
    $noise_sko_y2_400,
    $noise_sko_z2_400,
    $noise_sko_x1_2000,
    $noise_sko_y1_2000,
    $noise_sko_z1_2000,
    $noise_sko_x2_2000,
    $noise_sko_y2_2000,
    $noise_sko_z2_2000,
    $mds_transmitter_1_400,
    $mds_transmitter_2_400,
    $mds_transmitter_3_400,
    $mds_transmitter_4_400,
    $mds_transmitter_5_400,
    $mds_transmitter_1_2000,
    $mds_transmitter_2_2000,
    $mds_transmitter_3_2000,
    $mds_transmitter_4_2000,
    $mds_transmitter_5_2000,
    $second_harmonic_1_400,
    $second_harmonic_2_400,
    $second_harmonic_3_400,
    $second_harmonic_4_400,
    $second_harmonic_5_400,
    $second_harmonic_1_2000,
    $second_harmonic_2_2000,
    $second_harmonic_3_2000,
    $second_harmonic_4_2000,
    $second_harmonic_5_2000,
    $third_harmonic_1_400,
    $third_harmonic_2_400,
    $third_harmonic_3_400,
    $third_harmonic_4_400,
    $third_harmonic_5_400,
    $third_harmonic_1_2000,
    $third_harmonic_2_2000,
    $third_harmonic_3_2000,
    $third_harmonic_4_2000,
    $third_harmonic_5_2000,
    $non_harmonic_components_1_400,
    $non_harmonic_components_2_400,
    $non_harmonic_components_3_400,
    $non_harmonic_components_4_400,
    $non_harmonic_components_5_400,
    $non_harmonic_components_1_2000,
    $non_harmonic_components_2_2000,
    $non_harmonic_components_3_2000,
    $non_harmonic_components_4_2000,
    $non_harmonic_components_5_2000,
    $phase_difference_drift_1_400,
    $phase_difference_drift_2_400,
    $phase_difference_drift_3_400,
    $phase_difference_drift_4_400,
    $phase_difference_drift_5_400,
    $phase_difference_drift_1_2000,
    $phase_difference_drift_2_2000,
    $phase_difference_drift_3_2000,
    $phase_difference_drift_4_2000,
    $phase_difference_drift_5_2000
);

// Выполнение запроса
if ($stmt->execute()) {
    // Возвращаем JSON с сообщением об успехе
    echo json_encode(['success' => true, 'message' => 'Данные успешно сохранены!']);
} else {
    // Возвращаем JSON с ошибкой выполнения запроса
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении данных: ' . $stmt->error]);
}

// Закрытие соединения и запроса
$stmt->close();
$conn->close();
?>
