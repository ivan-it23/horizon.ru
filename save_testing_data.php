<?php
// Включаем буферизацию вывода, чтобы перехватить любые ошибки/предупреждения PHP
ob_start();

// Устанавливаем заголовок Content-Type для JSON ответа в самом начале
header('Content-Type: application/json');
include 'db_connection.php'; // Подключение к БД

// Инициализация массива для ответа
$response = ['success' => false, 'message' => 'Неизвестная ошибка.', 'errors' => []];

// Проверка подключения
if ($conn->connect_error) {
    $response['message'] = "Ошибка подключения: " . $conn->connect_error;
    http_response_code(500); // Internal Server Error
    // Очищаем буфер перед выводом JSON, если что-то уже было выведено (например, ошибки подключения)
    ob_end_clean();
    echo json_encode($response);
    exit;
}

// Получаем данные из POST-запроса
$test_result = isset($_POST['test_result']) ? mysqli_real_escape_string($conn, $_POST['test_result']) : '';
$serial_number = isset($_POST['serial_number']) ? mysqli_real_escape_string($conn, $_POST['serial_number']) : '';
$case_number = isset($_POST['case_number']) ? mysqli_real_escape_string($conn, $_POST['case_number']) : '';
$creator = isset($_POST['creator_surname']) ? mysqli_real_escape_string($conn, $_POST['creator_surname']) : '';

// Функция для безопасного получения float или NULL
function get_float_or_null($post_value) {
    return isset($post_value) && $post_value !== '' ? (float)$post_value : NULL;
}

// Поля для таблицы testing_data
$noise_sko_x1_400 = get_float_or_null($_POST['noise_sko_x1_400'] ?? null);
$noise_sko_y1_400 = get_float_or_null($_POST['noise_sko_y1_400'] ?? null);
$noise_sko_z1_400 = get_float_or_null($_POST['noise_sko_z1_400'] ?? null);
$noise_sko_x2_400 = get_float_or_null($_POST['noise_sko_x2_400'] ?? null);
$noise_sko_y2_400 = get_float_or_null($_POST['noise_sko_y2_400'] ?? null);
$noise_sko_z2_400 = get_float_or_null($_POST['noise_sko_z2_400'] ?? null);
$noise_sko_x1_2000 = get_float_or_null($_POST['noise_sko_x1_2000'] ?? null);
$noise_sko_y1_2000 = get_float_or_null($_POST['noise_sko_y1_2000'] ?? null);
$noise_sko_z1_2000 = get_float_or_null($_POST['noise_sko_z1_2000'] ?? null);
$noise_sko_x2_2000 = get_float_or_null($_POST['noise_sko_x2_2000'] ?? null);
$noise_sko_y2_2000 = get_float_or_null($_POST['noise_sko_y2_2000'] ?? null);
$noise_sko_z2_2000 = get_float_or_null($_POST['noise_sko_z2_2000'] ?? null);

$mds_transmitter_1_400 = get_float_or_null($_POST['mds_transmitter_1_400'] ?? null);
$mds_transmitter_2_400 = get_float_or_null($_POST['mds_transmitter_2_400'] ?? null);
$mds_transmitter_3_400 = get_float_or_null($_POST['mds_transmitter_3_400'] ?? null);
$mds_transmitter_4_400 = get_float_or_null($_POST['mds_transmitter_4_400'] ?? null);
$mds_transmitter_5_400 = get_float_or_null($_POST['mds_transmitter_5_400'] ?? null);
$mds_transmitter_1_2000 = get_float_or_null($_POST['mds_transmitter_1_2000'] ?? null);
$mds_transmitter_2_2000 = get_float_or_null($_POST['mds_transmitter_2_2000'] ?? null);
$mds_transmitter_3_2000 = get_float_or_null($_POST['mds_transmitter_3_2000'] ?? null);
$mds_transmitter_4_2000 = get_float_or_null($_POST['mds_transmitter_4_2000'] ?? null);
$mds_transmitter_5_2000 = get_float_or_null($_POST['mds_transmitter_5_2000'] ?? null);

$second_harmonic_1_400 = get_float_or_null($_POST['second_harmonic_1_400'] ?? null);
$second_harmonic_2_400 = get_float_or_null($_POST['second_harmonic_2_400'] ?? null);
$second_harmonic_3_400 = get_float_or_null($_POST['second_harmonic_3_400'] ?? null);
$second_harmonic_4_400 = get_float_or_null($_POST['second_harmonic_4_400'] ?? null);
$second_harmonic_5_400 = get_float_or_null($_POST['second_harmonic_5_400'] ?? null);
$second_harmonic_1_2000 = get_float_or_null($_POST['second_harmonic_1_2000'] ?? null);
$second_harmonic_2_2000 = get_float_or_null($_POST['second_harmonic_2_2000'] ?? null);
$second_harmonic_3_2000 = get_float_or_null($_POST['second_harmonic_3_2000'] ?? null);
$second_harmonic_4_2000 = get_float_or_null($_POST['second_harmonic_4_2000'] ?? null);
$second_harmonic_5_2000 = get_float_or_null($_POST['second_harmonic_5_2000'] ?? null);

$third_harmonic_1_400 = get_float_or_null($_POST['third_harmonic_1_400'] ?? null);
$third_harmonic_2_400 = get_float_or_null($_POST['third_harmonic_2_400'] ?? null);
$third_harmonic_3_400 = get_float_or_null($_POST['third_harmonic_3_400'] ?? null);
$third_harmonic_4_400 = get_float_or_null($_POST['third_harmonic_4_400'] ?? null);
$third_harmonic_5_400 = get_float_or_null($_POST['third_harmonic_5_400'] ?? null);
$third_harmonic_1_2000 = get_float_or_null($_POST['third_harmonic_1_2000'] ?? null);
$third_harmonic_2_2000 = get_float_or_null($_POST['third_harmonic_2_2000'] ?? null);
$third_harmonic_3_2000 = get_float_or_null($_POST['third_harmonic_3_2000'] ?? null);
$third_harmonic_4_2000 = get_float_or_null($_POST['third_harmonic_4_2000'] ?? null);
$third_harmonic_5_2000 = get_float_or_null($_POST['third_harmonic_5_2000'] ?? null);

$non_harmonic_components_1_400 = get_float_or_null($_POST['non_harmonic_components_1_400'] ?? null);
$non_harmonic_components_2_400 = get_float_or_null($_POST['non_harmonic_components_2_400'] ?? null);
$non_harmonic_components_3_400 = get_float_or_null($_POST['non_harmonic_components_3_400'] ?? null);
$non_harmonic_components_4_400 = get_float_or_null($_POST['non_harmonic_components_4_400'] ?? null);
$non_harmonic_components_5_400 = get_float_or_null($_POST['non_harmonic_components_5_400'] ?? null);
$non_harmonic_components_1_2000 = get_float_or_null($_POST['non_harmonic_components_1_2000'] ?? null);
$non_harmonic_components_2_2000 = get_float_or_null($_POST['non_harmonic_components_2_2000'] ?? null);
$non_harmonic_components_3_2000 = get_float_or_null($_POST['non_harmonic_components_3_2000'] ?? null);
$non_harmonic_components_4_2000 = get_float_or_null($_POST['non_harmonic_components_4_2000'] ?? null);
$non_harmonic_components_5_2000 = get_float_or_null($_POST['non_harmonic_components_5_2000'] ?? null);

$phase_difference_drift_1_400 = get_float_or_null($_POST['phase_difference_drift_1_400'] ?? null);
$phase_difference_drift_2_400 = get_float_or_null($_POST['phase_difference_drift_2_400'] ?? null);
$phase_difference_drift_3_400 = get_float_or_null($_POST['phase_difference_drift_3_400'] ?? null);
$phase_difference_drift_4_400 = get_float_or_null($_POST['phase_difference_drift_4_400'] ?? null);
$phase_difference_drift_5_400 = get_float_or_null($_POST['phase_difference_drift_5_400'] ?? null);
$phase_difference_drift_1_2000 = get_float_or_null($_POST['phase_difference_drift_1_2000'] ?? null);
$phase_difference_drift_2_2000 = get_float_or_null($_POST['phase_difference_drift_2_2000'] ?? null);
$phase_difference_drift_3_2000 = get_float_or_null($_POST['phase_difference_drift_3_2000'] ?? null);
$phase_difference_drift_4_2000 = get_float_or_null($_POST['phase_difference_drift_4_2000'] ?? null);
$phase_difference_drift_5_2000 = get_float_or_null($_POST['phase_difference_drift_5_2000'] ?? null);

$temperature_test = isset($_POST['temp_test']) ? mysqli_real_escape_string($conn, $_POST['temp_test']) : '';
$vibration_test = isset($_POST['vibration_data']) ? mysqli_real_escape_string($conn, $_POST['vibration_data']) : '';

// Подготовленный SQL-запрос для вставки данных
$sql = "INSERT INTO testing_data (
    test_result, serial_number, case_number, creator,
    noise_sko_x1_400, noise_sko_y1_400, noise_sko_z1_400, noise_sko_x2_400, noise_sko_y2_400, noise_sko_z2_400,
    noise_sko_x1_2000, noise_sko_y1_2000, noise_sko_z1_2000, noise_sko_x2_2000, noise_sko_y2_2000, noise_sko_z2_2000,
    mds_transmitter_1_400, mds_transmitter_2_400, mds_transmitter_3_400, mds_transmitter_4_400, mds_transmitter_5_400,
    mds_transmitter_1_2000, mds_transmitter_2_2000, mds_transmitter_3_2000, mds_transmitter_4_2000, mds_transmitter_5_2000,
    second_harmonic_1_400, second_harmonic_2_400, second_harmonic_3_400, second_harmonic_4_400, second_harmonic_5_400,
    second_harmonic_1_2000, second_harmonic_2_2000, second_harmonic_3_2000, second_harmonic_4_2000, second_harmonic_5_2000,
    third_harmonic_1_400, third_harmonic_2_400, third_harmonic_3_400, third_harmonic_4_400, third_harmonic_5_400,
    third_harmonic_1_2000, third_harmonic_2_2000, third_harmonic_3_2000, third_harmonic_4_2000, third_harmonic_5_2000,
    non_harmonic_components_1_400, non_harmonic_components_2_400, non_harmonic_components_3_400, non_harmonic_components_4_400, non_harmonic_components_5_400,
    non_harmonic_components_1_2000, non_harmonic_components_2_2000, non_harmonic_components_3_2000, non_harmonic_components_4_2000, non_harmonic_components_5_2000,
    phase_difference_drift_1_400, phase_difference_drift_2_400, phase_difference_drift_3_400, phase_difference_drift_4_400, phase_difference_drift_5_400,
    phase_difference_drift_1_2000, phase_difference_drift_2_2000, phase_difference_drift_3_2000, phase_difference_drift_4_2000, phase_difference_drift_5_2000,
    temperature_test, vibration_test
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
// Всего 68 знаков '?'

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $response['message'] = "Ошибка подготовки SQL-запроса: " . $conn->error;
    $response['errors'][] = $conn->error;
    http_response_code(500);
} else {
    // Строка типов для bind_param. 's' для строк, 'd' для float/double.
    // 4 строки, затем 62 float/double, затем 2 строки.
    $types = "ssss" . str_repeat("d", 62) . "ss"; // 4 + 62 + 2 = 68 символов

    // Переменные для bind_param в правильном порядке
    $bind_params_result = $stmt->bind_param($types,
        $test_result, $serial_number, $case_number, $creator,
        $noise_sko_x1_400, $noise_sko_y1_400, $noise_sko_z1_400, $noise_sko_x2_400, $noise_sko_y2_400, $noise_sko_z2_400,
        $noise_sko_x1_2000, $noise_sko_y1_2000, $noise_sko_z1_2000, $noise_sko_x2_2000, $noise_sko_y2_2000, $noise_sko_z2_2000,
        $mds_transmitter_1_400, $mds_transmitter_2_400, $mds_transmitter_3_400, $mds_transmitter_4_400, $mds_transmitter_5_400,
        $mds_transmitter_1_2000, $mds_transmitter_2_2000, $mds_transmitter_3_2000, $mds_transmitter_4_2000, $mds_transmitter_5_2000,
        $second_harmonic_1_400, $second_harmonic_2_400, $second_harmonic_3_400, $second_harmonic_4_400, $second_harmonic_5_400,
        $second_harmonic_1_2000, $second_harmonic_2_2000, $second_harmonic_3_2000, $second_harmonic_4_2000, $second_harmonic_5_2000,
        $third_harmonic_1_400, $third_harmonic_2_400, $third_harmonic_3_400, $third_harmonic_4_400, $third_harmonic_5_400,
        $third_harmonic_1_2000, $third_harmonic_2_2000, $third_harmonic_3_2000, $third_harmonic_4_2000, $third_harmonic_5_2000,
        $non_harmonic_components_1_400, $non_harmonic_components_2_400, $non_harmonic_components_3_400, $non_harmonic_components_4_400, $non_harmonic_components_5_400,
        $non_harmonic_components_1_2000, $non_harmonic_components_2_2000, $non_harmonic_components_3_2000, $non_harmonic_components_4_2000, $non_harmonic_components_5_2000,
        $phase_difference_drift_1_400, $phase_difference_drift_2_400, $phase_difference_drift_3_400, $phase_difference_drift_4_400, $phase_difference_drift_5_400,
        $phase_difference_drift_1_2000, $phase_difference_drift_2_2000, $phase_difference_drift_3_2000, $phase_difference_drift_4_2000, $phase_difference_drift_5_2000,
        $temperature_test, $vibration_test
    );

    if ($bind_params_result === false) {
        $response['message'] = "Ошибка привязки параметров (bind_param): " . $stmt->error;
        $response['errors'][] = $stmt->error;
        http_response_code(500);
    } else {
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Данные успешно сохранены!";
            $response['test_result_status'] = $test_result;
        } else {
            $response['message'] = "Ошибка выполнения запроса (execute): " . $stmt->error;
            $response['errors'][] = $stmt->error;
            http_response_code(500);
        }
    }
    $stmt->close();
}

$conn->close();

// Очищаем буфер вывода (удаляем все PHP warnings/errors, которые могли быть выведены)
$php_errors = ob_get_clean();
if (!empty($php_errors) && !$response['success']) {
    // Если были ошибки PHP и операция не успешна, добавляем их в JSON для отладки
    // Но только если это не успешный ответ, чтобы не засорять его
    if (empty($response['errors'])) { // Если еще нет ошибок от SQL
         $response['errors'][] = "PHP Warnings/Errors occurred: " . $php_errors;
    }
     // Если сообщение об успехе уже установлено, не перезаписываем его ошибкой PHP,
     // но ошибка PHP все равно будет в логах сервера.
     if(empty($response['message']) || $response['success']){ // Если сообщение не было установлено или это успех, но были ошибки PHP
        // $response['message'] = "Произошли ошибки PHP на сервере."; // Можно раскомментировать, если нужно
     }
} else if (!empty($php_errors) && $response['success']) {
    // Если операция успешна, но были ошибки PHP, они будут в логах сервера,
    // но мы не добавляем их в JSON ответ, чтобы не путать клиента.
    // error_log("PHP Warnings/Errors occurred during successful operation: " . $php_errors);
}


echo json_encode($response);
?>
