<?php
namespace App\Controller;

use App\Controller\BaseController;

class DeviceHistoryController extends BaseController
{
    /**
     * Получение истории изменений прибора
     * Поддерживает два типа истории: параметры (parameters) и состав (composition)
     */
    public function history(): void
    {
        header('Content-Type: application/json');
        
        // Подключаем соединение с базой данных
        require_once __DIR__ . '/../../db_connection.php';

        $deviceId    = isset($_POST['device_id']) ? (int)$_POST['device_id'] : 0;
        $historyType = isset($_POST['history_type']) ? $_POST['history_type'] : 'parameters';

        if ($deviceId <= 0) {
            echo json_encode(['error' => 'Неверный ID прибора']);
            $conn->close();
            exit;
        }

        $final_history_output = [];

        if ($historyType === 'parameters') {
            // --- Поля для сравнения (добавлено поле additional_info) ---
            $fields_to_compare = [
                'device_type',
                'serial_number',
                'case_number',
                'length_mm',
                'diameter_mm',
                'upper_thread',
                'lower_thread',
                'frequency1_khz',
                'frequency2_khz',
                'receiver_base1_mm',
                'receiver_base2_mm',
                'receiver_base3_mm',
                'transmitter_base1_mm',
                'transmitter_base2_mm',
                'transmitter_base3_mm',
                'transmitter_base4_mm',
                'transmitter_base5_mm',
                'additional_info' // <-- новое поле
            ];

            // --- 1. Получаем текущие данные прибора ---
            $current_record_data = null;
            $sql_current = "
                SELECT
                    id AS device_id,
                    device_type,
                    serial_number,
                    case_number,
                    length_mm,
                    diameter_mm,
                    upper_thread,
                    lower_thread,
                    frequency1_khz,
                    frequency2_khz,
                    receiver_base1_mm,
                    receiver_base2_mm,
                    receiver_base3_mm,
                    transmitter_base1_mm,
                    transmitter_base2_mm,
                    transmitter_base3_mm,
                    transmitter_base4_mm,
                    transmitter_base5_mm,
                    record_date,
                    creator_lastname,
                    additional_info,                            -- включаем новое поле
                    COALESCE(changed_at, created_at) AS changed_at,
                    '1' AS is_current
                FROM device_identification
                WHERE id = ?
            ";
            $stmt_current = $conn->prepare($sql_current);
            if ($stmt_current) {
                $stmt_current->bind_param("i", $deviceId);
                if ($stmt_current->execute()) {
                    $result_current = $stmt_current->get_result();
                    if ($row_current = $result_current->fetch_assoc()) {
                        // Приводим даты к строковому формату
                        if (isset($row_current['changed_at'])) {
                            $row_current['changed_at'] = date('Y-m-d H:i:s', strtotime($row_current['changed_at']));
                        }
                        if (isset($row_current['record_date']) && $row_current['record_date']) {
                            $row_current['record_date'] = date('Y-m-d', strtotime($row_current['record_date']));
                        }
                        // Поле additional_info остаётся без изменений (текст)
                        $current_record_data = $row_current;
                    }
                }
                $stmt_current->close();
            } else {
                echo json_encode(['error' => 'Ошибка подготовки запроса для текущих параметров: ' . $conn->error]);
                $conn->close();
                exit;
            }

            // Если текущие данные успешно получены, добавляем в итоговый массив
            if ($current_record_data) {
                $final_history_output[] = $current_record_data;
            }

            // --- 2. Получаем все исторические записи параметров ---
            $all_historical_records = [];
            $sql_history = "
                SELECT
                    history_id,
                    device_id,
                    device_type,
                    serial_number,
                    case_number,
                    length_mm,
                    diameter_mm,
                    upper_thread,
                    lower_thread,
                    frequency1_khz,
                    frequency2_khz,
                    receiver_base1_mm,
                    receiver_base2_mm,
                    receiver_base3_mm,
                    transmitter_base1_mm,
                    transmitter_base2_mm,
                    transmitter_base3_mm,
                    transmitter_base4_mm,
                    transmitter_base5_mm,
                    record_date,
                    creator_lastname,
                    additional_info,                            -- включаем новое поле
                    changed_at,
                    '0' AS is_current
                FROM device_history
                WHERE device_id = ?
                ORDER BY changed_at DESC
            ";
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
                        // additional_info передаётся как есть (может быть null или текст)
                        $all_historical_records[] = $row_history;
                    }
                }
                $stmt_history->close();
            } else {
                echo json_encode(['error' => 'Ошибка подготовки запроса для истории параметров: ' . $conn->error]);
                $conn->close();
                exit;
            }

            // --- 3. Фильтрация исторических записей и вычисление diff_details ---
            $previous_record_to_compare_with = $current_record_data;

            foreach ($all_historical_records as $historical_record) {
                $is_different  = false;
                $diff_details = []; // Хранит предыдущие значения изменённых полей

                if ($previous_record_to_compare_with === null) {
                    // Если нет текущей записи для сравнения (редкий случай)
                    $is_different = true;
                } else {
                    // Сравниваем каждое поле (включая additional_info)
                    foreach ($fields_to_compare as $field) {
                        // Надёжное сравнение строк, обрабатывая возможный null
                        $val_hist = isset($historical_record[$field]) ? (string)$historical_record[$field] : "";
                        $val_prev = isset($previous_record_to_compare_with[$field]) ? (string)$previous_record_to_compare_with[$field] : "";

                        if ($val_hist !== $val_prev) {
                            $is_different              = true;
                            // Сохраняем предыдущее значение
                            $diff_details[$field] = $val_prev;
                        }
                    }
                }

                if ($is_different) {
                    // Добавляем diff_details в запись
                    $historical_record['diff_details'] = $diff_details;
                    $final_history_output[]            = $historical_record;
                    // Обновляем для следующего сравнения
                    $previous_record_to_compare_with = $historical_record;
                }
            }

        } elseif ($historyType === 'composition') {
            // --- Логика для истории состава ---
            $current_composition_version_data = null;
            $current_boards                    = [];
            $current_version_changed_at        = null;
            $current_version_creator_name      = null;
            $current_additional_info           = null;

            // Получаем мета-информацию последней версии (время, автор, additional_info)
            $sql_device_meta = "
                SELECT
                    COALESCE(di.changed_at, di.created_at) AS last_update_time,
                    di.creator_lastname,
                    di.additional_info
                FROM device_identification di
                WHERE di.id = ?
            ";
            $stmt_device_meta = $conn->prepare($sql_device_meta);
            if ($stmt_device_meta) {
                $stmt_device_meta->bind_param("i", $deviceId);
                if ($stmt_device_meta->execute()) {
                    $res_meta = $stmt_device_meta->get_result();
                    if ($row_meta = $res_meta->fetch_assoc()) {
                        $current_version_changed_at = $row_meta['last_update_time']
                            ? date('Y-m-d H:i:s', strtotime($row_meta['last_update_time']))
                            : date('Y-m-d H:i:s');
                        $current_version_creator_name = $row_meta['creator_lastname'];
                        $current_additional_info      = $row_meta['additional_info']; // новое поле
                    }
                }
                $stmt_device_meta->close();
            } else {
                $current_version_changed_at = date('Y-m-d H:i:s');
            }

            // Получаем текущий состав прибора
            $sql_current_comp = "
                SELECT
                    id,
                    device_id,
                    board_name,
                    type,
                    version,
                    serial_number,
                    creator_name,
                    created_at AS board_created_at
                FROM device_composition
                WHERE device_id = ?
            ";
            $stmt_current_comp = $conn->prepare($sql_current_comp);
            if ($stmt_current_comp === false) {
                echo json_encode(['error' => 'Ошибка подготовки запроса для текущего состава: ' . $conn->error]);
                $conn->close();
                exit;
            }
            $stmt_current_comp->bind_param("i", $deviceId);
            if ($stmt_current_comp->execute()) {
                $result_current_comp    = $stmt_current_comp->get_result();
                $first_board_for_creator = true;
                while ($row_board = $result_current_comp->fetch_assoc()) {
                    if ($first_board_for_creator && !empty($row_board['creator_name'])) {
                        // При необходимости можно переопределить $current_version_creator_name
                        $first_board_for_creator = false;
                    }
                    $current_boards[] = $row_board;
                }
            }
            $stmt_current_comp->close();

            if (!empty($current_boards)) {
                $current_composition_version_data = [
                    'changed_at'      => $current_version_changed_at,
                    'creator_name'    => $current_version_creator_name,
                    'records'         => $current_boards,
                    'is_current_version' => true,
                    'additional_info' => $current_additional_info // добавляем сюда
                ];
                $final_history_output[] = $current_composition_version_data;
            }

            // Получаем все исторические версии состава
            $historical_versions = [];
            $sql_history_comp    = "
                SELECT
                    id,
                    device_id,
                    board_name,
                    type,
                    version,
                    serial_number,
                    creator_name,
                    changed_at
                FROM device_composition_history
                WHERE device_id = ?
                ORDER BY changed_at DESC, id DESC
            ";
            $stmt_history_comp = $conn->prepare($sql_history_comp);
            if ($stmt_history_comp === false) {
                echo json_encode(['error' => 'Ошибка подготовки запроса для истории состава: ' . $conn->error]);
                $conn->close();
                exit;
            }
            $stmt_history_comp->bind_param("i", $deviceId);
            if ($stmt_history_comp->execute()) {
                $result_history_comp  = $stmt_history_comp->get_result();
                $temp_history_boards = [];
                while ($row_comp_history = $result_history_comp->fetch_assoc()) {
                    if (isset($row_comp_history['changed_at'])) {
                        $row_comp_history['changed_at'] = date('Y-m-d H:i:s', strtotime($row_comp_history['changed_at']));
                    }
                    $temp_history_boards[] = $row_comp_history;
                }

                // Группировка по версии (changed_at + creator_name)
                $grouped_by_version = [];
                foreach ($temp_history_boards as $board_record) {
                    $version_key = $board_record['changed_at'] . '_' . $board_record['creator_name'];
                    if (!isset($grouped_by_version[$version_key])) {
                        $grouped_by_version[$version_key] = [
                            'changed_at'        => $board_record['changed_at'],
                            'creator_name'      => $board_record['creator_name'],
                            'records'           => [],
                            'is_current_version' => false,
                            'additional_info'   => null // Инициализируем, будет заполнено ниже
                        ];

                        // --- УЧАСТОК, КУДА БЫЛИ ВНЕСЕНЫ ИЗМЕНЕНИЯ (извлечение additional_info для истории состава) ---
                        // Пытаемся найти additional_info из device_history, которое было активно
                        // в момент или непосредственно перед changed_at этой версии состава.
                        // Сортируем по changed_at DESC и берем первую запись.
                        $sql_get_additional_info = "
                            SELECT additional_info
                            FROM device_history
                            WHERE device_id = ? AND changed_at <= ?
                            ORDER BY changed_at DESC
                            LIMIT 1
                        ";
                        $stmt_get_ai = $conn->prepare($sql_get_additional_info);
                        if ($stmt_get_ai) {
                            $stmt_get_ai->bind_param("is", $deviceId, $board_record['changed_at']);
                            if ($stmt_get_ai->execute()) {
                                $res_get_ai = $stmt_get_ai->get_result();
                                if ($row_get_ai = $res_get_ai->fetch_assoc()) {
                                    $grouped_by_version[$version_key]['additional_info'] = $row_get_ai['additional_info'];
                                }
                            }
                            $stmt_get_ai->close();
                        }
                        // --- КОНЕЦ УЧАСТКА ИЗМЕНЕНИЙ ---
                    }
                    $grouped_by_version[$version_key]['records'][] = $board_record;
                }
                $historical_versions = array_values($grouped_by_version);

            }
            $stmt_history_comp->close();

            // Сравнение версий и вычисление diff_details для состава
            $previous_composition_to_compare_with = $current_composition_version_data;
            foreach ($historical_versions as $idx => $historical_version) {
                $is_different_version = false;
                $diff_details = [ // Инициализируем diff_details
                    'added'   => [],
                    'removed' => [],
                    'changed' => []
                ];

                $current_boards_map = [];
                if ($previous_composition_to_compare_with && isset($previous_composition_to_compare_with['records'])) {
                    foreach ($previous_composition_to_compare_with['records'] as $board) {
                        $current_boards_map[$board['board_name']] = $board;
                    }
                }

                $historical_boards_map = [];
                foreach ($historical_version['records'] as $board) {
                    $historical_boards_map[$board['board_name']] = $board;
                }

                // Проверяем добавленные и изменённые платы
                foreach ($historical_boards_map as $board_name => $h_board) {
                    if (!isset($current_boards_map[$board_name])) {
                        // Плата добавлена (присутствует в старой версии, отсутствует в новой)
                        $diff_details['added'][] = $h_board;
                        $is_different_version   = true;
                    } else {
                        // Плата есть в обеих версиях — сравниваем поля
                        $c_board  = $current_boards_map[$board_name];
                        $board_diff = [];
                        $fields_to_compare_comp = ['type', 'version', 'serial_number'];
                        foreach ($fields_to_compare_comp as $field) {
                            $h_val = isset($h_board[$field]) ? (string)$h_board[$field] : "";
                            $c_val = isset($c_board[$field]) ? (string)$c_board[$field] : "";
                            if ($h_val !== $c_val) {
                                $board_diff[$field] = $c_val; // Сохраняем предыдущее значение
                                $is_different_version = true;
                            }
                        }
                        if (!empty($board_diff)) {
                            $diff_details['changed'][] = [
                                'board_name'      => $board_name,
                                'current_values'  => $h_board,
                                'previous_values' => $board_diff
                            ];
                        }
                    }
                }

                // Проверяем удалённые платы
                foreach ($current_boards_map as $board_name => $c_board) {
                    if (!isset($historical_boards_map[$board_name])) {
                        // Плата удалена (присутствовала в новой версии, отсутствует в старой)
                        $diff_details['removed'][] = $c_board;
                        $is_different_version     = true;
                    }
                }

                // --- УЧАСТОК, КУДА БЫЛИ ВНЕСЕНЫ ИЗМЕНЕНИЯ (сравнение additional_info для истории состава) ---
                $val_hist_ai = isset($historical_version['additional_info']) ? (string)$historical_version['additional_info'] : "";
                $val_prev_ai = isset($previous_composition_to_compare_with['additional_info']) ? (string)$previous_composition_to_compare_with['additional_info'] : "";

                if ($val_hist_ai !== $val_prev_ai) {
                    $is_different_version = true;
                    // Сохраняем предыдущее (более новое) значение additional_info как "старое" для текущей исторической записи
                    $diff_details['additional_info'] = $val_prev_ai;
                }
                // --- КОНЕЦ УЧАСТКА ИЗМЕНЕНИЙ ---

                // Присваиваем diff_details только если есть хоть какие-то изменения
                if ($is_different_version) {
                    $historical_versions[$idx]['diff_details'] = $diff_details;
                    $final_history_output[]                    = $historical_versions[$idx]; // Добавляем в общий выходной массив
                    // Обновляем предыдущую запись для следующего сравнения
                    $previous_composition_to_compare_with = $historical_versions[$idx];
                } else {
                    // Если изменений нет, все равно обновляем "предыдущую" запись для сравнения с следующей исторической
                    $previous_composition_to_compare_with = $historical_versions[$idx];
                }
            }

            // Здесь уже $final_history_output сформирован в цикле foreach,
            // он содержит текущую версию + отфильтрованные исторические.
            // Просто сортируем его по changed_at в убывающем порядке, чтобы текущая была первой.
            usort($final_history_output, function($a, $b) {
                $timeA = strtotime($a['changed_at']);
                $timeB = strtotime($b['changed_at']);
                return $timeB - $timeA; // Сортировка по убыванию даты
            });
        }

        $conn->close();
        echo json_encode($final_history_output);
    }
}
