<?php
include 'db_connection.php';

header('Content-Type: application/json');

$deviceId = isset($_POST['device_id']) ? (int)$_POST['device_id'] : 0;
$historyType = isset($_POST['history_type']) ? $_POST['history_type'] : 'parameters';

if ($deviceId <= 0) {
    echo json_encode(['error' => 'Неверный ID прибора']);
    $conn->close();
    exit;
}

$final_history_output = [];

if ($historyType === 'parameters') {
    // Поля для сравнения (исключая history_id, device_id, record_date, creator_lastname, changed_at)
    $fields_to_compare = [
        'device_type', 'serial_number', 'case_number', 'length_mm', 'diameter_mm',
        'upper_thread', 'lower_thread', 'frequency1_khz', 'frequency2_khz',
        'receiver_base1_mm', 'receiver_base2_mm', 'receiver_base3_mm',
        'transmitter_base1_mm', 'transmitter_base2_mm', 'transmitter_base3_mm',
        'transmitter_base4_mm', 'transmitter_base5_mm'
        // 'record_date' ИСКЛЮЧЕНО из сравнения для фильтрации записей
    ];

    // 1. Получаем текущие данные прибора
    $current_record_data = null;
    $sql_current = "SELECT
                        id as device_id, device_type, serial_number, case_number, length_mm, diameter_mm,
                        upper_thread, lower_thread, frequency1_khz, frequency2_khz,
                        receiver_base1_mm, receiver_base2_mm, receiver_base3_mm,
                        transmitter_base1_mm, transmitter_base2_mm, transmitter_base3_mm,
                        transmitter_base4_mm, transmitter_base5_mm,
                        record_date, creator_lastname,
                        COALESCE(changed_at, created_at) as changed_at,
                        '1' as is_current
                    FROM device_identification
                    WHERE id = ?";
    $stmt_current = $conn->prepare($sql_current);
    if ($stmt_current) {
        $stmt_current->bind_param("i", $deviceId);
        if ($stmt_current->execute()) {
            $result_current = $stmt_current->get_result();
            if ($row_current = $result_current->fetch_assoc()) {
                if (isset($row_current['changed_at'])) {
                    $row_current['changed_at'] = date('Y-m-d H:i:s', strtotime($row_current['changed_at']));
                }
                if (isset($row_current['record_date']) && $row_current['record_date']) {
                    $row_current['record_date'] = date('Y-m-d', strtotime($row_current['record_date']));
                }
                $current_record_data = $row_current;
            }
        }
        $stmt_current->close();
    } else {
        echo json_encode(['error' => 'Ошибка подготовки запроса для текущих параметров: ' . $conn->error]);
        $conn->close();
        exit;
    }

    // Если текущие данные есть, добавляем их в начало результата
    if ($current_record_data) {
        $final_history_output[] = $current_record_data;
    }

    // 2. Получаем все исторические записи, упорядоченные от новых к старым
    $all_historical_records = [];
    $sql_history = "SELECT
                        history_id, device_id, device_type, serial_number, case_number, length_mm, diameter_mm,
                        upper_thread, lower_thread, frequency1_khz, frequency2_khz,
                        receiver_base1_mm, receiver_base2_mm, receiver_base3_mm,
                        transmitter_base1_mm, transmitter_base2_mm, transmitter_base3_mm,
                        transmitter_base4_mm, transmitter_base5_mm,
                        record_date, creator_lastname, changed_at,
                        '0' as is_current
                    FROM device_history
                    WHERE device_id = ? ORDER BY changed_at DESC";
    $stmt_history = $conn->prepare($sql_history);
    if ($stmt_history) {
        $stmt_history->bind_param("i", $deviceId);
        if ($stmt_history->execute()) {
            $result_history = $stmt_history->get_result();
            while ($row_history = $result_history->fetch_assoc()) {
                if (isset($row_history['changed_at'])) {
                    $row_history['changed_at'] = date('Y-m-d H:i:s', strtotime($row_history['changed_at']));
                }
                if (isset($row_history['record_date']) && $row_history['record_date']) {
                    $row_history['record_date'] = date('Y-m-d', strtotime($row_history['record_date']));
                }
                $all_historical_records[] = $row_history;
            }
        }
        $stmt_history->close();
    } else {
        echo json_encode(['error' => 'Ошибка подготовки запроса для истории параметров: ' . $conn->error]);
        $conn->close();
        exit;
    }

    // 3. Фильтруем исторические записи и добавляем previous_value для измененных полей
    $previous_record_to_compare_with = $current_record_data;

    foreach ($all_historical_records as $historical_record) {
        $is_different = false;
        $diff_details = []; // Для хранения измененных полей и их предыдущих значений

        if ($previous_record_to_compare_with === null) {
            // Это самая первая историческая запись (самая новая из истории).
            // Если нет текущей записи для сравнения, считаем ее измененной.
            $is_different = true;
        } else {
            foreach ($fields_to_compare as $field) {
                // Преобразуем значения в строки для надежного сравнения, обрабатывая NULL
                $val_hist = isset($historical_record[$field]) ? (string)$historical_record[$field] : "";
                $val_prev = isset($previous_record_to_compare_with[$field]) ? (string)$previous_record_to_compare_with[$field] : "";

                if ($val_hist !== $val_prev) {
                    $is_different = true;
                    // Сохраняем предыдущее значение
                    $diff_details[$field] = $val_prev;
                }
            }
        }

        if ($is_different) {
            // Добавляем историческую запись
            $historical_record['diff_details'] = $diff_details; // Добавляем информацию об изменениях
            $final_history_output[] = $historical_record;
            $previous_record_to_compare_with = $historical_record; // Обновляем запись для следующего сравнения
        }
    }

} elseif ($historyType === 'composition') {
    // Логика для истории состава остается такой же, как в вашем исходном файле
    // так как она уже группирует изменения по версиям (changed_at, creator_name)
    // и клиентская часть затем сравнивает эти версии.

    $current_composition_version_data = null;
    $current_boards = [];
    $current_version_changed_at = null;
    $current_version_creator_name = null;

    $sql_device_meta = "SELECT COALESCE(di.changed_at, di.created_at) as last_update_time, di.creator_lastname
                        FROM device_identification di
                        WHERE di.id = ?";
    $stmt_device_meta = $conn->prepare($sql_device_meta);
    if ($stmt_device_meta) {
        $stmt_device_meta->bind_param("i", $deviceId);
        if ($stmt_device_meta->execute()) {
            $res_meta = $stmt_device_meta->get_result();
            if ($row_meta = $res_meta->fetch_assoc()) {
                $current_version_changed_at = $row_meta['last_update_time'] ? date('Y-m-d H:i:s', strtotime($row_meta['last_update_time'])) : date('Y-m-d H:i:s');
                $current_version_creator_name = $row_meta['creator_lastname']; // Изначально берем из device_identification
            }
        }
        $stmt_device_meta->close();
    } else {
         $current_version_changed_at = date('Y-m-d H:i:s');
    }


    $sql_current_comp = "SELECT
                                id, device_id, board_name, type, version, serial_number,
                                creator_name,
                                created_at as board_created_at
                           FROM device_composition
                           WHERE device_id = ?";
    $stmt_current_comp = $conn->prepare($sql_current_comp);
    if ($stmt_current_comp === false) {
        echo json_encode(['error' => 'Ошибка подготовки запроса для текущего состава: ' . $conn->error]);
        $conn->close();
        exit;
    }
    $stmt_current_comp->bind_param("i", $deviceId);
    if ($stmt_current_comp->execute()) {
        $result_current_comp = $stmt_current_comp->get_result();
        $first_board_for_creator = true;
        while ($row_board = $result_current_comp->fetch_assoc()) {
            // Если у плат есть свой creator_name, и он отличается от общего, можно его использовать
            // Для заголовка версии обычно используется общий автор изменения версии.
            if ($first_board_for_creator && !empty($row_board['creator_name'])) {
                // $current_version_creator_name = $row_board['creator_name']; // Можно переопределить, если нужно
                $first_board_for_creator = false;
            }
            $current_boards[] = $row_board;
        }
    }
    $stmt_current_comp->close();

    if (!empty($current_boards)) {
        $current_composition_version_data = [
            'changed_at' => $current_version_changed_at,
            'creator_name' => $current_version_creator_name,
            'records' => $current_boards,
            'is_current_version' => true
        ];
        $final_history_output[] = $current_composition_version_data;
    }

    // Fetch historical composition data
    $historical_versions = [];
    $sql_history_comp = "SELECT id, device_id, board_name, type, version, serial_number, creator_name, changed_at FROM device_composition_history WHERE device_id = ? ORDER BY changed_at DESC, id DESC"; // id DESC для стабильности порядка внутри одной временной метки
    $stmt_history_comp = $conn->prepare($sql_history_comp);
    if ($stmt_history_comp === false) {
        echo json_encode(['error' => 'Ошибка подготовки запроса для истории состава: ' . $conn->error]);
        $conn->close();
        exit;
    }
    $stmt_history_comp->bind_param("i", $deviceId);
    if ($stmt_history_comp->execute()) {
        $result_history_comp = $stmt_history_comp->get_result();
        $temp_history_boards = [];
        while ($row_comp_history = $result_history_comp->fetch_assoc()) {
            if (isset($row_comp_history['changed_at'])) {
                $row_comp_history['changed_at'] = date('Y-m-d H:i:s', strtotime($row_comp_history['changed_at']));
            }
            $temp_history_boards[] = $row_comp_history;
        }

        // Group historical records by changed_at and creator_name
        $grouped_by_version = [];
        foreach ($temp_history_boards as $board_record) {
            $version_key = $board_record['changed_at'] . '_' . $board_record['creator_name'];
            if (!isset($grouped_by_version[$version_key])) {
                $grouped_by_version[$version_key] = [
                    'changed_at' => $board_record['changed_at'],
                    'creator_name' => $board_record['creator_name'],
                    'records' => [],
                    'is_current_version' => false
                ];
            }
            $grouped_by_version[$version_key]['records'][] = $board_record;
        }
        $historical_versions = array_values($grouped_by_version); // Convert to indexed array
    }
    $stmt_history_comp->close();

    // Now, compare historical versions to filter out duplicates and add diff_details
    $previous_composition_to_compare_with = $current_composition_version_data;

    foreach ($historical_versions as $historical_version) {
        $is_different_version = false;
        $diff_boards = [
            'added'   => [], // Платы, которые были добавлены в этой версии
            'removed' => [], // Платы, которые были удалены в этой версии
            'changed' => []  // Платы, у которых изменились параметры
        ];

        $current_boards_map = [];
        if ($previous_composition_to_compare_with) {
            foreach ($previous_composition_to_compare_with['records'] as $board) {
                $current_boards_map[$board['board_name']] = $board;
            }
        }

        $historical_boards_map = [];
        foreach ($historical_version['records'] as $board) {
            $historical_boards_map[$board['board_name']] = $board;
        }

        // Check for added and changed boards
        foreach ($historical_boards_map as $board_name => $h_board) {
            if (!isset($current_boards_map[$board_name])) {
                // Board added in this historical version
                $diff_boards['added'][] = $h_board;
                $is_different_version = true;
            } else {
                // Board exists in both, check for changes
                $c_board = $current_boards_map[$board_name];
                $board_diff = [];
                $fields_to_compare_comp = ['type', 'version', 'serial_number'];
                foreach ($fields_to_compare_comp as $field) {
                    $h_val = isset($h_board[$field]) ? (string)$h_board[$field] : "";
                    $c_val = isset($c_board[$field]) ? (string)$c_board[$field] : "";
                    if ($h_val !== $c_val) {
                        $board_diff[$field] = $c_val; // Store the previous value
                        $is_different_version = true;
                    }
                }
                if (!empty($board_diff)) {
                    $diff_boards['changed'][] = [
                        'board_name' => $board_name,
                        'current_values' => $h_board, // Current values in this historical record
                        'previous_values' => $board_diff // Previous values (from the newer record)
                    ];
                }
            }
        }

        // Check for removed boards
        foreach ($current_boards_map as $board_name => $c_board) {
            if (!isset($historical_boards_map[$board_name])) {
                // Board removed in this historical version (was present in the newer version)
                $diff_boards['removed'][] = $c_board;
                $is_different_version = true;
            }
        }

        if ($is_different_version) {
            $historical_version['diff_details'] = $diff_boards;
            $final_history_output[] = $historical_version;
            $previous_composition_to_compare_with = $historical_version; // Update for next comparison
        }
    }
}

$conn->close();
echo json_encode($final_history_output);
?>