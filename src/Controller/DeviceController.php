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
        // Проверка авторизации
        $this->requireAuth();
        
        // Проверка метода запроса
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный метод запроса'], 405);
        }
        
        // Rate limiting для поиска
        if (!$this->checkRateLimit($this->getClientIP(), 'device_search', 20, 60)) {
            $this->jsonResponse(['success' => false, 'message' => 'Слишком много запросов поиска'], 429);
        }

        // Подключаем соединение и вспомогательные функции
        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация входных данных
        $serialNumber = isset($_POST['serial_number']) ? $this->sanitizeString($_POST['serial_number']) : null;
        $caseNumber   = isset($_POST['case_number'])   ? $this->sanitizeString($_POST['case_number'])   : null;

        // Проверка параметров
        if (!$serialNumber && !$caseNumber) {
            $this->jsonResponse(['success' => false, 'message' => 'Не указаны параметры поиска'], 400);
        }

        // Логируем поиск
        $this->logSecurityEvent('device_search', [
            'serial_number' => $serialNumber,
            'case_number' => $caseNumber,
            'user_id' => $_SESSION['user']['id']
        ]);

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
        // Проверка авторизации
        $this->requireAuth();
        
        // Проверка метода запроса
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Неверный метод запроса'], 405);
        }
        
        // Rate limiting для фильтрации
        if (!$this->checkRateLimit($this->getClientIP(), 'device_filter', 30, 60)) {
            $this->jsonResponse(['error' => 'Слишком много запросов фильтрации'], 429);
        }

        require_once __DIR__ . '/../../db_connection.php';

        // Безопасная валидация входных данных
        $deviceType       = isset($_POST['device_type'])       ? $this->sanitizeString($_POST['device_type'])       : null;
        $nominalDiameter  = isset($_POST['nominal_diameter'])  ? $this->sanitizeString($_POST['nominal_diameter'])  : null;
        $transmitterCount = isset($_POST['transmitter_count']) ? $this->sanitizeString($_POST['transmitter_count']) : null;
        $deviceId         = isset($_POST['device_id'])         ? $this->validateInt($_POST['device_id'], 1)         : null;

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
            
            // Логируем успешную фильтрацию
            $this->logSecurityEvent('device_filter_success', [
                'user_id' => $_SESSION['user']['id'],
                'device_type' => $deviceType,
                'nominal_diameter' => $nominalDiameter,
                'transmitter_count' => $transmitterCount,
                'device_id' => $deviceId,
                'results_count' => count($devices)
            ]);
            
            $this->jsonResponse($devices);
        } catch (\Exception $e) {
            $this->logSecurityEvent('device_filter_error', [
                'user_id' => $_SESSION['user']['id'],
                'error' => $e->getMessage(),
                'device_type' => $deviceType,
                'nominal_diameter' => $nominalDiameter,
                'transmitter_count' => $transmitterCount
            ]);
            
            $this->handleError('Ошибка при фильтрации приборов: ' . $e->getMessage(), [
                'method' => 'filter'
            ], 500);
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
        // Проверка авторизации
        $this->requireAuth();
        
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
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting
        if (!$this->checkRateLimit($this->getClientIP(), 'device_composition', 50, 60)) {
            $this->jsonResponse(['error' => 'Слишком много запросов состава'], 429);
        }

        require_once __DIR__ . '/../../db_connection.php';

        $deviceId = $this->validateInt($_POST['device_id'] ?? 0, 1);
        if (!$deviceId) {
            $this->jsonResponse(['error' => 'Не указан идентификатор прибора']);
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
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting
        if (!$this->checkRateLimit($this->getClientIP(), 'device_history', 50, 60)) {
            $this->jsonResponse(['error' => 'Слишком много запросов истории'], 429);
        }

        require_once __DIR__ . '/../../db_connection.php';

        $deviceId = $this->validateInt($_POST['device_id'] ?? 0, 1);
        if (!$deviceId) {
            $this->jsonResponse([]);
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
        // Проверка авторизации
        $this->requireAuth();
        
        // Rate limiting для экспорта (не чаще 5 раз в минуту)
        if (!$this->checkRateLimit($this->getClientIP(), 'device_export', 5, 60)) {
            $this->handleError('Слишком много запросов экспорта. Попробуйте через минуту.', [], 429);
        }

        require_once __DIR__ . '/../../db_connection.php';
        mysqli_set_charset($conn, 'utf8');

        // Безопасная валидация входных данных
        $deviceId     = $this->validateInt($_GET['device_id'] ?? 0, 1);
        $serialNumber = $this->sanitizeString($_GET['serial_number'] ?? '');
        $caseNumber   = $this->sanitizeString($_GET['case_number'] ?? '');

        if (!$deviceId || !$serialNumber || !$caseNumber) {
            $this->handleError('Некорректные параметры для экспорта', [], 400);
        }

        // Логируем экспорт
        $this->logSecurityEvent('device_export', [
            'device_id' => $deviceId,
            'serial_number' => $serialNumber,
            'case_number' => $caseNumber,
            'user_id' => $_SESSION['user']['id']
        ]);

        // Безопасное имя файла
        $safeFileName = 'device_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $serialNumber) . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $caseNumber) . '.xls';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $safeFileName . '"');
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

        // 5. Other lifecycle tables - безопасные запросы
        $allowedTables = [
            'production_discontinuation' => 'Сведения о выходе из производства',
            'testing_data'              => 'Сведения об испытаниях',
            'repair_data'               => 'Сведения о ремонте',
            'inspection_data'           => 'Сведения о неразрушающем контроле',
            'termination_data'          => 'Сведения о прекращении эксплуатации',
            'additional_data'           => 'Дополнительные сведения'
        ];
        
        foreach ($allowedTables as $table => $title) {
            try {
                $stmt = $conn->prepare("SELECT * FROM `{$table}` WHERE serial_number = ? AND case_number = ?");
                $stmt->bind_param('ss', $serialNumber, $caseNumber);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                $output .= generateTable($data, $title);
            } catch (\Exception $e) {
                // Логируем ошибку, но продолжаем экспорт
                $this->logSecurityEvent('export_table_error', [
                    'table' => $table,
                    'error' => $e->getMessage(),
                    'device_id' => $deviceId
                ]);
            }
        }

        // 6. Допуски в зависимости от типа прибора - безопасные запросы
        $deviceType = $device[0]['device_type'] ?? '';
        $allowedToleranceTables = [
            'Автономный' => ['tolerances_autonomous', 'Допуски (Автономный)'],
            'Картограф'  => ['tolerances_cartographer', 'Допуски (Картограф)'],
            'LWD'        => ['tolerances_lwd', 'Допуски (LWD)'],
        ];
        
        if (isset($allowedToleranceTables[$deviceType])) {
            list($tolTable, $tolTitle) = $allowedToleranceTables[$deviceType];
            try {
                $stmt = $conn->prepare("SELECT * FROM `{$tolTable}`");
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                $output .= generateTable($data, $tolTitle);
            } catch (\Exception $e) {
                $this->logSecurityEvent('export_tolerance_error', [
                    'table' => $tolTable,
                    'error' => $e->getMessage(),
                    'device_type' => $deviceType
                ]);
            }
        }
        $output .= '</body></html>';
        echo $output;
        $conn->close();
        exit;
    }
} 