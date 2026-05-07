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

            case 'repair_analysis':
                return $this->getRepairAnalysis();

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
            $sql = "SELECT t.serial_number, t.case_number, t.created_at, t.test_result, t.creator,
                           d.device_type
                    FROM testing_data t
                    LEFT JOIN device_identification d ON t.serial_number = d.serial_number
                    ORDER BY t.created_at DESC
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
                $testDate = $test['created_at'] ? date('d.m.Y', strtotime($test['created_at'])) : 'Не указана';
                $result = $test['test_result'] ?: 'Результат не указан';
                $message .= "• Прибор {$test['serial_number']} ({$test['device_type']}, корпус {$test['case_number']})\n";
                $message .= "  Дата испытания: {$testDate}\n";
                $message .= "  Результат: {$result}\n";
                $message .= "  Ответственный: {$test['creator']}\n\n";
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

    /**
     * Аналитический отчёт по ремонтам:
     *  - общая сводка (всего ремонтов, в ремонте сейчас, диапазон дат, среднее время);
     *  - распределение по типам приборов с COUNT(r.id), AVG(DATEDIFF), MIN/MAX дат и
     *    уникальными ответственными;
     *  - топ-5 «проблемных» приборов по числу ремонтов;
     *  - топ-5 ответственных по числу зарегистрированных ремонтов.
     *
     * Связь repair_data <-> device_identification — строго по бизнес-ключу
     * (serial_number, case_number); device_id/device_type в repair_data НЕТ.
     */
    private function getRepairAnalysis(): array
    {
        try {
            // Общая сводка
            $sqlOverall = "
                SELECT
                    COUNT(*) AS total_repairs,
                    SUM(CASE WHEN release_date IS NULL OR release_date = '' THEN 1 ELSE 0 END) AS in_repair_now,
                    COUNT(DISTINCT CONCAT_WS('|', serial_number, case_number)) AS devices_with_repairs,
                    MIN(NULLIF(admission_date, '')) AS first_repair_date,
                    MAX(NULLIF(admission_date, '')) AS last_repair_date,
                    AVG(CASE
                        WHEN release_date IS NOT NULL AND release_date <> ''
                             AND admission_date IS NOT NULL AND admission_date <> ''
                        THEN DATEDIFF(release_date, admission_date)
                        ELSE NULL
                    END) AS avg_repair_days
                FROM repair_data
            ";
            $resultOverall = $this->conn->query($sqlOverall);
            if (!$resultOverall) {
                return [
                    'success' => false,
                    'message' => 'Ошибка при выполнении запроса (сводка): ' . $this->conn->error
                ];
            }
            $overall = $resultOverall->fetch_assoc() ?: [];

            // Общее количество приборов в БД (для сводки)
            $resultDevices = $this->conn->query("SELECT COUNT(*) AS total FROM device_identification");
            $totalDevices = $resultDevices ? (int)($resultDevices->fetch_assoc()['total'] ?? 0) : 0;

            // Распределение по типам приборов
            $sqlByType = "
                SELECT
                    di.device_type,
                    COUNT(DISTINCT di.id) AS device_count,
                    COUNT(r.id) AS repair_count,
                    SUM(CASE WHEN r.release_date IS NULL OR r.release_date = '' THEN 1 ELSE 0 END) AS in_repair_now,
                    AVG(CASE
                        WHEN r.release_date IS NOT NULL AND r.release_date <> ''
                             AND r.admission_date IS NOT NULL AND r.admission_date <> ''
                        THEN DATEDIFF(r.release_date, r.admission_date)
                        ELSE NULL
                    END) AS avg_repair_days,
                    MIN(NULLIF(r.admission_date, '')) AS first_repair_date,
                    MAX(NULLIF(r.admission_date, '')) AS last_repair_date
                FROM device_identification di
                LEFT JOIN repair_data r
                       ON r.serial_number = di.serial_number
                      AND r.case_number   = di.case_number
                GROUP BY di.device_type
                ORDER BY repair_count DESC, di.device_type
            ";
            $resultByType = $this->conn->query($sqlByType);
            if (!$resultByType) {
                return [
                    'success' => false,
                    'message' => 'Ошибка при выполнении запроса (по типам): ' . $this->conn->error
                ];
            }
            $byType = $resultByType->fetch_all(MYSQLI_ASSOC);

            // Топ «проблемных» приборов
            $sqlTopDevices = "
                SELECT
                    di.serial_number,
                    di.case_number,
                    di.device_type,
                    COUNT(r.id) AS repair_count
                FROM device_identification di
                INNER JOIN repair_data r
                        ON r.serial_number = di.serial_number
                       AND r.case_number   = di.case_number
                GROUP BY di.id, di.serial_number, di.case_number, di.device_type
                HAVING repair_count > 0
                ORDER BY repair_count DESC, di.serial_number
                LIMIT 5
            ";
            $resultTop = $this->conn->query($sqlTopDevices);
            $topDevices = $resultTop ? $resultTop->fetch_all(MYSQLI_ASSOC) : [];

            // Топ ответственных
            $sqlTopCreators = "
                SELECT
                    repair_creator,
                    COUNT(*) AS repair_count
                FROM repair_data
                WHERE repair_creator IS NOT NULL AND repair_creator <> ''
                GROUP BY repair_creator
                ORDER BY repair_count DESC, repair_creator
                LIMIT 5
            ";
            $resultCreators = $this->conn->query($sqlTopCreators);
            $topCreators = $resultCreators ? $resultCreators->fetch_all(MYSQLI_ASSOC) : [];

            $message = $this->formatRepairAnalysis($overall, $totalDevices, $byType, $topDevices, $topCreators);

            return [
                'success' => true,
                'overall'      => $overall,
                'total_devices' => $totalDevices,
                'by_type'      => $byType,
                'top_devices'  => $topDevices,
                'top_creators' => $topCreators,
                'message'      => $message
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при выполнении запроса: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Форматирует результат `getRepairAnalysis()` в Markdown
     * (подзаголовки `###`/`####`, маркированные списки, таблицы — без эмодзи).
     */
    private function formatRepairAnalysis(array $overall, int $totalDevices, array $byType, array $topDevices, array $topCreators): string
    {
        $totalRepairs   = (int)($overall['total_repairs']   ?? 0);
        $inRepairNow    = (int)($overall['in_repair_now']   ?? 0);
        $devicesRepaired = (int)($overall['devices_with_repairs'] ?? 0);
        $avgDays = $overall['avg_repair_days'] ?? null;

        $fmtDate = function ($value): string {
            if ($value === null || $value === '' || $value === '0000-00-00') {
                return '—';
            }
            $ts = strtotime($value);
            return $ts ? date('d.m.Y', $ts) : (string)$value;
        };
        $fmtAvg = function ($value): string {
            return ($value === null || $value === '') ? '—' : number_format((float)$value, 1, ',', ' ');
        };
        $fmtInt = function ($value): string {
            return (string)((int)$value);
        };

        $out = "### Анализ ремонтов\n\n";

        if ($totalRepairs === 0) {
            $out .= "В таблице `repair_data` нет записей о ремонтах. Анализ невозможен.\n";
            return $out;
        }

        $out .= "#### Общая сводка\n\n";
        $out .= "- Приборов в системе: {$totalDevices}\n";
        $out .= "- Зарегистрированных ремонтов: {$totalRepairs}\n";
        $out .= "- Уникальных приборов, побывавших в ремонте: {$devicesRepaired}\n";
        $out .= "- Сейчас находится в ремонте: {$inRepairNow}\n";
        $out .= "- Первый ремонт: " . $fmtDate($overall['first_repair_date'] ?? null) . "\n";
        $out .= "- Последний ремонт: " . $fmtDate($overall['last_repair_date'] ?? null) . "\n";
        $out .= "- Среднее время ремонта (дней): " . $fmtAvg($avgDays) . "\n\n";

        if (!empty($byType)) {
            $out .= "#### Распределение по типам приборов\n\n";
            $out .= "| Тип прибора | Приборов | Ремонтов | В ремонте сейчас | Среднее время (дн.) | Первый ремонт | Последний ремонт |\n";
            $out .= "|---|---|---|---|---|---|---|\n";
            foreach ($byType as $row) {
                $type = $row['device_type'] ?? '—';
                $out .= "| {$type} "
                     . "| " . $fmtInt($row['device_count']    ?? 0) . " "
                     . "| " . $fmtInt($row['repair_count']    ?? 0) . " "
                     . "| " . $fmtInt($row['in_repair_now']   ?? 0) . " "
                     . "| " . $fmtAvg($row['avg_repair_days'] ?? null) . " "
                     . "| " . $fmtDate($row['first_repair_date'] ?? null) . " "
                     . "| " . $fmtDate($row['last_repair_date']  ?? null) . " |\n";
            }
            $out .= "\n";
        }

        if (!empty($topDevices)) {
            $out .= "#### Топ приборов по числу ремонтов\n\n";
            foreach ($topDevices as $idx => $row) {
                $n = $idx + 1;
                $serial = $row['serial_number'] ?? '—';
                $case   = $row['case_number']   ?? '—';
                $type   = $row['device_type']   ?? '—';
                $count  = $fmtInt($row['repair_count'] ?? 0);
                $out .= "{$n}. Прибор {$serial} (корпус {$case}, {$type}) — {$count} ремонт(ов).\n";
            }
            $out .= "\n";
        }

        if (!empty($topCreators)) {
            $out .= "#### Топ ответственных за ремонты\n\n";
            foreach ($topCreators as $row) {
                $creator = $row['repair_creator'] ?? '—';
                $count = $fmtInt($row['repair_count'] ?? 0);
                $out .= "- {$creator} — {$count} ремонт(ов).\n";
            }
            $out .= "\n";
        }

        return rtrim($out) . "\n";
    }
}
