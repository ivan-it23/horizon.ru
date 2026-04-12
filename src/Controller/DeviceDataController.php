<?php
namespace App\Controller;

use App\Controller\BaseController;
use App\Controller\DeviceHistoryController;

class DeviceDataController extends BaseController
{
    /**
     * Create a new device
     */
    public function create(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для создания приборов (5 приборов в час)
        if (!$this->checkRateLimit($this->getClientIP(), 'device_create', 5, 3600)) {
            $this->jsonResponse(['success' => false, 'error' => 'Слишком много попыток создания приборов. Попробуйте через час.'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Неверный метод запроса'], 405);
        }

        // Create DB connection
        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация и очистка входных данных
        $device_type       = $this->sanitizeString($_POST['device_type'] ?? '');
        $nominal_diameter  = $this->sanitizeString($_POST['nominal_diameter'] ?? '');
        $transmitter_count = $this->sanitizeString($_POST['transmitter_count'] ?? '');
        $serial_number     = $this->sanitizeString($_POST['serial_number'] ?? '');
        $case_number       = $this->sanitizeString($_POST['case_number'] ?? '');

        if (empty($serial_number) || empty($case_number)) {
            $this->jsonResponse(['success' => false, 'error' => 'Серийный номер и номер корпуса обязательны.'], 400);
        }

        // Валидация обязательных полей
        if (empty($device_type)) {
            $this->jsonResponse(['success' => false, 'error' => 'Тип прибора обязателен.'], 400);
        }

        // Проверка уникальности серийного номера
        try {
            $stmt = $conn->prepare('SELECT id FROM device_identification WHERE serial_number = ?');
            $stmt->bind_param('s', $serial_number);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $this->logSecurityEvent('device_duplicate_serial_attempt', [
                    'user_id' => $_SESSION['user']['id'],
                    'serial_number' => $serial_number
                ]);
                $this->jsonResponse(['success' => false, 'error' => 'Прибор с таким серийным номером уже существует в базе данных. Серийный номер должен быть уникальным.'], 409);
            }
            $stmt->close();
        } catch (\Exception $e) {
            $this->logSecurityEvent('device_duplicate_check_error', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'serial_number' => $serial_number
            ]);
            $this->handleError('Ошибка проверки уникальности серийного номера: ' . $e->getMessage(), [], 500);
        }

        // Additional fields - безопасная очистка
        $length_mm            = $this->sanitizeString($_POST['length_mm'] ?? '');
        $diameter_mm          = $this->sanitizeString($_POST['diameter_mm'] ?? '');
        $upper_thread         = $this->sanitizeString($_POST['upper_thread'] ?? '');
        $lower_thread         = $this->sanitizeString($_POST['lower_thread'] ?? '');
        $frequency1_khz       = $this->sanitizeString($_POST['frequency1_khz'] ?? '');
        $frequency2_khz       = $this->sanitizeString($_POST['frequency2_khz'] ?? '');
        $transmitter_base1_mm = $this->sanitizeString($_POST['transmitter_base1_mm'] ?? '');
        $transmitter_base2_mm = $this->sanitizeString($_POST['transmitter_base2_mm'] ?? '');
        $transmitter_base3_mm = $this->sanitizeString($_POST['transmitter_base3_mm'] ?? '');
        $transmitter_base4_mm = $this->sanitizeString($_POST['transmitter_base4_mm'] ?? '');
        $transmitter_base5_mm = $this->sanitizeString($_POST['transmitter_base5_mm'] ?? '');
        $receiver_base1_mm    = $this->sanitizeString($_POST['receiver_base1_mm'] ?? '');
        $receiver_base2_mm    = $this->sanitizeString($_POST['receiver_base2_mm'] ?? '');
        $receiver_base3_mm    = $this->sanitizeString($_POST['receiver_base3_mm'] ?? '');
        $record_date          = $this->sanitizeString($_POST['record_date'] ?? '');
        $additional_info      = $this->sanitizeString($_POST['additional_info'] ?? '', 1000);
        $creator_lastname     = isset($_SESSION['user'])
            ? trim($_SESSION['user']['lastname'] . ' ' . $_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['middlename'])
            : 'Неизвестный пользователь';

        // Insert into device_identification
        $sql = "INSERT INTO device_identification (
            device_type, nominal_diameter, transmitter_count,
            serial_number, case_number, length_mm, diameter_mm,
            upper_thread, lower_thread, frequency1_khz, frequency2_khz,
            transmitter_base1_mm, transmitter_base2_mm, transmitter_base3_mm,
            transmitter_base4_mm, transmitter_base5_mm,
            receiver_base1_mm, receiver_base2_mm, receiver_base3_mm,
            record_date, creator_lastname, additional_info
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $conn->prepare($sql);
            $types = str_repeat('s', 21 + 1); // 21 string params + device_type
            $stmt->bind_param(
                $types,
                $device_type,
                $nominal_diameter,
                $transmitter_count,
                $serial_number,
                $case_number,
                $length_mm,
                $diameter_mm,
                $upper_thread,
                $lower_thread,
                $frequency1_khz,
                $frequency2_khz,
                $transmitter_base1_mm,
                $transmitter_base2_mm,
                $transmitter_base3_mm,
                $transmitter_base4_mm,
                $transmitter_base5_mm,
                $receiver_base1_mm,
                $receiver_base2_mm,
                $receiver_base3_mm,
                $record_date,
                $creator_lastname,
                $additional_info
            );
            $stmt->execute();
            $deviceId = $conn->insert_id;
            $stmt->close();
            
            // Логируем успешное создание прибора
            $this->logSecurityEvent('device_created', [
                'user_id' => $_SESSION['user']['id'],
                'device_id' => $deviceId,
                'serial_number' => $serial_number,
                'case_number' => $case_number,
                'device_type' => $device_type
            ]);
            
        } catch (\Exception $e) {
            $this->logSecurityEvent('device_create_error', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'serial_number' => $serial_number,
                'case_number' => $case_number
            ]);
            
            $this->handleError('Ошибка создания прибора: ' . $e->getMessage(), [
                'method' => 'create',
                'serial_number' => $serial_number,
                'case_number' => $case_number
            ], 500);
        }

        // Build composition_data - безопасная обработка
        $composition_data = [];
        
        // Контроллер
        $controller_type = $this->sanitizeString($_POST['controller_type'] ?? '');
        $controller_version = $this->sanitizeString($_POST['controller_version'] ?? '');
        $controller_serial = $this->sanitizeString($_POST['controller_serial'] ?? '');
        if (!empty($controller_type) || !empty($controller_version) || !empty($controller_serial)) {
            $composition_data[] = [
                'board_name'    => 'Контроллер',
                'type'          => $controller_type,
                'version'       => $controller_version,
                'serial_number' => $controller_serial
            ];
        }
        
        // Инклинометр
        $inclinometer_type = $this->sanitizeString($_POST['inclinometer_type'] ?? '');
        $inclinometer_version = $this->sanitizeString($_POST['inclinometer_version'] ?? '');
        $inclinometer_serial = $this->sanitizeString($_POST['inclinometer_serial'] ?? '');
        if (!empty($inclinometer_type) || !empty($inclinometer_version) || !empty($inclinometer_serial)) {
            $composition_data[] = [
                'board_name'    => 'Инклинометр',
                'type'          => $inclinometer_type,
                'version'       => $inclinometer_version,
                'serial_number' => $inclinometer_serial
            ];
        }
        
        // Приёмники
        for ($i = 1; isset($_POST["receiver_{$i}_type"]); $i++) {
            $receiver_type = $this->sanitizeString($_POST["receiver_{$i}_type"] ?? '');
            $receiver_version = $this->sanitizeString($_POST["receiver_{$i}_version"] ?? '');
            $receiver_serial = $this->sanitizeString($_POST["receiver_{$i}_serial"] ?? '');
            if (!empty($receiver_type) || !empty($receiver_version) || !empty($receiver_serial)) {
                $composition_data[] = [
                    'board_name'    => "Приёмник {$i}",
                    'type'          => $receiver_type,
                    'version'       => $receiver_version,
                    'serial_number' => $receiver_serial
                ];
            }
        }
        
        // Передатчики
        for ($i = 1; isset($_POST["transmitter_{$i}_type"]); $i++) {
            $transmitter_type = $this->sanitizeString($_POST["transmitter_{$i}_type"] ?? '');
            $transmitter_version = $this->sanitizeString($_POST["transmitter_{$i}_version"] ?? '');
            $transmitter_serial = $this->sanitizeString($_POST["transmitter_{$i}_serial"] ?? '');
            if (!empty($transmitter_type) || !empty($transmitter_version) || !empty($transmitter_serial)) {
                $composition_data[] = [
                    'board_name'    => "Передатчик {$i}",
                    'type'          => $transmitter_type,
                    'version'       => $transmitter_version,
                    'serial_number' => $transmitter_serial
                ];
            }
        }
        
        // Другие платы
        if (isset($_POST['other_board_name']) && is_array($_POST['other_board_name'])) {
            $others = $_POST['other_board_name'];
            $typesArr = $_POST['other_board_type'] ?? [];
            $versArr  = $_POST['other_board_version'] ?? [];
            $serArr   = $_POST['other_board_serial'] ?? [];
            foreach ($others as $idx => $name) {
                $nm = $this->sanitizeString((string)$name);
                $tp = $this->sanitizeString($typesArr[$idx] ?? '');
                $vr = $this->sanitizeString($versArr[$idx] ?? '');
                $sr = $this->sanitizeString($serArr[$idx] ?? '');
                if (!empty($nm) || !empty($tp) || !empty($vr) || !empty($sr)) {
                    $composition_data[] = ['board_name'=>$nm, 'type'=>$tp, 'version'=>$vr, 'serial_number'=>$sr];
                }
            }
        }
        // Insert composition_data
        if (!empty($composition_data)) {
            $stmtComp = $conn->prepare('INSERT INTO device_composition (device_id, board_name, type, version, serial_number) VALUES (?, ?, ?, ?, ?)');
            foreach ($composition_data as $row) {
                $stmtComp->bind_param('issss', $deviceId, $row['board_name'], $row['type'], $row['version'], $row['serial_number']);
                $stmtComp->execute();
            }
            $stmtComp->close();
        }

        // Success response
        $this->jsonResponse(['success' => true, 'device_id' => $deviceId]);
        $conn->close();
    }

    /**
     * Update an existing device
     */
    public function update(): void
    {
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для обновления приборов (10 обновлений в час)
        if (!$this->checkRateLimit($this->getClientIP(), 'device_update', 10, 3600)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много попыток обновления приборов. Попробуйте через час.'], 429);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный метод запроса'], 405);
        }
        
        // Подключаем файл с настройками подключения к БД
        require_once __DIR__ . '/../../db_connection.php';

        // Получаем JSON данные из тела запроса
        $json_data = file_get_contents('php://input');
        // Декодируем JSON в ассоциативный массив PHP
        $data = json_decode($json_data, true);

        // Валидация JSON данных
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logSecurityEvent('device_update_invalid_json', [
                'user_id' => $_SESSION['user']['id'],
                'json_error' => json_last_error_msg()
            ]);
            $this->jsonResponse(['success' => false, 'message' => 'Некорректные JSON данные'], 400);
        }

        // Проверяем, авторизован ли пользователь (дублирующая проверка для совместимости)
        if (!isset($_SESSION['user']['id'])) {
            $this->jsonResponse(['success' => false, 'message' => "Ошибка: Пользователь не авторизован."], 401);
        }

        // Получаем данные текущего пользователя из сессии
        $current_user_id = (int)$_SESSION['user']['id'];
        $current_user_name = isset($_SESSION['user']['lastname']) ? 
            trim($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : 
            'Неизвестный пользователь';

        // Проверяем, что данные получены, декодированы и содержат ID
        if ($data && isset($data['id'])) {
            $id = $this->validateInt($data['id'], 1); // Безопасная валидация ID
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'Некорректный ID прибора'], 400);
            }

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

            // --- Проверка уникальности serial_number при изменении ---
            if (isset($data['serial_number']) && $data['serial_number'] !== $old_data['serial_number']) {
                $new_serial_number = $this->sanitizeString($data['serial_number']);
                try {
                    $stmt_check = $conn->prepare('SELECT id FROM device_identification WHERE serial_number = ? AND id != ?');
                    $stmt_check->bind_param('si', $new_serial_number, $id);
                    $stmt_check->execute();
                    $stmt_check->store_result();
                    if ($stmt_check->num_rows > 0) {
                        $this->logSecurityEvent('device_update_duplicate_serial', [
                            'user_id' => $_SESSION['user']['id'],
                            'device_id' => $id,
                            'old_serial' => $old_data['serial_number'],
                            'new_serial' => $new_serial_number
                        ]);
                        echo json_encode(['success' => false, 'message' => 'Прибор с таким серийным номером уже существует. Серийный номер должен быть уникальным.']);
                        $conn->close();
                        exit;
                    }
                    $stmt_check->close();
                } catch (\Exception $e) {
                    $this->logSecurityEvent('device_update_serial_check_error', [
                        'user_id' => $_SESSION['user']['id'],
                        'device_id' => $id,
                        'error' => $e->getMessage(),
                        'new_serial' => $new_serial_number
                    ]);
                    echo json_encode(['success' => false, 'message' => 'Ошибка проверки уникальности серийного номера: ' . $e->getMessage()]);
                    $conn->close();
                    exit;
                }
            }

            // --- Обновление основной таблицы device_identification ---
            $sql_update_main = "UPDATE device_identification SET ";
            $updates = array();
            $params = [];
            $types = "";

            // Список разрешенных полей для обновления в device_identification
            $allowed_fields = [
                'device_type', 'nominal_diameter',
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
            if ($main_fields_to_update_count > 0 || (isset($data['creator_lastname']) || isset($data['changed_at']))) {
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

                $stmt_insert_history = $conn->prepare($sql_insert_history);
                $history_save_success = false;
                if ($stmt_insert_history === false) {
                    error_log("Ошибка подготовки запроса (история параметров): " . $conn->error);
                } else {
                    // Типы для bind_param: device_id(i), затем 21 строковый параметр (s)
                    $history_types = "isssssssssssssssssssss"; // 1 'i' + 21 's'

                    $stmt_insert_history->bind_param(
                        $history_types,
                        $old_data['id'],
                        $old_data['device_type'],
                        $old_data['nominal_diameter'],
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
                $composition_save_success = true;
                if (isset($data['composition']) && is_array($data['composition'])) {
                    $composition_save_success = false;

                    // Получаем текущий состав прибора для сохранения в историю
                    $sql_select_comp = "SELECT * FROM device_composition WHERE device_id = ?";
                    $stmt_select_comp = $conn->prepare($sql_select_comp);
                    if ($stmt_select_comp) {
                        $stmt_select_comp->bind_param("i", $id);
                        $stmt_select_comp->execute();
                        $result_comp = $stmt_select_comp->get_result();

                        // Сохраняем текущий состав в историю
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

                    // Удаляем текущий состав прибора
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

                    // Добавляем новый состав прибора
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

                // Обработка удаления конкретных плат, если такие данные пришли
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

                // Формируем сообщение об успехе с указанием возможных проблем
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

        // Закрываем соединение с БД
        if (isset($conn) && $conn instanceof \mysqli) {
            $conn->close();
        }
    }

    /**
     * Получение истории изменений прибора
     * @deprecated Используйте DeviceHistoryController::history() вместо этого метода
     */
    public function history(): void
    {
        // Делегируем выполнение новому контроллеру
        $historyController = new DeviceHistoryController();
        $historyController->history();
    }


} 