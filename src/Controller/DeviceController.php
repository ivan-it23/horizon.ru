<?php
namespace App\Controller;

use App\Controller\BaseController;

class DeviceController extends BaseController
{
    /**
     * Search devices by serial or case number (AJAX JSON response)
     */
    public function search(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        // Подключаем соединение и вспомогательные функции
        require_once __DIR__ . '/../../db_connection.php';

        $serialNumber = isset($_POST['serial_number']) ? trim($_POST['serial_number']) : null;
        $caseNumber   = isset($_POST['case_number'])   ? trim($_POST['case_number'])   : null;

        // Проверка параметров
        if (!$serialNumber && !$caseNumber) {
            echo json_encode(['success' => false, 'message' => 'Не указаны параметры поиска']);
            return;
        }

        // Строим запрос
        $sql = 'SELECT * FROM device_identification WHERE 1=1';
        $params = [];
        $types  = '';

        if ($serialNumber) {
            $sql       .= ' AND serial_number = ?';
            $params[]   = $serialNumber;
            $types     .= 's';
        }
        if ($caseNumber) {
            $sql       .= ' AND case_number = ?';
            $params[]   = $caseNumber;
            $types     .= 's';
        }

        try {
            // Получаем результаты через вспомогательную функцию
            $devices = fetchResults($conn, $sql, $params, $types);

            if (count($devices) > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Найдено приборов: ' . count($devices),
                    'data'    => $devices
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Прибор не найден'
                ]);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            error_log('DeviceController::search error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Внутренняя ошибка сервера']);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) {
                $conn->close();
            }
        }
    }

    /**
     * Filter devices by parameters (type, diameter, transmitter count) (AJAX JSON response)
     */
    public function filter(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../../db_connection.php';

        $deviceType       = isset($_POST['device_type'])       ? trim($_POST['device_type'])       : null;
        $nominalDiameter  = isset($_POST['nominal_diameter'])  ? trim($_POST['nominal_diameter'])  : null;
        $transmitterCount = isset($_POST['transmitter_count']) ? trim($_POST['transmitter_count']) : null;
        $deviceId         = isset($_POST['device_id'])         ? trim($_POST['device_id'])         : null;

        $sql    = 'SELECT * FROM device_identification WHERE 1=1';
        $params = [];
        $types  = '';

        if ($deviceId) {
            $sql       .= ' AND id = ?';
            $params[]   = (int)$deviceId;
            $types     .= 'i';
        } else {
            if ($deviceType) {
                $sql       .= ' AND device_type = ?';
                $params[]   = $deviceType;
                $types     .= 's';
            }
            if ($nominalDiameter) {
                $sql       .= ' AND nominal_diameter = ?';
                $params[]   = $nominalDiameter;
                $types     .= 's';
            }
            if ($transmitterCount) {
                $sql       .= ' AND transmitter_count = ?';
                $params[]   = $transmitterCount;
                $types     .= 's';
            }
        }

        try {
            $devices = fetchResults($conn, $sql, $params, $types);
            echo json_encode($devices);
        } catch (\Exception $e) {
            error_log('DeviceController::filter error: ' . $e->getMessage());
            echo json_encode([]);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) {
                $conn->close();
            }
        }
    }

    /**
     * Главная страница учёта приборов (AccountingOfDevices2)
     */
    public function index(): void
    {
        session_start();
        require_once __DIR__ . '/../../db_connection.php';

        // Подготовка данных пользователя и непрочитанных сообщений
        $unread_count = 0;
        $is_logged_in = isset($_SESSION['user']['id']);
        $user_info = $is_logged_in ? $_SESSION['user'] : null;
        if ($is_logged_in) {
            $currentUserId = (int)$_SESSION['user']['id'];
            try {
                $result = fetchResults($conn,
                    'SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0',
                    [$currentUserId], 'i'
                );
                $unread_count = isset($result[0]['count']) ? (int)$result[0]['count'] : 0;
            } catch (\Exception $e) {
                $unread_count = 0;
            }
        }
        if (isset($conn) && $conn instanceof \mysqli) {
            $conn->close();
        }

        // Рендерим шаблон views/device/index.php
        $this->render('device/index', ['user_info' => $user_info, 'unread_count' => $unread_count]);
    }

    /**
     * Get device composition (JSON)
     */
    public function composition(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../../db_connection.php';

        $deviceId = filter_input(INPUT_POST, 'device_id', FILTER_VALIDATE_INT);
        if (!$deviceId) {
            echo json_encode(['error' => 'Не указан идентификатор прибора']);
            return;
        }

        try {
            $stmt = $conn->prepare('SELECT * FROM device_composition WHERE device_id = ? ORDER BY id ASC');
            $stmt->bind_param('i', $deviceId);
            $stmt->execute();
            $result = $stmt->get_result();
            $data   = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($data);
            $stmt->close();
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        $conn->close();
    }

    /**
     * Get device history (parameters) (JSON)
     */
    public function history(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../../db_connection.php';

        $deviceId = filter_input(INPUT_POST, 'device_id', FILTER_VALIDATE_INT);
        if (!$deviceId) {
            echo json_encode([]);
            return;
        }

        try {
            $stmt = $conn->prepare('SELECT * FROM device_history WHERE device_id = ? ORDER BY changed_at DESC');
            $stmt->bind_param('i', $deviceId);
            $stmt->execute();
            $result  = $stmt->get_result();
            $history = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($history);
            $stmt->close();
        } catch (\Exception $e) {
            echo json_encode([]);
        }
        $conn->close();
    }

    /**
     * Export device data as Excel (XLS)
     */
    public function export(): void
    {
        session_start();
        require_once __DIR__ . '/../../db_connection.php';
        mysqli_set_charset($conn, 'utf8');

        $deviceId     = filter_input(INPUT_GET, 'device_id', FILTER_VALIDATE_INT);
        $serialNumber = mysqli_real_escape_string($conn, $_GET['serial_number'] ?? '');
        $caseNumber   = mysqli_real_escape_string($conn, $_GET['case_number']   ?? '');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="device_{$serialNumber}_{$caseNumber}.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Helper for generating HTML tables
        function generateTable(array $data, string $title): string {
            if (empty($data)) return '';
            $html = "<h2>{$title}</h2>";
            $html .= '<table border="1"><tr>';
            foreach (array_keys($data[0]) as $header) {
                $html .= '<th>' . htmlspecialchars((string)$header) . '</th>';
            }
            $html .= '</tr>';
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table><br>';
            return $html;
        }

        $output = "<html><meta charset='UTF-8'><body>";

        // 1. Device identification
        $stmt = $conn->prepare('SELECT * FROM device_identification WHERE id = ?');
        $stmt->bind_param('i', $deviceId);
        $stmt->execute();
        $result = $stmt->get_result();
        $device = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $output .= generateTable($device, 'Идентификационные данные');

        // 2. Device composition
        $stmt = $conn->prepare('SELECT * FROM device_composition WHERE device_id = ?');
        $stmt->bind_param('i', $deviceId);
        $stmt->execute();
        $result      = $stmt->get_result();
        $composition = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $output .= generateTable($composition, 'Состав прибора');

        // 3. Device history
        $stmt = $conn->prepare('SELECT * FROM device_history WHERE device_id = ?');
        $stmt->bind_param('i', $deviceId);
        $stmt->execute();
        $result  = $stmt->get_result();
        $history = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $output .= generateTable($history, 'История параметров прибора');

        // 4. Composition history
        $stmt = $conn->prepare('SELECT * FROM device_composition_history WHERE device_id = ?');
        $stmt->bind_param('i', $deviceId);
        $stmt->execute();
        $result          = $stmt->get_result();
        $compHistory     = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $output .= generateTable($compHistory, 'История состава прибора');

        // 5. Other lifecycle tables
        $tables = [
            'production_discontinuation' => 'Сведения о выходе из производства',
            'testing_data'              => 'Сведения об испытаниях',
            'repair_data'               => 'Сведения о ремонте',
            'inspection_data'           => 'Сведения о неразрушающем контроле',
            'termination_data'          => 'Сведения о прекращении эксплуатации',
            'additional_data'           => 'Дополнительные сведения'
        ];
        foreach ($tables as $table => $title) {
            $query  = "SELECT * FROM {$table} WHERE serial_number = '{$serialNumber}' AND case_number = '{$caseNumber}'";
            $result = $conn->query($query);
            $data   = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            $output .= generateTable($data, $title);
        }

        // 6. Допуски в зависимости от типа прибора
        $deviceType = $device[0]['device_type'] ?? '';
        $tolMap = [
            'Автономный' => ['tolerances_autonomous', 'Допуски (Автономный)'],
            'Картограф'  => ['tolerances_cartographer', 'Допуски (Картограф)'],
            'LWD'        => ['tolerances_lwd', 'Допуски (LWD)'],
        ];
        if (isset($tolMap[$deviceType])) {
            list($tolTable, $tolTitle) = $tolMap[$deviceType];
            $result = $conn->query("SELECT * FROM {$tolTable}");
            $data   = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            $output .= generateTable($data, $tolTitle);
        }
        $output .= '</body></html>';
        echo $output;
        $conn->close();
        exit;
    }
} 