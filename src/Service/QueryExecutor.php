<?php
namespace App\Service;

/**
 * Класс для выполнения запросов к базе данных
 * Обрабатывает статистические запросы и списки
 */
class QueryExecutor
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    /**
     * Выполняет запрос к базе данных на основе анализа
     * 
     * @param array $queryAnalysis Результат анализа запроса
     * @return array Результат выполнения запроса
     */
    public function executeQuery($queryAnalysis): array
    {
        // Проверяем соединение с БД
        if (!$this->conn || $this->conn->connect_error) {
            return [
                'success' => false,
                'message' => 'Ошибка соединения с базой данных.'
            ];
        }

        switch ($queryAnalysis['type']) {
            case 'count_devices_by_type':
                return $this->countDevicesByType($queryAnalysis['device_type']);

            case 'count_all_devices':
                return $this->countAllDevices();

            case 'count_repairs':
                return $this->countRepairs();

            case 'list_devices_by_type':
                return $this->listDevicesByType($queryAnalysis['device_type']);

            case 'recent_repairs':
                return $this->getRecentRepairs();

            case 'last_repair':
                return $this->getLastRepair();

            case 'devices_in_repair':
                return $this->getDevicesInRepair();

            case 'devices_in_testing':
                return $this->getDevicesInTesting();

            case 'device_statistics':
                return $this->getDeviceStatistics();

            default:
                return [
                    'success' => false,
                    'message' => 'Извините, я не понял ваш запрос. Пожалуйста, уточните, какую информацию вы ищете. Например: "Выдай информацию о приборе с серийным номером 123" или "Покажи полную информацию о приборе с номером корпуса ABC-123".'
                ];
        }
    }

    /**
     * Подсчитывает количество приборов определенного типа
     */
    private function countDevicesByType($deviceType): array
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM device_identification WHERE device_type = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Ошибка подготовки запроса: ' . $this->conn->error
                ];
            }

            $stmt->bind_param('s', $deviceType);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            return [
                'success' => true,
                'count'   => (int)$row['count'],
                'device_type' => $deviceType,
                'message' => "В базе данных найдено {$row['count']} приборов типа {$deviceType}."
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении запроса: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Подсчитывает общее количество приборов
     */
    private function countAllDevices(): array
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM device_identification";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Ошибка подготовки запроса: ' . $this->conn->error
                ];
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            return [
                'success' => true,
                'count'   => (int)$row['count'],
                'message' => "Общее количество приборов в базе данных: {$row['count']}."
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении запроса: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Подсчитывает количество ремонтов
     */
    private function countRepairs(): array
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM repair_data";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Ошибка подготовки запроса: ' . $this->conn->error
                ];
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            return [
                'success' => true,
                'count'   => (int)$row['count'],
                'message' => "Общее количество ремонтов в базе данных: {$row['count']}."
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении запроса: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Получает список приборов определенного типа
     */
    private function listDevicesByType($deviceType): array
    {
        try {
            $sql = "SELECT serial_number, case_number, device_type, nominal_diameter 
                    FROM device_identification 
                    WHERE device_type = ? 
                    ORDER BY serial_number";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Ошибка подготовки запроса: ' . $this->conn->error
                ];
            }

            $stmt->bind_param('s', $deviceType);
            $stmt->execute();
            $result = $stmt->get_result();
            $devices = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (empty($devices)) {
                return [
                    'success' => false,
                    'message' => "Приборы типа {$deviceType} не найдены в базе данных."
                ];
            }

            $deviceList = '';
            foreach ($devices as $device) {
                $deviceList .= "• Серийный номер: {$device['serial_number']}, Номер корпуса: {$device['case_number']}, Диаметр: {$device['nominal_diameter']}\n";
            }

            return [
                'success' => true,
                'device_type' => $deviceType,
                'device_list' => $deviceList,
                'count' => count($devices),
                'message' => "Найдено " . count($devices) . " приборов типа {$deviceType}."
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении запроса: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Получает последние ремонты
     */
    private function getRecentRepairs($limit = 5): array
    {
        try {
            $sql = "SELECT serial_number, case_number, repair_reason, admission_date, release_date, repair_creator 
                    FROM repair_data 
                    ORDER BY admission_date DESC 
                    LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Ошибка подготовки запроса: ' . $this->conn->error
                ];
            }

            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $repairs = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (empty($repairs)) {
                return [
                    'success' => false,
                    'message' => "Ремонты не найдены в базе данных."
                ];
            }

            $repairsList = '';
            foreach ($repairs as $repair) {
                $admissionDate = $repair['admission_date'] ? date('d.m.Y', strtotime($repair['admission_date'])) : 'Не указана';
                $releaseDate = $repair['release_date'] ? date('d.m.Y', strtotime($repair['release_date'])) : 'Не указана';
                $repairsList .= "**Прибор {$repair['serial_number']} (корпус {$repair['case_number']})**\n";
                $repairsList .= "   Причина ремонта: {$repair['repair_reason']}\n";
                $repairsList .= "   Дата поступления: {$admissionDate}\n";
                $repairsList .= "   Дата выпуска: {$releaseDate}\n";
                $repairsList .= "   Ответственный: {$repair['repair_creator']}\n\n";
            }

            return [
                'success' => true,
                'repairs_list' => $repairsList,
                'count' => count($repairs),
                'message' => "Найдено " . count($repairs) . " последних ремонтов."
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении запроса: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Получает статистику приборов
     */
    private function getDeviceStatistics(): array
    {
        try {
            // Общее количество приборов
            $sqlTotal = "SELECT COUNT(*) as total FROM device_identification";
            $stmtTotal = $this->conn->prepare($sqlTotal);
            $stmtTotal->execute();
            $resultTotal = $stmtTotal->get_result();
            $totalDevices = $resultTotal->fetch_assoc()['total'];
            $stmtTotal->close();

            // Количество по типам
            $sqlTypes = "SELECT device_type, COUNT(*) as count FROM device_identification GROUP BY device_type";
            $stmtTypes = $this->conn->prepare($sqlTypes);
            $stmtTypes->execute();
            $resultTypes = $stmtTypes->get_result();
            $typeCounts = [];
            while ($row = $resultTypes->fetch_assoc()) {
                $typeCounts[$row['device_type']] = $row['count'];
            }
            $stmtTypes->close();

            // Количество ремонтов
            $sqlRepairs = "SELECT COUNT(*) as count FROM repair_data";
            $stmtRepairs = $this->conn->prepare($sqlRepairs);
            $stmtRepairs->execute();
            $resultRepairs = $stmtRepairs->get_result();
            $totalRepairs = $resultRepairs->fetch_assoc()['count'];
            $stmtRepairs->close();

            return [
                'success' => true,
                'total_devices' => (int)$totalDevices,
                'count_LWD' => (int)($typeCounts['LWD'] ?? 0),
                'count_Cartographer' => (int)($typeCounts['Картограф'] ?? 0),
                'count_Autonomous' => (int)($typeCounts['Автономный'] ?? 0),
                'count_repairs' => (int)$totalRepairs,
                'message' => "Статистика приборов успешно получена."
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении запроса: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Получает самый последний ремонт
     */
    private function getLastRepair(): array
    {
        try {
            $sql = "SELECT serial_number, case_number, repair_reason, failure_reason, admission_date, release_date, actions_description, repair_creator 
                    FROM repair_data 
                    ORDER BY admission_date DESC 
                    LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Ошибка подготовки запроса: ' . $this->conn->error
                ];
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $repair = $result->fetch_assoc();
            $stmt->close();

            if (!$repair) {
                return [
                    'success' => false,
                    'message' => "В базе данных нет записей о ремонтах."
                ];
            }

            $admissionDate = $repair['admission_date'] ? date('d.m.Y', strtotime($repair['admission_date'])) : 'Не указана';
            $releaseDate = $repair['release_date'] ? date('d.m.Y', strtotime($repair['release_date'])) : 'Ещё в ремонте';

            $message = "**Последний ремонт в системе:**\n\n";
            $message .= "**Прибор:** серийный номер {$repair['serial_number']}, корпус {$repair['case_number']}\n";
            $message .= "**Причина ремонта:** {$repair['repair_reason']}\n";
            $message .= "**Причина неисправности:** {$repair['failure_reason']}\n";
            $message .= "**Дата поступления:** {$admissionDate}\n";
            $message .= "**Дата выпуска:** {$releaseDate}\n";
            $message .= "**Выполненные действия:** {$repair['actions_description']}\n";
            $message .= "**Ответственный:** {$repair['repair_creator']}";

            return [
                'success' => true,
                'repair' => $repair,
                'message' => $message
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении запроса: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Получает приборы, находящиеся в ремонте (без даты выпуска)
     */
    private function getDevicesInRepair(): array
    {
        try {
            $sql = "SELECT serial_number, case_number, repair_reason, admission_date, repair_creator 
                    FROM repair_data 
                    WHERE release_date IS NULL OR release_date = ''
                    ORDER BY admission_date DESC";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Ошибка подготовки запроса: ' . $this->conn->error
                ];
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $repairs = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (empty($repairs)) {
                return [
                    'success' => true,
                    'count' => 0,
                    'message' => "Сейчас нет приборов в ремонте. Все приборы выпущены."
                ];
            }

            $message = "**Приборы в ремонте (всего " . count($repairs) . "):**\n\n";
            foreach ($repairs as $repair) {
                $admissionDate = $repair['admission_date'] ? date('d.m.Y', strtotime($repair['admission_date'])) : 'Не указана';
                $message .= "• Прибор {$repair['serial_number']} (корпус {$repair['case_number']})\n";
                $message .= "  Причина: {$repair['repair_reason']}\n";
                $message .= "  Поступил: {$admissionDate}\n";
                $message .= "  Ответственный: {$repair['repair_creator']}\n\n";
            }

            return [
                'success' => true,
                'count' => count($repairs),
                'repairs' => $repairs,
                'message' => $message
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении запроса: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Получает приборы на испытаниях (последние записи без результата)
     */
    private function getDevicesInTesting(): array
    {
        try {
            $sql = "SELECT t.serial_number, t.case_number, t.test_date, t.test_result, t.creator_surname,
                           d.device_type
                    FROM testing_data t
                    LEFT JOIN device_identification d ON t.serial_number = d.serial_number
                    ORDER BY t.test_date DESC
                    LIMIT 10";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Ошибка подготовки запроса: ' . $this->conn->error
                ];
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $tests = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (empty($tests)) {
                return [
                    'success' => false,
                    'message' => "В базе данных нет записей об испытаниях."
                ];
            }

            $message = "**Последние испытания приборов:**\n\n";
            foreach ($tests as $test) {
                $testDate = $test['test_date'] ? date('d.m.Y', strtotime($test['test_date'])) : 'Не указана';
                $result = $test['test_result'] ?: 'Результат не указан';
                $message .= "• Прибор {$test['serial_number']} ({$test['device_type']}, корпус {$test['case_number']})\n";
                $message .= "  Дата испытания: {$testDate}\n";
                $message .= "  Результат: {$result}\n";
                $message .= "  Ответственный: {$test['creator_surname']}\n\n";
            }

            return [
                'success' => true,
                'count' => count($tests),
                'tests' => $tests,
                'message' => $message
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении запроса: ' . $e->getMessage()
            ];
        }
    }
}