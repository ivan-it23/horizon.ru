<?php
session_start();
include 'db_connection.php';

// Функция для безопасного получения данных из POST
function get_post_data($conn, $key) {
    return isset($_POST[$key]) ? mysqli_real_escape_string($conn, $_POST[$key]) : null;
}

// Устанавливаем заголовок ответа как JSON
header('Content-Type: application/json');

// Получение основных данных прибора
$device_type         = get_post_data($conn, 'device_type');
$nominal_diameter    = get_post_data($conn, 'nominal_diameter');
$transmitter_count   = get_post_data($conn, 'transmitter_count');
$serial_number       = get_post_data($conn, 'serial_number');
$case_number         = get_post_data($conn, 'case_number');
$length_mm           = get_post_data($conn, 'length_mm');
$diameter_mm         = get_post_data($conn, 'diameter_mm');
$upper_thread        = get_post_data($conn, 'upper_thread');
$lower_thread        = get_post_data($conn, 'lower_thread');
$frequency1_khz      = get_post_data($conn, 'frequency1_khz');
$frequency2_khz      = get_post_data($conn, 'frequency2_khz');

// Базы передатчиков (максимум 5)
for ($i = 1; $i <= 5; $i++) {
    ${"transmitter_base{$i}_mm"} = get_post_data($conn, "transmitter_base{$i}_mm");
}

// Базы приёмников (текущие поля)
$receiver_base1_mm   = get_post_data($conn, 'receiver_base1_mm');
$receiver_base2_mm   = get_post_data($conn, 'receiver_base2_mm');
$receiver_base3_mm   = get_post_data($conn, 'receiver_base3_mm');

$record_date         = get_post_data($conn, 'record_date');
$creator_lastname    = isset($_SESSION['user'])
    ? mysqli_real_escape_string($conn, $_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename'])
    : 'Неизвестный пользователь';

// Обновлённый SQL‑запрос (добавлены столбцы transmitter_base1_mm…transmitter_base5_mm)
$sql_identification = "
INSERT INTO device_identification (
    device_type, nominal_diameter, transmitter_count,
    serial_number, case_number, length_mm, diameter_mm,
    upper_thread, lower_thread, frequency1_khz, frequency2_khz,
    transmitter_base1_mm, transmitter_base2_mm, transmitter_base3_mm,
    transmitter_base4_mm, transmitter_base5_mm,
    receiver_base1_mm, receiver_base2_mm, receiver_base3_mm,
    record_date, creator_lastname
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt_identification = $conn->prepare($sql_identification);
if (!$stmt_identification) {
    // В случае ошибки подготовки запроса, возвращаем JSON с ошибкой
    echo json_encode(['success' => false, 'error' => "Ошибка подготовки запроса идентификации: " . $conn->error]);
    $conn->close();
    exit;
}

// Биндим 21 параметр (все как строки)
// Используем 's' для всех, т.к. get_post_data возвращает строки или null
$stmt_identification->bind_param(
    str_repeat('s', 21),
    $device_type, $nominal_diameter, $transmitter_count,
    $serial_number, $case_number, $length_mm, $diameter_mm,
    $upper_thread, $lower_thread, $frequency1_khz, $frequency2_khz,
    $transmitter_base1_mm, $transmitter_base2_mm, $transmitter_base3_mm,
    $transmitter_base4_mm, $transmitter_base5_mm,
    $receiver_base1_mm, $receiver_base2_mm, $receiver_base3_mm,
    $record_date, $creator_lastname
);

if (!$stmt_identification->execute()) {
    // В случае ошибки выполнения запроса, возвращаем JSON с ошибкой
    echo json_encode(['success' => false, 'error' => "Ошибка выполнения вставки идентификации: " . $stmt_identification->error]);
    $stmt_identification->close();
    $conn->close();
    exit;
}

$device_id = $conn->insert_id;
$stmt_identification->close();

// Обработка данных состава прибора
if ($device_id) {
    // Данные для таблицы device_composition
    $composition_data = [];

    // Обработка данных из полей для основных плат (Контроллер, Инклинометр, Приёмники, Передатчики)
    // Контроллер
    if (!empty($_POST['controller_type']) || !empty($_POST['controller_version']) || !empty($_POST['controller_serial'])) {
         $composition_data[] = [
                'board_name' => 'Контроллер',
                'type' => get_post_data($conn, 'controller_type'),
                'version' => get_post_data($conn, 'controller_version'),
                'serial_number' => get_post_data($conn, 'controller_serial'),
            ];
    }
    // Инклинометр
    if (!empty($_POST['inclinometer_type']) || !empty($_POST['inclinometer_version']) || !empty($_POST['inclinometer_serial'])) {
         $composition_data[] = [
                'board_name' => 'Инклинометр',
                'type' => get_post_data($conn, 'inclinometer_type'),
                'version' => get_post_data($conn, 'inclinometer_version'),
                'serial_number' => get_post_data($conn, 'inclinometer_serial'),
            ];
    }

    // Приемники (количество может быть разным, проверяем наличие полей)
    for ($i = 1; isset($_POST["receiver_{$i}_type"]); $i++) {
        if (!empty($_POST["receiver_{$i}_type"]) || !empty($_POST["receiver_{$i}_version"]) || !empty($_POST["receiver_{$i}_serial"])) {
           $composition_data[] = [
                'board_name' => "Приёмник {$i}",
                'type' => get_post_data($conn, "receiver_{$i}_type"),
                'version' => get_post_data($conn, "receiver_{$i}_version"),
                'serial_number' => get_post_data($conn, "receiver_{$i}_serial"),
            ];
        }
    }

    // Передатчики (количество может быть разным, проверяем наличие полей)
    for ($i = 1; isset($_POST["transmitter_{$i}_type"]); $i++) {
        if (!empty($_POST["transmitter_{$i}_type"]) || !empty($_POST["transmitter_{$i}_version"]) || !empty($_POST["transmitter_{$i}_serial"])) {
            $composition_data[] = [
                'board_name' => "Передатчик {$i}",
                'type' => get_post_data($conn, "transmitter_{$i}_type"),
                'version' => get_post_data($conn, "transmitter_{$i}_version"),
                'serial_number' => get_post_data($conn, "transmitter_{$i}_serial"),
            ];
        }
    }


    // Обработка данных для прочих плат (используем массивы other_board_name[], other_board_type[], etc.)
    if (isset($_POST['other_board_name']) && is_array($_POST['other_board_name'])) {
        $other_board_names = $_POST['other_board_name'];
        $other_board_types = isset($_POST['other_board_type']) ? $_POST['other_board_type'] : [];
        $other_board_versions = isset($_POST['other_board_version']) ? $_POST['other_board_version'] : [];
        $other_board_serials = isset($_POST['other_board_serial']) ? $_POST['other_board_serial'] : [];

        for ($i = 0; $i < count($other_board_names); $i++) {
             // Проверяем, что хотя бы одно поле в строке заполнено
            if (!empty($other_board_names[$i]) || !empty($other_board_types[$i]) || !empty($other_board_versions[$i]) || !empty($other_board_serials[$i])) {
                $composition_data[] = [
                    'board_name' => isset($other_board_names[$i]) ? mysqli_real_escape_string($conn, $other_board_names[$i]) : null,
                    'type' => isset($other_board_types[$i]) ? mysqli_real_escape_string($conn, $other_board_types[$i]) : null,
                    'version' => isset($other_board_versions[$i]) ? mysqli_real_escape_string($conn, $other_board_versions[$i]) : null,
                    'serial_number' => isset($other_board_serials[$i]) ? mysqli_real_escape_string($conn, $other_board_serials[$i]) : null,
                ];
            }
        }
    }


    // Подготовка и выполнение запросов для device_composition, если есть данные
    if (!empty($composition_data)) {
        $sql_composition = "INSERT INTO device_composition (device_id, board_name, type, version, serial_number)
                            VALUES (?, ?, ?, ?, ?)";
        $stmt_composition = $conn->prepare($sql_composition);
        if (!$stmt_composition) {
             // В случае ошибки подготовки запроса, возвращаем JSON с ошибкой
            echo json_encode(['success' => false, 'error' => "Ошибка при подготовке запроса для device_composition: " . $conn->error]);
            $conn->close();
            exit; // Завершаем выполнение скрипта
        } else {
            $stmt_composition->bind_param("issss", $device_id, $board_name, $type, $version, $serial_number);
            foreach ($composition_data as $board) {
                $board_name = $board['board_name'];
                $type = $board['type'];
                $version = $board['version'];
                $serial_number = $board['serial_number'];

                if (!$stmt_composition->execute()) {
                    // В случае ошибки выполнения запроса, возвращаем JSON с ошибкой
                    echo json_encode(['success' => false, 'error' => "Ошибка при выполнении запроса для device_composition: " . $stmt_composition->error]);
                    $stmt_composition->close();
                    $conn->close();
                    exit; // Завершаем выполнение скрипта
                }
            }
            $stmt_composition->close();
        }
    }

    // Если все прошло успешно, возвращаем JSON с сообщением об успехе
    echo json_encode(['success' => true, 'message' => 'Прибор успешно создан!', 'device_id' => $device_id]);

} else {
     // Если device_id не был получен после вставки в device_identification (что маловероятно при успешном execute, но как запасной вариант)
     echo json_encode(['success' => false, 'error' => 'Не удалось получить ID созданного прибора.']);
}

$conn->close();
?>
