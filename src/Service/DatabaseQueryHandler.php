<?php
namespace App\Service;

/**
 * Главный класс для обработки запросов к базе данных для ИИ-агента
 * Координирует работу всех модулей
 */
class DatabaseQueryHandler
{
    private $conn;
    private $queryAnalyzer;
    private $queryExecutor;
    private $deviceInfoManager;
    private $responseFormatter;
    private $contextManager;

    public function __construct($connection)
    {
        $this->conn = $connection;
        $this->queryAnalyzer = new QueryAnalyzer();
        $this->queryExecutor = new QueryExecutor($connection);
        $this->deviceInfoManager = new DeviceInfoManager($connection);
        $this->responseFormatter = new ResponseFormatter();
        $this->contextManager = new ContextManager();
    }

    /**
     * Анализирует запрос пользователя и определяет тип запроса
     */
    public function analyzeQuery($userQuery): array
    {
        return $this->queryAnalyzer->analyzeQuery($userQuery);
    }

    /**
     * Выполняет запрос к базе данных на основе анализа
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
            case 'count_all_devices':
            case 'count_repairs':
            case 'list_devices_by_type':
            case 'recent_repairs':
            case 'device_statistics':
                return $this->queryExecutor->executeQuery($queryAnalysis);

            case 'device_info_by_serial':
                $result = $this->deviceInfoManager->getDeviceInfoBySerial($queryAnalysis['serial_number']);
                if ($result['success']) {
                    $this->queryAnalyzer->setLastDevice($queryAnalysis['serial_number'], $result['device']['case_number']);
                    $this->contextManager->setContext($queryAnalysis['serial_number'], $result['device']['case_number']);
                    return $this->responseFormatter->formatDeviceInfo($result['device'], $result['composition'], $result['repairs']);
                }
                return $result;

            case 'device_info_by_case':
                $result = $this->deviceInfoManager->getDeviceInfoByCase($queryAnalysis['case_number']);
                if ($result['success']) {
                    $this->queryAnalyzer->setLastDevice($result['device']['serial_number'], $queryAnalysis['case_number']);
                    $this->contextManager->setContext($result['device']['serial_number'], $queryAnalysis['case_number']);
                    return $this->responseFormatter->formatDeviceInfo($result['device'], $result['composition'], $result['repairs']);
                }
                return $result;

            case 'full_device_info_by_serial':
                $result = $this->deviceInfoManager->getFullDeviceInfo($queryAnalysis['serial_number'], null);
                if ($result['success']) {
                    $this->queryAnalyzer->setLastDevice($queryAnalysis['serial_number'], $result['data']['device']['case_number']);
                    $this->contextManager->setContext($queryAnalysis['serial_number'], $result['data']['device']['case_number']);
                    return $this->responseFormatter->formatFullDeviceInfo($result['data']);
                }
                return $result;

            case 'full_device_info_by_case':
                $result = $this->deviceInfoManager->getFullDeviceInfo(null, $queryAnalysis['case_number']);
                if ($result['success']) {
                    $this->queryAnalyzer->setLastDevice($result['data']['device']['serial_number'], $queryAnalysis['case_number']);
                    $this->contextManager->setContext($result['data']['device']['serial_number'], $queryAnalysis['case_number']);
                    return $this->responseFormatter->formatFullDeviceInfo($result['data']);
                }
                return $result;

            default:
                return [
                    'success' => false,
                    'message' => 'Извините, я не понял ваш запрос. Пожалуйста, уточните, какую информацию вы ищете. Например: "Выдай информацию о приборе с серийным номером 123" или "Покажи полную информацию о приборе с номером корпуса ABC-123".'
                ];
        }
    }

    /**
     * Выполняет SQL-запрос, сгенерированный ИИ, и форматирует результат
     * 
     * @param string $sql SQL-запрос для выполнения
     * @return array Результат выполнения
     */
    public function executeNaturalLanguageQuery($sql): array
    {
        // Простая валидация: разрешаем только SELECT-запросы
        if (stripos(trim($sql), 'select') !== 0) {
            return ['success' => false, 'message' => 'Разрешены только запросы на чтение данных (SELECT).'];
        }

        try {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return ['success' => false, 'message' => 'Ошибка подготовки SQL-запроса: ' . $this->conn->error];
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return ['success' => true, 'message' => 'По вашему запросу ничего не найдено.'];
            }

            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Форматируем результат в виде таблицы Markdown
            $message = $this->responseFormatter->formatResultsAsMarkdownTable($rows);

            return ['success' => true, 'message' => $message];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка при выполнении SQL-запроса: ' . $e->getMessage()];
        }
    }

    /**
     * Извлекает из ответа ИИ серийный номер или номер корпуса и сохраняет в сессию
     * 
     * @param string $responseText Текст ответа от ИИ
     * @return bool True, если контекст был найден и сохранен, иначе false
     */
    public function extractAndSaveContext($responseText): bool
    {
        return $this->contextManager->extractAndSaveContext($responseText);
    }

    /**
     * Получает серийный номер последнего прибора
     */
    public function getLastDeviceSerialNumber(): ?string
    {
        return $this->queryAnalyzer->getLastDeviceSerialNumber();
    }

    /**
     * Получает номер корпуса последнего прибора
     */
    public function getLastDeviceCaseNumber(): ?string
    {
        return $this->queryAnalyzer->getLastDeviceCaseNumber();
    }

    /**
     * Устанавливает контекст последнего найденного прибора
     */
    public function setLastDevice($serialNumber, $caseNumber): void
    {
        $this->queryAnalyzer->setLastDevice($serialNumber, $caseNumber);
        $this->contextManager->setContext($serialNumber, $caseNumber);
    }

    /**
     * Получает текущий контекст устройства
     */
    public function getCurrentContext(): ?array
    {
        return $this->contextManager->getCurrentContext();
    }

    /**
     * Очищает текущий контекст
     */
    public function clearContext(): void
    {
        $this->contextManager->clearContext();
    }
} 