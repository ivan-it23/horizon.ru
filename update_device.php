<?php
session_start(); // Убедитесь, что сессия запущена для доступа к $_SESSION['user']
include 'db_connection.php'; // Подключаем файл с настройками подключения к БД

// Устанавливаем заголовок, чтобы клиент знал, что ответ в формате JSON
header('Content-Type: application/json');

// Получаем JSON данные из тела запроса
$json_data = file_get_contents('php://input');
// Декодируем JSON в ассоциативный массив PHP
$data = json_decode($json_data, true);

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'message' => "Ошибка: Пользователь не авторизован."]);
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    exit;
}

// Получаем данные текущего пользователя из сессии
$current_user_id = (int)$_SESSION['user']['id'];
$current_user_name = isset($_SESSION['user']['lastname']) ? trim($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : 'Неизвестный пользователь';

// Проверяем, что данные получены, декодированы и содержат ID
if ($data && isset($data['id'])) {
    $id = mysqli_real_escape_string($conn, $data['id']); // Экранируем ID для безопасности

    // --- Получаем текущие данные прибора ПЕРЕД обновлением для записи в историю ---
    $sql_select = "SELECT * FROM device_identification WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    if ($stmt_select === false) {
        echo json_encode(['success' => false, 'message' => "Ошибка подготовки запроса (выборка старых данных): " . $conn->error]);
        $conn->close();
        exit;
    }
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $old_data = $result_select->fetch_assoc();
    $stmt_select->close();

    if (!$old_data) {
        echo json_encode(['success' => false, 'message' => "Прибор с ID $id не найден."]);
        $conn->close();
        exit;
    }

    // --- Обновление основной таблицы device_identification ---
    $sql_update_main = "UPDATE device_identification SET ";
    $updates = array();
    $params = [];
    $types = "";

    // Список разрешенных полей для обновления в device_identification
    $allowed_fields = [
        'device_type', 'nominal_diameter', // nominal_diameter теперь включен
        'serial_number', 'case_number', 'length_mm', 'diameter_mm',
        'upper_thread', 'lower_thread', 'frequency1_khz', 'frequency2_khz',
        'receiver_base1_mm', 'receiver_base2_mm', 'receiver_base3_mm',
        'transmitter_base1_mm', 'transmitter_base2_mm', 'transmitter_base3_mm',
        'transmitter_base4_mm', 'transmitter_base5_mm',
        'record_date', 'additional_info'
    ];

    $main_fields_to_update_count = 0;
    foreach ($allowed_fields as $field) {
        if (array_key_exists($field, $data)) {
            $updates[] = "`$field` = ?";
            $value = $data[$field];
            $types .= "s";
            $params[] = ($value === null || $value === '') ? null : (string)$value;
            $main_fields_to_update_count++;
        }
    }

    $updates[] = "`creator_lastname` = ?";
    $types .= "s";
    $params[] = $current_user_name;

    $updates[] = "`changed_at` = NOW()";

    // Добавляем ID в конец параметров для WHERE
    $params[] = $id;
    $types .= "i";

    $main_update_success = false;
    $main_update_error = '';

    // Выполняем обновление, только если есть что обновлять или если обновляется хотя бы creator_lastname/changed_at
    if ($main_fields_to_update_count > 0 || (isset($data['creator_lastname']) || isset($data['changed_at']))) { // Проверяем, есть ли данные для обновления
        $sql_update_main .= implode(', ', $updates) . " WHERE id = ?";

        $stmt_update_main = $conn->prepare($sql_update_main);
        if ($stmt_update_main === false) {
            echo json_encode(['success' => false, 'message' => "Ошибка подготовки запроса (обновление): " . $conn->error]);
            $conn->close();
            exit;
        }

        $stmt_update_main->bind_param($types, ...$params);
        $main_update_success = $stmt_update_main->execute();
        $main_update_error = $stmt_update_main->error;
        $stmt_update_main->close();
    } else {
        // Если нет других полей для обновления, но ID прибора есть,
        // можно обновить только creator_lastname и changed_at (если это предполагается логикой)
        // Однако, текущая логика добавляет их всегда.
        // Если $main_fields_to_update_count == 0, значит, из $allowed_fields ничего не пришло.
        // Но $updates будет содержать creator_lastname и changed_at.
        // Этот 'else' блок может быть не нужен, если мы всегда хотим выполнить запрос,
        // даже если он обновляет только creator_lastname и changed_at.
        // Для безопасности, если $updates содержит только creator_lastname и changed_at,
        // выполним более простой запрос.
        if (count($updates) == 2) { // Только creator_lastname и changed_at
             $sql_simple_update = "UPDATE device_identification SET `creator_lastname` = ?, `changed_at` = NOW() WHERE id = ?";
             $stmt_simple_update = $conn->prepare($sql_simple_update);
             if ($stmt_simple_update) {
                 $stmt_simple_update->bind_param("si", $current_user_name, $id);
                 $main_update_success = $stmt_simple_update->execute();
                 $main_update_error = $stmt_simple_update->error;
                 $stmt_simple_update->close();
             } else {
                 $main_update_success = false;
                 $main_update_error = "Ошибка подготовки простого запроса: " . $conn->error;
             }
        } else {
            // Если и $main_fields_to_update_count == 0 и нет других данных, считаем, что нет ошибки, но и нечего обновлять.
            // История все равно запишется на основе $old_data.
            $main_update_success = true;
        }
    }


    if ($main_update_success) {
        // --- Сохраняем историю изменений параметров прибора (используя $old_data) ---
        $sql_insert_history = "INSERT INTO device_history (
            device_id, device_type, nominal_diameter, serial_number, case_number, length_mm, diameter_mm,
            upper_thread, lower_thread, frequency1_khz, frequency2_khz,
            receiver_base1_mm, receiver_base2_mm, receiver_base3_mm,
            transmitter_base1_mm, transmitter_base2_mm, transmitter_base3_mm,
            transmitter_base4_mm, transmitter_base5_mm,
            record_date, creator_lastname, additional_info, changed_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        // 22 знака ? перед NOW()

        $stmt_insert_history = $conn->prepare($sql_insert_history);
        $history_save_success = false;
        if ($stmt_insert_history === false) {
             error_log("Ошибка подготовки запроса (история параметров): " . $conn->error);
        } else {
            // Типы для bind_param: device_id(i), затем 21 строковый параметр (s)
            // (device_type, nominal_diameter, ..., record_date, current_user_name, additional_info)
            $history_types = "isssssssssssssssssssss"; // 1 'i' + 21 's' (ИСПРАВЛЕНО)

            $stmt_insert_history->bind_param(
                $history_types,
                $old_data['id'],
                $old_data['device_type'],
                $old_data['nominal_diameter'], // nominal_diameter теперь включен
                $old_data['serial_number'],
                $old_data['case_number'],
                $old_data['length_mm'],
                $old_data['diameter_mm'],
                $old_data['upper_thread'],
                $old_data['lower_thread'],
                $old_data['frequency1_khz'],
                $old_data['frequency2_khz'],
                $old_data['receiver_base1_mm'],
                $old_data['receiver_base2_mm'],
                $old_data['receiver_base3_mm'],
                $old_data['transmitter_base1_mm'],
                $old_data['transmitter_base2_mm'],
                $old_data['transmitter_base3_mm'],
                $old_data['transmitter_base4_mm'],
                $old_data['transmitter_base5_mm'],
                $old_data['record_date'],
                $current_user_name,
                $old_data['additional_info']
            );
            $history_save_success = $stmt_insert_history->execute();
            if (!$history_save_success) {
                 error_log("Ошибка выполнения запроса (история параметров): " . $stmt_insert_history->error . " | SQL: " . $sql_insert_history . " | Types: " . $history_types);
            }
            $stmt_insert_history->close();
        }

        // --- Обновление состава прибора (device_composition), если данные пришли ---
        // (Эта часть кода остается без изменений, так как она не затрагивалась вашим запросом)
        $composition_save_success = true;
        if (isset($data['composition']) && is_array($data['composition'])) {
            $composition_save_success = false;

            $sql_select_comp = "SELECT * FROM device_composition WHERE device_id = ?";
            $stmt_select_comp = $conn->prepare($sql_select_comp);
            if ($stmt_select_comp) {
                $stmt_select_comp->bind_param("i", $id);
                $stmt_select_comp->execute();
                $result_comp = $stmt_select_comp->get_result();

                $sql_insert_comp_hist = "INSERT INTO device_composition_history
                    (device_id, board_name, type, version, serial_number, creator_id, creator_name, changed_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt_comp_hist = $conn->prepare($sql_insert_comp_hist);

                if ($stmt_comp_hist) {
                    while ($row = $result_comp->fetch_assoc()) {
                        $stmt_comp_hist->bind_param("issssis",
                            $row['device_id'],
                            $row['board_name'],
                            $row['type'],
                            $row['version'],
                            $row['serial_number'],
                            $current_user_id,
                            $current_user_name
                        );
                        if (!$stmt_comp_hist->execute()) {
                             error_log("Ошибка сохранения истории состава для платы " . $row['board_name'] . ": " . $stmt_comp_hist->error);
                        }
                    }
                    $stmt_comp_hist->close();
                } else {
                     error_log("Ошибка подготовки запроса (история состава): " . $conn->error);
                }
                $stmt_select_comp->close();
            } else {
                 error_log("Ошибка подготовки запроса (выборка старого состава): " . $conn->error);
            }


            $sql_delete_comp = "DELETE FROM device_composition WHERE device_id = ?";
            $stmt_delete_comp = $conn->prepare($sql_delete_comp);
            if ($stmt_delete_comp) {
                $stmt_delete_comp->bind_param("i", $id);
                if (!$stmt_delete_comp->execute()) {
                    error_log("Ошибка удаления старого состава: " . $stmt_delete_comp->error);
                }
                $stmt_delete_comp->close();
            } else {
                 error_log("Ошибка подготовки запроса (удаление состава): " . $conn->error);
            }


            $sql_insert_comp = "INSERT INTO device_composition
                (device_id, board_name, type, version, serial_number, creator_id, creator_name)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert_comp = $conn->prepare($sql_insert_comp);
            if ($stmt_insert_comp === false) {
                error_log("Ошибка при подготовке запроса для обновления состава прибора: " . $conn->error);
                $composition_save_success = false;
            } else {
                $all_inserts_successful = true;
                foreach ($data['composition'] as $comp) {
                    $board_name = isset($comp['board_name']) ? $comp['board_name'] : null;
                    $type = isset($comp['type']) ? $comp['type'] : null;
                    $version = isset($comp['version']) ? $comp['version'] : null;
                    $serial_number = isset($comp['serial_number']) ? $comp['serial_number'] : null;

                    $stmt_insert_comp->bind_param("issssis",
                        $id,
                        $board_name,
                        $type,
                        $version,
                        $serial_number,
                        $current_user_id,
                        $current_user_name
                    );
                    if (!$stmt_insert_comp->execute()) {
                         error_log("Ошибка вставки нового состава для платы " . $board_name . ": " . $stmt_insert_comp->error);
                         $all_inserts_successful = false;
                    }
                }
                $stmt_insert_comp->close();
                $composition_save_success = $all_inserts_successful;
            }
        }

        if (isset($data['boardsToDelete']) && is_array($data['boardsToDelete']) && !empty($data['boardsToDelete'])) {
            $placeholders = implode(',', array_fill(0, count($data['boardsToDelete']), '?'));
            $sql_delete_specific_boards = "DELETE FROM device_composition WHERE device_id = ? AND board_name IN ($placeholders)";
            $stmt_delete_specific_boards = $conn->prepare($sql_delete_specific_boards);

            if ($stmt_delete_specific_boards) {
                $delete_types = 'i' . str_repeat('s', count($data['boardsToDelete']));
                $delete_params = array_merge([$id], $data['boardsToDelete']);

                $stmt_delete_specific_boards->bind_param($delete_types, ...$delete_params);
                if (!$stmt_delete_specific_boards->execute()) {
                    error_log("Ошибка удаления конкретных плат: " . $stmt_delete_specific_boards->error);
                }
                $stmt_delete_specific_boards->close();
            } else {
                error_log("Ошибка подготовки запроса (удаление конкретных плат): " . $conn->error);
            }
        }

        $message = "Данные прибора успешно обновлены!";
        if (!$history_save_success) {
            $message .= " (Ошибка сохранения истории параметров)";
        }
        if (isset($data['composition']) && !$composition_save_success) {
             $message .= " (Ошибка обновления состава прибора)";
        }

        echo json_encode(['success' => true, 'message' => $message]);

    } else {
        echo json_encode(['success' => false, 'message' => "Ошибка при обновлении данных прибора: " . $main_update_error]);
    }

} else {
    echo json_encode(['success' => false, 'message' => "Неверные данные для обновления или отсутствует ID."]);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
