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
    $conn->close();
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

    // Если старые данные не найдены, выходим
    if (!$old_data) {
        echo json_encode(['success' => false, 'message' => "Прибор с ID $id не найден."]);
        $conn->close();
        exit;
    }

    // --- Обновление основной таблицы device_identification ---
    $sql_update_main = "UPDATE device_identification SET ";
    $updates = array();
    $params = []; // Массив для параметров bind_param
    $types = "";  // Строка типов для bind_param

    // Список разрешенных полей для обновления в device_identification
    $allowed_fields = [
        'device_type', 'serial_number', 'case_number', 'length_mm', 'diameter_mm',
        'upper_thread', 'lower_thread', 'frequency1_khz', 'frequency2_khz',
        'receiver_base1_mm', 'receiver_base2_mm', 'receiver_base3_mm',
        'transmitter_base1_mm', 'transmitter_base2_mm', 'transmitter_base3_mm',
        'transmitter_base4_mm', 'transmitter_base5_mm',
        'record_date' // creator_lastname больше не обновляем здесь, он берется из сессии
    ];

    foreach ($allowed_fields as $field) {
        if (array_key_exists($field, $data)) {
            $updates[] = "`$field` = ?";
            $value = $data[$field];

            if (strpos($field, '_mm') !== false || strpos($field, '_khz') !== false || strpos($field, 'transmitter_base') !== false || strpos($field, 'receiver_base') !== false) {
                 if ($value === null || $value === '') { // Проверяем и пустую строку
                     $types .= "s"; // Используем 's' для NULL
                     $params[] = null;
                 } else {
                     $types .= "d";
                     $params[] = (float)$value;
                 }
            } elseif ($field === 'record_date') {
                $types .= "s";
                $params[] = ($value === null || $value === '') ? null : $value;
            } else {
                $types .= "s";
                $params[] = ($value === null) ? null : (string)$value;
            }
        }
    }

    // Добавляем обновление creator_lastname и changed_at в конец
    // Обновляем создателя записи на текущего пользователя сессии
    $updates[] = "`creator_lastname` = ?";
    $types .= "s";
    $params[] = $current_user_name; // Используем имя из сессии

    $updates[] = "`changed_at` = NOW()";

    // Собираем SQL запрос
    $sql_update_main .= implode(', ', $updates) . " WHERE id = ?";
    $types .= "i"; // Добавляем тип 'i' для ID в WHERE
    $params[] = $id; // Добавляем ID в массив параметров

    // Подготавливаем и выполняем запрос на обновление основной таблицы
    $stmt_update_main = $conn->prepare($sql_update_main);
    if ($stmt_update_main === false) {
        echo json_encode(['success' => false, 'message' => "Ошибка подготовки запроса (обновление): " . $conn->error]);
        $conn->close();
        exit;
    }

    $stmt_update_main->bind_param($types, ...$params);

    // Выполняем обновление основной таблицы
    $main_update_success = $stmt_update_main->execute();
    $main_update_error = $stmt_update_main->error; // Сохраняем ошибку, если она есть
    $stmt_update_main->close(); // Закрываем основной statement обновления

    if ($main_update_success) {
        // --- Сохраняем историю изменений параметров прибора (используя $old_data) ---
        // Создателем записи истории будет пользователь, который ВНОСИЛ изменения ($current_user_name)
        $sql_insert_history = "INSERT INTO device_history (
            device_id, device_type, serial_number, case_number, length_mm, diameter_mm,
            upper_thread, lower_thread, frequency1_khz, frequency2_khz,
            receiver_base1_mm, receiver_base2_mm, receiver_base3_mm,
            transmitter_base1_mm, transmitter_base2_mm, transmitter_base3_mm,
            transmitter_base4_mm, transmitter_base5_mm,
            record_date, creator_lastname, changed_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt_insert_history = $conn->prepare($sql_insert_history);
        $history_save_success = false; // Инициализируем как false
        if ($stmt_insert_history === false) {
             error_log("Ошибка подготовки запроса (история параметров): " . $conn->error);
        } else {
            // Типы для bind_param истории
            $history_types = "issssssssssssdddddss"; // Проверьте типы!

            // Передаем значения из $old_data, но creator_lastname берем из сессии ($current_user_name)
            $stmt_insert_history->bind_param(
                $history_types,
                $old_data['id'],
                $old_data['device_type'],
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
                $current_user_name // Используем имя текущего пользователя сессии
            );
            $history_save_success = $stmt_insert_history->execute();
            if (!$history_save_success) {
                 error_log("Ошибка выполнения запроса (история параметров): " . $stmt_insert_history->error);
            }
            $stmt_insert_history->close();
        }


        // --- Обновление состава прибора (device_composition), если данные пришли ---
        $composition_save_success = true; // По умолчанию считаем успешным, если данных не было
        if (isset($data['composition']) && is_array($data['composition'])) {
            $composition_save_success = false; // Сбрасываем флаг, т.к. нужно выполнить операции

             // 1. Сохраняем текущий состав прибора в таблицу device_composition_history
             // Создателем записи истории будет пользователь из сессии ($current_user_id, $current_user_name)
            $sql_select_comp = "SELECT * FROM device_composition WHERE device_id = ?";
            $stmt_select_comp = $conn->prepare($sql_select_comp);
            if ($stmt_select_comp) {
                $stmt_select_comp->bind_param("i", $id);
                $stmt_select_comp->execute();
                $result_comp = $stmt_select_comp->get_result();

                // Используем данные пользователя из сессии для записи истории
                $sql_insert_comp_hist = "INSERT INTO device_composition_history
                    (device_id, board_name, type, version, serial_number, creator_id, creator_name, changed_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt_comp_hist = $conn->prepare($sql_insert_comp_hist);

                if ($stmt_comp_hist) {
                    while ($row = $result_comp->fetch_assoc()) {
                        $stmt_comp_hist->bind_param("issssis", // Добавили i и s для creator_id и creator_name
                            $row['device_id'],
                            $row['board_name'],
                            $row['type'],
                            $row['version'],
                            $row['serial_number'],
                            $current_user_id,    // ID пользователя из сессии
                            $current_user_name   // Имя пользователя из сессии
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


            // 2. Удаляем старый состав прибора из device_composition
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


            // 3. Вставляем новый состав прибора в device_composition
            // Создателем новых записей будет пользователь из сессии
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

                    $stmt_insert_comp->bind_param("issssis", // Добавили i и s для creator_id и creator_name
                        $id,
                        $board_name,
                        $type,
                        $version,
                        $serial_number,
                        $current_user_id,    // ID пользователя из сессии
                        $current_user_name   // Имя пользователя из сессии
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
        // --- Конец логики для состава прибора ---

        // Формируем сообщение об успехе
        $message = "Данные прибора успешно обновлены!";
        if (!$history_save_success) {
            $message .= " (Ошибка сохранения истории параметров)";
        }
        if (isset($data['composition']) && !$composition_save_success) {
             $message .= " (Ошибка обновления состава прибора)";
        }

        echo json_encode(['success' => true, 'message' => $message]);

    } else {
        // Ошибка при обновлении основной таблицы
        echo json_encode(['success' => false, 'message' => "Ошибка при обновлении данных прибора: " . $main_update_error]);
    }

} else {
    // Ошибка: неверные данные или отсутствует ID
    echo json_encode(['success' => false, 'message' => "Неверные данные для обновления или отсутствует ID."]);
}

$conn->close(); // Закрываем соединение с БД
?>
